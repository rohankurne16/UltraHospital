<?php
// ============================================================
// CREATE BILL – Step-by-step wizard with embedded AJAX search
// ============================================================

session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

// ============================================================
// AJAX ENDPOINT – Search patients from register table
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $hospital_id = (int)($_GET['hospital_id'] ?? 0);
    $query = trim($_GET['q'] ?? '');
    
    $response = [];
    if (strlen($query) >= 2 && $hospital_id > 0) {
        $like = '%' . $conn->real_escape_string($query) . '%';
        $sql = "
            SELECT id, name, email, role
            FROM register
            WHERE hospital_id = $hospital_id
            AND role_id = 8          -- Patient role
            AND delete_flag = 0
            AND name LIKE '$like'
            ORDER BY name ASC
            LIMIT 15
        ";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $response[] = $row;
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// ============================================================
// PERMISSION CHECK
// ============================================================
if (!function_exists('hasPermission') || !hasPermission('billing-create')) {
    header("Location: billing_list.php");
    exit();
}

 $hospital_id = $_SESSION['hospital_id'] ?? 0;
 $step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
 $patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bill - Accountant</title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? 'favicon.ico'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.01);
            --hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: var(--dark); line-height: 1.5; }
        a { text-decoration: none; }
        
        /* Layout */
        .main-wrapper { display: flex; min-height: 100vh; background: #f1f5f9; }
.main-content {
    margin-left: 260px;
    padding: 0px 15px 61px;
    width: calc(100% - 260px);
    min-height: calc(100vh - 67px);
    background: #f1f5f9;
    margin-top: 23px;
        }
                @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; width: 100%; } }
        
        .wizard-container { max-width: 900px; margin: 0 auto; }
        
        /* Hero Header */
        .hero-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 16px; margin-bottom: 28px; padding: 24px 32px; color: white;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
        }
        .hero-card h1 { font-size: 22px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
        .hero-card p { font-size: 14px; opacity: 0.9; margin-top: 4px; }
        .btn-glass { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 8px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-glass:hover { background: rgba(255,255,255,0.3); }
        
        /* Wizard Progress */
        .wizard-progress { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; position: relative; }
        .wizard-progress::before { content: ''; position: absolute; top: 20px; left: 40px; right: 40px; height: 2px; background: var(--slate-200); z-index: 0; }
        .step-indicator { display: flex; flex-direction: column; align-items: center; z-index: 1; background: transparent; position: relative; width: 80px; }
        .step-indicator .circle { width: 40px; height: 40px; border-radius: 50%; background: #fff; border: 2px solid var(--slate-200); color: var(--slate-400); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; transition: 0.3s; margin-bottom: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .step-indicator.active .circle { background: var(--primary); border-color: var(--primary); color: white; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2); }
        .step-indicator.done .circle { background: var(--success); border-color: var(--success); color: white; }
        .step-indicator .label { font-size: 12px; font-weight: 600; color: var(--slate-500); text-align: center; }
        .step-indicator.active .label { color: var(--primary); }
        .step-indicator.done .label { color: var(--success); }
        
        /* Card */
        .card { background: #fff; border-radius: 16px; border: 1px solid var(--slate-200); padding: 28px; margin-bottom: 24px; box-shadow: var(--card-shadow); }
        .card-title { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--slate-100); display: flex; align-items: center; gap: 12px; }
        .card-title i { color: var(--primary); }
        
        /* Form Elements */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 13px; color: var(--slate-600); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 11px 14px; border: 1px solid var(--slate-200); border-radius: 10px; font-size: 14px; background: var(--slate-50); transition: all 0.2s; font-family: 'Inter', sans-serif; }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); background: #fff; }
        
        /* Buttons */
        .btn { padding: 10px 22px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-family: 'Inter', sans-serif; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 10px -2px rgba(79, 70, 229, 0.3); }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(79, 70, 229, 0.4); }
        .btn-secondary { background: #fff; color: var(--slate-600); border: 1px solid var(--slate-200); }
        .btn-secondary:hover { background: var(--slate-50); border-color: var(--slate-300); }
        .btn-success { background: var(--success); color: white; box-shadow: 0 4px 10px -2px rgba(16, 185, 129, 0.3); }
        .btn-success:hover { background: #0ca672; transform: translateY(-1px); }
        
        .actions-bar { display: flex; gap: 12px; justify-content: space-between; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--slate-100); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
        
        /* Search Results */
        .search-result { border: 1px solid var(--slate-200); border-radius: 10px; max-height: 240px; overflow-y: auto; background: #fff; margin-top: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); z-index: 10; position: relative; }
        .search-result .item { padding: 12px 16px; border-bottom: 1px solid var(--slate-100); cursor: pointer; transition: 0.15s; display: flex; justify-content: space-between; align-items: center; }
        .search-result .item:last-child { border-bottom: none; }
        .search-result .item:hover { background: var(--slate-50); }
        .search-result .item .name { font-weight: 600; color: var(--dark); }
        .search-result .item .meta { font-size: 12px; color: var(--slate-500); margin-top: 2px; }
        .search-result .item .badge-role { background: #eef2ff; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        
        /* Selected Patient */
        .selected-patient { padding: 20px; background: #f0f9ff; border-radius: 12px; margin-top: 16px; border: 1px solid #bae6fd; border-left: 4px solid var(--primary); }
        .selected-patient .name { font-weight: 700; font-size: 18px; color: var(--dark); margin-bottom: 8px; }
        .selected-patient .detail { color: var(--slate-600); font-size: 14px; margin-top: 4px; display: flex; align-items: center; gap: 8px; }
        .selected-patient .detail i { color: var(--primary); width: 16px; text-align: center; }
        
        /* Service Items */
        .service-input-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .service-input-row > div { flex: 1; min-width: 120px; }
        .service-input-row > div:first-child { flex: 2; }
        .service-input-row > .btn { flex: 0; height: 42px; margin-bottom: 0; }
        
        .item-list { margin-top: 20px; }
        .item-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid var(--slate-100); border-radius: 10px; margin-bottom: 8px; background: #fff; transition: 0.2s; }
        .item-row:hover { background: var(--slate-50); border-color: var(--slate-200); }
        .item-row .item-name { font-weight: 600; color: var(--dark); font-size: 14px; }
        .item-row .item-meta { font-size: 12px; color: var(--slate-500); }
        .item-row .item-total { font-weight: 700; color: var(--dark); margin-right: 12px; }
        .item-row .remove { color: var(--danger); cursor: pointer; font-size: 14px; transition: 0.2s; padding: 4px 8px; border-radius: 6px; }
        .item-row .remove:hover { background: #fef2f2; }
        
        .service-total { background: var(--slate-50); padding: 16px 20px; border-radius: 10px; text-align: right; font-weight: 700; font-size: 18px; color: var(--dark); margin-top: 16px; border: 1px dashed var(--slate-300); }
        .service-total span { color: var(--primary); }
        
        /* Summary Box */
        .summary-box { background: #fff; border: 1px solid var(--slate-200); padding: 24px; border-radius: 12px; box-shadow: var(--card-shadow); }
        .summary-box h4 { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--slate-100); }
        .summary-box .row-item { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; color: var(--slate-600); }
        .summary-box .row-item span:last-child { font-weight: 600; color: var(--dark); }
        .summary-box .row-item.total { font-weight: 700; font-size: 18px; color: var(--dark); border-top: 1px solid var(--slate-100); margin-top: 8px; padding-top: 12px; }
        
        /* Spinner */
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; margin-right: 8px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .empty-text { text-align: center; color: var(--slate-400); padding: 20px; font-size: 14px; }
    </style>
</head>
<body>

<!-- ===== HEADER & SIDEBAR ===== -->
<?php include '../header.php'; ?>
<?php include '../Sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="wizard-container">
            
            <!-- Hero Header -->
            <div class="hero-card">
                <div>
                    <h1><i class="fas fa-file-invoice"></i> Create New Bill</h1>
                    <p>Follow the steps to generate an invoice</p>
                </div>
                <a href="billing_list.php" class="btn-glass"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <!-- Wizard Progress -->
            <div class="wizard-progress">
                <div class="step-indicator <?php echo $step>=1?'active':''; ?> <?php echo $step>1?'done':''; ?>">
                    <div class="circle"><i class="fas fa-user"></i></div>
                    <span class="label">Patient</span>
                </div>
                <div class="step-indicator <?php echo $step>=2?'active':''; ?> <?php echo $step>2?'done':''; ?>">
                    <div class="circle"><i class="fas fa-cogs"></i></div>
                    <span class="label">Services</span>
                </div>
                <div class="step-indicator <?php echo $step>=3?'active':''; ?>">
                    <div class="circle"><i class="fas fa-credit-card"></i></div>
                    <span class="label">Payment</span>
                </div>
            </div>

            <!-- Card Content -->
            <div class="card">
                <?php if ($step == 1): ?>
                <!-- STEP 1: PATIENT SEARCH -->
                <div class="card-title"><i class="fas fa-user-plus"></i> Select Patient</div>
                <div class="form-group">
                    <label>Search Patient by Name or ID</label>
                    <div style="position: relative;">
                        <input type="text" id="patientSearch" class="form-control" placeholder="Type patient name..." onkeyup="searchPatient(this.value)" autocomplete="off">
                        <span id="searchSpinner" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); display: none;"><i class="fas fa-circle-notch fa-spin" style="color: var(--primary);"></i></span>
                    </div>
                    <div id="searchResults" class="search-result" style="display: none;"></div>
                </div>
                
                <div id="selectedPatient" class="selected-patient" style="display: none;">
                    <div class="name" id="selectedName"></div>
                    <div class="detail"><i class="fas fa-envelope"></i> <span id="selectedEmail"></span></div>
                    <div class="detail"><i class="fas fa-user-tag"></i> <span id="selectedRole"></span></div>
                    <input type="hidden" id="patientId" value="0">
                </div>

                <div class="actions-bar">
                    <a href="billing_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button class="btn btn-primary" onclick="goStep(2)">Continue <i class="fas fa-arrow-right"></i></button>
                </div>

                <?php elseif ($step == 2): ?>
                <!-- STEP 2: SERVICES -->
                <div class="card-title"><i class="fas fa-list-alt"></i> Add Services / Items</div>
                <div id="serviceContainer">
                    <div class="service-input-row">
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: var(--slate-500); margin-bottom: 6px; display: block;">Service Name</label>
                            <input type="text" placeholder="e.g. Doctor Consultation" class="form-control" id="serviceName">
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: var(--slate-500); margin-bottom: 6px; display: block;">Qty</label>
                            <input type="number" class="form-control" id="serviceQty" value="1" min="1">
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: var(--slate-500); margin-bottom: 6px; display: block;">Rate (₹)</label>
                            <input type="number" class="form-control" id="serviceRate" step="0.01" placeholder="0.00">
                        </div>
                        <button class="btn btn-primary" onclick="addServiceItem()"><i class="fas fa-plus"></i> Add</button>
                    </div>
                    
                    <div id="serviceList" class="item-list"></div>
                    
                    <div class="service-total">
                        Total Amount: ₹ <span id="totalAmount">0.00</span>
                    </div>
                </div>
                
                <div class="actions-bar">
                    <button class="btn btn-secondary" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn btn-primary" onclick="goStep(3)">Continue <i class="fas fa-arrow-right"></i></button>
                </div>

                <?php elseif ($step == 3): ?>
                <!-- STEP 3: PAYMENT -->
                <div class="card-title"><i class="fas fa-credit-card"></i> Payment & Summary</div>
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select class="form-control" id="paymentMode">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank">Bank Transfer</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Paid Amount (₹)</label>
                            <input type="number" class="form-control" id="paidAmount" step="0.01" placeholder="Enter amount paid" oninput="updateSummary()">
                        </div>
                        <div class="form-group">
                            <label>Remark / Note</label>
                            <input type="text" class="form-control" id="remark" placeholder="Optional remark">
                        </div>
                    </div>
                    
                    <div class="summary-box">
                        <h4>Bill Summary</h4>
                        <div class="row-item"><span>Subtotal</span><span>₹ <span id="summaryTotal">0.00</span></span></div>
                        <div class="row-item"><span>Paid Amount</span><span style="color: var(--success);">₹ <span id="summaryPaid">0.00</span></span></div>
                        <div class="row-item"><span>Balance Due</span><span style="color: var(--danger);">₹ <span id="summaryPending">0.00</span></span></div>
                        <div class="row-item total"><span>Net Payable</span><span>₹ <span id="summaryTotal">0.00</span></span></div>
                        
                        <button class="btn btn-success" style="width: 100%; margin-top: 20px;" onclick="submitBill()">
                            <i class="fas fa-check-circle"></i> Generate Bill
                        </button>
                    </div>
                </div>
                
                <div class="actions-bar">
                    <button class="btn btn-secondary" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                    <a href="billing_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
</div>

<script>
    // FIX: Restore data from sessionStorage so state isn't lost on page reload
    let selectedPatientId = sessionStorage.getItem('bill_patient_id') ? parseInt(sessionStorage.getItem('bill_patient_id')) : 0;
    let selectedPatient = sessionStorage.getItem('bill_selected_patient') ? JSON.parse(sessionStorage.getItem('bill_selected_patient')) : null;
    let serviceItems = sessionStorage.getItem('bill_service_items') ? JSON.parse(sessionStorage.getItem('bill_service_items')) : [];

    // Restore UI on Page Load
    window.onload = function() {
        // Restore Step 1 UI
        if (document.getElementById('patientId') && selectedPatient) {
            selectPatient(selectedPatient);
        }
        // Restore Step 2 UI
        if (document.getElementById('serviceList')) {
            renderServiceList();
        }
        // Restore Step 3 UI
        if (document.getElementById('summaryTotal')) {
            updateSummary();
        }
    };

    // --- Search Patient ---
    function searchPatient(query) {
        const resultsDiv = document.getElementById('searchResults');
        const spinner = document.getElementById('searchSpinner');
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            spinner.style.display = 'none';
            return;
        }
        spinner.style.display = 'block';
        const url = `create_bill.php?action=search&q=${encodeURIComponent(query)}&hospital_id=<?php echo $hospital_id; ?>`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';
                resultsDiv.innerHTML = '';
                if (data.length > 0) {
                    resultsDiv.style.display = 'block';
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'item';
                        div.innerHTML = `
                            <div>
                                <div class="name">${p.name}</div>
                                <div class="meta">${p.email ? p.email : 'No email'}</div>
                            </div>
                            <span class="badge-role">${p.role || 'Patient'}</span>
                        `;
                        div.onclick = () => selectPatient(p);
                        resultsDiv.appendChild(div);
                    });
                } else {
                    resultsDiv.style.display = 'block';
                    resultsDiv.innerHTML = '<div class="empty-text">No patients found</div>';
                }
            })
            .catch(err => {
                spinner.style.display = 'none';
                resultsDiv.style.display = 'block';
                resultsDiv.innerHTML = '<div class="empty-text" style="color: var(--danger);">Error searching</div>';
                console.error(err);
            });
    }

    // --- Select Patient ---
    function selectPatient(p) {
        selectedPatientId = p.id;
        selectedPatient = p;
        // Save to sessionStorage
        sessionStorage.setItem('bill_patient_id', p.id);
        sessionStorage.setItem('bill_selected_patient', JSON.stringify(p));

        document.getElementById('patientId').value = p.id;
        document.getElementById('selectedName').textContent = p.name;
        document.getElementById('selectedEmail').textContent = p.email || 'N/A';
        document.getElementById('selectedRole').textContent = p.role || 'Patient';
        document.getElementById('selectedPatient').style.display = 'block';
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('patientSearch').value = p.name;
    }

    // --- Navigate steps ---
    function goStep(step) {
        if (step == 2 && selectedPatientId == 0) {
            alert('Please select a patient first.');
            return;
        }
        if (step == 3 && serviceItems.length == 0) {
            alert('Please add at least one service item.');
            return;
        }
        window.location.href = `create_bill.php?step=${step}&patient_id=${selectedPatientId}`;
    }

    // --- Add Service Item ---
    function addServiceItem() {
        const name = document.getElementById('serviceName').value.trim();
        const qty = parseInt(document.getElementById('serviceQty').value) || 1;
        const rate = parseFloat(document.getElementById('serviceRate').value) || 0;
        if (!name || rate <= 0) { alert('Please enter service name and a valid rate.'); return; }
        
        serviceItems.push({ name, qty, rate });
        // Save to sessionStorage
        sessionStorage.setItem('bill_service_items', JSON.stringify(serviceItems));
        
        renderServiceList();
        document.getElementById('serviceName').value = '';
        document.getElementById('serviceQty').value = 1;
        document.getElementById('serviceRate').value = '';
    }

    function removeServiceItem(index) {
        serviceItems.splice(index, 1);
        sessionStorage.setItem('bill_service_items', JSON.stringify(serviceItems));
        renderServiceList();
    }

    function renderServiceList() {
        const container = document.getElementById('serviceList');
        if (!container) return; // Guard clause if not on step 2
        
        container.innerHTML = '';
        let total = 0;
        if (serviceItems.length === 0) {
            container.innerHTML = '<div class="empty-text">No items added yet.</div>';
            document.getElementById('totalAmount').textContent = '0.00';
            return;
        }
        serviceItems.forEach((item, i) => {
            const div = document.createElement('div');
            div.className = 'item-row';
            div.innerHTML = `
                <div>
                    <div class="item-name">${item.name}</div>
                    <div class="item-meta">${item.qty} × ₹${item.rate.toFixed(2)}</div>
                </div>
                <div style="display: flex; align-items: center;">
                    <span class="item-total">₹${(item.qty * item.rate).toFixed(2)}</span>
                    <span class="remove" onclick="removeServiceItem(${i})"><i class="fas fa-trash-alt"></i></span>
                </div>
            `;
            container.appendChild(div);
            total += item.qty * item.rate;
        });
        document.getElementById('totalAmount').textContent = total.toFixed(2);
    }

    // --- Update Summary (Step 3) ---
    function updateSummary() {
        const total = serviceItems.reduce((sum, item) => sum + item.qty * item.rate, 0);
        const paid = parseFloat(document.getElementById('paidAmount')?.value) || 0;
        
        if (document.getElementById('summaryTotal')) {
            document.getElementById('summaryTotal').textContent = total.toFixed(2);
            document.getElementById('summaryPaid').textContent = paid.toFixed(2);
            document.getElementById('summaryPending').textContent = (total - paid).toFixed(2);
        }
    }

    // --- Submit Bill ---
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

        const btn = document.querySelector('.btn-success');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Processing...';

        fetch('create_bill_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                // Clear sessionStorage on successful submission
                sessionStorage.removeItem('bill_patient_id');
                sessionStorage.removeItem('bill_selected_patient');
                sessionStorage.removeItem('bill_service_items');
                
                window.location.href = `view_bill.php?id=${result.bill_id}`;
            } else {
                alert('Error: ' + result.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Generate Bill';
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Generate Bill';
        });
    }
</script>

</body>
</html>