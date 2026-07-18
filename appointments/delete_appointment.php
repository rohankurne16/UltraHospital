<?php

include "../config/db.php";

if(isset($_GET['appointment_id'])){

    $id = $_GET['appointment_id'];

    $sql = "UPDATE appointments SET delete_flag = 1 WHERE appointment_id = '$id'";

    if(mysqli_query($conn, $sql)){
        header("Location: ../appointments.php");
        exit();
    }
    else{
        echo "Delete Failed";
    }

}

mysqli_close($conn);

?>