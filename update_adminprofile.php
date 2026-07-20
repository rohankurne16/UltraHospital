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

$sql = "SELECT
            r.id,
            r.name,
            r.email,
            r.password,
            ap.admin_id,
            ap.register_id,
            ap.full_name,
            ap.mobile,
            ap.profile_image
        FROM register r
        LEFT JOIN admin_profile ap
        ON r.id = ap.register_id
        WHERE r.id='$admin_id'
        AND (r.delete_flag=0 OR r.delete_flag IS NULL)";

$result = $conn->query($sql);

if($result->num_rows > 0){
    $admin_data = $result->fetch_assoc();

    if(empty($admin_data['register_id'])){
        $conn->query("INSERT INTO admin_profile(register_id,full_name)
                      VALUES('$admin_id','".$admin_data['name']."')");

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
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $mobile    = mysqli_real_escape_string($conn, $_POST['mobile']);

    $profile_image = $admin_data['profile_image'] ?? '';

    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){
        $folder = "documents/admin/images/";
        if(!is_dir($folder)){
            mkdir($folder,0777,true);
        }
        $image_name = basename($_FILES['profile_image']['name']);
        $image_path = $folder.$image_name;

        if(move_uploaded_file($_FILES['profile_image']['tmp_name'],$image_path)){
            if(!empty($admin_data['profile_image']) && file_exists($admin_data['profile_image'])){
                unlink($admin_data['profile_image']);
            }
            $profile_image = $image_path;
        } else {
            die("Image Upload Failed");
        }
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();

    try{
        $conn->query("
            UPDATE admin_profile
            SET
                full_name='$full_name',
                mobile='$mobile',
                profile_image='$profile_image',
                updated_at=CURRENT_TIMESTAMP()
            WHERE register_id='$admin_id'
        ");

        $conn->query("
            UPDATE register
            SET
                name='$full_name'
            WHERE id='$admin_id'
        ");

        $_SESSION['name'] = $full_name;
        $_SESSION['profile_image'] = $profile_image;

        $conn->commit();

        echo "<script>
                alert('Profile Updated Successfully');
                window.location='update_adminprofile.php';
              </script>";

    } catch(Exception $e){
        $conn->rollback();
        die($e->getMessage());
    }
}

// ============================================================
// UPDATE PASSWORD
// ============================================================
if(isset($_POST['update_password'])) {
    $current_password = mysqli_real_escape_string($conn, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    $errors = [];
    if(empty($current_password)) $errors[] = "Current password is required";
    if(empty($new_password)) $errors[] = "New password is required";
    if(empty($confirm_password)) $errors[] = "Confirm password is required";
    if($new_password !== $confirm_password) $errors[] = "New password and confirm password do not match";

    if(empty($errors)) {
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
            echo "<script>
                    alert('Current password is incorrect');
                    window.location='update_adminprofile.php';
                  </script>";
        }
    } else {
        $error_message = implode("\\n", $errors);
        echo "<script>
                alert('$error_message');
                window.location='update_adminprofile.php';
              </script>";
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
           SIDEBAR STYLES
           ============================================================ */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 256px;
            z-index: 1000;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        #sidebar-container::-webkit-scrollbar { width: 4px; }
        #sidebar-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            }
            #sidebar-container.active { transform: translateX(0); }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            .sidebar-overlay.active { display: block; }
            #main-content { margin-left: 0 !important; }
        }
        @media (min-width: 1280px) {
            #sidebar-container { transform: translateX(0); width: 256px; }
        }

        /* ============================================================
           HEADER STYLES
           ============================================================ */
        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            min-height: 64px;
        }
        .top-header .header-left { display: flex; align-items: center; gap: 1rem; }
        .top-header .header-right { display: flex; align-items: center; gap: 1rem; }
        .top-header .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .mobile-toggle {
            display: none;
            padding: 0.5rem 0.75rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.25rem;
        }
        .mobile-toggle:hover { background: #f8fafc; }
        @media (max-width: 1279px) {
            .mobile-toggle { display: inline-flex; align-items: center; justify-content: center; }
        }

        /* ============================================================
           MAIN CONTENT
           ============================================================ */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 256px;
            padding: 1.5rem;
            min-height: calc(100vh - 64px);
            transition: margin-left 0.3s ease;
            background: #f1f5f9;
        }
        @media (max-width: 1279px) {
            .main-content {
                margin-left: 0 !important;
                padding: 1rem;
            }
        }

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

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 640px) {
            .grid-2 { grid-template-columns: 1fr; }
            .form-card { padding: 1.25rem; }
            .profile-image-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .profile-image-section .file-input { width: 100%; }
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .form-actions .btn { justify-content: center; }
            .profile-container { padding: 0 0.5rem; }
            .top-header {
                padding: 0.5rem 1rem;
            }
            .top-header .header-left h1 {
                font-size: 1rem;
            }
            .top-header .header-left p {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>

<!-- ============================================================
SIDEBAR OVERLAY (MOBILE)
============================================================ -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============================================================
SIDEBAR
============================================================ -->
<div id="sidebar-container">
    <?php include 'Sidebar.php'; ?>
</div>

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

            <!-- No Permission Warning -->
            <?php if (!$has_any_permission): ?>
            <div class="alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>You don't have any permissions assigned. Please contact your administrator.</span>
            </div>
            <?php endif; ?>

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
                            <img src="<?php echo $admin_data['profile_image']; ?>" class="profile-avatar">
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
                            <input type="file" name="profile_image" accept="image/*">
                            <p style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">Leave empty to keep current image</p>
                        </div>
                    </div>

                    <!-- Full Name & Mobile -->
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?php echo htmlspecialchars(!empty($admin_data['full_name']) ? $admin_data['full_name'] : $admin_data['name']); ?>"
                                   placeholder="Enter full name">
                        </div>
                        <div class="form-group">
                            <label>Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control"
                                   value="<?php echo htmlspecialchars($admin_data['mobile'] ?? ''); ?>"
                                   placeholder="Enter mobile number">
                        </div>
                    </div>

                    <!-- Email (Read Only) -->
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" 
                               value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" 
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
                
                <form action="update_adminprofile.php" method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Current Password <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div></div>
                        <div class="form-group">
                            <label>New Password <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
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
            NO PERMISSION BOX
            ============================================================ -->
            <?php if (!$has_any_permission): ?>
            <div class="no-permission-box">
                <i class="fas fa-shield-alt"></i>
                <p>You don't have any permissions assigned. Please contact your administrator to get access.</p>
            </div>
            <?php endif; ?>

        </div>

    </main>
</div>

<!-- ============================================================
SCRIPTS
============================================================ -->
<script>
// ============================================================
// SIDEBAR TOGGLE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebarContainer = document.getElementById('sidebar-container');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebarContainer.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebarContainer.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
});

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
</script>

</body>
</html>