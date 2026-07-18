<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../config/hospital.php';
$conn->set_charset("utf8");

// staff_header.php handles the login/session check and renders the top bar
include 'staff_header.php';

$patients = [];
$doctors = [];

// NOTE: there is no "lab_tests" master table in the schema you shared, so the
// test catalogue below is still a static price list. If you add a lab_tests
// table (id, test_name, test_price), swap this array for a query like:
// "SELECT id, test_name, test_price FROM lab_tests WHERE (delete_flag = 0 OR delete_flag IS NULL)"
$lab_tests_master = [
    ["name" => "Complete Blood Count (CBC)", "price" => 250.00],
    ["name" => "Blood Glucose (Fasting)", "price" => 150.00],
    ["name" => "Lipid Profile", "price" => 400.00],
    ["name" => "Liver Function Test (LFT)", "price" => 350.00],
    ["name" => "Kidney Function Test (KFT)", "price" => 300.00],
    ["name" => "Thyroid Function Test (TFT)", "price" => 500.00],
    ["name" => "Urine Analysis", "price" => 100.00],
    ["name" => "X-Ray Chest PA View", "price" => 600.00],
    ["name" => "ECG", "price" => 200.00],
    ["name" => "MRI Brain", "price" => 5000.00]
];

// Fetch Patients (patients.patient_id is the FK target used by lab_orders.patient_id)
$sql_patients = "SELECT patient_id, patient_name FROM patients WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY patient_name ASC";
$result_patients = $conn->query($sql_patients);
if ($result_patients) {
    while ($row = $result_patients->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch Doctors (doctor.doctor_id is the FK target used by lab_orders.doctor_id)
$sql_doctors = "SELECT doctor_id, doctor_name, specialization FROM doctor WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY doctor_name ASC";
$result_doctors = $conn->query($sql_doctors);
if ($result_doctors) {
    while ($row = $result_doctors->fetch_assoc()) {
        $doctors[] = $row;
    }
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST["patient_id"] ?? null;
    $doctor_id = $_POST["doctor_id"] ?? null;
    $order_date = $_POST["order_date"] ?? date("Y-m-d");
    $selected_tests = $_POST["tests"] ?? [];
    $payment_status = $_POST["payment_status"] ?? "Pending";
    $paid_amount = $_POST["paid_amount"] ?? 0.00;

    if (empty($patient_id) || empty($doctor_id) || empty($selected_tests)) {
        $message = "Please fill all required fields and select at least one test.";
        $message_type = "error";
    } else {
        $conn->begin_transaction();
        try {
            $order_no = "LAB" . date("YmdHis") . rand(100, 999);
            $total_amount = 0;

            foreach ($selected_tests as $test_name) {
                foreach ($lab_tests_master as $master_test) {
                    if ($master_test["name"] == $test_name) {
                        $total_amount += $master_test["price"];
                        break;
                    }
                }
            }

            // patient_id and doctor_id are stored as real FKs into patients / doctor
            $sql_order = "INSERT INTO lab_orders (order_no, patient_id, doctor_id, order_date, total_amount, paid_amount, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
            $stmt_order = $conn->prepare($sql_order);
            if (!$stmt_order) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt_order->bind_param("siisdds", $order_no, $patient_id, $doctor_id, $order_date, $total_amount, $paid_amount, $payment_status);
            $stmt_order->execute();
            $lab_order_id = $stmt_order->insert_id;
            $stmt_order->close();

            $sql_item = "INSERT INTO lab_order_items (lab_order_id, test_name, test_price) VALUES (?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            if (!$stmt_item) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            foreach ($selected_tests as $test_name) {
                $test_price = 0;
                foreach ($lab_tests_master as $master_test) {
                    if ($master_test["name"] == $test_name) {
                        $test_price = $master_test["price"];
                        break;
                    }
                }
                $stmt_item->bind_param("isd", $lab_order_id, $test_name, $test_price);
                $stmt_item->execute();
            }
            $stmt_item->close();

            $conn->commit();
            $message = "Lab order created successfully with Order No: " . $order_no;
            $message_type = "success";
            $_POST = array();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error creating lab order: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Lab Order</title>
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
        .form-group select, .form-group input[type="date"], .form-group input[type="number"] {
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; color: #334155; background-color: #fff; transition: border-color 0.2s ease;
        }
        .form-group select:focus, .form-group input:focus {
            outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2);
        }
        .checkbox-group {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px;
        }
        .checkbox-item {
            display: flex; align-items: center; gap: 8px; padding: 10px 12px;
            border: 1px solid #e2e8f0; border-radius: 8px; background: #f9fafb;
        }
        .checkbox-item input { width: 16px; height: 16px; }
        .checkbox-item label { font-size: 13px; color: #334155; margin: 0; }
        .btn-primary {
            background-color: #6366f1; color: white; padding: 10px 20px; border-radius: 8px;
            font-size: 16px; font-weight: 500; border: none; cursor: pointer; transition: background-color 0.2s ease;
        }
        .btn-primary:hover { background-color: #4f46e5; }
        .message-success { background-color: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #a7f3d0; }
        .message-error { background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #fecaca; }
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
                            <h1 class="text-2xl font-bold text-gray-900">Create Lab Order</h1>
                            <p class="text-gray-500">Generate a new laboratory order for a patient.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="<?php echo $message_type == 'success' ? 'message-success' : 'message-error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header"><h3>Lab Order Details</h3></div>
                        <div class="card-body">
                            <form action="create_lab_order.php" method="POST">
                                <div class="form-group">
                                    <label for="patient_id">Patient</label>
                                    <select id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($patient['patient_name']); ?> (ID: <?php echo htmlspecialchars($patient['patient_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="doctor_id">Referring Doctor</label>
                                    <select id="doctor_id" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo htmlspecialchars($doctor['doctor_id']); ?>" <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doctor['doctor_name']); ?> (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="order_date">Order Date</label>
                                    <input type="date" id="order_date" name="order_date" value="<?php echo htmlspecialchars($_POST['order_date'] ?? date('Y-m-d')); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Select Lab Tests</label>
                                    <div class="checkbox-group">
                                        <?php foreach ($lab_tests_master as $test): ?>
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="test_<?php echo str_replace(' ', '_', $test['name']); ?>" name="tests[]" value="<?php echo htmlspecialchars($test['name']); ?>" data-price="<?php echo htmlspecialchars($test['price']); ?>" <?php echo (isset($_POST['tests']) && in_array($test['name'], $_POST['tests'])) ? 'checked' : ''; ?>>
                                                <label for="test_<?php echo str_replace(' ', '_', $test['name']); ?>">
                                                    <?php echo htmlspecialchars($test['name']); ?> (<?php echo htmlspecialchars(number_format($test['price'], 2)); ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="total_amount">Total Amount</label>
                                    <input type="number" id="total_amount" name="total_amount" step="0.01" readonly class="bg-gray-100" value="0.00">
                                </div>

                                <div class="form-group">
                                    <label for="payment_status">Payment Status</label>
                                    <select id="payment_status" name="payment_status">
                                        <option value="Pending" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Paid" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Partial" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Partial') ? 'selected' : ''; ?>>Partial</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="paid_amount">Paid Amount</label>
                                    <input type="number" id="paid_amount" name="paid_amount" step="0.01" value="<?php echo htmlspecialchars($_POST['paid_amount'] ?? '0.00'); ?>">
                                </div>

                                <button type="submit" class="btn-primary">Create Order</button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="tests[]"]');
            const totalAmountInput = document.getElementById('total_amount');
            const paidAmountInput = document.getElementById('paid_amount');
            const paymentStatusSelect = document.getElementById('payment_status');

            function calculateTotal() {
                let total = 0;
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) total += parseFloat(checkbox.dataset.price);
                });
                totalAmountInput.value = total.toFixed(2);
                if (paymentStatusSelect.value === 'Paid' && parseFloat(paidAmountInput.value) < total) {
                    paidAmountInput.value = total.toFixed(2);
                }
            }
            checkboxes.forEach(checkbox => checkbox.addEventListener('change', calculateTotal));
            paymentStatusSelect.addEventListener('change', function() {
                if (this.value === 'Paid') paidAmountInput.value = totalAmountInput.value;
                else if (this.value === 'Pending') paidAmountInput.value = '0.00';
            });
            calculateTotal();
        });
    </script>
</body>
</html>
