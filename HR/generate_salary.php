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

$message = '';
$message_type = '';

// ========== HANDLE GENERATION ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $month_year = $_POST['month_year'] ?? date('Y-m-01');
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : [];

    if (empty($month_year)) {
        $message = 'Please select a month/year.';
        $message_type = 'error';
    } else {
        // Get all active employees if no specific selected
        if (empty($employee_ids)) {
            $emp_sql = "SELECT employee_id FROM employees WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active'";
            $emp_res = $conn->query($emp_sql);
            if ($emp_res) {
                while ($row = $emp_res->fetch_assoc()) {
                    $employee_ids[] = $row['employee_id'];
                }
            }
        }

        if (empty($employee_ids)) {
            $message = 'No active employees found.';
            $message_type = 'error';
        } else {
            $generated_count = 0;
            $skipped_count = 0;

            foreach ($employee_ids as $emp_id) {
                // Get latest salary structure for this employee
                $struct_sql = "SELECT * FROM salary_structures 
                               WHERE employee_id = $emp_id AND hospital_id = $hospital_id AND delete_flag = 0
                               ORDER BY effective_date DESC LIMIT 1";
                $struct_res = $conn->query($struct_sql);
                if ($struct_res && $struct_res->num_rows > 0) {
                    $struct = $struct_res->fetch_assoc();

                    // Check if salary already generated for this month
                    $check_sql = "SELECT id FROM salary_generated 
                                  WHERE employee_id = $emp_id AND month_year = '$month_year' AND hospital_id = $hospital_id AND delete_flag = 0";
                    $check_res = $conn->query($check_sql);
                    if ($check_res && $check_res->num_rows > 0) {
                        // Update existing
                        $id = $check_res->fetch_assoc()['id'];
                        $update_sql = "UPDATE salary_generated SET 
                                       basic = {$struct['basic']}, hra = {$struct['hra']}, da = {$struct['da']},
                                       medical = {$struct['medical']}, bonus = {$struct['bonus']}, other_allowance = {$struct['other_allowance']},
                                       pf = {$struct['pf']}, esi = {$struct['esi']}, professional_tax = {$struct['professional_tax']},
                                       gross_salary = " . ($struct['basic'] + $struct['hra'] + $struct['da'] + $struct['medical'] + $struct['bonus'] + $struct['other_allowance']) . ",
                                       total_deductions = " . ($struct['pf'] + $struct['esi'] + $struct['professional_tax']) . ",
                                       net_salary = " . ($struct['basic'] + $struct['hra'] + $struct['da'] + $struct['medical'] + $struct['bonus'] + $struct['other_allowance'] - $struct['pf'] - $struct['esi'] - $struct['professional_tax']) . "
                                       WHERE id = $id";
                        if ($conn->query($update_sql)) {
                            $generated_count++;
                        } else {
                            $skipped_count++;
                        }
                    } else {
                        // Insert new
                        $gross = $struct['basic'] + $struct['hra'] + $struct['da'] + $struct['medical'] + $struct['bonus'] + $struct['other_allowance'];
                        $deductions = $struct['pf'] + $struct['esi'] + $struct['professional_tax'];
                        $net = $gross - $deductions;

                        $insert_sql = "INSERT INTO salary_generated 
                                       (employee_id, month_year, basic, hra, da, medical, bonus, other_allowance, pf, esi, professional_tax,
                                        gross_salary, total_deductions, net_salary, hospital_id)
                                       VALUES ($emp_id, '$month_year', 
                                               {$struct['basic']}, {$struct['hra']}, {$struct['da']}, {$struct['medical']}, {$struct['bonus']}, {$struct['other_allowance']},
                                               {$struct['pf']}, {$struct['esi']}, {$struct['professional_tax']},
                                               $gross, $deductions, $net, $hospital_id)";
                        if ($conn->query($insert_sql)) {
                            $generated_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                } else {
                    $skipped_count++;
                }
            }

            $message = "Salary generated/updated for $generated_count employees. Skipped: $skipped_count (no structure).";
            $message_type = 'success';
        }
    }
}

// ========== FETCH EMPLOYEES FOR SELECTION ==========
$employees = [];
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name FROM employees WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY first_name";
$emp_res = $conn->query($emp_sql);
if ($emp_res) {
    while ($row = $emp_res->fetch_assoc()) {
        $employees[] = $row;
    }
}

// ========== FETCH GENERATED SALARIES FOR DISPLAY ==========
$month_filter = isset($_GET['month']) ? $_GET['month'] : date('Y-m-01');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = ["s.hospital_id = $hospital_id", "s.delete_flag = 0"];
if (!empty($month_filter)) {
    $where[] = "s.month_year = '$month_filter'";
}
if (!empty($status_filter)) {
    $where[] = "s.status = '$status_filter'";
}
$where_clause = implode(" AND ", $where);

$sql = "SELECT s.*, e.first_name, e.last_name, e.employee_code 
        FROM salary_generated s
        JOIN employees e ON s.employee_id = e.employee_id
        WHERE $where_clause
        ORDER BY e.first_name ASC";
$result = $conn->query($sql);
$salaries = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $salaries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Generate Salary</title>
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
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end; }
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
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .welcome-section { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        .employee-select { max-height: 200px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px; }
        .employee-select label { display: block; padding: 4px 0; font-size: 14px; }
        .employee-select input[type="checkbox"] { margin-right: 8px; }
        .summary-box { background: #f8fafc; border-radius: 8px; padding: 16px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 16px; }
        .summary-box .item { text-align: center; }
        .summary-box .value { font-size: 24px; font-weight: 700; }
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
                        <h1><i class="fas fa-cogs mr-3 text-white"></i> Generate Salary</h1>
                        <p>Generate monthly salary for all employees based on their salary structure</p>
                    </div>
                    <a href="salary_history.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-history"></i> History
                    </a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Generation Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Generate for Month</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="filter-grid">
                            <div>
                                <label>Month/Year <span class="required">*</span></label>
                                <input type="month" name="month_year" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                            </div>
                            <div>
                                <label>Select Employees (optional)</label>
                                <div class="employee-select">
                                    <label><input type="checkbox" id="selectAll" onchange="toggleAll(this)"> Select All</label>
                                    <?php foreach ($employees as $emp): ?>
                                        <label>
                                            <input type="checkbox" name="employee_ids[]" value="<?php echo $emp['employee_id']; ?>">
                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="flex items-end pb-1">
                                <button type="submit" name="generate" class="btn-primary"><i class="fas fa-cogs"></i> Generate Salary</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Generated Salaries -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Generated Salaries</h3>
                    <span class="badge-count"><?php echo count($salaries); ?> records</span>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-grid mb-4">
                        <div>
                            <label>Month</label>
                            <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month_filter); ?>" onchange="this.form.submit()">
                        </div>
                        <div>
                            <label>Status</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="Draft" <?php echo ($status_filter == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="Finalized" <?php echo ($status_filter == 'Finalized') ? 'selected' : ''; ?>>Finalized</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-1">
                            <a href="generate_salary.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                        </div>
                    </form>

                    <?php if (!empty($salaries)): 
                        $total_gross = 0;
                        $total_net = 0;
                        $total_deductions = 0;
                        foreach ($salaries as $s) {
                            $total_gross += $s['gross_salary'];
                            $total_net += $s['net_salary'];
                            $total_deductions += $s['total_deductions'];
                        }
                    ?>
                        <div class="summary-box">
                            <div class="item">
                                <div class="label">Total Gross</div>
                                <div class="value text-blue-600"><?php echo number_format($total_gross, 2); ?></div>
                            </div>
                            <div class="item">
                                <div class="label">Total Deductions</div>
                                <div class="value text-red-600"><?php echo number_format($total_deductions, 2); ?></div>
                            </div>
                            <div class="item">
                                <div class="label">Total Net Payable</div>
                                <div class="value text-green-600"><?php echo number_format($total_net, 2); ?></div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Basic</th>
                                        <th>HRA</th>
                                        <th>DA</th>
                                        <th>Medical</th>
                                        <th>Bonus</th>
                                        <th>Other</th>
                                        <th>PF</th>
                                        <th>ESI</th>
                                        <th>PT</th>
                                        <th>Gross</th>
                                        <th>Net</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($salaries as $s): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?><br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($s['employee_code']); ?></span></td>
                                            <td><?php echo number_format($s['basic'], 2); ?></td>
                                            <td><?php echo number_format($s['hra'], 2); ?></td>
                                            <td><?php echo number_format($s['da'], 2); ?></td>
                                            <td><?php echo number_format($s['medical'], 2); ?></td>
                                            <td><?php echo number_format($s['bonus'], 2); ?></td>
                                            <td><?php echo number_format($s['other_allowance'], 2); ?></td>
                                            <td><?php echo number_format($s['pf'], 2); ?></td>
                                            <td><?php echo number_format($s['esi'], 2); ?></td>
                                            <td><?php echo number_format($s['professional_tax'], 2); ?></td>
                                            <td><strong><?php echo number_format($s['gross_salary'], 2); ?></strong></td>
                                            <td><strong class="text-green-600"><?php echo number_format($s['net_salary'], 2); ?></strong></td>
                                            <td><span class="status-badge <?php echo $s['status']; ?>"><?php echo $s['status']; ?></span></td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <a href="salary_slip.php?id=<?php echo $s['id']; ?>" class="btn-primary btn-xs" title="View Slip">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                    <?php if ($s['status'] == 'Draft'): ?>
                                                        <button onclick="finalizeSalary(<?php echo $s['id']; ?>)" class="btn-success btn-xs" title="Finalize">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <p class="text-lg font-medium text-gray-700">No salaries generated for this month</p>
                            <p class="text-sm text-gray-400 mt-1">Use the form above to generate salaries.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function toggleAll(source) {
    document.querySelectorAll('input[name="employee_ids[]"]').forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

function finalizeSalary(id) {
    if (confirm('Finalize this salary? This action cannot be undone.')) {
        window.location.href = 'generate_salary.php?finalize=' + id;
    }
}
</script>

<?php
// ========== HANDLE FINALIZE ==========
if (isset($_GET['finalize']) && intval($_GET['finalize']) > 0) {
    $id = intval($_GET['finalize']);
    $sql = "UPDATE salary_generated SET status = 'Finalized' WHERE id = $id AND hospital_id = $hospital_id";
    if ($conn->query($sql)) {
        header('Location: generate_salary.php?finalized=1');
        exit();
    }
}
?>
</body>
</html>