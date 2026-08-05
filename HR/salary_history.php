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

// ========== FILTERS ==========
$month_filter = isset($_GET['month']) ? $_GET['month'] : '';
$employee_filter = isset($_GET['employee']) ? intval($_GET['employee']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = ["s.hospital_id = $hospital_id", "s.delete_flag = 0"];
if (!empty($month_filter)) {
    $where[] = "s.month_year = '$month_filter'";
}
if ($employee_filter > 0) {
    $where[] = "s.employee_id = $employee_filter";
}
if (!empty($status_filter)) {
    $where[] = "s.status = '$status_filter'";
}
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.employee_code LIKE '%$search%')";
}
$where_clause = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) as total FROM salary_generated s JOIN employees e ON s.employee_id = e.employee_id WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT s.*, e.first_name, e.last_name, e.employee_code, e.department_id, d.department_name
        FROM salary_generated s
        JOIN employees e ON s.employee_id = e.employee_id
        LEFT JOIN department d ON e.department_id = d.id
        WHERE $where_clause
        ORDER BY s.month_year DESC, e.first_name ASC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$salaries = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $salaries[] = $row;
    }
}

// ========== GET EMPLOYEES FOR FILTER ==========
$employees = [];
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name FROM employees WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY first_name";
$emp_res = $conn->query($emp_sql);
if ($emp_res) {
    while ($row = $emp_res->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Salary History</title>
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
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; align-items: end; }
        @media (max-width: 640px) { .filter-grid { grid-template-columns: 1fr; } }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.Draft { background: #fef3c7; color: #92400e; }
        .status-badge.Finalized { background: #dcfce7; color: #166534; }
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
        .summary-box { background: #f8fafc; border-radius: 8px; padding: 12px 16px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
        .summary-box .item { text-align: center; }
        .summary-box .value { font-size: 20px; font-weight: 700; }
        .summary-box .label { font-size: 12px; color: #64748b; }
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
                        <h1><i class="fas fa-history mr-3 text-white"></i> Salary History</h1>
                        <p>View all generated salary records with powerful filters</p>
                    </div>
                    <a href="generate_salary.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-cogs"></i> Generate New
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="filter-grid">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                            <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month_filter); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                            <select name="employee" class="form-control">
                                <option value="0">All Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['employee_id']; ?>" <?php echo ($employee_filter == $emp['employee_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="Draft" <?php echo ($status_filter == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="Finalized" <?php echo ($status_filter == 'Finalized') ? 'selected' : ''; ?>>Finalized</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name or code" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="flex gap-2 items-end pb-1">
                            <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
                            <a href="salary_history.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary -->
            <?php if (!empty($salaries)): 
                $total_gross = 0;
                $total_net = 0;
                foreach ($salaries as $s) {
                    $total_gross += $s['gross_salary'];
                    $total_net += $s['net_salary'];
                }
            ?>
                <div class="summary-box">
                    <div class="item">
                        <div class="label">Total Records</div>
                        <div class="value text-blue-600"><?php echo count($salaries); ?></div>
                    </div>
                    <div class="item">
                        <div class="label">Total Gross</div>
                        <div class="value text-blue-600"><?php echo number_format($total_gross, 2); ?></div>
                    </div>
                    <div class="item">
                        <div class="label">Total Net Payable</div>
                        <div class="value text-green-600"><?php echo number_format($total_net, 2); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Salary Records</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> records</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($salaries)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Month</th>
                                        <th>Department</th>
                                        <th>Gross</th>
                                        <th>Deductions</th>
                                        <th>Net</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($salaries as $s): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?><br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($s['employee_code']); ?></span></td>
                                            <td><?php echo date('M Y', strtotime($s['month_year'])); ?></td>
                                            <td><?php echo htmlspecialchars($s['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($s['gross_salary'], 2); ?></td>
                                            <td><?php echo number_format($s['total_deductions'], 2); ?></td>
                                            <td><strong class="text-green-600"><?php echo number_format($s['net_salary'], 2); ?></strong></td>
                                            <td><span class="status-badge <?php echo $s['status']; ?>"><?php echo $s['status']; ?></span></td>
                                            <td>
                                                <a href="salary_slip.php?id=<?php echo $s['id']; ?>" class="btn-primary btn-xs" title="View Slip">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&month=<?php echo urlencode($month_filter); ?>&employee=<?php echo $employee_filter; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&month=<?php echo urlencode($month_filter); ?>&employee=<?php echo $employee_filter; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&month=<?php echo urlencode($month_filter); ?>&employee=<?php echo $employee_filter; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="text-lg font-medium text-gray-700">No salary records found</p>
                            <p class="text-sm text-gray-400 mt-1">Generate salaries first using the "Generate New" button.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
