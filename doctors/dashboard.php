<?php
// ============================================================
// DASHBOARD - WITH DYNAMIC DATA FROM ALL TABLES
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
    header('Location: login.php');
    exit;
}

// Get user data
$user_id = $_SESSION['id'];
$role_id = $_SESSION['role_id'] ?? 0;
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

// For Doctor role - get doctor's appointments and patients
$is_doctor = strtolower(trim($role_name)) === 'doctor';
$doctor_id = 0;

if ($is_doctor) {
    // Get doctor_id from session or from doctor table
    $doc_query = "SELECT doctor_id FROM doctor WHERE register_id = '$user_id'";
    $doc_result = mysqli_query($conn, $doc_query);
    if ($doc_result && $row = mysqli_fetch_assoc($doc_result)) {
        $doctor_id = $row['doctor_id'];
    }
}

// Patients
if (hasPermission('patient-view')) {
    // Total Patients
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    // For doctors, only show their patients
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
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
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['new_patients_today'] = $row['total'] ?? 0;
    }
    
    // New Patients This Month
    $query = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
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
    // For doctors, only show their appointments
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
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
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['appointment_today'] = $row['total'] ?? 0;
        $stats['today_appointments'] = $row['total'] ?? 0;
    }
    
    // Pending Appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Scheduled'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
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
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
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
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['opd_today'] = $row['total'] ?? 0;
    }
}

// IPD Admissions
if (hasPermission('ipd-view')) {
    // Total IPD
    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_ipd'] = $row['total'] ?? 0;
    }
    
    // Active IPD
    $query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Admitted'";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['ipd_active'] = $row['total'] ?? 0;
    }
}

// Prescriptions
if (hasPermission('prescription-view')) {
    $query = "SELECT COUNT(*) as total FROM prescriptions WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_prescriptions'] = $row['total'] ?? 0;
    }
}

// Billing
if (hasPermission('billing-view')) {
    $query = "SELECT COUNT(*) as total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL)";
    if ($hospital_id > 0) {
        $query .= " AND hospital_id = '$hospital_id'";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_bills'] = $row['total'] ?? 0;
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
    $query = "SELECT COUNT(*) as total FROM lab_tests WHERE (delete_flag=0 OR delete_flag IS NULL)";
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
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
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
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND doctor_id = '$doctor_id'";
    }
    $result = mysqli_query($conn, $query);
    $count = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['total'] ?? 0;
    }
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
    if ($hospital_id > 0) {
        $query .= " AND a.hospital_id = '$hospital_id'";
    }
    if ($is_doctor && $doctor_id > 0) {
        $query .= " AND a.doctor_id = '$doctor_id'";
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
// GET HOSPITAL NAME AND USER DATA
// ============================================================
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$user_name = $_SESSION['name'] ?? 'User';
$profile_image = $_SESSION['profile_image'] ?? '';
$user_role = $_SESSION['role'] ?? '';

// Month names for charts
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
    <!-- Chart.js 3 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Main content margin to accommodate sidebar */
        .main-content {
            margin-left: 260px;
            padding: 20px 25px;
            min-height: 100vh;
        }
        
        @media (max-width: 1279px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 25px 35px;
            color: white;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .welcome-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 12px;
        }
        
        .welcome-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .welcome-stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .welcome-stat-item .value {
            font-weight: 700;
            font-size: 18px;
        }
        
        .welcome-date {
            position: relative;
            z-index: 1;
            text-align: right;
            font-size: 14px;
            opacity: 0.85;
        }
        
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 22px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #1a2332;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        .stat-card .stat-sub {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Icon Colors */
        .icon-blue { background: #e8f0fe; color: #1a73e8; }
        .icon-green { background: #e6f7e6; color: #0d8a3e; }
        .icon-purple { background: #f0e6ff; color: #7c3aed; }
        .icon-orange { background: #fff3e0; color: #f57c00; }
        .icon-red { background: #fce8e8; color: #d32f2f; }
        .icon-pink { background: #fce4ec; color: #d81b60; }
        .icon-indigo { background: #e8eaf6; color: #3949ab; }
        .icon-cyan { background: #e0f7fa; color: #00838f; }
        .icon-teal { background: #e0f2f1; color: #00695c; }
        .icon-rose { background: #fce4ec; color: #c62828; }
        
        /* Chart Cards */
        .chart-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            height: 100%;
        }
        
        .chart-card .card-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a2332;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chart-card .card-title .badge {
            font-size: 11px;
            font-weight: 400;
            padding: 4px 10px;
            border-radius: 20px;
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .chart-container {
            position: relative;
            height: 220px;
        }
        
        /* Today's Appointments */
        .appointment-list {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            height: 100%;
        }
        
        .appointment-list .list-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a2332;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .appointment-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .appointment-item:last-child {
            border-bottom: none;
        }
        
        .appointment-item .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            margin-right: 12px;
        }
        
        .appointment-item .info {
            flex: 1;
        }
        
        .appointment-item .info .name {
            font-weight: 600;
            color: #1a2332;
            font-size: 14px;
        }
        
        .appointment-item .info .details {
            font-size: 12px;
            color: #6b7280;
        }
        
        .appointment-item .time {
            font-size: 12px;
            font-weight: 500;
            color: #1a2332;
            background: #f3f4f6;
            padding: 3px 10px;
            border-radius: 20px;
        }
        
        .status-badge {
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 500;
            margin-left: 8px;
        }
        
        .status-badge.scheduled { background: #fff3e0; color: #f57c00; }
        .status-badge.confirmed { background: #e3f2fd; color: #1565c0; }
        .status-badge.completed { background: #e8f5e9; color: #2e7d32; }
        .status-badge.cancelled { background: #fce4ec; color: #c62828; }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 18px;
            }
            
            .welcome-title {
                font-size: 20px;
            }
            
            .welcome-stats {
                gap: 15px;
            }
            
            .welcome-date {
                text-align: left;
                margin-top: 8px;
            }
            
            .stat-card .stat-number {
                font-size: 22px;
            }
            
            .chart-container {
                height: 180px;
            }
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
                    <?php echo htmlspecialchars($hospital_name); ?> · <?php echo ucfirst(htmlspecialchars($user_role)); ?>
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
                <div style="font-size:12px; opacity:0.7; margin-top:3px;">
                    <i class="far fa-clock me-1"></i><?php echo date('h:i A'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Stat Cards - Row 1 -->
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
                <div class="stat-sub">
                    <i class="fas fa-circle text-success" style="font-size:8px;"></i>
                    Active
                </div>
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
                    <i class="fas fa-clock text-warning"></i>
                    <?php echo $stats['pending_appointments']; ?> pending
                    <?php else: ?>
                    <i class="fas fa-check-circle text-success"></i>
                    All processed
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('opd-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fas fa-stethoscope"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_opd']); ?></div>
                <div class="stat-label">OPD Visits</div>
                <div class="stat-sub">
                    <i class="fas fa-calendar-day"></i>
                    <?php echo $stats['opd_today']; ?> today
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Main Stat Cards - Row 2 -->
    <div class="row g-3 mb-3">
        <?php if (hasPermission('ipd-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-red"><i class="fas fa-hospital-user"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_ipd']); ?></div>
                <div class="stat-label">IPD Admissions</div>
                <div class="stat-sub">
                    <i class="fas fa-bed"></i>
                    <?php echo $stats['ipd_active']; ?> active
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('prescription-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-pink"><i class="fas fa-prescription"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_prescriptions']); ?></div>
                <div class="stat-label">Prescriptions</div>
                <div class="stat-sub">
                    <i class="fas fa-pills"></i>
                    Total issued
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('billing-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-indigo"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_bills']); ?></div>
                <div class="stat-label">Bills</div>
                <div class="stat-sub">
                    <i class="fas fa-rupee-sign"></i>
                    ₹<?php echo number_format($stats['revenue_today']); ?> today
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('staff-view')): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon icon-cyan"><i class="fas fa-user-tie"></i></div>
                <div class="stat-number"><?php echo number_format($stats['total_staff']); ?></div>
                <div class="stat-label">Staff Members</div>
                <div class="stat-sub">
                    <i class="fas fa-users"></i>
                    Total
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
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
    
    <!-- Today's Appointments -->
    <?php if (hasPermission('appointment-view') && !empty($today_appointments_list)): ?>
    <div class="row">
        <div class="col-12">
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
                            <span class="mx-2">·</span>
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
    </div>
    <?php endif; ?>
    
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthNames = <?php echo json_encode($month_names); ?>;
    
    // Patient Chart
    <?php if (hasPermission('patient-view')): ?>
    const patientCtx = document.getElementById('patientChart').getContext('2d');
    new Chart(patientCtx, {
        type: 'bar',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'New Patients',
                data: <?php echo json_encode($monthly_patients); ?>,
                backgroundColor: 'rgba(26, 115, 232, 0.7)',
                borderColor: 'rgba(26, 115, 232, 1)',
                borderWidth: 2,
                borderRadius: 6,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    // Appointment Chart
    <?php if (hasPermission('appointment-view')): ?>
    const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
    new Chart(appointmentCtx, {
        type: 'line',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'Appointments',
                data: <?php echo json_encode($monthly_appointments); ?>,
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                borderColor: 'rgba(124, 58, 237, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(124, 58, 237, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

</body>
</html>