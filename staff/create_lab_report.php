<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../config/hospital.php';
$conn->set_charset("utf8");

include 'staff_header.php';

$lab_orders = [];
$tests_for_order = [];
$message = "";
$message_type = "";

// Lab orders that still have at least one un-reported test
$sql_orders = "SELECT lo.id, lo.order_no, p.patient_name, d.doctor_name, lo.status
               FROM lab_orders lo
               JOIN patients p ON lo.patient_id = p.patient_id
               JOIN doctor d ON lo.doctor_id = d.doctor_id
               WHERE (lo.delete_flag = 0 OR lo.delete_flag IS NULL) AND lo.status != 'Completed'
               ORDER BY lo.order_date DESC";
$result_orders = $conn->query($sql_orders);
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) {
        $lab_orders[] = $row;
    }
}

$selected_order_id = null;
$current_patient_id = null;
$current_doctor_id = null;

// If an order is selected, fetch its FK details plus the tests that don't have a report yet.
// This requires lab_reports to carry a lab_order_item_id FK back to lab_order_items.id —
// if that column doesn't exist yet, add it: ALTER TABLE lab_reports ADD lab_order_item_id INT NULL;
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $selected_order_id = intval($_GET['order_id']);

    $sql_order_details = "SELECT patient_id, doctor_id FROM lab_orders WHERE id = ?";
    $stmt_order_details = $conn->prepare($sql_order_details);
    if ($stmt_order_details) {
        $stmt_order_details->bind_param("i", $selected_order_id);
        $stmt_order_details->execute();
        $result_order_details = $stmt_order_details->get_result();
        if ($row_details = $result_order_details->fetch_assoc()) {
            $current_patient_id = $row_details['patient_id'];
            $current_doctor_id = $row_details['doctor_id'];
        }
        $stmt_order_details->close();
    }

    $sql_tests = "SELECT li.id AS item_id, li.test_name, li.test_price
                  FROM lab_order_items li
                  LEFT JOIN lab_reports lr ON li.id = lr.lab_order_item_id
                  WHERE li.lab_order_id = ? AND (li.delete_flag = 0 OR li.delete_flag IS NULL) AND lr.id IS NULL
                  ORDER BY li.test_name ASC";
    $stmt_tests = $conn->prepare($sql_tests);
    if ($stmt_tests) {
        $stmt_tests->bind_param("i", $selected_order_id);
        $stmt_tests->execute();
        $result_tests = $stmt_tests->get_result();
        while ($row = $result_tests->fetch_assoc()) {
            $tests_for_order[] = $row;
        }
        $stmt_tests->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lab_order_id = $_POST["lab_order_id"] ?? null;
    $patient_id = $_POST["patient_id"] ?? null;
    $doctor_id = $_POST["doctor_id"] ?? null;
    $report_date = $_POST["report_date"] ?? date("Y-m-d");
    $item_ids = $_POST["item_id"] ?? [];
    $test_names = $_POST["test_name"] ?? [];
    $result_values = $_POST["result_value"] ?? [];
    $normal_ranges = $_POST["normal_range"] ?? [];
    $units = $_POST["unit"] ?? [];
    $remarks = $_POST["remark"] ?? [];

    $upload_report_file = null;
    if (isset($_FILES["upload_report_file"]) && $_FILES["upload_report_file"]["error"] == 0) {
        $target_dir = "../uploads/lab_reports/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["upload_report_file"]["name"], PATHINFO_EXTENSION);
        $new_file_name = uniqid("report_") . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        if (move_uploaded_file($_FILES["upload_report_file"]["tmp_name"], $target_file)) {
            $upload_report_file = $target_file;
        } else {
            $message = "Error uploading file.";
            $message_type = "error";
        }
    }

    if (empty($lab_order_id) || empty($patient_id) || empty($doctor_id) || empty($test_names)) {
        $message = "Please select a lab order and enter results for at least one test.";
        $message_type = "error";
    } else if ($message_type != "error") {
        $conn->begin_transaction();
        try {
            // lab_order_item_id ties each report row back to the specific test that was ordered
            $sql_insert_report = "INSERT INTO lab_reports (lab_order_id, lab_order_item_id, patient_id, doctor_id, test_name, result_value, normal_range, unit, report_date, report_status, upload_report_file, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, ?)";
            $stmt_insert_report = $conn->prepare($sql_insert_report);
            if (!$stmt_insert_report) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            foreach ($test_names as $key => $test_name) {
                $item_id = $item_ids[$key] ?? null;
                $result_value = $result_values[$key] ?? null;
                $normal_range = $normal_ranges[$key] ?? null;
                $unit = $units[$key] ?? null;
                $remark = $remarks[$key] ?? null;

                $stmt_insert_report->bind_param(
                    "iiiissssss",
                    $lab_order_id, $item_id, $patient_id, $doctor_id, $test_name,
                    $result_value, $normal_range, $unit, $report_date,
                    $upload_report_file, $remark
                );
                $stmt_insert_report->execute();
            }
            $stmt_insert_report->close();

            // Mark the order Completed once every one of its items has a matching report
            $sql_check_tests = "SELECT COUNT(li.id) AS total_tests, COUNT(lr.id) AS reported_tests
                                FROM lab_order_items li
                                LEFT JOIN lab_reports lr ON li.id = lr.lab_order_item_id
                                WHERE li.lab_order_id = ? AND (li.delete_flag = 0 OR li.delete_flag IS NULL)";
            $stmt_check_tests = $conn->prepare($sql_check_tests);
            if ($stmt_check_tests) {
                $stmt_check_tests->bind_param("i", $lab_order_id);
                $stmt_check_tests->execute();
                $result_check_tests = $stmt_check_tests->get_result();
                $counts = $result_check_tests->fetch_assoc();
                $stmt_check_tests->close();

                if ($counts['total_tests'] > 0 && $counts['total_tests'] == $counts['reported_tests']) {
                    $sql_update_order = "UPDATE lab_orders SET status = 'Completed' WHERE id = ?";
                    $stmt_update_order = $conn->prepare($sql_update_order);
                    if ($stmt_update_order) {
                        $stmt_update_order->bind_param("i", $lab_order_id);
                        $stmt_update_order->execute();
                        $stmt_update_order->close();
                    }
                } else {
                    $sql_update_order = "UPDATE lab_orders SET status = 'In-Process' WHERE id = ? AND status = 'Pending'";
                    $stmt_update_order = $conn->prepare($sql_update_order);
                    if ($stmt_update_order) {
                        $stmt_update_order->bind_param("i", $lab_order_id);
                        $stmt_update_order->execute();
                        $stmt_update_order->close();
                    }
                }
            }

            $conn->commit();
            $message = "Lab report(s) created successfully.";
            $message_type = "success";
            header("Location: create_lab_report.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error creating lab report: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Lab Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .card {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb;
            margin-bottom: 24px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .card-header h3 { font-size: 18px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 8px; }
        .form-group select, .form-group input[type="date"], .form-group input[type="text"], .form-group input[type="file"], .form-group textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; color: #334155; background-color: #fff; transition: border-color 0.2s ease;
        }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2);
        }
        .btn-primary {
            background-color: #6366f1; color: white; padding: 10px 20px; border-radius: 8px;
            font-size: 16px; font-weight: 500; border: none; cursor: pointer; transition: background-color 0.2s ease;
        }
        .btn-primary:hover { background-color: #4f46e5; }
        .message-success { background-color: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #a7f3d0; }
        .message-error { background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #fecaca; }
        .test-results-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;
            margin-top: 16px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f9fafb;
        }
        .test-result-item { background: white; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; }
        .test-result-item label { font-size: 13px; font-weight: 500; color: #334155; margin-bottom: 4px; }
        .test-result-item input, .test-result-item textarea { padding: 8px; font-size: 13px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <div class="flex flex-1 items-start">
            <?php include 'staff_sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-4xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="staff_dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Create Lab Report</h1>
                            <p class="text-gray-500">Enter results and upload reports for lab orders.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="<?php echo $message_type == 'success' ? 'message-success' : 'message-error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header"><h3>Lab Report Details</h3></div>
                        <div class="card-body">
                            <form action="create_lab_report.php" method="GET" class="mb-6">
                                <div class="form-group">
                                    <label for="order_id">Select Lab Order</label>
                                    <select id="order_id" name="order_id" onchange="this.form.submit()" required>
                                        <option value="">Select an Order</option>
                                        <?php foreach ($lab_orders as $order): ?>
                                            <option value="<?php echo htmlspecialchars($order['id']); ?>" <?php echo ($selected_order_id == $order['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($order['order_no']); ?> - <?php echo htmlspecialchars($order['patient_name']); ?> (Dr. <?php echo htmlspecialchars($order['doctor_name']); ?>) - Status: <?php echo htmlspecialchars($order['status']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>

                            <?php if ($selected_order_id && !empty($tests_for_order)): ?>
                                <form action="create_lab_report.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="lab_order_id" value="<?php echo htmlspecialchars($selected_order_id); ?>">
                                    <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($current_patient_id); ?>">
                                    <input type="hidden" name="doctor_id" value="<?php echo htmlspecialchars($current_doctor_id); ?>">

                                    <div class="form-group">
                                        <label for="report_date">Report Date</label>
                                        <input type="date" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>

                                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Enter Test Results</h4>
                                    <?php foreach ($tests_for_order as $index => $test): ?>
                                        <div class="card mb-4">
                                            <div class="card-header bg-gray-50">
                                                <h4 class="text-md font-medium text-gray-700">Test: <?php echo htmlspecialchars($test['test_name']); ?></h4>
                                            </div>
                                            <div class="card-body">
                                                <input type="hidden" name="item_id[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($test['item_id']); ?>">
                                                <input type="hidden" name="test_name[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($test['test_name']); ?>">
                                                <div class="test-results-grid">
                                                    <div class="form-group test-result-item">
                                                        <label for="result_value_<?php echo $index; ?>">Result Value</label>
                                                        <input type="text" id="result_value_<?php echo $index; ?>" name="result_value[<?php echo $index; ?>]" placeholder="Enter result" required>
                                                    </div>
                                                    <div class="form-group test-result-item">
                                                        <label for="normal_range_<?php echo $index; ?>">Normal Range</label>
                                                        <input type="text" id="normal_range_<?php echo $index; ?>" name="normal_range[<?php echo $index; ?>]" placeholder="e.g., 4.0-10.0">
                                                    </div>
                                                    <div class="form-group test-result-item">
                                                        <label for="unit_<?php echo $index; ?>">Unit</label>
                                                        <input type="text" id="unit_<?php echo $index; ?>" name="unit[<?php echo $index; ?>]" placeholder="e.g., g/dL">
                                                    </div>
                                                    <div class="form-group test-result-item" style="grid-column: span 2;">
                                                        <label for="remark_<?php echo $index; ?>">Remark</label>
                                                        <textarea id="remark_<?php echo $index; ?>" name="remark[<?php echo $index; ?>]" rows="2" placeholder="Any additional remarks"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="form-group">
                                        <label for="upload_report_file">Upload Report File (PDF, Image)</label>
                                        <input type="file" id="upload_report_file" name="upload_report_file" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>

                                    <button type="submit" class="btn-primary">Submit Lab Report</button>
                                </form>
                            <?php elseif ($selected_order_id && empty($tests_for_order)): ?>
                                <div class="message-success">All tests for this order have been reported or no tests were found.</div>
                            <?php endif; ?>
                        </div>
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
