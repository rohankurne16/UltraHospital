<?php
// ============================================================
// PERMISSION CONFIGURATION & LOGIC (config/permission.php)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

if (!isset($_SESSION['id'])) {
    return;
}

 $user_id = (int)$_SESSION['id'];
 $register_id = $_SESSION['register_id'] ?? $user_id;

// FIX: Initialize variables BEFORE if/else so they're always defined
 $hospital_id = 0;
 $role_id = 0;
 $is_super_admin = false;

if ($user_id == 999) {
    $_SESSION['role'] = 'Super Admin';
    $_SESSION['role_id'] = 1;
    $_SESSION['name'] = 'Super Admin';
    $role_id = 1;
    $is_super_admin = true;
} else {
    $query = "SELECT role_id, hospital_id, name, role
          FROM register
          WHERE id = $register_id
          AND (delete_flag = 0 OR delete_flag IS NULL)";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Query Error: " . mysqli_error($conn));
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        
        $_SESSION['role_id'] = $user_data['role_id'] ?? 0;
        $_SESSION['name']    = $user_data['name'] ?? 'User';
        $_SESSION['role']    = $user_data['role'] ?? 'Guest';
        
        $role_id = (int)($user_data['role_id'] ?? 0);
        $hospital_id = (int)($user_data['hospital_id'] ?? 0);
        $_SESSION['hospital_id'] = $hospital_id;
    } else {
        header('Location: index.php');
        exit;
    }
}

// Get profile image
 $profile_image = '';
if (isset($_SESSION['id'])) {
    $profile_id = $_SESSION['register_id'] ?? $_SESSION['id'];
    $profile_query = "SELECT profile_image FROM admin_profile WHERE register_id = $profile_id AND (delete_flag = 0 OR delete_flag IS NULL)";
    $profile_result = mysqli_query($conn, $profile_query);
    if ($profile_result && mysqli_num_rows($profile_result) > 0) {
        $profile_data = mysqli_fetch_assoc($profile_result);
        $profile_image = $profile_data['profile_image'] ?? '';
    }
}
 $_SESSION['profile_image'] = $profile_image;

 $role = strtolower(trim($_SESSION['role'] ?? ''));
 $is_super_admin = in_array($role, ['super admin', 'superadmin']);

// ============================================================
// FIX: Fetch permission_slug with proper hospital_id handling
// hospital_id = 0 means GLOBAL (applies to all hospitals)
// ============================================================
 $permission_slugs = [];

if ($is_super_admin) {
    // Super Admin gets ALL permission slugs
    $query = "SELECT permission_slug FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group ASC, permission_name ASC";
    $res = mysqli_query($conn, $query);
    if (!$res) die("Permission Query Error: " . mysqli_error($conn));
    while ($row = mysqli_fetch_assoc($res)) {
        $permission_slugs[] = trim($row['permission_slug']);
    }
} elseif ($role_id > 0) {
    // Normal user - get permission slugs from role_permissions
    // FIX: Handle hospital_id = 0 and IS NULL properly
    
    $query = "SELECT DISTINCT p.permission_slug
          FROM role_permissions rp
          INNER JOIN permissions p ON rp.permission_id = p.permission_id
          WHERE rp.role_id = $role_id
          AND (p.delete_flag = 0 OR p.delete_flag IS NULL)";

    if ($hospital_id > 0) {
        // User belongs to a specific hospital - get hospital-specific + global permissions
        $query .= " AND (rp.hospital_id = $hospital_id OR rp.hospital_id = 0 OR rp.hospital_id IS NULL)";
    } else {
        // User has no hospital - get only global permissions
        $query .= " AND (rp.hospital_id = 0 OR rp.hospital_id IS NULL)";
    }

    $query .= " ORDER BY p.permission_group ASC, p.permission_name ASC";

    $res = mysqli_query($conn, $query);
    if (!$res) die("Permission Query Error: " . mysqli_error($conn));
    while ($row = mysqli_fetch_assoc($res)) {
        $permission_slugs[] = trim($row['permission_slug']);
    }
}

// Store BOTH slugs and names for different use cases
 $_SESSION['permissions'] = $permission_slugs;
 $_SESSION['permission_names'] = [];
 $_SESSION['permission_slugs'] = $permission_slugs;

// ============================================================
// PERMISSION CHECK FUNCTIONS
// ============================================================

function hasPerm($permission) {
    global $is_super_admin;
    if ($is_super_admin) return true;
    return in_array($permission, $_SESSION['permissions'] ?? []);
}

function checkPermission($permission_name) { return hasPerm($permission_name); }
function hasPermission($permission_name) { return hasPerm($permission_name); }

function hasAnyPerm($permissions) {
    global $is_super_admin;
    if ($is_super_admin) return true;
    if (!is_array($permissions)) $permissions = [$permissions];
    foreach ($permissions as $perm) {
        if (hasPerm($perm)) return true;
    }
    return false;
}

function checkAnyPermission($permissions) { return hasAnyPerm($permissions); }

function hasAllPerm($permissions) {
    global $is_super_admin;
    if ($is_super_admin) return true;
    if (!is_array($permissions)) $permissions = [$permissions];
    foreach ($permissions as $perm) {
        if (!hasPerm($perm)) return false;
    }
    return true;
}

function checkAllPermissions($permissions) { return hasAllPerm($permissions); }

function requirePermission($permission_name) {
    if (!checkPermission($permission_name)) { 
        header('Location: 403.php'); 
        exit(); 
    }
}

function requireAnyPermission($permissions) {
    if (!checkAnyPermission($permissions)) { 
        header('Location: 403.php'); 
        exit(); 
    }
}

function getDashboardUrl($role) {
    $role = strtolower(trim($role));
    $dashboards = [
        'super admin' => 'superadmin/dashboard.php', 'superadmin' => 'superadmin/dashboard.php',
        'admin' => 'dashboard.php', 'hospital admin' => 'dashboard.php',
        'doctor' => 'doctors/dashboard.php', 'nurse' => 'nurse/dashboard.php',
        'pharmacist' => 'pharmacy/dashboard.php', 'lab technician' => 'lab/dashboard.php',
        'labtechnician' => 'lab/dashboard.php', 'accountant' => 'accounts/dashboard.php',
        'billing staff' => 'billing/dashboard.php', 'billingstaff' => 'billing/dashboard.php',
        'receptionist' => 'staff/reception_dashboard.php', 'ward boy' => 'staff/ward_dashboard.php',
        'wardboy' => 'staff/ward_dashboard.php', 'staff' => 'staff/dashboard.php',
        'patient' => 'patients/dashboard.php'
    ];
    return isset($dashboards[$role]) ? $dashboards[$role] : 'dashboard.php';
}

function checkSuperAdminLogin() {
    global $is_super_admin;
    if (!isset($_SESSION['id'])) { header("Location: ../index.php"); exit(); }
    if (!$is_super_admin) { header("Location: ../dashboard.php"); exit(); }
    return true;
}

function getRolePermissions($role_id, $hospital_id) {
    global $conn;
    $permissions = [];
    $role_id = (int)$role_id;
    $hospital_id = (int)$hospital_id;
    
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM role_permissions LIKE 'hospital_id'");
    $has_hospital_id = mysqli_num_rows($check_column) > 0;
    
    $query = "SELECT DISTINCT p.permission_slug 
              FROM role_permissions rp 
              INNER JOIN permissions p ON rp.permission_id = p.permission_id 
              WHERE rp.role_id = $role_id 
              AND (p.delete_flag = 0 OR p.delete_flag IS NULL)";
    
    if ($has_hospital_id) {
        if ($hospital_id > 0) {
            $query .= " AND (rp.hospital_id = $hospital_id OR rp.hospital_id = 0 OR rp.hospital_id IS NULL)";
        } else {
            $query .= " AND (rp.hospital_id = 0 OR rp.hospital_id IS NULL)";
        }
    }
    
    $query .= " ORDER BY p.permission_group ASC, p.permission_name ASC";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $permissions[] = $row['permission_slug']; 
        }
    }
    return $permissions;
}

function getUserPermissions($user_id) {
    global $conn;
    $register_id = $_SESSION['register_id'] ?? $user_id;
    $register_id = (int)$register_id;
    $permissions = [];
    $query = "SELECT role_id, hospital_id, name, role FROM register WHERE id = $register_id AND (delete_flag = 0 OR delete_flag IS NULL)";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if ($user['role_id'] > 0) { 
            $permissions = getRolePermissions($user['role_id'], $user['hospital_id']); 
        }
    }
    return $permissions;
}

// FIX: saveRolePermissions now uses hospital_id = 0 (global)
function saveRolePermissions($role_id, $permission_ids) {
    global $conn;
    $role_id = (int)$role_id;
    
    // Delete existing global permissions for this role
    mysqli_query($conn, "DELETE FROM role_permissions WHERE role_id = $role_id AND (hospital_id = 0 OR hospital_id IS NULL)");
    
    if (!empty($permission_ids)) {
        foreach ($permission_ids as $permission_id) {
            $permission_id = intval($permission_id);
            if ($permission_id > 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (hospital_id, role_id, permission_id) VALUES (0, $role_id, $permission_id)");
            }
        }
    }
    return true;
}

function logAudit($action_type, $description) {
    global $conn;
    $user_id = (int)($_SESSION['id'] ?? 0);
    $user_name = mysqli_real_escape_string($conn, $_SESSION['name'] ?? 'Unknown');
    $user_role = mysqli_real_escape_string($conn, $_SESSION['role'] ?? 'Unknown');
    $hospital_id = $_SESSION['hospital_id'] ?? null;
    $ip_address = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT'] ?? '');
    
    $browser = 'Unknown';
    if (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
    elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';
    
    if (empty($hospital_id) || $hospital_id == 0) $hospital_id = 'NULL';
    else $hospital_id = "'$hospital_id'";
    
    $action_type = mysqli_real_escape_string($conn, $action_type);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO audit_logs (register_id, user_name, user_role, action_type, description, module, action, hospital_id, ip_address, user_agent, browser, created_at)
              VALUES ($user_id, '$user_name', '$user_role', '$action_type', '$description', '$action_type', '$description', $hospital_id, '$ip_address', '$user_agent', '$browser', NOW())";
    return mysqli_query($conn, $query);
}

function getAllPermissions() {
    global $conn;
    $permissions = [];
    $result = mysqli_query($conn, "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group ASC, permission_name ASC");
    if ($result) { while ($row = mysqli_fetch_assoc($result)) { $permissions[] = $row; } }
    return $permissions;
}

function getPermissionsGrouped() {
    global $conn;
    $grouped = [];
    $result = mysqli_query($conn, "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group ASC, permission_name ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $group = $row['permission_group'] ?? 'General';
            if (!isset($grouped[$group])) $grouped[$group] = [];
            $grouped[$group][] = $row;
        }
    }
    return $grouped;
}

function getSuperAdminPermissionsList() {
    $permissions = getAllPermissions();
    return array_column($permissions, 'permission_slug');
}

// ============================================================
// REDIRECT USERS WITH NO PERMISSIONS (except Super Admin)
// ============================================================
if (empty($permission_slugs) && !$is_super_admin && isset($_SESSION['id'])) {
    $role = strtolower(trim($_SESSION['role']));
    $profile = 'update_adminprofile.php';
    switch ($role) {
        case 'admin': case 'hospital admin': $profile = 'update_adminprofile.php'; break;
        case 'doctor': $profile = 'doctors/doctor_profile.php'; break;
        case 'nurse': $profile = 'nurse/nurse_profile.php'; break;
        case 'ward boy': $profile = 'staff/ward_profile.php'; break;
        case 'lab technician': $profile = 'labtechnician/update_profile.php'; break;
        case 'patient': $profile = 'patients/update_profile.php'; break;
        case 'billing staff': $profile = 'billing/update_profile.php'; break;
        case 'accountant': $profile = 'update_adminprofile.php'; break;
        case 'pharmacist': $profile = 'pharmacy/update_profile.php'; break;
        case 'receptionist': $profile = 'reception_profile.php'; break;
    }
    if (basename($_SERVER['PHP_SELF']) != basename($profile)) {
        if (!headers_sent()) { header("Location: $profile"); exit(); } 
        else { echo "<script>window.location.href='$profile';</script>"; exit(); }
    }
}

// Database Helpers
if (!function_exists('getCount')) {
    function getCount($table, $column = null, $where = null) {
        global $conn;
        if (!isset($conn) || $conn === null) return 0;
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_*.]/', '', $column ?? '*');
        $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'delete_flag'");
        $query = "SELECT COUNT($column) AS total FROM `$table`";
        if ($check && mysqli_num_rows($check) > 0) {
            $query .= " WHERE delete_flag = 0";
            if (!empty($where)) $query .= " AND $where";
        } else if (!empty($where)) {
            $query .= " WHERE $where";
        }
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) return (int)mysqli_fetch_assoc($result)['total'];
        return 0;
    }
}

if (!function_exists('getHospitalLimits')) {
    function getHospitalLimits($hospital_id) {
        global $conn;
        $hospital_id = (int)$hospital_id;
        $result = mysqli_query($conn, "SELECT max_departments, max_doctors, max_staff FROM subscriptions WHERE hospital_id = $hospital_id AND delete_flag = 0 ORDER BY created_at DESC LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) return mysqli_fetch_assoc($result);
        $default = ['max_departments' => 2, 'max_doctors' => 10, 'max_staff' => 10];
        mysqli_query($conn, "INSERT INTO subscriptions (hospital_id, max_departments, max_doctors, max_staff, status, created_at, delete_flag) VALUES ($hospital_id, 2, 10, 10, 'Active', NOW(), 0)");
        return $default;
    }
}

if (!function_exists('checkResourceLimit')) {
    function checkResourceLimit($hospital_id, $resource_type) {
        global $conn; 
        $hospital_id = (int)$hospital_id;
        $limits = getHospitalLimits($hospital_id);
        switch ($resource_type) {
            case 'department': $count_query = "SELECT COUNT(*) as total FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0"; $max = $limits['max_departments']; break;
            case 'doctor': $count_query = "SELECT COUNT(*) as total FROM doctor WHERE hospital_id = $hospital_id AND delete_flag = 0"; $max = $limits['max_doctors']; break;
            case 'staff': $count_query = "SELECT COUNT(*) as total FROM staff WHERE hospital_id = $hospital_id AND delete_flag = 0"; $max = $limits['max_staff']; break;
            default: return false;
        }
        $result = mysqli_query($conn, $count_query); 
        $row = mysqli_fetch_assoc($result);
        return ($row['total'] ?? 0) < $max;
    }
}

if (!function_exists('getLimitMessage')) {
    function getLimitMessage($resource_type) {
        $messages = ['department' => 'departments', 'doctor' => 'doctors', 'staff' => 'staff members'];
        $name = $messages[$resource_type] ?? $resource_type;
        return "You have reached the maximum limit. Please contact the System Administrator to upgrade your plan for <strong>$name</strong>.";
    }
}

if (!function_exists('updateHospitalLimits')) {
    function updateHospitalLimits($hospital_id, $max_departments, $max_doctors, $max_staff) {
        global $conn;
        $hospital_id = (int)$hospital_id;
        $result = mysqli_query($conn, "SELECT subscription_id FROM subscriptions WHERE hospital_id = $hospital_id AND delete_flag = 0 LIMIT 1");
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return mysqli_query($conn, "UPDATE subscriptions SET max_departments = $max_departments, max_doctors = $max_doctors, max_staff = $max_staff, modified_at = NOW() WHERE subscription_id = {$row['subscription_id']}");
        } else {
            return mysqli_query($conn, "INSERT INTO subscriptions (hospital_id, max_departments, max_doctors, max_staff, status, created_at, delete_flag) VALUES ($hospital_id, $max_departments, $max_doctors, $max_staff, 'Active', NOW(), 0)");
        }
    }
}
?>