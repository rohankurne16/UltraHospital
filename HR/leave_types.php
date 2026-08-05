<?php
session_start();
include '../config/hospital.php';

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['id'] ?? 0;

$allowed_roles = ['HR', 'Admin', 'Super Admin'];
if (!in_array($user_role, $allowed_roles)) {
    header('Location: ../dashboard.php');
    exit();
}

// ========== GET HOSPITAL DATA ==========
$hospitalData = [];
$hospStmt = $conn->prepare("SELECT * FROM hospital_master WHERE hospital_id = ? LIMIT 1");
if ($hospStmt) {
    $hospStmt->bind_param('i', $hospital_id);
    $hospStmt->execute();
    $hospResult = $hospStmt->get_result();
    if ($hospResult->num_rows > 0) {
        $hospitalData = $hospResult->fetch_assoc();
    }
    $hospStmt->close();
}
$hospital_name = $hospitalData['hospital_name'] ?? 'MedixPro';
$hospital_logo = $hospitalData['hospital_logo'] ?? '../documents/hospital/logo.png';

// ========== HANDLE DELETE ==========
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $id = intval($_GET['delete']);
    $check = $conn->query("SELECT id FROM leave_types WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE leave_types SET delete_flag = 1 WHERE id = $id");
        header('Location: leave_type.php?deleted=1');
        exit();
    }
}

// ========== HANDLE ADD/EDIT ==========
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM leave_types WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_code = trim($_POST['leave_code'] ?? '');
    $leave_name = trim($_POST['leave_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $days_per_year = floatval($_POST['days_per_year'] ?? 0);
    $is_paid = isset($_POST['is_paid']) ? 1 : 0;
    $carry_forward = isset($_POST['carry_forward']) ? 1 : 0;
    $max_carry_forward = floatval($_POST['max_carry_forward'] ?? 0);
    $status = $_POST['status'] ?? 'Active';

    if (empty($leave_code) || empty($leave_name)) {
        $message = 'Leave Code and Name are required.';
        $message_type = 'error';
    } else {
        if ($edit_id > 0) {
            // Update
            $sql = "UPDATE leave_types SET 
                    leave_code = '$leave_code',
                    leave_name = '$leave_name',
                    description = '$description',
                    days_per_year = $days_per_year,
                    is_paid = $is_paid,
                    carry_forward = $carry_forward,
                    max_carry_forward = $max_carry_forward,
                    status = '$status'
                    WHERE id = $edit_id AND hospital_id = $hospital_id";
            if ($conn->query($sql)) {
                $message = 'Leave type updated successfully.';
                $message_type = 'success';
                $edit_data = null;
                // Refresh edit data
                $res = $conn->query("SELECT * FROM leave_types WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
                if ($res && $res->num_rows > 0) {
                    $edit_data = $res->fetch_assoc();
                }
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        } else {
            // Insert
            $sql = "INSERT INTO leave_types (leave_code, leave_name, description, days_per_year, is_paid, carry_forward, max_carry_forward, status, hospital_id) 
                    VALUES ('$leave_code', '$leave_name', '$description', $days_per_year, $is_paid, $carry_forward, $max_carry_forward, '$status', $hospital_id)";
            if ($conn->query($sql)) {
                $message = 'Leave type added successfully.';
                $message_type = 'success';
                // Clear form
                $_POST = [];
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// ========== FETCH LEAVE TYPES ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["hospital_id = $hospital_id", "delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(leave_code LIKE '%$search%' OR leave_name LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where[] = "status = '$status_filter'";
}
$where_clause = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) as total FROM leave_types WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT * FROM leave_types WHERE $where_clause ORDER BY leave_name ASC LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$leave_types = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $leave_types[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Leave Types</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 0; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-warning:hover { background: #d97706; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .search-box { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .search-box input, .search-box select { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; min-width: 160px; }
        .search-box input:focus, .search-box select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.Active { background: #dcfce7; color: #166534; }
        .status-badge.Inactive { background: #f1f5f9; color: #64748b; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #4b5563; font-size: 13px; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active { background: #3b82f6; color: white; border-color: #3b82f6; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .welcome-section { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; }
        .modal-close:hover { color: #1f2937; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        .grid-3col { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 1024px) { .grid-3col { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .grid-3col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <?php include '../header.php'; ?>
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-tags mr-3 text-white"></i> Leave Types</h1>
                        <p>Define and manage leave policies (Casual, Sick, Earned, Maternity, Emergency, etc.)</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-plus"></i> Add Leave Type
                    </button>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Leave Types List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> types</span>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search by code or name..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status_filter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="leave_type.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <?php if (!empty($leave_types)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Leave Name</th>
                                        <th>Days/Year</th>
                                        <th>Paid</th>
                                        <th>Carry Forward</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($leave_types as $lt): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($lt['leave_code']); ?></td>
                                            <td><?php echo htmlspecialchars($lt['leave_name']); ?></td>
                                            <td><?php echo number_format($lt['days_per_year'], 1); ?></td>
                                            <td><?php echo $lt['is_paid'] ? '<i class="fas fa-check text-green-500"></i>' : '<i class="fas fa-times text-red-500"></i>'; ?></td>
                                            <td><?php echo $lt['carry_forward'] ? 'Yes (max '.number_format($lt['max_carry_forward'], 1).')' : 'No'; ?></td>
                                            <td><span class="status-badge <?php echo $lt['status']; ?>"><?php echo $lt['status']; ?></span></td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="editLeaveType(<?php echo $lt['id']; ?>)" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteLeaveType(<?php echo $lt['id']; ?>)" class="btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <p class="text-lg font-medium text-gray-700">No leave types defined</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Leave Type" to create your first policy.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== ADD/EDIT MODAL ========== -->
<div class="modal" id="leaveTypeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle mr-2 text-blue-500"></i> Add Leave Type</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="leave_type.php" id="leaveTypeForm">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            <div class="form-row">
                <div class="form-group">
                    <label>Leave Code <span class="required">*</span></label>
                    <input type="text" name="leave_code" id="leave_code" class="form-control" placeholder="e.g. CL" required>
                </div>
                <div class="form-group">
                    <label>Leave Name <span class="required">*</span></label>
                    <input type="text" name="leave_name" id="leave_name" class="form-control" placeholder="e.g. Casual Leave" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="description" class="form-control" rows="2" placeholder="Brief description"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Days Per Year <span class="required">*</span></label>
                    <input type="number" name="days_per_year" id="days_per_year" class="form-control" step="0.5" min="0" value="12" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_paid" id="is_paid" value="1" checked> <span>Paid Leave</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="carry_forward" id="carry_forward" value="1"> <span>Carry Forward</span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Max Carry Forward (days)</label>
                <input type="number" name="max_carry_forward" id="max_carry_forward" class="form-control" step="0.5" min="0" value="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle mr-2 text-blue-500"></i> Add Leave Type';
    document.getElementById('edit_id').value = 0;
    document.getElementById('leave_code').value = '';
    document.getElementById('leave_name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('days_per_year').value = '12';
    document.getElementById('status').value = 'Active';
    document.getElementById('is_paid').checked = true;
    document.getElementById('carry_forward').checked = false;
    document.getElementById('max_carry_forward').value = '0';
    document.getElementById('leaveTypeModal').classList.add('show');
}

function editLeaveType(id) {
    fetch('leave_type_ajax.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-500"></i> Edit Leave Type';
                document.getElementById('edit_id').value = data.id;
                document.getElementById('leave_code').value = data.leave_code;
                document.getElementById('leave_name').value = data.leave_name;
                document.getElementById('description').value = data.description;
                document.getElementById('days_per_year').value = data.days_per_year;
                document.getElementById('status').value = data.status;
                document.getElementById('is_paid').checked = data.is_paid == 1;
                document.getElementById('carry_forward').checked = data.carry_forward == 1;
                document.getElementById('max_carry_forward').value = data.max_carry_forward;
                document.getElementById('leaveTypeModal').classList.add('show');
            } else {
                alert('Error loading leave type data.');
            }
        })
        .catch(() => alert('Network error.'));
}

function deleteLeaveType(id) {
    if (confirm('Are you sure you want to delete this leave type? This action cannot be undone.')) {
        window.location.href = 'leave_type.php?delete=' + id;
    }
}

function closeModal() {
    document.getElementById('leaveTypeModal').classList.remove('show');
}

// Close on outside click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>