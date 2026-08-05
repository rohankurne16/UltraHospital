<?php
// ============================================================
// SUPER ADMIN DASHBOARD – Fixed SQL error handling
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config with correct path
require_once '../config/permission.php';

// Check if user is Super Admin
checkSuperAdminLogin();

// Log dashboard access
logAudit('Dashboard', 'Super Admin accessed dashboard');

// Force light theme
$_SESSION['theme'] = 'light';
$theme = 'light';

// ============================================================
// SAFE COUNT HELPER – prevents fetch errors on failed queries
// ============================================================
function safeCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

// ============================================================
// HANDLE STATUS TOGGLE
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['hospital_id']) && isset($_GET['status'])) {
    $hospital_id = (int)$_GET['hospital_id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['status']);
    
    if ($new_status === 'Active' || $new_status === 'Inactive') {
        $update_query = "UPDATE hospital_master SET status = '$new_status' WHERE hospital_id = $hospital_id AND delete_flag = 0";
        if (mysqli_query($conn, $update_query)) {
            logAudit('Hospital', "Updated status of Hospital ID $hospital_id to $new_status");
            $_SESSION['success_message'] = "Hospital status updated to $new_status successfully!";
        } else {
            $_SESSION['error_message'] = "Update Error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Invalid status value!";
    }
    header("Location: dashboard.php");
    exit();
}

// ============================================================
// STATISTICS – using safeCount()
// ============================================================

$total_hospitals   = safeCount($conn, "SELECT COUNT(*) as total FROM hospital_master WHERE delete_flag = 0");
$active_hospitals  = safeCount($conn, "SELECT COUNT(*) as total FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'");
$inactive_hospitals= safeCount($conn, "SELECT COUNT(*) as total FROM hospital_master WHERE delete_flag = 0 AND status = 'Inactive'");

$total_doctors     = safeCount($conn, "SELECT COUNT(*) as total FROM doctor WHERE delete_flag = 0");
$total_departments = safeCount($conn, "SELECT COUNT(*) as total FROM department WHERE delete_flag = 0");
$total_staff       = safeCount($conn, "SELECT COUNT(*) as total FROM staff WHERE delete_flag = 0");
$total_patients    = safeCount($conn, "SELECT COUNT(*) as total FROM patients WHERE delete_flag = 0");
$total_users       = safeCount($conn, "SELECT COUNT(*) as total FROM register WHERE delete_flag = 0 AND role != 'SuperAdmin'");
$total_ipd         = safeCount($conn, "SELECT COUNT(*) as total FROM ipd_admissions WHERE delete_flag = 0");
$total_opd         = safeCount($conn, "SELECT COUNT(*) as total FROM opd WHERE delete_flag = 0");

$today = date('Y-m-d');
$today_logins = safeCount($conn, "SELECT COUNT(*) as total FROM login_logs WHERE DATE(login_time) = '$today'");

// ============================================================
// HOSPITAL OVERVIEW TABLE
// ============================================================
$hospital_overview_query = "SELECT 
                            h.hospital_id,
                            h.hospital_name,
                            h.hospital_code,
                            h.status,
                            h.created_at,
                            (SELECT COUNT(*) FROM hospital_admin WHERE hospital_id = h.hospital_id AND delete_flag = 0) as total_admins,
                            (SELECT COUNT(*) FROM doctor WHERE hospital_id = h.hospital_id AND delete_flag = 0) as total_doctors,
                            (SELECT COUNT(*) FROM department WHERE hospital_id = h.hospital_id AND delete_flag = 0) as total_departments,
                            (SELECT COUNT(*) FROM staff WHERE hospital_id = h.hospital_id AND delete_flag = 0) as total_staff,
                            (SELECT COUNT(*) FROM patients WHERE hospital_id = h.hospital_id AND delete_flag = 0) as total_patients
                            FROM hospital_master h
                            WHERE h.delete_flag = 0
                            ORDER BY h.created_at DESC
                            LIMIT 10";
$hospital_overview_result = mysqli_query($conn, $hospital_overview_query);
if (!$hospital_overview_result) {
    $hospital_overview_result = false; // fallback for later checks
}

// ============================================================
// RECENT HOSPITALS
// ============================================================
$recent_hospitals_query = "SELECT 
                          h.hospital_id,
                          h.hospital_name,
                          (SELECT full_name FROM hospital_admin WHERE hospital_id = h.hospital_id AND delete_flag = 0 LIMIT 1) as admin_name,
                          h.created_at,
                          h.status
                          FROM hospital_master h
                          WHERE h.delete_flag = 0
                          ORDER BY h.created_at DESC
                          LIMIT 5";
$recent_hospitals_result = mysqli_query($conn, $recent_hospitals_query);
if (!$recent_hospitals_result) {
    $recent_hospitals_result = false;
}

// ============================================================
// RECENT AUDIT LOGS
// ============================================================
$audit_logs_query = "SELECT 
    a.log_id,
    a.hospital_id,
    a.register_id,
    a.module,
    a.action,
    a.created_at,
    CASE 
        WHEN a.register_id = 999 THEN 'Super Admin'
        WHEN r.name IS NULL THEN CONCAT('User #', a.register_id)
        ELSE r.name 
    END as user_name,
    COALESCE(h.hospital_name, 'N/A') as hospital_name
FROM audit_logs a
LEFT JOIN register r ON a.register_id = r.id
LEFT JOIN hospital_master h ON a.hospital_id = h.hospital_id
ORDER BY a.created_at DESC
LIMIT 5";
$audit_logs_result = mysqli_query($conn, $audit_logs_query);
if (!$audit_logs_result) {
    $audit_logs_result = false;
}

// Get user name
$user_name = $_SESSION['name'] ?? 'Super Admin';

// Display messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Panel | Ultra Hospital Management System</title>
<link rel="icon" type="image/png" href="images/superadmin.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }

        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            margin-top: 70px;
        }

        .stat-card {
            background: #ffffff; border-radius: 14px; padding: 1.2rem;
            border: 1px solid #e2e8f0; transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            cursor: pointer; text-decoration: none; display: block;
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .stat-card .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .stat-card .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .stat-card .stat-icon.cyan { background: #ecfeff; color: #06b6d4; }
        .stat-card .stat-icon.orange { background: #fff7ed; color: #f97316; }
        .stat-card .stat-icon.indigo { background: #eef2ff; color: #6366f1; }
        .stat-card .stat-icon.pink { background: #fce7f3; color: #db2777; }
        .stat-card .stat-icon.green { background: #ecfdf5; color: #059669; }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; color: #1e293b; }
        .stat-card .stat-label { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
        .stat-card .stat-sub { font-size: 0.7rem; color: #94a3b8; margin-top: 0.4rem; }
        .stat-card .stat-sub .active { color: #22c55e; margin-right: 0.5rem; }
        .stat-card .stat-sub .inactive { color: #ef4444; }

        .grid { display: grid; gap: 0.8rem; margin-bottom: 1.2rem; }
        .grid-cols-6 { grid-template-columns: repeat(6, 1fr); }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        
        @media (max-width: 1400px) { .grid-cols-6 { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 1024px) { .grid-cols-6 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .grid-cols-6 { grid-template-columns: repeat(2, 1fr); }
            .grid-cols-2 { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; padding: 1rem; }
        }

        .content-card {
            background: #ffffff; border-radius: 14px; padding: 1.2rem;
            border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 1.2rem;
        }
        .content-card .card-title { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .content-card .card-title i { color: #3b82f6; }

        .quick-actions-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.8rem;
        }
        .quick-action {
            padding: 1rem 0.6rem; border-radius: 10px; text-align: center; text-decoration: none;
            transition: all 0.3s ease; cursor: pointer; background: #f8fafc; border: 1.5px solid #e2e8f0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .quick-action:hover { background: #eff6ff; border-color: #3b82f6; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(59,130,246,0.12); }
        .quick-action i { font-size: 1.5rem; margin-bottom: 0.4rem; color: #3b82f6; }
        .quick-action .action-label { font-size: 0.75rem; font-weight: 600; color: #1e293b; }
        .quick-action .action-desc { font-size: 0.6rem; color: #94a3b8; margin-top: 0.2rem; }

        .table-wrapper { overflow-x: auto; border-radius: 10px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        th { padding: 0.7rem 0.8rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase; }
        td { padding: 0.7rem 0.8rem; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #1e293b; }
        tbody tr:hover { background: #f8fafc; }

        .status-badge {
            display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px;
            font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        }
        .status-active { background: #ecfdf5; color: #059669; }
        .status-inactive { background: #fef2f2; color: #dc2626; }

        .user-badge {
            display: inline-block; padding: 0.2rem 0.7rem; border-radius: 6px;
            font-size: 0.75rem; font-weight: 500; background: #f1f5f9; color: #475569;
        }
        .user-badge.superadmin { background: #fef3c7; color: #b45309; }

        .hospital-badge {
            display: inline-block; padding: 0.2rem 0.7rem; border-radius: 6px;
            font-size: 0.75rem; font-weight: 500; background: #f1f5f9; color: #475569;
        }
        .hospital-badge.system { background: #fef3c7; color: #b45309; }

        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.4rem 0.9rem; border-radius: 6px; font-size: 0.75rem;
            font-weight: 600; text-decoration: none; cursor: pointer;
            border: none; transition: all 0.2s ease;
        }
        .btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-danger:hover { background: #fecaca; }
        .btn-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .btn-success:hover { background: #a7f3d0; }

        .text-center { text-align: center; }
        .text-secondary { color: #94a3b8; }
        .font-semibold { font-weight: 600; }
        .text-sm { font-size: 0.8rem; }
        .text-xs { font-size: 0.7rem; }
        .py-4 { padding: 1rem 0; }

        /* Alert Messages */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        /* Welcome Block */
       /* ============================================================
   WELCOME BLOCK – COLORFUL & LARGE
   ============================================================ */
.welcome-block {
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(59, 130, 246, 0.25);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

/* Subtle pattern overlay */
.welcome-block::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
    pointer-events: none;
}

.welcome-block .welcome-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1.5rem;
    position: relative;
    z-index: 1;
}

.welcome-block .greeting {
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.welcome-block .avatar-large {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(4px);
    border: 3px solid rgba(255,255,255,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.8rem;
    flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    overflow: hidden;
}
.welcome-block .avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.welcome-block .text h1 {
    font-size: 1.8rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    letter-spacing: -0.5px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.welcome-block .text .sub-text {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.85);
    margin: 0.2rem 0 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.8rem;
}
.welcome-block .text .sub-text i {
    color: rgba(255,255,255,0.9);
    margin-right: 0.2rem;
}

.welcome-block .stats-mini-large {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(8px);
    padding: 0.6rem 1.5rem;
    border-radius: 60px;
    border: 1px solid rgba(255,255,255,0.2);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.welcome-block .stats-mini-large .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.2rem 0.8rem;
}
.welcome-block .stats-mini-large .stat-number {
    font-size: 1.6rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
}
.welcome-block .stats-mini-large .stat-label {
    font-size: 0.6rem;
    font-weight: 600;
    color: rgba(255,255,255,0.8);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.welcome-block .stats-mini-large .stat-divider {
    width: 1px;
    height: 32px;
    background: rgba(255,255,255,0.25);
}

/* Responsive */
@media (max-width: 768px) {
    .welcome-block {
        padding: 1.5rem;
    }
    .welcome-block .welcome-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1.2rem;
    }
    .welcome-block .greeting {
        gap: 0.8rem;
    }
    .welcome-block .avatar-large {
        width: 56px;
        height: 56px;
        font-size: 1.4rem;
    }
    .welcome-block .text h1 {
        font-size: 1.3rem;
    }
    .welcome-block .stats-mini-large {
        padding: 0.5rem 1rem;
        border-radius: 40px;
        justify-content: space-around;
    }
    .welcome-block .stats-mini-large .stat-number {
        font-size: 1.2rem;
    }
}

@media (max-width: 480px) {
    .welcome-block .stats-mini-large {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.3rem;
        border-radius: 30px;
    }
    .welcome-block .stats-mini-large .stat-divider {
        display: none;
    }
    .welcome-block .stats-mini-large .stat-item {
        padding: 0.1rem 0.6rem;
        min-width: 60px;
    }
}

        /* System Overview Grid */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.8rem;
        }
        @media (max-width: 1024px) { .overview-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .overview-grid { grid-template-columns: 1fr; } }

        .overview-item {
            background: #f8fafc;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .overview-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .overview-item .num {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
        }
        .overview-item .label {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .overview-item i {
            color: #3b82f6;
            margin-right: 0.4rem;
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<?php include 'sidebar.php' ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content" id="mainContent">
    <?php include 'header.php' ?>

    <!-- ===== WELCOME BLOCK ===== -->
    <!-- ============================================================
     WELCOME BLOCK – COLORFUL & LARGE
     ============================================================ -->
<div class="welcome-block">
    <div class="welcome-content">
        <div class="greeting">
            <div class="avatar-large">
                <?php 
                $profile_img = $_SESSION['profile_image'] ?? '';
                if (!empty($profile_img) && file_exists($profile_img)): ?>
                    <img src="<?php echo $profile_img; ?>" alt="Profile">
                <?php else: ?>
                    <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                <?php endif; ?>
            </div>
            <div class="text">
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?> 👋</h1>
                <p class="sub-text">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                  
                </p>
            </div>
        </div>
        <div class="stats-mini-large">
            <div class="stat-item">
                <span class="stat-number"><?php echo $active_hospitals; ?></span>
                <span class="stat-label">Active Hospitals</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $today_logins; ?></span>
                <span class="stat-label">Today's Logins</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                
            </div>
        </div>
    </div>
</div>

    <!-- ===== MESSAGES ===== -->
    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- ===== STATISTICS CARDS ===== -->
    <div class="grid grid-cols-6">
        <a href="hospitals.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Hospitals</div>
                    <div class="stat-value"><?php echo $total_hospitals; ?></div>
                </div>
                <div class="stat-icon blue"><i class="fas fa-hospital"></i></div>
            </div>
            <div class="stat-sub">
                <span class="active"><i class="fas fa-circle" style="font-size:5px;"></i> <?php echo $active_hospitals; ?> Active</span>
                <span class="inactive"><i class="fas fa-circle" style="font-size:5px;"></i> <?php echo $inactive_hospitals; ?> Inactive</span>
            </div>
        </a>

        <a href="users.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-icon cyan"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-sub">
                <span class="text-secondary">Across all hospitals</span>
            </div>
        </a>

        <a href="doctors.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Doctors</div>
                    <div class="stat-value"><?php echo $total_doctors; ?></div>
                </div>
                <div class="stat-icon orange"><i class="fas fa-user-md"></i></div>
            </div>
            <div class="stat-sub">
                <span class="text-secondary">Medical professionals</span>
            </div>
        </a>

        <a href="departments.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-value"><?php echo $total_departments; ?></div>
                </div>
                <div class="stat-icon indigo"><i class="fas fa-building"></i></div>
            </div>
            <div class="stat-sub">
                <span class="text-secondary">Hospital departments</span>
            </div>
        </a>

        <a href="staff.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Staff Members</div>
                    <div class="stat-value"><?php echo $total_staff; ?></div>
                </div>
                <div class="stat-icon pink"><i class="fas fa-user-tie"></i></div>
            </div>
            <div class="stat-sub">
                <span class="text-secondary">Support staff</span>
            </div>
        </a>

      
    </div>

    <!-- ===== QUICK ACTIONS ===== -->
    <div class="content-card">
        <div class="card-title"><i class="fas fa-bolt"></i>Quick Actions</div>
        <div class="quick-actions-grid">
            <a href="add_hospital.php" class="quick-action">
                <i class="fas fa-plus-circle"></i>
                <div class="action-label">Add Hospital</div>
                <div class="action-desc">Create new</div>
            </a>
            <a href="add_doctor.php" class="quick-action">
                <i class="fas fa-user-md"></i>
                <div class="action-label">Add Doctors</div>
                <div class="action-desc">Manage all</div>
            </a>
            <a href="add_staff.php" class="quick-action">
                <i class="fas fa-users"></i>
                <div class="action-label">Add Staff</div>
                <div class="action-desc">Manage all</div>
            </a>
            <a href="hospitals.php" class="quick-action">
                <i class="fas fa-list"></i>
                <div class="action-label">Hospital List</div>
                <div class="action-desc">View all</div>
            </a>
            <a href="role_list.php" class="quick-action">
                <i class="fas fa-user-tag"></i>
                <div class="action-label">Manage Roles</div>
                <div class="action-desc">Roles & access</div>
            </a>
            <a href="users.php" class="quick-action">
                <i class="fas fa-users-cog"></i>
                <div class="action-label">All Users</div>
                <div class="action-desc">Manage access</div>
            </a>
            <a href="permissions.php" class="quick-action">
                <i class="fas fa-lock"></i>
                <div class="action-label">Manage Permissions</div>
                <div class="action-desc">Permissions</div>
            </a>
             <a href="manage_limits.php" class="quick-action">
            <i class="fas fa-credit-card"></i>
            <div class="action-label">Subscriptions</div>
            <div class="action-desc">Manage plans</div>
        </a>
          
        </div>
    </div>

    <!-- ===== SYSTEM OVERVIEW ===== -->
    <div class="content-card">
        <div class="card-title"><i class="fas fa-chart-line"></i>System Overview</div>
        <div class="overview-grid">
            <div class="overview-item">
                <div class="num"><i class="fas fa-user-injured"></i> <?php echo $total_patients; ?></div>
                <div class="label">Patients</div>
            </div>
            <div class="overview-item">
                <div class="num"><i class="fas fa-procedures"></i> <?php echo $total_ipd; ?></div>
                <div class="label">IPD Admissions</div>
            </div>
            <div class="overview-item">
                <div class="num"><i class="fas fa-stethoscope"></i> <?php echo $total_opd; ?></div>
                <div class="label">OPD Visits</div>
            </div>
           
        </div>
    </div>

    <!-- ===== HOSPITAL OVERVIEW TABLE ===== -->
    <div class="content-card">
        <div class="card-title"><i class="fas fa-hospital"></i>Hospital Overview</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Hospital Name</th>
                        <th>Code</th>
                        <th>Admin</th>
                        <th>Doctors</th>
                        <th>Depts</th>
                        <th>Staff</th>
                        <th>Patients</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hospital_overview_result && mysqli_num_rows($hospital_overview_result) > 0): ?>
                        <?php while($hospital = mysqli_fetch_assoc($hospital_overview_result)): ?>
                           <tr onclick="window.location.href='view_hospital.php?id=<?php echo $hospital['hospital_id']; ?>';" style="cursor:pointer;">
                                <td class="font-semibold"><?php echo htmlspecialchars($hospital['hospital_name']); ?></td>
                                <td class="text-sm"><?php echo htmlspecialchars($hospital['hospital_code']); ?></td>
                                <td class="text-sm"><?php echo $hospital['total_admins'] > 0 ? $hospital['total_admins'] . ' Admin(s)' : 'N/A'; ?></td>
                                <td class="text-center"><?php echo $hospital['total_doctors']; ?></td>
                                <td class="text-center"><?php echo $hospital['total_departments']; ?></td>
                                <td class="text-center"><?php echo $hospital['total_staff']; ?></td>
                                <td class="text-center"><?php echo $hospital['total_patients']; ?></td>
                               
                                <td>
                                    <span class="status-badge <?php echo $hospital['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo htmlspecialchars($hospital['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($hospital['status'] == "Active"){ ?>
                                        <a href="dashboard.php?action=toggle_status&hospital_id=<?php echo $hospital['hospital_id']; ?>&status=Inactive"
                                           onclick="return confirm('Are you sure you want to deactivate this hospital?');"
                                           class="btn btn-danger">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </a>
                                    <?php } else { ?>
                                        <a href="dashboard.php?action=toggle_status&hospital_id=<?php echo $hospital['hospital_id']; ?>&status=Active"
                                           onclick="return confirm('Are you sure you want to activate this hospital?');"
                                           class="btn btn-success">
                                            <i class="fas fa-check-circle"></i> Activate
                                        </a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-secondary py-4">No hospitals found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== RECENT HOSPITALS & OVERVIEW ===== -->
    <div class="grid grid-cols-2 gap-6">
        <!-- Recent Hospitals -->
        <div class="content-card" style="transition: all 0.3s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding-bottom: 0.75rem;">
                <div class="card-title" style="display: flex; align-items: center; gap: 0.6rem; font-size: 1rem; font-weight: 700; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                    <i class="fas fa-clock" style="color: #3b82f6;"></i>
                    Recent Hospitals
                    <span style="font-size: 0.7rem; background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding: 0.15rem 0.6rem; border-radius: 12px; font-weight: 400; color: <?php echo $theme == 'dark' ? '#94a3b8' : '#64748b'; ?>;">
                        <?php echo $recent_hospitals_result ? mysqli_num_rows($recent_hospitals_result) : 0; ?>
                    </span>
                </div>
                <a href="hospitals.php" style="font-size: 0.75rem; color: #3b82f6; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.3rem; transition: all 0.2s;">
                    View All <i class="fas fa-arrow-right" style="font-size: 0.6rem;"></i>
                </a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                <?php if ($recent_hospitals_result && mysqli_num_rows($recent_hospitals_result) > 0): ?>
                    <?php while($hospital = mysqli_fetch_assoc($recent_hospitals_result)): 
                        $status_color = $hospital['status'] == 'Active' ? '#22c55e' : '#ef4444';
                        $status_bg = $hospital['status'] == 'Active' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)';
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 1rem; border-radius: 12px; background: <?php echo $theme == 'dark' ? '#1a1a1a' : '#f8fafc'; ?>; border-left: 4px solid <?php echo $status_color; ?>; transition: all 0.25s ease; cursor: default; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0;">
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: <?php echo $status_color; ?>; font-size: 1rem;">
                                    <i class="fas fa-hospital"></i>
                                </div>
                                <div style="overflow: hidden;">
                                    <div style="font-weight: 600; font-size: 0.9rem; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($hospital['hospital_name']); ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; display: flex; gap: 0.8rem; margin-top: 0.15rem;">
                                        <span><i class="fas fa-user" style="margin-right: 0.2rem;"></i> <?php echo htmlspecialchars($hospital['admin_name'] ?? 'N/A'); ?></span>
                                        <span><i class="fas fa-calendar" style="margin-right: 0.2rem;"></i> <?php echo date('d M Y', strtotime($hospital['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <span style="padding: 0.2rem 0.7rem; border-radius: 20px; font-size: 0.6rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; white-space: nowrap;">
                                <?php echo htmlspecialchars($hospital['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2.5rem 1rem; text-align: center; color: <?php echo $theme == 'dark' ? '#6b7280' : '#94a3b8'; ?>; border-radius: 12px; background: <?php echo $theme == 'dark' ? '#1a1a1a' : '#f8fafc'; ?>; border: 1px dashed <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;">
                        <i class="fas fa-hospital" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                        <span style="font-size: 0.85rem;">No recent hospitals found</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Audit Logs -->
        <div class="content-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding-bottom: 0.75rem;">
                <div class="card-title" style="display: flex; align-items: center; gap: 0.6rem; font-size: 1rem; font-weight: 700; color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                    <i class="fas fa-history" style="color: #3b82f6;"></i>
                    Recent Audit Logs
                    <span style="font-size: 0.7rem; background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding: 0.15rem 0.6rem; border-radius: 12px; font-weight: 400; color: <?php echo $theme == 'dark' ? '#94a3b8' : '#64748b'; ?>;">
                        Latest 10
                    </span>
                </div>
                <a href="audit_logs.php" style="font-size: 0.75rem; color: #3b82f6; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.3rem; transition: all 0.2s;">
                    View All <i class="fas fa-arrow-right" style="font-size: 0.6rem;"></i>
                </a>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Hospital</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($audit_logs_result && mysqli_num_rows($audit_logs_result) > 0): ?>
                            <?php while($log = mysqli_fetch_assoc($audit_logs_result)): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($log['register_id'] == 999) {
                                            echo '<span class="user-badge superadmin">👑 Super Admin</span>';
                                        } elseif (!empty($log['user_name'])) {
                                            echo '<span class="user-badge">👤 ' . htmlspecialchars($log['user_name']) . '</span>';
                                        } else {
                                            echo '<span class="user-badge">❓ User #' . $log['register_id'] . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (is_null($log['hospital_id']) || $log['hospital_id'] == 0) {
                                            echo '<span class="hospital-badge system">🏢 System</span>';
                                        } elseif (!empty($log['hospital_name']) && $log['hospital_name'] != 'N/A') {
                                            echo '<span class="hospital-badge">🏥 ' . htmlspecialchars($log['hospital_name']) . '</span>';
                                        } else {
                                            echo '<span class="hospital-badge">🏥 Hospital #' . $log['hospital_id'] . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="user-badge"><?php echo htmlspecialchars($log['module']); ?></span>
                                    </td>
                                    <td class="text-sm">
                                        <?php echo htmlspecialchars(substr($log['action'], 0, 50) . (strlen($log['action']) > 50 ? '...' : '')); ?>
                                    </td>
                                    <td class="text-xs text-secondary">
                                        <?php echo date('d M Y H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No audit logs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>