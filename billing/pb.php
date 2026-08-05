<?php 
session_start(); 
include "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION["staff_id"]) && !isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("Location: print_bill.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$billQuery = "SELECT b.*, p.patient_name, p.mobile, p.email, p.address
FROM billing b
LEFT JOIN patients p ON b.patient_id = p.patient_id
WHERE b.id = '$id'
AND (b.delete_flag = 0 OR b.delete_flag IS NULL)";

$billResult = $conn->query($billQuery);

if (!$billResult) {
    die("SQL Error: " . $conn->error);
}

if ($billResult->num_rows == 0) {
    header("Location: print_bill.php");
    exit();
}

$billData = $billResult->fetch_assoc();

// Determine status
$status = 'Paid';
if ($billData["pending_amount"] > 0 && $billData["paid_amount"] > 0) {
    $status = 'Partial';
} elseif ($billData["pending_amount"] > 0 && $billData["paid_amount"] == 0) {
    $status = 'Pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Bill - <?php echo $billData["bill_no"]; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #ffffff; padding: 40px; }
        .bill-container { max-width: 800px; margin: 0 auto; background: white; }
        .bill-header { text-align: center; padding: 20px 0; border-bottom: 2px solid #e5e7eb; }
        .bill-header h1 { font-size: 28px; font-weight: 700; color: #0f172a; }
        .bill-header h1 span { color: #3b82f6; }
        .bill-header p { color: #64748b; font-size: 14px; }
        .bill-details { padding: 20px 0; border-bottom: 1px solid #e5e7eb; }
        .bill-details .row { display: flex; padding: 6px 0; }
        .bill-details .row .label { width: 150px; color: #64748b; font-size: 14px; }
        .bill-details .row .value { flex: 1; color: #0f172a; font-weight: 500; font-size: 14px; }
        .bill-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .bill-table th { background: #f8fafc; padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; }
        .bill-table td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .bill-table .total-row td { font-weight: 600; border-top: 2px solid #0f172a; }
        .amount-summary { margin: 20px 0; padding: 16px; background: #f8fafc; border-radius: 8px; }
        .amount-summary .row { display: flex; justify-content: space-between; padding: 4px 0; }
        .amount-summary .row .label { color: #64748b; }
        .amount-summary .row .value { font-weight: 600; }
        .amount-summary .row.total { border-top: 2px solid #e5e7eb; padding-top: 8px; margin-top: 4px; }
        .amount-summary .row.total .value { font-size: 18px; color: #0f172a; }
        .bill-footer { text-align: center; padding: 20px 0; border-top: 2px solid #e5e7eb; color: #94a3b8; font-size: 12px; margin-top: 20px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-partial { background: #dbeafe; color: #1e40af; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .bill-container { max-width: 100%; }
            .bill-table th { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .amount-summary { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <!-- Print Button -->
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                <i data-lucide="printer" style="width: 16px; height: 16px; display: inline-block; margin-right: 8px;"></i>
                Print Bill
            </button>
            <button onclick="window.close()" style="padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; margin-left: 8px;">
                Close
            </button>
        </div>

        <!-- Bill Header -->
        <div class="bill-header">
            <h1>Medix<span>Pro</span></h1>
            <p>Healthcare Administration Simplified</p>
            <p style="margin-top: 4px; font-size: 12px;">123 Healthcare Avenue, Medical District, City - 400001</p>
            <p style="font-size: 12px;">Phone: +91 98765 43210 | Email: info@medixpro.com</p>
        </div>

        <!-- Bill Details -->
        <div class="bill-details">
            <div class="row">
                <span class="label">Bill Number</span>
                <span class="value"><?php echo htmlspecialchars($billData["bill_no"]); ?></span>
            </div>
            <div class="row">
                <span class="label">Bill Date</span>
                <span class="value"><?php echo date("d/m/Y", strtotime($billData["bill_date"])); ?></span>
            </div>
            <div class="row">
                <span class="label">Patient Name</span>
                <span class="value"><?php echo htmlspecialchars($billData["patient_name"]); ?></span>
            </div>
            <div class="row">
                <span class="label">Mobile</span>
                <span class="value"><?php echo htmlspecialchars($billData["mobile"]); ?></span>
            </div>
            <div class="row">
                <span class="label">Email</span>
                <span class="value"><?php echo htmlspecialchars($billData["email"]); ?></span>
            </div>
            <div class="row">
                <span class="label">Address</span>
                <span class="value"><?php echo htmlspecialchars($billData["address"]); ?></span>
            </div>
            <div class="row">
                <span class="label">Status</span>
                <span class="value">
                    <span class="status-badge status-<?php echo strtolower($status); ?>"><?php echo $status; ?></span>
                </span>
            </div>
        </div>

        <!-- Bill Items Table -->
        <table class="bill-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service Name</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Rate (₹)</th>
                    <th style="text-align: right;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?php echo htmlspecialchars($billData["service_name"]); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($billData["qty"]); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($billData["rate"], 2); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($billData["total"], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Amount Summary -->
        <div class="amount-summary">
            <div class="row">
                <span class="label">Subtotal</span>
              <?php
$subtotal = $billData["qty"] * $billData["rate"];

$discountPercent = $billData["discount"];

$discountAmount = ($subtotal * $discountPercent) / 100;

$grandTotal = $subtotal - $discountAmount;

$pendingAmount = $grandTotal - $billData["paid_amount"];

if($pendingAmount < 0){
    $pendingAmount = 0;
}
?>

<div class="amount-summary">

    <div class="row">
        <span class="label">Subtotal</span>
        <span class="value">₹<?php echo number_format($subtotal,2); ?></span>
    </div>

    <div class="row">
        <span class="label">Discount (<?php echo $discountPercent; ?>%)</span>
        <span class="value">
            - ₹<?php echo number_format($discountAmount,2); ?>
        </span>
    </div>

    <div class="row total">
        <span class="label">Grand Total</span>
        <span class="value">
            ₹<?php echo number_format($grandTotal,2); ?>
        </span>
    </div>

    <div class="row">
        <span class="label">Paid Amount</span>
        <span class="value">
            ₹<?php echo number_format($billData["paid_amount"],2); ?>
        </span>
    </div>

    <div class="row">
        <span class="label">Pending Amount</span>
        <span class="value">
            ₹<?php echo number_format($pendingAmount,2); ?>
        </span>
    </div>

</div>
            <div class="row">
                <span class="label">Paid Amount</span>
                <span class="value">₹<?php echo number_format($billData["paid_amount"], 2); ?></span>
            </div>
            <div class="row">
                <span class="label">Pending Amount</span>
                <span class="value">₹<?php echo number_format($billData["pending_amount"], 2); ?></span>
            </div>
        </div>

        <?php if (!empty($billData["remark"])): ?>
        <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px;">
            <p style="font-size: 14px; color: #334155; font-weight: 500;">Remark:</p>
            <p style="font-size: 14px; color: #475569; margin-top: 5px;"><?php echo htmlspecialchars($billData["remark"]); ?></p>
        </div>
        <?php endif; ?>

        <div class="bill-footer">
            <p>Thank you for your business!</p>
            <p>Generated on <?php echo date("d/m/Y H:i:s"); ?></p>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>

<?php $conn->close(); ?>

