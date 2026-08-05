<?php
session_start();
include "config/hospital.php";
require_once "config/send_registration_email.php";
require_once 'config/subscription_limits.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['form_data']);
}

$message = "";
$messageType = "";
$image_path = "";

$hid = $_SESSION["hospital_id"];

// ============================================================
// FETCH STAFF ROLES (for dropdown and validation)
// ============================================================
$staff_roles_query = "SELECT role_id, role_name FROM roles 
                      WHERE delete_flag = 0 
                        AND role_slug NOT IN ('superadmin', 'admin', 'doctor', 'patient') 
                      ORDER BY role_name";
$staff_roles_result = mysqli_query($conn, $staff_roles_query);
$staff_roles = [];
if ($staff_roles_result && mysqli_num_rows($staff_roles_result) > 0) {
    while ($row = mysqli_fetch_assoc($staff_roles_result)) {
        $staff_roles[$row['role_id']] = $row['role_name'];
    }
}

// Fetch departments for dropdown
$departments_query = "SELECT id, department_name FROM department WHERE delete_flag = 0 and hospital_id = '$hid' ORDER BY department_name";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];

if ($departments_result && mysqli_num_rows($departments_result) > 0) {
    while ($row = mysqli_fetch_assoc($departments_result)) {
        $departments[$row['id']] = $row['department_name'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    $_SESSION['form_data'] = $_POST;

    // Personal Information
    $name           = mysqli_real_escape_string($conn, trim($_POST['name']));
    $mobile         = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $email          = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role_id        = isset($_POST['selectrole']) ? (int)$_POST['selectrole'] : 0;
    $password       = $_POST['password'];
    $address        = mysqli_real_escape_string($conn, trim($_POST['address']));
    $status         = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Additional Staff Fields
    $date_of_birth  = !empty($_POST['date_of_birth']) ? mysqli_real_escape_string($conn, $_POST['date_of_birth']) : NULL;
    $gender         = !empty($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : NULL;
    $blood_group    = !empty($_POST['blood_group']) ? mysqli_real_escape_string($conn, $_POST['blood_group']) : NULL;
    $qualification  = !empty($_POST['qualification']) ? mysqli_real_escape_string($conn, trim($_POST['qualification'])) : NULL;
    $specialization = !empty($_POST['specialization']) ? mysqli_real_escape_string($conn, trim($_POST['specialization'])) : NULL;
    $department_id  = !empty($_POST['department']) ? (int)$_POST['department'] : NULL;
    $experience     = !empty($_POST['experience']) ? (float)$_POST['experience'] : NULL;
    $shift_timing   = !empty($_POST['shift_timing']) ? mysqli_real_escape_string($conn, $_POST['shift_timing']) : NULL;
    $emergency_contact = !empty($_POST['emergency_contact']) ? mysqli_real_escape_string($conn, trim($_POST['emergency_contact'])) : NULL;

    // Server-side Validation
    if (empty($name) || empty($email) || empty($password) || empty($role_id)) {
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
    } elseif (!array_key_exists($role_id, $staff_roles)) {
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
    } elseif (!empty($date_of_birth) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
        $message = "Invalid Date of Birth format.";
        $messageType = "error";
    } elseif (!empty($gender) && !in_array($gender, ['Male', 'Female', 'Other'])) {
        $message = "Invalid Gender selected.";
        $messageType = "error";
    } elseif (!empty($blood_group) && !in_array($blood_group, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])) {
        $message = "Invalid Blood Group selected.";
        $messageType = "error";
    } elseif (!empty($department_id) && !array_key_exists($department_id, $departments)) {
        $message = "Invalid Department selected.";
        $messageType = "error";
    } elseif (!empty($experience) && ($experience < 0 || $experience > 50)) {
        $message = "Experience must be between 0 and 50 years.";
        $messageType = "error";
    } elseif (!empty($emergency_contact) && !preg_match('/^[0-9]{10}$/', $emergency_contact)) {
        $message = "Invalid Emergency Contact. Must be exactly 10 digits.";
        $messageType = "error";
    } else {
        // Check email in register table
        $check_sql = "SELECT * FROM register WHERE email='$email' AND delete_flag=0";
        $check_result = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_result) > 0) {
            $message = "Email already exists.";
            $messageType = "error";
        } else {
            // Check subscription limit for staff
            if (!checkResourceLimit($hid, 'staff')) {
                $message = getLimitMessage('staff');
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
                        $role_name = mysqli_real_escape_string($conn, $staff_roles[$role_id]);

                        // Insert into register table
                        $register_sql = "INSERT INTO register (name, email, password, role_id, role, created_by, modified_by, hospital_id) 
                                         VALUES ('$name', '$email', '$hashed_password', '$role_id', '$role_name', 'Admin', 'Admin', '$hid')";
                        
                        if (!mysqli_query($conn, $register_sql)) {
                            throw new Exception(mysqli_error($conn));
                        }

                        $register_id = mysqli_insert_id($conn);

                        // Calculate age from date of birth
                        $age = NULL;
                        if ($date_of_birth) {
                            $birth_date = new DateTime($date_of_birth);
                            $today = new DateTime('today');
                            $age = $birth_date->diff($today)->y;
                        }

                        // Insert into staff table
                        $staff_sql = "INSERT INTO staff(
                            register_id, name, mobile, email, role, address, 
                            date_of_birth, age, gender, blood_group, 
                            qualification, specialization, department, 
                            experience, shift_timing, emergency_contact, 
                            profile_image, status, hospital_id
                        ) VALUES (
                            '$register_id', '$name', '$mobile', '$email', '$role_name', '$address',
                            " . ($date_of_birth ? "'$date_of_birth'" : "NULL") . ", 
                            " . ($age !== NULL ? "'$age'" : "NULL") . ", 
                            " . ($gender ? "'$gender'" : "NULL") . ", 
                            " . ($blood_group ? "'$blood_group'" : "NULL") . ",
                            " . ($qualification ? "'$qualification'" : "NULL") . ",
                            " . ($specialization ? "'$specialization'" : "NULL") . ",
                            " . ($department_id ? "'$department_id'" : "NULL") . ",
                            " . ($experience !== NULL ? "'$experience'" : "NULL") . ",
                            " . ($shift_timing ? "'$shift_timing'" : "NULL") . ",
                            " . ($emergency_contact ? "'$emergency_contact'" : "NULL") . ",
                            '$image_path', '$status', '$hid'
                        )";

                        if (!mysqli_query($conn, $staff_sql)) {
                            throw new Exception(mysqli_error($conn));
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
}

$form_data = $_SESSION['form_data'] ?? [];
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
        
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }
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
        
        .btn-primary { background: #3b82f6; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-secondary { background: #e2e8f0; color: #475569; padding: 12px 24px; border-radius: 12px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: #22c55e; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-success:hover { background: #16a34a; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(34,197,94,0.2); }
        
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

        /* Wizard Progress Bar Styles */
        .wizard-container {
            margin-bottom: 32px;
            padding: 0 10px;
        }
        .wizard-progress {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 0 20px;
        }
        .wizard-progress::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }
        .wizard-progress .progress-bar {
            position: absolute;
            top: 30px;
            left: 0;
            height: 3px;
            background: #3b82f6;
            z-index: 1;
            transition: width 0.5s ease;
            width: 0%;
        }
        .wizard-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
            cursor: pointer;
        }
        .wizard-step .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 3px solid #fff;
        }
        .wizard-step.active .step-number {
            background: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.2);
        }
        .wizard-step.completed .step-number {
            background: #22c55e;
            color: #fff;
        }
        .wizard-step .step-label {
            margin-top: 8px;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-align: center;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .wizard-step.active .step-label {
            color: #3b82f6;
        }
        .wizard-step.completed .step-label {
            color: #22c55e;
        }
        .wizard-step .step-icon {
            font-size: 14px;
        }
        @media (max-width: 640px) {
            .wizard-progress {
                margin: 0 5px;
            }
            .wizard-step .step-label {
                font-size: 9px;
                margin-top: 4px;
            }
            .wizard-step .step-number {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            .wizard-progress::before {
                top: 24px;
            }
            .wizard-progress .progress-bar {
                top: 24px;
            }
        }

        .form-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .section-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 24px;
        }
        .required-star {
            color: #ef4444;
            margin-left: 2px;
        }
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
                        <p class="text-slate-500 text-xs md:text-sm mt-1">Register a new staff member using the wizard</p>
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
                            <h3>Staff Registration Wizard</h3>
                            <div class="subtitle">Complete all steps to add a new staff member</div>
                        </div>
                    </div>

                    <div class="body">
                        <!-- Wizard Progress -->
                        <div class="wizard-container">
                            <div class="wizard-progress">
                                <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
                                
                                <div class="wizard-step active" data-step="1" onclick="goToStep(1)">
                                    <div class="step-number">1</div>
                                    <div class="step-label">
                                        <span class="step-icon"><i class="fas fa-user"></i></span>
                                        <span class="hidden sm:inline">Personal</span>
                                    </div>
                                </div>
                                
                                <div class="wizard-step" data-step="2" onclick="goToStep(2)">
                                    <div class="step-number">2</div>
                                    <div class="step-label">
                                        <span class="step-icon"><i class="fas fa-briefcase"></i></span>
                                        <span class="hidden sm:inline">Professional</span>
                                    </div>
                                </div>
                                
                                <div class="wizard-step" data-step="3" onclick="goToStep(3)">
                                    <div class="step-number">3</div>
                                    <div class="step-label">
                                        <span class="step-icon"><i class="fas fa-lock"></i></span>
                                        <span class="hidden sm:inline">Account</span>
                                    </div>
                                </div>
                                
                                <div class="wizard-step" data-step="4" onclick="goToStep(4)">
                                    <div class="step-number">4</div>
                                    <div class="step-label">
                                        <span class="step-icon"><i class="fas fa-check-circle"></i></span>
                                        <span class="hidden sm:inline">Confirm</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form action="add_staff.php" method="POST" enctype="multipart/form-data" id="staffForm" novalidate>
                            
                            <!-- STEP 1: Personal Information -->
                            <div class="form-section active" id="step1">
                                <div class="section-title"><i class="fas fa-user text-blue-500 mr-2"></i>Personal Information</div>
                                <div class="section-subtitle">Enter the staff member's personal details</div>

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
                                        <label><i class="fas fa-user"></i> Full Name <span class="required-star">*</span></label>
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
                                        <label><i class="fas fa-envelope"></i> Email Address <span class="required-star">*</span></label>
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
                                        <label><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                                        <input type="date" name="date_of_birth" id="date_of_birth" 
                                            value="<?php echo isset($form_data['date_of_birth']) ? htmlspecialchars($form_data['date_of_birth']) : ''; ?>"
                                            max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-venus-mars"></i> Gender</label>
                                        <select name="gender" id="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo (isset($form_data['gender']) && $form_data['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($form_data['gender']) && $form_data['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo (isset($form_data['gender']) && $form_data['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-tint"></i> Blood Group</label>
                                        <select name="blood_group" id="blood_group">
                                            <option value="">Select Blood Group</option>
                                            <option value="A+" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                            <option value="O+" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo (isset($form_data['blood_group']) && $form_data['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
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

                                    <div class="field-group full-width">
                                        <label><i class="fas fa-phone-alt"></i> Emergency Contact</label>
                                        <div class="input-wrapper">
                                            <input type="tel" name="emergency_contact" id="emergency_contact" 
                                                value="<?php echo isset($form_data['emergency_contact']) ? htmlspecialchars($form_data['emergency_contact']) : ''; ?>" 
                                                pattern="[0-9]{10}" maxlength="10" minlength="10"
                                                placeholder="Emergency contact number"
                                                title="Please enter exactly 10 digits (0-9)">
                                            <i class="fas fa-check-circle input-icon" id="emergency_contact_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="emergency_contact_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Please enter exactly 10 digits (0-9)</span>
                                        </div>
                                        <div class="validation-message success" id="emergency_contact_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid 10-digit number</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-end">
                                    <button type="button" class="btn-primary" onclick="nextStep()">
                                        Next Step <i class="fas fa-arrow-right ml-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 2: Professional Details -->
                            <div class="form-section" id="step2">
                                <div class="section-title"><i class="fas fa-briefcase text-blue-500 mr-2"></i>Professional Details</div>
                                <div class="section-subtitle">Enter the staff member's professional information</div>

                                <div class="form-grid">
                                    <div class="field-group">
                                        <label><i class="fas fa-user-tag"></i> Role <span class="required-star">*</span></label>
                                        <select name="selectrole" id="selectrole" required>
                                            <option value="">Select Role</option>
                                            <?php foreach ($staff_roles as $role_id => $role_name): ?>
                                                <option value="<?php echo $role_id; ?>" <?php echo (isset($form_data['selectrole']) && (int)$form_data['selectrole'] == $role_id) ? 'selected' : ''; ?>>
                                                    <?php echo $role_name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-building"></i> Department</label>
                                        <select name="department" id="department">
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept_id => $dept_name): ?>
                                                <option value="<?php echo $dept_id; ?>" <?php echo (isset($form_data['department']) && (int)$form_data['department'] == $dept_id) ? 'selected' : ''; ?>>
                                                    <?php echo $dept_name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-graduation-cap"></i> Qualification</label>
                                        <input type="text" name="qualification" id="qualification" 
                                            value="<?php echo isset($form_data['qualification']) ? htmlspecialchars($form_data['qualification']) : ''; ?>"
                                            placeholder="e.g., MBBS, B.Sc Nursing">
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-stethoscope"></i> Specialization</label>
                                        <input type="text" name="specialization" id="specialization" 
                                            value="<?php echo isset($form_data['specialization']) ? htmlspecialchars($form_data['specialization']) : ''; ?>"
                                            placeholder="e.g., Cardiology, Pediatrics">
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-briefcase"></i> Experience (Years)</label>
                                        <input type="number" name="experience" id="experience" 
                                            value="<?php echo isset($form_data['experience']) ? htmlspecialchars($form_data['experience']) : ''; ?>"
                                            min="0" max="50" step="0.5"
                                            placeholder="e.g., 5">
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-clock"></i> Shift Timing</label>
                                        <select name="shift_timing" id="shift_timing">
                                            <option value="">Select Shift</option>
                                            <option value="Morning" <?php echo (isset($form_data['shift_timing']) && $form_data['shift_timing'] == 'Morning') ? 'selected' : ''; ?>>Morning (6:00 AM - 2:00 PM)</option>
                                            <option value="Evening" <?php echo (isset($form_data['shift_timing']) && $form_data['shift_timing'] == 'Evening') ? 'selected' : ''; ?>>Evening (2:00 PM - 10:00 PM)</option>
                                            <option value="Night" <?php echo (isset($form_data['shift_timing']) && $form_data['shift_timing'] == 'Night') ? 'selected' : ''; ?>>Night (10:00 PM - 6:00 AM)</option>
                                            <option value="Flexible" <?php echo (isset($form_data['shift_timing']) && $form_data['shift_timing'] == 'Flexible') ? 'selected' : ''; ?>>Flexible</option>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label><i class="fas fa-toggle-on"></i> Status</label>
                                        <select name="status" id="status">
                                            <option value="Active" <?php echo (isset($form_data['status']) && $form_data['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo (isset($form_data['status']) && $form_data['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-between">
                                    <button type="button" class="btn-secondary" onclick="prevStep()">
                                        <i class="fas fa-arrow-left mr-2"></i> Previous Step
                                    </button>
                                    <button type="button" class="btn-primary" onclick="nextStep()">
                                        Next Step <i class="fas fa-arrow-right ml-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 3: Account Setup -->
                            <div class="form-section" id="step3">
                                <div class="section-title"><i class="fas fa-lock text-blue-500 mr-2"></i>Account Setup</div>
                                <div class="section-subtitle">Create login credentials for the staff member</div>

                                <div class="form-grid">
                                    <div class="field-group full-width">
                                        <label><i class="fas fa-lock"></i> Password <span class="required-star">*</span></label>
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

                                <div class="mt-8 flex justify-between">
                                    <button type="button" class="btn-secondary" onclick="prevStep()">
                                        <i class="fas fa-arrow-left mr-2"></i> Previous Step
                                    </button>
                                    <button type="button" class="btn-primary" onclick="nextStep()">
                                        Review & Confirm <i class="fas fa-arrow-right ml-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 4: Confirmation -->
                            <div class="form-section" id="step4">
                                <div class="section-title"><i class="fas fa-check-circle text-green-500 mr-2"></i>Review & Confirm</div>
                                <div class="section-subtitle">Please review all information before submitting</div>

                                <div class="bg-slate-50 rounded-xl p-6 mb-6 border border-slate-200">
                                    <h4 class="font-bold text-slate-700 mb-4"><i class="fas fa-user mr-2 text-blue-500"></i>Personal Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div><span class="text-slate-500">Full Name:</span> <span class="font-medium" id="review_name">-</span></div>
                                        <div><span class="text-slate-500">Mobile:</span> <span class="font-medium" id="review_mobile">-</span></div>
                                        <div><span class="text-slate-500">Email:</span> <span class="font-medium" id="review_email">-</span></div>
                                        <div><span class="text-slate-500">Date of Birth:</span> <span class="font-medium" id="review_dob">-</span></div>
                                        <div><span class="text-slate-500">Gender:</span> <span class="font-medium" id="review_gender">-</span></div>
                                        <div><span class="text-slate-500">Blood Group:</span> <span class="font-medium" id="review_blood">-</span></div>
                                        <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <span class="font-medium" id="review_address">-</span></div>
                                        <div class="md:col-span-2"><span class="text-slate-500">Emergency Contact:</span> <span class="font-medium" id="review_emergency">-</span></div>
                                    </div>
                                </div>

                                <div class="bg-slate-50 rounded-xl p-6 mb-6 border border-slate-200">
                                    <h4 class="font-bold text-slate-700 mb-4"><i class="fas fa-briefcase mr-2 text-blue-500"></i>Professional Details</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div><span class="text-slate-500">Role:</span> <span class="font-medium" id="review_role">-</span></div>
                                        <div><span class="text-slate-500">Department:</span> <span class="font-medium" id="review_department">-</span></div>
                                        <div><span class="text-slate-500">Qualification:</span> <span class="font-medium" id="review_qualification">-</span></div>
                                        <div><span class="text-slate-500">Specialization:</span> <span class="font-medium" id="review_specialization">-</span></div>
                                        <div><span class="text-slate-500">Experience:</span> <span class="font-medium" id="review_experience">-</span></div>
                                        <div><span class="text-slate-500">Shift Timing:</span> <span class="font-medium" id="review_shift">-</span></div>
                                        <div><span class="text-slate-500">Status:</span> <span class="font-medium" id="review_status">-</span></div>
                                    </div>
                                </div>

                                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-sm text-yellow-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Please verify all information carefully. Once submitted, you can edit the staff details later.
                                </div>

                                <div class="mt-8 flex justify-between">
                                    <button type="button" class="btn-secondary" onclick="prevStep()">
                                        <i class="fas fa-arrow-left mr-2"></i> Previous Step
                                    </button>
                                    <button type="submit" name="submit" class="btn-success">
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
        // Sidebar toggle
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

        // Wizard Navigation
        let currentStep = 1;
        const totalSteps = 4;

        function goToStep(step) {
            if (step < 1 || step > totalSteps) return;
            
            // Hide all sections
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            
            // Show target section
            document.getElementById('step' + step).classList.add('active');
            
            // Update wizard steps
            document.querySelectorAll('.wizard-step').forEach((el, index) => {
                const stepNum = index + 1;
                el.classList.remove('active', 'completed');
                if (stepNum === step) {
                    el.classList.add('active');
                } else if (stepNum < step) {
                    el.classList.add('completed');
                }
            });
            
            // Update progress bar
            const progress = ((step - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            currentStep = step;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Update review data when going to step 4
            if (step === 4) {
                updateReview();
            }
        }

        function nextStep() {
            // Validate current step before proceeding
            if (currentStep === 1) {
                if (!validateStep1()) return;
            } else if (currentStep === 2) {
                if (!validateStep2()) return;
            } else if (currentStep === 3) {
                if (!validateStep3()) return;
            }
            
            if (currentStep < totalSteps) {
                goToStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        }

        // Step Validation
        function validateStep1() {
            let valid = true;
            
            // Validate name
            const name = document.getElementById('name');
            if (!validateField('name')) {
                valid = false;
            }
            
            // Validate email
            if (!validateField('email')) {
                valid = false;
            }
            
            // Validate mobile if filled
            const mobile = document.getElementById('mobile');
            if (mobile.value.trim() !== '' && !validateMobile(mobile)) {
                valid = false;
            }
            
            // Validate address if filled
            const address = document.getElementById('address');
            if (address.value.trim() !== '' && !validateField('address')) {
                valid = false;
            }
            
            // Validate emergency contact if filled
            const emergency = document.getElementById('emergency_contact');
            if (emergency.value.trim() !== '' && !validateEmergencyContact(emergency)) {
                valid = false;
            }
            
            if (!valid) {
                const firstError = document.querySelector('#step1 .field-group input.error, #step1 .field-group textarea.error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            
            return true;
        }

        function validateStep2() {
            let valid = true;
            
            // Validate role
            const role = document.getElementById('selectrole');
            if (role.value === '') {
                role.style.borderColor = '#ef4444';
                role.focus();
                valid = false;
            } else {
                role.style.borderColor = '';
            }
            
            if (!valid) {
                document.getElementById('selectrole').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            return true;
        }

        function validateStep3() {
            let valid = true;
            
            // Validate password
            if (!validateField('password')) {
                valid = false;
            }
            
            if (!valid) {
                document.getElementById('password').focus();
                document.getElementById('password').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            return true;
        }

        // Review Data Update
        function updateReview() {
            const fields = {
                name: 'review_name',
                mobile: 'review_mobile',
                email: 'review_email',
                date_of_birth: 'review_dob',
                gender: 'review_gender',
                blood_group: 'review_blood',
                address: 'review_address',
                emergency_contact: 'review_emergency',
                selectrole: 'review_role',
                department: 'review_department',
                qualification: 'review_qualification',
                specialization: 'review_specialization',
                experience: 'review_experience',
                shift_timing: 'review_shift',
                status: 'review_status'
            };
            
            Object.keys(fields).forEach(id => {
                const input = document.getElementById(id);
                const reviewEl = document.getElementById(fields[id]);
                if (input && reviewEl) {
                    let value = input.value.trim() || '-';
                    
                    // Format select values
                    if (id === 'selectrole') {
                        const option = input.options[input.selectedIndex];
                        value = option ? option.text : '-';
                    } else if (id === 'department') {
                        const option = input.options[input.selectedIndex];
                        value = option ? option.text : '-';
                    } else if (id === 'gender') {
                        value = value || '-';
                    } else if (id === 'blood_group') {
                        value = value || '-';
                    } else if (id === 'shift_timing') {
                        value = value || '-';
                    } else if (id === 'status') {
                        value = value || '-';
                    } else if (id === 'experience') {
                        value = value !== '-' ? value + ' years' : '-';
                    } else if (id === 'date_of_birth') {
                        value = value !== '-' ? new Date(value).toLocaleDateString() : '-';
                    }
                    
                    reviewEl.textContent = value;
                }
            });
        }

        // Image Preview
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

        // Validation Functions (same as before)
        const patterns = {
            name: /^[A-Za-z\s\-\'\\]+$/,
            email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            mobile: /^[0-9]{10}$/,
            password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
            address: /^[A-Za-z0-9\s\-\.,#\/]*$/
        };

        const fields = {
            name: { pattern: patterns.name, required: true },
            email: { pattern: patterns.email, required: true },
            mobile: { pattern: patterns.mobile, required: false },
            password: { pattern: patterns.password, required: true },
            address: { pattern: patterns.address, required: false }
        };

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

            input.classList.remove('error', 'success');
            if (errorMsg) errorMsg.classList.remove('show');
            if (successMsg) successMsg.classList.remove('show');
            if (icon) {
                icon.classList.remove('valid', 'invalid');
            }

            if (isRequired && value === '') {
                input.classList.add('error');
                if (errorMsg) errorMsg.classList.add('show');
                if (icon) icon.classList.add('invalid');
                return false;
            }

            if (!isRequired && value === '') {
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            if (pattern && !pattern.test(value)) {
                input.classList.add('error');
                if (errorMsg) errorMsg.classList.add('show');
                if (icon) icon.classList.add('invalid');
                return false;
            }

            input.classList.add('success');
            if (successMsg) successMsg.classList.add('show');
            if (icon) icon.classList.add('valid');
            return true;
        }

        function validateMobile(input) {
            const value = input.value.trim();
            const errorMsg = document.getElementById('mobile_error');
            const successMsg = document.getElementById('mobile_success');
            const icon = document.getElementById('mobile_icon');
            
            input.classList.remove('error', 'success');
            if (errorMsg) errorMsg.classList.remove('show');
            if (successMsg) successMsg.classList.remove('show');
            if (icon) {
                icon.classList.remove('valid', 'invalid');
            }
            
            if (value === '') {
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }
            
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
            
            input.classList.add('success');
            if (successMsg) successMsg.classList.add('show');
            if (icon) icon.classList.add('valid');
            return true;
        }

        function validateEmergencyContact(input) {
            const value = input.value.trim();
            const errorMsg = document.getElementById('emergency_contact_error');
            const successMsg = document.getElementById('emergency_contact_success');
            const icon = document.getElementById('emergency_contact_icon');
            
            input.classList.remove('error', 'success');
            if (errorMsg) errorMsg.classList.remove('show');
            if (successMsg) successMsg.classList.remove('show');
            if (icon) {
                icon.classList.remove('valid', 'invalid');
            }
            
            if (value === '') {
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }
            
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
            
            input.classList.add('success');
            if (successMsg) successMsg.classList.add('show');
            if (icon) icon.classList.add('valid');
            return true;
        }

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

            document.getElementById('reqLength').className = `req-item ${checks.length ? 'met' : 'unmet'}`;
            document.getElementById('reqUpper').className = `req-item ${checks.upper ? 'met' : 'unmet'}`;
            document.getElementById('reqLower').className = `req-item ${checks.lower ? 'met' : 'unmet'}`;
            document.getElementById('reqNumber').className = `req-item ${checks.number ? 'met' : 'unmet'}`;
            document.getElementById('reqSpecial').className = `req-item ${checks.special ? 'met' : 'unmet'}`;

            if (checks.length) score++;
            if (checks.upper) score++;
            if (checks.lower) score++;
            if (checks.number) score++;
            if (checks.special) score++;

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

        // Event Listeners for Real-time Validation
        document.addEventListener('DOMContentLoaded', function() {
            // Attach validation events
            Object.keys(fields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                input.addEventListener('blur', function() {
                    if (fieldId === 'mobile') {
                        validateMobile(this);
                    } else {
                        validateField(fieldId);
                    }
                });

                input.addEventListener('input', function() {
                    if (fieldId === 'mobile') {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 10) {
                            this.value = this.value.slice(0, 10);
                        }
                        validateMobile(this);
                    } else if (fieldId === 'password') {
                        checkPasswordStrength(this.value);
                        validateField(fieldId);
                    } else {
                        validateField(fieldId);
                    }
                });
            });

            // Mobile input handler
            const mobileInput = document.getElementById('mobile');
            if (mobileInput) {
                mobileInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
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

            // Emergency contact handler
            const emergencyInput = document.getElementById('emergency_contact');
            if (emergencyInput) {
                emergencyInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }
                    validateEmergencyContact(this);
                });
                
                emergencyInput.addEventListener('blur', function() {
                    validateEmergencyContact(this);
                });
                
                emergencyInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedText.replace(/[^0-9]/g, '');
                    if (digits.length > 0) {
                        this.value = digits.slice(0, 10);
                        validateEmergencyContact(this);
                    }
                });
            }

            // Password handler
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    validateField('password');
                });
            }

            // Form submit validation
            document.getElementById('staffForm').addEventListener('submit', function(e) {
                let isValid = true;

                // Validate all steps
                if (!validateStep1()) isValid = false;
                if (!validateStep2()) isValid = false;
                if (!validateStep3()) isValid = false;

                if (!isValid) {
                    e.preventDefault();
                    // Find first error and navigate to that step
                    const errorFields = document.querySelectorAll('.field-group input.error, .field-group textarea.error, .field-group select.error');
                    if (errorFields.length > 0) {
                        const firstError = errorFields[0];
                        const step = firstError.closest('.form-section');
                        if (step) {
                            const stepId = step.id.replace('step', '');
                            goToStep(parseInt(stepId));
                            setTimeout(() => {
                                firstError.focus();
                                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }, 300);
                        }
                    }
                }
            });

            // Initialize wizard
            goToStep(1);
        });
    </script>
</body>
</html>