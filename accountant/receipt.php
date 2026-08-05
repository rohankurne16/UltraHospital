<?php
session_start();
require_once '../config/hospital.php';
$id = (int)$_GET['id'];
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$query = "SELECT b.*, p.patient_name, p.mobile FROM billing b LEFT JOIN patients p ON b.patient_id = p.patient_id WHERE b.id = $id AND b.hospital_id = $hospital_id";
$result = $conn->query($query);
if (!$result || $result->num_rows == 0) { die('Bill not found'); }
$bill = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - #<?php echo $bill['bill_no']; ?></title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; max-width: 400px; margin: auto; }
        .header { text-align:center; border-bottom:1px dashed #333; padding-bottom:8px; margin-bottom:12px; }
        .row { display:flex; justify-content:space-between; padding:4px 0; }
        .total { font-weight:bold; border-top:1px solid #333; margin-top:8px; padding-top:8px; }
        .footer { text-align:center; margin-top:16px; font-size:12px; border-top:1px dashed #333; padding-top:8px; }
        .btn { display:inline-block; padding:6px 16px; background:#ed8936; color:white; border:none; border-radius:4px; cursor:pointer; margin-top:12px; }
    </style>
</head>
<body>
    <div class="header">
        <h3><?php echo $hospital['hospital_name'] ?? 'Hospital'; ?></h3>
        <small>Payment Receipt</small>
    </div>
    <div class="row"><span>Bill No:</span><span><?php echo $bill['bill_no']; ?></span></div>
    <div class="row"><span>Patient:</span><span><?php echo htmlspecialchars($bill['patient_name'] ?? 'N/A'); ?></span></div>
    <div class="row"><span>Date:</span><span><?php echo date('d M Y h:i A', strtotime($bill['created_at'])); ?></span></div>
    <div class="row"><span>Total:</span><span>₹ <?php echo number_format($bill['total'], 2); ?></span></div>
    <div class="row"><span>Paid:</span><span>₹ <?php echo number_format($bill['paid_amount'], 2); ?></span></div>
    <div class="row total"><span>Pending:</span><span>₹ <?php echo number_format($bill['pending_amount'], 2); ?></span></div>
    <?php if ($bill['payment_mode']): ?>
    <div class="row"><span>Mode:</span><span><?php echo $bill['payment_mode']; ?></span></div>
    <?php endif; ?>
    <div class="footer">
        Thank you for your visit<br>
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print</button>
        <button onclick="window.close()" class="btn" style="background:#64748b;">Close</button>
    </div>
</body>
</html>