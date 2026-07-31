<?php
// ==============================================
// Upload Data Page - Agency Data Upload (CSV Only)
// File: upload_data.php
// ==============================================

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Define base path for includes
$base_path = dirname(__FILE__);

// ==============================================
// Database Connection
// ==============================================
$conn = null;
$db_connected = false;

// Try multiple possible paths for db_connection
$db_file_paths = [
    $base_path . '/db_connection.php',
    $base_path . '/config/db_connection.php',
    $base_path . '/../db_connection.php',
    $base_path . '/../config/db_connection.php'
];

foreach ($db_file_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        if (isset($conn) && $conn instanceof mysqli) {
            $db_connected = true;
            break;
        }
    }
}

// If no connection found, try to create one
if (!$db_connected) {
    // Database configuration
    $db_host = 'localhost';
    $db_user = 'root';
    $db_password = '';
    $db_name = 'inventory_management';
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $db_connected = true;
    } catch (Exception $e) {
        $message = "Database connection failed: " . $e->getMessage();
        $message_type = "danger";
        $conn = null;
    }
}

// Get user ID
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id']) ? $_SESSION['id'] : null);

// Initialize variables
$message = '';
$message_type = '';
$uploaded_count = 0;
$error_count = 0;
$errors = [];

// ==============================================
// Handle Manual Form Submission
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_submit'])) {
    // Check if database is connected
    if (!$conn) {
        $message = "Database connection is not available. Please check your configuration.";
        $message_type = "danger";
    } else {
        // Get form data
        $agency_name = trim($_POST['agency_name'] ?? '');
        $customer_name = trim($_POST['customer_name'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $alt_number = trim($_POST['alt_number'] ?? '');
        $mail_id = trim($_POST['mail_id'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $feedback = trim($_POST['feedback'] ?? '');
        $agency_type = trim($_POST['agency_type'] ?? 'customer');
        $status = trim($_POST['status'] ?? 'active');
        $gst_number = trim($_POST['gst_number'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $country = trim($_POST['country'] ?? 'India');
        $website = trim($_POST['website'] ?? '');
        $opening_balance = floatval($_POST['opening_balance'] ?? 0);
        $credit_limit = floatval($_POST['credit_limit'] ?? 0);
        $credit_days = intval($_POST['credit_days'] ?? 0);

        // Validation
        $validation_errors = [];
        
        if (empty($agency_name)) $validation_errors[] = "Agency Name is required";
        if (empty($customer_name)) $validation_errors[] = "Customer Name is required";
        if (empty($mobile_number)) $validation_errors[] = "Mobile Number is required";
        if (empty($mail_id)) $validation_errors[] = "Email ID is required";
        if (empty($city)) $validation_errors[] = "City is required";
        if (empty($state)) $validation_errors[] = "State is required";
        if (empty($address)) $validation_errors[] = "Address is required";

        // Check for duplicate mobile number
        if (!empty($mobile_number) && $conn) {
            $check_query = "SELECT id FROM agencies WHERE mobile_number = ?";
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("s", $mobile_number);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows > 0) {
                    $validation_errors[] = "Mobile number already exists in the system";
                }
                $check_stmt->close();
            }
        }

        // Check for duplicate email
        if (!empty($mail_id) && $conn) {
            $check_query = "SELECT id FROM agencies WHERE mail_id = ?";
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("s", $mail_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows > 0) {
                    $validation_errors[] = "Email ID already exists in the system";
                }
                $check_stmt->close();
            }
        }

        if (empty($validation_errors) && $conn) {
            // Insert data
            $insert_query = "INSERT INTO agencies (
                agency_name, customer_name, mobile_number, alt_number, mail_id,
                city, state, address, feedback, agency_type, status,
                gst_number, pincode, country, website,
                opening_balance, credit_limit, credit_days,
                created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param(
                    "sssssssssssssssddii",
                    $agency_name, $customer_name, $mobile_number, $alt_number, $mail_id,
                    $city, $state, $address, $feedback, $agency_type, $status,
                    $gst_number, $pincode, $country, $website,
                    $opening_balance, $credit_limit, $credit_days,
                    $user_id
                );

                if ($stmt->execute()) {
                    $message = "Agency added successfully!";
                    $message_type = "success";
                    $uploaded_count = 1;
                } else {
                    $message = "Error adding agency: " . $conn->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Error preparing query: " . $conn->error;
                $message_type = "danger";
            }
        } else {
            $message = implode("<br>", $validation_errors);
            $message_type = "danger";
            $error_count = count($validation_errors);
        }
    }
}

// ==============================================
// Handle CSV File Upload
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_submit'])) {
    // Check if database is connected
    if (!$conn) {
        $message = "Database connection is not available. Please check your configuration.";
        $message_type = "danger";
    } elseif (isset($_FILES['data_file']) && $_FILES['data_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['data_file']['tmp_name'];
        $file_name = $_FILES['data_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check file size (5MB max)
        if ($_FILES['data_file']['size'] > 5 * 1024 * 1024) {
            $message = "File size exceeds 5MB limit.";
            $message_type = "danger";
        } else {
            // Only allow CSV files
            if ($file_ext !== 'csv') {
                $message = "Only CSV files are allowed. Please convert your Excel file to CSV format.";
                $message_type = "danger";
            } else {
                try {
                    // Read CSV file data
                    $data = [];
                    
                    if (($handle = fopen($file_tmp, "r")) !== false) {
                        // Read header row
                        $header = fgetcsv($handle);
                        
                        if ($header) {
                            // Clean header (remove BOM and trim)
                            $header = array_map(function($col) {
                                return trim(str_replace("\xEF\xBB\xBF", '', $col));
                            }, $header);
                            
                            // Read data rows
                            while (($row = fgetcsv($handle)) !== false) {
                                // Ensure row has same number of columns as header
                                if (count($header) === count($row)) {
                                    $data[] = array_combine($header, $row);
                                }
                            }
                        }
                        fclose($handle);
                    }
                    
                    // Process data if we have any
                    if (!empty($data)) {
                        $uploaded_count = 0;
                        $error_count = 0;
                        $errors = [];
                        
                        foreach ($data as $index => $row) {
                            // Skip empty rows
                            if (empty(array_filter($row))) continue;
                            
                            // Convert all keys and values to lowercase for case-insensitive matching
                            $row_lower = array_change_key_case($row, CASE_LOWER);
                            
                            // Extract data with case-insensitive column matching
                            $agency_name = trim($row_lower['agency_name'] ?? $row_lower['agency name'] ?? '');
                            $customer_name = trim($row_lower['customer_name'] ?? $row_lower['customer name'] ?? '');
                            $mobile_number = trim($row_lower['mobile_number'] ?? $row_lower['mobile number'] ?? '');
                            $alt_number = trim($row_lower['alt_number'] ?? $row_lower['alt number'] ?? $row_lower['alternate number'] ?? '');
                            $mail_id = trim($row_lower['mail_id'] ?? $row_lower['email'] ?? $row_lower['mail id'] ?? '');
                            $city = trim($row_lower['city'] ?? '');
                            $state = trim($row_lower['state'] ?? '');
                            $address = trim($row_lower['address'] ?? '');
                            $feedback = trim($row_lower['feedback'] ?? '');
                            $agency_type = trim($row_lower['agency_type'] ?? $row_lower['agency type'] ?? 'customer');
                            $status = trim($row_lower['status'] ?? 'active');
                            $gst_number = trim($row_lower['gst_number'] ?? $row_lower['gst number'] ?? $row_lower['gst'] ?? '');
                            $pincode = trim($row_lower['pincode'] ?? $row_lower['pin code'] ?? '');
                            $country = trim($row_lower['country'] ?? 'India');
                            $website = trim($row_lower['website'] ?? '');
                            $opening_balance = floatval($row_lower['opening_balance'] ?? $row_lower['opening balance'] ?? 0);
                            $credit_limit = floatval($row_lower['credit_limit'] ?? $row_lower['credit limit'] ?? 0);
                            $credit_days = intval($row_lower['credit_days'] ?? $row_lower['credit days'] ?? 0);
                            
                            // Validate required fields
                            if (empty($agency_name) || empty($customer_name) || empty($mobile_number) || 
                                empty($mail_id) || empty($city) || empty($state) || empty($address)) {
                                $error_count++;
                                $errors[] = "Row " . ($index + 2) . ": Missing required fields";
                                continue;
                            }
                            
                            // Check for duplicates
                            $check_query = "SELECT id FROM agencies WHERE mobile_number = ? OR mail_id = ?";
                            $check_stmt = $conn->prepare($check_query);
                            if ($check_stmt) {
                                $check_stmt->bind_param("ss", $mobile_number, $mail_id);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                
                                if ($check_result->num_rows > 0) {
                                    $error_count++;
                                    $errors[] = "Row " . ($index + 2) . ": Duplicate mobile or email found";
                                    $check_stmt->close();
                                    continue;
                                }
                                $check_stmt->close();
                            } else {
                                $error_count++;
                                $errors[] = "Row " . ($index + 2) . ": Database error - " . $conn->error;
                                continue;
                            }
                            
                            // Insert data
                            $insert_query = "INSERT INTO agencies (
                                agency_name, customer_name, mobile_number, alt_number, mail_id,
                                city, state, address, feedback, agency_type, status,
                                gst_number, pincode, country, website,
                                opening_balance, credit_limit, credit_days,
                                created_by, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $stmt = $conn->prepare($insert_query);
                            if ($stmt) {
                                $stmt->bind_param(
                                    "sssssssssssssssddii",
                                    $agency_name, $customer_name, $mobile_number, $alt_number, $mail_id,
                                    $city, $state, $address, $feedback, $agency_type, $status,
                                    $gst_number, $pincode, $country, $website,
                                    $opening_balance, $credit_limit, $credit_days,
                                    $user_id
                                );
                                
                                if ($stmt->execute()) {
                                    $uploaded_count++;
                                } else {
                                    $error_count++;
                                    $errors[] = "Row " . ($index + 2) . ": " . $conn->error;
                                }
                                $stmt->close();
                            } else {
                                $error_count++;
                                $errors[] = "Row " . ($index + 2) . ": Error preparing query - " . $conn->error;
                            }
                        }
                        
                        if ($uploaded_count > 0 && $error_count == 0) {
                            $message = "Successfully uploaded $uploaded_count records!";
                            $message_type = "success";
                        } elseif ($uploaded_count > 0 && $error_count > 0) {
                            $message = "Uploaded $uploaded_count records with $error_count errors.";
                            $message_type = "warning";
                        } else {
                            $message = "No records were uploaded. Please check your CSV format.";
                            $message_type = "danger";
                        }
                    } else {
                        $message = "No data found in the CSV file. Please check the file format.";
                        $message_type = "warning";
                    }
                    
                } catch (Exception $e) {
                    $message = "Error processing file: " . $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
    } else {
        $message = "Please select a CSV file to upload.";
        $message_type = "danger";
    }
}

// ==============================================
// Get agency types for dropdown
// ==============================================
$agency_types = ['customer', 'distributor', 'retailer', 'wholesaler', 'supplier'];

// Sample CSV format for download
$sample_csv = "agency_name,customer_name,mobile_number,alt_number,mail_id,city,state,address,feedback,agency_type,status,gst_number,pincode,country,website,opening_balance,credit_limit,credit_days\n";
$sample_csv .= "ABC Distributors,John Doe,9876543210,9876543211,john@abc.com,Mumbai,Maharashtra,123 Main Street,,distributor,active,GST12345,400001,India,www.abc.com,10000.00,50000.00,30\n";
$sample_csv .= "XYZ Retailers,Jane Smith,9876543212,,jane@xyz.com,Delhi,Delhi,456 Park Avenue,,retailer,active,GST67890,110001,India,www.xyz.com,5000.00,25000.00,15\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Agency Data - StockMaster Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2dd4bf;
            --primary-dark: #14b8a6;
            --sidebar-width: 280px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            color: #1e293b;
            margin: 0;
            font-weight: 700;
        }
        
        .page-header p {
            color: #64748b;
            margin: 5px 0 0 0;
        }
        
        .upload-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: none;
        }
        
        .upload-card .card-title {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .upload-card .card-title i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: #334155;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(45, 212, 191, 0.25);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(45, 212, 191, 0.3);
            color: white;
        }
        
        .btn-outline-custom {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            background: transparent;
            cursor: pointer;
        }
        
        .btn-outline-custom:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .file-upload-wrapper {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-upload-wrapper:hover {
            border-color: var(--primary-color);
            background: #f8fafc;
        }
        
        .file-upload-wrapper i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .file-upload-wrapper p {
            color: #64748b;
            margin: 0;
        }
        
        .file-upload-wrapper .file-types {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 10px;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }
        
        .alert-warning {
            background: #fffbeb;
            color: #92400e;
        }
        
        .stats-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px 20px;
            margin: 10px 0;
        }
        
        .stats-box .number {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stats-box .label {
            color: #64748b;
            font-size: 14px;
        }
        
        .stats-box .number.success {
            color: #22c55e;
        }
        
        .stats-box .number.danger {
            color: #ef4444;
        }
        
        .sample-btn {
            margin-top: 15px;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include sidebar - this will render the sidebar
    // The sidebar.php file should only contain the sidebar HTML, not a full page
    $sidebar_path = dirname(__FILE__) . '/sidebar.php';
    if (file_exists($sidebar_path)) {
        include $sidebar_path; 
    } else {
        // Try alternate path
        $alt_sidebar_path = dirname(__FILE__) . '/../sidebar.php';
        if (file_exists($alt_sidebar_path)) {
            include $alt_sidebar_path;
        } else {
            echo '<div style="background: #f8d7da; padding: 15px; margin: 20px; border-radius: 8px; color: #721c24; border: 1px solid #f5c6cb;">
                    <strong>Warning:</strong> sidebar.php file not found. Please make sure it exists.
                  </div>';
        }
    }
    ?>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-upload" style="color: #2dd4bf;"></i> Upload Agency Data</h2>
            <p>Add new agencies manually or upload multiple records via CSV files</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                <?php echo $message; ?>
                <?php if (!empty($errors)): ?>
                    <br><small><?php echo implode('<br>', array_slice($errors, 0, 10)); ?></small>
                    <?php if (count($errors) > 10): ?>
                        <br><small>... and <?php echo count($errors) - 10; ?> more errors</small>
                    <?php endif; ?>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($uploaded_count > 0 || $error_count > 0): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-box">
                        <div class="number success"><?php echo $uploaded_count; ?></div>
                        <div class="label">Successfully Uploaded</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-box">
                        <div class="number danger"><?php echo $error_count; ?></div>
                        <div class="label">Failed Records</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-box">
                        <div class="number"><?php echo $uploaded_count + $error_count; ?></div>
                        <div class="label">Total Processed</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Manual Upload Form -->
        <div class="upload-card">
            <div class="card-title">
                <i class="fas fa-hand-pointer"></i> Add Agency Manually
            </div>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Agency Name *</label>
                        <input type="text" class="form-control" name="agency_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" class="form-control" name="customer_name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mobile Number *</label>
                        <input type="text" class="form-control" name="mobile_number" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Alternate Number</label>
                        <input type="text" class="form-control" name="alt_number">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email ID *</label>
                        <input type="email" class="form-control" name="mail_id" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">City *</label>
                        <input type="text" class="form-control" name="city" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">State *</label>
                        <input type="text" class="form-control" name="state" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Pincode</label>
                        <input type="text" class="form-control" name="pincode">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="country" value="India">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Address *</label>
                        <textarea class="form-control" name="address" rows="2" required></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Agency Type</label>
                        <select class="form-select" name="agency_type">
                            <?php foreach ($agency_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">GST Number</label>
                        <input type="text" class="form-control" name="gst_number">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Website</label>
                        <input type="text" class="form-control" name="website">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" step="0.01" class="form-control" name="opening_balance" value="0.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Credit Limit</label>
                        <input type="number" step="0.01" class="form-control" name="credit_limit" value="0.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Credit Days</label>
                        <input type="number" class="form-control" name="credit_days" value="0">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Feedback</label>
                        <textarea class="form-control" name="feedback" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="manual_submit" class="btn btn-primary-custom">
                            <i class="fas fa-plus"></i> Add Agency
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- File Upload Form -->
        <div class="upload-card">
            <div class="card-title">
                <i class="fas fa-file-upload"></i> Upload CSV File
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="file-upload-wrapper" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p><strong>Click to upload</strong> or drag and drop</p>
                    <p class="file-types">Supported format: CSV (Max 5MB)</p>
                    <input type="file" id="fileInput" name="data_file" style="display: none;" accept=".csv" required>
                    <div id="file-name" style="margin-top: 10px; color: var(--primary-color); font-weight: 500;"></div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="file_submit" class="btn btn-primary-custom">
                        <i class="fas fa-upload"></i> Upload & Process
                    </button>
                    <button type="button" class="btn btn-outline-custom sample-btn" onclick="downloadSampleCSV()">
                        <i class="fas fa-download"></i> Download Sample CSV
                    </button>
                </div>
            </form>
            
            <div class="mt-4">
                <h6><i class="fas fa-info-circle" style="color: var(--primary-color);"></i> Instructions:</h6>
                <ul style="color: #64748b; font-size: 14px; padding-left: 20px;">
                    <li>Download the sample CSV file to see the required format</li>
                    <li>Required columns: agency_name, customer_name, mobile_number, mail_id, city, state, address</li>
                    <li>Optional columns: alt_number, feedback, agency_type, status, gst_number, pincode, country, website, opening_balance, credit_limit, credit_days</li>
                    <li>Maximum file size: 5MB</li>
                    <li>Duplicate mobile numbers or emails will be skipped</li>
                    <li><strong>For Excel files:</strong> Save your Excel file as CSV format before uploading</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show selected file name
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // Download sample CSV
        function downloadSampleCSV() {
            const sampleData = `<?php echo addslashes($sample_csv); ?>`;
            const blob = new Blob([sampleData], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sample_agency_data.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>