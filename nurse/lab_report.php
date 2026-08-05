<?php
session_start();
include "../config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$user_id = $_SESSION["id"];

// Get nurse name
$nurseName = '';
$nurseQuery = "SELECT name FROM register WHERE id = " . $_SESSION['id'] . " AND (delete_flag=0 OR delete_flag IS NULL)";
$nurseResult = mysqli_query($conn, $nurseQuery);
if ($nurseResult && mysqli_num_rows($nurseResult) > 0) {
    $nurseData = mysqli_fetch_assoc($nurseResult);
    $nurseName = $nurseData['name'] ?? '';
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

// ========== VIEW REPORT DETAILS ==========
$report_details = null;
$report_tests = [];
if (isset($_GET['view_report'])) {
    $report_id = intval($_GET['view_report']);
    $sql_view = "SELECT r.*, p.patient_name, p.mobile as patient_mobile, p.gender, p.date_of_birth, p.address,
                 d.doctor_name, d.qualification, d.mobile as doctor_mobile,
                 s.name as technician_name,
                 o.order_no, o.order_date
                 FROM lab_reports r
                 LEFT JOIN patients p ON r.patient_id = p.patient_id
                 LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
                 LEFT JOIN staff s ON r.technician_id = s.staff_id
                 LEFT JOIN lab_orders o ON r.order_id = o.order_id
                 WHERE r.report_id = $report_id AND r.hospital_id = $hid";
    $result_view = $conn->query($sql_view);
    if ($result_view && $result_view->num_rows > 0) {
        $report_details = $result_view->fetch_assoc();
        
        // Get tests for this report
        $sql_tests = "SELECT od.*, t.test_code, t.test_name, t.normal_range as test_normal_range, t.unit,
                      tr.result_value, tr.remarks as result_remarks,
                      tr.report_status as test_report_status
                      FROM lab_order_details od
                      LEFT JOIN lab_tests t ON od.test_id = t.test_id
                      LEFT JOIN lab_test_results tr ON od.detail_id = tr.order_detail_id
                      WHERE od.order_id = " . $report_details['order_id'];
        $result_tests = $conn->query($sql_tests);
        if ($result_tests) {
            while ($row = $result_tests->fetch_assoc()) {
                $report_tests[] = $row;
            }
        }
    }
}

// ========== GET REPORTS LIST ==========
$reports = [];
$sql_reports = "SELECT r.*, p.patient_name, p.mobile as patient_mobile,
                d.doctor_name, d.qualification,
                s.name as technician_name,
                o.order_no,
                (SELECT COUNT(*) FROM lab_order_details WHERE order_id = r.order_id) as test_count
                FROM lab_reports r
                LEFT JOIN patients p ON r.patient_id = p.patient_id
                LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
                LEFT JOIN staff s ON r.technician_id = s.staff_id
                LEFT JOIN lab_orders o ON r.order_id = o.order_id
                WHERE r.hospital_id = $hid
                ORDER BY r.report_id DESC";
$result_reports = $conn->query($sql_reports);
if ($result_reports) {
    while ($row = $result_reports->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Reports</title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) { .main-content { padding: 12px; } }
        
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            transition: all 0.2s ease;
        }
        .back-btn:hover { background: #f3f4f6; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background: #f8fafc;
        }
        
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.draft { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.corrected { background: #dbeafe; color: #1e40af; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.in_process { background: #e0f2fe; color: #0369a1; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        
        /* ===== REPORT VIEW STYLES (Same as Prescription) ===== */
        .report-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .report-header {
            padding: 32px 40px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        
        .report-header .hospital-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .report-header .hospital-info img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .report-header .hospital-info h2 {
            font-size: 22px;
            font-weight: 800;
            color: #2563eb;
            margin: 0;
        }
        
        .report-header .hospital-info p {
            color: #6b7280;
            font-size: 13px;
            margin: 2px 0;
        }
        
        .report-header .report-meta {
            text-align: right;
        }
        
        .report-header .report-meta .report-title {
            font-size: 28px;
            font-weight: 900;
            color: #f3f4f6;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            user-select: none;
        }
        
        .report-header .report-meta .meta-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        
        .report-header .report-meta .meta-value {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }
        
        .report-header .report-meta .meta-value.highlight {
            color: #2563eb;
            font-size: 18px;
        }
        
        .report-patient-section {
            padding: 24px 40px;
            background: #f8fafc;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .report-patient-section .section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        
        .report-patient-section .patient-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        
        .report-patient-section .patient-detail {
            font-size: 14px;
            color: #4b5563;
            margin: 2px 0;
        }
        
        .report-patient-section .followup-text {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
            margin: 0;
        }
        
        .report-tests-section {
            padding: 32px 40px;
        }
        
        .report-tests-section .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 16px;
        }
        
        .report-tests-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .report-tests-table thead th {
            background: #f1f5f9;
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .report-tests-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1f2937;
        }
        
        .report-tests-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .report-tests-table .test-result {
            font-weight: 700;
        }
        
        .report-tests-table .test-result.normal { color: #16a34a; }
        .report-tests-table .test-result.abnormal { color: #dc2626; }
        
        .report-remarks-section {
            padding: 0 40px 32px 40px;
        }
        
        .report-remarks-section .remarks-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px 20px;
        }
        
        .report-remarks-section .remarks-box p {
            font-size: 14px;
            color: #1e293b;
            font-style: italic;
            margin: 0;
        }
        
        .report-footer {
            background: #0f172a;
            color: white;
            text-align: center;
            padding: 20px;
        }
        
        .report-footer p {
            margin: 0;
        }
        
        .report-footer .footer-bold {
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .report-footer .footer-sub {
            font-size: 12px;
            color: #94a3b8;
        }
        
        @media print {
            .no-print { display: none !important; }
            .report-container { box-shadow: none !important; border: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            body { background: white; }
            .report-header .report-meta .report-title { color: #e5e7eb !important; }
        }
        
        @media (max-width: 768px) {
            .report-header {
                flex-direction: column;
                padding: 20px;
            }
            .report-header .report-meta { text-align: left; }
            .report-patient-section {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            .report-tests-section { padding: 20px; }
            .report-remarks-section { padding: 0 20px 20px 20px; }
            .report-tests-table { font-size: 12px; }
            .report-tests-table thead th,
            .report-tests-table tbody td { padding: 8px 10px; }
        }
        
        /* Action buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .action-btn:hover {
            background: #f3f4f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .action-btn i { font-size: 16px; }
        
        .action-btn.primary {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .action-btn.primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }
        
        .action-btn.success {
            background: #16a34a;
            color: white;
            border-color: #16a34a;
        }
        
        .action-btn.success:hover {
            background: #15803d;
            border-color: #15803d;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <div class="no-print"><?php include '../header.php'; ?></div>
        <div class="flex flex-1 items-start">
            <div class="no-print"><?php include '../Sidebar.php'; ?></div>
            <main class="main-content">
                
                <?php if ($report_details): ?>
                    <!-- ===== SINGLE REPORT VIEW ===== -->
                    <div class="max-w-6xl mx-auto">
                        
                        <!-- Navigation -->
                        <div class="flex items-center justify-between mb-6 no-print">
                            <a href="lab_report.php" class="text-gray-500 hover:text-gray-700 flex items-center gap-2 transition-colors">
                                <i class="fas fa-arrow-left"></i>
                                Back to Reports
                            </a>
                            <div class="flex gap-3">
                                <button onclick="window.print()" class="action-btn">
                                    <i class="fas fa-print"></i>
                                    Print Report
                                </button>
                                <?php if ($report_details['report_file']): ?>
                                    <a href="../documents/reports/<?php echo $report_details['report_file']; ?>" 
                                       download class="action-btn success">
                                        <i class="fas fa-download"></i>
                                        Download PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Report Container -->
                        <div class="report-container">
                            <!-- Header -->
                            <div class="report-header">
                                <div class="hospital-info">
                                    <img src="../<?php echo htmlspecialchars($hospital_logo); ?>" alt="Hospital Logo">
                                    <div>
                                        <h2><?php echo htmlspecialchars($hospital_name); ?></h2>
                                        <p><?php echo htmlspecialchars($hospital_address); ?></p>
                                        <p><?php echo htmlspecialchars($hospital_phone); ?> | <?php echo htmlspecialchars($hospital_email); ?></p>
                                    </div>
                                </div>
                                <div class="report-meta">
                                    <div class="report-title">Lab Report</div>
                                    <div class="meta-label">Report Number</div>
                                    <div class="meta-value highlight">#<?php echo htmlspecialchars($report_details['report_no']); ?></div>
                                    <div class="meta-label" style="margin-top: 8px;">Date Issued</div>
                                    <div class="meta-value"><?php echo date('F d, Y', strtotime($report_details['report_date'])); ?></div>
                                </div>
                            </div>

                            <!-- Patient Info -->
                            <div class="report-patient-section">
                                <div>
                                    <div class="section-label">Patient Details</div>
                                    <p class="patient-name"><?php echo htmlspecialchars($report_details['patient_name']); ?></p>
                                    <p class="patient-detail">Gender: <?php echo htmlspecialchars($report_details['gender'] ?? 'N/A'); ?></p>
                                    <p class="patient-detail">Contact: <?php echo htmlspecialchars($report_details['patient_mobile'] ?? ''); ?></p>
                                    <p class="patient-detail">Address: <?php echo htmlspecialchars($report_details['address'] ?? ''); ?></p>
                                </div>
                                <div>
                                    <div class="section-label">Order Details</div>
                                    <p class="patient-detail"><strong>Order No:</strong> <?php echo htmlspecialchars($report_details['order_no']); ?></p>
                                    <p class="patient-detail"><strong>Doctor:</strong> <?php echo htmlspecialchars($report_details['doctor_name']); ?></p>
                                    <p class="patient-detail"><strong>Qualification:</strong> <?php echo htmlspecialchars($report_details['qualification'] ?? ''); ?></p>
                                    <p class="patient-detail"><strong>Technician:</strong> <?php echo htmlspecialchars($report_details['technician_name'] ?? 'Not Assigned'); ?></p>
                                    <div style="margin-top: 8px;">
                                        <span class="status-badge <?php echo strtolower($report_details['report_status']); ?>">
                                            <?php echo htmlspecialchars($report_details['report_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Test Results -->
                            <div class="report-tests-section">
                                <div class="section-title">Test Results</div>
                                <?php if (!empty($report_tests)): ?>
                                    <table class="report-tests-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Test Code</th>
                                                <th>Test Name</th>
                                                <th>Result</th>
                                                <th>Normal Range</th>
                                                <th>Unit</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $t_counter = 1; ?>
                                            <?php foreach ($report_tests as $test): 
                                                $result_value = $test['result_value'] ?? 'Pending';
                                                $is_pending = ($result_value == 'Pending' || empty($result_value));
                                            ?>
                                                <tr>
                                                    <td><?php echo $t_counter++; ?></td>
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code']); ?></span></td>
                                                    <td><strong><?php echo htmlspecialchars($test['test_name']); ?></strong></td>
                                                    <td>
                                                        <?php if ($is_pending): ?>
                                                            <span class="text-gray-400">Pending</span>
                                                        <?php else: ?>
                                                            <span class="test-result <?php echo ($test['test_report_status'] ?? '') == 'Abnormal' ? 'abnormal' : 'normal'; ?>">
                                                                <?php echo htmlspecialchars($result_value); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($test['normal_range'] ?? $test['test_normal_range'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($test['unit'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php 
                                                        $report_class = strtolower(str_replace(' ', '_', $test['test_report_status'] ?? 'Pending'));
                                                        ?>
                                                        <span class="status-badge <?php echo $report_class; ?>">
                                                            <?php echo htmlspecialchars($test['test_report_status'] ?? 'Pending'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-8">No test results available for this report.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Remarks -->
                            <?php if ($report_details['remarks']): ?>
                            <div class="report-remarks-section">
                                <div class="remarks-box">
                                    <p>"<?php echo htmlspecialchars($report_details['remarks']); ?>"</p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Footer -->
                            <div class="report-footer">
                                <p class="footer-bold"><?php echo htmlspecialchars($hospital_name); ?></p>
                                <p class="footer-sub">This is a digitally generated lab report for clinical use. | Report #<?php echo htmlspecialchars($report_details['report_no']); ?></p>
                            </div>
                        </div>

                        <p class="text-center mt-6 text-gray-400 text-sm no-print">
                            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($hospital_name); ?> - All Rights Reserved.
                        </p>
                    </div>

                <?php else: ?>
                    <!-- ===== REPORTS LIST VIEW ===== -->
                    
                    <!-- Page Header -->
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4">
                            <a href="dashboard.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">
                                    Lab Reports
                                </h1>
                                <p class="text-gray-500 mt-1">
                                    Welcome, <?php echo htmlspecialchars($nurseName ?: 'Nurse'); ?> - View all lab reports
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-alt mr-2 text-blue-500"></i> All Reports</h3>
                            <span class="text-sm text-gray-500">Total: <?php echo count($reports); ?> reports</span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($reports)): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Report No</th>
                                                <th>Patient</th>
                                                <th>Doctor</th>
                                                <th>Technician</th>
                                                <th>Date</th>
                                                <th>Tests</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $counter = 1; ?>
                                            <?php foreach ($reports as $report): ?>
                                                <tr class="clickable-row" onclick="window.location='?view_report=<?php echo $report['report_id']; ?>'">
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($report['report_no']); ?></span></td>
                                                    <td>
                                                        <div class="font-medium"><?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?></div>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report['patient_mobile'] ?? ''); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="font-medium"><?php echo htmlspecialchars($report['doctor_name'] ?? 'N/A'); ?></div>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report['qualification'] ?? ''); ?></div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($report['technician_name'] ?? 'Not Assigned'); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($report['report_date'])); ?></td>
                                                    <td><span class="badge-count"><?php echo $report['test_count']; ?> tests</span></td>
                                                    <td>
                                                        <?php 
                                                        $status_class = strtolower($report['report_status']);
                                                        ?>
                                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($report['report_status']); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-alt"></i>
                                    <p class="text-lg font-medium">No reports found</p>
                                    <p class="text-sm text-gray-400">No lab reports available at the moment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer Stats -->
                    <div class="mt-4 flex flex-wrap justify-between items-center gap-3 text-sm text-gray-500">
                        <span>Showing <?php echo count($reports); ?> report(s)</span>
                        <span>Last updated: <?php echo date('d M Y, h:i A'); ?></span>
                    </div>

                    <footer class="mt-8 text-center text-gray-400 text-[10px] md:text-xs pb-6">
                        &copy; <?php echo date('Y'); ?> Hospital Management System
                    </footer>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script>
        // Close modal if it exists (for backward compatibility)
        function closeModal(id) {
            var modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('show');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(function(el) {
                    el.classList.remove('show');
                });
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>