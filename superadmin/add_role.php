<?php
include '../config/permission.php';
checkSuperAdminLogin();
checkPermission('role-management');

// --- Clean up GET parameters by moving them to session ---
if (isset($_GET['success']) && !isset($_SESSION['success'])) {
    $_SESSION['success'] = "Role created successfully!";
    header("Location: role_list.php");
    exit();
}
if (isset($_GET['error']) && !isset($_SESSION['error'])) {
    $_SESSION['error'] = $_GET['error'];
    header("Location: role_list.php");
    exit();
}

// --- Handle delete action ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $role_id = intval($_GET['delete']);
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM register WHERE role_id = $role_id AND delete_flag = 0");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] > 0) {
        $_SESSION['error'] = "Cannot delete role; it is assigned to users.";
    } else {
        $update = mysqli_query($conn, "UPDATE roles SET delete_flag = 1 WHERE role_id = $role_id");
        if ($update) {
            logAudit('Role', 'Deleted role ID: ' . $role_id);
            $_SESSION['success'] = "Role deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting role: " . mysqli_error($conn);
        }
    }
    header("Location: role_list.php");
    exit();
}

// --- Fetch roles ---
$sql = "SELECT * FROM roles WHERE delete_flag = 0 ORDER BY role_name";
$result = mysqli_query($conn, $sql);
$roles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $roles[] = $row;
}

// --- Get and clear session messages ---
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error   = isset($_SESSION['error'])   ? $_SESSION['error']   : '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role List - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .main-content { margin-left: 250px; padding: 1.5rem; min-height: 100vh; }
        .content-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .page-header { margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .page-header p { color: #64748b; margin-top: 0.25rem; }
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59,130,246,0.5); }
        .btn-secondary {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.3s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-danger:hover { background: #dc2626; }
        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit:hover { background: #2563eb; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 0.5rem; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 0.85rem; }
        td { padding: 0.75rem 0.5rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-inactive { background: #fee2e2; color: #dc2626; }
       
       
        .text-muted { color: #94a3b8; font-size: 0.8rem; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .empty-row td { text-align: center; padding: 2rem; color: #94a3b8; }
        @media (max-width: 768px) {
            .main-content { margin-left: 200px; padding: 1rem; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <?php include 'header.php'; ?>

       
        <div class="content-card">
            <div class="page-header">
                <h1><?php echo $page_title; ?></h1>
                <p>Manage system roles and permissions</p>
            </div>

            <div class="action-row">
                <a href="add_role.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Role</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($roles) > 0): ?>
                            <?php $counter = 1; foreach ($roles as $role): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($role['role_name']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($role['role_slug']); ?></code></td>
                                    <td><?php echo htmlspecialchars($role['description'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($role['status'] ?? 'Active') === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $role['status'] ?? 'Active'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_role.php?id=<?php echo $role['role_id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                            <a href="role_list.php?delete=<?php echo $role['role_id']; ?>" class="btn-danger" onclick="return confirm('Are you sure you want to delete this role?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row"><td colspan="6">No roles found. <a href="add_role.php">Create one now</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>