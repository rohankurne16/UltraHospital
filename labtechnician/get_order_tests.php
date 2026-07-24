<?php
session_start();
include "../config/hospital.php";

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["id"])) {
    echo json_encode(['tests' => [], 'error' => 'Not logged in']);
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);
$tests = [];

if ($order_id > 0) {
    $sql = "SELECT od.detail_id, od.test_id, t.test_name, t.test_code 
            FROM lab_order_details od
            LEFT JOIN lab_tests t ON od.test_id = t.test_id
            WHERE od.order_id = $order_id AND od.delete_flag = 0
            ORDER BY od.detail_id ASC";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        echo json_encode(['tests' => [], 'error' => 'SQL Error: ' . $conn->error]);
        exit();
    }
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tests[] = [
                'detail_id' => (int)$row['detail_id'],
                'test_id' => (int)$row['test_id'],
                'test_name' => $row['test_name'] ?? 'Unknown Test',
                'test_code' => $row['test_code'] ?? 'N/A'
            ];
        }
    }
}

echo json_encode(['tests' => $tests]);
?>