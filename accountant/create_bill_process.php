<?php
// ============================================================
// CREATE BILL PROCESS - Backend API to save bill
// ============================================================

session_start();
require_once '../config/hospital.php';

// Ensure the response is always JSON, even if an error occurs
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// Read JSON payload from JavaScript
 $data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

 $hospital_id = (int)($data['hospital_id'] ?? 0);
 $patient_id = (int)($data['patient_id'] ?? 0);
 $total = (float)($data['total'] ?? 0);
 $paid = (float)($data['paid'] ?? 0);
 $mode = $conn->real_escape_string($data['mode'] ?? 'Cash');
 $remark = $conn->real_escape_string($data['remark'] ?? '');
 $items = $data['items'] ?? [];

if ($hospital_id <= 0 || $patient_id <= 0 || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Calculate balance and status
 $balance = $total - $paid;
 $status = 'Unpaid';
if ($paid >= $total) $status = 'Paid';
elseif ($paid > 0) $status = 'Partial';

// Start Transaction to ensure both bills and bill_items save together
 $conn->begin_transaction();

try {
    // 1. Insert into bills table
    $sql_bill = "INSERT INTO bills (hospital_id, patient_id, total_amount, paid_amount, balance_amount, payment_mode, remark, status) 
                 VALUES ($hospital_id, $patient_id, $total, $paid, $balance, '$mode', '$remark', '$status')";
    
    if (!$conn->query($sql_bill)) {
        throw new Exception("Database error (bills): " . $conn->error);
    }
    
    $bill_id = $conn->insert_id;

    // 2. Insert items into bill_items table
    foreach ($items as $item) {
        $name = $conn->real_escape_string($item['name']);
        $qty = (int)$item['qty'];
        $rate = (float)$item['rate'];
        $item_total = $qty * $rate;
        
        $sql_item = "INSERT INTO bill_items (bill_id, service_name, qty, rate, total) 
                     VALUES ($bill_id, '$name', $qty, $rate, $item_total)";
        
        if (!$conn->query($sql_item)) {
            throw new Exception("Database error (bill_items): " . $conn->error);
        }
    }

    // Commit transaction if everything succeeded
    $conn->commit();
    echo json_encode(['success' => true, 'bill_id' => $bill_id]);

} catch (Exception $e) {
    // Rollback if any error occurred
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>