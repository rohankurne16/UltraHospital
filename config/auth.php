<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary configuration files
include_once 'config/hospital.php'; // Assuming this file contains DB connection and hospital details
include_once 'config/superadmin.php'; // Assuming this file defines SUPER_ADMIN_ROLE or similar

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: index.php'); // Redirect to login page if not logged in
    exit();
}

// Load user role and permissions into session if not already loaded
if (!isset($_SESSION['role_id']) || !isset($_SESSION['permissions'])) {
    // This part will be handled by the login process or a dedicated permission loading function
    // For now, we'll assume these are set upon successful login.
    // We will create a function to load these dynamically later.
}

// Define a constant for the Super Admin role, if not already defined in superadmin.php
if (!defined('SUPER_ADMIN_ROLE')) {
    define('SUPER_ADMIN_ROLE', 'Super Admin'); // Default Super Admin role name
}

// Function to check if the logged-in user has a specific permission
function hasPermission($permission_slug) {
    // Super Admin bypasses all permission checks
    if (isset($_SESSION['role']) && $_SESSION['role'] === SUPER_ADMIN_ROLE) {
        return true;
    }

    // Check if permissions are loaded in the session
    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        // Permissions not loaded, or invalid. For security, deny access.
        // In a real application, you might want to log this or force re-login.
        return false;
    }

    // Check if the permission slug exists in the user's session permissions
    return in_array($permission_slug, $_SESSION['permissions']);
}

// Function to redirect to an access denied page or dashboard
function redirectToAccessDenied() {
    header('Location: 403.php'); // Redirect to a 403 Access Denied page
    exit();
}

// Function to load user permissions from the database and store them in the session
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
            $permissions[] = $row['slug'];
        }
        $stmt->close();
    }
    $_SESSION['permissions'] = $permissions;
}

// Example usage (this would typically be called after successful login)
// if (isset($_SESSION['role_id']) && !isset($_SESSION['permissions'])) {
//     // Assuming $conn is your database connection object available globally or passed
//     // loadUserPermissions($conn, $_SESSION['role_id']);
// }

// Placeholder for database connection. In a real scenario, config/hospital.php would establish $conn.
// For now, let's assume $conn is available globally after including config/hospital.php
// If not, you'll need to adjust how $conn is accessed in loadUserPermissions.

?>
