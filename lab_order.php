<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}
$hid = $_SESSION["hospital_id"];

// Get hospital data
$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";

// ========== GENERATE ORDER NO ==========
function generateOrderNo($conn) {
    $prefix = "ORD";
    $date = date("Ymd");
    $sql = "SELECT MAX(order_no) as max_no FROM lab_orders WHERE order_no LIKE '$prefix$date%'";
    $orderresult = $conn->query($sql);
    if ($orderresult && $row = $orderresult->fetch_assoc()) {
        if ($row['max_no']) {
            $num = intval(substr($row['max_no'], -4)) + 1;
            return $prefix . $date . str_pad($num, 4, '0', STR_PAD_LEFT);
        }
    }
    return $prefix . $date . '0001';
}

// ========== GET TECHNICIANS ==========
$technicians = [];
$sql_technicians = "SELECT * FROM staff WHERE delete_flag = 0 AND role = 'Lab Technician' AND hospital_id = $hid ORDER BY name";
$result_technicians = $conn->query($sql_technicians);
if ($result_technicians) {
    while ($row = $result_technicians->fetch_assoc()) {
        $technicians[] = $row;
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

// ========== GET TESTS (Only from current hospital) ==========
$tests = [];
$sql_tests = "SELECT t.*, c.category_name FROM lab_tests t 
              LEFT JOIN lab_test_categories c ON t.category_id = c.category_id 
              WHERE t.delete_flag = 0 AND t.status = 'Active' AND t.hospital_id = $hid
              ORDER BY t.test_name";
$result_tests = $conn->query($sql_tests);
if ($result_tests) {
    while ($row = $result_tests->fetch_assoc()) {
        $tests[] = $row;
    }
}

// ========== CREATE NEW ORDER ==========
if (isset($_POST['create_order'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');
    $test_ids = $_POST['test_ids'] ?? [];
    $hid = $_SESSION['hospital_id'] ?? 1;
    $created_by = $_SESSION['id'] ?? 1;
    
    $errors = [];
    if (empty($patient_id)) $errors[] = "Please select a patient";
    if (empty($doctor_id)) $errors[] = "Please select a doctor";
    if (empty($test_ids)) $errors[] = "Please select at least one test";
    
    if (empty($errors)) {
        // Calculate total
        $total_amount = 0;
        $selected_tests = [];
        foreach ($test_ids as $tid) {
            foreach ($tests as $t) {
                if ($t['test_id'] == $tid) {
                    $selected_tests[] = $t;
                    $total_amount += $t['price'];
                    break;
                }
            }
        }
        
        $order_no = generateOrderNo($conn);
        
        $conn->begin_transaction();
        try {
            // Insert order
            $sql = "INSERT INTO lab_orders (order_no, patient_id, doctor_id, hospital_id, order_date, total_amount, remarks, created_by) 
                    VALUES ('$order_no', $patient_id, $doctor_id, $hid, '$order_date', $total_amount, '$remarks', $created_by)";
            
            if ($conn->query($sql)) {
                $order_id = $conn->insert_id;
                
                // Insert order details
                foreach ($selected_tests as $test) {
                    $sql_detail = "INSERT INTO lab_order_details (order_id, test_id, price) 
                                   VALUES ($order_id, {$test['test_id']}, {$test['price']})";
                    $conn->query($sql_detail);
                }
                
                $conn->commit();
                $_SESSION['success'] = "Order #$order_no created successfully!";
                header("Location: lab_order.php");
                exit();
            } else {
                throw new Exception("Error creating order: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
    header("Location: lab_order.php");
    exit();
}

// ========== UPDATE ORDER STATUS ==========
if (isset($_GET['update_status'])) {
    $order_id = intval($_GET['update_status']);
    $status = $_GET['status'] ?? '';
    $valid_statuses = ['Pending','Assigned','Sample Collected','In Process','Completed','Cancelled'];
    
    if ($order_id > 0 && in_array($status, $valid_statuses)) {
        $conn->query("UPDATE lab_orders SET order_status = '$status' WHERE order_id = $order_id AND hospital_id = $hid");
        $_SESSION['success'] = "Order status updated to $status!";
    }
    header("Location: lab_order.php");
    exit();
}

// ========== UPDATE PAYMENT STATUS ==========
if (isset($_POST['update_payment'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? '';
    $valid_payments = ['Pending','Partial','Paid'];
    
    if ($order_id > 0 && in_array($payment_status, $valid_payments)) {
        $conn->query("UPDATE lab_orders SET payment_status = '$payment_status' WHERE order_id = $order_id AND hospital_id = $hid");
        $_SESSION['success'] = "Payment status updated to $payment_status!";
    }
    header("Location: lab_order.php");
    exit();
}

// ========== ASSIGN TECHNICIAN ==========
if (isset($_POST['assign_technician'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $technician_id = intval($_POST['technician_id'] ?? 0);
    
    if ($order_id > 0 && $technician_id > 0) {
        $conn->query("UPDATE lab_orders SET technician_id = $technician_id, order_status = 'Assigned' WHERE order_id = $order_id AND hospital_id = $hid");
        $_SESSION['success'] = "Technician assigned successfully! Status updated to Assigned.";
    }
    header("Location: lab_order.php");
    exit();
}

// ========== GENERATE BILL ==========
if (isset($_GET['generate_bill'])) {
    $order_id = intval($_GET['generate_bill']);
    if ($order_id > 0) {
        // Check if bill already exists
        $check_bill = $conn->query("SELECT bill_id FROM lab_bill WHERE order_id = $order_id");
        if ($check_bill && $check_bill->num_rows > 0) {
            $_SESSION['error'] = "Bill already generated for this order!";
        } else {
            // Get order details
            $order_data = $conn->query("SELECT * FROM lab_orders WHERE order_id = $order_id AND hospital_id = $hid");
            if ($order_data && $order_data->num_rows > 0) {
                $order = $order_data->fetch_assoc();
                $bill_no = "BILL" . date("Ymd") . str_pad($order_id, 4, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO lab_bill (order_id, bill_no, total_amount, final_amount, payment_status) 
                        VALUES ($order_id, '$bill_no', {$order['total_amount']}, {$order['total_amount']}, 'Pending')";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = "Bill #$bill_no generated successfully!";
                } else {
                    $_SESSION['error'] = "Error generating bill: " . $conn->error;
                }
            }
        }
    }
    header("Location: lab_order.php");
    exit();
}

// ========== DELETE ORDER ==========
if (isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    if ($order_id > 0) {
        $conn->query("UPDATE lab_orders SET delete_flag = 1 WHERE order_id = $order_id AND hospital_id = $hid");
        $_SESSION['success'] = "Order deleted successfully!";
    }
    header("Location: lab_order.php");
    exit();
}

// ========== CANCEL ORDER ==========
if (isset($_GET['cancel_order'])) {
    $order_id = intval($_GET['cancel_order']);
    if ($order_id > 0) {
        $conn->query("UPDATE lab_orders SET order_status = 'Cancelled' WHERE order_id = $order_id AND hospital_id = $hid");
        $_SESSION['success'] = "Order cancelled successfully!";
    }
    header("Location: lab_order.php");
    exit();
}

// ========== PAGE LOAD ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause with hospital filter
$where_clause = "WHERE o.delete_flag = 0 AND o.hospital_id = $hid";
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $where_clause .= " AND (o.order_no LIKE '%$search_escaped%' 
                        OR COALESCE(p.patient_name, '') LIKE '%$search_escaped%' 
                        OR COALESCE(d.doctor_name, '') LIKE '%$search_escaped%'
                        OR o.order_status LIKE '%$search_escaped%')";
}
if (!empty($status_filter)) {
    $where_clause .= " AND o.order_status = '$status_filter'";
}

// Get total count
$sql_count = "SELECT COUNT(*) as total FROM lab_orders o 
              LEFT JOIN patients p ON o.patient_id = p.patient_id 
              LEFT JOIN doctor d ON o.doctor_id = d.doctor_id 
              $where_clause";
$result_count = $conn->query($sql_count);
if ($result_count) {
    $total_records = $result_count->fetch_assoc()['total'];
} else {
    $total_records = 0;
}
$total_pages = max(1, ceil($total_records / $limit));
if ($page > $total_pages) $page = $total_pages;

// Get orders
$sql = "SELECT
        o.*,
        COALESCE(p.patient_name,'Patient Deleted') AS patient_name,
        COALESCE(p.mobile,'') AS mobile_no,
        COALESCE(d.doctor_name,'Doctor Deleted') AS doctor_name,
        COALESCE(d.qualification,'') AS qualification,
        COUNT(od.detail_id) AS test_count,
        GROUP_CONCAT(t.test_name SEPARATOR ', ') AS tests,
        s.name AS technician_name
FROM lab_orders o
LEFT JOIN patients p ON o.patient_id = p.patient_id
LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
LEFT JOIN lab_order_details od ON o.order_id = od.order_id
LEFT JOIN lab_tests t ON od.test_id = t.test_id
LEFT JOIN staff s ON o.technician_id = s.staff_id
$where_clause
GROUP BY o.order_id
ORDER BY o.order_id DESC
LIMIT $offset,$limit";
$orderresult = $conn->query($sql);

// Get bill status for each order
$bill_status = [];
$bill_sql = "SELECT order_id, bill_no, payment_status FROM lab_bill";
$bill_result = $conn->query($bill_sql);
if ($bill_result) {
    while ($row = $bill_result->fetch_assoc()) {
        $bill_status[$row['order_id']] = $row;
    }
}

// Get all orders count for debug
$all_orders_count = 0;
$all_orders_sql = "SELECT COUNT(*) as total FROM lab_orders WHERE delete_flag = 0";
$all_orders_result = $conn->query($all_orders_sql);
if ($all_orders_result) {
    $all_orders_count = $all_orders_result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Orders</title>
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
        .form-select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }
        .form-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-warning:hover { background: #d97706; }
        .btn-info { background: #0ea5e9; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-info:hover { background: #0284c7; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        
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
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.assigned { background: #dbeafe; color: #1e40af; }
        .status-badge.sample_collected { background: #e0e7ff; color: #3730a3; }
        .status-badge.in_process { background: #e0f2fe; color: #0369a1; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.cancelled { background: #fecaca; color: #991b1b; }
        
        .payment-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .payment-badge.pending { background: #fef3c7; color: #92400e; }
        .payment-badge.partial { background: #fef3c7; color: #92400e; }
        .payment-badge.paid { background: #dcfce7; color: #166534; }
        
        .search-wrapper { position: relative; }
        .search-wrapper .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .search-wrapper .form-input { padding-left: 38px; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; animation: slideDown 0.3s ease; }
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
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        
        .test-checkbox-list { max-height: 200px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px; }
        .test-checkbox-list label { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 4px; cursor: pointer; }
        .test-checkbox-list label:hover { background: #f3f4f6; }
        .test-checkbox-list input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        
        .selected-tests-summary { background: #f9fafb; border-radius: 8px; padding: 12px; margin-top: 8px; }
        .selected-tests-summary .test-item { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        .selected-tests-summary .test-item:last-child { border-bottom: none; }
        .selected-tests-summary .total { font-weight: 600; font-size: 14px; margin-top: 8px; padding-top: 8px; border-top: 2px solid #d1d5db; }
        
        .actions-cell { white-space: nowrap; }
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .price-badge { font-weight: 600; color: #059669; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .test-names { font-size: 11px; color: #6b7280; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; }
        
        .action-dropdown { position: relative; display: inline-block; }
        .action-dropdown-content { display: none; position: absolute; right: 0; background: white; min-width: 200px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); border-radius: 8px; z-index: 1000; padding: 8px 0; border: 1px solid #e5e7eb; }
        .action-dropdown-content a, .action-dropdown-content button { display: block; width: 100%; padding: 8px 16px; text-align: left; background: none; border: none; cursor: pointer; font-size: 13px; color: #374151; transition: all 0.2s; }
        .action-dropdown-content a:hover, .action-dropdown-content button:hover { background: #f3f4f6; }
        .action-dropdown:hover .action-dropdown-content { display: block; }
        .action-dropdown .btn-outline { cursor: pointer; }
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
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Lab Orders</h1>
                        <p class="text-gray-500 mt-1">Manage all lab test orders</p>
                    </div>
                    <button onclick="openOrderModal()" class="btn-primary">
                        <i class="fas fa-plus"></i> New Order
                    </button>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Search & Filter -->
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="lab_order.php" class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <div class="search-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" name="search" class="form-input" 
                                           placeholder="Search by order no, patient, doctor..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="min-w-[150px]">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status_filter" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Assigned" <?php echo $status_filter == 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="Sample Collected" <?php echo $status_filter == 'Sample Collected' ? 'selected' : ''; ?>>Sample Collected</option>
                                    <option value="In Process" <?php echo $status_filter == 'In Process' ? 'selected' : ''; ?>>In Process</option>
                                    <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                            <?php if (!empty($search) || !empty($status_filter)): ?>
                                <a href="lab_order.php" class="btn-outline"><i class="fas fa-times mr-1"></i> Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-medical mr-2 text-blue-500"></i> Order List <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $total_records; ?> orders)</span></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($orderresult && $orderresult->num_rows > 0): ?>
                            <div class="table-container">
                                <table>
    <thead>
        <tr>
            <th>#</th>
            <th>Order No</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Technician</th>
            <th>Date</th>
            <th>Tests</th>
            <th>Status</th>
            <th>Payment</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php $counter = $offset + 1; ?>
        <?php while ($row = $orderresult->fetch_assoc()): ?>
            <tr>
                <td><?php echo $counter++; ?></td>
                <td><span class="test-code-badge"><?php echo htmlspecialchars($row['order_no']); ?></span></td>
                <td>
                    <div class="font-medium"><?php echo htmlspecialchars($row['patient_name'] ?? 'N/A'); ?></div>
                    <?php if (!empty($row['mobile_no'])): ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['mobile_no']); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="font-medium"><?php echo htmlspecialchars($row['doctor_name'] ?? 'N/A'); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['qualification'] ?? ''); ?></div>
                </td>
                <td>
                    <?php if (!empty($row['technician_name'])): ?>
                        <span class="text-sm font-medium text-green-600"><?php echo htmlspecialchars($row['technician_name']); ?></span>
                    <?php else: ?>
                        <span class="text-xs text-gray-400">Not Assigned</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d-m-Y', strtotime($row['order_date'])); ?></td>
                <td>
                    <span class="badge-count"><?php echo $row['test_count']; ?> Tests</span>
                </td>
                <td>
                    <?php 
                    $status_class = strtolower(str_replace(' ', '_', $row['order_status']));
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['order_status']); ?></span>
                </td>
                <td>
                    <?php 
                    $payment_class = strtolower($row['payment_status']);
                    ?>
                    <span class="payment-badge <?php echo $payment_class; ?>"><?php echo htmlspecialchars($row['payment_status']); ?></span>
                    <?php if (isset($bill_status[$row['order_id']])): ?>
                        <br><span class="text-xs text-blue-600">Bill: <?php echo $bill_status[$row['order_id']]['bill_no']; ?></span>
                    <?php endif; ?>
                </td>
                <td class="actions-cell">
                    <div class="flex items-center gap-1 flex-wrap">
                        <!-- View Button -->
                        <a href="order_details.php?id=<?php echo $row['order_id']; ?>" 
                           class="btn-info btn-sm" 
                           title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <!-- Edit Button -->
                        <a href="edit_order.php?id=<?php echo $row['order_id']; ?>" 
                           class="btn-warning btn-sm" 
                           title="Edit Order">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <!-- Delete Button -->
                        <a href="?delete_order=<?php echo $row['order_id']; ?>" 
                           class="btn-danger btn-sm" 
                           title="Delete Order"
                           onclick="return confirm('Are you sure you want to delete this order?');">
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
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>"><i class="fas fa-chevron-left"></i></a>
                                    <?php else: ?>
                                        <a class="disabled"><i class="fas fa-chevron-left"></i></a>
                                    <?php endif; ?>
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    if ($start_page > 1): ?>
                                        <a href="?page=1&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>">1</a>
                                        <?php if ($start_page > 2): ?><span class="px-2 text-gray-400">...</span><?php endif; ?>
                                    <?php endif; ?>
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?><span class="px-2 text-gray-400">...</span><?php endif; ?>
                                        <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>"><?php echo $total_pages; ?></a>
                                    <?php endif; ?>
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>"><i class="fas fa-chevron-right"></i></a>
                                    <?php else: ?>
                                        <a class="disabled"><i class="fas fa-chevron-right"></i></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-medical"></i>
                                <p class="text-lg font-medium text-gray-700">No orders found</p>
                                <p class="text-sm text-gray-400 mt-1">
                                    <?php 
                                    if (!empty($search)) {
                                        echo 'No results found for "<strong>' . htmlspecialchars($search) . '</strong>"';
                                    } else {
                                        echo 'Click "New Order" to create your first lab order.';
                                        if (count($patients) == 0) {
                                            echo '<br><span class="text-red-500">⚠️ No patients found. Please add patients first.</span>';
                                        }
                                        if (count($tests) == 0) {
                                            echo '<br><span class="text-red-500">⚠️ No tests found. Please add tests first.</span>';
                                        }
                                    }
                                    ?>
                                </p>
                                <?php if (!empty($search)): ?>
                                    <a href="lab_order.php" class="btn-outline mt-3 inline-block"><i class="fas fa-times mr-1"></i> Clear Search</a>
                                <?php else: ?>
                                    <button onclick="openOrderModal()" class="btn-primary mt-3 inline-block">
                                        <i class="fas fa-plus mr-1"></i> Create Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ========== NEW ORDER MODAL ========== -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle mr-2 text-blue-500"></i> New Lab Order</h2>
                <button class="modal-close" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <form method="POST" action="lab_order.php" id="orderForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Patient <span class="required">*</span></label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php if (!empty($patients)): ?>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo $p['patient_id']; ?>">
                                        <?php echo htmlspecialchars($p['patient_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No patients found for this hospital</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Doctor <span class="required">*</span></label>
                        <select class="form-select" name="doctor_id" required>
                            <option value="">Select Doctor</option>
                            <?php if (!empty($doctors)): ?>
                                <?php foreach ($doctors as $d): ?>
                                    <option value="<?php echo $d['doctor_id']; ?>">
                                        <?php echo htmlspecialchars($d['doctor_name'] . ' (' . $d['qualification'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No doctors found for this hospital</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Order Date</label>
                    <input type="date" class="form-input" name="order_date" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Select Tests <span class="required">*</span></label>
                    <div class="test-checkbox-list" id="testCheckboxList">
                        <?php if (!empty($tests)): ?>
                            <?php foreach ($tests as $t): ?>
                                <label>
                                    <input type="checkbox" name="test_ids[]" value="<?php echo $t['test_id']; ?>" 
                                           onchange="updateSelectedTests()">
                                    <span><?php echo htmlspecialchars($t['test_code']); ?></span>
                                    <span class="text-gray-500 text-sm">- <?php echo htmlspecialchars($t['test_name']); ?></span>
                                    <span class="price-badge text-sm ml-auto">₹<?php echo number_format($t['price'], 2); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-red-500 text-sm p-2">No active tests found for this hospital. Please add tests first.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="selected-tests-summary" id="selectedTestsSummary">
                    <div class="text-gray-500 text-sm">No tests selected</div>
                    <div class="total" id="totalAmount">Total: ₹0.00</div>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea class="form-input" name="remarks" rows="2" placeholder="Any special instructions..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('orderModal')">Cancel</button>
                    <button type="submit" name="create_order" class="btn-primary">
                        <i class="fas fa-save"></i> Create Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========== MODAL FUNCTIONS ==========
        function openOrderModal() {
            document.getElementById('orderModal').classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        // ========== UPDATE SELECTED TESTS SUMMARY ==========
        function updateSelectedTests() {
            const checkboxes = document.querySelectorAll('#testCheckboxList input[type="checkbox"]:checked');
            const summary = document.getElementById('selectedTestsSummary');
            let total = 0;
            let html = '';

            if (checkboxes.length === 0) {
                html = '<div class="text-gray-500 text-sm">No tests selected</div>';
            } else {
                checkboxes.forEach(cb => {
                    const label = cb.closest('label');
                    const name = label.querySelector('span:nth-child(2)').textContent;
                    const priceText = label.querySelector('.price-badge').textContent;
                    const price = parseFloat(priceText.replace('₹', ''));
                    total += price;
                    html += `<div class="test-item">
                                <span>${name}</span>
                                <span>₹${price.toFixed(2)}</span>
                            </div>`;
                });
            }

            html += `<div class="total">Total: ₹${total.toFixed(2)}</div>`;
            summary.innerHTML = html;
        }

        // ========== CLOSE MODAL ON ESC ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(el => el.classList.remove('show'));
            }
        });

        // ========== CLICK OUTSIDE MODAL =s=========
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });

        // Auto-submit for dropdown changes
        document.querySelectorAll('.action-dropdown-content select').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    </script>
</body>
</html>