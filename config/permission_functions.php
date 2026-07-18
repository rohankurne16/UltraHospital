<?php
// ============================================================
// PERMISSION FUNCTIONS
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include constants
include_once __DIR__ . '/constants.php';

// Include hospital config for database connection
include_once __DIR__ . '/hospital.php';

/**
 * Check if user has a specific permission
 */
function hasPermission($permission_slug) {
    // Super Admin bypasses all permission checks
    if (isset($_SESSION['role']) && $_SESSION['role'] === SUPER_ADMIN_ROLE) {
        return true;
    }
    
    // Admin bypasses all permission checks (optional)
    if (isset($_SESSION['role']) && $_SESSION['role'] === ADMIN_ROLE) {
        return true;
    }

    // Check if permissions are loaded in the session
    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        return false;
    }

    // Check if the permission slug exists
    return in_array($permission_slug, $_SESSION['permissions']);
}

/**
 * Check if user has any of the given permissions
 */
function hasAnyPermission($permissions) {
    // Super Admin bypasses all permission checks
    if (isset($_SESSION['role']) && $_SESSION['role'] === SUPER_ADMIN_ROLE) {
        return true;
    }
    
    // Admin bypasses all permission checks (optional)
    if (isset($_SESSION['role']) && $_SESSION['role'] === ADMIN_ROLE) {
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

/**
 * Load user permissions from database
 */
function loadUserPermissions($conn, $role_id) {
    $permissions = [];
    if ($role_id) {
        $stmt = $conn->prepare("
            SELECT p.permission_slug
            FROM permissions p
            JOIN role_permissions rp ON p.permission_id = rp.permission_id
            WHERE rp.role_id = ? AND p.delete_flag = 0
        ");
        if ($stmt) {
            $stmt->bind_param("i", $role_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $permissions[] = $row['permission_slug'];
            }
            $stmt->close();
        }
    }
    $_SESSION['permissions'] = $permissions;
}

/**
 * Redirect to access denied page
 */
function redirectToAccessDenied() {
    header('Location: 403.php');
    exit();
}

/**
 * Log audit event
 */
function logAudit($conn, $module, $action) {
    $register_id = $_SESSION['id'] ?? 0;
    $hospital_id = $_SESSION['hospital_id'] ?? 'NULL';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $module = mysqli_real_escape_string($conn, $module);
    $action = mysqli_real_escape_string($conn, $action);
    
    $sql = "INSERT INTO audit_logs (hospital_id, register_id, module, action, ip_address, browser) 
            VALUES ($hospital_id, '$register_id', '$module', '$action', '$ip_address', '$browser')";
    return mysqli_query($conn, $sql);
}
?>