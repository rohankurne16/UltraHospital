<?php
session_start();
require_once '../config/hospital.php';
$id = (int)$_GET['id'];
$hospital_id = $_SESSION['hospital_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $mode = $_POST['mode'];
    $remark = $_POST['remark'];

    $billQuery = "SELECT * FROM billing WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0";
    $billRes = $conn->query($billQuery);
    if ($billRes->num_rows == 0) { die('Bill not found'); }
    $bill = $billRes->fetch_assoc();

    if ($amount > $bill['pending_amount']) { die('Amount exceeds pending balance'); }

    $newPending = $bill['pending_amount'] - $amount;
    $newPaid = $bill['paid_amount'] + $amount;
    $update = "UPDATE billing SET paid_amount = $newPaid, pending_amount = $newPending, payment_mode = '$mode', remark = '$remark' WHERE id = $id";
    if ($conn->query($update)) {
        // Optionally insert into payment history table
        echo "<script>alert('Payment recorded successfully'); window.location='view_bill.php?id=$id';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

$query = "SELECT * FROM billing WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0";
$result = $conn->query($query);
if (!$result || $result->num_rows == 0) { echo "<script>alert('Bill not found'); window.location='billing.php';</script>"; exit; }
$bill = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        .card { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; max-width:600px; margin:0 auto; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; font-size:13px; color:#475569; margin-bottom:4px; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#f8fafc; }
        .btn { padding:10px 24px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#ed8936; color:white; }
        .btn-primary:hover { background:#d97706; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .flex { display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <div class="card">
                <h2 style="margin-bottom:16px;">Collect Payment for Bill #<?php echo $bill['bill_no']; ?></h2>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($bill['patient_id']); // We don't have patient_name in billing, so we rely on joining ?></p>
                <p><strong>Total:</strong> ₹ <?php echo number_format($bill['total'], 2); ?></p>
                <p><strong>Paid:</strong> ₹ <?php echo number_format($bill['paid_amount'], 2); ?></p>
                <p><strong>Pending:</strong> ₹ <?php echo number_format($bill['pending_amount'], 2); ?></p>
                <form method="POST">
                    <div class="form-group">
                        <label>Amount to Collect</label>
                        <input type="number" class="form-control" name="amount" step="0.01" max="<?php echo $bill['pending_amount']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select class="form-control" name="mode" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank">Bank</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remark</label>
                        <input type="text" class="form-control" name="remark" placeholder="Optional">
                    </div>
                    <div class="flex">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Record Payment</button>
                        <a href="view_bill.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>