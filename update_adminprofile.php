<?php
// ============================================================
// UPDATE ADMIN PROFILE
// ============================================================

session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

// ============================================================
// CHECK IF USER HAS ANY PERMISSION
// ============================================================
$has_any_permission = !empty($_SESSION['permissions']) && is_array($_SESSION['permissions']);

// ============================================================
// GET USER DATA
// ============================================================
$admin_id = $_SESSION['id'];
$admin_data = [];
$errors = [];
$form_data = [];

$sql = "SELECT
            r.id,
            r.name,
            r.email,
            r.password,
            r.role,
            ap.admin_id,
            ap.register_id,
            ap.full_name,
            ap.mobile,
            ap.profile_image,
            ap.created_at,
            ap.updated_at
        FROM register r
        LEFT JOIN admin_profile ap
        ON r.id = ap.register_id
        WHERE r.id='$admin_id'
        AND (r.delete_flag = 0 OR r.delete_flag IS NULL)
        AND r.role IN ('Super Admin', 'Admin')";
$result = $conn->query($sql);

if($result->num_rows > 0){
    $admin_data = $result->fetch_assoc();

    if(empty($admin_data['register_id'])){
        $conn->query("INSERT INTO admin_profile(register_id, full_name)
                      VALUES('$admin_id', '".$admin_data['name']."')");

        $result = $conn->query($sql);
        $admin_data = $result->fetch_assoc();
    }
} else {
    echo "<script>
            alert('Admin not found');
            window.location='dashboard.php';
          </script>";
    exit();
}

// ============================================================
// UPDATE PROFILE
// ============================================================
if(isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $mobile    = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));

    $form_data = [
        'full_name' => $full_name,
        'mobile' => $mobile,
        'email' => $email
    ];

    // Validation for full name (optional)
    if(!empty($full_name)) {
        if(strlen($full_name) < 3) {
            $errors['full_name'] = "Full name must be at least 3 characters.";
        } elseif(!preg_match("/^[a-zA-Z\s\.\-']+$/", $full_name)) {
            $errors['full_name'] = "Full name can only contain letters, spaces, dots, and hyphens.";
        }
    }

    // Validation for mobile (optional)
    if(!empty($mobile)) {
        if(!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            $errors['mobile'] = "Please enter a valid 10-digit mobile number starting with 6,7,8, or 9.";
        }
    }

    // Validation for email (optional) - Check only in register table
    if(!empty($email)) {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address.";
        } else {
            // Check if email already exists for another user in register table only
            $check_sql = "SELECT id FROM register 
                          WHERE email = '$email'
                          AND id != '$admin_id'
                          AND (delete_flag = 0 OR delete_flag IS NULL)";
            $check_result = $conn->query($check_sql);
            if($check_result->num_rows > 0) {
                $errors['email'] = "This email is already used by another user.";
            }
        }
    }

    // File validation for profile image
    $profile_image = $admin_data['profile_image'] ?? '';
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 3 * 1024 * 1024; // 3MB
        
        if(!in_array($file['type'], $allowed_types)) {
            $errors['profile_image'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif($file['size'] > $max_size) {
            $errors['profile_image'] = "Image size must be less than 3MB.";
        } elseif($file['error'] !== UPLOAD_ERR_OK) {
            $errors['profile_image'] = "Failed to upload image. Error code: " . $file['error'];
        }

        if(empty($errors['profile_image'])) {
            $folder = "documents/admin/images/";
            if(!is_dir($folder)){
                mkdir($folder,0777,true);
            }
            $image_name = time() . '_' . basename($_FILES['profile_image']['name']);
            $image_path = $folder . $image_name;

            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path)){
                // Delete old image if exists
                if(!empty($admin_data['profile_image']) && file_exists($admin_data['profile_image'])){
                    unlink($admin_data['profile_image']);
                }
                $profile_image = $image_path;
            } else {
                $errors['profile_image'] = "Failed to move uploaded file.";
            }
        }
    }

    // If no errors, proceed with update
    if(empty($errors)) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn->begin_transaction();

        try{
            // Update admin_profile table
            $update_admin_profile = "UPDATE admin_profile SET";
            $updates = [];
            if(!empty($full_name)) $updates[] = "full_name='$full_name'";
            if(!empty($mobile)) $updates[] = "mobile='$mobile'";
            $updates[] = "profile_image='$profile_image'";
            $updates[] = "updated_at=CURRENT_TIMESTAMP()";
            
            $update_admin_profile .= " " . implode(", ", $updates);
            $update_admin_profile .= " WHERE register_id='$admin_id'";
            
            $conn->query($update_admin_profile);

            // Update register table
            $update_register = "UPDATE register SET";
            $register_updates = [];
            if(!empty($full_name)) {
                $register_updates[] = "name='$full_name'";
                $_SESSION['name'] = $full_name;
            }
            if(!empty($email)) {
                $register_updates[] = "email='$email'";
                $_SESSION['email'] = $email;
            }
            $register_updates[] = "modified_by='$admin_id'";
            $register_updates[] = "reg_date=CURRENT_TIMESTAMP()";
            
            $update_register .= " " . implode(", ", $register_updates);
            $update_register .= " WHERE id='$admin_id'";
            
            $conn->query($update_register);

            $_SESSION['profile_image'] = $profile_image;

            $conn->commit();

            // Refresh admin data
            $result = $conn->query($sql);
            $admin_data = $result->fetch_assoc();

            echo "<script>
                    alert('Profile Updated Successfully');
                    window.location='update_adminprofile.php';
                  </script>";

        } catch(Exception $e){
            $conn->rollback();
            $errors['general'] = "Error updating profile: " . $e->getMessage();
        }
    }
}

// ============================================================
// UPDATE PASSWORD
// ============================================================
if(isset($_POST['update_password'])) {
    $current_password = mysqli_real_escape_string($conn, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    $password_errors = [];
    if(empty($current_password)) $password_errors['current_password'] = "Current password is required.";
    if(empty($new_password)) $password_errors['new_password'] = "New password is required.";
    if(empty($confirm_password)) $password_errors['confirm_password'] = "Confirm password is required.";
    if($new_password !== $confirm_password) $password_errors['confirm_password'] = "New password and confirm password do not match.";
    if(!empty($new_password) && strlen($new_password) < 6) {
        $password_errors['new_password'] = "New password must be at least 6 characters.";
    }

    if(empty($password_errors)) {
        $stored_password = $admin_data['password'];
        if($current_password === $stored_password) {
            $conn->query("
                UPDATE register
                SET
                    password='$new_password',
                    modified_by='$admin_id',
                    reg_date=CURRENT_TIMESTAMP()
                WHERE id='$admin_id'
            ");
            echo "<script>
                    alert('Password Updated Successfully');
                    window.location='update_adminprofile.php';
                  </script>";
        } else {
            $password_errors['current_password'] = "Current password is incorrect.";
        }
    }
}

// ============================================================
// GET HOSPITAL INFO
// ============================================================
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$user_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital_name; ?> - Update Profile</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital_logo; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }

        

        /* ============================================================
           PROFILE FORM STYLES
           ============================================================ */
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            padding-top: 1.5rem;
        }

        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
        }

        .form-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-card .card-title i {
            color: #3b82f6;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }
        .form-group label .required {
            color: #ef4444;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            background: #f8fafc;
            color: #1e293b;
            transition: all 0.2s ease;
            outline: none;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            background: #ffffff;
        }
        .form-control:disabled,
        .form-control[readonly] {
            background: #f1f5f9;
            cursor: not-allowed;
            opacity: 0.8;
        }
        .form-control.input-error {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }

        .error-text {
            color: #dc2626;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
            display: block;
        }

        .profile-image-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e2e8f0;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
            flex-shrink: 0;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-image-section .file-input {
            flex: 1;
            min-width: 200px;
        }
        .profile-image-section .file-input input[type="file"] {
            width: 100%;
            padding: 0.5rem;
            font-size: 0.85rem;
            border: 1px dashed #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            color: #475569;
        }
        .profile-image-section .file-input .file-error {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }

        .btn {
            padding: 0.65rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34,197,94,0.3);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .password-field {
            position: relative;
        }
        .password-field input {
            padding-right: 40px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            z-index: 2;
        }
        .password-toggle:hover {
            color: #475569;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        /* ============================================================
           ALERT / WARNING
           ============================================================ */
        .alert-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-warning i {
            color: #f59e0b;
            font-size: 1.2rem;
        }
        .alert-warning span {
            color: #92400e;
            font-size: 0.9rem;
        }

        .error-summary {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
        }
        .error-summary p {
            color: #991b1b;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .error-summary ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #991b1b;
            font-size: 0.85rem;
        }
        .error-summary ul li {
            margin-bottom: 0.15rem;
        }

        .no-permission-box {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .no-permission-box i {
            font-size: 2.5rem;
            color: #94a3b8;
            display: block;
            margin-bottom: 0.8rem;
        }
        .no-permission-box p {
            color: #64748b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>


    <?php include 'Sidebar.php'; ?>


<!-- ============================================================
MAIN WRAPPER
============================================================ -->
<div class="main-wrapper">
    <main class="main-content" id="mainContent">
        
       <?php include 'header.php'; ?>

        <!-- ============================================================
        PROFILE CONTENT - CENTERED
        ============================================================ -->
        <div class="profile-container">
            
            <!-- Back Button -->
            <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <span style="font-size:0.9rem; color:#94a3b8;">Back to Dashboard</span>
            </div>

            <!-- Display Profile Errors -->
            <?php if(!empty($errors)): ?>
                <div class="error-summary">
                    <p><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</p>
                    <ul>
                        <?php foreach($errors as $field => $message): ?>
                            <li><?php echo $message; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- No Permission Warning - Will be hidden when permissions exist -->
            <div id="permissionWarning" class="alert-warning" style="<?php echo $has_any_permission ? 'display:none;' : 'display:flex;'; ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <span>You don't have any permissions assigned. Please contact your administrator.</span>
            </div>

            <!-- ============================================================
            PROFILE FORM
            ============================================================ -->
            <div class="form-card">
                <h2 class="card-title">
                    <i class="fas fa-user-circle"></i>
                    Profile Information
                </h2>
                
                <form action="update_adminprofile.php" method="POST" enctype="multipart/form-data">
                    <!-- Profile Image -->
                    <div class="profile-image-section">
                        <?php if(!empty($admin_data['profile_image']) && file_exists($admin_data['profile_image'])): ?>
                            <div class="profile-avatar">
                                <img src="<?php echo $admin_data['profile_image']; ?>" alt="Profile Image">
                            </div>
                        <?php else: ?>
                            <div class="profile-avatar">
                                <?php
                                $name = !empty($admin_data['full_name']) ? $admin_data['full_name'] : $admin_data['name'];
                                echo strtoupper(substr($name, 0, 1));
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="file-input">
                            <label style="font-size:0.85rem; font-weight:600; color:#475569; display:block; margin-bottom:0.3rem;">Profile Image</label>
                            <input type="file" name="profile_image" accept="image/*" class="<?php echo isset($errors['profile_image']) ? 'file-error' : ''; ?>">
                            <?php if(isset($errors['profile_image'])): ?>
                                <span class="error-text"><?php echo $errors['profile_image']; ?></span>
                            <?php endif; ?>
                            <p style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">Leave empty to keep current image. Supported: JPG, PNG, GIF, WEBP (Max 3MB)</p>
                        </div>
                    </div>

                    <!-- Full Name & Mobile -->
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control <?php echo isset($errors['full_name']) ? 'input-error' : ''; ?>"
                                   value="<?php echo htmlspecialchars(!empty($form_data['full_name']) ? $form_data['full_name'] : (!empty($admin_data['full_name']) ? $admin_data['full_name'] : $admin_data['name'])); ?>"
                                   placeholder="Enter full name">
                            <?php if(isset($errors['full_name'])): ?>
                                <span class="error-text"><?php echo $errors['full_name']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control <?php echo isset($errors['mobile']) ? 'input-error' : ''; ?>"
                                   value="<?php echo htmlspecialchars(!empty($form_data['mobile']) ? $form_data['mobile'] : ($admin_data['mobile'] ?? '')); ?>"
                                   placeholder="Enter mobile number">
                            <?php if(isset($errors['mobile'])): ?>
                                <span class="error-text"><?php echo $errors['mobile']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'input-error' : ''; ?>"
                               value="<?php echo htmlspecialchars(!empty($form_data['email']) ? $form_data['email'] : ($admin_data['email'] ?? '')); ?>"
                               placeholder="Enter email address">
                        <?php if(isset($errors['email'])): ?>
                            <span class="error-text"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Role (Read Only) -->
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($admin_data['role'] ?? ''); ?>" 
                               readonly disabled>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- ============================================================
            PASSWORD FORM
            ============================================================ -->
            <div class="form-card">
                <h2 class="card-title">
                    <i class="fas fa-key" style="color:#22c55e;"></i>
                    Change Password
                </h2>

                <?php if(!empty($password_errors)): ?>
                    <div class="error-summary" style="margin-bottom:1.5rem;">
                        <p><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</p>
                        <ul>
                            <?php foreach($password_errors as $field => $message): ?>
                                <li><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="update_adminprofile.php" method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Current Password <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" name="current_password" class="form-control <?php echo isset($password_errors['current_password']) ? 'input-error' : ''; ?>" placeholder="Enter current password">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                                <?php if(isset($password_errors['current_password'])): ?>
                                    <span class="error-text"><?php echo $password_errors['current_password']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div></div>
                        <div class="form-group">
                            <label>New Password <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" name="new_password" class="form-control <?php echo isset($password_errors['new_password']) ? 'input-error' : ''; ?>" placeholder="Enter new password">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                                <?php if(isset($password_errors['new_password'])): ?>
                                    <span class="error-text"><?php echo $password_errors['new_password']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" name="confirm_password" class="form-control <?php echo isset($password_errors['confirm_password']) ? 'input-error' : ''; ?>" placeholder="Confirm new password">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                                <?php if(isset($password_errors['confirm_password'])): ?>
                                    <span class="error-text"><?php echo $password_errors['confirm_password']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_password" class="btn btn-success">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- ============================================================
            NO PERMISSION BOX - Will be hidden when permissions exist
            ============================================================ -->
            <div id="noPermissionBox" class="no-permission-box" style="<?php echo $has_any_permission ? 'display:none;' : 'display:block;'; ?>">
                <i class="fas fa-shield-alt"></i>
                <p>You don't have any permissions assigned. Please contact your administrator to get access.</p>
            </div>

        </div>

    </main>
</div>

<!-- ============================================================
SCRIPTS
============================================================ -->
<script>
/

// ============================================================
// PASSWORD TOGGLE
// ============================================================
function togglePassword(element) {
    const field = element.parentElement.querySelector('input');
    const icon = element.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ============================================================
// PERMISSION WARNING - AUTO HIDE/SHOW (WITH AJAX)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const permissionWarning = document.getElementById('permissionWarning');
    const noPermissionBox = document.getElementById('noPermissionBox');
    
    // Function to check permissions via AJAX
    function checkPermissions() {
        // Create AJAX request to check permissions
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'check_permissions.php', true);
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    // Update visibility based on AJAX response
                    if (response.has_permissions) {
                        // Hide warning messages
                        if (permissionWarning) {
                            permissionWarning.style.display = 'none';
                        }
                        if (noPermissionBox) {
                            noPermissionBox.style.display = 'none';
                        }
                    } else {
                        // Show warning messages
                        if (permissionWarning) {
                            permissionWarning.style.display = 'flex';
                        }
                        if (noPermissionBox) {
                            noPermissionBox.style.display = 'block';
                        }
                    }
                } catch(e) {
                    console.error('Error parsing JSON:', e);
                }

            }
        };
        xhr.onerror = function() {
            console.error('Error checking permissions');
        };
        xhr.send();
    }
    
    // Check permissions immediately
    checkPermissions();
    
    // Check permissions every 3 seconds (for real-time updates)
    setInterval(checkPermissions, 3000);
});
</script>

</body>
</html>