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

// ========== GET DEPARTMENTS FOR FILTER ==========
$departments = [];
$dept_sql = "SELECT id, department_name FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_sql);
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ========== GET EMPLOYEES FOR FILTER ==========
$employees_list = [];
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name, department_id FROM employees WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY first_name";
$emp_result = $conn->query($emp_sql);
if ($emp_result) {
    while ($row = $emp_result->fetch_assoc()) {
        $employees_list[] = $row;
    }
}

// ========== PROCESS FILTERS ==========
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // first day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$department_id = isset($_GET['department']) ? intval($_GET['department']) : 0;
$employee_id = isset($_GET['employee']) ? intval($_GET['employee']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// ========== BUILD WHERE CLAUSE ==========
$where = [
    "a.hospital_id = $hospital_id",
    "a.delete_flag = 0",
    "DATE(a.attendance_date) BETWEEN '$date_from' AND '$date_to'"
];
if ($department_id > 0) {
    $where[] = "e.department_id = $department_id";
}
if ($employee_id > 0) {
    $where[] = "a.employee_id = $employee_id";
}
if (!empty($status_filter)) {
    $where[] = "a.status = '$status_filter'";
}
$where_clause = implode(" AND ", $where);

// ========== FETCH ATTENDANCE DATA ==========
$sql = "SELECT a.*, e.employee_code, e.first_name, e.last_name, e.department_id, d.department_name
        FROM attendance a
        JOIN employees e ON a.employee_id = e.employee_id
        LEFT JOIN department d ON e.department_id = d.id
        WHERE $where_clause
        ORDER BY e.first_name, e.employee_id, a.attendance_date ASC";
$result = $conn->query($sql);

$attendance_data = [];
$employee_summary = [];
$total_days = (strtotime($date_to) - strtotime($date_from)) / 86400 + 1;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['employee_id'];
        if (!isset($attendance_data[$emp_id])) {
            $attendance_data[$emp_id] = [
                'employee' => $row,
                'days' => []
            ];
            // Initialize summary
            $employee_summary[$emp_id] = [
                'total_days' => $total_days,
                'present' => 0,
                'absent' => 0,
                'leave' => 0,
                'half_day' => 0,
                'holiday' => 0,
                'total_working_hours' => 0,
                'total_overtime' => 0,
                'total_late_minutes' => 0,
                'total_early_exit' => 0
            ];
        }
        $attendance_data[$emp_id]['days'][] = $row;
        
        // Update summary
        $status = $row['status'];
        $summary = &$employee_summary[$emp_id];
        switch ($status) {
            case 'Present': $summary['present']++; break;
            case 'Absent': $summary['absent']++; break;
            case 'Leave': $summary['leave']++; break;
            case 'Half Day': $summary['half_day']++; break;
            case 'Holiday': $summary['holiday']++; break;
        }
        $summary['total_working_hours'] += floatval($row['working_hours'] ?? 0);
        $summary['total_overtime'] += floatval($row['overtime_hours'] ?? 0);
        $summary['total_late_minutes'] += intval($row['late_minutes'] ?? 0);
        $summary['total_early_exit'] += intval($row['early_exit_minutes'] ?? 0);
    }
}

// ========== OVERALL STATS ==========
$total_employees = count($attendance_data);
$total_present = 0;
$total_absent = 0;
$total_leave = 0;
$total_half_day = 0;
$total_holiday = 0;
foreach ($employee_summary as $summary) {
    $total_present += $summary['present'];
    $total_absent += $summary['absent'];
    $total_leave += $summary['leave'];
    $total_half_day += $summary['half_day'];
    $total_holiday += $summary['holiday'];
}

// ========== HELPER FUNCTIONS ==========
function getStatusBadge($status) {
    $badges = [
        'Present' => 'success',
        'Absent' => 'danger',
        'Leave' => 'warning',
        'Half Day' => 'info',
        'Holiday' => 'purple'
    ];
    $color = $badges[$status] ?? 'secondary';
    return "<span class='status-badge $color'>$status</span>";
}

function formatTime($time) {
    if (empty($time)) return '—';
    return date('h:i A', strtotime($time));
}

// ========== EXPORT TO CSV ==========
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee Code', 'Employee Name', 'Department', 'Date', 'Status', 'Check In', 'Check Out', 'Working Hours', 'Late (min)', 'Early Exit (min)', 'Overtime (hrs)', 'Remarks']);
    
    foreach ($attendance_data as $emp_id => $data) {
        $emp = $data['employee'];
        foreach ($data['days'] as $day) {
            fputcsv($output, [
                $emp['employee_code'],
                $emp['first_name'] . ' ' . $emp['last_name'],
                $emp['department_name'] ?? 'N/A',
                $day['attendance_date'],
                $day['status'],
                formatTime($day['check_in_time']),
                formatTime($day['check_out_time']),
                number_format($day['working_hours'] ?? 0, 2),
                $day['late_minutes'] ?? 0,
                $day['early_exit_minutes'] ?? 0,
                number_format($day['overtime_hours'] ?? 0, 2),
                $day['remarks'] ?? ''
            ]);
        }
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Attendance Report</title>
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
        }
        .btn-success:hover { background: #16a34a; }
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
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            align-items: end;
        }
        @media (max-width: 640px) { .filter-grid { grid-template-columns: 1fr; } }
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
        .status-badge.info { background: #dbeafe; color: #1e40af; }
        .status-badge.purple { background: #ede9fe; color: #6d28d9; }
        .status-badge.secondary { background: #f1f5f9; color: #64748b; }
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
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .stat-card .stat-number { font-size: 22px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 12px; margin-top: 2px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead { background: #f9fafb; }
        th {
            padding: 8px 12px;
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
            padding: 8px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            vertical-align: middle;
        }
        tr:hover { background: #f8fafc; }
        .summary-row { background: #f0f4ff; font-weight: 600; }
        .summary-row td { border-top: 2px solid #3b82f6; }
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
        .metric-box {
            background: #f8fafc;
            border-radius: 6px;
            padding: 2px 8px;
            text-align: center;
            display: inline-block;
            min-width: 50px;
        }
        .metric-box .value {
            font-size: 14px;
            font-weight: 700;
        }
        .metric-box .value.positive { color: #22c55e; }
        .metric-box .value.negative { color: #ef4444; }
        .metric-box .value.warning { color: #f59e0b; }
        .metric-box .value.info { color: #3b82f6; }
        .sub-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .sub-header .info {
            color: #6b7280;
            font-size: 13px;
        }
        .export-buttons {
            display: flex;
            gap: 8px;
        }
        @media print {
            .no-print { display: none; }
            .main-content { padding: 0; }
        }
    </style>
</head>
<body>

<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <?php include '../header.php'; ?>
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <!-- Welcome -->
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-chart-bar mr-3 text-white"></i> Attendance Report</h1>
                        <p>View and export attendance data for any date range</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> <?php echo date('d M Y', strtotime($date_from)); ?> – <?php echo date('d M Y', strtotime($date_to)); ?>
                        </span>
                        <a href="mark_attendance.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card no-print mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="filter-grid">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select name="department" class="form-control">
                                    <option value="0">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo ($department_id == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                                <select name="employee" class="form-control">
                                    <option value="0">All Employees</option>
                                    <?php foreach ($employees_list as $emp): ?>
                                        <option value="<?php echo $emp['employee_id']; ?>" <?php echo ($employee_id == $emp['employee_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Present" <?php echo ($status_filter == 'Present') ? 'selected' : ''; ?>>Present</option>
                                    <option value="Absent" <?php echo ($status_filter == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                    <option value="Leave" <?php echo ($status_filter == 'Leave') ? 'selected' : ''; ?>>Leave</option>
                                    <option value="Half Day" <?php echo ($status_filter == 'Half Day') ? 'selected' : ''; ?>>Half Day</option>
                                    <option value="Holiday" <?php echo ($status_filter == 'Holiday') ? 'selected' : ''; ?>>Holiday</option>
                                </select>
                            </div>
                            <div class="flex gap-2 items-end pb-1">
                                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Generate</button>
                                <a href="attendance_report.php" class="btn-outline"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stat-grid">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-number text-blue-600"><?php echo $total_employees; ?></div>
                    <div class="stat-label">Employees</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-number text-green-600"><?php echo $total_present; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #ef4444;">
                    <div class="stat-number text-red-600"><?php echo $total_absent; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div class="stat-number text-yellow-600"><?php echo $total_leave; ?></div>
                    <div class="stat-label">On Leave</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-number text-blue-600"><?php echo $total_half_day; ?></div>
                    <div class="stat-label">Half Day</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                    <div class="stat-number text-purple-600"><?php echo $total_holiday; ?></div>
                    <div class="stat-label">Holiday</div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table mr-2 text-blue-500"></i> Detailed Report</h3>
                    <div class="flex items-center gap-3">
                        <span class="badge-count"><?php echo count($attendance_data); ?> employees</span>
                        <div class="export-buttons no-print">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-success btn-sm" target="_blank">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <button onclick="window.print()" class="btn-primary btn-sm"><i class="fas fa-print"></i> Print</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($attendance_data)): ?>
                        <div class="sub-header">
                            <div class="info">
                                <i class="fas fa-calendar-alt mr-1"></i> Period: <?php echo date('d M Y', strtotime($date_from)); ?> – <?php echo date('d M Y', strtotime($date_to)); ?>
                                (<?php echo $total_days; ?> days)
                            </div>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Work Hrs</th>
                                        <th>Late (min)</th>
                                        <th>Early Exit</th>
                                        <th>OT (hrs)</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    foreach ($attendance_data as $emp_id => $data):
                                        $emp = $data['employee'];
                                        $summary = $employee_summary[$emp_id];
                                    ?>
                                        <!-- Employee Summary Row -->
                                        <tr class="summary-row">
                                            <td colspan="2">
                                                <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                                                <span class="text-gray-500 text-xs ml-2">(<?php echo htmlspecialchars($emp['employee_code']); ?>)</span>
                                            </td>
                                            <td><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?></td>
                                            <td colspan="9">
                                                <div class="flex flex-wrap gap-2 items-center text-xs">
                                                    <span><span class="font-medium">Total:</span> <?php echo $summary['total_days']; ?> days</span>
                                                    <span class="text-green-600"><i class="fas fa-check-circle"></i> P: <?php echo $summary['present']; ?></span>
                                                    <span class="text-red-600"><i class="fas fa-times-circle"></i> A: <?php echo $summary['absent']; ?></span>
                                                    <span class="text-yellow-600"><i class="fas fa-clock"></i> L: <?php echo $summary['leave']; ?></span>
                                                    <span class="text-blue-600"><i class="fas fa-adjust"></i> H: <?php echo $summary['half_day']; ?></span>
                                                    <span class="text-purple-600"><i class="fas fa-gift"></i> Hol: <?php echo $summary['holiday']; ?></span>
                                                    <span class="text-gray-500"><i class="fas fa-hourglass-half"></i> WH: <?php echo number_format($summary['total_working_hours'], 1); ?></span>
                                                    <span class="text-yellow-500"><i class="fas fa-clock"></i> Late: <?php echo $summary['total_late_minutes']; ?>m</span>
                                                    <span class="text-orange-500"><i class="fas fa-sign-out-alt"></i> Early: <?php echo $summary['total_early_exit']; ?>m</span>
                                                    <span class="text-blue-500"><i class="fas fa-plus-circle"></i> OT: <?php echo number_format($summary['total_overtime'], 1); ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Daily rows for this employee -->
                                        <?php foreach ($data['days'] as $day): ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d M Y', strtotime($day['attendance_date'])); ?></td>
                                                <td><?php echo getStatusBadge($day['status']); ?></td>
                                                <td><?php echo formatTime($day['check_in_time']); ?></td>
                                                <td><?php echo formatTime($day['check_out_time']); ?></td>
                                                <td><?php echo number_format($day['working_hours'] ?? 0, 1); ?></td>
                                                <td><?php echo $day['late_minutes'] ?? 0; ?></td>
                                                <td><?php echo $day['early_exit_minutes'] ?? 0; ?></td>
                                                <td><?php echo number_format($day['overtime_hours'] ?? 0, 1); ?></td>
                                                <td><?php echo htmlspecialchars($day['remarks'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p class="text-lg font-medium text-gray-700">No attendance records found</p>
                            <p class="text-sm text-gray-400 mt-1">Try adjusting the date range or filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>