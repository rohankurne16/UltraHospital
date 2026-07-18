<?php
include '../config/superadmin.php';

checkSuperAdminLogin();
checkPermission('role-management');

$theme = $_SESSION['theme'] ?? 'light';
$error = '';
$success = '';

$role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($role_id <= 0) {
    header("Location: role_list.php");
    exit();
}

// Fetch role data
$query = "SELECT * FROM roles WHERE role_id = '$role_id' AND delete_flag = 0";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    header("Location: role_list.php");
    exit();
}
$role = mysqli_fetch_assoc($result);

// ============================================================
// UPDATE ROLE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $role_name = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $role_slug = mysqli_real_escape_string($conn, trim($_POST['role_slug']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    // Validate
    if (empty($role_name)) {
        $error = "Role name is required.";
    } elseif (empty($role_slug) && $role['is_system'] != 1) {
        $error = "Role slug is required.";
    } else {
        // Check if slug exists for other roles (only if not system role)
        if ($role['is_system'] != 1) {
            $check_query = "SELECT role_id FROM roles WHERE role_slug = '$role_slug' AND role_id != '$role_id' AND delete_flag = 0";
            $check_result = mysqli_query($conn, $check_query);
            if (mysqli_num_rows($check_result) > 0) {
                $error = "Role slug already exists. Please use a different slug.";
            }
        }
        
        if (empty($error)) {
            // Build update query
            $update_fields = "role_name = '$role_name', description = '$description', modified_at = NOW()";
            
            // Only update slug if not system role
            if ($role['is_system'] != 1) {
                $update_fields .= ", role_slug = '$role_slug'";
            }
            
            $update_query = "UPDATE roles SET $update_fields WHERE role_id = '$role_id'";
            
            if (mysqli_query($conn, $update_query)) {
                logAudit('Role', 'Updated role: ' . $role_name . ' (ID: ' . $role_id . ')');
                $_SESSION['success_msg'] = "Role updated successfully!";
                header("Location: role_list.php?updated=1");
                exit();
            } else {
                $error = "Error updating role: " . mysqli_error($conn);
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
    <title>Edit Role - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.light { background: #f1f5f9; }
        body.dark { background: #0a0a0a; }
        
        .main-content { margin-left: 240px; padding: 1.5rem; min-height: 100vh; transition: margin-left 0.3s ease; }
        .main-content.collapsed { margin-left: 72px; }
        
        .content-card {
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            max-width: 700px;
        }
        body.light .content-card {
            margin-top: 3%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        body.dark .content-card {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
        }
        
        .form-control {
            padding: 0.7rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }
        body.light .form-control {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        body.dark .form-control {
            background: #1e1e1e;
            border: 1px solid #2a2a2a;
            color: #f1f5f9;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-control.error {
            border-color: #ef4444;
        }
        .form-control[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        body.light label { color: #475569; }
        body.dark label { color: #d1d5db; }
        
        .form-group { margin-bottom: 1.25rem; }
        .required { color: #ef4444; }
        
        .btn {
            padding: 0.7rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        body.dark .btn-secondary {
            background: #2a2a2a;
            color: #d1d5db;
            border-color: #2a2a2a;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        body.dark .btn-secondary:hover {
            background: #3a3a3a;
        }
        
        .text-muted { color: #94a3b8; font-size: 0.8rem; }
        
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .success-msg {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .system-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #3b82f6;
        }
        body.dark .info-box {
            background: rgba(59, 130, 246, 0.15);
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        body.light .btn-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        body.dark .btn-back {
            background: #2a2a2a;
            color: #d1d5db;
            border: 1px solid #2a2a2a;
        }
        .btn-back:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 56px; }
            .content-card { padding: 1.5rem; }
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

    <!-- Back Button -->
    <a href="role_list.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Roles
    </a>

    <?php if ($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="success-msg"><i class="fas fa-check-circle"></i> Role updated successfully!</div>
    <?php endif; ?>

    <div class="content-card">
        <!-- System Role Info -->
        <?php if ($role['is_system'] == 1): ?>
            <div class="info-box">
                <i class="fas fa-shield-alt fa-lg"></i>
                <span><strong>System Role:</strong> This is a system role. The slug cannot be changed to maintain system integrity.</span>
            </div>
        <?php endif; ?>

        <form method="POST" id="editRoleForm">
            <input type="hidden" name="update_role" value="1">
            
            <!-- Role Name -->
            <div class="form-group">
                <label>Role Name <span class="required">*</span></label>
                <input type="text" 
                       name="role_name" 
                       class="form-control" 
                       required 
                       placeholder="e.g., Department Head" 
                       value="<?php echo htmlspecialchars($role['role_name'] ?? ''); ?>">
            </div>
            
            <!-- Role Slug -->
            <div class="form-group">
                <label>Role Slug <span class="required">*</span></label>
                <input type="text" 
                       name="role_slug" 
                       class="form-control" 
                       required 
                       placeholder="e.g., department-head" 
                       value="<?php echo htmlspecialchars($role['role_slug'] ?? ''); ?>" 
                       <?php echo $role['is_system'] == 1 ? 'disabled' : ''; ?>>
                <div class="text-muted" style="margin-top: 0.3rem;">
                    <i class="fas fa-info-circle"></i> Slug is used for URL and system identification.
                    <?php if ($role['is_system'] == 1): ?>
                        <span style="color: #3b82f6;"> (System role - slug cannot be changed)</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Description -->
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Brief description of this role..."><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Role
                </button>
                <a href="role_list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    if (sidebar && mainContent) {
        sidebar.classList.toggle('closed');
        mainContent.classList.toggle('collapsed');
    }
}

function toggleTheme() {
    const body = document.body;
    const currentTheme = body.classList.contains('light') ? 'dark' : 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.classList.remove(currentTheme);
    body.classList.add(newTheme);
    
    fetch('toggle_theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'theme=' + newTheme
    });
}

// Form validation
document.getElementById('editRoleForm')?.addEventListener('submit', function(e) {
    const roleName = this.querySelector('input[name="role_name"]');
    const roleSlug = this.querySelector('input[name="role_slug"]');
    
    if (roleName.value.trim() === '') {
        e.preventDefault();
        alert('Role name is required!');
        roleName.focus();
        return false;
    }
    
    // Only validate slug if not disabled (system role)
    if (!roleSlug.disabled && roleSlug.value.trim() === '') {
        e.preventDefault();
        alert('Role slug is required!');
        roleSlug.focus();
        return false;
    }
    
    return true;
});
</script>
</body>
</html>