<?php
include '../config/permission.php';

checkSuperAdminLogin();
checkPermission('role-management');

$page_title = 'Add Role';
$page_subtitle = 'Create a new system role';

$theme = $_SESSION['theme'] ?? 'light';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role_name = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $role_slug = mysqli_real_escape_string($conn, trim($_POST['role_slug']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    if (empty($role_name) || empty($role_slug)) {
        $error = "Role name and slug are required.";
    } else {
        $check_query = "SELECT role_id FROM roles WHERE role_slug = '$role_slug' AND delete_flag = 0";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Role slug already exists. Please use a different slug.";
        } else {
            $insert_query = "INSERT INTO roles (role_name, role_slug, description, created_by) 
                             VALUES ('$role_name', '$role_slug', '$description', '{$_SESSION['id']}')";
            if (mysqli_query($conn, $insert_query)) {
                $role_id = mysqli_insert_id($conn);
                logAudit('Role', 'Added new role: ' . $role_name . ' (ID: ' . $role_id . ')');
                header("Location: role_list.php?success=1");
                exit();
            } else {
                $error = "Error creating role: " . mysqli_error($conn);
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
    <title>Add Role - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Same styles as role_list.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        body.light { background: #f1f5f9; }
        body.dark { background: #0a0a0a; }
        
       
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
        .brand-text h2 { font-size: 0.9rem; font-weight: 700; margin: 0; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .brand-text p { font-size: 0.65rem; color: #94a3b8; margin: 0; }
        
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
            max-width: 700px;
        }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; }
        body.dark .content-card { background: #1a1a1a; border: 1px solid #2a2a2a; }
        
        .form-control {
            padding: 0.7rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }
        body.light .form-control { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.dark .form-control { background: #1e1e1e; border: 1px solid #2a2a2a; color: #f1f5f9; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .form-control.error { border-color: #ef4444; }
        
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
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
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
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
            display: inline-block;
        }
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        .text-muted { color: #94a3b8; font-size: 0.8rem; }
        
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .slug-hint {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.3rem;
        }
        
        @media (max-width: 768px) {
           
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .content-card { padding: 1.5rem; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .main-content.collapsed { margin-left: 0; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- Sidebar -->
   <?php include 'sidebar.php' ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Header -->
   <?php include 'header.php' ?>
    <?php if ($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST">
            <div class="form-group">
                <label>Role Name <span class="required">*</span></label>
                <input type="text" name="role_name" class="form-control" required placeholder="e.g., Department Head" value="<?php echo isset($_POST['role_name']) ? htmlspecialchars($_POST['role_name']) : ''; ?>">
                <small class="text-muted">Display name for the role</small>
            </div>
            
            <div class="form-group">
                <label>Role Slug <span class="required">*</span></label>
                <input type="text" name="role_slug" class="form-control" required placeholder="e.g., department-head" value="<?php echo isset($_POST['role_slug']) ? htmlspecialchars($_POST['role_slug']) : ''; ?>">
                <div class="slug-hint">
                    <i class="fas fa-info-circle"></i> Slug is used for URL and system identification. Use lowercase with hyphens.
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this role..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div style="display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap;">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Create Role
                </button>
                <a href="role_list.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('input[name="role_name"]').addEventListener('input', function() {
    const slugInput = document.querySelector('input[name="role_slug"]');
    if (!slugInput.value || slugInput.value === this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-')) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
});



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
</script>
</body>
</html>