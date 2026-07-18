<?php 
session_start(); 
include '../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../appointments_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$updateQuery = "UPDATE appointments SET status = 'Confirmed' WHERE appointment_id = '$id'";

if ($conn->query($updateQuery) === TRUE) {
    echo "<script>alert('Appointment confirmed successfully!'); window.location='appointments_list.php';</script>";
} else {
    echo "<script>alert('Error confirming appointment: " . $conn->error . "'); window.location='appointments_list.php';</script>";
}

$conn->close();
?>