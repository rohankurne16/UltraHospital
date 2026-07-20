<?php
include '../config/permission.php';

$page_title = 'Department Management';
$page_subtitle = 'Manage hospital departments and medical specialties';

$theme = $_SESSION['theme'] ?? 'light';

// Get all hospitals for filter
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'";
$hospitals_result = mysqli_query($conn, $hospitals_query);

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$hospital_filter = isset($_GET['hospital']) ? mysqli_real_escape_string($conn, $_GET['hospital']) : '';

// Handle Status Toggle
if (isset($_POST['toggle_status']) && isset($_POST['dept_id'])) {
    $dept_id = (int)$_POST['dept_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $update_query = "UPDATE department SET status = '$new_status' WHERE id = $dept_id";
    if (mysqli_query($conn, $update_query)) {
        logAudit('Department', "Updated status of Department ID $dept_id to $new_status");
        $success = "Department status updated successfully!";
    } else {
        $error = "Update Error : " . mysqli_error($conn);
    }
}

// Get all departments
$where = "d.delete_flag = 0";
if ($search) {
    $where .= " AND (d.department_name LIKE '%$search%' OR d.description LIKE '%$search%')";
}
if ($hospital_filter) {
    $where .= " AND d.hospital_id = '$hospital_filter'";
}

$query = "SELECT d.*, h.hospital_name 
          FROM department d 
          LEFT JOIN hospital_master h ON d.hospital_id = h.hospital_id 
          WHERE $where 
          ORDER BY d.id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - Super Admin</title>
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
            padding: 1.25rem 1rem;
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
        
        .dept-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            font-size: 1.2rem;
        }

        .success-msg { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .error-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }

        .main-content { margin-left: 18%; margin-top: 2%; padding: 2rem; }
        @media(max-width: 768px) { .main-content { margin-left: 0 !important; padding: 1rem; } }
    </style>
</head>
<body class="<?php echo $theme; ?>">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 class="text-2xl font-bold" style="color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;"><?php echo $page_title; ?></h1>
                <p style="color: #94a3b8; font-size: 0.9rem;"><?php echo $page_subtitle; ?></p>
            </div>
            <a href="add_department.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Department
            </a>
        </div>

        <?php if(isset($success)): ?>
            <div class="success-msg"><i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" name="search" placeholder="Search by department name or description..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
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
                <a href="departments.php" class="btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </form>
        </div>

        <!-- Departments List -->
        <div class="content-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="checkAll" class="rounded border-slate-300">
                            </th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Hospital</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="table-row">
                                    <td>
                                        <input type="checkbox" name="dept_ids[]" value="<?php echo $row['id']; ?>" class="rounded border-slate-300">
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="dept-icon">
                                                <i class="fas fa-hospital-user"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                                    <?php echo htmlspecialchars($row['department_name']); ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: #94a3b8;">ID: #<?php echo $row['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; max-width: 300px;">
                                            <?php echo htmlspecialchars($row['description'] ?: 'No description provided.'); ?>
                                        </div>
                                    </td>
                                    <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($row['hospital_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['status']) == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                            <a href="edit_department.php?id=<?php echo $row['id']; ?>" class="p-2 rounded-lg hover:bg-blue-50 text-blue-500 transition-all" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Change status for this department?');">
                                                <input type="hidden" name="dept_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo strtolower($row['status']) == 'active' ? 'Inactive' : 'Active'; ?>">
                                                <button type="submit" name="toggle_status" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-all" title="Toggle Status">
                                                    <i class="fas <?php echo strtolower($row['status']) == 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                </button>
                                            </form>
                                            <a href="delete_department.php?id=<?php echo $row['id']; ?>" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-all" title="Delete" onclick="return confirm('Are you sure you want to delete this department?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-layer-group mb-3" style="font-size: 2.5rem; opacity: 0.2;"></i>
                                    <p>No departments found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <select class="form-control" style="width: auto;">
                    <option value="">With selected:</option>
                    <option value="active">Set Active</option>
                    <option value="inactive">Set Inactive</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn-secondary" style="padding: 0.5rem 1rem;">Apply</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkAll').onclick = function() {
            var checkboxes = document.getElementsByName('dept_ids[]');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        }
    </script>
</body>
</html>
