<?php 
include("../config/db.php");

$appointment_id=$_GET["id"];

$cancle_appointment="update appointments set status='Cancelled' where appointment_id='$appointment_id'";
$result=$conn->query($cancle_appointment);
if($result===true){
    echo "<script>alert('Appointment Cancelled Successfully') </script>";
    header("Location: dashboard.php");
}


?>