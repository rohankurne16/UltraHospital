<?php
// ============================================================
// FATAL ERROR HANDLER (Prevents HTTP 500 on logout)
// ============================================================
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        // Fallback redirect if a fatal error occurs
        $hid = isset($_GET['hid']) ? $_GET['hid'] : '';
        $url = '../index.php' . (!empty($hid) ? '?hid=' . urlencode($hid) : '');
        if (!headers_sent()) {
            header("Location: $url");
        } else {
            echo "<script>window.location.href='$url';</script>";
        }
        exit();
    }
});

// ============================================================
// OUTPUT BUFFERING
// ============================================================
if (ob_get_level() === 0) {
    ob_start();
}

// ============================================================
// START SESSION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// LOAD CONFIGURATION
// Suppress warnings in case files have whitespace issues
// ============================================================
@include_once '../config/hospital.php';
@include_once '../config/permission.php';

// ============================================================
// SAVE REQUIRED SESSION DATA BEFORE DESTROYING SESSION
// ============================================================
 $register_id = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
 $login_id    = isset($_SESSION['login_id']) ? (int) $_SESSION['login_id'] : 0;
 $session_id  = session_id();

// ============================================================
// GET HOSPITAL ID
// Always prefer the encrypted string from URL or Session
// ============================================================
 $hid = '';
if (isset($_GET['hid']) && $_GET['hid'] !== '') {
    $hid = trim($_GET['hid']);
} elseif (isset($_SESSION['hid']) && $_SESSION['hid'] !== '') {
    $hid = trim($_SESSION['hid']);
}

// ============================================================
// UPDATE LOGOUT TIME
// Wrapped in try-catch to prevent DB errors from breaking logout
// ============================================================
 $logoutUpdated = false;

if ($register_id > 0 && isset($conn) && $conn instanceof mysqli) {
    try {
        // CASE 1: Update by login_id
        if ($login_id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE login_logs SET logout_time = NOW() WHERE login_id = ? AND register_id = ? AND logout_time IS NULL LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $login_id, $register_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_affected_rows($conn) > 0) { $logoutUpdated = true; }
                mysqli_stmt_close($stmt);
            }
        }

        // CASE 2: Fallback by session_id
        if (!$logoutUpdated && $session_id !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE login_logs SET logout_time = NOW() WHERE register_id = ? AND session_id = ? AND logout_time IS NULL LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "is", $register_id, $session_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_affected_rows($conn) > 0) { $logoutUpdated = true; }
                mysqli_stmt_close($stmt);
            }
        }

        // CASE 3: Final fallback
        if (!$logoutUpdated) {
            $stmt = mysqli_prepare($conn, "UPDATE login_logs SET logout_time = NOW() WHERE register_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $register_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    } catch (Throwable $e) {
        // Do nothing, proceed to logout
    }
}

// ============================================================
// AUDIT LOG
// ============================================================
if ($register_id > 0 && function_exists('logAudit')) {
    try {
        logAudit('Logout', 'User logged out');
    } catch (Throwable $e) {
        // Do nothing
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
@session_destroy();

// ============================================================
// PREVENT BROWSER CACHE
// ============================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Clear-Site-Data: "cache", "cookies", "storage"');

// ============================================================
// BUILD REDIRECT URL
// ============================================================
 $redirect_url = '../index.php';
if ($hid !== '') {
    $redirect_url .= '?hid=' . urlencode($hid);
}

// ============================================================
// FLUSH OUTPUT BUFFER
// ============================================================
if (ob_get_level() > 0) {
    ob_end_clean();
}

// ============================================================
// REDIRECT - Server-side with JS fallback
// ============================================================
if (!headers_sent()) {
    header('Location: ' . $redirect_url, true, 302);
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES) . '">';
    echo '<script>window.location.href="' . htmlspecialchars($redirect_url, ENT_QUOTES) . '";</script>';
    echo '</head><body>Redirecting…</body></html>';
} else {
    echo '<!DOCTYPE html><html><head>';
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES) . '">';
    echo '<script>window.location.href="' . htmlspecialchars($redirect_url, ENT_QUOTES) . '";</script>';
    echo '</head><body>Redirecting…</body></html>';
}
exit();
?>