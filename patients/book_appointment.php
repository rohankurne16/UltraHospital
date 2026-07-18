<?php
session_start();
include '../config/hospital.php';

$conn->set_charset("utf8");

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        $patient_id = $_SESSION['patient_id'] ?? "";
        $doctor_id = $_POST['doctor_id'] ?? "";
        $department = $_POST['department'] ?? "";
        $appointment_type = $_POST['appointment_type'] ?? "";
        $appointment_date = $_POST['appointment_date'] ?? "";
        $appointment_time = $_POST['appointment_time'] ?? "";
        $duration = $_POST['duration'] ?? "";
        $reason = $_POST['reason'] ?? "";
        $note = $_POST['note'] ?? "";
        $opd_ipd_type = $_POST['opd_ipd_type'] ?? "";

        $previous_history_array = $_POST['previous_history'] ?? [];
        $previous_history = implode(', ', array_map('htmlspecialchars', $previous_history_array));
        $symptoms = $_POST['symptoms'] ?? "";
        $since_when = $_POST['since_when'] ?? "";
        $severity = $_POST['severity'] ?? "";
        $allergies = $_POST['allergies'] ?? "";
        $current_medicines = $_POST['current_medicines'] ?? "";

        if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($opd_ipd_type)) {
            throw new Exception("Please fill in all required fields!");
        }

        $appointment_no = 'APP-' . date('YmdHis') . '-' . uniqid();

        // Insert into appointments table
        $sql_appointment = "INSERT INTO appointments(
            appointment_no, patient_id, doctor_id, department, appointment_type, appointment_date, 
            appointment_time, duration, reason, notes, opd_ipd_type
        ) VALUES(
            '$appointment_no', '$patient_id', '$doctor_id', '$department', '$appointment_type', '$appointment_date',
            '$appointment_time', '$duration', '$reason', '$note', '$opd_ipd_type'
        )";
        
        if (!mysqli_query($conn, $sql_appointment)) {
            throw new Exception("Appointment Insert Error: " . mysqli_error($conn));
        }
        $appointment_id = mysqli_insert_id($conn);

        // Handle file uploads
        $upload_dir = "../uploads/documents/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $document_fields = ['prescription', 'lab_report', 'xray', 'mri', 'ct_scan', 'other_document'];
        $uploaded_files = [];

        foreach ($document_fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                $file_tmp_name = $_FILES[$field]['tmp_name'];
                $file_original_name = $_FILES[$field]['name'];
                $file_extension = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Invalid file type for {$field}. Only PDF, JPG, JPEG, PNG are allowed.");
                }

                $new_file_name = uniqid('doc_', true) . '.' . $file_extension;
                $file_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $file_path)) {
                    $uploaded_files[$field . '_file'] = $file_path;
                } else {
                    throw new Exception("Failed to upload file for {$field}.");
                }
            }
        }

        $prescription_file = $uploaded_files['prescription_file'] ?? "NULL";
        $lab_report_file = $uploaded_files['lab_report_file'] ?? "NULL";
        $xray_file = $uploaded_files['xray_file'] ?? "NULL";
        $mri_file = $uploaded_files['mri_file'] ?? "NULL";
        $ctscan_file = $uploaded_files['ctscan_file'] ?? "NULL";
        $other_document = $uploaded_files['other_document_file'] ?? "NULL";

        // Insert into OPD or IPD table
        if ($opd_ipd_type === 'OPD') {
            $sql_opd = "INSERT INTO opd(
                appointment_id, patient_id, doctor_id, appointment_no, department, appointment_type,
                appointment_date, appointment_time, duration, reason, notes, symptoms, since_when,
                severity, previous_history, allergies, current_medicines, prescription_file,
                lab_report_file, xray_file, mri_file, ctscan_file, other_document, visit_date
            ) VALUES(
                '$appointment_id', '$patient_id', '$doctor_id', '$appointment_no', '$department', '$appointment_type',
                '$appointment_date', '$appointment_time', '$duration', '$reason', '$note', '$symptoms', '$since_when',
                '$severity', '$previous_history', '$allergies', '$current_medicines', '$prescription_file',
                '$lab_report_file', '$xray_file', '$mri_file', '$ctscan_file', '$other_document', '$appointment_date'
            )";
            
            if (!mysqli_query($conn, $sql_opd)) {
                throw new Exception("OPD Insert Error: " . mysqli_error($conn));
            }

        } elseif ($opd_ipd_type === 'IPD') {
          $sql_ipd = "INSERT INTO ipd_admissions (
    admission_no,
    appointment_id,
    appointment_type,
    patient_id,
    doctor_id,
    admission_date,
    disease_reason,
    symptoms,
    since_when,
    severity,
    previous_history,
    current_medicines,
    prescription_file,
    lab_report_file,
    xray_file,
    mri_file,
    ctscan_file,
    other_document
) VALUES (
    '$appointment_no',
    '$appointment_id',
    '$appointment_type',
    '$patient_id',
    '$doctor_id',
    '$appointment_date',
    '$reason',
    '$symptoms',
    '$since_when',
    '$severity',
    '$previous_history',
    '$current_medicines',
    '$prescription_file',
    '$lab_report_file',
    '$xray_file',
    '$mri_file',
    '$ctscan_file',
    '$other_document'
)";

if (!mysqli_query($conn, $sql_ipd)) {
    die("IPD Error : " . mysqli_error($conn));
}
            
            if (!mysqli_query($conn, $sql_ipd)) {
                throw new Exception("IPD Insert Error: " . mysqli_error($conn));
            }
        }

        $conn->commit();
        $_SESSION['message'] = "Appointment scheduled successfully!";
        $_SESSION['messageType'] = "success";
        header("Location: show_patient_appointments.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Fetch all doctors from database
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

// Get patient details
$patient_id = $_SESSION['patient_id'] ?? '';
$patient_name = '';
$patient_age = '';
$patient_gender = '';
$patient_mobile = '';
$patient_email = '';
$patient_blood_group = '';

if (!empty($patient_id)) {
    $patientQuery = "SELECT patient_name, age, gender, mobile, email, blood_group FROM patients WHERE patient_id = '$patient_id'";
    $patientResult = $conn->query($patientQuery);
    if ($patientResult && $patientResult->num_rows > 0) {
        $patientRow = $patientResult->fetch_assoc();
        $patient_name = $patientRow['patient_name'];
        $patient_age = $patientRow['age'];
        $patient_gender = $patientRow['gender'];
        $patient_mobile = $patientRow['mobile'];
        $patient_email = $patientRow['email'];
        $patient_blood_group = $patientRow['blood_group'] ?? '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $hospital['hospital_name'] ?> - Add Appointment</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    
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
        .form-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-card .header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }
        .form-card .header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .form-card .body {
            padding: 24px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .form-group label .required {
            color: #ef4444;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            outline: none;
            background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group input:disabled, .form-group input:read-only {
            background: #f1f5f9;
            cursor: not-allowed;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-group input[type="file"] {
            padding: 6px;
            background: #f8fafc;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-primary {
            padding: 10px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .btn-secondary {
            padding: 10px 24px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-right: 40px;
        }
        .search-box .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .search-results {
            position: absolute;
            z-index: 50;
            width: 100%;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            max-height: 250px;
            overflow-y: auto;
            display: none;
            margin-top: 4px;
        }
        .search-results .result-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .search-results .result-item:hover {
            background: #f1f5f9;
        }
        .search-results .result-item:last-child {
            border-bottom: none;
        }
        .search-results .result-item .name {
            font-weight: 500;
            font-size: 14px;
            color: #0f172a;
        }
        .search-results .result-item .info {
            font-size: 12px;
            color: #64748b;
        }
        .selected-item {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 10px 14px;
            display: none;
            margin-top: 8px;
        }
        .selected-item .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .selected-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }
        .selected-item .sub {
            font-size: 12px;
            color: #64748b;
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
        @media (max-width: 768px) {
            .form-card .body { padding: 16px; }
        }
        .doctor-select-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 16px;
        }
        @media (max-width: 640px) {
            .doctor-select-grid {
                grid-template-columns: 1fr;
            }
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
        }
        .checkbox-grid label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 400;
            font-size: 14px;
            cursor: pointer;
        }
        .checkbox-grid input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }
        .section-subtitle {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }
        .grid-cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        @media (max-width: 768px) {
            .grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .grid-cols-4 {
                grid-template-columns: 1fr;
            }
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
                            <h1 class="text-2xl font-bold text-gray-900">Add New Appointment</h1>
                            <p class="text-gray-500">Schedule a new appointment with complete medical details.</p>
                        </div>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?> fade-in">
                            <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-card fade-in">
                        <div class="header">
                            <h3>Complete Appointment Form</h3>
                        </div>
                        <div class="body">
                            <form action="book_appointment.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">

                                <!-- 1. Appointment Details -->
                                <div class="mb-6">
                                    <div class="section-title">1. Appointment Details</div>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="form-group">
                                            <label for="appointment_date">Appointment Date <span class="required">*</span></label>
                                            <input type="date" id="appointment_date" name="appointment_date" required
                                                value="<?php echo isset($_POST['appointment_date']) ? htmlspecialchars($_POST['appointment_date']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="appointment_time">Appointment Time <span class="required">*</span></label>
                                            <input type="time" id="appointment_time" name="appointment_time" required
                                                value="<?php echo isset($_POST['appointment_time']) ? htmlspecialchars($_POST['appointment_time']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="appointment_type">Appointment Type <span class="required">*</span></label>
                                            <select id="appointment_type" name="appointment_type" required>
                                                <option value="">Select Type</option>
                                                <option value="New Consultation" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'New Consultation') ? 'selected' : ''; ?>>New Consultation</option>
                                                <option value="Follow-up" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'Follow-up') ? 'selected' : ''; ?>>Follow-up</option>
                                                <option value="Emergency" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="opd_ipd_type">OPD/IPD Type <span class="required">*</span></label>
                                            <select id="opd_ipd_type" name="opd_ipd_type" required>
                                                <option value="">Select Type</option>
                                                <option value="OPD" <?php echo (isset($_POST['opd_ipd_type']) && $_POST['opd_ipd_type'] === 'OPD') ? 'selected' : ''; ?>>OPD</option>
                                                <option value="IPD" <?php echo (isset($_POST['opd_ipd_type']) && $_POST['opd_ipd_type'] === 'IPD') ? 'selected' : ''; ?>>IPD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                        <div class="form-group">
                                            <label for="duration">Duration</label>
                                            <select id="duration" name="duration">
                                                <option value="">Select Duration</option>
                                                <option value="15 min" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '15 min') ? 'selected' : ''; ?>>15 min</option>
                                                <option value="30 min" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '30 min') ? 'selected' : ''; ?>>30 min</option>
                                                <option value="45 min" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '45 min') ? 'selected' : ''; ?>>45 min</option>
                                                <option value="1 hour" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 hour') ? 'selected' : ''; ?>>1 hour</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Doctor Selection -->
                                <div class="mb-6">
                                    <div class="section-title">2. Doctor Selection</div>
                                    <div class="doctor-select-grid">
                                        <div class="form-group">
                                            <label for="department">Department <span class="required">*</span></label>
                                            <select id="department" name="department" required onchange="filterDoctorsByDepartment(); searchDoctors(document.getElementById('doctorSearch').value);">
                                                <option value="">Select Department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="doctorSearch">Select Doctor <span class="required">*</span></label>
                                            <div class="search-box">
                                                <input type="text" id="doctorSearch" placeholder="Type doctor name..." 
                                                    onkeyup="searchDoctors(this.value)" autocomplete="off">
                                                <span class="search-icon">
                                                    <i data-lucide="search" class="w-4 h-4"></i>
                                                </span>
                                                <div id="doctorResults" class="search-results"></div>
                                            </div>
                                            <input type="hidden" id="doctor_id" name="doctor_id" value="<?php echo isset($_POST['doctor_id']) ? htmlspecialchars($_POST['doctor_id']) : ''; ?>">
                                            <input type="hidden" id="doctor_name" name="doctor_name" value="<?php echo isset($_POST['doctor_name']) ? htmlspecialchars($_POST['doctor_name']) : ''; ?>">
                                            <div id="selectedDoctor" class="selected-item" style="<?php echo (isset($_POST['doctor_id']) && !empty($_POST['doctor_id'])) ? 'display: block;' : 'display: none;'; ?>">
                                                <div class="label">Selected Doctor</div>
                                                <div class="value" id="selectedDoctorName"><?php echo isset($_POST['doctor_name']) ? htmlspecialchars($_POST['doctor_name']) : ''; ?></div>
                                                <div class="sub" id="selectedDoctorDept"><?php echo (isset($_POST['department']) && !empty($_POST['department'])) ? 'Department: ' . htmlspecialchars($_POST['department']) : ''; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Patient Details -->
                                <div class="mb-6">
                                    <div class="section-title">3. Patient Details</div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="form-group">
                                            <label for="patient_name">Patient Name</label>
                                            <input type="text" id="patient_name" name="patient_name" 
                                                value="<?php echo htmlspecialchars($patient_name); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_age">Age</label>
                                            <input type="text" id="patient_age" name="patient_age" 
                                                value="<?php echo htmlspecialchars($patient_age); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_gender">Gender</label>
                                            <input type="text" id="patient_gender" name="patient_gender" 
                                                value="<?php echo htmlspecialchars($patient_gender); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_blood_group">Blood Group</label>
                                            <input type="text" id="patient_blood_group" name="patient_blood_group" 
                                                value="<?php echo htmlspecialchars($patient_blood_group); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_mobile">Mobile Number</label>
                                            <input type="text" id="patient_mobile" name="patient_mobile" 
                                                value="<?php echo htmlspecialchars($patient_mobile); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_email">Email</label>
                                            <input type="text" id="patient_email" name="patient_email" 
                                                value="<?php echo htmlspecialchars($patient_email); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. Medical Information -->
                                <div class="mb-6">
                                    <div class="section-title">4. Medical Information</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label for="reason">Reason for Visit / Chief Complaint <span class="required">*</span></label>
                                            <select id="reason" name="reason" required>
                                                <option value="">Select Reason</option>
                                                <option value="Fever" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Fever') ? 'selected' : ''; ?>>Fever</option>
                                                <option value="Cough" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Cough') ? 'selected' : ''; ?>>Cough</option>
                                                <option value="Headache" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Headache') ? 'selected' : ''; ?>>Headache</option>
                                                <option value="Chest Pain" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Chest Pain') ? 'selected' : ''; ?>>Chest Pain</option>
                                                <option value="Diabetes Checkup" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Diabetes Checkup') ? 'selected' : ''; ?>>Diabetes Checkup</option>
                                                <option value="Blood Pressure" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Blood Pressure') ? 'selected' : ''; ?>>Blood Pressure</option>
                                                <option value="Skin Problem" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Skin Problem') ? 'selected' : ''; ?>>Skin Problem</option>
                                                <option value="Eye Problem" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Eye Problem') ? 'selected' : ''; ?>>Eye Problem</option>
                                                <option value="Other" <?php echo (isset($_POST['reason']) && $_POST['reason'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="symptoms">Symptoms</label>
                                            <input type="text" id="symptoms" name="symptoms" 
                                                value="<?php echo isset($_POST['symptoms']) ? htmlspecialchars($_POST['symptoms']) : ''; ?>"
                                                placeholder="Enter symptoms (e.g., Fever, cough, body ache)">
                                        </div>
                                        <div class="form-group">
                                            <label for="since_when">Since When</label>
                                            <select id="since_when" name="since_when">
                                                <option value="">Select</option>
                                                <option value="Today" <?php echo (isset($_POST['since_when']) && $_POST['since_when'] === 'Today') ? 'selected' : ''; ?>>Today</option>
                                                <option value="Yesterday" <?php echo (isset($_POST['since_when']) && $_POST['since_when'] === 'Yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                                                <option value="2-3 Days" <?php echo (isset($_POST['since_when']) && $_POST['since_when'] === '2-3 Days') ? 'selected' : ''; ?>>2-3 Days</option>
                                                <option value="1 Week" <?php echo (isset($_POST['since_when']) && $_POST['since_when'] === '1 Week') ? 'selected' : ''; ?>>1 Week</option>
                                                <option value="1 Month" <?php echo (isset($_POST['since_when']) && $_POST['since_when'] === '1 Month') ? 'selected' : ''; ?>>1 Month</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="severity">Severity</label>
                                            <select id="severity" name="severity">
                                                <option value="">Select</option>
                                                <option value="Mild" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Mild') ? 'selected' : ''; ?>>Mild</option>
                                                <option value="Moderate" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Moderate') ? 'selected' : ''; ?>>Moderate</option>
                                                <option value="Severe" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Severe') ? 'selected' : ''; ?>>Severe</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- 5. Previous History -->
                                <div class="mb-6">
                                    <div class="section-title">5. Previous Medical History</div>
                                    <div class="form-group">
                                        <label class="section-subtitle">Select Previous Conditions</label>
                                        <div class="checkbox-grid">
                                            <label><input type="checkbox" name="previous_history[]" value="Diabetes" <?php echo (isset($_POST['previous_history']) && in_array('Diabetes', $_POST['previous_history'])) ? 'checked' : ''; ?>> Diabetes</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Blood Pressure" <?php echo (isset($_POST['previous_history']) && in_array('Blood Pressure', $_POST['previous_history'])) ? 'checked' : ''; ?>> Blood Pressure</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Heart Disease" <?php echo (isset($_POST['previous_history']) && in_array('Heart Disease', $_POST['previous_history'])) ? 'checked' : ''; ?>> Heart Disease</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Asthma" <?php echo (isset($_POST['previous_history']) && in_array('Asthma', $_POST['previous_history'])) ? 'checked' : ''; ?>> Asthma</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Kidney Disease" <?php echo (isset($_POST['previous_history']) && in_array('Kidney Disease', $_POST['previous_history'])) ? 'checked' : ''; ?>> Kidney Disease</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Thyroid" <?php echo (isset($_POST['previous_history']) && in_array('Thyroid', $_POST['previous_history'])) ? 'checked' : ''; ?>> Thyroid</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Allergy" <?php echo (isset($_POST['previous_history']) && in_array('Allergy', $_POST['previous_history'])) ? 'checked' : ''; ?>> Allergy</label>
                                            <label><input type="checkbox" name="previous_history[]" value="Other" <?php echo (isset($_POST['previous_history']) && in_array('Other', $_POST['previous_history'])) ? 'checked' : ''; ?>> Other</label>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                        <div class="form-group">
                                            <label for="allergies">Allergies</label>
                                            <input type="text" id="allergies" name="allergies" 
                                                value="<?php echo isset($_POST['allergies']) ? htmlspecialchars($_POST['allergies']) : ''; ?>"
                                                placeholder="Enter any allergies">
                                        </div>
                                        <div class="form-group">
                                            <label for="current_medicines">Current Medicines</label>
                                            <input type="text" id="current_medicines" name="current_medicines" 
                                                value="<?php echo isset($_POST['current_medicines']) ? htmlspecialchars($_POST['current_medicines']) : ''; ?>"
                                                placeholder="Enter current medicines">
                                        </div>
                                    </div>
                                </div>

                                <!-- 6. Documents Upload -->
                                <div class="mb-6">
                                    <div class="section-title">6. Documents</div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="form-group">
                                            <label for="prescription">Previous Prescription</label>
                                            <input type="file" id="prescription" name="prescription" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-group">
                                            <label for="lab_report">Lab Report</label>
                                            <input type="file" id="lab_report" name="lab_report" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-group">
                                            <label for="xray">X-Ray</label>
                                            <input type="file" id="xray" name="xray" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-group">
                                            <label for="mri">MRI</label>
                                            <input type="file" id="mri" name="mri" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-group">
                                            <label for="ct_scan">CT Scan</label>
                                            <input type="file" id="ct_scan" name="ct_scan" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-group">
                                            <label for="other_document">Other Document</label>
                                            <input type="file" id="other_document" name="other_document" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                    </div>
                                </div>

                                <!-- 7. Additional Notes -->
                                <div class="mb-6">
                                    <div class="section-title">7. Additional Notes</div>
                                    <div class="form-group">
                                        <label for="note">Notes for Doctor</label>
                                        <textarea id="note" name="note" rows="3" 
                                                placeholder="Enter any additional notes or remarks"><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea>
                                    </div>
                                </div>

                                <!-- Submit Buttons -->
                                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                    <button type="submit" class="btn-primary">
                                        <i data-lucide="calendar-plus" class="w-4 h-4 inline mr-2"></i>
                                        Book Appointment
                                    </button>
                                    <button type="reset" class="btn-secondary">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4 inline mr-2"></i>
                                        Reset
                                    </button>
                                    <a href="show_patient_appointments.php" class="btn-secondary">
                                        <i data-lucide="list" class="w-4 h-4 inline mr-2"></i>
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

        document.addEventListener('DOMContentLoaded', function() {
            const doctorIdInput = document.getElementById('doctor_id');
            const doctorNameInput = document.getElementById('doctor_name');
            const departmentSelect = document.getElementById('department');
            const selectedDoctorDiv = document.getElementById('selectedDoctor');
            const selectedDoctorNameDiv = document.getElementById('selectedDoctorName');
            const selectedDoctorDeptDiv = document.getElementById('selectedDoctorDept');

            if (doctorIdInput.value && doctorNameInput.value && departmentSelect.value) {
                selectedDoctorNameDiv.textContent = doctorNameInput.value;
                selectedDoctorDeptDiv.textContent = 'Department: ' + departmentSelect.value;
                selectedDoctorDiv.style.display = 'block';
                document.getElementById('doctorSearch').value = doctorNameInput.value;
            }
        });

        function searchDoctors(query) {
            const resultsDiv = document.getElementById("doctorResults");
            const selectedDept = document.getElementById("department").value;

            if (query.length < 2 && selectedDept === "") {
                resultsDiv.style.display = "none";
                return;
            }

            // Use direct PHP data instead of AJAX
            const allDoctors = <?php echo json_encode($doctors); ?>;
            
            let filtered = allDoctors.filter(function(doctor) {
                let matchName = doctor.doctor_name.toLowerCase().includes(query.toLowerCase());
                if (selectedDept == "")
                    return matchName;
                return matchName && doctor.department == selectedDept;
            });

            if (filtered.length === 0) {
                resultsDiv.innerHTML = "<div class='result-item'><div class='name text-gray-500'>No doctors found</div></div>";
            } else {
                let html = "";
                filtered.forEach(function(doctor) {
                    html += `
                        <div class="result-item"
                            onclick="selectDoctor(${doctor.doctor_id},'${doctor.doctor_name}','${doctor.department}')">
                            <div class="name">${doctor.doctor_name}</div>
                            <div class="info">${doctor.department}</div>
                        </div>`;
                });
                resultsDiv.innerHTML = html;
            }
            resultsDiv.style.display = "block";
        }

        function selectDoctor(id, name, department) {
            document.getElementById("doctor_id").value = id;
            document.getElementById("doctor_name").value = name;
            document.getElementById("doctorSearch").value = name;
            document.getElementById("selectedDoctorName").textContent = name;
            document.getElementById("selectedDoctorDept").textContent = "Department: " + department;
            document.getElementById("selectedDoctor").style.display = "block";
            document.getElementById("doctorResults").style.display = "none";
            document.getElementById("department").value = department;
        }

        function filterDoctorsByDepartment() {
            document.getElementById("doctor_id").value = "";
            document.getElementById("doctor_name").value = "";
            document.getElementById("doctorSearch").value = "";
            document.getElementById("selectedDoctor").style.display = "none";
            document.getElementById("doctorResults").style.display = "none";
            searchDoctors(document.getElementById('doctorSearch').value);
        }

        document.addEventListener('click', function(e) {
            const doctorSearch = document.getElementById("doctorSearch");
            const doctorResults = document.getElementById("doctorResults");
            if (!doctorSearch.contains(e.target) && !doctorResults.contains(e.target)) {
                doctorResults.style.display = "none";
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
