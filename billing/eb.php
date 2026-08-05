<?php 
session_start(); 
include "../config/db.php";

if (!isset($_SESSION["staff_id"]) && !isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("Location: billing.php");
    exit();
}

$bill_id = mysqli_real_escape_string($conn, $_GET["id"]);

$fetchBillQuery = "SELECT * FROM billing WHERE id = '$bill_id' AND (delete_flag=0 OR delete_flag IS NULL)";
$fetchBillResult = $conn->query($fetchBillQuery);

if ($fetchBillResult->num_rows == 0) {
    header("Location: billing.php");
    exit();
}

$billData = $fetchBillResult->fetch_assoc();

$patientQuery = "SELECT patient_id, patient_name FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY patient_name ASC";
$patientResult = $conn->query($patientQuery);
$patients = array();
if ($patientResult && $patientResult->num_rows > 0) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $bill_no = mysqli_real_escape_string($conn, $_POST["bill_no"]);
    $patient_id = mysqli_real_escape_string($conn, $_POST["patient_id"]);
    $bill_date = mysqli_real_escape_string($conn, $_POST["bill_date"]);
    $service_name = mysqli_real_escape_string($conn, $_POST["service_name"]);
    $qty = mysqli_real_escape_string($conn, $_POST["qty"]);
    $rate = mysqli_real_escape_string($conn, $_POST["rate"]);
    $total = mysqli_real_escape_string($conn, $_POST["total"]);
    $discount = mysqli_real_escape_string($conn, $_POST["discount_percentage"]);
    $paid_amount = mysqli_real_escape_string($conn, $_POST["paid_amount"]);
    $pending_amount = mysqli_real_escape_string($conn, $_POST["pending_amount"]);
    $payment_mode = mysqli_real_escape_string($conn, $_POST["payment_mode"]);
    $remark = mysqli_real_escape_string($conn, $_POST["remark"]);

    if (empty($patient_id) || empty($service_name)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $updateQuery = "UPDATE billing SET 
            bill_no = '$bill_no', 
            patient_id = '$patient_id', 
            bill_date = '$bill_date', 
            service_name = '$service_name', 
            qty = '$qty', 
            rate = '$rate', 
            total = '$total', 
            discount = '$discount', 
            paid_amount = '$paid_amount', 
            pending_amount = '$pending_amount', 
            payment_mode = '$payment_mode', 
            remark = '$remark'
            WHERE id = '$bill_id'";

        if ($conn->query($updateQuery) === TRUE) {
            echo "<script>alert('Bill updated successfully!'); window.location='billing_list.php';</script>";
            exit();
        } else {
            $message = "Error updating bill: " . $conn->error;
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Edit Bill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .form-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .form-card .body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #0f172a; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; outline: none; background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; }
        .total-box { background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; }
        .total-box .label { font-size: 12px; color: #64748b; }
        .total-box .value { font-size: 24px; font-weight: 700; color: #0f172a; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff/staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="billing.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit Bill</h1>
                            <p class="text-gray-500">Update the bill information below.</p>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="header"><h3>Bill Details</h3></div>
                        <div class="body">
                            <form action="edit_bill.php?id=<?php echo $bill_id; ?>" method="POST" id="billForm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                    <div class="form-group">
                                        <label>Bill No</label>
                                        <input type="text" name="bill_no" value="<?php echo htmlspecialchars($billData['bill_no']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Bill Date</label>
                                        <input type="date" name="bill_date" value="<?php echo $billData['bill_date']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Patient Name</label>
                                        <select name="patient_id" required>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['patient_id']; ?>" <?php echo ($patient['patient_id'] == $billData['patient_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($patient['patient_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Mode</label>
                                        <select name="payment_mode" required>
                                            <option value="Cash" <?php echo ($billData['payment_mode'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                            <option value="Card" <?php echo ($billData['payment_mode'] == 'Card') ? 'selected' : ''; ?>>Card</option>
                                            <option value="UPI" <?php echo ($billData['payment_mode'] == 'UPI') ? 'selected' : ''; ?>>UPI</option>
                                            <option value="Net Banking" <?php echo ($billData['payment_mode'] == 'Net Banking') ? 'selected' : ''; ?>>Net Banking</option>
                                        </select>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mt-6 mb-4">Service Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div class="form-group md:col-span-2">
                                        <label>Service Name</label>
                                        <input type="text" id="service_name_input" value="<?php echo htmlspecialchars($billData['service_name']); ?>" onkeyup="updateCalculations()">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label>Qty</label>
                                            <input type="number" id="qty_input" value="<?php echo $billData['qty']; ?>" min="1" onchange="updateCalculations()">
                                        </div>
                                        <div class="form-group">
                                            <label>Rate</label>
                                            <input type="number" id="rate_input" value="<?php echo $billData['rate']; ?>" step="0.01" onchange="updateCalculations()">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                    <div class="form-group">
                                        <label>Discount (%)</label>
                                        <input type="number" id="discount_percentage" name="discount_percentage" value="<?php echo $billData['discount']; ?>" step="0.01" onchange="updateCalculations()">
                                    </div>
                                    <div class="form-group">
                                        <label>Paid Amount</label>
                                        <input type="number" id="paid_amount" name="paid_amount" value="<?php echo $billData['paid_amount']; ?>" step="0.01" onchange="updateCalculations()" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                                    <div class="total-box"><div class="label">Subtotal</div><div class="value">₹<span id="displaySubtotal">0.00</span></div></div>
                                    <div class="total-box"><div class="label">Discount Amt</div><div class="value">₹<span id="displayDiscount">0.00</span></div></div>
                                    <div class="total-box"><div class="label">Grand Total</div><div class="value">₹<span id="displayGrandTotal">0.00</span></div></div>
                                    <div class="total-box" style="background: #fef2f2; border-color: #fecaca;"><div class="label">Pending</div><div class="value" style="color: #ef4444;">₹<span id="displayPending">0.00</span></div></div>
                                </div>

                                <div class="form-group mt-6"><label>Remark</label><textarea name="remark"><?php echo htmlspecialchars($billData['remark']); ?></textarea></div>

                                <input type="hidden" id="service_name" name="service_name" value="<?php echo htmlspecialchars($billData['service_name']); ?>">
                                <input type="hidden" id="qty" name="qty" value="<?php echo $billData['qty']; ?>">
                                <input type="hidden" id="rate" name="rate" value="<?php echo $billData['rate']; ?>">
                                <input type="hidden" id="total" name="total" value="<?php echo $billData['total']; ?>">
                                <input type="hidden" id="pending_amount" name="pending_amount" value="<?php echo $billData['pending_amount']; ?>">

                                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                                    <a href="billing.php" class="btn-secondary">Cancel</a>
                                    <button type="submit" name="submit" class="btn-primary">Update Bill</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        lucide.createIcons();
        function updateCalculations() {
            const qty = parseFloat(document.getElementById('qty_input').value) || 0;
            const rate = parseFloat(document.getElementById('rate_input').value) || 0;
            const subtotal = qty * rate;
            const discountPercent = parseFloat(document.getElementById('discount_percentage').value) || 0;
            const discountAmount = (subtotal * discountPercent) / 100;
            const grandTotal = subtotal - discountAmount;
            const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            const pending = Math.max(0, grandTotal - paid);

            document.getElementById('displaySubtotal').textContent = subtotal.toFixed(2);
            document.getElementById('displayDiscount').textContent = discountAmount.toFixed(2);
            document.getElementById('displayGrandTotal').textContent = grandTotal.toFixed(2);
            document.getElementById('displayPending').textContent = pending.toFixed(2);

            document.getElementById('service_name').value = document.getElementById('service_name_input').value;
            document.getElementById('qty').value = qty;
            document.getElementById('rate').value = rate.toFixed(2);
            document.getElementById('total').value = grandTotal.toFixed(2);
            document.getElementById('pending_amount').value = pending.toFixed(2);
        }
        window.onload = updateCalculations;
    </script>
</body>
</html>
<?php $conn->close(); ?>
