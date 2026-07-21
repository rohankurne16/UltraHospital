<?php
include '../config/permission.php';

$page_title = 'User Management';
$page_subtitle = 'Manage all users and assign roles';

$theme = $_SESSION['theme'] ?? 'light';

// Get all roles for dropdown
$roles_query = "SELECT role_id, role_name FROM roles WHERE delete_flag = 0 ORDER BY role_name";
$roles_result = mysqli_query($conn, $roles_query);

// Get all hospitals for filter
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'";
$hospitals_result = mysqli_query($conn, $hospitals_query);

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$hospital_filter = isset($_GET['hospital']) ? mysqli_real_escape_string($conn, $_GET['hospital']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

if (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role_id'])) {

    $user_id = (int)$_POST['user_id'];
    $role_id = (int)$_POST['role_id'];

    // Get role name from roles table
    $role_query = "SELECT role_name
                   FROM roles
                   WHERE role_id = $role_id
                   AND (delete_flag = 0 OR delete_flag IS NULL)";

    $role_result = mysqli_query($conn, $role_query);

    if ($role_result && mysqli_num_rows($role_result) > 0) {

        $role_data = mysqli_fetch_assoc($role_result);
        $role_name = mysqli_real_escape_string($conn, $role_data['role_name']);

        // Update both role_id and role
        $update_query = "UPDATE register
                         SET role_id = '$role_id',
                             role = '$role_name'
                         WHERE id = '$user_id'";

        if (mysqli_query($conn, $update_query)) {

            logAudit(
                'User',
                "Updated role of User ID $user_id to $role_name"
            );

            $success = "User role updated successfully!";

        } else {

            $error = "Update Error : " . mysqli_error($conn);

        }

    } else {

        $error = "Selected role not found.";

    }
}

// Get all users
$where = "r.delete_flag = 0 AND r.role != 'SuperAdmin'";
if ($search) {
    $where .= " AND (r.name LIKE '%$search%' OR r.email LIKE '%$search%' OR h.hospital_name LIKE '%$search%')";
}
if ($hospital_filter) {
    $where .= " AND r.hospital_id = '$hospital_filter'";
}
if ($role_filter) {
    $where .= " AND r.role_id = '$role_filter'";
}

$query = "SELECT
            r.*,
            h.hospital_name,
            roles.role_name AS role_display_name,
            ap.profile_image AS admin_image,
            d.doctor_image AS doctor_image,
            s.profile_image AS staff_image,
            p.patient_image AS patient_image
          FROM register r
          LEFT JOIN hospital_master h
                ON r.hospital_id = h.hospital_id
          LEFT JOIN roles
                ON r.role_id = roles.role_id
          LEFT JOIN admin_profile ap
                ON r.id = ap.register_id
          LEFT JOIN doctor d
                ON r.id = d.register_id
          LEFT JOIN staff s
                ON r.id = s.register_id
          LEFT JOIN patients p
                ON r.id = p.register_id
          WHERE $where
          ORDER BY r.id DESC";

$result = mysqli_query($conn, $query);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        body.light { background: #f1f5f9; }
        body.dark { background: #0a0a0a; }
   
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
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(34, 197, 94, 0.5); }
        .btn-secondary {
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        
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
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .empty-state { padding: 3rem; text-align: center; color: #94a3b8; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; color: #2a2a2a; }
        
      
        
        .role-select { padding: 0.3rem 0.5rem; border-radius: 8px; font-size: 0.8rem; }
        
        /* Main Content Layout Fix */
        .main-content {
            margin-left: 280px;
            margin-top: 72px;
            padding: 20px 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 80px;
        }
        
        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
            color: white;
        }
        
        .page-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        @media(max-width:768px){
            .main-content {
                margin-left: 0;
                margin-top: 65px;
                padding: 15px;
            }
            
            .main-content.sidebar-collapsed {
                margin-left: 0;
            }
            
            .back-btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
        
        @media(max-width:480px){
            .main-content {
                padding: 12px;
            }
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
    
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        
        <!-- Back Button -->
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <!-- Success/Error Messages -->
      
        
        <!-- Filters -->
        <div class="content-card" style="margin-bottom:1.5rem;">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
                <div style="flex:1;min-width:180px;">
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                </div>
                <div style="width:180px;">
                    <select name="hospital" class="form-control">
                        <option value="">All Hospitals</option>
                        <?php 
                        // Reset hospitals result pointer
                        mysqli_data_seek($hospitals_result, 0);
                        while($h = mysqli_fetch_assoc($hospitals_result)): ?>
                            <option value="<?php echo $h['hospital_id']; ?>" <?php echo $hospital_filter == $h['hospital_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['hospital_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="width:150px;">
                    <select name="role" class="form-control">
                        <option value="">All Roles</option>
                        <?php 
                        // Reset roles result pointer
                        mysqli_data_seek($roles_result, 0);
                        while($role = mysqli_fetch_assoc($roles_result)): ?>
                            <option value="<?php echo $role['role_id']; ?>" <?php echo $role_filter == $role['role_id'] ? 'selected' : ''; ?>>
                                <?php echo $role['role_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="padding:0.6rem 1.2rem;">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="users.php" class="btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </form>
        </div>

        <!-- Users List -->
        <div class="content-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Hospital</th>
                            <th>Current Role</th>
                            <th>Assign Role</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="table-row">
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <!-- Avatar -->
                                            <div style="width:40px;height:40px;border-radius:50%;overflow:hidden;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;flex-shrink:0;">
                                                <?php
                                                $image = '';
                                                if (!empty($row['admin_image'])) {
                                                    $image = $row['admin_image'];
                                                } elseif (!empty($row['doctor_image'])) {
                                                    $image = $row['doctor_image'];
                                                } elseif (!empty($row['staff_image'])) {
                                                    $image = $row['staff_image'];
                                                } elseif (!empty($row['patient_image'])) {
                                                    $image = $row['patient_image'];
                                                }

                                                if ($image != '') { ?>
                                                    <img src="../<?php echo $image; ?>" style="width:100%;height:100%;object-fit:cover;">
                                                <?php } else {
                                                    $words = explode(' ', trim($row['name']));
                                                    $initials = strtoupper(substr($words[0], 0, 1));
                                                    if (count($words) > 1) {
                                                        $initials .= strtoupper(substr(end($words), 0, 1));
                                                    }
                                                    echo $initials;
                                                } ?>
                                            </div>
                                            <!-- Name and ID -->
                                            <div>
                                                <div style="font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:#94a3b8;">ID: <?php echo $row['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                    </td>
                                    <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;">
                                        <?php echo htmlspecialchars($row['hospital_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php if ($row['role_display_name']): ?>
                                            <span class="role-badge" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                                                <?php echo htmlspecialchars($row['role_display_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="role-badge" style="background:rgba(239,68,68,0.1);color:#ef4444;">
                                                No Role Assigned
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:flex;gap:0.3rem;align-items:center;flex-wrap:wrap;">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <select name="role_id" class="role-select" style="padding:0.3rem 0.5rem;border-radius:8px;font-size:0.8rem;background:<?php echo $theme == 'dark' ? '#1e1e1e' : '#f8fafc'; ?>;border:1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                                <option value="">Select Role</option>
                                                <?php 
                                                    $role_query = "SELECT role_id, role_name FROM roles WHERE delete_flag = 0 ORDER BY role_name";
                                                    $role_result = mysqli_query($conn, $role_query);
                                                    while($role = mysqli_fetch_assoc($role_result)):
                                                ?>
                                                    <option value="<?php echo $role['role_id']; ?>" <?php echo $row['role_id'] == $role['role_id'] ? 'selected' : ''; ?>>
                                                        <?php echo $role['role_name']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <button type="submit" name="update_role" class="btn-success">
                                                <i class="fas fa-save"></i> Assign
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn-secondary" style="padding:0.3rem 0.8rem;font-size:0.8rem;display:inline-flex;align-items:center;gap:4px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    No users found
                                    <?php if ($search || $hospital_filter || $role_filter): ?>
                                        <br><small>Try adjusting your search filters</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Box -->
        <div style="margin-top:1rem;padding:1rem;border-radius:10px;background:rgba(59,130,246,0.05);border:1px solid rgba(59,130,246,0.1);">
            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <i class="fas fa-info-circle" style="color:#3b82f6;font-size:1.2rem;"></i>
                <div>
                    <span style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;">
                        <strong>How it works:</strong> Select a role from the dropdown and click <strong>"Assign"</strong> to give that role to the user. 
                        The user will then have all permissions assigned to that role.
                    </span>
                </div>
            </div>
        </div>
        
    </div>
</div>

</body>
</html>