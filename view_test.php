<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}


$hospital_name = $hospital["hospital_name"];
$hospital_logo = $hospital["hospital_logo"];


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


$all_patient_tests = [];
if (!empty($patient_name) && !empty($category)) {
    $sql_all = "SELECT * FROM lab_tests 
                WHERE patient_name = ? 
                AND test_category = ? 
                AND (delete_flag = 0 OR delete_flag IS NULL) 
                ORDER BY test_id DESC";
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


$all_test_details = [];
if (!empty($patient_name) && !empty($category)) {

    $test_ids = array_column($all_patient_tests, 'test_id');
    if (!empty($test_ids)) {
        $ids_string = implode(',', $test_ids);
        $sql_details = "SELECT ltd.*, lt.test_category 
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

$conn->close();


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
    <title><?php echo htmlspecialchars($hospital_name); ?> - View Test</title>
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
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary {
            background: #6b7280;
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
        .btn-secondary:hover { background: #4b5563; }
        .btn-warning {
            background: #f59e0b;
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
        .btn-warning:hover { background: #d97706; }
        .btn-danger {
            background: #ef4444;
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
        .btn-danger:hover { background: #dc2626; }
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
        
        .test-count-badge {
            background: #e5e7eb;
            color: #4b5563;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        /* Category Section - Single Category */
        .category-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .category-section .category-title {
            background: #f9fafb;
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
        .action-icon { 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    padding: 6px; 
    border-radius: 8px; 
    transition: all 0.2s; 
    background: transparent; 
    text-decoration: none;
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
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>

            <main class="main-content">
            
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <a href="lab_admin_master.php" class="p-2 border rounded-md hover:bg-gray-100 transition">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">
                              
                                Patient Test Details
                            </h1>
                            <p class="text-gray-500 mt-1">
                                <?php if (!empty($patient_name) && !empty($category)): ?>
                                    Tests for <strong><?php echo htmlspecialchars($patient_name); ?></strong> 
                                    - Category: <strong><?php echo htmlspecialchars($category); ?></strong>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="edit_test.php?id=<?php echo $test_id; ?>&category=<?php echo urlencode($category); ?>&patient=<?php echo urlencode($patient_name); ?>" class="btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>

              
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

                            <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-flask mr-2 text-blue-500"></i>
                            Tests - <?php echo htmlspecialchars($category); ?>
                            <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo count($all_test_details); ?> records)</span>
                        </h3>
                    </div>
                    <div class="card-body">
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
                                    <table class="detail-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Test Name</th>
                                                <th>Normal Range</th>
                                                <th>Unit</th>
                                                <th>Sample Type</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $counter = 1; ?>
                                            <?php foreach ($all_test_details as $detail): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td class="font-medium">
                                                        <span class="test-tag"><?php echo htmlspecialchars($detail['test_name'] ?? 'N/A'); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($detail['normal_range'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($detail['unit'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($detail['sample_type'] ?? '-'); ?></td>
                                                    <td><?php echo ($detail['price'] && $detail['price'] > 0) ? '₹' . number_format($detail['price'], 2) : '-'; ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo getStatusBadgeClass($detail['status'] ?? 'Pending'); ?>">
                                                            <?php echo getStatusLabel($detail['status'] ?? 'Pending'); ?>
                                                        </span>
                                                    </td>
                                                   <td>
    <div class="flex items-center justify-center gap-1">
        <a href="#" 
           class="action-icon edit-icon" title="Edit Test">
            <i data-lucide="edit-3" class="w-4 h-4"></i>
        </a>
        
    </div>
</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-4xl mb-2 text-gray-300"></i>
                                <p>No test details found for this category.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>