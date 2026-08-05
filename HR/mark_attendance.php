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

// ========== ATTENDANCE SETTINGS ==========
$settings = [
    'check_in_start' => '09:00:00',
    'check_in_end' => '10:00:00',
    'check_out_start' => '17:00:00',
    'check_out_end' => '18:00:00',
    'working_hours_per_day' => 8.00,
    'late_grace_minutes' => 15,
    'early_exit_grace_minutes' => 15,
    'overtime_rate' => 1.50
];

// Get settings from database
$settings_sql = "SELECT * FROM attendance_settings WHERE hospital_id = $hospital_id AND delete_flag = 0 LIMIT 1";
$settings_result = $conn->query($settings_sql);
if ($settings_result && $settings_result->num_rows > 0) {
    $db_settings = $settings_result->fetch_assoc();
    $settings = array_merge($settings, $db_settings);
}

// ========== GET EMPLOYEES ==========
$employees = [];
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name, department_id, shift, joining_date 
            FROM employees 
            WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' 
            ORDER BY first_name ASC";
$emp_result = $conn->query($emp_sql);
if ($emp_result) {
    while ($row = $emp_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// ========== GET TODAY'S ATTENDANCE ==========
$today = date('Y-m-d');
$att_sql = "SELECT * FROM attendance 
            WHERE hospital_id = $hospital_id AND attendance_date = '$today' AND delete_flag = 0";
$att_result = $conn->query($att_sql);
$attendance_today = [];
if ($att_result) {
    while ($row = $att_result->fetch_assoc()) {
        $attendance_today[$row['employee_id']] = $row;
    }
}

// ========== CALCULATE ATTENDANCE METRICS FUNCTIONS ==========
function calculateWorkingHours($check_in, $check_out) {
    if (empty($check_in) || empty($check_out)) return 0;
    $in = strtotime($check_in);
    $out = strtotime($check_out);
    if ($out < $in) {
        $out = strtotime('+1 day', $out);
    }
    $diff = ($out - $in) / 3600;
    return round($diff, 2);
}

function calculateLateMinutes($check_in, $start_time, $grace_minutes = 15) {
    if (empty($check_in)) return 0;
    $in = strtotime($check_in);
    $start = strtotime($start_time);
    $late = max(0, ($in - $start) / 60);
    return round(max(0, $late - $grace_minutes));
}

function calculateEarlyExit($check_out, $end_time, $grace_minutes = 15) {
    if (empty($check_out)) return 0;
    $out = strtotime($check_out);
    $end = strtotime($end_time);
    $early = max(0, ($end - $out) / 60);
    return round(max(0, $early - $grace_minutes));
}

function calculateOvertime($working_hours, $standard_hours = 8) {
    return round(max(0, $working_hours - $standard_hours), 2);
}

function getShiftTimings($shift) {
    $shifts = [
        'Morning' => ['start' => '09:00:00', 'end' => '18:00:00'],
        'Evening' => ['start' => '14:00:00', 'end' => '23:00:00'],
        'Night' => ['start' => '22:00:00', 'end' => '07:00:00'],
        'General' => ['start' => '09:00:00', 'end' => '17:00:00']
    ];
    return $shifts[$shift] ?? $shifts['General'];
}

// ========== PROCESS MARK ATTENDANCE ==========
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $success_count = 0;
    $error_count = 0;
    
    foreach ($_POST['status'] as $employee_id => $status) {
        $employee_id = intval($employee_id);
        $check_in = $_POST['check_in'][$employee_id] ?? null;
        $check_out = $_POST['check_out'][$employee_id] ?? null;
        $remarks = $_POST['remarks'][$employee_id] ?? '';
        
        // Get employee shift
        $shift_sql = "SELECT shift FROM employees WHERE employee_id = $employee_id AND hospital_id = $hospital_id";
        $shift_result = $conn->query($shift_sql);
        $shift = 'General';
        if ($shift_result && $shift_result->num_rows > 0) {
            $shift = $shift_result->fetch_assoc()['shift'] ?? 'General';
        }
        $shift_timing = getShiftTimings($shift);
        
        // Calculate metrics
        $working_hours = calculateWorkingHours($check_in, $check_out);
        $late_minutes = calculateLateMinutes($check_in, $shift_timing['start'], $settings['late_grace_minutes']);
        $early_exit = calculateEarlyExit($check_out, $shift_timing['end'], $settings['early_exit_grace_minutes']);
        $overtime = calculateOvertime($working_hours, $settings['working_hours_per_day']);
        
        // Check if already exists
        $check_sql = "SELECT attendance_id FROM attendance WHERE employee_id = $employee_id AND attendance_date = '$today' AND hospital_id = $hospital_id AND delete_flag = 0";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing
            $row = $check_result->fetch_assoc();
            $att_id = $row['attendance_id'];
            $update_sql = "UPDATE attendance SET 
                           status = '$status', 
                           check_in_time = " . ($check_in ? "'$check_in'" : "NULL") . ",
                           check_out_time = " . ($check_out ? "'$check_out'" : "NULL") . ",
                           working_hours = $working_hours,
                           late_minutes = $late_minutes,
                           early_exit_minutes = $early_exit,
                           overtime_hours = $overtime,
                           remarks = '$remarks',
                           updated_by = $user_id
                           WHERE attendance_id = $att_id";
            if ($conn->query($update_sql)) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            // Insert new
            $insert_sql = "INSERT INTO attendance 
                           (employee_id, attendance_date, status, check_in_time, check_out_time, 
                            working_hours, late_minutes, early_exit_minutes, overtime_hours, remarks, hospital_id, marked_by) 
                           VALUES 
                           ($employee_id, '$today', '$status', " . ($check_in ? "'$check_in'" : "NULL") . ", " . ($check_out ? "'$check_out'" : "NULL") . ", 
                            $working_hours, $late_minutes, $early_exit, $overtime, '$remarks', $hospital_id, $user_id)";
            if ($conn->query($insert_sql)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success_message = "Attendance marked successfully for $success_count employee(s)";
        if ($error_count > 0) {
            $success_message .= " ( $error_count failed )";
        }
    } else {
        $error_message = "Failed to mark attendance. Please try again.";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Mark Attendance</title>
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
        
        .employee-row {
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .employee-row:hover { background: #f8fafc; border-left-color: #3b82f6; }
        .employee-row.present { border-left-color: #22c55e; }
        .employee-row.absent { border-left-color: #ef4444; }
        .employee-row.leave { border-left-color: #f59e0b; }
        .employee-row.half-day { border-left-color: #3b82f6; }
        .employee-row.holiday { border-left-color: #8b5cf6; }
        
        .form-control {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            transition: all 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .metric-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 6px 10px;
            text-align: center;
            min-width: 55px;
        }
        .metric-box .value {
            font-size: 15px;
            font-weight: 700;
        }
        .metric-box .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .metric-box .value.positive { color: #22c55e; }
        .metric-box .value.negative { color: #ef4444; }
        .metric-box .value.warning { color: #f59e0b; }
        .metric-box .value.info { color: #3b82f6; }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        
        .badge-count {
            background: #e5e7eb;
            color: #4b5563;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        
        .table-responsive { overflow-x: auto; }
        table { min-width: 1200px; }
        
        @media (max-width: 768px) {
            .table-responsive { overflow-x: auto; }
            .metric-box { min-width: 45px; padding: 4px 6px; }
            .metric-box .value { font-size: 12px; }
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
                        <h1><i class="fas fa-check-double mr-3 text-white"></i> Mark Attendance</h1>
                        <p>Record today's attendance with check-in/out, working hours, late entry, early exit & overtime</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-calendar-day mr-1"></i> <?php echo date('d M Y'); ?>
                        </span>
                        <a href="attendance.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Attendance Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users mr-2 text-blue-500"></i> Employee Attendance - <?php echo date('d M Y'); ?></h3>
                    <div class="flex items-center gap-3">
                        <span class="badge-count"><?php echo count($employees); ?> employees</span>
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-clock mr-1"></i> 
                            Shift: <?php echo $settings['check_in_start'] ?? '09:00'; ?> - <?php echo $settings['check_out_end'] ?? '18:00'; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" id="attendanceForm">
                        <div class="table-responsive">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="w-8">#</th>
                                        <th>Employee</th>
                                        <th>Shift</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Working Hrs</th>
                                        <th>Late (min)</th>
                                        <th>Early Exit</th>
                                        <th>OT (hrs)</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($employees)): ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($employees as $emp): ?>
                                            <?php 
                                            $today_att = $attendance_today[$emp['employee_id']] ?? null;
                                            $status = $today_att['status'] ?? '';
                                            $check_in = $today_att['check_in_time'] ?? '';
                                            $check_out = $today_att['check_out_time'] ?? '';
                                            $working_hours = $today_att['working_hours'] ?? 0;
                                            $late_minutes = $today_att['late_minutes'] ?? 0;
                                            $early_exit = $today_att['early_exit_minutes'] ?? 0;
                                            $overtime = $today_att['overtime_hours'] ?? 0;
                                            $att_id = $today_att['attendance_id'] ?? 0;
                                            $row_class = strtolower($status);
                                            $shift = $emp['shift'] ?? 'General';
                                            ?>
                                            <tr class="employee-row <?php echo $row_class; ?>">
                                                <td class="text-center text-gray-500 text-sm"><?php echo $counter++; ?></td>
                                                <td>
                                                    <div>
                                                        <div class="font-medium text-gray-800 text-sm">
                                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($emp['employee_code']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded-full"><?php echo $shift; ?></span>
                                                </td>
                                                <td>
                                                    <select name="status[<?php echo $emp['employee_id']; ?>]" class="form-control status-select" style="width:120px;" data-empid="<?php echo $emp['employee_id']; ?>">
                                                        <option value="Present" <?php echo ($status == 'Present') ? 'selected' : ''; ?>>Present</option>
                                                        <option value="Absent" <?php echo ($status == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                                        <option value="Leave" <?php echo ($status == 'Leave') ? 'selected' : ''; ?>>Leave</option>
                                                        <option value="Half Day" <?php echo ($status == 'Half Day') ? 'selected' : ''; ?>>Half Day</option>
                                                        <option value="Holiday" <?php echo ($status == 'Holiday') ? 'selected' : ''; ?>>Holiday</option>
                                                    </select>
                                                    <?php if ($att_id > 0): ?>
                                                        <input type="hidden" name="attendance_id[<?php echo $emp['employee_id']; ?>]" value="<?php echo $att_id; ?>">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="time" name="check_in[<?php echo $emp['employee_id']; ?>]" class="form-control check-in-input" value="<?php echo $check_in; ?>" style="width:110px;" data-empid="<?php echo $emp['employee_id']; ?>">
                                                </td>
                                                <td>
                                                    <input type="time" name="check_out[<?php echo $emp['employee_id']; ?>]" class="form-control check-out-input" value="<?php echo $check_out; ?>" style="width:110px;" data-empid="<?php echo $emp['employee_id']; ?>">
                                                </td>
                                                <td>
                                                    <div class="metric-box">
                                                        <div class="value info" id="working_hrs_<?php echo $emp['employee_id']; ?>"><?php echo number_format($working_hours, 1); ?></div>
                                                        <div class="label">Hrs</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="metric-box">
                                                        <div class="value <?php echo $late_minutes > 0 ? 'negative' : 'positive'; ?>" id="late_min_<?php echo $emp['employee_id']; ?>">
                                                            <?php echo $late_minutes; ?>
                                                        </div>
                                                        <div class="label">min</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="metric-box">
                                                        <div class="value <?php echo $early_exit > 0 ? 'negative' : 'positive'; ?>" id="early_exit_<?php echo $emp['employee_id']; ?>">
                                                            <?php echo $early_exit; ?>
                                                        </div>
                                                        <div class="label">min</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="metric-box">
                                                        <div class="value <?php echo $overtime > 0 ? 'positive' : 'info'; ?>" id="overtime_<?php echo $emp['employee_id']; ?>">
                                                            <?php echo number_format($overtime, 1); ?>
                                                        </div>
                                                        <div class="label">OT Hrs</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" name="remarks[<?php echo $emp['employee_id']; ?>]" class="form-control" placeholder="..." value="<?php echo htmlspecialchars($today_att['remarks'] ?? ''); ?>" style="width:100px;font-size:12px;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center py-8 text-gray-500">
                                                <i class="fas fa-users text-4xl text-gray-300 block mb-2"></i>
                                                No active employees found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4 flex gap-3 flex-wrap">
                            <button type="submit" name="action" value="mark_all" class="btn-success">
                                <i class="fas fa-save"></i> Save All Attendance
                            </button>
                            <button type="button" onclick="markAllPresent()" class="btn-outline">
                                <i class="fas fa-check-circle"></i> Mark All Present
                            </button>
                            <button type="button" onclick="autoFillTime()" class="btn-outline">
                                <i class="fas fa-clock"></i> Auto Fill Time
                            </button>
                            <button type="reset" class="btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <a href="attendance.php" class="btn-outline">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-4">
                <div class="card p-4 text-center">
                    <div class="text-2xl text-green-500"><i class="fas fa-check-circle"></i></div>
                    <div class="text-sm text-gray-600">Present</div>
                    <div class="text-xl font-bold text-green-600" id="presentCount">
                        <?php 
                        $present_count = 0;
                        foreach ($attendance_today as $att) {
                            if ($att['status'] == 'Present') $present_count++;
                        }
                        echo $present_count;
                        ?>
                    </div>
                </div>
                <div class="card p-4 text-center">
                    <div class="text-2xl text-red-500"><i class="fas fa-times-circle"></i></div>
                    <div class="text-sm text-gray-600">Absent</div>
                    <div class="text-xl font-bold text-red-600" id="absentCount">
                        <?php 
                        $absent_count = 0;
                        foreach ($attendance_today as $att) {
                            if ($att['status'] == 'Absent') $absent_count++;
                        }
                        echo $absent_count;
                        ?>
                    </div>
                </div>
                <div class="card p-4 text-center">
                    <div class="text-2xl text-yellow-500"><i class="fas fa-clock"></i></div>
                    <div class="text-sm text-gray-600">On Leave</div>
                    <div class="text-xl font-bold text-yellow-600" id="leaveCount">
                        <?php 
                        $leave_count = 0;
                        foreach ($attendance_today as $att) {
                            if ($att['status'] == 'Leave') $leave_count++;
                        }
                        echo $leave_count;
                        ?>
                    </div>
                </div>
                <div class="card p-4 text-center">
                    <div class="text-2xl text-blue-500"><i class="fas fa-adjust"></i></div>
                    <div class="text-sm text-gray-600">Half Day</div>
                    <div class="text-xl font-bold text-blue-600" id="halfDayCount">
                        <?php 
                        $half_day_count = 0;
                        foreach ($attendance_today as $att) {
                            if ($att['status'] == 'Half Day') $half_day_count++;
                        }
                        echo $half_day_count;
                        ?>
                    </div>
                </div>
                <div class="card p-4 text-center">
                    <div class="text-2xl text-purple-500"><i class="fas fa-gift"></i></div>
                    <div class="text-sm text-gray-600">Holiday</div>
                    <div class="text-xl font-bold text-purple-600" id="holidayCount">
                        <?php 
                        $holiday_count = 0;
                        foreach ($attendance_today as $att) {
                            if ($att['status'] == 'Holiday') $holiday_count++;
                        }
                        echo $holiday_count;
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// ========== MARK ALL PRESENT ==========
function markAllPresent() {
    if (!confirm('Mark all employees as Present?')) {
        return;
    }
    
    var selects = document.querySelectorAll('.status-select');
    selects.forEach(function(select) {
        select.value = 'Present';
    });
    
    autoFillTime();
}

// ========== AUTO FILL TIME ==========
function autoFillTime() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var time = hours + ':' + minutes;
    
    var checkIns = document.querySelectorAll('.check-in-input');
    checkIns.forEach(function(input) {
        if (!input.value) {
            input.value = time;
            input.dispatchEvent(new Event('change'));
        }
    });
}

// ========== CALCULATE METRICS ON TIME CHANGE ==========
document.querySelectorAll('.check-in-input, .check-out-input').forEach(function(input) {
    input.addEventListener('change', function() {
        var empId = this.dataset.empid;
        var checkIn = document.querySelector('.check-in-input[data-empid="' + empId + '"]');
        var checkOut = document.querySelector('.check-out-input[data-empid="' + empId + '"]');
        
        if (checkIn && checkOut && checkIn.value && checkOut.value) {
            var inTime = checkIn.value.split(':');
            var outTime = checkOut.value.split(':');
            var inMinutes = parseInt(inTime[0]) * 60 + parseInt(inTime[1]);
            var outMinutes = parseInt(outTime[0]) * 60 + parseInt(outTime[1]);
            
            if (outMinutes < inMinutes) {
                outMinutes += 24 * 60;
            }
            
            var diffMinutes = outMinutes - inMinutes;
            var hours = (diffMinutes / 60).toFixed(1);
            
            // Update working hours
            document.getElementById('working_hrs_' + empId).textContent = hours;
            
            // Calculate late minutes (shift starts at 9:00 AM)
            var shiftStart = 9 * 60;
            var lateMinutes = Math.max(0, inMinutes - shiftStart - 15);
            var lateEl = document.getElementById('late_min_' + empId);
            lateEl.textContent = lateMinutes;
            lateEl.className = 'value ' + (lateMinutes > 0 ? 'negative' : 'positive');
            
            // Calculate early exit (shift ends at 6:00 PM)
            var shiftEnd = 18 * 60;
            var earlyExit = Math.max(0, shiftEnd - outMinutes - 15);
            var earlyEl = document.getElementById('early_exit_' + empId);
            earlyEl.textContent = earlyExit;
            earlyEl.className = 'value ' + (earlyExit > 0 ? 'negative' : 'positive');
            
            // Calculate overtime
            var standardHours = 8;
            var overtime = Math.max(0, parseFloat(hours) - standardHours);
            var overtimeEl = document.getElementById('overtime_' + empId);
            overtimeEl.textContent = overtime.toFixed(1);
            overtimeEl.className = 'value ' + (overtime > 0 ? 'positive' : 'info');
        }
    });
});

// ========== UPDATE STATS ON STATUS CHANGE ==========
document.querySelectorAll('.status-select').forEach(function(select) {
    select.addEventListener('change', function() {
        var status = this.value;
        var row = this.closest('tr');
        row.className = 'employee-row ' + status.toLowerCase().replace(' ', '-');
    });
});

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
        greetingEl.innerHTML = '<i class="fas fa-check-double mr-3 text-white"></i> ' + greeting + ', ' + name + '!';
    }
}
updateClock();
</script>

</body>
</html>