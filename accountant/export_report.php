<?php
session_start();
require_once '../config/hospital.php';
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$query = "SELECT b.bill_no, p.patient_name, b.total, b.paid_amount, b.pending_amount, b.created_at 
          FROM billing b 
          LEFT JOIN patients p ON b.patient_id = p.patient_id 
          WHERE b.hospital_id = $hospital_id AND b.delete_flag = 0 AND DATE(b.created_at) BETWEEN '$from' AND '$to'";
$result = $conn->query($query);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="revenue_report.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Bill No', 'Patient', 'Total', 'Paid', 'Pending', 'Date']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['bill_no'], $row['patient_name'], $row['total'], $row['paid_amount'], $row['pending_amount'], $row['created_at']]);
}
fclose($output);
exit;