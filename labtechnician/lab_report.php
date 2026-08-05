<?php 
session_start();
include "../config/hospital.php";

// ========== HELPER: GET DOCTOR NAME WITH CACHE ==========
function getDoctorName($conn, $doctor_id) {
    static $doctor_cache = [];
    
    if (empty($doctor_id)) {
        return 'Not Assigned';
    }
    
    if (isset($doctor_cache[$doctor_id])) {
        return $doctor_cache[$doctor_id];
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
            $doctor_cache[$doctor_id] = 'Dr. ' . $name;
            return $doctor_cache[$doctor_id];
        }
        $doctor_cache[$doctor_id] = 'Not Assigned';
        return 'Not Assigned';
    }
    
    $sql2 = "SELECT name FROM staff WHERE staff_id = $doctor_id AND role = 'Doctor' AND (delete_flag = 0 OR delete_flag IS NULL) LIMIT 1";
    $result2 = $conn->query($sql2);
    if ($result2 && $result2->num_rows > 0) {
        $staff = $result2->fetch_assoc();
        $name = $staff['name'] ?? '';
        if (!empty($name)) {
            $name = preg_replace('/^Dr\.?\s*/i', '', $name);
            $doctor_cache[$doctor_id] = 'Dr. ' . $name;
            return $doctor_cache[$doctor_id];
        }
    }
    
    $doctor_cache[$doctor_id] = 'Not Assigned';
    return 'Not Assigned';
}

// ========== HELPER: GET PATIENT NAME WITH CACHE ==========
function getPatientName($conn, $patient_id) {
    static $patient_cache = [];
    
    if (empty($patient_id)) {
        return 'N/A';
    }
    
    if (isset($patient_cache[$patient_id])) {
        return $patient_cache[$patient_id];
    }
    
    $sql = "SELECT patient_name FROM patients WHERE patient_id = $patient_id AND (delete_flag = 0 OR delete_flag IS NULL) LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $patient_cache[$patient_id] = $patient['patient_name'] ?? 'N/A';
        return $patient_cache[$patient_id];
    }
    
    $patient_cache[$patient_id] = 'N/A';
    return 'N/A';
}

// ========== HELPER: GET TEST NAME WITH CACHE ==========
function getTestName($conn, $detail_id) {
    static $test_cache = [];
    
    if (empty($detail_id)) {
        return 'N/A';
    }
    
    if (isset($test_cache[$detail_id])) {
        return $test_cache[$detail_id];
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
        $test_cache[$detail_id] = $test['test_name'] ?? 'N/A';
        return $test_cache[$detail_id];
    }
    
    $test_cache[$detail_id] = 'N/A';
    return 'N/A';
}

// ========== HELPER: GET TEST RESULT WITH CACHE ==========
function getTestResult($conn, $detail_id) {
    static $result_cache = [];
    
    if (empty($detail_id)) {
        return null;
    }
    
    if (isset($result_cache[$detail_id])) {
        return $result_cache[$detail_id];
    }
    
    $sql = "SELECT result_value, unit, normal_range, remarks 
            FROM lab_test_results 
            WHERE order_detail_id = $detail_id 
            LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $result_cache[$detail_id] = $result->fetch_assoc();
        return $result_cache[$detail_id];
    }
    
    $result_cache[$detail_id] = null;
    return null;
}

// ========== CHECK SESSION FIRST ==========
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$register_id = $_SESSION["id"];

// ========== GET TECHNICIAN INFO - CACHED ==========
$tech_cache_key = 'tech_info_' . $register_id;
$technician = isset($_SESSION[$tech_cache_key]) ? $_SESSION[$tech_cache_key] : null;

if (!$technician || !isset($technician['time']) || (time() - $technician['time'] > 300)) {
    $sql_staff = "SELECT staff_id, name, profile_image FROM staff WHERE register_id = $register_id AND role = 'Lab Technician' AND hospital_id = $hid AND delete_flag = 0";
    $result_staff = $conn->query($sql_staff);

    if ($result_staff && $result_staff->num_rows > 0) {
        $technician = $result_staff->fetch_assoc();
        $technician['time'] = time();
        $_SESSION[$tech_cache_key] = $technician;
        $user_id = $technician['staff_id'];
        $_SESSION["name"] = $technician['name'] ?? 'Technician';
        $_SESSION["role"] = "Lab Technician";
        $_SESSION["profile_image"] = $technician['profile_image'] ?? '';
        $_SESSION['staff_id'] = $user_id;
    } else {
        $sql_tech = "SELECT id, name, email, phone FROM lab_technicians WHERE register_id = $register_id AND hospital_id = $hid AND status = 'active'";
        $result_tech = $conn->query($sql_tech);
        if ($result_tech && $result_tech->num_rows > 0) {
            $technician = $result_tech->fetch_assoc();
            $technician['time'] = time();
            $_SESSION[$tech_cache_key] = $technician;
            $user_id = $technician['id'];
            $_SESSION["name"] = $technician['name'];
            $_SESSION["role"] = "Lab Technician";
            $_SESSION['lab_tech_id'] = $user_id;
        } else {
            echo "<script>alert('Lab Technician not found!'); window.location='../index.php';</script>";
            exit();
        }
    }
} else {
    $user_id = $technician['staff_id'] ?? $technician['id'] ?? 0;
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

// ========== GET HOSPITAL DATA - CACHED ==========
$hospital_cache_key = 'hospital_data_' . $hid;
$hospital_data = isset($_SESSION[$hospital_cache_key]) ? $_SESSION[$hospital_cache_key] : null;

if (!$hospital_data || !isset($hospital_data['time']) || (time() - $hospital_data['time'] > 3600)) {
    $sql_hospital = "SELECT * FROM hospital_master WHERE hospital_id = $hid AND delete_flag = 0 LIMIT 1";
    $result_hospital = $conn->query($sql_hospital);
    if ($result_hospital && $result_hospital->num_rows > 0) {
        $hospital_data = $result_hospital->fetch_assoc();
        $hospital_data['time'] = time();
        $_SESSION[$hospital_cache_key] = $hospital_data;
    } else {
        $hospital_data = [];
    }
}

$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/hospital_1784169924_6a5845c46a419.jpg";
$hospital_address = $hospital_data["address"] ?? "";
$hospital_phone = $hospital_data["phone"] ?? "";
$hospital_email = $hospital_data["email"] ?? "";
$hospital_city = $hospital_data["city"] ?? "";
$hospital_state = $hospital_data["state"] ?? "";
$hospital_pincode = $hospital_data["pincode"] ?? "";
$hospital_registration = $hospital_data["registration_number"] ?? "";
$hospital_gst = $hospital_data["gst_number"] ?? "";
$hospital_type = $hospital_data["hospital_type"] ?? "";
$hospital_established = $hospital_data["established_year"] ?? "";

$full_address = $hospital_address;
if (!empty($hospital_city)) $full_address .= ", " . $hospital_city;
if (!empty($hospital_state)) $full_address .= ", " . $hospital_state;
if (!empty($hospital_pincode)) $full_address .= " - " . $hospital_pincode;

// ========== AJAX HANDLER - MUST BE BEFORE HTML ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    if (isset($_GET['view_report'])) {
        $report_id = intval($_GET['view_report']);
        
        $sql = "SELECT r.*, 
                o.order_no, o.order_date,
                p.patient_name, p.mobile as patient_mobile, p.gender, p.date_of_birth, p.age,
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
        
        if ($result && $result->num_rows > 0) {
            $report = $result->fetch_assoc();
            $report['doctor_name'] = getDoctorName($conn, $report['doctor_id']);
            
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
            
            $result_sql = "SELECT result_value, unit, normal_range, remarks 
                           FROM lab_test_results 
                           WHERE order_detail_id = " . $report['detail_id'] . " 
                           LIMIT 1";
            $result_result = $conn->query($result_sql);
            if ($result_result && $result_result->num_rows > 0) {
                $result_data = $result_result->fetch_assoc();
                $report = array_merge($report, $result_data);
            }
            
            
            $report['hospital_name'] = $hospital_name;
            $report['hospital_logo'] = $hospital_logo;
            $report['hospital_address'] = $full_address;
            $report['hospital_phone'] = $hospital_phone;
            $report['hospital_email'] = $hospital_email;
            $report['hospital_registration'] = $hospital_registration;
            $report['hospital_gst'] = $hospital_gst;
            $report['hospital_type'] = $hospital_type;
            
            echo json_encode($report);
        } else {
            echo json_encode(['error' => 'Report not found']);
        }
        exit();
    }
    
    if (isset($_GET['action']) && $_GET['action'] == 'get_stats') {
        $sql_stats = "
            SELECT 'total' as type, COUNT(*) as count FROM lab_reports WHERE technician_id = $user_id AND hospital_id = $hid AND (delete_flag = 0 OR delete_flag IS NULL)
            UNION ALL
            SELECT 'completed' as type, COUNT(*) as count FROM lab_reports WHERE technician_id = $user_id AND hospital_id = $hid AND report_status = 'Completed' AND (delete_flag = 0 OR delete_flag IS NULL)
            UNION ALL
            SELECT 'pending' as type, COUNT(*) as count FROM lab_reports WHERE technician_id = $user_id AND hospital_id = $hid AND report_status = 'Pending' AND (delete_flag = 0 OR delete_flag IS NULL)
        ";
        $result = $conn->query($sql_stats);
        $stats = ['success' => true];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats[$row['type']] = $row['count'];
            }
        }
        echo json_encode($stats);
        exit();
    }
    
    exit();
}

// ========== GET REPORTS ==========
$reports = [];

$sql_reports = "SELECT r.*, 
                o.order_no, o.order_date,
                p.patient_name,
                d.doctor_name, d.department,
                t.test_name, t.test_code, t.normal_range as test_normal_range, t.unit as test_unit,
                tr.result_value, tr.unit as result_unit, tr.normal_range as result_normal_range, tr.remarks as result_remarks,
                s.name as technician_name
                FROM lab_reports r
                LEFT JOIN lab_orders o ON r.order_id = o.order_id AND (o.delete_flag = 0 OR o.delete_flag IS NULL)
                LEFT JOIN patients p ON r.patient_id = p.patient_id AND (p.delete_flag = 0 OR p.delete_flag IS NULL)
                LEFT JOIN doctor d ON r.doctor_id = d.doctor_id AND (d.delete_flag = 0 OR d.delete_flag IS NULL)
                LEFT JOIN lab_order_details od ON r.detail_id = od.detail_id AND (od.delete_flag = 0 OR od.delete_flag IS NULL)
                LEFT JOIN lab_tests t ON od.test_id = t.test_id
                LEFT JOIN lab_test_results tr ON od.detail_id = tr.order_detail_id
                LEFT JOIN staff s ON r.technician_id = s.staff_id
                WHERE r.technician_id = $user_id 
                AND r.hospital_id = $hid
                AND (r.delete_flag = 0 OR r.delete_flag IS NULL)
                GROUP BY r.report_id
                ORDER BY r.report_id DESC";

$result_reports = $conn->query($sql_reports);

if ($result_reports && $result_reports->num_rows > 0) {
    while ($report_row = $result_reports->fetch_assoc()) {
        if (!empty($report_row['doctor_name'])) {
            $name = preg_replace('/^Dr\.?\s*/i', '', $report_row['doctor_name']);
            $report_row['doctor_display'] = 'Dr. ' . $name;
            if (!empty($report_row['department'])) {
                $report_row['doctor_display'] .= ' (' . $report_row['department'] . ')';
            }
        } else {
            $report_row['doctor_display'] = 'Not Assigned';
        }
        
        $report_row['test_name'] = $report_row['test_name'] ?? 'N/A';
        $report_row['result_value'] = $report_row['result_value'] ?? null;
        $report_row['unit'] = $report_row['result_unit'] ?? $report_row['test_unit'] ?? null;
        $report_row['normal_range'] = $report_row['result_normal_range'] ?? $report_row['test_normal_range'] ?? null;
        $report_row['result_remarks'] = $report_row['result_remarks'] ?? null;
        
        $reports[] = $report_row;
    }
}

// ========== EDIT REPORT ==========
if (isset($_POST['edit_report'])) {
    $report_id = intval($_POST['report_id']);
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $result_value = trim($_POST['result_value'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $report_status = $_POST['report_status'] ?? 'Completed';
    
    $detail_query = $conn->query("SELECT detail_id FROM lab_reports WHERE report_id = $report_id AND technician_id = $user_id");
    if ($detail_query && $detail_query->num_rows > 0) {
        $detail_data = $detail_query->fetch_assoc();
        $detail_id = $detail_data['detail_id'];
        
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
        
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $target_dir = "../documents/reports/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
            
            if (in_array($file_ext, $allowed_ext)) {
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
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital_logo); ?>">
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
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 14px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        color: white !important;
    }
    .action-btn i { font-size: 13px; transition: transform 0.2s ease; color: white !important; }
    .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); color: white !important; }
    .action-btn:hover i { transform: scale(1.1); color: white !important; }
    .view-btn { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .view-btn:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }
    .edit-btn { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .edit-btn:hover { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4); }
    .delete-btn { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .delete-btn:hover { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); }
    .download-btn { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
    .download-btn:hover { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4); }
    .print-btn { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .print-btn:hover { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4); }
    
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
    .modal-content { background: white; border-radius: 12px; max-width: 850px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
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
    
    .actions-cell { white-space: nowrap; }
    .file-link { color: #3b82f6; text-decoration: none; }
    .file-link:hover { text-decoration: underline; }
    
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
    .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
    
    .doctor-name { font-weight: 500; color: #1e40af; }

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

    @media (max-width: 640px) {
        .action-btn span { display: none; }
        .action-btn { padding: 8px 10px; border-radius: 50%; width: 34px; height: 34px; justify-content: center; }
        .action-btn i { font-size: 14px; margin: 0; }
        .report-info-grid { grid-template-columns: 1fr; }
        .report-info-label { width: 100px; }
        .report-table th, .report-table td { padding: 6px 10px; font-size: 12px; }
        .modal-content { padding: 16px; }
        .report-container { padding: 16px; }
    }
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
                                                <td><?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($report['test_name'] ?? 'N/A'); ?></td>
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
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <button onclick="viewReport(<?php echo $report['report_id']; ?>)" 
                                                                class="action-btn view-btn" 
                                                                title="View Report">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <button onclick="editReport(<?php echo $report['report_id']; ?>)" 
                                                                class="action-btn edit-btn" 
                                                                title="Edit Report">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <button onclick="deleteReport(<?php echo $report['report_id']; ?>, '<?php echo htmlspecialchars($report['report_no']); ?>')" 
                                                                class="action-btn delete-btn" 
                                                                title="Delete Report">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        
                                                        <?php if (!empty($report['report_file'])): ?>
                                                            <a href="download_report.php?file=<?php echo urlencode($report['report_file']); ?>"
                                                               class="action-btn download-btn"
                                                               title="Download Report">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <a href="../printt_report.php?id=<?php echo $report['report_id']; ?>"
   target="_blank"
   class="action-btn print-btn"
   title="Print Report">
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

    <!-- View Report Modal -->
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
                <button class="btn-outline" onclick="closeModal('viewModal')">Close</button>
                <button onclick="printReportFromModal()" class="btn-primary">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Report Modal -->
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
    let currentReportId = null;

    function viewReport(reportId) {
        currentReportId = reportId;
        document.getElementById('viewModal').classList.add('show');
        document.getElementById('viewReportContent').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
                <p class="mt-2 text-gray-500">Loading report details...</p>
            </div>
        `;
        
        fetch(`lab_report.php?ajax=1&view_report=${reportId}`)
            .then(response => response.json())
            .then(data => {
                console.log(data);
console.log(data.hospital_logo);
console.log(data.hospital_name);
                if (data.error) {
                    document.getElementById('viewReportContent').innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="fas fa-exclamation-circle text-2xl"></i>
                            <p class="mt-2">${data.error}</p>
                        </div>
                    `;
                    return;
                }
                
                let reportDate = data.report_date || 'N/A';
                if (reportDate !== 'N/A' && reportDate !== '0000-00-00') {
                    let d = new Date(reportDate);
                    reportDate = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                }
                
                let orderDate = data.order_date || 'N/A';
                if (orderDate !== 'N/A' && orderDate !== '0000-00-00') {
                    let d = new Date(orderDate);
                    orderDate = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                }
                
                let dob = data.date_of_birth || 'N/A';
                if (dob !== 'N/A' && dob !== '0000-00-00') {
                    let d = new Date(dob);
                    dob = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                }
                
                let doctorName = data.doctor_name || 'Not Assigned';
                let statusClass = (data.report_status || 'pending').toLowerCase();
                
              let hospitalLogo = '';

if (data.hospital_logo) {
    hospitalLogo = '../' + data.hospital_logo.replace(/^(\.\.\/)+/, '');
}
                let hospitalName = data.hospital_name || 'Hospital';
                let hospitalAddress = data.hospital_address || '';
                let hospitalPhone = data.hospital_phone || '';
                let hospitalEmail = data.hospital_email || '';
                
             let logoHtml = '';

if (hospitalLogo) {
    logoHtml = `
        <img src="${hospitalLogo}"
             style="max-height:70px;max-width:120px"
             onerror="this.style.display='none'">
    `;
}
                let html = `
                    <div class="report-container" id="reportPrintArea">
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
                        
                        <div class="report-info-grid">
                            <div class="report-info-item">
                                <span class="report-info-label">Report No:</span>
                                <span class="report-info-value"><strong>${data.report_no || 'N/A'}</strong></span>
                            </div>
                            <div class="report-info-item">
                                <span class="report-info-label">Order No:</span>
                                <span class="report-info-value">${data.order_no || 'N/A'}</span>
                            </div>
                            <div class="report-info-item">
                                <span class="report-info-label">Patient Name:</span>
                                <span class="report-info-value"><strong>${data.patient_name || 'N/A'}</strong></span>
                            </div>
                            <div class="report-info-item">
                                <span class="report-info-label">Gender:</span>
                                <span class="report-info-value">${data.gender || 'N/A'}</span>
                            </div>
                            <div class="report-info-item">
                                <span class="report-info-label">Date of Birth:</span>
                                <span class="report-info-value">${dob}</span>
                            </div>
                            <div class="report-info-item">
                                <span class="report-info-label">Mobile:</span>
                                <span class="report-info-value">${data.patient_mobile || 'N/A'}</span>
                            </div>
                            <div class="report-info-item">
                                <span class="report-info-label">Referring Doctor:</span>
                                <span class="report-info-value doctor-name">${doctorName}</span>
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
                                    <span class="report-status-badge ${statusClass}">${data.report_status || 'Pending'}</span>
                                </span>
                            </div>
                        </div>
                        
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
                                <tr>
                                    <td>1</td>
                                    <td><strong>${data.test_name || 'N/A'}</strong><br>
                                        <span style="font-size:11px;color:#6b7280;">Code: ${data.test_code || 'N/A'}</span>
                                    </td>
                                    <td>
                                        <span class="report-result-highlight">${data.result_value || 'Not entered'}</span>
                                    </td>
                                    <td>${data.normal_range || data.test_normal_range || 'N/A'}</td>
                                    <td>${data.unit || data.test_unit || 'N/A'}</td>
                                    <td>${data.remarks || data.result_remarks || '-'}</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="report-footer">
                            <div>
                                <div><strong>Technician:</strong> ${data.technician_name || 'N/A'}</div>
                                <div style="font-size:12px;color:#9ca3af;margin-top:4px;">Generated on: ${new Date().toLocaleString('en-IN')}</div>
                                ${data.hospital_registration ? `<div style="font-size:11px;color:#9ca3af;margin-top:2px;">Reg No: ${data.hospital_registration}</div>` : ''}
                                ${data.hospital_gst ? `<div style="font-size:11px;color:#9ca3af;">GST: ${data.hospital_gst}</div>` : ''}
                            </div>
                            <div class="report-signature">
                                <div class="line"></div>
                                <span style="font-size:12px;color:#6b7280;">Authorized Signature</span>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('viewReportContent').innerHTML = html;
            })
            .catch((error) => {
                console.error('Error:', error);
                document.getElementById('viewReportContent').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-circle text-2xl"></i>
                        <p class="mt-2">Error loading report details. Please try again.</p>
                    </div>
                `;
            });
    }

    function printReport(reportId) {
    window.open('../printt_report.php?id=' + reportId, '_blank');
}

function printReportFromModal() {
    if (currentReportId) {
        window.open('../printt_report.php?id=' + currentReportId, '_blank');
    }
}
    function editReport(reportId) {
        document.getElementById('editModal').classList.add('show');
        document.getElementById('edit_report_id').value = reportId;
        
        fetch(`lab_report.php?ajax=1&view_report=${reportId}`)
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

    function deleteReport(reportId, reportNo) {
        if (confirm(`Are you sure you want to delete report ${reportNo}? This action cannot be undone.`)) {
            window.location.href = `lab_report.php?delete_report=${reportId}`;
        }
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
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