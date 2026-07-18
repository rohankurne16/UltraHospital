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

if(isset($_GET['appointment_id'])) {
    $appointment_id = mysqli_real_escape_string($conn, $_GET['appointment_id']);
    
  
    $doctor_reg_id = $_SESSION['id'];
    
 
    $getDoctor = mysqli_query($conn, "SELECT doctor_id FROM doctor WHERE register_id='$doctor_reg_id'");
    $doctor = mysqli_fetch_assoc($getDoctor);
    $doctor_id = $doctor['doctor_id'];
    
  
    $verifySql = "SELECT * FROM appointments WHERE appointment_id='$appointment_id' AND doctor_id='$doctor_id' AND (delete_flag=0 OR delete_flag IS NULL)";
    $verifyResult = mysqli_query($conn, $verifySql);
    
    if(mysqli_num_rows($verifyResult) > 0) {

        $appointment_no = "APT" . date("Ymd") . rand(1000,9999);

        $updateSql = "update appointments set status='Confirmed', appointment_no='$appointment_no' where appointment_id='$appointment_id'";
            
        if(mysqli_query($conn, $updateSql)) {
            
            $_SESSION['success_message'] = "Appointment confirmed successfully!";
            header("Location: show_myappointment.php");
            exit();
        } else {
            
            $_SESSION['error_message'] = "Error confirming appointment: " . mysqli_error($conn);
          header("Location: show_myappointment.php");
            exit();
        }
    } else {
        
        $_SESSION['error_message'] = "Appointment not found or you don't have permission to confirm it.";
       header("Location: show_myappointment.php");
        exit();
    }
} else {
   
    $_SESSION['error_message'] = "No appointment selected.";
    header("Location: show_myappointment.php");
    exit();
}

mysqli_close($conn);
?>