<?php
// ==============================================
// Header Component - Top Navigation Bar
// File: header.php
// ==============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page name for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Top Header Styles - Matching Sidebar Design */
    .top-header {
        position: fixed;
        top: 0;
        right: 0;
        left: 280px;
        height: 70px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 30px;
        z-index: 999;
        transition: all 0.3s ease;
        border-bottom: 1px solid #e2e8f0;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 22px;
        color: #475569;
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .mobile-toggle:hover {
        background: #f1f5f9;
        color: #2dd4bf;
    }

    .page-title {
        font-size: 20px;
        font-weight: 600;
        color: #0f172a;
        letter-spacing: -0.3px;
    }

    .page-title i {
        color: #2dd4bf;
        margin-right: 10px;
        font-size: 20px;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 25px;
    }

    /* Notification Icon */
    .notification-icon {
        position: relative;
        cursor: pointer;
    }

    .notification-icon i {
        font-size: 20px;
        color: #64748b;
        transition: color 0.2s;
    }

    .notification-icon i:hover {
        color: #2dd4bf;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 20px;
        min-width: 18px;
        text-align: center;
        font-family: monospace;
    }

    /* User Dropdown */
    .user-dropdown {
        position: relative;
        cursor: pointer;
    }

    .user-dropdown-trigger {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 12px;
        border-radius: 12px;
        transition: all 0.2s;
    }

    .user-dropdown-trigger:hover {
        background: #f8fafc;
    }

    .user-avatar-small {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #2dd4bf, #14b8a6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }

    .user-avatar-small i {
        font-size: 16px;
    }

    .user-info-small h4 {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 2px;
    }

    .user-info-small p {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }

    .dropdown-arrow {
        color: #94a3b8;
        font-size: 12px;
        transition: transform 0.2s;
    }

    .user-dropdown.active .dropdown-arrow {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 10px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        min-width: 220px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s;
        z-index: 1000;
        border: 1px solid #e2e8f0;
    }

    .user-dropdown.active .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #334155;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .dropdown-menu a:hover {
        background: #f8fafc;
        color: #2dd4bf;
    }

    .dropdown-menu a i {
        width: 20px;
        font-size: 14px;
        color: #64748b;
    }

    .dropdown-menu a:hover i {
        color: #2dd4bf;
    }

    .dropdown-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 6px 0;
    }

    .dropdown-menu .logout-item {
        color: #ef4444;
    }

    .dropdown-menu .logout-item i {
        color: #ef4444;
    }

    .dropdown-menu .logout-item:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .dropdown-menu .logout-item:hover i {
        color: #dc2626;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .top-header {
            left: 0;
            padding: 0 20px;
        }
        .mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .page-title {
            font-size: 16px;
        }
        .user-info-small {
            display: none;
        }
        .user-dropdown-trigger {
            padding: 6px 8px;
        }
        .header-right {
            gap: 15px;
        }
    }
</style>

<header class="top-header">
    <div class="header-left">
        <button class="mobile-toggle" id="mobileToggleBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-title">
            <i class="fas fa-chart-line"></i>
            <?php
            // Dynamic page title based on current page
            $titles = [
                'admin_dashboard.php' => 'Dashboard',
                'add_user.php' => 'Add User',
                'user.php' => 'User Management',
                'upload_data.php' => 'Upload Data',
                'manage_data.php' => 'Manage Data',
                'add_agency.php' => 'Add Agency',
                'manage_agency.php' => 'Manage Agency',
                'attendance_management.php' => 'Attendance Management',
                'email_data.php' => 'Email Data',
                'user_activity.php' => 'User Activity'
            ];
            echo isset($titles[$current_page]) ? $titles[$current_page] : 'Dashboard';
            ?>
        </div>
    </div>
    <div class="header-right">
        <div class="notification-icon">
            <i class="far fa-bell"></i>
            <span class="notification-badge">3</span>
        </div>
        <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-trigger">
                <div class="user-avatar-small">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info-small">
                    <h4><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : (isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Administrator'); ?></h4>
                    <p><?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Admin'; ?></p>
                </div>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>
            <div class="dropdown-menu">
                <a href="profile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="logout-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>

<script>
    // User dropdown toggle
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
        });
    }
    
    // Mobile sidebar toggle
    const mobileToggleBtn = document.getElementById('mobileToggleBtn');
    const sidebar = document.getElementById('mainSidebar');
    
    if (mobileToggleBtn && sidebar) {
        mobileToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
</script>