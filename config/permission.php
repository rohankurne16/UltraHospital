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
$register_id = $_SESSION['register_id'] ?? $user_id;

// Super Admin doesn't exist in register table
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
        die("Query Error: " . mysqli_error($conn) . "<br><br>SQL: " . $query);
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        
        $_SESSION['role_id'] = $user_data['role_id'] ?? 0;
        $_SESSION['name']    = $user_data['name'] ?? 'User';
        $_SESSION['role']    = $user_data['role'] ?? 'Guest';
        
        $role_id = $user_data['role_id'] ?? 0;
        $hospital_id = $user_data['hospital_id'] ?? 0;
        $_SESSION['hospital_id'] = $hospital_id;
    } else {
        header('Location: index.php');
        exit;
    }
}

// Get profile image from admin_profile table if exists
$profile_image = '';
if (isset($_SESSION['id'])) {
    $profile_id = $_SESSION['register_id'] ?? $_SESSION['id'];

$profile_query = "SELECT profile_image
                  FROM admin_profile
                  WHERE register_id = $profile_id
                  AND (delete_flag = 0 OR delete_flag IS NULL)";
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
       $permission_names[] = trim($row['permission_name']);
    }
} elseif ($role_id > 0) {
    // Normal user - get permissions from role_permissions
   $query = "SELECT DISTINCT p.permission_name
          FROM role_permissions rp
          INNER JOIN permissions p
              ON rp.permission_id = p.permission_id
          WHERE rp.role_id = $role_id
          AND rp.hospital_id = $hospital_id
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
function hasPerm($permission) {
    global $is_super_admin;
    
    // Super Admin has all permissions
    if ($is_super_admin) {
        return true;
    }
    
    return in_array($permission, $_SESSION['permissions'] ?? []);
}

/**
 * Alias for hasPerm - For backward compatibility
 */
function checkPermission($permission_name) {
    return hasPerm($permission_name);
}

/**
 * Alias for hasPermission - For backward compatibility
 */
function hasPermission($permission_name) {
    return hasPerm($permission_name);
}

/**
 * Check if user has any of the given permissions
 */
function hasAnyPerm($permissions) {
    global $is_super_admin;
    
    // Super Admin has all permissions
    if ($is_super_admin) {
        return true;
    }
    
    foreach ($permissions as $perm) {
        if (hasPerm($perm)) {
            return true;
        }
    }
    return false;
}

/**
 * Alias for hasAnyPerm - For backward compatibility
 */
function checkAnyPermission($permissions) {
    return hasAnyPerm($permissions);
}

/**
 * Check if user has all of the given permissions
 */
function hasAllPerm($permissions) {
    global $is_super_admin;
    
    // Super Admin has all permissions
    if ($is_super_admin) {
        return true;
    }
    
    foreach ($permissions as $perm) {
        if (!hasPerm($perm)) {
            return false;
        }
    }
    return true;
}

/**
 * Alias for hasAllPerm - For backward compatibility
 */
function checkAllPermissions($permissions) {
    return hasAllPerm($permissions);
}

/**
 * Redirect to access denied page if no permission
 */
function requirePermission($permission_name) {
    if (!checkPermission($permission_name)) {
        header('Location: 403.php');
        exit();
    }
}

/**
 * Redirect to access denied page if any permission is missing
 */
function requireAnyPermission($permissions) {
    if (!checkAnyPermission($permissions)) {
        header('Location: 403.php');
        exit();
    }
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
function getRolePermissions($role_id, $hospital_id) {
    global $conn;
    
    $permissions = [];
    
    // First, check if hospital_id column exists in role_permissions table
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM role_permissions LIKE 'hospital_id'");
    $has_hospital_id = mysqli_num_rows($check_column) > 0;
    
    // Build the query
    $query = "SELECT DISTINCT p.permission_name
              FROM role_permissions rp
              INNER JOIN permissions p ON rp.permission_id = p.permission_id
              WHERE rp.role_id = $role_id";
    
    // Only add hospital_id condition if column exists and hospital_id is provided
    if ($has_hospital_id && !empty($hospital_id) && $hospital_id > 0) {
        $query .= " AND rp.hospital_id = $hospital_id";
    } elseif ($has_hospital_id && (empty($hospital_id) || $hospital_id == 0)) {
        // If hospital_id is NULL or 0, check for NULL or 0 in the database
        $query .= " AND (rp.hospital_id = 0 OR rp.hospital_id IS NULL)";
    }
    
    $query .= " AND (p.delete_flag = 0 OR p.delete_flag IS NULL)
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

    $register_id = $_SESSION['register_id'] ?? $user_id;

    $permissions = [];

    $query = "SELECT role_id, hospital_id, name, role
              FROM register
              WHERE id = $register_id
              AND (delete_flag = 0 OR delete_flag IS NULL)";

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if ($user['role_id'] > 0) {
            $permissions = getRolePermissions($user['role_id'], $user['hospital_id']);
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
 * Log audit trail with hospital_id
 */
function logAudit($action_type, $description) {
    global $conn;
    
    $user_id = $_SESSION['id'] ?? 0;
    $user_name = $_SESSION['name'] ?? 'Unknown';
    $user_role = $_SESSION['role'] ?? 'Unknown';
    $hospital_id = $_SESSION['hospital_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Get browser from user_agent
    $browser = 'Unknown';
    if (strpos($user_agent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'Opera') !== false) {
        $browser = 'Opera';
    }
    
    // If hospital_id is null or 0, set to NULL
    if (empty($hospital_id) || $hospital_id == 0) {
        $hospital_id = 'NULL';
    } else {
        $hospital_id = "'$hospital_id'";
    }
    
    $query = "INSERT INTO audit_logs (
                register_id, 
                user_name, 
                user_role, 
                action_type, 
                description, 
                module, 
                action, 
                hospital_id, 
                ip_address, 
                user_agent, 
                browser, 
                created_at
              ) VALUES (
                '$user_id', 
                '$user_name', 
                '$user_role', 
                '$action_type', 
                '$description', 
                '$action_type', 
                '$description', 
                $hospital_id, 
                '$ip_address', 
                '$user_agent', 
                '$browser', 
                NOW()
              )";
    
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


if (empty($permission_names) && !$is_super_admin && isset($_SESSION['id'])) {

    $role = strtolower(trim($_SESSION['role']));
    $profile = 'update_adminprofile.php'; // default

    switch ($role) {
        case 'admin':
        case 'hospital admin':
            $profile = 'update_adminprofile.php';
            break;

        case 'doctor':
            $profile = 'doctors/doctor_profile.php';
            break;

        case 'nurse':
            $profile = 'staff/nurse_profile.php';
            break;

        case 'ward boy':
            $profile = 'staff/ward_profile.php';
            break;

        case 'lab technician':
            $profile = 'labtechnician/update_profile.php';
            break;

        case 'patient':
            $profile = 'patients/update_profile.php';
            break;

        case 'billing staff':
            $profile = 'billing/update_profile.php';
            break;

        case 'accountant':
            $profile = 'accounts/update_profile.php';
            break;

        case 'pharmacist':
            $profile = 'pharmacy/update_profile.php';
            break;

        case 'staff':
            $profile = 'staff/update_profile.php';
            break;

        case 'receptionist':
            $profile = 'staff/reception_profile.php';
            break;
    }

    if (basename($_SERVER['PHP_SELF']) != basename($profile)) {
        header("Location: $profile");
        exit();
    }
}

  

// ============================================================
// DATABASE HELPER FUNCTIONS
// ============================================================

if (!function_exists('getCount')) {
    function getCount($table, $column = null, $where = null) {
        global $conn;

        if (!isset($conn) || $conn === null) {
            return 0;
        }

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = $column ?? '*';
        $column = preg_replace('/[^a-zA-Z0-9_*.]/', '', $column);

        // Check if delete_flag exists
        $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'delete_flag'");

        $query = "SELECT COUNT($column) AS total FROM `$table`";

        if ($check && mysqli_num_rows($check) > 0) {
            $query .= " WHERE delete_flag = 0";

            if (!empty($where)) {
                $query .= " AND $where";
            }
        } else {
            if (!empty($where)) {
                $query .= " WHERE $where";
            }
        }

        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return (int)$row['total'];
        }

        return 0;
    }
}
?>