<?php
// ============================================================
// PERMISSION MANAGEMENT PAGE (permissions.php)
// ============================================================
require_once __DIR__ . '/../config/permission.php'; // Use __DIR__ for robust path inclusion

// Security: Only Super Admin can access this page
if (!$is_super_admin) {
    header("Location: dashboard.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Fetch all hospitals for filter
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE (delete_flag = 0 OR delete_flag IS NULL) AND status = 'Active' ORDER BY hospital_name";
$hospitals_result = mysqli_query($conn, $hospitals_query);
$hospitals = [];
if ($hospitals_result && mysqli_num_rows($hospitals_result) > 0) {
    while ($row = mysqli_fetch_assoc($hospitals_result)) {
        $hospitals[] = $row;
    }
}

// Get selected hospital filter
$selected_hospital = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

// Fetch all roles (with optional hospital filter)
$roles_query = "SELECT role_id, role_name, description FROM roles WHERE (delete_flag = 0 OR delete_flag IS NULL)";
if ($selected_hospital > 0) {
    $roles_query .= " AND hospital_id = $selected_hospital";
}
$roles_query .= " ORDER BY role_name";
$roles_res = mysqli_query($conn, $roles_query);
$roles = [];
if ($roles_res && mysqli_num_rows($roles_res) > 0) {
    while ($row = mysqli_fetch_assoc($roles_res)) {
        $roles[] = $row;
    }
}

// Handle Form Submission: Save Permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $selected_role_id = intval($_POST['role_id']);
    $assigned_permissions = $_POST['permissions'] ?? [];

    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Remove existing permissions for this role
        $delete_query = "DELETE FROM role_permissions WHERE role_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $selected_role_id);
        $stmt->execute();

        // Insert new permissions
        if (!empty($assigned_permissions)) {
            $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            foreach ($assigned_permissions as $p_id) {
                $p_id = intval($p_id);
                $stmt->bind_param("ii", $selected_role_id, $p_id);
                $stmt->execute();
            }
        }
        mysqli_commit($conn);
        $success_msg = "Permissions updated successfully!";
        
        // Refresh role permissions
        $role_permissions = [];
        $rp_query = "SELECT permission_id FROM role_permissions WHERE role_id = ?";
        $stmt = $conn->prepare($rp_query);
        $stmt->bind_param("i", $selected_role_id);
        $stmt->execute();
        $rp_res = $stmt->get_result();
        while ($row = $rp_res->fetch_assoc()) {
            $role_permissions[] = $row['permission_id'];
        }
        $stmt->close();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Error updating permissions: " . $e->getMessage();
    }
}

// Fetch all permissions grouped by category
$perm_res = mysqli_query($conn, "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group, permission_name");
$all_permissions = [];
while ($row = mysqli_fetch_assoc($perm_res)) {
    $all_permissions[$row['permission_group']][] = $row;
}

// Get permissions for selected role
$selected_role = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
$role_permissions = [];
if ($selected_role > 0) {
    $rp_query = "SELECT permission_id FROM role_permissions WHERE role_id = ?";
    $stmt = $conn->prepare($rp_query);
    $stmt->bind_param("i", $selected_role);
    $stmt->execute();
    $rp_res = $stmt->get_result();
    while ($row = $rp_res->fetch_assoc()) {
        $role_permissions[] = $row['permission_id'];
    }
    $stmt->close();
}

// Get role name for display
$role_name = '';
if ($selected_role > 0) {
    $role_query = "SELECT role_name FROM roles WHERE role_id = $selected_role";
    $role_result = mysqli_query($conn, $role_query);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_data = mysqli_fetch_assoc($role_result);
        $role_name = $role_data['role_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Permissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; display: flex; }
        
        /* Sidebar Styles - Same as your theme */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.03);
        }
        
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            width: calc(100% - 260px);
            min-height: 100vh;
        }
        
        .card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert i { font-size: 1.1rem; }
        
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
        .page-header .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-header .header-left i {
            color: #3b82f6;
            font-size: 1.5rem;
        }
        
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
            padding: 0.6rem 0.8rem;
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
        
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59,130,246,0.3);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.6rem;
        }
        .perm-group {
            margin-bottom: 2rem;
        }
        .perm-group-title {
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            color: #3b82f6;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .perm-group-title .count {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 400;
            background: #f1f5f9;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
        }
        .perm-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            padding: 0.5rem 0.7rem;
            border-radius: 6px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .perm-item:hover {
            background: #eff6ff;
            border-color: #dbeafe;
        }
        .perm-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
            flex-shrink: 0;
        }
        .perm-item span {
            font-size: 0.85rem;
            color: #475569;
        }
        .perm-item .perm-icon {
            color: #3b82f6;
            font-size: 0.8rem;
            margin-right: 0.2rem;
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
            bottom: 2rem;
            text-align: right;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .select-all-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .select-all-row .left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .select-all-row .left .btn-sm {
            padding: 0.25rem 0.8rem;
            font-size: 0.75rem;
        }
        .btn-outline {
            background: transparent;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-outline:hover {
            background: #f1f5f9;
        }
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34,197,94,0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 3rem;
            color: #e2e8f0;
            display: block;
            margin-bottom: 1rem;
        }
        .empty-state h3 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .hospital-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 1rem; width: calc(100% - 200px); }
        }
        @media (max-width: 480px) {
            .sidebar { width: 70px; }
            .main-content { margin-left: 70px; padding: 1rem; width: calc(100% - 70px); }
            .perm-grid { grid-template-columns: 1fr; }
            .filter-row .field { min-width: 140px; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <i class="fas fa-lock"></i>
            <div>
                <h1>Permission Management</h1>
                <p>Manage role-based access control for all users</p>
            </div>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card">
        <form method="GET" id="filterForm" class="filter-row">
            <div class="field">
                <label><i class="fas fa-hospital" style="color:#3b82f6;"></i> Hospital</label>
                <select name="hospital_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Hospitals</option>
                    <?php foreach ($hospitals as $h): ?>
                        <option value="<?php echo $h['hospital_id']; ?>" <?php echo ($selected_hospital == $h['hospital_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['hospital_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label><i class="fas fa-user-tag" style="color:#3b82f6;"></i> Role</label>
                <select name="role_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['role_id']; ?>" <?php echo ($selected_role == $r['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="flex:0.5; min-width:150px;">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('filterForm').reset(); this.form.submit();">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </form>
        
        <?php if ($selected_hospital > 0): ?>
        <div style="margin-top:0.75rem; display:flex; align-items:center; gap:0.5rem;">
            <span class="hospital-badge">
                <i class="fas fa-hospital"></i> 
                <?php 
                    foreach ($hospitals as $h) {
                        if ($h['hospital_id'] == $selected_hospital) {
                            echo htmlspecialchars($h['hospital_name']);
                            break;
                        }
                    }
                ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($selected_role > 0): ?>
        <!-- Role Info -->
        <div class="card" style="padding:0.8rem 1.25rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <span class="role-badge">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($role_name); ?>
                </span>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()">
                        <i class="fas fa-check-double"></i> Select All
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()">
                        <i class="fas fa-times"></i> Deselect All
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Permissions Form -->
        <form method="POST" id="permissionForm">
            <input type="hidden" name="role_id" value="<?php echo $selected_role; ?>">
            <input type="hidden" name="save_permissions" value="1">
            
            <?php foreach ($all_permissions as $group => $perms): ?>
                <div class="card perm-group">
                    <div class="perm-group-title">
                        <i class="fas fa-folder-open"></i>
                        <?php echo htmlspecialchars($group); ?>
                        <span class="count"><?php echo count($perms); ?> permissions</span>
                    </div>
                    <div class="perm-grid">
                        <?php foreach ($perms as $p): ?>
                            <div class="perm-item">
                                <input type="checkbox" name="permissions[]" value="<?php echo $p['permission_id']; ?>" 
                                    id="perm_<?php echo $p['permission_id']; ?>"
                                    <?php echo in_array($p['permission_id'], $role_permissions) ? 'checked' : ''; ?>>
                                <label for="perm_<?php echo $p['permission_id']; ?>" style="display:flex; align-items:center; gap:0.4rem; cursor:pointer; width:100%;">
                                    <?php if (!empty($p['permission_icon'])): ?>
                                        <i class="fas <?php echo $p['permission_icon']; ?> perm-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle perm-icon"></i>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($p['permission_name']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="sticky-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="card empty-state">
            <i class="fas fa-hand-pointer"></i>
            <h3>Select a Role</h3>
            <p>Choose a role from the dropdown above to view and manage its permissions.</p>
            <p style="font-size:0.85rem; margin-top:0.5rem; color:#94a3b8;">
                <?php if ($selected_hospital > 0): ?>
                    Showing roles for selected hospital only.
                <?php else: ?>
                    Showing all roles across all hospitals.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
// Select/Deselect All
function selectAll() {
    document.querySelectorAll('.perm-item input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.perm-item input[type="checkbox"]').forEach(cb => cb.checked = false);
}

// Auto-submit on hospital/role change
document.addEventListener('DOMContentLoaded', function() {
    const hospitalSelect = document.querySelector('select[name="hospital_id"]');
    const roleSelect = document.querySelector('select[name="role_id"]');
    
    if (hospitalSelect) {
        hospitalSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});

// Confirm before saving
document.getElementById('permissionForm')?.addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to save these permissions?')) {
        e.preventDefault();
    }
});
</script>

</body>
</html>