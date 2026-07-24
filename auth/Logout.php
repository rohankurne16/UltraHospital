<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../config/hospital.php';
include_once '../config/permission.php';

// Log logout if function exists
if (isset($_SESSION['id']) && function_exists('logAudit')) {
    logAudit('Logout', 'User logged out');
}

// Save hospital ID before clearing session
$hid = $_SESSION['hid'] ?? '';

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// ** Prevent caching of logged‑in pages **
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect to index.php (preserve hospital ID if available)
if (!empty($hid)) {
    header("Location: ../index.php?hid=" . urlencode($hid));
} else {
    header("Location: ../index.php");
}
exit();