<?php
session_start();
include "config/db.php";

include 'config/permission_check.php';
    checkPermission('appointment-edit'); 

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

// Get appointment type
$getAppointment = "SELECT opd_ipd_type FROM appointments WHERE appointment_id = '$id'";
$result = mysqli_query($conn, $getAppointment);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $opd_ipd_type = $row['opd_ipd_type'];
    
    if ($opd_ipd_type == 'IPD') {
        $redirect_page = 'view_ipd_appointments.php';
    } elseif ($opd_ipd_type == 'OPD') {
        $redirect_page = 'view_opd_appointments.php';
    }
}

$sql = "UPDATE appointments SET status = 'Confirmed' WHERE appointment_id = '$id'";

if (mysqli_query($conn, $sql)) {
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Appointment confirmed successfully!'
    ];
} else {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Failed to confirm appointment: ' . mysqli_error($conn)
    ];
}

header("Location: $redirect_page");
exit();
?>