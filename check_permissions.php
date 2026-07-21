<?php
// ============================================================
// CHECK PERMISSIONS (AJAX ENDPOINT)
// ============================================================

session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['has_permissions' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if user has any permissions
$has_permissions = !empty($_SESSION['permissions']) && is_array($_SESSION['permissions']);

// Return JSON response
echo json_encode(['has_permissions' => $has_permissions]);
?>