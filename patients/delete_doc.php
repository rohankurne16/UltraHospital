<?php
session_start();
include("../config/hospital.php");

// Check if user is logged in
if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location: ../auth/logout.php");
    exit();
}

// Check if document ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: show_my_docs.php");
    exit();
}

$document_id = $_GET['id'];


$deleteQuery = "UPDATE patient_documents SET delete_flag=1 WHERE document_id='$document_id'";

if ($conn->query($deleteQuery)==true) {
    header("Location: show_my_docs.php");
    exit();
} else {
    header("Location: show_my_docs.php");
    exit();
}

$conn->close();
?>