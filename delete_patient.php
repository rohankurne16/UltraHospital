<?php

include "config/db.php";

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $sql = "update patients set delete_flag = 1 where patient_id = '$id'";

    if($conn->query($sql)){
        header("Location: patients.php?msg=deleted");
        exit();
    }else{
        echo "Error: ".$conn->error;
    }

}else{
    header("Location: patients.php");
    exit();
}
?>