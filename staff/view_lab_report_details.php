<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../config/hospital.php';
$conn->set_charset("utf8");

include 'staff_header.php';

$report_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$order_id  = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

$report_details = null;
$order_details = null;
$error_message = null;

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

function formatDate($date_str) {
    if (empty($date_str) || $date_str == '0000-00-00') return 'N/A';
    return date('d M Y', strtotime($date_str));
}

if ($report_id) {
    // A single completed report, joined to its order/patient/doctor via FKs
    $sql = "SELECT
                lr.id AS report_id,
                lo.id AS order_id,
                lo.order_no,
                lo.status AS order_status,
                p.patient_id,
                p.patient_name,
                p.patient_image AS patient_photo,
                d.doctor_id,
                d.doctor_name,
                d.doctor_photo,
                lr.test_name,
                lr.result_value,
                lr.normal_range,
                lr.unit,
                lr.report_date,
                lr.report_status,
                lr.upload_report_file,
                lr.remark,
                lo.total_amount AS order_total_amount,
                lo.paid_amount AS order_paid_amount,
                lo.payment_status AS order_payment_status
            FROM lab_reports lr
            JOIN lab_orders lo ON lr.lab_order_id = lo.id
            JOIN patients p ON lr.patient_id = p.patient_id
            JOIN doctor d ON lr.doctor_id = d.doctor_id
            WHERE lr.id = ? AND (lr.delete_flag = 0 OR lr.delete_flag IS NULL)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $report_details = $result->fetch_assoc();
        } else {
            $error_message = "Lab report not found or has been deleted.";
        }
        $stmt->close();
    } else {
        $error_message = "Database query error.";
    }
} elseif ($order_id) {
    // Order-level view: the order itself plus every test/report tied to it
    $sql_order = "SELECT
                    lo.id AS order_id, lo.order_no, lo.order_date, lo.status AS order_status,
                    lo.total_amount, lo.paid_amount, lo.payment_status,
                    p.patient_id, p.patient_name, p.patient_image AS patient_photo,
                    d.doctor_id, d.doctor_name, d.doctor_photo
                  FROM lab_orders lo
                  JOIN patients p ON lo.patient_id = p.patient_id
                  JOIN doctor d ON lo.doctor_id = d.doctor_id
                  WHERE lo.id = ? AND (lo.delete_flag = 0 OR lo.delete_flag IS NULL)";
    $stmt_order = $conn->prepare($sql_order);
    if ($stmt_order) {
        $stmt_order->bind_param("i", $order_id);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();
        if ($result_order && $result_order->num_rows > 0) {
            $order_details = $result_order->fetch_assoc();

            $sql_items = "SELECT li.id AS item_id, li.test_name, li.test_price,
                                 lr.id AS report_id, lr.result_value, lr.normal_range, lr.unit,
                                 lr.report_date, lr.report_status, lr.upload_report_file, lr.remark
                          FROM lab_order_items li
                          LEFT JOIN lab_reports lr ON li.id = lr.lab_order_item_id
                          WHERE li.lab_order_id = ? AND (li.delete_flag = 0 OR li.delete_flag IS NULL)
                          ORDER BY li.test_name ASC";
            $stmt_items = $conn->prepare($sql_items);
            $order_details['items'] = [];
            if ($stmt_items) {
                $stmt_items->bind_param("i", $order_id);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                while ($row = $result_items->fetch_assoc()) {
                    $order_details['items'][] = $row;
                }
                $stmt_items->close();
            }
        } else {
            $error_message = "Lab order not found or has been deleted.";
        }
        $stmt_order->close();
    } else {
        $error_message = "Database query error.";
    }
} else {
    $error_message = "Invalid report or order ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .main-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 24px; border: 1px solid #e5e7eb; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 24px; font-size: 18px; font-weight: 600; }
        .card-body { padding: 24px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .info-item { background: #f9fafb; padding: 12px; border-radius: 8px; border-left: 4px solid #667eea; }
        .info-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-value { font-size: 14px; font-weight: 500; color: #1f2937; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in-process { background: #bfdbfe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .profile-section { display: flex; gap: 20px; margin-bottom: 24px; align-items: center; }
        .profile-image { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 3px solid #667eea; }
        .profile-info h3 { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0; }
        .profile-info p { font-size: 13px; color: #6b7280; margin: 4px 0; }
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .error-message { background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 8px; border-left: 4px solid #dc2626; }
        .download-btn {
            background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;
            border: none; cursor: pointer; transition: background-color 0.2s ease; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .download-btn:hover { background-color: #2563eb; }
        table.items-table { width: 100%; border-collapse: collapse; }
        table.items-table th, table.items-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        table.items-table th { background: #f8fafc; font-weight: 600; color: #475569; text-transform: uppercase; font-size: 11px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) {
            .two-column { grid-template-columns: 1fr; }
            .profile-section { flex-direction: column; align-items: center; text-align: center; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <div class="flex flex-1 items-start">
            <?php include 'staff_sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="view_lab_orders.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Lab Report Details</h1>
                            <p class="text-gray-500">Comprehensive view of a laboratory order and its reports.</p>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="main-card">
                            <div class="card-body">
                                <div class="error-message">
                                    <i data-lucide="alert-circle" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($report_details): ?>
                        <div class="main-card">
                            <div class="card-header">
                                <i data-lucide="clipboard-list" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>
                                Report Information
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item"><div class="info-label">Order No</div><div class="info-value"><?php echo htmlspecialchars($report_details['order_no']); ?></div></div>
                                    <div class="info-item"><div class="info-label">Test Name</div><div class="info-value"><?php echo htmlspecialchars($report_details['test_name']); ?></div></div>
                                    <div class="info-item"><div class="info-label">Report Date</div><div class="info-value"><?php echo formatDate($report_details['report_date']); ?></div></div>
                                    <div class="info-item"><div class="info-label">Status</div><div class="info-value"><span class="status-badge <?php echo getStatusClass($report_details['report_status']); ?>"><?php echo htmlspecialchars($report_details['report_status']); ?></span></div></div>
                                    <div class="info-item"><div class="info-label">Result Value</div><div class="info-value"><?php echo htmlspecialchars($report_details['result_value'] ?? 'N/A'); ?></div></div>
                                    <div class="info-item"><div class="info-label">Normal Range</div><div class="info-value"><?php echo htmlspecialchars($report_details['normal_range'] ?? 'N/A'); ?></div></div>
                                    <div class="info-item"><div class="info-label">Unit</div><div class="info-value"><?php echo htmlspecialchars($report_details['unit'] ?? 'N/A'); ?></div></div>
                                </div>

                                <?php if (!empty($report_details['remark'])): ?>
                                    <div style="margin-top:16px;">
                                        <div class="info-label">Remark</div>
                                        <div style="background:white;padding:12px;border-radius:6px;border:1px solid #e5e7eb;margin-top:8px;">
                                            <?php echo nl2br(htmlspecialchars($report_details['remark'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($report_details['upload_report_file'])): ?>
                                    <div style="margin-top:24px;text-align:center;">
                                        <a href="<?php echo htmlspecialchars($report_details['upload_report_file']); ?>" target="_blank" class="download-btn">
                                            <i data-lucide="download" class="w-4 h-4"></i> Download Report File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="two-column">
                            <div class="main-card">
                                <div class="card-header"><i data-lucide="user" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>Patient Information</div>
                                <div class="card-body">
                                    <div class="profile-section">
                                        <img src="<?php echo htmlspecialchars($report_details['patient_photo'] ?? 'https://via.placeholder.com/100'); ?>" alt="Patient Photo" class="profile-image">
                                        <div class="profile-info">
                                            <h3><?php echo htmlspecialchars($report_details['patient_name']); ?></h3>
                                            <p><strong>Order No:</strong> <?php echo htmlspecialchars($report_details['order_no']); ?></p>
                                        </div>
                                    </div>
                                    <div class="info-grid">
                                        <div class="info-item"><div class="info-label">Order Total</div><div class="info-value"><?php echo htmlspecialchars(number_format($report_details['order_total_amount'], 2)); ?></div></div>
                                        <div class="info-item"><div class="info-label">Order Paid</div><div class="info-value"><?php echo htmlspecialchars(number_format($report_details['order_paid_amount'], 2)); ?></div></div>
                                        <div class="info-item"><div class="info-label">Payment Status</div><div class="info-value"><span class="status-badge <?php echo getStatusClass($report_details['order_payment_status']); ?>"><?php echo htmlspecialchars($report_details['order_payment_status']); ?></span></div></div>
                                    </div>
                                </div>
                            </div>

                            <div class="main-card">
                                <div class="card-header"><i data-lucide="stethoscope" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>Referring Doctor Information</div>
                                <div class="card-body">
                                    <div class="profile-section">
                                        <img src="<?php echo htmlspecialchars($report_details['doctor_photo'] ?? 'https://via.placeholder.com/100'); ?>" alt="Doctor Photo" class="profile-image">
                                        <div class="profile-info"><h3><?php echo htmlspecialchars($report_details['doctor_name']); ?></h3></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($order_details): ?>
                        <div class="main-card">
                            <div class="card-header"><i data-lucide="clipboard-list" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>Order Information</div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item"><div class="info-label">Order No</div><div class="info-value"><?php echo htmlspecialchars($order_details['order_no']); ?></div></div>
                                    <div class="info-item"><div class="info-label">Order Date</div><div class="info-value"><?php echo formatDate($order_details['order_date']); ?></div></div>
                                    <div class="info-item"><div class="info-label">Order Status</div><div class="info-value"><span class="status-badge <?php echo getStatusClass($order_details['order_status']); ?>"><?php echo htmlspecialchars($order_details['order_status']); ?></span></div></div>
                                    <div class="info-item"><div class="info-label">Total Amount</div><div class="info-value"><?php echo htmlspecialchars(number_format($order_details['total_amount'], 2)); ?></div></div>
                                    <div class="info-item"><div class="info-label">Paid Amount</div><div class="info-value"><?php echo htmlspecialchars(number_format($order_details['paid_amount'], 2)); ?></div></div>
                                    <div class="info-item"><div class="info-label">Payment Status</div><div class="info-value"><span class="status-badge <?php echo getStatusClass($order_details['payment_status']); ?>"><?php echo htmlspecialchars($order_details['payment_status']); ?></span></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="two-column">
                            <div class="main-card">
                                <div class="card-header"><i data-lucide="user" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>Patient Information</div>
                                <div class="card-body">
                                    <div class="profile-section">
                                        <img src="<?php echo htmlspecialchars($order_details['patient_photo'] ?? 'https://via.placeholder.com/100'); ?>" alt="Patient Photo" class="profile-image">
                                        <div class="profile-info"><h3><?php echo htmlspecialchars($order_details['patient_name']); ?></h3></div>
                                    </div>
                                </div>
                            </div>
                            <div class="main-card">
                                <div class="card-header"><i data-lucide="stethoscope" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>Referring Doctor Information</div>
                                <div class="card-body">
                                    <div class="profile-section">
                                        <img src="<?php echo htmlspecialchars($order_details['doctor_photo'] ?? 'https://via.placeholder.com/100'); ?>" alt="Doctor Photo" class="profile-image">
                                        <div class="profile-info"><h3><?php echo htmlspecialchars($order_details['doctor_name']); ?></h3></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="main-card">
                            <div class="card-header"><i data-lucide="flask-conical" class="w-5 h-5" style="display:inline;margin-right:8px;"></i>Tests &amp; Reports</div>
                            <div class="card-body" style="padding:0;">
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th>Test</th><th>Price</th><th>Result</th><th>Normal Range</th><th>Unit</th><th>Status</th><th>File</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($order_details['items'])): ?>
                                            <tr><td colspan="7" class="text-center py-4 text-gray-500">No tests found for this order.</td></tr>
                                        <?php else: foreach ($order_details['items'] as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['test_name']); ?></td>
                                                <td><?php echo htmlspecialchars(number_format($item['test_price'], 2)); ?></td>
                                                <td><?php echo htmlspecialchars($item['result_value'] ?? '—'); ?></td>
                                                <td><?php echo htmlspecialchars($item['normal_range'] ?? '—'); ?></td>
                                                <td><?php echo htmlspecialchars($item['unit'] ?? '—'); ?></td>
                                                <td>
                                                    <?php if ($item['report_id']): ?>
                                                        <span class="status-badge <?php echo getStatusClass($item['report_status']); ?>"><?php echo htmlspecialchars($item['report_status']); ?></span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-pending">Awaiting Result</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($item['upload_report_file'])): ?>
                                                        <a href="<?php echo htmlspecialchars($item['upload_report_file']); ?>" target="_blank" class="text-blue-600 hover:underline">View</a>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
