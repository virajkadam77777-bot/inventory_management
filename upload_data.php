<?php
// ==============================================
// Upload Agency Data - File Upload & Manual Entry
// File: upload_data.php
// ==============================================

session_start();

// Include database connection
require_once 'db_connection.php';

// Set active page for sidebar
$active_page = 'upload_data';

// Initialize variables
$success_message = "";
$error_message = "";
$upload_errors = [];
$upload_success_count = 0;
$upload_fail_count = 0;

// Process Manual Entry Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manual_submit'])) {
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
        if (empty($agency_name) || empty($customer_name) || empty($mobile_number) || empty($mail_id) || empty($city) || empty($state) || empty($address)) {
            throw new Exception("All required fields must be filled!");
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
            $success_message = "✅ Agency added successfully!";
            // Clear form fields
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

// Process File Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['file_submit'])) {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['data_file']) || $_FILES['data_file']['error'] != UPLOAD_ERR_OK) {
            throw new Exception("Please select a valid file to upload.");
        }

        $file = $_FILES['data_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_types = ['csv', 'xlsx', 'xls'];
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception("Only CSV, XLSX, and XLS files are allowed.");
        }

        // Create upload directory if not exists
        $upload_dir = "uploads/agency_data/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Move uploaded file
        $file_name = time() . '_' . basename($file['name']);
        $file_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception("Failed to move uploaded file.");
        }

        // Process file based on extension
        if ($file_extension == 'csv') {
            $result = processCSV($file_path);
        } else {
            $result = processExcel($file_path);
        }

        $upload_success_count = $result['success'];
        $upload_fail_count = $result['failed'];
        $upload_errors = $result['errors'];

        if ($upload_success_count > 0) {
            $success_message = "✅ Successfully uploaded " . $upload_success_count . " records!";
        }

        if ($upload_fail_count > 0) {
            $error_message = "⚠️ " . $upload_fail_count . " records failed to upload.";
        }

        // Delete file after processing
        if (file_exists($file_path)) {
            unlink($file_path);
        }

    } catch (Exception $e) {
        $error_message = "❌ Error: " . $e->getMessage();
    }
}

// Function to process CSV file
function processCSV($file_path) {
    $result = ['success' => 0, 'failed' => 0, 'errors' => []];
    
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        // Get header row
        $header = fgetcsv($handle);
        
        // Map headers to database columns
        $column_map = array_map(function($col) {
            return strtolower(trim($col));
        }, $header);
        
        $row_number = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row_number++;
            
            try {
                // Map data to associative array
                $row_data = array_combine($column_map, $data);
                
                // Validate required fields
                if (empty($row_data['agency_name']) || empty($row_data['customer_name']) || 
                    empty($row_data['mobile_number']) || empty($row_data['mail_id']) ||
                    empty($row_data['city']) || empty($row_data['state']) || empty($row_data['address'])) {
                    throw new Exception("Missing required fields at row " . $row_number);
                }
                
                // Insert into database
                $result['success'] += insertAgencyData($row_data);
                
            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Row " . $row_number . ": " . $e->getMessage();
            }
        }
        fclose($handle);
    }
    
    return $result;
}

// Function to process Excel file (requires PHPExcel or PhpSpreadsheet)
function processExcel($file_path) {
    $result = ['success' => 0, 'failed' => 0, 'errors' => []];
    
    try {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception("PhpSpreadsheet library not installed. Please install using: composer require phpoffice/phpspreadsheet");
        }
        
        require_once 'vendor/autoload.php';
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        if (empty($rows)) {
            throw new Exception("File is empty or invalid.");
        }
        
        // Get header row
        $header = array_shift($rows);
        $column_map = array_map(function($col) {
            return strtolower(trim($col));
        }, $header);
        
        $row_number = 1;
        foreach ($rows as $data) {
            $row_number++;
            
            try {
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }
                
                // Map data to associative array
                $row_data = array_combine($column_map, $data);
                
                // Validate required fields
                if (empty($row_data['agency_name']) || empty($row_data['customer_name']) || 
                    empty($row_data['mobile_number']) || empty($row_data['mail_id']) ||
                    empty($row_data['city']) || empty($row_data['state']) || empty($row_data['address'])) {
                    throw new Exception("Missing required fields at row " . $row_number);
                }
                
                // Insert into database
                $result['success'] += insertAgencyData($row_data);
                
            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Row " . $row_number . ": " . $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        throw new Exception("Excel processing error: " . $e->getMessage());
    }
    
    return $result;
}

// Function to insert agency data
function insertAgencyData($data) {
    global $db;
    
    // Check if mobile number already exists
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM agencies WHERE mobile_number = ?");
    $check_stmt->execute([$data['mobile_number']]);
    if ($check_stmt->fetchColumn() > 0) {
        throw new Exception("Mobile number " . $data['mobile_number'] . " already exists");
    }
    
    // Check if email already exists
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM agencies WHERE mail_id = ?");
    $check_stmt->execute([$data['mail_id']]);
    if ($check_stmt->fetchColumn() > 0) {
        throw new Exception("Email " . $data['mail_id'] . " already exists");
    }
    
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
    
    // Set default values
    $agency_type = !empty($data['agency_type']) ? $data['agency_type'] : 'customer';
    $status = !empty($data['status']) ? $data['status'] : 'active';
    $country = !empty($data['country']) ? $data['country'] : 'India';
    $opening_balance = !empty($data['opening_balance']) ? floatval($data['opening_balance']) : 0.00;
    $credit_limit = !empty($data['credit_limit']) ? floatval($data['credit_limit']) : 0.00;
    $credit_days = !empty($data['credit_days']) ? intval($data['credit_days']) : 0;
    
    $stmt->bindParam(':agency_name', $data['agency_name']);
    $stmt->bindParam(':customer_name', $data['customer_name']);
    $stmt->bindParam(':mobile_number', $data['mobile_number']);
    $stmt->bindParam(':alt_number', !empty($data['alt_number']) ? $data['alt_number'] : null);
    $stmt->bindParam(':mail_id', $data['mail_id']);
    $stmt->bindParam(':city', $data['city']);
    $stmt->bindParam(':state', $data['state']);
    $stmt->bindParam(':address', $data['address']);
    $stmt->bindParam(':feedback', !empty($data['feedback']) ? $data['feedback'] : null);
    $stmt->bindParam(':agency_type', $agency_type);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':gst_number', !empty($data['gst_number']) ? $data['gst_number'] : null);
    $stmt->bindParam(':pincode', !empty($data['pincode']) ? $data['pincode'] : null);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':website', !empty($data['website']) ? $data['website'] : null);
    $stmt->bindParam(':opening_balance', $opening_balance);
    $stmt->bindParam(':credit_limit', $credit_limit);
    $stmt->bindParam(':credit_days', $credit_days);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        return 1;
    }
    
    throw new Exception("Failed to insert record");
}

// Download sample CSV
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sample_agency_data.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'agency_name', 'customer_name', 'mobile_number', 'alt_number', 'mail_id',
        'city', 'state', 'address', 'feedback', 'agency_type', 'status',
        'gst_number', 'pincode', 'country', 'website',
        'opening_balance', 'credit_limit', 'credit_days'
    ]);
    
    // Sample data
    fputcsv($output, [
        'ABC Distributors', 'John Doe', '9876543210', '9876543211', 'john@abc.com',
        'Mumbai', 'Maharashtra', '123 Main Street, Mumbai', 'Good customer', 'distributor', 'active',
        'GST123456789', '400001', 'India', 'www.abc.com',
        '10000.00', '50000.00', '30'
    ]);
    
    fputcsv($output, [
        'XYZ Retailers', 'Jane Smith', '9876543212', '', 'jane@xyz.com',
        'Delhi', 'Delhi', '456 Park Avenue, Delhi', '', 'retailer', 'active',
        'GST987654321', '110001', 'India', '',
        '5000.00', '25000.00', '15'
    ]);
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Agency Data</title>
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

        /* Upload Main Section */
        .upload-main-section {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .upload-main-section .upload-icon {
            font-size: 80px;
            color: #1a3c5e;
            margin-bottom: 20px;
        }

        .upload-main-section h2 {
            color: #1a3c5e;
            margin-bottom: 10px;
        }

        .upload-main-section p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        .file-upload-area {
            border: 2px dashed #d0d0d0;
            padding: 50px 30px;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s;
            cursor: pointer;
            background: #fafafa;
            margin-bottom: 20px;
        }
        .file-upload-area:hover {
            border-color: #1a3c5e;
            background: #f0f4f8;
        }
        .file-upload-area.dragover {
            border-color: #1a3c5e;
            background: #e8f0fe;
        }
        .file-upload-area i {
            font-size: 60px;
            color: #1a3c5e;
            margin-bottom: 15px;
        }
        .file-upload-area p {
            color: #666;
            margin: 0;
            font-size: 16px;
        }
        .file-upload-area .file-info {
            color: #1a3c5e;
            font-weight: 600;
            font-size: 18px;
            margin-top: 10px;
        }
        .file-upload-area .help-text {
            color: #999;
            font-size: 13px;
            margin-top: 10px;
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
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
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
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
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

        .error-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 12px;
            text-align: left;
        }
        .error-list .error-item {
            padding: 3px 0;
            color: #dc3545;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .summary-stat {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .summary-stat .number {
            font-size: 28px;
            font-weight: 700;
        }
        .summary-stat .label {
            font-size: 13px;
            color: #666;
        }
        .summary-stat.success .number { color: #28a745; }
        .summary-stat.danger .number { color: #dc3545; }
        .summary-stat.info .number { color: #17a2b8; }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex !important;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
            color: #1a3c5e;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 32px;
            color: #999;
            cursor: pointer;
            transition: 0.3s;
            padding: 0 10px;
            line-height: 1;
        }
        .modal-close:hover {
            color: #333;
            transform: rotate(90deg);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .form-group label .required {
            color: red;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            font-size: 13px;
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
            min-height: 50px;
        }
        .form-group .help-text {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .modal-footer {
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
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

            .upload-main-section {
                padding: 20px;
            }

            .upload-main-section .upload-icon {
                font-size: 50px;
            }

            .file-upload-area {
                padding: 30px 15px;
            }
            .file-upload-area i {
                font-size: 40px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .summary-stats {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 15px;
                max-height: 95vh;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .upload-main-section {
                padding: 15px;
            }

            .file-upload-area {
                padding: 20px 10px;
            }
            .file-upload-area i {
                font-size: 30px;
            }
            .file-upload-area p {
                font-size: 14px;
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
                    <h1><i class="fas fa-upload"></i> Upload Agency Data</h1>
                </div>
                <div class="user-info">
                    <span style="font-size: 14px; color: #666;">Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>

            <?php if (!empty($upload_errors)): ?>
                <div class="alert alert-info">
                    <strong>Upload Errors:</strong>
                    <div class="error-list">
                        <?php foreach ($upload_errors as $error): ?>
                            <div class="error-item"><?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Upload Section -->
            <div class="upload-main-section">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h2>Upload Agency Data</h2>
                <p>Upload CSV or Excel file with agency data. Download the sample file for format reference.</p>

                <div style="margin-bottom: 20px;">
                    <a href="?download_sample=1" class="btn btn-info">
                        <i class="fas fa-download"></i> Download Sample CSV
                    </a>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                    <div class="file-upload-area" id="dropArea" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-file-upload"></i>
                        <p><strong>Click to select file</strong> or drag and drop</p>
                        <p class="file-info" id="fileName">No file selected</p>
                        <input type="file" name="data_file" id="fileInput" style="display:none" 
                               accept=".csv,.xlsx,.xls" onchange="updateFileName(this)">
                        <div class="help-text">Supported formats: CSV, XLSX, XLS (Max size: 10MB)</div>
                    </div>

                    <button type="submit" name="file_submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 15px;">
                        <i class="fas fa-upload"></i> Upload & Import Data
                    </button>
                </form>

                <?php if ($upload_success_count > 0 || $upload_fail_count > 0): ?>
                <div class="summary-stats">
                    <div class="summary-stat success">
                        <div class="number"><?= $upload_success_count ?></div>
                        <div class="label">Successfully Imported</div>
                    </div>
                    <div class="summary-stat danger">
                        <div class="number"><?= $upload_fail_count ?></div>
                        <div class="label">Failed to Import</div>
                    </div>
                    <div class="summary-stat info">
                        <div class="number"><?= $upload_success_count + $upload_fail_count ?></div>
                        <div class="label">Total Processed</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
               <div class="action-buttons">
    <a href="add_agency_manual.php" class="btn btn-success">
        <i class="fas fa-pencil-alt"></i> Add Agency Manually
    </a>
    <a href="manage_agencies.php" class="btn btn-primary">
        <i class="fas fa-building"></i> Manage Agencies
    </a>
    <a href="view_agencies.php" class="btn btn-info">
        <i class="fas fa-list"></i> View All Agencies
    </a>
</div>
            </div>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <div class="modal-overlay" id="manualModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-pencil-alt text-success"></i> Add Agency Manually</h3>
                <button class="modal-close" id="closeModalBtn">&times;</button>
            </div>

            <form method="POST" action="" id="manualForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Agency Name <span class="required">*</span></label>
                        <input type="text" name="agency_name" required value="<?= isset($_POST['agency_name']) ? htmlspecialchars($_POST['agency_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Customer Name <span class="required">*</span></label>
                        <input type="text" name="customer_name" required value="<?= isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number <span class="required">*</span></label>
                        <input type="text" name="mobile_number" required maxlength="15" value="<?= isset($_POST['mobile_number']) ? htmlspecialchars($_POST['mobile_number']) : '' ?>">
                        <div class="help-text">Enter 10-digit mobile number</div>
                    </div>
                    <div class="form-group">
                        <label>Alternate Number</label>
                        <input type="text" name="alt_number" maxlength="15" value="<?= isset($_POST['alt_number']) ? htmlspecialchars($_POST['alt_number']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Email ID <span class="required">*</span></label>
                        <input type="email" name="mail_id" required value="<?= isset($_POST['mail_id']) ? htmlspecialchars($_POST['mail_id']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>City <span class="required">*</span></label>
                        <input type="text" name="city" required value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>State <span class="required">*</span></label>
                        <input type="text" name="state" required value="<?= isset($_POST['state']) ? htmlspecialchars($_POST['state']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Pincode</label>
                        <input type="text" name="pincode" maxlength="10" value="<?= isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : '' ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>Address <span class="required">*</span></label>
                        <textarea name="address" rows="2" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" value="<?= isset($_POST['country']) ? htmlspecialchars($_POST['country']) : 'India' ?>">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" value="<?= isset($_POST['website']) ? htmlspecialchars($_POST['website']) : '' ?>">
                        <div class="help-text">e.g., www.example.com</div>
                    </div>
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
                        <input type="text" name="gst_number" maxlength="50" value="<?= isset($_POST['gst_number']) ? htmlspecialchars($_POST['gst_number']) : '' ?>">
                        <div class="help-text">Format: 22AAAAA0000A1Z5</div>
                    </div>
                    <div class="form-group">
                        <label>Opening Balance</label>
                        <input type="number" step="0.01" min="0" name="opening_balance" value="<?= isset($_POST['opening_balance']) ? $_POST['opening_balance'] : '0.00' ?>">
                    </div>
                    <div class="form-group">
                        <label>Credit Limit</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" value="<?= isset($_POST['credit_limit']) ? $_POST['credit_limit'] : '0.00' ?>">
                    </div>
                    <div class="form-group">
                        <label>Credit Days</label>
                        <input type="number" min="0" name="credit_days" value="<?= isset($_POST['credit_days']) ? $_POST['credit_days'] : '0' ?>">
                        <div class="help-text">Number of credit days allowed</div>
                    </div>
                    <div class="form-group full-width">
                        <label>Feedback</label>
                        <textarea name="feedback" rows="2"><?= isset($_POST['feedback']) ? htmlspecialchars($_POST['feedback']) : '' ?></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelModalBtn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="reset" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" name="manual_submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Agency
                    </button>
                </div>
            </form>
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
        // Modal Functions - FIXED
        // =============================================
        const modal = document.getElementById('manualModal');
        const openBtn = document.getElementById('openManualBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelModalBtn');

        function openManualModal() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeManualModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Open modal on button click
        if (openBtn) {
            openBtn.addEventListener('click', openManualModal);
        }

        // Close modal on close button
        if (closeBtn) {
            closeBtn.addEventListener('click', closeManualModal);
        }

        // Close modal on cancel button
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeManualModal);
        }

        // Close modal on outside click
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeManualModal();
                }
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeManualModal();
            }
        });

        // =============================================
        // File Upload Functions
        // =============================================
        function updateFileName(input) {
            const fileInfo = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 10485760) {
                    alert('File size exceeds 10MB limit. Please select a smaller file.');
                    input.value = '';
                    fileInfo.textContent = 'No file selected';
                    fileInfo.style.color = '#1a3c5e';
                    return;
                }
                fileInfo.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                fileInfo.style.color = '#28a745';
            } else {
                fileInfo.textContent = 'No file selected';
                fileInfo.style.color = '#1a3c5e';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Drag and drop support
        const dropArea = document.getElementById('dropArea');
        if (dropArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropArea.classList.add('dragover');
            }

            function unhighlight() {
                dropArea.classList.remove('dragover');
            }

            dropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                const fileInput = document.getElementById('fileInput');
                if (files.length) {
                    fileInput.files = files;
                    updateFileName(fileInput);
                }
            }
        }

        // Auto-open modal if there were validation errors
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manual_submit']) && !empty($error_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openManualModal();
            });
        <?php endif; ?>
    </script>
</body>
</html>