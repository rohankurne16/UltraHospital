<?php
session_start(); // ensure session is started
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

// ========== HANDLE STATUS TOGGLE (POST) ==========
if (isset($_POST['toggle_status']) && isset($_POST['dept_id'])) {
    $dept_id = (int)$_POST['dept_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $update_query = "UPDATE department SET status = '$new_status' WHERE id = $dept_id";
    if (mysqli_query($conn, $update_query)) {
        logAudit('Department', "Updated status of Department ID $dept_id to $new_status");
        $_SESSION['success'] = "Department status updated successfully!";
    } else {
        $_SESSION['error'] = "Update Error : " . mysqli_error($conn);
    }
    header("Location: departments.php" . ($search ? "?search=" . urlencode($search) : "") . ($hospital_filter ? "&hospital=" . urlencode($hospital_filter) : ""));
    exit();
}

// ========== HANDLE ADD DEPARTMENT (POST) ==========
if (isset($_POST['add_department'])) {
    $dept_name = mysqli_real_escape_string($conn, trim($_POST['dept_name']));
    $dept_desc = mysqli_real_escape_string($conn, trim($_POST['dept_desc']));
    $hospital_id = (int)$_POST['hospital_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (!empty($dept_name) && $hospital_id > 0) {
        $insert = "INSERT INTO department (department_name, description, hospital_id, status, delete_flag) 
                   VALUES ('$dept_name', '$dept_desc', $hospital_id, '$status', 0)";
        if (mysqli_query($conn, $insert)) {
            logAudit('Department', "Added new department: $dept_name");
            $_SESSION['success'] = "Department added successfully!";
        } else {
            $_SESSION['error'] = "Insert Error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Department name and Hospital are required.";
    }
    // Redirect to prevent resubmission on refresh
    header("Location: departments.php" . ($search ? "?search=" . urlencode($search) : "") . ($hospital_filter ? "&hospital=" . urlencode($hospital_filter) : ""));
    exit();
}

// ========== HANDLE SOFT DELETE (NEW) ==========
if (isset($_POST['delete_department']) && isset($_POST['dept_id'])) {
    $dept_id = (int)$_POST['dept_id'];
    $update_query = "UPDATE department SET delete_flag = 1 WHERE id = $dept_id";
    if (mysqli_query($conn, $update_query)) {
        logAudit('Department', "Soft deleted Department ID $dept_id");
        $_SESSION['success'] = "Department deleted successfully!";
    } else {
        $_SESSION['error'] = "Delete Error : " . mysqli_error($conn);
    }
    // Redirect with filters
    header("Location: departments.php" . ($search ? "?search=" . urlencode($search) : "") . ($hospital_filter ? "&hospital=" . urlencode($hospital_filter) : ""));
    exit();
}

// ========== FETCH DEPARTMENTS ==========
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

// Reset hospital result pointer for modal dropdown
mysqli_data_seek($hospitals_result, 0);
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
        /* ... existing styles (unchanged) ... */
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

        .main-content {
    margin-left: 250px; 
    padding: 1.5rem; 
    min-height: 100vh;
    margin-top: 70px; /* Adjust to match your header height */
}
        @media(max-width: 768px) { .main-content { margin-left: 0 !important; padding: 1rem; } }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: <?php echo $theme == 'dark' ? '#1e1e1e' : '#ffffff'; ?>;
            border-radius: 16px;
            max-width: 520px;
            width: 95%;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
        }
        .modal-close:hover { color: #1e293b; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#334155'; ?>;
            margin-bottom: 0.3rem;
        }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#d1d5db'; ?>;
            border-radius: 8px;
            font-size: 0.9rem;
            background: <?php echo $theme == 'dark' ? '#1a1a1a' : '#ffffff'; ?>;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.8rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
        }
        .modal-footer .btn-primary { padding: 0.5rem 1.2rem; }
        .modal-footer .btn-secondary { padding: 0.5rem 1.2rem; }

        /* Delete button style */
        .btn-delete {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 0.3rem 0.5rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: rgba(239,68,68,0.1);
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

        <a href="dashboard.php" class="btn btn-primary" style="margin-bottom:2%;">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-msg"><i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Filters + Add Button -->
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
                <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; flex: 1;">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" placeholder="Search by department name or description..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    </div>
                    <div style="width: 200px;">
                        <select name="hospital" class="form-control">
                            <option value="">All Hospitals</option>
                            <?php 
                            mysqli_data_seek($hospitals_result, 0);
                            while($h = mysqli_fetch_assoc($hospitals_result)): ?>
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

                <!-- Add Department Button -->
                <button onclick="openAddModal()" class="btn-primary" style="flex-shrink: 0;">
                    <i class="fas fa-plus"></i> Add Department
                </button>
            </div>
        </div>

        <!-- Departments List -->
        <div class="content-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Hospital</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th> <!-- NEW COLUMN -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="table-row">
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
                                    <td style="text-align:right;">
                                        <!-- DELETE BUTTON (NEW) -->
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to soft delete this department? This can be restored later.')">
                                            <input type="hidden" name="delete_department" value="1">
                                            <input type="hidden" name="dept_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn-delete" title="Delete Department">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-layer-group mb-3" style="font-size: 2.5rem; opacity: 0.2;"></i>
                                    <p>No departments found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================
    ADD DEPARTMENT MODAL
    ============================================================ -->
    <div class="modal-overlay" id="addDeptModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle" style="color:#3b82f6;margin-right:0.5rem;"></i> Add Department</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="departments.php" id="addDeptForm">
                <input type="hidden" name="add_department" value="1">

                <div class="form-group">
                    <label>Department Name <span class="required">*</span></label>
                    <input type="text" name="dept_name" required placeholder="e.g., Cardiology">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="dept_desc" rows="2" placeholder="Brief description"></textarea>
                </div>

                <div class="form-group">
                    <label>Hospital <span class="required">*</span></label>
                    <select name="hospital_id" required>
                        <option value="">Select Hospital</option>
                        <?php 
                        mysqli_data_seek($hospitals_result, 0);
                        while($h = mysqli_fetch_assoc($hospitals_result)): ?>
                            <option value="<?php echo $h['hospital_id']; ?>"><?php echo htmlspecialchars($h['hospital_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addDeptModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addDeptModal').classList.remove('active');
        }

        document.getElementById('addDeptModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });

        document.getElementById('checkAll').onclick = function() {
            var checkboxes = document.getElementsByName('dept_ids[]');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        }
    </script>
</body>
</html>