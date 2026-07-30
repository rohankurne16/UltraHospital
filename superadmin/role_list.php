<?php
include_once '../config/hospital.php';
include_once '../config/permission.php';

// Check Super Admin login
checkSuperAdminLogin();

$page_title = 'Role Management';
$page_subtitle = 'Manage all system roles';

$theme = $_SESSION['theme'] ?? 'light';

// ============================================================
// HANDLE ADD ROLE (NEW)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $role_name = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $role_slug = mysqli_real_escape_string($conn, trim($_POST['role_slug']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $created_by = $_SESSION['id'] ?? 0;

    // Validate
    $errors = [];
    if (empty($role_name)) {
        $errors[] = "Role name is required.";
    }
    if (empty($role_slug)) {
        $errors[] = "Role slug is required.";
    } else {
        // Check uniqueness
        $check = mysqli_query($conn, "SELECT role_id FROM roles WHERE role_slug = '$role_slug' AND delete_flag = 0");
        if (mysqli_num_rows($check) > 0) {
            $errors[] = "Role slug '$role_slug' already exists. Please use a different slug.";
        }
    }

    if (empty($errors)) {
        $insert = "INSERT INTO roles (hospital_id, role_name, role_slug, description, is_system, created_by, created_at, modified_at, delete_flag)
                   VALUES (NULL, '$role_name', '$role_slug', '$description', 0, '$created_by', NOW(), NOW(), 0)";
        if (mysqli_query($conn, $insert)) {
            header("Location: role_list.php?add_success=1");
            exit();
        } else {
            $error_msg = "Database error: " . mysqli_error($conn);
        }
    } else {
        $error_msg = implode("<br>", $errors);
    }
}

// ============================================================
// EXISTING CODE (search, fetch roles, etc.)
// ============================================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "delete_flag = 0";
if ($search) {
    $where .= " AND (role_name LIKE '%$search%' OR role_slug LIKE '%$search%' OR description LIKE '%$search%')";
}

$query = "SELECT * FROM roles WHERE $where ORDER BY role_id ASC";
$result = mysqli_query($conn, $query);

$total_query = "SELECT COUNT(*) as total FROM roles WHERE delete_flag = 0";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_roles = $total_row['total'];

// Get messages
$add_success = isset($_GET['add_success']);
$error_msg_display = isset($error_msg) ? $error_msg : (isset($_GET['error']) ? $_GET['error'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Same styles as permissions.php */
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
    margin-top: 70px; /* Adjust to match your header height */
}
        .main-content.collapsed { margin-left: 70px; }
        
        .content-card {
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; }
        body.dark .content-card { background: #1a1a1a; border: 1px solid #2a2a2a; }
        
        .form-control {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }
        body.light .form-control { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.dark .form-control { background: #1e1e1e; border: 1px solid #2a2a2a; color: #f1f5f9; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        .btn-secondary {
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: linear-gradient(135deg, #2563eb, #2563eb);
            color:#ffffff;
            text-decoration: none;
            display: inline-flex;
           
            align-items: center;
            gap: 0.5rem;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(239, 68, 68, 0.5); }
        .btn-warning {
            background: linear-gradient(135deg, #eab308, #ca8a04);
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
        }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(234, 179, 8, 0.5); }
        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
        }
        .btn-info:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(6, 182, 212, 0.5); }
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        .text-muted { color: #94a3b8; font-size: 0.8rem; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
        }
        td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
        }
        .table-row:hover { background: <?php echo $theme == 'dark' ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)'; ?>; }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-system { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .status-custom { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        
        .empty-state { padding: 3rem; text-align: center; color: #94a3b8; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; color: #2a2a2a; }
        .action-buttons { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        
        
        
        /* Page Header with Add Button */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
        }
        .page-header p {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: <?php echo $theme == 'dark' ? '#1a1a1a' : '#ffffff'; ?>;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalFade 0.3s ease;
        }
        @keyframes modalFade {
            from { transform: scale(0.95) translateY(-20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-box h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
        }
        .modal-close {
            float: right;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }
        .modal-close:hover { color: #ef4444; }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .modal-box { padding: 1.5rem; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .main-content.collapsed { margin-left: 0; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom:2%;display:inline-flex;align-items:center;gap:0.5rem;padding:0.6rem 1.2rem;border-radius:8px;background:#2563eb;color:#ffffff;border:1px solid #e2e8f0;text-decoration:none;">
        <i class="fas fa-arrow-left"></i> Back
    </a>

   
    <?php if (isset($_GET['error'])): 
        $error_msg = '';
        if ($_GET['error'] == 'system_role') $error_msg = 'System roles cannot be deleted.';
        elseif ($_GET['error'] == 'users_assigned') $error_msg = 'Cannot delete this role because users are assigned to it.';
        elseif ($_GET['error'] == 'delete_failed') $error_msg = 'Failed to delete the role. Please try again.';
    ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="content-card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
            <div style="flex:1;min-width:200px;">
                <input type="text" name="search" placeholder="Search roles..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <button type="submit" class="btn-primary" style="padding:0.6rem 1.2rem;">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="role_list.php" class="btn-secondary">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>
    </div>

    <!-- Role List -->
    <div class="content-card">
        <!-- Page Header with Add Button -->
        <div class="page-header">
            <div>
                <h1>Role List</h1>
                <p>Manage all system roles</p>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Role
            </button>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Role Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $counter = 1; while($row = mysqli_fetch_assoc($result)): 
                            $is_system = $row['is_system'] == 1;
                            $can_delete = !$is_system;
                        ?>
                            <tr class="table-row">
                                <td style="color:#94a3b8;font-size:0.8rem;"><?php echo $counter++; ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:0.75rem;">
                                        <div style="width:36px;height:36px;border-radius:50%;background:<?php echo $is_system ? 'rgba(59,130,246,0.1)' : 'rgba(168,85,247,0.1)'; ?>;display:flex;align-items:center;justify-content:center;color:<?php echo $is_system ? '#3b82f6' : '#a855f7'; ?>;">
                                            <i class="fas <?php echo $is_system ? 'fa-shield-alt' : 'fa-user-tag'; ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                                <?php echo htmlspecialchars($row['role_name']); ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:#94a3b8;">ID: <?php echo $row['role_id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-size:0.85rem;color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                                        <?php echo htmlspecialchars($row['role_slug']); ?>
                                    </span>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;max-width:200px;">
                                    <?php echo htmlspecialchars($row['description'] ?? 'No description'); ?>
                                </td>
                                <td>
                                    <?php if ($is_system): ?>
                                        <span class="status-badge status-system">
                                            <i class="fas fa-shield-alt mr-1"></i> System
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-custom">
                                            <i class="fas fa-user mr-1"></i> Custom
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#94a3b8;font-size:0.8rem;">
                                    <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td style="text-align:right;">
                                    <div class="action-buttons" style="justify-content:flex-end;">
                                        <a href="view_role.php?id=<?php echo $row['role_id']; ?>" class="btn-info" title="View Role">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_role.php?id=<?php echo $row['role_id']; ?>" class="btn-warning" title="Edit Role">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($can_delete): ?>
                                            <a href="delete_role.php?id=<?php echo $row['role_id']; ?>" class="btn-danger" title="Delete Role" onclick="return confirm('Are you sure you want to delete this role? This action cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-danger" style="opacity:0.5;cursor:not-allowed;" title="System roles cannot be deleted" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-user-tag"></i>
                                No roles found
                                <?php if ($search): ?>
                                    <br><small>Try adjusting your search filters</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
            <span class="text-muted">Showing <?php echo mysqli_num_rows($result); ?> of <?php echo $total_roles; ?> roles</span>
            <span class="text-muted">
                <i class="fas fa-shield-alt mr-1" style="color:#3b82f6;"></i> System roles cannot be deleted
            </span>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD ROLE MODAL (NEW) -->
<!-- ============================================================ -->
<div class="modal-overlay" id="addRoleModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2><i class="fas fa-user-plus" style="color:#3b82f6;"></i> Add New Role</h2>
        <form method="POST" action="" onsubmit="return validateForm()">
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.3rem;color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                    Role Name <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="role_name" id="role_name" class="form-control" placeholder="e.g., Department Head" required oninput="generateSlug()">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.3rem;color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                    Role Slug <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="role_slug" id="role_slug" class="form-control" placeholder="e.g., department-head" required>
                <small style="color:#94a3b8;font-size:0.7rem;">Unique identifier (lowercase, hyphens only)</small>
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block;font-weight:600;margin-bottom:0.3rem;color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                    Description
                </label>
                <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="add_role" class="btn-primary">
                    <i class="fas fa-save"></i> Create Role
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal controls
function openModal() {
    document.getElementById('addRoleModal').classList.add('active');
    document.getElementById('role_name').focus();
}
function closeModal() {
    document.getElementById('addRoleModal').classList.remove('active');
}
// Close modal on overlay click
document.getElementById('addRoleModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-generate slug from name
function generateSlug() {
    const name = document.getElementById('role_name').value;
    const slugInput = document.getElementById('role_slug');
    // Only auto-generate if slug is empty or was auto-generated (not manually edited)
    if (!slugInput.dataset.userModified) {
        const slug = name
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        slugInput.value = slug;
    }
}
// Mark slug as user-modified when user types in it
document.getElementById('role_slug').addEventListener('input', function() {
    this.dataset.userModified = 'true';
});

// Validate form before submit
function validateForm() {
    const name = document.getElementById('role_name').value.trim();
    const slug = document.getElementById('role_slug').value.trim();
    if (!name || !slug) {
        alert('Role Name and Slug are required.');
        return false;
    }
    if (!/^[a-z0-9-]+$/.test(slug)) {
        alert('Slug can only contain lowercase letters, numbers, and hyphens.');
        return false;
    }
    return true;
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