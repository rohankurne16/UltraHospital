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

// Allow all logged-in users (employees + HR)
if (!in_array($user_role, ['HR', 'Admin', 'Super Admin', 'Employee'])) {
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

// ========== GET EMPLOYEE ID (for employee role) ==========
$employee_id = 0;
if ($user_role == 'Employee') {
    $emp_sql = "SELECT employee_id FROM employees WHERE user_id = $user_id AND hospital_id = $hospital_id AND delete_flag = 0";
    $emp_res = $conn->query($emp_sql);
    if ($emp_res && $emp_res->num_rows > 0) {
        $employee_id = $emp_res->fetch_assoc()['employee_id'];
    } else {
        // If no employee record linked, redirect
        header('Location: ../dashboard.php');
        exit();
    }
}

// ========== FETCH ACTIVE LEAVE TYPES ==========
$leave_types = [];
$lt_sql = "SELECT id, leave_code, leave_name, days_per_year FROM leave_types 
           WHERE hospital_id = $hospital_id AND status = 'Active' AND delete_flag = 0 
           ORDER BY leave_name";
$lt_res = $conn->query($lt_sql);
if ($lt_res) {
    while ($row = $lt_res->fetch_assoc()) {
        $leave_types[] = $row;
    }
}

// ========== HANDLE LEAVE APPLICATION ==========
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    $leave_type_id = intval($_POST['leave_type_id'] ?? 0);
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    // Determine employee_id (if HR applying for someone, might have a dropdown; we'll keep simple)
    // For HR, we can apply for self or for others? For simplicity, we apply for logged-in employee.
    // If HR/Admin, they might apply on behalf of someone – we'll add a selector.
    $target_employee_id = $employee_id;
    if (in_array($user_role, ['HR', 'Admin', 'Super Admin']) && isset($_POST['employee_id']) && $_POST['employee_id'] > 0) {
        $target_employee_id = intval($_POST['employee_id']);
    }

    if ($leave_type_id <= 0 || empty($from_date) || empty($to_date)) {
        $message = 'Please select leave type and date range.';
        $message_type = 'error';
    } else {
        // Calculate days (excluding weekends? We'll keep it simple – count all days)
        $from = new DateTime($from_date);
        $to = new DateTime($to_date);
        $interval = $from->diff($to);
        $days = $interval->days + 1; // inclusive

        // Check available balance (simplified – we'll just check if days <= allowed)
        // In a real system, you'd calculate used leaves
        $balance_sql = "SELECT SUM(days) as used FROM leave_requests 
                        WHERE employee_id = $target_employee_id AND leave_type_id = $leave_type_id 
                        AND status IN ('Approved', 'Pending') AND delete_flag = 0";
        $bal_res = $conn->query($balance_sql);
        $used = $bal_res ? floatval($bal_res->fetch_assoc()['used']) : 0;

        $lt_info = null;
        foreach ($leave_types as $lt) {
            if ($lt['id'] == $leave_type_id) {
                $lt_info = $lt;
                break;
            }
        }
        $allowed = $lt_info ? $lt_info['days_per_year'] : 0;
        $remaining = $allowed - $used;

        if ($days > $remaining && $remaining >= 0) {
            $message = "Insufficient leave balance. Available: $remaining days, Requested: $days days.";
            $message_type = 'error';
        } else {
            $insert_sql = "INSERT INTO leave_requests 
                           (employee_id, leave_type_id, from_date, to_date, days, reason, status, hospital_id) 
                           VALUES ($target_employee_id, $leave_type_id, '$from_date', '$to_date', $days, '$reason', 'Pending', $hospital_id)";
            if ($conn->query($insert_sql)) {
                $message = 'Leave application submitted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// ========== FETCH LEAVE HISTORY ==========
$history_sql = "SELECT lr.*, lt.leave_name 
                FROM leave_requests lr
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.employee_id = $employee_id AND lr.hospital_id = $hospital_id AND lr.delete_flag = 0
                ORDER BY lr.applied_date DESC";
$history_res = $conn->query($history_sql);
$history = [];
if ($history_res) {
    while ($row = $history_res->fetch_assoc()) {
        $history[] = $row;
    }
}

// ========== FOR HR: GET EMPLOYEES LIST ==========
$employees_list = [];
if (in_array($user_role, ['HR', 'Admin', 'Super Admin'])) {
    $emp_sql = "SELECT employee_id, employee_code, first_name, last_name FROM employees 
                WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' 
                ORDER BY first_name";
    $emp_res = $conn->query($emp_sql);
    if ($emp_res) {
        while ($row = $emp_res->fetch_assoc()) {
            $employees_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Leave Request</title>
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
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.Pending { background: #fef3c7; color: #92400e; }
        .status-badge.Approved { background: #dcfce7; color: #166534; }
        .status-badge.Rejected { background: #fecaca; color: #991b1b; }
        .status-badge.Cancelled { background: #f1f5f9; color: #64748b; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .welcome-section { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 1024px) { .grid-2col { grid-template-columns: 1fr; } }
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
                        <h1><i class="fas fa-paper-plane mr-3 text-white"></i> Apply for Leave</h1>
                        <p>Submit a leave request or view your leave history</p>
                    </div>
                    <a href="dashboard.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="grid-2col">
                <!-- Application Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle mr-2 text-blue-500"></i> New Leave Request</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if (in_array($user_role, ['HR', 'Admin', 'Super Admin']) && !empty($employees_list)): ?>
                                <div class="form-group">
                                    <label>Employee <span class="required">*</span></label>
                                    <select name="employee_id" class="form-control" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees_list as $emp): ?>
                                            <option value="<?php echo $emp['employee_id']; ?>">
                                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Leave Type <span class="required">*</span></label>
                                <select name="leave_type_id" class="form-control" required>
                                    <option value="">Select Leave Type</option>
                                    <?php foreach ($leave_types as $lt): ?>
                                        <option value="<?php echo $lt['id']; ?>"><?php echo htmlspecialchars($lt['leave_name'] . ' (' . $lt['leave_code'] . ') - ' . $lt['days_per_year'] . ' days'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>From Date <span class="required">*</span></label>
                                    <input type="date" name="from_date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>To Date <span class="required">*</span></label>
                                    <input type="date" name="to_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Reason <span class="required">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="Explain the reason for leave..." required></textarea>
                            </div>
                            <button type="submit" name="apply" class="btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
                        </form>
                    </div>
                </div>

                <!-- Leave Balance & Quick Info -->
                <div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-balance-scale mr-2 text-green-500"></i> Leave Balance</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // Calculate used leaves per type for the employee
                            $balance_data = [];
                            foreach ($leave_types as $lt) {
                                $used_sql = "SELECT SUM(days) as used FROM leave_requests 
                                             WHERE employee_id = $employee_id AND leave_type_id = {$lt['id']} 
                                             AND status IN ('Approved', 'Pending') AND delete_flag = 0";
                                $used_res = $conn->query($used_sql);
                                $used = $used_res ? floatval($used_res->fetch_assoc()['used']) : 0;
                                $balance_data[$lt['id']] = [
                                    'name' => $lt['leave_name'],
                                    'code' => $lt['leave_code'],
                                    'allowed' => $lt['days_per_year'],
                                    'used' => $used,
                                    'remaining' => max(0, $lt['days_per_year'] - $used)
                                ];
                            }
                            ?>
                            <?php if (!empty($balance_data)): ?>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Allowed</th>
                                            <th>Used</th>
                                            <th>Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($balance_data as $bd): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bd['code'] . ' - ' . $bd['name']); ?></td>
                                                <td><?php echo number_format($bd['allowed'], 1); ?></td>
                                                <td><?php echo number_format($bd['used'], 1); ?></td>
                                                <td><strong><?php echo number_format($bd['remaining'], 1); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500 text-sm">No leave types defined.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-history mr-2 text-blue-500"></i> Leave History</h3>
                    <span class="badge-count"><?php echo count($history); ?> records</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($history)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Leave Type</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                        <th>Applied On</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($h['leave_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($h['from_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($h['to_date'])); ?></td>
                                            <td><?php echo number_format($h['days'], 1); ?></td>
                                            <td><span class="status-badge <?php echo $h['status']; ?>"><?php echo $h['status']; ?></span></td>
                                            <td><?php echo date('d M Y', strtotime($h['applied_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($h['reason']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="text-lg font-medium text-gray-700">No leave requests found</p>
                            <p class="text-sm text-gray-400 mt-1">Apply for leave to see your history here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>