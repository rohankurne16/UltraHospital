<?php
session_start();

include "../config/hospital.php";
include "../config/permission.php";

// ========== FIRST: Get user ID and hospital ID ==========
if (!isset($_SESSION['id'])) {
    die("Session ID missing");
}

$user_id = (int)$_SESSION['id'];
$hid = $_SESSION['hospital_id'];

// ========== DEBUG: Check technician ID and query ==========
error_log("Technician User ID: " . $user_id);
error_log("Hospital ID: " . $hid);

$sql = "SELECT order_id, order_no, technician_id, hospital_id, order_status
        FROM lab_orders
        WHERE technician_id = $user_id";

error_log("Dashboard SQL: " . $sql);

$result = mysqli_query($conn, $sql);

if ($result) {
    error_log("Number of orders found: " . mysqli_num_rows($result));
} else {
    error_log("Query error: " . mysqli_error($conn));
}

// ========== FIX: SET SESSION VARIABLES FOR HEADER ==========
$technician = null;
$sql_tech = "SELECT * FROM staff WHERE staff_id = $user_id AND role = 'Lab Technician' AND hospital_id = $hid";
$result_tech = $conn->query($sql_tech);
if ($result_tech && $result_tech->num_rows > 0) {
    $technician = $result_tech->fetch_assoc();
    $_SESSION["name"] = $technician['name'] ?? 'Technician';
    $_SESSION["role"] = "Lab Technician";
    $_SESSION["profile_image"] = $technician['profile_image'] ?? '';
}

// Check if user is a Lab Technician
$user_role = $_SESSION["role"] ?? "";
if ($user_role != "Lab Technician" && $user_role != "Admin") {
    header("Location: ../index.php");
    exit();
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

// ========== GET DASHBOARD STATISTICS ==========
$sql_total = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND delete_flag = 0";
$result_total = $conn->query($sql_total);
$total_orders = $result_total ? $result_total->fetch_assoc()['total'] : 0;

$sql_pending = "SELECT COUNT(*) as total
FROM lab_orders
WHERE technician_id = $user_id
AND order_status IN ('Assigned','Accepted')";
$result_pending = $conn->query($sql_pending);
$pending_orders = $result_pending ? $result_pending->fetch_assoc()['total'] : 0;

$sql_collected = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND order_status = 'Sample Collected' AND delete_flag = 0";
$result_collected = $conn->query($sql_collected);
$collected_orders = $result_collected ? $result_collected->fetch_assoc()['total'] : 0;

$sql_process = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND order_status = 'In Process' AND delete_flag = 0";
$result_process = $conn->query($sql_process);
$process_orders = $result_process ? $result_process->fetch_assoc()['total'] : 0;

$sql_completed = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND order_status = 'Completed' AND delete_flag = 0";
$result_completed = $conn->query($sql_completed);
$completed_orders = $result_completed ? $result_completed->fetch_assoc()['total'] : 0;

$sql_rejected = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND order_status = 'Cancelled' AND delete_flag = 0";
$result_rejected = $conn->query($sql_rejected);
$rejected_orders = $result_rejected ? $result_rejected->fetch_assoc()['total'] : 0;

// ========== GET ORDERS ==========
$orders = [];
$sql_orders = "SELECT o.*, p.patient_name, p.mobile as patient_mobile, p.gender, d.doctor_name,
               (SELECT COUNT(*) FROM lab_order_details WHERE order_id = o.order_id) as test_count
               FROM lab_orders o
               LEFT JOIN patients p ON o.patient_id = p.patient_id
               LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
               WHERE o.technician_id = $user_id AND o.delete_flag = 0
               ORDER BY o.order_id DESC";
$result_orders = $conn->query($sql_orders);
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) {
        $orders[] = $row;
    }
}

// ========== GET PENDING TESTS FOR RESULT ENTRY ==========
$pending_tests = [];
$sql_pending_tests = "SELECT od.*, t.test_name, t.test_code, t.normal_range, t.unit, 
                      o.order_no, p.patient_name, o.order_id
                      FROM lab_order_details od
                      LEFT JOIN lab_tests t ON od.test_id = t.test_id
                      LEFT JOIN lab_orders o ON od.order_id = o.order_id
                      LEFT JOIN patients p ON o.patient_id = p.patient_id
                      LEFT JOIN lab_test_results r ON od.detail_id = r.order_detail_id
                      WHERE o.technician_id = $user_id 
                      AND o.order_status IN ('Accepted','Sample Collected','In Process')
                      AND o.delete_flag = 0
                      AND r.result_id IS NULL
                      ORDER BY o.order_id DESC";
$result_pending_tests = $conn->query($sql_pending_tests);
if ($result_pending_tests) {
    while ($row = $result_pending_tests->fetch_assoc()) {
        $pending_tests[] = $row;
    }
}

// ========== UPDATE ORDER STATUS ==========
if (isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $valid_statuses = [
        'Assigned',
        'Accepted',
        'Sample Collected',
        'In Process',
        'Completed',
        'Rejected'
    ];
    
    if ($order_id > 0 && in_array($status, $valid_statuses)) {
        $conn->query("UPDATE lab_orders SET order_status = '$status' WHERE order_id = $order_id AND technician_id = $user_id");
        $_SESSION['success'] = "Order status updated to $status!";
    }
    header("Location: dashboard.php");
    exit();
}

// ========== SAVE TEST RESULT ==========
if (isset($_POST['save_result'])) {
    $detail_id = intval($_POST['detail_id'] ?? 0);
    $result_value = trim($_POST['result_value'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($detail_id > 0) {
        $test_info = $conn->query("SELECT t.normal_range, t.unit FROM lab_order_details od 
                                   LEFT JOIN lab_tests t ON od.test_id = t.test_id 
                                   WHERE od.detail_id = $detail_id");
        $test_data = $test_info ? $test_info->fetch_assoc() : null;
        $normal_range = $test_data['normal_range'] ?? '';
        $unit = $test_data['unit'] ?? '';
        
        $check = $conn->query("SELECT result_id FROM lab_test_results WHERE order_detail_id = $detail_id");
        if ($check && $check->num_rows > 0) {
            $sql = "UPDATE lab_test_results SET 
                    result_value = '$result_value',
                    normal_range = '$normal_range',
                    unit = '$unit',
                    remarks = '$remarks',
                    entered_by = $user_id,
                    report_status = 'Completed'
                    WHERE order_detail_id = $detail_id";
        } else {
            $sql = "INSERT INTO lab_test_results (order_detail_id, result_value, normal_range, unit, remarks, entered_by, report_status) 
                    VALUES ($detail_id, '$result_value', '$normal_range', '$unit', '$remarks', $user_id, 'Completed')";
        }
        
        if ($conn->query($sql)) {
            if ($order_id > 0) {
                $check_all = $conn->query("SELECT COUNT(*) as total FROM lab_order_details od 
                                          LEFT JOIN lab_test_results r ON od.detail_id = r.order_detail_id
                                          WHERE od.order_id = $order_id AND r.result_id IS NULL");
                if ($check_all && $check_all->fetch_assoc()['total'] == 0) {
                    $conn->query("UPDATE lab_orders SET order_status = 'Completed' WHERE order_id = $order_id");
                    $_SESSION['success'] = "All tests completed! Order status updated to Completed.";
                } else {
                    $_SESSION['success'] = "Test result saved successfully!";
                }
            }
        } else {
            $_SESSION['error'] = "Error saving result: " . $conn->error;
        }
    }
    header("Location: dashboard.php");
    exit();
}

// ========== GENERATE REPORT ==========
if (isset($_POST['generate_report'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $report_remarks = trim($_POST['report_remarks'] ?? '');
    
    if ($order_id > 0) {
        $order_data = $conn->query("SELECT patient_id, doctor_id FROM lab_orders WHERE order_id = $order_id");
        if ($order_data && $order_data->num_rows > 0) {
            $order = $order_data->fetch_assoc();
            $patient_id = $order['patient_id'];
            $doctor_id = $order['doctor_id'];
            
            $prefix = "RPT";
            $date = date("Ymd");
            $sql = "SELECT MAX(report_no) as max_no FROM lab_reports WHERE report_no LIKE '$prefix$date%'";
            $result = $conn->query($sql);
            if ($result && $row = $result->fetch_assoc() && $row['max_no']) {
                $num = intval(substr($row['max_no'], -4)) + 1;
                $report_no = $prefix . $date . str_pad($num, 4, '0', STR_PAD_LEFT);
            } else {
                $report_no = $prefix . $date . '0001';
            }
            
            $report_file = '';
            if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
                $target_dir = "../documents/reports/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_name = time() . '_' . basename($_FILES['report_file']['name']);
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_dir . $file_name)) {
                    $report_file = $file_name;
                }
            }
            
            $sql_insert = "INSERT INTO lab_reports (order_id, patient_id, doctor_id, technician_id, report_no, report_date, report_file, report_status, remarks, hospital_id) 
                           VALUES ($order_id, $patient_id, $doctor_id, $user_id, '$report_no', '$report_date', '$report_file', 'Completed', '$report_remarks', $hid)";
            
            if ($conn->query($sql_insert)) {
                $_SESSION['success'] = "Report #$report_no generated successfully!";
            } else {
                $_SESSION['error'] = "Error generating report: " . $conn->error;
            }
        }
    }
    header("Location: dashboard.php");
    exit();
}

// ========== DELETE REPORT ==========
if (isset($_GET['delete_report']) && isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    $order_id = intval($_GET['order_id'] ?? 0);
    
    // Get report file path
    $file_query = $conn->query("SELECT report_file FROM lab_reports WHERE report_id = $report_id");
    if ($file_query && $file_query->num_rows > 0) {
        $file_data = $file_query->fetch_assoc();
        if (!empty($file_data['report_file'])) {
            $file_path = "../documents/reports/" . $file_data['report_file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    $conn->query("DELETE FROM lab_reports WHERE report_id = $report_id");
    $_SESSION['success'] = "Report deleted successfully!";
    header("Location: dashboard.php");
    exit();
}

// ========== GET EXISTING REPORTS FOR THE ORDER ==========
$existing_reports = [];
if (isset($_GET['view_reports']) && isset($_GET['order_id'])) {
    $view_order_id = intval($_GET['order_id']);
    $sql_reports = "SELECT * FROM lab_reports WHERE order_id = $view_order_id ORDER BY report_id DESC";
    $result_reports = $conn->query($sql_reports);
    if ($result_reports) {
        while ($row = $result_reports->fetch_assoc()) {
            $existing_reports[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Technician Dashboard</title>
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
        
        .stat-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; text-align: center; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        .stat-card .stat-number { font-size: 32px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 14px; margin-top: 4px; }
        .stat-card .stat-icon { font-size: 24px; margin-bottom: 8px; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 1024px) { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .stat-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
        
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
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        .alert-info { background: #dbeafe; color: #1e40af; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #3b82f6; }
        
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.assigned { background: #dbeafe; color: #1e40af; }
        .status-badge.sample_collected { background: #e0e7ff; color: #3730a3; }
        .status-badge.in_process { background: #e0f2fe; color: #0369a1; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.cancelled { background: #fecaca; color: #991b1b; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .price-badge { font-weight: 600; color: #059669; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        
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
        
        .welcome-section { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        
        .info-text { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .readonly-field { background: #f3f4f6; cursor: not-allowed; }
        
        .tab-container { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; background: none; border: none; font-size: 14px; font-weight: 500; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .quick-action-btn { padding: 12px 20px; border-radius: 10px; border: 1px solid #e5e7eb; background: white; cursor: pointer; transition: all 0.2s; text-align: center; flex: 1; min-width: 120px; }
        .quick-action-btn:hover { background: #f1f5f9; border-color: #3b82f6; transform: translateY(-2px); }
        .quick-action-btn i { font-size: 24px; display: block; margin-bottom: 6px; }
        .quick-action-btn .label { font-size: 12px; color: #6b7280; }
        
        .actions-cell { white-space: nowrap; }
        
        .print-btn { background: #8b5cf6; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .print-btn:hover { background: #7c3aed; }
        
        .btn-reject { background: #ef4444; color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-reject:hover { background: #dc2626; }
        
        .btn-accept { background: #22c55e; color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-accept:hover { background: #16a34a; }
        
        .report-item { background: #f9fafb; border-radius: 8px; padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e5e7eb; }
        .report-item:hover { background: #f3f4f6; }
        .report-item .report-info { display: flex; flex-direction: column; gap: 2px; }
        .report-item .report-info .report-no { font-weight: 600; color: #1f2937; }
        .report-item .report-info .report-date { font-size: 12px; color: #6b7280; }
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
                    <h1><i class="fas fa-flask mr-2"></i> Welcome, <?php echo htmlspecialchars($technician['name'] ?? 'Technician'); ?>!</h1>
                    <p>Manage your assigned lab tests, collect samples, and enter results</p>
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
                    <div class="quick-action-btn" onclick="switchTab('orders')">
                        <i class="fas fa-list text-blue-500"></i>
                        <div class="label">View Orders</div>
                    </div>
                    <div class="quick-action-btn" onclick="switchTab('pending')">
                        <i class="fas fa-edit text-yellow-500"></i>
                        <div class="label">Pending Results</div>
                    </div>
                    <div class="quick-action-btn" onclick="switchTab('completed')">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <div class="label">Completed</div>
                    </div>
                    <div class="quick-action-btn" onclick="switchTab('reports')">
                        <i class="fas fa-file-alt text-purple-500"></i>
                        <div class="label">My Reports</div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stat-grid">
                    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                        <div class="stat-icon text-blue-500"><i class="fas fa-file-medical"></i></div>
                        <div class="stat-number text-blue-600"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                        <div class="stat-icon text-yellow-500"><i class="fas fa-clock"></i></div>
                        <div class="stat-number text-yellow-600"><?php echo $pending_orders; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                        <div class="stat-icon text-purple-500"><i class="fas fa-flask"></i></div>
                        <div class="stat-number text-purple-600"><?php echo $collected_orders; ?></div>
                        <div class="stat-label">Sample Collected</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #f97316;">
                        <div class="stat-icon text-orange-500"><i class="fas fa-cogs"></i></div>
                        <div class="stat-number text-orange-600"><?php echo $process_orders; ?></div>
                        <div class="stat-label">In Process</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #22c55e;">
                        <div class="stat-icon text-green-500"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number text-green-600"><?php echo $completed_orders; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #ef4444;">
                        <div class="stat-icon text-red-500"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-number text-red-600"><?php echo $rejected_orders; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tab-container">
                    <button class="tab-btn active" onclick="switchTab('orders')"><i class="fas fa-list"></i> My Orders</button>
                    <button class="tab-btn" onclick="switchTab('pending')"><i class="fas fa-edit"></i> Pending Results</button>
                    <button class="tab-btn" onclick="switchTab('completed')"><i class="fas fa-check-circle"></i> Completed</button>
                    <button class="tab-btn" onclick="switchTab('reports')"><i class="fas fa-file-alt"></i> Reports</button>
                </div>

                <!-- ========== MY ORDERS TAB ========== -->
                <div id="tab-orders" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list mr-2 text-blue-500"></i> My Assigned Orders</h3>
                            <span class="badge-count"><?php echo count($orders); ?> orders</span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orders)): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Order No</th>
                                                <th>Patient</th>
                                                <th>Doctor</th>
                                                <th>Tests</th>
                                                <th>Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $counter = 1; ?>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($order['order_no']); ?></span></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($order['patient_name'] ?? 'N/A'); ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['patient_mobile'] ?? ''); ?></div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['doctor_name'] ?? 'N/A'); ?></td>
                                                    <td><span class="badge-count"><?php echo $order['test_count']; ?> tests</span></td>
                                                    <td>
                                                        <?php 
                                                        $status_class = strtolower(str_replace(' ', '_', $order['order_status']));
                                                        ?>
                                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                                                    </td>
                                                    <td class="actions-cell">
                                                        <div class="flex items-center gap-1 flex-wrap">
                                                            <?php if ($order['order_status'] == 'Assigned'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                    <input type="hidden" name="status" value="Accepted">
                                                                    <button type="submit" name="update_order_status" class="btn-accept btn-sm" onclick="return confirm('Accept this order?')">
                                                                        <i class="fas fa-check"></i> Accept
                                                                    </button>
                                                                </form>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                    <input type="hidden" name="status" value="Cancelled">
                                                                    <button type="submit" name="update_order_status" class="btn-reject btn-sm" onclick="return confirm('Reject this order?')">
                                                                        <i class="fas fa-times"></i> Reject
                                                                    </button>
                                                                </form>
                                                            <?php elseif ($order['order_status'] == 'Accepted'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                    <input type="hidden" name="status" value="Sample Collected">
                                                                    <button type="submit" name="update_order_status" class="btn-primary btn-sm">
                                                                        <i class="fas fa-flask"></i> Collect Sample
                                                                    </button>
                                                                </form>
                                                            <?php elseif ($order['order_status'] == 'Sample Collected'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                    <input type="hidden" name="status" value="In Process">
                                                                    <button type="submit" name="update_order_status" class="btn-warning btn-sm">
                                                                        <i class="fas fa-play"></i> Start Test
                                                                    </button>
                                                                </form>
                                                            <?php elseif ($order['order_status'] == 'In Process'): ?>
                                                                <button onclick="openResultModalForOrder(<?php echo $order['order_id']; ?>)" 
                                                                        class="btn-info btn-sm">
                                                                    <i class="fas fa-edit"></i> Enter Result
                                                                </button>
                                                            <?php elseif ($order['order_status'] == 'Completed'): ?>
                                                                <button onclick="openReportModal(<?php echo $order['order_id']; ?>)" 
                                                                        class="btn-success btn-sm">
                                                                    <i class="fas fa-file-alt"></i> Report
                                                                </button>
                                                                <button onclick="window.location.href='../print_report.php?order_id=<?php echo $order['order_id']; ?>'" 
                                                                        class="print-btn btn-sm">
                                                                    <i class="fas fa-print"></i>
                                                                </button>
                                                                <a href="?view_reports=1&order_id=<?php echo $order['order_id']; ?>" class="btn-info btn-sm">
                                                                    <i class="fas fa-eye"></i> View Reports
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($order['order_status'] != 'Completed' && $order['order_status'] != 'Cancelled' && $order['order_status'] != 'Pending'): ?>
                                                                <form method="POST" style="display: inline;" onchange="this.submit()">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                    <select name="status" class="form-select text-xs" style="width: auto; padding: 2px 6px; font-size: 11px; border-radius: 6px;">
                                                                        <option value="">Quick Status</option>
                                                                        <option value="Sample Collected" <?php echo $order['order_status'] == 'Sample Collected' ? 'selected' : ''; ?>>Sample Collected</option>
                                                                        <option value="In Process" <?php echo $order['order_status'] == 'In Process' ? 'selected' : ''; ?>>In Process</option>
                                                                        <option value="Completed">Completed</option>
                                                                        <option value="Cancelled">Cancel</option>
                                                                    </select>
                                                                    <input type="hidden" name="update_order_status" value="1">
                                                                </form>
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
                                    <i class="fas fa-file-medical"></i>
                                    <p class="text-lg font-medium text-gray-700">No orders assigned</p>
                                    <p class="text-sm text-gray-400 mt-1">Orders will appear here once assigned by admin</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========== PENDING RESULTS TAB ========== -->
                <div id="tab-pending" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-edit mr-2 text-yellow-500"></i> Pending Test Results</h3>
                            <span class="badge-count"><?php echo count($pending_tests); ?> pending</span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($pending_tests)): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Order No</th>
                                                <th>Patient</th>
                                                <th>Test</th>
                                                <th>Normal Range</th>
                                                <th>Unit</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $counter = 1; ?>
                                            <?php foreach ($pending_tests as $test): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($test['order_no']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($test['patient_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="test-code-badge"><?php echo htmlspecialchars($test['test_code']); ?></span>
                                                        <?php echo htmlspecialchars($test['test_name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($test['normal_range'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($test['unit'] ?? '-'); ?></td>
                                                    <td class="actions-cell">
                                                        <button onclick="openResultModal(<?php echo $test['detail_id']; ?>, <?php echo $test['order_id']; ?>, '<?php echo htmlspecialchars($test['normal_range'] ?? ''); ?>', '<?php echo htmlspecialchars($test['unit'] ?? ''); ?>')" 
                                                                class="btn-primary btn-sm">
                                                            <i class="fas fa-edit"></i> Enter Result
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <p class="text-lg font-medium text-gray-700">No pending results</p>
                                    <p class="text-sm text-gray-400 mt-1">All tests have been completed</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========== COMPLETED TAB ========== -->
                <div id="tab-completed" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-check-circle mr-2 text-green-500"></i> Completed Orders</h3>
                        </div>
                        <div class="card-body">
                            <?php 
                            $completed_list = array_filter($orders, function($o) {
                                return $o['order_status'] == 'Completed';
                            });
                            if (!empty($completed_list)): 
                            ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Order No</th>
                                                <th>Patient</th>
                                                <th>Doctor</th>
                                                <th>Tests</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $counter = 1; ?>
                                            <?php foreach ($completed_list as $order): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($order['order_no']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($order['patient_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($order['doctor_name'] ?? 'N/A'); ?></td>
                                                    <td><span class="badge-count"><?php echo $order['test_count']; ?> tests</span></td>
                                                    <td class="actions-cell">
                                                        <div class="flex items-center gap-1 flex-wrap">
                                                            <button onclick="openReportModal(<?php echo $order['order_id']; ?>)" 
                                                                    class="btn-success btn-sm">
                                                                <i class="fas fa-file-alt"></i> Generate Report
                                                            </button>
                                                            <button onclick="window.location.href='../print_report.php?order_id=<?php echo $order['order_id']; ?>'" 
                                                                    class="print-btn btn-sm">
                                                                <i class="fas fa-print"></i> Print
                                                            </button>
                                                            <a href="?view_reports=1&order_id=<?php echo $order['order_id']; ?>" class="btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> View Reports
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
                                    <i class="fas fa-check-circle"></i>
                                    <p class="text-lg font-medium text-gray-700">No completed orders yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========== REPORTS TAB ========== -->
                <div id="tab-reports" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-alt mr-2 text-purple-500"></i> My Reports</h3>
                            <span class="badge-count">Reports</span>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get all reports generated by this technician
                            $sql_all_reports = "SELECT r.*, o.order_no, p.patient_name 
                                               FROM lab_reports r
                                               LEFT JOIN lab_orders o ON r.order_id = o.order_id
                                               LEFT JOIN patients p ON r.patient_id = p.patient_id
                                               WHERE r.technician_id = $user_id
                                               ORDER BY r.report_id DESC";
                            $result_all_reports = $conn->query($sql_all_reports);
                            $all_reports = [];
                            if ($result_all_reports) {
                                while ($row = $result_all_reports->fetch_assoc()) {
                                    $all_reports[] = $row;
                                }
                            }
                            ?>
                            
                            <?php if (!empty($all_reports)): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Report No</th>
                                                <th>Order No</th>
                                                <th>Patient</th>
                                                <th>Date</th>
                                                <th>File</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $counter = 1; ?>
                                            <?php foreach ($all_reports as $report): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><span class="test-code-badge"><?php echo htmlspecialchars($report['report_no']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($report['order_no'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($report['report_date'])); ?></td>
                                                    <td>
                                                        <?php if (!empty($report['report_file'])): ?>
                                                            <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" target="_blank" class="btn-info btn-xs">
                                                                <i class="fas fa-file-pdf"></i> View
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 text-xs">No file</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="actions-cell">
                                                        <div class="flex items-center gap-1 flex-wrap">
                                                            <?php if (!empty($report['report_file'])): ?>
                                                                <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" target="_blank" class="print-btn btn-xs">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="?delete_report=1&report_id=<?php echo $report['report_id']; ?>&order_id=<?php echo $report['order_id']; ?>" 
                                                               class="btn-danger btn-xs" 
                                                               onclick="return confirm('Are you sure you want to delete this report?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                            <a href="../print_report.php?order_id=<?php echo $report['order_id']; ?>" target="_blank" class="btn-success btn-xs">
                                                                <i class="fas fa-print"></i>
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
                                    <i class="fas fa-file-alt text-gray-300"></i>
                                    <p class="text-lg font-medium text-gray-700">No reports generated yet</p>
                                    <p class="text-sm text-gray-400 mt-1">Reports will appear here after you generate them</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========== VIEW REPORTS FOR ORDER SECTION ========== -->
                <?php if (isset($_GET['view_reports']) && isset($_GET['order_id'])): 
                    $view_order_id = intval($_GET['order_id']);
                    $sql_view_reports = "SELECT r.*, o.order_no, p.patient_name 
                                        FROM lab_reports r
                                        LEFT JOIN lab_orders o ON r.order_id = o.order_id
                                        LEFT JOIN patients p ON r.patient_id = p.patient_id
                                        WHERE r.order_id = $view_order_id
                                        ORDER BY r.report_id DESC";
                    $result_view_reports = $conn->query($sql_view_reports);
                ?>
                    <div class="card mt-6">
                        <div class="card-header">
                            <h3><i class="fas fa-file-alt mr-2 text-purple-500"></i> Reports for Order</h3>
                            <a href="dashboard.php" class="btn-secondary btn-sm">Close</a>
                        </div>
                        <div class="card-body">
                            <?php if ($result_view_reports && $result_view_reports->num_rows > 0): ?>
                                <div class="space-y-2">
                                    <?php while ($report = $result_view_reports->fetch_assoc()): ?>
                                        <div class="report-item">
                                            <div class="report-info">
                                                <span class="report-no"><?php echo htmlspecialchars($report['report_no']); ?></span>
                                                <span class="report-date">Patient: <?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?> | Date: <?php echo date('d-m-Y', strtotime($report['report_date'])); ?></span>
                                                <?php if (!empty($report['remarks'])): ?>
                                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars($report['remarks']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex gap-2">
                                                <?php if (!empty($report['report_file'])): ?>
                                                    <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" target="_blank" class="btn-info btn-xs">
                                                        <i class="fas fa-file-pdf"></i> View
                                                    </a>
                                                    <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" download class="print-btn btn-xs">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                <?php endif; ?>
                                                <a href="../print_report.php?order_id=<?php echo $report['order_id']; ?>" target="_blank" class="btn-success btn-xs">
                                                    <i class="fas fa-print"></i> Print
                                                </a>
                                                <a href="?delete_report=1&report_id=<?php echo $report['report_id']; ?>&order_id=<?php echo $report['order_id']; ?>" 
                                                   class="btn-danger btn-xs" 
                                                   onclick="return confirm('Are you sure you want to delete this report?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-alt text-gray-300"></i>
                                    <p class="text-lg font-medium text-gray-700">No reports for this order</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- ========== ENTER RESULT MODAL ========== -->
    <div class="modal" id="resultModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit mr-2 text-blue-500"></i> Enter Test Result</h2>
                <button class="modal-close" onclick="closeModal('resultModal')">&times;</button>
            </div>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="detail_id" id="result_detail_id">
                <input type="hidden" name="order_id" id="result_order_id">
                
                <div class="form-group">
                    <label>Test Result <span class="required">*</span></label>
                    <input type="text" class="form-input" name="result_value" required placeholder="Enter test result value">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Normal Range</label>
                        <input type="text" class="form-input readonly-field" id="result_normal_range" readonly>
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" class="form-input readonly-field" id="result_unit" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea class="form-input" name="remarks" rows="3" placeholder="Any remarks about this test..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('resultModal')">Cancel</button>
                    <button type="submit" name="save_result" class="btn-primary">
                        <i class="fas fa-save"></i> Save Result
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== GENERATE REPORT MODAL ========== -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-alt mr-2 text-green-500"></i> Generate Report</h2>
                <button class="modal-close" onclick="closeModal('reportModal')">&times;</button>
            </div>
            <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                <input type="hidden" name="order_id" id="report_order_id">
                
                <div class="alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Generating report for order #<span id="report_order_no">-</span>
                </div>
                
                <div class="form-group">
                    <label>Report Date</label>
                    <input type="date" class="form-input" name="report_date" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Upload PDF Report <span class="required">*</span></label>
                    <input type="file" class="form-input" name="report_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                    <div class="info-text">Supported: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</div>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea class="form-input" name="report_remarks" rows="3" placeholder="Additional notes..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('reportModal')">Cancel</button>
                    <button type="submit" name="generate_report" class="btn-success">
                        <i class="fas fa-save"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========== TAB SWITCHING ==========
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            document.querySelector('.tab-btn[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
            
            // If switching to reports tab, reload to get fresh data
            if (tab === 'reports') {
                window.location.href = 'dashboard.php#tab-reports';
            }
        }

        // ========== OPEN RESULT MODAL ==========
        function openResultModal(detailId, orderId, normalRange, unit) {
            document.getElementById('result_detail_id').value = detailId;
            document.getElementById('result_order_id').value = orderId;
            document.getElementById('result_normal_range').value = normalRange || '';
            document.getElementById('result_unit').value = unit || '';
            document.getElementById('resultModal').classList.add('show');
        }

        // ========== OPEN RESULT MODAL FOR ORDER ==========
        function openResultModalForOrder(orderId) {
            // Fetch pending tests for this order
            window.location.href = 'dashboard.php?order_id=' + orderId + '#tab-pending';
        }

        // ========== OPEN REPORT MODAL ==========
        function openReportModal(orderId) {
            document.getElementById('report_order_id').value = orderId;
            document.getElementById('report_order_no').textContent = orderId;
            document.getElementById('reportModal').classList.add('show');
        }

        // ========== CLOSE MODAL ==========
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        // ========== AUTO SUBMIT FOR DROPDOWN ==========
        document.querySelectorAll('form[onchange]').forEach(function(form) {
            form.addEventListener('change', function() {
                this.submit();
            });
        });

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

        // ========== CHECK URL HASH FOR TAB ==========
        window.addEventListener('load', function() {
            const hash = window.location.hash;
            if (hash === '#tab-reports') {
                switchTab('reports');
            }
        });
    </script>
</body>
</html>