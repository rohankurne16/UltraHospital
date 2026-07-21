<?php
session_start();
include "config/hospital.php";
require_once "config/send_registration_email.php";
$message = "";
$messageType = "";
$image_path = "";

$hid = $_SESSION["hospital_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    $_SESSION['form_data'] = $_POST;

    $name       = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile     = mysqli_real_escape_string($conn, $_POST['mobile']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $selectrole = mysqli_real_escape_string($conn, $_POST['selectrole']);
    $password   = $_POST['password'];
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $status     = mysqli_real_escape_string($conn, $_POST['status']);

    // Server-side Validation with Regex
    if (empty($name) || empty($email) || empty($password) || empty($selectrole)) {
        $message = "Please fill all required fields.";
        $messageType = "error";
} elseif (!preg_match("/^[A-Za-z\s'-]+$/", $name)) {
        $message = "Invalid Name. Only letters, spaces, hyphens, and apostrophes are allowed.";
        $messageType = "error";
    } elseif (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = "Invalid Mobile Number. Must be exactly 10 digits.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid Email Address.";
        $messageType = "error";
    } elseif (!in_array($selectrole, ["Receptionist", "Nurse", "Ward_boy", "Lab Technician"])) {
        $message = "Invalid Role selected.";
        $messageType = "error";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$/', $password)) {
        $message = "Invalid Password. Must be at least 8 characters with uppercase, lowercase, number, and special character.";
        $messageType = "error";
    } elseif (!empty($address) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $address)) {
        $message = "Invalid Address. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
        $messageType = "error";
    } elseif (!in_array($status, ['Active', 'Inactive'])) {
        $message = "Invalid Status selected.";
        $messageType = "error";
    } else {
        // Check email in register table
        $check_sql = "SELECT * FROM register WHERE email=?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already exists.";
            $messageType = "error";
        } else {
            // Upload Image
            if (isset($_FILES['staff_image']) && $_FILES['staff_image']['error'] == 0 && !empty($_FILES['staff_image']['name'])) {
                $folder = "documents/staff/images/";
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                }

                $image_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['staff_image']['name']));
                $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $allowed = array("jpg", "jpeg", "png", "gif");

                if (in_array($ext, $allowed)) {
                    if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $folder . $image_name)) {
                        $image_path = $image_name;
                    } else {
                        $message = "Failed to upload image.";
                        $messageType = "error";
                    }
                } else {
                    $message = "Only JPG, JPEG, PNG, GIF allowed.";
                    $messageType = "error";
                }
            }

            if ($messageType != "error") {
                mysqli_begin_transaction($conn);

                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $register_sql = "INSERT INTO register (name, email, password, role, created_by, modified_by, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_reg = $conn->prepare($register_sql);
                    $admin_user = "Admin";
                    $stmt_reg->bind_param("ssssssi", $name, $email, $hashed_password, $selectrole, $admin_user, $admin_user, $hid);
                    
                    if (!$stmt_reg->execute()) {
                        throw new Exception($stmt_reg->error);
                    }

                    $register_id = $conn->insert_id;

                    $staff_sql = "INSERT INTO staff(register_id, name, mobile, email, role, address, status, profile_image, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_staff = $conn->prepare($staff_sql);
                    $stmt_staff->bind_param("isssssssi", $register_id, $name, $mobile, $email, $selectrole, $address, $status, $image_path, $hid);

                    if (!$stmt_staff->execute()) {
                        throw new Exception($stmt_staff->error);
                    }

                    mysqli_commit($conn);
                    unset($_SESSION['form_data']);

                    sendRegistrationEmail($conn, $hid, $name, $email, $password);

                    echo "<script>
                    alert('Staff Added Successfully');
                    window.location='staff.php';
                    </script>";
                    exit();

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = "Database error: " . $e->getMessage();
                    $messageType = "error";
                }
            }
        }
    }
}

$form_data = $_SESSION['form_data'] ?? [];
$staff_roles = array("Receptionist", "Nurse", "Ward_boy", "Lab Technician");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Add Staff</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        
        /* Sidebar and Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }

        /* Mobile Sidebar behavior */
        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            #sidebar-container.active {
                transform: translateX(0);
            }
            #main-content {
                margin-left: 0 !important;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Desktop Sidebar behavior */
        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 260px;
            }
        }

        .main-content { 
            padding: 16px; 
            min-height: 100vh; 
            transition: 0.3s; 
        }
        @media (min-width: 1280px) {
            .main-content {
                margin-left: 260px;
                padding: 32px;
            }
        }

        .form-container { width: 100%; margin: 0 auto; max-width: 1000px; }
        .form-card { background: white; border-radius: 20px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .form-card .header { padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; display: flex; align-items: center; gap: 12px; }
        @media (min-width: 768px) {
            .form-card .header { padding: 24px 32px; }
        }
        .form-card .header .header-icon { width: 44px; height: 44px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; flex-shrink: 0; }
        .form-card .header h3 { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
        @media (min-width: 768px) {
            .form-card .header h3 { font-size: 20px; }
        }
        .form-card .header .subtitle { font-size: 12px; color: #64748b; font-weight: 400; }
        
        .form-card .body { padding: 20px; }
        @media (min-width: 768px) {
            .form-card .body { padding: 32px 40px; }
        }

        .form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        @media (min-width: 768px) {
            .form-grid { grid-template-columns: 1fr 1fr; gap: 24px; }
        }
        .full-width { grid-column: 1 / -1; }
        
        /* Validation Styles */
        .field-group { position: relative; }
        .field-group label { font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .field-group label i { color: #3b82f6; width: 16px; }
        
        .input-wrapper { position: relative; }
        .input-wrapper .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .input-wrapper .input-icon.valid { color: #22c55e; opacity: 1; }
        .input-wrapper .input-icon.invalid { color: #ef4444; opacity: 1; }
        
        .field-group input, .field-group select, .field-group textarea { 
            padding: 12px 16px; 
            border-radius: 12px; 
            border: 1.5px solid #e2e8f0; 
            background: #fcfdfe; 
            font-size: 14px; 
            outline: none; 
            transition: 0.3s; 
            width: 100%; 
        }
        .field-group input:focus, .field-group select:focus, .field-group textarea:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1); 
            background: #fff; 
        }
        
        .field-group input.error, .field-group textarea.error { 
            border-color: #ef4444 !important; 
            background-color: #fef2f2 !important; 
        }
        .field-group input.success, .field-group textarea.success { 
            border-color: #22c55e !important; 
            background-color: #f0fdf4 !important; 
        }
        
        .validation-message {
            font-size: 11px;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        .validation-message.show { display: flex; }
        .validation-message.error { color: #ef4444; }
        .validation-message.success { color: #22c55e; }
        
        .validation-hint {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
            display: block;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .password-strength .strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .password-strength .strength-bar.weak { width: 25%; background: #ef4444; }
        .password-strength .strength-bar.fair { width: 50%; background: #f59e0b; }
        .password-strength .strength-bar.good { width: 75%; background: #3b82f6; }
        .password-strength .strength-bar.strong { width: 100%; background: #22c55e; }
        
        .strength-text {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        .password-requirements {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .password-requirements .req-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .password-requirements .req-item .req-icon { font-size: 10px; }
        .password-requirements .req-item.met { color: #22c55e; }
        .password-requirements .req-item.unmet { color: #9ca3af; }
        
        .image-upload-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 24px; }
        .image-preview-wrapper { position: relative; width: 100px; height: 100px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.1); overflow: hidden; background: #f1f5f9; cursor: pointer; transition: 0.3s; }
        @media (min-width: 768px) {
            .image-preview-wrapper { width: 120px; height: 120px; }
        }
        .image-preview-wrapper:hover { transform: scale(1.03); border-color: #3b82f6; }
        .image-preview { width: 100%; height: 100%; object-fit: cover; }
        .camera-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 35px; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: #fff; transition: 0.3s; opacity: 0; }
        .image-preview-wrapper:hover .camera-overlay { opacity: 1; }
        
        .btn-primary { background: #3b82f6; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; }
        @media (min-width: 768px) {
            .btn-primary { width: auto; padding: 12px 32px; }
        }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        
        .back-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; color: #374151; transition: all 0.2s ease; text-decoration: none; flex-shrink: 0; }
        .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        
        #mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            cursor: pointer;
        }

        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        
        .step-nav { display: flex; border-bottom: 1px solid #e5e7eb; margin-bottom: 24px; gap: 16px; overflow-x: auto; }
        @media (min-width: 768px) {
            .step-nav { gap: 24px; margin-bottom: 32px; }
        }
        .step-btn { padding: 12px 0; font-size: 13px; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent; transition: 0.2s; cursor: pointer; background: none; border-top: none; border-left: none; border-right: none; white-space: nowrap; }
        @media (min-width: 768px) {
            .step-btn { font-size: 14px; }
        }
        .step-btn.active { color: #3b82f6; border-color: #3b82f6; }
        
        .form-section { display: none; }
        .form-section.active { display: block; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <?php include 'header.php'; ?>  
    <div class="flex min-h-screen">
      
            <?php include 'Sidebar.php'; ?>  
        
        
        <main class="main-content w-full">
            <div class="form-container">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    
                    <a href="staff.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900">Add Staff</h1>
                        <p class="text-slate-500 text-xs md:text-sm mt-1">Register a new staff member.</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                        <i class="fas <?php echo ($messageType === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <div class="header">
                        <div class="header-icon"><i class="fas fa-user-plus"></i></div>
                        <div>
                            <h3>Staff Registration</h3>
                            <div class="subtitle">Complete all sections to add a new member</div>
                        </div>
                    </div>

                    <div class="body">
                        <div class="step-nav">
                            <button type="button" class="step-btn active" id="step1-btn" onclick="showSection(1)">1. Personal Info</button>
                            <button type="button" class="step-btn" id="step2-btn" onclick="showSection(2)">2. Account Details</button>
                        </div>

                        <form action="add_staff.php" method="POST" enctype="multipart/form-data" id="staffForm" novalidate>
                            <div class="form-section active" id="section1">
                                <div class="image-upload-container">
                                    <div class="image-preview-wrapper" onclick="document.getElementById('imageInput').click()">
                                        <div class="flex items-center justify-center h-full bg-slate-100 text-slate-400" id="previewPlaceholder">
                                            <i class="fas fa-user text-3xl md:text-4xl"></i>
                                        </div>
                                        <img src="" class="image-preview hidden" id="imagePreview" alt="Preview">
                                        <div class="camera-overlay">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    </div>
                                    <input type="file" id="imageInput" name="staff_image" class="hidden" accept="image/*" onchange="previewImage(event)">
                                    <p class="text-[10px] md:text-xs text-slate-400 mt-3 font-bold uppercase tracking-wider">Upload Profile Photo</p>
                                </div>

                                <div class="form-grid">
                                    <div class="field-group">
                                        <label><i class="fas fa-user"></i> Full Name <span class="text-red-500">*</span></label>
                                        <div class="input-wrapper">
                                            <input type="text" name="name" id="name" 
                                                value="<?php echo isset($form_data['name']) ? htmlspecialchars($form_data['name']) : ''; ?>" 
                                                required pattern="^[A-Za-z\s\-\'\\]+$"
                                                data-validation="name"
                                                title="Only letters, spaces, hyphens, and apostrophes are allowed."
                                                placeholder="Enter full name">
                                            <i class="fas fa-check-circle input-icon" id="name_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="name_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Only letters, spaces, hyphens, and apostrophes are allowed.</span>
                                        </div>
                                        <div class="validation-message success" id="name_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid name format</span>
                                        </div>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-phone"></i> Mobile Number</label>
                                        <div class="input-wrapper">
                                            <input type="tel" name="mobile" id="mobile" 
                                                value="<?php echo isset($form_data['mobile']) ? htmlspecialchars($form_data['mobile']) : ''; ?>" 
                                                pattern="[0-9]{10}" maxlength="10" minlength="10"
                                                data-validation="mobile"
                                                title="Please enter exactly 10 digits (0-9)"
                                                placeholder="9876543210">
                                            <i class="fas fa-check-circle input-icon" id="mobile_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="mobile_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Please enter exactly 10 digits (0-9)</span>
                                        </div>
                                        <div class="validation-message success" id="mobile_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid 10-digit mobile number</span>
                                        </div>
                                        <small class="validation-hint">Enter exactly 10 digits (e.g., 9876543210)</small>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-envelope"></i> Email Address <span class="text-red-500">*</span></label>
                                        <div class="input-wrapper">
                                            <input type="email" name="email" id="email" 
                                                value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" 
                                                required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                                data-validation="email"
                                                title="Please enter a valid email address."
                                                placeholder="staff@hospital.com">
                                            <i class="fas fa-check-circle input-icon" id="email_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="email_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Please enter a valid email address (e.g., staff@hospital.com)</span>
                                        </div>
                                        <div class="validation-message success" id="email_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid email address</span>
                                        </div>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-user-tag"></i> Role <span class="text-red-500">*</span></label>
                                        <select name="selectrole" id="selectrole" required>
                                            <option value="">Select Role</option>
                                            <?php foreach ($staff_roles as $role_option): ?>
                                                <option value="<?php echo $role_option; ?>" <?php echo (isset($form_data['selectrole']) && $form_data['selectrole'] == $role_option) ? 'selected' : ''; ?>>
                                                    <?php echo $role_option; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field-group full-width">
                                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                                        <div class="input-wrapper">
                                            <textarea name="address" id="address" rows="2" 
                                                pattern="^[A-Za-z0-9\s\-\.,#\/]+$"
                                                data-validation="address"
                                                placeholder="Enter complete address"><?php echo isset($form_data['address']) ? htmlspecialchars($form_data['address']) : ''; ?></textarea>
                                        </div>
                                        <div class="validation-message error" id="address_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.</span>
                                        </div>
                                        <div class="validation-message success" id="address_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid address format</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-end">
                                    <button type="button" class="btn-primary" onclick="showSection(2)">
                                        Next: Account Details <i class="fas fa-arrow-right ml-2"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-section" id="section2">
                                <div class="form-grid">
                                    <div class="field-group">
                                        <label><i class="fas fa-toggle-on"></i> Status</label>
                                        <select name="status" id="status">
                                            <option value="Active" <?php echo (isset($form_data['status']) && $form_data['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo (isset($form_data['status']) && $form_data['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-lock"></i> Password <span class="text-red-500">*</span></label>
                                        <div class="input-wrapper">
                                            <input type="password" name="password" id="password" 
                                                required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                                data-validation="password"
                                                title="Password must be at least 8 characters with uppercase, lowercase, number, and special character."
                                                placeholder="Set secure password">
                                            <i class="fas fa-check-circle input-icon" id="password_icon"></i>
                                        </div>
                                        <div class="password-strength">
                                            <div class="strength-bar" id="strengthBar"></div>
                                        </div>
                                        <div class="strength-text" id="strengthText">Weak</div>
                                        <div class="password-requirements">
                                            <span class="req-item unmet" id="reqLength">
                                                <i class="fas fa-times req-icon"></i> 8+ characters
                                            </span>
                                            <span class="req-item unmet" id="reqUpper">
                                                <i class="fas fa-times req-icon"></i> Uppercase
                                            </span>
                                            <span class="req-item unmet" id="reqLower">
                                                <i class="fas fa-times req-icon"></i> Lowercase
                                            </span>
                                            <span class="req-item unmet" id="reqNumber">
                                                <i class="fas fa-times req-icon"></i> Number
                                            </span>
                                            <span class="req-item unmet" id="reqSpecial">
                                                <i class="fas fa-times req-icon"></i> Special char
                                            </span>
                                        </div>
                                        <div class="validation-message error" id="password_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Must be at least 8 characters with uppercase, lowercase, number & special character</span>
                                        </div>
                                        <div class="validation-message success" id="password_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Strong password</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-10 flex flex-col sm:flex-row justify-between gap-4 border-t pt-8">
                                    <button type="button" class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition order-2 sm:order-1 flex items-center justify-center" onclick="showSection(1)">
                                        <i class="fas fa-arrow-left mr-2"></i> Back
                                    </button>
                                    <button type="submit" name="submit" class="btn-primary order-1 sm:order-2">
                                        <i class="fas fa-check-circle"></i> Complete Registration
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebarContainer = document.getElementById('sidebar-container');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            function openSidebar() {
                sidebarContainer.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
        });

        function showSection(step) {
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.step-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById('section' + step).classList.add('active');
            document.getElementById('step' + step + '-btn').classList.add('active');
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                const placeholder = document.getElementById('previewPlaceholder');
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        // ============================================================
        // VALIDATION LOGIC
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Define validation patterns
            const patterns = {
                name: /^[A-Za-z\s\-\'\\]+$/,
                email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                mobile: /^[0-9]{10}$/,
                password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
                address: /^[A-Za-z0-9\s\-\.,#\/]*$/
            };

            // Get all fields that need validation
            const fields = {
                name: { pattern: patterns.name, required: true },
                email: { pattern: patterns.email, required: true },
                mobile: { pattern: patterns.mobile, required: false },
                password: { pattern: patterns.password, required: true },
                address: { pattern: patterns.address, required: false }
            };

            // Function to validate a single field
            function validateField(fieldId) {
                const input = document.getElementById(fieldId);
                if (!input) return true;

                const value = input.value.trim();
                const fieldConfig = fields[fieldId];
                const isRequired = fieldConfig ? fieldConfig.required : false;
                const pattern = fieldConfig ? fieldConfig.pattern : null;

                const errorMsg = document.getElementById(fieldId + '_error');
                const successMsg = document.getElementById(fieldId + '_success');
                const icon = document.getElementById(fieldId + '_icon');

                // Reset states
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }

                // Check if empty and required
                if (isRequired && value === '') {
                    input.classList.add('error');
                    if (errorMsg) errorMsg.classList.add('show');
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // If optional and empty, it's valid
                if (!isRequired && value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }

                // Test against pattern
                if (pattern && !pattern.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) errorMsg.classList.add('show');
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // All validations passed
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Mobile number validation - exactly 10 digits
            function validateMobile(input) {
                const value = input.value.trim();
                const errorMsg = document.getElementById('mobile_error');
                const successMsg = document.getElementById('mobile_success');
                const icon = document.getElementById('mobile_icon');
                
                // Reset states
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }
                
                // If empty, it's optional
                if (value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }
                
                // Check if exactly 10 digits
                const mobileRegex = /^[0-9]{10}$/;
                if (!mobileRegex.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) {
                        if (value.length > 0 && value.length < 10) {
                            errorMsg.querySelector('span').textContent = 'Please enter exactly 10 digits (currently ' + value.length + ' digits)';
                        } else if (value.length > 10) {
                            errorMsg.querySelector('span').textContent = 'Maximum 10 digits allowed (currently ' + value.length + ' digits)';
                        } else {
                            errorMsg.querySelector('span').textContent = 'Please enter exactly 10 digits (0-9 only)';
                        }
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }
                
                // Valid
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Password strength checker
            function checkPasswordStrength(password) {
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');
                
                let score = 0;
                const checks = {
                    length: password.length >= 8,
                    upper: /[A-Z]/.test(password),
                    lower: /[a-z]/.test(password),
                    number: /\d/.test(password),
                    special: /[@$!%*?&]/.test(password)
                };

                // Update requirement indicators
                document.getElementById('reqLength').className = `req-item ${checks.length ? 'met' : 'unmet'}`;
                document.getElementById('reqUpper').className = `req-item ${checks.upper ? 'met' : 'unmet'}`;
                document.getElementById('reqLower').className = `req-item ${checks.lower ? 'met' : 'unmet'}`;
                document.getElementById('reqNumber').className = `req-item ${checks.number ? 'met' : 'unmet'}`;
                document.getElementById('reqSpecial').className = `req-item ${checks.special ? 'met' : 'unmet'}`;

                // Calculate score
                if (checks.length) score++;
                if (checks.upper) score++;
                if (checks.lower) score++;
                if (checks.number) score++;
                if (checks.special) score++;

                // Update strength bar
                if (password.length === 0) {
                    strengthBar.className = 'strength-bar';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#9ca3af';
                    return;
                }

                if (score <= 2) {
                    strengthBar.className = 'strength-bar weak';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#ef4444';
                } else if (score === 3) {
                    strengthBar.className = 'strength-bar fair';
                    strengthText.textContent = 'Fair';
                    strengthText.style.color = '#f59e0b';
                } else if (score === 4) {
                    strengthBar.className = 'strength-bar good';
                    strengthText.textContent = 'Good';
                    strengthText.style.color = '#3b82f6';
                } else {
                    strengthBar.className = 'strength-bar strong';
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = '#22c55e';
                }
            }

            // Attach event listeners for real-time validation
            Object.keys(fields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                // Validate on blur
                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });

                // Validate on input for better UX
                input.addEventListener('input', function() {
                    validateField(fieldId);
                    
                    // Special handling for password
                    if (fieldId === 'password') {
                        checkPasswordStrength(this.value);
                    }
                });
            });

            // Special handling for password field
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    validateField('password');
                });
            }

            // Mobile number specific validation
            const mobileInput = document.getElementById('mobile');
            if (mobileInput) {
                mobileInput.addEventListener('input', function() {
                    // Remove non-digits
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limit to 10 characters
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }
                    
                    validateMobile(this);
                });
                
                mobileInput.addEventListener('blur', function() {
                    validateMobile(this);
                });
                
                mobileInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedText.replace(/[^0-9]/g, '');
                    if (digits.length > 0) {
                        this.value = digits.slice(0, 10);
                        validateMobile(this);
                    }
                });
            }

            // Form submission validation
            document.getElementById('staffForm').addEventListener('submit', function(e) {
                let isValid = true;

                // Validate all fields
                Object.keys(fields).forEach(fieldId => {
                    if (fieldId === 'mobile') {
                        if (!validateMobile(document.getElementById('mobile'))) {
                            isValid = false;
                        }
                    } else if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = document.querySelector('.field-group input.error, .field-group textarea.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            // Reset form - clear validation states
            document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
                setTimeout(() => {
                    document.querySelectorAll('.field-group input, .field-group textarea').forEach(input => {
                        input.classList.remove('error', 'success');
                    });
                    document.querySelectorAll('.validation-message').forEach(msg => {
                        msg.classList.remove('show');
                    });
                    document.querySelectorAll('.input-icon').forEach(icon => {
                        icon.classList.remove('valid', 'invalid');
                    });
                    // Reset password strength
                    const strengthBar = document.getElementById('strengthBar');
                    const strengthText = document.getElementById('strengthText');
                    if (strengthBar) strengthBar.className = 'strength-bar';
                    if (strengthText) {
                        strengthText.textContent = 'Weak';
                        strengthText.style.color = '#9ca3af';
                    }
                }, 10);
            });
        });
    </script>
</body>
</html>