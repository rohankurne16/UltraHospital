<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    $_SESSION['error'] = "Invalid order ID!";
    header("Location: lab_order.php");
    exit();
}

// Get hospital data
$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";

// ========== CANCEL ORDER ==========
if (isset($_GET['cancel_order'])) {
    $conn->query("UPDATE lab_orders SET order_status = 'Cancelled' WHERE order_id = $order_id AND hospital_id = $hid");
    $_SESSION['success'] = "Order cancelled successfully!";
    header("Location: order_details.php?id=$order_id");
    exit();
}

// ========== GET ORDER DETAILS ==========
$sql = "SELECT 
        o.*,
        COALESCE(p.patient_name, 'Patient Deleted') AS patient_name,
        COALESCE(p.mobile, '') AS mobile_no,
        COALESCE(p.email, '') AS email,
        COALESCE(p.address, '') AS address,
        COALESCE(p.gender, '') AS gender,
        COALESCE(p.date_of_birth, '') AS dob,
        COALESCE(d.doctor_name, 'Doctor Deleted') AS doctor_name,
        COALESCE(d.qualification, '') AS qualification,
        COALESCE(d.mobile, '') AS doctor_mobile,
        COALESCE(s.name, 'Not Assigned') AS technician_name,
        (SELECT COUNT(*) FROM lab_order_details WHERE order_id = o.order_id) AS test_count
FROM lab_orders o
LEFT JOIN patients p ON o.patient_id = p.patient_id
LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
LEFT JOIN staff s ON o.technician_id = s.staff_id
WHERE o.order_id = $order_id AND o.hospital_id = $hid AND o.delete_flag = 0";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    $_SESSION['error'] = "Order not found!";
    header("Location: lab_order.php");
    exit();
}

$order = $result->fetch_assoc();

// ========== GET ORDER TESTS ==========
$sql_tests = "SELECT od.*, t.test_code, t.test_name, t.price 
FROM lab_order_details od
LEFT JOIN lab_tests t ON od.test_id = t.test_id
WHERE od.order_id = $order_id
ORDER BY od.detail_id ASC";
$tests_result = $conn->query($sql_tests);

$selected_tests = [];
if ($tests_result) {
    while ($row = $tests_result->fetch_assoc()) {
        $selected_tests[] = $row['test_id'];
    }
}

// ========== GET PATIENTS (Only from current hospital) ==========
$patients = [];
$sql_patients = "SELECT * FROM patients WHERE delete_flag = 0 AND hospital_id = $hid ORDER BY patient_name";
$result_patients = $conn->query($sql_patients);
if ($result_patients) {
    while ($row = $result_patients->fetch_assoc()) {
        $patients[] = $row;
    }
}

// ========== GET DOCTORS (Only from current hospital) ==========
$doctors = [];
$sql_doctors = "SELECT * FROM doctor WHERE delete_flag = 0 AND hospital_id = $hid ORDER BY doctor_name";
$result_doctors = $conn->query($sql_doctors);
if ($result_doctors) {
    while ($row = $result_doctors->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// ========== GET TECHNICIANS (Only from current hospital) ==========
$technicians = [];
$sql_technicians = "SELECT * FROM staff WHERE delete_flag = 0 AND role = 'Lab Technician' AND hospital_id = $hid ORDER BY name";
$result_technicians = $conn->query($sql_technicians);
if ($result_technicians) {
    while ($row = $result_technicians->fetch_assoc()) {
        $technicians[] = $row;
    }
}

// ========== GET TESTS (Only from current hospital) ==========
$tests = [];
$sql_tests_list = "SELECT t.*, c.category_name FROM lab_tests t 
              LEFT JOIN lab_test_categories c ON t.category_id = c.category_id 
              WHERE t.delete_flag = 0 AND t.status = 'Active' AND t.hospital_id = $hid
              ORDER BY t.test_name";
$result_tests_list = $conn->query($sql_tests_list);
if ($result_tests_list) {
    while ($row = $result_tests_list->fetch_assoc()) {
        $tests[] = $row;
    }
}

// ========== UPDATE ORDER ==========
if (isset($_POST['update_order'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $technician_id = intval($_POST['technician_id'] ?? 0);
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');
    $order_status = $_POST['order_status'] ?? 'Pending';
    $test_ids = $_POST['test_ids'] ?? [];
    $updated_by = $_SESSION['id'] ?? 1;
    
    $errors = [];
    if (empty($patient_id)) $errors[] = "Please select a patient";
    if (empty($doctor_id)) $errors[] = "Please select a doctor";
    if (empty($test_ids)) $errors[] = "Please select at least one test";
    
    if (empty($errors)) {
        // Calculate total
        $total_amount = 0;
        $selected_tests_list = [];
        foreach ($test_ids as $tid) {
            foreach ($tests as $t) {
                if ($t['test_id'] == $tid) {
                    $selected_tests_list[] = $t;
                    $total_amount += $t['price'];
                    break;
                }
            }
        }
        
        $conn->begin_transaction();
        try {
            // Update order with status and technician
            $sql = "UPDATE lab_orders SET 
                    patient_id = $patient_id,
                    doctor_id = $doctor_id,
                    technician_id = " . ($technician_id > 0 ? $technician_id : "NULL") . ",
                    order_date = '$order_date',
                    total_amount = $total_amount,
                    remarks = '$remarks',
                    order_status = '$order_status',
                    updated_by = $updated_by
                    WHERE order_id = $order_id AND hospital_id = $hid";
            
            if ($conn->query($sql)) {
                // Delete existing order details
                $conn->query("DELETE FROM lab_order_details WHERE order_id = $order_id");
                
                // Insert new order details
                foreach ($selected_tests_list as $test) {
                    $sql_detail = "INSERT INTO lab_order_details (order_id, test_id, price) 
                                   VALUES ($order_id, {$test['test_id']}, {$test['price']})";
                    $conn->query($sql_detail);
                }
                
                $conn->commit();
                $_SESSION['success'] = "Order #{$order['order_no']} updated successfully!";
                header("Location: order_details.php?id=$order_id");
                exit();
            } else {
                throw new Exception("Error updating order: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Edit Order</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; border-bottom: 1px solid #e5e7eb; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .form-input { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }
        .form-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        .btn-primary { background: #3b82f6; color: white; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; color: #374151; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-danger { background: #ef4444; color: white; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-warning:hover { background: #d97706; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        
        .test-checkbox-list { max-height: 300px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px; }
        .test-checkbox-list label { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 4px; cursor: pointer; }
        .test-checkbox-list label:hover { background: #f3f4f6; }
        .test-checkbox-list input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        
        .selected-tests-summary { background: #f9fafb; border-radius: 8px; padding: 12px; margin-top: 8px; }
        .selected-tests-summary .test-item { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        .selected-tests-summary .test-item:last-child { border-bottom: none; }
        .selected-tests-summary .total { font-weight: 600; font-size: 14px; margin-top: 8px; padding-top: 8px; border-top: 2px solid #d1d5db; }
        
        .price-badge { font-weight: 600; color: #059669; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #1e40af; }
        
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.assigned { background: #dbeafe; color: #1e40af; }
        .status-badge.sample_collected { background: #e0e7ff; color: #3730a3; }
        .status-badge.in_process { background: #e0f2fe; color: #0369a1; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.cancelled { background: #fecaca; color: #991b1b; }
        
        .action-buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 2px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Edit Order</h1>
                        <p class="text-gray-500 mt-1">Order #<?php echo htmlspecialchars($order['order_no']); ?></p>
                    </div>
                    <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Details
                    </a>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Info Box -->
                <div class="info-box">
                    <i class="fas fa-info-circle mr-2"></i>
                    Editing order #<strong><?php echo htmlspecialchars($order['order_no']); ?></strong> 
                    | Patient: <strong><?php echo htmlspecialchars($order['patient_name']); ?></strong>
                    | Technician: <strong><?php echo htmlspecialchars($order['technician_name']); ?></strong>
                    | Status: <strong><?php echo htmlspecialchars($order['order_status']); ?></strong>
                </div>

                <!-- Edit Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit mr-2 text-yellow-500"></i> Edit Order Details</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="edit_order.php?id=<?php echo $order_id; ?>" id="orderForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Patient <span class="required">*</span></label>
                                    <select class="form-select" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $p): ?>
                                            <option value="<?php echo $p['patient_id']; ?>" 
                                                <?php echo ($order['patient_id'] == $p['patient_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['patient_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Doctor <span class="required">*</span></label>
                                    <select class="form-select" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $d): ?>
                                            <option value="<?php echo $d['doctor_id']; ?>" 
                                                <?php echo ($order['doctor_id'] == $d['doctor_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['doctor_name'] . ' (' . $d['qualification'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Technician</label>
                                <select class="form-select" name="technician_id">
                                    <option value="">Select Technician (Optional)</option>
                                    <?php foreach ($technicians as $t): ?>
                                        <option value="<?php echo $t['staff_id']; ?>" 
                                            <?php echo ($order['technician_id'] == $t['staff_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($technicians)): ?>
                                    <small class="text-gray-500">No technicians available. Please add lab technicians first.</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Order Date</label>
                                <input type="date" class="form-input" name="order_date" 
                                       value="<?php echo $order['order_date']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Select Tests <span class="required">*</span></label>
                                <div class="test-checkbox-list" id="testCheckboxList">
                                    <?php if (!empty($tests)): ?>
                                        <?php foreach ($tests as $t): ?>
                                            <label>
                                                <input type="checkbox" name="test_ids[]" value="<?php echo $t['test_id']; ?>" 
                                                       <?php echo in_array($t['test_id'], $selected_tests) ? 'checked' : ''; ?>
                                                       onchange="updateSelectedTests()">
                                                <span><?php echo htmlspecialchars($t['test_code']); ?></span>
                                                <span class="text-gray-500 text-sm">- <?php echo htmlspecialchars($t['test_name']); ?></span>
                                                <span class="price-badge text-sm ml-auto">₹<?php echo number_format($t['price'], 2); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-red-500 text-sm p-2">No active tests found. Please add tests first.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="selected-tests-summary" id="selectedTestsSummary">
                                <?php 
                                $total = 0;
                                $selected_tests_data = [];
                                foreach ($tests as $t) {
                                    if (in_array($t['test_id'], $selected_tests)) {
                                        $selected_tests_data[] = $t;
                                        $total += $t['price'];
                                    }
                                }
                                ?>
                                <?php if (!empty($selected_tests_data)): ?>
                                    <?php foreach ($selected_tests_data as $t): ?>
                                        <div class="test-item">
                                            <span><?php echo htmlspecialchars($t['test_name']); ?></span>
                                            <span>₹<?php echo number_format($t['price'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="total">Total: ₹<?php echo number_format($total, 2); ?></div>
                                <?php else: ?>
                                    <div class="text-gray-500 text-sm">No tests selected</div>
                                    <div class="total">Total: ₹0.00</div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea class="form-input" name="remarks" rows="3" 
                                          placeholder="Any special instructions..."><?php echo htmlspecialchars($order['remarks'] ?? ''); ?></textarea>
                            </div>

                            <!-- Order Status - Editable -->
                            <div class="form-group">
                                <label>Order Status</label>
                                <select class="form-select" name="order_status">
                                    <option value="Pending" <?php echo ($order['order_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Assigned" <?php echo ($order['order_status'] == 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="Sample Collected" <?php echo ($order['order_status'] == 'Sample Collected') ? 'selected' : ''; ?>>Sample Collected</option>
                                    <option value="In Process" <?php echo ($order['order_status'] == 'In Process') ? 'selected' : ''; ?>>In Process</option>
                                    <option value="Completed" <?php echo ($order['order_status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo ($order['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                                <button type="submit" name="update_order" class="btn-primary">
                                    <i class="fas fa-save"></i> Update Order
                                </button>
                                <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                
                    <?php if ($order['order_status'] != 'Cancelled' && $order['order_status'] != 'Completed'): ?>
                        <!-- Cancel Order Button -->
                     <a href="edit_order.php?id=<?php echo $order_id; ?>&cancel_order=1"
   class="btn-danger"
   onclick="return confirm('Are you sure you want to cancel this order?');">
    <i class="fas fa-times-circle"></i> Cancel Order
</a>
                    <?php else: ?>
                        <span class="text-gray-500 text-sm">This order is <?php echo strtolower($order['order_status']); ?>. Cannot be cancelled.</span>
                    <?php endif; ?>
              
                            </div>
                        </form>
                    </div>
                </div>

                
            </main>
        </div>
    </div>

    <script>
        // ========== UPDATE SELECTED TESTS SUMMARY ==========
        function updateSelectedTests() {
            const checkboxes = document.querySelectorAll('#testCheckboxList input[type="checkbox"]:checked');
            const summary = document.getElementById('selectedTestsSummary');
            let total = 0;
            let html = '';

            if (checkboxes.length === 0) {
                html = '<div class="text-gray-500 text-sm">No tests selected</div>';
                html += `<div class="total">Total: ₹0.00</div>`;
            } else {
                checkboxes.forEach(cb => {
                    const label = cb.closest('label');
                    const name = label.querySelector('span:nth-child(2)').textContent + ' ' + label.querySelector('span:nth-child(3)').textContent;
                    const priceText = label.querySelector('.price-badge').textContent;
                    const price = parseFloat(priceText.replace('₹', ''));
                    total += price;
                    html += `<div class="test-item">
                                <span>${name.trim()}</span>
                                <span>₹${price.toFixed(2)}</span>
                            </div>`;
                });
                html += `<div class="total">Total: ₹${total.toFixed(2)}</div>`;
            }
            summary.innerHTML = html;
        }
    </script>
</body>
</html>