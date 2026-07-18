<?php
// ============================================================
// LOGOUT
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// INCLUDE CONFIG FILES WITH CORRECT PATH
// ============================================================
// Going up one level from auth folder
include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// ============================================================
// LOG THE LOGOUT ACTION
// ============================================================
if (isset($_SESSION['id'])) {
    // Check if logAudit function exists
    if (function_exists('logAudit')) {
        logAudit('Logout', 'User logged out');
    }
}

// ============================================================
// DESTROY SESSION
// ============================================================
// Unset all session variables
$_SESSION = array();

// If session cookie exists, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// ============================================================
// REDIRECT TO LOGIN PAGE
// ============================================================
header('Location: ../index.php');
exit();
?>