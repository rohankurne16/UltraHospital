<?php
session_start();
include 'config/hospital.php';

include 'config/permission.php';
    checkPermission('appointment-edit'); 

$conn->set_charset("utf8");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'OPD';
$redirect_page = isset($_GET['redirect']) ? $_GET['redirect'] : 'show_opd_appointments.php';

// If redirect is not set properly, determine based on type
if (empty($_GET['redirect'])) {
    if ($type == 'IPD') {
        $redirect_page = 'show_ipd_appointments.php';
    } else {
        $redirect_page = 'show_opd_appointments.php';
    }
}

if ($id == 0) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid appointment ID!'
    ];
    header("Location: $redirect_page");
    exit();
}

// Fetch appointment details
$sql = "SELECT a.*, p.patient_name, p.mobile, p.email, p.address, p.age, p.gender, p.blood_group,
        d.doctor_name, d.department as doctor_dept, d.specialization, d.qualification, d.experience, d.consultation_fee
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN doctor d ON a.doctor_id = d.doctor_id 
        WHERE a.appointment_id = '$id' AND (a.delete_flag = 0 OR a.delete_flag IS NULL)";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Appointment not found!'
    ];
    header("Location: $redirect_page");
    exit();
}

$appointment = mysqli_fetch_assoc($result);

// Fetch IPD details if exists
$ipd_details = null;
if ($appointment['opd_ipd_type'] == 'IPD') {
    $ipd_sql = "SELECT * FROM ipd_admissions WHERE appointment_id = '$id' AND delete_flag = 0";
    $ipd_result = mysqli_query($conn, $ipd_sql);
    if ($ipd_result && mysqli_num_rows($ipd_result) > 0) {
        $ipd_details = mysqli_fetch_assoc($ipd_result);
    }
}

// Fetch doctors for dropdown
$doctorQuery = "SELECT doctor_id, doctor_name, department FROM doctor ORDER BY doctor_name ASC";
$doctorResult = $conn->query($doctorQuery);
$doctors = array();
if ($doctorResult && $doctorResult->num_rows > 0) {
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Get unique departments
$departments = array();
foreach ($doctors as $doctor) {
    if (!in_array($doctor['department'], $departments)) {
        $departments[] = $doctor['department'];
    }
}
sort($departments);

// Fetch wards
$wards = array();
$wardQuery = "SELECT ward_id, ward_name FROM ward_master WHERE status='Active' AND (delete_flag=0 OR delete_flag IS NULL)";
$wardResult = $conn->query($wardQuery);
if ($wardResult && $wardResult->num_rows > 0) {
    while ($row = $wardResult->fetch_assoc()) {
        $wards[] = $row;
    }
}

// Fetch rooms based on ward
$rooms = array();
if (!empty($ipd_details['ward_id'])) {
    $roomQuery = "SELECT room_id, room_no FROM room_master WHERE ward_id = '{$ipd_details['ward_id']}' AND status != 'Occupied' AND (delete_flag=0 OR delete_flag IS NULL)";
    $roomResult = $conn->query($roomQuery);
    if ($roomResult && $roomResult->num_rows > 0) {
        while ($row = $roomResult->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date'] ?? '');
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time'] ?? '');
    $appointment_type = mysqli_real_escape_string($conn, $_POST['appointment_type'] ?? '');
    $department = mysqli_real_escape_string($conn, $_POST['department'] ?? '');
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id'] ?? '');
    $duration = mysqli_real_escape_string($conn, $_POST['duration'] ?? '');
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
    $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
    $opd_ipd_type = mysqli_real_escape_string($conn, $_POST['opd_ipd_type'] ?? '');
    
    // IPD fields
    $admission_date = mysqli_real_escape_string($conn, $_POST['admission_date'] ?? '');
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis'] ?? '');
    $treatment_plan = mysqli_real_escape_string($conn, $_POST['treatment_plan'] ?? '');
    $ward_id = mysqli_real_escape_string($conn, $_POST['ward_id'] ?? '');
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id'] ?? '');
    $bed_id = mysqli_real_escape_string($conn, $_POST['bed_id'] ?? '');
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms'] ?? '');
    $severity = mysqli_real_escape_string($conn, $_POST['severity'] ?? '');
    $previous_history = mysqli_real_escape_string($conn, $_POST['previous_history'] ?? '');
    $current_medicines = mysqli_real_escape_string($conn, $_POST['current_medicines'] ?? '');
    
    if (empty($appointment_date) || empty($appointment_time) || empty($doctor_id)) {
        $_SESSION['toast'] = [
            'type' => 'warning',
            'message' => 'Please fill all required fields!'
        ];
        header("Location: edit_appointment.php?id=$id&type=$opd_ipd_type&redirect=" . urlencode($redirect_page));
        exit();
    } else {
        // Update appointments table
        $updateSql = "UPDATE appointments SET 
                        appointment_date = '$appointment_date',
                        appointment_time = '$appointment_time',
                        appointment_type = '$appointment_type',
                        department = '$department',
                        doctor_id = '$doctor_id',
                        duration = '$duration',
                        reason = '$reason',
                        notes = '$note',
                        status = '$status',
                        opd_ipd_type = '$opd_ipd_type'
                      WHERE appointment_id = '$id'";
        
        if (mysqli_query($conn, $updateSql)) {
            // If IPD, update ipd_admissions
            if ($opd_ipd_type == 'IPD') {
                // Check if IPD record exists
                $check_ipd = mysqli_query($conn, "SELECT * FROM ipd_admissions WHERE appointment_id = '$id'");
                if (mysqli_num_rows($check_ipd) > 0) {
                    // Update existing IPD record
                    $ipdUpdateSql = "UPDATE ipd_admissions SET 
                                        admission_date = '$admission_date',
                                        diagnosis = '$diagnosis',
                                        treatment_plan = '$treatment_plan',
                                        ward_id = '$ward_id',
                                        room_id = '$room_id',
                                        bed_id = '$bed_id',
                                        symptoms = '$symptoms',
                                        severity = '$severity',
                                        previous_history = '$previous_history',
                                        current_medicines = '$current_medicines'
                                      WHERE appointment_id = '$id'";
                    mysqli_query($conn, $ipdUpdateSql);
                } else {
                    // Insert new IPD record
                    $admission_no = "IPD-" . date('Ymd') . "-" . rand(1000, 9999);
                    $ipdInsertSql = "INSERT INTO ipd_admissions (
                                        admission_no, appointment_id, patient_id, doctor_id, department,
                                        ward_id, room_id, bed_id, admission_date, appointment_time,
                                        disease_reason, notes, symptoms, severity, previous_history,
                                        current_medicines, status, delete_flag
                                    ) VALUES (
                                        '$admission_no', '$id', '{$appointment['patient_id']}', '$doctor_id', '$department',
                                        '$ward_id', '$room_id', '$bed_id', '$admission_date', '$appointment_time',
                                        '$reason', '$note', '$symptoms', '$severity', '$previous_history',
                                        '$current_medicines', 'Admitted', '0'
                                    )";
                    mysqli_query($conn, $ipdInsertSql);
                }
            }
            
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Appointment updated successfully!'
            ];
            
            // Redirect based on type
            if ($opd_ipd_type == 'IPD') {
                header("Location: show_ipd_appointments.php");
            } else {
                header("Location: show_opd_appointments.php");
            }
            exit();
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Error: ' . mysqli_error($conn)
            ];
            header("Location: edit_appointment.php?id=$id&type=$opd_ipd_type&redirect=" . urlencode($redirect_page));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $hospital['hospital_name'] ?> -Edit Appointment</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 84px 28px 20px 28px; min-height: 100vh; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; max-width: 950px; margin: 0 auto; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .form-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
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
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .ipd-section { display: none; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) { .form-card .body { padding: 16px; } }
        
        /* Bed status colors in dropdown */
        /* Bed status colors in dropdown */
.bed-available { color: #22c55e; font-weight: 500; }
.bed-occupied { color: #ef4444; font-weight: 500; }
.bed-maintenance { color: #f59e0b; font-weight: 500; }
option:disabled { opacity: 0.6; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content w-full">
                <div class="max-w-5xl mx-auto w-full">
                    
                    <!-- Back Button -->
                    <div class="mb-6 flex items-center gap-4">
                        <a href="<?php echo htmlspecialchars($redirect_page); ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit Appointment</h1>
                            <p class="text-gray-500">Update appointment details</p>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="header">
                            <h3>Appointment #<?php echo htmlspecialchars($appointment['appointment_no']); ?></h3>
                        </div>
                        <div class="body">
                            <form method="POST" id="editForm">
                                <!-- Basic Appointment Details -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="form-group">
                                        <label>Patient Name</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['patient_name']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Appointment No</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['appointment_no']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Patient Mobile</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['mobile'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Patient Email</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['email'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Patient Age</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['age'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Patient Gender</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['gender'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Blood Group</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['blood_group'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Patient Address</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['address'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="appointment_date">Date <span class="required">*</span></label>
                                        <input type="date" id="appointment_date" name="appointment_date" required
                                               value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="appointment_time">Time <span class="required">*</span></label>
                                        <input type="time" id="appointment_time" name="appointment_time" required
                                               value="<?php echo htmlspecialchars($appointment['appointment_time']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="appointment_type">Appointment Type</label>
                                        <select id="appointment_type" name="appointment_type">
                                            <option value="Consultation" <?php echo ($appointment['appointment_type'] == 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                                            <option value="Follow-up" <?php echo ($appointment['appointment_type'] == 'Follow-up') ? 'selected' : ''; ?>>Follow-up</option>
                                            <option value="Emergency" <?php echo ($appointment['appointment_type'] == 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
                                            <option value="Check-up" <?php echo ($appointment['appointment_type'] == 'Check-up') ? 'selected' : ''; ?>>Check-up</option>
                                            <option value="Procedure" <?php echo ($appointment['appointment_type'] == 'Procedure') ? 'selected' : ''; ?>>Procedure</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="opd_ipd_type">OPD/IPD Type</label>
                                        <select id="opd_ipd_type" name="opd_ipd_type" onchange="toggleIPDFields()">
                                            <option value="OPD" <?php echo ($appointment['opd_ipd_type'] == 'OPD') ? 'selected' : ''; ?>>OPD</option>
                                            <option value="IPD" <?php echo ($appointment['opd_ipd_type'] == 'IPD') ? 'selected' : ''; ?>>IPD</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select id="department" name="department">
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($appointment['department'] == $dept) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="doctor_id">Doctor <span class="required">*</span></label>
                                        <select id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            <?php foreach ($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo ($appointment['doctor_id'] == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?> - <?php echo htmlspecialchars($doctor['department']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="duration">Duration (minutes)</label>
                                        <select id="duration" name="duration">
                                            <option value="15" <?php echo ($appointment['duration'] == '15') ? 'selected' : ''; ?>>15 min</option>
                                            <option value="30" <?php echo ($appointment['duration'] == '30') ? 'selected' : ''; ?>>30 min</option>
                                            <option value="45" <?php echo ($appointment['duration'] == '45') ? 'selected' : ''; ?>>45 min</option>
                                            <option value="60" <?php echo ($appointment['duration'] == '60') ? 'selected' : ''; ?>>60 min</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status">
                                            <option value="Scheduled" <?php echo ($appointment['status'] == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="Confirmed" <?php echo ($appointment['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="In Progress" <?php echo ($appointment['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Completed" <?php echo ($appointment['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo ($appointment['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="form-group md:col-span-3">
                                        <label for="reason">Reason for Visit</label>
                                        <textarea id="reason" name="reason" rows="2"><?php echo htmlspecialchars($appointment['reason'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-group md:col-span-3">
                                        <label for="note">Notes</label>
                                        <textarea id="note" name="note" rows="2"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- IPD Section -->
                                <div id="ipdSection" class="ipd-section mt-6 pt-4 border-t border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">IPD Admission Details</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="form-group">
                                            <label for="admission_date">Admission Date</label>
                                            <input type="date" id="admission_date" name="admission_date"
                                                   value="<?php echo htmlspecialchars($ipd_details['admission_date'] ?? date('Y-m-d')); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="diagnosis">Diagnosis</label>
                                            <input type="text" id="diagnosis" name="diagnosis" placeholder="Enter diagnosis"
                                                   value="<?php echo htmlspecialchars($ipd_details['diagnosis'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="severity">Severity</label>
                                            <select id="severity" name="severity">
                                                <option value="">Select Severity</option>
                                                <option value="Mild" <?php echo ($ipd_details['severity'] ?? '') == 'Mild' ? 'selected' : ''; ?>>Mild</option>
                                                <option value="Moderate" <?php echo ($ipd_details['severity'] ?? '') == 'Moderate' ? 'selected' : ''; ?>>Moderate</option>
                                                <option value="Severe" <?php echo ($ipd_details['severity'] ?? '') == 'Severe' ? 'selected' : ''; ?>>Severe</option>
                                            </select>
                                        </div>
                                        <div class="form-group md:col-span-3">
                                            <label for="treatment_plan">Treatment Plan</label>
                                            <textarea id="treatment_plan" name="treatment_plan" rows="2"><?php echo htmlspecialchars($ipd_details['treatment_plan'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="ward_id">Ward <span class="required">*</span></label>
                                            <select id="ward_id" name="ward_id" onchange="loadRooms(this.value)">
                                                <option value="">Select Ward</option>
                                                <?php foreach ($wards as $ward): ?>
                                                    <option value="<?php echo $ward['ward_id']; ?>" <?php echo ($ipd_details['ward_id'] ?? '') == $ward['ward_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($ward['ward_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="room_id">Room <span class="required">*</span></label>
                                            <select id="room_id" name="room_id" onchange="loadBeds(this.value)">
                                                <option value="">Select Room</option>
                                                <?php foreach ($rooms as $room): ?>
                                                    <option value="<?php echo $room['room_id']; ?>" <?php echo ($ipd_details['room_id'] ?? '') == $room['room_id'] ? 'selected' : ''; ?>>
                                                        Room <?php echo htmlspecialchars($room['room_no']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="bed_id">Bed <span class="required">*</span></label>
                                            <select id="bed_id" name="bed_id">
                                                <option value="">Select Bed</option>
                                                <!-- Beds will be loaded dynamically with status -->
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="symptoms">Symptoms</label>
                                            <input type="text" id="symptoms" name="symptoms" placeholder="Enter symptoms"
                                                   value="<?php echo htmlspecialchars($ipd_details['symptoms'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group md:col-span-3">
                                            <label for="previous_history">Previous History</label>
                                            <textarea id="previous_history" name="previous_history" rows="2"><?php echo htmlspecialchars($ipd_details['previous_history'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-group md:col-span-3">
                                            <label for="current_medicines">Current Medicines</label>
                                            <textarea id="current_medicines" name="current_medicines" rows="2"><?php echo htmlspecialchars($ipd_details['current_medicines'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                    <button type="submit" class="btn-primary" id="updateBtn">
                                        <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                                        Update Appointment
                                    </button>
                                    <a href="<?php echo htmlspecialchars($redirect_page); ?>" class="btn-secondary">
                                        <i data-lucide="x" class="w-4 h-4 inline mr-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleIPDFields() {
            const type = document.getElementById('opd_ipd_type').value;
            const ipdSection = document.getElementById('ipdSection');
            if (type === 'IPD') {
                ipdSection.style.display = 'block';
            } else {
                ipdSection.style.display = 'none';
            }
        }

        // Load rooms based on ward
        function loadRooms(wardId) {
            if (!wardId) {
                document.getElementById('room_id').innerHTML = '<option value="">Select Room</option>';
                document.getElementById('bed_id').innerHTML = '<option value="">Select Bed</option>';
                return;
            }
            
            // Show loading
            document.getElementById('room_id').innerHTML = '<option value="">Loading rooms...</option>';
            
            fetch(`get_rooms.php?ward_id=${wardId}`)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">Select Room</option>';
                    data.forEach(room => {
                        options += `<option value="${room.room_id}">Room ${room.room_no}</option>`;
                    });
                    document.getElementById('room_id').innerHTML = options;
                    document.getElementById('bed_id').innerHTML = '<option value="">Select Bed</option>';
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                    document.getElementById('room_id').innerHTML = '<option value="">Error loading rooms</option>';
                });
        }

        // Load beds based on room with status
      // Load beds based on room with status
// Load beds based on room with status
function loadBeds(roomId) {
    if (!roomId) {
        document.getElementById('bed_id').innerHTML = '<option value="">Select Bed</option>';
        return;
    }
    
    // Show loading
    document.getElementById('bed_id').innerHTML = '<option value="">Loading beds...</option>';
    
    fetch(`get_beds.php?room_id=${roomId}`)
        .then(response => response.json())
        .then(data => {
            let options = '<option value="">Select Bed</option>';
            let hasAvailable = false;
            
            if (data.length === 0) {
                options = '<option value="">No beds found</option>';
            } else {
                data.forEach(bed => {
                    let statusClass = '';
                    let statusText = '';
                    let disabled = '';
                    
                    // Get status and convert to lowercase for comparison
                    let status = (bed.status || '').toLowerCase();
                    
                    if (status == 'available') {
                        statusClass = 'bed-available';
                        statusText = '✅ Available';
                        hasAvailable = true;
                        disabled = '';
                    } else if (status == 'occupied') {
                        statusClass = 'bed-occupied';
                        statusText = '🔴 Occupied';
                        disabled = 'disabled';
                    } else if (status == 'maintenance') {
                        statusClass = 'bed-maintenance';
                        statusText = '🛠️ Maintenance';
                        disabled = 'disabled';
                    } else {
                        // Default to Available
                        statusClass = 'bed-available';
                        statusText = '✅ Available';
                        hasAvailable = true;
                        disabled = '';
                    }
                    
                    options += `<option value="${bed.bed_id}" class="${statusClass}" ${disabled}>Bed ${bed.bed_no} - ${statusText}</option>`;
                });
                
                // If no available beds, show message
                if (!hasAvailable && data.length > 0) {
                    options += `<option value="" disabled>--- No available beds ---</option>`;
                }
            }
            
            document.getElementById('bed_id').innerHTML = options;
        })
        .catch(error => {
            console.error('Error loading beds:', error);
            document.getElementById('bed_id').innerHTML = '<option value="">Error loading beds</option>';
        });
}
        // Show IPD fields on page load if type is IPD
        document.addEventListener('DOMContentLoaded', function() {
            toggleIPDFields();
            
            // If room is already selected, load beds
            const roomId = document.getElementById('room_id').value;
            if (roomId) {
                loadBeds(roomId);
            }
        });

        // Toast function
        function showToast(type, message) {
            let colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            let textColor = type === 'warning' ? '#000' : '#fff';
            
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                close: true,
                stopOnFocus: true,
                style: {
                    background: colors[type] || '#28a745',
                    color: textColor
                }
            }).showToast();
        }

        <?php if(isset($_SESSION['toast'])): ?>
            (function() {
                let type = "<?= $_SESSION['toast']['type']; ?>";
                let message = "<?= $_SESSION['toast']['message']; ?>";
                let colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    warning: '#ffc107',
                    info: '#17a2b8'
                };
                let textColor = type === 'warning' ? '#000' : '#fff';
                
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    close: true,
                    stopOnFocus: true,
                    style: {
                        background: colors[type] || '#28a745',
                        color: textColor
                    }
                }).showToast();
            })();
        <?php unset($_SESSION['toast']); endif; ?>

        // Form validation
        document.getElementById('editForm').addEventListener('submit', function(e) {
            var date = document.getElementById('appointment_date').value;
            var time = document.getElementById('appointment_time').value;
            var doctor = document.getElementById('doctor_id').value;

            if (!date || !time || !doctor) {
                e.preventDefault();
                showToast('warning', 'Please fill all required fields!');
                return false;
            }
        });
    </script>

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</body>
</html>