<?php
session_start();
include '../config/superadmin.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$total_hospitals = getCount('hospital_master');
$active_hospitals = getCount('hospital_master', null, "status = 'Active'");
$inactive_hospitals = getCount('hospital_master', null, "status = 'Inactive'");

echo json_encode([
    'success' => true,
    'total' => $total_hospitals,
    'active' => $active_hospitals,
    'inactive' => $inactive_hospitals
]);
?>