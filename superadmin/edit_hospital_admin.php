<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/permission.php';
checkSuperAdminLogin();

$page_title = 'Edit Hospital Admin';
$page_subtitle = 'Update hospital administrator details';

$theme = $_SESSION['theme'] ?? 'dark';

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

if ($admin_id == 0) {
    header('Location: hospital_admins.php');
    exit;
}

// Fetch admin details - FIXED: Added ha.register_id
$query = "SELECT ha.*, ha.register_id, h.hospital_name, h.hospital_code, r.email, r.role, r.reg_date, r.password
          FROM hospital_admin ha 
          LEFT JOIN hospital_master h ON ha.hospital_id = h.hospital_id 
          LEFT JOIN register r ON ha.register_id = r.id 
          WHERE ha.admin_id = '$admin_id' AND ha.delete_flag = 0";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    header('Location: hospital_admins.php');
    exit;
}

$admin = mysqli_fetch_assoc($result);

// Debug - check if register_id exists
if (empty($admin['register_id'])) {
    die("Error: register_id not found for this admin. Admin ID: " . $admin_id);
}

// Fetch hospitals for dropdown
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active' ORDER BY hospital_name";
$hospitals_result = mysqli_query($conn, $hospitals_query);

// Update admin
$error = '';
$success = '';
if (isset($_POST['update_admin'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $hospital_id = mysqli_real_escape_string($conn, $_POST['hospital_id']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = "Name and Email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        
        $check_email = "SELECT id FROM register WHERE email = '$email' AND id != '" . $admin['register_id'] . "' AND delete_flag = 0";
        $check_result = mysqli_query($conn, $check_email);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already exists for another user!";
        } else {
            
            mysqli_begin_transaction($conn);
            
            try {
               
                $update_admin = "UPDATE hospital_admin SET 
                                 full_name = '$full_name',
                                 mobile = '$mobile',
                                 hospital_id = '$hospital_id',
                                 modified_at = NOW()
                                 WHERE admin_id = '$admin_id'";
                
                if (!mysqli_query($conn, $update_admin)) {
                    throw new Exception("Error updating admin: " . mysqli_error($conn));
                }
                
              
                $update_register = "UPDATE register SET 
                                    name = '$full_name',
                                    email = '$email',
                                    hospital_id = '$hospital_id',
                                    modified_by = 'Super Admin',
                                    modified_at = NOW()";
                
               
                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        throw new Exception("Password must be at least 8 characters long.");
                    }
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_register .= ", password = '$hashed_password'";
                }
                
                $update_register .= " WHERE id = '" . $admin['register_id'] . "'";
                
                if (!mysqli_query($conn, $update_register)) {
                    throw new Exception("Error updating user: " . mysqli_error($conn));
                }
                
                mysqli_commit($conn);
                
           
                logAudit('Hospital Admin', "Updated hospital admin: " . $full_name . " (ID: $admin_id)");
                
                $success = "Admin updated successfully!";
          
                $result = mysqli_query($conn, $query);
                $admin = mysqli_fetch_assoc($result);
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hospital Admin - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.dark .content-card { background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a; }
        body.dark .form-control { background: #1e1e1e; border: 1px solid #2a2a2a; color: #f1f5f9; }
        body.dark .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        body.dark .text-primary { color: #f1f5f9; }
        body.dark .text-secondary { color: #9ca3af; }
        body.dark label { color: #94a3b8; }
        body.dark .section-title { color: #f1f5f9; }
        body.dark .form-control.error { border-color: #ef4444; }
        body.dark .profile-avatar { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        
        body.light { background: #f1f5f9; }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        body.light .form-control { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.light .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        body.light .text-primary { color: #1e293b; }
        body.light .text-secondary { color: #64748b; }
        body.light label { color: #475569; }
        body.light .section-title { color: #1e293b; }
        body.light .form-control.error { border-color: #ef4444; }
        body.light .profile-avatar { background: rgba(168, 85, 247, 0.1); color: #a855f7; }
        
      
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
        
        .content-card { border-radius: 16px; padding: 1.5rem; transition: all 0.3s ease; }
        .form-control { padding: 0.7rem 1rem; border-radius: 10px; transition: all 0.3s ease; width: 100%; outline: none; font-size: 0.9rem; }
        .form-control.error { border-color: #ef4444 !important; }
        label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem; }
        .form-group { margin-bottom: 1.25rem; }
        .required { color: #ef4444; }
        
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 0.7rem 2rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        .btn-secondary { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding: 0.7rem 2rem; border-radius: 10px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary:hover { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 0.7rem 2rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(34, 197, 94, 0.5); }
        
        .btn-back {
            padding: 0.6rem 1.5rem;
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
        .btn-back:hover {
            background: <?php echo $theme == 'dark' ? '#3a3a3a' : '#e2e8f0'; ?>;
            transform: translateY(-2px);
        }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .error-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        .success-msg { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #16a34a; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
        
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .form-grid { grid-template-columns: 1fr; }
            .action-row { flex-direction: column; align-items: stretch; }
            .action-row a { justify-content: center; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .main-content.collapsed { margin-left: 0; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- ============================================
     SIDEBAR
     ============================================ -->
<?php include 'sidebar.php'; ?>

<!-- ============================================
     MAIN CONTENT
     ============================================ -->
<div class="main-content" id="mainContent">
    <?php include 'header.php'; ?>

    <!-- Action Row -->
    <div class="action-row">
        <a href="hospital_admins.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Admins
        </a>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a href="view_hospital_admin.php?id=<?php echo $admin['admin_id']; ?>" class="btn-secondary">
                <i class="fas fa-eye"></i> View Profile
            </a>
        </div>
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

    <!-- Edit Admin Form -->
    <div class="content-card">
        <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($admin['full_name'], 0, 2)); ?>
            </div>
            <div>
                <h3 style="font-size:1.3rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin:0;">
                    Edit Hospital Admin
                </h3>
                <p style="color:#94a3b8;font-size:0.85rem;margin:0;">
                    Update details for <?php echo htmlspecialchars($admin['full_name']); ?>
                </p>
            </div>
        </div>

        <form method="POST" action="" id="editAdminForm">
            <div class="form-grid">
                <!-- Full Name -->
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                </div>

                <!-- Mobile -->
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" name="mobile" id="mobile" class="form-control" value="<?php echo htmlspecialchars($admin['mobile'] ?? ''); ?>" placeholder="e.g., 9876543210">
                </div>

                <!-- Hospital -->
                <div class="form-group">
                    <label>Hospital <span class="required">*</span></label>
                    <select name="hospital_id" id="hospital_id" class="form-control" required>
                        <option value="">Select Hospital</option>
                        <?php while($h = mysqli_fetch_assoc($hospitals_result)): ?>
                            <option value="<?php echo $h['hospital_id']; ?>" <?php echo ($admin['hospital_id'] == $h['hospital_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['hospital_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Password (Optional) -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>New Password <span style="color:#94a3b8;font-weight:400;font-size:0.7rem;">(Leave blank to keep current password)</span></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password (min 8 characters)" minlength="8">
                    <small style="color:#94a3b8;font-size:0.7rem;">Password must be at least 8 characters long</small>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;flex-wrap:wrap;justify-content:flex-end;border-top:1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;padding-top:1.5rem;">
                <a href="hospital_admins.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" name="update_admin" class="btn-success" id="updateBtn">
                    <i class="fas fa-save"></i> Update Admin
                </button>
            </div>
        </form>
    </div>
</div>

<script>

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editAdminForm');
    
    form.addEventListener('submit', function(e) {
        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        
        if (fullName === '') {
            alert('Please enter full name');
            e.preventDefault();
            return false;
        }
        
        if (email === '') {
            alert('Please enter email address');
            e.preventDefault();
            return false;
        }
        
        // Simple email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            e.preventDefault();
            return false;
        }
        
        if (password !== '' && password.length < 8) {
            alert('Password must be at least 8 characters long');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    console.log('Edit Hospital Admin page loaded');
    console.log('Admin Data:', <?php echo json_encode($admin); ?>);
});
</script>
</body>
</html>