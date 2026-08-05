<?php
session_start();
require_once '../config/hospital.php';
$id = (int)$_GET['id'];
$hospital_id = $_SESSION['hospital_id'] ?? 0;
if ($id) {
    $update = "UPDATE billing SET delete_flag = 1 WHERE id = $id AND hospital_id = $hospital_id";
    if ($conn->query($update)) {
        echo "<script>alert('Bill deleted successfully'); window.location='billing.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.location='billing.php';</script>";
    }
} else {
    header("Location: billing.php");
}