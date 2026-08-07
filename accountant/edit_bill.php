<?php
// ============================================================
// EDIT BILL – Load and update bill details
// ============================================================

session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

if (!hasPermission('billing-edit')) {
    header("Location: billing_list.php");
    exit();
}

$id = (int)$_GET['id'];
$hospital_id = $_SESSION['hospital_id'] ?? 0;

// Fetch bill data
$query = "SELECT b.*, p.patient_name FROM billing b
          LEFT JOIN patients p ON b.patient_id = p.patient_id
          WHERE b.id = $id AND b.hospital_id = $hospital_id AND b.delete_flag = 0 AND b.status != 'Cancelled'";
$result = $conn->query($query);
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Bill not found or cannot be edited'); window.location='billing_list.php';</script>";
    exit;
}
$bill = $result->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total = $_POST['total'];
    $paid = $_POST['paid_amount'];
    $pending = $total - $paid;
    $mode = $_POST['payment_mode'];
    $remark = $_POST['remark'];

    $update = "UPDATE billing SET total = $total, paid_amount = $paid, pending_amount = $pending, payment_mode = '$mode', remark = '$remark', modified_at = NOW() WHERE id = $id";
    if ($conn->query($update)) {
        echo "<script>alert('Bill updated successfully'); window.location='view_bill.php?id=$id';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bill - Accountant</title>
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

        .card { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; max-width:700px; margin:0 auto; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; font-size:13px; color:#475569; margin-bottom:4px; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#f8fafc; }
        .form-control:focus { border-color:#ed8936; outline:none; box-shadow:0 0 0 3px rgba(237,137,54,0.1); }
        .btn { padding:10px 24px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#ed8936; color:white; }
        .btn-primary:hover { background:#d97706; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .btn-secondary:hover { background:#cbd5e1; }
        .flex { display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-end; margin-top:16px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media (max-width:768px){ .grid-2{ grid-template-columns:1fr; } }
        .readonly { background:#f1f5f9; color:#64748b; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <div class="greeting-gradient">
                <div>
                    <h1><i class="fas fa-edit"></i> Edit Bill #<?php echo $bill['bill_no']; ?></h1>
                    <p>Update bill details</p>
                </div>
                <a href="view_bill.php?id=<?php echo $id; ?>" class="btn" style="background:rgba(255,255,255,0.15); color:white;">Back to Bill</a>
            </div>

            <div class="card">
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Bill No</label>
                            <input type="text" class="form-control readonly" value="<?php echo $bill['bill_no']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Patient</label>
                            <input type="text" class="form-control readonly" value="<?php echo htmlspecialchars($bill['patient_name'] ?? 'Unknown'); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Total Amount (₹)</label>
                            <input type="number" class="form-control" name="total" step="0.01" value="<?php echo $bill['total']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Paid Amount (₹)</label>
                            <input type="number" class="form-control" name="paid_amount" step="0.01" value="<?php echo $bill['paid_amount']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select class="form-control" name="payment_mode">
                                <option value="Cash" <?php echo $bill['payment_mode']=='Cash'?'selected':''; ?>>Cash</option>
                                <option value="Card" <?php echo $bill['payment_mode']=='Card'?'selected':''; ?>>Card</option>
                                <option value="UPI" <?php echo $bill['payment_mode']=='UPI'?'selected':''; ?>>UPI</option>
                                <option value="Bank" <?php echo $bill['payment_mode']=='Bank'?'selected':''; ?>>Bank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Remark</label>
                            <input type="text" class="form-control" name="remark" value="<?php echo htmlspecialchars($bill['remark']); ?>">
                        </div>
                    </div>
                    <div class="flex">
                        <a href="view_bill.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Bill</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>