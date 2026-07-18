<?php
// Ensure database connection is available. Assumed to be established in config/hospital.php
// global $conn; // Uncomment if $conn is not globally accessible after inclusion

// Define a constant for the Super Admin role, if not already defined
if (!defined("SUPER_ADMIN_ROLE")) {
    define("SUPER_ADMIN_ROLE", "Super Admin"); // Default Super Admin role name
}

/**
 * Checks if the logged-in user has a specific permission.
 * Super Admin bypasses all permission checks.
 *
 * @param string $permission_slug The slug of the permission to check (e.g., 'dashboard-view').
 * @return bool True if the user has the permission or is a Super Admin, false otherwise.
 */
function hasPermission($permission_slug) {
    // Super Admin bypasses all permission checks
    if (isset($_SESSION["role"]) && $_SESSION["role"] === SUPER_ADMIN_ROLE) {
        return true;
    }

    // Check if permissions are loaded in the session
    if (!isset($_SESSION["permissions"]) || !is_array($_SESSION["permissions"])) {
        // Permissions not loaded, or invalid. For security, deny access.
        // In a real application, you might want to log this or force re-login.
        return false;
    }

    // Check if the permission slug exists in the user's session permissions
    return in_array($permission_slug, $_SESSION["permissions"]);
}

/**
 * Loads user permissions from the database for a given role ID and stores them in the session.
 *
 * @param mysqli $conn The database connection object.
 * @param int $role_id The ID of the user's role.
 */
function loadUserPermissions($conn, $role_id) {
    $permissions = [];
    if ($role_id) {
        $stmt = $conn->prepare("
            SELECT p.slug
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ?
        ");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row["slug"];
        }
        $stmt->close();
    }
    $_SESSION["permissions"] = $permissions;
}

/**
 * Redirects to an access denied page (403).
 */
function redirectToAccessDenied() {
    header("Location: 403.php"); // Redirect to a 403 Access Denied page
    exit();
}

/**
 * Logs an audit event to the database.
 *
 * @param mysqli $conn The database connection object.
 * @param string $action The action performed (e.g., 'Login', 'Create', 'Update').
 * @param string $description A description of the audit event.
 */
function logAudit($conn, $action, $description) {
    if (isset($_SESSION["id"])) {
        $user_id = $_SESSION["id"];
        $timestamp = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $action, $description, $timestamp);
        $stmt->execute();
        $stmt->close();
    }
}

?>
