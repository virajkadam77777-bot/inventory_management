<?php
// ==============================================
// Edit Client Form Handler
// File: edit_client.php
// ==============================================

// Start session FIRST - before ANY output
session_start();

// Include database connection
require_once 'db_connection.php';

// Set active page for sidebar
$active_page = 'view_clients';

// Initialize variables
$success_message = "";
$error_message = "";
$client = null;

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_clients.php");
    exit();
}

$client_id = intval($_GET['id']);

// Fetch client data
try {
    $stmt = $db->prepare("SELECT * FROM client WHERE id = :id");
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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $agency_name = trim($_POST['agency_name']);
        $owner_name = trim($_POST['owner_name']);
        $mobile_no = trim($_POST['mobile_no']);
        $support_alt_no = !empty($_POST['support_alt_no']) ? trim($_POST['support_alt_no']) : null;
        $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
        $new_address = !empty($_POST['new_address']) ? trim($_POST['new_address']) : null; // NEW FIELD
        $mail_id = !empty($_POST['mail_id']) ? trim($_POST['mail_id']) : null;
        $purchase_rental = $_POST['purchase_rental'];
        $only_software = !empty($_POST['only_software']) ? $_POST['only_software'] : 'No';
        $gateway_quantity = !empty($_POST['gateway_quantity']) ? intval($_POST['gateway_quantity']) : 0;
        $gateway_name = !empty($_POST['gateway_name']) ? $_POST['gateway_name'] : null;
        $gateway_mac_id = !empty($_POST['gateway_mac_id']) ? trim($_POST['gateway_mac_id']) : null;
        $server_quantity = !empty($_POST['server_quantity']) ? intval($_POST['server_quantity']) : 0;
        $server_name = !empty($_POST['server_name']) ? trim($_POST['server_name']) : null;
        $server_mac_id = !empty($_POST['server_mac_id']) ? trim($_POST['server_mac_id']) : null;
        $gateway_price = !empty($_POST['gateway_price']) ? floatval($_POST['gateway_price']) : 0.00;
        $server_price = !empty($_POST['server_price']) ? floatval($_POST['server_price']) : 0.00;
        $amc = !empty($_POST['amc']) ? $_POST['amc'] : 'No';
        $amc_expiry = !empty($_POST['amc_expiry']) ? $_POST['amc_expiry'] : null;
        $services = !empty($_POST['services']) ? $_POST['services'] : 'off'; // NEW FIELD
        $payment_status = !empty($_POST['payment_status']) ? $_POST['payment_status'] : 'Unpaid';
        $total_outstanding = !empty($_POST['total_outstanding']) ? floatval($_POST['total_outstanding']) : 0.00;
        $headphones_total_count = !empty($_POST['headphones_total_count']) ? intval($_POST['headphones_total_count']) : 0;
        $headphones_price = !empty($_POST['headphones_price']) ? floatval($_POST['headphones_price']) : 0.00;
        $unpaid_headphones_price = !empty($_POST['unpaid_headphones_price']) ? floatval($_POST['unpaid_headphones_price']) : 0.00;
        $gst_number = !empty($_POST['gst_number']) ? trim($_POST['gst_number']) : null;

        // Prepare SQL query for UPDATE - UPDATED with new_address and services
        $sql = "UPDATE client SET 
            agency_name = :agency_name,
            owner_name = :owner_name,
            mobile_no = :mobile_no,
            support_alt_no = :support_alt_no,
            address = :address,
            new_address = :new_address,
            mail_id = :mail_id,
            purchase_rental = :purchase_rental,
            only_software = :only_software,
            gateway_quantity = :gateway_quantity,
            gateway_name = :gateway_name,
            gateway_mac_id = :gateway_mac_id,
            server_quantity = :server_quantity,
            server_name = :server_name,
            server_mac_id = :server_mac_id,
            gateway_price = :gateway_price,
            server_price = :server_price,
            amc = :amc,
            amc_expiry = :amc_expiry,
            services = :services,
            payment_status = :payment_status,
            total_outstanding = :total_outstanding,
            headphones_total_count = :headphones_total_count,
            headphones_price = :headphones_price,
            unpaid_headphones_price = :unpaid_headphones_price,
            gst_number = :gst_number
        WHERE id = :id";

        // Prepare and execute the statement
        $stmt = $db->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':agency_name', $agency_name);
        $stmt->bindParam(':owner_name', $owner_name);
        $stmt->bindParam(':mobile_no', $mobile_no);
        $stmt->bindParam(':support_alt_no', $support_alt_no);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':new_address', $new_address);
        $stmt->bindParam(':mail_id', $mail_id);
        $stmt->bindParam(':purchase_rental', $purchase_rental);
        $stmt->bindParam(':only_software', $only_software);
        $stmt->bindParam(':gateway_quantity', $gateway_quantity);
        $stmt->bindParam(':gateway_name', $gateway_name);
        $stmt->bindParam(':gateway_mac_id', $gateway_mac_id);
        $stmt->bindParam(':server_quantity', $server_quantity);
        $stmt->bindParam(':server_name', $server_name);
        $stmt->bindParam(':server_mac_id', $server_mac_id);
        $stmt->bindParam(':gateway_price', $gateway_price);
        $stmt->bindParam(':server_price', $server_price);
        $stmt->bindParam(':amc', $amc);
        $stmt->bindParam(':amc_expiry', $amc_expiry);
        $stmt->bindParam(':services', $services);
        $stmt->bindParam(':payment_status', $payment_status);
        $stmt->bindParam(':total_outstanding', $total_outstanding);
        $stmt->bindParam(':headphones_total_count', $headphones_total_count);
        $stmt->bindParam(':headphones_price', $headphones_price);
        $stmt->bindParam(':unpaid_headphones_price', $unpaid_headphones_price);
        $stmt->bindParam(':gst_number', $gst_number);
        $stmt->bindParam(':id', $client_id);

        // Execute the statement
        if ($stmt->execute()) {
            $success_message = "✅ Client updated successfully!";
            // Refresh client data
            $stmt = $db->prepare("SELECT * FROM client WHERE id = :id");
            $stmt->bindParam(':id', $client_id);
            $stmt->execute();
            $client = $stmt->fetch();
        } else {
            throw new Exception("Error updating client!");
        }

    } catch (PDOException $e) {
        $error_message = "❌ Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        $error_message = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client</title>
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .form-group label .required {
            color: red;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a3c5e;
            box-shadow: 0 0 0 3px rgba(26, 60, 94, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-group .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        .section-title {
            grid-column: 1 / -1;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 700;
            color: #1a3c5e;
            margin: 10px 0;
            border-left: 4px solid #1a3c5e;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            flex-wrap: wrap;
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
        }
        .btn-primary {
            background: #1a3c5e;
            color: white;
        }
        .btn-primary:hover {
            background: #0f2a42;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 60, 94, 0.3);
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
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
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

        /* Toggle Switch Styles for Services */
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-top: 5px;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 34px;
            display: inline-block;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background-color: #28a745;
        }
        .toggle-switch input:focus + .toggle-slider {
            box-shadow: 0 0 1px #28a745;
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .toggle-label {
            font-weight: 600;
            font-size: 14px;
        }
        .toggle-label.on {
            color: #28a745;
        }
        .toggle-label.off {
            color: #dc3545;
        }

        @media (max-width: 992px) {
            .form-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 10px;
                width: 100%;
                max-width: 100%;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 15px;
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

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
                text-align: center;
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
                    <h1>✏️ Edit Client</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <div class="container">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($client): ?>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="section-title">🏢 Personal Information</div>

                        <div class="form-group">
                            <label>Agency Name <span class="required">*</span></label>
                            <input type="text" name="agency_name" required value="<?php echo htmlspecialchars($client['agency_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Owner Name <span class="required">*</span></label>
                            <input type="text" name="owner_name" required value="<?php echo htmlspecialchars($client['owner_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Mobile Number <span class="required">*</span></label>
                            <input type="text" name="mobile_no" required maxlength="15" value="<?php echo htmlspecialchars($client['mobile_no']); ?>">
                            <div class="help-text">Enter 10-digit mobile number</div>
                        </div>

                        <div class="form-group">
                            <label>Support Alternative Number</label>
                            <input type="text" name="support_alt_no" maxlength="15" value="<?php echo htmlspecialchars($client['support_alt_no']); ?>">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Address</label>
                            <textarea name="address" rows="2"><?php echo htmlspecialchars($client['address']); ?></textarea>
                        </div>

                        <!-- NEW: New Address Field -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>New/Alternate Address</label>
                            <textarea name="new_address" rows="2" placeholder="Enter new/alternate address if different from above"><?php echo htmlspecialchars($client['new_address']); ?></textarea>
                            <div class="help-text">If the client has moved or has a secondary address</div>
                        </div>

                        <div class="form-group">
                            <label>Email ID</label>
                            <input type="email" name="mail_id" value="<?php echo htmlspecialchars($client['mail_id']); ?>">
                        </div>

                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" name="gst_number" maxlength="20" value="<?php echo htmlspecialchars($client['gst_number']); ?>">
                            <div class="help-text">Format: 22AAAAA0000A1Z5</div>
                        </div>

                        <div class="section-title">💼 Business Details</div>

                        <div class="form-group">
                            <label>Purchase/Rental <span class="required">*</span></label>
                            <select name="purchase_rental" required>
                                <option value="">Select...</option>
                                <option value="Purchase" <?php echo ($client['purchase_rental'] == 'Purchase') ? 'selected' : ''; ?>>Purchase</option>
                                <option value="Rental" <?php echo ($client['purchase_rental'] == 'Rental') ? 'selected' : ''; ?>>Rental</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Only Software</label>
                            <select name="only_software">
                                <option value="No" <?php echo ($client['only_software'] == 'No') ? 'selected' : ''; ?>>No</option>
                                <option value="Yes" <?php echo ($client['only_software'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>

                        <!-- NEW: Services Toggle Field -->
                        <div class="form-group">
                            <label>Services <span class="required">*</span></label>
                            <div class="toggle-container">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="services_toggle" id="servicesToggle" <?php echo ($client['services'] == 'on') ? 'checked' : ''; ?> onchange="updateServicesValue()">
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="toggle-label <?php echo ($client['services'] == 'on') ? 'on' : 'off'; ?>" id="servicesLabel">
                                    <?php echo ($client['services'] == 'on') ? 'ON' : 'OFF'; ?>
                                </span>
                            </div>
                            <input type="hidden" name="services" id="servicesHidden" value="<?php echo $client['services'] ?? 'off'; ?>">
                            <div class="help-text">Toggle ON to enable services for this client</div>
                        </div>

                        <div class="form-group">
                            <label>Payment Status</label>
                            <select name="payment_status">
                                <option value="Unpaid" <?php echo ($client['payment_status'] == 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="Paid" <?php echo ($client['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Total Outstanding</label>
                            <input type="number" step="0.01" min="0" name="total_outstanding" value="<?php echo $client['total_outstanding']; ?>">
                        </div>

                        <div class="form-group">
                            <label>AMC</label>
                            <select name="amc">
                                <option value="No" <?php echo ($client['amc'] == 'No') ? 'selected' : ''; ?>>No</option>
                                <option value="Yes" <?php echo ($client['amc'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>AMC Expiry</label>
                            <input type="datetime-local" name="amc_expiry" value="<?php echo $client['amc_expiry']; ?>">
                        </div>

                        <div class="section-title">🛠️ Gateway Details</div>

                        <div class="form-group">
                            <label>Gateway Quantity</label>
                            <input type="number" min="0" name="gateway_quantity" value="<?php echo $client['gateway_quantity']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Gateway Name</label>
                            <select name="gateway_name">
                                <option value="">Select...</option>
                                <option value="OpenVox" <?php echo ($client['gateway_name'] == 'OpenVox') ? 'selected' : ''; ?>>OpenVox</option>
                                <option value="Dinstar" <?php echo ($client['gateway_name'] == 'Dinstar') ? 'selected' : ''; ?>>Dinstar</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Gateway MAC ID</label>
                            <input type="text" name="gateway_mac_id" maxlength="100" value="<?php echo htmlspecialchars($client['gateway_mac_id']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Gateway Price</label>
                            <input type="number" step="0.01" min="0" name="gateway_price" value="<?php echo $client['gateway_price']; ?>">
                        </div>

                        <div class="section-title">🖥️ Server Details</div>

                        <div class="form-group">
                            <label>Server Quantity</label>
                            <input type="number" min="0" name="server_quantity" value="<?php echo $client['server_quantity']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Server Name</label>
                            <input type="text" name="server_name" maxlength="150" value="<?php echo htmlspecialchars($client['server_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Server MAC ID</label>
                            <input type="text" name="server_mac_id" maxlength="100" value="<?php echo htmlspecialchars($client['server_mac_id']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Server Price</label>
                            <input type="number" step="0.01" min="0" name="server_price" value="<?php echo $client['server_price']; ?>">
                        </div>

                        <div class="section-title">🎧 Headphones Details</div>

                        <div class="form-group">
                            <label>Headphones Total Count</label>
                            <input type="number" min="0" name="headphones_total_count" value="<?php echo $client['headphones_total_count']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Headphones Price</label>
                            <input type="number" step="0.01" min="0" name="headphones_price" value="<?php echo $client['headphones_price']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Unpaid Headphones Price</label>
                            <input type="number" step="0.01" min="0" name="unpaid_headphones_price" value="<?php echo $client['unpaid_headphones_price']; ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Update Client</button>
                            <a href="view_clients.php" class="btn btn-secondary">⬅️ Cancel</a>
                            <a href="view_clients.php" class="btn btn-success">📋 View All Clients</a>
                        </div>
                    </div>
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

        // Services Toggle Function
        function updateServicesValue() {
            const toggle = document.getElementById('servicesToggle');
            const hidden = document.getElementById('servicesHidden');
            const label = document.getElementById('servicesLabel');
            
            if (toggle.checked) {
                hidden.value = 'on';
                label.textContent = 'ON';
                label.className = 'toggle-label on';
            } else {
                hidden.value = 'off';
                label.textContent = 'OFF';
                label.className = 'toggle-label off';
            }
        }

        // Initialize services toggle on page load
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('servicesToggle');
            const hidden = document.getElementById('servicesHidden');
            const label = document.getElementById('servicesLabel');
            
            if (hidden.value === 'on') {
                toggle.checked = true;
                label.textContent = 'ON';
                label.className = 'toggle-label on';
            } else {
                toggle.checked = false;
                label.textContent = 'OFF';
                label.className = 'toggle-label off';
            }
        });
    </script>
</body>
</html>