<?php
// ==============================================
// Delete Client Handler
// File: delete_client.php
// ==============================================

// Start session FIRST - before ANY output
session_start();

// Include database connection
require_once 'db_connection.php';

// Initialize variables
$error_message = "";
$success_message = "";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_clients.php");
    exit();
}

$client_id = intval($_GET['id']);

// Fetch client data for confirmation
try {
    $stmt = $db->prepare("SELECT agency_name FROM client WHERE id = :id");
    $stmt->bindParam(':id', $client_id);
    $stmt->execute();
    $client = $stmt->fetch();
    
    if (!$client) {
        header("Location: view_clients.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = "❌ Database Error: " . $e->getMessage();
}

// Process deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
        try {
            // Delete the client
            $stmt = $db->prepare("DELETE FROM client WHERE id = :id");
            $stmt->bindParam(':id', $client_id);
            
            if ($stmt->execute()) {
                $success_message = "✅ Client deleted successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=view_clients.php");
            } else {
                throw new Exception("Error deleting client!");
            }
        } catch (PDOException $e) {
            $error_message = "❌ Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = "❌ Error: " . $e->getMessage();
        }
    } else {
        header("Location: view_clients.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Client</title>
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
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
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
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }

        .warning-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .client-name {
            font-weight: bold;
            color: #1a3c5e;
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 10px;
                width: 100%;
                max-width: 100%;
            }

            .container {
                margin: 20px auto;
                padding: 20px;
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

            .btn {
                width: 100%;
                margin: 5px 0;
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
                    <h1>🗑️ Delete Client</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <div class="container">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <div style="margin-top: 20px;">
                        <p>Redirecting to client list...</p>
                        <a href="view_clients.php" class="btn btn-success">📋 Go to Client List</a>
                    </div>
                <?php elseif ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                    <a href="view_clients.php" class="btn btn-secondary">⬅️ Back to Client List</a>
                <?php elseif ($client): ?>
                    <div class="warning-icon">⚠️</div>
                    <h2 style="color: #dc3545; margin-bottom: 20px;">Delete Confirmation</h2>
                    <div class="alert alert-warning">
                        <p style="font-size: 18px;">Are you sure you want to delete this client?</p>
                        <p style="margin-top: 10px;">
                            <span class="client-name">"<?php echo htmlspecialchars($client['agency_name']); ?>"</span>
                        </p>
                        <p style="margin-top: 10px; color: #856404; font-size: 14px;">
                            <strong>⚠️ Warning:</strong> This action cannot be undone!
                        </p>
                    </div>

                    <form method="POST" action="" style="margin-top: 20px;">
                        <input type="hidden" name="confirm" value="yes">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this client?')">
                            🗑️ Yes, Delete Client
                        </button>
                        <a href="view_clients.php" class="btn btn-secondary">❌ Cancel</a>
                    </form>
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