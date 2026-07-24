<?php
session_start();
include "../config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];
$user_id = $_SESSION['staff_id'] ?? 0;

// Get order ID from URL
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id == 0) {
    echo "<script>alert('Invalid Order ID'); window.location='dashboard.php';</script>";
    exit();
}

// ========== GET ORDER DETAILS ==========
$sql_order = "SELECT o.*, p.patient_name, p.mobile, p.gender, p.age, p.address, 
               d.doctor_name, d.department, d.qualification,
               CONCAT(s.name, ' (', s.role, ')') as technician_name
               FROM lab_orders o
               LEFT JOIN patients p ON o.patient_id = p.patient_id
               LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
               LEFT JOIN staff s ON o.technician_id = s.staff_id
               WHERE o.order_id = $order_id AND o.delete_flag = 0";

$result_order = $conn->query($sql_order);

if (!$result_order || $result_order->num_rows == 0) {
    echo "<script>alert('Order not found!'); window.location='dashboard.php';</script>";
    exit();
}

$order = $result_order->fetch_assoc();

// ========== GET TEST DETAILS ==========
$sql_tests = "SELECT od.*, t.test_name, t.test_code, t.normal_range, t.unit, t.price,
               r.result_value, r.remarks as result_remarks, r.report_status
               FROM lab_order_details od
               LEFT JOIN lab_tests t ON od.test_id = t.test_id
               LEFT JOIN lab_test_results r ON od.detail_id = r.order_detail_id
               WHERE od.order_id = $order_id AND od.delete_flag = 0
               ORDER BY od.detail_id ASC";

$result_tests = $conn->query($sql_tests);
$tests = [];
if ($result_tests && $result_tests->num_rows > 0) {
    while ($row = $result_tests->fetch_assoc()) {
        $tests[] = $row;
    }
}

// ========== GET REPORTS ==========
$sql_reports = "SELECT r.*, t.test_name 
                FROM lab_reports r
                LEFT JOIN lab_order_details od ON r.detail_id = od.detail_id
                LEFT JOIN lab_tests t ON od.test_id = t.test_id
                WHERE r.order_id = $order_id
                ORDER BY r.report_id DESC";

$result_reports = $conn->query($sql_reports);
$reports = [];
if ($result_reports && $result_reports->num_rows > 0) {
    while ($row = $result_reports->fetch_assoc()) {
        $reports[] = $row;
    }
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

// Status badge class
function getStatusClass($status) {
    $status = strtolower(str_replace(' ', '_', $status));
    $classes = [
        'pending' => 'pending',
        'assigned' => 'assigned',
        'sample_collected' => 'sample_collected',
        'in_process' => 'in_process',
        'completed' => 'completed',
        'cancelled' => 'cancelled'
    ];
    return $classes[$status] ?? 'pending';
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
        
        i, .fas, .far, .fal, .fab, .fa, .icon, [class*="fa-"] {
            color: #3b82f6 !important;
        }
        
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.assigned { background: #dbeafe; color: #1e40af; }
        .status-badge.sample_collected { background: #e0e7ff; color: #3730a3; }
        .status-badge.in_process { background: #e0f2fe; color: #0369a1; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.cancelled { background: #fecaca; color: #991b1b; }
        
        .test-code-badge { font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .info-item { padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .info-label { font-weight: 500; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
        .info-value { font-weight: 500; color: #1f2937; margin-top: 2px; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        
        .result-normal { color: #16a34a; }
        .result-abnormal { color: #dc2626; font-weight: bold; }
        
        .print-btn { background: #8b5cf6; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .print-btn:hover { background: #7c3aed; }
        
        .back-btn { background: #6b7280; color: white; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .back-btn:hover { background: #4b5563; }
    </style>
</head>
<body>

    <?php include '../Sidebar.php'; ?>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <main class="main-content">
                
                <!-- Back Button -->
                <div class="mb-4">
                    <button onclick="window.location.href='dashboard.php'" class="back-btn">
                        <i class="fas fa-arrow-left text-white"></i> Back to Dashboard
                    </button>
                </div>

                <!-- Order Details -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3><i class="fas fa-file-medical mr-2"></i> Order #<?php echo htmlspecialchars($order['order_no']); ?></h3>
                        <span class="status-badge <?php echo getStatusClass($order['order_status']); ?>">
                            <?php echo htmlspecialchars($order['order_status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Order Number</div>
                                <div class="info-value"><span class="test-code-badge"><?php echo htmlspecialchars($order['order_no']); ?></span></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Order Date</div>
                                <div class="info-value"><?php echo date('d-m-Y h:i A', strtotime($order['order_date'] ?? $order['created_at'] ?? 'now')); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Patient Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['patient_name'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Patient Mobile</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['mobile'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['gender'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Age</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['age'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Doctor</div>
                                <div class="info-value">Dr. <?php echo htmlspecialchars($order['doctor_name'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Department</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['department'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Technician</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['technician_name'] ?? 'Not Assigned'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Tests</div>
                                <div class="info-value"><span class="badge-count"><?php echo count($tests); ?> tests</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tests Details -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3><i class="fas fa-flask mr-2"></i> Test Results</h3>
                        <span class="badge-count"><?php echo count($tests); ?> tests</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($tests)): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Test Code</th>
                                            <th>Test Name</th>
                                            <th>Result</th>
                                            <th>Normal Range</th>
                                            <th>Unit</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($tests as $test): ?>
                                            <?php 
                                            $result_value = $test['result_value'] ?? '-';
                                            $normal_range = $test['normal_range'] ?? '-';
                                            $is_abnormal = false;
                                            
                                            if ($result_value != '-' && $normal_range != '-') {
                                                if (strpos($normal_range, '-') !== false) {
                                                    $range = explode('-', $normal_range);
                                                    if (count($range) == 2 && is_numeric($result_value)) {
                                                        $min = trim($range[0]);
                                                        $max = trim($range[1]);
                                                        if (is_numeric($min) && is_numeric($max)) {
                                                            if ($result_value < $min || $result_value > $max) {
                                                                $is_abnormal = true;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code'] ?? 'N/A'); ?></span></td>
                                                <td><?php echo htmlspecialchars($test['test_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($result_value != '-'): ?>
                                                        <span class="<?php echo $is_abnormal ? 'result-abnormal' : 'result-normal'; ?>">
                                                            <?php echo htmlspecialchars($result_value); ?>
                                                            <?php if ($is_abnormal): ?>
                                                                <i class="fas fa-exclamation-triangle text-red-500" title="Abnormal"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($normal_range); ?></td>
                                                <td><?php echo htmlspecialchars($test['unit'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if ($test['result_value']): ?>
                                                        <span class="status-badge completed">Completed</span>
                                                    <?php else: ?>
                                                        <span class="status-badge pending">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-flask text-4xl mb-3 block"></i>
                                <p>No tests found for this order</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reports -->
                <?php if (!empty($reports)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt mr-2"></i> Generated Reports</h3>
                        <span class="badge-count"><?php echo count($reports); ?> reports</span>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Report No</th>
                                        <th>Test</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><span class="test-code-badge"><?php echo htmlspecialchars($report['report_no']); ?></span></td>
                                            <td><?php echo htmlspecialchars($report['test_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($report['report_date'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo strtolower($report['report_status']); ?>">
                                                    <?php echo htmlspecialchars($report['report_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($report['report_file']): ?>
                                                    <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" 
                                                       target="_blank" class="btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="../documents/reports/<?php echo htmlspecialchars($report['report_file']); ?>" 
                                                       download class="btn-success btn-sm">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">No file</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

</body>
</html>