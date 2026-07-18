<?php
session_start();
include '../config/hospital.php';

if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}

if(!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

if(isset($_GET['id'])) {
    $opd_id = mysqli_real_escape_string($conn, $_GET['id']);
    $doctor_reg_id = $_SESSION['id'];
    
    $getDoctor = "SELECT doctor_id FROM doctor WHERE register_id='$doctor_reg_id'";
    $all_doctor_info = $conn->query($getDoctor);
    
    if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
        $doctor = $all_doctor_info->fetch_assoc();
        $doctor_id = $doctor["doctor_id"];
    }
    
    $verifySql = "SELECT * FROM opd WHERE id='$opd_id' AND doctor_id='$doctor_id' AND (delete_flag=0 OR delete_flag IS NULL)";
    $verifyResult = mysqli_query($conn, $verifySql);
    
    if(mysqli_num_rows($verifyResult) > 0) {
        $updateSql = "UPDATE opd SET delete_flag = 1 WHERE id='$opd_id'";
        
        if(mysqli_query($conn, $updateSql)) {
            $_SESSION['success_message'] = "OPD record deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting OPD record: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "OPD record not found or you don't have permission.";
    }
} else {
    $_SESSION['error_message'] = "No OPD record selected.";
}

header("Location: opd_main.php");
exit();

mysqli_close($conn);
?>