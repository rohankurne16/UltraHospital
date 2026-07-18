<?php 
    session_start();
    include 'config/hospital.php'; 

    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $dept_id = mysqli_real_escape_string($conn, $_GET['id']);
        
        $sql = "update department set delete_flag = 1 where id = '$dept_id'";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: departments.php?msg=deleted");
            exit();
        } else {
            echo "Error deleting record: " . $conn->error;
        }
    } else {
        header("Location: show_departments.php");
        exit();
    }
?>
