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
$hospital_address = $hospitalData['address'] ?? '';

// ========== GET SALARY ID ==========
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: generate_salary.php');
    exit();
}

$sql = "SELECT s.*, e.first_name, e.last_name, e.employee_code, e.email, e.mobile, e.department_id, d.department_name
        FROM salary_generated s
        JOIN employees e ON s.employee_id = e.employee_id
        LEFT JOIN department d ON e.department_id = d.id
        WHERE s.id = $id AND s.hospital_id = $hospital_id AND s.delete_flag = 0";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    header('Location: generate_salary.php');
    exit();
}
$salary = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Salary Slip</title>
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
        .card-body { padding: 24px; }
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .welcome-section { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .slip-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 20px; }
        .slip-header .hospital-info h2 { font-size: 24px; font-weight: 700; color: #0f172a; }
        .slip-header .hospital-info p { color: #64748b; font-size: 14px; }
        .slip-title { font-size: 20px; font-weight: 600; color: #0f172a; }
        .slip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .slip-grid .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #f1f5f9; }
        .slip-grid .info-row .label { color: #64748b; font-weight: 500; }
        .slip-grid .info-row .value { font-weight: 600; }
        .slip-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .slip-table th, .slip-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
        .slip-table th { background: #f8fafc; font-weight: 600; color: #4b5563; font-size: 13px; }
        .slip-table .total-row td { font-weight: 700; border-top: 2px solid #3b82f6; }
        .slip-table .positive { color: #22c55e; }
        .slip-table .negative { color: #ef4444; }
        .slip-footer { margin-top: 24px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        @media print {
            body { background: white; }
            .no-print { display: none; }
            .main-content { padding: 0; }
            .welcome-section { background: none; color: black; padding: 0; margin-bottom: 16px; }
            .welcome-section h1 { color: black; }
            .welcome-section .btn-primary { display: none; }
            .card { border: none; box-shadow: none; }
            .card-header { border-bottom: 2px solid #000; }
        }
    </style>
</head>
<body>

<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <?php include '../header.php'; ?>
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <div class="welcome-section no-print">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-file-invoice mr-3 text-white"></i> Salary Slip</h1>
                        <p>View salary details for <?php echo date('F Y', strtotime($salary['month_year'])); ?></p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="window.print()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="generate_salary.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body" id="slip-content">
                    <!-- Slip Header -->
                    <div class="slip-header">
                        <div class="hospital-info">
                            <h2><?php echo htmlspecialchars($hospital_name); ?></h2>
                            <p><?php echo htmlspecialchars($hospital_address); ?></p>
                            <p class="text-sm">Salary Slip for the month of <?php echo date('F Y', strtotime($salary['month_year'])); ?></p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Status: <span class="font-semibold <?php echo $salary['status'] == 'Finalized' ? 'text-green-600' : 'text-yellow-600'; ?>"><?php echo $salary['status']; ?></span></div>
                            <div class="text-sm text-gray-500">Generated: <?php echo date('d M Y', strtotime($salary['generated_date'])); ?></div>
                        </div>
                    </div>

                    <!-- Employee Info -->
                    <div class="slip-grid mb-4">
                        <div>
                            <div class="info-row"><span class="label">Employee</span><span class="value"><?php echo htmlspecialchars($salary['first_name'] . ' ' . $salary['last_name']); ?></span></div>
                            <div class="info-row"><span class="label">Employee Code</span><span class="value"><?php echo htmlspecialchars($salary['employee_code']); ?></span></div>
                            <div class="info-row"><span class="label">Department</span><span class="value"><?php echo htmlspecialchars($salary['department_name'] ?? 'N/A'); ?></span></div>
                        </div>
                        <div>
                            <div class="info-row"><span class="label">Email</span><span class="value"><?php echo htmlspecialchars($salary['email'] ?? '—'); ?></span></div>
                            <div class="info-row"><span class="label">Mobile</span><span class="value"><?php echo htmlspecialchars($salary['mobile'] ?? '—'); ?></span></div>
                            <div class="info-row"><span class="label">Month</span><span class="value"><?php echo date('F Y', strtotime($salary['month_year'])); ?></span></div>
                        </div>
                    </div>

                    <!-- Earnings & Deductions Table -->
                    <table class="slip-table">
                        <thead>
                            <tr>
                                <th>Earnings</th>
                                <th>Amount</th>
                                <th>Deductions</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Basic</td>
                                <td><?php echo number_format($salary['basic'], 2); ?></td>
                                <td>PF</td>
                                <td><?php echo number_format($salary['pf'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>HRA</td>
                                <td><?php echo number_format($salary['hra'], 2); ?></td>
                                <td>ESI</td>
                                <td><?php echo number_format($salary['esi'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>DA</td>
                                <td><?php echo number_format($salary['da'], 2); ?></td>
                                <td>Professional Tax</td>
                                <td><?php echo number_format($salary['professional_tax'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Medical</td>
                                <td><?php echo number_format($salary['medical'], 2); ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Bonus</td>
                                <td><?php echo number_format($salary['bonus'], 2); ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Other Allowance</td>
                                <td><?php echo number_format($salary['other_allowance'], 2); ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>Gross Salary</strong></td>
                                <td><strong><?php echo number_format($salary['gross_salary'], 2); ?></strong></td>
                                <td><strong>Total Deductions</strong></td>
                                <td><strong><?php echo number_format($salary['total_deductions'], 2); ?></strong></td>
                            </tr>
                            <tr class="total-row" style="background: #f0f4ff;">
                                <td colspan="2"></td>
                                <td><strong>Net Salary</strong></td>
                                <td><strong class="text-green-600"><?php echo number_format($salary['net_salary'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Footer -->
                    <div class="slip-footer">
                        This is a computer-generated salary slip. No signature required.
                        <br>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($hospital_name); ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>