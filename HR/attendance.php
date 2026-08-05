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

// ========== GET EMPLOYEES ==========
$employees = [];
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name, department_id 
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
$att_sql = "SELECT employee_id, attendance_id, status, check_in_time, check_out_time 
            FROM attendance 
            WHERE hospital_id = $hospital_id AND attendance_date = '$today' AND delete_flag = 0";
$att_result = $conn->query($att_sql);
$attendance_today = [];
if ($att_result) {
    while ($row = $att_result->fetch_assoc()) {
        $attendance_today[$row['employee_id']] = $row;
    }
}

// ========== PROCESS MARK ATTENDANCE ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $employee_id = intval($_POST['employee_id']);
    $status = $_POST['status'];
    $check_in = $_POST['check_in'] ?? null;
    $check_out = $_POST['check_out'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    
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
                       remarks = '$remarks',
                       updated_by = $user_id
                       WHERE attendance_id = $att_id";
        $conn->query($update_sql);
    } else {
        // Insert new
        $insert_sql = "INSERT INTO attendance (employee_id, attendance_date, status, check_in_time, check_out_time, remarks, hospital_id, marked_by) 
                       VALUES ($employee_id, '$today', '$status', " . ($check_in ? "'$check_in'" : "NULL") . ", " . ($check_out ? "'$check_out'" : "NULL") . ", '$remarks', $hospital_id, $user_id)";
        $conn->query($insert_sql);
    }
    
    header('Location: mark_attendance.php?success=1');
    exit();
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

function getStatusIcon($status) {
    $icons = [
        'Present' => 'fa-check-circle text-green-500',
        'Absent' => 'fa-times-circle text-red-500',
        'Leave' => 'fa-clock text-yellow-500',
        'Half Day' => 'fa-adjust text-blue-500',
        'Holiday' => 'fa-gift text-purple-500'
    ];
    return $icons[$status] ?? 'fa-question-circle';
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
        .employee-row:hover {
            background: #f8fafc;
            border-left-color: #3b82f6;
        }
        .employee-row.present { border-left-color: #22c55e; }
        .employee-row.absent { border-left-color: #ef4444; }
        .employee-row.leave { border-left-color: #f59e0b; }
        
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
            .table-responsive { overflow-x: auto; }
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
                        <p>Record today's attendance for all employees - <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <a href="attendance.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left"></i> Back to Attendance
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                    <i class="fas fa-check-circle mr-2"></i> Attendance marked successfully!
                </div>
            <?php endif; ?>

            <!-- Attendance Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users mr-2 text-blue-500"></i> Employee Attendance - <?php echo date('d M Y'); ?></h3>
                    <span class="badge-count"><?php echo count($employees); ?> employees</span>
                </div>
                <div class="card-body">
                    <form method="POST" id="attendanceForm">
                        <div class="table-responsive">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="w-12">#</th>
                                        <th>Employee</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
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
                                            $att_id = $today_att['attendance_id'] ?? 0;
                                            $row_class = strtolower($status);
                                            ?>
                                            <tr class="employee-row <?php echo $row_class; ?>">
                                                <td class="text-center text-gray-500"><?php echo $counter++; ?></td>
                                                <td>
                                                    <div>
                                                        <div class="font-medium text-gray-800">
                                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($emp['employee_code']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="status[<?php echo $emp['employee_id']; ?>]" class="form-control status-select" data-empid="<?php echo $emp['employee_id']; ?>">
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
                                                    <input type="time" name="check_in[<?php echo $emp['employee_id']; ?>]" class="form-control" value="<?php echo $check_in; ?>" style="width:130px;">
                                                </td>
                                                <td>
                                                    <input type="time" name="check_out[<?php echo $emp['employee_id']; ?>]" class="form-control" value="<?php echo $check_out; ?>" style="width:130px;">
                                                </td>
                                                <td>
                                                    <input type="text" name="remarks[<?php echo $emp['employee_id']; ?>]" class="form-control" placeholder="Remarks..." value="<?php echo htmlspecialchars($today_att['remarks'] ?? ''); ?>" style="width:150px;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-8 text-gray-500">
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

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <div class="card p-4 text-center">
                    <div class="text-2xl text-green-500"><i class="fas fa-check-circle"></i></div>
                    <div class="text-sm text-gray-600">Present Today</div>
                    <div class="text-xl font-bold text-green-600">
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
                    <div class="text-sm text-gray-600">Absent Today</div>
                    <div class="text-xl font-bold text-red-600">
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
                    <div class="text-xl font-bold text-yellow-600">
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
                    <div class="text-2xl text-blue-500"><i class="fas fa-users"></i></div>
                    <div class="text-sm text-gray-600">Total Employees</div>
                    <div class="text-xl font-bold text-blue-600"><?php echo count($employees); ?></div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== TOAST CONTAINER ========== -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:9999;"></div>

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
    
    // Auto-fill check-in time for all
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var time = hours + ':' + minutes;
    
    var checkIns = document.querySelectorAll('input[name^="check_in"]');
    checkIns.forEach(function(input) {
        if (!input.value) {
            input.value = time;
        }
    });
}

// ========== AUTO FILL CHECK-IN TIME ==========
document.querySelectorAll('input[name^="check_in"]').forEach(function(input) {
    input.addEventListener('change', function() {
        var empId = this.name.match(/\d+/);
        if (empId) {
            var checkOut = document.querySelector('input[name="check_out[' + empId[0] + ']"]');
            if (checkOut && !checkOut.value && this.value) {
                // Auto fill check-out after 8 hours
                var parts = this.value.split(':');
                var hours = parseInt(parts[0]) + 8;
                var minutes = parts[1];
                if (hours >= 24) hours -= 24;
                checkOut.value = String(hours).padStart(2, '0') + ':' + minutes;
            }
        }
    });
});

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
        greetingEl.innerHTML = '<i class="fas fa-check-double mr-3 text-white"></i> ' + greeting + ', ' + name + '!';
    }
}
updateClock();
</script>

</body>
</html>