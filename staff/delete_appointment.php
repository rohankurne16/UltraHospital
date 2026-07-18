<?php 
session_start(); 
include '../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: appointments_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$deleteQuery = "UPDATE appointments SET delete_flag = 1 WHERE appointment_id = '$id'";

if ($conn->query($deleteQuery) === TRUE) {
    echo "<script>alert('Appointment deleted successfully!'); window.location='appointments_list.php';</script>";
} else {
    echo "<script>alert('Error deleting appointment: " . $conn->error . "'); window.location='appointments_list.php';</script>";
}

$conn->close();
?>