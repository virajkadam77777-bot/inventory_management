<?php
// ============================================================
// Include database connection (same folder)
// ============================================================
require_once __DIR__ . '/db_connection.php';

// If $db is not set, show error
if (!isset($db)) {
    die('Database connection not available. Check db_connection.php');
}

// ============================================================
// Helper function to format IP address for display
// ============================================================
function formatIp($ip) {
    if (empty($ip)) return '-';
    if ($ip === '::1' || $ip === '127.0.0.1') {
        return 'localhost';
    }
    // Convert IPv6-mapped IPv4: ::ffff:192.0.2.1 -> 192.0.2.1
    if (strpos($ip, '::ffff:') === 0) {
        $ip = substr($ip, 7);
    }
    return $ip;
}

// ============================================================
// Pagination & filtering parameters
// ============================================================
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$type_filter = isset($_GET['activity_type']) ? trim($_GET['activity_type']) : '';

// Optional date filter – show last 30 days by default? Uncomment if needed
// $date_filter = isset($_GET['date_from']) ? trim($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));

// ============================================================
// Build WHERE clause
// ============================================================
$where = [];
$params = [];

if ($user_filter > 0) {
    $where[] = "a.user_id = :user_id";
    $params[':user_id'] = $user_filter;
}
if ($type_filter !== '') {
    $where[] = "a.activity_type = :activity_type";
    $params[':activity_type'] = $type_filter;
}
// Uncomment for date filter:
// if (!empty($date_filter)) {
//     $where[] = "DATE(a.created_at) >= :date_from";
//     $params[':date_from'] = $date_filter;
// }

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ============================================================
// Count total records
// ============================================================
$count_sql = "SELECT COUNT(*) FROM user_activity a $where_sql";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// ============================================================
// Fetch activity data with user online status and name
// ============================================================
$sql = "
    SELECT 
        a.id,
        a.user_id,
        a.activity_type,
        a.description,
        a.ip_address,
        a.created_at,
        s.is_online,
        s.last_seen,
        u.name AS user_name
    FROM user_activity a
    LEFT JOIN user_online_status s ON a.user_id = s.user_id
    LEFT JOIN users u ON a.user_id = u.id
    $where_sql
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$activities = $stmt->fetchAll();

// ============================================================
// Fetch distinct activity types and users for filters
// ============================================================
$type_list = $db->query("SELECT DISTINCT activity_type FROM user_activity WHERE activity_type IS NOT NULL ORDER BY activity_type")->fetchAll();
$users_list = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

// ============================================================
// Debug: check if table is empty (for troubleshooting)
// ============================================================
$table_count = $db->query("SELECT COUNT(*) FROM user_activity")->fetchColumn();
$debug_message = '';
if ($table_count == 0) {
    $debug_message = '⚠️ The user_activity table is empty. Please log in at least once to create activity records.';
} elseif (count($activities) == 0 && $total_records > 0) {
    $debug_message = 'ℹ️ No records match your current filters. Try clearing the filters.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Log - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .online-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .online-dot.online { background-color: #28a745; }
        .online-dot.offline { background-color: #dc3545; }
        .table-activity td {
            vertical-align: middle;
        }
        .filter-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            flex: 0 0 250px;
            background: #f8f9fa;
            padding: 15px;
        }
        .admin-content {
            flex: 1;
            padding: 20px;
        }
        .debug-banner {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <?php require_once __DIR__ . '/sidebar.php'; // adjust if sidebar is in another folder ?>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="container-fluid">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-history"></i> User Activity Log</h1>
                <span class="badge bg-secondary">Total: <?= number_format($total_records) ?> activities</span>
            </div>

            <!-- Debug banner if empty -->
            <?php if (!empty($debug_message)): ?>
                <div class="debug-banner">
                    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($debug_message) ?>
                </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="filter-box">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="user_id" class="form-label">Filter by User</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="0">All Users</option>
                            <?php foreach ($users_list as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($user_filter == $user['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="activity_type" class="form-label">Filter by Activity Type</label>
                        <select name="activity_type" id="activity_type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($type_list as $row): ?>
                                <option value="<?= htmlspecialchars($row['activity_type']) ?>" <?= ($type_filter === $row['activity_type']) ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($row['activity_type'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Apply Filters</button>
                    </div>
                </form>
            </div>

            <!-- Activity Table -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-activity">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Activity Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($activities) > 0): ?>
                            <?php foreach ($activities as $index => $row): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['user_name'] ?? 'Unknown User') ?></strong>
                                        <br><small class="text-muted">ID: <?= $row['user_id'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= ucfirst(htmlspecialchars($row['activity_type'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(formatIp($row['ip_address'] ?? '')) ?></td>
                                    <td><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php if ($row['is_online'] == 1): ?>
                                            <span class="online-dot online"></span> Online
                                        <?php else: ?>
                                            <span class="online-dot offline"></span> Offline
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['last_seen'] ? date('Y-m-d H:i:s', strtotime($row['last_seen'])) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No activity records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Activity pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&user_id=<?= $user_filter ?>&activity_type=<?= urlencode($type_filter) ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&user_id=<?= $user_filter ?>&activity_type=<?= urlencode($type_filter) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&user_id=<?= $user_filter ?>&activity_type=<?= urlencode($type_filter) ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>