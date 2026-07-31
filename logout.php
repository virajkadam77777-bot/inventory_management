<?php
// ==============================================
// Logout Script - Inventory Management System
// ==============================================

session_start();

// Get user ID and request details BEFORE destroying session
$user_id = $_SESSION['user_id'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

if ($user_id) {
    require_once 'db_connection.php';
    try {
        // 1. Log logout activity
        $log_query = "INSERT INTO user_activity (user_id, activity_type, description, ip_address, user_agent, created_at) 
                      VALUES (:user_id, 'logout', 'User logged out', :ip, :agent, NOW())";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->execute([
            ':user_id' => $user_id,
            ':ip'      => $ip,
            ':agent'   => $agent
        ]);

        // 2. Update online status to offline
        $status_query = "UPDATE user_online_status SET is_online = 0, last_seen = NOW() WHERE user_id = :user_id";
        $status_stmt = $db->prepare($status_query);
        $status_stmt->execute([':user_id' => $user_id]);
    } catch (PDOException $e) {
        error_log("Logout activity/status update failed: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>