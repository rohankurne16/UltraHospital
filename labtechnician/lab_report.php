<?php
session_start();
include "../config/hospital.php";

// ========== HELPER: GET DOCTOR NAME ==========
function getDoctorName($conn, $doctor_id) {
   
    if (empty($doctor_id)) {
        return 'Not Assigned';
    }
 $sql = "SELECT doctor_name, department, qualification
        FROM doctor
        WHERE (doctor_id = $doctor_id OR register_id = $doctor_id)
        AND (delete_flag = 0 OR delete_flag IS NULL)
        LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
        $name = $doctor['doctor_name'] ?? '';
        if (!empty($name)) {
            $name = preg_replace('/^Dr\.?\s*/i', '', $name);
            return 'Dr. ' . $name;
        }
        return 'Not Assigned';
    }
    
    // Try to get from staff table if not found in doctor
    $sql2 = "SELECT name FROM staff WHERE staff_id = $doctor_id AND role = 'Doctor' AND (delete_flag = 0 OR delete_flag IS NULL) LIMIT 1";
    $result2 = $conn->query($sql2);
    if ($result2 && $result2->num_rows > 0) {
        $staff = $result2->fetch_assoc();
        $name = $staff['name'] ?? '';
        if (!empty($name)) {
            $name = preg_replace('/^Dr\.?\s*/i', '', $name);
            return 'Dr. ' . $name;
        }
    }
    
    return 'Not Assigned';
}

// ========== HELPER: GET PATIENT NAME ==========
function getPatientName($conn, $patient_id) {
    if (empty($patient_id)) {
        return 'N/A';
    }
    
    $sql = "SELECT patient_name FROM patients WHERE patient_id = $patient_id AND (delete_flag = 0 OR delete_flag IS NULL) LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        return $patient['patient_name'] ?? 'N/A';
    }
    return 'N/A';
}

// ========== HELPER: GET TEST NAME ==========
function getTestName($conn, $detail_id) {
    if (empty($detail_id)) {
        return 'N/A';
    }
    
    $sql = "SELECT t.test_name, t.test_code 
            FROM lab_order_details od
            LEFT JOIN lab_tests t ON od.test_id = t.test_id
            WHERE od.detail_id = $detail_id 
            AND (od.delete_flag = 0 OR od.delete_flag IS NULL)
            LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $test = $result->fetch_assoc();
        return $test['test_name'] ?? 'N/A';
    }
    return 'N/A';
}

// ========== HELPER: GET TEST RESULT ==========
function getTestResult($conn, $detail_id) {
    if (empty($detail_id)) {
        return null;
    }
    
    $sql = "SELECT result_value, unit, normal_range, remarks 
            FROM lab_test_results 
            WHERE order_detail_id = $detail_id 
            LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$register_id = $_SESSION["id"];

// ========== GET STAFF ID FROM REGISTER ID ==========
$sql_staff = "SELECT staff_id, name, profile_image FROM staff WHERE register_id = $register_id AND role = 'Lab Technician' AND hospital_id = $hid AND delete_flag = 0";
$result_staff = $conn->query($sql_staff);

if ($result_staff && $result_staff->num_rows > 0) {
    $technician = $result_staff->fetch_assoc();
    $user_id = $technician['staff_id'];
    $_SESSION["name"] = $technician['name'] ?? 'Technician';
    $_SESSION["role"] = "Lab Technician";
    $_SESSION["profile_image"] = $technician['profile_image'] ?? '';
    $_SESSION['staff_id'] = $user_id;
} else {
    $sql_tech = "SELECT id, name, email, phone FROM lab_technicians WHERE register_id = $register_id AND hospital_id = $hid AND status = 'active'";
    $result_tech = $conn->query($sql_tech);
    if ($result_tech && $result_tech->num_rows > 0) {
        $tech = $result_tech->fetch_assoc();
        $user_id = $tech['id'];
        $_SESSION["name"] = $tech['name'];
        $_SESSION["role"] = "Lab Technician";
        $_SESSION['lab_tech_id'] = $user_id;
    } else {
        echo "<script>alert('Lab Technician not found!'); window.location='../index.php';</script>";
        exit();
    }
}

if (!isset($user_id) || empty($user_id)) {
    echo "<script>alert('Technician ID not found!'); window.location='../index.php';</script>";
    exit();
}

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

// ========== GET ALL REPORTS - FIXED QUERY ==========
$reports = [];

// First, get all reports for this technician
$sql_reports = "SELECT r.* 
                FROM lab_reports r
                WHERE r.technician_id = $user_id 
                AND r.hospital_id = $hid
                AND (r.delete_flag = 0 OR r.delete_flag IS NULL)
                ORDER BY r.report_id DESC";

$result_reports = $conn->query($sql_reports);

if ($result_reports && $result_reports->num_rows > 0) {
    while ($report_row = $result_reports->fetch_assoc()) {
        // Get order details
        $order_data = null;
        $order_sql = "SELECT order_no, order_date FROM lab_orders WHERE order_id = " . $report_row['order_id'] . " AND delete_flag = 0";
        $order_result = $conn->query($order_sql);
        if ($order_result && $order_result->num_rows > 0) {
            $order_data = $order_result->fetch_assoc();
        }
        
        // Get patient name
        $patient_name = getPatientName($conn, $report_row['patient_id']);
        
        // Get doctor name - using the improved function
        $doctor_name = getDoctorName($conn, $report_row['doctor_id']);
        
        // Get test name from detail_id
        $test_name = getTestName($conn, $report_row['detail_id']);
        
        // Get test result
        $test_result = getTestResult($conn, $report_row['detail_id']);
        
        // Combine all data
        $reports[] = array_merge($report_row, [
            'order_no' => $order_data['order_no'] ?? 'N/A',
            'order_date' => $order_data['order_date'] ?? 'N/A',
            'patient_name' => $patient_name,
            'doctor_name' => $doctor_name,
            'test_name' => $test_name,
            'result_value' => $test_result['result_value'] ?? null,
            'unit' => $test_result['unit'] ?? null,
            'normal_range' => $test_result['normal_range'] ?? null,
            'result_remarks' => $test_result['remarks'] ?? null
        ]);
    }
}

// ========== VIEW REPORT (AJAX) - FIXED WITH BETTER DOCTOR FETCH ==========
if (isset($_GET['view_report'])) {
    $report_id = intval($_GET['view_report']);
    
    // First get the report data
    $sql = "SELECT r.*, 
            o.order_no, o.order_date,
            p.patient_name, p.mobile as patient_mobile, p.gender, p.date_of_birth,
            s.name as technician_name
            FROM lab_reports r
            LEFT JOIN lab_orders o ON r.order_id = o.order_id
            LEFT JOIN patients p ON r.patient_id = p.patient_id
            LEFT JOIN staff s ON r.technician_id = s.staff_id
            WHERE r.report_id = $report_id 
            AND r.technician_id = $user_id
            AND r.hospital_id = $hid
            AND (r.delete_flag = 0 OR r.delete_flag IS NULL)
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        die("SQL Error: " . $conn->error);
    }
    
    if ($result && $result->num_rows > 0) {
        $report = $result->fetch_assoc();
        $report['doctor_name'] = getDoctorName($conn, $report['doctor_id']);
        // Get doctor name using helper function
       
        
        // Get test details
        $test_sql = "SELECT t.test_name, t.test_code, t.normal_range as test_normal_range, t.unit as test_unit
                     FROM lab_order_details od
                     LEFT JOIN lab_tests t ON od.test_id = t.test_id
                     WHERE od.detail_id = " . $report['detail_id'] . " 
                     AND (od.delete_flag = 0 OR od.delete_flag IS NULL)
                     LIMIT 1";
        $test_result = $conn->query($test_sql);
        if ($test_result && $test_result->num_rows > 0) {
            $test_data = $test_result->fetch_assoc();
            $report = array_merge($report, $test_data);
        }
        
        // Get test result
        $result_sql = "SELECT result_value, unit, normal_range, remarks 
                       FROM lab_test_results 
                       WHERE order_detail_id = " . $report['detail_id'] . " 
                       LIMIT 1";
        $result_result = $conn->query($result_sql);
        if ($result_result && $result_result->num_rows > 0) {
            $result_data = $result_result->fetch_assoc();
            $report = array_merge($report, $result_data);
        }
        
        header('Content-Type: application/json');
        echo json_encode($report);
    } else {
        echo json_encode(['error' => 'Report not found']);
    }
    exit();
}

// ========== EDIT REPORT ==========
if (isset($_POST['edit_report'])) {
    $report_id = intval($_POST['report_id']);
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $result_value = trim($_POST['result_value'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $report_status = $_POST['report_status'] ?? 'Completed';
    
    // Get detail_id from report
    $detail_query = $conn->query("SELECT detail_id FROM lab_reports WHERE report_id = $report_id AND technician_id = $user_id");
    if ($detail_query && $detail_query->num_rows > 0) {
        $detail_data = $detail_query->fetch_assoc();
        $detail_id = $detail_data['detail_id'];
        
        // Update test result
        if ($detail_id) {
            $check = $conn->query("SELECT result_id FROM lab_test_results WHERE order_detail_id = $detail_id");
            if ($check && $check->num_rows > 0) {
                $conn->query("UPDATE lab_test_results SET 
                              result_value = '$result_value',
                              remarks = '$remarks',
                              updated_at = NOW()
                              WHERE order_detail_id = $detail_id");
            } else {
                $conn->query("INSERT INTO lab_test_results (order_detail_id, result_value, remarks, report_status, entered_by, created_at) 
                              VALUES ($detail_id, '$result_value', '$remarks', 'Completed', $user_id, NOW())");
            }
        }
        
        // Handle file upload
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $target_dir = "../documents/reports/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
            
            if (in_array($file_ext, $allowed_ext)) {
                // Get old file to delete
                $old_file_query = $conn->query("SELECT report_file FROM lab_reports WHERE report_id = $report_id");
                if ($old_file_query && $old_file_query->num_rows > 0) {
                    $old_file = $old_file_query->fetch_assoc();
                    if (!empty($old_file['report_file']) && file_exists($target_dir . $old_file['report_file'])) {
                        unlink($target_dir . $old_file['report_file']);
                    }
                }
                
                $file_name = 'RPT_' . $report_id . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_dir . $file_name)) {
                    $conn->query("UPDATE lab_reports SET report_file = '$file_name' WHERE report_id = $report_id");
                }
            }
        }
        
        // Update report
        $sql = "UPDATE lab_reports SET 
                report_date = '$report_date',
                report_status = '$report_status',
                remarks = '$remarks',
                updated_at = NOW()
                WHERE report_id = $report_id AND technician_id = $user_id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Report updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating report: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Report not found or you don't have permission to edit it.";
    }
    
    header("Location: lab_report.php");
    exit();
}

// ========== DELETE REPORT ==========
if (isset($_GET['delete_report'])) {
    $report_id = intval($_GET['delete_report']);
    
    // Get file path to delete
    $file_query = $conn->query("SELECT report_file FROM lab_reports WHERE report_id = $report_id AND technician_id = $user_id");
    if ($file_query && $file_query->num_rows > 0) {
        $file_data = $file_query->fetch_assoc();
        if (!empty($file_data['report_file'])) {
            $target_dir = "../documents/reports/";
            if (file_exists($target_dir . $file_data['report_file'])) {
                unlink($target_dir . $file_data['report_file']);
            }
        }
    }
    
    // Soft delete
    $sql = "UPDATE lab_reports SET delete_flag = 1 WHERE report_id = $report_id AND technician_id = $user_id";
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Report deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting report: " . $conn->error;
    }
    
    header("Location: lab_report.php");
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
    
    i, .fas, .far, .fal, .fab, .fa, .icon, [class*="fa-"] {
        color: #3b82f6 !important;
    }
    
    .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
    .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
    .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
    .card-body { padding: 20px 24px; }
    
    .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-primary:hover { background: #2563eb; }
    .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-success:hover { background: #16a34a; }
    .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-danger:hover { background: #dc2626; }
    .btn-warning { background: #f59e0b; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-warning:hover { background: #d97706; }
    .btn-info { background: #0ea5e9; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-info:hover { background: #0284c7; }
    .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-outline:hover { background: #f3f4f6; }
    .btn-sm { padding: 4px 10px; font-size: 11px; }
    
    .table-container { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    thead { background: #f9fafb; }
    th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
    td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
    
    .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
    .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
    
    .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
    .status-badge.completed { background: #dcfce7; color: #166534; }
    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.cancelled { background: #fecaca; color: #991b1b; }
    
    .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
    .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
    .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
    .modal.show { display: flex; }
    .modal-content { background: white; border-radius: 12px; max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
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
    
    .form-input, .form-select, .form-textarea { 
        width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; 
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .form-textarea { min-height: 80px; resize: vertical; }
    .readonly-field { background: #f3f4f6; cursor: not-allowed; }
    
    .actions-cell { white-space: nowrap; }
    .file-link { color: #3b82f6; text-decoration: none; }
    .file-link:hover { text-decoration: underline; }
    
    .report-detail-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .report-detail-label { width: 150px; font-weight: 600; color: #4b5563; flex-shrink: 0; }
    .report-detail-value { flex: 1; color: #1f2937; }
    
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
        margin-right: 12px;
    }
    .back-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    
    /* Print button style */
    .print-btn { background: #8b5cf6; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
    .print-btn:hover { background: #7c3aed; }
    
    /* Doctor name display */
    .doctor-name { font-weight: 500; color: #1e40af; }
    </style>
</head>
<body>

    <?php include '../Sidebar.php'; ?>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <main class="main-content">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div class="flex items-center">
                        <a href="dashboard.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">
                                <i class="fas fa-file-alt text-blue-500 mr-2"></i>Lab Reports
                            </h1>
                            <p class="text-gray-500 mt-1">View, edit, and manage your generated reports</p>
                        </div>
                    </div>
                    <a href="dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
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
                        <h3><i class="fas fa-list mr-2 text-blue-500"></i> All Reports</h3>
                        <span class="badge-count"><?php echo count($reports); ?> reports</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reports)): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Report No</th>
                                            <th>Order No</th>
                                            <th>Patient</th>
                                            <th>Test</th>
                                            <th>Result</th>
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
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($report['order_no'] ?? 'N/A'); ?></span></td>
                                                <td>
                                                    <?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($report['test_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($report['result_value'])): ?>
                                                        <span class="font-medium"><?php echo htmlspecialchars($report['result_value']); ?></span>
                                                        <?php if (!empty($report['unit'])): ?>
                                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($report['unit']); ?></span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-sm">Not entered</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_class = strtolower($report['report_status'] ?? 'pending');
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($report['report_status'] ?? 'Pending'); ?></span>
                                                </td>
                                                <td class="actions-cell">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <!-- View Button -->
                                                        <button onclick="viewReport(<?php echo $report['report_id']; ?>)" 
                                                                class="btn-info btn-sm" title="View Report">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <!-- Edit Button -->
                                                        <button onclick="editReport(<?php echo $report['report_id']; ?>)" 
                                                                class="btn-warning btn-sm" title="Edit Report">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <!-- Delete Button -->
                                                        <button onclick="deleteReport(<?php echo $report['report_id']; ?>, '<?php echo htmlspecialchars($report['report_no']); ?>')" 
                                                                class="btn-danger btn-sm" title="Delete Report">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        
                                                        <!-- Download Button -->
                                                        <?php if (!empty($report['report_file'])): ?>
                                                            <a href="download_report.php?file=<?php echo urlencode($report['report_file']); ?>"
   class="btn-success btn-sm"
   title="Download Report">
    <i class="fas fa-download"></i>
</a>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Print Button -->
                                                        <?php if (!empty($report['order_id'])): ?>
                                                            <a href="print_report.php?order_id=<?php echo $report['order_id']; ?>"
   target="_blank"
   class="print-btn btn-sm">
    <i class="fas fa-print" style="color:white"></i>
</a>
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
                                <i class="fas fa-file-alt"></i>
                                <p class="text-lg font-medium text-gray-700">No reports found</p>
                                <p class="text-sm text-gray-400 mt-1">Reports will appear here once you generate them from completed orders</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ========== VIEW REPORT MODAL ========== -->
    <div class="modal" id="viewModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-alt mr-2 text-blue-500"></i> Report Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="viewReportContent">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                    <p class="mt-2 text-gray-500">Loading report details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeModal('viewModal')">Close</button>
                <button onclick="window.print()" class="btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- ========== EDIT REPORT MODAL ========== -->
    <div class="modal" id="editModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fas fa-edit mr-2 text-yellow-500"></i> Edit Report</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="lab_report.php" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="report_id" id="edit_report_id">
                <input type="hidden" name="edit_report" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Report Date <span class="required">*</span></label>
                        <input type="date" class="form-input" name="report_date" id="edit_report_date" required>
                    </div>
                    <div class="form-group">
                        <label>Report Status <span class="required">*</span></label>
                        <select class="form-select" name="report_status" id="edit_report_status">
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Result Value <span class="required">*</span></label>
                    <input type="text" class="form-input" name="result_value" id="edit_result_value" required placeholder="Enter test result">
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea class="form-textarea" name="remarks" id="edit_remarks" rows="3" placeholder="Any remarks about this test..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Upload New Report File (Optional)</label>
                    <input type="file" class="form-input" name="report_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                    <p class="text-xs text-gray-400 mt-1">Leave empty to keep existing file. Allowed: PDF, DOC, DOCX, JPG, PNG, XLS, XLSX</p>
                    <div id="current_file_display" class="text-sm text-blue-600 mt-2"></div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-warning">
                        <i class="fas fa-save"></i> Update Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========== VIEW REPORT ==========
        function viewReport(reportId) {
            document.getElementById('viewModal').classList.add('show');
            document.getElementById('viewReportContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                    <p class="mt-2 text-gray-500">Loading report details...</p>
                </div>
            `;
            
            fetch(`lab_report.php?view_report=${reportId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('viewReportContent').innerHTML = `
                            <div class="text-center py-8 text-red-500">
                                <i class="fas fa-exclamation-circle text-2xl"></i>
                                <p class="mt-2">${data.error}</p>
                            </div>
                        `;
                        return;
                    }
                    
                    // Doctor name is already formatted by the helper function
                    let doctorName = data.doctor_name || 'Not Assigned';
                    
                    let html = `
                        <div class="space-y-2">
                            <div class="report-detail-row">
                                <div class="report-detail-label">Report No</div>
                                <div class="report-detail-value"><span class="test-code-badge">${data.report_no || 'N/A'}</span></div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Order No</div>
                                <div class="report-detail-value"><span class="test-code-badge">${data.order_no || 'N/A'}</span></div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Patient Name</div>
                                <div class="report-detail-value font-medium">${data.patient_name || 'N/A'}</div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Patient Mobile</div>
                                <div class="report-detail-value">${data.patient_mobile || 'N/A'}</div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Doctor</div>
                                <div class="report-detail-value doctor-name">${doctorName}</div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Test Name</div>
                                <div class="report-detail-value">
                                    <span class="test-code-badge">${data.test_code || 'N/A'}</span>
                                    ${data.test_name || 'N/A'}
                                </div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Result</div>
                                <div class="report-detail-value font-medium text-green-600">
                                    ${data.result_value || 'Not entered'}
                                    ${data.test_unit ? ' ' + data.test_unit : ''}
                                </div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Normal Range</div>
                                <div class="report-detail-value">${data.test_normal_range || data.normal_range || 'N/A'}</div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Report Date</div>
                                <div class="report-detail-value">${data.report_date || 'N/A'}</div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Status</div>
                                <div class="report-detail-value">
                                    <span class="status-badge ${(data.report_status || 'pending').toLowerCase()}">${data.report_status || 'Pending'}</span>
                                </div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Remarks</div>
                                <div class="report-detail-value">${data.remarks || data.result_remarks || 'No remarks'}</div>
                            </div>
                            <div class="report-detail-row">
                                <div class="report-detail-label">Technician</div>
                                <div class="report-detail-value">${data.technician_name || 'N/A'}</div>
                            </div>
                            ${data.report_file ? `
                            <div class="report-detail-row">
                                <div class="report-detail-label">Attached File</div>
                                <div class="report-detail-value">
                                    <a href="../documents/reports/${data.report_file}" target="_blank" class="file-link">
                                        <i class="fas fa-file-pdf"></i> View File
                                    </a>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                    document.getElementById('viewReportContent').innerHTML = html;
                })
                .catch((error) => {
                    console.error('Error:', error);
                    document.getElementById('viewReportContent').innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="fas fa-exclamation-circle text-2xl"></i>
                            <p class="mt-2">Error loading report details</p>
                        </div>
                    `;
                });
        }

        // ========== EDIT REPORT ==========
        function editReport(reportId) {
            document.getElementById('editModal').classList.add('show');
            document.getElementById('edit_report_id').value = reportId;
            
            fetch(`lab_report.php?view_report=${reportId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        closeModal('editModal');
                        return;
                    }
                    
                    document.getElementById('edit_report_date').value = data.report_date || '';
                    document.getElementById('edit_report_status').value = data.report_status || 'Pending';
                    document.getElementById('edit_result_value').value = data.result_value || '';
                    document.getElementById('edit_remarks').value = data.remarks || data.result_remarks || '';
                    
                    if (data.report_file) {
                        document.getElementById('current_file_display').innerHTML = `
                            <i class="fas fa-paperclip"></i> Current file: 
                            <a href="../documents/reports/${data.report_file}" target="_blank" class="text-blue-600 underline">
                                ${data.report_file}
                            </a>
                        `;
                    } else {
                        document.getElementById('current_file_display').innerHTML = 'No file attached';
                    }
                })
                .catch(() => {
                    alert('Error loading report data');
                    closeModal('editModal');
                });
        }

        // ========== DELETE REPORT ==========
        function deleteReport(reportId, reportNo) {
            if (confirm(`Are you sure you want to delete report ${reportNo}? This action cannot be undone.`)) {
                window.location.href = `lab_report.php?delete_report=${reportId}`;
            }
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
    </script>
</body>
</html>