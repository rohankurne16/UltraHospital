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
    $check = $conn->query("SELECT id FROM salary_structures WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE salary_structures SET delete_flag = 1 WHERE id = $id");
        header('Location: salary_structure.php?deleted=1');
        exit();
    }
}

// ========== HANDLE ADD/EDIT ==========
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM salary_structures WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $basic = floatval($_POST['basic'] ?? 0);
    $hra = floatval($_POST['hra'] ?? 0);
    $da = floatval($_POST['da'] ?? 0);
    $medical = floatval($_POST['medical'] ?? 0);
    $bonus = floatval($_POST['bonus'] ?? 0);
    $other_allowance = floatval($_POST['other_allowance'] ?? 0);
    $pf = floatval($_POST['pf'] ?? 0);
    $esi = floatval($_POST['esi'] ?? 0);
    $professional_tax = floatval($_POST['professional_tax'] ?? 0);
    $effective_date = $_POST['effective_date'] ?? date('Y-m-d');

    if ($employee_id <= 0) {
        $message = 'Please select an employee.';
        $message_type = 'error';
    } else {
        // Check if structure already exists for this employee (active)
        $exists = $conn->query("SELECT id FROM salary_structures WHERE employee_id = $employee_id AND hospital_id = $hospital_id AND delete_flag = 0");
        if ($exists && $exists->num_rows > 0 && $edit_id == 0) {
            $message = 'Salary structure already exists for this employee. You can edit it.';
            $message_type = 'error';
        } else {
            if ($edit_id > 0) {
                $sql = "UPDATE salary_structures SET 
                        employee_id = $employee_id,
                        basic = $basic, hra = $hra, da = $da, medical = $medical, bonus = $bonus,
                        other_allowance = $other_allowance, pf = $pf, esi = $esi, professional_tax = $professional_tax,
                        effective_date = '$effective_date'
                        WHERE id = $edit_id AND hospital_id = $hospital_id";
                if ($conn->query($sql)) {
                    $message = 'Salary structure updated successfully.';
                    $message_type = 'success';
                    $edit_data = null;
                    $res = $conn->query("SELECT * FROM salary_structures WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
                    if ($res && $res->num_rows > 0) {
                        $edit_data = $res->fetch_assoc();
                    }
                } else {
                    $message = 'Error: ' . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $sql = "INSERT INTO salary_structures 
                        (employee_id, basic, hra, da, medical, bonus, other_allowance, pf, esi, professional_tax, effective_date, hospital_id) 
                        VALUES ($employee_id, $basic, $hra, $da, $medical, $bonus, $other_allowance, $pf, $esi, $professional_tax, '$effective_date', $hospital_id)";
                if ($conn->query($sql)) {
                    $message = 'Salary structure added successfully.';
                    $message_type = 'success';
                    $_POST = [];
                } else {
                    $message = 'Error: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }
}

// ========== FETCH EMPLOYEES ==========
$employees = [];
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name FROM employees WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY first_name";
$emp_res = $conn->query($emp_sql);
if ($emp_res) {
    while ($row = $emp_res->fetch_assoc()) {
        $employees[] = $row;
    }
}

// ========== FETCH STRUCTURES ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["s.hospital_id = $hospital_id", "s.delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.employee_code LIKE '%$search%')";
}
$where_clause = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) as total FROM salary_structures s JOIN employees e ON s.employee_id = e.employee_id WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT s.*, e.first_name, e.last_name, e.employee_code 
        FROM salary_structures s
        JOIN employees e ON s.employee_id = e.employee_id
        WHERE $where_clause
        ORDER BY e.first_name ASC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$structures = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $structures[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Salary Structure</title>
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
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        @media (max-width: 1024px) { .form-row { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .search-box { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .search-box input { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; min-width: 200px; }
        .search-box input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
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
        .modal-content { background: white; border-radius: 12px; max-width: 700px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; }
        .modal-close:hover { color: #1f2937; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        .salary-summary { background: #f8fafc; border-radius: 8px; padding: 12px 16px; margin-top: 16px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .salary-summary .item { text-align: center; padding: 4px; }
        .salary-summary .label { font-size: 11px; color: #64748b; text-transform: uppercase; }
        .salary-summary .value { font-size: 18px; font-weight: 700; }
        .value.positive { color: #22c55e; }
        .value.negative { color: #ef4444; }
        .value.neutral { color: #3b82f6; }
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
                        <h1><i class="fas fa-coins mr-3 text-white"></i> Salary Structure</h1>
                        <p>Define salary components (Basic, HRA, DA, etc.) for each employee</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-plus"></i> Add Structure
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
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Employee Salary Structures</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> records</span>
                </div>
                <div class="card-body">
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search by employee name or code..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="salary_structure.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <?php if (!empty($structures)): ?>
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
                                        <th>Other Allow.</th>
                                        <th>PF</th>
                                        <th>ESI</th>
                                        <th>Prof. Tax</th>
                                        <th>Net Salary</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($structures as $s): 
                                        $gross = $s['basic'] + $s['hra'] + $s['da'] + $s['medical'] + $s['bonus'] + $s['other_allowance'];
                                        $deductions = $s['pf'] + $s['esi'] + $s['professional_tax'];
                                        $net = $gross - $deductions;
                                    ?>
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
                                            <td><strong><?php echo number_format($net, 2); ?></strong></td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="editStructure(<?php echo $s['id']; ?>)" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteStructure(<?php echo $s['id']; ?>)" class="btn-danger btn-xs" title="Delete">
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
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-coins"></i>
                            <p class="text-lg font-medium text-gray-700">No salary structures defined</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Structure" to set up salary components for employees.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== ADD/EDIT MODAL ========== -->
<div class="modal" id="structureModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle mr-2 text-blue-500"></i> Add Salary Structure</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="salary_structure.php" id="structureForm">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            <div class="form-group">
                <label>Employee <span class="required">*</span></label>
                <select name="employee_id" id="employee_id" class="form-control" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Effective Date <span class="required">*</span></label>
                <input type="date" name="effective_date" id="effective_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Basic <span class="required">*</span></label>
                    <input type="number" name="basic" id="basic" class="form-control salary-input" step="0.01" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label>HRA</label>
                    <input type="number" name="hra" id="hra" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>DA</label>
                    <input type="number" name="da" id="da" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Medical</label>
                    <input type="number" name="medical" id="medical" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Bonus</label>
                    <input type="number" name="bonus" id="bonus" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Other Allowance</label>
                    <input type="number" name="other_allowance" id="other_allowance" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>PF (Deduction)</label>
                    <input type="number" name="pf" id="pf" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>ESI (Deduction)</label>
                    <input type="number" name="esi" id="esi" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Professional Tax</label>
                    <input type="number" name="professional_tax" id="professional_tax" class="form-control salary-input" step="0.01" min="0" value="0">
                </div>
            </div>
            <div class="salary-summary">
                <div class="item">
                    <div class="label">Gross Salary</div>
                    <div class="value neutral" id="gross_display">0.00</div>
                </div>
                <div class="item">
                    <div class="label">Total Deductions</div>
                    <div class="value negative" id="deductions_display">0.00</div>
                </div>
                <div class="item">
                    <div class="label">Net Salary</div>
                    <div class="value positive" id="net_display">0.00</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// ========== REAL-TIME SALARY CALCULATION ==========
document.querySelectorAll('.salary-input').forEach(input => {
    input.addEventListener('input', calculateSalary);
});

function calculateSalary() {
    const basic = parseFloat(document.getElementById('basic').value) || 0;
    const hra = parseFloat(document.getElementById('hra').value) || 0;
    const da = parseFloat(document.getElementById('da').value) || 0;
    const medical = parseFloat(document.getElementById('medical').value) || 0;
    const bonus = parseFloat(document.getElementById('bonus').value) || 0;
    const other = parseFloat(document.getElementById('other_allowance').value) || 0;
    const pf = parseFloat(document.getElementById('pf').value) || 0;
    const esi = parseFloat(document.getElementById('esi').value) || 0;
    const pt = parseFloat(document.getElementById('professional_tax').value) || 0;

    const gross = basic + hra + da + medical + bonus + other;
    const deductions = pf + esi + pt;
    const net = gross - deductions;

    document.getElementById('gross_display').textContent = gross.toFixed(2);
    document.getElementById('deductions_display').textContent = deductions.toFixed(2);
    document.getElementById('net_display').textContent = net.toFixed(2);
}

// ========== MODAL CONTROLS ==========
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle mr-2 text-blue-500"></i> Add Salary Structure';
    document.getElementById('edit_id').value = 0;
    document.getElementById('structureForm').reset();
    document.getElementById('effective_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('employee_id').value = '';
    // Reset all salary inputs to 0
    document.querySelectorAll('.salary-input').forEach(inp => inp.value = 0);
    calculateSalary();
    document.getElementById('structureModal').classList.add('show');
}

function editStructure(id) {
    fetch('salary_structure_ajax.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-500"></i> Edit Salary Structure';
                document.getElementById('edit_id').value = data.id;
                document.getElementById('employee_id').value = data.employee_id;
                document.getElementById('effective_date').value = data.effective_date;
                document.getElementById('basic').value = data.basic;
                document.getElementById('hra').value = data.hra;
                document.getElementById('da').value = data.da;
                document.getElementById('medical').value = data.medical;
                document.getElementById('bonus').value = data.bonus;
                document.getElementById('other_allowance').value = data.other_allowance;
                document.getElementById('pf').value = data.pf;
                document.getElementById('esi').value = data.esi;
                document.getElementById('professional_tax').value = data.professional_tax;
                calculateSalary();
                document.getElementById('structureModal').classList.add('show');
            } else {
                alert('Error loading structure data.');
            }
        })
        .catch(() => alert('Network error.'));
}

function deleteStructure(id) {
    if (confirm('Are you sure you want to delete this salary structure?')) {
        window.location.href = 'salary_structure.php?delete=' + id;
    }
}

function closeModal() {
    document.getElementById('structureModal').classList.remove('show');
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Initial calculation on load (if modal has values)
window.addEventListener('load', calculateSalary);
</script>

</body>
</html>