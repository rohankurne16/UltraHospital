<?php
// ============================================================
// DASHBOARD - WITH DYNAMIC DATA FROM ALL TABLES
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/permission.php';
require_once 'config/hospital.php';

// Check login
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$user_id = $_SESSION['id'];
$role_id = $_SESSION['role_id'];
$role_name = $_SESSION['role'] ?? '';
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$page_title = 'Dashboard';

// ============================================================
// GET DASHBOARD STATISTICS FROM ALL TABLES
// ============================================================

// Today's date
$today = date('Y-m-d');
$current_month = date('m');
$current_year = date('Y');
$current_week = date('W');

// Initialize all statistics
$stats = [
    'total_patients' => 0,
    'total_doctors' => 0,
    'total_staff' => 0,
    'total_departments' => 0,
    'total_appointments' => 0,
    'today_appointments' => 0,
    'pending_appointments' => 0,
    'total_opd' => 0,
    'total_ipd' => 0,
    'total_prescriptions' => 0,
    'total_bills' => 0,
    'total_wards' => 0,
    'total_rooms' => 0,
    'total_beds' => 0,
    'total_lab_tests' => 0,
    'revenue_today' => 0,
    'revenue_month' => 0,
    'revenue_year' => 0,
    'new_patients_today' => 0,
    'new_patients_week' => 0,
    'new_patients_month' => 0,
    'appointment_today' => 0,
    'appointment_week' => 0,
    'appointment_month' => 0,
    'opd_today' => 0,
    'opd_month' => 0,
    'ipd_active' => 0,
    'ipd_discharged' => 0,
];

// ============================================================
// FETCH DATA FROM ALL TABLES
// ============================================================

// Patients
if (hasPermission('patient-view')) {
    // Total Patients
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_patients'] = $row['total'] ?? 0;
    }
    
    // New Patients Today
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = '$today'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['new_patients_today'] = $row['total'] ?? 0;
    }
    
    // New Patients This Week
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND WEEK(created_at) = '$current_week' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['new_patients_week'] = $row['total'] ?? 0;
    }
    
    // New Patients This Month
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['new_patients_month'] = $row['total'] ?? 0;
    }
}

// Doctors
if (hasPermission('doctor-view')) {
    $query = "SELECT COUNT(*) as total FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_doctors'] = $row['total'] ?? 0;
    }
}

// Staff
if (hasPermission('staff-view')) {
    $query = "SELECT COUNT(*) as total FROM staff WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_staff'] = $row['total'] ?? 0;
    }
}

// Departments
if (hasPermission('department-view')) {
    $query = "SELECT COUNT(*) as total FROM department WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_departments'] = $row['total'] ?? 0;
    }
}

// Appointments
if (hasPermission('appointment-view')) {
    // Total Appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_appointments'] = $row['total'] ?? 0;
    }
    
    // Today's Appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND appointment_date = '$today'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['appointment_today'] = $row['total'] ?? 0;
        $stats['today_appointments'] = $row['total'] ?? 0;
    }
    
    // Week Appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND WEEK(appointment_date) = '$current_week' AND YEAR(appointment_date) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['appointment_week'] = $row['total'] ?? 0;
    }
    
    // Month Appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(appointment_date) = '$current_month' AND YEAR(appointment_date) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['appointment_month'] = $row['total'] ?? 0;
    }
    
    // Pending Appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Pending'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['pending_appointments'] = $row['total'] ?? 0;
    }
}

// OPD
if (hasPermission('opd-view')) {
    // Total OPD
    $query = "SELECT COUNT(*) as total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_opd'] = $row['total'] ?? 0;
    }
    
    // OPD Today
    $query = "SELECT COUNT(*) as total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = '$today'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['opd_today'] = $row['total'] ?? 0;
    }
    
    // OPD Month
    $query = "SELECT COUNT(*) as total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['opd_month'] = $row['total'] ?? 0;
    }
}

// IPD Admissions
if (hasPermission('ipd-view')) {
    // Total IPD
    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_ipd'] = $row['total'] ?? 0;
    }
    
    // Active IPD
    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL) AND (status = 'Active' OR status = 'Admitted')";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['ipd_active'] = $row['total'] ?? 0;
    }
    
    // Discharged IPD
    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Discharged'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['ipd_discharged'] = $row['total'] ?? 0;
    }
}

// Prescriptions
if (hasPermission('prescription-view')) {
    $query = "SELECT COUNT(*) as total FROM prescriptions WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_prescriptions'] = $row['total'] ?? 0;
    }
}

// Billing - Check if columns exist first
if (hasPermission('billing-view')) {
    // First check if billing table has records
    $query = "SELECT COUNT(*) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_bills'] = $row['total'] ?? 0;
    }
    
    // Try to get revenue - check if total_amount column exists
    // First, check if column exists
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'total_amount'");
    $has_total_amount = mysqli_num_rows($check_col) > 0;
    
    if ($has_total_amount) {
        // Today's Revenue
        $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = '$today'";
        if ($hospital_id > 0) {
            $query .= " AND hospital_id = '$hospital_id'";
        }
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['revenue_today'] = $row['total'] ?? 0;
        }
        
        // Month's Revenue
        $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
        if ($hospital_id > 0) {
            $query .= " AND hospital_id = '$hospital_id'";
        }
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['revenue_month'] = $row['total'] ?? 0;
        }
        
        // Year's Revenue
        $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND YEAR(created_at) = '$current_year'";
        if ($hospital_id > 0) {
            $query .= " AND hospital_id = '$hospital_id'";
        }
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['revenue_year'] = $row['total'] ?? 0;
        }
    } else {
        // Try alternative column names
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'amount'");
        $has_amount = mysqli_num_rows($check_col) > 0;
        
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'bill_amount'");
        $has_bill_amount = mysqli_num_rows($check_col) > 0;
        
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'total'");
        $has_total = mysqli_num_rows($check_col) > 0;
        
        $amount_col = 'total_amount';
        if ($has_amount) $amount_col = 'amount';
        elseif ($has_bill_amount) $amount_col = 'bill_amount';
        elseif ($has_total) $amount_col = 'total';
        
        if ($has_amount || $has_bill_amount || $has_total) {
            // Today's Revenue
            $query = "SELECT COALESCE(SUM($amount_col), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = '$today'";
            if ($hospital_id > 0) {
                $query .= " AND hospital_id = '$hospital_id'";
            }
            $result = mysqli_query($conn, $query);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $stats['revenue_today'] = $row['total'] ?? 0;
            }
            
            // Month's Revenue
            $query = "SELECT COALESCE(SUM($amount_col), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
            if ($hospital_id > 0) {
                $query .= " AND hospital_id = '$hospital_id'";
            }
            $result = mysqli_query($conn, $query);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $stats['revenue_month'] = $row['total'] ?? 0;
            }
            
            // Year's Revenue
            $query = "SELECT COALESCE(SUM($amount_col), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND YEAR(created_at) = '$current_year'";
            if ($hospital_id > 0) {
                $query .= " AND hospital_id = '$hospital_id'";
            }
            $result = mysqli_query($conn, $query);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $stats['revenue_year'] = $row['total'] ?? 0;
            }
        }
    }
}

// Wards
if (hasPermission('ward-view') || hasPermission('bed-view')) {
    $query = "SELECT COUNT(*) as total FROM wards WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_wards'] = $row['total'] ?? 0;
    }
}

// Room Master
if (hasPermission('room-view') || hasPermission('bed-view')) {
    $query = "SELECT COUNT(*) as total FROM room_master WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_rooms'] = $row['total'] ?? 0;
    }
}

// Bed Master
if (hasPermission('bed-view')) {
    $query = "SELECT COUNT(*) as total FROM bed_master WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_beds'] = $row['total'] ?? 0;
    }
}

// Lab Tests
if (hasPermission('lab-test-view')) {
    $query = "SELECT COUNT(*) as total FROM lab_test_master WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_lab_tests'] = $row['total'] ?? 0;
    }
}

// ============================================================
// GET CHART DATA
// ============================================================

// Monthly Patient Data for Chart
$monthly_patients = [];
for ($i = 1; $i <= 12; $i++) {
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$i' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    $count = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['total'] ?? 0;
    }
    $monthly_patients[] = $count;
}

// Monthly Appointment Data for Chart
$monthly_appointments = [];
for ($i = 1; $i <= 12; $i++) {
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(appointment_date) = '$i' AND YEAR(appointment_date) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    $count = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['total'] ?? 0;
    }
    $monthly_appointments[] = $count;
}

// Monthly Revenue Data for Chart - Only if billing table has amount column
$monthly_revenue = array_fill(0, 12, 0);
if (hasPermission('billing-view')) {
    // Check if any amount column exists
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'total_amount'");
    $has_total_amount = mysqli_num_rows($check_col) > 0;
    if (!$has_total_amount) {
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'amount'");
        $has_total_amount = mysqli_num_rows($check_col) > 0;
        if (!$has_total_amount) {
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'bill_amount'");
            $has_total_amount = mysqli_num_rows($check_col) > 0;
        }
        if (!$has_total_amount) {
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing LIKE 'total'");
            $has_total_amount = mysqli_num_rows($check_col) > 0;
        }
    }
    
    if ($has_total_amount) {
        $amount_col = 'total_amount';
        // Find the correct column name
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM billing");
        while ($col = mysqli_fetch_assoc($check_col)) {
            if (in_array($col['Field'], ['total_amount', 'amount', 'bill_amount', 'total'])) {
                $amount_col = $col['Field'];
                break;
            }
        }
        
        for ($i = 1; $i <= 12; $i++) {
            $query = "SELECT COALESCE(SUM($amount_col), 0) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$i' AND YEAR(created_at) = '$current_year'";
            if ($hospital_id > 0) {
                $query .= " AND hospital_id = '$hospital_id'";
            }
            $result = mysqli_query($conn, $query);
            $count = 0;
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $count = $row['total'] ?? 0;
            }
            $monthly_revenue[$i-1] = $count;
        }
    }
}

// Department-wise Patient Distribution
// Department-wise Patient Distribution
$department_patients = [];
$department_names = [];

// First, check what the primary key column name is in patients table
$pk_check = mysqli_query($conn, "SHOW KEYS FROM patients WHERE Key_name = 'PRIMARY'");
$pk_row = mysqli_fetch_assoc($pk_check);
$pk_column = $pk_row['Column_name'] ?? 'id';

// Also check if department_id column exists
$dept_col_check = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'department_id'");
$has_dept_col = mysqli_num_rows($dept_col_check) > 0;

if ($has_dept_col) {
    $query = "SELECT d.department_name, COUNT(p.$pk_column) as total 
              FROM department d 
              LEFT JOIN patients p ON d.id = p.department_id AND (p.delete_flag=0 OR p.delete_flag IS NULL)
              WHERE (d.delete_flag=0 OR d.delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND d.hospital_id = '$hospital_id'";
    }
    $query .= " GROUP BY d.id LIMIT 6";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $department_names[] = $row['department_name'];
            $department_patients[] = (int)$row['total'];
        }
    }
} else {
    // If department_id doesn't exist, try other possible column names
    $possible_dept_cols = ['dept_id', 'deptartment', 'department', 'dept'];
    foreach ($possible_dept_cols as $col) {
        $col_check = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE '$col'");
        if (mysqli_num_rows($col_check) > 0) {
            $query = "SELECT d.department_name, COUNT(p.$pk_column) as total 
                      FROM department d 
                      LEFT JOIN patients p ON d.id = p.$col AND (p.delete_flag=0 OR p.delete_flag IS NULL)
                      WHERE (d.delete_flag=0 OR d.delete_flag IS NULL)";
            if ($hospital_id > 0) {
                $query .= " AND d.hospital_id = '$hospital_id'";
            }
            $query .= " GROUP BY d.id LIMIT 6";
            $result = mysqli_query($conn, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $department_names[] = $row['department_name'];
                    $department_patients[] = (int)$row['total'];
                }
            }
            break;
        }
    }
}

// If no department column found, just show department names with 0
if (empty($department_names)) {
    $query = "SELECT department_name FROM department WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $query .= " LIMIT 6";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $department_names[] = $row['department_name'];
            $department_patients[] = 0;
        }
    }
}

// Appointment Status Distribution
$status_data = [];
$status_labels = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
foreach ($status_labels as $status) {
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = '$status'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    $count = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['total'] ?? 0;
    }
    $status_data[] = $count;
}

// Gender Distribution
$gender_data = [];
$query = "SELECT gender, COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) GROUP BY gender";
if ($hospital_id > 0) {
    $query .= " AND hospital_id = '$hospital_id'";
}
$result = mysqli_query($conn, $query);
$gender_counts = ['Male' => 0, 'Female' => 0, 'Other' => 0];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $gender = $row['gender'] ?? 'Other';
        if ($gender == 'Male' || $gender == 'male' || $gender == 'M' || $gender == 'm') {
            $gender_counts['Male'] = (int)$row['total'];
        } elseif ($gender == 'Female' || $gender == 'female' || $gender == 'F' || $gender == 'f') {
            $gender_counts['Female'] = (int)$row['total'];
        } else {
            $gender_counts['Other'] = (int)$row['total'];
        }
    }
}
$gender_labels = ['Male', 'Female', 'Other'];
$gender_values = array_values($gender_counts);

// ============================================================
// GET TODAY'S APPOINTMENTS LIST
// ============================================================
$today_appointments_list = [];
if (hasPermission('appointment-view')) {
    $query = "SELECT a.*, p.name as patient_name, p.id as patient_id, d.doctor_name 
              FROM appointments a 
              LEFT JOIN patients p ON a.patient_id = p.id 
              LEFT JOIN doctor d ON a.doctor_id = d.id 
              WHERE (a.delete_flag=0 OR a.delete_flag IS NULL) AND a.appointment_date = '$today'";
    if ($hospital_id > 0) {
        $query .= " AND a.hospital_id = '$hospital_id'";
    }
    $query .= " ORDER BY a.appointment_time LIMIT 5";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $today_appointments_list[] = $row;
        }
    }
}

// ============================================================
// GET RECENT ACTIVITY
// ============================================================
$recent_activities = [];
$query = "SELECT module, action, created_at, register_id FROM audit_logs";
if ($hospital_id > 0) {
    $query .= " WHERE hospital_id = '$hospital_id' OR hospital_id IS NULL";
}
$query .= " ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_activities[] = $row;
    }
}

// ============================================================
// GET HOSPITAL NAME AND USER DATA
// ============================================================
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$user_name = $_SESSION['name'] ?? 'User';
$profile_image = $_SESSION['profile_image'] ?? '';
$user_role = $_SESSION['role'] ?? '';

// Build modules array for widgets
$modules = [];
if (hasPermission('appointment-view')) {
    $modules[] = ['title' => 'Appointments', 'count' => $stats['total_appointments'], 'icon' => 'fa-calendar-check', 'color' => 'blue', 'sub' => $stats['today_appointments'] . ' today'];
}
if (hasPermission('patient-view')) {
    $modules[] = ['title' => 'Patients', 'count' => $stats['total_patients'], 'icon' => 'fa-users', 'color' => 'green', 'sub' => $stats['new_patients_today'] . ' new today'];
}
if (hasPermission('doctor-view')) {
    $modules[] = ['title' => 'Doctors', 'count' => $stats['total_doctors'], 'icon' => 'fa-user-md', 'color' => 'purple', 'sub' => 'Available'];
}
if (hasPermission('opd-view')) {
    $modules[] = ['title' => 'OPD Visits', 'count' => $stats['total_opd'], 'icon' => 'fa-stethoscope', 'color' => 'orange', 'sub' => $stats['opd_today'] . ' today'];
}
if (hasPermission('ipd-view')) {
    $modules[] = ['title' => 'IPD Admissions', 'count' => $stats['total_ipd'], 'icon' => 'fa-hospital-user', 'color' => 'red', 'sub' => $stats['ipd_active'] . ' active'];
}
if (hasPermission('prescription-view')) {
    $modules[] = ['title' => 'Prescriptions', 'count' => $stats['total_prescriptions'], 'icon' => 'fa-prescription', 'color' => 'pink', 'sub' => 'Total issued'];
}
if (hasPermission('billing-view')) {
    $modules[] = ['title' => 'Bills', 'count' => $stats['total_bills'], 'icon' => 'fa-file-invoice', 'color' => 'indigo', 'sub' => '₹' . number_format($stats['revenue_today']) . ' today'];
}
if (hasPermission('staff-view')) {
    $modules[] = ['title' => 'Staff', 'count' => $stats['total_staff'], 'icon' => 'fa-user-tie', 'color' => 'cyan', 'sub' => 'Total'];
}
if (hasPermission('department-view')) {
    $modules[] = ['title' => 'Departments', 'count' => $stats['total_departments'], 'icon' => 'fa-building', 'color' => 'teal', 'sub' => 'Active'];
}
if (hasPermission('bed-view')) {
    $modules[] = ['title' => 'Beds', 'count' => $stats['total_beds'], 'icon' => 'fa-bed', 'color' => 'rose', 'sub' => 'Available'];
}

// Month names for charts
$month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars($hospital_name); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
   
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f0f2f5;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 70px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            height: 100%;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .dashboard-card .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .quick-action-btn {
    padding: 16px 12px;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    background: #fafbfc;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    color: #1e293b;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    position: relative;
    overflow: hidden;
}

.quick-action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, transparent 50%, rgba(59, 130, 246, 0.03));
    pointer-events: none;
}

.quick-action-btn:hover {
    background: #ffffff;
    border-color: #e2e8f0;
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.quick-action-btn .icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.quick-action-btn:hover .icon-wrapper {
    transform: scale(1.05);
}

.quick-action-btn .action-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1e293b;
    margin-top: 4px;
    transition: color 0.3s ease;
}

.quick-action-btn:hover .action-label {
    color: #3b82f6;
}

.quick-action-btn .action-sub {
    font-size: 0.6rem;
    color: #94a3b8;
    margin-top: 1px;
}
        .dashboard-card .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .dashboard-card .card-label {
            font-size: 0.85rem;
            color: #94a3b8;
        }
        
        .dashboard-card .card-sub {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 2px;
        }
        
        .color-blue { background: #dbeafe; color: #3b82f6; }
        .color-green { background: #dcfce7; color: #22c55e; }
        .color-purple { background: #f3e8ff; color: #8b5cf6; }
        .color-orange { background: #fef3c7; color: #f59e0b; }
        .color-red { background: #fee2e2; color: #dc2626; }
        .color-pink { background: #fce7f3; color: #ec4899; }
        .color-indigo { background: #e0e7ff; color: #6366f1; }
        .color-cyan { background: #cffafe; color: #06b6d4; }
        .color-teal { background: #ccfbf1; color: #14b8a6; }
        .color-yellow { background: #fef9c3; color: #eab308; }
        .color-rose { background: #ffe4e6; color: #f43f5e; }
        
        .quick-action-btn {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .quick-action-btn:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.15);
        }
        .quick-action-btn i {
            font-size: 1.5rem;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 12px;
            padding: 30px;
            color: white;
        }
        .welcome-card .text-blue-light { color: rgba(255,255,255,0.8); }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .activity-dot.blue { background: #3b82f6; }
        .activity-dot.green { background: #22c55e; }
        .activity-dot.orange { background: #f59e0b; }
        .activity-dot.red { background: #dc2626; }
        .activity-dot.purple { background: #8b5cf6; }
        
        .activity-text { font-size: 0.85rem; color: #1e293b; }
        .activity-time { font-size: 0.7rem; color: #94a3b8; }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .appointment-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .appointment-item:last-child { border-bottom: none; }
        .appointment-time {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1e293b;
            min-width: 70px;
        }
        .appointment-patient {
            flex: 1;
            font-size: 0.85rem;
            color: #1e293b;
        }
        .appointment-doctor {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .appointment-status {
            font-size: 0.7rem;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #dbeafe; color: #2563eb; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<!-- Include Dynamic Sidebar -->
  <?php include 'header.php'; ?>
<?php include 'Sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content <?php echo isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : ''; ?>" id="mainContent">
    
    <!-- Welcome Card -->
    <div class="welcome-card mb-6">
        <div class="flex flex-wrap justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p class="text-blue-light mt-1">
                    <i class="fas fa-calendar-alt mr-1"></i> <?php echo date('l, F j, Y'); ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-clock mr-1"></i> <?php echo date('h:i A'); ?>
                </p>
            </div>
            <div class="flex items-center gap-3 mt-2 sm:mt-0 flex-wrap">
                <span class="px-3 py-1 bg-white/20 text-white rounded-full text-sm flex items-center">
                    <i class="fas fa-user-tag mr-1"></i> <?php echo htmlspecialchars($user_role); ?>
                </span>
                <?php if (!empty($hospital_name)): ?>
                <span class="px-3 py-1 bg-white/20 text-white rounded-full text-sm flex items-center">
                    <i class="fas fa-hospital mr-1"></i> <?php echo htmlspecialchars($hospital_name); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        
      

    <!-- Main Statistics Cards -->
    <?php if (!empty($modules)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-6">
        <?php foreach ($modules as $module): ?>
        <div class="dashboard-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="card-value"><?php echo $module['count']; ?></div>
                    <div class="card-label"><?php echo htmlspecialchars($module['title']); ?></div>
                    <?php if (isset($module['sub'])): ?>
                    <div class="card-sub"><?php echo $module['sub']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-icon color-<?php echo $module['color'] ?? 'blue'; ?>">
                    <i class="fas <?php echo $module['icon']; ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

  <!-- Quick Actions -->
  <!-- Quick Stats Mini -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mt-4">
            <div class="bg-white/10 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold"><?php echo $stats['today_appointments']; ?></div>
                <div class="text-xs text-blue-light">Today's Appointments</div>
            </div>
            <div class="bg-white/10 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold"><?php echo $stats['new_patients_today']; ?></div>
                <div class="text-xs text-blue-light">New Patients</div>
            </div>
            <div class="bg-white/10 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold"><?php echo $stats['ipd_active']; ?></div>
                <div class="text-xs text-blue-light">Active IPD</div>
            </div>
            <div class="bg-white/10 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold"><?php echo $stats['opd_today']; ?></div>
                <div class="text-xs text-blue-light">OPD Today</div>
            </div>
            <div class="bg-white/10 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold"><?php echo $stats['pending_appointments']; ?></div>
                <div class="text-xs text-blue-light">Pending</div>
            </div>
            <div class="bg-white/10 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold">₹<?php echo number_format($stats['revenue_today']); ?></div>
                <div class="text-xs text-blue-light">Today's Revenue</div>
            </div>
        </div>
    </div>
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Patient & Appointment Chart -->
        <div class="dashboard-card">
            <h4 class="font-semibold text-gray-700 mb-4">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i> Patients & Appointments Overview
            </h4>
            <div class="chart-container">
                <canvas id="patientAppointmentChart"></canvas>
            </div>
        </div>
        
        <!-- Revenue Chart -->
        <div class="dashboard-card">
            <h4 class="font-semibold text-gray-700 mb-4">
                <i class="fas fa-chart-bar text-green-600 mr-2"></i> Monthly Revenue
            </h4>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Second Row Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Department Distribution -->
        <div class="dashboard-card">
            <h4 class="font-semibold text-gray-700 mb-4">
                <i class="fas fa-building text-purple-600 mr-2"></i> Department Patients
            </h4>
            <div class="chart-container" style="height: 200px;">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
        
        <!-- Appointment Status -->
        <div class="dashboard-card">
            <h4 class="font-semibold text-gray-700 mb-4">
                <i class="fas fa-chart-pie text-orange-600 mr-2"></i> Appointment Status
            </h4>
            <div class="chart-container" style="height: 200px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        
        <!-- Gender Distribution -->
        <div class="dashboard-card">
            <h4 class="font-semibold text-gray-700 mb-4">
                <i class="fas fa-venus-mars text-pink-600 mr-2"></i> Gender Distribution
            </h4>
            <div class="chart-container" style="height: 200px;">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Two Column Layout - Today's Appointments & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Left Column - Today's Appointments -->
        <div class="dashboard-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-calendar-day text-blue-600 mr-2"></i> Today's Appointments
                </h3>
                <a href="appointments.php" class="text-sm text-blue-600 hover:underline">View All</a>
            </div>
            
            <div class="space-y-1 max-h-[300px] overflow-y-auto">
                <?php if (!empty($today_appointments_list)): ?>
                    <?php foreach ($today_appointments_list as $appt): ?>
                    <div class="appointment-item">
                        <span class="appointment-time">
                            <?php echo date('h:i A', strtotime($appt['appointment_time'] ?? '00:00:00')); ?>
                        </span>
                        <div class="appointment-patient">
                            <div><?php echo htmlspecialchars($appt['patient_name'] ?? 'Unknown'); ?></div>
                            <div class="appointment-doctor">
                                <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($appt['doctor_name'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <span class="appointment-status status-<?php echo strtolower($appt['status'] ?? 'pending'); ?>">
                            <?php echo htmlspecialchars($appt['status'] ?? 'Pending'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-calendar-check text-3xl block mb-2"></i>
                        <p>No appointments scheduled for today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Recent Activity -->
        <div class="dashboard-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-clock text-blue-600 mr-2"></i> Recent Activity
                </h3>
                <a href="audit_logs.php" class="text-sm text-blue-600 hover:underline">View All</a>
            </div>
            
            <div class="space-y-1 max-h-[300px] overflow-y-auto">
                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $log): 
                        $dot_color = 'blue';
                        if (strpos($log['action'], 'Create') !== false || strpos($log['action'], 'Add') !== false) {
                            $dot_color = 'green';
                        } elseif (strpos($log['action'], 'Delete') !== false || strpos($log['action'], 'Remove') !== false) {
                            $dot_color = 'red';
                        } elseif (strpos($log['action'], 'Update') !== false || strpos($log['action'], 'Edit') !== false) {
                            $dot_color = 'orange';
                        } elseif (strpos($log['action'], 'Login') !== false) {
                            $dot_color = 'purple';
                        }
                    ?>
                    <div class="activity-item">
                        <span class="activity-dot <?php echo $dot_color; ?>"></span>
                        <div class="flex-1">
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($log['module']); ?></strong> 
                                <?php echo htmlspecialchars($log['action']); ?>
                            </div>
                            <div class="activity-time">
                                <?php echo date('d M Y h:i A', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-inbox text-3xl block mb-2"></i>
                        <p>No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Patients & Appointments Chart (Bar Chart)
    const ctx1 = document.getElementById('patientAppointmentChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($month_names); ?>,
            datasets: [
                {
                    label: 'Patients',
                    data: <?php echo json_encode($monthly_patients); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 4,
                },
                {
                    label: 'Appointments',
                    data: <?php echo json_encode($monthly_appointments); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderColor: '#22c55e',
                    borderWidth: 2,
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: { size: 11 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Revenue Chart (Line Chart)
    const ctx2 = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($month_names); ?>,
            datasets: [{
                label: 'Revenue (₹)',
                data: <?php echo json_encode($monthly_revenue); ?>,
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderColor: '#22c55e',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#22c55e',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: { size: 11 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 3. Department Chart (Doughnut)
    const ctx3 = document.getElementById('departmentChart').getContext('2d');
    const deptColors = ['#3b82f6', '#22c55e', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'];
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($department_names); ?>,
            datasets: [{
                data: <?php echo json_encode($department_patients); ?>,
                backgroundColor: deptColors.slice(0, <?php echo count($department_patients); ?>),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: { size: 10 }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // 4. Appointment Status Chart (Pie)
    const ctx4 = document.getElementById('statusChart').getContext('2d');
    const statusColors = ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444'];
    new Chart(ctx4, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_data); ?>,
                backgroundColor: statusColors,
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // 5. Gender Distribution Chart (Doughnut)
    const ctx5 = document.getElementById('genderChart').getContext('2d');
    const genderColors = ['#3b82f6', '#ec4899', '#8b5cf6'];
    new Chart(ctx5, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($gender_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($gender_values); ?>,
                backgroundColor: genderColors,
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: { size: 10 }
                    }
                }
            },
            cutout: '60%'
        }
    });
});

// Update main content margin when sidebar toggles
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        const isCollapsed = sidebar.classList.contains('collapsed');
        if (isCollapsed) {
            mainContent.style.marginLeft = '70px';
        }
    }
});

// Set sidebar toggle listener
document.addEventListener('sidebarToggled', function(e) {
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.style.marginLeft = e.detail.collapsed ? '70px' : '260px';
        mainContent.classList.toggle('sidebar-collapsed', e.detail.collapsed);
    }
});
</script>

</body>
</html>