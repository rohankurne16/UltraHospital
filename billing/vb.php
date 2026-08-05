<?php 
session_start(); 
include "config/db.php";

// Check if user is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: billing.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch bill details with patient information
$billQuery = "SELECT b.*, p.patient_name, p.mobile, p.email, p.address 
              FROM billing b
              LEFT JOIN patients p ON b.patient_id = p.patient_id
              WHERE b.id = '$id' AND (b.delete_flag=0 OR b.delete_flag IS NULL)";
$billResult = $conn->query($billQuery);

if ($billResult->num_rows == 0) {
    header("Location: billing.php");
    exit();
}

$billData = $billResult->fetch_assoc();

// Determine status
$status = 'Paid';
$statusClass = 'status-paid';
if ($billData['pending_amount'] > 0 && $billData['paid_amount'] > 0) {
    $status = 'Partial';
    $statusClass = 'status-partial';
} elseif ($billData['pending_amount'] > 0 && $billData['paid_amount'] == 0) {
    $status = 'Pending';
    $statusClass = 'status-pending';
}

$subtotal = $billData['qty'] * $billData['rate'];
$discount_percent = $billData['discount'];
$discount_amount = ($subtotal * $discount_percent) / 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - View Bill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .detail-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .detail-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .detail-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .detail-card .body { padding: 20px 24px; }
        .detail-item { display: flex; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .detail-item:last-child { border-bottom: none; }
        .detail-item .label { font-size: 13px; color: #64748b; width: 140px; flex-shrink: 0; font-weight: 500; }
        .detail-item .value { font-size: 14px; color: #0f172a; font-weight: 500; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-partial { background: #dbeafe; color: #1e40af; }
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 500; text-decoration: none; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-success { background: #22c55e; color: white; }
        .amount-box { background: #f8fafc; border-radius: 8px; padding: 12px 16px; text-align: center; }
        .amount-box .label { font-size: 12px; color: #64748b; }
        .amount-box .value { font-size: 20px; font-weight: 700; color: #0f172a; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'staff_sidebar.php'; ?>
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <a href="billing.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Bill Details</h1>
                                <p class="text-gray-500">View complete bill information</p>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="detail-card">
                                <div class="header"><h3>Bill Information</h3></div>
                                <div class="body">
                                    <div class="detail-item">
                                        <span class="label">Bill Number</span>
                                        <span class="value font-semibold text-blue-600"><?php echo htmlspecialchars($billData['bill_no']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Bill Date</span>
                                        <span class="value"><?php echo date('l, F j, Y', strtotime($billData['bill_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient</span>
                                        <span class="value"><?php echo htmlspecialchars($billData['patient_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Service</span>
                                        <span class="value"><?php echo htmlspecialchars($billData['service_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Quantity</span>
                                        <span class="value"><?php echo htmlspecialchars($billData['qty']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Rate</span>
                                        <span class="value">₹<?php echo number_format($billData['rate'], 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Payment Mode</span>
                                        <span class="value"><?php echo htmlspecialchars($billData['payment_mode'] ?? 'N/A'); ?></span>
                                    </div>
                                    <?php if ($billData['remark']): ?>
                                    <div class="detail-item">
                                        <span class="label">Remark</span>
                                        <span class="value"><?php echo htmlspecialchars($billData['remark']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="detail-card">
                                <div class="header"><h3>Amount Summary</h3></div>
                                <div class="body space-y-4">
                                    <div class="amount-box">
                                        <div class="label">Subtotal</div>
                                        <div class="value" style="color: #3b82f6;">₹<?php echo number_format($subtotal, 2); ?></div>
                                    </div>
                                    <div class="amount-box">
                                        <div class="label">Discount (<?php echo number_format($discount_percent, 2); ?>%)</div>
                                        <div class="value" style="color: #f59e0b;">- ₹<?php echo number_format($discount_amount, 2); ?></div>
                                    </div>
                                    <div class="amount-box">
                                        <div class="label">Grand Total</div>
                                        <div class="value" style="color: #0f172a;">₹<?php echo number_format($billData['total'], 2); ?></div>
                                    </div>
                                    <div class="amount-box">
                                        <div class="label">Paid Amount</div>
                                        <div class="value" style="color: #22c55e;">₹<?php echo number_format($billData['paid_amount'], 2); ?></div>
                                    </div>
                                    <div class="amount-box" style="background: #fef2f2; border: 1px solid #fecaca;">
                                        <div class="label">Pending Amount</div>
                                        <div class="value" style="color: #ef4444;">₹<?php echo number_format($billData['pending_amount'], 2); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                <a href="edit_bill.php?id=<?php echo $id; ?>" class="action-btn btn-primary justify-center">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Bill
                                </a>
                                <a href="print_bill.php?id=<?php echo $id; ?>" target="_blank" class="action-btn btn-success justify-center">
                                    <i data-lucide="printer" class="w-4 h-4"></i> Print Bill
                                </a>
                                <a href="billing.php" class="action-btn btn-secondary justify-center">
                                    <i data-lucide="list" class="w-4 h-4"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php $conn->close(); ?>
