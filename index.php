<?php
// inventory_welcome.php
// Professional Inventory Management Welcome Page
// Click on "Login" tab redirects to your login file

// Configuration - Set your login file path here
$login_file = 'login.php'; // UPDATE THIS to your actual login file path

// Page title
$page_title = "Inventory Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0b1a2e 0%, #1a3a4a 50%, #0f2c38 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
        }

        /* Animated background overlay */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 20%, rgba(45,212,191,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        /* Main Card Container - Perfectly Centered */
        .welcome-card {
            max-width: 900px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 56px;
            box-shadow: 0 40px 60px -20px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.2);
            overflow: hidden;
            backdrop-filter: blur(2px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .welcome-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 50px 70px -25px rgba(0, 0, 0, 0.5);
        }

        /* Decorative Top Bar */
        .top-bar {
            height: 6px;
            background: linear-gradient(90deg, #2dd4bf, #3b82f6, #8b5cf6);
        }

        /* Content Area */
        .content {
            padding: 60px 48px;
        }

        /* Icon / Logo */
        .logo-wrapper {
            margin-bottom: 28px;
        }

        .logo-circle {
            width: 90px;
            height: 90px;
            background: linear-gradient(145deg, #1e3a4b, #0f2c38);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 15px 30px -10px rgba(0,0,0,0.25);
        }

        .logo-circle i {
            font-size: 48px;
            color: #2dd4bf;
        }

        /* Title */
        .title {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #1e3a4b, #2c5364);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 16px;
        }

        /* Subtitle */
        .subtitle {
            font-size: 18px;
            color: #4b5563;
            max-width: 550px;
            margin: 0 auto 32px auto;
            line-height: 1.5;
        }

        /* Divider */
        .divider {
            width: 80px;
            height: 3px;
            background: #2dd4bf;
            margin: 0 auto 32px auto;
            border-radius: 4px;
        }

        /* Feature Icons Row */
        .features {
            display: flex;
            justify-content: center;
            gap: 48px;
            flex-wrap: wrap;
            margin: 40px 0 48px 0;
        }

        .feature-item {
            text-align: center;
            min-width: 100px;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: #f0fdfa;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            color: #2dd4bf;
            font-size: 26px;
            transition: all 0.2s;
        }

        .feature-item span {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
        }

        /* Simple Tab - Only Login Tab */
        .tab-container {
            margin: 20px 0 10px 0;
        }

        .login-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(95deg, #1e3a4b 0%, #0f2c38 100%);
            color: white;
            font-size: 18px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            padding: 16px 56px;
            border-radius: 60px;
            text-decoration: none;
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            letter-spacing: 0.5px;
            border: none;
        }

        .login-tab i {
            font-size: 20px;
            transition: transform 0.2s;
        }

        .login-tab:hover {
            background: linear-gradient(95deg, #0f2c38 0%, #1a3a4a 100%);
            transform: scale(1.03);
            box-shadow: 0 12px 28px rgba(0,0,0,0.25);
        }

        .login-tab:hover i {
            transform: translateX(5px);
        }

        /* Login File Info */
        .login-note {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .login-note code {
            background: #f3f4f6;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            color: #1e3a4b;
            font-weight: 500;
        }

        .login-note i {
            color: #2dd4bf;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .content {
                padding: 40px 24px;
            }
            .title {
                font-size: 32px;
            }
            .subtitle {
                font-size: 15px;
            }
            .features {
                gap: 24px;
            }
            .login-tab {
                padding: 14px 40px;
                font-size: 16px;
            }
            .logo-circle {
                width: 70px;
                height: 70px;
            }
            .logo-circle i {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>

<div class="welcome-card">
    <div class="top-bar"></div>
    
    <div class="content">
        <!-- Logo Area -->
        <div class="logo-wrapper">
            <div class="logo-circle">
                <i class="fas fa-boxes"></i>
            </div>
        </div>

        <!-- Main Title -->
        <h1 class="title">StockMaster Pro</h1>
        
        <!-- Tagline -->
        <p class="subtitle">
            Intelligent inventory tracking • Real-time stock alerts • Seamless operations
        </p>
        
        <div class="divider"></div>

        <!-- Feature Highlights -->
        <div class="features">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <span>Analytics</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-warehouse"></i></div>
                <span>Multi-Warehouse</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-bell"></i></div>
                <span>Low Stock Alerts</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-file-invoice"></i></div>
                <span>Reports</span>
            </div>
        </div>

        <!-- SINGLE LOGIN TAB - Click to Redirect -->
        <div class="tab-container">
            <a href="<?php echo htmlspecialchars($login_file); ?>" class="login-tab">
                <i class="fas fa-sign-in-alt"></i> Login
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Connection Information -->
        <div class="login-note">
            <i class="fas fa-link"></i> 
            <span>Redirects to:</span>
            <code><?php echo htmlspecialchars($login_file); ?></code>
            <span style="font-size:12px;">— Update <strong>$login_file</strong> variable to your actual login file path</span>
        </div>
    </div>
</div>

</body>
</html>