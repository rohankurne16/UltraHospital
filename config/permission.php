<?php
// ============================================================
// PERMISSION CONFIGURATION & LOGIC (config/permission.php)
// ============================================================

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    return;
}

// ============================================================
// GET USER DATA
// ============================================================
$user_id = (int)$_SESSION['id'];

// Super Admin doesn't exist in register table
if ($user_id == 999) {
    $_SESSION['role'] = 'Super Admin';
    $_SESSION['role_id'] = 1;
    $_SESSION['name'] = 'Super Admin';
    $role_id = 1;
    $is_super_admin = true;
} else {
    $query = "SELECT role_id, name, role
              FROM register
              WHERE id = $user_id
              AND (delete_flag = 0 OR delete_flag IS NULL)";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Query Error: " . mysqli_error($conn) . "<br><br>SQL: " . $query);
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        
        $_SESSION['role_id'] = $user_data['role_id'] ?? 0;
        $_SESSION['name']    = $user_data['name'] ?? 'User';
        $_SESSION['role']    = $user_data['role'] ?? 'Guest';
        
        $role_id = $user_data['role_id'] ?? 0;
    } else {
        header('Location: index.php');
        exit;
    }
}

// Get profile image from admin_profile table if exists
$profile_image = '';
if (isset($_SESSION['id'])) {
    $profile_query = "SELECT profile_image FROM admin_profile WHERE register_id = " . $_SESSION['id'] . " AND (delete_flag = 0 OR delete_flag IS NULL)";
    $profile_result = mysqli_query($conn, $profile_query);
    if ($profile_result && mysqli_num_rows($profile_result) > 0) {
        $profile_data = mysqli_fetch_assoc($profile_result);
        $profile_image = $profile_data['profile_image'] ?? '';
    }
}
$_SESSION['profile_image'] = $profile_image;

// ============================================================
// CHECK IF SUPER ADMIN
// ============================================================
$role = strtolower(trim($_SESSION['role'] ?? ''));
$is_super_admin = in_array($role, ['super admin', 'superadmin']);

// ============================================================
// FETCH PERMISSIONS
// ============================================================
$permission_names = [];

if ($is_super_admin) {
    // Super Admin - get ALL permissions
    $query = "SELECT permission_name
              FROM permissions
              WHERE (delete_flag = 0 OR delete_flag IS NULL)
              ORDER BY permission_group ASC, permission_name ASC";
    
    $res = mysqli_query($conn, $query);
    if (!$res) {
        die("Permission Query Error: " . mysqli_error($conn));
    }
    
    while ($row = mysqli_fetch_assoc($res)) {
        $permission_names[] = $row['permission_name'];
    }
} elseif ($role_id > 0) {
    // Normal user - get permissions from role_permissions
    $query = "SELECT p.permission_name
              FROM role_permissions rp
              INNER JOIN permissions p ON rp.permission_id = p.permission_id
              WHERE rp.role_id = $role_id
              AND (p.delete_flag = 0 OR p.delete_flag IS NULL)
              ORDER BY p.permission_group ASC, p.permission_name ASC";

    $res = mysqli_query($conn, $query);
    if (!$res) {
        die("Permission Query Error: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $permission_names[] = $row['permission_name'];
    }
}

$_SESSION['permissions'] = $permission_names;

// ============================================================
// PERMISSION CHECK FUNCTIONS
// ============================================================

/**
 * Check if user has a specific permission
 */
function hasPerm($permission_name) {
    global $is_super_admin;
    
    // Super Admin has all permissions
    if ($is_super_admin) {
        return true;
    }
    
    // Check if permission exists in session
    return isset($_SESSION['permissions']) && in_array($permission_name, $_SESSION['permissions']);
}

function hasPermission($permission_name) {
    return hasPerm($permission_name);
}

/**
 * Check if user has any of the given permissions
 */
function hasAnyPerm($permissions) {
    foreach ($permissions as $perm) {
        if (hasPerm($perm)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the given permissions
 */
function hasAllPerm($permissions) {
    foreach ($permissions as $perm) {
        if (!hasPerm($perm)) {
            return false;
        }
    }
    return true;
}

// ============================================================
// DASHBOARD URL HELPER
// ============================================================

/**
 * Get dashboard URL based on user role
 */
function getDashboardUrl($role) {
    $role = strtolower(trim($role));
    
    $dashboards = [
        'super admin' => 'superadmin/dashboard.php',
        'superadmin' => 'superadmin/dashboard.php',
        'admin' => 'dashboard.php',
        'hospital admin' => 'dashboard.php',
        'doctor' => 'doctors/dashboard.php',
        'nurse' => 'staff/nurse_dashboard.php',
        'pharmacist' => 'pharmacy/dashboard.php',
        'lab technician' => 'lab/dashboard.php',
        'labtechnician' => 'lab/dashboard.php',
        'accountant' => 'accounts/dashboard.php',
        'billing staff' => 'billing/dashboard.php',
        'billingstaff' => 'billing/dashboard.php',
        'receptionist' => 'staff/reception_dashboard.php',
        'ward boy' => 'staff/ward_dashboard.php',
        'wardboy' => 'staff/ward_dashboard.php',
        'staff' => 'staff/dashboard.php',
        'patient' => 'patients/dashboard.php'
    ];
    
    return isset($dashboards[$role]) ? $dashboards[$role] : 'dashboard.php';
}

// ============================================================
// SUPER ADMIN LOGIN CHECK - FIXED
// ============================================================

/**
 * Check if user is Super Admin and redirect if not
 */
function checkSuperAdminLogin() {
    global $is_super_admin;
    
    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        header("Location: ../index.php");
        exit();
    }
    
    // Check if user is Super Admin
    if (!$is_super_admin) {
        header("Location: ../dashboard.php");
        exit();
    }
    
    return true;
}

// ============================================================
// ROLE PERMISSION FUNCTIONS
// ============================================================

/**
 * Get role permissions by role ID
 */
function getRolePermissions($role_id) {
    global $conn;
    
    $permissions = [];
    $query = "SELECT p.permission_name 
              FROM role_permissions rp
              INNER JOIN permissions p ON rp.permission_id = p.permission_id
              WHERE rp.role_id = $role_id
              AND (p.delete_flag = 0 OR p.delete_flag IS NULL)
              ORDER BY p.permission_group ASC, p.permission_name ASC";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row['permission_name'];
        }
    }
    
    return $permissions;
}

/**
 * Get user permissions by user ID
 */
function getUserPermissions($user_id) {
    global $conn;
    
    $permissions = [];
    $query = "SELECT r.role_id, r.name, r.role
              FROM register r
              WHERE r.id = $user_id
              AND (r.delete_flag = 0 OR r.delete_flag IS NULL)";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if ($user['role_id'] > 0) {
            $permissions = getRolePermissions($user['role_id']);
        }
    }
    
    return $permissions;
}

/**
 * Save role permissions
 */
function saveRolePermissions($role_id, $permission_ids) {
    global $conn;
    
    // Delete old permissions
    $delete_query = "DELETE FROM role_permissions WHERE role_id = $role_id";
    mysqli_query($conn, $delete_query);
    
    // Insert new permissions
    if (!empty($permission_ids)) {
        foreach ($permission_ids as $permission_id) {
            $permission_id = intval($permission_id);
            if ($permission_id > 0) {
                $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES ($role_id, $permission_id)";
                mysqli_query($conn, $insert_query);
            }
        }
    }
    
    return true;
}

// ============================================================
// AUDIT LOG FUNCTION
// ============================================================

/**
 * Log audit trail
 */
function logAudit($action_type, $description) {
    global $conn;
    
    $user_id = $_SESSION['id'] ?? 0;
    $user_name = $_SESSION['name'] ?? 'Unknown';
    $user_role = $_SESSION['role'] ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query = "INSERT INTO audit_logs (user_id, user_name, user_role, action_type, description, ip_address, user_agent, created_at) 
              VALUES ('$user_id', '$user_name', '$user_role', '$action_type', '$description', '$ip_address', '$user_agent', NOW())";
    
    return mysqli_query($conn, $query);
}

// ============================================================
// GET ALL PERMISSIONS (for Super Admin)
// ============================================================

/**
 * Get all permissions from database
 */
function getAllPermissions() {
    global $conn;
    
    $permissions = [];
    $query = "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group ASC, permission_name ASC";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row;
        }
    }
    
    return $permissions;
}

/**
 * Get permissions grouped by group
 */
function getPermissionsGrouped() {
    global $conn;
    
    $grouped = [];
    $query = "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group ASC, permission_name ASC";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $group = $row['permission_group'] ?? 'General';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $row;
        }
    }
    
    return $grouped;
}

// ============================================================
// SUPER ADMIN PERMISSIONS LIST
// ============================================================

/**
 * Get all permission names (for Super Admin)
 */
function getSuperAdminPermissionsList() {
    $permissions = getAllPermissions();
    return array_column($permissions, 'permission_name');
}

// ============================================================
// NO PERMISSIONS REDIRECT
// ============================================================

// If no permissions and not Super Admin, redirect to profile page
if (empty($permission_names) && !$is_super_admin && isset($_SESSION['id'])) {
    // Check if current page is not already the profile page to avoid redirect loop
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page != 'update_adminprofile.php' && $current_page != 'profile.php') {
        header('Location: update_adminprofile.php');
        exit;
    }
}

// ============================================================
// DATABASE HELPER FUNCTIONS
// ============================================================

if (!function_exists('getCount')) {
    function getCount($table, $column = null, $where = null) {
        global $conn;
        
        // Check if connection exists
        if (!isset($conn) || $conn === null) {
            return 0;
        }
        
        // Validate table name - prevent SQL injection
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = $column ?? '*';
        $column = preg_replace('/[^a-zA-Z0-9_*.]/', '', $column);
        
        $query = "SELECT COUNT($column) as total FROM `$table` WHERE delete_flag = 0";
        
        if ($where && !empty($where)) {
            $query .= " AND $where";
        }
        
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return (int)($row['total'] ?? 0);
        }
        
        return 0;
    }
}
?>