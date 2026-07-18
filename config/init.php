<?php
// ============================================================
// INITIALIZATION FILE
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include constants
include_once __DIR__ . '/constants.php';

// Include database connection
include_once __DIR__ . '/hospital.php';

// Include permission functions
include_once __DIR__ . '/permission_functions.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}

// Load user permissions if not already loaded
if (!isset($_SESSION['permissions'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === SUPER_ADMIN_ROLE) {
        $_SESSION['permissions'] = ['*'];
    } elseif (isset($_SESSION['role_id']) && $_SESSION['role_id'] > 0) {
        global $conn;
        loadUserPermissions($conn, $_SESSION['role_id']);
    }
}

// Set default theme if not set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

// Get user info
$user_name = $_SESSION['name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'Guest';
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$profile_image = $_SESSION['profile_image'] ?? '';
?>