<?php
include '../config/permission.php';

$page_title = 'Staff Management';
$page_subtitle = 'Manage hospital staff members and their assigned roles';

$theme = $_SESSION['theme'] ?? 'light';

// Get all hospitals for filter
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'";
$hospitals_result = mysqli_query($conn, $hospitals_query);

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$hospital_filter = isset($_GET['hospital']) ? mysqli_real_escape_string($conn, $_GET['hospital']) : '';

// Handle Status Toggle
if (isset($_POST['toggle_status']) && isset($_POST['staff_id'])) {
    $staff_id = (int)$_POST['staff_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $update_query = "UPDATE staff SET status = '$new_status' WHERE staff_id = $staff_id";
    if (mysqli_query($conn, $update_query)) {
        logAudit('Staff', "Updated status of Staff ID $staff_id to $new_status");
        $success = "Staff status updated successfully!";
    } else {
        $error = "Update Error : " . mysqli_error($conn);
    }
}

// Get all staff
$where = "s.delete_flag = 0";
if ($search) {
    $where .= " AND (s.name LIKE '%$search%' OR s.email LIKE '%$search%' OR s.role LIKE '%$search%')";
}
if ($hospital_filter) {
    $where .= " AND s.hospital_id = '$hospital_filter'";
}

$query = "SELECT s.*, h.hospital_name 
          FROM staff s 
          LEFT JOIN hospital_master h ON s.hospital_id = h.hospital_id 
          WHERE $where 
          ORDER BY s.staff_id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Super Admin</title>
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(34, 197, 94, 0.5); }
        
        .btn-secondary {
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        
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
            padding: 1rem;
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
        .status-active { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .status-inactive { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .staff-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(59, 130, 246, 0.1);
        }

        .role-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .success-msg { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .error-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }

        .main-content { margin-left: 18%; margin-top: 2%; padding: 2rem; }
        @media(max-width: 768px) { .main-content { margin-left: 0 !important; padding: 1rem; } }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
        }
        .page-header .subtitle {
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .page-header .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
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

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <a href="dashboard.php" class="btn-secondary" style="padding: 0.5rem 0.8rem;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1><i class="fas fa-users-cog text-blue-500 mr-2"></i> Staff Management</h1>
                        <p class="subtitle">Manage hospital staff members and their assigned roles</p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <!-- Add Staff Button -->
                <a href="add_staff.php" class="btn-success">
                    <i class="fas fa-plus"></i> Add Staff
                </a>
            </div>
        </div>
       
       <?php if (!empty($success)): ?>
            <div class="success-msg"><i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" name="search" placeholder="Search by name, email, or role..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
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
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="staff.php" class="btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </form>
        </div>

        <!-- Staff List -->
        <div class="content-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span style="font-weight: 600; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                        <i class="fas fa-users mr-2"></i> Total Staff: <?php echo mysqli_num_rows($result); ?>
                    </span>
                </div>
                <div style="font-size: 0.8rem; color: #94a3b8;">
                    <i class="fas fa-sync-alt mr-1"></i> Live updates
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Contact Details</th>
                            <th>Role / Designation</th>
                            <th>Hospital</th>
                            <th>Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="table-row">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?php if($row['profile_image']): ?>
                                                <img src="../<?php echo htmlspecialchars($row['profile_image']); ?>" class="staff-img" alt="">
                                            <?php else: ?>
                                                <div class="staff-img flex items-center justify-center text-blue-500 font-bold bg-blue-50">
                                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 600; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: #94a3b8;">ID: <?php echo $row['staff_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                                            <i class="fas fa-envelope mr-1 text-slate-400"></i> <?php echo htmlspecialchars($row['email']); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #94a3b8;">
                                            <i class="fas fa-phone mr-1 text-slate-400"></i> <?php echo htmlspecialchars($row['mobile']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $row['role'])); ?>
                                        </span>
                                    </td>
                                    <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($row['hospital_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['status']) == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                            <!-- View Button -->
                                            <a href="view_staff.php?id=<?php echo $row['staff_id']; ?>" 
                                               class="btn-secondary" 
                                               style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" 
                                               title="View Staff">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href="edit_staff.php?id=<?php echo $row['staff_id']; ?>" 
                                               class="btn-primary" 
                                               style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" 
                                               title="Edit Staff">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <a href="delete_staff.php?id=<?php echo $row['staff_id']; ?>" 
                                               class="btn-secondary" 
                                               style="padding: 0.3rem 0.6rem; font-size: 0.75rem; border-color: #ef4444; color: #ef4444;" 
                                               title="Delete Staff"
                                               onclick="return confirm('Are you sure you want to delete this staff member?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-users-cog mb-3" style="font-size: 2.5rem; opacity: 0.2;"></i>
                                    <p>No staff members found.</p>
                                    <a href="add_staff.php" class="btn-success" style="margin-top: 1rem; display: inline-flex;">
                                        <i class="fas fa-plus"></i> Add New Staff
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Footer Stats -->
            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8; flex-wrap: wrap; gap: 0.5rem;">
                <span>Showing <?php echo mysqli_num_rows($result); ?> staff member(s)</span>
                <span>Last updated: <?php echo date('d M Y, h:i A'); ?></span>
            </div>
        </div>
    </div>
</body>
</html>