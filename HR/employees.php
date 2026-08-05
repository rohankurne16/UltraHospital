<?php
session_start();
include '../config/hospital.php';

// ========== CHECK SESSION & HR ACCESS ==========
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
$admin_name = $_SESSION['full_name'] ?? 'HR';

// ========== PAGE VARIABLES ==========
$page_title = "Employee Master";

// ========== GET ALL EMPLOYEES ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? intval($_GET['department']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where_conditions = ["e.hospital_id = $hospital_id", "e.delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.employee_code LIKE '%$search%' OR e.email LIKE '%$search%' OR e.mobile LIKE '%$search%')";
}
if ($department_filter > 0) {
    $where_conditions[] = "e.department_id = $department_filter";
}
if (!empty($status_filter)) {
    $where_conditions[] = "e.status = '$status_filter'";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total employees
$count_sql = "SELECT COUNT(*) as total FROM employees e WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

// Get employees with pagination
$sql = "SELECT e.*, 
        d.department_name,
        des.designation_name,
        (SELECT COUNT(*) FROM employee_documents WHERE employee_id = e.employee_id AND delete_flag = 0) as document_count
        FROM employees e
        LEFT JOIN department d ON e.department_id = d.id
        LEFT JOIN designations des ON e.designation_id = des.id
        WHERE $where_clause
        ORDER BY e.employee_id DESC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$employees = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// ========== GET DEPARTMENTS FOR FILTER ==========
$departments = [];
$dept_sql = "SELECT id, department_name FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_sql);
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ========== HELPER FUNCTIONS ==========
function getStatusBadge($status) {
    $colors = [
        'Active' => 'success',
        'Inactive' => 'secondary',
        'On Leave' => 'warning',
        'Terminated' => 'danger'
    ];
    $color = $colors[$status] ?? 'secondary';
    return "<span class='status-badge $color'>$status</span>";
}

function getGenderIcon($gender) {
    if ($gender == 'Male') return '<i class="fas fa-mars text-blue-500"></i>';
    if ($gender == 'Female') return '<i class="fas fa-venus text-pink-500"></i>';
    return '<i class="fas fa-genderless text-gray-500"></i>';
}

function getAvatar($employee) {
    if (!empty($employee['profile_image']) && file_exists($employee['profile_image'])) {
        return '<img src="' . htmlspecialchars($employee['profile_image']) . '" alt="Profile" class="avatar-img">';
    }
    $name = ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '');
    $initial = strtoupper(substr(trim($name), 0, 1) ?: '?');
    return '<div class="avatar-initial">' . $initial . '</div>';
}

function formatDate($date) {
    if (empty($date)) return '—';
    return date('d M Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Employee Master</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        
        .main-content {
            width: 100%;
            margin-left: 0;
            padding: 20px 28px;
            min-height: 100vh;
        }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: #2563eb; }
        
        .btn-success {
            background: #22c55e;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-success:hover { background: #16a34a; }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-danger:hover { background: #dc2626; }
        
        .btn-outline {
            background: transparent;
            color: #6b7280;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-outline:hover { background: #f3f4f6; }
        
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th {
            padding: 10px 16px;
            text-align: left;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        td {
            padding: 10px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            vertical-align: middle;
        }
        tr:hover { background: #f8fafc; }
        
        .status-badge {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.success { background: #dcfce7; color: #166534; }
        .status-badge.danger { background: #fecaca; color: #991b1b; }
        .status-badge.warning { background: #fef3c7; color: #92400e; }
        .status-badge.secondary { background: #f1f5f9; color: #64748b; }
        
        .avatar-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-initial {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .name-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .name-cell .name-text { font-weight: 600; color: #0f172a; }
        .name-cell .emp-code { font-size: 11px; color: #94a3b8; }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-box input, .search-box select {
            padding: 8px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            min-width: 180px;
        }
        .search-box input:focus, .search-box select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #4b5563;
            font-size: 13px;
        }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 12px;
        }
        
        .badge-count {
            background: #e5e7eb;
            color: #4b5563;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 13px; margin-top: 2px; }
        
        @media (max-width: 768px) {
            .search-box { flex-direction: column; align-items: stretch; }
            .search-box input, .search-box select { min-width: auto; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ========== INCLUDE HR SIDEBAR ========== -->
<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <!-- ========== INCLUDE HEADER ========== -->
    <?php include '../header.php'; ?>
    
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-user-tie mr-3 text-white"></i> Employee Master</h1>
                        <p>Manage all employee records, view, edit, and update employee information</p>
                    </div>
                    <a href="add_employee.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-user-plus"></i> Add Employee
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <?php
            $stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'On Leave' THEN 1 ELSE 0 END) as on_leave
                FROM employees WHERE hospital_id = $hospital_id AND delete_flag = 0";
            $stats_result = $conn->query($stats_sql);
            $stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'active' => 0, 'inactive' => 0, 'on_leave' => 0];
            ?>
            <div class="stat-grid">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-number text-blue-600"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-number text-green-600"><?php echo number_format($stats['active']); ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div class="stat-number text-yellow-600"><?php echo number_format($stats['on_leave']); ?></div>
                    <div class="stat-label">On Leave</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #ef4444;">
                    <div class="stat-number text-red-600"><?php echo number_format($stats['inactive']); ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>

            <!-- Employee List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Employee List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> employees</span>
                </div>
                <div class="card-body">
                    <!-- Search & Filter -->
                    <form method="GET" class="search-box mb-4">
                        <input type="text" name="search" placeholder="Search by name, code, email..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="department">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($department_filter == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status_filter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="On Leave" <?php echo ($status_filter == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                            <option value="Terminated" <?php echo ($status_filter == 'Terminated') ? 'selected' : ''; ?>>Terminated</option>
                        </select>
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="employees.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <!-- Table -->
                    <?php if (!empty($employees)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td>
                                                <div class="name-cell">
                                                    <?php echo getAvatar($emp); ?>
                                                    <div>
                                                        <div class="name-text">
                                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                                            <?php echo getGenderIcon($emp['gender']); ?>
                                                        </div>
                                                        <div class="emp-code"><?php echo htmlspecialchars($emp['employee_code']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['designation_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="text-sm">
                                                    <div><i class="fas fa-phone text-gray-400 text-xs"></i> <?php echo htmlspecialchars($emp['mobile']); ?></div>
                                                    <div class="text-xs text-gray-500"><i class="fas fa-envelope text-gray-400 text-xs"></i> <?php echo htmlspecialchars($emp['email']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo getStatusBadge($emp['status']); ?></td>
                                            <td>
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    <a href="view_employee.php?id=<?php echo $emp['employee_id']; ?>" class="btn-outline btn-xs" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_employee.php?id=<?php echo $emp['employee_id']; ?>" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="employee_documents.php?id=<?php echo $emp['employee_id']; ?>" class="btn-success btn-xs" title="Documents">
                                                        <i class="fas fa-file-alt"></i>
                                                        <?php if ($emp['document_count'] > 0): ?>
                                                            <span style="background: rgba(255,255,255,0.3); padding: 0 4px; border-radius: 4px; font-size: 9px;"><?php echo $emp['document_count']; ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                    <button onclick="deleteEmployee(<?php echo $emp['employee_id']; ?>)" class="btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&status=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p class="text-lg font-medium text-gray-700">No employees found</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Employee" to create your first employee record</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// ========== DELETE EMPLOYEE ==========
function deleteEmployee(id) {
    if (!confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
        return;
    }
    
    if (!confirm('This will permanently delete all data including documents. Confirm again?')) {
        return;
    }
    
    fetch('delete_employee.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Employee deleted successfully!');
            location.reload();
        } else {
            alert(data.message || 'Error deleting employee');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}
</script>

</body>
</html>