<?php
// ============================================================
// SUPER ADMIN DASHBOARD - WITHOUT getCount() function
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
// STATISTICS - Using direct SQL queries
// ============================================================

// Total Hospitals
$query = "SELECT COUNT(*) as total FROM hospital_master WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_hospitals = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active Hospitals
$query = "SELECT COUNT(*) as total FROM hospital_master WHERE delete_flag = 0 AND status = 'Active'";
$result = mysqli_query($conn, $query);
$active_hospitals = mysqli_fetch_assoc($result)['total'] ?? 0;

// Inactive Hospitals
$query = "SELECT COUNT(*) as total FROM hospital_master WHERE delete_flag = 0 AND status = 'Inactive'";
$result = mysqli_query($conn, $query);
$inactive_hospitals = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Admins
$query = "SELECT COUNT(*) as total FROM hospital_admin WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_admins = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Doctors
$query = "SELECT COUNT(*) as total FROM doctor WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_doctors = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Departments
$query = "SELECT COUNT(*) as total FROM department WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_departments = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Staff
$query = "SELECT COUNT(*) as total FROM staff WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_staff = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Patients
$query = "SELECT COUNT(*) as total FROM patients WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_patients = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Appointments
$query = "SELECT COUNT(*) as total FROM appointments WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_appointments = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total IPD
$query = "SELECT COUNT(*) as total FROM ipd_admissions WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_ipd = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total OPD
$query = "SELECT COUNT(*) as total FROM opd WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_opd = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Users
$query = "SELECT COUNT(*) as total FROM register WHERE delete_flag = 0 AND role != 'SuperAdmin'";
$result = mysqli_query($conn, $query);
$total_users = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active Subscriptions
$query = "SELECT COUNT(*) as total FROM subscriptions WHERE delete_flag = 0 AND status = 'Active'";
$result = mysqli_query($conn, $query);
$active_subscriptions = mysqli_fetch_assoc($result)['total'] ?? 0;

// Expiring Subscriptions
$query = "SELECT COUNT(*) as total FROM subscriptions WHERE delete_flag = 0 AND status = 'Active' AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$result = mysqli_query($conn, $query);
$expiring_subscriptions = mysqli_fetch_assoc($result)['total'] ?? 0;

// Expired Subscriptions
$query = "SELECT COUNT(*) as total FROM subscriptions WHERE delete_flag = 0 AND status = 'Expired'";
$result = mysqli_query($conn, $query);
$expired_subscriptions = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total Subscriptions
$query = "SELECT COUNT(*) as total FROM subscriptions WHERE delete_flag = 0";
$result = mysqli_query($conn, $query);
$total_subscriptions = mysqli_fetch_assoc($result)['total'] ?? 0;

// Today's logins
$today = date('Y-m-d');
$query = "SELECT COUNT(*) as total FROM login_logs WHERE DATE(login_time) = '$today'";
$result = mysqli_query($conn, $query);
$today_logins = mysqli_fetch_assoc($result)['total'] ?? 0;

// ============================================================
// HOSPITAL OVERVIEW TABLE DATA
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
                            (SELECT COUNT(*) FROM patients WHERE hospital_id = h.hospital_id AND delete_flag = 0) as total_patients,
                            COALESCE((SELECT status FROM subscriptions WHERE hospital_id = h.hospital_id AND delete_flag = 0 ORDER BY created_at DESC LIMIT 1), 'No Subscription') as subscription_status
                            FROM hospital_master h
                            WHERE h.delete_flag = 0
                            ORDER BY h.created_at DESC
                            LIMIT 10";
$hospital_overview_result = mysqli_query($conn, $hospital_overview_query);

// ============================================================
// RECENT HOSPITALS (Latest 5)
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

// ============================================================
// SUBSCRIPTION SUMMARY
// ============================================================
$subscription_summary_query = "SELECT 
                              status,
                              COUNT(*) as count
                              FROM subscriptions
                              WHERE delete_flag = 0
                              GROUP BY status";
$subscription_summary_result = mysqli_query($conn, $subscription_summary_query);
$subscription_summary = ['Active' => 0, 'Expired' => 0];

if ($subscription_summary_result && mysqli_num_rows($subscription_summary_result) > 0) {
    while($row = mysqli_fetch_assoc($subscription_summary_result)) {
        $subscription_summary[$row['status']] = $row['count'];
    }
}

// ============================================================
// RECENT AUDIT LOGS (Latest 25)
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
LIMIT 25";

$audit_logs_result = mysqli_query($conn, $audit_logs_query);

// Get user name
$user_name = $_SESSION['name'] ?? 'Super Admin';
?>
<!-- Rest of HTML remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 250px; 
            padding: 1rem 0.75rem; overflow-y: auto; z-index: 1000; 
            background: #ffffff; border-right: 1px solid #e2e8f0;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        .sidebar-brand {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0 0.5rem 1rem 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }
        .sidebar-brand .brand-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .sidebar-brand .brand-text h2 { font-size: 1rem; font-weight: 700; color: #1e293b; }
        .sidebar-brand .brand-text p { font-size: 0.6rem; color: #94a3b8; }

        .sidebar-item {
            display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 0.8rem; 
            border-radius: 10px; text-decoration: none; font-size: 0.85rem;
            margin: 2px 0; color: #475569; cursor: pointer;
        }
        .sidebar-item i { width: 1.25rem; text-align: center; color: #94a3b8; }
        .sidebar-item:hover { background: #f1f5f9; color: #1e293b; }
        .sidebar-item:hover i { color: #3b82f6; }
        .sidebar-item.active { background: #eff6ff; color: #3b82f6; }
        .sidebar-item.active i { color: #3b82f6; }

        .sidebar-label {
            font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;
            padding: 0.5rem 0.8rem 0.3rem; color: #94a3b8; font-weight: 600;
        }

        .main-content {
            margin-left: 250px; padding: 1.5rem; min-height: 100vh;
        }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;
        }
        .header-left h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .header-left p { color: #94a3b8; font-size: 0.85rem; margin-top: 4px; }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .header-right .date { color: #94a3b8; font-size: 0.85rem; }
        .header-right .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.9rem;
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
        .stat-card .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem; }
        .stat-card .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .stat-card .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .stat-card .stat-icon.cyan { background: #ecfeff; color: #06b6d4; }
        .stat-card .stat-icon.orange { background: #fff7ed; color: #f97316; }
        .stat-card .stat-icon.indigo { background: #eef2ff; color: #6366f1; }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; color: #1e293b; }
        .stat-card .stat-label { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
        .stat-card .stat-sub { font-size: 0.7rem; color: #94a3b8; margin-top: 0.4rem; }
        .stat-card .stat-sub .active { color: #22c55e; margin-right: 0.5rem; }
        .stat-card .stat-sub .inactive { color: #ef4444; }

        .grid { display: grid; gap: 0.8rem; margin-bottom: 1.2rem; }
        .grid-cols-6 { grid-template-columns: repeat(4, 1fr); }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        
        @media (max-width: 1400px) { .grid-cols-6 { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 1024px) { .grid-cols-6 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .grid-cols-6 { grid-template-columns: repeat(2, 1fr); }
            .grid-cols-2 { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { width: 70px; }
            .sidebar-brand .brand-text { display: none; }
            .sidebar-label { display: none; }
            .sidebar-item span { display: none; }
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
        .mb-6 { margin-bottom: 1.2rem; }
    </style>
</head>
<body>

<!-- ============================================================
SIDEBAR - SUPER ADMIN
============================================================ -->
<?php include('sidebar.php') ?>

<!-- ============================================================
MAIN CONTENT
============================================================ -->
<div class="main-content" id="mainContent">
    
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-chart-pie" style="color:#3b82f6;margin-right:0.5rem;"></i>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($user_name); ?></p>
        </div>
        <div class="header-right">
            <span class="date"><?php echo date('l, d M Y'); ?></span>
            <div class="avatar"><?php echo strtoupper(substr($user_name, 0, 2)); ?></div>
        </div>
    </div>

    <!-- Statistics -->
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
                    <div class="stat-label">Total Doctors</div>
                    <div class="stat-value"><?php echo $total_doctors; ?></div>
                </div>
                <div class="stat-icon cyan"><i class="fas fa-user-md"></i></div>
            </div>
            <div class="stat-sub">
                <span class="text-secondary">Across all hospitals</span>
            </div>
        </a>

        <a href="departments.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Departments</div>
                    <div class="stat-value"><?php echo $total_departments; ?></div>
                </div>
                <div class="stat-icon orange"><i class="fas fa-building"></i></div>
            </div>
            <div class="stat-sub">
                <span class="text-secondary">Hospital departments</span>
            </div>
        </a>

        <a href="subscriptions.php" class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Active Subscriptions</div>
                    <div class="stat-value"><?php echo $active_subscriptions; ?></div>
                </div>
                <div class="stat-icon indigo"><i class="fas fa-credit-card"></i></div>
            </div>
            <div class="stat-sub">
                <span class="active"><i class="fas fa-circle" style="font-size:5px;"></i> <?php echo $expiring_subscriptions; ?> Expiring Soon</span>
            </div>
        </a>
    </div>

    <!-- Quick Actions -->
    <div class="content-card">
        <div class="card-title"><i class="fas fa-bolt"></i>Quick Actions</div>
        <div class="quick-actions-grid">
            <a href="add_hospital.php" class="quick-action">
                <i class="fas fa-plus-circle"></i>
                <div class="action-label">Add Hospital</div>
                <div class="action-desc">Create new</div>
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
            <a href="permissions.php" class="quick-action">
                <i class="fas fa-lock"></i>
                <div class="action-label">Manage Permissions</div>
                <div class="action-desc">Permissions</div>
            </a>
            <a href="audit_logs.php" class="quick-action">
                <i class="fas fa-history"></i>
                <div class="action-label">Audit Logs</div>
                <div class="action-desc">View activities</div>
            </a>
        </div>
    </div>

    <!-- Hospital Overview Table -->
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
                        <th>Subscription</th>
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
                                    <span class="status-badge <?php echo $hospital['subscription_status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo htmlspecialchars($hospital['subscription_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $hospital['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo htmlspecialchars($hospital['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($hospital['status']=="Active"){ ?>
                                        <a href="toggle_hospital_status.php?hospital_id=<?php echo $hospital['hospital_id']; ?>&status=Inactive"
                                           class="btn btn-danger"
                                           onclick="return confirm('Are you sure you want to deactivate this hospital?');">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </a>
                                    <?php } else { ?>
                                        <a href="toggle_hospital_status.php?hospital_id=<?php echo $hospital['hospital_id']; ?>&status=Active"
                                           class="btn btn-success"
                                           onclick="return confirm('Are you sure you want to activate this hospital?');">
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

    <!-- Recent Hospitals & Subscription Summary -->
    <div class="grid grid-cols-2">
        <div class="content-card">
            <div class="card-title"><i class="fas fa-clock"></i>Recent Hospitals</div>
            <div style="display:flex;flex-direction:column;gap:0.6rem;">
                <?php if ($recent_hospitals_result && mysqli_num_rows($recent_hospitals_result) > 0): ?>
                    <?php while($hospital = mysqli_fetch_assoc($recent_hospitals_result)): ?>
                        <div style="padding:0.6rem 0.8rem;border:1px solid #e2e8f0;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div class="font-semibold text-sm"><?php echo htmlspecialchars($hospital['hospital_name']); ?></div>
                                <div class="text-xs text-secondary mt-2">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($hospital['admin_name'] ?? 'N/A'); ?>
                                </div>
                                <div class="text-xs text-secondary mt-2">
                                    <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($hospital['created_at'])); ?>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $hospital['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo htmlspecialchars($hospital['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-secondary py-4">No recent hospitals</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-card">
            <div class="card-title"><i class="fas fa-chart-pie"></i>Subscription Summary</div>
            <div style="display:flex;flex-direction:column;gap:0.6rem;">
                <div style="padding:0.8rem 1rem;background:#ecfdf5;border-radius:8px;border-left:4px solid #22c55e;">
                    <div class="text-xs text-secondary mb-1">Active Plans</div>
                    <div style="font-size:1.5rem;font-weight:700;color:#22c55e;">
                        <?php echo $subscription_summary['Active'] ?? 0; ?>
                    </div>
                </div>
                <div style="padding:0.8rem 1rem;background:#fef3c7;border-radius:8px;border-left:4px solid #eab308;">
                    <div class="text-xs text-secondary mb-1">Expiring Soon</div>
                    <div style="font-size:1.5rem;font-weight:700;color:#b45309;">
                        <?php echo $expiring_subscriptions; ?>
                    </div>
                </div>
                <div style="padding:0.8rem 1rem;background:#fee2e2;border-radius:8px;border-left:4px solid #dc2626;">
                    <div class="text-xs text-secondary mb-1">Expired Plans</div>
                    <div style="font-size:1.5rem;font-weight:700;color:#dc2626;">
                        <?php echo $subscription_summary['Expired'] ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="content-card">
        <div class="card-title"><i class="fas fa-history"></i>Recent Audit Logs</div>
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

</body>
</html>