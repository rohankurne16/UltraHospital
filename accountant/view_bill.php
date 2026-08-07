<?php
// ============================================================
// VIEW BILL – Detailed view with print option
// ============================================================

session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

if (!hasPermission('billing-view')) {
    header("Location: billing_list.php");
    exit();
}

$id = (int)$_GET['id'];
$hospital_id = $_SESSION['hospital_id'] ?? 0;

$query = "SELECT b.*, p.patient_name, p.mobile, p.address FROM billing b
          LEFT JOIN patients p ON b.patient_id = p.patient_id
          WHERE b.id = $id AND b.hospital_id = $hospital_id AND b.delete_flag = 0";
$result = $conn->query($query);
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Bill not found'); window.location='billing_list.php';</script>";
    exit;
}
$bill = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #<?php echo $bill['bill_no']; ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? 'favicon.ico'; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; background:#f0f4f8; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        @media (max-width:768px){ .main-content{ padding:16px; } }

        .greeting-gradient {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border-radius:16px;
            padding:20px 28px;
            margin-bottom:24px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
        }
        .greeting-gradient h1 { color:white; font-weight:700; font-size:22px; }
        .greeting-gradient p { color:rgba(255,255,255,0.7); font-size:14px; }
        .greeting-gradient .btn { padding:8px 20px; border-radius:8px; border:none; font-weight:600; cursor:pointer; background:#ed8936; color:white; transition:0.2s; }
        .greeting-gradient .btn:hover { background:#d97706; }

        .card { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; margin-bottom:24px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media (max-width:768px){ .grid-2{ grid-template-columns:1fr; } }
        .label { font-weight:600; color:#64748b; font-size:13px; }
        .value { font-size:15px; font-weight:500; }
        .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-paid { background:#d1fae5; color:#065f46; }
        .badge-pending { background:#fef3c7; color:#92400e; }
        .badge-partial { background:#fef3c7; color:#92400e; }
        .badge-cancelled { background:#fee2e2; color:#991b1b; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { background:#f8fafc; padding:10px 12px; text-align:left; border-bottom:2px solid #e2e8f0; }
        td { padding:10px 12px; border-bottom:1px solid #f1f5f9; }
        .total-row { font-weight:700; background:#f8fafc; }
        .flex { display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
        .btn { padding:8px 18px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#ed8936; color:white; }
        .btn-primary:hover { background:#d97706; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .btn-secondary:hover { background:#cbd5e1; }
        .btn-success { background:#38a169; color:white; }
        .btn-success:hover { background:#2f855a; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <div class="greeting-gradient">
                <div>
                    <h1><i class="fas fa-file-invoice"></i> Bill #<?php echo $bill['bill_no']; ?></h1>
                    <p>View bill details</p>
                </div>
                <div>
                    <a href="print_bill.php?id=<?php echo $id; ?>" class="btn" style="background:#38a169;"><i class="fas fa-print"></i> Print</a>
                    <a href="billing_list.php" class="btn" style="background:rgba(255,255,255,0.15); color:white;">Back</a>
                </div>
            </div>

            <div class="card">
                <div class="grid-2">
                    <div>
                        <div class="label">Patient</div>
                        <div class="value"><?php echo htmlspecialchars($bill['patient_name'] ?? 'N/A'); ?></div>
                        <div class="label">Mobile</div>
                        <div class="value"><?php echo htmlspecialchars($bill['mobile'] ?? 'N/A'); ?></div>
                        <div class="label">Address</div>
                        <div class="value"><?php echo htmlspecialchars($bill['address'] ?? 'N/A'); ?></div>
                    </div>
                    <div>
                        <div class="label">Bill Date</div>
                        <div class="value"><?php echo date('d M Y h:i A', strtotime($bill['created_at'])); ?></div>
                        <div class="label">Status</div>
                        <div class="value">
                            <?php 
                            $status = 'Paid';
                            $class = 'paid';
                            if ($bill['pending_amount'] == $bill['total']) { $status = 'Pending'; $class = 'pending'; }
                            elseif ($bill['pending_amount'] > 0 && $bill['pending_amount'] < $bill['total']) { $status = 'Partial'; $class = 'partial'; }
                            if ($bill['status'] == 'Cancelled') { $status = 'Cancelled'; $class = 'cancelled'; }
                            ?>
                            <span class="badge badge-<?php echo $class; ?>"><?php echo $status; ?></span>
                        </div>
                        <?php if ($bill['payment_mode']): ?>
                        <div class="label">Payment Mode</div>
                        <div class="value"><?php echo $bill['payment_mode']; ?></div>
                        <?php endif; ?>
                        <?php if ($bill['remark']): ?>
                        <div class="label">Remark</div>
                        <div class="value"><?php echo htmlspecialchars($bill['remark']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-bottom:12px;">Bill Items</h3>
                <table>
                    <thead><tr><th>Service</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php 
                        // For simplicity, we assume a single line item. In real system, fetch from bill_items table.
                        ?>
                        <tr><td>Total Services</td><td>1</td><td>₹ <?php echo number_format($bill['total'], 2); ?></td><td>₹ <?php echo number_format($bill['total'], 2); ?></td></tr>
                        <tr class="total-row"><td colspan="3">Total</td><td>₹ <?php echo number_format($bill['total'], 2); ?></td></tr>
                        <tr><td colspan="3">Paid</td><td>₹ <?php echo number_format($bill['paid_amount'], 2); ?></td></tr>
                        <tr><td colspan="3">Pending</td><td>₹ <?php echo number_format($bill['pending_amount'], 2); ?></td></tr>
                    </tbody>
                </table>
                <?php if ($bill['pending_amount'] > 0 && $bill['status'] != 'Cancelled'): ?>
                <div class="flex">
                    <a href="collect_payment.php?id=<?php echo $id; ?>" class="btn btn-success"><i class="fas fa-hand-holding-usd"></i> Collect Payment</a>
                </div>
                <?php endif; ?>
                <?php if ($bill['status'] != 'Cancelled' && $bill['pending_amount'] == 0): ?>
                <div class="flex">
                    <span class="btn" style="background:#d1fae5; color:#065f46; cursor:default;"><i class="fas fa-check-circle"></i> Fully Paid</span>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>