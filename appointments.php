<?php
session_start();
include("config/hospital.php");

 include 'config/permission.php';
    checkPermission('appointment-view'); 

// Ensure database connection is using UTF-8
$conn->set_charset("utf8");

$hid=$_SESSION["hospital_id"];

// Initialize message variables
$message = "";
$messageType = "";

// Function to safely get POST data
function getPostData($key, $default = "") {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

// Function to fetch data from the database
function fetchData($conn, $query) {
    $result = $conn->query($query);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Fetch patients
$patients = fetchData($conn, "SELECT patient_id, patient_name, mobile, email, address, date_of_birth, age, gender, blood_group FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id='$hid' ORDER BY patient_name ASC");
// Fetch doctors
$doctors = fetchData($conn, "SELECT doctor_id, doctor_name, department, specialization, qualification, experience, consultation_fee, mobile, email FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id='$hid' ORDER BY doctor_name ASC");

// Fetch wards with available rooms count
$wards = fetchData($conn, "SELECT ward_id, ward_name, ward_type, floor_no, (SELECT COUNT(*) FROM room_master WHERE ward_id = ward_master.ward_id AND status != 'Occupied' AND delete_flag = 0) as available_rooms FROM ward_master WHERE status='Available' AND (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid'  ORDER BY ward_name ASC");

// Get unique departments from doctors
$departments = [];
foreach ($doctors as $doctor) {
    if (!in_array($doctor['department'], $departments)) {
        $departments[] = $doctor['department'];
    }
}
sort($departments);

// Initialize form data with session values or defaults
$form_data = isset($_SESSION['appointment_form_data']) ? $_SESSION['appointment_form_data'] : [];
$patient_name = $form_data['patient_name'] ?? "";
$patient_age = $form_data['patient_age'] ?? "";
$patient_gender = $form_data['patient_gender'] ?? "";
$patient_blood_group = $form_data['patient_blood_group'] ?? "";
$patient_mobile = $form_data['patient_mobile'] ?? "";
$patient_email = $form_data['patient_email'] ?? "";
$appointment_no = $form_data['appointment_no'] ?? ("APP-" . date('YmdHis') . "-" . uniqid());

// --- Form Submission Handling ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store all POST data in session to preserve on refresh
    $_SESSION['appointment_form_data'] = $_POST;
    
    // Get form inputs
    $appointment_no = mysqli_real_escape_string($conn, getPostData('appointment_no'));
    $patient_id = mysqli_real_escape_string($conn, getPostData('patient_id'));
    $doctor_id = mysqli_real_escape_string($conn, getPostData('doctor_id'));
    $appointment_type = mysqli_real_escape_string($conn, getPostData('appointment_type'));
    $appointment_date = mysqli_real_escape_string($conn, getPostData('appointment_date'));
    $appointment_time = mysqli_real_escape_string($conn, getPostData('appointment_time'));
    $duration = mysqli_real_escape_string($conn, getPostData('duration'));
    $reason = mysqli_real_escape_string($conn, getPostData('reason'));
    $symptoms = mysqli_real_escape_string($conn, getPostData('symptoms'));
    $since_when = mysqli_real_escape_string($conn, getPostData('since_when'));
    $severity = mysqli_real_escape_string($conn, getPostData('severity'));
    $allergies = mysqli_real_escape_string($conn, getPostData('allergies'));
    $current_medicines = mysqli_real_escape_string($conn, getPostData('current_medicines'));
    $note = mysqli_real_escape_string($conn, getPostData('note'));
    $opd_ipd_type = mysqli_real_escape_string($conn, getPostData('opd_ipd_type', 'OPD'));
    $status = mysqli_real_escape_string($conn, getPostData('status', 'Scheduled'));
    $previous_history = isset($_POST['previous_history']) ? implode(", ", $_POST['previous_history']) : "";
    
    // IPD specific fields
    $admission_date = mysqli_real_escape_string($conn, getPostData('admission_date', date('Y-m-d')));
    $diagnosis = mysqli_real_escape_string($conn, getPostData('diagnosis'));
    $treatment_plan = mysqli_real_escape_string($conn, getPostData('treatment_plan'));
    $ward_id = mysqli_real_escape_string($conn, getPostData('ward_id'));
    $room_id = mysqli_real_escape_string($conn, getPostData('room_id'));
    $bed_id = mysqli_real_escape_string($conn, getPostData('bed_id'));
    
    // File upload handling - Store file names
    $upload_dir = "uploads/documents/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Function to upload file
    function uploadFile($file, $upload_dir) {
        if (isset($file) && $file['error'] == 0 && !empty($file['name'])) {
            $file_name = time() . '_' . basename($file['name']);
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                return $file_name;
            }
        }
        return '';
    }
    
    // IPD Document Uploads
    $prescription_file = uploadFile($_FILES['prescription_file'] ?? null, $upload_dir);
    $lab_report_file = uploadFile($_FILES['lab_report_file'] ?? null, $upload_dir);
    $xray_file = uploadFile($_FILES['xray_file'] ?? null, $upload_dir);
    $mri_file = uploadFile($_FILES['mri_file'] ?? null, $upload_dir);
    $ctscan_file = uploadFile($_FILES['ctscan_file'] ?? null, $upload_dir);
    $other_document = uploadFile($_FILES['other_document'] ?? null, $upload_dir);
    
    // OPD Document Uploads
    $opd_prescription_file = uploadFile($_FILES['opd_prescription_file'] ?? null, $upload_dir);
    $opd_lab_report_file = uploadFile($_FILES['opd_lab_report_file'] ?? null, $upload_dir);
    $opd_xray_file = uploadFile($_FILES['opd_xray_file'] ?? null, $upload_dir);
    $opd_mri_file = uploadFile($_FILES['opd_mri_file'] ?? null, $upload_dir);
    $opd_ctscan_file = uploadFile($_FILES['opd_ctscan_file'] ?? null, $upload_dir);
    $opd_other_document = uploadFile($_FILES['opd_other_document'] ?? null, $upload_dir);
    
    // Get patient details from DB
    $patient_details = [];
    if (!empty($patient_id)) {
        $result = $conn->query("SELECT patient_name, mobile, email, address, date_of_birth, age, gender, blood_group FROM patients WHERE patient_id = '$patient_id' and hospital_id='$hid'");
        if ($result && $result->num_rows > 0) {
            $patient_details = $result->fetch_assoc();
        }
    }

    // Get doctor details from DB
    $doctor_details = [];
    if (!empty($doctor_id)) {
        $result = $conn->query("SELECT doctor_name, department, specialization, qualification, experience, consultation_fee, mobile, email FROM doctor WHERE doctor_id = '$doctor_id' and hospital_id='$hid'");
        if ($result && $result->num_rows > 0) {
            $doctor_details = $result->fetch_assoc();
        }
    }

    // Assign details to variables
    $patient_name = $patient_details['patient_name'] ?? '';
    $patient_mobile = $patient_details['mobile'] ?? '';
    $patient_email = $patient_details['email'] ?? '';
    $patient_address = $patient_details['address'] ?? '';
    $patient_dob = $patient_details['date_of_birth'] ?? '';
    $patient_age = $patient_details['age'] ?? '';
    $patient_gender = $patient_details['gender'] ?? '';
    $patient_blood_group = $patient_details['blood_group'] ?? '';

    $doctor_name = $doctor_details['doctor_name'] ?? '';
    $department = $doctor_details['department'] ?? '';
    $doctor_specialization = $doctor_details['specialization'] ?? '';
    $doctor_qualification = $doctor_details['qualification'] ?? '';
    $doctor_experience = $doctor_details['experience'] ?? '';
    $doctor_fee = $doctor_details['consultation_fee'] ?? '';
    $doctor_mobile = $doctor_details['mobile'] ?? '';
    $doctor_email = $doctor_details['email'] ?? '';

    // --- Validation ---
    $error = false;
    if (empty($appointment_no) || empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
        $error = true;
    }

    // For IPD, validate ward, room, bed and diagnosis
    if ($opd_ipd_type == 'IPD' && !$error) {
        if (empty($ward_id) || empty($room_id) || empty($bed_id) || empty($diagnosis)) {
            $message = "Please fill in all IPD admission details!";
            $messageType = "error";
            $error = true;
        }
    }

    // If no errors, proceed with insertion
    if (!$error) {
        // Insert into appointments table
        $sql = "INSERT INTO appointments(
                    appointment_no, 
                    patient_id, 
                    doctor_id, 
                    department, 
                    appointment_type, 
                    opd_ipd_type,
                    appointment_date, 
                    appointment_time, 
                    duration, 
                    reason, 
                    status, 
                    notes, 
                    delete_flag,
                    hospital_id
                ) VALUES (
                    '$appointment_no',
                    '$patient_id',
                    '$doctor_id',
                    '$department',
                    '$appointment_type',
                    '$opd_ipd_type',
                    '$appointment_date',
                    '$appointment_time',
                    '$duration',
                    '$reason',
                    '$status',
                    '$note',
                    '0',
                    '$hid'
                )";

        if ($conn->query($sql)) {
            $appointment_id = $conn->insert_id;
            
            // If IPD, insert into ipd_admissions
            if ($opd_ipd_type == 'IPD') {
                $admission_no = "IPD-" . date('Ymd') . "-" . rand(1000, 9999);
                
                // Get ward, room, bed details
                $ward_result = $conn->query("SELECT ward_name FROM ward_master WHERE ward_id = '$ward_id' and hospital_id='$hid'");
                $ward_row = $ward_result->fetch_assoc();
                $ward_name = $ward_row['ward_name'] ?? '';
                
                $room_result = $conn->query("SELECT room_no FROM room_master WHERE room_id = '$room_id' and hospital_id='$hid'");
                $room_row = $room_result->fetch_assoc();
                $room_no = $room_row['room_no'] ?? '';
                
                $bed_result = $conn->query("SELECT bed_no FROM bed_master WHERE bed_id = '$bed_id' and hospital_id='$hid'");
                $bed_row = $bed_result->fetch_assoc();
                $bed_no = $bed_row['bed_no'] ?? '';
                
                // Insert IPD admission with document fields
                $sql_ipd = "INSERT INTO ipd_admissions (
                                admission_no, 
                                appointment_id, 
                                appointment_type, 
                                patient_id, 
                                doctor_id, 
                                department,
                                ward_id, 
                                room_no, 
                                bed_no, 
                                admission_date, 
                                appointment_time, 
                                duration, 
                                disease_reason,
                                notes, 
                                symptoms, 
                                since_when, 
                                severity, 
                                previous_history, 
                                current_medicines, 
                                status,
                                prescription_file,
                                lab_report_file,
                                xray_file,
                                mri_file,
                                ctscan_file,
                                other_document,
                                delete_flag,
                                hospital_id
                            ) VALUES (
                                '$admission_no',
                                '$appointment_id',
                                '$appointment_type',
                                '$patient_id',
                                '$doctor_id',
                                '$department',
                                '$ward_id',
                                '$room_no',
                                '$bed_no',
                                '$admission_date',
                                '$appointment_time',
                                '$duration',
                                '$reason',
                                '$note',
                                '$symptoms',
                                '$since_when',
                                '$severity',
                                '$previous_history',
                                '$current_medicines',
                                'Admitted',
                                '$prescription_file',
                                '$lab_report_file',
                                '$xray_file',
                                '$mri_file',
                                '$ctscan_file',
                                '$other_document',
                                '0',
                                '$hid'
                            )";
                
                if ($conn->query($sql_ipd)) {
                    // Allocate bed
                    $conn->query("INSERT INTO bed_allocation (patient_id, bed_id, admit_date, status) VALUES ('$patient_id', '$bed_id', NOW(), 'Occupied')");
                    
                    // Update bed status
                    $conn->query("UPDATE bed_master SET status='Occupied' WHERE bed_id='$bed_id'");
                    // Check if all beds in this room are occupied
                        $room_check = $conn->query("
                        select count(*) total,
                        sum(case when status='Occupied' then 1 else 0 end) occupied
                        from bed_master
                        where room_id='$room_id'
                        and (delete_flag=0 or delete_flag is null)
                        ");

                        $room = $room_check->fetch_assoc();

                        if($room['total'] == $room['occupied'])
                        {
                            $conn->query("update room_master set status='Occupied' where room_id='$room_id'");
                        }
                        else
                        {
                            $conn->query("update room_master set status='Available' where room_id='$room_id'");
                        }


                        // Check if all rooms in this ward are occupied
                        $ward_check = $conn->query("
                        select count(*) total,
                        sum(case when status='Occupied' then 1 else 0 end) occupied
                        from room_master
                        where ward_id='$ward_id'
                        and (delete_flag=0 or delete_flag is null)
                        ");

                        $ward = $ward_check->fetch_assoc();

                        if($ward['total'] == $ward['occupied'])
                        {
                            $conn->query("update ward_master set status='Occupied' where ward_id='$ward_id'");
                        }
                        else
                        {
                            $conn->query("update ward_master set status='Available' where ward_id='$ward_id'");
                        }
                    
                    // Check if all beds in room are occupied
                    $room_check = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied FROM bed_master WHERE room_id = '$room_id' AND delete_flag = 0");
                    $room_data = $room_check->fetch_assoc();
                    if ($room_data && $room_data['total'] == $room_data['occupied'] && $room_data['total'] > 0) {
                        $conn->query("UPDATE room_master SET status='Occupied' WHERE room_id='$room_id'");
                    }
                    
                    // Check if all rooms in ward are occupied
                    $ward_check = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied FROM room_master WHERE ward_id = '$ward_id' AND delete_flag = 0");
                    $ward_data = $ward_check->fetch_assoc();
                    if ($ward_data && $ward_data['total'] == $ward_data['occupied'] && $ward_data['total'] > 0) {
                        $conn->query("UPDATE ward_master SET status='Occupied' WHERE ward_id='$ward_id'");
                    }
                    
                    // Update appointment status
                    $conn->query("UPDATE appointments SET status='Confirmed' WHERE appointment_id='$appointment_id'");
                    
                    unset($_SESSION['appointment_form_data']);
                    echo "<script>alert('IPD Admission completed successfully!'); window.location='show_ipd_appointments.php';</script>";
                    exit();
                } else {
                    $message = "Error inserting IPD admission: " . $conn->error;
                    $messageType = "error";
                }
            } else {
                // OPD appointment
                unset($_SESSION['appointment_form_data']);
                echo "<script>alert('OPD Appointment scheduled successfully!'); window.location='show_opd_appointments.php';</script>";
                exit();
            }
        } else {
            $message = "Error inserting appointment: " . $conn->error;
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset='utf-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1' />
   
        <title><?php echo $hospital['hospital_name'] ?> - Add New Appointment</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { 
            margin-left: 260px; 
            padding: 24px 28px; 
            min-height: 100vh; 
            margin-top: 70px;
        }
        @media (max-width: 1024px) { 
            .main-content { 
                margin-left: 0; 
                padding: 16px; 
                margin-top: 60px;
            } 
        }
        .form-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; color: #4a5568; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #6366f1; outline: 0; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
        .form-group input[type="file"] { padding: 0.5rem; }
        .required { color: #ef4444; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: #1a202c; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        .alert { padding: 1rem; border-radius: 8px; display: flex; align-items: center; margin-bottom: 1.5rem; }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .btn-primary { background-color: #6366f1; color: #fff; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-primary:hover { background-color: #4f46e5; }
        .btn-secondary { background-color: #e2e8f0; color: #4a5568; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-secondary:hover { background-color: #d1d5db; }
        .search-box { position: relative; }
        .search-results { position: absolute; background: white; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; max-height: 200px; overflow-y: auto; z-index: 10; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: none; }
        .search-results .result-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e2e8f0; }
        .search-results .result-item:hover { background-color: #f7fafc; }
        .selected-item { background-color: #e0f2fe; border: 1px solid #90cdf4; border-radius: 8px; padding: 0.75rem 1rem; margin-top: 0.5rem; display: none; }
        .ipd-section { display: none; }
        .ipd-summary { background-color: #f0f9ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; padding: 1.5rem; border-radius: 8px; margin-top: 2rem; display: none; }
        .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem; }
        .checkbox-grid label { display: flex; align-items: center; font-weight: normal; font-size: 14px; }
        .checkbox-grid input { margin-right: 0.5rem; width: auto; }
        .document-upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) { .document-upload-grid { grid-template-columns: 1fr; } }
    </style>
</head>

<body class='bg-gray-50 text-gray-900'>
    <div class='flex min-h-screen flex-col bg-gray-50'>
        <?php include 'header.php'; ?> 
        
        <div class='flex flex-1 items-start'>
            <?php include 'Sidebar.php'; ?>

            <main class='flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64'>
                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                        <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 mr-3"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <div class="flex items-center gap-4 mb-6">
                    <a href="dashboard.php" class="p-2 border rounded-md hover:bg-gray-100 transition">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight">Add New Appointment</h1>
                        <p class="text-gray-500 text-sm">Schedule a new appointment for patients.</p>
                    </div>
                </div>
               

                <div class="form-card">
                    <form action="appointments.php" method="POST" id="appointmentForm" enctype="multipart/form-data">
                        <div class="section-title">1. Basic Appointment Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="form-group">
                                <label for="appointment_no">Appointment No <span class="required">*</span></label>
                                <input type="text" id="appointment_no" name="appointment_no" value="<?php echo htmlspecialchars($appointment_no); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="opd_ipd_type">OPD / IPD <span class="required">*</span></label>
                                <select id="opd_ipd_type" name="opd_ipd_type" required onchange="toggleSections();">
                                    <option value="OPD" <?php echo ($form_data['opd_ipd_type'] ?? '') == 'OPD' ? 'selected' : ''; ?>>OPD</option>
                                    <option value="IPD" <?php echo ($form_data['opd_ipd_type'] ?? '') == 'IPD' ? 'selected' : ''; ?>>IPD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="appointment_type">Visit Type <span class="required">*</span></label>
                                <select id="appointment_type" name="appointment_type" required>
                                    <option value="">Select type</option>
                                    <option value="Consultation" <?php echo ($form_data['appointment_type'] ?? '') == 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
                                    <option value="Follow-up" <?php echo ($form_data['appointment_type'] ?? '') == 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                                    <option value="Procedure" <?php echo ($form_data['appointment_type'] ?? '') == 'Procedure' ? 'selected' : ''; ?>>Procedure</option>
                                    <option value="Check-up" <?php echo ($form_data['appointment_type'] ?? '') == 'Check-up' ? 'selected' : ''; ?>>Check-up</option>
                                    <option value="Emergency" <?php echo ($form_data['appointment_type'] ?? '') == 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="appointment_date">Date <span class="required">*</span></label>
                                <input type="date" id="appointment_date" name="appointment_date" value="<?php echo htmlspecialchars($form_data['appointment_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="appointment_time">Time <span class="required">*</span></label>
                                <select id="appointment_time" name="appointment_time" required>
                                    <option value="">Select time</option>
                                    <?php
                                    $times = ["08:00 AM", "08:30 AM", "09:00 AM", "09:30 AM", "10:00 AM", "10:30 AM", "11:00 AM", "11:30 AM", "12:00 PM", "02:00 PM", "02:30 PM", "03:00 PM", "03:30 PM", "04:00 PM", "04:30 PM", "05:00 PM"];
                                    foreach ($times as $time) {
                                        $selected = (($form_data['appointment_time'] ?? '') == $time) ? 'selected' : '';
                                        echo "<option value=\"{$time}\" {$selected}>{$time}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="duration">Duration <span class="required">*</span></label>
                                <select id="duration" name="duration" required>
                                    <option value="15" <?php echo (($form_data['duration'] ?? '') == '15') ? 'selected' : ''; ?>>15 minutes</option>
                                    <option value="30" <?php echo (($form_data['duration'] ?? '') == '30') ? 'selected' : ''; ?>>30 minutes</option>
                                    <option value="45" <?php echo (($form_data['duration'] ?? '') == '45') ? 'selected' : ''; ?>>45 minutes</option>
                                    <option value="60" <?php echo (($form_data['duration'] ?? '') == '60') ? 'selected' : ''; ?>>60 minutes</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-title">2. Doctor Selection</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="form-group">
                                <label for="department">Department <span class="required">*</span></label>
                                <select id="department" name="department" required onchange="filterDoctorsByDepartment();">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (($form_data['department'] ?? '') == $dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="doctorSearch">Select Doctor <span class="required">*</span></label>
                                <div class="search-box">
                                    <input type="text" id="doctorSearch" placeholder="Type doctor name..." value="<?php echo htmlspecialchars($form_data['doctor_name'] ?? ''); ?>" onkeyup="searchDoctors(this.value)" autocomplete="off" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div id="doctorResults" class="search-results"></div>
                                </div>
                                <input type="hidden" id="doctor_id" name="doctor_id" value="<?php echo htmlspecialchars($form_data['doctor_id'] ?? ''); ?>">
                                <input type="hidden" id="doctor_name" name="doctor_name" value="<?php echo htmlspecialchars($form_data['doctor_name'] ?? ''); ?>">
                                <input type="hidden" id="doctor_specialization" name="doctor_specialization" value="<?php echo htmlspecialchars($form_data['doctor_specialization'] ?? ''); ?>">
                                <input type="hidden" id="doctor_qualification" name="doctor_qualification" value="<?php echo htmlspecialchars($form_data['doctor_qualification'] ?? ''); ?>">
                                <input type="hidden" id="doctor_experience" name="doctor_experience" value="<?php echo htmlspecialchars($form_data['doctor_experience'] ?? ''); ?>">
                                <input type="hidden" id="doctor_fee" name="doctor_fee" value="<?php echo htmlspecialchars($form_data['doctor_fee'] ?? ''); ?>">
                                <input type="hidden" id="doctor_mobile" name="doctor_mobile" value="<?php echo htmlspecialchars($form_data['doctor_mobile'] ?? ''); ?>">
                                <input type="hidden" id="doctor_email" name="doctor_email" value="<?php echo htmlspecialchars($form_data['doctor_email'] ?? ''); ?>">
                                <div id="selectedDoctor" class="selected-item" style="<?php echo (isset($form_data['doctor_id']) && !empty($form_data['doctor_id'])) ? 'display: block;' : 'display: none;'; ?>">
                                    <div class="font-medium text-sm">Selected Doctor</div>
                                    <div class="font-semibold" id="selectedDoctorName"><?php echo htmlspecialchars($form_data['doctor_name'] ?? ''); ?></div>
                                    <div id="selectedDoctorDetails" class="text-sm text-gray-600"><?php echo htmlspecialchars($form_data['doctor_specialization'] ?? ''); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">3. Patient Selection</div>
                        <div class="form-group mb-6">
                            <label for="patient_id">Select Patient <span class="required">*</span></label>
                            <select id="patient_id" name="patient_id" required onchange="loadPatientDetails()" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['patient_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($patient['patient_name']); ?>"
                                            data-age="<?php echo htmlspecialchars($patient['age']); ?>"
                                            data-gender="<?php echo htmlspecialchars($patient['gender']); ?>"
                                            data-blood="<?php echo htmlspecialchars($patient['blood_group']); ?>"
                                            data-mobile="<?php echo htmlspecialchars($patient['mobile']); ?>"
                                            data-email="<?php echo htmlspecialchars($patient['email']); ?>"
                                            data-address="<?php echo htmlspecialchars($patient['address']); ?>"
                                            data-dob="<?php echo htmlspecialchars($patient['date_of_birth']); ?>"
                                            <?php echo (($form_data['patient_id'] ?? '') == $patient['patient_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['patient_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="patientDetailsContainer" style="display: none;">
                            <div class="section-title">4. Patient Details</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="form-group"><label>Patient Name</label><input type="text" id="displayName" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Age</label><input type="text" id="displayAge" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Gender</label><input type="text" id="displayGender" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Blood Group</label><input type="text" id="displayBlood" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Mobile Number</label><input type="text" id="displayMobile" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Email</label><input type="text" id="displayEmail" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Address</label><input type="text" id="displayAddress" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                                <div class="form-group"><label>Date of Birth</label><input type="text" id="displayDOB" readonly class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50"></div>
                            </div>
                        </div>

                        <div class="section-title">5. Medical Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="form-group">
                                <label for="reason">Reason for Visit / Chief Complaint <span class="required">*</span></label>
                                <select id="reason" name="reason" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Reason</option>
                                    <option value="Fever" <?php echo (($form_data['reason'] ?? '') == 'Fever') ? 'selected' : ''; ?>>Fever</option>
                                    <option value="Cough" <?php echo (($form_data['reason'] ?? '') == 'Cough') ? 'selected' : ''; ?>>Cough</option>
                                    <option value="Headache" <?php echo (($form_data['reason'] ?? '') == 'Headache') ? 'selected' : ''; ?>>Headache</option>
                                    <option value="Chest Pain" <?php echo (($form_data['reason'] ?? '') == 'Chest Pain') ? 'selected' : ''; ?>>Chest Pain</option>
                                    <option value="Diabetes Checkup" <?php echo (($form_data['reason'] ?? '') == 'Diabetes Checkup') ? 'selected' : ''; ?>>Diabetes Checkup</option>
                                    <option value="Blood Pressure" <?php echo (($form_data['reason'] ?? '') == 'Blood Pressure') ? 'selected' : ''; ?>>Blood Pressure</option>
                                    <option value="Skin Problem" <?php echo (($form_data['reason'] ?? '') == 'Skin Problem') ? 'selected' : ''; ?>>Skin Problem</option>
                                    <option value="Eye Problem" <?php echo (($form_data['reason'] ?? '') == 'Eye Problem') ? 'selected' : ''; ?>>Eye Problem</option>
                                    <option value="Other" <?php echo (($form_data['reason'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="symptoms">Symptoms</label>
                                <input type="text" id="symptoms" name="symptoms" placeholder="Enter symptoms (e.g., Fever, cough, body ache)" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($form_data['symptoms'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="since_when">Since When</label>
                                <select id="since_when" name="since_when" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select</option>
                                    <option value="Today" <?php echo (($form_data['since_when'] ?? '') == 'Today') ? 'selected' : ''; ?>>Today</option>
                                    <option value="Yesterday" <?php echo (($form_data['since_when'] ?? '') == 'Yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                                    <option value="2-3 Days" <?php echo (($form_data['since_when'] ?? '') == '2-3 Days') ? 'selected' : ''; ?>>2-3 Days</option>
                                    <option value="1 Week" <?php echo (($form_data['since_when'] ?? '') == '1 Week') ? 'selected' : ''; ?>>1 Week</option>
                                    <option value="1 Month" <?php echo (($form_data['since_when'] ?? '') == '1 Month') ? 'selected' : ''; ?>>1 Month</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="severity">Severity</label>
                                <select id="severity" name="severity" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select</option>
                                    <option value="Mild" <?php echo (($form_data['severity'] ?? '') == 'Mild') ? 'selected' : ''; ?>>Mild</option>
                                    <option value="Moderate" <?php echo (($form_data['severity'] ?? '') == 'Moderate') ? 'selected' : ''; ?>>Moderate</option>
                                    <option value="Severe" <?php echo (($form_data['severity'] ?? '') == 'Severe') ? 'selected' : ''; ?>>Severe</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-title">6. Previous Medical History</div>
                        <div class="form-group mb-6">
                            <label class="font-medium">Select Previous Conditions</label>
                            <div class="checkbox-grid">
                                <?php 
                                $prev_history = isset($form_data['previous_history']) ? (is_array($form_data['previous_history']) ? $form_data['previous_history'] : explode(', ', $form_data['previous_history'])) : [];
                                $conditions = ["Diabetes", "Blood Pressure", "Heart Disease", "Asthma", "Kidney Disease", "Thyroid", "Allergy", "Other"];
                                foreach ($conditions as $condition) {
                                    $checked = (in_array($condition, $prev_history)) ? 'checked' : '';
                                    echo "<label><input type=\"checkbox\" name=\"previous_history[]\" value=\"{$condition}\" {$checked}> {$condition}</label>";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="form-group">
                                <label for="allergies">Known Allergies</label>
                                <input type="text" id="allergies" name="allergies" placeholder="e.g., Penicillin, Dust, Pollen" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($form_data['allergies'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="current_medicines">Current Medications</label>
                                <input type="text" id="current_medicines" name="current_medicines" placeholder="e.g., Aspirin, Insulin" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($form_data['current_medicines'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="section-title">7. Additional Notes</div>
                        <div class="form-group mb-6">
                            <label for="note">Notes</label>
                            <textarea id="note" name="note" rows="3" placeholder="Any additional notes or instructions" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($form_data['note'] ?? ''); ?></textarea>
                        </div>

                        <!-- IPD Admission Details -->
                        <div id="ipdSection" class="ipd-section">
                            <div class="section-title">8. IPD Admission Details</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="form-group">
                                    <label for="admission_date">Admission Date <span class="required">*</span></label>
                                    <input type="date" id="admission_date" name="admission_date" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($form_data['admission_date'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="diagnosis">Diagnosis <span class="required">*</span></label>
                                    <input type="text" id="diagnosis" name="diagnosis" placeholder="Enter preliminary diagnosis" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($form_data['diagnosis'] ?? ''); ?>">
                                </div>
                                <div class="form-group md:col-span-2">
                                    <label for="treatment_plan">Treatment Plan</label>
                                    <textarea id="treatment_plan" name="treatment_plan" rows="3" placeholder="Outline the initial treatment plan" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($form_data['treatment_plan'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="section-title mt-6">Ward & Room Allocation</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="form-group">
                                    <label for="ward_id">Ward <span class="required">*</span></label>
                                    <select id="ward_id" name="ward_id" onchange="loadRooms(this.value)" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Ward</option>
                                        <?php foreach ($wards as $ward): ?>
                                            <option value="<?php echo $ward['ward_id']; ?>" 
                                                    <?php echo (($form_data['ward_id'] ?? '') == $ward['ward_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ward['ward_name']); ?> (Available Rooms: <?php echo $ward['available_rooms']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="room_id">Room <span class="required">*</span></label>
                                    <select id="room_id" name="room_id" onchange="loadBeds(this.value)" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Room</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="bed_id">Bed <span class="required">*</span></label>
                                    <select id="bed_id" name="bed_id" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Bed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Document Upload Section -->
                        <div id="documentSection" class="mt-6 pt-4 border-t border-gray-200">
                            <div class="section-title">8. Document Uploads</div>
                            <div class="document-upload-grid">
                                <div class="form-group">
                                    <label for="prescription_file">Prescription File</label>
                                    <input type="file" id="prescription_file" name="prescription_file" class="block w-full p-2 border border-gray-300 rounded-lg">
                                </div>
                                <div class="form-group">
                                    <label for="lab_report_file">Lab Report File</label>
                                    <input type="file" id="lab_report_file" name="lab_report_file" class="block w-full p-2 border border-gray-300 rounded-lg">
                                </div>
                                <div class="form-group">
                                    <label for="xray_file">X-Ray File</label>
                                    <input type="file" id="xray_file" name="xray_file" class="block w-full p-2 border border-gray-300 rounded-lg">
                                </div>
                                <div class="form-group">
                                    <label for="mri_file">MRI File</label>
                                    <input type="file" id="mri_file" name="mri_file" class="block w-full p-2 border border-gray-300 rounded-lg">
                                </div>
                                <div class="form-group">
                                    <label for="ctscan_file">CT Scan File</label>
                                    <input type="file" id="ctscan_file" name="ctscan_file" class="block w-full p-2 border border-gray-300 rounded-lg">
                                </div>
                                <div class="form-group">
                                    <label for="other_document">Other Document</label>
                                    <input type="file" id="other_document" name="other_document" class="block w-full p-2 border border-gray-300 rounded-lg">
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-end space-x-4 mt-8">
                            <button type="button" id="nextToIPD" class="btn-primary" style="display:none;" onclick="showIPDSummary()">
                                Next: Review IPD Admission
                            </button>
                            <button type="button" id="backToIPD" class="btn-secondary" style="display:none;" onclick="hideIPDSummary()">
                                Back to IPD Details
                            </button>
                            <button type="submit" id="submitOPD" class="btn-primary">
                                Schedule OPD Appointment
                            </button>
                            <button type="submit" id="submitIPD" class="btn-primary" style="display:none;">
                                Confirm IPD Admission
                            </button>
                        </div>
                    </form>

                    <!-- IPD Summary Section -->
                    <div id="ipdSummary" class="ipd-summary">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">IPD Admission Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <p><strong>Patient Name:</strong> <span id="summaryPatient">-</span></p>
                            <p><strong>Doctor Name:</strong> <span id="summaryDoctor">-</span></p>
                            <p><strong>Admission Date:</strong> <span id="summaryAdmissionDate">-</span></p>
                            <p><strong>Ward:</strong> <span id="summaryWard">-</span></p>
                            <p><strong>Room:</strong> <span id="summaryRoom">-</span></p>
                            <p><strong>Bed:</strong> <span id="summaryBed">-</span></p>
                            <p><strong>Diagnosis:</strong> <span id="summaryDiagnosis">-</span></p>
                            <p><strong>Treatment Plan:</strong> <span id="summaryTreatmentPlan">-</span></p>
                        </div>
                        <div class="flex justify-end space-x-4 mt-6">
                            <button type="button" class="btn-secondary" onclick="hideIPDSummary()">Edit Details</button>
                            <button type="submit" form="appointmentForm" class="btn-primary">Confirm Admission</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const ALL_DOCTORS = <?php echo json_encode($doctors); ?>;
        const ALL_PATIENTS = <?php echo json_encode($patients); ?>;

        lucide.createIcons();

        function toggleSections() {
            const type = document.getElementById('opd_ipd_type').value;
            const ipdSection = document.getElementById('ipdSection');
            const submitOPD = document.getElementById('submitOPD');
            const submitIPD = document.getElementById('submitIPD');
            const nextToIPD = document.getElementById('nextToIPD');
            const backToIPD = document.getElementById('backToIPD');
            const ipdSummary = document.getElementById('ipdSummary');
            const documentSection = document.getElementById('documentSection');

            if (type === 'IPD') {
                ipdSection.style.display = 'block';
                submitOPD.style.display = 'none';
                nextToIPD.style.display = 'inline-flex';
                backToIPD.style.display = 'none';
                ipdSummary.style.display = 'none';
                submitIPD.style.display = 'none';
                documentSection.style.display = 'block';
            } else {
                ipdSection.style.display = 'none';
                submitOPD.style.display = 'inline-flex';
                nextToIPD.style.display = 'none';
                backToIPD.style.display = 'none';
                ipdSummary.style.display = 'none';
                submitIPD.style.display = 'none';
                documentSection.style.display = 'block';
            }
        }

        function searchDoctors(query) {
            const resultsDiv = document.getElementById('doctorResults');
            const department = document.getElementById('department').value;
            resultsDiv.innerHTML = '';

            if (query.length < 2 && !department) {
                resultsDiv.style.display = 'none';
                return;
            }

            let filtered = ALL_DOCTORS.filter(doctor => {
                const matchesQuery = doctor.doctor_name.toLowerCase().includes(query.toLowerCase());
                const matchesDepartment = department ? doctor.department === department : true;
                return matchesQuery && matchesDepartment;
            });

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="result-item text-gray-500">No doctors found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(doctor => {
                html += `
                    <div class="result-item" onclick="selectDoctor(
                        '${doctor.doctor_id}', 
                        '${doctor.doctor_name}', 
                        '${doctor.department}', 
                        '${doctor.specialization}', 
                        '${doctor.qualification}', 
                        '${doctor.experience}', 
                        '${doctor.consultation_fee}', 
                        '${doctor.mobile}', 
                        '${doctor.email}'
                    )">
                        <div class="font-medium">${doctor.doctor_name}</div>
                        <div class="text-sm text-gray-600">${doctor.department} | ${doctor.specialization}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        function selectDoctor(id, name, dept, spec, qual, exp, fee, mobile, email) {
            document.getElementById('doctor_id').value = id;
            document.getElementById('doctor_name').value = name;
            document.getElementById('doctor_specialization').value = spec || '';
            document.getElementById('doctor_qualification').value = qual || '';
            document.getElementById('doctor_experience').value = exp || '';
            document.getElementById('doctor_fee').value = fee || '';
            document.getElementById('doctor_mobile').value = mobile || '';
            document.getElementById('doctor_email').value = email || '';

            document.getElementById('doctorSearch').value = name;
            document.getElementById('selectedDoctorName').textContent = name;
            
            let detailsText = '';
            if (spec) detailsText += spec;
            if (qual) detailsText += (detailsText ? ' | ' : '') + qual;
            if (exp) detailsText += (detailsText ? ' | ' : '') + exp + ' yrs';
            if (fee) detailsText += (detailsText ? ' | ' : '') + 'Fee: $' + fee;
            
            document.getElementById('selectedDoctorDetails').textContent = detailsText || 'Doctor selected';
            document.getElementById('selectedDoctor').style.display = 'block';
            document.getElementById('doctorResults').style.display = 'none';
            updateIPDInfo();
        }

        function filterDoctorsByDepartment() {
            const dept = document.getElementById('department').value;
            document.getElementById('doctorSearch').value = '';
            document.getElementById('doctor_id').value = '';
            document.getElementById('doctor_name').value = '';
            document.getElementById('selectedDoctor').style.display = 'none';
            document.getElementById('doctorResults').style.display = 'none';
            
            if (dept) {
                document.getElementById('doctorSearch').placeholder = 'Search doctors in ' + dept;
            } else {
                document.getElementById('doctorSearch').placeholder = 'Type doctor name...';
            }
            if (dept) {
                const resultsDiv = document.getElementById('doctorResults');
                let filtered = ALL_DOCTORS.filter(doctor => doctor.department === dept);
                let html = '';
                filtered.forEach(doctor => {
                    html += `
                        <div class="result-item" onclick="selectDoctor(
                            '${doctor.doctor_id}', 
                            '${doctor.doctor_name}', 
                            '${doctor.department}', 
                            '${doctor.specialization}', 
                            '${doctor.qualification}', 
                            '${doctor.experience}', 
                            '${doctor.consultation_fee}', 
                            '${doctor.mobile}', 
                            '${doctor.email}'
                        )">
                            <div class="font-medium">${doctor.doctor_name}</div>
                            <div class="text-sm text-gray-600">${doctor.department} | ${doctor.specialization}</div>
                        </div>
                    `;
                });
                resultsDiv.innerHTML = html || '<div class="result-item text-gray-500">No doctors in this department</div>';
                resultsDiv.style.display = 'block';
            }
        }

        function loadPatientDetails() {
            const select = document.getElementById('patient_id');
            const selectedOption = select.options[select.selectedIndex];
            const container = document.getElementById('patientDetailsContainer');
            
            if (selectedOption.value) {
                document.getElementById('displayName').value = selectedOption.dataset.name || '';
                document.getElementById('displayAge').value = selectedOption.dataset.age || '';
                document.getElementById('displayGender').value = selectedOption.dataset.gender || '';
                document.getElementById('displayBlood').value = selectedOption.dataset.blood || '';
                document.getElementById('displayMobile').value = selectedOption.dataset.mobile || '';
                document.getElementById('displayEmail').value = selectedOption.dataset.email || '';
                document.getElementById('displayAddress').value = selectedOption.dataset.address || '';
                document.getElementById('displayDOB').value = selectedOption.dataset.dob || '';
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
            updateIPDInfo();
        }

        function updateIPDInfo() {
            const doctorName = document.getElementById('doctor_name').value;
            const patientName = document.getElementById('displayName').value;
            
            document.getElementById('summaryPatient').textContent = patientName || '-';
            document.getElementById('summaryDoctor').textContent = doctorName || '-';
        }

        async function loadRooms(wardId) {
            console.log("Ward Selected:", wardId);
            const roomSelect = document.getElementById('room_id');
            const bedSelect = document.getElementById('bed_id');
            roomSelect.innerHTML = '<option value="">Select Room</option>';
            bedSelect.innerHTML = '<option value="">Select Bed</option>';
            if (!wardId) return;
            roomSelect.innerHTML = '<option>Loading...</option>';
            const response = await fetch(`get_rooms.php?ward_id=${wardId}`);
            console.log(response);
            const data = await response.json();
            console.log(data);
            let options = '<option value="">Select Room</option>';
            data.forEach(room=>{
                options += `<option value="${room.room_id}">
                    ${room.room_no}
                </option>`;
            });
            roomSelect.innerHTML = options;
        }
        
        async function loadBeds(roomId) {
            const bedSelect = document.getElementById('bed_id');
            bedSelect.innerHTML = '<option value="">Select Bed</option>';
            if (!roomId) return;
            bedSelect.innerHTML = '<option value="">Loading beds...</option>';
            try {
                const response = await fetch(`get_beds.php?room_id=${roomId}`);
                const data = await response.json();
                let options = '<option value="">Select Bed</option>';
                if (data.length === 0) {
                    options += '<option value="" disabled>No beds available</option>';
                }
                const selectedBedId = '<?php echo $form_data['bed_id'] ?? ''; ?>';
                data.forEach(bed => {
                    const statusText = bed.status === 'Available' ? ' ✅ Available' : ' 🔴 Occupied';
                    const selected = (selectedBedId == bed.bed_id) ? 'selected' : '';
                    options += `<option value="${bed.bed_id}" ${selected}>${bed.bed_no} - ${bed.bed_type}${statusText}</option>`;
                });
                bedSelect.innerHTML = options;
            } catch (error) {
                console.error('Error loading beds:', error);
                bedSelect.innerHTML = '<option value="">Error loading beds</option>';
            }
        }

        function showIPDSummary() {
            if (!document.getElementById('patient_id').value) {
                alert('Please select a patient first!');
                return false;
            }
            if (!document.getElementById('doctor_id').value) {
                alert('Please select a doctor first!');
                return false;
            }
            if (!document.getElementById('ward_id').value || !document.getElementById('room_id').value || !document.getElementById('bed_id').value) {
                alert('Please select Ward, Room, and Bed!');
                return false;
            }
            if (!document.getElementById('diagnosis').value) {
                alert('Please enter preliminary diagnosis!');
                return false;
            }

            const wardSelect = document.getElementById('ward_id');
            const roomSelect = document.getElementById('room_id');
            const bedSelect = document.getElementById('bed_id');

            document.getElementById('summaryPatient').textContent = document.getElementById('displayName').value || '-';
            document.getElementById('summaryDoctor').textContent = document.getElementById('doctor_name').value || '-';
            document.getElementById('summaryAdmissionDate').textContent = document.getElementById('admission_date').value || 'N/A';
            document.getElementById('summaryWard').textContent = wardSelect.options[wardSelect.selectedIndex]?.text || '-';
            document.getElementById('summaryRoom').textContent = roomSelect.options[roomSelect.selectedIndex]?.text || '-';
            document.getElementById('summaryBed').textContent = bedSelect.options[bedSelect.selectedIndex]?.text || '-';
            document.getElementById('summaryDiagnosis').textContent = document.getElementById('diagnosis').value || '-';
            document.getElementById('summaryTreatmentPlan').textContent = document.getElementById('treatment_plan').value || 'Not specified';
            
            document.getElementById('ipdSummary').style.display = 'block';
            document.getElementById('ipdSection').style.display = 'none';
            document.getElementById('nextToIPD').style.display = 'none';
            document.getElementById('backToIPD').style.display = 'inline-flex';
            document.getElementById('submitIPD').style.display = 'inline-flex';
            document.getElementById('submitOPD').style.display = 'none';
            
            window.scrollTo({ top: document.getElementById('ipdSummary').offsetTop - 100, behavior: 'smooth' });
        }

        function hideIPDSummary() {
            document.getElementById('ipdSummary').style.display = 'none';
            document.getElementById('ipdSection').style.display = 'block';
            document.getElementById('nextToIPD').style.display = 'inline-flex';
            document.getElementById('backToIPD').style.display = 'none';
            document.getElementById('submitIPD').style.display = 'none';
            document.getElementById('submitOPD').style.display = 'none';
            window.scrollTo({ top: document.getElementById('ipdSection').offsetTop - 100, behavior: 'smooth' });
        }

        document.addEventListener('click', function(e) {
            const searchInput = document.getElementById('doctorSearch');
            const resultsDiv = document.getElementById('doctorResults');
            if (searchInput && resultsDiv && !searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            toggleSections();
            
            const patientSelect = document.getElementById('patient_id');
            if (patientSelect && patientSelect.value) {
                loadPatientDetails();
            }
            
            const doctorId = document.getElementById('doctor_id').value;
            if (doctorId) {
                const selectedDoctor = ALL_DOCTORS.find(doc => doc.doctor_id === doctorId);
                if (selectedDoctor) {
                    selectDoctor(
                        selectedDoctor.doctor_id, 
                        selectedDoctor.doctor_name, 
                        selectedDoctor.department, 
                        selectedDoctor.specialization, 
                        selectedDoctor.qualification, 
                        selectedDoctor.experience, 
                        selectedDoctor.consultation_fee, 
                        selectedDoctor.mobile, 
                        selectedDoctor.email
                    );
                }
            }
            
            const wardId = document.getElementById('ward_id').value;
            if (wardId) {
                loadRooms(wardId);
            }
            
            const roomId = document.getElementById('room_id').value;
            if (roomId) {
                loadBeds(roomId);
            }
        });
    </script>
</body>
</html>