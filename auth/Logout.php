<?php
// ============================================================
// OUTPUT BUFFERING - prevent "headers already sent" errors
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
// SUPPRESS NON-FATAL ERRORS DURING LOGOUT
// (we don't want warnings to break the redirect)
// ============================================================
error_reporting(E_ERROR | E_PARSE);

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
 $hid = '';
if (isset($_GET['hid']) && $_GET['hid'] !== '') {
    $hid = trim($_GET['hid']);
} elseif (isset($_SESSION['hid']) && $_SESSION['hid'] !== '') {
    $hid = trim($_SESSION['hid']);
} elseif (isset($_SESSION['hospital_id']) && $_SESSION['hospital_id'] !== '') {
    $hid = trim($_SESSION['hospital_id']);
}

// ============================================================
// UPDATE LOGOUT TIME
// ============================================================
 $logoutUpdated = false;

if ($register_id > 0 && isset($conn) && $conn instanceof mysqli) {

    // --------------------------------------------------------
    // CASE 1: Update by login_id + register_id
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
            mysqli_stmt_bind_param($stmt, "ii", $login_id, $register_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_affected_rows($conn) > 0) {
                $logoutUpdated = true;
            }
            mysqli_stmt_close($stmt);
        }
    }

    // --------------------------------------------------------
    // CASE 2: Fallback by session_id + register_id
    // --------------------------------------------------------
    if (!$logoutUpdated && $session_id !== '') {
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
            mysqli_stmt_bind_param($stmt, "is", $register_id, $session_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_affected_rows($conn) > 0) {
                $logoutUpdated = true;
            }
            mysqli_stmt_close($stmt);
        }
    }

    // --------------------------------------------------------
    // CASE 3: Final fallback - latest active login only
    // (Runs ONLY if CASE 1 and CASE 2 both failed)
    // --------------------------------------------------------
    if (!$logoutUpdated) {
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
            mysqli_stmt_bind_param($stmt, "i", $register_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// ============================================================
// AUDIT LOG
// ============================================================
if ($register_id > 0 && function_exists('logAudit')) {
    try {
        logAudit('Logout', 'User logged out');
    } catch (Throwable $e) {
        // Silent fail - logout must continue
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
// BUILD RELATIVE REDIRECT URL (Safest for your setup)
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
    // Headers already sent - use HTML/JS fallback
    echo '<!DOCTYPE html><html><head>';
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES) . '">';
    echo '<script>window.location.href="' . htmlspecialchars($redirect_url, ENT_QUOTES) . '";</script>';
    echo '</head><body>Redirecting…</body></html>';
}
exit();
?>