<?php
// ==============================================
// Login Page - Professional Inventory Management System
// ==============================================

session_start();

// Include database connection
require_once 'db_connection.php';

// Check if database connection exists
if (!isset($db)) {
    die("Database connection failed. Please check config/db_connection.php");
}

// Configuration
$page_title = "Login | Inventory Management System";

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') {
        header("Location: admin_dashboard.php");
        exit();
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'Employee') {
        header("Location: employee.php");
        exit();
    }
}

// Initialize error variable
$error = '';
$username_input = '';
$debug_info = ''; // For debugging

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $username_input = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // Validation
    if (empty($username_input) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        try {
            // Query to get user by username or email
            $query = "SELECT id, name, username, email_id, password_hash, designation, role, city, profile_picture, status 
                      FROM users 
                      WHERE (username = :login OR email_id = :login) 
                      LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->execute([':login' => $username_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug information (remove in production)
            $debug_info = "Debug: User found = " . ($user ? 'Yes' : 'No');
            
            if ($user) {
                $debug_info .= "<br>Stored Hash: " . substr($user['password_hash'], 0, 30) . "...";
                $debug_info .= "<br>Password verify result: " . (password_verify($password, $user['password_hash']) ? 'TRUE' : 'FALSE');
                
                // If password_verify fails, try alternative methods (for troubleshooting)
                if (!password_verify($password, $user['password_hash'])) {
                    // Check if it's MD5
                    if (md5($password) === $user['password_hash']) {
                        $debug_info .= "<br>Password matches using MD5! Updating to secure hash...";
                        // Update to secure hash
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                        $update->execute([':hash' => $new_hash, ':id' => $user['id']]);
                        $debug_info .= "<br>Password hash updated to secure format. Please try again.";
                        $error = 'Password format updated. Please login again.';
                    }
                }
            }
            
            // Verify password and account status
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is active
                if ($user['status'] != 'active') {
                    $error = 'Your account is ' . $user['status'] . '. Please contact administrator.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email_id'];
                    $_SESSION['designation'] = $user['designation'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['city'] = $user['city'];
                    $_SESSION['profile_picture'] = $user['profile_picture'];
                    $_SESSION['logged_in'] = true;
                    
                    // ============================================================
                    // 1. LOG LOGIN ACTIVITY (insert into user_activity)
                    // ============================================================
                    try {
                        $log_query = "INSERT INTO user_activity 
                                      (user_id, activity_type, description, ip_address, user_agent, created_at) 
                                      VALUES 
                                      (:user_id, 'login', 'User logged in', :ip, :agent, NOW())";
                        $log_stmt = $db->prepare($log_query);
                        $result = $log_stmt->execute([
                            ':user_id' => $user['id'],
                            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                            ':agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null
                        ]);
                        if ($result) {
                            $debug_info .= "<br>✅ Activity log inserted successfully!";
                        } else {
                            $debug_info .= "<br>❌ Activity log insert failed (no error thrown).";
                        }
                    } catch (PDOException $e) {
                        $error = "Activity logging failed: " . $e->getMessage();
                        error_log("Login activity logging failed: " . $e->getMessage());
                    }
                    
                    // ============================================================
                    // 2. UPDATE ONLINE STATUS (insert or update user_online_status)
                    // ============================================================
                    try {
                        $status_query = "INSERT INTO user_online_status (user_id, is_online, last_seen) 
                                         VALUES (:user_id, 1, NOW()) 
                                         ON DUPLICATE KEY UPDATE is_online = 1, last_seen = NOW()";
                        $status_stmt = $db->prepare($status_query);
                        $status_stmt->execute([':user_id' => $user['id']]);
                        $debug_info .= "<br>✅ Online status updated to Online.";
                    } catch (PDOException $e) {
                        $error = "Online status update failed: " . $e->getMessage();
                        error_log("Online status update failed: " . $e->getMessage());
                    }
                    // ============================================================
                    
                    // Update last login information
                    try {
                        // Check if last_login_at column exists
                        $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'last_login_at'");
                        if ($check_column->rowCount() > 0) {
                            $update_query = "UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id";
                            $update_stmt = $db->prepare($update_query);
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                            $update_stmt->execute([':ip' => $ip_address, ':id' => $user['id']]);
                        }
                    } catch (PDOException $e) {
                        // Column might not exist, skip
                        error_log("Last login update failed: " . $e->getMessage());
                    }
                    
                    // Remember me functionality (30 days)
                    if ($remember_me) {
                        try {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            // Check if remember_token column exists
                            $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
                            if ($check_column->rowCount() > 0) {
                                $token_query = "UPDATE users SET remember_token = :token, remember_expires = :expires WHERE id = :id";
                                $token_stmt = $db->prepare($token_query);
                                $token_stmt->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);
                                setcookie('remember_token', $token, time() + (86400 * 30), '/');
                            }
                        } catch (Exception $e) {
                            error_log("Remember me failed: " . $e->getMessage());
                        }
                    }
                    
                    // Redirect based on role
                    if ($user['role'] == 'Admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: employee.php");
                    }
                    exit();
                }
            } else {
                if (!$user) {
                    $error = 'User not found. Invalid username/email.';
                } else {
                    $error = 'Invalid password. Please try again.';
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'System error. Please try again later.';
            $debug_info = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 20%, rgba(45,212,191,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .login-container {
            max-width: 460px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 48px;
            box-shadow: 0 40px 60px -20px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-4px);
        }

        .top-bar {
            height: 6px;
            background: linear-gradient(90deg, #2dd4bf, #3b82f6, #8b5cf6);
        }

        .login-content {
            padding: 48px 40px;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(145deg, #1e3a4b, #0f2c38);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }

        .logo-circle i {
            font-size: 36px;
            color: #2dd4bf;
        }

        .logo-area h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }

        .logo-area p {
            font-size: 14px;
            color: #6b7280;
            margin-top: 6px;
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #b91c1c;
        }

        .error-message i {
            font-size: 18px;
        }

        .debug-info {
            background: #f0fdf4;
            border-left: 4px solid #2dd4bf;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 28px;
            font-size: 12px;
            color: #115e59;
            font-family: monospace;
        }

        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: 14px;
        }

        .input-group label i {
            margin-right: 8px;
            color: #5b6e8c;
        }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: #fafcff;
        }

        .input-field:focus {
            outline: none;
            border-color: #2dd4bf;
            box-shadow: 0 0 0 4px rgba(45,212,191,0.15);
            background: white;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
        }

        .password-toggle:hover {
            color: #1e3a4b;
        }

        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #4b5563;
            cursor: pointer;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 14px;
            color: #2dd4bf;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(95deg, #1e3a4b 0%, #0f2c38 100%);
            color: white;
            border: none;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .login-btn:hover {
            background: linear-gradient(95deg, #0f2c38 0%, #1a3a4a 100%);
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: #1e3a4b;
        }

        .info-text {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 20px;
        }

        @media (max-width: 500px) {
            .login-content {
                padding: 36px 24px;
            }
            .options-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="top-bar"></div>
    
    <div class="login-content">
        <div class="logo-area">
            <div class="logo-circle">
                <i class="fas fa-boxes"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Sign in to your inventory account</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($debug_info) && isset($_POST['username'])): ?>
        <div class="debug-info">
            <i class="fas fa-bug"></i> <?php echo $debug_info; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" autocomplete="off">
            <div class="input-group">
                <label><i class="fas fa-user"></i> Username or Email</label>
                <input type="text" name="username" class="input-field" 
                       value="<?php echo htmlspecialchars($username_input); ?>"
                       placeholder="Enter your username or email" 
                       required autofocus>
            </div>

            <div class="input-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="input-field" 
                           placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="far fa-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="options-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember_me"> Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-arrow-right-to-bracket"></i> Sign In
                <i class="fas fa-angle-right"></i>
            </button>
        </form>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <div class="info-text">
            <i class="fas fa-shield-alt"></i> Secure Login System
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }
}
</script>

</body>
</html>