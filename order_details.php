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
$sql_tests = "SELECT od.*, 
              t.test_code,
              t.test_name,
              t.normal_range,
              t.unit,
              COALESCE(r.result_value, '') AS result_value,
              COALESCE(r.normal_range, '') AS result_normal_range,
              COALESCE(r.remarks, '') AS result_remarks,
              COALESCE(r.report_status, 'Pending') AS report_status
FROM lab_order_details od
LEFT JOIN lab_tests t ON od.test_id = t.test_id
LEFT JOIN lab_test_results r ON od.detail_id = r.order_detail_id
WHERE od.order_id = $order_id
ORDER BY od.detail_id ASC";

$tests_result = $conn->query($sql_tests);

// ========== GET BILL DETAILS ==========
$bill = null;
$sql_bill = "SELECT * FROM lab_bill WHERE order_id = $order_id";
$bill_result = $conn->query($sql_bill);
if ($bill_result && $bill_result->num_rows > 0) {
    $bill = $bill_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Order Details</title>
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
        
        .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-success:hover { background: #16a34a; }
        .btn-info { background: #0ea5e9; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-info:hover { background: #0284c7; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #ef4444; }
        
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
        
        .price-badge { font-weight: 600; color: #059669; }
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
        .info-item { padding: 12px 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6; }
        .info-item .label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px; }
        .info-item .value { font-size: 14px; font-weight: 500; color: #1f2937; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        
        .readonly-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #1e40af; margin-bottom: 16px; }
        .readonly-info i { margin-right: 6px; }
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
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Order Details</h1>
                        <p class="text-gray-500 mt-1">Order #<?php echo htmlspecialchars($order['order_no']); ?></p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <a href="lab_order.php" class="btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                        <?php if ($bill): ?>
                            <a href="view_bill.php?id=<?php echo $order_id; ?>" class="btn-success" target="_blank">
                                <i class="fas fa-file-invoice"></i> View Bill
                            </a>
                        <?php endif; ?>
                        <a href="print_order.php?id=<?php echo $order_id; ?>" target="_blank" class="btn-info">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
                    <div class="alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Read-only Info -->
                <div class="readonly-info">
                    <i class="fas fa-info-circle"></i> This is a read-only view. To make changes, go to the <a href="lab_order.php" class="text-blue-600 hover:underline">Orders List</a>.
                </div>

                <!-- Order Summary -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3><i class="fas fa-file-medical mr-2 text-blue-500"></i> Order Summary</h3>
                        <div>
                            <?php 
                            $status_class = strtolower(str_replace(' ', '_', $order['order_status']));
                            $payment_class = strtolower($order['payment_status']);
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                            <span class="payment-badge <?php echo $payment_class; ?> ml-2"><?php echo htmlspecialchars($order['payment_status']); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="label">Order No</span>
                                <span class="value test-code-badge"><?php echo htmlspecialchars($order['order_no']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Order Date</span>
                                <span class="value"><?php echo date('d-m-Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Amount</span>
                                <span class="value price-badge">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Patient</span>
                                <span class="value"><?php echo htmlspecialchars($order['patient_name']); ?></span>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['mobile_no']); ?></div>
                            </div>
                            <div class="info-item">
                                <span class="label">Doctor</span>
                                <span class="value"><?php echo htmlspecialchars($order['doctor_name']); ?></span>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['qualification']); ?></div>
                            </div>
                            <div class="info-item">
                                <span class="label">Technician</span>
                                <span class="value"><?php echo htmlspecialchars($order['technician_name']); ?></span>
                            </div>
                        </div>
                        <?php if (!empty($order['remarks'])): ?>
                            <div class="mt-3 p-3 bg-gray-50 rounded border border-gray-200">
                                <span class="font-medium text-sm">Remarks:</span>
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($order['remarks']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bill Summary -->
                <?php if ($bill): ?>
                <div class="card mb-6" style="border-left: 4px solid #22c55e;">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice mr-2 text-green-500"></i> Bill Details</h3>
                        <span class="payment-badge <?php echo strtolower($bill['payment_status']); ?>"><?php echo htmlspecialchars($bill['payment_status']); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="label">Bill No</span>
                                <span class="value test-code-badge"><?php echo htmlspecialchars($bill['bill_no']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Amount</span>
                                <span class="value price-badge">₹<?php echo number_format($bill['total_amount'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Final Amount</span>
                                <span class="value price-badge">₹<?php echo number_format($bill['final_amount'], 2); ?></span>
                            </div>
                        </div>
                        <div class="mt-3 action-buttons">
                            <a href="view_bill.php?id=<?php echo $order_id; ?>" class="btn-success btn-sm" target="_blank">
                                <i class="fas fa-file-invoice"></i> View Full Bill
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card mb-6" style="border-left: 4px solid #f59e0b;">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice mr-2 text-yellow-500"></i> Bill Status</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-gray-600">No bill has been generated for this order yet.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tests List -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-flask mr-2 text-blue-500"></i> Tests (<?php echo $order['test_count']; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($tests_result && $tests_result->num_rows > 0): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Test Code</th>
                                            <th>Test Name</th>
                                            <th>Price</th>
                                            <th>Normal Range</th>
                                            <th>Unit</th>
                                            <th>Result</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counter = 1; ?>
                                        <?php while ($test = $tests_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code']); ?></span></td>
                                                <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                                <td class="price-badge">₹<?php echo number_format($test['price'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($test['normal_range'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($test['unit'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if (!empty($test['result_value'])): ?>
                                                        <span class="font-medium"><?php echo htmlspecialchars($test['result_value']); ?></span>
                                                        <?php if (!empty($test['result_normal_range'])): ?>
                                                            <br><span class="text-xs text-gray-500">Range: <?php echo htmlspecialchars($test['result_normal_range']); ?></span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-xs">Not processed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $report_class = strtolower(str_replace(' ', '_', $test['report_status']));
                                                    ?>
                                                    <span class="status-badge <?php echo $report_class; ?>"><?php echo htmlspecialchars($test['report_status']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f9fafb; font-weight: 600;">
                                            <td colspan="3" class="text-right">Total</td>
                                            <td class="price-badge">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td colspan="4"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-flask"></i>
                                <p class="text-lg font-medium text-gray-700">No tests found</p>
                                <p class="text-sm text-gray-400 mt-1">No tests are associated with this order.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons - Only View Bill -->
                <div class="mt-6 action-buttons">
                    <!-- View Bill - Always Visible if Bill Exists -->
                    <?php if ($bill): ?>
                        <a href="view_bill.php?id=<?php echo $order_id; ?>" class="btn-success" target="_blank">
                            <i class="fas fa-file-invoice"></i> View Bill
                        </a>
                    <?php endif; ?>
                    
                    <!-- Back to Orders -->
                    <a href="lab_order.php" class="btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </main>
        </div>
    </div>
</body>
</html>