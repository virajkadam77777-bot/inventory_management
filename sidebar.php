<?php
// ==============================================
// Sidebar Component - Standalone File
// File: sidebar.php (ADMIN PANEL)
// ==============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==============================================
// HELPER FUNCTION - Get User Initials
// ==============================================
function getUserInitials($name) {
    if (empty($name)) return 'U';
    
    $name = trim($name);
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    
    return substr($initials, 0, 2);
}

// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get user profile data
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Administrator');
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Admin';

// Determine which chat page to link to based on role
// For admin panel, always use admin_chat.php
$chat_page = 'admin_chat.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Sidebar Styles - Professional Design */
        :root {
            --sidebar-bg: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            --sidebar-hover: #2dd4bf;
            --sidebar-active: #2dd4bf;
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-width: 280px;
            --header-height: 70px;
            --footer-height: 160px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Sidebar Container - FIXED SCROLLING */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            overflow: hidden;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        /* Sidebar Logo Area - Fixed at top */
        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            flex-shrink: 0;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #2dd4bf, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            font-size: 22px;
            color: white;
        }

        .logo-text h3 {
            color: white;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .logo-text p {
            font-size: 11px;
            color: #2dd4bf;
            margin-top: 2px;
        }

        /* Navigation Menu - SCROLLABLE AREA */
        .nav-menu-wrapper {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 16px 10px 16px;
        }

        /* Custom scrollbar for nav menu */
        .nav-menu-wrapper::-webkit-scrollbar {
            width: 4px;
        }

        .nav-menu-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav-menu-wrapper::-webkit-scrollbar-thumb {
            background: #2dd4bf;
            border-radius: 4px;
        }

        .nav-menu-wrapper::-webkit-scrollbar-thumb:hover {
            background: #14b8a6;
        }

        .nav-menu {
            padding-bottom: 10px;
        }

        .nav-item {
            margin-bottom: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 16px;
            border-radius: 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-link i {
            width: 22px;
            font-size: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-link span {
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(45, 212, 191, 0.1);
            color: var(--sidebar-text-hover);
        }

        .nav-link.active {
            background: rgba(45, 212, 191, 0.15);
            color: var(--sidebar-active);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: -16px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 30px;
            background: #2dd4bf;
            border-radius: 0 4px 4px 0;
        }

        /* Chat badge in sidebar */
        .nav-link .chat-badge-sidebar {
            margin-left: auto;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
            display: none;
            flex-shrink: 0;
        }

        /* Section Divider */
        .nav-divider {
            padding: 16px 16px 8px 16px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            font-weight: 600;
        }

        /* User Profile in Sidebar - Fixed at bottom */
        .sidebar-footer {
            flex-shrink: 0;
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(10px);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .user-avatar .avatar-fallback {
            font-size: 15px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-details h5 {
            color: white;
            font-size: 13px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-details p {
            font-size: 11px;
            color: #94a3b8;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Logout Button in Sidebar */
        .logout-sidebar {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .logout-sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            color: #ef4444;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-size: 13px;
        }

        .logout-sidebar a:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .logout-sidebar a i {
            width: 22px;
            font-size: 16px;
            text-align: center;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1200;
            background: #2dd4bf;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            color: white;
            font-size: 20px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(45, 212, 191, 0.4);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(45, 212, 191, 0.5);
        }

        /* Responsive - Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1100;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .mobile-menu-btn {
                display: flex !important;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="mainSidebar">
        <!-- Logo - Fixed at top -->
        <div class="sidebar-logo">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="logo-text">
                    <h3>StockMaster Pro</h3>
                    <p>Inventory Management System</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu - Scrollable -->
        <div class="nav-menu-wrapper">
            <nav class="nav-menu">
                <div class="nav-divider">MAIN NAVIGATION</div>
                
                <!-- Dashboard -->
                <div class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Upload Data -->
                <div class="nav-item">
                    <a href="upload_data.php" class="nav-link <?php echo $current_page == 'upload_data1.php' ? 'active' : ''; ?>">
                        <i class="fas fa-upload"></i>
                        <span>Upload Data</span>
                    </a>
                </div>

                <!-- Manage Data -->
                <div class="nav-item">
                    <a href="manage_data.php" class="nav-link <?php echo $current_page == 'manage_data.php' ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i>
                        <span>Manage Data</span>
                    </a>
                </div>

                <div class="nav-divider">AGENCY MANAGEMENT</div>

                <!-- Add Agency -->
                <div class="nav-item">
                    <a href="add_client.php" class="nav-link <?php echo $current_page == 'add_agency.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Add Agency</span>
                    </a>
                </div>

                <!-- Add User -->
                <div class="nav-item">
                    <a href="add_user.php" class="nav-link <?php echo $current_page == 'add_user.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Add User</span>
                    </a>
                </div>

                <!-- Manage Agency -->
                <div class="nav-item">
                    <a href="manage_agency.php" class="nav-link <?php echo $current_page == 'manage_agency.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Manage Agency</span>
                    </a>
                </div>

                <!-- ========== FINANCE SECTION ========== -->
                <div class="nav-divider">FINANCE</div>

                <!-- Invoices -->
                <div class="nav-item">
                    <a href="invoice_viewer.php" class="nav-link <?php echo $current_page == 'invoice_viewer.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i>
                        <span>Invoices</span>
                    </a>
                </div>

                <!-- Quotation Bill -->
                <div class="nav-item">
                    <a href="qutation_bill.php" class="nav-link <?php echo $current_page == 'qutation_bill.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Quotation Bill</span>
                    </a>
                </div>

                <!-- Manage Bill (NEW) -->
                <div class="nav-item">
                    <a href="manage_bill.php" class="nav-link <?php echo $current_page == 'manage_bill.php' ? 'active' : ''; ?>">
                        <i class="fas fa-receipt"></i>
                        <span>Manage Bill</span>
                    </a>
                </div>
                <!-- ======================================== -->

                <div class="nav-divider">HR & COMMUNICATION</div>

                <!-- Attendance Management -->
                <div class="nav-item">
                    <a href="attendance_management.php" class="nav-link <?php echo $current_page == 'attendance_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Attendance Management</span>
                    </a>
                </div>

                <!-- Email Data -->
                <div class="nav-item">
                    <a href="email_data.php" class="nav-link <?php echo $current_page == 'email_data.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i>
                        <span>Email Data</span>
                    </a>
                </div>

                <!-- Chat - Admin Chat Page -->
                <div class="nav-item">
                    <a href="admin_chat.php" class="nav-link <?php echo ($current_page == 'admin_chat.php') ? 'active' : ''; ?>" id="sidebarChatLink">
                        <i class="fas fa-comment-dots"></i>
                        <span>Chat</span>
                        <span class="chat-badge-sidebar" id="sidebarChatBadge">0</span>
                    </a>
                </div>

                <div class="nav-divider">SYSTEM</div>

                <!-- User Management -->
                <div class="nav-item">
                    <a href="add_user.php" class="nav-link <?php echo $current_page == 'add_user.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </div>

                <!-- User Activity -->
                <div class="nav-item">
                    <a href="admin_user_activity.php" class="nav-link <?php echo $current_page == 'user_activity.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>User Activity</span>
                    </a>
                </div>
                
                <!-- Extra space at bottom for better scrolling -->
                <div style="height: 10px;"></div>
            </nav>
        </div>

        <!-- User Profile - Fixed at bottom -->
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <span class="avatar-fallback"><?php echo getUserInitials($user_name); ?></span>
                </div>
                <div class="user-details">
                    <h5><?php echo htmlspecialchars($user_name); ?></h5>
                    <p><?php echo htmlspecialchars($user_role); ?></p>
                </div>
            </div>
            <div class="logout-sidebar">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        // Mobile menu toggle functionality
        var mobileBtn = document.getElementById('mobileMenuBtn');
        var sidebar = document.getElementById('mainSidebar');
        
        function checkMobile() {
            if (window.innerWidth <= 768) {
                if (mobileBtn) {
                    mobileBtn.style.display = 'flex';
                }
                if (sidebar) sidebar.classList.remove('open');
            } else {
                if (mobileBtn) mobileBtn.style.display = 'none';
                if (sidebar) sidebar.classList.remove('open');
            }
        }
        
        if (mobileBtn) {
            mobileBtn.addEventListener('click', function() {
                if (sidebar) sidebar.classList.toggle('open');
            });
        }
        
        window.addEventListener('resize', checkMobile);
        checkMobile();

        // ==============================================
        // Chat Integration in Sidebar - FIXED
        // ==============================================
        
        // Get the chat link
        var chatLink = document.getElementById('sidebarChatLink');
        if (chatLink) {
            chatLink.addEventListener('click', function(e) {
                // Just close mobile sidebar if open
                if (window.innerWidth <= 768 && sidebar) {
                    sidebar.classList.remove('open');
                }
                // Let the link work normally - NO e.preventDefault()
            });
        }

        // Function to update sidebar chat badge
        function updateSidebarBadge(count) {
            var badge = document.getElementById('sidebarChatBadge');
            if (badge) {
                if (count > 0) {
                    badge.style.display = 'inline';
                    badge.textContent = count;
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // Function to get unread count from chat_api.php
        function fetchUnreadCount() {
            fetch('chat_api.php?action=get_unread_count')
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        updateSidebarBadge(data.unread_count);
                    } else {
                        updateSidebarBadge(0);
                    }
                })
                .catch(function(error) {
                    console.error('Error loading unread count:', error);
                    updateSidebarBadge(0);
                });
        }

        // Initial badge update
        document.addEventListener('DOMContentLoaded', function() {
            fetchUnreadCount();
            
            // Update badge every 30 seconds
            setInterval(fetchUnreadCount, 30000);
        });

        // Also update when page loads
        window.addEventListener('load', function() {
            fetchUnreadCount();
        });
    </script>
</body>
</html>