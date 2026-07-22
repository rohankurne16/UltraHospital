<?php
session_start();
include "../config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["id"];
$hid = $_SESSION["hospital_id"];

// Get doctor info
$doctor_sql = "SELECT doctor_id, doctor_name, qualification FROM doctor WHERE doctor_id = $user_id AND hospital_id = $hid";
$doctor_result = $conn->query($doctor_sql);
if ($doctor_result && $doctor_result->num_rows > 0) {
    $doctor = $doctor_result->fetch_assoc();
} else {
    $staff_sql = "SELECT staff_id, name as doctor_name, role as qualification FROM staff WHERE staff_id = $user_id AND hospital_id = $hid";
    $staff_result = $conn->query($staff_sql);
    if ($staff_result && $staff_result->num_rows > 0) {
        $doctor = $staff_result->fetch_assoc();
    } else {
        $doctor = ['doctor_name' => 'Doctor', 'qualification' => ''];
    }
}

// Get hospital data
$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";
$hospital_address = $hospital_data["address"] ?? "";
$hospital_phone = $hospital_data["phone"] ?? "";
$hospital_email = $hospital_data["email"] ?? "";

// ========== GET ALL REPORTS FOR THIS DOCTOR ==========
$reports = [];
$sql_reports = "SELECT r.*, o.order_no, p.patient_name, p.mobile, p.gender,
                (SELECT COUNT(*) FROM lab_order_details WHERE order_id = o.order_id) as test_count,
                s.name as technician_name
                FROM lab_reports r
                LEFT JOIN lab_orders o ON r.order_id = o.order_id
                LEFT JOIN patients p ON r.patient_id = p.patient_id
                LEFT JOIN staff s ON r.technician_id = s.staff_id
                WHERE o.doctor_id = $user_id 
                AND o.hospital_id = $hid
                AND (o.delete_flag = 0 OR o.delete_flag IS NULL)
                ORDER BY r.report_id DESC";
$result_reports = $conn->query($sql_reports);
if ($result_reports) {
    while ($row = $result_reports->fetch_assoc()) {
        $reports[] = $row;
    }
}

// ========== GET REPORT DETAILS FOR VIEW MODAL ==========
$report_detail = null;
if (isset($_GET['view_report']) && isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    $sql_detail = "SELECT r.*, o.order_no, p.patient_name, p.mobile, p.gender, p.date_of_birth, p.address,
                   d.doctor_name, d.qualification, s.name as technician_name
                   FROM lab_reports r
                   LEFT JOIN lab_orders o ON r.order_id = o.order_id
                   LEFT JOIN patients p ON r.patient_id = p.patient_id
                   LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
                   LEFT JOIN staff s ON r.technician_id = s.staff_id
                   WHERE r.report_id = $report_id AND o.doctor_id = $user_id";
    $result_detail = $conn->query($sql_detail);
    if ($result_detail && $result_detail->num_rows > 0) {
        $report_detail = $result_detail->fetch_assoc();
        
        // Get test results for this report
        $sql_tests = "SELECT od.*, t.test_name, t.test_code, t.normal_range as test_normal_range, t.unit,
                      r2.result_value, r2.normal_range, r2.remarks as result_remarks
                      FROM lab_order_details od
                      LEFT JOIN lab_tests t ON od.test_id = t.test_id
                      LEFT JOIN lab_test_results r2 ON od.detail_id = r2.order_detail_id
                      WHERE od.order_id = " . $report_detail['order_id'] . "
                      ORDER BY od.detail_id";
        $result_tests = $conn->query($sql_tests);
        $test_results = [];
        if ($result_tests) {
            while ($row = $result_tests->fetch_assoc()) {
                $test_results[] = $row;
            }
        }
    }
}

// ========== DELETE REPORT ==========
if (isset($_GET['delete_report']) && isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    $conn->query("DELETE FROM lab_reports WHERE report_id = $report_id");
    $_SESSION['success'] = "Report deleted successfully!";
    header("Location: doctor_lab_reports.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Reports</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-info { background: #0ea5e9; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-info:hover { background: #0284c7; }
        .btn-secondary { background: #e5e7eb; color: #374151; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .print-btn { background: #8b5cf6; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .print-btn:hover { background: #7c3aed; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .badge-completed { background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .actions-cell { white-space: nowrap; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 4px 8px; }
        .modal-close:hover { color: #1f2937; }
        
        .report-header { background: linear-gradient(135deg, #1e40af, #2563eb); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .report-header .title { font-size: 20px; font-weight: 700; }
        .report-header .subtitle { opacity: 0.9; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        @media (max-width: 640px) { .info-grid { grid-template-columns: 1fr; } }
        
        .result-normal { color: #16a34a; font-weight: 600; }
        .result-abnormal { color: #dc2626; font-weight: 600; }
        .result-pending { color: #f59e0b; font-weight: 600; }
        
        .status-badge.completed { background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.pending { background: #fef3c7; color: #92400e; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        
        .welcome-section { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 768px) { .stat-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
        
        .stat-card { background: white; border-radius: 12px; padding: 16px; border: 1px solid #e5e7eb; text-align: center; }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 13px; margin-top: 2px; }
        .stat-card .stat-icon { font-size: 20px; margin-bottom: 4px; }
        
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .quick-action-btn { padding: 12px 20px; border-radius: 10px; border: 1px solid #e5e7eb; background: white; cursor: pointer; transition: all 0.2s; text-align: center; flex: 1; min-width: 120px; text-decoration: none; color: #1f2937; }
        .quick-action-btn:hover { background: #f1f5f9; border-color: #3b82f6; transform: translateY(-2px); }
        .quick-action-btn i { font-size: 24px; display: block; margin-bottom: 6px; }
        .quick-action-btn .label { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h1><i class="fas fa-file-alt mr-2"></i> Lab Reports</h1>
                    <p>View, download and print patient lab reports</p>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="lab_order.php" class="quick-action-btn">
                        <i class="fas fa-plus-circle text-blue-500"></i>
                        <div class="label">Create Lab Order</div>
                    </a>
                    <a href="doctor_lab_orders.php" class="quick-action-btn">
                        <i class="fas fa-list text-green-500"></i>
                        <div class="label">My Orders</div>
                    </a>
                    <a href="doctor_lab_reports.php" class="quick-action-btn">
                        <i class="fas fa-file-alt text-purple-500"></i>
                        <div class="label">Lab Reports</div>
                    </a>
                </div>

                <!-- Statistics -->
                <?php 
                $total_reports = count($reports);
                $completed_reports = 0;
                foreach ($reports as $r) {
                    if ($r['report_status'] == 'Completed') $completed_reports++;
                }
                ?>
                <div class="stat-grid">
                    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                        <div class="stat-icon text-blue-500"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-number text-blue-600"><?php echo $total_reports; ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #22c55e;">
                        <div class="stat-icon text-green-500"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number text-green-600"><?php echo $completed_reports; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                        <div class="stat-icon text-yellow-500"><i class="fas fa-clock"></i></div>
                        <div class="stat-number text-yellow-600"><?php echo $total_reports - $completed_reports; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt mr-2 text-purple-500"></i> All Reports</h3>
                        <span class="badge-count"><?php echo count($reports); ?> reports</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reports)): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Report No</th>
                                            <th>Order No</th>
                                            <th>Patient</th>
                                            <th>Tests</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($report['report_no']); ?></span></td>
                                                <td><?php echo htmlspecialchars($report['order_no'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report['mobile'] ?? ''); ?></div>
                                                </td>
                                                <td><span class="badge-count"><?php echo $report['test_count']; ?> tests</span></td>
                                                <td><?php echo date('d-m-Y', strtotime($report['report_date'])); ?></td>
                                                <td>
                                                    <span class="badge-completed"><?php echo htmlspecialchars($report['report_status']); ?></span>
                                                </td>
                                                <td class="actions-cell">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <!-- View Report -->
                                                        <a href="?view_report=1&report_id=<?php echo $report['report_id']; ?>" class="btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <!-- Download Report -->
                                                        <?php if (!empty($report['report_file'])): ?>
                                                            <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" download class="btn-success btn-sm" title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 text-xs" title="No file attached">No file</span>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Print Report -->
                                                        <a href="print_report.php?order_id=<?php echo $report['order_id']; ?>" target="_blank" class="print-btn btn-sm" title="Print Report">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                        
                                                        <!-- View Previous Reports -->
                                                        <a href="view_previous_reports.php?patient_id=<?php echo $report['patient_id']; ?>" class="btn-secondary btn-sm" title="View Previous Reports">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                        
                                                        <!-- Delete Report -->
                                                        <a href="?delete_report=1&report_id=<?php echo $report['report_id']; ?>" class="btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this report?')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <p class="text-lg font-medium text-gray-700">No reports found</p>
                                <p class="text-sm text-gray-400 mt-1">Reports will appear here after lab tests are completed</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ========== VIEW REPORT MODAL ========== -->
    <?php if ($report_detail): ?>
    <div class="modal show" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-alt mr-2 text-blue-500"></i> Report Details</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <!-- Report Header -->
            <div class="report-header">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <div class="title"><?php echo htmlspecialchars($hospital_name); ?></div>
                        <div class="subtitle">Lab Report - <?php echo htmlspecialchars($report_detail['report_no']); ?></div>
                    </div>
                    <div class="flex gap-2 mt-2 md:mt-0">
                        <a href="print_report.php?order_id=<?php echo $report_detail['order_id']; ?>" target="_blank" class="btn-primary" style="background: rgba(255,255,255,0.2); color: white; padding: 4px 12px; font-size: 12px;">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </div>
                </div>
            </div>

            <!-- Patient & Order Info -->
            <div class="info-grid">
                <div>
                    <h4 class="text-sm font-semibold text-gray-500">Patient Information</h4>
                    <p class="font-medium"><?php echo htmlspecialchars($report_detail['patient_name'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($report_detail['mobile'] ?? ''); ?></p>
                    <p class="text-sm text-gray-600">Gender: <?php echo htmlspecialchars($report_detail['gender'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-gray-600">DOB: <?php echo htmlspecialchars($report_detail['date_of_birth'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-500">Order Details</h4>
                    <p class="font-medium">Order #<?php echo htmlspecialchars($report_detail['order_no']); ?></p>
                    <p class="text-sm text-gray-600">Report Date: <?php echo date('d-m-Y', strtotime($report_detail['report_date'])); ?></p>
                    <p class="text-sm text-gray-600">Technician: <?php echo htmlspecialchars($report_detail['technician_name'] ?? 'N/A'); ?></p>
                    <?php if (!empty($report_detail['remarks'])): ?>
                        <p class="text-sm text-gray-600">Remarks: <?php echo htmlspecialchars($report_detail['remarks']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Test Results -->
            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-500 mb-2">Test Results</h4>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Test Code</th>
                                <th>Test Name</th>
                                <th>Result</th>
                                <th>Normal Range</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($test_results)): ?>
                                <?php foreach ($test_results as $test): ?>
                                    <tr>
                                        <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                        <td>
                                            <?php if (!empty($test['result_value'])): ?>
                                                <span class="font-medium"><?php echo htmlspecialchars($test['result_value']); ?></span>
                                                <?php if (!empty($test['result_remarks'])): ?>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($test['result_remarks']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="result-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($test['normal_range'] ?? $test['test_normal_range'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($test['unit'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">No test results found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report File -->
            <?php if (!empty($report_detail['report_file'])): ?>
                <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="font-semibold text-sm">Attached Report:</span>
                    <a href="../documents/reports/<?php echo htmlspecialchars($report_detail['report_file']); ?>" target="_blank" class="btn-info btn-sm ml-2">
                        <i class="fas fa-file-pdf"></i> View PDF
                    </a>
                    <a href="../documents/reports/<?php echo htmlspecialchars($report_detail['report_file']); ?>" download class="btn-success btn-sm">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            <?php endif; ?>

            <div class="modal-footer flex justify-end gap-2 mt-4 pt-3 border-t border-gray-200">
                <button onclick="closeModal()" class="btn-secondary btn-sm">Close</button>
                <a href="print_report.php?order_id=<?php echo $report_detail['order_id']; ?>" target="_blank" class="print-btn btn-sm">
                    <i class="fas fa-print"></i> Print Report
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // ========== CLOSE MODAL ==========
        function closeModal() {
            document.getElementById('reportModal').classList.remove('show');
            // Remove query parameter from URL
            if (window.history && window.history.pushState) {
                window.history.pushState('', '', window.location.pathname);
            }
        }

        // ========== CLOSE MODAL ON ESC ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // ========== CLOSE MODAL ON CLICK OUTSIDE ==========
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>