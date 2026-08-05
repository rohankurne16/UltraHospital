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
$sql_hospital = "SELECT * FROM hospital_master WHERE hospital_id = $hid AND delete_flag = 0 LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/hospital_1784169924_6a5845c46a419.jpg";
$hospital_address = $hospital_data["address"] ?? "";
$hospital_phone = $hospital_data["phone"] ?? "";
$hospital_email = $hospital_data["email"] ?? "";
$hospital_city = $hospital_data["city"] ?? "";
$hospital_state = $hospital_data["state"] ?? "";
$hospital_pincode = $hospital_data["pincode"] ?? "";

// ========== VIEW REPORT DETAILS (AJAX) - MUST BE FIRST ==========
// ========== VIEW REPORT DETAILS (AJAX) - WITH HOSPITAL DATA ==========
if (isset($_GET['view_report']) && isset($_GET['report_id'])) {
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    
    $report_id = intval($_GET['report_id']);
    
    // Get report details
    $sql_detail = "SELECT r.*, o.order_no, o.order_date,
                   p.patient_name, p.mobile, p.gender, p.date_of_birth, p.address,
                   d.doctor_name, d.qualification, s.name as technician_name
                   FROM lab_reports r
                   LEFT JOIN lab_orders o ON r.order_id = o.order_id
                   LEFT JOIN patients p ON r.patient_id = p.patient_id
                   LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
                   LEFT JOIN staff s ON r.technician_id = s.staff_id
                   WHERE r.report_id = $report_id AND o.doctor_id = $user_id";
    $result_detail = $conn->query($sql_detail);
    
    if (!$result_detail) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    if ($result_detail && $result_detail->num_rows > 0) {
        $report_detail = $result_detail->fetch_assoc();
        $report_detail['report_id'] = $report_id;
        
        // Get test results
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
        
        // Add hospital data - Full address with city, state, pincode
        $full_address = $hospital_address;
        if (!empty($hospital_city)) $full_address .= ", " . $hospital_city;
        if (!empty($hospital_state)) $full_address .= ", " . $hospital_state;
        if (!empty($hospital_pincode)) $full_address .= " - " . $hospital_pincode;
        
        $report_detail['hospital_name'] = $hospital_name;
        $report_detail['hospital_logo'] = $hospital_logo;
        $report_detail['hospital_address'] = $full_address;
        $report_detail['hospital_phone'] = $hospital_phone;
        $report_detail['hospital_email'] = $hospital_email;
        $report_detail['test_results'] = $test_results;
        
        echo json_encode(['success' => true, 'report' => $report_detail]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Report not found or you don\'t have permission to view it']);
    }
    exit();
}

// ========== GET ALL REPORTS FOR THIS DOCTOR ==========
$reports = [];
$sql_reports = "SELECT r.*, o.order_no, p.patient_name, p.mobile, p.gender,
                (SELECT COUNT(*) FROM lab_order_details WHERE order_id = o.order_id AND delete_flag = 0) as test_count,
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

// ========== COUNT STATISTICS ==========
$total_reports = count($reports);
$completed_reports = 0;
$pending_reports = 0;
$today_reports = 0;
$today = date('Y-m-d');

foreach ($reports as $r) {
    if ($r['report_status'] == 'Completed') {
        $completed_reports++;
    } else {
        $pending_reports++;
    }
    if (isset($r['report_date']) && date('Y-m-d', strtotime($r['report_date'])) == $today) {
        $today_reports++;
    }
}

// ========== DELETE REPORT ==========
if (isset($_GET['delete_report']) && isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    $conn->query("DELETE FROM lab_reports WHERE report_id = $report_id");
    $_SESSION['success'] = "Report deleted successfully!";
    header("Location: doctor_lab_report.php");
    exit();
}

// ========== VIEW REPORT FILE DIRECTLY ==========
if (isset($_GET['view_file']) && isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    $sql = "SELECT report_file FROM lab_reports WHERE report_id = $report_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $file = $result->fetch_assoc();
        if (!empty($file['report_file'])) {
            $file_path = "../documents/reports/" . $file['report_file'];
            if (file_exists($file_path)) {
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                switch($ext) {
                    case 'pdf': header('Content-Type: application/pdf'); break;
                    case 'jpg': case 'jpeg': header('Content-Type: image/jpeg'); break;
                    case 'png': header('Content-Type: image/png'); break;
                    case 'doc': header('Content-Type: application/msword'); break;
                    case 'docx': header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'); break;
                    case 'xls': header('Content-Type: application/vnd.ms-excel'); break;
                    case 'xlsx': header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); break;
                    default: header('Content-Type: application/octet-stream');
                }
                header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit();
            } else {
                $_SESSION['error'] = "File not found!";
                header("Location: doctor_lab_report.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "No file attached to this report!";
            header("Location: doctor_lab_report.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Report not found!";
        header("Location: doctor_lab_report.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Reports</title>
  
    <link rel="icon" type="image/png" href="../<?php echo $hospital_data['hospital_logo'] ?? 'documents/hospital/hospital_1784169924_6a5845c46a419.jpg'; ?>">

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
        
        /* ===== TAB STYLES ===== */
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #64748b;
            text-decoration: none;
            user-select: none;
            white-space: nowrap;
        }
        .tab-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .tab-btn .badge-count {
            background: #e2e8f0;
            color: #64748b;
            padding: 1px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .tab-active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .tab-active:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-color: #2563eb;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
        }
        .tab-active .badge-count {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        .tab-inactive {
            background: #fff;
            color: #64748b;
            border-color: #e5e7eb;
        }
        .tab-inactive .badge-count {
            background: #e2e8f0;
            color: #64748b;
        }
        .tabs-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding: 16px 9px;
            margin-bottom: -31px;
        }
        .tabs-wrapper::-webkit-scrollbar {
            height: 4px;
        }
        .tabs-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .tabs-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .tabs-wrapper .flex-wrap {
            flex-wrap: nowrap;
        }
        @media (min-width: 640px) {
            .tabs-wrapper .flex-wrap {
                flex-wrap: wrap;
            }
        }
        /* ===== END TAB STYLES ===== */
        
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; color: #374151; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-secondary:hover { background: #d1d5db; }
        
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
            text-decoration: none;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .badge-completed { background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .actions-cell { white-space: nowrap; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }
        .page-header .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-top: 2px;
        }
        
        /* Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .action-btn i { font-size: 14px; }
        .action-btn.view { background-color: #e0f2fe; color: #0284c7; }
        .action-btn.view:hover { background-color: #bae6fd; transform: scale(1.05); }
        .action-btn.delete { background-color: #fee2e2; color: #dc2626; }
        .action-btn.delete:hover { background-color: #fecaca; transform: scale(1.05); }
        .action-btn.print { background-color: #f3e8ff; color: #7c3aed; }
        .action-btn.print:hover { background-color: #e9d5ff; transform: scale(1.05); }
        .action-btn.download { background-color: #d1fae5; color: #059669; }
        .action-btn.download:hover { background-color: #a7f3d0; transform: scale(1.05); }
        
        .no-file-text { color: #9ca3af; font-size: 12px; font-style: italic; }
        
        .fade-in { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== STANDARD REPORT STYLES ===== */
        .report-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .report-header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .report-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .report-header p {
            color: #6b7280;
            font-size: 13px;
            margin: 4px 0 0 0;
        }
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .report-info-item {
            display: flex;
            padding: 4px 0;
        }
        .report-info-label {
            font-weight: 600;
            color: #4b5563;
            width: 130px;
            flex-shrink: 0;
            font-size: 13px;
        }
        .report-info-value {
            color: #1f2937;
            font-size: 13px;
        }
        .report-info-value strong {
            font-weight: 600;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
        }
        .report-table th {
            background: #3b82f6;
            color: white;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }
        .report-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .report-table tr:hover {
            background: #eff6ff;
        }
        .report-result-highlight {
            font-weight: 700;
            color: #059669;
            font-size: 15px;
        }
        .report-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6b7280;
            flex-wrap: wrap;
            gap: 10px;
        }
        .report-signature {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 10px;
        }
        .report-signature .line {
            width: 200px;
            border-top: 1px solid #1f2937;
            margin: 5px 0;
        }
        .report-status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .report-status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }
        .report-status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        .report-status-badge.cancelled {
            background: #fecaca;
            color: #991b1b;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 850px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            position: relative;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 4px 8px;
        }
        .modal-close:hover {
            color: #1f2937;
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 640px) {
            .report-info-grid { grid-template-columns: 1fr; }
            .report-info-label { width: 100px; }
            .report-table th, .report-table td { padding: 6px 10px; font-size: 12px; }
            .modal-content { padding: 16px; }
            .report-container { padding: 16px; }
            .page-header h1 { font-size: 22px; }
            .action-btn { width: 28px; height: 28px; font-size: 12px; }
            .action-btn i { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    <!-- Page Header -->
                    <div class="page-header">
                        <a href="dashboard.php" class="back-btn" title="Go back to Dashboard">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1><i class="fas fa-file-alt text-blue-500 mr-2"></i> Lab Reports</h1>
                            <p class="subtitle">View, download and print patient lab reports</p>
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
                        <div class="card-body">
                            <!-- ===== TABS ===== -->
                            <div class="tabs-wrapper">
                                <div class="flex flex-wrap gap-2 mb-6" style="flex-wrap: nowrap;">
                                    <div class="tab-btn tab-active" id="tab-all" onclick="filterReports('all')">
                                        All <span class="badge-count"><?php echo $total_reports; ?></span>
                                    </div>
                                    <div class="tab-btn tab-inactive" id="tab-completed" onclick="filterReports('completed')">
                                        Completed <span class="badge-count"><?php echo $completed_reports; ?></span>
                                    </div>
                                    <div class="tab-btn tab-inactive" id="tab-pending" onclick="filterReports('pending')">
                                        Pending <span class="badge-count"><?php echo $pending_reports; ?></span>
                                    </div>
                                    <div class="tab-btn tab-inactive" id="tab-today" onclick="filterReports('today')">
                                        Today <span class="badge-count"><?php echo $today_reports; ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- ===== END TABS ===== -->

                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900" id="table-title">All Reports</h2>
                                    <p class="text-sm text-gray-500" id="table-subtitle">View and manage all your lab reports.</p>
                                </div>
                                <div class="relative flex-1 sm:flex-none">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" id="searchInput"
                                           placeholder="Search reports..."
                                           class="w-full sm:w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchReports()">
                                </div>
                            </div>

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
                                        <tbody id="reportsTableBody">
                                            <?php foreach ($reports as $report): 
                                                $status = $report['report_status'] ?? 'Pending';
                                                $statusClass = ($status == 'Completed') ? 'badge-completed' : 'badge-pending';
                                                $dataStatus = strtolower($status);
                                                $reportDate = $report['report_date'] ?? '';
                                            ?>
                                                <tr class="report-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in"
                                                    data-status="<?php echo $dataStatus; ?>"
                                                    data-date="<?php echo htmlspecialchars($reportDate); ?>"
                                                    data-patient="<?php echo strtolower($report['patient_name'] ?? ''); ?>"
                                                    data-report="<?php echo strtolower($report['report_no'] ?? ''); ?>">
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($report['report_no']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($report['order_no'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report['mobile'] ?? ''); ?></div>
                                                    </td>
                                                    <td><span class="badge-count"><?php echo $report['test_count']; ?> tests</span></td>
                                                    <td><?php echo date('d-m-Y', strtotime($reportDate)); ?></td>
                                                    <td>
                                                        <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                                    </td>
                                                    <td class="actions-cell" style="text-align: center;">
                                                        <div class="flex items-center gap-1 flex-wrap" style="justify-content: center;">
                                                            <!-- View Report Details Button -->
                                                            <button onclick="viewReport(<?php echo $report['report_id']; ?>)" 
                                                                    class="action-btn view" 
                                                                    title="View Report Details">
                                                               <i class="fas fa-eye"></i>
                                                            </button>
                                                            
                                                            <!-- View File Button -->
                                                            <?php if (!empty($report['report_file'])): ?>
                                                                <a href="?view_file=1&report_id=<?php echo $report['report_id']; ?>" 
                                                                   target="_blank" 
                                                                   class="action-btn view" 
                                                                   title="View Report File">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                           
                                                               
                                                            <?php endif; ?>
                                                            
                                                            <!-- Download Report -->
                                                            <?php if (!empty($report['report_file'])): ?>
                                                                <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" 
                                                                   download 
                                                                   class="action-btn download" 
                                                                   title="Download Report">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Print Report -->
                                                            <?php if (!empty($report['order_id'])): ?>
                                                              <button onclick="window.open('../printt_report.php?id=<?php echo $report['report_id']; ?>', '_blank')"
        class="action-btn print"
        title="Print Report">
    <i class="fas fa-print"></i>
</button>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Delete Report -->
                                                            <a href="?delete_report=1&report_id=<?php echo $report['report_id']; ?>" 
                                                               class="action-btn delete" 
                                                               onclick="return confirm('Are you sure you want to delete this report?')" 
                                                               title="Delete Report">
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
                            
                            <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                                <div>Showing <span id="visibleCount"><?php echo count($reports); ?></span> reports</div>
                                <div class="text-xs text-gray-400"><i class="fas fa-sync-alt mr-1"></i> Live updates</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ========== VIEW REPORT MODAL - STANDARD REPORT FORMAT ========== -->
    <div class="modal" id="viewModal">
        <div class="modal-content" style="max-width: 850px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-alt mr-2 text-blue-500"></i> Laboratory Report</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="viewReportContent">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                    <p class="mt-2 text-gray-500">Loading report details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('viewModal')">Close</button>
                <button onclick="printModalReport()" class="btn-primary">
    <i class="fas fa-print"></i> Print Report
</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
// ========== PRINT MODAL REPORT ==========
// ============================================================


let currentReportId = null;

function printModalReport() {
    if (!currentReportId) {
        alert('Report is not loaded yet.');
        return;
    }

    window.open(
        '../printt_report.php?id=' + currentReportId,
        '_blank'
    );
}    let currentFilter = 'all';
        const serverToday = '<?php echo date('Y-m-d'); ?>';

        function filterReports(filter) {
            currentFilter = filter;
            const rows = document.querySelectorAll('.report-row');
            let visibleCount = 0;

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });
            const activeTab = document.getElementById('tab-' + filter);
            if (activeTab) {
                activeTab.classList.remove('tab-inactive');
                activeTab.classList.add('tab-active');
            }

            const titles = {
                'all': ['All Reports', 'View all your lab reports'],
                'completed': ['Completed Reports', 'View completed reports'],
                'pending': ['Pending Reports', 'View pending reports'],
                'today': ['Today Reports', 'View reports generated today']
            };
            document.getElementById('table-title').textContent = titles[filter]?.[0] || 'All Reports';
            document.getElementById('table-subtitle').textContent = titles[filter]?.[1] || '';

            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            rows.forEach(row => {
                let show = true;
                const status = row.dataset.status;
                const date = row.dataset.date;
                const searchText = (row.dataset.patient || '') + ' ' + (row.dataset.report || '');

                switch (filter) {
                    case 'completed': show = status === 'completed'; break;
                    case 'pending': show = status === 'pending'; break;
                    case 'today': show = date === serverToday; break;
                    default: show = true;
                }

                if (show && (searchTerm === '' || searchText.includes(searchTerm))) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function searchReports() {
            filterReports(currentFilter);
        }

        // ========== VIEW REPORT - STANDARD FORMAT ==========
       // ========== VIEW REPORT - STANDARD FORMAT (EXACT SAME AS TECHNICIAN) ==========
function viewReport(reportId) {
    const modal = document.getElementById('viewModal');
    const content = document.getElementById('viewReportContent');
    
    modal.classList.add('show');
    content.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-2xl"></i>
            <p class="mt-2 text-gray-500">Loading report details...</p>
        </div>
    `;
    
    const url = window.location.pathname + '?view_report=1&report_id=' + reportId;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                content.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-circle text-2xl"></i>
                        <p class="mt-2">${data.error || 'Report not found'}</p>
                    </div>
                `;
                return;
            }
            
            const report = data.report;
            currentReportId = report.report_id;
        
            
            // Format dates
            let reportDate = report.report_date || 'N/A';
            if (reportDate !== 'N/A' && reportDate !== '0000-00-00') {
                let d = new Date(reportDate);
                reportDate = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            
            let orderDate = report.order_date || 'N/A';
            if (orderDate !== 'N/A' && orderDate !== '0000-00-00') {
                let d = new Date(orderDate);
                orderDate = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            
            let dob = report.date_of_birth || 'N/A';
            if (dob !== 'N/A' && dob !== '0000-00-00') {
                let d = new Date(dob);
                dob = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            
            let doctorName = report.doctor_name || 'Not Assigned';
            if (doctorName !== 'Not Assigned') {
                doctorName = doctorName.replace(/^Dr\.?\s*/i, '');
                doctorName = 'Dr. ' + doctorName;
            }
            
            let statusClass = (report.report_status || 'pending').toLowerCase();
            
            // Hospital info
            let hospitalLogo = report.hospital_logo || '../documents/hospital/hospital_1784169924_6a5845c46a419.jpg';
            let hospitalName = report.hospital_name || 'Hospital';
            let hospitalAddress = report.hospital_address || '';
            let hospitalPhone = report.hospital_phone || '';
            let hospitalEmail = report.hospital_email || '';
            
            let logoHtml = '';
            if (hospitalLogo) {
                logoHtml = `<img src="${hospitalLogo}" alt="Hospital Logo" style="max-height:70px; max-width:120px; object-fit:contain; margin-bottom:5px;">`;
            }
            
            // Build test results table
            let testRows = '';
            if (report.test_results && report.test_results.length > 0) {
                report.test_results.forEach((test, index) => {
                    testRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${test.test_name || 'N/A'}</strong><br>
                                <span style="font-size:11px;color:#6b7280;">Code: ${test.test_code || 'N/A'}</span>
                            </td>
                            <td>
                                <span class="report-result-highlight">${test.result_value || 'Not entered'}</span>
                            </td>
                            <td>${test.normal_range || test.test_normal_range || 'N/A'}</td>
                            <td>${test.unit || 'N/A'}</td>
                            <td>${test.result_remarks || '-'}</td>
                        </tr>
                    `;
                });
            } else {
                testRows = `
                    <tr>
                        <td colspan="6" style="text-align:center;color:#6b7280;">No test results available</td>
                    </tr>
                `;
            }
            
            let html = `
                <div class="report-container" id="reportPrintArea">
                    <!-- Report Header with Logo -->
                    <div class="report-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <div style="flex:1; text-align:left;">
                            ${logoHtml}
                        </div>
                        <div style="flex:2; text-align:center;">
                            <h2 style="font-size:22px; font-weight:700; color:#0f172a; margin:0;">${hospitalName}</h2>
                            <p style="color:#6b7280; font-size:13px; margin:4px 0 0 0;">${hospitalAddress}</p>
                            ${hospitalPhone ? `<p style="color:#6b7280; font-size:12px; margin:2px 0;">Phone: ${hospitalPhone}</p>` : ''}
                            ${hospitalEmail ? `<p style="color:#6b7280; font-size:12px; margin:0;">Email: ${hospitalEmail}</p>` : ''}
                        </div>
                        <div style="flex:1;"></div>
                    </div>
                    
                    <div class="report-title">Laboratory Test Report</div>
                    
                    <!-- Patient & Report Info -->
                    <div class="report-info-grid">
                        <div class="report-info-item">
                            <span class="report-info-label">Report No:</span>
                            <span class="report-info-value"><strong>${report.report_no || 'N/A'}</strong></span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Order No:</span>
                            <span class="report-info-value">${report.order_no || 'N/A'}</span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Patient Name:</span>
                            <span class="report-info-value"><strong>${report.patient_name || 'N/A'}</strong></span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Gender:</span>
                            <span class="report-info-value">${report.gender || 'N/A'}</span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Date of Birth:</span>
                            <span class="report-info-value">${dob}</span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Mobile:</span>
                            <span class="report-info-value">${report.mobile || 'N/A'}</span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Referring Doctor:</span>
                            <span class="report-info-value" style="font-weight:500;color:#1e40af;">${doctorName}</span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Report Date:</span>
                            <span class="report-info-value"><strong>${reportDate}</strong></span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Order Date:</span>
                            <span class="report-info-value">${orderDate}</span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Status:</span>
                            <span class="report-info-value">
                                <span class="report-status-badge ${statusClass}">${report.report_status || 'Pending'}</span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Test Results Table -->
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Test Name</th>
                                <th style="width:120px;">Result</th>
                                <th style="width:130px;">Normal Range</th>
                                <th style="width:80px;">Unit</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${testRows}
                        </tbody>
                    </table>
                    
                    <!-- Footer -->
                    <div class="report-footer">
                        <div>
                            <div><strong>Technician:</strong> ${report.technician_name || 'N/A'}</div>
                            <div style="font-size:12px;color:#9ca3af;margin-top:4px;">Generated on: ${new Date().toLocaleString('en-IN')}</div>
                        </div>
                        <div class="report-signature">
                            <div class="line"></div>
                            <span style="font-size:12px;color:#6b7280;">Authorized Signature</span>
                        </div>
                    </div>
                </div>
            `;
            content.innerHTML = html;
        })
        .catch((error) => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="mt-2">Error loading report details. Please try again.</p>
                    <p class="text-xs text-gray-400 mt-1">${error.message}</p>
                </div>
            `;
        });
}

        // ========== CLOSE MODAL ==========
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

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });
            const allTab = document.getElementById('tab-all');
            if (allTab) {
                allTab.classList.remove('tab-inactive');
                allTab.classList.add('tab-active');
            }
            filterReports('all');
        });
    </script>
</body>
</html>