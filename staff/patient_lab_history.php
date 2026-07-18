<?php
session_start();
include 'config/hospital.php';

$conn->set_charset("utf8");

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$patient_name = "";
$patient_lab_orders = [];
$patient_lab_reports = [];
$error_message = null;

if (!$patient_id) {
    $error_message = "Patient ID is missing.";
} else {
    // Fetch patient name
    $sql_patient_name = "SELECT patient_name FROM patients WHERE patient_id = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
    $stmt_patient_name = $conn->prepare($sql_patient_name);
    if ($stmt_patient_name) {
        $stmt_patient_name->bind_param("i", $patient_id);
        $stmt_patient_name->execute();
        $result_patient_name = $stmt_patient_name->get_result();
        if ($row = $result_patient_name->fetch_assoc()) {
            $patient_name = $row['patient_name'];
        } else {
            $error_message = "Patient not found.";
        }
        $stmt_patient_name->close();
    } else {
        $error_message = "Database error fetching patient name.";
    }

    if (!$error_message) {
        // Fetch lab orders for the patient
        $sql_orders = "SELECT
                            lo.id AS order_id,
                            lo.order_no,
                            d.doctor_name,
                            lo.order_date,
                            lo.total_amount,
                            lo.paid_amount,
                            lo.payment_status,
                            lo.status AS order_status
                        FROM
                            lab_orders lo
                        JOIN
                            doctor d ON lo.doctor_id = d.doctor_id
                        WHERE
                            lo.patient_id = ? AND (lo.delete_flag = 0 OR lo.delete_flag IS NULL)
                        ORDER BY
                            lo.order_date DESC, lo.order_no DESC";

        $stmt_orders = $conn->prepare($sql_orders);
        if ($stmt_orders) {
            $stmt_orders->bind_param("i", $patient_id);
            $stmt_orders->execute();
            $result_orders = $stmt_orders->get_result();
            while ($row = $result_orders->fetch_assoc()) {
                $patient_lab_orders[] = $row;
            }
            $stmt_orders->close();
        } else {
            $error_message = "Database error fetching lab orders.";
        }

        // Fetch lab reports for the patient
        $sql_reports = "SELECT
                            lr.id AS report_id,
                            lo.order_no,
                            lr.test_name,
                            lr.result_value,
                            lr.normal_range,
                            lr.unit,
                            lr.report_date,
                            lr.report_status,
                            lr.upload_report_file
                        FROM
                            lab_reports lr
                        JOIN
                            lab_orders lo ON lr.lab_order_id = lo.id
                        WHERE
                            lr.patient_id = ? AND (lr.delete_flag = 0 OR lr.delete_flag IS NULL)
                        ORDER BY
                            lr.report_date DESC, lo.order_no DESC";

        $stmt_reports = $conn->prepare($sql_reports);
        if ($stmt_reports) {
            $stmt_reports->bind_param("i", $patient_id);
            $stmt_reports->execute();
            $result_reports = $stmt_reports->get_result();
            while ($row = $result_reports->fetch_assoc()) {
                $patient_lab_reports[] = $row;
            }
            $stmt_reports->close();
        } else {
            $error_message = "Database error fetching lab reports.";
        }
    }
}

$conn->close();

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
    <title>Patient Lab History - <?php echo htmlspecialchars($patient_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow-x: auto;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f8fafc;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        td {
            font-size: 14px;
            color: #334155;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-in-process {
            background-color: #bfdbfe;
            color: #1e40af;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-right: 4px;
        }
        .action-btn.view {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        .action-btn.view:hover {
            background-color: #bae6fd;
        }
        .action-btn.download {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        .action-btn.download:hover {
            background-color: #bfdbfe;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
            margin-bottom: 24px;
        }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Lab History for <?php echo htmlspecialchars($patient_name); ?></h1>
                            <p class="text-gray-500">View all lab orders and reports for this patient.</p>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="error-message">
                            <i data-lucide="alert-circle" class="w-5 h-5" style="display: inline; margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php else: ?>
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Lab Orders</h2>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order No</th>
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
                                    <?php if (empty($patient_lab_orders)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-gray-500">No lab orders found for this patient.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($patient_lab_orders as $order): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['order_no']); ?></td>
                                                <td><?php echo htmlspecialchars($order['doctor_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
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
                                                    <a href="view_lab_order_details.php?id=<?php echo htmlspecialchars($order['order_id']); ?>" class="action-btn view" title="View Order Details">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h2 class="text-xl font-bold text-gray-900 mb-4 mt-8">Lab Reports</h2>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order No</th>
                                        <th>Test Name</th>
                                        <th>Result</th>
                                        <th>Normal Range</th>
                                        <th>Unit</th>
                                        <th>Report Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patient_lab_reports)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-gray-500">No lab reports found for this patient.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($patient_lab_reports as $report): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['order_no']); ?></td>
                                                <td><?php echo htmlspecialchars($report['test_name']); ?></td>
                                                <td><?php echo htmlspecialchars($report['result_value']); ?></td>
                                                <td><?php echo htmlspecialchars($report['normal_range']); ?></td>
                                                <td><?php echo htmlspecialchars($report['unit']); ?></td>
                                                <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo getStatusClass($report['report_status']); ?>">
                                                        <?php echo htmlspecialchars($report['report_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($report['upload_report_file'])): ?>
                                                        <a href="<?php echo htmlspecialchars($report['upload_report_file']); ?>" target="_blank" class="action-btn download" title="Download Report">
                                                            <i data-lucide="download" class="w-4 h-4"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="view_lab_report_details.php?id=<?php echo htmlspecialchars($report['report_id']); ?>" class="action-btn view" title="View Report Details">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
