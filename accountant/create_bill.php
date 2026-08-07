<?php
// ============================================================
// CREATE BILL – Step-by-step wizard with AJAX patient search
// ============================================================

session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

if (!hasPermission('billing-create')) {
    header("Location: billing_list.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process each step – we'll handle via session or hidden fields.
    // For simplicity, we show wizard UI.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bill - Accountant</title>
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

        .card { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; margin-bottom:24px; }
        .wizard-header { display:flex; gap:16px; margin-bottom:24px; border-bottom:2px solid #e2e8f0; padding-bottom:16px; }
        .step { flex:1; text-align:center; padding:10px; border-radius:8px; font-weight:600; background:#f1f5f9; color:#94a3b8; position:relative; }
        .step.active { background:#ed8936; color:white; }
        .step.done { background:#d1fae5; color:#065f46; }
        .step .num { display:inline-block; width:28px; height:28px; line-height:28px; border-radius:50%; background:white; color:#1a202c; margin-right:6px; }
        .step.active .num { background:rgba(255,255,255,0.3); color:white; }

        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; font-size:13px; color:#475569; margin-bottom:4px; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#f8fafc; }
        .form-control:focus { border-color:#ed8936; outline:none; box-shadow:0 0 0 3px rgba(237,137,54,0.1); }
        .btn { padding:10px 24px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#ed8936; color:white; }
        .btn-primary:hover { background:#d97706; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .btn-secondary:hover { background:#cbd5e1; }
        .flex { display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media (max-width:768px){ .grid-2{ grid-template-columns:1fr; } }

        .search-result { border:1px solid #e2e8f0; border-radius:8px; max-height:200px; overflow-y:auto; background:white; }
        .search-result .item { padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:0.2s; }
        .search-result .item:hover { background:#f8fafc; }
        .item-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
        .item-row .remove { color:#e53e3e; cursor:pointer; }
        .selected-patient { padding:16px; background:#f8fafc; border-radius:8px; margin-top:12px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <!-- Greeting -->
            <div class="greeting-gradient">
                <div>
                    <h1><i class="fas fa-plus-circle"></i> Create New Bill</h1>
                    <p>Follow the steps to generate a bill</p>
                </div>
                <a href="billing_list.php" class="btn" style="background:rgba(255,255,255,0.15); color:white;">Back to List</a>
            </div>

            <!-- Wizard Steps -->
            <div class="wizard-header">
                <div class="step <?php echo $step>=1?'active':''; ?>"><span class="num">1</span> Select Patient</div>
                <div class="step <?php echo $step>=2?'active':''; ?>"><span class="num">2</span> Add Services</div>
                <div class="step <?php echo $step>=3?'active':''; ?>"><span class="num">3</span> Payment</div>
            </div>

            <div class="card">
                <?php if ($step == 1): ?>
                <!-- Step 1: Patient Search -->
                <h3 style="margin-bottom:16px;">Select Patient</h3>
                <div class="form-group">
                    <label>Search Patient</label>
                    <input type="text" id="patientSearch" class="form-control" placeholder="Type patient name or ID..." onkeyup="searchPatient(this.value)">
                    <div id="searchResults" class="search-result" style="display:none;"></div>
                </div>
                <div id="selectedPatient" class="selected-patient" style="display:none;">
                    <strong id="selectedName"></strong><br>
                    <span id="selectedDetails"></span>
                    <input type="hidden" id="patientId" value="0">
                </div>
                <div class="flex" style="margin-top:20px;">
                    <a href="billing_list.php" class="btn btn-secondary">Cancel</a>
                    <button class="btn btn-primary" onclick="goStep(2)">Next <i class="fas fa-arrow-right"></i></button>
                </div>
                <?php elseif ($step == 2): ?>
                <!-- Step 2: Services -->
                <h3 style="margin-bottom:16px;">Add Services / Items</h3>
                <div id="serviceContainer">
                    <div class="item-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                        <input type="text" placeholder="Service name" class="form-control" style="flex:2;" id="serviceName">
                        <input type="number" placeholder="Qty" class="form-control" style="flex:1;" id="serviceQty" value="1">
                        <input type="number" placeholder="Rate" class="form-control" style="flex:1;" id="serviceRate" step="0.01">
                        <button class="btn btn-primary" onclick="addServiceItem()" style="flex:0;">Add</button>
                    </div>
                    <div id="serviceList" style="margin-top:16px;"></div>
                </div>
                <div style="margin-top:16px; background:#f8fafc; padding:16px; border-radius:8px;">
                    <strong>Total: ₹ <span id="totalAmount">0.00</span></strong>
                </div>
                <div class="flex" style="margin-top:20px;">
                    <button class="btn btn-secondary" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn btn-primary" onclick="goStep(3)">Next <i class="fas fa-arrow-right"></i></button>
                </div>
                <?php elseif ($step == 3): ?>
                <!-- Step 3: Payment -->
                <h3 style="margin-bottom:16px;">Payment & Summary</h3>
                <div class="grid-2">
                    <div>
                        <div class="form-group"><label>Payment Mode</label>
                            <select class="form-control" id="paymentMode">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank">Bank</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Paid Amount</label>
                            <input type="number" class="form-control" id="paidAmount" step="0.01" placeholder="Enter amount">
                        </div>
                        <div class="form-group"><label>Remark</label>
                            <input type="text" class="form-control" id="remark" placeholder="Optional remark">
                        </div>
                    </div>
                    <div style="background:#f8fafc; padding:20px; border-radius:12px;">
                        <h4>Bill Summary</h4>
                        <div class="item-row"><span>Subtotal:</span><span>₹ <span id="summaryTotal">0.00</span></span></div>
                        <div class="item-row"><span>Paid:</span><span>₹ <span id="summaryPaid">0.00</span></span></div>
                        <div class="item-row"><span>Pending:</span><span>₹ <span id="summaryPending">0.00</span></span></div>
                        <hr style="margin:12px 0;">
                        <button class="btn btn-primary" style="width:100%;" onclick="submitBill()"><i class="fas fa-check"></i> Generate Bill</button>
                    </div>
                </div>
                <div class="flex" style="margin-top:20px;">
                    <button class="btn btn-secondary" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                    <a href="billing_list.php" class="btn btn-secondary">Cancel</a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let selectedPatientId = 0;
        let serviceItems = [];

        function searchPatient(query) {
            if (query.length < 2) {
                document.getElementById('searchResults').style.display = 'none';
                return;
            }
            fetch(`../ajax/search_patient.php?q=${encodeURIComponent(query)}&hospital_id=<?php echo $hospital_id; ?>`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('searchResults');
                    container.innerHTML = '';
                    if (data.length > 0) {
                        container.style.display = 'block';
                        data.forEach(p => {
                            const div = document.createElement('div');
                            div.className = 'item';
                            div.innerHTML = `<strong>${p.patient_name}</strong> (ID: ${p.patient_id})`;
                            div.onclick = () => selectPatient(p);
                            container.appendChild(div);
                        });
                    } else {
                        container.style.display = 'block';
                        container.innerHTML = '<div class="item" style="color:#94a3b8;">No patients found</div>';
                    }
                });
        }

        function selectPatient(p) {
            selectedPatientId = p.patient_id;
            document.getElementById('patientId').value = p.patient_id;
            document.getElementById('selectedName').textContent = p.patient_name;
            document.getElementById('selectedDetails').textContent = `Mobile: ${p.mobile || 'N/A'} | Gender: ${p.gender || 'N/A'}`;
            document.getElementById('selectedPatient').style.display = 'block';
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('patientSearch').value = p.patient_name;
        }

        function goStep(step) {
            if (step == 2 && selectedPatientId == 0) {
                alert('Please select a patient first.');
                return;
            }
            if (step == 3 && serviceItems.length == 0) {
                alert('Please add at least one service item.');
                return;
            }
            if (step == 3) {
                let total = serviceItems.reduce((sum, item) => sum + item.qty * item.rate, 0);
                document.getElementById('summaryTotal').textContent = total.toFixed(2);
                document.getElementById('totalAmount').textContent = total.toFixed(2);
                document.getElementById('summaryPaid').textContent = '0.00';
                document.getElementById('summaryPending').textContent = total.toFixed(2);
            }
            window.location.href = `create_bill.php?step=${step}&patient_id=${selectedPatientId}`;
        }

        function addServiceItem() {
            const name = document.getElementById('serviceName').value.trim();
            const qty = parseInt(document.getElementById('serviceQty').value) || 1;
            const rate = parseFloat(document.getElementById('serviceRate').value) || 0;
            if (!name || rate <= 0) { alert('Please enter service name and rate'); return; }
            serviceItems.push({ name, qty, rate });
            renderServiceList();
            document.getElementById('serviceName').value = '';
            document.getElementById('serviceQty').value = 1;
            document.getElementById('serviceRate').value = '';
        }

        function removeServiceItem(index) {
            serviceItems.splice(index, 1);
            renderServiceList();
        }

        function renderServiceList() {
            const container = document.getElementById('serviceList');
            container.innerHTML = '';
            let total = 0;
            serviceItems.forEach((item, i) => {
                const div = document.createElement('div');
                div.className = 'item-row';
                div.innerHTML = `
                    <span>${item.name} (${item.qty} x ₹${item.rate.toFixed(2)})</span>
                    <span>₹${(item.qty * item.rate).toFixed(2)} <span class="remove" onclick="removeServiceItem(${i})"><i class="fas fa-trash-alt"></i></span></span>
                `;
                container.appendChild(div);
                total += item.qty * item.rate;
            });
            document.getElementById('totalAmount').textContent = total.toFixed(2);
        }

        function submitBill() {
            const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
            const mode = document.getElementById('paymentMode').value;
            const remark = document.getElementById('remark').value;
            const total = serviceItems.reduce((sum, item) => sum + item.qty * item.rate, 0);
            if (paid > total) { alert('Paid amount cannot exceed total.'); return; }

            const data = {
                patient_id: selectedPatientId,
                items: serviceItems,
                total: total,
                paid: paid,
                mode: mode,
                remark: remark,
                hospital_id: <?php echo $hospital_id; ?>
            };
            fetch('create_bill_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    window.location.href = `view_bill.php?id=${result.bill_id}`;
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }
    </script>
</body>
</html>