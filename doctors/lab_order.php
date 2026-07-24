<?php
session_start();
include "../config/hospital.php";

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["id"];
$hid = $_SESSION["hospital_id"];

// ========== GET DOCTOR INFO ==========
// ========== GET DOCTOR INFO ==========
$doctor = [];

// Try doctor table first
$doctor_sql = "SELECT doctor_id, doctor_name, qualification, mobile, email 
               FROM doctor 
               WHERE doctor_id = $user_id AND hospital_id = $hid";
$doctor_result = $conn->query($doctor_sql);

if ($doctor_result && $doctor_result->num_rows > 0) {
    $doctor = $doctor_result->fetch_assoc();
} else {
    // Check staff table
    $staff_sql = "SELECT staff_id, name, email, role 
                  FROM staff 
                  WHERE staff_id = $user_id AND hospital_id = $hid";
    $staff_result = $conn->query($staff_sql);
    
    if ($staff_result && $staff_result->num_rows > 0) {
        $staff = $staff_result->fetch_assoc();
        $doctor['doctor_name'] = $staff['name'] ?? 'Doctor';
        $doctor['qualification'] = $staff['role'] ?? '';
    } else {
        // Use session data
        $doctor['doctor_name'] = $_SESSION['name'] ?? 'Doctor';
        $doctor['qualification'] = $_SESSION['role'] ?? '';
    }
}

// Ensure doctor_name is never empty
if (empty($doctor['doctor_name']) || $doctor['doctor_name'] == '') {
    $doctor['doctor_name'] = 'Doctor';
}

// ========== FIX: Remove duplicate "Dr" ==========
// Remove existing "Dr" or "Dr." from the name if it already has it
$doctor_name = trim($doctor['doctor_name']);
// Remove "Dr." prefix if exists
$doctor_name = preg_replace('/^Dr\.?\s*/i', '', $doctor_name);
// Remove "Dr" prefix if exists (without dot)
$doctor_name = preg_replace('/^Dr\s*/i', '', $doctor_name);
// Now add single "Dr. " prefix
$doctor['doctor_name'] = 'Dr. ' . $doctor_name;

// ========== GET HOSPITAL DATA ==========
$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";

// ========== GET TECHNICIANS ==========
$technicians = [];
$sql_technicians = "SELECT staff_id, name, email FROM staff 
                    WHERE role = 'Lab Technician' AND hospital_id = $hid 
                    AND (delete_flag = 0 OR delete_flag IS NULL)
                    ORDER BY name";
$result_technicians = $conn->query($sql_technicians);
if ($result_technicians) {
    while ($row = $result_technicians->fetch_assoc()) {
        $technicians[] = $row;
    }
}

// ========== GET TEST CATEGORIES ==========
$categories = [];
$sql_categories = "SELECT category_id, category_name FROM lab_test_categories 
                   WHERE hospital_id = $hid AND (delete_flag = 0 OR delete_flag IS NULL)
                   ORDER BY category_name";
                  


$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// ========== GET SELECTED CATEGORY ID ==========
$selected_category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

// ========== GET TESTS FOR SELECTED CATEGORY ==========
$category_tests = [];
if ($selected_category_id > 0) {
    $sql_tests = "SELECT test_id, test_code, test_name, price, normal_range, unit 
                  FROM lab_tests 
                  WHERE category_id = $selected_category_id 
                  AND status = 'Active' 
                  AND (delete_flag = 0 OR delete_flag IS NULL)
                  AND hospital_id = $hid
                  ORDER BY test_name";
    $result_tests = $conn->query($sql_tests);
    if ($result_tests) {
        while ($row = $result_tests->fetch_assoc()) {
            $category_tests[] = $row;
        }
    }
}

// ========== PROCESS FORM SUBMISSION ==========
// ========== PROCESS FORM SUBMISSION ==========
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_order'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $technician_id = intval($_POST['technician_id'] ?? 0);
    $test_ids = $_POST['test_ids'] ?? [];
    $clinical_notes = trim($_POST['clinical_notes'] ?? '');
    $order_date = date('Y-m-d');
    
    if ($patient_id <= 0) {
        $errors[] = "Please select a patient";
    }
    if (empty($test_ids)) {
        $errors[] = "Please select at least one test";
    }
    
    if (empty($errors)) {
        // ========== GENERATE UNIQUE ORDER NUMBER ==========
        $prefix = "LAB";
        $date = date("Ymd");
        
        // Get the last order number for today
        $sql = "SELECT MAX(order_no) as max_no FROM lab_orders WHERE order_no LIKE '$prefix$date%'";
        $result = $conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc() && $row['max_no']) {
            $last_no = $row['max_no'];
            $num = intval(substr($last_no, -4)) + 1;
            $order_no = $prefix . $date . str_pad($num, 4, '0', STR_PAD_LEFT);
        } else {
            $order_no = $prefix . $date . '0001';
        }
        
        // ========== ENSURE UNIQUE (Safety Check) ==========
        $check_sql = "SELECT COUNT(*) as count FROM lab_orders WHERE order_no = '$order_no'";
        $check_result = $conn->query($check_sql);
        $counter = 0;
        while ($check_result && $check_result->fetch_assoc()['count'] > 0 && $counter < 100) {
            $counter++;
            $num = intval(substr($order_no, -4)) + 1;
            $order_no = $prefix . $date . str_pad($num, 4, '0', STR_PAD_LEFT);
            $check_sql = "SELECT COUNT(*) as count FROM lab_orders WHERE order_no = '$order_no'";
            $check_result = $conn->query($check_sql);
        }
        
        // ========== GET TEST PRICES ==========
        $all_tests = [];
        if (!empty($test_ids)) {
            $ids_string = implode(',', array_map('intval', $test_ids));
            $sql_prices = "SELECT test_id, price FROM lab_tests WHERE test_id IN ($ids_string)";
            $result_prices = $conn->query($sql_prices);
            if ($result_prices) {
                while ($row = $result_prices->fetch_assoc()) {
                    $all_tests[$row['test_id']] = $row['price'];
                }
            }
        }
        
        $total_amount = 0;
        foreach ($test_ids as $tid) {
            if (isset($all_tests[$tid])) {
                $total_amount += $all_tests[$tid];
            }
        }
        
        $conn->begin_transaction();
        try {
            $order_status = ($technician_id > 0) ? 'Assigned' : 'Pending';


            // Get Doctor ID from logged-in Doctor
$register_id = $_SESSION['id'];

$sql_doctor = "SELECT doctor_id
               FROM doctor
               WHERE register_id = '$register_id'
               LIMIT 1";

$result_doctor = $conn->query($sql_doctor);

if ($result_doctor && $result_doctor->num_rows > 0) {
    $doctor = $result_doctor->fetch_assoc();
    $doctor_id = $doctor['doctor_id'];
} else {
    die("Doctor not found!");
}
            
            $sql_order = "INSERT INTO lab_orders (order_no, patient_id, doctor_id, technician_id, hospital_id, 
                        order_date, total_amount, clinical_notes, order_status, created_by) 
                        VALUES ('$order_no', $patient_id, $user_id, " . ($technician_id > 0 ? $technician_id : "NULL") . ", $hid, 
                        '$order_date', $total_amount, '$clinical_notes', '$order_status', $user_id)";
            
            if ($conn->query($sql_order)) {
                $order_id = $conn->insert_id;
                
                foreach ($test_ids as $tid) {
                    $price = isset($all_tests[$tid]) ? $all_tests[$tid] : 0;
                    $sql_detail = "INSERT INTO lab_order_details (order_id, test_id, price) 
                                  VALUES ($order_id, $tid, $price)";
                    $conn->query($sql_detail);
                }
                
                $conn->commit();
                $success = true;
                $_SESSION['success'] = "Lab Order #$order_no created successfully!";
                header("Location: lab_order.php?id=$order_id");
                exit();
            } else {
                throw new Exception("Error creating order: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Search patients (AJAX)
if (isset($_GET['search_patient'])) {

    $search = mysqli_real_escape_string($conn, $_GET['search_patient']);

    $sql = "SELECT patient_id, patient_name, mobile, gender, date_of_birth
            FROM patients
            WHERE hospital_id = $hid
            AND (delete_flag = 0 OR delete_flag IS NULL)
            AND (patient_name LIKE '%$search%' OR mobile LIKE '%$search%')
            LIMIT 10";

    $result = $conn->query($sql);

    $patients = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($patients);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Create Lab Order</title>
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
        
        .form-input, .form-select, .form-textarea { 
            width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; 
            font-size: 14px; transition: all 0.2s; background: white; 
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { 
            outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); 
        }
        .form-textarea { min-height: 80px; resize: vertical; }
        
        .btn-primary { background: #3b82f6; color: white; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; color: #374151; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-sm { padding: 6px 14px; font-size: 12px; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        
        .test-selection-area {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            background: #fafafa;
            min-height: 100px;
            max-height: 400px;
            overflow-y: auto;
        }
        .test-selection-area .no-tests-msg {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .test-item { 
            display: flex; align-items: center; gap: 10px; 
            padding: 6px 10px; border-radius: 4px; cursor: pointer; 
            border-bottom: 1px solid #f9fafb;
        }
        .test-item:hover { background: #f3f4f6; }
        .test-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; flex-shrink: 0; }
        .test-item .test-code { 
            font-family: monospace; background: #f1f5f9; color: #475569; 
            padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; 
        }
        .test-item .test-name { flex: 1; font-size: 13px; }
        .test-item .test-price { font-weight: 600; color: #059669; font-size: 12px; }
        
        .selected-tests-summary { background: #f9fafb; border-radius: 8px; padding: 12px; margin-top: 8px; }
        .selected-tests-summary .test-item-summary { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        .selected-tests-summary .test-item-summary:last-child { border-bottom: none; }
        .selected-tests-summary .total { font-weight: 600; font-size: 15px; margin-top: 8px; padding-top: 8px; border-top: 2px solid #d1d5db; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
        .search-results { 
            position: absolute; background: white; border: 1px solid #d1d5db; 
            border-radius: 8px; max-height: 220px; overflow-y: auto; 
            width: 100%; z-index: 10; display: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .search-results .result-item { 
            padding: 10px 14px; cursor: pointer; display: flex; justify-content: space-between; 
            align-items: center; border-bottom: 1px solid #f3f4f6; 
        }
        .search-results .result-item:hover { background: #f3f4f6; }
        .search-results .result-item .patient-name { font-weight: 500; }
        .search-results .result-item .patient-detail { font-size: 12px; color: #6b7280; }
        
        .selected-patient { 
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; 
            padding: 12px 16px; display: flex; justify-content: space-between; 
            align-items: center; margin-top: 8px;
        }
        .selected-patient .patient-info .name { font-weight: 600; font-size: 15px; }
        .selected-patient .patient-info .detail { font-size: 13px; color: #6b7280; }
        
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        
        .select-all-btn {
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .select-all-btn:hover { background: #f3f4f6; }
        .select-all-btn.select-all { background: #3b82f6; color: white; border-color: #3b82f6; }
        .select-all-btn.select-all:hover { background: #2563eb; }
        .select-all-btn.deselect-all { background: #ef4444; color: white; border-color: #ef4444; }
        .select-all-btn.deselect-all:hover { background: #dc2626; }

        .category-select-wrapper {
            position: relative;
        }
        .category-select-wrapper select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .category-select-wrapper select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">
                            <i class="fas fa-flask text-blue-500 mr-2"></i>Create Lab Order
                        </h1>
                        <p class="text-gray-500 mt-1">Select patient, assign technician, and choose tests</p>
                    </div>
                    <a href="doctor_dashboard.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo implode("<br>", $errors); ?>
                    </div>
                <?php endif; ?>

                <!-- Order Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle mr-2 text-blue-500"></i> New Lab Order</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="lab_order.php" id="orderForm" novalidate>
                            <!-- Patient Selection -->
                            <div class="form-group" style="position: relative;">
                                <label>Search Patient <span class="required">*</span></label>
                                <input type="text" class="form-input" id="patientSearch" 
                                       placeholder="Type patient name or mobile number..." 
                                       autocomplete="off" required>
                                <div class="search-results" id="searchResults"></div>
                                <input type="hidden" name="patient_id" id="patient_id" required>
                            </div>
                            

                            <!-- Selected Patient Display -->
                            <div id="selectedPatientDisplay" style="display: none;" class="selected-patient">
                                <div class="patient-info">
                                    <div class="name" id="selectedPatientName"></div>
                                    <div class="detail" id="selectedPatientDetail"></div>
                                </div>
                                <button type="button" class="btn-secondary btn-sm" onclick="clearPatient()">
                                    <i class="fas fa-times"></i> Change
                                </button>
                            </div>

                            <!-- Doctor & Technician -->
                            <div class="form-row">
                             <div class="form-group">
    <label>Doctor <span class="required">*</span></label>
    <input type="text" class="form-input" 
           value="<?php echo htmlspecialchars($doctor['doctor_name'] ?? 'Doctor'); ?>" 
           readonly disabled 
           style="background: #f3f4f6; cursor: not-allowed; font-weight: 600; color: #1e40af;">
    <?php if (!empty($doctor['qualification'])): ?>
        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($doctor['qualification']); ?></p>
    <?php endif; ?>
</div>
                                <div class="form-group">
                                    <label>Assign Technician <span class="required">*</label>
                                    <select class="form-select" name="technician_id" id="technicianSelect">
                                        <option value="">-- Select Technician  --</option>
                                        <?php foreach ($technicians as $tech): ?>
                                            <option value="<?php echo $tech['staff_id']; ?>">
                                                <?php echo htmlspecialchars($tech['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($technicians)): ?>
                                        <p class="text-xs text-yellow-600 mt-1">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            No technicians available. Order will be in Pending status.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Order Date -->
                            <div class="form-row">
                                <div class="form-group">
    <label>Order Date <span class="required">*</span></label>
    <input type="date"
           class="form-input"
           name="order_date"
           value="<?php echo date('Y-m-d'); ?>"
           required>
</div>
                                <div class="form-group">
                                    <label>Clinical Notes</label>
                                    <textarea class="form-textarea" name="clinical_notes" 
                                              placeholder="Enter clinical notes, symptoms, or specific instructions for the lab technician..."
                                              rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Test Selection - Category Wise -->
                            <div class="form-group">
                                <label>Select Test Category <span class="required">*</span></label>
                                <div class="category-select-wrapper">
                                    <select class="form-select" id="categorySelect" name="category_id" onchange="this.form.submit()">
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['category_id']; ?>"
                                                <?php echo ($selected_category_id == $cat['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if (empty($categories)): ?>
                                    <p class="text-xs text-red-600 mt-1">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        No categories found. Please add test categories first.
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Tests Display Area -->
                            <div class="form-group">
                                <label>Select Tests <span class="required">*</span></label>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="badge-count" id="selectedTestCount">0 tests selected</span>
                                    <button type="button" class="select-all-btn select-all" id="toggleSelectBtn" onclick="toggleSelectAll()">
                                        <i class="fas fa-check-double mr-1"></i> Select All
                                    </button>
                                </div>
                                <div class="test-selection-area" id="testSelectionArea">
                                    <?php if ($selected_category_id > 0): ?>
                                        <?php if (!empty($category_tests)): ?>
                                            <?php foreach ($category_tests as $test): ?>
                                                <div class="test-item">
                                                    <input type="checkbox" name="test_ids[]" value="<?php echo $test['test_id']; ?>" 
                                                           class="test-checkbox" onchange="updateSelectedTests()">
                                                    <span class="test-code"><?php echo htmlspecialchars($test['test_code']); ?></span>
                                                    <span class="test-name"><?php echo htmlspecialchars($test['test_name']); ?></span>
                                                    <span class="test-price">₹<?php echo number_format($test['price'], 2); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-tests-msg">
                                                <i class="fas fa-exclamation-circle text-gray-300 text-2xl block mb-2"></i>
                                                No tests found in this category
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="no-tests-msg">
                                            <i class="fas fa-folder-open text-gray-300 text-2xl block mb-2"></i>
                                            Please select a category to load tests
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Selected Tests Summary -->
                            <div class="selected-tests-summary" id="selectedTestsSummary">
                                <div class="text-gray-500 text-sm">No tests selected</div>
                                <div class="total">Total: ₹0.00</div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                <button type="submit" name="create_order" class="btn-primary">
                                    <i class="fas fa-save"></i> Create Order
                                </button>
                                <button type="reset" class="btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <a href="doctor_dashboard.php" class="btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="font-semibold text-blue-800"><i class="fas fa-lightbulb mr-2"></i> Quick Tips</h4>
                    <ul class="text-sm text-blue-700 mt-1 space-y-1">
                        <li>• Search patient by name or mobile number</li>
                        <li>• Select a category first to load tests</li>
                        <li>• Choose multiple tests from the category</li>
                        <li>• Assign a technician for faster processing</li>
                        <li>• Add clinical notes to help lab technicians</li>
                    </ul>
                </div>
            </main>
        </div>
    </div>

    <script>
        // ========== PATIENT SEARCH ==========
        let searchTimeout;

        document.getElementById('patientSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            const results = document.getElementById('searchResults');
            
            if (query.length < 2) {
                results.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`lab_order.php?search_patient=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            results.innerHTML = data.map(p => `
                                <div class="result-item" onclick="selectPatient(${p.patient_id}, '${p.patient_name}', '${p.mobile || ''}', '${p.gender || ''}', '${p.date_of_birth || ''}')">
                                    <div>
                                        <div class="patient-name">${p.patient_name}</div>
                                        <div class="patient-detail">${p.mobile || ''} ${p.gender ? '| ' + p.gender : ''} ${p.date_of_birth ? '| DOB: ' + p.date_of_birth : ''}</div>
                                    </div>
                                    <span class="text-xs text-gray-400">ID: ${p.patient_id}</span>
                                </div>
                            `).join('');
                            results.style.display = 'block';
                        } else {
                            results.innerHTML = '<div class="result-item text-gray-500">No patients found</div>';
                            results.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        results.style.display = 'none';
                    });
            }, 300);
        });

        function selectPatient(id, name, mobile, gender, dob) {
            document.getElementById('patient_id').value = id;
            document.getElementById('patientSearch').value = name;
            document.getElementById('selectedPatientName').textContent = name;
            document.getElementById('selectedPatientDetail').textContent = 
                `${mobile || ''} ${gender ? '| ' + gender : ''} ${dob ? '| DOB: ' + dob : ''}`;
            document.getElementById('selectedPatientDisplay').style.display = 'flex';
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('patientSearch').style.borderColor = '#22c55e';
            document.getElementById('patientSearch').style.background = '#f0fdf4';
        }

        function clearPatient() {
            document.getElementById('patient_id').value = '';
            document.getElementById('patientSearch').value = '';
            document.getElementById('selectedPatientDisplay').style.display = 'none';
            document.getElementById('patientSearch').style.borderColor = '#d1d5db';
            document.getElementById('patientSearch').style.background = 'white';
            document.getElementById('searchResults').style.display = 'none';
        }

        // ========== UPDATE SELECTED TESTS SUMMARY ==========
        function updateSelectedTests() {
            const checkboxes = document.querySelectorAll('#testSelectionArea .test-checkbox:checked');
            const summary = document.getElementById('selectedTestsSummary');
            const countDisplay = document.getElementById('selectedTestCount');
            let total = 0;
            let html = '';

            const totalCheckboxes = document.querySelectorAll('#testSelectionArea .test-checkbox').length;
            const selected = checkboxes.length;
            countDisplay.textContent = selected + ' tests selected';

            const toggleBtn = document.getElementById('toggleSelectBtn');
            if (totalCheckboxes > 0 && selected === totalCheckboxes) {
                toggleBtn.className = 'select-all-btn deselect-all';
                toggleBtn.innerHTML = '<i class="fas fa-times mr-1"></i> Deselect All';
            } else {
                toggleBtn.className = 'select-all-btn select-all';
                toggleBtn.innerHTML = '<i class="fas fa-check-double mr-1"></i> Select All';
            }

            if (selected === 0) {
                html = '<div class="text-gray-500 text-sm">No tests selected</div>';
                html += `<div class="total">Total: ₹0.00</div>`;
            } else {
                checkboxes.forEach(cb => {
                    const item = cb.closest('.test-item');
                    const name = item.querySelector('.test-name').textContent;
                    const priceText = item.querySelector('.test-price').textContent;
                    const price = parseFloat(priceText.replace('₹', ''));
                    total += price;
                    html += `<div class="test-item-summary">
                                <span>${name.trim()}</span>
                                <span>₹${price.toFixed(2)}</span>
                            </div>`;
                });
                html += `<div class="total">Total: ₹${total.toFixed(2)}</div>`;
            }
            summary.innerHTML = html;
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('#testSelectionArea .test-checkbox');
            const checked = document.querySelectorAll('#testSelectionArea .test-checkbox:checked');
            
            if (checked.length === checkboxes.length && checkboxes.length > 0) {
                deselectAllTests();
            } else {
                selectAllTests();
            }
        }

        function selectAllTests() {
            const checkboxes = document.querySelectorAll('#testSelectionArea .test-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedTests();
        }

        function deselectAllTests() {
            const checkboxes = document.querySelectorAll('#testSelectionArea .test-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedTests();
        }

        // ========== RESET FORM ==========
        function resetForm() {
            document.getElementById('orderForm').reset();
            document.querySelectorAll('#testSelectionArea .test-checkbox').forEach(cb => cb.checked = false);
            clearPatient();
            updateSelectedTests();
            document.getElementById('patientSearch').style.borderColor = '#d1d5db';
            document.getElementById('patientSearch').style.background = 'white';
            document.getElementById('technicianSelect').value = '';
        }

        // ========== CLOSE SEARCH RESULTS ON CLICK OUTSIDE ==========
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#patientSearch') && !e.target.closest('#searchResults')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });

        // ========== FORM VALIDATION ==========
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            const patientId = document.getElementById('patient_id').value;
            const checkedTests = document.querySelectorAll('#testSelectionArea .test-checkbox:checked');
            const category = document.getElementById('categorySelect').value;
            
            if (!patientId) {
                e.preventDefault();
                alert('Please select a patient');
                document.getElementById('patientSearch').focus();
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a test category');
                document.getElementById('categorySelect').focus();
                return false;
            }
            
            if (checkedTests.length === 0) {
                e.preventDefault();
                alert('Please select at least one test');
                return false;
            }
            
            return true;
        });

        // ========== KEYBOARD SHORTCUTS ==========
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.getElementById('orderForm').submit();
            }
            if (e.key === 'Escape') {
                document.getElementById('searchResults').style.display = 'none';
            }
        });

        // ========== INITIALIZE ==========
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedTests();
        });
    </script>
</body>
</html>