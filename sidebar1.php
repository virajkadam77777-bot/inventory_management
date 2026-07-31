<?php
// ==============================================
// Employee Sidebar Component with Profile Picture
// File: sidebar1.php
// ==============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get user data from session
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Employee');
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Employee';
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$profile_picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ========== SIDEBAR STYLES ========== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #94a3b8;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-logo {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #2dd4bf, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            font-size: 24px;
            color: white;
        }

        .logo-text h3 {
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 11px;
            color: #2dd4bf;
            margin-top: 3px;
        }

        /* User Profile Section in Sidebar */
        .user-profile-section {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .profile-avatar {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #2dd4bf;
        }

        .profile-avatar .default-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            color: white;
        }

        .profile-name {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 12px;
            color: #2dd4bf;
            margin-bottom: 5px;
        }

        .profile-email {
            font-size: 11px;
            color: #94a3b8;
        }

        .nav-menu {
            padding: 0 16px;
        }

        .nav-item {
            margin-bottom: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }

        .nav-link i {
            width: 22px;
            font-size: 18px;
        }

        .nav-link:hover {
            background: rgba(45, 212, 191, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(45, 212, 191, 0.15);
            color: #2dd4bf;
            position: relative;
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

        .nav-divider {
            padding: 16px 16px 8px;
            font-size: 11px;
            text-transform: uppercase;
            color: #475569;
            font-weight: 600;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.95);
        }

        .logout-btn {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: #ef4444;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .logout-btn a:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #1e293b;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #2dd4bf;
            border-radius: 4px;
        }

        /* Responsive - Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="employeeSidebar">
        <div class="sidebar-logo">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="logo-text">
                    <h3>StockMaster Pro</h3>
                    <p>Employee Portal</p>
                </div>
            </div>
        </div>

        <!-- User Profile Section -->
        <div class="user-profile-section">
            <div class="profile-avatar">
                <?php if ($profile_picture && file_exists($profile_picture)): ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>?t=<?php echo time(); ?>" alt="Profile Picture">
                <?php else: ?>
                    <div class="default-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
            <div class="profile-role"><?php echo htmlspecialchars($user_role); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($user_email); ?></div>
        </div>

        <nav class="nav-menu">
            <div class="nav-divider">MAIN MENU</div>
            
            <!-- Dashboard -->
            <div class="nav-item">
                <a href="employee.php" class="nav-link <?php echo $current_page == 'employee.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Attendance -->
            <div class="nav-item">
                <a href="employee_attendance.php" class="nav-link <?php echo $current_page == 'employee_attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </div>

            <!-- Leave Management -->
            <div class="nav-item">
                <a href="employee_leave.php" class="nav-link <?php echo $current_page == 'employee_leave.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plane-departure"></i>
                    <span>Leave Management</span>
                </a>
            </div>

            <!-- Chat -->
            <div class="nav-item">
                <a href="employee_chat.php" class="nav-link <?php echo $current_page == 'employee_chat.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                </a>
            </div>

            <div class="nav-divider">PERSONAL</div>

            <!-- My Profile -->
            <div class="nav-item">
                <a href="employee_profile.php" class="nav-link <?php echo $current_page == 'employee_profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="logout-btn">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" style="display: none; position: fixed; top: 20px; left: 20px; z-index: 1200; background: #2dd4bf; border: none; width: 45px; height: 45px; border-radius: 12px; color: white; font-size: 20px; cursor: pointer;">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        // Mobile menu toggle functionality
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('employeeSidebar');
        
        function checkMobile() {
            if (window.innerWidth <= 768) {
                if (mobileBtn) {
                    mobileBtn.style.display = 'flex';
                    mobileBtn.style.alignItems = 'center';
                    mobileBtn.style.justifyContent = 'center';
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
    </script>
</body>
</html>