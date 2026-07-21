<?php
include '../config/permission.php';
checkSuperAdminLogin();

$page_title = 'Hospital Admins';
$page_subtitle = 'Manage all hospital administrators';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$hospital_filter = isset($_GET['hospital']) ? mysqli_real_escape_string($conn, $_GET['hospital']) : '';

// Delete action - Soft delete (delete_flag = 1)
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['id']);
    $check_query = "SELECT ha.*, h.hospital_name, r.email 
                    FROM hospital_admin ha 
                    LEFT JOIN hospital_master h ON ha.hospital_id = h.hospital_id 
                    LEFT JOIN register r ON ha.register_id = r.id 
                    WHERE ha.admin_id = '$delete_id' AND ha.delete_flag = 0";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $admin_data = mysqli_fetch_assoc($check_result);
        
        $delete_hospital_admin = "UPDATE hospital_admin SET delete_flag = 1 WHERE admin_id = '$delete_id'";
        mysqli_query($conn, $delete_hospital_admin);
        
        $register_id = $admin_data['register_id'];
        $delete_register = "UPDATE register SET delete_flag = 1 WHERE id = '$register_id'";
        mysqli_query($conn, $delete_register);
        
        logAudit('Hospital Admin', "Deleted hospital admin: " . $admin_data['full_name'] . " (ID: $delete_id)");
        
        header("Location: hospital_admins.php?deleted=1");
        exit();
    }
}

$where = "ha.delete_flag = 0";
if ($search) {
    $where .= " AND (ha.full_name LIKE '%$search%' OR ha.email LIKE '%$search%' OR h.hospital_name LIKE '%$search%')";
}
if ($hospital_filter) {
    $where .= " AND ha.hospital_id = '$hospital_filter'";
}

$query = "SELECT ha.*, h.hospital_name, r.role 
          FROM hospital_admin ha 
          LEFT JOIN hospital_master h ON ha.hospital_id = h.hospital_id 
          LEFT JOIN register r ON ha.register_id = r.id 
          WHERE $where 
          ORDER BY ha.admin_id DESC";
$result = mysqli_query($conn, $query);

$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'";
$hospitals_result = mysqli_query($conn, $hospitals_query);

$theme = $_SESSION['theme'] ?? 'dark';
$deleted = isset($_GET['deleted']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Admins - Super Admin</title>
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
        body.dark .table-row { border-bottom-color: #2a2a2a; }
        body.dark .table-row:hover { background: rgba(255,255,255,0.03); }
        
        body.light { background: #f1f5f9; }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        body.light .form-control { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.light .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        body.light .text-primary { color: #1e293b; }
        body.light .text-secondary { color: #64748b; }
        body.light .table-row { border-bottom-color: #e2e8f0; }
        body.light .table-row:hover { background: rgba(0,0,0,0.02); }
        
        .content-card { border-radius: 16px; padding: 1.5rem; transition: all 0.3s ease; }
        .form-control { padding: 0.6rem 1rem; border-radius: 10px; transition: all 0.3s ease; width: 100%; outline: none; font-size: 0.9rem; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        .btn-secondary { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding: 0.6rem 1.5rem; border-radius: 10px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; }
        .btn-secondary:hover { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .role-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 1rem; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .action-btn { 
            padding: 0.4rem; 
            border-radius: 8px; 
            border: none; 
            background: transparent; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            color: #94a3b8; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.9rem;
        }
        .action-btn:hover { background: <?php echo $theme == 'dark' ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'; ?>; }
        .action-btn.blue:hover { color: #3b82f6; }
        .action-btn.green:hover { color: #22c55e; }
        .action-btn.red:hover { color: #ef4444; }
        
        .main-content {
            margin-left: 64px;
            padding: 1.5rem;
            min-height: 100vh;
            transition: all .3s ease;
        }
       
        
        .success-msg {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 56px; padding: 1rem; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">
    <!-- ============================================
         SIDEBAR
         ============================================ -->
    <?php include 'sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <?php include 'header.php'; ?>

        <?php if ($deleted): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle mr-2"></i>Hospital admin deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" placeholder="Search by name, email, or hospital..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                </div>
                <div style="width: 200px;">
                    <select name="hospital" class="form-control">
                        <option value="">All Hospitals</option>
                        <?php while($h = mysqli_fetch_assoc($hospitals_result)): ?>
                            <option value="<?php echo $h['hospital_id']; ?>" <?php echo $hospital_filter == $h['hospital_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['hospital_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="padding: 0.6rem 1.2rem;">
                    <i class="fas fa-search mr-1"></i>Search
                </button>
                <a href="hospital_admins.php" class="btn-secondary">
                    <i class="fas fa-undo"></i>
                </a>
            </form>
        </div>

        <!-- Admin List -->
        <div class="content-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Hospital</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Role</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="table-row">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(168, 85, 247, 0.1); display: flex; align-items: center; justify-content: center; color: #a855f7; font-weight: 700; font-size: 0.8rem;">
                                                <?php echo strtoupper(substr($row['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                <div style="font-size: 0.75rem; color: #94a3b8;">ID: <?php echo $row['admin_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;"><?php echo htmlspecialchars($row['hospital_name'] ?? 'N/A'); ?></td>
                                    <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;"><?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="role-badge">
                                            <?php echo $row['role'] ?? 'HospitalAdmin'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                            <!-- View Button - Opens view_hospital_admin.php -->
                                            <a href="view_hospital_admin.php?id=<?php echo $row['admin_id']; ?>" class="action-btn blue" title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href="edit_hospital_admin.php?id=<?php echo $row['admin_id']; ?>" class="action-btn green" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button onclick="deleteAdmin(<?php echo $row['admin_id']; ?>, '<?php echo addslashes($row['full_name']); ?>')" class="action-btn red" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-user-shield" style="font-size: 3rem; display: block; margin-bottom: 1rem; color: #2a2a2a;"></i>
                                    No hospital admins found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function deleteAdmin(adminId, adminName) {
        if (confirm('Are you sure you want to delete "' + adminName + '"?')) {
            window.location.href = 'hospital_admins.php?delete=1&id=' + adminId;
        }
    }

   
    </script>
</body>
</html>