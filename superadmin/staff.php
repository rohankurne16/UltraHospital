<?php
include '../config/permission.php';

$page_title = 'Staff Management';
$page_subtitle = 'Manage hospital staff members and their assigned roles';

$theme = $_SESSION['theme'] ?? 'light';

// ---------- SESSION MESSAGES ----------
$success = $_SESSION['staff_success'] ?? '';
$error   = $_SESSION['staff_error'] ?? '';
unset($_SESSION['staff_success'], $_SESSION['staff_error']);

// ---------- HANDLE DELETE ----------
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id > 0) {
        $check = mysqli_query($conn, "SELECT staff_id FROM staff WHERE staff_id = $delete_id AND delete_flag = 0");
        if (mysqli_num_rows($check) > 0) {
            $update = "UPDATE staff SET delete_flag = 1 WHERE staff_id = $delete_id";
            if (mysqli_query($conn, $update)) {
                logAudit('Staff', "Deleted Staff ID $delete_id");
                $_SESSION['staff_success'] = 'Staff member deleted successfully.';
            } else {
                $_SESSION['staff_error'] = 'Error deleting staff: ' . mysqli_error($conn);
            }
        } else {
            $_SESSION['staff_error'] = 'Staff not found or already deleted.';
        }
    } else {
        $_SESSION['staff_error'] = 'Invalid staff ID.';
    }
    header('Location: staff.php');
    exit;
}

// ---------- HANDLE EDIT (POST) ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_staff_id'])) {
    $staff_id = (int)$_POST['edit_staff_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $errors = [];
    if (empty($name)) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', preg_replace('/[\s\-+]/', '', $mobile))) {
        $errors[] = "Mobile must be exactly 10 digits.";
    }
    if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";

    if (empty($errors)) {
        $update = "UPDATE staff SET 
                    name = '$name',
                    mobile = '$mobile',
                    email = '$email',
                    role = '$role',
                    address = '$address',
                    status = '$status'
                  WHERE staff_id = $staff_id AND delete_flag = 0";
        if (mysqli_query($conn, $update)) {
            logAudit('Staff', "Updated Staff ID $staff_id");
            $_SESSION['staff_success'] = 'Staff details updated successfully.';
            header('Location: staff.php');
            exit;
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// ---------- FETCH STAFF FOR EDIT (GET) ----------
$edit_staff = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    if ($edit_id > 0) {
        $q = "SELECT * FROM staff WHERE staff_id = $edit_id AND delete_flag = 0";
        $res = mysqli_query($conn, $q);
        if (mysqli_num_rows($res) > 0) {
            $edit_staff = mysqli_fetch_assoc($res);
        } else {
            $_SESSION['staff_error'] = 'Staff not found.';
            header('Location: staff.php');
            exit;
        }
    } else {
        header('Location: staff.php');
        exit;
    }
}

// ---------- FETCH HOSPITALS FOR FILTER ----------
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'";
$hospitals_result = mysqli_query($conn, $hospitals_query);

// ---------- FILTERS ----------
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$hospital_filter = isset($_GET['hospital']) ? mysqli_real_escape_string($conn, $_GET['hospital']) : '';

// ---------- HANDLE STATUS TOGGLE (unchanged) ----------
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

// ---------- BUILD MAIN QUERY ----------
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

// Staff roles (for edit form)
$staff_roles = ["Receptionist", "Nurse", "Ward_boy", "Lab Technician"];
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

        
        .main-content {
            margin-left: 250px; 
            padding: 1.5rem; 
            min-height: 100vh;
            margin-top: 70px;
        }
        @media(max-width: 768px) { .main-content { margin-left: 0 !important; padding: 1rem; } }

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
                <a href="add_staff.php">
                    <i class="fas fa-plus"></i> Add Staff
                </a>
            </div>
        </div>
       
      

        <!-- Filters (always shown) -->
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" name="search" placeholder="Search by name, email, or role..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                </div>
                <div style="width: 200px;">
                    <select name="hospital" class="form-control">
                        <option value="">All Hospitals</option>
                        <?php mysqli_data_seek($hospitals_result, 0); while($h = mysqli_fetch_assoc($hospitals_result)): ?>
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

        <!-- ============================================================ -->
        <!-- EDIT FORM (shown when edit_id is set)                        -->
        <!-- ============================================================ -->
        <?php if ($edit_staff): ?>
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-weight: 600; color: <?php echo $theme=='dark'?'#f1f5f9':'#1e293b'; ?>;">
                    <i class="fas fa-user-edit text-blue-500 mr-2"></i> Edit Staff: <?php echo htmlspecialchars($edit_staff['name']); ?>
                </h3>
                <a href="staff.php" class="btn-secondary" style="padding:0.3rem 1rem;">Cancel</a>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="edit_staff_id" value="<?php echo $edit_staff['staff_id']; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium <?php echo $theme=='dark'?'text-gray-300':'text-gray-700'; ?>">Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_staff['name']); ?>" class="form-control" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium <?php echo $theme=='dark'?'text-gray-300':'text-gray-700'; ?>">Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($edit_staff['email']); ?>" class="form-control" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium <?php echo $theme=='dark'?'text-gray-300':'text-gray-700'; ?>">Mobile</label>
                        <input type="text" name="mobile" value="<?php echo htmlspecialchars($edit_staff['mobile']); ?>" class="form-control" pattern="[0-9]{10}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium <?php echo $theme=='dark'?'text-gray-300':'text-gray-700'; ?>">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php foreach ($staff_roles as $role): ?>
                                <option value="<?php echo $role; ?>" <?php echo ($edit_staff['role'] == $role) ? 'selected' : ''; ?>>
                                    <?php echo $role; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium <?php echo $theme=='dark'?'text-gray-300':'text-gray-700'; ?>">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($edit_staff['address']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium <?php echo $theme=='dark'?'text-gray-300':'text-gray-700'; ?>">Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($edit_staff['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($edit_staff['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex gap-3">
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Staff</button>
                    <a href="staff.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- STAFF LIST (hidden when editing)                             -->
        <!-- ============================================================ -->
        <?php if (!$edit_staff): ?>
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
 
                                            <!-- Edit Button (now uses GET parameter) -->
                                            <a href="staff.php?edit_id=<?php echo $row['staff_id']; ?>" 
                                               class="btn-primary" 
                                               style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" 
                                               title="Edit Staff">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button (with confirmation) -->
                                            <a href="staff.php?delete_id=<?php echo $row['staff_id']; ?>" 
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
                                    <a href="add_staff.php" style="margin-top: 1rem; display: inline-flex;">
                                        <i class="fas fa-plus"></i> Add New Staff
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8; flex-wrap: wrap; gap: 0.5rem;">
                <span>Showing <?php echo mysqli_num_rows($result); ?> staff member(s)</span>
                <span>Last updated: <?php echo date('d M Y, h:i A'); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>












