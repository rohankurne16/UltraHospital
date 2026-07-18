<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../config/hospital.php';
$conn->set_charset("utf8");

// staff_header.php handles the login check and pulls staff/hospital info
include 'staff_header.php';

$lab_orders = [];

// Every column pulled here comes straight from the DB via the FK relationships:
// lab_orders.patient_id -> patients.patient_id, lab_orders.doctor_id -> doctor.doctor_id
$sql = "SELECT
            lo.id AS order_id,
            lo.order_no,
            p.patient_id,
            p.patient_name,
            d.doctor_id,
            d.doctor_name,
            lo.order_date,
            lo.total_amount,
            lo.paid_amount,
            lo.payment_status,
            lo.status AS order_status
        FROM lab_orders lo
        JOIN patients p ON lo.patient_id = p.patient_id
        JOIN doctor d ON lo.doctor_id = d.doctor_id
        WHERE (lo.delete_flag = 0 OR lo.delete_flag IS NULL)
        ORDER BY lo.order_date DESC, lo.order_no DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lab_orders[] = $row;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'Pending': return 'status-pending';
        case 'In-Process': return 'status-in-process';
        case 'Completed': return 'status-completed';
        case 'Cancelled': return 'status-cancelled';
        case 'Paid': return 'status-paid';
        case 'Partial': return 'status-partial';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Lab Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .table-container {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb;
            overflow-x: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; }
        td { font-size: 14px; color: #334155; }
        tr:last-child td { border-bottom: none; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in-process { background-color: #bfdbfe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-partial { background-color: #fef3c7; color: #92400e; }
        .action-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 6px 10px; border-radius: 6px; font-size: 13px; font-weight: 500;
            transition: all 0.2s ease; text-decoration: none; margin-right: 4px;
        }
        .action-btn.view { background-color: #e0f2fe; color: #0284c7; }
        .action-btn.view:hover { background-color: #bae6fd; }
        .action-btn.report { background-color: #dbeafe; color: #1d4ed8; }
        .action-btn.report:hover { background-color: #bfdbfe; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php // header already rendered above by staff_header.php ?>

        <div class="flex flex-1 items-start">
            <?php include 'staff_sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="staff_dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Lab Orders</h1>
                            <p class="text-gray-500">View all laboratory orders and their statuses.</p>
                        </div>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Patient Name</th>
                                    <th>Doctor Name</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Payment Status</th>
                                    <th>Order Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lab_orders)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-gray-500">No lab orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lab_orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_no']); ?></td>
                                            <td><?php echo htmlspecialchars($order['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($order['paid_amount'], 2)); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo getStatusClass($order['payment_status']); ?>">
                                                    <?php echo htmlspecialchars($order['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo getStatusClass($order['order_status']); ?>">
                                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="create_lab_report.php?order_id=<?php echo (int)$order['order_id']; ?>" class="action-btn report" title="Create Report">
                                                    <i data-lucide="file-plus" class="w-4 h-4"></i>
                                                </a>
                                                <a href="view_lab_report_details.php?order_id=<?php echo (int)$order['order_id']; ?>" class="action-btn view" title="View Order Details">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
