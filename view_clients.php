<?php
// ==============================================
// View Clients
// File: view_clients.php
// ==============================================

// Start session FIRST - before ANY output
session_start();

// Include database connection
require_once 'db_connection.php';

// Set active page for sidebar
$active_page = 'view_clients';

// Fetch all clients - FIXED: changed 'clients' to 'client'
try {
    $stmt = $db->query("SELECT * FROM client ORDER BY id DESC");
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "❌ Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Clients</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th {
            background: #1a3c5e;
            color: white;
            padding: 12px;
            text-align: left;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
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

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .alert-error {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

            .container {
                padding: 15px;
                overflow-x: auto;
            }

            table {
                font-size: 12px;
            }

            table th, table td {
                padding: 8px;
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
                    <h1>📋 View All Clients</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <h2>Client List</h2>
                    <a href="add_client.php" class="btn btn-success">➕ Add New Client</a>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if (isset($clients) && count($clients) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Agency Name</th>
                                <th>Owner</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?php echo $client['id']; ?></td>
                                    <td><?php echo htmlspecialchars($client['agency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['mobile_no']); ?></td>
                                    <td><?php echo htmlspecialchars($client['mail_id']); ?></td>
                                    <td>
                                        <span style="padding: 3px 10px; border-radius: 3px; background: <?php echo $client['payment_status'] == 'Paid' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $client['payment_status'] == 'Paid' ? '#155724' : '#721c24'; ?>;">
                                            <?php echo $client['payment_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-warning">✏️ Edit</a>
                                        <a href="delete_client.php?id=<?php echo $client['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this client?')">🗑️ Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #666;">No clients found. <a href="add_client.php">Add your first client</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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