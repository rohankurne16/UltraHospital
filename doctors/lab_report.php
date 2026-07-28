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
                header("Location: doctor_lab_reports.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "No file attached to this report!";
            header("Location: doctor_lab_reports.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Report not found!";
        header("Location: doctor_lab_reports.php");
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
  
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        /* ===== TAB STYLES (Same as Appointments) ===== */
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
        
        /* Statistics Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 768px) { .stat-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 13px; margin-top: 2px; }
        .stat-card .stat-icon { font-size: 20px; margin-bottom: 4px; }
        
        /* Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-right: 4px;
            border: none;
            cursor: pointer;
        }
        .action-btn.view { background-color: #e0f2fe; color: #0284c7; }
        .action-btn.view:hover { background-color: #bae6fd; }
        .action-btn.edit { background-color: #fff7ed; color: #ea580c; }
        .action-btn.edit:hover { background-color: #fed7aa; }
        .action-btn.delete { background-color: #fee2e2; color: #dc2626; }
        .action-btn.delete:hover { background-color: #fecaca; }
        .action-btn.print { background-color: #f3e8ff; color: #7c3aed; }
        .action-btn.print:hover { background-color: #e9d5ff; }
        .action-btn.download { background-color: #d1fae5; color: #059669; }
        .action-btn.download:hover { background-color: #a7f3d0; }
        
        .fade-in { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .no-file-text { color: #9ca3af; font-size: 12px; font-style: italic; }
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
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
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

                    <!-- Statistics -->
                  

                    <!-- Reports Table -->
                    <div class="card">
                      
                        <div class="card-body">
                            <!-- ===== TABS (Same as Appointments) ===== -->
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
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
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
                                                            <!-- View File Button -->
                                                            <?php if (!empty($report['report_file'])): ?>
                                                                <a href="?view_file=1&report_id=<?php echo $report['report_id']; ?>" 
                                                                   target="_blank" 
                                                                   class="action-btn view btn-sm" 
                                                                   title="View Report File">
                                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="no-file-text" title="No file attached">
                                                                    <i data-lucide="eye-off" class="w-4 h-4"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Download Report -->
                                                            <?php if (!empty($report['report_file'])): ?>
                                                                <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" 
                                                                   download 
                                                                   class="action-btn download btn-sm" 
                                                                   title="Download Report">
                                                                    <i data-lucide="download" class="w-4 h-4"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Print Report -->
                                                            <?php if (!empty($report['order_id'])): ?>
                                                                <button onclick="window.open('print_report.php?order_id=<?php echo $report['order_id']; ?>', '_blank')" 
                                                                        class="action-btn print btn-sm" 
                                                                        title="Print Report">
                                                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Delete Report -->
                                                            <a href="?delete_report=1&report_id=<?php echo $report['report_id']; ?>" 
                                                               class="action-btn delete btn-sm" 
                                                               onclick="return confirm('Are you sure you want to delete this report?')" 
                                                               title="Delete Report">
                                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
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

    <script>
        lucide.createIcons();
        let currentFilter = 'all';
        const serverToday = '<?php echo date('Y-m-d'); ?>';

        function filterReports(filter) {
            currentFilter = filter;
            const rows = document.querySelectorAll('.report-row');
            let visibleCount = 0;

            // Toggle active class on tabs
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

        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            // Ensure 'all' tab is active by default
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