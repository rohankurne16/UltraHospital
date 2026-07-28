<?php
session_start();
include '../config/hospital.php';

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

// Get appointment ID from GET (supports both 'id' and 'appointment_id')
$appointment_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0);

if ($appointment_id == 0) {
    $_SESSION['error_message'] = "No appointment selected.";
    header("Location: appointments.php");
    exit();
}

$doctor_reg_id = (int) $_SESSION['id'];
$hospital_id   = (int) $_SESSION['hospital_id'];

// Get the doctor's ID from the doctor table
$getDoctor = mysqli_query($conn, "SELECT doctor_id FROM doctor WHERE register_id = '$doctor_reg_id' AND hospital_id = '$hospital_id' AND (delete_flag = 0 OR delete_flag IS NULL)");
if (!$getDoctor || mysqli_num_rows($getDoctor) == 0) {
    $_SESSION['error_message'] = "Doctor not found.";
    header("Location: appointments.php");
    exit();
}
$doctor = mysqli_fetch_assoc($getDoctor);
$doctor_id = (int) $doctor['doctor_id'];

// Verify that this appointment belongs to this doctor and this hospital
$verifySql = "SELECT * FROM appointments 
              WHERE appointment_id = '$appointment_id' 
                AND doctor_id = '$doctor_id' 
                AND hospital_id = '$hospital_id'
                AND (delete_flag = 0 OR delete_flag IS NULL)";
$verifyResult = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($verifyResult) == 0) {
    $_SESSION['error_message'] = "Appointment not found or you don't have permission.";
    header("Location: appointments.php");
    exit();
}

// Soft delete: set delete_flag = 1 instead of permanently deleting
$updateSql = "UPDATE appointments SET delete_flag = 1 WHERE appointment_id = '$appointment_id'";
if (mysqli_query($conn, $updateSql)) {
    $_SESSION['success_message'] = "Appointment deleted successfully!";
} else {
    $_SESSION['error_message'] = "Error deleting appointment: " . mysqli_error($conn);
}

// Redirect back to the appointments list (adjust the page name if needed)
header("Location: appointments.php");
exit();

mysqli_close($conn);
?>