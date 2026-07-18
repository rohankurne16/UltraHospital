<?php
// ============================================================
// DASHBOARD - ADMIN
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config files
include "config/hospital.php";
include "config/superadmin.php";
include "config/constants.php";

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

// Check if user has dashboard permission
if (!hasPermission('dashboard-view')) {
    header('Location: update_adminprofile.php');
    exit();
}

// Get user info
$user_name = $_SESSION['name'] ?? 'User';
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$user_role = $_SESSION['role'] ?? 'Guest';

// Initialize all variables with default values
$patient_count = ['total_patients' => 0];
$doctor_count = ['total_doctors' => 0];
$appt_count = ['total_appointments' => 0];
$department_count = ['total_depts' => 0];
$staff_count = ['total_staff' => 0];
$total_appointments_today = 0;
$total_patients_today = 0;
$upcoming_appointments = [];
$recent_patients = [];
$recent_activities = [];

// ============================================================
// DASHBOARD COUNTS - ONLY IF USER HAS PERMISSION
// ============================================================

// Patients Count
if (hasPermission('patient-view')) {
    $patcount = "SELECT COUNT(*) as total_patients FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id";
    $patientcount = $conn->query($patcount);
    if ($patientcount && $patientcount->num_rows > 0) {
        $patient_count = $patientcount->fetch_assoc();
    }
    
    // Today's Patients
    $today = date('Y-m-d');
    $today_patients = "SELECT COUNT(*) as total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id AND DATE(created_at) = '$today'";
    $today_result = $conn->query($today_patients);
    if ($today_result && $today_result->num_rows > 0) {
        $total_patients_today = $today_result->fetch_assoc()['total'] ?? 0;
    }
    
    // Recent Patients
    $recent_patients_query = "SELECT patient_id, name, mobile, created_at 
                              FROM patients 
                              WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id 
                              ORDER BY created_at DESC LIMIT 5";
    $recent_patients_result = $conn->query($recent_patients_query);
    if ($recent_patients_result) {
        while ($row = $recent_patients_result->fetch_assoc()) {
            $recent_patients[] = $row;
        }
    }
}

// Doctors Count
if (hasPermission('doctor-view')) {
    $dotcount = "SELECT COUNT(*) as total_doctors FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id";
    $doctorcount = $conn->query($dotcount);
    if ($doctorcount && $doctorcount->num_rows > 0) {
        $doctor_count = $doctorcount->fetch_assoc();
    }
}

// Appointments Count
if (hasPermission('appointment-view')) {
    $appcount = "SELECT COUNT(*) as total_appointments FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id";
    $apptcount = $conn->query($appcount);
    if ($apptcount && $apptcount->num_rows > 0) {
        $appt_count = $apptcount->fetch_assoc();
    }
    
    // Today's Appointments
    $today = date('Y-m-d');
    $today_app = "SELECT COUNT(*) as total FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id AND DATE(appointment_date) = '$today'";
    $today_app_result = $conn->query($today_app);
    if ($today_app_result && $today_app_result->num_rows > 0) {
        $total_appointments_today = $today_app_result->fetch_assoc()['total'] ?? 0;
    }
    
    // Upcoming Appointments (Next 7 days)
    $upcoming_query = "SELECT appointment_id, patient_name, doctor_name, appointment_date, status, department
                       FROM appointments 
                       WHERE (delete_flag=0 OR delete_flag IS NULL) 
                       AND hospital_id = $hospital_id 
                       AND appointment_date >= CURDATE() 
                       AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                       AND status != 'Cancelled'
                       ORDER BY appointment_date ASC 
                       LIMIT 5";
    $upcoming_result = $conn->query($upcoming_query);
    if ($upcoming_result) {
        while ($row = $upcoming_result->fetch_assoc()) {
            $upcoming_appointments[] = $row;
        }
    }
}

// Departments Count
if (hasPermission('department-view')) {
    $deptcount = "SELECT COUNT(*) as total_depts FROM department WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id";
    $dept_count = $conn->query($deptcount);
    if ($dept_count && $dept_count->num_rows > 0) {
        $department_count = $dept_count->fetch_assoc();
    }
}

// Staff Count
if (hasPermission('staff-view')) {
    $staffcount = "SELECT COUNT(*) as total_staff FROM staff WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id = $hospital_id";
    $staff_count_result = $conn->query($staffcount);
    if ($staff_count_result && $staff_count_result->num_rows > 0) {
        $staff_count = $staff_count_result->fetch_assoc();
    }
}

// ============================================================
// RECENT ACTIVITIES - ONLY IF USER HAS AUDIT LOG PERMISSION
// ============================================================
if (hasPermission('auditlog-view')) {
    $recent_activities_query = "SELECT module, action, created_at, register_id
                                FROM audit_logs 
                                WHERE hospital_id = '$hospital_id' OR hospital_id IS NULL
                                ORDER BY created_at DESC 
                                LIMIT 5";
    $recent_activities_result = $conn->query($recent_activities_query);
    if ($recent_activities_result) {
        while ($row = $recent_activities_result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
}

// ============================================================
// CHART DATA - ONLY IF USER HAS APPOINTMENT PERMISSION
// ============================================================
$months = [];
$monthlyAppointments = [];
$status = [];
$total = [];
$departments = [];
$totalDeptAppointments = [];

if (hasPermission('appointment-view')) {
    // Month-wise Chart
    $chartQuery = "SELECT DATE_FORMAT(appointment_date, '%b') as month,
                    COUNT(*) as total
                    FROM appointments
                    WHERE (delete_flag=0 OR delete_flag IS NULL)
                    AND hospital_id = $hospital_id
                    GROUP BY MONTH(appointment_date)
                    ORDER BY MONTH(appointment_date)";
    $chartResult = mysqli_query($conn, $chartQuery);
    if ($chartResult) {
        while ($row = mysqli_fetch_assoc($chartResult)) {
            $months[] = $row['month'];
            $monthlyAppointments[] = $row['total'];
        }
    }

    // Status data
    $query = "SELECT status, COUNT(*) as total
              FROM appointments
              WHERE (delete_flag=0 OR delete_flag IS NULL)
              AND hospital_id = $hospital_id
              GROUP BY status";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status[] = $row['status'];
            $total[] = $row['total'];
        }
    }

    // Department wise
    $dept_chart = "SELECT department, COUNT(*) as total
                   FROM appointments
                   WHERE (delete_flag=0 OR delete_flag IS NULL)
                   AND hospital_id = $hospital_id
                   GROUP BY department
                   ORDER BY total DESC
                   LIMIT 10";
    $chartResult = mysqli_query($conn, $dept_chart);
    if ($chartResult) {
        while ($row = mysqli_fetch_assoc($chartResult)) {
            $departments[] = $row['department'];
            $totalDeptAppointments[] = $row['total'];
        }
    }
}

// Log dashboard access
if (function_exists('logAudit')) {
    logAudit('Dashboard', 'User accessed dashboard');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Dashboard</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }

        #sidebar-container {
            position: fixed; top: 0; left: 0; height: 100vh; width: 256px;
            z-index: 1000; background: #ffffff; border-right: 1px solid #e2e8f0;
            overflow-y: auto; transition: transform 0.3s ease;
        }

        @media (max-width: 1279px) {
            #sidebar-container { transform: translateX(-100%); width: 280px; box-shadow: 4px 0 20px rgba(0,0,0,0.1); }
            #sidebar-container.active { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
            .sidebar-overlay.active { display: block; }
            #main-content { margin-left: 0 !important; }
        }
        @media (min-width: 1280px) {
            #sidebar-container { transform: translateX(0); width: 256px; }
        }

        .top-header {
            background: #ffffff; border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 100;
            min-height: 64px;
        }

        .main-content {
            margin-left: 256px; padding: 1.5rem; min-height: calc(100vh - 64px);
            transition: margin-left 0.3s ease; background: #f1f5f9;
        }
        @media (max-width: 1279px) {
            .main-content { margin-left: 0 !important; padding: 1rem; }
        }

        .dashboard-card {
            background: #ffffff; border-radius: 12px; padding: 1.25rem;
            border: 1px solid #e2e8f0; transition: all 0.3s ease;
            text-decoration: none; display: block; color: inherit;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .dashboard-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); border-color: #3b82f6; }
        .dashboard-card .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; margin-bottom: 0.75rem; }
        .dashboard-card .count { font-size: 1.75rem; font-weight: 700; color: #1e293b; }
        .dashboard-card .label { font-size: 0.85rem; color: #94a3b8; font-weight: 500; }
        .dashboard-card .sub { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }
        .dashboard-card .sub i { color: #22c55e; }
        
        .bg-blue { background: #eff6ff; color: #3b82f6; }
        .bg-green { background: #ecfdf5; color: #059669; }
        .bg-amber { background: #fef3c7; color: #b45309; }
        .bg-purple { background: #f5f3ff; color: #7c3aed; }
        .bg-cyan { background: #ecfeff; color: #0891b2; }
        .bg-rose { background: #fef2f2; color: #dc2626; }

        .grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; }

        .text-muted-foreground { color: #64748b; }
        .bg-white { background: #ffffff; }
        .rounded-lg { border-radius: 12px; }
        .border { border: 1px solid #e2e8f0; }
        .shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mt-4 { margin-top: 1rem; }

        .mobile-toggle { display: none; padding: 0.5rem 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-size: 1.25rem; }
        .mobile-toggle:hover { background: #f8fafc; }
        @media (max-width: 1279px) { .mobile-toggle { display: inline-flex; align-items: center; justify-content: center; } }

        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.8rem; }

        /* Widget Cards */
        .widget-card {
            background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .widget-card .widget-title { font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .widget-card .widget-title i { color: #3b82f6; }

        .activity-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; flex-shrink: 0; }
        .activity-icon.blue { background: #eff6ff; color: #3b82f6; }
        .activity-content { flex: 1; }
        .activity-content .activity-text { font-size: 0.85rem; color: #1e293b; }
        .activity-content .activity-time { font-size: 0.7rem; color: #94a3b8; }

        .appointment-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
        .appointment-item:last-child { border-bottom: none; }
        .appointment-status { padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.65rem; font-weight: 600; }
        .appointment-status.scheduled { background: #eff6ff; color: #3b82f6; }
        .appointment-status.completed { background: #ecfdf5; color: #22c55e; }
        .appointment-status.cancelled { background: #fef2f2; color: #dc2626; }
        .appointment-status.pending { background: #fef3c7; color: #b45309; }

        .chart-container { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .chart-container h2 { font-size: 1.1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; }
        .chart-container h2 i { color: #3b82f6; margin-right: 0.5rem; }

        .no-permission-msg {
            background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px;
            padding: 2rem; text-align: center; margin: 2rem auto; max-width: 500px;
        }
        .no-permission-msg i { font-size: 3rem; color: #f59e0b; display: block; margin-bottom: 1rem; }
        .no-permission-msg h3 { color: #1e293b; margin-bottom: 0.5rem; }
        .no-permission-msg p { color: #64748b; }
        .no-permission-msg a { display: inline-block; margin-top: 1rem; padding: 0.5rem 1.5rem; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; }
        .no-permission-msg a:hover { background: #2563eb; }

        @media (max-width: 1024px) {
            .grid-2col { grid-template-columns: 1fr; }
            .grid-3col { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .grid-cards { grid-template-columns: repeat(2, 1fr); }
            .grid-3col { grid-template-columns: 1fr; }
            .grid-2col { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .grid-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div id="sidebar-container">
    <?php include 'Sidebar.php'; ?>
</div>

<!-- Main Content -->
<div style="display:flex; min-height:100vh;">
    <main class="main-content" id="mainContent">
        
        <!-- Header -->
        <?php include 'header.php'; ?>

        <!-- Dashboard Content -->
        <div style="padding-top:1.5rem;">
            
            <!-- Dashboard Cards -->
            <div class="grid-cards">
                
                <!-- Appointments Card -->
                <?php if (hasPermission('appointment-view')): ?>
                <a href="appointments.php" class="dashboard-card">
                    <div class="icon bg-blue"><i class="fas fa-calendar-check"></i></div>
                    <div class="count"><?php echo $appt_count['total_appointments'] ?? 0; ?></div>
                    <div class="label">Total Appointments</div>
                    <div class="sub"><i class="fas fa-arrow-up"></i> <?php echo $total_appointments_today; ?> Today</div>
                </a>
                <?php endif; ?>

                <!-- Patients Card -->
                <?php if (hasPermission('patient-view')): ?>
                <a href="patients.php" class="dashboard-card">
                    <div class="icon bg-amber"><i class="fas fa-user"></i></div>
                    <div class="count"><?php echo $patient_count['total_patients'] ?? 0; ?></div>
                    <div class="label">Total Patients</div>
                    <div class="sub"><i class="fas fa-arrow-up"></i> <?php echo $total_patients_today; ?> Today</div>
                </a>
                <?php endif; ?>

                <!-- Doctors Card -->
                <?php if (hasPermission('doctor-view')): ?>
                <a href="doctors.php" class="dashboard-card">
                    <div class="icon bg-purple"><i class="fas fa-user-md"></i></div>
                    <div class="count"><?php echo $doctor_count['total_doctors'] ?? 0; ?></div>
                    <div class="label">Total Doctors</div>
                    <div class="sub"><i class="fas fa-circle" style="color:#22c55e; font-size:8px;"></i> Active</div>
                </a>
                <?php endif; ?>

                <!-- Staff Card -->
                <?php if (hasPermission('staff-view')): ?>
                <a href="staff.php" class="dashboard-card">
                    <div class="icon bg-cyan"><i class="fas fa-users"></i></div>
                    <div class="count"><?php echo $staff_count['total_staff'] ?? 0; ?></div>
                    <div class="label">Total Staff</div>
                    <div class="sub"><i class="fas fa-circle" style="color:#22c55e; font-size:8px;"></i> Active</div>
                </a>
                <?php endif; ?>

                <!-- Departments Card -->
                <?php if (hasPermission('department-view')): ?>
                <a href="departments.php" class="dashboard-card">
                    <div class="icon bg-green"><i class="fas fa-building"></i></div>
                    <div class="count"><?php echo $department_count['total_depts'] ?? 0; ?></div>
                    <div class="label">Total Departments</div>
                    <div class="sub"><i class="fas fa-circle" style="color:#22c55e; font-size:8px;"></i> Active</div>
                </a>
                <?php endif; ?>

            </div>

            <!-- Quick Stats Row -->
            <?php if (hasPermission('patient-view') || hasPermission('appointment-view')): ?>
            <div class="grid-3col" style="margin-top:1.5rem;">
                <?php if (hasPermission('patient-view')): ?>
                <div class="widget-card">
                    <div class="widget-title"><i class="fas fa-user-plus"></i> Today's Patients</div>
                    <div style="font-size:2rem; font-weight:700; color:#1e293b;"><?php echo $total_patients_today; ?></div>
                    <div style="font-size:0.8rem; color:#94a3b8; margin-top:0.25rem;">
                        <i class="fas fa-arrow-up" style="color:#22c55e;"></i> New registrations today
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasPermission('appointment-view')): ?>
                <div class="widget-card">
                    <div class="widget-title"><i class="fas fa-calendar-day"></i> Today's Appointments</div>
                    <div style="font-size:2rem; font-weight:700; color:#1e293b;"><?php echo $total_appointments_today; ?></div>
                    <div style="font-size:0.8rem; color:#94a3b8; margin-top:0.25rem;">
                        <i class="fas fa-clock" style="color:#3b82f6;"></i> Scheduled for today
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasPermission('appointment-view')): ?>
                <div class="widget-card">
                    <div class="widget-title"><i class="fas fa-calendar-week"></i> Upcoming Appointments</div>
                    <div style="font-size:2rem; font-weight:700; color:#1e293b;"><?php echo count($upcoming_appointments); ?></div>
                    <div style="font-size:0.8rem; color:#94a3b8; margin-top:0.25rem;">
                        <i class="fas fa-arrow-right" style="color:#f59e0b;"></i> Next 7 days
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Charts -->
            <?php if (hasPermission('appointment-view') && !empty($months)): ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-top:1.5rem;">
                <div class="chart-container">
                    <h2><i class="fas fa-chart-bar"></i>Month-wise Appointments</h2>
                    <div style="height:280px;">
                        <canvas id="doctorChart"></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <h2><i class="fas fa-chart-pie"></i>Appointment Status</h2>
                    <div style="height:280px;">
                        <canvas id="appointmentChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="chart-container" style="margin-top:1.25rem;">
                <h2><i class="fas fa-chart-doughnut"></i>Department-wise Appointments</h2>
                <div style="height:280px; max-width:500px; margin:0 auto;">
                    <canvas id="ringChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            RECENT ACTIVITIES & UPCOMING APPOINTMENTS - PERMISSION BASED
            ============================================================ -->
            <?php if (hasPermission('auditlog-view') || hasPermission('appointment-view')): ?>
            <div class="grid-2col" style="margin-top:1.5rem;">
                
                <!-- Recent Activities - ONLY IF USER HAS auditlog-view PERMISSION -->
                <?php if (hasPermission('auditlog-view')): ?>
                <div class="widget-card">
                    <div class="widget-title"><i class="fas fa-bolt"></i> Recent Activities</div>
                    <?php if (!empty($recent_activities)): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon blue"><i class="fas fa-circle"></i></div>
                                <div class="activity-content">
                                    <div class="activity-text"><?php echo htmlspecialchars($activity['module'] . ' - ' . $activity['action']); ?></div>
                                    <div class="activity-time"><?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:1.5rem; color:#94a3b8;">No recent activities</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Upcoming Appointments - ONLY IF USER HAS appointment-view PERMISSION -->
                <?php if (hasPermission('appointment-view')): ?>
                <div class="widget-card">
                    <div class="widget-title"><i class="fas fa-calendar-alt"></i> Upcoming Appointments</div>
                    <?php if (!empty($upcoming_appointments)): ?>
                        <?php foreach ($upcoming_appointments as $appt): ?>
                            <div class="appointment-item">
                                <div style="flex:1;">
                                    <div style="font-size:0.85rem; font-weight:600; color:#1e293b;">
                                        <?php echo htmlspecialchars($appt['patient_name']); ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:#94a3b8;">
                                        <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($appt['doctor_name'] ?? 'N/A'); ?>
                                        <span style="margin:0 4px;">•</span>
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($appt['department'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:0.75rem; font-weight:600; color:#1e293b;">
                                        <?php echo date('d M', strtotime($appt['appointment_date'])); ?>
                                    </div>
                                    <span class="appointment-status <?php echo strtolower($appt['status'] ?? 'pending'); ?>">
                                        <?php echo htmlspecialchars($appt['status'] ?? 'Pending'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:1.5rem; color:#94a3b8;">No upcoming appointments</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <!-- Recent Patients - ONLY IF USER HAS patient-view PERMISSION -->
            <?php if (hasPermission('patient-view') && !empty($recent_patients)): ?>
            <div class="widget-card" style="margin-top:1.5rem;">
                <div class="widget-title"><i class="fas fa-user-clock"></i> Recent Patients</div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <th style="padding:0.5rem; text-align:left; font-size:0.7rem; font-weight:600; color:#64748b; text-transform:uppercase;">Name</th>
                                <th style="padding:0.5rem; text-align:left; font-size:0.7rem; font-weight:600; color:#64748b; text-transform:uppercase;">Mobile</th>
                                <th style="padding:0.5rem; text-align:left; font-size:0.7rem; font-weight:600; color:#64748b; text-transform:uppercase;">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_patients as $patient): ?>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:0.5rem; font-size:0.85rem; color:#1e293b;"><?php echo htmlspecialchars($patient['name']); ?></td>
                                    <td style="padding:0.5rem; font-size:0.85rem; color:#475569;"><?php echo htmlspecialchars($patient['mobile'] ?? 'N/A'); ?></td>
                                    <td style="padding:0.5rem; font-size:0.8rem; color:#94a3b8;"><?php echo date('d M Y', strtotime($patient['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- No Permission Message -->
            <?php if (!hasPermission('appointment-view') && !hasPermission('patient-view') && !hasPermission('department-view') && !hasPermission('doctor-view') && !hasPermission('staff-view')): ?>
            <div class="no-permission-msg">
                <i class="fas fa-lock"></i>
                <h3>No Permissions Assigned</h3>
                <p>You don't have any module permissions. Please contact your administrator.</p>
                <a href="update_adminprofile.php">
                    <i class="fas fa-user-edit"></i> Update Profile
                </a>
            </div>
            <?php endif; ?>

        </div>

    </main>
</div>

<script>
// Sidebar Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebarContainer = document.getElementById('sidebar-container');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebarContainer.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebarContainer.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
});

// Charts
<?php if (hasPermission('appointment-view') && !empty($months)): ?>
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 500 }
};

// Month-wise Chart
const ctx = document.getElementById('doctorChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Appointments',
                data: <?php echo json_encode($monthlyAppointments); ?>,
                backgroundColor: ['#3b82f6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...commonOptions,
            plugins: { legend: { display: false } },
            scales: { 
                y: { 
                    beginAtZero: true,
                    grid: { display: true, color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

// Appointment Status Chart
const cts = document.getElementById("appointmentChart");
if (cts) {
    new Chart(cts, {
        type: "pie",
        data: {
            labels: <?php echo json_encode($status); ?>,
            datasets: [{
                data: <?php echo json_encode($total); ?>,
                backgroundColor: ["#3b82f6", "#22c55e", "#f59e0b", "#ef4444", "#8b5cf6", "#06b6d4"],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            ...commonOptions,
            plugins: { 
                legend: { 
                    position: "bottom",
                    labels: { padding: 15, usePointStyle: true }
                }
            },
            cutout: '55%'
        }
    });
}

// Department-wise Chart
const cty = document.getElementById('ringChart');
if (cty) {
    new Chart(cty, {
        type: "doughnut",
        data: {
            labels: <?php echo json_encode($departments); ?>,
            datasets: [{
                backgroundColor: ["#3b82f6", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6", "#06B6D4", "#14b8a6", "#e11d48"],
                data: <?php echo json_encode($totalDeptAppointments); ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            ...commonOptions,
            plugins: { 
                legend: { 
                    position: "bottom",
                    labels: { padding: 15, usePointStyle: true }
                }
            },
            cutout: '55%'
        }
    });
}
<?php endif; ?>
</script>

</body>
</html>