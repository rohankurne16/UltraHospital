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

// ========== PAGE VARIABLES ==========
$page_title = "Department Management";
$admin_name = $_SESSION['full_name'] ?? 'HR';

// ========== GET ALL DEPARTMENTS ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where_conditions = ["hospital_id = $hospital_id", "delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(department_name LIKE '%$search%' OR department_code LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where_conditions[] = "status = '$status_filter'";
}
$where_clause = implode(" AND ", $where_conditions);

// Count total departments
$count_sql = "SELECT COUNT(*) as total FROM department WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

// Get departments with pagination
$sql = "SELECT d.*, 
        CONCAT(dr.doctor_name, ' (', dr.doctor_id, ')') as hod_name
        FROM department d
        LEFT JOIN doctor dr ON d.hod_id = dr.doctor_id
        WHERE $where_clause
        ORDER BY d.department_name ASC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$departments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ========== GET DOCTORS FOR HOD DROPDOWN ==========
$doctors = [];
$doc_sql = "SELECT doctor_id, doctor_name FROM doctor WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY doctor_name";
$doc_result = $conn->query($doc_sql);
if ($doc_result) {
    while ($row = $doc_result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// ========== STATISTICS ==========
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive
    FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'active' => 0, 'inactive' => 0];

// ========== HELPER FUNCTIONS ==========
function getStatusBadge($status) {
    if ($status == 'Active') {
        return '<span class="status-badge success"><i class="fas fa-check-circle"></i> Active</span>';
    }
    return '<span class="status-badge secondary"><i class="fas fa-circle"></i> Inactive</span>';
}

function getDepartmentIcon($name) {
    $icons = [
        'HR' => 'fa-user-tie',
        'Human Resources' => 'fa-user-tie',
        'Medical' => 'fa-user-md',
        'Doctor' => 'fa-user-md',
        'Nursing' => 'fa-user-nurse',
        'Nurse' => 'fa-user-nurse',
        'Reception' => 'fa-headset',
        'Laboratory' => 'fa-flask',
        'Lab' => 'fa-flask',
        'Pharmacy' => 'fa-pills',
        'Billing' => 'fa-file-invoice-dollar',
        'Accounts' => 'fa-calculator',
        'Operation Theater' => 'fa-scalpel',
        'OT' => 'fa-scalpel',
        'Radiology' => 'fa-x-ray',
        'Cardiology' => 'fa-heart',
        'Neurology' => 'fa-brain',
        'Orthopedics' => 'fa-bone',
        'Pediatrics' => 'fa-child',
        'Gynecology' => 'fa-female'
    ];
    $icon = $icons[$name] ?? 'fa-building';
    return $icon;
}

function getDepartmentColor($name) {
    $colors = [
        'HR' => '#7c3aed',
        'Medical' => '#3b82f6',
        'Nursing' => '#22c55e',
        'Reception' => '#f59e0b',
        'Laboratory' => '#8b5cf6',
        'Pharmacy' => '#ec4899',
        'Billing' => '#06b6d4',
        'Accounts' => '#f97316',
        'Operation Theater' => '#ef4444',
        'Radiology' => '#6366f1'
    ];
    return $colors[$name] ?? '#64748b';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Department Management</title>
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
        @media (max-width: 1024px) {
            .main-content { padding: 16px; }
        }
        
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
        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
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
        
        .btn-warning {
            background: #f59e0b;
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
        .btn-warning:hover { background: #d97706; }
        
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
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .status-badge.success { background: #dcfce7; color: #166534; }
        .status-badge.secondary { background: #f1f5f9; color: #64748b; }
        .status-badge.danger { background: #fecaca; color: #991b1b; }
        .status-badge.warning { background: #fef3c7; color: #92400e; }
        
        .dept-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }
        
        .dept-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .dept-cell .dept-name {
            font-weight: 600;
            color: #0f172a;
        }
        .dept-cell .dept-code {
            font-size: 11px;
            color: #94a3b8;
            font-family: monospace;
        }
        
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
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
        }
        .stat-card .stat-label {
            color: #6b7280;
            font-size: 13px;
            margin-top: 2px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
        }
        .modal-close:hover { color: #1f2937; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }
        .form-group .required { color: #ef4444; }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        textarea.form-control { resize: vertical; min-height: 80px; }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        .toast.success { background: #22c55e; }
        .toast.error { background: #ef4444; }
        .toast.info { background: #3b82f6; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast .close-toast {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-left: 12px;
            opacity: 0.7;
        }
        .toast .close-toast:hover { opacity: 1; }
        
        @media (max-width: 768px) {
            .search-box { flex-direction: column; align-items: stretch; }
            .search-box input, .search-box select { min-width: auto; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stat-grid { grid-template-columns: 1fr; }
        }
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
                        <h1><i class="fas fa-building mr-3 text-white"></i> Department Management</h1>
                        <p>Manage departments, assign HODs, and configure department settings</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="stat-grid">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-number text-blue-600"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Departments</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-number text-green-600"><?php echo number_format($stats['active']); ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #94a3b8;">
                    <div class="stat-number text-gray-500"><?php echo number_format($stats['inactive']); ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>

            <!-- Department List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Departments List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> departments</span>
                </div>
                <div class="card-body">
                    <!-- Search & Filter -->
                    <form method="GET" class="search-box mb-4">
                        <input type="text" name="search" placeholder="Search by name or code..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status_filter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="departments.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <!-- Table -->
                    <?php if (!empty($departments)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Department</th>
                                        <th>Code</th>
                                        <th>HOD</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <?php 
                                        $icon = getDepartmentIcon($dept['department_name']);
                                        $color = getDepartmentColor($dept['department_name']);
                                        ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td>
                                                <div class="dept-cell">
                                                    <div class="dept-icon" style="background: <?php echo $color; ?>">
                                                        <i class="fas <?php echo $icon; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <div class="dept-name"><?php echo htmlspecialchars($dept['department_name']); ?></div>
                                                        <div class="dept-code"><?php echo htmlspecialchars($dept['department_code']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="dept-code"><?php echo htmlspecialchars($dept['department_code']); ?></span></td>
                                            <td>
                                                <?php if (!empty($dept['hod_name'])): ?>
                                                    <span class="text-sm"><?php echo htmlspecialchars($dept['hod_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-sm text-gray-400">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($dept['description'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($dept['description'] ?? '—'); ?>
                                                </div>
                                            </td>
                                            <td><?php echo getStatusBadge($dept['status']); ?></td>
                                            <td>
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    <button onclick="viewDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)" class="btn-outline btn-xs" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="editDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="toggleDepartmentStatus(<?php echo $dept['id']; ?>, '<?php echo $dept['status']; ?>')" 
                                                            class="<?php echo ($dept['status'] == 'Active') ? 'btn-warning' : 'btn-success'; ?> btn-xs" 
                                                            title="<?php echo ($dept['status'] == 'Active') ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas <?php echo ($dept['status'] == 'Active') ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                    </button>
                                                    <button onclick="deleteDepartment(<?php echo $dept['id']; ?>)" class="btn-danger btn-xs" title="Delete">
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
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <p class="text-lg font-medium text-gray-700">No departments found</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Department" to create your first department</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== ADD/EDIT MODAL ========== -->
<div class="modal" id="departmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus mr-2 text-blue-500"></i> Add Department</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="departmentForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="department_id" id="department_id" value="">
            
            <div class="form-group">
                <label>Department Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="department_name" id="dept_name" placeholder="e.g. Cardiology" required>
            </div>
            
            <div class="form-group">
                <label>Department Code <span class="required">*</span></label>
                <input type="text" class="form-control" name="department_code" id="dept_code" placeholder="e.g. CAR" required>
                <small class="text-gray-500 text-xs">Unique code for the department (max 10 characters)</small>
            </div>
            
            <div class="form-group">
                <label>Head of Department (HOD)</label>
                <select class="form-control" name="hod_id" id="dept_hod">
                    <option value="">— Select HOD —</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['doctor_id']; ?>">
                            <?php echo htmlspecialchars($doc['doctor_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" id="dept_description" placeholder="Department description..." rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select class="form-control" name="status" id="dept_status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Save Department
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== VIEW MODAL ========== -->
<div class="modal" id="viewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-building mr-2 text-blue-500"></i> Department Details</h2>
            <button class="modal-close" onclick="document.getElementById('viewModal').classList.remove('show')">&times;</button>
        </div>
        <div id="viewContent">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>
</div>

<!-- ========== TOAST CONTAINER ========== -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:9999;"></div>

<script>
// ========== MODAL FUNCTIONS ==========
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus mr-2 text-blue-500"></i> Add Department';
    document.getElementById('formAction').value = 'add';
    document.getElementById('department_id').value = '';
    document.getElementById('dept_name').value = '';
    document.getElementById('dept_code').value = '';
    document.getElementById('dept_hod').value = '';
    document.getElementById('dept_description').value = '';
    document.getElementById('dept_status').value = 'Active';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Department';
    document.getElementById('departmentModal').classList.add('show');
}

function editDepartment(dept) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-500"></i> Edit Department';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('department_id').value = dept.id;
    document.getElementById('dept_name').value = dept.department_name;
    document.getElementById('dept_code').value = dept.department_code;
    document.getElementById('dept_hod').value = dept.hod_id || '';
    document.getElementById('dept_description').value = dept.description || '';
    document.getElementById('dept_status').value = dept.status || 'Active';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-edit"></i> Update Department';
    document.getElementById('departmentModal').classList.add('show');
}

function viewDepartment(dept) {
    var content = `
        <div style="padding: 10px 0;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                <div class="dept-icon" style="background: ${getDepartmentColor(dept.department_name)}; width: 56px; height: 56px; font-size: 24px;">
                    <i class="fas ${getDepartmentIcon(dept.department_name)}"></i>
                </div>
                <div>
                    <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">${dept.department_name}</h3>
                    <span style="font-size: 13px; color: #94a3b8; font-family: monospace;">${dept.department_code}</span>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">HOD</label>
                    <p style="font-weight: 500; margin: 4px 0 0;">${dept.hod_name || 'Not Assigned'}</p>
                </div>
                <div>
                    <label style="font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Status</label>
                    <p style="margin: 4px 0 0;">${dept.status == 'Active' ? '<span class="status-badge success"><i class="fas fa-check-circle"></i> Active</span>' : '<span class="status-badge secondary"><i class="fas fa-circle"></i> Inactive</span>'}</p>
                </div>
            </div>
            
            <div style="margin-top: 16px;">
                <label style="font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Description</label>
                <p style="margin: 4px 0 0; color: #475569;">${dept.description || 'No description provided'}</p>
            </div>
            
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <button onclick="document.getElementById('viewModal').classList.remove('show')" class="btn-outline">Close</button>
            </div>
        </div>
    `;
    document.getElementById('viewContent').innerHTML = content;
    document.getElementById('viewModal').classList.add('show');
}

function closeModal() {
    document.getElementById('departmentModal').classList.remove('show');
}

function getDepartmentColor(name) {
    const colors = {
        'HR': '#7c3aed',
        'Medical': '#3b82f6',
        'Nursing': '#22c55e',
        'Reception': '#f59e0b',
        'Laboratory': '#8b5cf6',
        'Pharmacy': '#ec4899',
        'Billing': '#06b6d4',
        'Accounts': '#f97316',
        'Operation Theater': '#ef4444',
        'Radiology': '#6366f1'
    };
    return colors[name] || '#64748b';
}

function getDepartmentIcon(name) {
    const icons = {
        'HR': 'fa-user-tie',
        'Medical': 'fa-user-md',
        'Nursing': 'fa-user-nurse',
        'Reception': 'fa-headset',
        'Laboratory': 'fa-flask',
        'Pharmacy': 'fa-pills',
        'Billing': 'fa-file-invoice-dollar',
        'Accounts': 'fa-calculator',
        'Operation Theater': 'fa-scalpel',
        'Radiology': 'fa-x-ray'
    };
    return icons[name] || 'fa-building';
}

// ========== FORM SUBMIT ==========
document.getElementById('departmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('save_department.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Error saving department', 'error');
            btn.disabled = false;
            btn.innerHTML = document.getElementById('formAction').value == 'add' ? 
                '<i class="fas fa-save"></i> Save Department' : 
                '<i class="fas fa-edit"></i> Update Department';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = document.getElementById('formAction').value == 'add' ? 
            '<i class="fas fa-save"></i> Save Department' : 
            '<i class="fas fa-edit"></i> Update Department';
    });
});

// ========== TOGGLE STATUS ==========
function toggleDepartmentStatus(id, currentStatus) {
    var newStatus = currentStatus == 'Active' ? 'Inactive' : 'Active';
    var action = currentStatus == 'Active' ? 'deactivate' : 'activate';
    
    if (!confirm('Are you sure you want to ' + action + ' this department?')) {
        return;
    }
    
    fetch('toggle_department_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&status=' + newStatus
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Error updating status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    });
}

// ========== DELETE DEPARTMENT ==========
function deleteDepartment(id) {
    if (!confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
        return;
    }
    
    if (!confirm('This will permanently remove all department data. Confirm again?')) {
        return;
    }
    
    fetch('delete_department.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Error deleting department', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    });
}

// ========== TOAST NOTIFICATION ==========
function showToast(message, type = 'info') {
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    var icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    toast.innerHTML = '<i class="fas ' + icon + ' mr-2"></i>' + message + 
                      '<button class="close-toast" onclick="this.parentElement.remove()">&times;</button>';
    container.appendChild(toast);
    setTimeout(function() {
        if (toast.parentElement) toast.remove();
    }, 5000);
}

// ========== LIVE CLOCK ==========
function updateClock() {
    const now = new Date();
    const hours = now.getHours();
    let greeting = 'Good Night!';
    if (hours >= 5 && hours < 12) greeting = 'Good Morning!';
    else if (hours >= 12 && hours < 17) greeting = 'Good Afternoon!';
    else if (hours >= 17 && hours < 21) greeting = 'Good Evening!';
    
    const greetingEl = document.querySelector('.welcome-section h1');
    if (greetingEl) {
        const name = '<?php echo htmlspecialchars($admin_name); ?>';
        greetingEl.innerHTML = '<i class="fas fa-building mr-3 text-white"></i> ' + greeting + ', ' + name + '!';
    }
}
updateClock();

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(function(el) {
            el.classList.remove('show');
        });
    }
});

// Close modal on outside click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>