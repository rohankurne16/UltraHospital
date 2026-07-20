<?php
include_once '../config/hospital.php';
include_once '../config/permission.php';

// Check Super Admin login
checkSuperAdminLogin();

$page_title = 'View Role';
$page_subtitle = 'Role details and information';

$theme = $_SESSION['theme'] ?? 'light';

$role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($role_id <= 0) {
    header("Location: role_list.php");
    exit();
}

// Fetch role data
$query = "SELECT r.*, 
          (SELECT name FROM register WHERE id = r.created_by) as creator_name
          FROM roles r 
          WHERE r.role_id = '$role_id' AND r.delete_flag = 0";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    header("Location: role_list.php");
    exit();
}
$role = mysqli_fetch_assoc($result);

// Get user count with this role
$user_count_query = "SELECT COUNT(*) as total FROM register WHERE role_id = '$role_id' AND delete_flag = 0";
$user_count_result = mysqli_query($conn, $user_count_query);
$user_count = mysqli_fetch_assoc($user_count_result)['total'];

// Get permission count for this role
$perm_count_query = "SELECT COUNT(*) as total FROM role_permissions WHERE role_id = '$role_id'";
$perm_count_result = mysqli_query($conn, $perm_count_query);
$perm_count = mysqli_fetch_assoc($perm_count_result)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Super Admin</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        body.light .content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        body.dark .content-card {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
        }
        
        .btn-secondary {
            padding: 0.7rem 2rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-block;
        }
        body.light .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }
        body.dark .btn-secondary {
            background: #2a2a2a;
            color: #d1d5db;
            border-color: #2a2a2a;
        }
        .btn-secondary:hover {
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
        }
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
            display: inline-block;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
        }
        .btn-warning {
            background: linear-gradient(135deg, #eab308, #ca8a04);
            color: white;
            border: none;
            padding: 0.7rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(234, 179, 8, 0.5);
        }
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        .text-muted { color: #94a3b8; font-size: 0.8rem; }
        
        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-system { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .status-custom { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        
        .stat-box {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 10px;
            text-align: center;
            min-width: 120px;
        }
        body.light .stat-box { background: #f8fafc; }
        body.dark .stat-box { background: #1e1e1e; }
        .stat-box .number { font-size: 1.5rem; font-weight: 700; color: #3b82f6; }
        .stat-box .label { font-size: 0.7rem; color: #94a3b8; }
        
        .action-buttons { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.5rem; }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 56px; }
            .content-card { padding: 1.5rem; }
            .info-row { flex-direction: column; gap: 0.25rem; }
            .info-label { width: 100%; }
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

  
  <a href="dashboard.php" class="btn btn-primary" style="margin-bottom:2%;">
    <i class="fas fa-arrow-left"></i> Back
</a>
    <div class="content-card">
        <!-- Role Header -->
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: <?php echo $role['is_system'] == 1 ? 'rgba(59, 130, 246, 0.1)' : 'rgba(168, 85, 247, 0.1)'; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: <?php echo $role['is_system'] == 1 ? '#3b82f6' : '#a855f7'; ?>;">
                <i class="fas <?php echo $role['is_system'] == 1 ? 'fa-shield-alt' : 'fa-user-tag'; ?>"></i>
            </div>
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                    <?php echo htmlspecialchars($role['role_name']); ?>
                </h2>
                <span class="status-badge <?php echo $role['is_system'] == 1 ? 'status-system' : 'status-custom'; ?>">
                    <?php echo $role['is_system'] == 1 ? '<i class="fas fa-shield-alt mr-1"></i> System Role' : '<i class="fas fa-user mr-1"></i> Custom Role'; ?>
                </span>
            </div>
        </div>

        <!-- Statistics -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <div class="stat-box">
                <div class="number"><?php echo $user_count; ?></div>
                <div class="label">Users with this role</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $perm_count; ?></div>
                <div class="label">Permissions assigned</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $role['is_system'] == 1 ? 'System' : 'Custom'; ?></div>
                <div class="label">Role Type</div>
            </div>
        </div>

        <!-- Role Details -->
        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-weight: 600; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; margin-bottom: 0.75rem;">
                <i class="fas fa-info-circle mr-2" style="color: #3b82f6;"></i> Role Information
            </h3>
            
            <div class="info-row">
                <span class="info-label"><i class="fas fa-tag mr-2" style="color: #3b82f6; width: 16px;"></i> Role ID</span>
                <span class="info-value">#<?php echo $role['role_id']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-font mr-2" style="color: #3b82f6; width: 16px;"></i> Role Name</span>
                <span class="info-value"><?php echo htmlspecialchars($role['role_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-link mr-2" style="color: #3b82f6; width: 16px;"></i> Role Slug</span>
                <span class="info-value"><code><?php echo htmlspecialchars($role['role_slug']); ?></code></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-align-left mr-2" style="color: #3b82f6; width: 16px;"></i> Description</span>
                <span class="info-value"><?php echo htmlspecialchars($role['description'] ?? 'No description provided.'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-user mr-2" style="color: #3b82f6; width: 16px;"></i> Created By</span>
                <span class="info-value"><?php echo htmlspecialchars($role['creator_name'] ?? 'System'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar mr-2" style="color: #3b82f6; width: 16px;"></i> Created At</span>
                <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($role['created_at'])); ?></span>
            </div>
            <?php if ($role['modified_at'] && $role['modified_at'] != $role['created_at']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-edit mr-2" style="color: #3b82f6; width: 16px;"></i> Last Modified</span>
                <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($role['modified_at'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <a href="edit_role.php?id=<?php echo $role['role_id']; ?>" class="btn-warning">
                <i class="fas fa-edit"></i> Edit Role
            </a>
            <a href="permissions.php?role_id=<?php echo $role['role_id']; ?>" class="btn-primary">
                <i class="fas fa-lock"></i> Manage Permissions
            </a>
            <a href="role_list.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
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
</script>
</body>
</html>