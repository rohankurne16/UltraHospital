<?php
session_start();
include "../config/hospital.php";


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
    // Fallback - try to get from lab_technicians table
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

// If we still don't have user_id, redirect
if (!isset($user_id) || empty($user_id)) {
    echo "<script>alert('Technician ID not found!'); window.location='../index.php';</script>";
    exit();
}

// ============================================================
// FIX: UPDATE ALL ORDERS WITH NULL/0 TECHNICIAN_ID
// ============================================================
$conn->query("UPDATE lab_orders SET technician_id = $user_id WHERE (technician_id IS NULL OR technician_id = 0) AND delete_flag = 0");

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

$sql_pending = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND order_status IN ('Pending','Assigned') AND delete_flag = 0";
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

// ============================================================
// FIX: GET ORDERS - DIRECT QUERY
// ============================================================
// ============================================================
// FIX: GET ORDERS - DIRECT QUERY
// ============================================================
$orders = [];

// Direct query - doctor name with department
$sql_orders = "SELECT o.*, p.patient_name, p.mobile as patient_mobile, p.gender, 
               d.doctor_name, d.department
               FROM lab_orders o
               LEFT JOIN patients p ON o.patient_id = p.patient_id
               LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
               WHERE o.technician_id = $user_id AND o.delete_flag = 0
               ORDER BY o.order_id DESC";

$result_orders = $conn->query($sql_orders);

if ($result_orders && $result_orders->num_rows > 0) {
    while ($row = $result_orders->fetch_assoc()) {
        // Get test count separately
        $test_count_sql = "SELECT COUNT(*) as count FROM lab_order_details WHERE order_id = " . $row['order_id'] . " AND delete_flag = 0";
        $test_count_result = $conn->query($test_count_sql);
        $row['test_count'] = $test_count_result ? $test_count_result->fetch_assoc()['count'] : 0;
        
        // Format doctor name with department
        if (!empty($row['doctor_name'])) {
            if (!empty($row['department'])) {
                $row['doctor_name'] = $row['doctor_name'] . ' (' . $row['department'] . ')';
            }
        } else {
            $row['doctor_name'] = 'Not Assigned';
        }
        
        $orders[] = $row;
    }
}

// ============================================================
// FALLBACK: If no orders found, try with all orders
// ============================================================
if (empty($orders)) {
    $fallback_sql = "SELECT o.*, p.patient_name, p.mobile as patient_mobile, p.gender,
                     d.doctor_name, d.department
                     FROM lab_orders o
                     LEFT JOIN patients p ON o.patient_id = p.patient_id
                     LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
                     WHERE o.delete_flag = 0
                     ORDER BY o.order_id DESC";
    $fallback_result = $conn->query($fallback_sql);
    if ($fallback_result && $fallback_result->num_rows > 0) {
        while ($row = $fallback_result->fetch_assoc()) {
            $test_count_sql = "SELECT COUNT(*) as count FROM lab_order_details WHERE order_id = " . $row['order_id'] . " AND delete_flag = 0";
            $test_count_result = $conn->query($test_count_sql);
            $row['test_count'] = $test_count_result ? $test_count_result->fetch_assoc()['count'] : 0;
            
            // Format doctor name with department
            if (!empty($row['doctor_name'])) {
                if (!empty($row['department'])) {
                    $row['doctor_name'] = $row['doctor_name'] . ' (' . $row['department'] . ')';
                }
            } else {
                $row['doctor_name'] = 'Not Assigned';
            }
            
            $orders[] = $row;
        }
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
                      AND o.order_status IN ('Sample Collected', 'In Process', 'Assigned', 'Pending')
                      AND o.delete_flag = 0
                      AND (r.result_id IS NULL OR r.result_id = 0)
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
    $valid_statuses = ['Pending','Assigned','Sample Collected','In Process','Completed','Cancelled'];
    
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
                    report_status = 'Completed',
                    updated_at = NOW()
                    WHERE order_detail_id = $detail_id";
        } else {
            $sql = "INSERT INTO lab_test_results (order_detail_id, result_value, normal_range, unit, remarks, entered_by, report_status, created_at) 
                    VALUES ($detail_id, '$result_value', '$normal_range', '$unit', '$remarks', $user_id, 'Completed', NOW())";
        }
        
        if ($conn->query($sql)) {
            if ($order_id > 0) {
                $check_all = $conn->query("SELECT COUNT(*) as total FROM lab_order_details od 
                                          LEFT JOIN lab_test_results r ON od.detail_id = r.order_detail_id
                                          WHERE od.order_id = $order_id AND (r.result_id IS NULL OR r.result_id = 0)");
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
// ========== GENERATE REPORT ==========
if (isset($_POST['generate_report'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $report_remarks = trim($_POST['report_remarks'] ?? '');
    
    if ($order_id > 0) {
        // Order डिटेल्स मिळवा
        $order_data = $conn->query("SELECT patient_id, doctor_id FROM lab_orders WHERE order_id = $order_id");
        if ($order_data && $order_data->num_rows > 0) {
            $order = $order_data->fetch_assoc();
            $patient_id = $order['patient_id'];
            $doctor_id = $order['doctor_id'];
            
            // ========== GET ALL TESTS FOR THIS ORDER ==========
            $tests_sql = "SELECT od.detail_id, od.test_id, t.test_name, t.test_code 
                          FROM lab_order_details od
                          LEFT JOIN lab_tests t ON od.test_id = t.test_id
                          WHERE od.order_id = $order_id AND od.delete_flag = 0";
            $tests_result = $conn->query($tests_sql);
            
            $uploaded_files = [];
            $all_success = true;
            $error_messages = [];
            
            if ($tests_result && $tests_result->num_rows > 0) {
                $test_count = 0;
                while ($test_row = $tests_result->fetch_assoc()) {
                    $test_count++;
                    $detail_id = $test_row['detail_id'];
                    $test_name = $test_row['test_name'] ?? 'Test ' . $test_count;
                    $test_code = $test_row['test_code'] ?? 'T' . str_pad($test_count, 3, '0', STR_PAD_LEFT);
                    
                    // ========== GENERATE UNIQUE REPORT NUMBER FOR EACH TEST ==========
                    $prefix = "RPT";
                    $date = date("Ymd");
                    
                    // Add detail_id to make it unique
                    $report_no = $prefix . $date . str_pad($detail_id, 4, '0', STR_PAD_LEFT);
                    
                    // Check if this report_no already exists
                    $check_sql = "SELECT report_no FROM lab_reports WHERE report_no = '$report_no'";
                    $check_result = $conn->query($check_sql);
                    $counter = 1;
                    while ($check_result && $check_result->num_rows > 0) {
                        $report_no = $prefix . $date . str_pad($detail_id, 4, '0', STR_PAD_LEFT) . '_' . $counter;
                        $check_sql = "SELECT report_no FROM lab_reports WHERE report_no = '$report_no'";
                        $check_result = $conn->query($check_sql);
                        $counter++;
                    }
                    
                    // ========== HANDLE FILE UPLOAD FOR EACH TEST ==========
                    $report_file = '';
                    $file_key = 'report_file_' . $detail_id;
                    
                    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                        $target_dir = "../documents/reports/";
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        
                        $file_ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $file_name = $report_no . '_' . time() . '.' . $file_ext;
                            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_dir . $file_name)) {
                                $report_file = $file_name;
                            } else {
                                $all_success = false;
                                $error_messages[] = "Failed to upload file for test: $test_name";
                            }
                        } else {
                            $all_success = false;
                            $error_messages[] = "Invalid file format for test: $test_name. Allowed: PDF, DOC, DOCX, JPG, PNG";
                        }
                    }
                    
                    // ========== SAVE TO DATABASE ==========
                    $remarks = $report_remarks ? $report_remarks . " (Test: $test_name)" : "Test: $test_name";
                    
                    $sql_insert = "INSERT INTO lab_reports 
                                   (order_id, detail_id, patient_id, doctor_id, technician_id, 
                                    report_no, report_date, report_file, report_status, remarks, hospital_id) 
                                   VALUES 
                                   ($order_id, $detail_id, $patient_id, $doctor_id, $user_id, 
                                    '$report_no', '$report_date', '$report_file', 'Completed', 
                                    '$remarks', $hid)";
                    
                    if (!$conn->query($sql_insert)) {
                        $all_success = false;
                        $error_messages[] = "Database error for test: $test_name - " . $conn->error;
                    } else {
                        $uploaded_files[] = $report_file;
                    }
                }
                
                // ========== FINAL MESSAGE ==========
                if ($all_success) {
                    $uploaded_count = count(array_filter($uploaded_files));
                    $total_tests = $tests_result->num_rows;
                    
                    if ($uploaded_count == $total_tests) {
                        $_SESSION['success'] = "Reports generated successfully! All $total_tests test documents uploaded.";
                    } elseif ($uploaded_count > 0) {
                        $_SESSION['success'] = "Reports generated! $uploaded_count out of $total_tests documents uploaded.";
                    } else {
                        $_SESSION['success'] = "Reports generated! No documents uploaded.";
                    }
                } else {
                    $_SESSION['error'] = "Some errors occurred: " . implode("; ", $error_messages);
                    if (!empty($uploaded_files)) {
                        $_SESSION['error'] .= " (Some files uploaded successfully)";
                    }
                }
                
            } else {
                $_SESSION['error'] = "No tests found for this order!";
            }
        } else {
            $_SESSION['error'] = "Order not found!";
        }
    }
    header("Location: dashboard.php");
    exit();
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
    
    /* ============================================================
       ALL ICONS BLUE
       ============================================================ */
    i, .fas, .far, .fal, .fab, .fa, .icon, [class*="fa-"] {
        color: #3b82f6 !important;
    }
    
    /* Stat card icons */
    .stat-card .stat-icon i,
    .stat-card .stat-icon .fas,
    .stat-card .stat-icon .fa {
        color: #3b82f6 !important;
    }
    
    /* Quick action icons */
    .quick-action-btn i,
    .quick-action-btn .fas,
    .quick-action-btn .fa {
        color: #3b82f6 !important;
    }
    
    /* Tab icons */
    .tab-btn i,
    .tab-btn .fas,
    .tab-btn .fa {
        color: #3b82f6 !important;
    }
    
    /* Card header icons */
    .card-header i,
    .card-header .fas,
    .card-header .fa {
        color: #3b82f6 !important;
    }
    
    /* Button icons - keep white for buttons */
    .btn-primary i,
    .btn-primary .fas,
    .btn-primary .fa,
    .btn-success i,
    .btn-success .fas,
    .btn-success .fa,
    .btn-danger i,
    .btn-danger .fas,
    .btn-danger .fa,
    .btn-warning i,
    .btn-warning .fas,
    .btn-warning .fa,
    .btn-info i,
    .btn-info .fas,
    .btn-info .fa,
    .print-btn i,
    .print-btn .fas,
    .print-btn .fa {
        color: white !important;
    }
    
    /* Welcome section icons - keep white */
    .welcome-section i,
    .welcome-section .fas,
    .welcome-section .fa {
        color: white !important;
    }
    
    /* Status badge icons - keep original color */
    .status-badge i,
    .status-badge .fas,
    .status-badge .fa {
        color: inherit !important;
    }
    
    .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
    .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
    .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
    .card-body { padding: 20px 24px; }
    
    .stat-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; text-align: center; }
    .stat-card .stat-number { font-size: 32px; font-weight: 700; }
    .stat-card .stat-label { color: #6b7280; font-size: 14px; margin-top: 4px; }
    .stat-card .stat-icon { font-size: 24px; margin-bottom: 8px; }
    
    .stat-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
    @media (max-width: 1024px) { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .stat-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
    
    .form-input, .form-select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
    .form-input:focus, .form-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    
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
    .modal-content { background: white; border-radius: 12px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
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
    .quick-action-btn { padding: 12px 20px; border-radius: 10px; border: 1px solid #e5e7eb; background: white; cursor: pointer; text-align: center; flex: 1; min-width: 120px; }
    .quick-action-btn i { font-size: 24px; display: block; margin-bottom: 6px; }
    .quick-action-btn .label { font-size: 12px; color: #6b7280; }
    
    .actions-cell { white-space: nowrap; }
    
    .print-btn { background: #8b5cf6; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .print-btn:hover { background: #7c3aed; }
    
    .btn-reject { background: #ef4444; color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-reject:hover { background: #dc2626; }
    
    .btn-accept { background: #22c55e; color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-accept:hover { background: #16a34a; }
    
    /* ============================================================
       TEXT COLORS - BLUE
       ============================================================ */
    .text-blue-500, .text-blue-600, .text-blue-700 {
        color: #3b82f6 !important;
    }
    .text-blue-500 i,
    .text-blue-600 i,
    .text-blue-700 i {
        color: #3b82f6 !important;
    }

    /* Test upload styles */
    .test-upload-item {
        transition: all 0.2s ease;
    }
    .test-upload-item:hover {
        background: #f8fafc;
        border-color: #3b82f6;
    }
    .test-number {
        min-width: 32px;
        height: 32px;
    }
    .file-input-wrapper {
        position: relative;
    }
    .file-input-wrapper input[type="file"] {
        padding: 6px 10px;
        font-size: 13px;
    }
    .file-input-wrapper input[type="file"]::file-selector-button {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        background: #3b82f6;
        color: white;
        font-size: 12px;
        cursor: pointer;
        margin-right: 10px;
        transition: background 0.2s;
    }
    .file-input-wrapper input[type="file"]::file-selector-button:hover {
        background: #2563eb;
    }
</style>
</head>
<body>

    <?php include '../Sidebar.php'; ?>
    <div class="flex min-h-screen flex-col bg-gray-50">
             <?php include '../header.php'; ?>
        
        <div class="flex flex-1 items-start">
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
                    <div class="quick-action-btn" onclick="window.location.href='../lab_reports.php'">
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
                                               <tr class="cursor-pointer hover:bg-gray-50 transition-colors" onclick="viewOrder(<?php echo $order['order_id']; ?>)">
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
    <td class="actions-cell" onclick="event.stopPropagation();">
        <div class="flex items-center gap-1 flex-wrap">
            <!-- Accept Order Button -->
            <?php if ($order['order_status'] == 'Pending'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    <input type="hidden" name="status" value="Assigned">
                    <button type="submit" name="update_order_status" class="btn-accept btn-sm" onclick="return confirm('Accept this order?')">
                        <i class="fas fa-check"></i> Accept
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Status Update Dropdown -->
            <?php if ($order['order_status'] != 'Completed' && $order['order_status'] != 'Cancelled' && $order['order_status'] != 'Pending'): ?>
                <form method="POST" style="display: inline;" onchange="this.submit()">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    <select name="status" class="form-select text-xs" style="width: auto; padding: 2px 6px; font-size: 11px; border-radius: 6px;">
                        <option value="">Update Status</option>
                        <option value="Sample Collected" <?php echo $order['order_status'] == 'Sample Collected' ? 'selected' : ''; ?>>Sample Collected</option>
                        <option value="In Process" <?php echo $order['order_status'] == 'In Process' ? 'selected' : ''; ?>>In Process</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <input type="hidden" name="update_order_status" value="1">
                </form>
            <?php endif; ?>
            
            <!-- Sample Rejected Button -->
            <?php if ($order['order_status'] != 'Completed' && $order['order_status'] != 'Cancelled'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    <input type="hidden" name="status" value="Cancelled">
                    <button type="submit" name="update_order_status" class="btn-reject btn-sm" onclick="return confirm('Reject this sample?')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Generate Report -->
            <?php if ($order['order_status'] == 'Completed'): ?>
                <button onclick="event.stopPropagation(); openReportModal(<?php echo $order['order_id']; ?>)" 
                        class="btn-success btn-sm" title="Generate Report">
                    <i class="fas fa-file-alt"></i>
                </button>
                <button onclick="event.stopPropagation(); window.location.href='../print_report.php?order_id=<?php echo $order['order_id']; ?>'" 
                        class="print-btn btn-sm" title="Print Report">
                    <i class="fas fa-print"></i>
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
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-alt mr-2 text-green-500"></i> Generate Report with Test Documents</h2>
                <button class="modal-close" onclick="closeModal('reportModal')">&times;</button>
            </div>
            <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                <input type="hidden" name="order_id" id="report_order_id">
                
                <!-- Test Count Info -->
                <div class="bg-blue-50 p-3 rounded-lg mb-4">
                    <p class="text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Upload separate document for each test. <strong id="testCountDisplay">0</strong> test(s) found.
                    </p>
                </div>
                
                <div class="form-group">
                    <label>Report Date</label>
                    <input type="date" class="form-input" name="report_date" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Dynamic Test File Uploads -->
                <div id="testUploadContainer" class="mb-4">
                    <h4 class="font-semibold text-gray-700 mb-2">Upload Documents for Each Test</h4>
                    <div id="testUploadList" class="space-y-3">
                        <!-- Will be populated by JavaScript -->
                        <div class="text-gray-500 text-center py-4">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading tests...
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>General Remarks (Optional)</label>
                    <textarea class="form-input" name="report_remarks" rows="2" placeholder="Additional notes for all tests..."></textarea>
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
        }

        // ========== OPEN RESULT MODAL ==========
        function openResultModal(detailId, orderId, normalRange, unit) {
            document.getElementById('result_detail_id').value = detailId;
            document.getElementById('result_order_id').value = orderId;
            document.getElementById('result_normal_range').value = normalRange || '';
            document.getElementById('result_unit').value = unit || '';
            document.getElementById('resultModal').classList.add('show');
        }

        // ========== OPEN REPORT MODAL WITH TESTS ==========
        function openReportModal(orderId) {
            document.getElementById('report_order_id').value = orderId;
            document.getElementById('reportModal').classList.add('show');
            
            // Load tests for this order
            loadTestsForReport(orderId);
        }

        // ========== LOAD TESTS FOR REPORT ==========
        function loadTestsForReport(orderId) {
            var container = document.getElementById('testUploadList');
            container.innerHTML = '<div class="text-gray-500 text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading tests...</div>';
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_order_tests.php?order_id=' + orderId, true);
            xhr.onload = function() {
                if (this.status == 200) {
                    try {
                        var data = JSON.parse(this.responseText);
                        if (data.tests && data.tests.length > 0) {
                            var html = '';
                            data.tests.forEach(function(test, index) {
                                html += `
                                    <div class="test-upload-item flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="test-number flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-sm">
                                            ${index + 1}
                                        </div>
                                        <div class="flex-1 min-w-[150px]">
                                            <div class="font-medium text-gray-800">${test.test_name || 'Test ' + (index + 1)}</div>
                                            <div class="text-xs text-gray-500">Code: ${test.test_code || 'N/A'}</div>
                                        </div>
                                        <div class="flex-1 file-input-wrapper">
                                            <input type="file" 
                                                   name="report_file_${test.detail_id}" 
                                                   id="file_${test.detail_id}"
                                                   class="form-input text-sm" 
                                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx"
                                                   ${index === 0 ? 'required' : ''}>
                                            <div class="text-xs text-gray-400 mt-1">PDF, DOC, JPG, PNG allowed</div>
                                        </div>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                            document.getElementById('testCountDisplay').textContent = data.tests.length;
                        } else {
                            container.innerHTML = '<div class="text-yellow-600 text-center py-4"><i class="fas fa-exclamation-triangle mr-2"></i> No tests found for this order</div>';
                            document.getElementById('testCountDisplay').textContent = '0';
                        }
                    } catch(e) {
                        console.error('Error parsing JSON:', e);
                        container.innerHTML = '<div class="text-red-500 text-center py-4"><i class="fas fa-exclamation-circle mr-2"></i> Error loading tests</div>';
                        document.getElementById('testCountDisplay').textContent = '?';
                    }
                } else {
                    container.innerHTML = '<div class="text-red-500 text-center py-4"><i class="fas fa-exclamation-circle mr-2"></i> Failed to load tests</div>';
                    document.getElementById('testCountDisplay').textContent = '?';
                }
            };
            xhr.onerror = function() {
                container.innerHTML = '<div class="text-red-500 text-center py-4"><i class="fas fa-exclamation-circle mr-2"></i> Network error loading tests</div>';
                document.getElementById('testCountDisplay').textContent = '?';
            };
            xhr.send();
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
        // ========== VIEW ORDER ==========
function viewOrder(orderId) {
    if (orderId) {
        window.location.href = 'view_order.php?order_id=' + orderId;
    }
}
    </script>
</body>
</html> 