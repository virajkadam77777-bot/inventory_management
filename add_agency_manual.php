<?php
// ==============================================
// Add Agency Manually - Separate Page
// File: add_agency_manual.php
// ==============================================

session_start();

// Include database connection
require_once 'db_connection.php';

// Set active page for sidebar
$active_page = 'add_agency_manual';

// Initialize variables
$success_message = "";
$error_message = "";
$form_data = [];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_agency'])) {
    try {
        // Get form data
        $agency_name = trim($_POST['agency_name']);
        $customer_name = trim($_POST['customer_name']);
        $mobile_number = trim($_POST['mobile_number']);
        $alt_number = !empty($_POST['alt_number']) ? trim($_POST['alt_number']) : null;
        $mail_id = trim($_POST['mail_id']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $address = trim($_POST['address']);
        $feedback = !empty($_POST['feedback']) ? trim($_POST['feedback']) : null;
        $agency_type = $_POST['agency_type'];
        $status = $_POST['status'];
        $gst_number = !empty($_POST['gst_number']) ? trim($_POST['gst_number']) : null;
        $pincode = !empty($_POST['pincode']) ? trim($_POST['pincode']) : null;
        $country = !empty($_POST['country']) ? trim($_POST['country']) : 'India';
        $website = !empty($_POST['website']) ? trim($_POST['website']) : null;
        $opening_balance = !empty($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0.00;
        $credit_limit = !empty($_POST['credit_limit']) ? floatval($_POST['credit_limit']) : 0.00;
        $credit_days = !empty($_POST['credit_days']) ? intval($_POST['credit_days']) : 0;

        // Validate required fields
        if (empty($agency_name) || empty($customer_name) || empty($mobile_number) || 
            empty($mail_id) || empty($city) || empty($state) || empty($address)) {
            throw new Exception("All required fields must be filled!");
        }

        // Validate mobile number (10 digits)
        if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
            throw new Exception("Please enter a valid 10-digit mobile number!");
        }

        // Validate email
        if (!filter_var($mail_id, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address!");
        }

        // Check if mobile number already exists
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM agencies WHERE mobile_number = ?");
        $check_stmt->execute([$mobile_number]);
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("Mobile number already exists!");
        }

        // Check if email already exists
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM agencies WHERE mail_id = ?");
        $check_stmt->execute([$mail_id]);
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("Email ID already exists!");
        }

        // Prepare SQL query
        $sql = "INSERT INTO agencies (
            agency_name, customer_name, mobile_number, alt_number, mail_id,
            city, state, address, feedback, agency_type, status,
            gst_number, pincode, country, website,
            opening_balance, credit_limit, credit_days,
            created_by, created_at
        ) VALUES (
            :agency_name, :customer_name, :mobile_number, :alt_number, :mail_id,
            :city, :state, :address, :feedback, :agency_type, :status,
            :gst_number, :pincode, :country, :website,
            :opening_balance, :credit_limit, :credit_days,
            :created_by, NOW()
        )";

        $stmt = $db->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':agency_name', $agency_name);
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':mobile_number', $mobile_number);
        $stmt->bindParam(':alt_number', $alt_number);
        $stmt->bindParam(':mail_id', $mail_id);
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':state', $state);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':feedback', $feedback);
        $stmt->bindParam(':agency_type', $agency_type);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':gst_number', $gst_number);
        $stmt->bindParam(':pincode', $pincode);
        $stmt->bindParam(':country', $country);
        $stmt->bindParam(':website', $website);
        $stmt->bindParam(':opening_balance', $opening_balance);
        $stmt->bindParam(':credit_limit', $credit_limit);
        $stmt->bindParam(':credit_days', $credit_days);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);

        if ($stmt->execute()) {
            $agency_id = $db->lastInsertId();
            $success_message = "✅ Agency added successfully! Agency ID: #" . $agency_id;
            // Clear form fields
            $form_data = array();
            $_POST = array();
        } else {
            throw new Exception("Failed to add agency!");
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
    <title>Add Agency Manually</title>
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

        /* Sidebar Styles */
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

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 1000px;
            margin: 0 auto;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .form-header h2 {
            color: #1a3c5e;
            margin: 0;
        }

        .form-header .badge {
            font-size: 14px;
            padding: 8px 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 10px;
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
        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .section-title {
            grid-column: 1 / -1;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 700;
            color: #1a3c5e;
            margin: 10px 0 15px 0;
            border-left: 4px solid #1a3c5e;
            font-size: 16px;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
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
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Responsive */
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
                flex-wrap: wrap;
                gap: 10px;
            }

            .top-header h1 {
                font-size: 18px;
            }

            .user-info span {
                display: none;
            }

            .form-container {
                padding: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 10px;
            }

            .form-group label {
                font-size: 13px;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 13px;
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <?php include 'sidebar.php'; ?>

        <div class="content-area">
            <div class="top-header">
                <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                    <button class="hamburger" id="hamburgerBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><i class="fas fa-pencil-alt"></i> Add Agency Manually</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-building text-primary"></i> New Agency Registration</h2>
                    <span class="badge bg-primary">Fill all required fields (*)</span>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success_message ?>
                        <br><br>
                        <a href="add_agency_manual.php" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add Another
                        </a>
                        <a href="manage_agencies.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-building"></i> View All Agencies
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="agencyForm">
                    <div class="form-row">
                        <!-- Personal Information -->
                        <div class="section-title">🏢 Agency Information</div>

                        <div class="form-group">
                            <label>Agency Name <span class="required">*</span></label>
                            <input type="text" name="agency_name" required 
                                   value="<?= isset($_POST['agency_name']) ? htmlspecialchars($_POST['agency_name']) : '' ?>"
                                   placeholder="Enter agency name">
                        </div>

                        <div class="form-group">
                            <label>Customer Name <span class="required">*</span></label>
                            <input type="text" name="customer_name" required 
                                   value="<?= isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : '' ?>"
                                   placeholder="Enter customer name">
                        </div>

                        <div class="form-group">
                            <label>Mobile Number <span class="required">*</span></label>
                            <input type="text" name="mobile_number" required maxlength="10" 
                                   value="<?= isset($_POST['mobile_number']) ? htmlspecialchars($_POST['mobile_number']) : '' ?>"
                                   placeholder="Enter 10-digit mobile number">
                            <div class="help-text">Enter 10-digit mobile number</div>
                        </div>

                        <div class="form-group">
                            <label>Alternate Number</label>
                            <input type="text" name="alt_number" maxlength="10" 
                                   value="<?= isset($_POST['alt_number']) ? htmlspecialchars($_POST['alt_number']) : '' ?>"
                                   placeholder="Enter alternate number">
                        </div>

                        <div class="form-group">
                            <label>Email ID <span class="required">*</span></label>
                            <input type="email" name="mail_id" required 
                                   value="<?= isset($_POST['mail_id']) ? htmlspecialchars($_POST['mail_id']) : '' ?>"
                                   placeholder="Enter email address">
                        </div>

                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" required 
                                   value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>"
                                   placeholder="Enter city">
                        </div>

                        <div class="form-group">
                            <label>State <span class="required">*</span></label>
                            <input type="text" name="state" required 
                                   value="<?= isset($_POST['state']) ? htmlspecialchars($_POST['state']) : '' ?>"
                                   placeholder="Enter state">
                        </div>

                        <div class="form-group">
                            <label>Pincode</label>
                            <input type="text" name="pincode" maxlength="10" 
                                   value="<?= isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : '' ?>"
                                   placeholder="Enter pincode">
                        </div>

                        <div class="form-group full-width">
                            <label>Address <span class="required">*</span></label>
                            <textarea name="address" rows="3" required 
                                      placeholder="Enter complete address"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                        </div>

                        <!-- Business Details -->
                        <div class="section-title">💼 Business Details</div>

                        <div class="form-group">
                            <label>Agency Type</label>
                            <select name="agency_type">
                                <option value="customer" <?= (isset($_POST['agency_type']) && $_POST['agency_type'] == 'customer') ? 'selected' : '' ?>>Customer</option>
                                <option value="distributor" <?= (isset($_POST['agency_type']) && $_POST['agency_type'] == 'distributor') ? 'selected' : '' ?>>Distributor</option>
                                <option value="retailer" <?= (isset($_POST['agency_type']) && $_POST['agency_type'] == 'retailer') ? 'selected' : '' ?>>Retailer</option>
                                <option value="wholesaler" <?= (isset($_POST['agency_type']) && $_POST['agency_type'] == 'wholesaler') ? 'selected' : '' ?>>Wholesaler</option>
                                <option value="supplier" <?= (isset($_POST['agency_type']) && $_POST['agency_type'] == 'supplier') ? 'selected' : '' ?>>Supplier</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?= (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                <option value="pending" <?= (isset($_POST['status']) && $_POST['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="suspended" <?= (isset($_POST['status']) && $_POST['status'] == 'suspended') ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" name="gst_number" maxlength="50" 
                                   value="<?= isset($_POST['gst_number']) ? htmlspecialchars($_POST['gst_number']) : '' ?>"
                                   placeholder="Enter GST number">
                            <div class="help-text">Format: 22AAAAA0000A1Z5</div>
                        </div>

                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" 
                                   value="<?= isset($_POST['country']) ? htmlspecialchars($_POST['country']) : 'India' ?>"
                                   placeholder="Enter country">
                        </div>

                        <div class="form-group">
                            <label>Website</label>
                            <input type="text" name="website" 
                                   value="<?= isset($_POST['website']) ? htmlspecialchars($_POST['website']) : '' ?>"
                                   placeholder="Enter website URL">
                            <div class="help-text">e.g., www.example.com</div>
                        </div>

                        <!-- Financial Details -->
                        <div class="section-title">💰 Financial Details</div>

                        <div class="form-group">
                            <label>Opening Balance</label>
                            <input type="number" step="0.01" min="0" name="opening_balance" 
                                   value="<?= isset($_POST['opening_balance']) ? $_POST['opening_balance'] : '0.00' ?>">
                        </div>

                        <div class="form-group">
                            <label>Credit Limit</label>
                            <input type="number" step="0.01" min="0" name="credit_limit" 
                                   value="<?= isset($_POST['credit_limit']) ? $_POST['credit_limit'] : '0.00' ?>">
                        </div>

                        <div class="form-group">
                            <label>Credit Days</label>
                            <input type="number" min="0" name="credit_days" 
                                   value="<?= isset($_POST['credit_days']) ? $_POST['credit_days'] : '0' ?>">
                            <div class="help-text">Number of credit days allowed</div>
                        </div>

                        <div class="form-group full-width">
                            <label>Feedback</label>
                            <textarea name="feedback" rows="2" 
                                      placeholder="Enter any feedback or notes"><?= isset($_POST['feedback']) ? htmlspecialchars($_POST['feedback']) : '' ?></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" name="submit_agency" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Agency
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                            <a href="manage_agencies.php" class="btn btn-primary">
                                <i class="fas fa-building"></i> Manage Agencies
                            </a>
                            <a href="upload_data.php" class="btn btn-info">
                                <i class="fas fa-upload"></i> Upload Data
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // =============================================
        // Hamburger Menu Toggle
        // =============================================
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (hamburgerBtn && sidebar) {
            function toggleSidebar() {
                sidebar.classList.toggle('open');
                if (overlay) {
                    overlay.classList.toggle('active');
                }
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

            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                    closeSidebar();
                }
            });
        }

        // =============================================
        // Form Validation
        // =============================================
        document.getElementById('agencyForm').addEventListener('submit', function(e) {
            const mobile = document.querySelector('input[name="mobile_number"]').value;
            const email = document.querySelector('input[name="mail_id"]').value;
            
            // Validate mobile number
            if (mobile && !/^[0-9]{10}$/.test(mobile)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit mobile number!');
                return false;
            }
            
            // Validate email
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address!');
                return false;
            }
        });

        // =============================================
        // Auto-format mobile number
        // =============================================
        document.querySelector('input[name="mobile_number"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        document.querySelector('input[name="alt_number"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        document.querySelector('input[name="pincode"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    </script>
</body>
</html>