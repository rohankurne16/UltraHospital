<?php
include '../config/superadmin.php';
checkSuperAdminLogin();

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'ultrahospital8@gmail.com';
$mail->Password = 'rjuk cjay cbeq wrub';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->setFrom('ultrahospital8@gmail.com', 'UltraHospital');
$mail->isHTML(true);

$page_title = 'Add New Hospital';
$page_subtitle = 'Create a new hospital and assign admin';

$error = '';
$success = '';

function generateCode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}
$hospital_code = generateCode(6);

function encryptId($id) {
    $key = 'UltraHospital@2026#SecureKey';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($id, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function checkDuplicateHospital($hospital_name, $hospital_code, $conn) {
    $sql = "SELECT hospital_id FROM hospital_master 
            WHERE (hospital_name = '$hospital_name' OR hospital_code = '$hospital_code') 
            AND delete_flag = 0";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

function checkDuplicateAdmin($email, $conn) {
    $sql = "SELECT id FROM register WHERE email = '$email' AND delete_flag = 0";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hospital_name = mysqli_real_escape_string($conn, trim($_POST['hospital_name']));
    $hospital_code = mysqli_real_escape_string($conn, trim($_POST['hospital_code']));
    $hospital_type = mysqli_real_escape_string($conn, $_POST['hospital_type']);
    $registration_number = mysqli_real_escape_string($conn, trim($_POST['registration_number']));
    $gst_number = mysqli_real_escape_string($conn, trim($_POST['gst_number']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $website = mysqli_real_escape_string($conn, trim($_POST['website']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $hospital_logo = '';
    if (isset($_FILES['hospital_logo']) && $_FILES['hospital_logo']['error'] == 0) {
        $target_dir = "../documents/hospital/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['hospital_logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'hospital_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['hospital_logo']['tmp_name'], $target_file)) {
                $hospital_logo = 'documents/hospital/' . $new_filename;
            }
        }
    }
    
    $admin_name = mysqli_real_escape_string($conn, trim($_POST['admin_name']));
    $admin_email = mysqli_real_escape_string($conn, trim($_POST['admin_email']));
    $admin_password = $_POST['admin_password'];
    $admin_mobile = mysqli_real_escape_string($conn, trim($_POST['admin_mobile']));
    
    if (empty($hospital_name) || empty($hospital_code) || empty($admin_email) || empty($admin_password)) {
        $error = "Please fill all required fields";
    } 
    elseif (checkDuplicateHospital($hospital_name, $hospital_code, $conn)) {
        $error = "Hospital with this name or code already exists!";
    }
    elseif (checkDuplicateAdmin($admin_email, $conn)) {
        $error = "Admin email already registered!";
    }
    else {
        $sql = "INSERT INTO hospital_master (hospital_name, hospital_code, hospital_logo, hospital_type, registration_number, gst_number, address, city, state, country, pincode, phone, website, status) 
                VALUES ('$hospital_name', '$hospital_code', '$hospital_logo', '$hospital_type', '$registration_number', '$gst_number', '$address', '$city', '$state', '$country', '$pincode', '$phone', '$website', '$status')";
        
        if (mysqli_query($conn, $sql)) {
            $hospital_id = mysqli_insert_id($conn);
            
            logAudit('Hospital', 'Added new hospital: ' . $hospital_name . ' (ID: ' . $hospital_id . ')');
            
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            
            $role_query = "SELECT role_id FROM roles WHERE role_name = 'Admin' AND delete_flag = 0";
            $role_result = mysqli_query($conn, $role_query);
            $role_id = 0;
            if ($role_result && mysqli_num_rows($role_result) > 0) {
                $role_row = mysqli_fetch_assoc($role_result);
                $role_id = $role_row['role_id'];
            }
            
            if ($role_id == 0) {
                $insert_role = "INSERT INTO roles (role_name, role_description, status, created_at) 
                                VALUES ('Admin', 'Hospital Administrator', 'Active', NOW())";
                if (mysqli_query($conn, $insert_role)) {
                    $role_id = mysqli_insert_id($conn);
                    
                    $admin_permissions = [
                        'dashboard-view', 'appointments-view', 'patients-view', 
                        'doctors-view', 'staff-view', 'departments-view',
                        'reports-view', 'settings-view', 'profile-view'
                    ];
                    
                    foreach ($admin_permissions as $perm_slug) {
                        $perm_query = "SELECT permission_id FROM permissions WHERE permission_slug = '$perm_slug' AND delete_flag = 0";
                        $perm_result = mysqli_query($conn, $perm_query);
                        if ($perm_result && mysqli_num_rows($perm_result) > 0) {
                            $perm_row = mysqli_fetch_assoc($perm_result);
                            $perm_id = $perm_row['permission_id'];
                            $assign_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES ('$role_id', '$perm_id')";
                            mysqli_query($conn, $assign_query);
                        }
                    }
                }
            }
            
            $insert_admin = "INSERT INTO register (name, email, password, created_by, modified_by, role, role_id, hospital_id) 
                             VALUES ('$admin_name', '$admin_email', '$hashed_password', 'Super Admin', 'Super Admin', 'Admin', '$role_id', '$hospital_id')";
            
            if (mysqli_query($conn, $insert_admin)) {
                $register_id = mysqli_insert_id($conn);
                
                $insert_profile = "INSERT INTO hospital_admin (hospital_id, register_id, full_name, mobile, email) 
                                   VALUES ('$hospital_id', '$register_id', '$admin_name', '$admin_mobile', '$admin_email')";
                mysqli_query($conn, $insert_profile);
                
                logAudit('Hospital Admin', 'Added new hospital admin: ' . $admin_name . ' (ID: ' . $register_id . ') for hospital: ' . $hospital_name);
                
                $getTemplate = "SELECT subject, body FROM email_templates WHERE template_name='successful_registration'";
                $templateResult = mysqli_query($conn, $getTemplate);
                
                if ($templateResult && mysqli_num_rows($templateResult) > 0) {
                    $template = mysqli_fetch_assoc($templateResult);
                    
                    $subject = $template['subject'];
                    $body = $template['body'];
                    
                    $getHospital = "SELECT hospital_name, hospital_code FROM hospital_master WHERE hospital_id='$hospital_id'";
                    $hospitalResult = mysqli_query($conn, $getHospital);
                    $hospital = mysqli_fetch_assoc($hospitalResult);
                    
                    $getAdmin = "SELECT name, email FROM register WHERE id='$register_id'";
                    $adminResult = mysqli_query($conn, $getAdmin);
                    $admin = mysqli_fetch_assoc($adminResult);
                    
                    $encryptedHospitalId = encryptId($hospital_id);
                    $loginLink = "http://localhost/UltraHospital-main/index.php?hid=" . $encryptedHospitalId;
                    
                    $body = str_replace("{admin_name}", $admin['name'], $body);
                    $body = str_replace("{hospital_name}", $hospital['hospital_name'], $body);
                    $body = str_replace("{hospital_code}", $hospital['hospital_code'], $body);
                    $body = str_replace("{email}", $admin['email'], $body);
                    $body = str_replace("{password}", $admin_password, $body);
                    $body = str_replace("{login_link}", $loginLink, $body);
                    $body = str_replace("{year}", date('Y'), $body);
                    
                    try {
                        $mail->clearAddresses();
                        $mail->addAddress($admin['email'], $admin['name']);
                        $mail->Subject = $subject;
                        $mail->Body = $body;
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Email sending failed: " . $mail->ErrorInfo);
                    }
                }
                
                header("Location: hospitals.php?success=1");
                exit();
            } else {
                $error = "Error creating admin user: " . mysqli_error($conn);
            }
        } else {
            $error = "Error creating hospital: " . mysqli_error($conn);
        }
    }
}

$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hospital - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.light { background: #f1f5f9; }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            padding: 1rem 0.5rem;
            overflow-y: auto;
            z-index: 1000;
            transition: width 0.3s ease;
        }
        body.dark .sidebar { background: #1a1a1a; border-right: 1px solid #2a2a2a; }
        body.light .sidebar { background: #ffffff; border-right: 1px solid #e2e8f0; }
        .sidebar.closed { width: 70px; }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.8rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 2px 0;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        .sidebar-item i { width: 1.25rem; text-align: center; }
        .sidebar-item:hover { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .sidebar-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .sidebar.closed .sidebar-item span { display: none; }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            padding: 0 0.5rem 1rem 0.5rem;
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .brand-text h2 { 
            font-size: 0.9rem; 
            font-weight: 700; 
            margin: 0; 
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; 
        }
        .brand-text p { 
            font-size: 0.65rem; 
            color: #94a3b8; 
            margin: 0; 
        }
        
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed { margin-left: 70px; }
        
        .content-card { 
            border-radius: 16px; 
            padding: 2rem; 
            transition: all 0.3s ease; 
            margin-bottom: 1.5rem;
        }
        body.dark .content-card { 
            background: #1a1a1a; 
            border: 1px solid #2a2a2a; 
        }
        body.light .content-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.06); 
        }
        
        .form-control { 
            padding: 0.7rem 1rem; 
            border-radius: 10px; 
            transition: all 0.3s ease; 
            width: 100%; 
            outline: none; 
            font-size: 0.9rem; 
        }
        body.dark .form-control { 
            background: #1e1e1e; 
            border: 1px solid #2a2a2a; 
            color: #f1f5f9; 
        }
        body.light .form-control { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            color: #1e293b; 
        }
        .form-control:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); 
        }
        .form-control.error { border-color: #ef4444 !important; }
        
        label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem; color: <?php echo $theme == 'dark' ? '#94a3b8' : '#475569'; ?>; }
        .form-group { margin-bottom: 1.25rem; }
        .required { color: #ef4444; }
        
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            color: white; 
            border: none; 
            padding: 0.7rem 2rem; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); 
        }
        
        .btn-secondary { 
            padding: 0.7rem 2rem; 
            border-radius: 10px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-secondary:hover { 
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; 
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: white; 
            border: none; 
            padding: 0.7rem 2rem; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-success:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 30px -10px rgba(34, 197, 94, 0.5); 
        }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .error-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        .success-msg { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #16a34a; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        
        .file-upload-box { padding: 1.5rem; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        body.dark .file-upload-box { background: #1e1e1e; border: 2px dashed #2a2a2a; }
        body.light .file-upload-box { background: #f8fafc; border: 2px dashed #e2e8f0; }
        .file-upload-box:hover { border-color: #3b82f6; }
        .file-upload-box .upload-icon { font-size: 2rem; color: #3b82f6; margin-bottom: 0.5rem; }
        .file-upload-box .upload-text { font-size: 0.85rem; color: #94a3b8; }
        .file-upload-box .upload-hint { font-size: 0.7rem; color: #64748b; }
        .file-upload-box input[type="file"] { display: none; }
        .file-preview { display: none; margin-top: 0.5rem; padding: 0.5rem; border-radius: 8px; background: rgba(59, 130, 246, 0.1); align-items: center; gap: 0.5rem; }
        .file-preview img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .file-preview .file-name { font-size: 0.8rem; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; }
        
        .wizard-container { position: relative; }
        .wizard-progress { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; padding: 0 1rem; position: relative; }
        .wizard-step { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 12px; border: 2px solid transparent; transition: all 0.3s ease; position: relative; z-index: 2; background: transparent; min-width: 80px; }
        body.dark .wizard-step { border-color: #2a2a2a; }
        body.light .wizard-step { border-color: #e2e8f0; }
        .wizard-step .step-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700; transition: all 0.3s ease; background: <?php echo $theme == 'dark' ? '#1e1e1e' : '#f1f5f9'; ?>; color: #94a3b8; border: 2px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .wizard-step .step-label { font-size: 0.7rem; font-weight: 600; text-align: center; color: #94a3b8; transition: all 0.3s ease; }
        .wizard-step.active { border-color: #3b82f6; background: <?php echo $theme == 'dark' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)'; ?>; }
        .wizard-step.active .step-icon { background: #3b82f6; color: white; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); }
        .wizard-step.active .step-label { color: #3b82f6; }
        .wizard-step.completed { border-color: #22c55e; background: <?php echo $theme == 'dark' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(34, 197, 94, 0.05)'; ?>; }
        .wizard-step.completed .step-icon { background: #22c55e; color: white; border-color: #22c55e; }
        .wizard-step.completed .step-label { color: #22c55e; }
        .step-connector { flex: 1; height: 3px; margin: 0 0.5rem; transition: all 0.3s ease; border-radius: 2px; }
        body.dark .step-connector { background: #2a2a2a; }
        body.light .step-connector { background: #e2e8f0; }
        body.dark .step-connector.active { background: #3b82f6; }
        body.light .step-connector.active { background: #3b82f6; }
        body.dark .step-connector.completed { background: #22c55e; }
        body.light .step-connector.completed { background: #22c55e; }
        
        .wizard-content { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .step-hidden { display: none !important; }
        
        .wizard-buttons { display: flex; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; flex-wrap: wrap; gap: 0.75rem; }
        .wizard-buttons .left { display: flex; gap: 0.75rem; }
        .wizard-buttons .right { display: flex; gap: 0.75rem; }
        
        .review-card { border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
        body.dark .review-card { background: #1e1e1e; border: 1px solid #2a2a2a; }
        body.light .review-card { background: #f8fafc; border: 1px solid #e2e8f0; }
        .review-card .review-title { font-size: 0.9rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
        .review-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .review-item { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .review-item .review-label { font-size: 0.8rem; color: <?php echo $theme == 'dark' ? '#94a3b8' : '#64748b'; ?>; }
        .review-item .review-value { font-size: 0.85rem; font-weight: 500; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .sidebar.closed { width: 60px; }
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .form-grid { grid-template-columns: 1fr; }
            .wizard-progress { flex-wrap: nowrap; overflow-x: auto; gap: 0.5rem; padding: 0.5rem; }
            .wizard-step { min-width: 60px; padding: 0.25rem 0.5rem; }
            .wizard-step .step-label { font-size: 0.6rem; }
            .wizard-step .step-icon { width: 32px; height: 32px; font-size: 0.8rem; }
            .review-grid { grid-template-columns: 1fr; }
            .wizard-buttons { flex-direction: column; align-items: stretch; }
            .wizard-buttons .left, .wizard-buttons .right { justify-content: center; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .main-content.collapsed { margin-left: 0; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<?php include 'sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <?php include 'header.php'; ?>
    
    <div class="action-row">
        <a href="hospitals.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Hospitals
        </a>
    </div>

    <?php if ($error): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-msg">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST" id="wizardForm" enctype="multipart/form-data" novalidate>
            <!-- Wizard Progress -->
            <div class="wizard-container">
                <div class="wizard-progress">
                    <div class="wizard-step active" data-step="1">
                        <div class="step-icon"><i class="fas fa-hospital"></i></div>
                        <span class="step-label">Hospital Details</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="wizard-step" data-step="2">
                        <div class="step-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <span class="step-label">Address</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="wizard-step" data-step="3">
                        <div class="step-icon"><i class="fas fa-user-shield"></i></div>
                        <span class="step-label">Admin Details</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="wizard-step" data-step="4">
                        <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                        <span class="step-label">Review</span>
                    </div>
                </div>

                <!-- Step 1: Hospital Details -->
                <div class="wizard-content" data-step="1">
                    <div class="section-title">
                        <i class="fas fa-hospital mr-2" style="color: #3b82f6;"></i>Hospital Details
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hospital Name <span class="required">*</span></label>
                            <input type="text" name="hospital_name" id="hospital_name" class="form-control" required placeholder="e.g., City Hospital" data-required="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">Hospital Name is required</small>
                        </div>
                        <div class="form-group">
                            <label>Hospital Code <span class="required">*</span></label>
                            <input type="text" name="hospital_code" id="hospital_code" class="form-control" value="<?php echo $hospital_code; ?>" readonly data-required="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">Hospital Code is required</small>
                        </div>
                        <div class="form-group">
                            <label>Hospital Logo</label>
                            <div class="file-upload-box" id="logoUploadBox">
                                <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="upload-text">Click to upload hospital logo</div>
                                <div class="upload-hint">PNG, JPG, GIF, SVG, WEBP (Max 2MB)</div>
                                <input type="file" name="hospital_logo" id="hospital_logo" accept="image/*">
                            </div>
                            <div class="file-preview" id="logoPreview">
                                <img id="logoPreviewImage" src="#" alt="Logo Preview">
                                <span class="file-name" id="logoFileName">No file selected</span>
                                <button type="button" onclick="removeLogo()" style="background: none; border: none; color: #ef4444; cursor: pointer;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Hospital Type</label>
                            <select name="hospital_type" class="form-control">
                                <option value="Multi-Speciality">Multi-Speciality</option>
                                <option value="Super-Speciality">Super-Speciality</option>
                                <option value="General">General</option>
                                <option value="Speciality">Speciality</option>
                                <option value="Clinic">Clinic</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="registration_number" class="form-control" placeholder="e.g., REG/2026/001">
                        </div>
                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" name="gst_number" class="form-control" placeholder="e.g., 27ABCDE1234F1Z5">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Address -->
                <div class="wizard-content step-hidden" data-step="2">
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt mr-2" style="color: #3b82f6;"></i>Hospital Address
                    </div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full address..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" id="city" class="form-control" placeholder="e.g., Mumbai" data-required="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">City is required</small>
                        </div>
                        <div class="form-group">
                            <label>State <span class="required">*</span></label>
                            <input type="text" name="state" id="state" class="form-control" placeholder="e.g., Maharashtra" data-required="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">State is required</small>
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" class="form-control" value="India">
                        </div>
                        <div class="form-group">
                            <label>Pincode</label>
                            <input type="text" name="pincode" id="pincode" class="form-control" placeholder="e.g., 400001">
                        </div>
                        <div class="form-group">
                            <label>Phone <span class="required">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g., 9876543210" data-required="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">Phone number is required</small>
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="text" name="website" class="form-control" placeholder="https://hospital.com">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Admin Details -->
                <div class="wizard-content step-hidden" data-step="3">
                    <div class="section-title">
                        <i class="fas fa-user-shield mr-2" style="color: #3b82f6;"></i>Hospital Admin Details
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Admin Full Name <span class="required">*</span></label>
                            <input type="text" name="admin_name" id="admin_name" class="form-control" required placeholder="Dr. Admin Name" data-required="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">Admin name is required</small>
                        </div>
                        <div class="form-group">
                            <label>Admin Email <span class="required">*</span></label>
                            <input type="email" name="admin_email" id="admin_email" class="form-control" required placeholder="admin@hospital.com" data-required="true" data-email="true">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">Valid admin email is required</small>
                        </div>
                        <div class="form-group">
                            <label>Admin Password <span class="required">*</span></label>
                            <input type="password" name="admin_password" id="admin_password" class="form-control" required placeholder="Min 8 characters" data-required="true" minlength="8">
                            <small style="color: #ef4444; font-size: 0.7rem; display: none;" class="error-text">Password must be at least 8 characters</small>
                        </div>
                        <div class="form-group">
                            <label>Admin Mobile</label>
                            <input type="text" name="admin_mobile" class="form-control" placeholder="e.g., 9876543210">
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div class="wizard-content step-hidden" data-step="4">
                    <div class="section-title">
                        <i class="fas fa-check-circle mr-2" style="color: #22c55e;"></i>Review & Confirm
                    </div>
                    <p style="color: #94a3b8; margin-bottom: 1.5rem;">Please review all details before creating the hospital. You can go back to edit any section.</p>
                    <div id="reviewContainer"></div>
                </div>

                <!-- Wizard Buttons -->
                <div class="wizard-buttons">
                    <div class="left">
                        <button type="button" class="btn-secondary" id="prevBtn" style="display: none;">
                            <i class="fas fa-arrow-left mr-1"></i>Previous
                        </button>
                    </div>
                    <div class="right">
                        <a href="hospitals.php" class="btn-secondary">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </a>
                        <button type="button" class="btn-primary" id="nextBtn">
                            Next <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                        <button type="submit" class="btn-success" id="submitBtn" style="display: none;">
                            <i class="fas fa-save mr-2"></i>Create Hospital
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        sidebar.classList.toggle('closed');
        mainContent.classList.toggle('collapsed');
    }
    
    // Make toggle function globally available
    window.toggleSidebar = toggleSidebar;

    // Logo Upload
    const logoInput = document.getElementById('hospital_logo');
    const logoUploadBox = document.getElementById('logoUploadBox');
    const logoPreview = document.getElementById('logoPreview');
    const logoPreviewImage = document.getElementById('logoPreviewImage');
    const logoFileName = document.getElementById('logoFileName');

    logoUploadBox.addEventListener('click', function() { logoInput.click(); });

    logoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            const maxSize = 2 * 1024 * 1024;

            if (!validTypes.includes(file.type)) {
                alert('Please upload a valid image file (PNG, JPG, GIF, WEBP, SVG)');
                this.value = '';
                return;
            }
            if (file.size > maxSize) {
                alert('File size must be less than 2MB');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreviewImage.src = e.target.result;
                logoPreview.style.display = 'flex';
                logoFileName.textContent = file.name;
                logoUploadBox.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    window.removeLogo = function() {
        logoInput.value = '';
        logoPreview.style.display = 'none';
        logoUploadBox.style.display = 'block';
        logoPreviewImage.src = '#';
        logoFileName.textContent = 'No file selected';
    };

    // Wizard
    const TOTAL_STEPS = 4;
    let currentStep = 1;
    const form = document.getElementById('wizardForm');
    const steps = document.querySelectorAll('.wizard-step');
    const connectors = document.querySelectorAll('.step-connector');
    const contents = document.querySelectorAll('.wizard-content');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const reviewContainer = document.getElementById('reviewContainer');

    function validateStep(step) {
        const content = document.querySelector(`.wizard-content[data-step="${step}"]`);
        const inputs = content.querySelectorAll('[data-required="true"]');
        let isValid = true;

        inputs.forEach(input => {
            const errorText = input.parentElement.querySelector('.error-text');
            const value = input.value.trim();

            if (!value) {
                input.classList.add('error');
                if (errorText) errorText.style.display = 'block';
                isValid = false;
            } else {
                input.classList.remove('error');
                if (errorText) errorText.style.display = 'none';
            }

            if (input.hasAttribute('data-email') && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    input.classList.add('error');
                    if (errorText) errorText.style.display = 'block';
                    isValid = false;
                }
            }

            if (input.type === 'password' && value && input.hasAttribute('minlength')) {
                if (value.length < parseInt(input.getAttribute('minlength'))) {
                    input.classList.add('error');
                    if (errorText) errorText.style.display = 'block';
                    isValid = false;
                }
            }
        });

        if (step === 2) {
            const phoneInput = document.getElementById('phone');
            const phoneError = phoneInput.parentElement.querySelector('.error-text');
            if (phoneInput.value.trim() && !/^[0-9]{10}$/.test(phoneInput.value.trim())) {
                phoneInput.classList.add('error');
                if (phoneError) {
                    phoneError.textContent = 'Enter a valid 10-digit phone number';
                    phoneError.style.display = 'block';
                }
                isValid = false;
            }
        }

        return isValid;
    }

    function updateWizard(step) {
        steps.forEach((el, index) => {
            const num = index + 1;
            el.classList.remove('active', 'completed');
            if (num < step) el.classList.add('completed');
            if (num === step) el.classList.add('active');
        });

        connectors.forEach((el, index) => {
            const num = index + 1;
            el.classList.remove('active', 'completed');
            if (num < step) el.classList.add('completed');
            if (num === step) el.classList.add('active');
        });

        contents.forEach(el => {
            el.classList.add('step-hidden');
            if (parseInt(el.dataset.step) === step) {
                el.classList.remove('step-hidden');
            }
        });

        prevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
        nextBtn.style.display = step === TOTAL_STEPS ? 'none' : 'inline-flex';
        submitBtn.style.display = step === TOTAL_STEPS ? 'inline-flex' : 'none';

        if (step === TOTAL_STEPS) {
            generateReview();
        }

        document.querySelector('.content-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function generateReview() {
        const getVal = (id) => document.getElementById(id)?.value || 'N/A';
        const getText = (id) => document.querySelector(`[name="${id}"]`)?.value || 'N/A';
        const getSelectText = (id) => {
            const select = document.querySelector(`[name="${id}"]`);
            return select ? select.options[select.selectedIndex]?.text || 'N/A' : 'N/A';
        };
        const getLogo = () => {
            const file = document.getElementById('hospital_logo').files[0];
            return file ? file.name : 'No logo uploaded';
        };

        const reviewData = {
            hospital: [
                { label: 'Hospital Name', value: getVal('hospital_name') },
                { label: 'Hospital Code', value: getVal('hospital_code') },
                { label: 'Hospital Logo', value: getLogo() },
                { label: 'Hospital Type', value: getSelectText('hospital_type') },
                { label: 'Registration Number', value: getText('registration_number') },
                { label: 'GST Number', value: getText('gst_number') },
                { label: 'Status', value: getSelectText('status') }
            ],
            address: [
                { label: 'Address', value: getText('address') || 'N/A' },
                { label: 'City', value: getVal('city') },
                { label: 'State', value: getVal('state') },
                { label: 'Country', value: getText('country') || 'India' },
                { label: 'Pincode', value: getText('pincode') || 'N/A' },
                { label: 'Phone', value: getVal('phone') },
                { label: 'Website', value: getText('website') || 'N/A' }
            ],
            admin: [
                { label: 'Admin Full Name', value: getVal('admin_name') },
                { label: 'Admin Email', value: getVal('admin_email') },
                { label: 'Admin Password', value: '••••••••' },
                { label: 'Admin Mobile', value: getText('admin_mobile') || 'N/A' }
            ]
        };

        let html = '';
        html += `<div class="review-card"><div class="review-title" style="color:#3b82f6;"><i class="fas fa-hospital"></i> Hospital Information</div><div class="review-grid">`;
        reviewData.hospital.forEach(item => {
            html += `<div class="review-item"><span class="review-label">${item.label}</span><span class="review-value">${item.value}</span></div>`;
        });
        html += `</div></div>`;

        html += `<div class="review-card"><div class="review-title" style="color:#8b5cf6;"><i class="fas fa-map-marker-alt"></i> Address</div><div class="review-grid">`;
        reviewData.address.forEach(item => {
            html += `<div class="review-item"><span class="review-label">${item.label}</span><span class="review-value">${item.value}</span></div>`;
        });
        html += `</div></div>`;

        html += `<div class="review-card"><div class="review-title" style="color:#22c55e;"><i class="fas fa-user-shield"></i> Hospital Admin</div><div class="review-grid">`;
        reviewData.admin.forEach(item => {
            html += `<div class="review-item"><span class="review-label">${item.label}</span><span class="review-value">${item.value}</span></div>`;
        });
        html += `</div></div>`;

        reviewContainer.innerHTML = html;
    }

    function goToStep(step) {
        if (step < 1 || step > TOTAL_STEPS) return;
        if (step > currentStep) {
            if (!validateStep(currentStep)) {
                const content = document.querySelector(`.wizard-content[data-step="${currentStep}"]`);
                const firstError = content.querySelector('.form-control.error');
                if (firstError) { firstError.focus(); firstError.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                return;
            }
        }
        currentStep = step;
        updateWizard(currentStep);
    }

    nextBtn.addEventListener('click', function() {
        if (validateStep(currentStep)) { goToStep(currentStep + 1); }
        else {
            const content = document.querySelector(`.wizard-content[data-step="${currentStep}"]`);
            const firstError = content.querySelector('.form-control.error');
            if (firstError) { firstError.focus(); firstError.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }
    });

    prevBtn.addEventListener('click', function() { goToStep(currentStep - 1); });

    steps.forEach((step, index) => {
        step.addEventListener('click', function() {
            const targetStep = index + 1;
            if (targetStep <= currentStep || targetStep === currentStep + 1) { goToStep(targetStep); }
        });
    });

    form.addEventListener('submit', function(e) {
        for (let i = 1; i <= TOTAL_STEPS - 1; i++) {
            if (!validateStep(i)) {
                e.preventDefault();
                currentStep = i;
                updateWizard(currentStep);
                const content = document.querySelector(`.wizard-content[data-step="${i}"]`);
                const firstError = content.querySelector('.form-control.error');
                if (firstError) { firstError.focus(); firstError.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                return;
            }
        }
    });

    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('input', function() {
            const errorText = this.parentElement.querySelector('.error-text');
            if (this.classList.contains('error')) {
                this.classList.remove('error');
                if (errorText) errorText.style.display = 'none';
            }
        });
    });

    updateWizard(1);
});
</script>
</body>
</html>