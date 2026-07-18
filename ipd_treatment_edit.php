<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

// Get treatment_daily_id from URL
$daily_id = isset($_GET['daily_id']) ? intval($_GET['daily_id']) : 0;
$master_id = isset($_GET['master_id']) ? intval($_GET['master_id']) : 0;
$message = "";
$messageType = "";

if ($daily_id == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid daily treatment ID!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

// Fetch daily treatment details
$daily_sql = "SELECT td.*, tm.ipd_id, tm.patient_id, tm.doctor_id, tm.diagnosis, tm.status,
              p.patient_name, p.patient_id as patient_code, p.age, p.gender, p.mobile, p.email, p.address,
              d.doctor_name,
              a.admission_no, a.ward_id, a.room_no, a.bed_no, a.admission_date,
              w.ward_name
              FROM ipd_treatment_daily td
              LEFT JOIN ipd_treatment_master tm ON td.treatment_master_id = tm.treatment_master_id
              LEFT JOIN patients p ON tm.patient_id = p.patient_id
              LEFT JOIN doctor d ON tm.doctor_id = d.doctor_id
              LEFT JOIN ipd_admissions a ON tm.ipd_id = a.id
              LEFT JOIN ward_master w ON a.ward_id = w.ward_id
              WHERE td.treatment_daily_id = '$daily_id' AND td.delete_flag = 0 AND tm.delete_flag = 0";

$daily_result = $conn->query($daily_sql);

if (!$daily_result || $daily_result->num_rows == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Daily treatment not found!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

$daily = $daily_result->fetch_assoc();
$master_id = $daily['treatment_master_id'];

// Fetch vitals for this day
$vitals = [];
$vital_result = $conn->query("SELECT * FROM ipd_vitals WHERE treatment_daily_id = '$daily_id' AND delete_flag = 0");
if ($vital_result && $vital_result->num_rows > 0) {
    while ($row = $vital_result->fetch_assoc()) {
        $vitals[] = $row;
    }
}

// Fetch medicines for this day
$medicines = [];
$med_result = $conn->query("SELECT * FROM ipd_medicine_chart WHERE treatment_daily_id = '$daily_id' AND delete_flag = 0");
if ($med_result && $med_result->num_rows > 0) {
    while ($row = $med_result->fetch_assoc()) {
        $medicines[] = $row;
    }
}

// Fetch doctors for dropdown
$doctors = [];
$result = $conn->query("SELECT doctor_id, doctor_name FROM doctor WHERE delete_flag = 0 ORDER BY doctor_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $treatment_date = mysqli_real_escape_string($conn, $_POST['treatment_date'] ?? '');
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id'] ?? '');
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis'] ?? '');
    $daily_doctor_notes = mysqli_real_escape_string($conn, $_POST['daily_doctor_notes'] ?? '');
    $nursing_notes = mysqli_real_escape_string($conn, $_POST['nursing_notes'] ?? '');
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Active');
    
    if (empty($treatment_date) || empty($doctor_id)) {
        $message = "Please fill all required fields!";
        $messageType = "error";
    } else {
        // 1. Update ipd_treatment_daily
        $update_daily_sql = "UPDATE ipd_treatment_daily SET 
                                treatment_date = '$treatment_date',
                                daily_doctor_notes = '$daily_doctor_notes',
                                nursing_notes = '$nursing_notes',
                                remarks = '$remarks',
                                modified_at = NOW()
                            WHERE treatment_daily_id = '$daily_id'";
        
        if ($conn->query($update_daily_sql)) {
            // 2. Update ipd_treatment_master (diagnosis, doctor, status)
            $update_master_sql = "UPDATE ipd_treatment_master SET 
                                    doctor_id = '$doctor_id',
                                    diagnosis = '$diagnosis',
                                    status = '$status',
                                    modified_at = NOW()
                                WHERE treatment_master_id = '$master_id'";
            $conn->query($update_master_sql);
            
            // 3. Delete existing vitals and medicines for this day (soft delete)
            $conn->query("UPDATE ipd_vitals SET delete_flag = 1, modified_at = NOW() WHERE treatment_daily_id = '$daily_id'");
            $conn->query("UPDATE ipd_medicine_chart SET delete_flag = 1, modified_at = NOW() WHERE treatment_daily_id = '$daily_id'");
            
            // 4. Insert updated vitals
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
                        
                        $conn->query("INSERT INTO ipd_vitals (
                            treatment_daily_id, temperature, pulse, blood_pressure,
                            respiratory_rate, spo2, weight
                        ) VALUES (
                            '$daily_id', '$temp', '$pulse', '$bp', '$rr', '$spo2', '$weight'
                        )");
                    }
                }
            }
            
            // 5. Insert updated medicines
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
                        
                        $conn->query("INSERT INTO ipd_medicine_chart (
                            treatment_daily_id, medicine_name, dosage, frequency,
                            number_of_days, route, remarks
                        ) VALUES (
                            '$daily_id', '$med_name', '$dosage', '$freq',
                            '$days', '$route', '$med_remark'
                        )");
                    }
                }
            }
            
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Treatment updated successfully!'];
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
    <title>Edit Daily Treatment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { width: 260px; height: 100vh; background: #1e293b; position: fixed; left: 0; top: 0; color: #fff; z-index: 50; overflow-y: auto; }
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
        .form-group input[readonly] { background: #f1f5f9; cursor: not-allowed; }
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
        <div class="mb-6 flex items-center gap-4">
            <a href="ipd_treatment_view.php?id=<?php echo $master_id; ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Day <?php echo $daily['day_number']; ?> Treatment</h1>
                <p class="text-gray-500">Patient: <?php echo htmlspecialchars($daily['patient_name']); ?> | IPD: <?php echo htmlspecialchars($daily['admission_no']); ?></p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="header"><h3 class="font-semibold text-gray-800">Daily Treatment Record - Day <?php echo $daily['day_number']; ?></h3></div>
            <div class="body">
                <form method="POST" id="editForm" action="ipd_treatment_edit.php?daily_id=<?php echo $daily_id; ?>&master_id=<?php echo $master_id; ?>">
                    <!-- Patient Info (Readonly) -->
                    <div class="patient-info">
                        <div class="patient-info-grid">
                            <div><strong>Patient Name:</strong> <?php echo htmlspecialchars($daily['patient_name']); ?></div>
                            <div><strong>Patient ID:</strong> <?php echo htmlspecialchars($daily['patient_code']); ?></div>
                            <div><strong>IPD No:</strong> <?php echo htmlspecialchars($daily['admission_no']); ?></div>
                            <div><strong>Ward:</strong> <?php echo htmlspecialchars($daily['ward_name'] ?? 'N/A'); ?></div>
                            <div><strong>Room:</strong> <?php echo htmlspecialchars($daily['room_no'] ?? 'N/A'); ?></div>
                            <div><strong>Bed:</strong> <?php echo htmlspecialchars($daily['bed_no'] ?? 'N/A'); ?></div>
                            <div><strong>Admission Date:</strong> <?php echo date('d-m-Y', strtotime($daily['admission_date'])); ?></div>
                            <div><strong>Age:</strong> <?php echo htmlspecialchars($daily['age'] ?? 'N/A'); ?></div>
                            <div><strong>Gender:</strong> <?php echo htmlspecialchars($daily['gender'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Editable Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Treatment Date <span class="required">*</span></label>
                            <input type="date" name="treatment_date" value="<?php echo $daily['treatment_date']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Doctor <span class="required">*</span></label>
                            <select name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo ($doctor['doctor_id'] == $daily['doctor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label>Diagnosis</label>
                            <textarea name="diagnosis" rows="2" placeholder="Enter diagnosis"><?php echo htmlspecialchars($daily['diagnosis'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label>Daily Doctor Notes</label>
                            <textarea name="daily_doctor_notes" rows="3" placeholder="Enter doctor's notes"><?php echo htmlspecialchars($daily['daily_doctor_notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label>Nursing Notes</label>
                            <textarea name="nursing_notes" rows="3" placeholder="Enter nursing notes"><?php echo htmlspecialchars($daily['nursing_notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label>Remarks</label>
                            <textarea name="remarks" rows="2" placeholder="Any additional remarks"><?php echo htmlspecialchars($daily['remarks'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Active" <?php echo ($daily['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Discharged" <?php echo ($daily['status'] == 'Discharged') ? 'selected' : ''; ?>>Discharged</option>
                                <option value="Transferred" <?php echo ($daily['status'] == 'Transferred') ? 'selected' : ''; ?>>Transferred</option>
                            </select>
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
                            <?php if (count($vitals) > 0): ?>
                                <?php foreach ($vitals as $vital): ?>
                                    <div class="vital-row">
                                        <div class="grid-3">
                                            <div class="form-group">
                                                <label>Temperature (°C)</label>
                                                <input type="number" step="0.1" name="temperature[]" class="vital-input" value="<?php echo $vital['temperature']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Pulse (bpm)</label>
                                                <input type="number" name="pulse[]" class="vital-input" value="<?php echo $vital['pulse']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Blood Pressure</label>
                                                <input type="text" name="blood_pressure[]" placeholder="120/80" class="vital-input" value="<?php echo $vital['blood_pressure']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Respiratory Rate</label>
                                                <input type="number" name="respiratory_rate[]" class="vital-input" value="<?php echo $vital['respiratory_rate']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>SpO₂ (%)</label>
                                                <input type="number" name="spo2[]" class="vital-input" value="<?php echo $vital['spo2']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Weight (kg)</label>
                                                <input type="number" step="0.01" name="weight[]" class="vital-input" value="<?php echo $vital['weight']; ?>">
                                            </div>
                                            <div class="form-group flex items-end">
                                                <button type="button" onclick="removeRow(this)" class="btn-danger text-sm py-1 px-3">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
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
                            <?php endif; ?>
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
                            <?php if (count($medicines) > 0): ?>
                                <?php foreach ($medicines as $med): ?>
                                    <div class="medicine-row">
                                        <div class="grid-4">
                                            <div class="form-group">
                                                <label>Medicine Name</label>
                                                <input type="text" name="medicine_name[]" class="medicine-input" placeholder="Enter medicine name" value="<?php echo htmlspecialchars($med['medicine_name']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Dosage</label>
                                                <input type="text" name="dosage[]" class="medicine-input" placeholder="e.g., 500mg" value="<?php echo htmlspecialchars($med['dosage'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Frequency</label>
                                                <input type="text" name="frequency[]" class="medicine-input" placeholder="e.g., BD, TDS" value="<?php echo htmlspecialchars($med['frequency'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Number of Days</label>
                                                <input type="number" name="number_of_days[]" class="medicine-input" value="<?php echo $med['number_of_days'] ?? ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Route</label>
                                                <input type="text" name="route[]" class="medicine-input" placeholder="e.g., Oral, IV" value="<?php echo htmlspecialchars($med['route'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Remarks</label>
                                                <input type="text" name="medicine_remarks[]" class="medicine-input" placeholder="Any remarks" value="<?php echo htmlspecialchars($med['remarks'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group flex items-end">
                                                <button type="button" onclick="removeRow(this)" class="btn-danger text-sm py-1 px-3">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
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
                                        <div class="form-group flex items-end">
                                            <button type="button" onclick="removeRow(this)" class="btn-danger text-sm py-1 px-3">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i> Update Treatment
                        </button>
                        <a href="ipd_treatment_view.php?id=<?php echo $master_id; ?>" class="btn-secondary">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
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
