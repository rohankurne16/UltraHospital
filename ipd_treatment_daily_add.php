<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

$master_id = isset($_GET['master_id']) ? intval($_GET['master_id']) : 0;
$message = "";
$messageType = "";

if ($master_id == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid treatment!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

// Get treatment master details
$master_sql = "SELECT tm.*, p.patient_name, d.doctor_name, a.admission_no 
                FROM ipd_treatment_master tm
                LEFT JOIN patients p ON tm.patient_id = p.patient_id
                LEFT JOIN doctor d ON tm.doctor_id = d.doctor_id
                LEFT JOIN ipd_admissions a ON tm.ipd_id = a.id
                WHERE tm.treatment_master_id = '$master_id' AND tm.delete_flag = 0";
$master_result = $conn->query($master_sql);

if (!$master_result || $master_result->num_rows == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Treatment not found!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

$master = $master_result->fetch_assoc();

// Get next day number
$day_result = $conn->query("SELECT COUNT(*) as total FROM ipd_treatment_daily WHERE treatment_master_id = '$master_id' AND delete_flag = 0");
$day_count = 0;
if ($day_result && $day_result->num_rows > 0) {
    $row = $day_result->fetch_assoc();
    $day_count = $row['total'];
}
$next_day = $day_count + 1;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $treatment_date = mysqli_real_escape_string($conn, $_POST['treatment_date'] ?? date('Y-m-d'));
    $daily_doctor_notes = mysqli_real_escape_string($conn, $_POST['daily_doctor_notes'] ?? '');
    $nursing_notes = mysqli_real_escape_string($conn, $_POST['nursing_notes'] ?? '');
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
    $day_number = intval($_POST['day_number'] ?? $next_day);
    
    if (empty($treatment_date)) {
        $message = "Please enter treatment date!";
        $messageType = "error";
    } else {
        $sql = "INSERT INTO ipd_treatment_daily (
                    treatment_master_id, treatment_date, day_number,
                    daily_doctor_notes, nursing_notes, remarks
                ) VALUES (
                    '$master_id', '$treatment_date', '$day_number',
                    '$daily_doctor_notes', '$nursing_notes', '$remarks'
                )";
        
        if ($conn->query($sql)) {
            $daily_id = $conn->insert_id; // This is treatment_daily_id
            
            // ========== INSERT VITALS ==========
            if (isset($_POST['temperature']) && !empty($_POST['temperature'][0])) {
                $temperatures = $_POST['temperature'];
                $pulses = $_POST['pulse'];
                $blood_pressures = $_POST['blood_pressure'];
                $respiratory_rates = $_POST['respiratory_rate'];
                $spo2s = $_POST['spo2'];
                $weights = $_POST['weight'];
                
                for ($i = 0; $i < count($temperatures); $i++) {
                    if (!empty($temperatures[$i])) {
                        $temp = mysqli_real_escape_string($conn, $temperatures[$i]);
                        $pulse = mysqli_real_escape_string($conn, $pulses[$i] ?? '');
                        $bp = mysqli_real_escape_string($conn, $blood_pressures[$i] ?? '');
                        $rr = mysqli_real_escape_string($conn, $respiratory_rates[$i] ?? '');
                        $spo2 = mysqli_real_escape_string($conn, $spo2s[$i] ?? '');
                        $weight = mysqli_real_escape_string($conn, $weights[$i] ?? '');
                        
                        // IMPORTANT: treatment_daily_id = daily_id
                        $vital_sql = "INSERT INTO ipd_vitals (
                            treatment_daily_id, temperature, pulse, blood_pressure,
                            respiratory_rate, spo2, weight
                        ) VALUES (
                            '$daily_id', '$temp', '$pulse', '$bp', '$rr', '$spo2', '$weight'
                        )";
                        
                        $conn->query($vital_sql);
                    }
                }
            }
            
            // ========== INSERT MEDICINES ==========
            if (isset($_POST['medicine_name']) && !empty($_POST['medicine_name'][0])) {
                $medicine_names = $_POST['medicine_name'];
                $dosages = $_POST['dosage'];
                $frequencies = $_POST['frequency'];
                $number_of_days = $_POST['number_of_days'];
                $routes = $_POST['route'];
                $medicine_remarks = $_POST['medicine_remarks'];
                
                for ($i = 0; $i < count($medicine_names); $i++) {
                    if (!empty($medicine_names[$i])) {
                        $med_name = mysqli_real_escape_string($conn, $medicine_names[$i]);
                        $dosage = mysqli_real_escape_string($conn, $dosages[$i] ?? '');
                        $freq = mysqli_real_escape_string($conn, $frequencies[$i] ?? '');
                        $days = mysqli_real_escape_string($conn, $number_of_days[$i] ?? '');
                        $route = mysqli_real_escape_string($conn, $routes[$i] ?? '');
                        $med_remark = mysqli_real_escape_string($conn, $medicine_remarks[$i] ?? '');
                        
                        // IMPORTANT: treatment_daily_id = daily_id
                        $medicine_sql = "INSERT INTO ipd_medicine_chart (
                            treatment_daily_id, medicine_name, dosage, frequency,
                            number_of_days, route, remarks
                        ) VALUES (
                            '$daily_id', '$med_name', '$dosage', '$freq',
                            '$days', '$route', '$med_remark'
                        )";
                        
                        $conn->query($medicine_sql);
                    }
                }
            }
            
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Day ' . $day_number . ' treatment added successfully!'];
            header("Location: ipd_treatment_view.php?id=$master_id");
            exit();
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Daily Treatment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { width: 260px; height: 100vh; background: #f8f3f3; position: fixed; left: 0; top: 0; color: #0b0707; z-index: 50; overflow-y: auto; }
        .header { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; position: fixed; left: 260px; right: 0; top: 0; z-index: 40; display: flex; align-items: center; padding: 0 1.5rem; }
        .main-content { margin-left: 260px; padding: 84px 28px 20px 28px; min-height: 100vh; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; max-width: 950px; margin: 0 auto; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; position: static; }
        .form-card .body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #0f172a; margin-bottom: 4px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; transition: all 0.2s ease; outline: none; background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-success { padding: 10px 24px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { padding: 10px 24px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-danger:hover { background: #dc2626; }
        .section-title { font-size: 1.1rem; font-weight: 600; color: #0f172a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        .vital-row, .medicine-row { background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
        .patient-info { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .patient-info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } .header { left: 0; } }
        @media (max-width: 768px) { .grid-3, .grid-4, .patient-info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="sidebar"><?php include 'Sidebar.php'; ?></div>
    <header class="header"><div class="flex items-center justify-between w-full"><?php include 'header.php'; ?></div></header>

    <main class="main-content">
        <!-- Page Header with Back Button -->
        <div class="mb-6 flex items-center gap-4">
            <a href="ipd_treatment_view.php?id=<?php echo $master_id; ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 p-2 transition-colors shadow-sm">
                <i class="fas fa-arrow-left text-gray-600"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Day <?php echo $next_day; ?> Treatment</h1>
                <p class="text-gray-500">Patient: <?php echo htmlspecialchars($master['patient_name']); ?> | IPD: <?php echo htmlspecialchars($master['admission_no']); ?></p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="header"><h3 class="font-semibold text-gray-800">Daily Treatment Record</h3></div>
            <div class="body">
                <form method="POST">
                    <input type="hidden" name="day_number" value="<?php echo $next_day; ?>">
                    
                    <div class="patient-info">
                        <div class="patient-info-grid">
                            <div><strong>Patient:</strong> <?php echo htmlspecialchars($master['patient_name']); ?></div>
                            <div><strong>Doctor:</strong> <?php echo htmlspecialchars($master['doctor_name']); ?></div>
                            <div><strong>IPD No:</strong> <?php echo htmlspecialchars($master['admission_no']); ?></div>
                            <div><strong>Day:</strong> <?php echo $next_day; ?></div>
                            <div><strong>Diagnosis:</strong> <?php echo htmlspecialchars($master['diagnosis'] ?? 'N/A'); ?></div>
                            <div><strong>Status:</strong> <?php echo htmlspecialchars($master['status']); ?></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Treatment Date <span class="required">*</span></label>
                            <input type="date" name="treatment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4">
                        <div class="form-group">
                            <label>Daily Doctor Notes</label>
                            <textarea name="daily_doctor_notes" rows="3" placeholder="Enter doctor's notes for today"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Nursing Notes</label>
                            <textarea name="nursing_notes" rows="3" placeholder="Enter nursing notes for today"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Remarks</label>
                            <textarea name="remarks" rows="2" placeholder="Any additional remarks"></textarea>
                        </div>
                    </div>

                    <!-- Vitals Section -->
                    <div class="mt-6">
                        <div class="section-title flex justify-between items-center">
                            <span>Vitals</span>
                            <button type="button" onclick="addVitalRow()" class="btn-success text-sm py-1 px-3">
                                <i class="fas fa-plus"></i> Add Vital
                            </button>
                        </div>
                        <div id="vitalsContainer">
                            <div class="vital-row">
                                <div class="grid-3">
                                    <div class="form-group">
                                        <label>Temperature (°C)</label>
                                        <input type="number" step="0.1" name="temperature[]" class="vital-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Pulse (bpm)</label>
                                        <input type="number" name="pulse[]" class="vital-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Blood Pressure</label>
                                        <input type="text" name="blood_pressure[]" placeholder="120/80" class="vital-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Respiratory Rate</label>
                                        <input type="number" name="respiratory_rate[]" class="vital-input">
                                    </div>
                                    <div class="form-group">
                                        <label>SpO₂ (%)</label>
                                        <input type="number" name="spo2[]" class="vital-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Weight (kg)</label>
                                        <input type="number" step="0.01" name="weight[]" class="vital-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medicine Chart -->
                    <div class="mt-6">
                        <div class="section-title flex justify-between items-center">
                            <span>Medicine Chart</span>
                            <button type="button" onclick="addMedicineRow()" class="btn-success text-sm py-1 px-3">
                                <i class="fas fa-plus"></i> Add Medicine
                            </button>
                        </div>
                        <div id="medicinesContainer">
                            <div class="medicine-row">
                                <div class="grid-4">
                                    <div class="form-group">
                                        <label>Medicine Name</label>
                                        <input type="text" name="medicine_name[]" class="medicine-input" placeholder="Enter medicine name">
                                    </div>
                                    <div class="form-group">
                                        <label>Dosage</label>
                                        <input type="text" name="dosage[]" class="medicine-input" placeholder="e.g., 500mg">
                                    </div>
                                    <div class="form-group">
                                        <label>Frequency</label>
                                        <input type="text" name="frequency[]" class="medicine-input" placeholder="e.g., BD, TDS">
                                    </div>
                                    <div class="form-group">
                                        <label>Number of Days</label>
                                        <input type="number" name="number_of_days[]" class="medicine-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Route</label>
                                        <input type="text" name="route[]" class="medicine-input" placeholder="e.g., Oral, IV">
                                    </div>
                                    <div class="form-group">
                                        <label>Remarks</label>
                                        <input type="text" name="medicine_remarks[]" class="medicine-input" placeholder="Any remarks">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                        <button type="submit" class="btn-primary">Save Day <?php echo $next_day; ?> Treatment</button>
                        <a href="ipd_treatment_view.php?id=<?php echo $master_id; ?>" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function addVitalRow() {
            const container = document.getElementById('vitalsContainer');
            const row = document.createElement('div');
            row.className = 'vital-row';
            row.innerHTML = `
                <div class="grid-3">
                    <div class="form-group">
                        <label>Temperature (°C)</label>
                        <input type="number" step="0.1" name="temperature[]" class="vital-input">
                    </div>
                    <div class="form-group">
                        <label>Pulse (bpm)</label>
                        <input type="number" name="pulse[]" class="vital-input">
                    </div>
                    <div class="form-group">
                        <label>Blood Pressure</label>
                        <input type="text" name="blood_pressure[]" placeholder="120/80" class="vital-input">
                    </div>
                    <div class="form-group">
                        <label>Respiratory Rate</label>
                        <input type="number" name="respiratory_rate[]" class="vital-input">
                    </div>
                    <div class="form-group">
                        <label>SpO₂ (%)</label>
                        <input type="number" name="spo2[]" class="vital-input">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.01" name="weight[]" class="vital-input">
                    </div>
                    <div class="form-group flex items-end">
                        <button type="button" onclick="removeRow(this)" class="btn-danger text-sm py-1 px-3">Remove</button>
                    </div>
                </div>
            `;
            container.appendChild(row);
        }

        function addMedicineRow() {
            const container = document.getElementById('medicinesContainer');
            const row = document.createElement('div');
            row.className = 'medicine-row';
            row.innerHTML = `
                <div class="grid-4">
                    <div class="form-group">
                        <label>Medicine Name</label>
                        <input type="text" name="medicine_name[]" class="medicine-input" placeholder="Enter medicine name">
                    </div>
                    <div class="form-group">
                        <label>Dosage</label>
                        <input type="text" name="dosage[]" class="medicine-input" placeholder="e.g., 500mg">
                    </div>
                    <div class="form-group">
                        <label>Frequency</label>
                        <input type="text" name="frequency[]" class="medicine-input" placeholder="e.g., BD, TDS">
                    </div>
                    <div class="form-group">
                        <label>Number of Days</label>
                        <input type="number" name="number_of_days[]" class="medicine-input">
                    </div>
                    <div class="form-group">
                        <label>Route</label>
                        <input type="text" name="route[]" class="medicine-input" placeholder="e.g., Oral, IV">
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <input type="text" name="medicine_remarks[]" class="medicine-input" placeholder="Any remarks">
                    </div>
                    <div class="form-group flex items-end">
                        <button type="button" onclick="removeRow(this)" class="btn-danger text-sm py-1 px-3">Remove</button>
                    </div>
                </div>
            `;
            container.appendChild(row);
        }

        function removeRow(button) {
            const row = button.closest('.vital-row') || button.closest('.medicine-row');
            if (row && document.querySelectorAll('.vital-row, .medicine-row').length > 1) {
                row.remove();
            } else {
                alert('At least one row is required!');
            }
        }
    </script>
</body>
</html>