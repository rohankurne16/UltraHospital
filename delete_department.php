<?php
session_start();
include 'config/hospital.php';

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../auth/logout.php");
    exit();
}

// Get department ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: departments.php?error=missing_id");
    exit();
}

$dept_id = (int) $_GET['id'];
$hospital_id = (int) $_SESSION['hospital_id'];

// Verify that the department belongs to this hospital and is not already deleted
$check_query = "SELECT id FROM department 
                WHERE id = $dept_id 
                AND hospital_id = $hospital_id 
                AND delete_flag = 0";
$check_result = $conn->query($check_query);

if ($check_result->num_rows === 0) {
    header("Location: departments.php?error=not_found");
    exit();
}

// Perform soft delete: set delete_flag = 1
$update_query = "UPDATE department 
                 SET delete_flag = 1 
                 WHERE id = $dept_id 
                 AND hospital_id = $hospital_id";

if ($conn->query($update_query) === TRUE) {
    // Optionally log the action
    // logAudit('Department', "Soft deleted department ID $dept_id");
    header("Location: departments.php?success=deleted");
} else {
    header("Location: departments.php?error=delete_failed");
}
exit();
?>