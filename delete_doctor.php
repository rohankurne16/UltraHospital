<?php

include "config/db.php";

if(isset($_GET['id']))
{
    $id = $_GET['id'];

    $sql = "UPDATE doctor
            SET delete_flag = 1
            WHERE doctor_id = '$id'";

    if(mysqli_query($conn,$sql))
    {
        header("Location: doctors.php");
        exit();
    }
    else
    {
        echo mysqli_error($conn);
    }
}
else
{
    echo "Invalid Doctor ID";
}

?>