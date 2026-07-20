<?php
// ============================================================
// DOCTOR DASHBOARD - WITH PERMISSION CHECKS
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo '<div style="background:#fef3c7;padding:15px;margin:10px;border:1px solid #f59e0b;border-radius:8px;font-family:Arial;">';
echo '<strong>🔍 DEBUG - Session Data:</strong><br>';
echo 'User ID: ' . ($_SESSION['id'] ?? 'Not set') . '<br>';
echo 'User Role: ' . ($_SESSION['role'] ?? 'Not set') . '<br>';
echo 'User Role ID: ' . ($_SESSION['role_id'] ?? 'Not set') . '<br>';
echo 'Permissions Count: ' . (isset($_SESSION['permissions']) ? count($_SESSION['permissions']) : 0) . '<br>';
if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
    echo 'Permissions: <pre style="background:#fff;padding:10px;border:1px solid #ccc;max-height:200px;overflow:auto;font-size:12px;">' . print_r($_SESSION['permissions'], true) . '</pre>';
} else {
    echo '⚠️ No permissions found in session!<br>';
}
echo '</div>';

// Include config files with correct paths
require_once '../config/hospital.php';
require_once '../config/superadmin.php';
require_once '../config/constants.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}

// Get user role
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

// ============================================================
// PERMISSION CHECKS
// ============================================================

// Check if user has dashboard-view permission
if (!function_exists('hasPermission')) {
    function hasPermission($permission_name) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Super Admin has all permissions
        if (isset($_SESSION['role']) && (strtolower(trim($_SESSION['role'])) === 'super admin' || strtolower(trim($_SESSION['role'])) === 'superadmin')) {
            return true;
        }
        
        if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
            return false;
        }
        
        return in_array($permission_name, $_SESSION['permissions']);
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission($permissions) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Super Admin has all permissions
        if (isset($_SESSION['role']) && (strtolower(trim($_SESSION['role'])) === 'super admin' || strtolower(trim($_SESSION['role'])) === 'superadmin')) {
            return true;
        }
        
        if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
            return false;
        }
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $_SESSION['permissions'])) {
                return true;
            }
        }
        return false;
    }
}

// Check if user is doctor
if ($user_role != 'doctor') {
    header('Location: ../dashboard.php');
    exit();
}

// ============================================================
// GET DOCTOR INFORMATION
// ============================================================
$doctor_register_id = $_SESSION['id'];
$doctor_name = "Doctor";
$doctor_id = 0;
$totalAppointments = 0;
$todayAppointments = 0;
$opdPatientsToday = 0;
$pendingPrescriptions = 0;
$followupPatients = 0;
$totalPatients = 0;
$todayVisits = 0;

// Get doctor info
$get_all_doctor_info = "SELECT * FROM doctor WHERE register_id='$doctor_register_id' AND (delete_flag=0 OR delete_flag IS NULL)";
$all_doctor_info = $conn->query($get_all_doctor_info);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_name = $doctor["doctor_name"] ?? "Doctor";
    $doctor_id = $doctor["doctor_id"] ?? 0;
    $_SESSION["doctor_id"] = $doctor_id;

    // ============================================================
    // STATISTICS - ONLY IF USER HAS PERMISSION
    // ============================================================

    // Total Appointments (requires appointment-view)
    if (function_exists('hasPermission') && hasPermission('appointment-view')) {
        $query_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id='$doctor_id' AND status NOT IN('Cancelled') AND (delete_flag=0 OR delete_flag IS NULL)");
        if ($query_total) {
            $totalAppointments = mysqli_fetch_assoc($query_total)['total'] ?? 0;
        }
    }

    // Today's Appointments (requires appointment-view)
    if (function_exists('hasPermission') && hasPermission('appointment-view')) {
        $query_today = mysqli_query($conn, "SELECT COUNT(*) AS today FROM appointments WHERE doctor_id='$doctor_id' AND status NOT IN('Cancelled') AND (delete_flag=0 OR delete_flag IS NULL) AND appointment_date = CURDATE()");
        if ($query_today) {
            $todayAppointments = mysqli_fetch_assoc($query_today)['today'] ?? 0;
        }
    }

    // OPD Patients Today (requires opd-view)
    if (function_exists('hasPermission') && hasPermission('opd-view')) {
        $query_opd = mysqli_query($conn, "SELECT COUNT(*) AS opd FROM opd WHERE doctor_id='$doctor_id' AND visit_date = CURDATE() AND (delete_flag=0 OR delete_flag IS NULL)");
        if ($query_opd) {
            $opdPatientsToday = mysqli_fetch_assoc($query_opd)['opd'] ?? 0;
        }
    }

    // Pending Prescriptions (requires prescription-view)
    if (function_exists('hasPermission') && hasPermission('prescription-view')) {
        $query_pending = mysqli_query($conn, "SELECT COUNT(*) AS pending FROM prescriptions WHERE doctor_id='$doctor_id' AND (delete_flag=0 OR delete_flag IS NULL)");
        if ($query_pending) {
            $pendingPrescriptions = mysqli_fetch_assoc($query_pending)['pending'] ?? 0;
        }
    }

    // Total Patients (requires patient-view)
    if (function_exists('hasPermission') && hasPermission('patient-view')) {
        $query_patients = mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL)");
        if ($query_patients) {
            $totalPatients = mysqli_fetch_assoc($query_patients)['total'] ?? 0;
        }
    }

    // Follow-up Patients (requires prescription-view)
    if (function_exists('hasPermission') && hasPermission('prescription-view')) {
        $query_followup = mysqli_query($conn, "SELECT COUNT(*) AS followup_date FROM prescriptions WHERE doctor_id='$doctor_id' AND followup_date = CURDATE() + INTERVAL 1 DAY AND (delete_flag=0 OR delete_flag IS NULL)");
        if ($query_followup) {
            $followupPatients = mysqli_fetch_assoc($query_followup)['followup_date'] ?? 0;
        }
    }
}

// Get hospital info
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$user_name = $_SESSION['name'] ?? 'User';

// Log dashboard access
if (function_exists('logAudit')) {
    logAudit('Doctor Dashboard', 'Doctor accessed dashboard');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - <?php echo htmlspecialchars($hospital_name); ?></title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital_logo); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }

        /* ============================================================
           SIDEBAR CONTAINER
           ============================================================ */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 256px;
            z-index: 1000;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        #sidebar-container::-webkit-scrollbar { width: 4px; }
        #sidebar-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            }
            #sidebar-container.active { transform: translateX(0); }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            .sidebar-overlay.active { display: block; }
            #main-content { margin-left: 0 !important; }
        }

        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 256px;
            }
        }

        /* ============================================================
           HEADER
           ============================================================ */
        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            min-height: 64px;
        }

        /* ============================================================
           MAIN CONTENT
           ============================================================ */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 256px;
            padding: 1.5rem;
            min-height: calc(100vh - 64px);
            transition: margin-left 0.3s ease;
            background: #f8fafc;
            width: calc(100% - 256px);
        }

        @media (max-width: 1279px) {
            .main-content {
                margin-left: 0 !important;
                padding: 1rem;
                width: 100%;
            }
        }

        /* ============================================================
           STAT CARDS
           ============================================================ */
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .stat-card .icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 0.75rem; }
        .stat-card .count { font-size: 1.75rem; font-weight: 700; color: #1e293b; }
        .stat-card .label { font-size: 0.8rem; color: #94a3b8; font-weight: 500; }

        .bg-blue { background: #eff6ff; color: #3b82f6; }
        .bg-purple { background: #f5f3ff; color: #7c3aed; }
        .bg-orange { background: #fff7ed; color: #f97316; }
        .bg-red { background: #fef2f2; color: #dc2626; }
        .bg-green { background: #ecfdf5; color: #059669; }
        .bg-cyan { background: #ecfeff; color: #0891b2; }

        .grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }

        .widget-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .widget-card .widget-title { font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }

        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-top: 1.5rem; }
        .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; }

        .mobile-toggle { display: none; padding: 0.5rem 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-size: 1.25rem; }
        .mobile-toggle:hover { background: #f8fafc; }
        @media (max-width: 1279px) { .mobile-toggle { display: inline-flex; align-items: center; justify-content: center; } }

        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.8rem; }

        .activity-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
        .activity-item:last-child { border-bottom: none; }

        .appointment-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
        .appointment-item:last-child { border-bottom: none; }

        .status-badge { padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.65rem; font-weight: 600; }
        .status-scheduled { background: #eff6ff; color: #3b82f6; }
        .status-completed { background: #ecfdf5; color: #22c55e; }
        .status-cancelled { background: #fef2f2; color: #dc2626; }
        .status-pending { background: #fef3c7; color: #b45309; }

        .no-permission-msg {
            background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px;
            padding: 2rem; text-align: center; margin: 2rem auto; max-width: 500px;
        }
        .no-permission-msg i { font-size: 3rem; color: #f59e0b; display: block; margin-bottom: 1rem; }
        .no-permission-msg h3 { color: #1e293b; margin-bottom: 0.5rem; }
        .no-permission-msg p { color: #64748b; }

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

        /* ============================================================
           SIDEBAR OVERLAY
           ============================================================ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>

<!-- ============================================================
SIDEBAR OVERLAY
============================================================ -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============================================================
SIDEBAR
============================================================ -->
<div id="sidebar-container">
    <?php include '../Sidebar.php'; ?>
</div>

<!-- ============================================================
MAIN WRAPPER
============================================================ -->
<div class="main-wrapper">
    <main class="main-content" id="mainContent">
        
        <!-- ============================================================
        HEADER
        ============================================================ -->
        <div class="top-header">
            <div style="display:flex; align-items:center; gap:1rem;">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1 style="font-size:1.25rem; font-weight:700; color:#1e293b;">Doctor Dashboard</h1>
                    <p style="font-size:0.875rem; color:#64748b;">
                        Welcome back, <strong><?php echo htmlspecialchars($doctor_name); ?></strong>
                        <?php if (function_exists('hasPermission') && hasPermission('appointment-view')): ?>
                            | <span style="color:#3b82f6;"><?php echo $todayAppointments; ?> appointments today</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:1rem;">
                <span style="font-size:0.875rem; color:#64748b;">
                    <i class="fas fa-calendar"></i> <?php echo date('l, d M Y'); ?>
                </span>
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 2)); ?></div>
            </div>
        </div>

        <!-- ============================================================
        DASHBOARD CONTENT
        ============================================================ -->
        <div style="padding-top:1.5rem;">
            
            <!-- Statistics Cards -->
            <div class="grid-cards">
                
                <?php if (function_exists('hasPermission') && hasPermission('appointment-view')): ?>
                <div class="stat-card">
                    <div class="icon bg-blue"><i class="fas fa-calendar-day"></i></div>
                    <div class="count"><?php echo $todayAppointments; ?></div>
                    <div class="label">Today's Appointments</div>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasPermission') && hasPermission('appointment-view')): ?>
                <div class="stat-card">
                    <div class="icon bg-purple"><i class="fas fa-calendar-check"></i></div>
                    <div class="count"><?php echo $totalAppointments; ?></div>
                    <div class="label">Total Appointments</div>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasPermission') && hasPermission('opd-view')): ?>
                <div class="stat-card">
                    <div class="icon bg-orange"><i class="fas fa-stethoscope"></i></div>
                    <div class="count"><?php echo $opdPatientsToday; ?></div>
                    <div class="label">OPD Patients Today</div>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasPermission') && hasPermission('prescription-view')): ?>
                <div class="stat-card">
                    <div class="icon bg-red"><i class="fas fa-prescription"></i></div>
                    <div class="count"><?php echo $pendingPrescriptions; ?></div>
                    <div class="label">Total Prescriptions</div>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasPermission') && hasPermission('prescription-view')): ?>
                <div class="stat-card">
                    <div class="icon bg-green"><i class="fas fa-user-check"></i></div>
                    <div class="count"><?php echo $followupPatients; ?></div>
                    <div class="label">Follow-up Patients</div>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasPermission') && hasPermission('patient-view')): ?>
                <div class="stat-card">
                    <div class="icon bg-cyan"><i class="fas fa-users"></i></div>
                    <div class="count"><?php echo $totalPatients; ?></div>
                    <div class="label">Total Patients</div>
                </div>
                <?php endif; ?>

            </div>

            <!-- No Permission Message -->
            <?php if (!function_exists('hasPermission') || (!hasPermission('appointment-view') && !hasPermission('opd-view') && !hasPermission('prescription-view') && !hasPermission('patient-view'))): ?>
            <div class="no-permission-msg">
                <i class="fas fa-lock"></i>
                <h3>No Module Permissions</h3>
                <p>You don't have any module permissions. Please contact your administrator.</p>
                <a href="update_adminprofile.php" style="display:inline-block; margin-top:1rem; padding:0.5rem 1.5rem; background:#3b82f6; color:white; border-radius:8px; text-decoration:none;">
                    <i class="fas fa-user-edit"></i> Update Profile
                </a>
            </div>
            <?php endif; ?>

            <!-- Recent Activities -->
            <?php if (function_exists('hasPermission') && (hasPermission('appointment-view') || hasPermission('opd-view') || hasPermission('prescription-view'))): ?>
            <div class="grid-2col" style="margin-top:1.5rem;">
                
                <?php if (function_exists('hasPermission') && hasPermission('opd-view')): ?>
                <div class="widget-card">
                    <div class="widget-title">
                        <i class="fas fa-stethoscope" style="color:#3b82f6;"></i>
                        Recent OPD Visits
                    </div>
                    <?php 
                        $recentOpd = "SELECT o.*, p.patient_name, p.patient_image 
                                      FROM opd o 
                                      LEFT JOIN patients p ON o.patient_id = p.patient_id 
                                      WHERE o.doctor_id = '$doctor_id' 
                                      AND (o.delete_flag = 0 OR o.delete_flag IS NULL) 
                                      ORDER BY o.created_at DESC LIMIT 5";
                        $recentOpdResult = $conn->query($recentOpd);      
                    ?>
                    <?php if($recentOpdResult && $recentOpdResult->num_rows > 0): ?>
                        <?php while($row = $recentOpdResult->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div style="flex:1;">
                                    <div style="font-size:0.85rem; font-weight:600; color:#1e293b;">
                                        <?php echo htmlspecialchars($row['patient_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:#94a3b8;">
                                        <?php echo date('d M Y H:i', strtotime($row['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                                <a href="view_opd.php?id=<?php echo $row['id']; ?>" 
                                   style="font-size:0.75rem; color:#3b82f6; text-decoration:none;">
                                    View <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:1.5rem; color:#94a3b8;">No OPD visits</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasPermission') && hasPermission('prescription-view')): ?>
                <div class="widget-card">
                    <div class="widget-title">
                        <i class="fas fa-prescription" style="color:#dc2626;"></i>
                        Recent Prescriptions
                    </div>
                    <?php
                        $recentPrescription = "SELECT p.*, pt.patient_name, pt.patient_image 
                                               FROM prescriptions p 
                                               LEFT JOIN patients pt ON p.patient_id = pt.patient_id 
                                               WHERE p.doctor_id = '$doctor_id' 
                                               AND (p.delete_flag = 0 OR p.delete_flag IS NULL) 
                                               ORDER BY p.created_at DESC LIMIT 5";
                        $recentPrescriptionResult = $conn->query($recentPrescription);
                    ?>
                    <?php if($recentPrescriptionResult && $recentPrescriptionResult->num_rows > 0): ?>
                        <?php while($row = $recentPrescriptionResult->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div style="flex:1;">
                                    <div style="font-size:0.85rem; font-weight:600; color:#1e293b;">
                                        <?php echo htmlspecialchars($row['patient_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:#94a3b8;">
                                        <?php echo htmlspecialchars($row['medicine_name'] ?? 'N/A'); ?>
                                        <span style="margin:0 4px;">•</span>
                                        <?php echo date('d M Y', strtotime($row['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                                <a href="view_prescription.php?id=<?php echo $row['id']; ?>" 
                                   style="font-size:0.75rem; color:#3b82f6; text-decoration:none;">
                                    View <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:1.5rem; color:#94a3b8;">No prescriptions</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <!-- Today's Appointments Queue -->
            <?php if (function_exists('hasPermission') && hasPermission('appointment-view')): ?>
            <div class="widget-card" style="margin-top:1.5rem;">
                <div class="widget-title">
                    <i class="fas fa-calendar-alt" style="color:#3b82f6;"></i>
                    Today's Appointments Queue
                    <span style="margin-left:auto; font-size:0.75rem; background:#eff6ff; color:#3b82f6; padding:0.2rem 0.8rem; border-radius:20px;">
                        <?php echo $todayAppointments; ?> Appointments
                    </span>
                </div>
                <?php
                $todayAppointmentsList = "SELECT a.*, p.patient_name, p.patient_image
                                          FROM appointments a
                                          LEFT JOIN patients p ON a.patient_id = p.patient_id
                                          WHERE a.doctor_id = '$doctor_id'
                                          AND (a.delete_flag = 0 OR a.delete_flag IS NULL)
                                          AND a.appointment_date = CURDATE()
                                          ORDER BY a.appointment_time ASC LIMIT 10";
                $todayAppointmentsResult = mysqli_query($conn, $todayAppointmentsList);
                ?>
                <?php if($todayAppointmentsResult && $todayAppointmentsResult->num_rows > 0): ?>
                    <?php while($row = $todayAppointmentsResult->fetch_assoc()): ?>
                        <div class="appointment-item">
                            <div style="flex:1;">
                                <div style="font-size:0.85rem; font-weight:600; color:#1e293b;">
                                    <?php echo htmlspecialchars($row['patient_name'] ?? 'Unknown'); ?>
                                </div>
                                <div style="font-size:0.75rem; color:#94a3b8;">
                                    <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($row['appointment_time'] ?? 'now')); ?>
                                    <span style="margin:0 4px;">•</span>
                                    <?php echo htmlspecialchars($row['appointment_type'] ?? 'General'); ?>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'pending'); ?>">
                                <?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:1.5rem; color:#94a3b8;">
                        <i class="fas fa-calendar" style="font-size:2rem; display:block; margin-bottom:0.5rem; color:#e2e8f0;"></i>
                        No appointments for today.
                    </div>
                <?php endif; ?>
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
</script>

</body>
</html> 