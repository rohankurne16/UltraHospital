<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// Log logout
if (isset($_SESSION['id']) && function_exists('logAudit')) {
    logAudit('Logout', 'User logged out');
}

// ✅ Save hid BEFORE clearing the session
$hid = $_SESSION['hid'] ?? '';

// Clear session
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect
if (!empty($hid)) {
    header("Location: ../index.php?hid=" . urlencode($hid));
} else {
    header("Location: ../index.php");
}
exit();