<?php
// ==============================================
// Add User Page - Professional Form with Profile Picture & Camera Support
// File: add_user.php
// ==============================================

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Initialize variables
$name = $address = $employee_id = $email_id = $contact_no = $username = "";
$designation = $city = $role = "";
$status = "active";
$error = "";
$success = "";
$profile_picture_path = "";

// Create uploads directory if not exists
$target_dir = "uploads/profile_pictures/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $employee_id = trim($_POST['employee_id']);
        $email_id = trim($_POST['email_id']);
        $contact_no = trim($_POST['contact_no']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $designation = trim($_POST['designation']);
        $role = $_POST['role'];
        $city = trim($_POST['city']);
        $status = $_POST['status'];
        
        // Handle profile picture upload (from file or camera capture)
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $file_name = time() . '_' . basename($_FILES["profile_picture"]["name"]);
            $target_file = $target_dir . $file_name;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is actual image
            $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $error = "File is not an image.";
                $uploadOk = 0;
            }
            
            // Check file size (max 5MB)
            if ($_FILES["profile_picture"]["size"] > 5000000) {
                $error = "Sorry, your file is too large (max 5MB).";
                $uploadOk = 0;
            }
            
            // Allow certain file formats
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }
            
            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    $profile_picture_path = $target_file;
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            }
        }
        
        // Handle camera captured image (base64 data)
        if (isset($_POST['camera_image']) && !empty($_POST['camera_image'])) {
            $camera_image = $_POST['camera_image'];
            // Remove the base64 header
            $image_data = explode(',', $camera_image);
            if (count($image_data) > 1) {
                $image_base64 = $image_data[1];
                $image = base64_decode($image_base64);
                $file_name = time() . '_camera_capture.jpg';
                $target_file = $target_dir . $file_name;
                
                if (file_put_contents($target_file, $image)) {
                    $profile_picture_path = $target_file;
                } else {
                    $error = "Failed to save camera image.";
                }
            }
        }
        
        // Validation
        if (empty($error)) {
            if (empty($name) || empty($email_id) || empty($username) || empty($password)) {
                $error = "Please fill all required fields.";
            } elseif (!filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters.";
            } else {
                // Check if username, email, or employee_id already exists using PDO
                $check_sql = "SELECT id FROM users WHERE username = :username OR email_id = :email_id";
                $check_stmt = $db->prepare($check_sql);
                $check_stmt->execute([
                    ':username' => $username,
                    ':email_id' => $email_id
                ]);
                
                if ($check_stmt->rowCount() > 0) {
                    $error = "Username or Email already exists.";
                } else {
                    // Check employee_id if provided
                    if (!empty($employee_id)) {
                        $check_emp_sql = "SELECT id FROM users WHERE employee_id = :employee_id";
                        $check_emp_stmt = $db->prepare($check_emp_sql);
                        $check_emp_stmt->execute([':employee_id' => $employee_id]);
                        if ($check_emp_stmt->rowCount() > 0) {
                            $error = "Employee ID already exists.";
                        }
                    }
                    
                    if (empty($error)) {
                        // Hash password
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert user using PDO with profile picture
                        $insert_sql = "INSERT INTO users (name, address, employee_id, email_id, contact_no, username, password_hash, designation, role, city, status, profile_picture, created_by, created_at, updated_at) 
                                       VALUES (:name, :address, :employee_id, :email_id, :contact_no, :username, :password_hash, :designation, :role, :city, :status, :profile_picture, :created_by, NOW(), NOW())";
                        
                        $insert_stmt = $db->prepare($insert_sql);
                        $result = $insert_stmt->execute([
                            ':name' => $name,
                            ':address' => $address,
                            ':employee_id' => $employee_id ?: null,
                            ':email_id' => $email_id,
                            ':contact_no' => $contact_no ?: null,
                            ':username' => $username,
                            ':password_hash' => $password_hash,
                            ':designation' => $designation ?: null,
                            ':role' => $role,
                            ':city' => $city ?: null,
                            ':status' => $status,
                            ':profile_picture' => $profile_picture_path ?: null,
                            ':created_by' => $_SESSION['user_id']
                        ]);
                        
                        if ($result) {
                            $success = "User added successfully!";
                            // Clear form
                            $name = $address = $employee_id = $email_id = $contact_no = $username = "";
                            $designation = $city = "";
                            $profile_picture_path = "";
                        } else {
                            $error = "Error adding user. Please try again.";
                        }
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - StockMaster Pro</title>
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
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #2dd4bf;
            font-size: 32px;
        }

        .breadcrumb {
            color: #64748b;
            margin-top: 8px;
            font-size: 14px;
        }

        .breadcrumb i {
            margin-right: 6px;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .form-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
        }

        .form-header h2 {
            font-size: 20px;
            font-weight: 600;
        }

        .form-header h2 i {
            margin-right: 10px;
            color: #2dd4bf;
        }

        .form-header p {
            color: #94a3b8;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert i {
            font-size: 20px;
        }

        /* Profile Picture Upload */
        .profile-upload {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-preview {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .profile-preview img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #2dd4bf;
        }

        .profile-preview .default-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }

        .upload-info {
            flex: 1;
        }

        .upload-info h4 {
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .upload-info p {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 12px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .upload-btn, .camera-btn {
            background: #f1f5f9;
            color: #475569;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .upload-btn:hover, .camera-btn:hover {
            background: #e2e8f0;
        }

        .upload-btn-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .remove-photo {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 12px;
            margin-left: 15px;
        }

        .remove-photo:hover {
            text-decoration: underline;
        }

        /* Camera Modal */
        .camera-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .camera-modal.active {
            display: flex;
        }

        .camera-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .camera-container video {
            width: 100%;
            border-radius: 12px;
            background: #000;
        }

        .camera-container canvas {
            display: none;
        }

        .camera-buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .capture-btn, .close-camera {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .capture-btn {
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            color: white;
        }

        .close-camera {
            background: #ef4444;
            color: white;
        }

        /* Form Body */
        .form-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        label i {
            color: #2dd4bf;
            width: 18px;
        }

        .required {
            color: #ef4444;
        }

        input, select, textarea {
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
            background: #fefefe;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #2dd4bf;
            box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Password Hint */
        .password-hint {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-hint i {
            font-size: 10px;
        }

        /* Form Actions */
        .form-actions {
            padding: 20px 30px 30px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 212, 191, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                justify-content: center;
            }
            .profile-upload {
                flex-direction: column;
                text-align: center;
            }
            .button-group {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Include Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Camera Modal -->
<div class="camera-modal" id="cameraModal">
    <div class="camera-container">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-camera"></i> Take a Photo</h3>
        <video id="video" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="camera-buttons">
            <button class="capture-btn" onclick="capturePhoto()">
                <i class="fas fa-camera"></i> Capture
            </button>
            <button class="close-camera" onclick="closeCamera()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h1>
            <i class="fas fa-user-plus"></i>
            Add New User
        </h1>
        <div class="breadcrumb">
            <i class="fas fa-tachometer-alt"></i> Dashboard / 
            <i class="fas fa-users"></i> User Management / 
            <i class="fas fa-user-plus"></i> Add User
        </div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-user-circle"></i> User Registration Form</h2>
            <p>Fill in the details below to create a new user account in the system</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" id="userForm">
            <div class="form-body">
                <!-- Profile Picture Upload Section -->
                <div class="profile-upload">
                    <div class="profile-preview">
                        <div class="default-avatar" id="avatarPreview">
                            <i class="fas fa-user"></i>
                        </div>
                        <img id="imagePreview" style="display: none; width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <div class="upload-info">
                        <h4><i class="fas fa-camera"></i> Profile Picture</h4>
                        <p>Upload a profile photo or take a photo using your camera</p>
                        <div class="button-group">
                            <div class="upload-btn-wrapper">
                                <button type="button" class="upload-btn">
                                    <i class="fas fa-upload"></i> Choose File
                                </button>
                                <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="previewImage(this)">
                            </div>
                            <button type="button" class="camera-btn" onclick="openCamera()">
                                <i class="fas fa-camera"></i> Take Photo
                            </button>
                            <button type="button" class="remove-photo" onclick="removeImage()" style="display: none;" id="removeBtn">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter full name" required>
                    </div>

                    <!-- Employee ID -->
                    <div class="form-group">
                        <label><i class="fas fa-id-badge"></i> Employee ID</label>
                        <input type="text" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>" placeholder="EMP-001">
                    </div>

                    <!-- Email ID -->
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                        <input type="email" name="email_id" value="<?php echo htmlspecialchars($email_id); ?>" placeholder="user@example.com" required>
                    </div>

                    <!-- Contact Number -->
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact Number</label>
                        <input type="text" name="contact_no" value="<?php echo htmlspecialchars($contact_no); ?>" placeholder="+91 98765 43210">
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Username <span class="required">*</span></label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Choose a unique username" required>
                    </div>

                    <!-- Designation -->
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Designation</label>
                        <input type="text" name="designation" value="<?php echo htmlspecialchars($designation); ?>" placeholder="e.g., Software Engineer, Manager">
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Role <span class="required">*</span></label>
                        <select name="role" required>
                            <option value="Employee" <?php echo $role == 'Employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="Admin" <?php echo $role == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <!-- City -->
                    <div class="form-group">
                        <label><i class="fas fa-city"></i> City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Enter city name">
                    </div>

                    <!-- Address (Full Width) -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-map-marker-alt"></i> Complete Address</label>
                        <textarea name="address" placeholder="Enter complete address (House No, Street, Area, Landmark, etc.)"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                        <input type="password" name="password" placeholder="••••••••" required>
                        <div class="password-hint"><i class="fas fa-info-circle"></i> Password must be at least 6 characters</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> Account Status</label>
                        <select name="status">
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-undo-alt"></i> Reset Form
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let stream = null;
    let cameraImageData = null;
    
    // Preview image from file upload
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const avatarPreview = document.getElementById('avatarPreview');
        const removeBtn = document.getElementById('removeBtn');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                avatarPreview.style.display = 'none';
                removeBtn.style.display = 'inline-block';
                cameraImageData = null; // Clear camera data when file is uploaded
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function removeImage() {
        const fileInput = document.getElementById('profile_picture');
        const preview = document.getElementById('imagePreview');
        const avatarPreview = document.getElementById('avatarPreview');
        const removeBtn = document.getElementById('removeBtn');
        
        fileInput.value = '';
        preview.src = '';
        preview.style.display = 'none';
        avatarPreview.style.display = 'flex';
        removeBtn.style.display = 'none';
        cameraImageData = null;
    }
    
    // Camera functions
    async function openCamera() {
        const cameraModal = document.getElementById('cameraModal');
        const video = document.getElementById('video');
        
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            cameraModal.classList.add('active');
        } catch (err) {
            alert('Unable to access camera. Please make sure you have granted permission.');
            console.error('Camera error:', err);
        }
    }
    
    function capturePhoto() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        
        // Set canvas dimensions to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Draw video frame to canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Convert canvas to data URL
        const imageDataURL = canvas.toDataURL('image/jpeg', 0.8);
        
        // Preview the captured image
        const preview = document.getElementById('imagePreview');
        const avatarPreview = document.getElementById('avatarPreview');
        const removeBtn = document.getElementById('removeBtn');
        
        preview.src = imageDataURL;
        preview.style.display = 'block';
        avatarPreview.style.display = 'none';
        removeBtn.style.display = 'inline-block';
        
        // Store camera image data for form submission
        cameraImageData = imageDataURL;
        
        // Add hidden input to form with camera image data
        let hiddenInput = document.getElementById('cameraImageInput');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'camera_image';
            hiddenInput.id = 'cameraImageInput';
            document.getElementById('userForm').appendChild(hiddenInput);
        }
        hiddenInput.value = cameraImageData;
        
        // Clear file input
        document.getElementById('profile_picture').value = '';
        
        // Close camera modal
        closeCamera();
    }
    
    function closeCamera() {
        const cameraModal = document.getElementById('cameraModal');
        const video = document.getElementById('video');
        
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        video.srcObject = null;
        cameraModal.classList.remove('active');
    }
    
    // Close camera modal when clicking outside
    document.getElementById('cameraModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCamera();
        }
    });
</script>

</body>
</html>