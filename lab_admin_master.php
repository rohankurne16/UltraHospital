<?php
session_start();
include "config/hospital.php";


$hospital_name = $hospital["hospital_name"];
$hospital_logo = $hospital["hospital_logo"];
if (isset($_GET['ajax_search']) && $_GET['ajax_search'] == 1) {
    header('Content-Type: application/json');
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $where_clause = "WHERE (lt.delete_flag = 0 OR lt.delete_flag IS NULL)";
    $count_where = "WHERE (delete_flag = 0 OR delete_flag IS NULL)";
    
    if (!empty($search)) {
        $search_escaped = $conn->real_escape_string($search);
        $search_condition = " AND (lt.patient_name LIKE '%$search_escaped%' 
                            OR lt.test_category LIKE '%$search_escaped%' 
                            OR lt.test_name LIKE '%$search_escaped%')";
        $where_clause .= $search_condition;
        $count_where .= " AND (patient_name LIKE '%$search_escaped%' 
                            OR test_category LIKE '%$search_escaped%' 
                            OR test_name LIKE '%$search_escaped%')";
    }
    
    $sql_count = "SELECT COUNT(*) as total FROM (
        SELECT patient_name, test_category 
        FROM lab_tests 
        $count_where 
        GROUP BY patient_name, test_category
    ) as grouped";
    $result_count = $conn->query($sql_count);
    $total_records = $result_count->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);
    
    $sql = "
    SELECT
        lt.test_id,
        lt.patient_name,
        lt.test_category,
        lt.test_name,
        GROUP_CONCAT(ltd.test_name ORDER BY ltd.detail_id SEPARATOR ', ') AS tests,
        COUNT(ltd.detail_id) AS test_count
    FROM lab_tests lt
    LEFT JOIN lab_test_details ltd ON lt.test_id = ltd.test_id AND (ltd.delete_flag = 0 OR ltd.delete_flag IS NULL)
    $where_clause
    GROUP BY lt.test_id
    ORDER BY lt.test_id DESC
    LIMIT $offset, $limit";
    $result = $conn->query($sql);
    
    $rows = [];
    $counter = $offset + 1;
    
    while ($row = $result->fetch_assoc()) {
        $tests = !empty($row['tests']) ? explode(', ', $row['tests']) : [];
        $test_count = $row['test_count'] ?? count($tests);
        
        $tests_html = '';
        if (!empty($tests)) {
            $count = count($tests);
            foreach ($tests as $index => $test) {
                if (!empty($test)) {
                    if ($index >= 2 && $count > 2) {
                        $remaining = $count - 2;
                        $tests_html .= '<span class="test-tag" style="background:#dbeafe;color:#1e40af;">+' . $remaining . ' more</span>';
                        break;
                    }
                    $tests_html .= '<span class="test-tag">' . htmlspecialchars($test) . '</span>';
                }
            }
        } else {
            if (!empty($row['test_name'])) {
                $tests_html .= '<span class="test-tag">' . htmlspecialchars($row['test_name']) . '</span>';
            } else {
                $tests_html .= '<span class="text-gray-400 text-xs">No tests</span>';
            }
        }
        
        $rows[] = [
            'id' => $row['test_id'],
            'counter' => $counter++,
            'patient_name' => htmlspecialchars($row['patient_name']),
            'test_category' => htmlspecialchars($row['test_category']),
            'test_count' => $test_count,
            'tests_html' => $tests_html,
            'view_url' => 'view_test.php?id=' . $row['test_id'] . '&category=' . urlencode($row['test_category']) . '&patient=' . urlencode($row['patient_name']),
            'edit_url' => 'edit_test.php?id=' . $row['test_id'] . '&category=' . urlencode($row['test_category']) . '&patient=' . urlencode($row['patient_name']),
            'delete_url' => 'delete.php?type=main&id=' . $row['test_id'] . '&category=' . urlencode($row['test_category']) . '&patient=' . urlencode($row['patient_name'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'search' => $search
    ]);
    exit();
}

$edit_test = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $sql_edit = "SELECT * FROM lab_tests WHERE test_id = $edit_id AND (delete_flag = 0 OR delete_flag IS NULL)";
    $result_edit = $conn->query($sql_edit);
    if ($result_edit && $result_edit->num_rows > 0) {
        $edit_test = $result_edit->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $test_category = trim($_POST['test_category'] ?? '');
    $test_name = trim($_POST['test_name'] ?? '');
    $action = $_POST['action'] ?? 'add';
    $test_id = intval($_POST['test_id'] ?? 0);
    
    $errors = [];
    
    if (empty($patient_name)) $errors[] = "Patient name is required";
    if (empty($test_category)) $errors[] = "Test category is required";
    if (empty($test_name)) $errors[] = "Test name is required";
    
    if (empty($errors)) {
        if ($action === 'add') {
            $sql = "INSERT INTO lab_tests (patient_name, test_category, test_name) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $patient_name, $test_category, $test_name);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Test added successfully for patient: " . htmlspecialchars($patient_name);
                header("Location: lab_test_master.php");
                exit();
            } else {
                $errors[] = "Error adding test: " . $conn->error;
            }
            $stmt->close();
            
        } elseif ($action === 'update' && $test_id > 0) {
            $sql = "UPDATE lab_tests SET 
                    patient_name = ?,
                    test_category = ?, 
                    test_name = ? 
                    WHERE test_id = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $patient_name, $test_category, $test_name, $test_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Test updated successfully for patient: " . htmlspecialchars($patient_name);
                header("Location: lab_test_master.php");
                exit();
            } else {
                $errors[] = "Error updating test: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode(", ", $errors);
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $sql = "UPDATE lab_tests SET delete_flag = 1 WHERE test_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Test deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting test: " . $conn->error;
    }
    $stmt->close();
    header("Location: lab_test_master.php");
    exit();
}

if (isset($_GET['delete_patient']) && !empty($_GET['delete_patient'])) {
    $patient_name = mysqli_real_escape_string($conn, $_GET['delete_patient']);
    $sql = "UPDATE lab_tests SET delete_flag = 1 WHERE patient_name = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $patient_name);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "All tests deleted successfully for patient: " . htmlspecialchars($patient_name);
    } else {
        $_SESSION['error'] = "Error deleting tests: " . $conn->error;
    }
    $stmt->close();
    header("Location: lab_test_master.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE (lt.delete_flag = 0 OR lt.delete_flag IS NULL)";
$count_where = "WHERE (delete_flag = 0 OR delete_flag IS NULL)";

if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $search_condition = " AND (lt.patient_name LIKE '%$search_escaped%' 
                        OR lt.test_category LIKE '%$search_escaped%' 
                        OR lt.test_name LIKE '%$search_escaped%')";
    $where_clause .= $search_condition;
    $count_where .= " AND (patient_name LIKE '%$search_escaped%' 
                        OR test_category LIKE '%$search_escaped%' 
                        OR test_name LIKE '%$search_escaped%')";
}

$sql_count = "SELECT COUNT(*) as total FROM (
    SELECT patient_name, test_category 
    FROM lab_tests 
    $count_where 
    GROUP BY patient_name, test_category
) as grouped";
$result_count = $conn->query($sql_count);
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$sql = "
SELECT
    lt.test_id,
    lt.patient_name,
    lt.test_category,
    lt.test_name,
    GROUP_CONCAT(ltd.test_name ORDER BY ltd.detail_id SEPARATOR ', ') AS tests,
    COUNT(ltd.detail_id) AS test_count
FROM lab_tests lt
LEFT JOIN lab_test_details ltd ON lt.test_id = ltd.test_id AND (ltd.delete_flag = 0 OR ltd.delete_flag IS NULL)
$where_clause
GROUP BY lt.test_id
ORDER BY lt.test_id DESC
LIMIT $offset, $limit";
$result = $conn->query($sql);

$categories = [];
$sql_categories = "SELECT DISTINCT test_category FROM lab_tests WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY test_category";
$result_categories = $conn->query($sql_categories);
if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row['test_category'];
    }
}
$conn->close();
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
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-primary:hover { background: #2563eb; }
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
        }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning {
            background: #f59e0b;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-warning:hover { background: #d97706; }
        .btn-outline {
            background: transparent;
            color: #6b7280;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-outline:hover { background: #f3f4f6; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th {
            padding: 10px 16px;
            text-align: left;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        td {
            padding: 10px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        tr:hover td { background: #f9fafb; }
        
        .clickable-row {
            cursor: pointer;
            transition: all 0.15s;
        }
        .clickable-row:hover {
            background: #eff6ff !important;
        }
        .clickable-row:hover td {
            background: transparent !important;
        }
        
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
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 32px;
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-top: 16px;
        }
        .pagination a {
            padding: 6px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            color: #4b5563;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .pagination a:hover { background: #f3f4f6; }
        .pagination a.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 12px;
        }
        
        .patient-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .test-tag {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin: 2px 3px 2px 0;
        }
        .category-tag {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            margin: 2px 3px 2px 0;
        }
        .count-badge {
            background: #e5e7eb;
            color: #4b5563;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 4px;
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 11px;
        }
        .actions-cell {
            position: relative;
            z-index: 10;
        }
        .actions-cell a {
            position: relative;
            z-index: 11;
        }
        .action-icons { 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    gap: 4px; 
}
.action-icon { 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    padding: 6px; 
    border-radius: 8px; 
    transition: all 0.2s; 
    background: transparent; 
}
.action-icon svg { 
    width: 18px; 
    height: 18px; 
}
.action-icon.edit-icon:hover { 
    background: #f5f3ff; 
}
.action-icon.edit-icon svg { 
    color: #8b5cf6; 
}
.action-icon.delete-icon:hover { 
    background: #fef2f2; 
}
.action-icon.delete-icon svg { 
    color: #ef4444; 
}
        
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .search-wrapper .form-input {
            padding-left: 38px;
        }
        .search-results-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px 16px;
            margin-top: 10px;
            font-size: 13px;
            color: #1e40af;
        }
        .search-results-info .highlight {
            font-weight: 600;
        }
        .search-results-info .clear-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .search-results-info .clear-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>

            <main class="main-content">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Lab Test Master</h1>
                        <p class="text-gray-500 mt-1">Click on any row to view test details</p>
                    </div>
                    <button type="button" onclick="window.location.href='add_test.php';" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-all shadow-sm">
                        <i class="fas fa-plus mr-2"></i>
                        Add New Test
                    </button>
                </div>

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

                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" class="flex flex-wrap gap-3 items-end" id="searchForm">
                            <div class="flex-1 min-w-[200px]">
                                <label class="form-label">Search Tests</label>
                                <div class="search-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" name="search" id="searchInput" class="form-input" 
                                           placeholder="Search by patient name, category, or test name..." 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           autocomplete="off">
                                </div>
                               


                                
                            </div>
                            <button type="submit" class="btn-primary" id="searchBtn">
                                <i class="fas fa-search mr-1"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="lab_test_master.php" class="btn-outline">
                                    <i class="fas fa-times mr-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-flask mr-2 text-blue-500"></i>
                            Test List
                            <span class="text-sm font-normal text-gray-500 ml-2">(<span id="totalRecords"><?php echo $total_records; ?></span> records)</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="tableContainer">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Patient Name</th>
                                                <th>Category</th>
                                                <th>Tests</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableBody">
                                            <?php $counter = $offset + 1; ?>
                                            <?php while ($row = $result->fetch_assoc()): 
                                                $patient_name = $row['patient_name'];
                                                $test_category = $row['test_category'];
                                                $test_id = $row['test_id'];
                                                $tests = !empty($row['tests']) ? explode(', ', $row['tests']) : [];
                                                $test_count = $row['test_count'] ?? count($tests);
                                            ?>
                                                <tr class="clickable-row" onclick="window.location.href='view_test.php?id=<?php echo $test_id; ?>&category=<?php echo urlencode($test_category); ?>&patient=<?php echo urlencode($patient_name); ?>'">
                                                    <td><?php echo $counter++; ?></td>
                                                    <td class="patient-name-cell">
                                                        <span class="patient-badge">
                                                            <i class="fas fa-user mr-1"></i>
                                                            <?php echo htmlspecialchars($patient_name); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="category-tag">
                                                            <?php echo htmlspecialchars($test_category); ?>
                                                        </span>
                                                        <span class="count-badge"><?php echo $test_count; ?> tests</span>
                                                    </td>
                                                    <td class="tests-cell">
                                                        <?php 
                                                            if (!empty($tests)) {
                                                                $count = count($tests);
                                                                foreach ($tests as $index => $test) {
                                                                    if (!empty($test)) {
                                                                        if ($index >= 2 && $count > 2) {
                                                                            $remaining = $count - 2;
                                                                            echo '<span class="test-tag" style="background:#dbeafe;color:#1e40af;">+' . $remaining . ' more</span>';
                                                                            break;
                                                                        }
                                                                        echo '<span class="test-tag">' . htmlspecialchars($test) . '</span>';
                                                                    }
                                                                }
                                                            } else {
                                                                if (!empty($row['test_name'])) {
                                                                    echo '<span class="test-tag">' . htmlspecialchars($row['test_name']) . '</span>';
                                                                } else {
                                                                    echo '<span class="text-gray-400 text-xs">No tests</span>';
                                                                }
                                                            }
                                                        ?>
                                                    </td>
                                                   
<td class="actions-cell">
    <div class="flex items-center justify-center gap-1 flex-wrap">
        <a href="edit_test.php?id=<?php echo $test_id; ?>&category=<?php echo urlencode($test_category); ?>&patient=<?php echo urlencode($patient_name); ?>" 
           class="action-icon edit-icon" title="Edit Test">
            <i data-lucide="edit-3" class="w-4 h-4"></i>
        </a>
        <a href="delete.php?type=main&id=<?php echo $test_id; ?>&category=<?php echo urlencode($test_category); ?>&patient=<?php echo urlencode($patient_name); ?>" 
           class="action-icon delete-icon" 
           onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this test?');" 
           title="Delete Test">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
        </a>
    </div>
</td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ($total_pages > 1): ?>
                                    <div class="pagination" id="pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-flask"></i>
                                    <p class="text-lg font-medium text-gray-700">No tests found</p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        <?php echo !empty($search) ? 'No results found for "<strong>' . htmlspecialchars($search) . '</strong>"' : 'Click "Add New Test" to create your first test'; ?>
                                    </p>
                                    <?php if (!empty($search)): ?>
                                        <a href="lab_test_master.php" class="btn-outline mt-3 inline-block">
                                            <i class="fas fa-times mr-1"></i> Clear Search
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="testModal" class="modal-overlay <?php echo $edit_test ? 'active' : ''; ?>">
                    <div class="modal-content">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900">
                                <?php echo $edit_test ? 'Edit Test' : 'Add New Test'; ?>
                            </h2>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <form method="POST" id="testForm">
                            <input type="hidden" name="action" value="<?php echo $edit_test ? 'update' : 'add'; ?>">
                            <?php if ($edit_test): ?>
                                <input type="hidden" name="test_id" value="<?php echo $edit_test['test_id']; ?>">
                            <?php endif; ?>

                            <div class="space-y-4">
                                <div>
                                    <label class="form-label">
                                        Patient Name <span class="required">*</span>
                                    </label>
                                    <input type="text" name="patient_name" class="form-input" 
                                           placeholder="Enter patient full name"
                                           value="<?php echo $edit_test ? htmlspecialchars($edit_test['patient_name'] ?? '') : ''; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Test Category <span class="required">*</span>
                                    </label>
                                    <select name="test_category" class="form-select" required>
                                        <option value="">-- Select Category --</option>
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                                    <?php echo ($edit_test && $edit_test['test_category'] == $category) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>No categories found</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Test Name <span class="required">*</span>
                                    </label>
                                    <input type="text" name="test_name" class="form-input" 
                                           placeholder="e.g., Complete Blood Count"
                                           value="<?php echo $edit_test ? htmlspecialchars($edit_test['test_name']) : ''; ?>" required>
                                </div>

                                <div class="flex gap-3 pt-4 border-t border-gray-200">
                                    <button type="submit" class="btn-primary flex-1">
                                        <i class="fas fa-save mr-2"></i>
                                        <?php echo $edit_test ? 'Update Test' : 'Save Test'; ?>
                                    </button>
                                    <button type="button" onclick="closeModal()" class="btn-outline flex-1">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
       

        function performSearch(query) {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax_search', 1);
            url.searchParams.set('search', query);
            url.searchParams.set('page', 1);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(data);
                    updateSearchInfo(data);
                    updatePagination(data);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
        }

        function updateTable(data) {
            const tbody = document.getElementById('tableBody');
            const totalRecords = document.getElementById('totalRecords');
            
            totalRecords.textContent = data.total_records;
            
            if (data.rows.length > 0) {
                let html = '';
                data.rows.forEach(row => {
                    html += `
                        <tr class="clickable-row" onclick="window.location.href='${row.view_url}'">
                            <td>${row.counter}</td>
                            <td class="patient-name-cell">
                                <span class="patient-badge">
                                    <i class="fas fa-user mr-1"></i>
                                    ${row.patient_name}
                                </span>
                            </td>
                            <td>
                                <span class="category-tag">${row.test_category}</span>
                                <span class="count-badge">${row.test_count} tests</span>
                            </td>
                            <td class="tests-cell">${row.tests_html}</td>
                            <td class="actions-cell">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    <a href="${row.edit_url}" 
                                       class="btn-warning btn-sm inline-flex items-center px-2 py-1 rounded text-xs">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <a href="${row.delete_url}" 
                                       class="btn-danger btn-sm inline-flex items-center px-2 py-1 rounded text-xs"
                                       onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this test?');">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-flask"></i>
                                <p class="text-lg font-medium text-gray-700">No tests found</p>
                                <p class="text-sm text-gray-400 mt-1">
                                    No results found for "<strong>${data.search}</strong>"
                                </p>
                                <a href="lab_test_master.php" class="btn-outline mt-3 inline-block">
                                    <i class="fas fa-times mr-1"></i> Clear Search
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }

        function updateSearchInfo(data) {
            const infoDiv = document.getElementById('searchResultsInfo');
            const resultCount = document.getElementById('resultCount');
            
            if (data.search && data.search.length > 0) {
                infoDiv.style.display = 'block';
                resultCount.textContent = data.total_records;
                const highlightSpan = infoDiv.querySelector('.highlight');
                if (highlightSpan) {
                    highlightSpan.textContent = `"${data.search}"`;
                }
            } else {
                infoDiv.style.display = 'none';
            }
        }

        function updatePagination(data) {
            const paginationDiv = document.getElementById('pagination');
            if (!paginationDiv) return;
            
            if (data.total_pages > 1) {
                let html = '';
                
                if (data.current_page > 1) {
                    html += `<a href="#" class="page-link" onclick="event.preventDefault(); goToPage(${data.current_page - 1}, '${data.search}')">
                                <i class="fas fa-chevron-left"></i>
                            </a>`;
                }
                
                for (let i = 1; i <= data.total_pages; i++) {
                    html += `<a href="#" class="page-link ${i == data.current_page ? 'active' : ''}" 
                                onclick="event.preventDefault(); goToPage(${i}, '${data.search}')">
                                ${i}
                            </a>`;
                }
                
                if (data.current_page < data.total_pages) {
                    html += `<a href="#" class="page-link" onclick="event.preventDefault(); goToPage(${data.current_page + 1}, '${data.search}')">
                                <i class="fas fa-chevron-right"></i>
                            </a>`;
                }
                
                paginationDiv.innerHTML = html;
                paginationDiv.style.display = 'flex';
            } else {
                paginationDiv.style.display = 'none';
            }
        }

        function goToPage(page, search) {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax_search', 1);
            url.searchParams.set('search', search);
            url.searchParams.set('page', page);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(data);
                    updateSearchInfo(data);
                    updatePagination(data);
                }
            })
            .catch(error => {
                console.error('Pagination error:', error);
            });
        }

        function openAddModal() {
            const url = new URL(window.location.href);
            url.searchParams.delete('edit');
            window.history.replaceState({}, '', url);
            
            const form = document.getElementById('testForm');
            form.querySelector('input[name="action"]').value = 'add';
            const existingTestId = form.querySelector('input[name="test_id"]');
            if (existingTestId) existingTestId.remove();
            form.querySelector('input[name="patient_name"]').value = '';
            form.querySelector('select[name="test_category"]').value = '';
            form.querySelector('input[name="test_name"]').value = '';
            
            document.getElementById('testModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('testModal').classList.remove('active');
            const url = new URL(window.location.href);
            url.searchParams.delete('edit');
            window.history.replaceState({}, '', url);
        }

        document.getElementById('testModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        document.querySelector('.clear-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'lab_test_master.php';
        });

        lucide.createIcons();
    </script>
</body>
</html>