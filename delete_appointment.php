<?php
session_start();
include "config/hospital.php";
include 'config/permission_check.php';
    checkPermission('appointment-delete'); 

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$redirect_page = isset($_GET['redirect']) ? $_GET['redirect'] : 'view_appointment.php';

if ($id == 0) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid appointment ID!'
    ];
    header("Location: $redirect_page");
    exit();
}

// Get appointment type for proper redirect
$getAppointment = "SELECT opd_ipd_type FROM appointments WHERE appointment_id = '$id'";
$result = mysqli_query($conn, $getAppointment);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $opd_ipd_type = $row['opd_ipd_type'];
    
    if ($opd_ipd_type == 'IPD') {
        $redirect_page = 'show_ipd_appointments.php';
    } elseif ($opd_ipd_type == 'OPD') {
        $redirect_page = 'show_opd_appointments.php';
    }
}

// Soft delete - set delete_flag = 1
$sql = "UPDATE appointments SET delete_flag = 1 WHERE appointment_id = '$id'";

if (mysqli_query($conn, $sql)) {
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Appointment deleted successfully!'
    ];
} else {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Failed to delete appointment: ' . mysqli_error($conn)
    ];
}

header("Location: $redirect_page");
exit();

mysqli_close($conn);
?>