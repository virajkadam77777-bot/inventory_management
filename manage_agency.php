<?php
// ==============================================
// Manage Agency/Clients
// File: manage_agency.php
// ==============================================

session_start();

// Include database connection
require_once 'db_connection.php';

// Set active page for sidebar
$active_page = 'manage_agency';

// Initialize variables
$success_message = "";
$error_message = "";
$search_term = "";

// Handle Delete Request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM client WHERE id = :id");
        $stmt->bindParam(':id', $delete_id);
        if ($stmt->execute()) {
            $success_message = "✅ Client deleted successfully!";
        } else {
            $error_message = "❌ Failed to delete client!";
        }
    } catch (PDOException $e) {
        $error_message = "❌ Database Error: " . $e->getMessage();
    }
}

// Handle Toggle Service Status
if (isset($_GET['toggle_service']) && !empty($_GET['toggle_service'])) {
    $toggle_id = intval($_GET['toggle_service']);
    try {
        // Get current status
        $stmt = $db->prepare("SELECT services FROM client WHERE id = :id");
        $stmt->bindParam(':id', $toggle_id);
        $stmt->execute();
        $current = $stmt->fetch();
        
        $new_status = ($current['services'] == 'on') ? 'off' : 'on';
        
        $stmt = $db->prepare("UPDATE client SET services = :services WHERE id = :id");
        $stmt->bindParam(':services', $new_status);
        $stmt->bindParam(':id', $toggle_id);
        if ($stmt->execute()) {
            $success_message = "✅ Service status updated successfully!";
        } else {
            $error_message = "❌ Failed to update service status!";
        }
    } catch (PDOException $e) {
        $error_message = "❌ Database Error: " . $e->getMessage();
    }
}

// Handle Toggle Status (Active/Inactive)
if (isset($_GET['toggle_status']) && !empty($_GET['toggle_status'])) {
    $toggle_id = intval($_GET['toggle_status']);
    try {
        // Get current status
        $stmt = $db->prepare("SELECT status FROM client WHERE id = :id");
        $stmt->bindParam(':id', $toggle_id);
        $stmt->execute();
        $current = $stmt->fetch();
        
        $new_status = ($current['status'] == 'active') ? 'inactive' : 'active';
        
        $stmt = $db->prepare("UPDATE client SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $toggle_id);
        if ($stmt->execute()) {
            $success_message = "✅ Status updated successfully!";
        } else {
            $error_message = "❌ Failed to update status!";
        }
    } catch (PDOException $e) {
        $error_message = "❌ Database Error: " . $e->getMessage();
    }
}

// Build search query
$search_condition = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $search_condition = "WHERE (agency_name LIKE :search OR owner_name LIKE :search OR mobile_no LIKE :search OR mail_id LIKE :search OR address LIKE :search)";
}

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total records for pagination
try {
    $count_sql = "SELECT COUNT(*) as total FROM client " . $search_condition;
    $count_stmt = $db->prepare($count_sql);
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $count_stmt->bindParam(':search', $search_param);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
} catch (PDOException $e) {
    $error_message = "❌ Database Error: " . $e->getMessage();
    $total_records = 0;
    $total_pages = 0;
}

// Fetch client data with pagination
try {
    $sql = "SELECT * FROM client " . $search_condition . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $stmt->bindParam(':search', $search_param);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "❌ Database Error: " . $e->getMessage();
    $clients = [];
}

// Get statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total_clients,
        SUM(CASE WHEN services = 'on' THEN 1 ELSE 0 END) as services_on,
        SUM(CASE WHEN services = 'off' THEN 1 ELSE 0 END) as services_off,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_clients,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_clients,
        SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_clients,
        SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_clients,
        SUM(CASE WHEN amc = 'Yes' THEN 1 ELSE 0 END) as amc_clients
    FROM client";
    $stats_stmt = $db->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'total_clients' => 0,
        'services_on' => 0,
        'services_off' => 0,
        'active_clients' => 0,
        'inactive_clients' => 0,
        'paid_clients' => 0,
        'unpaid_clients' => 0,
        'amc_clients' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agencies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles - FIXED */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: #1a3c5e;
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            height: 100vh;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Content Area - FIXED with proper margin */
        .content-area {
            flex: 1;
            padding: 20px;
            min-height: 100vh;
            margin-left: 250px;
            width: calc(100% - 250px);
            max-width: calc(100% - 250px);
            background: #f0f2f5;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-header h1 {
            color: #1a3c5e;
            margin: 0;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1a3c5e;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }

        .hamburger {
            display: none;
            background: #1a3c5e;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 20px;
            cursor: pointer;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a3c5e;
        }

        .stat-card .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .stat-card .icon {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .stat-card.primary .icon { color: #1a3c5e; }
        .stat-card.success .icon { color: #28a745; }
        .stat-card.danger .icon { color: #dc3545; }
        .stat-card.warning .icon { color: #ffc107; }
        .stat-card.info .icon { color: #17a2b8; }

        .filter-section {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filter-section .search-box {
            flex: 1;
            min-width: 200px;
            display: flex;
            gap: 10px;
        }

        .filter-section .search-box input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-section .search-box input:focus {
            outline: none;
            border-color: #1a3c5e;
        }

        .filter-section .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: #1a3c5e;
            color: white;
        }
        .btn-primary:hover {
            background: #0f2a42;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 900px;
        }

        .table th {
            background: #f8f9fa;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        .table td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #cce5ff; color: #004085; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }

        .service-toggle {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }

        .service-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .service-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 34px;
        }

        .service-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        .service-toggle input:checked + .service-slider {
            background-color: #28a745;
        }

        .service-toggle input:checked + .service-slider:before {
            transform: translateX(18px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #1a3c5e;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #1a3c5e;
            color: white;
        }

        .pagination .active {
            background: #1a3c5e;
            color: white;
            border-color: #1a3c5e;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .text-truncate {
            max-width: 150px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .status-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 34px;
        }

        .status-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        .status-toggle input:checked + .status-slider {
            background-color: #28a745;
        }

        .status-toggle input:checked + .status-slider:before {
            transform: translateX(18px);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .content-area {
                margin-left: 0 !important;
                padding: 10px;
                width: 100% !important;
                max-width: 100% !important;
            }

            .hamburger {
                display: block;
            }

            .top-header {
                padding: 12px 15px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .top-header h1 {
                font-size: 18px;
            }

            .user-info span {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-section {
                flex-direction: column;
            }

            .filter-section .search-box {
                width: 100%;
                flex-wrap: wrap;
            }

            .filter-section .search-box form {
                flex-wrap: wrap;
                width: 100%;
            }

            .filter-section .search-box input {
                min-width: 150px;
                flex: 1;
            }

            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .table td, .table th {
                padding: 8px 5px;
                font-size: 12px;
            }

            .text-truncate {
                max-width: 80px;
            }

            .table {
                min-width: 750px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .stat-card .number {
                font-size: 18px;
            }

            .stat-card {
                padding: 10px;
            }

            .top-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-info {
                width: 100%;
                justify-content: flex-end;
            }

            .filter-section .search-box {
                flex-direction: column;
                width: 100%;
            }

            .filter-section .search-box form {
                flex-direction: column;
                width: 100%;
            }

            .filter-section .search-box input {
                width: 100%;
                min-width: unset;
            }

            .filter-section .search-box button,
            .filter-section .search-box a {
                width: 100%;
                justify-content: center;
            }

            .table-container {
                padding: 10px;
            }

            .table {
                min-width: 650px;
                font-size: 11px;
            }

            .action-buttons .btn-sm {
                padding: 4px 6px;
                font-size: 10px;
            }

            .stat-card .label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Top Header -->
            <div class="top-header">
                <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                    <button class="hamburger" id="hamburgerBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><i class="fas fa-building"></i> Manage Agencies</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?= $stats['total_clients'] ?? 0 ?></div>
                    <div class="label">Total Agencies</div>
                </div>
                <div class="stat-card success">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?= $stats['services_on'] ?? 0 ?></div>
                    <div class="label">Services ON</div>
                </div>
                <div class="stat-card danger">
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                    <div class="number"><?= $stats['services_off'] ?? 0 ?></div>
                    <div class="label">Services OFF</div>
                </div>
                <div class="stat-card success">
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                    <div class="number"><?= $stats['active_clients'] ?? 0 ?></div>
                    <div class="label">Active Clients</div>
                </div>
                <div class="stat-card warning">
                    <div class="icon"><i class="fas fa-user-clock"></i></div>
                    <div class="number"><?= $stats['inactive_clients'] ?? 0 ?></div>
                    <div class="label">Inactive Clients</div>
                </div>
                <div class="stat-card info">
                    <div class="icon"><i class="fas fa-handshake"></i></div>
                    <div class="number"><?= $stats['amc_clients'] ?? 0 ?></div>
                    <div class="label">AMC Clients</div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="search-box">
                    <form method="GET" action="" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap;">
                        <input type="text" name="search" placeholder="Search by Agency, Owner, Mobile, Email..." value="<?= htmlspecialchars($search_term) ?>" style="flex: 1; min-width: 180px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (!empty($search_term)): ?>
                            <a href="manage_agency.php" class="btn" style="background: #6c757d; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <a href="add_client.php" class="btn btn-success" style="white-space: nowrap;">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>

            <!-- Table -->
            <div class="table-container">
                <?php if (empty($clients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <h5>No clients found</h5>
                        <p class="text-muted"><?= !empty($search_term) ? 'No results match your search criteria.' : 'Click "Add New" to create your first client.' ?></p>
                        <?php if (!empty($search_term)): ?>
                            <a href="manage_agency.php" class="btn btn-primary">View All Clients</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Agency Name</th>
                                    <th>Owner</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Services</th>
                                    <th>Status</th>
                                    <th>AMC</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><strong>#<?= $client['id'] ?></strong></td>
                                    <td>
                                        <span class="text-truncate" title="<?= htmlspecialchars($client['agency_name']) ?>">
                                            <?= htmlspecialchars($client['agency_name']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($client['owner_name']) ?></td>
                                    <td><?= htmlspecialchars($client['mobile_no']) ?></td>
                                    <td>
                                        <span class="text-truncate" title="<?= htmlspecialchars($client['mail_id']) ?>">
                                            <?= htmlspecialchars($client['mail_id'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $client['purchase_rental'] == 'Purchase' ? 'badge-primary' : 'badge-warning' ?>">
                                            <?= $client['purchase_rental'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <label class="service-toggle">
                                                <input type="checkbox" <?= $client['services'] == 'on' ? 'checked' : '' ?> 
                                                       onchange="toggleService(<?= $client['id'] ?>, this)">
                                                <span class="service-slider"></span>
                                            </label>
                                            <span style="font-size: 11px; font-weight: 600; <?= $client['services'] == 'on' ? 'color: #28a745;' : 'color: #dc3545;' ?>">
                                                <?= strtoupper($client['services']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <label class="status-toggle">
                                                <input type="checkbox" <?= $client['status'] == 'active' ? 'checked' : '' ?> 
                                                       onchange="toggleStatus(<?= $client['id'] ?>, this)">
                                                <span class="status-slider"></span>
                                            </label>
                                            <span style="font-size: 11px; font-weight: 600; <?= $client['status'] == 'active' ? 'color: #28a745;' : 'color: #dc3545;' ?>">
                                                <?= strtoupper($client['status']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $client['amc'] == 'Yes' ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $client['amc'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_agency.php?id=<?= $client['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_agency.php?id=<?= $client['id'] ?>" class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="manage_agency.php?delete=<?= $client['id'] ?>" class="btn btn-danger btn-sm" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this client? This action cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_term) ?>">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search_term) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_term) ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top: 10px; color: #6c757d; font-size: 13px;">
                        Showing <?= count($clients) ?> of <?= $total_records ?> records
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Hamburger Menu Toggle
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (hamburgerBtn && sidebar) {
            function toggleSidebar() {
                sidebar.classList.toggle('open');
                if (overlay) {
                    overlay.classList.toggle('active');
                }
                // Toggle body overflow
                document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                if (overlay) {
                    overlay.classList.remove('active');
                }
                document.body.style.overflow = '';
            }

            hamburgerBtn.addEventListener('click', toggleSidebar);
            
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            // Close sidebar on window resize (when going from mobile to desktop)
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });

            // Close sidebar on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                    closeSidebar();
                }
            });
        }

        // Toggle Service Status via AJAX
        function toggleService(clientId, checkbox) {
            const status = checkbox.checked ? 'on' : 'off';
            const row = checkbox.closest('tr');
            const statusLabel = row.querySelector('td:nth-child(7) span:last-child');
            
            // Disable checkbox while processing
            checkbox.disabled = true;
            
            $.ajax({
                url: 'manage_agency.php',
                method: 'GET',
                data: {
                    toggle_service: clientId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the status label
                        statusLabel.textContent = status.toUpperCase();
                        statusLabel.style.color = status === 'on' ? '#28a745' : '#dc3545';
                        showToast('Service status updated successfully!', 'success');
                        // Refresh page to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        checkbox.checked = !checkbox.checked;
                        showToast(response.error || 'Failed to update service status', 'error');
                    }
                },
                error: function() {
                    checkbox.checked = !checkbox.checked;
                    showToast('Failed to update service status. Please try again.', 'error');
                },
                complete: function() {
                    checkbox.disabled = false;
                }
            });
        }

        // Toggle Status (Active/Inactive) via AJAX
        function toggleStatus(clientId, checkbox) {
            const status = checkbox.checked ? 'active' : 'inactive';
            const row = checkbox.closest('tr');
            const statusLabel = row.querySelector('td:nth-child(8) span:last-child');
            
            // Disable checkbox while processing
            checkbox.disabled = true;
            
            $.ajax({
                url: 'manage_agency.php',
                method: 'GET',
                data: {
                    toggle_status: clientId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the status label
                        statusLabel.textContent = status.toUpperCase();
                        statusLabel.style.color = status === 'active' ? '#28a745' : '#dc3545';
                        showToast('Status updated successfully!', 'success');
                        // Refresh page to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        checkbox.checked = !checkbox.checked;
                        showToast(response.error || 'Failed to update status', 'error');
                    }
                },
                error: function() {
                    checkbox.checked = !checkbox.checked;
                    showToast('Failed to update status. Please try again.', 'error');
                },
                complete: function() {
                    checkbox.disabled = false;
                }
            });
        }

        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = $(`
                <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 99999;">
                    <div class="toast show align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(toast);
            setTimeout(() => {
                toast.find('.toast').fadeOut('slow', function() {
                    toast.remove();
                });
            }, 3000);
        }
    </script>
</body>
</html>