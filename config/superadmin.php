<?php
// ============================================================
// SUPER ADMIN CONFIGURATION
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include_once __DIR__ . '/hospital.php';
include_once __DIR__ . '/constants.php';

// ============================================================
// AUTHENTICATION FUNCTIONS
// ============================================================

if (!function_exists('checkSuperAdminLogin')) {
    function checkSuperAdminLogin() {
        if (!isset($_SESSION['id'])) {
            header("Location: ../index.php");
            exit();
        }
        if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'SuperAdmin' && strtolower($_SESSION['role']) != 'superadmin')) {
            header("Location: ../index.php");
            exit();
        }
    }
}

if (!function_exists('checkAdminOrSuperAdminLogin')) {
    function checkAdminOrSuperAdminLogin() {
        if (!isset($_SESSION['id'])) {
            header("Location: ../index.php");
            exit();
        }
        $role = strtolower($_SESSION['role'] ?? '');
        if ($role != 'superadmin' && $role != 'admin') {
            header("Location: ../index.php");
            exit();
        }
    }
}

// ============================================================
// PERMISSION FUNCTIONS - FIXED (Admin ला सर्व permissions नाही)
// ============================================================

if (!function_exists('hasPermission')) {
    function hasPermission($permission_slug) {
        // ONLY SuperAdmin has all permissions
        if (isset($_SESSION['role']) && ($_SESSION['role'] == 'SuperAdmin' || strtolower($_SESSION['role']) == 'superadmin')) {
            return true;
        }
        
        // Admin - check session permissions (NO automatic all permissions)
        // येथे Admin ला सर्व permissions दिल्या नाहीत
        if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
            return in_array($permission_slug, $_SESSION['permissions']);
        }
        
        return false;
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission($permissions) {
        // ONLY SuperAdmin has all permissions
        if (isset($_SESSION['role']) && ($_SESSION['role'] == 'SuperAdmin' || strtolower($_SESSION['role']) == 'superadmin')) {
            return true;
        }
        
        // Admin - check session permissions
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

// ============================================================
// LOAD USER PERMISSIONS - FIXED (Admin ला सर्व permissions नाही)
// ============================================================

if (!function_exists('loadUserPermissions')) {
    function loadUserPermissions() {
        // ONLY SuperAdmin gets all permissions
        if (isset($_SESSION['role']) && ($_SESSION['role'] == 'SuperAdmin' || strtolower($_SESSION['role']) == 'superadmin')) {
            $_SESSION['permissions'] = getAllPermissions();
            return;
        }
        
        // Admin gets permissions from database (NOT all)
        // Admin ला सर्व permissions देण्यासाठी खालील comment काढा
        /*
        if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || strtolower($_SESSION['role']) == 'admin')) {
            $_SESSION['permissions'] = getAllPermissions();
            return;
        }
        */
        
        // Get permissions from database for all other roles (including admin)
        if (isset($_SESSION['role_id']) && $_SESSION['role_id'] > 0) {
            $_SESSION['permissions'] = getUserPermissions($_SESSION['role_id']);
        } else {
            $_SESSION['permissions'] = ['dashboard-view'];
        }
    }
}

// ============================================================
// COUNT FUNCTION
// ============================================================

if (!function_exists('getCount')) {
    function getCount($table, $hospital_id = null, $where = '') {
        global $conn;
        $sql = "SELECT COUNT(*) as total FROM $table WHERE delete_flag = 0 OR delete_flag IS NULL";
        if ($hospital_id) {
            $sql .= " AND hospital_id = '$hospital_id'";
        }
        if ($where) {
            $sql .= " AND $where";
        }
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            return 0;
        }
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
}

// ============================================================
// AUDIT LOG FUNCTION
// ============================================================

if (!function_exists('logAudit')) {
    function logAudit($module, $action) {
        global $conn;
        $register_id = $_SESSION['id'] ?? 0;
        $hospital_id = $_SESSION['hospital_id'] ?? 'NULL';
        $module = mysqli_real_escape_string($conn, $module);
        $action = mysqli_real_escape_string($conn, $action);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO audit_logs (hospital_id, register_id, module, action, ip_address, browser) 
                VALUES ($hospital_id, '$register_id', '$module', '$action', '$ip_address', '$browser')";
        return mysqli_query($conn, $sql);
    }
}

// ============================================================
// ROLE PERMISSION FUNCTIONS
// ============================================================

if (!function_exists('getRolePermissions')) {
    function getRolePermissions($role_id) {
        global $conn;
        $sql = "SELECT permission_id FROM role_permissions WHERE role_id = '$role_id'";
        $result = mysqli_query($conn, $sql);
        $permissions = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = $row['permission_id'];
            }
        }
        return $permissions;
    }
}

if (!function_exists('getUserPermissions')) {
    function getUserPermissions($role_id = null) {
        global $conn;
        if ($role_id === null && isset($_SESSION['role_id'])) {
            $role_id = $_SESSION['role_id'];
        }
        if (!$role_id) {
            return [];
        }
        $sql = "SELECT p.permission_slug FROM role_permissions rp 
                INNER JOIN permissions p ON rp.permission_id = p.permission_id 
                WHERE rp.role_id = '$role_id' AND p.delete_flag = 0 
                ORDER BY p.sort_order";
        $result = mysqli_query($conn, $sql);
        $permissions = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = $row['permission_slug'];
            }
        }
        return $permissions;
    }
}

if (!function_exists('getAllPermissions')) {
    function getAllPermissions() {
        global $conn;
        $sql = "SELECT permission_slug FROM permissions WHERE delete_flag = 0";
        $result = mysqli_query($conn, $sql);
        $permissions = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = $row['permission_slug'];
            }
        }
        return $permissions;
    }
}

if (!function_exists('getPermissionsGrouped')) {
    function getPermissionsGrouped() {
        global $conn;
        $sql = "SELECT * FROM permissions WHERE delete_flag = 0 ORDER BY sort_order";
        $result = mysqli_query($conn, $sql);
        $groups = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $group = $row['permission_group'] ?? 'Other';
                $groups[$group][] = $row;
            }
        }
        return $groups;
    }
}

if (!function_exists('saveRolePermissions')) {
    function saveRolePermissions($role_id, $permission_ids) {
        global $conn;
        $role_id = mysqli_real_escape_string($conn, $role_id);
        
        // Delete existing
        $delete_sql = "DELETE FROM role_permissions WHERE role_id = '$role_id'";
        mysqli_query($conn, $delete_sql);
        
        // Insert new
        if (!empty($permission_ids) && is_array($permission_ids)) {
            foreach ($permission_ids as $permission_id) {
                $permission_id = mysqli_real_escape_string($conn, $permission_id);
                $insert_sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES ('$role_id', '$permission_id')";
                mysqli_query($conn, $insert_sql);
            }
        }
        return true;
    }
}

// ============================================================
// LOAD PERMISSIONS IF NOT SET
// ============================================================

if (isset($_SESSION['id']) && !isset($_SESSION['permissions'])) {
    loadUserPermissions();
}
?>