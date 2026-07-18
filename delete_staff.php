<?php
session_start();
include "config/hospital.php";

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $check_sql = "SELECT * FROM staff WHERE staff_id = '$id' AND (delete_flag IS NULL OR delete_flag = 0)";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows > 0) {
        $delete_sql = "UPDATE staff SET delete_flag = 1, updated_at = CURRENT_TIMESTAMP() WHERE staff_id = '$id'";
        
        if($conn->query($delete_sql)) {
            echo "<script> window.location='staff.php';</script>";
        } else {
            echo "<script>alert('Error deleting staff: " . $conn->error . "'); window.location='staff.php';</script>";
        }
    } else {
        echo "<script>alert('Staff member not found'); window.location='staff.php';</script>";
    }
} else {
    header("Location: staff.php");
    exit();
}
?>