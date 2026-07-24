<?php

// ============================================================
// DASHBOARD - WITH DYNAMIC DATA, QUICK ACTIONS & NOTICES
// (Corrected: permission-slug mismatches, missing columns, type safety)
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config/permission.php';
require_once '../config/hospital.php';

// Check login
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit;
}

// Get user data (cast IDs to int for basic type safety)
$user_id     = (int) $_SESSION['id'];
$role_id     = (int) ($_SESSION['role_id'] ?? 0);
$role_name   = $_SESSION['role'] ?? '';
$hospital_id = (int) ($_SESSION['hospital_id'] ?? 0);
$hid         = $hospital_id; // kept for any legacy includes that reference $hid
$page_title  = 'Dashboard';

// ============================================================
// GET DASHBOARD STATISTICS FROM ALL TABLES
// ============================================================

// Today's date
$today         = date('Y-m-d');
$current_month = date('m');
$current_year  = date('Y');
$current_week  = date('W');

// Initialize all statistics
$stats = [
    'total_patients'      => 0,
    'total_doctors'       => 0,
    'total_staff'         => 0,
    'total_departments'   => 0,
    'total_appointments'  => 0,
    'today_appointments'  => 0,
    'pending_appointments'=> 0,
    'total_opd'           => 0,
    'total_ipd'           => 0,
    'total_prescriptions' => 0,
    'total_bills'         => 0,
    'total_wards'         => 0,
    'total_rooms'         => 0,
    'total_beds'          => 0,
    'available_beds'      => 0,
    'total_lab_tests'     => 0,
    'revenue_today'       => 0,
    'revenue_month'       => 0,
    'revenue_year'        => 0,
    'new_patients_today'  => 0,
    'new_patients_week'   => 0,
    'new_patients_month'  => 0,
    'appointment_today'   => 0,
    'appointment_week'    => 0,
    'appointment_month'   => 0,
    'opd_today'           => 0,
    'opd_month'           => 0,
    'ipd_active'          => 0,
    'ipd_discharged'      => 0,
    'pending_bills'       => 0,
    'pending_lab_orders'  => 0,
    'critical_alerts'     => 0,
];

// For Doctor role - get doctor's appointments and patients
$is_doctor = strtolower(trim($role_name)) === 'doctor';
$doctor_id = 0;

if ($is_doctor) {
    $doc_query = "SELECT * FROM doctor WHERE register_id = '$user_id'";
    $doc_result = mysqli_query($conn, $doc_query);
    if ($doc_result && $row = mysqli_fetch_assoc($doc_result)) {
        $_SESSION['doctor_id']     = $doctor_id = $row['doctor_id'];
        $_SESSION['doctor_name']   = $doctor_name = $row['doctor_name'];
        $_SESSION['doctor_image']  = $doctor_image = $row['doctor_image'];
        $_SESSION['doctor_mobile'] = $doctor_mobile = $row['mobile'];
        $_SESSION['doctor_email']  = $doctor_email = $row['email'];
    }
}

// --- Statistics (only if permission) ---

// Patients
if (hasPermission('patient-view')) {
    // Total Patients
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_patients'] = $row['total'] ?? 0; }

    // New Patients Today
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = '$today'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['new_patients_today'] = $row['total'] ?? 0; }

    // New Patients This Month
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['new_patients_month'] = $row['total'] ?? 0; }
}

// Doctors
if (hasPermission('doctor-view')) {
    $query = "SELECT COUNT(*) as total FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_doctors'] = $row['total'] ?? 0; }
}

// Staff
if (hasPermission('staff-view')) {
    $query = "SELECT COUNT(*) as total FROM staff WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_staff'] = $row['total'] ?? 0; }
}

// Departments
if (hasPermission('department-view')) {
    $query = "SELECT COUNT(*) as total FROM department WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_departments'] = $row['total'] ?? 0; }
}

// Appointments
if (hasPermission('appointment-view')) {
    // Total
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_appointments'] = $row['total'] ?? 0; }

    // Today
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND appointment_date = '$today'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['appointment_today'] = $row['total'] ?? 0; $stats['today_appointments'] = $row['total'] ?? 0; }

    // Pending
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Scheduled'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['pending_appointments'] = $row['total'] ?? 0; }
}

// OPD Visits
// FIX: the correct permission slug is 'opd-visit-view' (there is no 'opd-view' permission)
if (hasPermission('opd-visit-view')) {
    $query = "SELECT COUNT(*) as total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_opd'] = $row['total'] ?? 0; }

    $query = "SELECT COUNT(*) as total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = '$today'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['opd_today'] = $row['total'] ?? 0; }
}

// IPD Admissions
// FIX: the correct permission slug is 'ipd-admission-view' (there is no 'ipd-view')
if (hasPermission('ipd-admission-view')) {
    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_ipd'] = $row['total'] ?? 0; }

    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Admitted'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['ipd_active'] = $row['total'] ?? 0; }
}

// Prescriptions
if (hasPermission('prescription-view')) {
    $query = "SELECT COUNT(*) as total FROM prescriptions WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_prescriptions'] = $row['total'] ?? 0; }
}

// Billing
// FIX: 'billing-view' does not exist as a permission slug -> use 'payments-view'.
// FIX: `billing` table has no `status` column, so "Pending" bills are derived from pending_amount > 0 instead.
if (hasPermission('payments-view')) {
    $query = "SELECT COUNT(*) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_bills'] = $row['total'] ?? 0; }

    // Pending bills (no `status` column exists — use pending_amount > 0)
    $query = "SELECT COUNT(*) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL) AND pending_amount > 0";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['pending_bills'] = $row['total'] ?? 0; }
}

// Lab Tests
if (hasPermission('lab-test-view')) {
    $query = "SELECT COUNT(*) as total FROM lab_tests WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_lab_tests'] = $row['total'] ?? 0; }

    // FIX: lab_orders' status column is actually named `order_status`, not `status`.
    $query = "SELECT COUNT(*) as total FROM lab_orders WHERE (delete_flag=0 OR delete_flag IS NULL) AND order_status != 'Completed'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['pending_lab_orders'] = $row['total'] ?? 0; }
}

// Wards / Rooms / Beds — declared in $stats originally but never populated or shown.
if (hasPermission('ward-view')) {
    $query = "SELECT COUNT(*) as total FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_wards'] = $row['total'] ?? 0; }
}

if (hasPermission('room-view')) {
    $query = "SELECT COUNT(*) as total FROM room_master WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_rooms'] = $row['total'] ?? 0; }
}

if (hasPermission('bed-view')) {
    $query = "SELECT COUNT(*) as total FROM bed_master WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['total_beds'] = $row['total'] ?? 0; }

    $query = "SELECT COUNT(*) as total FROM bed_master WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Available'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    $result = mysqli_query($conn, $query);
    if ($result) { $row = mysqli_fetch_assoc($result); $stats['available_beds'] = $row['total'] ?? 0; }
}

// Critical Alerts (from patient_alerts)
$query = "SELECT COUNT(*) as total FROM patient_alerts WHERE (delete_flag=0 OR delete_flag IS NULL) AND is_read = 0";
if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
$result = mysqli_query($conn, $query);
if ($result) { $row = mysqli_fetch_assoc($result); $stats['critical_alerts'] = $row['total'] ?? 0; }

// ============================================================
// GET CHART DATA
// ============================================================

$monthly_patients = [];
for ($i = 1; $i <= 12; $i++) {
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$i' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    $count = 0;
    if ($result) { $row = mysqli_fetch_assoc($result); $count = $row['total'] ?? 0; }
    $monthly_patients[] = $count;
}

$monthly_appointments = [];
for ($i = 1; $i <= 12; $i++) {
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(appointment_date) = '$i' AND YEAR(appointment_date) = '$current_year'";
    if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    $count = 0;
    if ($result) { $row = mysqli_fetch_assoc($result); $count = $row['total'] ?? 0; }
    $monthly_appointments[] = $count;
}

// ============================================================
// GET TODAY'S APPOINTMENTS LIST
// ============================================================
$today_appointments_list = [];
if (hasPermission('appointment-view')) {
    $query = "SELECT a.*, p.patient_name, p.patient_id, d.doctor_name
              FROM appointments a
              LEFT JOIN patients p ON a.patient_id = p.patient_id
              LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
              WHERE (a.delete_flag=0 OR a.delete_flag IS NULL) AND a.appointment_date = '$today'";
    if ($hospital_id > 0) $query .= " AND a.hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND a.doctor_id = '$doctor_id'";
    $query .= " ORDER BY a.appointment_time LIMIT 5";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $today_appointments_list[] = $row;
        }
    }
}

// ============================================================
// GET UPCOMING APPOINTMENTS (beyond today)
// ============================================================
$upcoming_appointments = [];
if (hasPermission('appointment-view')) {
    $query = "SELECT a.*, p.patient_name, p.patient_id, d.doctor_name
              FROM appointments a
              LEFT JOIN patients p ON a.patient_id = p.patient_id
              LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
              WHERE (a.delete_flag=0 OR a.delete_flag IS NULL) AND a.appointment_date > '$today' AND a.status != 'Cancelled'";
    if ($hospital_id > 0) $query .= " AND a.hospital_id = '$hospital_id'";
    if ($is_doctor && $doctor_id > 0) $query .= " AND a.doctor_id = '$doctor_id'";
    $query .= " ORDER BY a.appointment_date, a.appointment_time LIMIT 5";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $upcoming_appointments[] = $row;
        }
    }
}

// ============================================================
// GET RECENT ACTIVITY (from audit_logs)
// ============================================================
// FIX: previously gated on hasPermission('audit-view'), a slug that doesn't exist
// in the permissions table, so this section never rendered for anyone. The query
// is already scoped to the user's own hospital_id, so it's safe to run unguarded.
$recent_activities = [];
$query = "SELECT * FROM audit_logs WHERE (delete_flag=0 OR delete_flag IS NULL)";
if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
$query .= " ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_activities[] = $row;
    }
}

// ============================================================
// GET CRITICAL ALERTS (from patient_alerts)
// ============================================================
$critical_alerts_list = [];
$query = "SELECT * FROM patient_alerts WHERE (delete_flag=0 OR delete_flag IS NULL) AND is_read = 0";
if ($hospital_id > 0) $query .= " AND hospital_id = '$hospital_id'";
$query .= " ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $critical_alerts_list[] = $row;
    }
}

// ============================================================
// GET HOSPITAL NAME AND USER DATA
// ============================================================
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$user_name     = $_SESSION['name'] ?? 'User';
$profile_image = $_SESSION['profile_image'] ?? '';
$user_role     = $_SESSION['role'] ?? '';

$month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($hospital_name); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js 3 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #6d5ef8;
            --primary-dark: #5a46e8;
            --surface: #ffffff;
            --bg: #f4f5fb;
            --text-main: #1a1d29;
            --text-muted: #6b7280;
            --radius-lg: 18px;
            --radius-md: 12px;
            --shadow-soft: 0 6px 24px rgba(28, 30, 60, 0.06);
            --shadow-hover: 0 14px 34px rgba(28, 30, 60, 0.12);
        }

        body {
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
        }

        .main-content {
            margin-left: 260px;
            padding: 24px 28px 40px;
            min-height: 100vh;
        }

        @media (max-width: 1279px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(120deg, #6d5ef8 0%, #9b6ef3 55%, #c874e0 100%);
            border-radius: var(--radius-lg);
            padding: 28px 36px;
            color: white;
            margin-bottom: 26px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(109, 94, 248, 0.28);
        }
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -60%;
            right: -8%;
            width: 420px;
            height: 420px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -70%;
            left: 10%;
            width: 260px;
            height: 260px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .welcome-content { position: relative; z-index: 1; }
        .welcome-title { font-size: 25px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.3px; }
        .welcome-subtitle { font-size: 14px; opacity: 0.92; margin-bottom: 14px; }
        .welcome-stats { display: flex; gap: 28px; flex-wrap: wrap; }
        .welcome-stat-item { display: flex; align-items: center; gap: 8px; font-size: 14px; opacity: 0.95; background: rgba(255,255,255,0.14); padding: 6px 14px; border-radius: 30px; backdrop-filter: blur(2px); }
        .welcome-stat-item .value { font-weight: 800; font-size: 15px; }
        .welcome-date { position: relative; z-index: 1; text-align: right; font-size: 14px; opacity: 0.9; }

        /* Stat Cards */
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px 22px;
            box-shadow: var(--shadow-soft);
            transition: all 0.25s ease;
            border: 1px solid rgba(28,30,60,0.04);
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }
        .stat-card .stat-number {
            font-size: 29px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            margin-bottom: 3px;
            letter-spacing: -0.5px;
        }
        .stat-card .stat-label {
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .stat-card .stat-sub {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .icon-blue   { background: #e8f0ff; color: #3767f0; }
        .icon-green  { background: #e2f8ec; color: #12a150; }
        .icon-purple { background: #f1e9ff; color: #7c3aed; }
        .icon-orange { background: #fff2e2; color: #ea8a00; }
        .icon-red    { background: #ffe9e9; color: #e0403f; }
        .icon-pink   { background: #ffe6f1; color: #d6266f; }
        .icon-indigo { background: #e8eaff; color: #4438ca; }
        .icon-cyan   { background: #e0f7fb; color: #0891a8; }
        .icon-teal   { background: #dcf7f1; color: #0e8a72; }
        .icon-emerald{ background: #e3fbe9; color: #16a34a; }

        /* Chart Cards */
        .chart-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(28,30,60,0.04);
            height: 100%;
        }
        .chart-card .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-card .card-title .badge {
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            background: #f0f1fa;
            color: var(--text-muted);
        }
        .chart-container { position: relative; height: 220px; }

        /* Quick Actions */
        .quick-actions {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(28,30,60,0.04);
            height: 100%;
        }
        .quick-actions .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            border-radius: 12px;
            background: #f8f9fd;
            color: var(--text-main);
            border: 1px solid #edeefa;
            transition: all 0.2s ease;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
        }
        .quick-actions .action-btn:hover {
            background: #f0eeff;
            border-color: var(--primary);
            transform: translateX(3px);
            box-shadow: 0 4px 14px rgba(109, 94, 248, 0.18);
        }
        .quick-actions .action-btn i { font-size: 18px; }

        /* Appointment / Activity / Alert Lists */
        .appointment-list, .activity-list, .alert-list {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(28,30,60,0.04);
            height: 100%;
        }
        .list-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .appointment-item, .activity-item, .alert-item {
            display: flex;
            align-items: center;
            padding: 11px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .appointment-item:last-child, .activity-item:last-child, .alert-item:last-child {
            border-bottom: none;
        }
        .appointment-item .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6d5ef8 0%, #9b6ef3 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            margin-right: 12px;
        }
        .appointment-item .info, .activity-item .info, .alert-item .info {
            flex: 1;
        }
        .appointment-item .info .name, .activity-item .info .name, .alert-item .info .name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 14px;
        }
        .appointment-item .info .details, .activity-item .info .details, .alert-item .info .details {
            font-size: 12px;
            color: var(--text-muted);
        }
        .appointment-item .time {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            background: #f3f4f6;
            padding: 4px 11px;
            border-radius: 20px;
        }
        .status-badge {
            font-size: 10px;
            padding: 3px 11px;
            border-radius: 20px;
            font-weight: 600;
            margin-left: 8px;
        }
        .status-badge.scheduled { background: #fff2e2; color: #ea8a00; }
        .status-badge.confirmed { background: #e8f0ff; color: #3767f0; }
        .status-badge.completed { background: #e2f8ec; color: #12a150; }
        .status-badge.cancelled { background: #ffe6f1; color: #d6266f; }
        .status-badge.pending   { background: #fff2e2; color: #ea8a00; }

        .alert-item .alert-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .welcome-section { padding: 20px; }
            .welcome-title { font-size: 21px; }
            .welcome-stats { gap: 12px; }
            .welcome-date { text-align: left; margin-top: 10px; }
            .stat-card .stat-number { font-size: 23px; }
            .chart-container { height: 180px; }
        }
    </style>
</head>
<body>

<!-- Include Sidebar -->
<?php include '../Sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <?php include '../header.php'; ?>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8 welcome-content">
                <div class="welcome-title">
                    <i class="fas fa-wave-square me-2"></i>
                    Welcome back, <?php echo htmlspecialchars($user_name); ?>!
                </div>
                <div class="welcome-subtitle">
                    <?php echo htmlspecialchars($hospital_name); ?> &middot; <?php echo ucfirst(htmlspecialchars($user_role)); ?>
                    <?php if ($is_doctor): ?>
                        <span class="badge bg-light text-dark ms-2"><i class="fas fa-user-md"></i> Doctor</span>
                    <?php endif; ?>
                </div>
                <div class="welcome-stats">
                    <div class="welcome-stat-item">
                        <i class="fas fa-calendar-day"></i>
                        <span class="value"><?php echo date('d M Y'); ?></span>
                    </div>
                    <?php if ($stats['today_appointments'] > 0): ?>
                    <div class="welcome-stat-item">
                        <i class="fas fa-calendar-check"></i>
                        <span class="value"><?php echo $stats['today_appointments']; ?></span> appointments today
                    </div>
                    <?php endif; ?>
                    <?php if ($stats['new_patients_today'] > 0): ?>
                    <div class="welcome-stat-item">
                        <i class="fas fa-user-plus"></i>
                        <span class="value"><?php echo $stats['new_patients_today']; ?></span> new patients
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 welcome-date">
                <div><i class="far fa-calendar-alt me-2"></i><?php echo date('l, F j, Y'); ?></div>
                <div style="font-size:12px; opacity:0.75; margin-top:4px;">
                    <i class="far fa-clock me-1"></i><?php echo date('h:i A'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards Row 1 -->
    <div class="row g-3 mb-3">
        <?php if (hasPermission('patient-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_patients']); ?></div>
                <div class="stat-label">Total Patients</div>
                <div class="stat-sub">
                    <i class="fas fa-arrow-up text-success"></i>
                    <?php echo $stats['new_patients_today']; ?> new today
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('doctor-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-user-md"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_doctors']); ?></div>
                <div class="stat-label">Total Doctors</div>
                <div class="stat-sub"><i class="fas fa-circle text-success" style="font-size:8px;"></i> Active</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('appointment-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_appointments']); ?></div>
                <div class="stat-label">Total Appointments</div>
                <div class="stat-sub">
                    <?php if ($stats['pending_appointments'] > 0): ?>
                    <i class="fas fa-clock text-warning"></i> <?php echo $stats['pending_appointments']; ?> pending
                    <?php else: ?>
                    <i class="fas fa-check-circle text-success"></i> All processed
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('opd-visit-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fas fa-stethoscope"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_opd']); ?></div>
                <div class="stat-label">OPD Visits</div>
                <div class="stat-sub"><i class="fas fa-calendar-day"></i> <?php echo $stats['opd_today']; ?> today</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stat Cards Row 2 -->
    <div class="row g-3 mb-3">
        <?php if (hasPermission('ipd-admission-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-red"><i class="fas fa-hospital-user"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_ipd']); ?></div>
                <div class="stat-label">IPD Admissions</div>
                <div class="stat-sub"><i class="fas fa-bed"></i> <?php echo $stats['ipd_active']; ?> active</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('prescription-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-pink"><i class="fas fa-prescription"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_prescriptions']); ?></div>
                <div class="stat-label">Prescriptions</div>
                <div class="stat-sub"><i class="fas fa-pills"></i> Total issued</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('payments-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-indigo"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_bills']); ?></div>
                <div class="stat-label">Bills</div>
                <div class="stat-sub">
                    <?php if ($stats['pending_bills'] > 0): ?>
                    <i class="fas fa-exclamation-triangle text-warning"></i> <?php echo $stats['pending_bills']; ?> pending
                    <?php else: ?>
                    <i class="fas fa-check-circle text-success"></i> All cleared
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('lab-test-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-cyan"><i class="fas fa-flask"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_lab_tests']); ?></div>
                <div class="stat-label">Lab Tests</div>
                <div class="stat-sub">
                    <?php if ($stats['pending_lab_orders'] > 0): ?>
                    <i class="fas fa-hourglass-half text-warning"></i> <?php echo $stats['pending_lab_orders']; ?> pending
                    <?php else: ?>
                    <i class="fas fa-check-circle text-success"></i> All done
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stat Cards Row 3 (Wards / Rooms / Beds) -->
    <?php if (hasPermission('ward-view') || hasPermission('room-view') || hasPermission('bed-view')): ?>
    <div class="row g-3 mb-3">
        <?php if (hasPermission('ward-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-teal"><i class="fas fa-door-closed"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_wards']); ?></div>
                <div class="stat-label">Wards</div>
                <div class="stat-sub"><i class="fas fa-hospital"></i> Facility-wide</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('room-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-cyan"><i class="fas fa-door-open"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_rooms']); ?></div>
                <div class="stat-label">Rooms</div>
                <div class="stat-sub"><i class="fas fa-th-large"></i> Facility-wide</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('bed-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-emerald"><i class="fas fa-bed"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_beds']); ?></div>
                <div class="stat-label">Beds</div>
                <div class="stat-sub"><i class="fas fa-check-circle text-success"></i> <?php echo $stats['available_beds']; ?> available</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Charts Row -->
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-title">
                    <span><i class="fas fa-chart-line text-primary me-2"></i>Patients Overview</span>
                    <span class="badge"><?php echo $current_year; ?></span>
                </div>
                <div class="chart-container">
                    <canvas id="patientChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-title">
                    <span><i class="fas fa-calendar-alt text-purple me-2"></i>Appointments Overview</span>
                    <span class="badge"><?php echo $current_year; ?></span>
                </div>
                <div class="chart-container">
                    <canvas id="appointmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions + Notices + Activity -->
    <div class="row g-3 mb-3">
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="quick-actions">
                <div class="list-title">
                    <span><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</span>
                </div>
                <div class="d-grid gap-2">
                    <?php if (hasPermission('patient-add')): ?>
                    <a href="add_patient.php" class="action-btn"><i class="fas fa-user-plus text-primary"></i> Add Patient</a>
                    <?php endif; ?>
                    <?php if (hasPermission('appointment-add')): ?>
                    <a href="add_appointment.php" class="action-btn"><i class="fas fa-calendar-plus text-purple"></i> Add Appointment</a>
                    <?php endif; ?>
                    <?php if (hasPermission('prescription-add')): ?>
                    <a href="add_prescription.php" class="action-btn"><i class="fas fa-prescription text-pink"></i> New Prescription</a>
                    <?php endif; ?>
                    <?php if (hasPermission('doctor-add')): ?>
                    <a href="add_doctor.php" class="action-btn"><i class="fas fa-user-md text-green"></i> Add Doctor</a>
                    <?php endif; ?>
                    <?php if (hasPermission('staff-add')): ?>
                    <a href="add_staff.php" class="action-btn"><i class="fas fa-user-tie text-cyan"></i> Add Staff</a>
                    <?php endif; ?>
                    <?php if (hasPermission('billing-add') || hasPermission('payments-create')): ?>
                    <a href="add_bill.php" class="action-btn"><i class="fas fa-file-invoice-dollar text-indigo"></i> Generate Bill</a>
                    <?php endif; ?>
                    <?php if (hasPermission('lab-test-add') || hasPermission('lab-test-create')): ?>
                    <a href="add_lab_test.php" class="action-btn"><i class="fas fa-flask text-orange"></i> New Lab Test</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Important Notices / Alerts -->
        <div class="col-lg-4">
            <div class="alert-list">
                <div class="list-title">
                    <span><i class="fas fa-bell text-danger me-2"></i>Notifications & Alerts</span>
                    <?php if ($stats['critical_alerts'] > 0): ?>
                    <span class="badge bg-danger"><?php echo $stats['critical_alerts']; ?> new</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($critical_alerts_list)): ?>
                    <?php foreach ($critical_alerts_list as $alert): ?>
                    <div class="alert-item">
                        <div class="alert-icon"><i class="fas fa-exclamation"></i></div>
                        <div class="info">
                            <div class="name"><?php echo htmlspecialchars($alert['alert_title'] ?? $alert['alert_type'] ?? 'Critical Alert'); ?></div>
                            <div class="details"><?php echo htmlspecialchars($alert['description'] ?? ''); ?> <span class="text-muted">&middot; <?php echo date('d M H:i', strtotime($alert['created_at'])); ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                        No critical alerts at this time.
                    </div>
                <?php endif; ?>

                <!-- Additional notices: pending bills, lab orders, etc. -->
                <?php if ($stats['pending_bills'] > 0): ?>
                <div class="alert-item">
                    <div class="alert-icon" style="background: #fef3c7; color: #f59e0b;"><i class="fas fa-file-invoice"></i></div>
                    <div class="info">
                        <div class="name"><?php echo $stats['pending_bills']; ?> pending bill(s)</div>
                        <div class="details">Requires attention</div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($stats['pending_lab_orders'] > 0): ?>
                <div class="alert-item">
                    <div class="alert-icon" style="background: #e0f2fe; color: #0284c7;"><i class="fas fa-flask"></i></div>
                    <div class="info">
                        <div class="name"><?php echo $stats['pending_lab_orders']; ?> pending lab order(s)</div>
                        <div class="details">Waiting for processing</div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($stats['pending_appointments'] > 0): ?>
                <div class="alert-item">
                    <div class="alert-icon" style="background: #f3e8ff; color: #7c3aed;"><i class="fas fa-clock"></i></div>
                    <div class="info">
                        <div class="name"><?php echo $stats['pending_appointments']; ?> pending appointment(s)</div>
                        <div class="details">Need confirmation</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="activity-list">
                <div class="list-title">
                    <span><i class="fas fa-history text-info me-2"></i>Recent Activity</span>
                </div>
                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="avatar" style="background: #e0f2fe; color: #0284c7; width:36px; height:36px; font-size:14px;">
                            <i class="fas fa-circle-info"></i>
                        </div>
                        <div class="info">
                            <div class="name"><?php echo htmlspecialchars($activity['action_type'] ?? $activity['module'] ?? 'Activity'); ?></div>
                            <div class="details">
                                <?php echo htmlspecialchars($activity['description'] ?? $activity['action'] ?? ''); ?>
                                <span class="text-muted">&middot; <?php echo date('d M H:i', strtotime($activity['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No recent activity.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Today's Appointments & Upcoming Appointments -->
    <div class="row g-3">
        <?php if (hasPermission('appointment-view') && !empty($today_appointments_list)): ?>
        <div class="col-lg-6">
            <div class="appointment-list">
                <div class="list-title">
                    <span><i class="fas fa-calendar-day text-primary me-2"></i>Today's Appointments</span>
                    <span class="badge bg-primary"><?php echo count($today_appointments_list); ?> today</span>
                </div>
                <?php foreach ($today_appointments_list as $apt): ?>
                <div class="appointment-item">
                    <div class="avatar">
                        <?php echo strtoupper(substr($apt['patient_name'] ?? 'P', 0, 1)); ?>
                    </div>
                    <div class="info">
                        <div class="name"><?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown'); ?></div>
                        <div class="details">
                            <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($apt['doctor_name'] ?? 'N/A'); ?>
                            <span class="mx-2">&middot;</span>
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($apt['appointment_type'] ?? 'General'); ?>
                        </div>
                    </div>
                    <div class="time">
                        <?php echo date('h:i A', strtotime($apt['appointment_time'] ?? '00:00:00')); ?>
                    </div>
                    <span class="status-badge <?php echo strtolower($apt['status'] ?? 'scheduled'); ?>">
                        <?php echo htmlspecialchars($apt['status'] ?? 'Scheduled'); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('appointment-view') && !empty($upcoming_appointments)): ?>
        <div class="col-lg-6">
            <div class="appointment-list">
                <div class="list-title">
                    <span><i class="fas fa-calendar-plus text-success me-2"></i>Upcoming Appointments</span>
                    <span class="badge bg-success">next <?php echo count($upcoming_appointments); ?></span>
                </div>
                <?php foreach ($upcoming_appointments as $apt): ?>
                <div class="appointment-item">
                    <div class="avatar" style="background: #d1fae5; color: #065f46;">
                        <?php echo strtoupper(substr($apt['patient_name'] ?? 'P', 0, 1)); ?>
                    </div>
                    <div class="info">
                        <div class="name"><?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown'); ?></div>
                        <div class="details">
                            <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($apt['doctor_name'] ?? 'N/A'); ?>
                            <span class="mx-2">&middot;</span>
                            <i class="fas fa-calendar me-1"></i><?php echo date('d M', strtotime($apt['appointment_date'])); ?>
                            <span class="mx-1">&middot;</span>
                            <i class="fas fa-clock me-1"></i><?php echo date('h:i A', strtotime($apt['appointment_time'] ?? '00:00:00')); ?>
                        </div>
                    </div>
                    <span class="status-badge <?php echo strtolower($apt['status'] ?? 'scheduled'); ?>">
                        <?php echo htmlspecialchars($apt['status'] ?? 'Scheduled'); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthNames = <?php echo json_encode($month_names); ?>;

    Chart.defaults.font.family = "'Plus Jakarta Sans', 'Segoe UI', sans-serif";

    <?php if (hasPermission('patient-view')): ?>
    const patientCtx = document.getElementById('patientChart').getContext('2d');
    new Chart(patientCtx, {
        type: 'bar',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'New Patients',
                data: <?php echo json_encode($monthly_patients); ?>,
                backgroundColor: 'rgba(109, 94, 248, 0.75)',
                borderColor: 'rgba(109, 94, 248, 1)',
                borderWidth: 0,
                borderRadius: 8,
                barPercentage: 0.55
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f1fa' } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>

    <?php if (hasPermission('appointment-view')): ?>
    const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
    new Chart(appointmentCtx, {
        type: 'line',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'Appointments',
                data: <?php echo json_encode($monthly_appointments); ?>,
                backgroundColor: 'rgba(200, 116, 224, 0.12)',
                borderColor: 'rgba(200, 116, 224, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(200, 116, 224, 1)',
                pointRadius: 4,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f1fa' } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
});
</script>

</body>
</html>