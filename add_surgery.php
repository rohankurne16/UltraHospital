<?php
session_start();
include('config/hospital.php');

// Check if patient_id is provided
if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['patient_id'];
$hid = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];
$hospital_logo = $_SESSION['hospital_logo'];

// --- PROCESS FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $hospital_id = mysqli_real_escape_string($conn, $_POST['hospital_id']);
    $surgery_no = mysqli_real_escape_string($conn, $_POST['surgery_no']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $doctor_query = mysqli_query($conn, "SELECT doctor_name FROM doctor WHERE doctor_id='$doctor_id'");
    $doctor_data = mysqli_fetch_assoc($doctor_query);
    $surgeon_name = $doctor_data['doctor_name'];
    $surgery_title = mysqli_real_escape_string($conn, $_POST['surgery_title']);
    $surgery_full_name = mysqli_real_escape_string($conn, $_POST['surgery_full_name']);
    $surgery_date = mysqli_real_escape_string($conn, $_POST['surgery_date']);
    $surgery_time = mysqli_real_escape_string($conn, $_POST['surgery_time']);
    $surgery_duration = mysqli_real_escape_string($conn, $_POST['surgery_duration']);
    $hospital_location = mysqli_real_escape_string($conn, $_POST['hospital_location']);
    $assistant_surgeon = mysqli_real_escape_string($conn, $_POST['assistant_surgeon']);
    $anesthetist = mysqli_real_escape_string($conn, $_POST['anesthetist']);
    $surgery_type = mysqli_real_escape_string($conn, $_POST['surgery_type']);
    $surgery_category = mysqli_real_escape_string($conn, $_POST['surgery_category']);
    $diagnosis_before_surgery = mysqli_real_escape_string($conn, $_POST['diagnosis_before_surgery']);
    $procedure_details = mysqli_real_escape_string($conn, $_POST['procedure_details']);
    $findings = mysqli_real_escape_string($conn, $_POST['findings']);
    $complications = mysqli_real_escape_string($conn, $_POST['complications']);
    $blood_loss = mysqli_real_escape_string($conn, $_POST['blood_loss']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $follow_up_date = mysqli_real_escape_string($conn, $_POST['follow_up_date']);
    $recovery_notes = mysqli_real_escape_string($conn, $_POST['recovery_notes']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // Initialize error array
    $errors = array();

    // --- BACKEND VALIDATION ---
    
    // 1. Validate required fields
    if (empty($patient_id)) {
        $errors[] = "Patient ID is required";
    }

    if (empty($hospital_id)) {
        $errors[] = "Hospital ID is required";
    }

    if (empty($doctor_id)) {
        $errors[] = "Surgeon is required";
    } else {
        // Check if doctor exists
        $check_doctor = mysqli_query($conn, "SELECT doctor_id FROM doctor WHERE doctor_id = '$doctor_id' AND status='Active' AND delete_flag='0'");
        if (mysqli_num_rows($check_doctor) == 0) {
            $errors[] = "Selected surgeon is not valid or inactive";
        }
    }

    if (empty($surgery_title)) {
        $errors[] = "Surgery title is required";
    } else {
        // Validate surgery title length and characters
        if (strlen($surgery_title) < 3) {
            $errors[] = "Surgery title must be at least 3 characters long";
        }
        if (strlen($surgery_title) > 255) {
            $errors[] = "Surgery title must be less than 255 characters";
        }
        if (!preg_match("/^[a-zA-Z0-9\s\-']+$/", $surgery_title)) {
            $errors[] = "Surgery title contains invalid characters";
        }
    }

    // Validate surgery full name if provided
    if (!empty($surgery_full_name)) {
        if (strlen($surgery_full_name) > 255) {
            $errors[] = "Surgery full name must be less than 255 characters";
        }
        if (!preg_match("/^[a-zA-Z0-9\s\-',.]+$/", $surgery_full_name)) {
            $errors[] = "Surgery full name contains invalid characters";
        }
    }

    if (empty($surgery_date)) {
        $errors[] = "Surgery date is required";
    } else {
        // Validate date format
        $date_parts = explode('-', $surgery_date);
        if (count($date_parts) != 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
            $errors[] = "Invalid date format";
        }
        // Validate date not in the past (optional)
        $today = date('Y-m-d');
        if ($surgery_date < $today) {
            $errors[] = "Surgery date cannot be in the past";
        }
    }

    if (empty($surgery_time)) {
        $errors[] = "Surgery time is required";
    } else {
        // Validate time format
        if (!preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $surgery_time)) {
            $errors[] = "Invalid time format";
        }
    }

    if (empty($surgery_duration)) {
        $errors[] = "Surgery duration is required";
    } else {
        // Validate duration format - allow numbers with optional unit
        if (!preg_match("/^[0-9.]+[\s]*(hours?|hrs?|mins?|minutes?|h|m)?$/i", trim($surgery_duration))) {
            $errors[] = "Invalid duration format. Use format like '2 Hours', '45 mins', or '1.5 hrs'";
        }
    }

    // Validate hospital location if provided
    if (!empty($hospital_location)) {
        if (strlen($hospital_location) > 255) {
            $errors[] = "Hospital location must be less than 255 characters";
        }
    }

    // Validate assistant surgeon if provided
    if (!empty($assistant_surgeon)) {
        if (strlen($assistant_surgeon) > 100) {
            $errors[] = "Assistant surgeon name must be less than 100 characters";
        }
        if (!preg_match("/^[a-zA-Z\s\-'.]+$/", $assistant_surgeon)) {
            $errors[] = "Assistant surgeon name contains invalid characters";
        }
    }

    if (empty($anesthetist)) {
        $errors[] = "Anesthetist name is required";
    } else {
        if (strlen($anesthetist) > 100) {
            $errors[] = "Anesthetist name must be less than 100 characters";
        }
        if (!preg_match("/^[a-zA-Z\s\-'.]+$/", $anesthetist)) {
            $errors[] = "Anesthetist name contains invalid characters";
        }
    }

    if (empty($surgery_type)) {
        $errors[] = "Surgery type is required";
    }

    if (empty($surgery_category)) {
        $errors[] = "Surgery category is required";
    }

    if (empty($diagnosis_before_surgery)) {
        $errors[] = "Diagnosis before surgery is required";
    } else {
        if (strlen($diagnosis_before_surgery) < 5) {
            $errors[] = "Diagnosis must be at least 5 characters long";
        }
        if (strlen($diagnosis_before_surgery) > 1000) {
            $errors[] = "Diagnosis must be less than 1000 characters";
        }
    }

    if (empty($procedure_details)) {
        $errors[] = "Procedure details are required";
    } else {
        if (strlen($procedure_details) < 5) {
            $errors[] = "Procedure details must be at least 5 characters long";
        }
        if (strlen($procedure_details) > 2000) {
            $errors[] = "Procedure details must be less than 2000 characters";
        }
    }

    // Validate findings if provided
    if (!empty($findings)) {
        if (strlen($findings) > 1000) {
            $errors[] = "Findings must be less than 1000 characters";
        }
    }

    // Validate complications if provided
    if (!empty($complications)) {
        if (strlen($complications) > 1000) {
            $errors[] = "Complications must be less than 1000 characters";
        }
    }

    // 2. Validate data types and formats
    if (!empty($blood_loss)) {
        // Check if blood loss is numeric or numeric with ml
        if (!preg_match('/^[0-9]+\s*(ml|ML|mL)?$/', trim($blood_loss))) {
            $errors[] = "Blood loss should be a number (e.g., 250 or 250 ml)";
        }
        // Check if blood loss is within reasonable range
        $blood_loss_num = intval(preg_replace('/[^0-9]/', '', $blood_loss));
        if ($blood_loss_num < 0) {
            $errors[] = "Blood loss cannot be negative";
        }
        if ($blood_loss_num > 5000) {
            $errors[] = "Blood loss seems too high (maximum 5000 ml)";
        }
    }

    if (!empty($follow_up_date)) {
        // Validate date format
        $date_parts = explode('-', $follow_up_date);
        if (count($date_parts) != 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
            $errors[] = "Invalid follow-up date format";
        }
        // Check if follow-up date is after surgery date
        if ($follow_up_date < $surgery_date) {
            $errors[] = "Follow-up date cannot be before surgery date";
        }
    }

    // Validate recovery notes if provided
    if (!empty($recovery_notes)) {
        if (strlen($recovery_notes) > 1000) {
            $errors[] = "Recovery notes must be less than 1000 characters";
        }
    }

    // Validate notes if provided
    if (!empty($notes)) {
        if (strlen($notes) > 1000) {
            $errors[] = "Notes must be less than 1000 characters";
        }
    }

    // 3. Check for duplicate surgery number
    $check_duplicate = mysqli_query($conn, "SELECT surgery_no FROM surgeries WHERE surgery_no = '$surgery_no'");
    if (mysqli_num_rows($check_duplicate) > 0) {
        $errors[] = "Surgery number already exists. Please try again.";
    }

    // 4. Check if patient exists
    $check_patient = mysqli_query($conn, "SELECT patient_id FROM patients WHERE patient_id = '$patient_id'");
    if (mysqli_num_rows($check_patient) == 0) {
        $errors[] = "Patient not found";
    }

    // 6. Validate status values
    $valid_statuses = ['Scheduled', 'Completed', 'Cancelled'];
    if (!empty($status) && !in_array($status, $valid_statuses)) {
        $errors[] = "Invalid status selected";
    }

    // 7. Validate surgery type values
    $valid_types = ['Major', 'Minor', 'Emergency', 'Elective'];
    if (!empty($surgery_type) && !in_array($surgery_type, $valid_types)) {
        $errors[] = "Invalid surgery type selected";
    }

    // 8. Validate surgery category values
    $valid_categories = ['General Surgery', 'Orthopedic', 'ENT', 'Cardiac', 'Neuro', 'Gynecology', 'Urology', 'Plastic Surgery'];
    if (!empty($surgery_category) && !in_array($surgery_category, $valid_categories)) {
        $errors[] = "Invalid surgery category selected";
    }

    // 9. Validate surgery number format
    if (!preg_match("/^SUR[0-9]{14}$/", $surgery_no)) {
        $errors[] = "Invalid surgery number format";
    }

    // --- IF NO ERRORS, INSERT DATA ---
    if (empty($errors)) {
        $insert_query = "INSERT INTO surgeries (
            patient_id,
            hospital_id,
            surgery_no,
            doctor_id,
            surgeon_name,
            surgery_title,
            surgery_full_name,
            surgery_date,
            surgery_time,
            surgery_duration,
            hospital_location,
            assistant_surgeon,
            anesthetist,
            surgery_type,
            surgery_category,
            diagnosis_before_surgery,
            procedure_details,
            findings,
            complications,
            blood_loss,
            status,
            follow_up_date,
            recovery_notes,
            notes,
            created_at
        ) VALUES (
            '$patient_id',
            '$hospital_id',
            '$surgery_no',
            '$doctor_id',
            '$surgeon_name',
            '$surgery_title',
            '$surgery_full_name',
            '$surgery_date',
            '$surgery_time',
            '$surgery_duration',
            '$hospital_location',
            '$assistant_surgeon',
            '$anesthetist',
            '$surgery_type',
            '$surgery_category',
            '$diagnosis_before_surgery',
            '$procedure_details',
            '$findings',
            '$complications',
            '$blood_loss',
            '$status',
            '$follow_up_date',
            '$recovery_notes',
            '$notes',
            NOW()
        )";

       
      if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = "Surgery added successfully!";
        header("Location: view_patient.php?id=" . $patient_id);
        exit();
    } else {
        $errors[] = "Database error: " . mysqli_error($conn);
    }
    }

    // If we have errors, store them in session
    if (!empty($errors)) {
        $_SESSION['surgery_errors'] = $errors;
        $_SESSION['surgery_form_data'] = $_POST;
        // Redirect to reload the page with errors
        header("Location: add_surgery.php?patient_id=" . $patient_id . "&error=1");
        exit();
    }
}

// --- DISPLAY PAGE ---

// Check for validation errors and display them
$errors = array();
if (isset($_SESSION['surgery_errors'])) {
    $errors = $_SESSION['surgery_errors'];
    unset($_SESSION['surgery_errors']);
}

// Get form data from session if available (for repopulating after errors)
$form_data = array();
if (isset($_SESSION['surgery_form_data'])) {
    $form_data = $_SESSION['surgery_form_data'];
    unset($_SESSION['surgery_form_data']);
}

// Check for success message
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get hospital info
$hospital_id = $_SESSION['hospital_id'];

// Get patient info for display
$patient_query = mysqli_query($conn, "SELECT patient_name FROM patients WHERE patient_id = '$patient_id'");
$patient = mysqli_fetch_assoc($patient_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($hospital['hospital_name']); ?> - Add New Surgery</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital['hospital_logo']); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Responsive main wrapper */
        .main-wrapper {
            margin-left: 250px;
            margin-top: 70px;
            padding: 20px;
            min-height: 100vh;
            background: #f9fafb;
            transition: margin-left 0.3s ease;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 10px;
                margin-top: 60px;
            }
            
            .content-container {
                padding: 0 5px;
            }
            
            .form-card {
                padding: 15px;
                border-radius: 8px;
            }
            
            /* Stack grid items on mobile */
            .grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .grid-cols-1.md\:grid-cols-3 {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            /* Make buttons full width on mobile */
            .flex.justify-end.gap-3 {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }
            
            .flex.justify-end.gap-3 a,
            .flex.justify-end.gap-3 button {
                width: 100% !important;
                text-align: center !important;
                justify-content: center !important;
            }
            
            /* Adjust form inputs for touch */
            input, select, textarea {
                font-size: 16px !important; /* Prevents zoom on iOS */
            }
            
            .flex.items-center.gap-3 {
                flex-wrap: wrap;
            }
            
            /* Adjust header text sizes */
            h1.text-2xl {
                font-size: 1.25rem !important;
            }
            
            .text-sm.text-gray-500 {
                font-size: 0.75rem !important;
            }
        }
        
        /* Tablet adjustments */
        @media (min-width: 769px) and (max-width: 1024px) {
            .main-wrapper {
                margin-left: 200px;
                padding: 15px;
            }
            
            .grid-cols-1.md\:grid-cols-2 {
                gap: 1.25rem !important;
            }
        }
        
        /* Large screens */
        @media (min-width: 1025px) {
            .content-container {
                max-width: 1200px;
            }
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 24px;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .input-error {
            border-color: #dc2626 !important;
            background-color: #fef2f2;
        }
        
        .input-success {
            border-color: #22c55e !important;
            background-color: #f0fdf4;
        }
        
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'Sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="view_patient.php?id=<?php  echo $patient_id ?>" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-100 hover:bg-blue-50 transition-all flex-shrink-0">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="flex-1 min-w-[200px]">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Add Surgery</h1>
                <p class="text-sm text-gray-500 break-words"><?php echo $hospital['hospital_name'] ?> • Patient ID: #<?php echo $patient_id ?></p>
            </div>
        </div>
                      
        <div class="content-container" style="margin-top:30px;">
            <div class="form-card">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-4 border-b gap-3">
                    <div class="w-full sm:w-auto">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Add New Surgery</h1>
                        <p class="text-sm text-gray-600 mt-1 break-words">Patient: <?php echo htmlspecialchars($patient['patient_name']); ?></p>
                    </div>
                </div>

                <!-- Display validation errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert-error">
                        <strong>Please fix the following errors:</strong>
                        <ul class="list-disc list-inside mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Display success message -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="surgeryForm" onsubmit="return validateForm()">
                    <!-- Hidden IDs -->
                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                    <input type="hidden" name="hospital_id" value="<?php echo $_SESSION['hospital_id']; ?>">

                    <!-- Responsive grid with better mobile handling -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        <!-- Surgery Number -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Number <span class="text-red-500">*</span></label>
                            <input type="text"
                                   name="surgery_no"
                                   value="SUR<?php echo date('YmdHis'); ?>"
                                   readonly
                                   class="w-full border rounded-lg px-4 py-2 bg-gray-100">
                        </div>

                        <!-- Doctor -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgeon <span class="text-red-500">*</span></label>
                           <select name="doctor_id" id="doctor_id" onchange="setDoctorName()" required class="w-full border rounded-lg px-4 py-2 appearance-none bg-white">
                                <option value="">Select Surgeon</option>
                                <?php
                                $doctor = mysqli_query($conn,"
                                    SELECT doctor_id, doctor_name
                                    FROM doctor
                                    WHERE status='Active'
                                    AND hospital_id='$hid'
                                    AND delete_flag='0'
                                    ORDER BY doctor_name ASC
                                ");
                                while($doc = mysqli_fetch_assoc($doctor)){
                                    $selected = (isset($form_data['doctor_id']) && $form_data['doctor_id'] == $doc['doctor_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $doc['doctor_id']; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($doc['doctor_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <div class="error-message" id="doctor_error">Please select a surgeon</div>
                        </div>

                        <!-- Surgery Title -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Title <span class="text-red-500">*</span></label>
                            <input type="text"
                                   name="surgery_title"
                                   id="surgery_title"
                                   class="w-full border rounded-lg px-4 py-2"
                                   placeholder="Appendectomy"
                                   value="<?php echo isset($form_data['surgery_title']) ? htmlspecialchars($form_data['surgery_title']) : ''; ?>"
                                   required>
                            <div class="error-message" id="title_error">Surgery title is required (minimum 3 characters)</div>
                        </div>

                        <!-- Surgery Full Name -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Full Name</label>
                            <input type="text"
                                   name="surgery_full_name"
                                   id="surgery_full_name"
                                   class="w-full border rounded-lg px-4 py-2"
                                   placeholder="Laparoscopic Appendectomy"
                                   value="<?php echo isset($form_data['surgery_full_name']) ? htmlspecialchars($form_data['surgery_full_name']) : ''; ?>">
                            <div class="error-message" id="fullname_error">Invalid characters in surgery full name</div>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Date <span class="text-red-500">*</span></label>
                            <input type="date"
                                   name="surgery_date"
                                   id="surgery_date"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['surgery_date']) ? htmlspecialchars($form_data['surgery_date']) : ''; ?>"
                                   required>
                            <div class="error-message" id="date_error">Please select a valid surgery date</div>
                        </div>

                        <!-- Time -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Time <span class="text-red-500">*</span></label>
                            <input type="time"
                                   name="surgery_time"
                                   id="surgery_time"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['surgery_time']) ? htmlspecialchars($form_data['surgery_time']) : ''; ?>"
                                   required>
                            <div class="error-message" id="time_error">Please select a valid surgery time</div>
                        </div>

                        <!-- Duration -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Duration <span class="text-red-500">*</span></label>
                            <input type="text"
                                   name="surgery_duration"
                                   id="surgery_duration"
                                   placeholder="2 Hours"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['surgery_duration']) ? htmlspecialchars($form_data['surgery_duration']) : ''; ?>"
                                   required>
                            <div class="error-message" id="duration_error">Please enter a valid duration (e.g., 2 Hours, 45 mins)</div>
                        </div>

                        <!-- Hospital -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Hospital Location</label>
                            <input type="text"
                                   name="hospital_location"
                                   value="<?php echo htmlspecialchars($hospital['hospital_name']); ?>"
                                   class="w-full border rounded-lg px-4 py-2 bg-gray-50">
                        </div>

                        <!-- Assistant -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Assistant Surgeon</label>
                            <input type="text"
                                   name="assistant_surgeon"
                                   id="assistant_surgeon"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['assistant_surgeon']) ? htmlspecialchars($form_data['assistant_surgeon']) : ''; ?>">
                            <div class="error-message" id="assistant_error">Invalid characters in assistant surgeon name</div>
                        </div>

                        <!-- Anesthetist -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Anesthetist <span class="text-red-500">*</span></label>
                            <input type="text"
                                   name="anesthetist"
                                   id="anesthetist"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['anesthetist']) ? htmlspecialchars($form_data['anesthetist']) : ''; ?>"
                                   required>
                            <div class="error-message" id="anesthetist_error">Anesthetist name is required</div>
                        </div>

                        <!-- Surgery Type -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Type <span class="text-red-500">*</span></label>
                            <select name="surgery_type" id="surgery_type" required class="w-full border rounded-lg px-4 py-2 appearance-none bg-white">
                                <option value="">Select</option>
                                <?php
                                $types = ['Major', 'Minor', 'Emergency', 'Elective'];
                                foreach ($types as $type) {
                                    $selected = (isset($form_data['surgery_type']) && $form_data['surgery_type'] == $type) ? 'selected' : '';
                                    echo "<option value='$type' $selected>$type</option>";
                                }
                                ?>
                            </select>
                            <div class="error-message" id="type_error">Please select a surgery type</div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Surgery Category <span class="text-red-500">*</span></label>
                            <select name="surgery_category" id="surgery_category" required class="w-full border rounded-lg px-4 py-2 appearance-none bg-white">
                                <option value="">Select</option>
                                <?php
                                $categories = ['General Surgery', 'Orthopedic', 'ENT', 'Cardiac', 'Neuro', 'Gynecology', 'Urology', 'Plastic Surgery'];
                                foreach ($categories as $category) {
                                    $selected = (isset($form_data['surgery_category']) && $form_data['surgery_category'] == $category) ? 'selected' : '';
                                    echo "<option value='$category' $selected>$category</option>";
                                }
                                ?>
                            </select>
                            <div class="error-message" id="category_error">Please select a surgery category</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium mb-2">Diagnosis Before Surgery <span class="text-red-500">*</span></label>
                        <textarea name="diagnosis_before_surgery"
                                  id="diagnosis_before_surgery"
                                  rows="3"
                                  class="w-full border rounded-lg p-3"
                                  required><?php echo isset($form_data['diagnosis_before_surgery']) ? htmlspecialchars($form_data['diagnosis_before_surgery']) : ''; ?></textarea>
                        <div class="error-message" id="diagnosis_error">Diagnosis before surgery is required (minimum 5 characters)</div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">Procedure Details <span class="text-red-500">*</span></label>
                        <textarea name="procedure_details"
                                  id="procedure_details"
                                  rows="4"
                                  class="w-full border rounded-lg p-3"
                                  required><?php echo isset($form_data['procedure_details']) ? htmlspecialchars($form_data['procedure_details']) : ''; ?></textarea>
                        <div class="error-message" id="procedure_error">Procedure details are required (minimum 5 characters)</div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">Findings</label>
                        <textarea name="findings"
                                  id="findings"
                                  rows="3"
                                  class="w-full border rounded-lg p-3"><?php echo isset($form_data['findings']) ? htmlspecialchars($form_data['findings']) : ''; ?></textarea>
                        <div class="error-message" id="findings_error">Findings must be less than 1000 characters</div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">Complications</label>
                        <textarea name="complications"
                                  id="complications"
                                  rows="3"
                                  class="w-full border rounded-lg p-3"><?php echo isset($form_data['complications']) ? htmlspecialchars($form_data['complications']) : ''; ?></textarea>
                        <div class="error-message" id="complications_error">Complications must be less than 1000 characters</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Blood Loss</label>
                            <input type="text"
                                   name="blood_loss"
                                   id="blood_loss"
                                   placeholder="250 ml"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['blood_loss']) ? htmlspecialchars($form_data['blood_loss']) : ''; ?>">
                            <div class="error-message" id="bloodloss_error">Enter a number (e.g., 250 or 250 ml)</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Status <span class="text-red-500">*</span></label>
                            <select name="status" id="status" required class="w-full border rounded-lg px-4 py-2 appearance-none bg-white">
                                <option value="">Select Status</option>
                                <?php
                                $statuses = ['Scheduled', 'Completed', 'Cancelled'];
                                foreach ($statuses as $stat) {
                                    $selected = (isset($form_data['status']) && $form_data['status'] == $stat) ? 'selected' : '';
                                    echo "<option value='$stat' $selected>$stat</option>";
                                }
                                ?>
                            </select>
                            <div class="error-message" id="status_error">Please select a status</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Follow-up Date</label>
                            <input type="date"
                                   name="follow_up_date"
                                   id="follow_up_date"
                                   class="w-full border rounded-lg px-4 py-2"
                                   value="<?php echo isset($form_data['follow_up_date']) ? htmlspecialchars($form_data['follow_up_date']) : ''; ?>">
                            <div class="error-message" id="followup_error">Follow-up date cannot be before surgery date</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">Recovery Notes</label>
                        <textarea name="recovery_notes"
                                  id="recovery_notes"
                                  rows="3"
                                  class="w-full border rounded-lg p-3"><?php echo isset($form_data['recovery_notes']) ? htmlspecialchars($form_data['recovery_notes']) : ''; ?></textarea>
                        <div class="error-message" id="recovery_error">Recovery notes must be less than 1000 characters</div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">Notes</label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="w-full border rounded-lg p-3"><?php echo isset($form_data['notes']) ? htmlspecialchars($form_data['notes']) : ''; ?></textarea>
                        <div class="error-message" id="notes_error">Notes must be less than 1000 characters</div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row justify-end gap-3">
                        <a href="view_patient.php?id=<?php  echo $patient_id ?>"
                           class="px-6 py-2 border rounded-lg hover:bg-gray-50 text-center order-2 sm:order-1">
                            Cancel
                        </a>
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-lg order-1 sm:order-2">
                            Save Surgery
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Real-time validation for each field
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners for all required fields
        const requiredFields = [
            { id: 'doctor_id', errorId: 'doctor_error' },
            { id: 'surgery_title', errorId: 'title_error' },
            { id: 'surgery_date', errorId: 'date_error' },
            { id: 'surgery_time', errorId: 'time_error' },
            { id: 'surgery_duration', errorId: 'duration_error' },
            { id: 'anesthetist', errorId: 'anesthetist_error' },
            { id: 'surgery_type', errorId: 'type_error' },
            { id: 'surgery_category', errorId: 'category_error' },
            { id: 'diagnosis_before_surgery', errorId: 'diagnosis_error' },
            { id: 'procedure_details', errorId: 'procedure_error' },
            { id: 'status', errorId: 'status_error' }
        ];

        // Additional fields with validation
        const optionalFields = [
            { id: 'surgery_full_name', errorId: 'fullname_error' },
            { id: 'assistant_surgeon', errorId: 'assistant_error' },
            { id: 'blood_loss', errorId: 'bloodloss_error' },
            { id: 'findings', errorId: 'findings_error' },
            { id: 'complications', errorId: 'complications_error' },
            { id: 'follow_up_date', errorId: 'followup_error' },
            { id: 'recovery_notes', errorId: 'recovery_error' },
            { id: 'notes', errorId: 'notes_error' }
        ];

        requiredFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                element.addEventListener('blur', function() {
                    validateField(field.id, field.errorId);
                });
                element.addEventListener('change', function() {
                    validateField(field.id, field.errorId);
                });
                element.addEventListener('input', function() {
                    if (element.value.trim() !== '') {
                        validateField(field.id, field.errorId);
                    }
                });
            }
        });

        optionalFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                element.addEventListener('blur', function() {
                    validateOptionalField(field.id, field.errorId);
                });
                element.addEventListener('change', function() {
                    validateOptionalField(field.id, field.errorId);
                });
                element.addEventListener('input', function() {
                    if (element.value.trim() !== '') {
                        validateOptionalField(field.id, field.errorId);
                    }
                });
            }
        });

        // Follow-up date validation
        const followUpDate = document.getElementById('follow_up_date');
        const surgeryDate = document.getElementById('surgery_date');
        
        if (followUpDate && surgeryDate) {
            followUpDate.addEventListener('change', function() {
                if (this.value && surgeryDate.value) {
                    if (this.value < surgeryDate.value) {
                        document.getElementById('followup_error').classList.add('show');
                        this.classList.add('input-error');
                    } else {
                        document.getElementById('followup_error').classList.remove('show');
                        this.classList.remove('input-error');
                        this.classList.add('input-success');
                    }
                }
            });
        }

        // Blood loss validation
        const bloodLoss = document.getElementById('blood_loss');
        if (bloodLoss) {
            bloodLoss.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    const validPattern = /^[0-9]+\s*(ml|ML|mL)?$/;
                    const error = document.getElementById('bloodloss_error');
                    if (!validPattern.test(this.value.trim())) {
                        error.classList.add('show');
                        this.classList.add('input-error');
                        this.classList.remove('input-success');
                    } else {
                        const numValue = parseInt(this.value.replace(/[^0-9]/g, ''));
                        if (numValue > 5000) {
                            error.textContent = 'Blood loss seems too high (maximum 5000 ml)';
                            error.classList.add('show');
                            this.classList.add('input-error');
                            this.classList.remove('input-success');
                        } else {
                            error.classList.remove('show');
                            this.classList.remove('input-error');
                            this.classList.add('input-success');
                        }
                    }
                }
            });
        }
    });

    function validateField(fieldId, errorId) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(errorId);
        
        if (!field || !error) return false;

        let isValid = false;
        
        if (field.tagName === 'SELECT') {
            isValid = field.value !== '';
        } else if (field.type === 'date' || field.type === 'time') {
            isValid = field.value !== '';
        } else if (fieldId === 'surgery_title') {
            isValid = field.value.trim() !== '' && field.value.trim().length >= 3;
        } else if (fieldId === 'diagnosis_before_surgery' || fieldId === 'procedure_details') {
            isValid = field.value.trim() !== '' && field.value.trim().length >= 5;
        } else if (fieldId === 'surgery_duration') {
            const value = field.value.trim();
            if (value === '') {
                isValid = false;
            } else {
                const validPattern = /^[0-9.]+[\s]*(hours?|hrs?|mins?|minutes?|h|m)?$/i;
                isValid = validPattern.test(value);
            }
        } else {
            isValid = field.value.trim() !== '';
        }

        if (isValid) {
            field.classList.remove('input-error');
            field.classList.add('input-success');
            error.classList.remove('show');
            return true;
        } else {
            field.classList.remove('input-success');
            field.classList.add('input-error');
            error.classList.add('show');
            return false;
        }
    }

    function validateOptionalField(fieldId, errorId) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(errorId);
        
        if (!field || !error) return true;

        const value = field.value.trim();
        
        // If field is empty, it's valid (optional)
        if (value === '') {
            field.classList.remove('input-error', 'input-success');
            error.classList.remove('show');
            return true;
        }

        let isValid = true;

        switch(fieldId) {
            case 'surgery_full_name':
                isValid = /^[a-zA-Z0-9\s\-',.]+$/.test(value) && value.length <= 255;
                break;
            case 'assistant_surgeon':
                isValid = /^[a-zA-Z\s\-'.]+$/.test(value) && value.length <= 100;
                break;
            case 'blood_loss':
                const validPattern = /^[0-9]+\s*(ml|ML|mL)?$/;
                if (validPattern.test(value)) {
                    const numValue = parseInt(value.replace(/[^0-9]/g, ''));
                    isValid = numValue <= 5000;
                } else {
                    isValid = false;
                }
                break;
            case 'findings':
            case 'complications':
            case 'recovery_notes':
            case 'notes':
                isValid = value.length <= 1000;
                break;
            case 'follow_up_date':
                const surgeryDate = document.getElementById('surgery_date');
                if (surgeryDate && surgeryDate.value) {
                    isValid = value >= surgeryDate.value;
                }
                break;
        }

        if (isValid) {
            field.classList.remove('input-error');
            field.classList.add('input-success');
            error.classList.remove('show');
            return true;
        } else {
            field.classList.remove('input-success');
            field.classList.add('input-error');
            error.classList.add('show');
            return false;
        }
    }

    function validateForm() {
        let isValid = true;

        // Validate all required fields
        const fields = [
            { id: 'doctor_id', errorId: 'doctor_error' },
            { id: 'surgery_title', errorId: 'title_error' },
            { id: 'surgery_date', errorId: 'date_error' },
            { id: 'surgery_time', errorId: 'time_error' },
            { id: 'surgery_duration', errorId: 'duration_error' },
            { id: 'anesthetist', errorId: 'anesthetist_error' },
            { id: 'surgery_type', errorId: 'type_error' },
            { id: 'surgery_category', errorId: 'category_error' },
            { id: 'diagnosis_before_surgery', errorId: 'diagnosis_error' },
            { id: 'procedure_details', errorId: 'procedure_error' },
            { id: 'status', errorId: 'status_error' }
        ];

        fields.forEach(field => {
            if (!validateField(field.id, field.errorId)) {
                isValid = false;
            }
        });

        // Validate optional fields
        const optionalFields = [
            { id: 'surgery_full_name', errorId: 'fullname_error' },
            { id: 'assistant_surgeon', errorId: 'assistant_error' },
            { id: 'blood_loss', errorId: 'bloodloss_error' },
            { id: 'findings', errorId: 'findings_error' },
            { id: 'complications', errorId: 'complications_error' },
            { id: 'follow_up_date', errorId: 'followup_error' },
            { id: 'recovery_notes', errorId: 'recovery_error' },
            { id: 'notes', errorId: 'notes_error' }
        ];

        optionalFields.forEach(field => {
            if (!validateOptionalField(field.id, field.errorId)) {
                isValid = false;
            }
        });

        if (!isValid) {
            // Scroll to the first error
            const firstError = document.querySelector('.input-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            return false;
        }

        return true;
    }

    // Clear error on focus
    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('focus', function() {
            this.classList.remove('input-error');
            this.classList.remove('input-success');
            // Hide associated error message
            const errorId = this.id + '_error';
            const error = document.getElementById(errorId);
            if (error) {
                error.classList.remove('show');
            }
        });
    });

    function setDoctorName() {
        // This function is called from onchange of doctor select
        // It's a placeholder for any additional logic needed
    }
    </script>

    <script>
    lucide.createIcons();
</script>
</body>
</html>