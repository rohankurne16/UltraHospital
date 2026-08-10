<?php

// ============================================================
// START SESSION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// LOAD CONFIGURATION
// ============================================================
include_once '../config/hospital.php';
include_once '../config/permission.php';


// ============================================================
// SAVE REQUIRED SESSION DATA BEFORE DESTROYING SESSION
// ============================================================
$register_id = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
$login_id    = isset($_SESSION['login_id']) ? (int) $_SESSION['login_id'] : 0;
$session_id  = session_id();


// ============================================================
// GET HOSPITAL ID
// ============================================================
// First try GET parameter
$hid = isset($_GET['hid']) ? trim($_GET['hid']) : '';


// If hid is not present in URL, try session
if (empty($hid) && isset($_SESSION['hid'])) {
    $hid = trim($_SESSION['hid']);
}


// If your project uses hospital_id instead of hid
if (empty($hid) && isset($_SESSION['hospital_id'])) {
    $hid = trim($_SESSION['hospital_id']);
}


// ============================================================
// UPDATE LOGOUT TIME
// ============================================================
if ($register_id > 0 && isset($conn) && $conn) {

    // --------------------------------------------------------
    // CASE 1: login_id is available
    // --------------------------------------------------------
    if ($login_id > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE login_logs
             SET logout_time = NOW()
             WHERE login_id = ?
             AND register_id = ?
             AND logout_time IS NULL
             LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $login_id,
                $register_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }


    // --------------------------------------------------------
    // CASE 2: login_id unavailable OR no row updated
    // Use session_id
    // --------------------------------------------------------
    if ($login_id <= 0 || mysqli_affected_rows($conn) == 0) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE login_logs
             SET logout_time = NOW()
             WHERE register_id = ?
             AND session_id = ?
             AND logout_time IS NULL
             LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "is",
                $register_id,
                $session_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }


    // --------------------------------------------------------
    // CASE 3: Final fallback
    // Update latest active login for this user
    // --------------------------------------------------------
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE login_logs
         SET logout_time = NOW()
         WHERE register_id = ?
         AND logout_time IS NULL
         ORDER BY login_time DESC
         LIMIT 1"
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $register_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}


// ============================================================
// AUDIT LOG
// ============================================================
if ($register_id > 0 && function_exists('logAudit')) {

    try {
        logAudit(
            'Logout',
            'User logged out'
        );
    } catch (Throwable $e) {
        // Do not stop logout if audit logging fails
    }
}


// ============================================================
// CLEAR SESSION VARIABLES
// ============================================================
$_SESSION = array();


// ============================================================
// DELETE SESSION COOKIE
// ============================================================
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}


// ============================================================
// DESTROY SESSION
// ============================================================
session_destroy();


// ============================================================
// PREVENT BROWSER CACHE
// ============================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');


// ============================================================
// REDIRECT TO LOGIN PAGE
// ============================================================
if (!empty($hid)) {

    $redirect_url = '../index.php?hid=' . urlencode($hid);

} else {

    $redirect_url = '../index.php';
}


// ============================================================
// SERVER-SIDE REDIRECT
// ============================================================
header('Location: ' . $redirect_url, true, 302);
exit();

?>