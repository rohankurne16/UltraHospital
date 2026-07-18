<?php
session_start();
include "config/hospital.php";


if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}


$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$detail_id = isset($_GET['detail_id']) ? intval($_GET['detail_id']) : 0;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$patient = isset($_GET['patient']) ? trim($_GET['patient']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';


if ($test_id == 0 && $detail_id == 0) {
    header("Location: lab_test_master.php");
    exit();
}

$success = false;
$error = "";

if ($type === 'detail' && $detail_id > 0) {
    
    $sql = "UPDATE lab_test_details SET delete_flag = 1 WHERE detail_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $detail_id);
    
    if ($stmt->execute()) {
        $success = true;
        $_SESSION['success'] = "Test detail deleted successfully!";
    } else {
        $error = "Error deleting test detail: " . $conn->error;
        $_SESSION['error'] = $error;
    }
    $stmt->close();
    
} elseif ($type === 'main' && $test_id > 0) {
  
    $sql = "UPDATE lab_tests SET delete_flag = 1 WHERE test_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test_id);
    
    if ($stmt->execute()) {
        $success = true;
        $_SESSION['success'] = "Test deleted successfully!";
    } else {
        $error = "Error deleting test: " . $conn->error;
        $_SESSION['error'] = $error;
    }
    $stmt->close();
    
} elseif ($test_id > 0) {
    
    $sql = "UPDATE lab_tests SET delete_flag = 1 WHERE test_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test_id);
    
    if ($stmt->execute()) {
        $success = true;
        $_SESSION['success'] = "Test deleted successfully!";
    } else {
        $error = "Error deleting test: " . $conn->error;
        $_SESSION['error'] = $error;
    }
    $stmt->close();
}

$conn->close();

if (!empty($category) && !empty($patient)) {
    
    header("Location: edit_test.php?id=" . $test_id . "&category=" . urlencode($category) . "&patient=" . urlencode($patient));
} elseif (!empty($category)) {
   
    header("Location: edit_test.php?category=" . urlencode($category) . "&id=" . $test_id);
} else {
   
    header("Location: lab_test_master.php");
}
exit();
?>