<?php
session_start();
include "../config/hospital.php";

// ========== CHECK SESSION FIRST ==========
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$register_id = $_SESSION["id"];

// ========== GET TECHNICIAN INFO - SINGLE QUERY ==========
$sql_staff = "SELECT s.staff_id, s.name, s.profile_image 
              FROM staff s 
              WHERE s.register_id = $register_id 
              AND s.role = 'Lab Technician' 
              AND s.hospital_id = $hid 
              AND s.delete_flag = 0 
              LIMIT 1";
$result_staff = $conn->query($sql_staff);

if ($result_staff && $result_staff->num_rows > 0) {
    $technician = $result_staff->fetch_assoc();
    $user_id = $technician['staff_id'];
    $_SESSION["name"] = $technician['name'] ?? 'Technician';
    $_SESSION["role"] = "Lab Technician";
    $_SESSION["profile_image"] = $technician['profile_image'] ?? '';
    $_SESSION['staff_id'] = $user_id;
} else {
    // Fallback to lab_technicians table
    $sql_tech = "SELECT id, name FROM lab_technicians WHERE register_id = $register_id AND hospital_id = $hid AND status = 'active' LIMIT 1";
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

// ========== CACHE KEY FOR STATISTICS ==========
$cache_key = 'tech_stats_' . $user_id;
$stats = isset($_SESSION[$cache_key]) ? $_SESSION[$cache_key] : null;
$stats_expired = !$stats || !isset($stats['time']) || (time() - $stats['time'] > 300);

// ========== USE CACHED STATS OR FETCH NEW ==========
if ($stats_expired) {
    // Get all stats in a single query using UNION
    $sql_stats = "
        SELECT 'total' as type, COUNT(*) as count FROM lab_orders WHERE technician_id = $user_id AND delete_flag = 0
        UNION ALL
        SELECT 'pending' as type, COUNT(*) as count FROM lab_orders WHERE technician_id = $user_id AND order_status IN ('Pending','Assigned') AND delete_flag = 0
        UNION ALL
        SELECT 'collected' as type, COUNT(*) as count FROM lab_orders WHERE technician_id = $user_id AND order_status = 'Sample Collected' AND delete_flag = 0
        UNION ALL
        SELECT 'process' as type, COUNT(*) as count FROM lab_orders WHERE technician_id = $user_id AND order_status = 'In Process' AND delete_flag = 0
        UNION ALL
        SELECT 'completed' as type, COUNT(*) as count FROM lab_orders WHERE technician_id = $user_id AND order_status = 'Completed' AND delete_flag = 0
        UNION ALL
        SELECT 'cancelled' as type, COUNT(*) as count FROM lab_orders WHERE technician_id = $user_id AND order_status = 'Cancelled' AND delete_flag = 0
    ";
    $result_stats = $conn->query($sql_stats);
    
    $stats = ['time' => time()];
    if ($result_stats) {
        while ($row = $result_stats->fetch_assoc()) {
            $stats[$row['type'] . '_orders'] = $row['count'];
        }
    }
    $_SESSION[$cache_key] = $stats;
}

// Extract stats
$total_orders = $stats['total_orders'] ?? 0;
$pending_orders = $stats['pending_orders'] ?? 0;
$collected_orders = $stats['collected_orders'] ?? 0;
$process_orders = $stats['process_orders'] ?? 0;
$completed_orders = $stats['completed_orders'] ?? 0;
$rejected_orders = $stats['cancelled_orders'] ?? 0;

// ========== GET ORDERS WITH PAGINATION ==========
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sql_orders = "
SELECT
    o.*,
    p.patient_name,
    p.mobile AS patient_mobile,
    d.doctor_name,
    d.department,
    COUNT(DISTINCT lod.detail_id) AS test_count,
    MAX(lr.report_id) AS report_id
FROM lab_orders o

LEFT JOIN patients p
    ON p.patient_id = o.patient_id

LEFT JOIN doctor d
    ON d.doctor_id = o.doctor_id
    AND (d.delete_flag = 0 OR d.delete_flag IS NULL)

LEFT JOIN lab_order_details lod
    ON lod.order_id = o.order_id
    AND lod.delete_flag = 0

LEFT JOIN lab_reports lr
    ON lr.order_id = o.order_id

WHERE
    o.technician_id = $user_id
    AND o.delete_flag = 0

GROUP BY o.order_id
ORDER BY o.order_id DESC
LIMIT $per_page OFFSET $offset";

$result_orders = $conn->query($sql_orders);
$orders = [];
if ($result_orders && $result_orders->num_rows > 0) {
    while ($row = $result_orders->fetch_assoc()) {
        // Format doctor name
        if (!empty($row['doctor_name'])) {
            $name = preg_replace('/^Dr\.?\s*/i', '', $row['doctor_name']);
            $row['doctor_display'] = 'Dr. ' . $name;
            if (!empty($row['department'])) {
                $row['doctor_display'] .= ' (' . $row['department'] . ')';
            }
        } else {
            $row['doctor_display'] = 'Not Assigned';
        }
        $orders[] = $row;
    }
}

// ========== GET PENDING TESTS (LIMITED) ==========
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
                      ORDER BY o.order_id DESC
                      LIMIT 50";
$result_pending_tests = $conn->query($sql_pending_tests);
if ($result_pending_tests) {
    while ($row = $result_pending_tests->fetch_assoc()) {
        $pending_tests[] = $row;
    }
}

// ========== AJAX HANDLER ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    // Update Status
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $valid_statuses = ['Pending','Assigned','Sample Collected','In Process','Completed','Cancelled'];
        
        if ($order_id > 0 && in_array($status, $valid_statuses)) {
            $conn->query("UPDATE lab_orders SET order_status = '$status' WHERE order_id = $order_id AND technician_id = $user_id");
            // Clear cache
            unset($_SESSION[$cache_key]);
            echo json_encode(['success' => true, 'message' => "Status updated to $status"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
        }
        exit();
    }
    
    // Save Result
    if (isset($_POST['action']) && $_POST['action'] == 'save_result') {
        $detail_id = intval($_POST['detail_id'] ?? 0);
        $result_value = trim($_POST['result_value'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        $order_id = intval($_POST['order_id'] ?? 0);
        
        if ($detail_id > 0 && !empty($result_value)) {
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
                        remarks = '$remarks',
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
                        // Clear cache
                        unset($_SESSION[$cache_key]);
                        echo json_encode(['success' => true, 'message' => 'All tests completed! Order status updated to Completed.']);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Test result saved successfully!']);
                    }
                } else {
                    echo json_encode(['success' => true, 'message' => 'Test result saved successfully!']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error saving result: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Please enter a result value']);
        }
        exit();
    }
    
    // Generate Report
    if (isset($_POST['action']) && $_POST['action'] == 'generate_report') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $report_date = $_POST['report_date'] ?? date('Y-m-d');
        $report_remarks = trim($_POST['report_remarks'] ?? '');
        $results = isset($_POST['results']) ? $_POST['results'] : [];
        $document_category = $_POST['document_category'] ?? 'Post-Operation';
        
        if ($order_id > 0 && !empty($results)) {
            $order_data = $conn->query("SELECT patient_id, doctor_id, created_by FROM lab_orders WHERE order_id = $order_id");
            if ($order_data && $order_data->num_rows > 0) {
                $order = $order_data->fetch_assoc();
                $patient_id = $order['patient_id'];
                $doctor_id = $order['doctor_id'];
                
                $all_success = true;
                $saved_count = 0;
                $error_messages = [];
                
                foreach ($results as $result) {
                    $detail_id = intval($result['order_detail_id'] ?? 0);
                    $result_value = trim($result['result_value'] ?? '');
                    $normal_range = trim($result['normal_range'] ?? '');
                    $unit = trim($result['unit'] ?? '');
                    $remarks = trim($result['remarks'] ?? '');
                    $test_name = trim($result['test_name'] ?? '');
                    
                    if ($detail_id > 0 && !empty($result_value)) {
                        $prefix = "RPT";
                        $date = date("Ymd");
                        $report_no = $prefix . $date . str_pad($detail_id, 4, '0', STR_PAD_LEFT);
                        
                        $check_sql = "SELECT report_no FROM lab_reports WHERE report_no = '$report_no'";
                        $check_result = $conn->query($check_sql);
                        $counter = 1;
                        while ($check_result && $check_result->num_rows > 0) {
                            $report_no = $prefix . $date . str_pad($detail_id, 4, '0', STR_PAD_LEFT) . '_' . $counter;
                            $check_sql = "SELECT report_no FROM lab_reports WHERE report_no = '$report_no'";
                            $check_result = $conn->query($check_sql);
                            $counter++;
                        }
                        
                        // Update or insert test result
                        $check_result_sql = "SELECT result_id FROM lab_test_results WHERE order_detail_id = $detail_id";
                        $check_result_result = $conn->query($check_result_sql);
                        
                        if ($check_result_result && $check_result_result->num_rows > 0) {
                            $sql_result = "UPDATE lab_test_results SET 
                                           result_value = '$result_value',
                                           remarks = '$remarks',
                                           report_status = 'Completed',
                                           updated_at = NOW()
                                           WHERE order_detail_id = $detail_id";
                        } else {
                            $sql_result = "INSERT INTO lab_test_results 
                                           (order_detail_id, result_value, normal_range, unit, remarks, entered_by, report_status, created_at) 
                                           VALUES 
                                           ($detail_id, '$result_value', '$normal_range', '$unit', '$remarks', $user_id, 'Completed', NOW())";
                        }
                        
                        if ($conn->query($sql_result)) {
                            $final_remarks = $report_remarks ? $report_remarks . " (Result: $result_value)" : "Result: $result_value";
                            
                            $sql_report = "INSERT INTO lab_reports 
                                           (order_id, detail_id, patient_id, doctor_id, technician_id, 
                                            report_no, report_date, report_file, report_status, remarks, hospital_id) 
                                           VALUES 
                                           ($order_id, $detail_id, $patient_id, $doctor_id, $user_id, 
                                            '$report_no', '$report_date', '', 'Completed', 
                                            '$final_remarks', $hid)";
                            
                            if ($conn->query($sql_report)) {
                                $saved_count++;
                                
                                // Save to document upload
                                $document_name = !empty($test_name) ? $test_name : 'Report_' . $report_no;
                                $document_type = 'Lab Result';
                                $document_sub_category = 'Lab';
                                
                                $pdf_file_name = $report_no . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $document_name) . '.pdf';
                                $upload_dir = "../documents/upload/documents/";
                                if (!file_exists($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                
                                $report_content = "========================================\n";
                                $report_content .= "LAB REPORT\n";
                                $report_content .= "========================================\n\n";
                                $report_content .= "Report No: $report_no\n";
                                $report_content .= "Date: $report_date\n";
                                $report_content .= "Patient ID: $patient_id\n";
                                $report_content .= "Test: $document_name\n";
                                $report_content .= "Result: $result_value\n";
                                $report_content .= "Normal Range: $normal_range\n";
                                $report_content .= "Unit: $unit\n";
                                $report_content .= "Remarks: $remarks\n";
                                $report_content .= "========================================\n";
                                
                                $file_path = $upload_dir . $pdf_file_name;
                                file_put_contents($file_path, $report_content);
                                $file_size = filesize($file_path);
                                
                                $sql_doc = "INSERT INTO document_upload 
                                            (patient_id, document_name, document_type, document_category, 
                                             document_sub_category, upload_file, file_size, uploaded_by, 
                                             note, document_tags, document_date, created_at) 
                                            VALUES 
                                            ($patient_id, '$document_name', '$document_type', '$document_category', 
                                             '$document_sub_category', '$pdf_file_name', $file_size, $user_id, 
                                             'Generated from lab report: $report_no', 'lab_report, $report_no', '$report_date', NOW())";
                                $conn->query($sql_doc);
                            } else {
                                $all_success = false;
                                $error_messages[] = "Failed to save report for test ID $detail_id: " . $conn->error;
                            }
                        } else {
                            $all_success = false;
                            $error_messages[] = "Failed to save result for test ID $detail_id: " . $conn->error;
                        }
                    }
                }
                
                // Check if all tests are completed
                $check_all = $conn->query("SELECT COUNT(*) as total FROM lab_order_details od 
                                          LEFT JOIN lab_test_results r ON od.detail_id = r.order_detail_id
                                          WHERE od.order_id = $order_id AND (r.result_id IS NULL OR r.result_id = 0)");
                if ($check_all && $check_all->fetch_assoc()['total'] == 0) {
                    $conn->query("UPDATE lab_orders SET order_status = 'Completed' WHERE order_id = $order_id");
                    unset($_SESSION[$cache_key]);
                }
                
                if ($all_success && $saved_count > 0) {
                    echo json_encode(['success' => true, 'message' => "Report generated successfully! $saved_count test(s) saved."]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Report generated with warnings: " . implode("; ", $error_messages)]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No test results provided!']);
        }
        exit();
    }
}

// ========== GET HOSPITAL DATA (CACHED) ==========
$hospital_cache_key = 'hospital_data';
$hospital_data = isset($_SESSION[$hospital_cache_key]) ? $_SESSION[$hospital_cache_key] : null;
if (!$hospital_data || !isset($hospital_data['time']) || (time() - $hospital_data['time'] > 3600)) {
    $sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
    $result_hospital = $conn->query($sql_hospital);
    if ($result_hospital && $result_hospital->num_rows > 0) {
        $hospital_data = $result_hospital->fetch_assoc();
        $hospital_data['time'] = time();
        $_SESSION[$hospital_cache_key] = $hospital_data;
    }
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";
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
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
     
        
        .stat-card .stat-icon i,
        .stat-card .stat-icon .fas,
        .stat-card .stat-icon .fa {
            color: #3b82f6 !important;
        }
        
        .tab-btn i,
        .tab-btn .fas,
        .tab-btn .fa {
            color: #3b82f6 !important;
        }
        
        .card-header i,
        .card-header .fas,
        .card-header .fa {
            color: #3b82f6 !important;
        }
        
        .btn-primary i,
        .btn-primary .fas,
        .btn-primary .fa,
        .btn-success i,
        .btn-success .fas,
        .btn-success .fa,
        .btn-danger i,
        .btn-danger .fas,
        .btn-danger .fa {
            color: white !important;
        }
        
        .welcome-section i,
        .welcome-section .fas,
        .welcome-section .fa {
            color: white !important;
        }
        
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
        
        .tab-container { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; background: none; border: none; font-size: 14px; font-weight: 500; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .actions-cell { white-space: nowrap; }
        .readonly-field { background: #f3f4f6; cursor: not-allowed; }
        
        .test-result-item {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .test-result-item:hover {
            background: #f8fafc;
            border-color: #3b82f6;
        }
        .test-number {
            width: 32px;
            height: 32px;
            background: #dbeafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(59,130,246,0.3);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        .toast.success { background: #22c55e; }
        .toast.error { background: #ef4444; }
        .toast.info { background: #3b82f6; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast .close-toast {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-left: 12px;
            opacity: 0.7;
        }
        .toast .close-toast:hover { opacity: 1; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #4b5563;
            font-size: 13px;
        }
        .pagination a:hover {
            background: #f3f4f6;
        }
        .pagination .active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
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

            <!-- Alert Container -->
            <div id="alertContainer"></div>

            <!-- Statistics -->
            <div class="stat-grid">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-icon text-blue-500"><i class="fas fa-file-medical"></i></div>
                    <div class="stat-number text-blue-600" id="statTotal"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div class="stat-icon text-yellow-500"><i class="fas fa-clock"></i></div>
                    <div class="stat-number text-yellow-600" id="statPending"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                    <div class="stat-icon text-purple-500"><i class="fas fa-flask"></i></div>
                    <div class="stat-number text-purple-600" id="statCollected"><?php echo $collected_orders; ?></div>
                    <div class="stat-label">Sample Collected</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f97316;">
                    <div class="stat-icon text-orange-500"><i class="fas fa-cogs"></i></div>
                    <div class="stat-number text-orange-600" id="statProcess"><?php echo $process_orders; ?></div>
                    <div class="stat-label">In Process</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-icon text-green-500"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number text-green-600" id="statCompleted"><?php echo $completed_orders; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #ef4444;">
                    <div class="stat-icon text-red-500"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-number text-red-600" id="statRejected"><?php echo $rejected_orders; ?></div>
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
                                        <?php $counter = ($page - 1) * $per_page + 1; ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr class="cursor-pointer hover:bg-gray-50 transition-colors" onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                                <td><?php echo $counter++; ?></td>
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($order['order_no']); ?></span></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['patient_name'] ?? 'N/A'); ?>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['patient_mobile'] ?? ''); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['doctor_display'] ?? 'N/A'); ?></td>
                                                <td><span class="badge-count"><?php echo $order['test_count']; ?> tests</span></td>
                                                <td>
                                                    <?php 
                                                    $status_class = strtolower(str_replace(' ', '_', $order['order_status']));
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                                                </td>
                                                <td class="actions-cell" onclick="event.stopPropagation();">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <?php if ($order['order_status'] == 'Pending'): ?>
                                                            <button onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'Assigned')" class="btn-accept btn-sm" style="background:#22c55e;color:white;padding:4px 10px;border-radius:6px;font-size:11px;border:none;cursor:pointer;">
                                                                <i class="fas fa-check"></i> Accept
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($order['order_status'] != 'Completed' && $order['order_status'] != 'Cancelled' && $order['order_status'] != 'Pending'): ?>
                                                            <select onchange="updateOrderStatus(<?php echo $order['order_id']; ?>, this.value)" 
                                                                    class="form-select text-xs" style="width:auto;padding:2px 6px;font-size:11px;border-radius:6px;">
                                                                <option value="">Update</option>
                                                                <option value="Sample Collected" <?php echo $order['order_status'] == 'Sample Collected' ? 'selected' : ''; ?>>Sample Collected</option>
                                                                <option value="In Process" <?php echo $order['order_status'] == 'In Process' ? 'selected' : ''; ?>>In Process</option>
                                                                <option value="Completed">Completed</option>
                                                            </select>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($order['order_status'] != 'Completed' && $order['order_status'] != 'Cancelled'): ?>
                                                            <button onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'Cancelled')" class="btn-reject btn-sm" style="background:#ef4444;color:white;padding:4px 10px;border-radius:6px;font-size:11px;border:none;cursor:pointer;">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($order['order_status'] == 'Completed' && empty($order['report_id'])): ?>
                                                            <button onclick="openReportModal(<?php echo $order['order_id']; ?>)" class="btn-success btn-sm">
                                                                <i class="fas fa-file-alt"></i> Generate Report
                                                            </button>
                                                        <?php elseif ($order['order_status'] == 'Completed' && !empty($order['report_id'])): ?>
                                                            <span style="display:inline-block;padding:4px 10px;background:#dcfce7;color:#166534;border-radius:6px;font-size:11px;font-weight:600;">
                                                                <i class="fas fa-check-circle"></i> Report Done
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <?php
                            $total_sql = "SELECT COUNT(*) as total FROM lab_orders WHERE technician_id = $user_id AND delete_flag = 0";
                            $total_result = $conn->query($total_sql);
                            $total_rows = $total_result ? $total_result->fetch_assoc()['total'] : 0;
                            $total_pages = ceil($total_rows / $per_page);
                            if ($total_pages > 1):
                            ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
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
                                            <tr class="cursor-pointer hover:bg-gray-50 transition-colors" onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                                <td><?php echo $counter++; ?></td>
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($order['order_no']); ?></span></td>
                                                <td><?php echo htmlspecialchars($order['patient_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($order['doctor_display'] ?? 'N/A'); ?></td>
                                                <td><span class="badge-count"><?php echo $order['test_count']; ?> tests</span></td>
                                                <td class="actions-cell">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <?php if (empty($order['report_id'])): ?>
                                                            <button onclick="event.stopPropagation(); openReportModal(<?php echo $order['order_id']; ?>)" class="btn-success btn-sm">
                                                                <i class="fas fa-file-alt"></i> Generate Report
                                                            </button>
                                                        <?php else: ?>
                                                            <span style="display:inline-block;padding:4px 10px;background:#dcfce7;color:#166534;border-radius:6px;font-size:11px;font-weight:600;">
                                                                <i class="fas fa-check-circle"></i> Report Generated
                                                            </span>
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
        <form id="resultForm">
            <input type="hidden" name="action" value="save_result">
            <input type="hidden" name="detail_id" id="result_detail_id">
            <input type="hidden" name="order_id" id="result_order_id">
            
            <div class="form-group">
                <label>Test Result <span class="required">*</span></label>
                <input type="text" class="form-input" id="result_value" name="result_value" required placeholder="Enter test result value">
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
                <textarea class="form-input" id="result_remarks" name="remarks" rows="3" placeholder="Any remarks about this test..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('resultModal')">Cancel</button>
                <button type="submit" class="btn-primary" id="saveResultBtn">
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
            <h2><i class="fas fa-file-alt mr-2 text-green-500"></i> Generate Report</h2>
            <button class="modal-close" onclick="closeModal('reportModal')">&times;</button>
        </div>
        <form id="reportForm">
            <input type="hidden" name="action" value="generate_report">
            <input type="hidden" name="order_id" id="report_order_id">

            <!-- Test Count Info -->
            <div class="bg-blue-50 p-3 rounded-lg mb-4">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i> 
                    Enter results for each test below. 
                    <strong id="testCountDisplay">0</strong> test(s) found.
                </p>
            </div>

            <!-- Document Category Selection -->
            <div class="form-group">
                <label>Document Category <span class="required">*</span></label>
                <select class="form-select" name="document_category" id="document_category" required>
                    <option value="">-- Select Document Category --</option>
                    <option value="Pre-Operation">🔬 Pre-Operation</option>
                    <option value="Operation-Theater">🏥 Operation-Theater</option>
                    <option value="Post-Operation" selected>💊 Post-Operation</option>
                </select>
                <p class="info-text mt-1" style="font-size:12px;color:#6b7280;">
                    <i class="fas fa-info-circle"></i> Selected category will be used for document upload
                </p>
            </div>

            <!-- Report Date -->
            <div class="form-group">
                <label>Report Date</label>
                <input type="date" class="form-input" name="report_date" id="report_date" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <!-- Dynamic Test Result Entries -->
            <div id="testUploadContainer" class="mb-4">
                <h4 class="font-semibold text-gray-700 mb-2">Enter Results for Each Test</h4>
                <div id="testUploadList" class="space-y-3">
                    <div class="text-gray-500 text-center py-4">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading tests...
                    </div>
                </div>
            </div>

            <!-- General Remarks -->
            <div class="form-group">
                <label>General Remarks (Optional)</label>
                <textarea class="form-input" name="report_remarks" id="report_remarks" rows="2" placeholder="Additional notes for all tests..."></textarea>
            </div>

            <!-- Footer Buttons -->
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('reportModal')">Cancel</button>
                <button type="submit" class="btn-success" id="generateReportBtn">
                    <i class="fas fa-save"></i> Generate Report & Upload
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== TOAST CONTAINER ========== -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:9999;"></div>

<script>
// ========== TAB SWITCHING ==========
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelector('.tab-btn[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
}

// ========== TOAST NOTIFICATION ==========
function showToast(message, type = 'info') {
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    var icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    toast.innerHTML = '<i class="fas ' + icon + ' mr-2"></i>' + message + 
                      '<button class="close-toast" onclick="this.parentElement.remove()">&times;</button>';
    container.appendChild(toast);
    setTimeout(function() {
        if (toast.parentElement) toast.remove();
    }, 5000);
}

// ========== AJAX: UPDATE ORDER STATUS ==========
function updateOrderStatus(orderId, status) {
    if (!status) return;
    if (!confirm('Update status to ' + status + '?')) {
        var selects = document.querySelectorAll('select[onchange*="updateOrderStatus(' + orderId + ')"]');
        selects.forEach(function(sel) { sel.value = ''; });
        return;
    }
    
    var btn = event ? event.target : null;
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
    
    fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_status&order_id=' + orderId + '&status=' + status
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Update stats and reload page after short delay
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showToast(data.message || 'Failed to update status', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Accept'; }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Accept'; }
    });
}

// ========== OPEN RESULT MODAL ==========
function openResultModal(detailId, orderId, normalRange, unit) {
    document.getElementById('result_detail_id').value = detailId;
    document.getElementById('result_order_id').value = orderId;
    document.getElementById('result_normal_range').value = normalRange || '';
    document.getElementById('result_unit').value = unit || '';
    document.getElementById('result_value').value = '';
    document.getElementById('result_remarks').value = '';
    document.getElementById('resultModal').classList.add('show');
}

// ========== RESULT FORM SUBMIT (AJAX) ==========
document.getElementById('resultForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('saveResultBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    var formData = new FormData(this);
    
    fetch('dashboard.php?ajax=1', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('resultModal');
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showToast(data.message || 'Failed to save result', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Result';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Result';
    });
});

// ========== OPEN REPORT MODAL ==========
function openReportModal(orderId) {
    document.getElementById('report_order_id').value = orderId;
    document.getElementById('report_remarks').value = '';
    document.getElementById('report_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('reportModal').classList.add('show');
    loadTestsForReport(orderId);
}

// ========== LOAD TESTS FOR REPORT (AJAX) ==========
function loadTestsForReport(orderId) {
    var container = document.getElementById('testUploadList');
    container.innerHTML = '<div class="text-gray-500 text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading tests...</div>';
    
    fetch('get_order_tests.php?order_id=' + orderId)
    .then(response => response.json())
    .then(data => {
        if (data.tests && data.tests.length > 0) {
            var html = '';
            data.tests.forEach(function(test, index) {
                html += `
                    <div class="test-result-item">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="test-number">${index + 1}</span>
                                <div>
                                    <div class="font-medium text-gray-800">${test.test_name || 'Test ' + (index + 1)}</div>
                                    <div class="text-xs text-gray-500">Code: ${test.test_code || 'N/A'}</div>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">Detail ID: ${test.detail_id}</span>
                        </div>
                        
                        <input type="hidden" name="results[${index}][order_detail_id]" value="${test.detail_id}">
                        <input type="hidden" name="results[${index}][normal_range]" value="${test.normal_range || ''}">
                        <input type="hidden" name="results[${index}][unit]" value="${test.unit || ''}">
                        <input type="hidden" name="results[${index}][test_name]" value="${test.test_name || ''}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-700">Normal Range</label>
                                <input type="text" class="form-input w-full text-sm bg-gray-100 cursor-not-allowed" 
                                       value="${test.normal_range || 'N/A'}" readonly style="background-color:#f3f4f6;cursor:not-allowed;">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-700">Unit</label>
                                <input type="text" class="form-input w-full text-sm bg-gray-100 cursor-not-allowed" 
                                       value="${test.unit || 'N/A'}" readonly style="background-color:#f3f4f6;cursor:not-allowed;">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-700">Result Value <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full text-sm" 
                                       name="results[${index}][result_value]" placeholder="Enter test result" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="text-xs font-medium text-gray-700">Remarks</label>
                            <input type="text" class="form-input w-full text-sm" 
                                   name="results[${index}][remarks]" placeholder="e.g. Normal, High, Low, or any comments">
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
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<div class="text-red-500 text-center py-4"><i class="fas fa-exclamation-circle mr-2"></i> Error loading tests</div>';
        document.getElementById('testCountDisplay').textContent = '?';
    });
}

// ========== REPORT FORM SUBMIT (AJAX) ==========
document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('generateReportBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    // Validate all result fields
    var resultInputs = this.querySelectorAll('input[name*="[result_value]"]');
    var emptyFound = false;
    resultInputs.forEach(function(input) {
        if (input.value.trim() === '') {
            input.style.borderColor = 'red';
            emptyFound = true;
        } else {
            input.style.borderColor = '';
        }
    });
    
    if (emptyFound) {
        showToast('Please enter result values for all tests.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Generate Report & Upload';
        return;
    }
    
    var formData = new FormData(this);
    
    fetch('dashboard.php?ajax=1', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('reportModal');
            setTimeout(function() { location.reload(); }, 2000);
        } else {
            showToast(data.message || 'Failed to generate report', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Generate Report & Upload';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Generate Report & Upload';
    });
});

// ========== CLOSE MODAL ==========
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// ========== VIEW ORDER ==========
function viewOrder(orderId) {
    if (orderId) {
        window.location.href = 'view_order.php?order_id=' + orderId;
    }
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

// ========== REFRESH STATS ON TAB CHANGE ==========
// Auto-refresh stats every 60 seconds
setInterval(function() {
    fetch('dashboard.php?ajax=1&action=get_stats')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('statTotal').textContent = data.total || 0;
            document.getElementById('statPending').textContent = data.pending || 0;
            document.getElementById('statCollected').textContent = data.collected || 0;
            document.getElementById('statProcess').textContent = data.process || 0;
            document.getElementById('statCompleted').textContent = data.completed || 0;
            document.getElementById('statRejected').textContent = data.cancelled || 0;
        }
    })
    .catch(error => console.log('Stats refresh error:', error));
}, 60000);
</script>

</body>
</html>