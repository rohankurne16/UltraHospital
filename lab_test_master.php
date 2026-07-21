<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
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

// ========== HANDLE NEW CATEGORY WITH TEST FROM TEST FORM ==========
if (isset($_POST['add_new_category'])) {
    $category_name = trim($_POST['new_category_name'] ?? '');
    $category_description = trim($_POST['new_category_description'] ?? '');
    $category_status = $_POST['new_category_status'] ?? 'Active';
    $hospital_id = $_SESSION['hospital_id'] ?? 1;
    $created_by = $_SESSION['id'] ?? 1;
    
    // Get test data from form
    $test_code = trim($_POST['test_code'] ?? '');
    $test_name = trim($_POST['test_name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $normal_range = trim($_POST['normal_range'] ?? '');
    $sample_type = trim($_POST['sample_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    
    $errors = [];
    
    if (empty($category_name)) {
        $errors[] = "Category name is required!";
    }
    if (empty($test_code)) {
        $errors[] = "Test code is required!";
    }
    if (empty($test_name)) {
        $errors[] = "Test name is required!";
    }
    if ($price <= 0) {
        $errors[] = "Please enter a valid price!";
    }
    
    if (empty($errors)) {
        $check = $conn->query("SELECT category_id FROM lab_test_categories WHERE category_name = '$category_name' AND delete_flag = 0");
        if ($check && $check->num_rows > 0) {
            $_SESSION['error'] = "Category already exists! Please select it from the dropdown.";
            $_SESSION['form_data'] = $_POST;
            header("Location: lab_test_master.php?new_test=1");
            exit();
        }
        
        $check_test = $conn->query("SELECT test_id FROM lab_tests WHERE test_code = '$test_code' AND delete_flag = 0");
        if ($check_test && $check_test->num_rows > 0) {
            $_SESSION['error'] = "Test code already exists!";
            $_SESSION['form_data'] = $_POST;
            header("Location: lab_test_master.php?new_test=1");
            exit();
        }
        
        $conn->begin_transaction();
        
        try {
            $sql_cat = "INSERT INTO lab_test_categories (category_name, description, status, hospital_id, created_by) 
                        VALUES ('$category_name', '$category_description', '$category_status', $hospital_id, $created_by)";
            
            if ($conn->query($sql_cat)) {
                $category_id = $conn->insert_id;
                
                $sql_test = "INSERT INTO lab_tests (
                            category_id, test_code, test_name, price, unit, normal_range, 
                            sample_type, description, status, hospital_id, created_by
                        ) VALUES (
                            $category_id, '$test_code', '$test_name', $price, '$unit', '$normal_range',
                            '$sample_type', '$description', '$status', $hospital_id, $created_by
                        )";
                
                if ($conn->query($sql_test)) {
                    $conn->commit();
                    $_SESSION['success'] = "Category '{$category_name}' and Test '{$test_name}' added successfully!";
                    header("Location: lab_test_master.php");
                    exit();
                } else {
                    throw new Exception("Error adding test: " . $conn->error);
                }
            } else {
                throw new Exception("Error adding category: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header("Location: lab_test_master.php?new_test=1");
            exit();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
        $_SESSION['form_data'] = $_POST;
        header("Location: lab_test_master.php?new_test=1");
        exit();
    }
}

// ========== CATEGORY CRUD OPERATIONS ==========

// Add Category
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['category_description'] ?? '');
    $status = $_POST['category_status'] ?? 'Active';
    $hospital_id = $_SESSION['hospital_id'] ?? 1;
    $created_by = $_SESSION['id'] ?? 1;
    
    if (!empty($category_name)) {
        $check = $conn->query("SELECT category_id FROM lab_test_categories WHERE category_name = '$category_name' AND delete_flag = 0");
        if ($check && $check->num_rows > 0) {
            $_SESSION['error'] = "Category already exists!";
        } else {
            $sql = "INSERT INTO lab_test_categories (category_name, description, status, hospital_id, created_by) 
                    VALUES ('$category_name', '$description', '$status', $hospital_id, $created_by)";
            if ($conn->query($sql)) {
                $_SESSION['success'] = "Category added successfully!";
            } else {
                $_SESSION['error'] = "Error adding category: " . $conn->error;
            }
        }
    } else {
        $_SESSION['error'] = "Category name is required!";
    }
    header("Location: lab_test_master.php");
    exit();
}

// Edit Category
if (isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id'] ?? 0);
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['category_description'] ?? '');
    $status = $_POST['category_status'] ?? 'Active';
    $updated_by = $_SESSION['id'] ?? 1;
    
    if ($category_id > 0 && !empty($category_name)) {
        $check = $conn->query("SELECT category_id FROM lab_test_categories WHERE category_name = '$category_name' AND category_id != $category_id AND delete_flag = 0");
        if ($check && $check->num_rows > 0) {
            $_SESSION['error'] = "Category already exists!";
        } else {
            $sql = "UPDATE lab_test_categories SET 
                    category_name = '$category_name', 
                    description = '$description', 
                    status = '$status',
                    updated_by = $updated_by
                    WHERE category_id = $category_id AND delete_flag = 0";
            if ($conn->query($sql)) {
                $_SESSION['success'] = "Category updated successfully!";
            } else {
                $_SESSION['error'] = "Error updating category: " . $conn->error;
            }
        }
    } else {
        $_SESSION['error'] = "Category name is required!";
    }
    header("Location: lab_test_master.php");
    exit();
}

// Delete Category
if (isset($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    if ($category_id > 0) {
        $check = $conn->query("SELECT test_id FROM lab_tests WHERE category_id = $category_id AND delete_flag = 0 LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $_SESSION['error'] = "Cannot delete category! It has associated tests.";
        } else {
            $conn->query("UPDATE lab_test_categories SET delete_flag = 1 WHERE category_id = $category_id");
            $_SESSION['success'] = "Category deleted successfully!";
        }
    }
    header("Location: lab_test_master.php");
    exit();
}

// ========== TEST CRUD OPERATIONS ==========

// Delete Test
if (isset($_GET['delete_test'])) {
    $test_id = intval($_GET['delete_test']);
    if ($test_id > 0) {
        $conn->query("UPDATE lab_tests SET delete_flag = 1 WHERE test_id = $test_id");
        $_SESSION['success'] = "Test deleted successfully!";
    }
    header("Location: lab_test_master.php");
    exit();
}

// Toggle Test Status
if (isset($_GET['toggle_status'])) {
    $test_id = intval($_GET['toggle_status']);
    if ($test_id > 0) {
        $current_status = $conn->query("SELECT status FROM lab_tests WHERE test_id = $test_id AND delete_flag = 0");
        if ($current_status && $current_status->num_rows > 0) {
            $row = $current_status->fetch_assoc();
            $new_status = ($row['status'] == 'Active') ? 'Inactive' : 'Active';
            $conn->query("UPDATE lab_tests SET status = '$new_status' WHERE test_id = $test_id");
            $_SESSION['success'] = "Status updated to " . $new_status . "!";
        }
    }
    header("Location: lab_test_master.php");
    exit();
}

// Add/Edit Test (Regular)
if (isset($_POST['save_test'])) {
    $test_id = intval($_POST['test_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $test_code = trim($_POST['test_code'] ?? '');
    $test_name = trim($_POST['test_name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $normal_range = trim($_POST['normal_range'] ?? '');
    $sample_type = trim($_POST['sample_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $hospital_id = $_SESSION['hospital_id'] ?? 1;
    $user_id = $_SESSION['id'] ?? 1;
    
    $errors = [];
    if (empty($category_id)) $errors[] = "Please select a category";
    if (empty($test_code)) $errors[] = "Please enter test code";
    if (empty($test_name)) $errors[] = "Please enter test name";
    if ($price <= 0) $errors[] = "Please enter a valid price";
    
    if (empty($errors)) {
        if ($test_id > 0) {
            $sql = "UPDATE lab_tests SET 
                    category_id = $category_id,
                    test_name = '$test_name',
                    price = $price,
                    unit = '$unit',
                    normal_range = '$normal_range',
                    sample_type = '$sample_type',
                    description = '$description',
                    status = '$status',
                    updated_by = $user_id
                    WHERE test_id = $test_id AND delete_flag = 0";
            if ($conn->query($sql)) {
                $_SESSION['success'] = "Test updated successfully!";
            } else {
                $_SESSION['error'] = "Error updating test: " . $conn->error;
            }
        } else {
            $check = $conn->query("SELECT test_id FROM lab_tests WHERE test_code = '$test_code' AND delete_flag = 0");
            if ($check && $check->num_rows > 0) {
                $_SESSION['error'] = "Test code already exists!";
            } else {
                $sql = "INSERT INTO lab_tests (
                            category_id, test_code, test_name, price, unit, normal_range, 
                            sample_type, description, status, hospital_id, created_by
                        ) VALUES (
                            $category_id, '$test_code', '$test_name', $price, '$unit', '$normal_range',
                            '$sample_type', '$description', '$status', $hospital_id, $user_id
                        )";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = "Test added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding test: " . $conn->error;
                }
            }
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
    header("Location: lab_test_master.php");
    exit();
}

// ========== PAGE LOAD ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause - REMOVED hospital_id filter to show ALL tests
$where_clause = "WHERE t.delete_flag = 0";
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $where_clause .= " AND (t.test_code LIKE '%$search_escaped%' 
                        OR c.category_name LIKE '%$search_escaped%' 
                        OR t.test_name LIKE '%$search_escaped%'
                        OR t.normal_range LIKE '%$search_escaped%'
                        OR t.unit LIKE '%$search_escaped%'
                        OR t.status LIKE '%$search_escaped%'
                        OR t.price LIKE '%$search_escaped%')";
}

// Get total count
$sql_count = "SELECT COUNT(*) as total FROM lab_tests t 
              LEFT JOIN lab_test_categories c ON t.category_id = c.category_id 
              $where_clause";
$result_count = $conn->query($sql_count);
if ($result_count) {
    $total_records = $result_count->fetch_assoc()['total'];
} else {
    $total_records = 0;
}
$total_pages = max(1, ceil($total_records / $limit));
if ($page > $total_pages) $page = $total_pages;

// Get tests
$sql = "SELECT t.*, c.category_name 
        FROM lab_tests t 
        LEFT JOIN lab_test_categories c ON t.category_id = c.category_id 
        $where_clause 
        ORDER BY t.test_id DESC 
        LIMIT $offset, $limit";
$testResult = $conn->query($sql);


// Get all categories for dropdown
$categories = [];
$cat_sql = "SELECT * FROM lab_test_categories WHERE delete_flag = 0 ORDER BY category_name";
$cat_result = $conn->query($cat_sql);
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get single test for editing
$edit_test = null;
if (isset($_GET['edit_test'])) {
    $edit_id = intval($_GET['edit_test']);
    $edit_sql = "SELECT * FROM lab_tests WHERE test_id = $edit_id AND delete_flag = 0";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_test = $edit_result->fetch_assoc();
    }
}

// Get single category for editing
$edit_category = null;
if (isset($_GET['edit_category_id'])) {
    $edit_cat_id = intval($_GET['edit_category_id']);
    $edit_cat_sql = "SELECT * FROM lab_test_categories WHERE category_id = $edit_cat_id AND delete_flag = 0";
    $edit_cat_result = $conn->query($edit_cat_sql);
    if ($edit_cat_result && $edit_cat_result->num_rows > 0) {
        $edit_category = $edit_cat_result->fetch_assoc();
    }
}

// Restore form data if any
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Test Master</title>
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
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .form-input { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-input.error { border-color: #ef4444; }
        
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-warning:hover { background: #d97706; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
        .pagination { display: flex; gap: 4px; justify-content: center; margin-top: 16px; flex-wrap: wrap; }
        .pagination a { padding: 6px 14px; border: 1px solid #e5e7eb; border-radius: 6px; color: #4b5563; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination a.active { background: #3b82f6; color: white; border-color: #3b82f6; }
        .pagination a.disabled { opacity: 0.5; pointer-events: none; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #fecaca; color: #991b1b; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .category-badge { background: #e0e7ff; color: #4338ca; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .price-badge { font-weight: 600; color: #059669; }
        
        .search-wrapper { position: relative; }
        .search-wrapper .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .search-wrapper .form-input { padding-left: 38px; }
        .search-results-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 16px; margin-top: 10px; font-size: 13px; color: #1e40af; }
        .search-results-info .highlight { font-weight: 600; }
        
        .actions-cell { white-space: nowrap; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 700px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 4px 8px; }
        .modal-close:hover { color: #1f2937; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        select.form-input { appearance: auto; }
        textarea.form-input { resize: vertical; min-height: 80px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        
        .tab-container { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .tab-btn { padding: 10px 20px; background: none; border: none; font-size: 14px; font-weight: 500; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        
        .category-select-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .category-select-wrapper select {
            flex: 1;
        }
        .category-select-wrapper .btn-sm {
            white-space: nowrap;
        }
        .inline-category-form {
            display: none;
            margin-top: 8px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .inline-category-form.show {
            display: block;
        }
        .inline-category-form .form-row {
            margin-bottom: 0;
        }
        .inline-category-form .form-group {
            margin-bottom: 8px;
        }
        
        .info-note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #1e40af;
            margin-top: 4px;
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
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Lab Test Master</h1>
                        <p class="text-gray-500 mt-1">Manage test categories &amp; tests</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button onclick="openAddTestModal()" class="btn-primary">
                            <i class="fas fa-plus"></i> Add Test
                        </button>
                        <button onclick="openAddCategoryModal()" class="btn-success">
                            <i class="fas fa-tag"></i> Add Category
                        </button>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="tab-container">
                    <button class="tab-btn active" onclick="switchTab('tests')"><i class="fas fa-flask"></i> Tests</button>
                    <button class="tab-btn" onclick="switchTab('categories')"><i class="fas fa-tags"></i> Categories</button>
                </div>

                <!-- ========== TESTS TAB ========== -->
                <div id="tab-tests" class="tab-content active">
                    <!-- Search -->
                    <div class="card mb-6">
                        <div class="card-body">
                            <form method="GET" action="lab_test_master.php" class="flex flex-wrap gap-3 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Tests</label>
                                    <div class="search-wrapper">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" name="search" class="form-input" 
                                               placeholder="Search by code, category, name, range, unit..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary"><i class="fas fa-search mr-1"></i> Search</button>
                                <?php if (!empty($search)): ?>
                                    <a href="lab_test_master.php" class="btn-outline"><i class="fas fa-times mr-1"></i> Clear</a>
                                <?php endif; ?>
                            </form>
                            <?php if (!empty($search)): ?>
                                <div class="search-results-info mt-3">
                                    <i class="fas fa-filter mr-1"></i> Showing results for: <span class="highlight">"<?php echo htmlspecialchars($search); ?>"</span>
                                    <span class="text-gray-500">|</span> <span class="highlight"><?php echo $total_records; ?></span> result(s) found
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tests Table -->
                    <!-- Tests Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-flask mr-2 text-blue-500"></i> Test List <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $total_records; ?> records)</span></h3>
    </div>
    <div class="card-body">
        <?php if ($testResult && $testResult->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Test Code</th>
                            <th>Category</th>
                            <th>Test Name</th>
                            <th>Price</th>
                            <th>Normal Range</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($testResult, 0); ?>
                        <?php $counter = $offset + 1; ?>
                        <?php while ($test = $testResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code']); ?></span></td>
                                <td>
                                    <?php if (!empty($test['category_name'])): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($test['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">No Category</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                <td class="price-badge">₹<?php echo number_format($test['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($test['normal_range'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($test['unit'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($test['status'] == 'Active'): ?>
                                        <span class="status-badge active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <!-- Edit Button -->
                                        <a href="?edit_test=<?php echo $test['test_id']; ?>" 
                                           class="btn-warning btn-sm" 
                                           title="Edit Test">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Toggle Status Button -->
                                        <a href="?toggle_status=<?php echo $test['test_id']; ?>" 
                                           class="<?php echo ($test['status'] == 'Active') ? 'btn-danger' : 'btn-success'; ?> btn-sm" 
                                           title="<?php echo ($test['status'] == 'Active') ? 'Deactivate' : 'Activate'; ?>"
                                           onclick="return confirm('Are you sure you want to change status?');">
                                            <i class="fas <?php echo ($test['status'] == 'Active') ? 'fa-pause' : 'fa-play'; ?>"></i>
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <a href="?delete_test=<?php echo $test['test_id']; ?>" 
                                           class="btn-danger btn-sm" 
                                           title="Delete Test"
                                           onclick="return confirm('Are you sure you want to delete this test permanently?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <a class="disabled"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    if ($start_page > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>">1</a>
                        <?php if ($start_page > 2): ?><span class="px-2 text-gray-400">...</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?><span class="px-2 text-gray-400">...</span><?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <a class="disabled"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-flask"></i>
                <p class="text-lg font-medium text-gray-700">No tests found</p>
                <p class="text-sm text-gray-400 mt-1">
                    <?php 
                    if (!empty($search)) {
                        echo 'No results found for "<strong>' . htmlspecialchars($search) . '</strong>"';
                    } else {
                        echo 'No tests in the system. Click "Add Test" to create your first test.';
                        if (empty($categories)) {
                            echo '<br><span class="text-red-500">⚠️ No categories found! Please add a category first.</span>';
                        }
                    }
                    ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="lab_test_master.php" class="btn-outline mt-3 inline-block"><i class="fas fa-times mr-1"></i> Clear Search</a>
                <?php else: ?>
                    <div class="flex gap-2 justify-center mt-3 flex-wrap">
                        <button onclick="openAddTestModal()" class="btn-primary inline-block">
                            <i class="fas fa-plus mr-1"></i> Add Test
                        </button>
                        <?php if (empty($categories)): ?>
                            <button onclick="openAddCategoryModal()" class="btn-success inline-block">
                                <i class="fas fa-tag mr-1"></i> Add Category First
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
                </div>

                <!-- ========== CATEGORIES TAB ========== -->
                <!-- ========== CATEGORIES TAB ========== -->
<!-- ========== CATEGORIES TAB ========== -->
<!-- ========== CATEGORIES TAB ========== -->
<!-- ========== CATEGORIES TAB ========== -->
<div id="tab-categories" class="tab-content">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tags mr-2 text-green-500"></i> Categories</h3>
            <button onclick="openAddCategoryModal()" class="btn-success btn-sm"><i class="fas fa-plus"></i> Add Category</button>
        </div>
        <div class="card-body">
            <?php if (!empty($categories)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Tests</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $cat_counter = 1; ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php 
                                    $test_count = 0;
                                    $count_sql = "SELECT COUNT(*) as cnt FROM lab_tests WHERE category_id = {$cat['category_id']} AND delete_flag = 0";
                                    $count_result = $conn->query($count_sql);
                                    if ($count_result) {
                                        $test_count = $count_result->fetch_assoc()['cnt'];
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $cat_counter++; ?></td>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($cat['category_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($cat['status'] == 'Active'): ?>
                                            <span class="status-badge active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge-count"><?php echo $test_count; ?> tests</span></td>
                                    <td class="actions-cell">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <!-- Edit Category Button -->
                                            <a href="?edit_category_id=<?php echo $cat['category_id']; ?>" 
                                               class="btn-warning btn-sm" 
                                               title="Edit Category">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Delete Category Button - Always Show -->
                                            <a href="?delete_category=<?php echo $cat['category_id']; ?>" 
                                               class="btn-danger btn-sm" 
                                               title="Delete Category"
                                               onclick="return confirm('Are you sure you want to delete this category?\n\nWarning: This will also delete <?php echo $test_count; ?> test(s) linked to this category!');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p class="text-lg font-medium text-gray-700">No categories found</p>
                    <p class="text-sm text-gray-400 mt-1">Click "Add Category" to create your first category</p>
                    <button onclick="openAddCategoryModal()" class="btn-success mt-3 inline-block">
                        <i class="fas fa-plus mr-1"></i> Add Category
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
            </main>
        </div>
    </div>

    <!-- ========== MODALS ========== -->

    <!-- Add/Edit Test Modal -->
    <div class="modal" id="testModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="testModalTitle"><?php echo $edit_test ? 'Edit Test' : 'Add New Test'; ?></h2>
                <button class="modal-close" onclick="closeModal('testModal')">&times;</button>
            </div>
            <form method="POST" action="lab_test_master.php" id="testForm">
                <input type="hidden" name="test_id" value="<?php echo $edit_test['test_id'] ?? 0; ?>">
                
                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <div class="category-select-wrapper">
                        <select class="form-input" name="category_id" id="categorySelect" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" 
                                    <?php echo ($edit_test && $edit_test['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>
                                    <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-success btn-sm" onclick="toggleNewCategoryForm()" title="Add New Category with Test">
                            <i class="fas fa-plus"></i> New
                        </button>
                    </div>
                    
                    <!-- Inline Category Creation Form -->
                    <div class="inline-category-form <?php echo isset($form_data['add_new_category']) ? 'show' : ''; ?>" id="newCategoryForm">
                        <div class="info-note">
                            <i class="fas fa-info-circle mr-1"></i> 
                            Fill category details below. The test will be automatically created with this category.
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>New Category Name <span class="required">*</span></label>
                                <input type="text" class="form-input" id="new_category_name" name="new_category_name" 
                                       placeholder="Enter category name"
                                       value="<?php echo htmlspecialchars($form_data['new_category_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Category Description</label>
                                <input type="text" class="form-input" id="new_category_description" name="new_category_description"
                                       placeholder="Category description"
                                       value="<?php echo htmlspecialchars($form_data['new_category_description'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Category Status</label>
                            <select class="form-input" name="new_category_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="submit" name="add_new_category" class="btn-success btn-sm">
                                <i class="fas fa-save"></i> Add Category & Test
                            </button>
                            <button type="button" class="btn-outline btn-sm" onclick="toggleNewCategoryForm()">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Test Code <?php if (!$edit_test): ?><span class="required">*</span><?php endif; ?></label>
                    <input type="text" class="form-input" name="test_code" 
                           value="<?php echo htmlspecialchars($edit_test['test_code'] ?? $form_data['test_code'] ?? ''); ?>" 
                           <?php echo $edit_test ? 'readonly' : 'required'; ?>
                           placeholder="<?php echo $edit_test ? 'Cannot be changed' : 'e.g., CBC001'; ?>">
                </div>

                <div class="form-group">
                    <label>Test Name <span class="required">*</span></label>
                    <input type="text" class="form-input" name="test_name" required
                           value="<?php echo htmlspecialchars($edit_test['test_name'] ?? $form_data['test_name'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Price (₹) <span class="required">*</span></label>
                        <input type="number" step="0.01" class="form-input" name="price" required
                               value="<?php echo htmlspecialchars($edit_test['price'] ?? $form_data['price'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" class="form-input" name="unit"
                               value="<?php echo htmlspecialchars($edit_test['unit'] ?? $form_data['unit'] ?? ''); ?>"
                               placeholder="e.g., mg/dL, g/L">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Normal Range</label>
                        <input type="text" class="form-input" name="normal_range"
                               value="<?php echo htmlspecialchars($edit_test['normal_range'] ?? $form_data['normal_range'] ?? ''); ?>"
                               placeholder="e.g., 70-110 mg/dL">
                    </div>
                    <div class="form-group">
                        <label>Sample Type</label>
                        <input type="text" class="form-input" name="sample_type"
                               value="<?php echo htmlspecialchars($edit_test['sample_type'] ?? $form_data['sample_type'] ?? ''); ?>"
                               placeholder="e.g., Blood, Urine">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-input" name="description" rows="3"><?php echo htmlspecialchars($edit_test['description'] ?? $form_data['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select class="form-input" name="status">
                        <option value="Active" <?php echo (($edit_test && $edit_test['status'] == 'Active') || ($form_data['status'] ?? '') == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo (($edit_test && $edit_test['status'] == 'Inactive') || ($form_data['status'] ?? '') == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('testModal')">Cancel</button>
                    <button type="submit" name="save_test" class="btn-primary"><i class="fas fa-save"></i> <?php echo $edit_test ? 'Update Test' : 'Save Test'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="categoryModalTitle"><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
                <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <form method="POST" action="lab_test_master.php">
                <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id'] ?? 0; ?>">
                
                <div class="form-group">
                    <label>Category Name <span class="required">*</span></label>
                    <input type="text" class="form-input" name="category_name" required
                           value="<?php echo htmlspecialchars($edit_category['category_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-input" name="category_description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select class="form-input" name="category_status">
                        <option value="Active" <?php echo ($edit_category && $edit_category['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($edit_category && $edit_category['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('categoryModal')">Cancel</button>
                    <button type="submit" name="<?php echo $edit_category ? 'edit_category' : 'add_category'; ?>" class="btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_category ? 'Update Category' : 'Save Category'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            document.querySelector('.tab-btn[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }

        function openAddTestModal() {
            const url = new URL(window.location);
            url.searchParams.delete('edit_test');
            window.history.pushState({}, '', url);
            document.getElementById('testModal').classList.add('show');
            
            <?php if (isset($form_data['add_new_category'])): ?>
                document.getElementById('newCategoryForm').classList.add('show');
            <?php else: ?>
                document.getElementById('newCategoryForm').classList.remove('show');
            <?php endif; ?>
        }

        function openAddCategoryModal() {
            const url = new URL(window.location);
            url.searchParams.delete('edit_category_id');
            window.history.pushState({}, '', url);
            document.getElementById('categoryModal').classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function toggleNewCategoryForm() {
            document.getElementById('newCategoryForm').classList.toggle('show');
            if (document.getElementById('newCategoryForm').classList.contains('show')) {
                document.getElementById('new_category_name').focus();
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(el => el.classList.remove('show'));
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('new_test') || urlParams.has('edit_test')) {
                document.getElementById('testModal').classList.add('show');
                <?php if (isset($form_data['add_new_category'])): ?>
                    document.getElementById('newCategoryForm').classList.add('show');
                <?php endif; ?>
            }
            
            if (urlParams.has('edit_category_id')) {
                document.getElementById('categoryModal').classList.add('show');
            }
            
            const tab = urlParams.get('tab');
            if (tab) {
                switchTab(tab);
            }
        });

        document.getElementById('new_category_name').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('button[name="add_new_category"]').click();
            }
        });
    </script>
</body>
</html>