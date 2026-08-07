<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../config/hospital.php';
include_once '../config/permission.php';

// ============================================================
// ADDED: Update logout_time in login_logs
// ============================================================
if (isset($_SESSION['id'])) {
    $register_id = mysqli_real_escape_string($conn, $_SESSION['id']);
    $current_session_id = mysqli_real_escape_string($conn, session_id());

    // FIX 1: Use !empty() instead of isset() — isset(0) is true but login_id=0 is invalid
    if (!empty($_SESSION['login_id'])) {
        $login_id = intval($_SESSION['login_id']);
        // FIX 2: Also match register_id as a safety guard to prevent cross-user update
        $update = "UPDATE login_logs SET logout_time = NOW() WHERE login_id = $login_id AND register_id = '$register_id'";
        mysqli_query($conn, $update);
    } else {
        // Fallback: Try session_id first for precise matching (if session_id column exists)
        $update_with_session = "UPDATE login_logs 
                   SET logout_time = NOW() 
                   WHERE register_id = '$register_id' 
                     AND session_id = '$current_session_id'
                     AND logout_time IS NULL 
                   ORDER BY login_time DESC 
                   LIMIT 1";
        $result = mysqli_query($conn, $update_with_session);

        // FIX 3: If session_id column doesn't exist or no row was affected, use register_id only
        if (!$result || mysqli_affected_rows($conn) == 0) {
            $update = "UPDATE login_logs 
                       SET logout_time = NOW() 
                       WHERE register_id = '$register_id' 
                         AND logout_time IS NULL 
                       ORDER BY login_time DESC 
                       LIMIT 1";
            mysqli_query($conn, $update);
        }
    }
}
// ============================================================

// Log logout if function exists
if (isset($_SESSION['id']) && function_exists('logAudit')) {
    logAudit('Logout', 'User logged out');
}

// Save hospital ID before clearing session
 $hid = $_GET['hid'] ?? '';

// Clear all session variables
 $_SESSION = array();

// Delete the session cookie
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

// Destroy the session
session_destroy();

// ============================================================
// FIX: Prevent browser back button from showing cached pages
// ============================================================
 $redirect_url = !empty($hid) ? "../index.php?hid=" . urlencode($hid) : "../index.php";

// Send no-cache headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Output a page that replaces the current history entry and redirects
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="refresh" content="0;url=' . $redirect_url . '">
    <script>
        // Replace the current history entry to prevent back button
        window.location.replace("' . $redirect_url . '");
    </script>
    <title>Logging out...</title>
</head>
<body>
    <p>Logging out, please wait...</p>
</body>
</html>';
exit();
?>