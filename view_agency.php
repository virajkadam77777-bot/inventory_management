<?php
// ==============================================
// View Agency Details
// File: view_agency.php
// ==============================================

session_start();

// Include database connection
require_once 'db_connection.php';

// Set active page for sidebar
$active_page = 'manage_agency';

// Initialize variables
$agency = null;
$error_message = "";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_agency.php");
    exit();
}

$client_id = intval($_GET['id']);

// Fetch agency data
try {
    $stmt = $db->prepare("SELECT * FROM client WHERE id = :id");
    $stmt->bindParam(':id', $client_id);
    $stmt->execute();
    $agency = $stmt->fetch();
    
    if (!$agency) {
        header("Location: manage_agency.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = "❌ Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Agency Details</title>
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
        }
        
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            padding: 20px;
            min-height: 100vh;
            margin-left: 250px;
            width: calc(100% - 250px);
            max-width: calc(100% - 250px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .top-header h1 {
            color: #1a3c5e;
            margin: 0;
            padding: 0;
            border: none;
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

        .agency-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .agency-title h2 {
            margin: 0;
            color: #1a3c5e;
        }

        .agency-title .sub-info {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }

        .agency-id-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #1a3c5e;
            color: white;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .detail-section {
            margin-bottom: 30px;
        }

        .detail-section h4 {
            color: #1a3c5e;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .detail-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-item .label {
            font-weight: 600;
            color: #555;
            width: 180px;
            flex-shrink: 0;
        }

        .detail-item .value {
            color: #333;
            flex: 1;
        }

        .detail-item .value .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #cce5ff; color: #004085; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
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
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .service-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
        }

        .service-status-badge.on {
            background: #d4edda;
            color: #155724;
        }

        .service-status-badge.off {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 992px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 10px;
                width: 100%;
                max-width: 100%;
            }

            .hamburger {
                display: block;
            }

            .top-header {
                flex-wrap: wrap;
                gap: 10px;
            }

            .top-header h1 {
                font-size: 18px;
            }

            .user-info span {
                display: none;
            }

            .container {
                padding: 15px;
            }

            .agency-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-item {
                flex-direction: column;
                gap: 5px;
            }

            .detail-item .label {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .top-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-info {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-area">
            <div class="top-header">
                <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                    <button class="hamburger" id="hamburgerBtn">☰</button>
                    <h1><i class="fas fa-building"></i> Agency Details</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <div class="container">
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($agency): ?>
                <!-- Agency Header -->
                <div class="agency-header">
                    <div class="agency-title">
                        <h2><i class="fas fa-star" style="color: #ffc107;"></i> <?php echo htmlspecialchars($agency['agency_name']); ?></h2>
                        <div class="sub-info">
                            <span><i class="fas fa-user"></i> Owner: <?php echo htmlspecialchars($agency['owner_name']); ?></span>
                            <span style="margin-left: 15px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($agency['mobile_no']); ?></span>
                            <?php if ($agency['mail_id']): ?>
                                <span style="margin-left: 15px;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($agency['mail_id']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <span class="agency-id-badge">#<?= $agency['id'] ?></span>
                        <span style="margin-left: 10px; font-size: 14px; color: #6c757d;">
                            <i class="fas fa-calendar"></i> <?= date('d M Y, h:i A', strtotime($agency['created_at'])) ?>
                        </span>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="detail-grid">
                    <!-- Left Column -->
                    <div>
                        <!-- Personal Information -->
                        <div class="detail-section">
                            <h4><i class="fas fa-user-circle"></i> Personal Information</h4>
                            <div class="detail-item">
                                <span class="label">Agency Name</span>
                                <span class="value"><strong><?php echo htmlspecialchars($agency['agency_name']); ?></strong></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Owner Name</span>
                                <span class="value"><?php echo htmlspecialchars($agency['owner_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Mobile Number</span>
                                <span class="value"><?php echo htmlspecialchars($agency['mobile_no']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Support Number</span>
                                <span class="value"><?php echo htmlspecialchars($agency['support_alt_no']) ?: 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Email ID</span>
                                <span class="value"><?php echo htmlspecialchars($agency['mail_id']) ?: 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">GST Number</span>
                                <span class="value"><?php echo htmlspecialchars($agency['gst_number']) ?: 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Address</span>
                                <span class="value"><?php echo nl2br(htmlspecialchars($agency['address'])) ?: 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">New Address</span>
                                <span class="value"><?php echo nl2br(htmlspecialchars($agency['new_address'])) ?: 'N/A'; ?></span>
                            </div>
                        </div>

                        <!-- Business Details -->
                        <div class="detail-section">
                            <h4><i class="fas fa-briefcase"></i> Business Details</h4>
                            <div class="detail-item">
                                <span class="label">Type</span>
                                <span class="value">
                                    <span class="badge <?= $agency['purchase_rental'] == 'Purchase' ? 'badge-primary' : 'badge-warning' ?>">
                                        <?= $agency['purchase_rental'] ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Only Software</span>
                                <span class="value">
                                    <span class="badge <?= $agency['only_software'] == 'Yes' ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= $agency['only_software'] ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Services</span>
                                <span class="value">
                                    <span class="service-status-badge <?= $agency['services'] ?>">
                                        <i class="fas fa-<?= $agency['services'] == 'on' ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= strtoupper($agency['services']) ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Payment Status</span>
                                <span class="value">
                                    <span class="badge <?= $agency['payment_status'] == 'Paid' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $agency['payment_status'] ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Total Outstanding</span>
                                <span class="value">₹ <?= number_format($agency['total_outstanding'], 2) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">AMC</span>
                                <span class="value">
                                    <span class="badge <?= $agency['amc'] == 'Yes' ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= $agency['amc'] ?>
                                    </span>
                                    <?php if ($agency['amc'] == 'Yes' && $agency['amc_expiry']): ?>
                                        <span style="margin-left: 10px; font-size: 12px; color: #6c757d;">
                                            Expires: <?= date('d M Y', strtotime($agency['amc_expiry'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <!-- Gateway Details -->
                        <div class="detail-section">
                            <h4><i class="fas fa-router"></i> Gateway Details</h4>
                            <div class="detail-item">
                                <span class="label">Quantity</span>
                                <span class="value"><?= $agency['gateway_quantity'] ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Gateway Name</span>
                                <span class="value"><?= htmlspecialchars($agency['gateway_name']) ?: 'N/A' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">MAC ID</span>
                                <span class="value"><?= htmlspecialchars($agency['gateway_mac_id']) ?: 'N/A' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Price</span>
                                <span class="value">₹ <?= number_format($agency['gateway_price'], 2) ?></span>
                            </div>
                        </div>

                        <!-- Server Details -->
                        <div class="detail-section">
                            <h4><i class="fas fa-server"></i> Server Details</h4>
                            <div class="detail-item">
                                <span class="label">Quantity</span>
                                <span class="value"><?= $agency['server_quantity'] ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Server Name</span>
                                <span class="value"><?= htmlspecialchars($agency['server_name']) ?: 'N/A' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">MAC ID</span>
                                <span class="value"><?= htmlspecialchars($agency['server_mac_id']) ?: 'N/A' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Price</span>
                                <span class="value">₹ <?= number_format($agency['server_price'], 2) ?></span>
                            </div>
                        </div>

                        <!-- Headphones Details -->
                        <div class="detail-section">
                            <h4><i class="fas fa-headphones"></i> Headphones Details</h4>
                            <div class="detail-item">
                                <span class="label">Total Count</span>
                                <span class="value"><?= $agency['headphones_total_count'] ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Price</span>
                                <span class="value">₹ <?= number_format($agency['headphones_price'], 2) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Unpaid Price</span>
                                <span class="value">₹ <?= number_format($agency['unpaid_headphones_price'], 2) ?></span>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="detail-section">
                            <h4><i class="fas fa-info-circle"></i> System Information</h4>
                            <div class="detail-item">
                                <span class="label">Created At</span>
                                <span class="value"><?= date('d M Y, h:i A', strtotime($agency['created_at'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Last Updated</span>
                                <span class="value"><?= date('d M Y, h:i A', strtotime($agency['updated_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="edit_agency.php?id=<?= $agency['id'] ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Agency
                    </a>
                    <a href="manage_agency.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="manage_agency.php?delete=<?= $agency['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this agency? This action cannot be undone!')">
                        <i class="fas fa-trash"></i> Delete Agency
                    </a>
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
        const overlay = document.querySelector('.sidebar-overlay');

        if (hamburgerBtn && sidebar) {
            function toggleSidebar() {
                sidebar.classList.toggle('open');
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                if (overlay) {
                    overlay.classList.remove('active');
                }
            }

            hamburgerBtn.addEventListener('click', toggleSidebar);
            
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });
        }
    </script>
</body>
</html>