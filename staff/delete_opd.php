<?php

include "../config/db.php";

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $sql = "UPDATE opd SET delete_flag = 1 WHERE id = '$id'";

    if(mysqli_query($conn, $sql)){
        header("Location: ../opd_list.php");
        exit();
    }
    else{
        echo "Delete Failed";
    }

}

mysqli_close($conn);

?>