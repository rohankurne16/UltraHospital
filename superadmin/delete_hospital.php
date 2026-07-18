<?php
session_start();
include '../config/superadmin.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'SuperAdmin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {

    $hospital_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Soft Delete Hospital
    $sql = "UPDATE hospital_master
            SET delete_flag = 1,
                modified_at = NOW()
            WHERE hospital_id = '$hospital_id'";

    if (mysqli_query($conn, $sql)) {

        // Optional: Soft delete related records
        mysqli_query($conn, "UPDATE hospital_admin SET delete_flag=1 WHERE hospital_id='$hospital_id'");
        mysqli_query($conn, "UPDATE doctor SET delete_flag=1 WHERE hospital_id='$hospital_id'");
        mysqli_query($conn, "UPDATE department SET delete_flag=1 WHERE hospital_id='$hospital_id'");
        mysqli_query($conn, "UPDATE staff SET delete_flag=1 WHERE hospital_id='$hospital_id'");

        // Audit Log
        $user_name = $_SESSION['name'];

        mysqli_query($conn, "
            INSERT INTO audit_logs
            (hospital_id, register_id, module, action)
            VALUES
            ('$hospital_id','".$_SESSION['id']."','Hospital',
            'Hospital deleted by $user_name')
        ");

        header("Location: hospitals.php?deleted=1");
        exit();

    } else {

        die(mysqli_error($conn));

    }

} else {

    header("Location: hospitals.php");
    exit();

}