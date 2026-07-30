<?php
session_start();
include "../config/hospital.php";

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

$sql = "SELECT od.detail_id, t.test_name, t.test_code, t.normal_range, t.unit 
        FROM lab_order_details od
        LEFT JOIN lab_tests t ON od.test_id = t.test_id
        WHERE od.order_id = $order_id
        AND od.delete_flag = 0";

$result = $conn->query($sql);
$tests = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tests[] = [
            'detail_id' => $row['detail_id'],
            'test_name' => $row['test_name'] ?? 'Unknown Test',
            'test_code' => $row['test_code'] ?? 'N/A',
            'normal_range' => $row['normal_range'] ?? '',
            'unit' => $row['unit'] ?? ''
        ];
    }
}

echo json_encode(['tests' => $tests]);
?>