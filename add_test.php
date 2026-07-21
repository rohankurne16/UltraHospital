<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";

// ========== Test Categories with Tests ==========
$test_categories = [
    'Blood Tests' => [
        'Complete Blood Count (CBC)',
        'Hemoglobin (Hb)',
        'Blood Sugar (Fasting)',
        'Blood Sugar (PP)',
        'Random Blood Sugar (RBS)',
        'HbA1c',
        'ESR',
        'Blood Group',
        'Platelet Count',
        'WBC Count',
        'RBC Count'
    ],
    'Liver Function Tests (LFT)' => [
        'SGOT (AST)',
        'SGPT (ALT)',
        'Bilirubin Total',
        'Bilirubin Direct',
        'Alkaline Phosphatase (ALP)',
        'Total Protein',
        'Albumin'
    ],
    'Kidney Function Tests (KFT)' => [
        'Serum Creatinine',
        'Blood Urea',
        'Uric Acid',
        'Sodium',
        'Potassium',
        'Chloride'
    ],
    'Lipid Profile' => [
        'Total Cholesterol',
        'HDL Cholesterol',
        'LDL Cholesterol',
        'VLDL',
        'Triglycerides'
    ],
    'Thyroid Tests' => [
        'T3',
        'T4',
        'TSH'
    ],
    'Urine Tests' => [
        'Urine Routine',
        'Urine Microscopy',
        'Urine Culture',
        'Urine Pregnancy Test'
    ],
    'Cardiac Tests' => [
        'Troponin-I',
        'CK-MB',
        'D-Dimer'
    ],
    'Vitamin Tests' => [
        'Vitamin D',
        'Vitamin B12'
    ],
    'Infection Tests' => [
        'Dengue NS1',
        'Dengue IgG/IgM',
        'Malaria Parasite',
        'Widal Test',
        'CRP',
        'Procalcitonin'
    ],
    'Hormone Tests' => [
        'Insulin',
        'Prolactin',
        'Testosterone',
        'Estrogen',
        'FSH',
        'LH'
    ],
    'Viral Tests' => [
        'HIV',
        'HBsAg',
        'HCV',
        'COVID-19 RT-PCR'
    ],
    'Others' => [
        'Stool Routine',
        'Stool Culture',
        'Semen Analysis',
        'Sputum Test',
        'Biopsy',
        'Histopathology'
    ]
];

$errors = [];
$inserted_tests = [];

// ========== HANDLE FORM SUBMISSION ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_category = mysqli_real_escape_string($conn, trim($_POST['test_category'] ?? ''));
    $test_code = mysqli_real_escape_string($conn, trim($_POST['test_code'] ?? ''));
    $price = trim($_POST['price'] ?? '');
    $normal_range = mysqli_real_escape_string($conn, trim($_POST['normal_range'] ?? ''));
    $unit = mysqli_real_escape_string($conn, trim($_POST['unit'] ?? ''));
    $selected_tests = $_POST['selected_tests'] ?? [];
    
    // Validation
    if (empty($test_category)) $errors[] = "Test Category is required";
    if (empty($test_code)) $errors[] = "Test Code is required";
    if (empty($selected_tests)) $errors[] = "Please select at least one test";
    
    // Check for duplicate test_code
    if (empty($errors)) {
        $check_sql = "SELECT test_id FROM lab_test_master WHERE test_code = '$test_code' AND (delete_flag = 0 OR delete_flag IS NULL)";
        $check_result = $conn->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            $errors[] = "Test Code '$test_code' already exists. Please use a unique code.";
        }
    }
    
    // Process price
    if ($price === '' || $price === null) {
        $price_value = 'NULL';
        $price_db = null;
    } else {
        $price_db = floatval($price);
        $price_value = $price_db;
    }
    
    $normal_range_db = !empty($normal_range) ? $normal_range : null;
    $unit_db = !empty($unit) ? $unit : null;
    
    if (empty($errors)) {
        $conn->begin_transaction();
        $success_count = 0;
        
        try {
            foreach ($selected_tests as $test_name) {
                $test_name_escaped = mysqli_real_escape_string($conn, $test_name);
                
                // Check if test already exists in master
                $check_master = mysqli_query($conn, "SELECT test_id FROM lab_test_master WHERE test_name = '$test_name_escaped' AND test_category = '$test_category' AND delete_flag = 0");
                
                if (mysqli_num_rows($check_master) > 0) {
                    // Test already exists, skip or update
                    $master_row = mysqli_fetch_assoc($check_master);
                    $master_test_id = $master_row['test_id'];
                    
                    // Update existing test
                    $sql_update = "UPDATE lab_test_master SET 
                                    test_code = '$test_code',
                                    price = $price_value,
                                    normal_range = " . ($normal_range_db ? "'$normal_range_db'" : "NULL") . ",
                                    unit = " . ($unit_db ? "'$unit_db'" : "NULL") . ",
                                    status = 'Active',
                                    updated_at = CURRENT_TIMESTAMP
                                   WHERE test_id = $master_test_id";
                    
                    if (!mysqli_query($conn, $sql_update)) {
                        throw new Exception("Error updating test: " . mysqli_error($conn));
                    }
                } else {
                    // Insert new test
                    $sql_master = "INSERT INTO lab_test_master (
                                    test_code,
                                    test_category,
                                    test_name,
                                    price,
                                    normal_range,
                                    unit,
                                    status,
                                    created_by
                                ) VALUES (
                                    '$test_code',
                                    '$test_category',
                                    '$test_name_escaped',
                                    $price_value,
                                    " . ($normal_range_db ? "'$normal_range_db'" : "NULL") . ",
                                    " . ($unit_db ? "'$unit_db'" : "NULL") . ",
                                    'Active',
                                    " . intval($_SESSION['id']) . "
                                )";
                    
                    if (!mysqli_query($conn, $sql_master)) {
                        throw new Exception("Error inserting test '$test_name': " . mysqli_error($conn));
                    }
                    
                    $master_test_id = mysqli_insert_id($conn);
                }
                
                $success_count++;
                $inserted_tests[] = $test_name;
            }
            
            $conn->commit();
            
            if ($success_count > 0) {
                $_SESSION['success'] = $success_count . " test(s) added successfully!<br>
                                        <strong>Category:</strong> " . htmlspecialchars($test_category) . "<br>
                                        <strong>Tests:</strong> " . implode(", ", $inserted_tests) . "<br>
                                        <strong>Test Code:</strong> " . htmlspecialchars($test_code);
                header("Location: lab_admin_master.php");
                exit();
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Add Lab Test</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { 
            width: 100%; margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .card-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .card-body { padding: 20px 24px; }
        
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }
        .form-label .required {
            color: #ef4444;
        }
        .form-label .optional {
            color: #6b7280;
            font-weight: 400;
            font-size: 11px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline {
            background: transparent;
            color: #6b7280;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-outline:hover { background: #f3f4f6; }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border-left: 4px solid #22c55e;
        }
        .alert-error {
            background: #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border-left: 4px solid #ef4444;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .test-selection-area {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 8px;
            background: #fafafa;
        }
        .test-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 4px;
        }
        @media (max-width: 768px) {
            .test-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .test-grid {
                grid-template-columns: 1fr;
            }
        }
        .test-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 6px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .test-item:hover {
            background: #f3f4f6;
            border-color: #e5e7eb;
        }
        .test-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .test-item label {
            font-size: 13px;
            color: #374151;
            cursor: pointer;
        }

        .selected-count-badge {
            background: #3b82f6;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .no-tests-msg {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .select-all-btn {
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }
        .select-all-btn:hover {
            background: #f3f4f6;
        }
        .select-all-btn.select-all {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .select-all-btn.select-all:hover {
            background: #2563eb;
        }
        .select-all-btn.deselect-all {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        .select-all-btn.deselect-all:hover {
            background: #dc2626;
        }

        .status-badge {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.active {
            background: #dcfce7;
            color: #166534;
        }
        .status-badge.inactive {
            background: #fecaca;
            color: #991b1b;
        }
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
                    <div class="flex items-center gap-4">
                        <a href="lab_admin_master.php" class="p-2 border rounded-md hover:bg-gray-100 transition">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">
                                Add New Lab Test
                            </h1>
                            <p class="text-gray-500 mt-1">Select category then choose multiple tests</p>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (!empty($errors)): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Add Test Form -->
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-plus-circle mr-2 text-blue-500"></i>
                            Test Details
                        </h3>
                        <span class="text-xs text-gray-400">* Required fields</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="testForm">
                            <!-- Test Code -->
                            <div class="mb-4">
                                <label class="form-label">
                                    Test Code <span class="required">*</span>
                                </label>
                                <input type="text" name="test_code" class="form-input" 
                                       placeholder="Enter unique test code (e.g., CBC001, LFT001)" 
                                       value="<?php echo htmlspecialchars($_POST['test_code'] ?? ''); ?>"
                                       required>
                                <p class="text-xs text-gray-400 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Must be unique. Example: CBC001, LFT001
                                </p>
                            </div>

                            <!-- Test Category -->
                            <div class="mb-4">
                                <label class="form-label">
                                    Test Category <span class="required">*</span>
                                </label>
                                <select name="test_category" id="testCategory" class="form-select" required onchange="loadTests()">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($test_categories as $category => $tests): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>"
                                            <?php echo (isset($_POST['test_category']) && $_POST['test_category'] == $category) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category); ?> (<?php echo count($tests); ?> tests)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Test Selection -->
                            <div class="test-selection-area" id="testSelectionArea">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="form-label mb-0">
                                        Select Tests <span class="required">*</span>
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <span class="selected-count-badge" id="selectedCount">0 selected</span>
                                        <button type="button" class="select-all-btn" id="toggleSelectBtn" onclick="toggleSelectAll()">
                                            <i class="fas fa-check-double mr-1"></i> Select All
                                        </button>
                                    </div>
                                </div>
                                <div id="testGrid" class="test-grid">
                                    <div class="no-tests-msg">Please select a category first</div>
                                </div>
                            </div>

                            <!-- Price & Unit -->
                            <div class="form-row mb-4">
                                <div>
                                    <label class="form-label">
                                        Price (₹) <span class="optional">(Optional)</span>
                                    </label>
                                    <input type="number" name="price" class="form-input" 
                                           placeholder="Enter price" step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="form-label">
                                        Unit <span class="optional">(Optional)</span>
                                    </label>
                                    <input type="text" name="unit" class="form-input" 
                                           placeholder="e.g., g/dL, mg/dL, µL"
                                           value="<?php echo htmlspecialchars($_POST['unit'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Normal Range -->
                            <div class="mb-4">
                                <label class="form-label">
                                    Normal Range <span class="optional">(Optional)</span>
                                </label>
                                <input type="text" name="normal_range" class="form-input" 
                                       placeholder="e.g., 4.5-11.0, 120-180 mg/dL"
                                       value="<?php echo htmlspecialchars($_POST['normal_range'] ?? ''); ?>">
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-3 pt-4 border-t border-gray-200">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Tests
                                </button>
                                <a href="lab_admin_master.php" class="btn-outline flex-1 text-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const testCategories = <?php echo json_encode($test_categories); ?>;

        function loadTests() {
            const category = document.getElementById('testCategory').value;
            const grid = document.getElementById('testGrid');
            
            if (!category || !testCategories[category]) {
                grid.innerHTML = '<div class="no-tests-msg">Please select a category first</div>';
                updateCount();
                return;
            }

            const tests = testCategories[category];
            let html = '';
            tests.forEach(test => {
                html += `
                    <div class="test-item">
                        <input type="checkbox" name="selected_tests[]" value="${test}" class="test-checkbox" onchange="updateCount()">
                        <label>${test}</label>
                    </div>
                `;
            });
            grid.innerHTML = html;
            updateCount();
        }

        function updateCount() {
            const checkboxes = document.querySelectorAll('.test-checkbox');
            const checked = document.querySelectorAll('.test-checkbox:checked');
            const total = checkboxes.length;
            const selected = checked.length;
            
            document.getElementById('selectedCount').textContent = selected + ' selected';
            
            const toggleBtn = document.getElementById('toggleSelectBtn');
            if (total > 0 && selected === total) {
                toggleBtn.className = 'select-all-btn deselect-all';
                toggleBtn.innerHTML = '<i class="fas fa-times mr-1"></i> Deselect All';
            } else {
                toggleBtn.className = 'select-all-btn select-all';
                toggleBtn.innerHTML = '<i class="fas fa-check-double mr-1"></i> Select All';
            }
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.test-checkbox');
            const checked = document.querySelectorAll('.test-checkbox:checked');
            const total = checkboxes.length;
            const selected = checked.length;
            
            if (total > 0 && selected === total) {
                deselectAllTests();
            } else {
                selectAllTests();
            }
        }

        function selectAllTests() {
            const checkboxes = document.querySelectorAll('.test-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            updateCount();
        }

        function deselectAllTests() {
            const checkboxes = document.querySelectorAll('.test-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateCount();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('testCategory');
            if (categorySelect.value) {
                loadTests();
            }
            updateCount();
            lucide.createIcons();
        });
    </script>
</body>
</html>