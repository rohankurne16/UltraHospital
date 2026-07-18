<?php 
session_start(); 
include "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION["staff_id"]) && !isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit();
}

// Fetch patients for dropdown
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

// Generate bill number
$billNoQuery = "SELECT COUNT(*) AS count FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL)";
$billNoResult = $conn->query($billNoQuery);
$billCount = $billNoResult->fetch_assoc();
$bill_number = "INV-" . str_pad(($billCount["count"] + 1), 4, "0", STR_PAD_LEFT);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $bill_no = mysqli_real_escape_string($conn, $_POST["bill_no"]);
    $patient_id = mysqli_real_escape_string($conn, $_POST["patient_id"]);
    $bill_date = mysqli_real_escape_string($conn, $_POST["bill_date"]);
    $service_name = mysqli_real_escape_string($conn, $_POST["service_name"]);
    $qty = mysqli_real_escape_string($conn, $_POST["qty"]);
    $rate = mysqli_real_escape_string($conn, $_POST["rate"]);
    $total = mysqli_real_escape_string($conn, $_POST["total"]);
    $discount = mysqli_real_escape_string($conn, $_POST["discount_percentage"]); // Now storing percentage
    $paid_amount = mysqli_real_escape_string($conn, $_POST["paid_amount"]);
    $pending_amount = mysqli_real_escape_string($conn, $_POST["pending_amount"]);
    $payment_mode = mysqli_real_escape_string($conn, $_POST["payment_mode"]);
    $remark = mysqli_real_escape_string($conn, $_POST["remark"]);

    if (empty($patient_id) || empty($service_name)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $insertQuery = "INSERT INTO billing (
            bill_no, patient_id, bill_date, service_name, qty, rate, total, 
            discount, paid_amount, pending_amount, payment_mode, remark, delete_flag
        ) VALUES (
            '$bill_no', '$patient_id', '$bill_date', '$service_name', '$qty', '$rate', '$total',
            '$discount', '$paid_amount', '$pending_amount', '$payment_mode', '$remark', 0
        )";

        if ($conn->query($insertQuery) === TRUE) {
            $message = "Bill created successfully!";
            $messageType = "success";
            echo "<script>alert('Bill created successfully!'); window.location='billing.php';</script>";
            exit();
        } else {
            $message = "Error creating bill: " . $conn->error;
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
    <title>MedixPro - Create Bill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .form-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .form-card .body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #0f172a; margin-bottom: 4px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; transition: all 0.2s ease; outline: none; background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group input[readonly] { background: #f1f5f9; cursor: not-allowed; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-success { padding: 10px 24px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-success:hover { background: #16a34a; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .total-box { background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; }
        .total-box .label { font-size: 12px; color: #64748b; }
        .total-box .value { font-size: 24px; font-weight: 700; color: #0f172a; }
        .table-header { background: #f8fafc; font-weight: 600; color: #475569; }
        .remove-btn { color: #ef4444; cursor: pointer; transition: all 0.2s ease; }
        .remove-btn:hover { transform: scale(1.1); color: #dc2626; }
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
                        <a href="billing.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Create New Bill</h1>
                            <p class="text-gray-500">Fill out the form to generate a new bill.</p>
                        </div>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?> fade-in">
                            <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'x-circle'; ?>" class="w-5 h-5"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-card">
                        <div class="header">
                            <h3>Bill Details</h3>
                        </div>
                        <div class="body">
                            <form action="create_bill.php" method="POST" id="billForm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                    <div class="form-group">
                                        <label for="bill_no">Bill No <span class="required">*</span></label>
                                        <input type="text" id="bill_no" name="bill_no" value="<?php echo $bill_number; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="bill_date">Bill Date <span class="required">*</span></label>
                                        <input type="date" id="bill_date" name="bill_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="patient_id">Patient Name <span class="required">*</span></label>
                                        <select id="patient_id" name="patient_id" required>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['patient_id']; ?>">
                                                    <?php echo htmlspecialchars($patient['patient_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="payment_mode">Payment Mode <span class="required">*</span></label>
                                        <select id="payment_mode" name="payment_mode" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Net Banking">Net Banking</option>
                                        </select>
                                    </div>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 mt-6 mb-4">Service Details</h4>
                                <div class="overflow-x-auto mb-6">
                                    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                                        <thead>
                                            <tr class="table-header">
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service Name</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            <!-- Dynamic rows will be added here -->
                                        </tbody>
                                    </table>
                                    <button type="button" onclick="addRow()" class="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600 transition-all">
                                        <i data-lucide="plus" class="w-4 h-4 inline-block mr-2"></i>Add Item
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                    <div class="form-group">
                                        <label for="discount_percentage">Discount (%)</label>
                                        <input type="number" id="discount_percentage" name="discount_percentage" value="0.00" step="0.01" onkeyup="calculateGrandTotal()" onchange="calculateGrandTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label for="paid_amount">Paid Amount <span class="required">*</span></label>
                                        <input type="number" id="paid_amount" name="paid_amount" value="0.00" step="0.01" onkeyup="calculatePending()" onchange="calculatePending()" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                                    <div class="total-box">
                                        <div class="label">Subtotal</div>
                                        <div class="value">₹<span id="displaySubtotal">0.00</span></div>
                                    </div>
                                    <div class="total-box">
                                        <div class="label">Discount Amt</div>
                                        <div class="value">₹<span id="displayDiscount">0.00</span></div>
                                    </div>
                                    <div class="total-box">
                                        <div class="label">Grand Total</div>
                                        <div class="value">₹<span id="displayGrandTotal">0.00</span></div>
                                    </div>
                                    <div class="total-box" style="background: #fef2f2; border-color: #fecaca;">
                                        <div class="label">Pending</div>
                                        <div class="value" style="color: #ef4444;">₹<span id="displayPending">0.00</span></div>
                                    </div>
                                </div>

                                <div class="form-group mt-6">
                                    <label for="remark">Remark</label>
                                    <textarea id="remark" name="remark" placeholder="Enter any additional information..."></textarea>
                                </div>

                                <!-- Hidden fields for form submission -->
                                <input type="hidden" id="service_name" name="service_name">
                                <input type="hidden" id="qty" name="qty">
                                <input type="hidden" id="rate" name="rate">
                                <input type="hidden" id="total" name="total">
                                <input type="hidden" id="pending_amount" name="pending_amount">

                                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                                    <a href="billing.php" class="btn-secondary">Cancel</a>
                                    <button type="submit" name="submit" class="btn-primary">Generate Bill</button>
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

        let rowCount = 0;

        function addRow() {
            rowCount++;
            const tbody = document.getElementById("itemsBody");
            const newRow = document.createElement("tr");
            newRow.className = "table-row item-row";
            newRow.innerHTML = `
                <td class="px-3 py-2 text-gray-500 row-number">${rowCount}</td>
                <td class="px-3 py-2">
                    <input type="text" class="service-name-input w-full p-2 border border-gray-200 rounded-md text-sm" placeholder="Service name" onkeyup="updateServiceData()">
                </td>
                <td class="px-3 py-2">
                    <input type="number" class="qty-input w-full p-2 border border-gray-200 rounded-md text-sm text-center" value="1" min="1" onkeyup="calculateRowTotal(this)" onchange="calculateRowTotal(this)">
                </td>
                <td class="px-3 py-2">
                    <input type="number" class="rate-input w-full p-2 border border-gray-200 rounded-md text-sm text-right" value="0.00" step="0.01" onkeyup="calculateRowTotal(this)" onchange="calculateRowTotal(this)">
                </td>
                <td class="px-3 py-2 text-right font-medium row-total">₹0.00</td>
                <td class="px-3 py-2 text-center">
                    <button type="button" class="remove-btn" onclick="removeRow(this)" title="Remove item">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
            lucide.createIcons();
            updateRowNumbers();
            calculateGrandTotal();
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll(".item-row");
            if (rows.length <= 1) {
                alert("At least one item is required!");
                return;
            }
            btn.closest("tr").remove();
            updateRowNumbers();
            calculateGrandTotal();
        }

        function updateRowNumbers() {
            const rows = document.querySelectorAll(".item-row");
            rows.forEach((row, index) => {
                row.querySelector(".row-number").textContent = index + 1;
            });
            rowCount = rows.length;
        }

        function calculateRowTotal(input) {
            const row = input.closest("tr");
            const qty = parseFloat(row.querySelector(".qty-input").value) || 0;
            const rate = parseFloat(row.querySelector(".rate-input").value) || 0;
            const total = qty * rate;
            row.querySelector(".row-total").textContent = '₹' + total.toFixed(2);
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            const rows = document.querySelectorAll(".item-row");
            let subtotal = 0;
            let totalQty = 0;
            let totalRate = 0;
            let serviceNames = [];

            rows.forEach(row => {
                const qty = parseFloat(row.querySelector(".qty-input").value) || 0;
                const rate = parseFloat(row.querySelector(".rate-input").value) || 0;
                const name = row.querySelector(".service-name-input").value || '';
                
                subtotal += (qty * rate);
                totalQty += qty;
                totalRate += rate;
                if(name) serviceNames.push(name);
            });

            const discountPercent = parseFloat(document.getElementById("discount_percentage").value) || 0;
            const discountAmount = (subtotal * discountPercent) / 100;
            const grandTotal = subtotal - discountAmount;

            document.getElementById("displaySubtotal").textContent = subtotal.toFixed(2);
            document.getElementById("displayDiscount").textContent = discountAmount.toFixed(2);
            document.getElementById("displayGrandTotal").textContent = grandTotal.toFixed(2);
            
            // Set hidden fields
            document.getElementById("service_name").value = serviceNames.join(", ");
            document.getElementById("qty").value = totalQty;
            document.getElementById("rate").value = rows.length > 0 ? (totalRate / rows.length).toFixed(2) : 0;
            document.getElementById("total").value = grandTotal.toFixed(2);
            
            calculatePending();
        }

        function calculatePending() {
            const grandTotal = parseFloat(document.getElementById("displayGrandTotal").textContent) || 0;
            const paid = parseFloat(document.getElementById("paid_amount").value) || 0;
            const pending = Math.max(0, grandTotal - paid);
            
            document.getElementById("displayPending").textContent = pending.toFixed(2);
            document.getElementById("pending_amount").value = pending.toFixed(2);
        }

        function updateServiceData() {
            const rows = document.querySelectorAll(".item-row");
            let serviceNames = [];
            rows.forEach(row => {
                const name = row.querySelector(".service-name-input").value || '';
                if(name) serviceNames.push(name);
            });
            document.getElementById("service_name").value = serviceNames.join(", ");
        }

        // Initialize with one row
        window.onload = function() {
            addRow();
        };
    </script>
</body>
</html>
<?php $conn->close(); ?>
