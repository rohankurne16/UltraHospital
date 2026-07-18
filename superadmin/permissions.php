<?php
include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// Check Super Admin login
checkSuperAdminLogin();

$page_title = 'Role Permission Management';
$page_subtitle = 'Manage role-based access control';

$theme = $_SESSION['theme'] ?? 'light';

$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active' ORDER BY hospital_name";
$hospitals_result = mysqli_query($conn, $hospitals_query);

$roles_query = "SELECT * FROM roles WHERE delete_flag = 0 ORDER BY role_name";
$roles_result = mysqli_query($conn, $roles_query);

$selected_role = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
$role_permissions = [];

if ($selected_role > 0) {
    $role_permissions = getRolePermissions($selected_role);
}

// ============================================================
// FIX: GET PERMISSIONS GROUPED
// ============================================================
$permissions_grouped = getPermissionsGrouped();

$role_name = '';
if ($selected_role > 0) {
    $role_query = "SELECT role_name FROM roles WHERE role_id = '$selected_role'";
    $role_result = mysqli_query($conn, $role_query);
    $role_row = mysqli_fetch_assoc($role_result);
    $role_name = $role_row['role_name'] ?? '';
}

$success_msg = '';
$error_msg = '';

// ============================================================
// SAVE PERMISSIONS - FIXED (डुप्लिकेट काढले)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_permissions'])) {
    $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;
    $permission_ids = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if ($role_id > 0) {
        // Save permissions to database
        saveRolePermissions($role_id, $permission_ids);
        logAudit('Permission', 'Updated permissions for role ID: ' . $role_id);
        
        // Refresh session permissions if current user's role is updated
        if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == $role_id) {
            $_SESSION['permissions'] = getUserPermissions($role_id);
            $success_msg = 'Permissions saved successfully! Your permissions have been updated.';
        } else {
            $success_msg = 'Permissions saved successfully!';
        }
        
        $role_permissions = getRolePermissions($role_id);
    } else {
        $error_msg = 'Invalid role selected!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Permission Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; background: #f0f2f5; }

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
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
        }
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
            color: #475569;
        }
        .sidebar-item i { width: 1.25rem; text-align: center; }
        .sidebar-item:hover { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .sidebar-item.active { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .sidebar.closed .sidebar-item span { display: none; }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 0 0.5rem 1rem 0.5rem;
            border-bottom: 1px solid #e2e8f0;
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
        .brand-text h2 { font-size: 0.9rem; font-weight: 700; margin: 0; color: #1e293b; }
        .brand-text p { font-size: 0.65rem; color: #94a3b8; margin: 0; }

        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed { margin-left: 70px; }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .breadcrumb a { color: #3b82f6; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        .page-header p {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-row .field {
            flex: 1;
            min-width: 180px;
        }
        .filter-row label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-row .form-control {
            width: 100%;
            padding: 0.5rem 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .filter-row .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .action-bar .left { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .action-bar .right { display: flex; gap: 0.5rem; flex-wrap: wrap; }

        .btn {
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-outline {
            background: transparent;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-outline:hover { background: #f8fafc; }
        .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.75rem; }

        .perm-accordion { margin-top: 0.5rem; }
        .perm-group {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 0.6rem;
            overflow: hidden;
            background: #ffffff;
            transition: all 0.3s ease;
        }
        .perm-group:hover { border-color: #cbd5e1; }

        .perm-group-header {
            padding: 0.7rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.2s ease;
            border-bottom: 1px solid transparent;
        }
        .perm-group-header:hover { background: #f1f5f9; }
        .perm-group-header .left {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e293b;
        }
        .perm-group-header .left i { color: #3b82f6; font-size: 1rem; }
        .perm-group-header .count {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 400;
            background: #f1f5f9;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
        }
        .perm-group-header .arrow {
            transition: transform 0.3s ease;
            color: #94a3b8;
        }
        .perm-group-header .arrow.open { transform: rotate(180deg); }
        .perm-group-header .badge {
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            background: #dbeafe;
            color: #3b82f6;
        }

        .perm-group-body {
            padding: 0.8rem 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.4rem;
        }
        .perm-group-body.hidden { display: none; }

        .perm-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .perm-item:hover { background: #f8fafc; }
        .perm-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
            flex-shrink: 0;
        }
        .perm-item label {
            font-size: 0.8rem;
            cursor: pointer;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .perm-item label i {
            color: #3b82f6;
            width: 14px;
            font-size: 0.7rem;
        }

        .alert {
            padding: 0.8rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #16a34a;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }
        .alert i { font-size: 1.1rem; }

        .empty-state {
            text-align: center;
            padding: 2.5rem;
        }
        .empty-state i {
            font-size: 2.5rem;
            color: #94a3b8;
            display: block;
            margin-bottom: 0.8rem;
        }
        .empty-state h3 { font-size: 1rem; color: #1e293b; }
        .empty-state p { color: #94a3b8; font-size: 0.85rem; }

        .search-box {
            position: relative;
            min-width: 200px;
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .search-box input {
            padding-left: 2.2rem;
            width: 100%;
            padding: 0.5rem 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            outline: none;
            background: #f8fafc;
        }
        .search-box input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            background: #ffffff;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            background: #dbeafe;
            color: #3b82f6;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .sticky-footer {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1rem 0;
            margin-top: 1rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
            z-index: 10;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .sidebar.closed { width: 60px; }
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .perm-group-body { grid-template-columns: 1fr 1fr; }
            .filter-row .field { min-width: 140px; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 0.8rem; }
            .main-content.collapsed { margin-left: 0; }
            .perm-group-body { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .action-bar { flex-direction: column; align-items: stretch; }
            .action-bar .left, .action-bar .right { justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <?php include 'header.php'; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-primary" style="margin-bottom:2%;">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <div class="card">
        <form method="GET" class="filter-row">
            <?php if ($_SESSION['role'] == 'SuperAdmin'): ?>
            <div class="field">
                <label><i class="fas fa-hospital" style="color:#3b82f6;"></i> Hospital</label>
                <select class="form-control" name="hospital_id" onchange="this.form.submit()">
                    <option value="">All Hospitals</option>
                    <?php
                    $hospital_filter = isset($_GET['hospital_id']) ? $_GET['hospital_id'] : '';
                    while($h = mysqli_fetch_assoc($hospitals_result)):
                    ?>
                        <option value="<?php echo $h['hospital_id']; ?>" <?php echo $hospital_filter == $h['hospital_id'] ? 'selected' : ''; ?>>
                            <?php echo $h['hospital_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="field">
                <label><i class="fas fa-user-tag" style="color:#3b82f6;"></i> Role</label>
                <select name="role_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Role --</option>
                    <?php while($role = mysqli_fetch_assoc($roles_result)): ?>
                        <option value="<?php echo $role['role_id']; ?>" <?php echo $selected_role == $role['role_id'] ? 'selected' : ''; ?>>
                            <?php echo $role['role_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="field" style="flex:0.5;">
                <label><i class="fas fa-search" style="color:#3b82f6;"></i> Search</label>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchPermission" placeholder="Search permissions..." onkeyup="searchPermissions()">
                </div>
            </div>
        </form>

        <?php if ($selected_role > 0): ?>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
            <span class="role-badge">
                <i class="fas fa-check-circle"></i>
                <?php echo $role_name; ?>
            </span>
            <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                <button type="button" class="btn btn-sm btn-outline" onclick="expandAll()"><i class="fas fa-plus-circle"></i> Expand All</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="collapseAll()"><i class="fas fa-minus-circle"></i> Collapse All</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()"><i class="fas fa-check-double"></i> Select All</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAll()"><i class="fas fa-times"></i> Unselect All</button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($selected_role > 0): ?>
    <div class="card" style="padding:0.5rem 1.25rem 1.25rem;">
        <form method="POST" id="permissionForm">
            <input type="hidden" name="role_id" value="<?php echo $selected_role; ?>">
            <input type="hidden" name="save_permissions" value="1">

            <div class="perm-accordion" id="permAccordion">
                <?php foreach ($permissions_grouped as $group => $permissions): ?>
                    <div class="perm-group" data-group="<?php echo strtolower(str_replace(' ', '-', $group)); ?>">
                        <div class="perm-group-header" onclick="toggleGroup(this)">
                            <div class="left">
                                <i class="fas fa-folder-open"></i>
                                <?php echo $group; ?>
                                <span class="count"><?php echo count($permissions); ?></span>
                                <span class="badge">
                                    <?php
                                        $checked_count = 0;
                                        foreach ($permissions as $p) {
                                            if (in_array($p['permission_id'], $role_permissions)) $checked_count++;
                                        }
                                        echo $checked_count . '/' . count($permissions) . ' selected';
                                    ?>
                                </span>
                            </div>
                            <i class="fas fa-chevron-down arrow open"></i>
                        </div>
                        <div class="perm-group-body">
                            <?php foreach ($permissions as $permission): ?>
                                <?php
                                    $checked = in_array($permission['permission_id'], $role_permissions) ? 'checked' : '';
                                ?>
                                <div class="perm-item" data-permission="<?php echo strtolower($permission['permission_name']); ?>">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $permission['permission_id']; ?>" id="p_<?php echo $permission['permission_id']; ?>" <?php echo $checked; ?> onchange="updateCount(this)">
                                    <label for="p_<?php echo $permission['permission_id']; ?>">
                                        <?php if ($permission['permission_icon']): ?>
                                            <i class="fas <?php echo $permission['permission_icon']; ?>"></i>
                                        <?php endif; ?>
                                        <?php echo $permission['permission_name']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="sticky-footer">
                <button type="reset" class="btn btn-secondary" onclick="window.location.reload()">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card empty-state">
        <i class="fas fa-lock"></i>
        <h3>Select a Role</h3>
        <p>Choose a role from the dropdown above to manage its permissions.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('closed');
    document.getElementById('mainContent').classList.toggle('collapsed');
}

function toggleGroup(el) {
    const body = el.nextElementSibling;
    const arrow = el.querySelector('.arrow');
    body.classList.toggle('hidden');
    arrow.classList.toggle('open');
}

function expandAll() {
    document.querySelectorAll('.perm-group-body').forEach(b => b.classList.remove('hidden'));
    document.querySelectorAll('.perm-group-header .arrow').forEach(a => a.classList.add('open'));
}

function collapseAll() {
    document.querySelectorAll('.perm-group-body').forEach((b, i) => {
        if (i > 0) b.classList.add('hidden');
    });
    document.querySelectorAll('.perm-group-header .arrow').forEach((a, i) => {
        if (i > 0) a.classList.remove('open');
    });
}

function selectAll() {
    document.querySelectorAll('.perm-item input[type="checkbox"]').forEach(c => c.checked = true);
    updateAllCounts();
}

function deselectAll() {
    document.querySelectorAll('.perm-item input[type="checkbox"]').forEach(c => c.checked = false);
    updateAllCounts();
}

function updateCount(el) {
    const group = el.closest('.perm-group');
    const items = group.querySelectorAll('.perm-item input[type="checkbox"]');
    const checked = group.querySelectorAll('.perm-item input[type="checkbox"]:checked');
    const badge = group.querySelector('.badge');
    if (badge) {
        badge.textContent = checked.length + '/' + items.length + ' selected';
    }
}

function updateAllCounts() {
    document.querySelectorAll('.perm-group').forEach(g => {
        const items = g.querySelectorAll('.perm-item input[type="checkbox"]');
        const checked = g.querySelectorAll('.perm-item input[type="checkbox"]:checked');
        const badge = g.querySelector('.badge');
        if (badge) {
            badge.textContent = checked.length + '/' + items.length + ' selected';
        }
    });
}

function searchPermissions() {
    const query = document.getElementById('searchPermission').value.toLowerCase().trim();
    document.querySelectorAll('.perm-item').forEach(item => {
        const text = item.dataset.permission || '';
        item.style.display = text.includes(query) ? 'flex' : 'none';
    });

    document.querySelectorAll('.perm-group').forEach(group => {
        const visible = group.querySelectorAll('.perm-item[style*="display: flex"]').length > 0;
        group.style.display = visible ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.perm-group').forEach((g, i) => {
        const body = g.querySelector('.perm-group-body');
        const arrow = g.querySelector('.arrow');
        if (i > 0) {
            body.classList.add('hidden');
            arrow.classList.remove('open');
        }
    });
});
</script>
</body>
</html>