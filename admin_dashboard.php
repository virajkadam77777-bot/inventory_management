<?php
// ==============================================
// Admin Dashboard Page
// File: admin_dashboard.php
// ==============================================

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: user_dashboard.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Get statistics for dashboard
try {
    // Total users count
    $user_query = "SELECT COUNT(*) as total FROM users";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute();
    $total_users = $user_stmt->fetch()['total'];
    
    // Total agencies count
    $agency_query = "SELECT COUNT(*) as total FROM agencies";
    $agency_stmt = $db->prepare($agency_query);
    $agency_stmt->execute();
    $total_agencies = $agency_stmt->fetch()['total'];
    
    // Active users count
    $active_query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
    $active_stmt = $db->prepare($active_query);
    $active_stmt->execute();
    $active_users = $active_stmt->fetch()['total'];
    
    // New users this month
    $new_query = "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $new_stmt = $db->prepare($new_query);
    $new_stmt->execute();
    $new_users = $new_stmt->fetch()['total'];
    
    // Recent users (last 5)
    $recent_users_query = "SELECT id, name, email_id, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5";
    $recent_users_stmt = $db->prepare($recent_users_query);
    $recent_users_stmt->execute();
    $recent_users = $recent_users_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StockMaster Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* Main Content Area (with sidebar offset) */
        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #0f172a;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .welcome-text {
            color: #64748b;
            margin-top: 8px;
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }

        .stat-info h3 {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-info .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            background: rgba(45, 212, 191, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 28px;
            color: #2dd4bf;
        }

        /* Recent Users Table */
        .recent-section {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
        }

        .section-header h2 i {
            color: #2dd4bf;
            margin-right: 10px;
        }

        .view-all {
            color: #2dd4bf;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .view-all:hover {
            color: #14b8a6;
            text-decoration: underline;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px 24px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 16px 24px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-suspended {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pending {
            background: #e0e7ff;
            color: #3730a3;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #e0e7ff;
            color: #3730a3;
        }

        .role-admin {
            background: #fef3c7;
            color: #92400e;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
                margin-top: 70px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .stat-card {
                padding: 20px;
            }
            .stat-info .stat-number {
                font-size: 28px;
            }
            .section-header {
                padding: 16px 20px;
            }
            th, td {
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>

<!-- Include Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Include Header -->
<?php include 'header.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <div class="welcome-text">
            Welcome back, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Administrator'; ?>! Here's what's happening with your inventory system today.
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Users</h3>
                <div class="stat-number"><?php echo isset($total_users) ? $total_users : 0; ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Agencies</h3>
                <div class="stat-number"><?php echo isset($total_agencies) ? $total_agencies : 0; ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Active Users</h3>
                <div class="stat-number"><?php echo isset($active_users) ? $active_users : 0; ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>New This Month</h3>
                <div class="stat-number"><?php echo isset($new_users) ? $new_users : 0; ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
        </div>
    </div>

    <!-- Recent Users Section -->
    <div class="recent-section">
        <div class="section-header">
            <h2><i class="fas fa-user-clock"></i> Recently Added Users</h2>
            <a href="user.php" class="view-all">View All Users <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($recent_users) && count($recent_users) > 0): ?>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email_id']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role'] == 'Admin' ? 'role-admin' : ''; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($user['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fas fa-inbox" style="font-size: 40px; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                                No users found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>