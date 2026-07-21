<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}


$hospital_name = $hospital["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital["hospital_logo"] ?? "../documents/hospital/logo.png";


$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$patient = isset($_GET['patient']) ? trim($_GET['patient']) : '';

if ($test_id == 0 && empty($category)) {
    header("Location: lab_test_master.php");
    exit();
}


$test_data = null;
$sql = "SELECT * FROM lab_tests WHERE test_id = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $test_data = $result->fetch_assoc();
} else {

    if (!empty($patient) && !empty($category)) {
        $sql = "SELECT * FROM lab_tests WHERE patient_name = ? AND test_category = ? AND (delete_flag = 0 OR delete_flag IS NULL) LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $patient, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $test_data = $result->fetch_assoc();
            $test_id = $test_data['test_id'];
        }
    }
    $stmt->close();
    
    if (!$test_data) {
        header("Location: lab_test_master.php");
        exit();
    }
}
$stmt->close();

$patient_name = $test_data['patient_name'] ?? '';


if (empty($category)) {
    $category = $test_data['test_category'] ?? '';
}


$all_test_details = [];
if (!empty($patient_name) && !empty($category)) {
    
    $sql_tests = "SELECT test_id FROM lab_tests 
                  WHERE patient_name = ? 
                  AND test_category = ? 
                  AND (delete_flag = 0 OR delete_flag IS NULL)";
    $stmt_tests = $conn->prepare($sql_tests);
    $stmt_tests->bind_param("ss", $patient_name, $category);
    $stmt_tests->execute();
    $result_tests = $stmt_tests->get_result();
    
    $test_ids = [];
    while ($row = $result_tests->fetch_assoc()) {
        $test_ids[] = $row['test_id'];
    }
    $stmt_tests->close();
    

    if (!empty($test_ids)) {
        $ids_string = implode(',', $test_ids);
        $sql_details = "SELECT ltd.*, lt.test_category, lt.patient_name 
                        FROM lab_test_details ltd
                        LEFT JOIN lab_tests lt ON ltd.test_id = lt.test_id
                        WHERE ltd.test_id IN ($ids_string) 
                        AND (ltd.delete_flag = 0 OR ltd.delete_flag IS NULL) 
                        ORDER BY ltd.detail_id";
        $result_details = $conn->query($sql_details);
        
        if ($result_details && $result_details->num_rows > 0) {
            while ($row = $result_details->fetch_assoc()) {
                $all_test_details[] = $row;
            }
        }
    }
}


$all_patient_tests = [];
if (!empty($patient_name) && !empty($category)) {
    $sql_all = "SELECT * FROM lab_tests 
                WHERE patient_name = ? 
                AND test_category = ? 
                AND (delete_flag = 0 OR delete_flag IS NULL) 
                ORDER BY test_id";
    $stmt_all = $conn->prepare($sql_all);
    $stmt_all->bind_param("ss", $patient_name, $category);
    $stmt_all->execute();
    $result_all = $stmt_all->get_result();
    
    if ($result_all && $result_all->num_rows > 0) {
        while ($row = $result_all->fetch_assoc()) {
            $all_patient_tests[] = $row;
        }
    }
    $stmt_all->close();
}


$categories = [];
$sql_categories = "SELECT DISTINCT test_category FROM lab_tests WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY test_category";
$result_categories = $conn->query($sql_categories);
if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row['test_category'];
    }
}


$predefined_tests = [
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
$success_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_details';
    
    if ($action === 'update_details') {
        // Update test details
        $detail_ids = $_POST['detail_ids'] ?? [];
        $detail_names = $_POST['detail_names'] ?? [];
        $detail_ranges = $_POST['detail_ranges'] ?? [];
        $detail_units = $_POST['detail_units'] ?? [];
        $detail_sample_types = $_POST['detail_sample_types'] ?? [];
        $detail_prices = $_POST['detail_prices'] ?? [];
        $detail_statuses = $_POST['detail_statuses'] ?? [];
        
        foreach ($detail_ids as $key => $did) {
            $did = intval($did);
            $dname = trim($detail_names[$key] ?? '');
            $drange = trim($detail_ranges[$key] ?? '');
            $dunit = trim($detail_units[$key] ?? '');
            $dsample = trim($detail_sample_types[$key] ?? '');
            $dprice = floatval($detail_prices[$key] ?? 0);
            $dstatus = trim($detail_statuses[$key] ?? 'Pending');
            
            if (!empty($dname) && $did > 0) {
                $sql = "UPDATE lab_test_details SET 
                        test_name = ?,
                        normal_range = ?,
                        unit = ?,
                        sample_type = ?,
                        price = ?,
                        status = ?
                        WHERE detail_id = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssdsi", $dname, $drange, $dunit, $dsample, $dprice, $dstatus, $did);
                
                if ($stmt->execute()) {
                    $success_count++;
                }
                $stmt->close();
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success'] = $success_count . " test detail(s) updated successfully";
            header("Location: edit_test.php?id=" . $test_id . "&category=" . urlencode($category) . "&patient=" . urlencode($patient_name));
            exit();
        } else {
            $errors[] = "No details were updated. Please try again.";
        }
    } elseif ($action === 'add_detail') {

        $new_detail_test_id = intval($_POST['new_detail_test_id'] ?? 0);
        $new_detail_name = trim($_POST['new_detail_name'] ?? '');
        $new_detail_range = trim($_POST['new_detail_range'] ?? '');
        $new_detail_unit = trim($_POST['new_detail_unit'] ?? '');
        $new_detail_sample = trim($_POST['new_detail_sample'] ?? '');
        $new_detail_price = floatval($_POST['new_detail_price'] ?? 0);
        $new_detail_status = trim($_POST['new_detail_status'] ?? 'Pending');
        
        if (empty($new_detail_name)) {
            $errors[] = "Detail test name is required";
        } else if ($new_detail_test_id <= 0) {
            $errors[] = "Invalid test ID";
        } else {
           $check_master_sql = "SELECT test_id FROM lab_test_master WHERE test_name = ? AND test_category = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
            $check_master_stmt = $conn->prepare($check_master_sql);
            $check_master_stmt->bind_param("ss", $new_detail_name, $category);
            $check_master_stmt->execute();
            $check_master_result = $check_master_stmt->get_result();
            
            if ($check_master_result->num_rows > 0) {
                // Test exists in master, get its ID
                $master_row = $check_master_result->fetch_assoc();
                $master_test_id = $master_row['test_id'];
            } else {
                
                $insert_master_sql = "INSERT INTO lab_test_master (test_category, test_name, normal_range, unit, price, status) 
                                      VALUES (?, ?, ?, ?, ?, ?)";
                $insert_master_stmt = $conn->prepare($insert_master_sql);
                $insert_master_stmt->bind_param("ssssds", $category, $new_detail_name, $new_detail_range, $new_detail_unit, $new_detail_price, $new_detail_status);
                
                if ($insert_master_stmt->execute()) {
                    $master_test_id = $insert_master_stmt->insert_id;
                } else {
                    $errors[] = "Error inserting into master: " . $conn->error;
                    $insert_master_stmt->close();
                    $check_master_stmt->close();
             
                    $master_test_id = 0;
                }
                $insert_master_stmt->close();
            }
            $check_master_stmt->close();
            
          
            if ($master_test_id > 0) {
                $sql = "INSERT INTO lab_test_details (test_id, master_test_id, test_name, normal_range, unit, sample_type, price, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissssds", $new_detail_test_id, $master_test_id, $new_detail_name, $new_detail_range, $new_detail_unit, $new_detail_sample, $new_detail_price, $new_detail_status);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "New test detail added successfully";
                    header("Location: lab_admin_master.php");
                    exit();
                } else {
                    $errors[] = "Error adding test detail: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}



if (isset($_GET['delete_detail']) && is_numeric($_GET['delete_detail'])) {
    $delete_detail_id = intval($_GET['delete_detail']);
    $sql = "UPDATE lab_test_details SET delete_flag = 1 WHERE detail_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_detail_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Test detail deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting test detail: " . $conn->error;
    }
    $stmt->close();
    header("Location: lab_admin_master.php");
    exit();
}





function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending': return 'status-pending';
        case 'Completed': return 'status-completed';
        case 'In Progress': return 'status-progress';
        default: return 'status-pending';
    }
}



function getStatusLabel($status) {
    switch ($status) {
        case 'Pending': return 'Pending';
        case 'Completed': return 'Completed';
        case 'In Progress': return 'In Progress';
        default: return $status ?? 'Pending';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Edit Tests</title>
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
        
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: #4b5563;
            flex-shrink: 0;
        }
        .info-value {
            color: #1f2937;
            flex: 1;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-progress {
            background: #e0e7ff;
            color: #4338ca;
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
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover { background: #4b5563; }
        .btn-warning {
            background: #f59e0b;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-warning:hover { background: #d97706; }
        .btn-success {
            background: #22c55e;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-success:hover { background: #16a34a; }
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-danger:hover { background: #dc2626; }
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
        .btn-info {
            background: #0ea5e9;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-info:hover { background: #0284c7; }
        
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
        
        .test-tag {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px 4px 2px 0;
        }
        .category-tag {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .patient-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .test-count-badge {
            background: #e5e7eb;
            color: #4b5563;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        /* Edit Rows */
        .edit-row {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            background: #fafafa;
            transition: all 0.2s;
        }
        .edit-row:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
        }
        .edit-row .test-number {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }
        .edit-row .delete-link {
            color: #ef4444;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }
        .edit-row .delete-link:hover {
            color: #dc2626;
        }
        
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
        .form-label .disabled-hint {
            font-weight: 400;
            font-size: 11px;
            color: #6b7280;
        }
        
        .category-section {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .category-section .category-title {
            background: #f1f5f9;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .category-section .category-title .category-name {
            font-weight: 600;
            font-size: 16px;
            color: #1f2937;
        }
        .category-section .category-body {
            padding: 12px 16px;
            background: white;
        }
        
        .debug-info {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .debug-info .debug-title {
            font-weight: 600;
            color: #92400e;
        }
        .debug-info .debug-text {
            color: #78350f;
            font-size: 14px;
        }
        .debug-info .debug-highlight {
            background: #fef3c7;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .detail-table thead {
            background: #f9fafb;
        }
        .detail-table th {
            padding: 10px 16px;
            text-align: left;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .detail-table td {
            padding: 10px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        .detail-table tr:hover td {
            background: #f9fafb;
        }
        .table-container { overflow-x: auto; }
        .form-input-sm {
            width: 100%;
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            transition: all 0.2s;
        }
        .form-input-sm:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-select-sm {
            width: 100%;
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            background: white;
            transition: all 0.2s;
        }
        .form-select-sm:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-sm {
            padding: 2px 8px;
            font-size: 11px;
        }
        
        /* Dropdown hint */
        .dropdown-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .dropdown-hint i {
            margin-right: 4px;
        }

        /* Disabled input style */
        .form-input-disabled {
            background: #f3f4f6;
            color: #1f2937;
            cursor: not-allowed;
            opacity: 0.8;
        }
        .form-select-disabled {
            background: #f3f4f6;
            color: #1f2937;
            cursor: not-allowed;
            opacity: 0.8;
            appearance: none;
            background-image: none;
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
                                Edit Tests
                            </h1>
                            <p class="text-gray-500 mt-1">
                                <?php if (!empty($patient_name) && !empty($category)): ?>
                                    Editing tests for <strong><?php echo htmlspecialchars($patient_name); ?></strong> 
                                    - Category: <strong><?php echo htmlspecialchars($category); ?></strong>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="view_test.php?id=<?php echo $test_id; ?>&category=<?php echo urlencode($category); ?>&patient=<?php echo urlencode($patient_name); ?>" class="btn-secondary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>

                

                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo implode(", ", $errors); ?>
                    </div>
                <?php endif; ?>

                <!-- Patient Information -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-user mr-2 text-blue-500"></i>
                            Patient Information
                            <span class="test-count-badge ml-2"><?php echo count($all_test_details); ?> tests</span>
                            <span class="test-count-badge ml-2">Category: <?php echo htmlspecialchars($category); ?></span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <div class="info-row">
                                    <span class="info-label">Patient Name</span>
                                    <span class="info-value">
                                        <span class="patient-badge">
                                            <i class="fas fa-user mr-1"></i>
                                            <?php echo htmlspecialchars($patient_name); ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="info-row">
                                    <span class="info-label">Category</span>
                                    <span class="info-value">
                                        <span class="category-tag">
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="info-row">
                                    <span class="info-label">Total Tests</span>
                                    <span class="info-value">
                                        <?php echo count($all_test_details); ?> tests
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Test Details - All tests in one category -->
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-list-ul mr-2 text-purple-500"></i>
                            Edit Tests - <?php echo htmlspecialchars($category); ?>
                            <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo count($all_test_details); ?> records)</span>
                        </h3>
                        <span class="text-xs text-gray-400">Edit all test details below</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_details">
                            
                            <?php if (!empty($all_test_details)): ?>
                                <div class="category-section">
                                    <div class="category-title">
                                        <span class="category-name">
                                            <i class="fas fa-folder-open mr-2 text-blue-500"></i>
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                        <span class="test-count-badge"><?php echo count($all_test_details); ?> tests</span>
                                    </div>
                                    <div class="category-body">
                                        <?php foreach ($all_test_details as $index => $detail): ?>
                                            <div class="edit-row">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="test-number">
                                                        <i class="fas fa-flask text-blue-400 mr-1"></i>
                                                        Test #<?php echo $index + 1; ?>
                                                    </span>
                                                  
                                                </div>
                                                <div>
                                                    <input type="hidden" name="detail_ids[]" value="<?php echo $detail['detail_id']; ?>">
                                                    <label class="form-label text-xs text-gray-500">Test Name</label>
                                                    <input type="text" name="detail_names[]" class="form-input" 
                                                           value="<?php echo htmlspecialchars($detail['test_name'] ?? ''); ?>" 
                                                           placeholder="Enter test name" required>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                                    <button type="submit" class="btn-primary flex-1 min-w-[150px]">
                                        <i class="fas fa-save mr-2"></i>
                                        Update All Tests
                                    </button>
                                    <a href="view_test.php?id=<?php echo $test_id; ?>&category=<?php echo urlencode($category); ?>&patient=<?php echo urlencode($patient_name); ?>" class="btn-outline flex-1 min-w-[150px] text-center">
                                        Cancel
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-info-circle text-4xl mb-2 text-gray-300"></i>
                                    <p>No test details found for this category.</p>
                                    <p class="text-sm mt-2">Try adding a new test detail below.</p>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Add New Test Detail -->
                <div class="card mt-6">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-plus-circle mr-2 text-green-500"></i>
                            Add New Test Detail
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_detail">
                            
                            <!-- Category - Disabled/Readonly -->
                            <div class="mb-4">
                                <label class="form-label">
                                    Category <span class="required">*</span>
                                    <span class="disabled-hint">(Auto-selected from current category)</span>
                                </label>
                                <?php 
                                // Get the first test_id from all_patient_tests to use as hidden value
                                $first_test_id = !empty($all_patient_tests) ? $all_patient_tests[0]['test_id'] : 0;
                                ?>
                                <input type="hidden" name="new_detail_test_id" value="<?php echo $first_test_id; ?>">
                                <input type="text" class="form-input form-input-disabled" 
                                       value="<?php echo htmlspecialchars($category); ?>" 
                                       disabled readonly>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">
                                        Test Name <span class="required">*</span>
                                    </label>
                                    <select name="new_detail_name" class="form-select" required>
                                        <option value="">-- Select Test Name --</option>
                                        <?php 
                                        
                                        $category_tests = $predefined_tests[$category] ?? [];
                                        foreach ($category_tests as $test_name): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($test_name); ?>">
                                                <?php echo htmlspecialchars($test_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="dropdown-hint">
                                        <i class="fas fa-list-ul text-green-500"></i>
                                        Predefined tests for <strong><?php echo htmlspecialchars($category); ?></strong> category
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Normal Range</label>
                                    <input type="text" name="new_detail_range" class="form-input" 
                                           placeholder="e.g., 4.5-11.0">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3">
                                <div>
                                    <label class="form-label">Unit</label>
                                    <input type="text" name="new_detail_unit" class="form-input" 
                                           placeholder="e.g., g/dL">
                                </div>
                                <div>
                                    <label class="form-label">Sample Type</label>
                                    <input type="text" name="new_detail_sample" class="form-input" 
                                           placeholder="e.g., Blood">
                                </div>
                                <div>
                                    <label class="form-label">Price</label>
                                    <input type="number" name="new_detail_price" class="form-input" 
                                           placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label class="form-label">Status</label>
                                    <select name="new_detail_status" class="form-select">
                                        <option value="Pending">Pending</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-3">
                                <button type="submit" class="btn-success">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Test
                                </button>
                                <button type="reset" class="btn-outline">
                                    Clear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
                                                <?php $conn->close(); ?>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>