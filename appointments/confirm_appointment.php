<?php

include "../config/db.php";

if(isset($_GET['appointment_id'])){

    $id = $_GET['appointment_id'];

    $sql = "UPDATE appointments SET status = 'confirmed' WHERE appointment_id = '$id'";

    if(mysqli_query($conn, $sql)){
        echo"<script>alert('Apointment confirmed successsfully')</script>";
        header("Location: ../appointments.php");
        exit();
    }
    else{
        echo "Confirm Failed";
    }

}

mysqli_close($conn);

?>