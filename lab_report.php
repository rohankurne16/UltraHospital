<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$user_id = $_SESSION["id"];

// Get hospital data
$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";

// ========== GENERATE REPORT NO ==========
function generateReportNo($conn) {
    $prefix = "RPT";
    $date = date("Ymd");
    $sql = "SELECT MAX(report_no) as max_no FROM lab_reports WHERE report_no LIKE '$prefix$date%'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['max_no']) {
            $num = intval(substr($row['max_no'], -4)) + 1;
            return $prefix . $date . str_pad($num, 4, '0', STR_PAD_LEFT);
        }
    }
    return $prefix . $date . '0001';
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

// ========== GET COMPLETED ORDERS FOR REPORT CREATION ==========
$orders = [];
$sql_orders = "SELECT o.*, p.patient_name, d.doctor_name 
               FROM lab_orders o
               LEFT JOIN patients p ON o.patient_id = p.patient_id
               LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
               WHERE o.order_status = 'Completed' AND o.delete_flag = 0 AND o.hospital_id = $hid
               ORDER BY o.order_id DESC";
$result_orders = $conn->query($sql_orders);
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) {
        $orders[] = $row;
    }
}

// ========== GET TECHNICIANS ==========
$technicians = [];
$sql_technicians = "SELECT * FROM staff WHERE delete_flag = 0 AND role = 'Lab Technician' AND hospital_id = $hid ORDER BY name";
$result_technicians = $conn->query($sql_technicians);
if ($result_technicians) {
    while ($row = $result_technicians->fetch_assoc()) {
        $technicians[] = $row;
    }
}

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

// ========== PATIENT HISTORY ==========
$patient_history = null;
$patient_history_data = [];
if (isset($_GET['patient_history'])) {
    $patient_id = intval($_GET['patient_history']);
    if ($patient_id > 0) {
        $sql_history = "SELECT r.*, o.order_no, o.order_date,
                        d.doctor_name,
                        (SELECT COUNT(*) FROM lab_order_details WHERE order_id = r.order_id) as test_count
                        FROM lab_reports r
                        LEFT JOIN lab_orders o ON r.order_id = o.order_id
                        LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
                        WHERE r.patient_id = $patient_id AND r.hospital_id = $hid
                        ORDER BY r.report_id DESC";
        $result_history = $conn->query($sql_history);
        if ($result_history) {
            while ($row = $result_history->fetch_assoc()) {
                $patient_history_data[] = $row;
            }
        }
        // Get patient details
        $sql_patient = "SELECT patient_name, mobile, gender FROM patients WHERE patient_id = $patient_id";
        $result_patient = $conn->query($sql_patient);
        if ($result_patient && $result_patient->num_rows > 0) {
            $patient_history = $result_patient->fetch_assoc();
        }
    }
}

// ========== CREATE REPORT ==========
if (isset($_POST['create_report'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $technician_id = intval($_POST['technician_id'] ?? 0);
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');
    
    if ($order_id > 0 && $patient_id > 0 && $doctor_id > 0) {
        $report_no = generateReportNo($conn);
        $report_file = '';
        
        // Handle file upload
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $target_dir = "../documents/reports/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_name = time() . '_' . basename($_FILES['report_file']['name']);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_file)) {
                $report_file = $file_name;
            }
        }
        
        $sql = "INSERT INTO lab_reports (order_id, patient_id, doctor_id, technician_id, report_no, report_date, report_file, report_status, remarks, hospital_id) 
                VALUES ($order_id, $patient_id, $doctor_id, " . ($technician_id > 0 ? $technician_id : "NULL") . ", '$report_no', '$report_date', '$report_file', 'Completed', '$remarks', $hid)";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Report #$report_no created successfully!";
            header("Location: lab_reports.php");
            exit();
        } else {
            $_SESSION['error'] = "Error creating report: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Please fill all required fields";
    }
    header("Location: lab_reports.php");
    exit();
}

// ========== UPLOAD CORRECTED REPORT ==========
if (isset($_POST['upload_corrected'])) {
    $report_id = intval($_POST['report_id'] ?? 0);
    
    if ($report_id > 0 && isset($_FILES['corrected_file']) && $_FILES['corrected_file']['error'] == 0) {
        $target_dir = "../documents/reports/corrected/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_corrected_' . basename($_FILES['corrected_file']['name']);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['corrected_file']['tmp_name'], $target_file)) {
            $sql = "UPDATE lab_reports SET 
                    corrected_report_file = '$file_name',
                    report_status = 'Corrected',
                    corrected_by = $user_id,
                    corrected_date = NOW()
                    WHERE report_id = $report_id AND hospital_id = $hid";
            
            if ($conn->query($sql)) {
                $_SESSION['success'] = "Corrected report uploaded successfully!";
            } else {
                $_SESSION['error'] = "Error updating report: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Error uploading file";
        }
    } else {
        $_SESSION['error'] = "Please select a file to upload";
    }
    header("Location: lab_reports.php");
    exit();
}

// ========== DELETE REPORT ==========
if (isset($_GET['delete_report'])) {
    $report_id = intval($_GET['delete_report']);
    if ($report_id > 0) {
        $conn->query("DELETE FROM lab_reports WHERE report_id = $report_id AND hospital_id = $hid");
        $_SESSION['success'] = "Report deleted successfully!";
    }
    header("Location: lab_reports.php");
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
        
        .form-input, .form-select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-warning:hover { background: #d97706; }
        .btn-info { background: #0ea5e9; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-info:hover { background: #0284c7; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.draft { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.corrected { background: #dbeafe; color: #1e40af; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.in_process { background: #e0f2fe; color: #0369a1; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 700px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 4px 8px; }
        .modal-close:hover { color: #1f2937; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
        .info-item { padding: 12px 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6; }
        .info-item .label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px; }
        .info-item .value { font-size: 14px; font-weight: 500; color: #1f2937; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .price-badge { font-weight: 600; color: #059669; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .actions-cell { white-space: nowrap; }
        
        .report-preview { background: #f9fafb; border-radius: 8px; padding: 20px; border: 1px solid #e5e7eb; }
        .report-preview .header { text-align: center; border-bottom: 2px solid #d1d5db; padding-bottom: 16px; margin-bottom: 16px; }
        .report-preview .header h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .report-preview .header p { color: #6b7280; font-size: 14px; }
        .report-preview .detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .report-preview .detail-row .label { font-weight: 500; color: #6b7280; }
        .report-preview .detail-row .value { font-weight: 500; color: #1f2937; }
        .report-preview .test-table { width: 100%; margin-top: 12px; font-size: 13px; }
        .report-preview .test-table th { background: #f1f5f9; padding: 8px 12px; text-align: left; }
        .report-preview .test-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Lab Reports</h1>
                        <p class="text-gray-500 mt-1">Manage all lab test reports</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button onclick="openCreateReportModal()" class="btn-primary">
                            <i class="fas fa-plus"></i> Create Report
                        </button>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

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
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
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
                                                    <?php if ($report['report_status'] == 'Corrected'): ?>
                                                        <br><span class="text-xs text-blue-600">Corrected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="actions-cell">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <!-- View Report -->
                                                        <a href="?view_report=<?php echo $report['report_id']; ?>" 
                                                           class="btn-info btn-sm" title="View Report">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <!-- Print Report -->
                                                        <a href="print_report.php?id=<?php echo $report['report_id']; ?>" 
                                                           target="_blank" class="btn-warning btn-sm" title="Print Report">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                        
                                                        <!-- Download Report -->
                                                        <?php if ($report['report_file']): ?>
                                                            <a href="../documents/reports/<?php echo $report['report_file']; ?>" 
                                                               download class="btn-success btn-sm" title="Download Report">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Upload Corrected Report -->
                                                        <button onclick="openCorrectedModal(<?php echo $report['report_id']; ?>)" 
                                                                class="btn-warning btn-sm" title="Upload Corrected Report">
                                                            <i class="fas fa-upload"></i>
                                                        </button>
                                                        
                                                        <!-- Patient History -->
                                                        <a href="?patient_history=<?php echo $report['patient_id']; ?>" 
                                                           class="btn-info btn-sm" title="Patient History">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                        
                                                        <!-- Delete Report -->
                                                        <a href="?delete_report=<?php echo $report['report_id']; ?>" 
                                                           class="btn-danger btn-sm" title="Delete Report" 
                                                           onclick="return confirm('Are you sure you want to delete this report?');">
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
                                <p class="text-sm text-gray-400 mt-1">Click "Create Report" to generate your first report</p>
                                <button onclick="openCreateReportModal()" class="btn-primary mt-3">
                                    <i class="fas fa-plus"></i> Create Report
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== VIEW REPORT MODAL ========== -->
                <?php if ($report_details): ?>
                <div class="modal show" id="viewReportModal">
                    <div class="modal-content" style="max-width: 900px;">
                        <div class="modal-header">
                            <h2><i class="fas fa-file-alt mr-2 text-blue-500"></i> Report Details</h2>
                            <button class="modal-close" onclick="closeModal('viewReportModal')">&times;</button>
                        </div>
                        <div class="card-body">
                            <!-- Report Preview -->
                            <div class="report-preview">
                                <div class="header">
                                    <h2><?php echo htmlspecialchars($hospital_name); ?></h2>
                                    <p>Lab Report - <?php echo htmlspecialchars($report_details['report_no']); ?></p>
                                </div>
                                
                                <div class="info-grid" style="margin-bottom: 16px;">
                                    <div class="info-item">
                                        <span class="label">Patient</span>
                                        <span class="value"><?php echo htmlspecialchars($report_details['patient_name']); ?></span>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report_details['mobile'] ?? ''); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Doctor</span>
                                        <span class="value"><?php echo htmlspecialchars($report_details['doctor_name']); ?></span>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report_details['qualification'] ?? ''); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Technician</span>
                                        <span class="value"><?php echo htmlspecialchars($report_details['technician_name'] ?? 'Not Assigned'); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Order No</span>
                                        <span class="value"><?php echo htmlspecialchars($report_details['order_no']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Report Date</span>
                                        <span class="value"><?php echo date('d-m-Y', strtotime($report_details['report_date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Status</span>
                                        <span class="value">
                                            <?php 
                                            $status_class = strtolower($report_details['report_status']);
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($report_details['report_status']); ?></span>
                                        </span>
                                    </div>
                                </div>

                                <?php if (!empty($report_tests)): ?>
                                <h4 class="font-semibold text-sm mb-2">Test Results</h4>
                                <table class="test-table">
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
                                        <?php foreach ($report_tests as $test): ?>
                                            <tr>
                                                <td><?php echo $t_counter++; ?></td>
                                                <td><?php echo htmlspecialchars($test['test_code']); ?></td>
                                                <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                                <td><strong><?php echo htmlspecialchars($test['result_value'] ?? 'Pending'); ?></strong></td>
                                                <td><?php echo htmlspecialchars($test['normal_range'] ?? $test['test_normal_range'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($test['unit'] ?? '-'); ?></td>
                                                <td>
                                                    <?php 
                                                    $report_class = strtolower(str_replace(' ', '_', $test['test_report_status'] ?? 'Pending'));
                                                    ?>
                                                    <span class="status-badge <?php echo $report_class; ?>"><?php echo htmlspecialchars($test['test_report_status'] ?? 'Pending'); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>

                                <?php if ($report_details['remarks']): ?>
                                <div class="mt-3 p-3 bg-gray-50 rounded border border-gray-200">
                                    <span class="font-medium text-sm">Remarks:</span>
                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($report_details['remarks']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($report_details['report_file']): ?>
                                <div class="mt-3">
                                    <a href="../documents/reports/<?php echo $report_details['report_file']; ?>" 
                                       target="_blank" class="btn-info btn-sm">
                                        <i class="fas fa-file"></i> View Attached File
                                    </a>
                                </div>
                                <?php endif; ?>

                                <?php if ($report_details['corrected_report_file']): ?>
                                <div class="mt-2">
                                    <a href="../documents/reports/corrected/<?php echo $report_details['corrected_report_file']; ?>" 
                                       target="_blank" class="btn-success btn-sm">
                                        <i class="fas fa-file"></i> View Corrected Report
                                    </a>
                                    <span class="text-xs text-gray-500 ml-2">
                                        Corrected on: <?php echo date('d-m-Y H:i', strtotime($report_details['corrected_date'])); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="print_report.php?id=<?php echo $report_details['report_id']; ?>" 
                               target="_blank" class="btn-warning">
                                <i class="fas fa-print"></i> Print
                            </a>
                            <button onclick="closeModal('viewReportModal')" class="btn-outline">Close</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========== PATIENT HISTORY MODAL ========== -->
                <?php if ($patient_history && !empty($patient_history_data)): ?>
                <div class="modal show" id="historyModal">
                    <div class="modal-content" style="max-width: 800px;">
                        <div class="modal-header">
                            <h2><i class="fas fa-history mr-2 text-blue-500"></i> Patient History</h2>
                            <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-4 p-3 bg-blue-50 rounded border border-blue-200">
                                <strong>Patient:</strong> <?php echo htmlspecialchars($patient_history['patient_name']); ?>
                                <?php if ($patient_history['mobile']): ?>
                                    | <strong>Mobile:</strong> <?php echo htmlspecialchars($patient_history['mobile']); ?>
                                <?php endif; ?>
                                <?php if ($patient_history['gender']): ?>
                                    | <strong>Gender:</strong> <?php echo htmlspecialchars($patient_history['gender']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Report No</th>
                                            <th>Order No</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Tests</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($patient_history_data as $history): ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($history['report_no']); ?></span></td>
                                                <td><?php echo htmlspecialchars($history['order_no']); ?></td>
                                                <td><?php echo htmlspecialchars($history['doctor_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($history['report_date'])); ?></td>
                                                <td><span class="badge-count"><?php echo $history['test_count']; ?> tests</span></td>
                                                <td>
                                                    <?php 
                                                    $status_class = strtolower($history['report_status']);
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($history['report_status']); ?></span>
                                                </td>
                                                <td>
                                                    <a href="?view_report=<?php echo $history['report_id']; ?>" 
                                                       class="btn-info btn-sm" title="View Report">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button onclick="closeModal('historyModal')" class="btn-outline">Close</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========== CREATE REPORT MODAL ========== -->
                <div class="modal" id="createReportModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fas fa-plus-circle mr-2 text-green-500"></i> Create New Report</h2>
                            <button class="modal-close" onclick="closeModal('createReportModal')">&times;</button>
                        </div>
                        <form method="POST" action="lab_reports.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Select Completed Order <span class="required">*</span></label>
                                <select class="form-select" name="order_id" required onchange="loadOrderDetails(this.value)">
                                    <option value="">Select Order</option>
                                    <?php foreach ($orders as $order): ?>
                                        <option value="<?php echo $order['order_id']; ?>" 
                                                data-patient-id="<?php echo $order['patient_id']; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($order['patient_name']); ?>"
                                                data-doctor-id="<?php echo $order['doctor_id']; ?>"
                                                data-doctor-name="<?php echo htmlspecialchars($order['doctor_name']); ?>">
                                            <?php echo htmlspecialchars($order['order_no'] . ' - ' . $order['patient_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Patient <span class="required">*</span></label>
                                    <input type="text" class="form-input" id="patient_name" readonly style="background: #f3f4f6;">
                                    <input type="hidden" name="patient_id" id="patient_id" value="">
                                </div>
                                <div class="form-group">
                                    <label>Doctor <span class="required">*</span></label>
                                    <input type="text" class="form-input" id="doctor_name" readonly style="background: #f3f4f6;">
                                    <input type="hidden" name="doctor_id" id="doctor_id" value="">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Technician</label>
                                    <select class="form-select" name="technician_id">
                                        <option value="">Select Technician (Optional)</option>
                                        <?php foreach ($technicians as $t): ?>
                                            <option value="<?php echo $t['staff_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Report Date</label>
                                    <input type="date" class="form-input" name="report_date" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Upload Report File</label>
                                <input type="file" class="form-input" name="report_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-gray-500">Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</small>
                            </div>

                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea class="form-input" name="remarks" rows="3" placeholder="Additional notes..."></textarea>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn-outline" onclick="closeModal('createReportModal')">Cancel</button>
                                <button type="submit" name="create_report" class="btn-primary">
                                    <i class="fas fa-save"></i> Create Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ========== UPLOAD CORRECTED REPORT MODAL ========== -->
                <div class="modal" id="correctedModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fas fa-upload mr-2 text-yellow-500"></i> Upload Corrected Report</h2>
                            <button class="modal-close" onclick="closeModal('correctedModal')">&times;</button>
                        </div>
                        <form method="POST" action="lab_reports.php" enctype="multipart/form-data">
                            <input type="hidden" name="report_id" id="corrected_report_id">
                            <div class="form-group">
                                <label>Select Corrected Report File <span class="required">*</span></label>
                                <input type="file" class="form-input" name="corrected_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <small class="text-gray-500">Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-outline" onclick="closeModal('correctedModal')">Cancel</button>
                                <button type="submit" name="upload_corrected" class="btn-warning">
                                    <i class="fas fa-upload"></i> Upload Corrected Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // ========== LOAD ORDER DETAILS ==========
        function loadOrderDetails(orderId) {
            var select = document.getElementById('order_id');
            var option = select.querySelector('option[value="' + orderId + '"]');
            if (option) {
                document.getElementById('patient_id').value = option.getAttribute('data-patient-id') || '';
                document.getElementById('patient_name').value = option.getAttribute('data-patient-name') || '';
                document.getElementById('doctor_id').value = option.getAttribute('data-doctor-id') || '';
                document.getElementById('doctor_name').value = option.getAttribute('data-doctor-name') || '';
            }
        }

        // ========== MODAL FUNCTIONS ==========
        function openCreateReportModal() {
            document.getElementById('createReportModal').classList.add('show');
        }

        function openCorrectedModal(reportId) {
            document.getElementById('corrected_report_id').value = reportId;
            document.getElementById('correctedModal').classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        // ========== CLOSE MODAL ON ESC ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(function(el) {
                    el.classList.remove('show');
                });
            }
        });

        // ========== CLICK OUTSIDE MODAL ==========
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>