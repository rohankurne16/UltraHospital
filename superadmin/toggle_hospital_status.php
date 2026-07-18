<?php
session_start();
include '../config/superadmin.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'SuperAdmin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['hospital_id']) && isset($_GET['status'])) {

    $hospital_id = mysqli_real_escape_string($conn, $_GET['hospital_id']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);

    if ($status == "Active" || $status == "Inactive") {

        $sql = "UPDATE hospital_master
                SET status='$status',
                    modified_at = NOW()
                WHERE hospital_id='$hospital_id'
                AND delete_flag='0'";

        if (mysqli_query($conn, $sql)) {

            $user_name = $_SESSION['name'];

            mysqli_query($conn,"
                INSERT INTO audit_logs
                (hospital_id, register_id, module, action)
                VALUES
                ('$hospital_id','".$_SESSION['id']."','Hospital',
                'Status changed to $status by $user_name')
            ");

            header("Location: dashboard.php?success=status_updated");
            exit();

        } else {

            header("Location: dashboard.php?error=1");
            exit();

        }

    } else {

        header("Location: hospitals.php?invalid=1");
        exit();

    }

} else {

    header("Location: hospitals.php");
    exit();

}