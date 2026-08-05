<?php
require_once __DIR__ . '/../config/permission.php';

// Security: Only Super Admin can access this page
if (!$is_super_admin) {
    header("Location: dashboard.php");
    exit();
}

 $success_msg = "";
 $error_msg = "";

// ============================================================
// HANDLE SEED DEFAULT PERMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_permissions'])) {
    $default_permissions = [
        ['Dashboard', 'Dashboard View', 'dashboard-view', 'fa-chart-pie', 1, 1],
        ['Dashboard', 'Dashboard Edit', 'dashboard-edit', 'fa-edit', 0, 0],
        ['Patient', 'Patient View', 'patient-view', 'fa-users', 1, 1],
        ['Patient', 'Patient Create', 'patient-create', 'fa-user-plus', 1, 1],
        ['Patient', 'Patient Edit', 'patient-edit', 'fa-user-edit', 0, 0],
        ['Patient', 'Patient Delete', 'patient-delete', 'fa-user-times', 0, 0],
        ['OPD', 'OPD Visit View', 'opd-visit-view', 'fa-stethoscope', 1, 1],
        ['OPD', 'OPD Visit Create', 'opd-visit-create', 'fa-plus-circle', 0, 0],
        ['OPD', 'OPD Visit Edit', 'opd-visit-edit', 'fa-edit', 0, 0],
        ['OPD', 'OPD Visit Delete', 'opd-visit-delete', 'fa-trash', 0, 0],
        ['IPD', 'IPD Admission View', 'ipd-admission-view', 'fa-hospital-user', 1, 1],
        ['IPD', 'IPD Admission Create', 'ipd-admission-create', 'fa-plus-circle', 0, 0],
        ['IPD', 'IPD Admission Edit', 'ipd-admission-edit', 'fa-edit', 0, 0],
        ['IPD', 'IPD Admission Delete', 'ipd-admission-delete', 'fa-trash', 0, 0],
        ['Referral', 'Referral View', 'referral-view', 'fa-share-alt', 1, 1],
        ['Referral', 'Referral Create', 'referral-create', 'fa-plus-circle', 0, 0],
        ['Call Patient', 'Call Patient View', 'call-patient-view', 'fa-phone', 1, 1],
        ['Call Patient', 'Call Patient Create', 'call-patient-create', 'fa-plus-circle', 0, 0],
        ['Appointment', 'Appointment View', 'appointment-view', 'fa-calendar-check', 1, 1],
        ['Appointment', 'Appointment Create', 'appointment-create', 'fa-plus-circle', 0, 0],
        ['Appointment', 'Appointment Edit', 'appointment-edit', 'fa-edit', 0, 0],
        ['Appointment', 'Appointment Delete', 'appointment-delete', 'fa-trash', 0, 0],
        ['Prescription', 'Prescription View', 'prescription-view', 'fa-prescription', 1, 1],
        ['Prescription', 'Prescription Create', 'prescription-create', 'fa-plus-circle', 0, 0],
        ['Prescription', 'Prescription Edit', 'prescription-edit', 'fa-edit', 0, 0],
        ['Prescription', 'Prescription Delete', 'prescription-delete', 'fa-trash', 0, 0],
        ['Surgery', 'Surgery View', 'surgery-view', 'fa-procedures', 1, 1],
        ['Surgery', 'Surgery Create', 'surgery-create', 'fa-plus-circle', 0, 0],
        ['Surgery', 'Surgery Edit', 'surgery-edit', 'fa-edit', 0, 0],
        ['Surgery', 'Surgery Delete', 'surgery-delete', 'fa-trash', 0, 0],
        ['Laboratory', 'Lab Master View', 'lab-master-view', 'fa-file-alt', 1, 1],
        ['Laboratory', 'Lab Master Create', 'lab-master-create', 'fa-plus-circle', 0, 0],
        ['Laboratory', 'Lab Orders View', 'lab-orders-view', 'fa-vial', 1, 1],
        ['Laboratory', 'Lab Orders Create', 'lab-orders-create', 'fa-plus-circle', 0, 0],
        ['Laboratory', 'Lab Reports View', 'lab-reports-view', 'fa-file-medical', 1, 1],
        ['Laboratory', 'Lab Reports Create', 'lab-reports-create', 'fa-plus-circle', 0, 0],
        ['Pharmacy', 'Stock View', 'stock-view', 'fa-boxes', 1, 1],
        ['Pharmacy', 'Stock Create', 'stock-create', 'fa-plus-circle', 0, 0],
        ['Pharmacy', 'Stock Edit', 'stock-edit', 'fa-edit', 0, 0],
        ['Pharmacy', 'Medicine Sales View', 'medicine-sales-view', 'fa-cash-register', 1, 1],
        ['Pharmacy', 'Medicine Sales Create', 'medicine-sales-create', 'fa-plus-circle', 0, 0],
        ['Doctor', 'Doctor View', 'doctor-view', 'fa-user-md', 1, 1],
        ['Doctor', 'Doctor Create', 'doctor-create', 'fa-plus-circle', 0, 0],
        ['Doctor', 'Doctor Edit', 'doctor-edit', 'fa-edit', 0, 0],
        ['Doctor', 'Doctor Delete', 'doctor-delete', 'fa-trash', 0, 0],
        ['Staff', 'Staff View', 'staff-view', 'fa-users-cog', 1, 1],
        ['Staff', 'Staff Create', 'staff-create', 'fa-plus-circle', 0, 0],
        ['Staff', 'Staff Edit', 'staff-edit', 'fa-edit', 0, 0],
        ['Staff', 'Staff Delete', 'staff-delete', 'fa-trash', 0, 0],
        ['Department', 'Department View', 'department-view', 'fa-layer-group', 1, 1],
        ['Department', 'Department Create', 'department-create', 'fa-plus-circle', 0, 0],
        ['Department', 'Department Edit', 'department-edit', 'fa-edit', 0, 0],
        ['Department', 'Department Delete', 'department-delete', 'fa-trash', 0, 0],
        ['Ward', 'Ward View', 'ward-view', 'fa-bed', 1, 1],
        ['Ward', 'Ward Create', 'ward-create', 'fa-plus-circle', 0, 0],
        ['Ward', 'Ward Edit', 'ward-edit', 'fa-edit', 0, 0],
        ['Ward', 'Ward Delete', 'ward-delete', 'fa-trash', 0, 0],
        ['Hospital', 'Hospital View', 'hospital-view', 'fa-hospital', 1, 1],
        ['Hospital', 'Hospital Create', 'hospital-create', 'fa-plus-circle', 0, 0],
        ['Hospital', 'Hospital Edit', 'hospital-edit', 'fa-edit', 0, 0],
        ['Hospital', 'Hospital Delete', 'hospital-delete', 'fa-trash', 0, 0],
        ['Billing', 'Billing View', 'billing-view', 'fa-file-invoice-dollar', 1, 1],
        ['Billing', 'Billing Create', 'billing-create', 'fa-plus-circle', 0, 0],
        ['Billing', 'Billing Edit', 'billing-edit', 'fa-edit', 0, 0],
        ['Billing', 'Billing Delete', 'billing-delete', 'fa-trash', 0, 0],
        ['Advance Deposit', 'Advance Deposit View', 'advance-deposit-view', 'fa-wallet', 1, 1],
        ['Advance Deposit', 'Advance Deposit Create', 'advance-deposit-create', 'fa-plus-circle', 0, 0],
        ['Role Management', 'Role View', 'role-view', 'fa-user-tag', 1, 1],
        ['Role Management', 'Role Create', 'role-create', 'fa-plus-circle', 0, 0],
        ['Role Management', 'Role Edit', 'role-edit', 'fa-edit', 0, 0],
        ['Role Management', 'Role Delete', 'role-delete', 'fa-trash', 0, 0],
        ['Permission Management', 'Permission View', 'permission-view', 'fa-lock', 1, 1],
        ['Permission Management', 'Permission Manage', 'permission-manage', 'fa-key', 0, 0],
        ['Audit Logs', 'Audit Log View', 'audit-log-view', 'fa-clipboard-list', 1, 1],
        ['Reports', 'Report View', 'report-view', 'fa-chart-bar', 1, 1],
        ['Reports', 'Report Export', 'report-export', 'fa-download', 0, 0],
        ['Settings', 'Settings View', 'settings-view', 'fa-cog', 1, 1],
        ['Settings', 'Settings Edit', 'settings-edit', 'fa-cogs', 0, 0],
        ['Events', 'Events View', 'events-view', 'fa-calendar', 1, 1],
        ['Events', 'Events Create', 'events-create', 'fa-plus-circle', 0, 0],
        ['Profile', 'Profile View', 'profile-view', 'fa-user-circle', 1, 1],
        ['Profile', 'Profile Edit', 'profile-edit', 'fa-edit', 0, 0],
        ['HR', 'HR Dashboard View', 'hr-dashboard-view', 'fa-tachometer-alt', 1, 1],
        ['HR', 'HR Attendance View', 'hr-attendance-view', 'fa-clock', 1, 1],
        ['HR', 'HR Leave View', 'hr-leave-view', 'fa-calendar-minus', 1, 1],
        ['HR', 'HR Payroll View', 'hr-payroll-view', 'fa-money-bill-wave', 1, 1],
        ['HR', 'HR Recruitment View', 'hr-recruitment-view', 'fa-user-plus', 1, 1],
        ['Accountant', 'Accountant Dashboard View', 'accountant-dashboard-view', 'fa-calculator', 1, 1],
        ['Accountant', 'Accountant Salary View', 'accountant-salary-view', 'fa-money-bill-wave', 1, 1],
        ['Accountant', 'Accountant Expense View', 'accountant-expense-view', 'fa-receipt', 1, 1],
        ['Accountant', 'Accountant Payment View', 'accountant-payment-view', 'fa-credit-card', 1, 1],
        ['Accountant', 'Accountant Invoice View', 'accountant-invoice-view', 'fa-file-invoice-dollar', 1, 1],
        ['Accountant', 'Accountant Ledger View', 'accountant-ledger-view', 'fa-book', 1, 1],
        ['Pharmacist', 'Pharmacist Dashboard View', 'pharmacist-dashboard-view', 'fa-prescription-bottle-alt', 1, 1],
        ['Pharmacist', 'Pharmacist Medicine View', 'pharmacist-medicine-view', 'fa-capsules', 1, 1],
        ['Pharmacist', 'Pharmacist Prescription View', 'pharmacist-prescription-view', 'fa-prescription', 1, 1],
        ['Pharmacist', 'Pharmacist Supplier View', 'pharmacist-supplier-view', 'fa-truck', 1, 1],
        ['Pharmacist', 'Pharmacist Expiry View', 'pharmacist-expiry-view', 'fa-exclamation-triangle', 1, 1],
    ];

    $seeded = 0;
    $skipped = 0;
    $sort = 0;

    foreach ($default_permissions as $perm) {
        $group = mysqli_real_escape_string($conn, $perm[0]);
        $name = mysqli_real_escape_string($conn, $perm[1]);
        $slug = mysqli_real_escape_string($conn, $perm[2]);
        $icon = mysqli_real_escape_string($conn, $perm[3]);
        $is_sidebar = intval($perm[4]);
        $is_dashboard = intval($perm[5]);
        $sort++;

        $check = mysqli_query($conn, "SELECT permission_id FROM permissions WHERE permission_slug = '$slug' AND (delete_flag = 0 OR delete_flag IS NULL)");
        if (mysqli_num_rows($check) > 0) {
            $skipped++;
            continue;
        }

        $insert = "INSERT INTO permissions (permission_group, parent_id, permission_name, permission_slug, permission_icon, menu_order, is_sidebar, is_dashboard, description, is_system, sort_order, created_at, modified_at, delete_flag)
                   VALUES ('$group', NULL, '$name', '$slug', '$icon', 0, $is_sidebar, $is_dashboard, '', 0, $sort, NOW(), NOW(), 0)";

        if (mysqli_query($conn, $insert)) {
            $seeded++;
        }
    }

    logAudit('Permission', "Seeded default permissions: $seeded added, $skipped skipped");
    $success_msg = "Seeded $seeded new permissions. $skipped already existed and were skipped.";
}

// ============================================================
// HANDLE ADD NEW PERMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_permission'])) {
    $permission_group = mysqli_real_escape_string($conn, $_POST['permission_group']);
    $permission_name = mysqli_real_escape_string($conn, $_POST['permission_name']);
    $permission_slug = mysqli_real_escape_string($conn, $_POST['permission_slug']);
    $permission_icon = mysqli_real_escape_string($conn, $_POST['permission_icon'] ?? 'fa-circle');
    $menu_order = intval($_POST['menu_order'] ?? 0);
    $is_sidebar = isset($_POST['is_sidebar']) ? 1 : 0;
    $is_dashboard = isset($_POST['is_dashboard']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

    $check_query = "SELECT permission_id FROM permissions WHERE permission_slug = '$permission_slug' AND (delete_flag = 0 OR delete_flag IS NULL)";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $error_msg = "Permission with slug '$permission_slug' already exists!";
    } else {
        $insert_query = "INSERT INTO permissions (
            permission_group, parent_id, permission_name, permission_slug, permission_icon,
            menu_order, is_sidebar, is_dashboard, description, is_system, sort_order,
            created_at, modified_at, delete_flag
        ) VALUES (
            '$permission_group', NULL, '$permission_name', '$permission_slug', '$permission_icon',
            '$menu_order', '$is_sidebar', '$is_dashboard', '$description', 0, '$sort_order',
            NOW(), NOW(), 0
        )";

        if (mysqli_query($conn, $insert_query)) {
            logAudit('Permission', "Added new permission: $permission_name (Slug: $permission_slug)");
            $success_msg = "Permission added successfully!";
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
}

// ============================================================
// HANDLE DELETE PERMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_permission'])) {
    $del_id = intval($_POST['permission_id']);
    $del_query = "UPDATE permissions SET delete_flag = 1, modified_at = NOW() WHERE permission_id = $del_id AND is_system = 0";
    if (mysqli_query($conn, $del_query) && mysqli_affected_rows($conn) > 0) {
        mysqli_query($conn, "DELETE FROM role_permissions WHERE permission_id = $del_id");
        logAudit('Permission', "Deleted permission ID: $del_id");
        $success_msg = "Permission deleted successfully!";
    } else {
        $error_msg = "Cannot delete system permission or permission not found.";
    }
}

// Fetch all hospitals for filter
 $hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE (delete_flag = 0 OR delete_flag IS NULL) AND status = 'Active' ORDER BY hospital_name";
 $hospitals_result = mysqli_query($conn, $hospitals_query);
 $hospitals = [];
if ($hospitals_result && mysqli_num_rows($hospitals_result) > 0) {
    while ($row = mysqli_fetch_assoc($hospitals_result)) {
        $hospitals[] = $row;
    }
}

 $selected_hospital = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

// Fetch all roles
 $roles_query = "SELECT role_id, role_name, description FROM roles WHERE (delete_flag = 0 OR delete_flag IS NULL)";
if ($selected_hospital > 0) {
    $roles_query .= " AND (hospital_id IS NULL OR hospital_id = 0 OR hospital_id = $selected_hospital)";
}
 $roles_query .= " ORDER BY role_name";
 $roles_res = mysqli_query($conn, $roles_query);
 $roles = [];
if ($roles_res && mysqli_num_rows($roles_res) > 0) {
    while ($row = mysqli_fetch_assoc($roles_res)) {
        $roles[] = $row;
    }
}

// ============================================================
// HANDLE SAVE PERMISSIONS
// FIX: Use hospital_id=0 for "All Hospitals" (single global template)
// This ensures ALL hospitals (existing + future) get these permissions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $post_hospital = intval($_POST['hospital_id']);
    $post_role = intval($_POST['role_id']);
    $assigned_permissions = $_POST['permissions'] ?? [];

    // Validate role exists
    $role_check = mysqli_query($conn, "SELECT role_id FROM roles WHERE role_id = $post_role AND (delete_flag = 0 OR delete_flag IS NULL)");
    if (!$role_check || mysqli_num_rows($role_check) == 0) {
        $error_msg = "Invalid role selected.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // FIX: When hospital_id=0 (All Hospitals), save with hospital_id=0
            // This creates a SINGLE set of permissions that applies to ALL hospitals
            // When specific hospital, save with that hospital_id (override)

            // Delete existing entries for this role + hospital combination
            if ($post_hospital == 0) {
                // Delete only hospital_id=0 entries (global template)
                $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ? AND (hospital_id = 0 OR hospital_id IS NULL)");
                $stmt->bind_param("i", $post_role);
            } else {
                // Delete only this hospital's specific entries
                $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ? AND hospital_id = ?");
                $stmt->bind_param("ii", $post_role, $post_hospital);
            }
            $stmt->execute();
            $stmt->close();

            // Insert new permissions
            if (!empty($assigned_permissions)) {
                foreach ($assigned_permissions as $p_id) {
                    $p_id = intval($p_id);
                    if ($p_id > 0) {
                        if ($post_hospital == 0) {
                            // Save as global (hospital_id=0)
                            $stmt = $conn->prepare("INSERT INTO role_permissions (hospital_id, role_id, permission_id) VALUES (0, ?, ?)");
                            $stmt->bind_param("ii", $post_role, $p_id);
                        } else {
                            // Save for specific hospital
                            $stmt = $conn->prepare("INSERT INTO role_permissions (hospital_id, role_id, permission_id) VALUES (?, ?, ?)");
                            $stmt->bind_param("iii", $post_hospital, $post_role, $p_id);
                        }
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            mysqli_commit($conn);

            $hospital_text = $post_hospital == 0 ? "ALL HOSPITALS" : "hospital ID: $post_hospital";
            logAudit('Permission', "Updated permissions for role ID: $post_role, $hospital_text");

            // Redirect back with GET parameters so checkboxes stay checked
            $redirect_url = "permissions.php?hospital_id=$post_hospital&role_id=$post_role&saved=1";
            header("Location: $redirect_url");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = "Error updating permissions: " . $e->getMessage();
        }
    }
}

// Check for saved success message from redirect
if (isset($_GET['saved']) && $_GET['saved'] == 1) {
    $success_msg = "Permissions updated successfully!";
}

// Fetch all permissions grouped by category
 $perm_res = mysqli_query($conn, "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group, sort_order, permission_name");
 $all_permissions = [];
while ($row = mysqli_fetch_assoc($perm_res)) {
    $all_permissions[$row['permission_group']][] = $row;
}

// ============================================================
// FIX: Get permissions for selected role
// - hospital_id=0 selected: Show ONLY global (hospital_id=0) entries
// - specific hospital selected: Show hospital_id=X OR hospital_id=0 (effective permissions)
// ============================================================
 $selected_role = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
 $role_permissions = [];

if ($selected_role > 0 && $selected_hospital > 0) {
    // Specific hospital: show effective permissions (global + hospital-specific)
    $stmt = $conn->prepare("SELECT DISTINCT permission_id FROM role_permissions WHERE role_id = ? AND (hospital_id = ? OR hospital_id = 0 OR hospital_id IS NULL)");
    $stmt->bind_param("ii", $selected_role, $selected_hospital);
    $stmt->execute();
    $rp_res = $stmt->get_result();
    while ($row = $rp_res->fetch_assoc()) {
        $role_permissions[] = $row['permission_id'];
    }
    $stmt->close();
} elseif ($selected_role > 0 && $selected_hospital == 0) {
    // All Hospitals: show ONLY global (hospital_id=0) entries
    $stmt = $conn->prepare("SELECT DISTINCT permission_id FROM role_permissions WHERE role_id = ? AND (hospital_id = 0 OR hospital_id IS NULL)");
    $stmt->bind_param("i", $selected_role);
    $stmt->execute();
    $rp_res = $stmt->get_result();
    while ($row = $rp_res->fetch_assoc()) {
        $role_permissions[] = $row['permission_id'];
    }
    $stmt->close();
}

// Get role name for display
 $role_name = '';
if ($selected_role > 0) {
    $role_query = "SELECT role_name FROM roles WHERE role_id = $selected_role";
    $role_result = mysqli_query($conn, $role_query);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_data = mysqli_fetch_assoc($role_result);
        $role_name = $role_data['role_name'];
    }
}

// Count total permissions
 $total_perms = getCount('permissions', 'permission_id', 'delete_flag = 0');
 $total_roles = getCount('roles', 'role_id', 'delete_flag = 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Permissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; display: flex; }

        .btn {
            padding: 0.6rem 1.4rem; border-radius: 8px; border: none; cursor: pointer;
            font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center;
            gap: 0.5rem; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            text-decoration: none; font-family: 'Inter', sans-serif;
        }
        .btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 2px 8px rgba(34,197,94,0.25); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(34,197,94,0.35); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; transform: translateY(-1px); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(239,68,68,0.35); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(245,158,11,0.35); }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59,130,246,0.3); }
        .btn-sm { padding: 0.25rem 0.7rem; font-size: 0.75rem; border-radius: 6px; }

        .form-control {
            padding: 0.6rem 0.9rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
            font-size: 0.9rem; outline: none; transition: all 0.3s ease; background: #f8fafc;
            color: #1e293b; font-family: 'Inter', sans-serif; width: 100%;
        }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
        .form-label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.5px; }

        .main-content { margin-left: 260px; padding: 2rem; width: calc(100% - 260px); min-height: 100vh; }
        .card { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; border: 1px solid #e2e8f0; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .alert i { font-size: 1.1rem; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .page-header p { color: #94a3b8; font-size: 0.85rem; }
        .page-header .header-left { display: flex; align-items: center; gap: 0.75rem; }
        .page-header .header-left i { color: #3b82f6; font-size: 1.5rem; }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; padding: 1.25rem; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-icon.blue { background: #dbeafe; color: #3b82f6; }
        .stat-icon.green { background: #dcfce7; color: #22c55e; }
        .stat-icon.purple { background: #f3e8ff; color: #8b5cf6; }
        .stat-icon.orange { background: #fff7ed; color: #f59e0b; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .stat-label { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }

        .filter-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .filter-row .field { flex: 1; min-width: 180px; }
        .filter-row label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.5px; }

        .role-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 1rem; border-radius: 20px; background: #dbeafe; color: #3b82f6; font-size: 0.8rem; font-weight: 600; }
        .hospital-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.8rem; border-radius: 12px; background: #f1f5f9; color: #475569; font-size: 0.75rem; font-weight: 500; }

        .sticky-footer { position: sticky; bottom: 2rem; text-align: center; padding: 1rem; border-top: 1px solid #e2e8f0; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); border-radius: 8px; margin-top: 0.5rem; }
        .empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; color: #e2e8f0; }
        .empty-state h3 { color: #1e293b; margin-bottom: 0.5rem; }

        .tree-container { display: flex; flex-direction: column; gap: 0.25rem; }
        .tree-item { display: flex; flex-direction: column; }
        .tree-folder { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.15s ease; user-select: none; }
        .tree-folder:hover { background: #eef2f6; }
        .tree-folder .chevron { font-size: 0.7rem; color: #94a3b8; transition: transform 0.25s ease; width: 1.2rem; text-align: center; }
        .tree-folder .chevron.open { transform: rotate(90deg); }
        .tree-folder .folder-icon { font-size: 1rem; color: #3b82f6; width: 1.2rem; text-align: center; }
        .tree-folder .folder-name { font-weight: 600; font-size: 0.9rem; color: #1e293b; flex: 1; }
        .tree-folder .folder-badge { font-size: 0.65rem; background: #e2e8f0; padding: 0.1rem 0.5rem; border-radius: 12px; color: #475569; font-weight: 500; }
        .tree-folder .folder-count { font-size: 0.65rem; font-weight: 600; }

        .tree-children { margin-left: 1.5rem; padding-left: 0.5rem; border-left: 2px solid #e2e8f0; display: flex; flex-direction: column; gap: 0.15rem; max-height: 0; overflow: hidden; opacity: 0; transition: max-height 0.3s ease, opacity 0.25s ease; padding-top: 0; padding-bottom: 0; }
        .tree-children.open { max-height: 5000px; opacity: 1; padding-top: 0.25rem; padding-bottom: 0.25rem; }

        .tree-file { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.5rem; border-radius: 6px; background: #f8fafc; transition: background 0.15s; }
        .tree-file:hover { background: #eef2f6; }
        .tree-file input[type="checkbox"] { width: 15px; height: 15px; accent-color: #3b82f6; cursor: pointer; flex-shrink: 0; }
        .tree-file .file-icon { color: #94a3b8; font-size: 0.8rem; width: 1.2rem; text-align: center; }
        .tree-file .file-name { font-size: 0.85rem; color: #475569; flex: 1; }
        .tree-file .file-slug { font-size: 0.7rem; color: #94a3b8; font-family: 'Courier New', monospace; background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 4px; }
        .tree-file .file-delete { color: #ef4444; cursor: pointer; font-size: 0.75rem; opacity: 0; transition: opacity 0.2s; padding: 0.2rem; }
        .tree-file:hover .file-delete { opacity: 1; }

        .tree-toolbar { display: flex; gap: 0.5rem; padding: 0.2rem 0; flex-wrap: wrap; }
        .tree-toolbar .btn-sm { padding: 0.15rem 0.6rem; font-size: 0.7rem; }

        .progress-bar { height: 4px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 0.5rem; }
        .progress-bar .fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }

        .info-banner { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #1e40af; }

        @media (max-width: 768px) { .main-content { margin-left: 200px; padding: 1rem; width: calc(100% - 200px); } }
        @media (max-width: 480px) { .main-content { margin-left: 70px; padding: 1rem; width: calc(100% - 70px); } }
    </style>
</head>
<body>
    <?php include 'header.php' ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content" style="margin-top: 80px;">

        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                <i class="fas fa-lock"></i>
                <div>
                    <h1>Permission Management</h1>
                    <p>Manage role-based access control for all users</p>
                </div>
            </div>
            <form method="POST" style="display:inline;" onsubmit="return confirm('This will add all default permissions to the database. Existing permissions will be skipped. Continue?');">
                <button type="submit" name="seed_permissions" class="btn btn-warning">
                    <i class="fas fa-magic"></i> Seed Default Permissions
                </button>
            </form>
        </div>

        <!-- Messages -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

       

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-key"></i></div>
                <div><div class="stat-value"><?php echo $total_perms; ?></div><div class="stat-label">Total Permissions</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-user-tag"></i></div>
                <div><div class="stat-value"><?php echo $total_roles; ?></div><div class="stat-label">Total Roles</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-layer-group"></i></div>
                <div><div class="stat-value"><?php echo count($all_permissions); ?></div><div class="stat-label">Permission Groups</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-hospital"></i></div>
                <div><div class="stat-value"><?php echo count($hospitals); ?></div><div class="stat-label">Active Hospitals</div></div>
            </div>
        </div>

        <!-- Add Permission Form -->
        <div class="card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <i class="fas fa-plus-circle" style="color: #3b82f6;"></i> Add New Permission
                </h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddForm()">
                    <i class="fas fa-chevron-down" id="toggleIcon"></i> <span id="toggleText">Show Form</span>
                </button>
            </div>
            <div id="addPermissionForm" style="display: none; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <form method="POST" onsubmit="return validatePermissionForm()">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem 1.5rem;">
                        <div>
                            <label class="form-label">Permission Group <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="permission_group" class="form-control" placeholder="e.g., Dashboard, Patients" required>
                        </div>
                        <div>
                            <label class="form-label">Permission Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="permission_name" class="form-control" placeholder="e.g., Dashboard View" required>
                        </div>
                        <div>
                            <label class="form-label">Permission Slug <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="permission_slug" class="form-control" placeholder="e.g., dashboard-view" required>
                            <small style="color: #94a3b8; font-size: 0.7rem;">Unique identifier (lowercase, hyphens only). Must match sidebar hasPerm() check!</small>
                        </div>
                        <div>
                            <label class="form-label">Icon</label>
                            <select name="permission_icon" class="form-control" id="iconSelect" onchange="updateIconPreview()">
                                <option value="fa-circle">fa-circle</option>
                                <?php
                                $fa_icons = ['fa-chart-pie'=>'Chart Pie','fa-users'=>'Users','fa-user-plus'=>'User Plus','fa-user-edit'=>'User Edit','fa-user-times'=>'User Times','fa-user-injured'=>'User Injured','fa-user-md'=>'User MD','fa-users-cog'=>'Users Cog','fa-stethoscope'=>'Stethoscope','fa-hospital-user'=>'Hospital User','fa-hospital'=>'Hospital','fa-procedures'=>'Procedures','fa-bed'=>'Bed','fa-prescription'=>'Prescription','fa-calendar-check'=>'Calendar Check','fa-phone'=>'Phone','fa-share-alt'=>'Share','fa-flask'=>'Flask','fa-vial'=>'Vial','fa-file-medical'=>'File Medical','fa-file-alt'=>'File Alt','fa-boxes'=>'Boxes','fa-cash-register'=>'Cash Register','fa-layer-group'=>'Layer Group','fa-user-tag'=>'User Tag','fa-lock'=>'Lock','fa-key'=>'Key','fa-clipboard-list'=>'Clipboard','fa-chart-bar'=>'Chart Bar','fa-download'=>'Download','fa-cog'=>'Cog','fa-cogs'=>'Cogs','fa-edit'=>'Edit','fa-trash'=>'Trash','fa-plus-circle'=>'Plus Circle','fa-eye'=>'Eye','fa-wallet'=>'Wallet','fa-file-invoice-dollar'=>'Invoice','fa-calendar'=>'Calendar','fa-user-circle'=>'User Circle','fa-tachometer-alt'=>'Tachometer','fa-clock'=>'Clock','fa-calendar-minus'=>'Calendar Minus','fa-money-bill-wave'=>'Money Bill','fa-calculator'=>'Calculator','fa-receipt'=>'Receipt','fa-credit-card'=>'Credit Card','fa-book'=>'Book','fa-prescription-bottle-alt'=>'Prescription Bottle','fa-capsules'=>'Capsules','fa-truck'=>'Truck','fa-exclamation-triangle'=>'Exclamation'];
                                foreach ($fa_icons as $icon => $label): ?>
                                    <option value="<?php echo $icon; ?>"><?php echo $icon; ?> - <?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="margin-top: 0.5rem;">
                                <span style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.8rem; background: #f1f5f9; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem; color: #475569;">
                                    <i id="iconPreview" class="fas fa-circle" style="color: #3b82f6;"></i>
                                    <span id="iconPreviewText">fa-circle</span>
                                </span>
                            </div>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Visibility</label>
                            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #475569; cursor: pointer;">
                                    <input type="checkbox" name="is_sidebar" value="1" checked style="width:16px;height:16px;accent-color:#3b82f6;"> Show in Sidebar
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #475569; cursor: pointer;">
                                    <input type="checkbox" name="is_dashboard" value="1" checked style="width:16px;height:16px;accent-color:#3b82f6;"> Show in Dashboard
                                </label>
                            </div>
                        </div>
                        <div style="grid-column: 1 / -1; display: flex; gap: 0.75rem; padding-top: 0.8rem; border-top: 1px solid #e2e8f0; margin-top: 0.5rem;">
                            <button type="submit" name="add_permission" class="btn btn-success"><i class="fas fa-save"></i> Add Permission</button>
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card">
            <form method="GET" id="filterForm" class="filter-row">
                <div class="field">
                    <label><i class="fas fa-hospital" style="color:#3b82f6;"></i> Hospital</label>
                    <select name="hospital_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">All Hospitals (Global Template)</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?php echo $h['hospital_id']; ?>" <?php echo ($selected_hospital == $h['hospital_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['hospital_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label><i class="fas fa-user-tag" style="color:#3b82f6;"></i> Role</label>
                    <select name="role_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['role_id']; ?>" <?php echo ($selected_role == $r['role_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="flex:0.5; min-width:150px;">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='permissions.php'">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </form>
            <?php if ($selected_hospital > 0): ?>
                <div style="margin-top:0.75rem; display:flex; align-items:center; gap:0.5rem;">
                    <span class="hospital-badge"><i class="fas fa-hospital"></i>
                        <?php foreach ($hospitals as $h) { if ($h['hospital_id'] == $selected_hospital) { echo htmlspecialchars($h['hospital_name']); break; } } ?>
                    </span>
                    <span style="font-size:0.75rem; color:#94a3b8;">(Showing effective permissions: global + hospital-specific)</span>
                </div>
            <?php else: ?>
                <div style="margin-top:0.75rem; display:flex; align-items:center; gap:0.5rem;">
                    <span class="hospital-badge" style="background:#dbeafe; color:#3b82f6;"><i class="fas fa-globe"></i> All Hospitals</span>
                    <span style="font-size:0.75rem; color:#94a3b8;">(Editing global template - applies to ALL hospitals)</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- PERMISSIONS TREE VIEW -->
        <?php if ($selected_role > 0): ?>
            <?php
            $assigned_count = count($role_permissions);
            $total_count = $total_perms > 0 ? $total_perms : 1;
            $percentage = round(($assigned_count / $total_count) * 100);
            $bar_color = $percentage > 80 ? '#22c55e' : ($percentage > 50 ? '#f59e0b' : '#ef4444');
            ?>

            <!-- Role Info -->
            <div class="card" style="padding:0.8rem 1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <span class="role-badge"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($role_name); ?></span>
                        <span style="font-size:0.8rem; color:#64748b;"><?php echo $assigned_count; ?> / <?php echo $total_perms; ?> permissions assigned (<?php echo $percentage; ?>%)</span>
                    </div>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()"><i class="fas fa-check-double"></i> Select All</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()"><i class="fas fa-times"></i> Deselect All</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="expandAll()"><i class="fas fa-expand-alt"></i> Expand All</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="collapseAll()"><i class="fas fa-compress-alt"></i> Collapse All</button>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $bar_color; ?>;"></div>
                </div>
            </div>

            <!-- Permissions Form -->
            <form method="POST" id="permissionForm">
                <input type="hidden" name="hospital_id" value="<?php echo $selected_hospital; ?>">
                <input type="hidden" name="role_id" value="<?php echo $selected_role; ?>">
                <input type="hidden" name="save_permissions" value="1">

                <div class="card" style="padding:0.75rem 1rem;">
                    <div class="tree-container">
                        <?php
                        $group_index = 0;
                        foreach ($all_permissions as $group => $perms):
                            $group_id = 'group-' . $group_index++;
                            $group_assigned = 0;
                            foreach ($perms as $p) {
                                if (in_array($p['permission_id'], $role_permissions)) $group_assigned++;
                            }
                            $count_color = $group_assigned == count($perms) ? '#22c55e' : ($group_assigned > 0 ? '#f59e0b' : '#94a3b8');
                        ?>
                            <div class="tree-item">
                                <!-- MODIFIED: Removed "open" class from chevron and folder-icon changed to fa-folder -->
                                <div class="tree-folder" data-target="<?php echo $group_id; ?>">
                                    <span class="chevron"><i class="fas fa-chevron-right"></i></span>
                                    <span class="folder-icon"><i class="fas fa-folder"></i></span>
                                    <span class="folder-name"><?php echo htmlspecialchars($group); ?></span>
                                    <span class="folder-count" style="color:<?php echo $count_color; ?>;"><?php echo $group_assigned; ?>/<?php echo count($perms); ?></span>
                                    <span class="folder-badge"><?php echo count($perms); ?> items</span>
                                </div>
                                <!-- MODIFIED: Removed "open" class from tree-children -->
                                <div class="tree-children" id="<?php echo $group_id; ?>">
                                    <div class="tree-toolbar">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="selectGroup('<?php echo $group_id; ?>')"><i class="fas fa-check-double"></i> Select All</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="deselectGroup('<?php echo $group_id; ?>')"><i class="fas fa-times"></i> Deselect All</button>
                                    </div>
                                    <?php foreach ($perms as $p): ?>
                                        <div class="tree-file">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $p['permission_id']; ?>"
                                                   id="perm_<?php echo $p['permission_id']; ?>"
                                                   <?php echo in_array($p['permission_id'], $role_permissions) ? 'checked' : ''; ?>>
                                            <label for="perm_<?php echo $p['permission_id']; ?>" style="display:flex; align-items:center; gap:0.4rem; cursor:pointer; width:100%; margin:0; flex:1;">
                                                <?php if (!empty($p['permission_icon'])): ?>
                                                    <i class="fas <?php echo $p['permission_icon']; ?>" style="color:#3b82f6; width:1rem;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-file" style="color:#94a3b8; width:1rem;"></i>
                                                <?php endif; ?>
                                                <span class="file-name"><?php echo htmlspecialchars($p['permission_name']); ?></span>
                                                <span class="file-slug"><?php echo htmlspecialchars($p['permission_slug']); ?></span>
                                            </label>
                                            <?php if (empty($p['is_system'])): ?>
                                                <span class="file-delete" title="Delete" onclick="deletePermission(<?php echo $p['permission_id']; ?>, '<?php echo htmlspecialchars(addslashes($p['permission_name'])); ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sticky-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Permissions</button>
                    <span style="margin-left: 1rem; font-size: 0.8rem; color: #94a3b8;">
                        <i class="fas fa-info-circle"></i> Changes will apply to
                        <?php if ($selected_hospital > 0): ?>
                            <strong><?php echo htmlspecialchars($role_name); ?></strong> role at selected hospital only.
                        <?php else: ?>
                            <strong><?php echo htmlspecialchars($role_name); ?></strong> role across <strong>ALL HOSPITALS</strong> (global template).
                        <?php endif; ?>
                    </span>
                </div>
            </form>
        <?php else: ?>
            <div class="card empty-state">
                <i class="fas fa-hand-pointer"></i>
                <h3>Select a Role</h3>
                <p>Choose a hospital and role from the dropdown above to view and manage permissions.</p>
                <?php if ($total_perms == 0): ?>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 1.2rem;"></i>
                        <strong style="color: #92400e;">No permissions found!</strong>
                        <p style="color: #92400e; font-size: 0.85rem; margin-top: 0.5rem;">Click the <strong>"Seed Default Permissions"</strong> button above to populate the permissions table.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Permission Form (hidden) -->
    <form id="deletePermForm" method="POST" style="display:none;">
        <input type="hidden" name="delete_permission" value="1">
        <input type="hidden" name="permission_id" id="delete_perm_id">
    </form>

    <script>
        function toggleAddForm() {
            const form = document.getElementById('addPermissionForm');
            const icon = document.getElementById('toggleIcon');
            const text = document.getElementById('toggleText');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
                text.textContent = 'Hide Form';
            } else {
                form.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
                text.textContent = 'Show Form';
            }
        }

        function updateIconPreview() {
            const select = document.getElementById('iconSelect');
            const icon = document.getElementById('iconPreview');
            const text = document.getElementById('iconPreviewText');
            const val = select.value;
            icon.className = 'fas ' + val;
            text.textContent = val;
        }

        function validatePermissionForm() {
            const group = document.querySelector('input[name="permission_group"]').value.trim();
            const name = document.querySelector('input[name="permission_name"]').value.trim();
            const slug = document.querySelector('input[name="permission_slug"]').value.trim();

            if (!group || !name || !slug) {
                alert('Please fill in all required fields.');
                return false;
            }

            // Validate slug format (lowercase, hyphens, no spaces)
            const slugPattern = /^[a-z0-9]+(-[a-z0-9]+)*$/;
            if (!slugPattern.test(slug)) {
                alert('Permission slug must be lowercase letters/numbers separated by hyphens only (e.g., dashboard-view).');
                return false;
            }

            return true;
        }

        function deletePermission(permId, permName) {
            if (confirm('Are you sure you want to delete permission "' + permName + '"?\n\nThis will remove it from ALL roles.')) {
                document.getElementById('delete_perm_id').value = permId;
                document.getElementById('deletePermForm').submit();
            }
        }

        // Tree folder toggle
        document.querySelectorAll('.tree-folder').forEach(folder => {
            folder.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const children = document.getElementById(targetId);
                const chevron = this.querySelector('.chevron');
                const folderIcon = this.querySelector('.folder-icon i');

                if (children.classList.contains('open')) {
                    children.classList.remove('open');
                    chevron.classList.remove('open');
                    folderIcon.className = 'fas fa-folder';
                } else {
                    children.classList.add('open');
                    chevron.classList.add('open');
                    folderIcon.className = 'fas fa-folder-open';
                }
            });
        });

        function selectAll() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
            updateGroupCounts();
        }

        function deselectAll() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
            updateGroupCounts();
        }

        function expandAll() {
            document.querySelectorAll('.tree-children').forEach(c => c.classList.add('open'));
            document.querySelectorAll('.chevron').forEach(c => c.classList.add('open'));
            document.querySelectorAll('.folder-icon i').forEach(i => i.className = 'fas fa-folder-open');
        }

        function collapseAll() {
            document.querySelectorAll('.tree-children').forEach(c => c.classList.remove('open'));
            document.querySelectorAll('.chevron').forEach(c => c.classList.remove('open'));
            document.querySelectorAll('.folder-icon i').forEach(i => i.className = 'fas fa-folder');
        }

        function selectGroup(groupId) {
            const container = document.getElementById(groupId);
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
            updateGroupCounts();
        }

        function deselectGroup(groupId) {
            const container = document.getElementById(groupId);
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            updateGroupCounts();
        }

        function updateGroupCounts() {
            document.querySelectorAll('.tree-item').forEach(item => {
                const folder = item.querySelector('.tree-folder');
                const children = item.querySelector('.tree-children');
                if (!folder || !children) return;

                const checkboxes = children.querySelectorAll('input[type="checkbox"]');
                const checked = children.querySelectorAll('input[type="checkbox"]:checked');
                const countSpan = folder.querySelector('.folder-count');

                if (countSpan) {
                    countSpan.textContent = checked.length + '/' + checkboxes.length;
                    if (checked.length === checkboxes.length && checkboxes.length > 0) {
                        countSpan.style.color = '#22c55e';
                    } else if (checked.length > 0) {
                        countSpan.style.color = '#f59e0b';
                    } else {
                        countSpan.style.color = '#94a3b8';
                    }
                }
            });
        }

        // Update counts when checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target && e.target.name === 'permissions[]') {
                updateGroupCounts();
            }
        });

        // Warn before leaving if changes were made
        let formChanged = false;
        document.getElementById('permissionForm')?.addEventListener('change', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Clear flag on form submit
        document.getElementById('permissionForm')?.addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>