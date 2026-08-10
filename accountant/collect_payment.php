<?php
session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

// Support both id and bill_id in URL
 $bill_id = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
 $hospital_id = $_SESSION['hospital_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = (int)$_POST['bill_id'];
    $amount = floatval($_POST['amount']);
    $mode = $conn->real_escape_string($_POST['mode']);
    $remark = $conn->real_escape_string($_POST['remark']);

    // Fetch current bill details from new `bills` table
    $billQuery = "SELECT * FROM bills WHERE bill_id = $bill_id AND hospital_id = $hospital_id AND delete_flag = 0";
    $billRes = $conn->query($billQuery);
    
    if (!$billRes || $billRes->num_rows == 0) { 
        echo "<script>alert('Bill not found'); window.location='billing.php';</script>";
        exit;
    }
    $bill = $billRes->fetch_assoc();

    if ($amount <= 0) { 
        echo "<script>alert('Please enter a valid amount greater than 0.'); window.history.back();</script>";
        exit;
    }
    if ($amount > $bill['balance_amount']) { 
        echo "<script>alert('Amount exceeds pending balance of ₹" . $bill['balance_amount'] . "'); window.history.back();</script>";
        exit;
    }

    $newBalance = $bill['balance_amount'] - $amount;
    $newPaid = $bill['paid_amount'] + $amount;
    $newStatus = ($newBalance <= 0) ? 'Paid' : 'Partial';
    
    // Append new remark to old remark if exists
    $finalRemark = $bill['remark'] ? $bill['remark'] . " | " . $remark : $remark;

    $update = "UPDATE bills SET 
                paid_amount = $newPaid, 
                balance_amount = $newBalance, 
                payment_mode = '$mode', 
                status = '$newStatus',
                remark = '$finalRemark' 
               WHERE bill_id = $bill_id";
               
    if ($conn->query($update)) {
        echo "<script>alert('Payment of ₹" . number_format($amount, 2) . " recorded successfully.'); window.location='view_bill.php?id=$bill_id';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Fetch bill data to display the form
 $query = "SELECT b.*, p.patient_name 
          FROM bills b 
          LEFT JOIN patients p ON b.patient_id = p.patient_id 
          WHERE b.bill_id = $bill_id AND b.hospital_id = $hospital_id AND b.delete_flag = 0";
          
 $result = $conn->query($query);
if (!$result || $result->num_rows == 0) { 
    echo "<script>alert('Bill not found'); window.location='billing.php';</script>"; 
    exit; 
}
 $bill = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Payment - Accountant</title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? 'favicon.ico'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #334155; }
        a { text-decoration: none; }
        
        /* Layout */
        .main-content { margin-left: 260px; padding: 32px; min-height: 100vh; width: calc(100% - 260px); }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; width: 100%; } }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 12px; }
        .page-header h1 i { color: var(--success); }
        
        .form-container { max-width: 800px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 768px) { .form-container { grid-template-columns: 1fr; } }
        
        .card { background: white; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 24px; }
        .card-title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
        .card-title i { color: var(--primary); }
        
        .summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .summary-item:last-child { border-bottom: none; }
        .summary-item .label { color: #64748b; font-weight: 500; font-size: 14px; }
        .summary-item .val { font-weight: 700; color: var(--dark); font-size: 16px; }
        .summary-item.highlight .val { color: var(--danger); font-size: 20px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 15px; background: var(--light); transition: all 0.2s; font-family: 'Inter', sans-serif; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); background: white; }
        
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 10px; border: none; cursor: pointer; transition: all 0.3s ease; gap: 8px; text-decoration: none; width: 100%; }
        .btn-success { background: linear-gradient(135deg, var(--success), #059669); color: white; box-shadow: 0 4px 15px -4px rgba(16, 185, 129, 0.4); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.5); }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        
        .alert-pending { background: #fffbeb; border: 1px solid #fde68a; color: #d97706; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-hand-holding-usd"></i> Collect Payment</h1>
            </div>

            <div class="form-container">
                <!-- Bill Summary Section -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-file-invoice"></i> Bill Details
                    </div>
                    <div class="summary-item">
                        <span class="label">Bill ID</span>
                        <span class="val">#INV-<?php echo str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Patient Name</span>
                        <span class="val"><?php echo htmlspecialchars($bill['patient_name'] ?? 'Walk-in Patient'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Total Amount</span>
                        <span class="val">₹<?php echo number_format($bill['total_amount'], 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Amount Paid</span>
                        <span class="val" style="color: var(--success);">₹<?php echo number_format($bill['paid_amount'], 2); ?></span>
                    </div>
                    <div class="summary-item highlight">
                        <span class="label">Pending Balance</span>
                        <span class="val">₹<?php echo number_format($bill['balance_amount'], 2); ?></span>
                    </div>
                </div>

                <!-- Payment Form Section -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-cash-register"></i> Payment Entry
                    </div>
                    
                    <?php if($bill['balance_amount'] > 0): ?>
                    <div class="alert-pending">
                        <i class="fas fa-exclamation-circle"></i>
                        Collecting payment for pending balance.
                    </div>
                    
                    <form method="POST" action="collect_payment.php">
                        <input type="hidden" name="bill_id" value="<?php echo $bill['bill_id']; ?>">
                        
                        <div class="form-group">
                            <label>Amount to Collect (₹)</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="<?php echo $bill['balance_amount']; ?>" placeholder="Enter Amount" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select class="form-control" name="mode" required>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Remark / Note</label>
                            <input type="text" class="form-control" name="remark" placeholder="Optional remark">
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Record Payment
                            </button>
                            <a href="view_bill.php?id=<?php echo $bill['bill_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--success);">
                        <i class="fas fa-check-circle" style="font-size: 64px; margin-bottom: 16px;"></i>
                        <h3 style="font-weight: 700; color: var(--dark); margin-bottom: 8px;">Bill Fully Paid</h3>
                        <p style="color: #64748b;">There is no pending balance for this bill.</p>
                        <a href="view_bill.php?id=<?php echo $bill['bill_id']; ?>" class="btn btn-secondary" style="width: auto; margin-top: 20px;">
                            <i class="fas fa-arrow-left"></i> Back to Bill
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </main>
    </div>
</body>
</html>