<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;



if ($id == 0) {
    die("Invalid Treatment ID");
}
$message = "";
$messageType = "";



$sql = "SELECT tm.*, 
        p.patient_name, p.patient_id as patient_code, p.age, p.gender, p.mobile, p.email, p.address, p.blood_group,
        d.doctor_name, d.specialization, d.qualification, d.registration_number,
        a.id as ipd_id, a.admission_no, a.ward_id, a.room_no, a.bed_no, a.admission_date,
        w.ward_name
        FROM ipd_treatment_master tm
        LEFT JOIN patients p ON tm.patient_id = p.patient_id
        LEFT JOIN doctor d ON tm.doctor_id = d.doctor_id
        LEFT JOIN ipd_admissions a ON tm.ipd_id = a.id
        LEFT JOIN ward_master w ON a.ward_id = w.ward_id
        WHERE tm.treatment_master_id = '$id'
        AND (tm.delete_flag=0 OR tm.delete_flag IS NULL)";

$result = $conn->query($sql);

if (!$result) {
    die($conn->error);
}

echo "Rows = " . $result->num_rows . "<br>";

if ($result->num_rows > 0) {
    echo "<pre>";
    print_r($result->fetch_assoc());
}
exit;


if (!$result || $result->num_rows == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Treatment not found!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

$treatment = $result->fetch_assoc();

// Check if discharge already exists
$discharge_check = $conn->query("SELECT discharge_id FROM ipd_discharge_summary WHERE treatment_master_id = '$id' AND delete_flag = 0");
$discharge_exists = ($discharge_check && $discharge_check->num_rows > 0);

// Fetch all daily treatments
$daily_treatments = [];
$daily_result = $conn->query("SELECT * FROM ipd_treatment_daily WHERE treatment_master_id = '$id' AND delete_flag = 0 ORDER BY day_number ASC");
if ($daily_result && $daily_result->num_rows > 0) {
    while ($row = $daily_result->fetch_assoc()) {
        $daily_treatments[] = $row;
    }
}

// Calculate total admission days
$total_days = 0;
if (!empty($treatment['admission_date'])) {
    $admission_date = new DateTime($treatment['admission_date']);
    $today = new DateTime();
    $interval = $admission_date->diff($today);
    $total_days = $interval->days + 1;
}

// Fetch latest vitals
$latest_vitals = [];
$vital_result = $conn->query("SELECT * FROM ipd_vitals WHERE treatment_daily_id IN 
                              (SELECT treatment_daily_id FROM ipd_treatment_daily WHERE treatment_master_id = '$id' AND delete_flag = 0) 
                              AND delete_flag = 0 ORDER BY recorded_at DESC LIMIT 1");
if ($vital_result && $vital_result->num_rows > 0) {
    $latest_vitals = $vital_result->fetch_assoc();
}

// Fetch all medicines given during admission
$all_medicines = [];
$med_result = $conn->query("SELECT DISTINCT medicine_name, dosage, frequency, number_of_days, route 
                            FROM ipd_medicine_chart 
                            WHERE treatment_daily_id IN (SELECT treatment_daily_id FROM ipd_treatment_daily WHERE treatment_master_id = '$id' AND delete_flag = 0) 
                            AND delete_flag = 0");
if ($med_result && $med_result->num_rows > 0) {
    while ($row = $med_result->fetch_assoc()) {
        $all_medicines[] = $row;
    }
}

// Fetch doctors for follow-up dropdown
$doctors = [];
$doc_result = $conn->query("SELECT doctor_id, doctor_name FROM doctor WHERE delete_flag = 0 ORDER BY doctor_name");
if ($doc_result && $doc_result->num_rows > 0) {
    while ($row = $doc_result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $primary_diagnosis = mysqli_real_escape_string($conn, $_POST['primary_diagnosis'] ?? '');
    $final_diagnosis = mysqli_real_escape_string($conn, $_POST['final_diagnosis'] ?? '');
    $treatment_given = mysqli_real_escape_string($conn, $_POST['treatment_given'] ?? '');
    $procedures_performed = mysqli_real_escape_string($conn, $_POST['procedures_performed'] ?? '');
    $patient_condition = mysqli_real_escape_string($conn, $_POST['patient_condition'] ?? 'Stable');
    $diet_advice = mysqli_real_escape_string($conn, $_POST['diet_advice'] ?? '');
    $activity_advice = mysqli_real_escape_string($conn, $_POST['activity_advice'] ?? '');
    $follow_up_date = mysqli_real_escape_string($conn, $_POST['follow_up_date'] ?? '');
    $follow_up_doctor = mysqli_real_escape_string($conn, $_POST['follow_up_doctor'] ?? '');
    $follow_up_instructions = mysqli_real_escape_string($conn, $_POST['follow_up_instructions'] ?? '');
    $final_remarks = mysqli_real_escape_string($conn, $_POST['final_remarks'] ?? '');
    
    if (empty($primary_diagnosis) || empty($final_diagnosis)) {
        $message = "Please fill at least Primary and Final Diagnosis!";
        $messageType = "error";
    } else {
        $discharge_date = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO ipd_discharge_summary (
                    ipd_id, treatment_master_id, patient_id, doctor_id,
                    primary_diagnosis, final_diagnosis, total_admission_days,
                    treatment_given, procedures_performed, patient_condition,
                    diet_advice, activity_advice, follow_up_date, follow_up_doctor,
                    follow_up_instructions, final_remarks, discharge_date, status
                ) VALUES (
                    '{$treatment['ipd_id']}', '$id', '{$treatment['patient_id']}', '{$treatment['doctor_id']}',
                    '$primary_diagnosis', '$final_diagnosis', '$total_days',
                    '$treatment_given', '$procedures_performed', '$patient_condition',
                    '$diet_advice', '$activity_advice', '$follow_up_date', '$follow_up_doctor',
                    '$follow_up_instructions', '$final_remarks', '$discharge_date', 'Completed'
                )";
        
        if ($conn->query($sql)) {
            $discharge_id = $conn->insert_id;
            
            // Insert discharge medicines
            if (isset($_POST['dis_medicine_name']) && !empty($_POST['dis_medicine_name'][0])) {
                $dis_med_names = $_POST['dis_medicine_name'];
                $dis_dosages = $_POST['dis_dosage'];
                $dis_frequencies = $_POST['dis_frequency'];
                $dis_days = $_POST['dis_days'];
                $dis_instructions = $_POST['dis_instructions'];
                
                for ($i = 0; $i < count($dis_med_names); $i++) {
                    if (!empty($dis_med_names[$i])) {
                        $med_name = mysqli_real_escape_string($conn, $dis_med_names[$i]);
                        $dosage = mysqli_real_escape_string($conn, $dis_dosages[$i] ?? '');
                        $freq = mysqli_real_escape_string($conn, $dis_frequencies[$i] ?? '');
                        $days = mysqli_real_escape_string($conn, $dis_days[$i] ?? '');
                        $instructions = mysqli_real_escape_string($conn, $dis_instructions[$i] ?? '');
                        
                        $conn->query("INSERT INTO ipd_discharge_medicines (
                            discharge_id, medicine_name, dosage, frequency, number_of_days, instructions
                        ) VALUES (
                            '$discharge_id', '$med_name', '$dosage', '$freq', '$days', '$instructions'
                        )");
                    }
                }
            }
            
            // Update IPD Admission Status
            // ================================
// Update Admission Status
// ================================
$conn->query("
    UPDATE ipd_admissions
    SET status='Discharged',
        modified_at=NOW()
    WHERE id='{$treatment['ipd_id']}'
");


// ================================
// Make Bed Available
// ================================
$roomResult = $conn->query("
    SELECT room_id
    FROM room_master
    WHERE room_no='{$treatment['room_no']}'
");

$room_id = 0;

if ($roomResult && $roomResult->num_rows > 0) {

    $room = $roomResult->fetch_assoc();
    $room_id = $room['room_id'];

    $conn->query("
        UPDATE bed_master
        SET status='Available',
            modified_at=NOW()
        WHERE room_id='$room_id'
        AND bed_no='{$treatment['bed_no']}'
    ");
}



// ================================
// Update Room Status
// ================================
if ($room_id > 0) {

    $occupiedBeds = $conn->query("
        SELECT COUNT(*) AS total
        FROM bed_master
        WHERE room_id='$room_id'
        AND status='Occupied'
        AND (delete_flag=0 OR delete_flag IS NULL)
    ");

    $occupiedBedCount = $occupiedBeds->fetch_assoc()['total'];

    if ($occupiedBedCount == 0) {

        $conn->query("
            UPDATE room_master
            SET status='Available',
                modified_at=NOW()
            WHERE room_id='$room_id'
        ");

    } else {

        $conn->query("
            UPDATE room_master
            SET status='Occupied',
                modified_at=NOW()
            WHERE room_id='$room_id'
        ");
    }
}



// ================================
// Update Ward Status
// ================================
$ward_id = $treatment['ward_id'];

$occupiedRooms = $conn->query("
    SELECT COUNT(*) AS total
    FROM room_master
    WHERE ward_id='$ward_id'
    AND status='Occupied'
    AND (delete_flag=0 OR delete_flag IS NULL)
");

$occupiedRoomCount = $occupiedRooms->fetch_assoc()['total'];

if ($occupiedRoomCount == 0) {

    $conn->query("
        UPDATE ward_master
        SET status='Available',
            modified_at=NOW()
        WHERE ward_id='$ward_id'
    ");

} else {

    $conn->query("
        UPDATE ward_master
        SET status='Occupied',
            modified_at=NOW()
        WHERE ward_id='$ward_id'
    ");
}



// ================================
// Update Treatment Status
// ================================
$conn->query("
    UPDATE ipd_treatment_master
    SET status='Discharged',
        modified_at=NOW()
    WHERE treatment_master_id='$id'
");

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Patient discharged successfully!'];
            header("Location: ipd_discharge_print.php?id=$discharge_id");
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
    <title>Discharge Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { width: 260px; height: 100vh; background: #1e293b; position: fixed; left: 0; top: 0; color: #fff; z-index: 50; overflow-y: auto; }
        .header { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; position: fixed; left: 260px; right: 0; top: 0; z-index: 40; display: flex; align-items: center; padding: 0 1.5rem; }
        .main-content { margin-left: 260px; padding: 84px 28px 20px 28px; min-height: 100vh; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; max-width: 1000px; margin: 0 auto; }
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
        .section-title { font-size: 1.1rem; font-weight: 600; color: #0f172a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #3b82f6; }
        .info-box { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 10px; }
        .info-box .label { font-size: 12px; color: #64748b; font-weight: 500; }
        .info-box .value { font-size: 14px; color: #0f172a; font-weight: 500; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
        .dis-med-row { background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } .header { left: 0; } }
        @media (max-width: 768px) { .grid-3, .grid-4 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="sidebar"><?php include 'Sidebar.php'; ?></div>
    <header class="header"><div class="flex items-center justify-between w-full"><?php include 'header.php'; ?></div></header>

    <main class="main-content">
        <div class="mb-6 flex items-center gap-4">
            <a href="ipd_treatment_view.php?id=<?php echo $id; ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Discharge Summary</h1>
                <p class="text-gray-500">Complete patient discharge process</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($discharge_exists): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <p class="text-yellow-700">Discharge already exists for this patient. <a href="ipd_discharge_print.php?id=<?php echo $discharge_check->fetch_assoc()['discharge_id']; ?>" class="text-blue-600 underline">View Discharge Summary</a></p>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="header"><h3 class="font-semibold text-gray-800"><i class="fas fa-file-medical-alt mr-2"></i>Discharge Summary Form</h3></div>
            <div class="body">
                <form method="POST" id="dischargeForm">
                    
                    <!-- 1. Hospital Details (Auto) -->
                    <div class="section-title">Hospital Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="info-box">
                            <div class="label">Hospital Name</div>
                            <div class="value"><?php echo $hospital['hospital_name'] ?? 'City Hospital'; ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Address</div>
                            <div class="value"><?php echo $hospital['hospital_address'] ?? 'Main Street, City'; ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Contact</div>
                            <div class="value"><?php echo $hospital['hospital_phone'] ?? '+91 9876543210'; ?></div>
                        </div>
                    </div>

                    <!-- 2. Patient Information (Auto) -->
                    <div class="section-title">Patient Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="info-box">
                            <div class="label">Patient ID</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['patient_code'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Patient Name</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['patient_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Age</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['age'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Gender</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['gender'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Blood Group</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['blood_group'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Mobile Number</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['mobile'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box md:col-span-2">
                            <div class="label">Address</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['address'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- 3. Admission Details (Auto) -->
                    <div class="section-title">Admission Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="info-box">
                            <div class="label">IPD Number</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['admission_no'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Admission Date</div>
                            <div class="value"><?php echo date('d-m-Y h:i A', strtotime($treatment['admission_date'] ?? date('Y-m-d'))); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Department</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['specialization'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Treating Doctor</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['doctor_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Ward</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['ward_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Room / Bed</div>
                            <div class="value">Room <?php echo htmlspecialchars($treatment['room_no'] ?? 'N/A'); ?> | Bed <?php echo htmlspecialchars($treatment['bed_no'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- 4. Diagnosis (Manual) -->
                    <div class="section-title">Diagnosis <span class="required">*</span></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label>Primary Diagnosis <span class="required">*</span></label>
                            <input type="text" name="primary_diagnosis" placeholder="Enter primary diagnosis" required>
                        </div>
                        <div class="form-group">
                            <label>Final Diagnosis <span class="required">*</span></label>
                            <input type="text" name="final_diagnosis" placeholder="Enter final diagnosis" required>
                        </div>
                    </div>

                    <!-- 5. Treatment Summary (Auto + Manual) -->
                    <div class="section-title">Treatment Summary</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="info-box">
                            <div class="label">Total Admission Days</div>
                            <div class="value"><?php echo $total_days; ?> days</div>
                        </div>
                        <div class="info-box">
                            <div class="label">Total Treatment Days</div>
                            <div class="value"><?php echo count($daily_treatments); ?> days</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Treatment Given</label>
                        <textarea name="treatment_given" rows="2" placeholder="Summary of treatment given during admission"><?php 
                            $treatment_summary = [];
                            foreach ($daily_treatments as $day) {
                                if (!empty($day['daily_doctor_notes'])) {
                                    $treatment_summary[] = "Day " . $day['day_number'] . ": " . $day['daily_doctor_notes'];
                                }
                            }
                            echo htmlspecialchars(implode("\n", $treatment_summary));
                        ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Procedures Performed</label>
                        <textarea name="procedures_performed" rows="2" placeholder="List any procedures performed during admission"></textarea>
                    </div>

                    <!-- 6. Vitals Summary (Auto) -->
                    <div class="section-title">Latest Vitals</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="info-box">
                            <div class="label">Temperature</div>
                            <div class="value"><?php echo $latest_vitals['temperature'] ?? 'N/A'; ?> °C</div>
                        </div>
                        <div class="info-box">
                            <div class="label">Pulse</div>
                            <div class="value"><?php echo $latest_vitals['pulse'] ?? 'N/A'; ?> bpm</div>
                        </div>
                        <div class="info-box">
                            <div class="label">Blood Pressure</div>
                            <div class="value"><?php echo $latest_vitals['blood_pressure'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Respiratory Rate</div>
                            <div class="value"><?php echo $latest_vitals['respiratory_rate'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">SpO₂</div>
                            <div class="value"><?php echo $latest_vitals['spo2'] ?? 'N/A'; ?> %</div>
                        </div>
                        <div class="info-box">
                            <div class="label">Weight</div>
                            <div class="value"><?php echo $latest_vitals['weight'] ?? 'N/A'; ?> kg</div>
                        </div>
                    </div>

                    <!-- 7. Medicine History (Auto) -->
                    <div class="section-title">Medicine History</div>
                    <?php if (count($all_medicines) > 0): ?>
                        <div class="overflow-x-auto mb-4">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-3 py-2 text-left">Medicine</th>
                                        <th class="px-3 py-2 text-left">Dosage</th>
                                        <th class="px-3 py-2 text-left">Frequency</th>
                                        <th class="px-3 py-2 text-left">Route</th>
                                        <th class="px-3 py-2 text-left">Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_medicines as $med): ?>
                                        <tr class="border-b border-gray-100">
                                            <td class="px-3 py-2 font-medium"><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($med['dosage'] ?? '-'); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($med['frequency'] ?? '-'); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($med['route'] ?? '-'); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($med['number_of_days'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 mb-4">No medicines recorded during admission.</p>
                    <?php endif; ?>

                    <!-- 8. Patient Condition at Discharge (Manual) -->
                    <div class="section-title">Patient Condition at Discharge</div>
                    <div class="form-group mb-4">
                        <label>Condition <span class="required">*</span></label>
                        <select name="patient_condition" required>
                            <option value="Stable">Stable</option>
                            <option value="Improved">Improved</option>
                            <option value="Recovered">Recovered</option>
                            <option value="Critical">Critical</option>
                            <option value="Referred">Referred</option>
                        </select>
                    </div>

                    <!-- 9. Discharge Medicines (Manual) -->
                    <div class="section-title flex justify-between items-center">
                        <span>Discharge Medicines</span>
                        <button type="button" onclick="addDischargeMedicine()" class="btn-success text-sm py-1 px-3">
                            <i class="fas fa-plus"></i> Add Medicine
                        </button>
                    </div>
                    <div id="dischargeMedicinesContainer">
                        <div class="dis-med-row">
                            <div class="grid-4">
                                <div class="form-group">
                                    <label>Medicine Name</label>
                                    <input type="text" name="dis_medicine_name[]" placeholder="Enter medicine name">
                                </div>
                                <div class="form-group">
                                    <label>Dosage</label>
                                    <input type="text" name="dis_dosage[]" placeholder="e.g., 500mg">
                                </div>
                                <div class="form-group">
                                    <label>Frequency</label>
                                    <input type="text" name="dis_frequency[]" placeholder="e.g., BD, TDS">
                                </div>
                                <div class="form-group">
                                    <label>Days</label>
                                    <input type="number" name="dis_days[]" placeholder="Number of days">
                                </div>
                                <div class="form-group md:col-span-4">
                                    <label>Instructions</label>
                                    <input type="text" name="dis_instructions[]" placeholder="Special instructions">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 10. Diet Advice (Manual) -->
                    <div class="section-title">Diet Advice</div>
                    <div class="form-group mb-4">
                        <label>Diet Advice</label>
                        <select name="diet_advice">
                            <option value="">Select Diet Advice</option>
                            <option value="Normal Diet">Normal Diet</option>
                            <option value="Soft Diet">Soft Diet</option>
                            <option value="High Protein Diet">High Protein Diet</option>
                            <option value="Low Salt Diet">Low Salt Diet</option>
                            <option value="Diabetic Diet">Diabetic Diet</option>
                            <option value="Liquid Diet">Liquid Diet</option>
                            <option value="Cardiac Diet">Cardiac Diet</option>
                            <option value="Renal Diet">Renal Diet</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- 11. Activity Advice (Manual) -->
                    <div class="section-title">Activity Advice</div>
                    <div class="form-group mb-4">
                        <label>Activity Advice</label>
                        <select name="activity_advice">
                            <option value="">Select Activity Advice</option>
                            <option value="Complete Bed Rest">Complete Bed Rest</option>
                            <option value="Walking Allowed">Walking Allowed</option>
                            <option value="Avoid Heavy Work">Avoid Heavy Work</option>
                            <option value="Normal Activity">Normal Activity</option>
                            <option value="Physiotherapy Recommended">Physiotherapy Recommended</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- 12. Follow-up (Manual) -->
                    <div class="section-title">Follow-up</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="form-group">
                            <label>Follow-up Date</label>
                            <input type="date" name="follow_up_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Follow-up Doctor</label>
                            <select name="follow_up_doctor">
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doc): ?>
                                    <option value="<?php echo $doc['doctor_id']; ?>"><?php echo htmlspecialchars($doc['doctor_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Follow-up Instructions</label>
                            <textarea name="follow_up_instructions" rows="2" placeholder="Enter follow-up instructions"></textarea>
                        </div>
                    </div>

                    <!-- 13. Final Remarks (Manual) -->
                    <div class="section-title">Final Remarks</div>
                    <div class="form-group mb-4">
                        <label>Doctor's Remarks</label>
                        <textarea name="final_remarks" rows="3" placeholder="Enter final discharge remarks"></textarea>
                    </div>

                    <!-- 14. Doctor Details (Auto) -->
                    <div class="section-title">Doctor Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="info-box">
                            <div class="label">Doctor Name</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['doctor_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Registration Number</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['registration_number'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="label">Specialization</div>
                            <div class="value"><?php echo htmlspecialchars($treatment['specialization'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- 15. Buttons -->
                    <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i> Save Discharge
                        </button>
                        <a href="ipd_treatment_view.php?id=<?php echo $id; ?>" class="btn-secondary">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function addDischargeMedicine() {
            const container = document.getElementById('dischargeMedicinesContainer');
            const row = document.createElement('div');
            row.className = 'dis-med-row';
            row.innerHTML = `
                <div class="grid-4">
                    <div class="form-group">
                        <label>Medicine Name</label>
                        <input type="text" name="dis_medicine_name[]" placeholder="Enter medicine name">
                    </div>
                    <div class="form-group">
                        <label>Dosage</label>
                        <input type="text" name="dis_dosage[]" placeholder="e.g., 500mg">
                    </div>
                    <div class="form-group">
                        <label>Frequency</label>
                        <input type="text" name="dis_frequency[]" placeholder="e.g., BD, TDS">
                    </div>
                    <div class="form-group">
                        <label>Days</label>
                        <input type="number" name="dis_days[]" placeholder="Number of days">
                    </div>
                    <div class="form-group md:col-span-3">
                        <label>Instructions</label>
                        <input type="text" name="dis_instructions[]" placeholder="Special instructions">
                    </div>
                    <div class="form-group flex items-end">
                        <button type="button" onclick="removeDischargeRow(this)" class="btn-danger text-sm py-1 px-3">Remove</button>
                    </div>
                </div>
            `;
            container.appendChild(row);
        }

        function removeDischargeRow(button) {
            const row = button.closest('.dis-med-row');
            if (document.querySelectorAll('.dis-med-row').length > 1) {
                row.remove();
            } else {
                alert('At least one medicine row is required!');
            }
        }
    </script>
</body>
</html>