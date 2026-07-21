<?php

session_start(); 
include "config/hospital.php";
require_once "config/send_registration_email.php";

$hid=$_SESSION["hospital_id"];

$image_path = "";
$message = "";
$messageType = "";

$doctorsQuery = "SELECT doctor_id, doctor_name, department FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid' ORDER BY doctor_name ASC";
$doctorsResult = $conn->query($doctorsQuery);
$doctors = array();
if ($doctorsResult && $doctorsResult->num_rows > 0) {
    while ($row = $doctorsResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

if (isset($_POST['email'])) {
    
    $patient_name = $_POST['patient_name'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $blood_group = $_POST['blood_group'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $emergency_contact = $_POST['emergency_contact'];
    $medical_history = $_POST['medical_history'];
    $allergy = $_POST['allergy'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $status = 'Active';
    $doctor_id = isset($_POST['doctor_id']) && $_POST['doctor_id'] != '' ? $_POST['doctor_id'] : NULL;
    $password = $_POST['password'];

    // Server-side Validation with Regex
    if (empty($patient_name) || empty($email) || empty($password)) {
        $message = "Please fill all required fields.";
        $messageType = "error";
    } elseif (!preg_match('/^[A-Za-z\s\'-]+$/', $patient_name)) {
        $message = "Invalid Patient Name. Only letters, spaces, hyphens, and apostrophes are allowed.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid Email Address.";
        $messageType = "error";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$/', $password)) {
        $message = "Invalid Password. Must be at least 8 characters with uppercase, lowercase, number, and special character.";
        $messageType = "error";
    } elseif (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = "Invalid Mobile Number. Must be exactly 10 digits.";
        $messageType = "error";
    } elseif (!empty($emergency_contact) && !preg_match('/^[0-9]{10}$/', $emergency_contact)) {
        $message = "Invalid Emergency Contact. Must be exactly 10 digits.";
        $messageType = "error";
    } elseif (!empty($age) && (!is_numeric($age) || $age < 0 || $age > 150)) {
        $message = "Invalid Age. Must be between 0 and 150.";
        $messageType = "error";
    } elseif (!empty($address) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $address)) {
        $message = "Invalid Address. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
        $messageType = "error";
    } elseif (!empty($medical_history) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $medical_history)) {
        $message = "Invalid Medical History. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
        $messageType = "error";
    } elseif (!empty($allergy) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $allergy)) {
        $message = "Invalid Allergy. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
        $messageType = "error";
    } elseif (!empty($blood_group) && !in_array($blood_group, ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])) {
        $message = "Invalid Blood Group selected.";
        $messageType = "error";
    } elseif (!empty($gender) && !in_array($gender, ['Male', 'Female', 'Other'])) {
        $message = "Invalid Gender selected.";
        $messageType = "error";
    } else {

        // Check if email already exists
        $check_sql = "SELECT * FROM register WHERE email=?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already exists.";
            $messageType = "error";
        } else {

            $register = "INSERT INTO register(name, email, password, role, created_by, modified_by, hospital_id) VALUES('$patient_name','$email','$password','patient','Admin','Admin','$hid')";

            if($conn->query($register)){
                $register_id = $conn->insert_id;

                $folder = "documents/patients/images/";
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                }

                $image_name = "";
                if (!empty($_FILES['patient_image']['name']) && $_FILES['patient_image']['error'] == 0) {
                    $image_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['patient_image']['name']));
                    $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                    $allowed = array("jpg", "jpeg", "png", "gif", "webp");
                    
                    if (in_array($ext, $allowed)) {
                        if (move_uploaded_file($_FILES['patient_image']['tmp_name'], $folder . $image_name)) {
                            $image_path = "documents/patients/images/" . $image_name;
                        }
                    }
                }
                
                $insert = "INSERT INTO patients(register_id, doctor_id, patient_name, date_of_birth, age, blood_group, gender, address, emergency_contact, medical_history, allergy, email, mobile, status, patient_image, delete_flag, hospital_id,patient_admission_type) VALUES('$register_id', " . ($doctor_id ? "'$doctor_id'" : "NULL") . ", '$patient_name','$dob','$age','$blood_group','$gender','$address','$emergency_contact','$medical_history','$allergy','$email','$mobile','$status','$image_path',0,'$hid','OPD')";

                if ($conn->query($insert) === true) {
                    $patient_id = $conn->insert_id;

                    $message = "
                    Congratulations! Your patient account has been created successfully in
                    <strong>{$hospital['hospital_name']}</strong>.
                    ";

                    sendRegistrationEmail(
                        $conn,
                        $hid,
                        $patient_name,
                        $email,
                        $password,
                        $message
                    );
                    
                    if(isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
                        $document_name = $_POST['document_name'];
                        $document_type = $_POST['document_type'];
                        $note = $_POST['document_note'];
                        $document_date = $_POST['document_date'];
                
                        $upload_dir = "../documents/patients/document/";
                        $file_name = $_FILES['document_file']['name'];
                        $upload_file = $upload_dir . $file_name;
                        
                    } else {
                        echo "<script>
                            alert('Patient added successfully');
                            window.location='patients.php';
                        </script>";
                        exit();
                    }
                } else {
                    echo "<script>alert('Unable to add patient. Error: " . $conn->error . "')</script>";
                }
            } else {
                die("Register Error : " . $conn->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Add OPD Patient</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            background-color: #f3f4f6;
            color: #111827;
        }
        .step-active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
        }
        .step-inactive {
            color: #6b7280;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        
        /* Validation Styles */
        .input-wrapper {
            position: relative;
        }
        .input-wrapper .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .input-wrapper .input-icon.valid { color: #22c55e; opacity: 1; }
        .input-wrapper .input-icon.invalid { color: #ef4444; opacity: 1; }
        
        .field-group input.error, .field-group select.error, .field-group textarea.error { 
            border-color: #ef4444 !important; 
            background-color: #fef2f2 !important; 
        }
        .field-group input.success, .field-group select.success, .field-group textarea.success { 
            border-color: #22c55e !important; 
            background-color: #f0fdf4 !important; 
        }
        
        .validation-message {
            font-size: 11px;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        .validation-message.show { display: flex; }
        .validation-message.error { color: #ef4444; }
        .validation-message.success { color: #22c55e; }
        
        .validation-hint {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
            display: block;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .password-strength .strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .password-strength .strength-bar.weak { width: 25%; background: #ef4444; }
        .password-strength .strength-bar.fair { width: 50%; background: #f59e0b; }
        .password-strength .strength-bar.good { width: 75%; background: #3b82f6; }
        .password-strength .strength-bar.strong { width: 100%; background: #22c55e; }
        
        .strength-text {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        .password-requirements {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .password-requirements .req-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .password-requirements .req-item .req-icon { font-size: 10px; }
        .password-requirements .req-item.met { color: #22c55e; }
        .password-requirements .req-item.unmet { color: #9ca3af; }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50" >
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="patients.php" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Add OPD Patient</h1>
                            <p class="text-gray-500 text-sm">Complete the following forms to register a new patient in the system.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="p-4 mb-6 rounded-lg <?php echo ($messageType == 'error') ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'; ?>">
                            <div class="flex items-center gap-3">
                                <i class="fas <?php echo ($messageType == 'error') ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                                <span class="font-medium"><?php echo $message; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex border-b mb-8 overflow-x-auto custom-scrollbar">
                        <button onclick="showSection('personal')" type="button" id="btn-personal"
                            class="px-6 py-3 text-sm font-medium whitespace-nowrap step-active">
                            Personal Information
                        </button>
                    </div>

                    <form action="add_patient.php" method="POST" enctype="multipart/form-data" id="patientForm" novalidate>

                        <div class="bg-white rounded-xl border shadow-sm p-6 md:p-8">

                            <div id="section-personal" class="form-section active">
                                <h2 class="text-lg font-semibold mb-6">Personal Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="patient_name">Full Name <span class="text-red-500">*</span></label>
                                        <div class="input-wrapper">
                                            <input id="patient_name" name="patient_name" placeholder="Enter full name"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required pattern="^[A-Za-z\s\-\'\\]+$"
                                                data-validation="name"
                                                title="Only letters, spaces, hyphens, and apostrophes are allowed.">
                                            <i class="fas fa-check-circle input-icon" id="patient_name_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="patient_name_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Only letters, spaces, hyphens, and apostrophes are allowed.</span>
                                        </div>
                                        <div class="validation-message success" id="patient_name_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid name format</span>
                                        </div>
                                        <small class="validation-hint">Only letters, spaces, hyphens, and apostrophes</small>
                                    </div>
                                    
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="doctor_id">Doctor</label>
                                        <select id="doctor_id" name="doctor_id"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="">Select Doctor (Optional)</option>
                                            <?php foreach($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?> - <?php echo htmlspecialchars($doctor['department']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-500">Select the primary doctor for this patient</p>
                                    </div>

                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="dob">Date of Birth</label>
                                        <input id="dob" type="date" name="dob"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                    </div>
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="age">Age</label>
                                        <div class="input-wrapper">
                                            <input id="age" type="number" name="age" placeholder="Enter age"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                min="0" max="150"
                                                data-validation="age"
                                                title="Age must be between 0 and 150">
                                            <i class="fas fa-check-circle input-icon" id="age_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="age_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Age must be between 0 and 150</span>
                                        </div>
                                        <div class="validation-message success" id="age_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid age</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="blood_group">Blood Group</label>
                                        <select id="blood_group" name="blood_group"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="">Select Blood Group</option>
                                            <option>A+</option>
                                            <option>A-</option>
                                            <option>B+</option>
                                            <option>B-</option>
                                            <option>O+</option>
                                            <option>O-</option>
                                            <option>AB+</option>
                                            <option>AB-</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="gender">Gender</label>
                                        <select id="gender" name="gender"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="">Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="emergency_contact">Emergency Contact</label>
                                        <div class="input-wrapper">
                                            <input id="emergency_contact" name="emergency_contact" placeholder="Enter emergency contact number"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                pattern="[0-9]{10}" maxlength="10" minlength="10"
                                                data-validation="emergency"
                                                title="Please enter exactly 10 digits">
                                            <i class="fas fa-check-circle input-icon" id="emergency_contact_icon"></i>
                                        </div>
                                        <div class="validation-message error" id="emergency_contact_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Please enter exactly 10 digits (0-9)</span>
                                        </div>
                                        <div class="validation-message success" id="emergency_contact_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid 10-digit number</span>
                                        </div>
                                        <small class="validation-hint">Enter exactly 10 digits (e.g., 9876543210)</small>
                                    </div>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div class="space-y-2 field-group">
                                        <label class="text-sm font-medium" for="address">Address</label>
                                        <div class="input-wrapper">
                                            <textarea id="address" name="address" placeholder="Enter address"
                                                class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                pattern="^[A-Za-z0-9\s\-\.,#\/]*$"
                                                data-validation="address"></textarea>
                                        </div>
                                        <div class="validation-message error" id="address_error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.</span>
                                        </div>
                                        <div class="validation-message success" id="address_success">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Valid address format</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2 field-group">
                                            <label class="text-sm font-medium" for="email">Email <span class="text-red-500">*</span></label>
                                            <div class="input-wrapper">
                                                <input id="email" type="email" name="email" placeholder="Enter email address"
                                                    class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                                    data-validation="email"
                                                    title="Please enter a valid email address">
                                                <i class="fas fa-check-circle input-icon" id="email_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="email_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Please enter a valid email address (e.g., patient@hospital.com)</span>
                                            </div>
                                            <div class="validation-message success" id="email_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid email address</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 field-group">
                                            <label class="text-sm font-medium">Password <span class="text-red-500">*</span></label>
                                            <div class="input-wrapper">
                                                <input type="password" name="password" id="password" placeholder="Enter Login Password"
                                                    class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                                    data-validation="password"
                                                    title="Password must be at least 8 characters with uppercase, lowercase, number, and special character">
                                                <i class="fas fa-check-circle input-icon" id="password_icon"></i>
                                            </div>
                                            <div class="password-strength">
                                                <div class="strength-bar" id="strengthBar"></div>
                                            </div>
                                            <div class="strength-text" id="strengthText">Weak</div>
                                            <div class="password-requirements">
                                                <span class="req-item unmet" id="reqLength">
                                                    <i class="fas fa-times req-icon"></i> 8+ characters
                                                </span>
                                                <span class="req-item unmet" id="reqUpper">
                                                    <i class="fas fa-times req-icon"></i> Uppercase
                                                </span>
                                                <span class="req-item unmet" id="reqLower">
                                                    <i class="fas fa-times req-icon"></i> Lowercase
                                                </span>
                                                <span class="req-item unmet" id="reqNumber">
                                                    <i class="fas fa-times req-icon"></i> Number
                                                </span>
                                                <span class="req-item unmet" id="reqSpecial">
                                                    <i class="fas fa-times req-icon"></i> Special char
                                                </span>
                                            </div>
                                            <div class="validation-message error" id="password_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Must be at least 8 characters with uppercase, lowercase, number & special character</span>
                                            </div>
                                            <div class="validation-message success" id="password_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Strong password</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 field-group">
                                            <label class="text-sm font-medium" for="mobile">Mobile Number</label>
                                            <div class="input-wrapper">
                                                <input id="mobile" name="mobile" placeholder="Enter mobile number"
                                                    class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    pattern="[0-9]{10}" maxlength="10" minlength="10"
                                                    data-validation="mobile"
                                                    title="Please enter exactly 10 digits">
                                                <i class="fas fa-check-circle input-icon" id="mobile_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="mobile_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Please enter exactly 10 digits (0-9)</span>
                                            </div>
                                            <div class="validation-message success" id="mobile_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid 10-digit mobile number</span>
                                            </div>
                                            <small class="validation-hint">Enter exactly 10 digits (e.g., 9876543210)</small>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2 field-group">
                                            <label class="text-sm font-medium" for="medical_history">Medical History</label>
                                            <div class="input-wrapper">
                                                <textarea id="medical_history" name="medical_history" placeholder="Previous medical conditions"
                                                    class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    pattern="^[A-Za-z0-9\s\-\.,#\/]*$"
                                                    data-validation="medical"></textarea>
                                            </div>
                                            <div class="validation-message error" id="medical_history_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="medical_history_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid format</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 field-group">
                                            <label class="text-sm font-medium" for="allergy">Allergies</label>
                                            <div class="input-wrapper">
                                                <textarea id="allergy" name="allergy" placeholder="Known allergies"
                                                    class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    pattern="^[A-Za-z0-9\s\-\.,#\/]*$"
                                                    data-validation="allergy"></textarea>
                                            </div>
                                            <div class="validation-message error" id="allergy_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="allergy_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid format</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Patient Image</label>
                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                                            <input type="file" name="patient_image" accept="image/*">
                                            <p id="image_file_name" class="mt-2 text-sm text-green-600"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="submit"
                                    class="bg-blue-600 text-white px-8 py-2 rounded-md font-semibold hover:bg-blue-700 shadow-md transition">Submit
                                    Patient</button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        document.querySelector('input[name="patient_image"]').addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('image_file_name').innerHTML = '📄 ' + this.files[0].name;
            }
        });

        // ============================================================
        // VALIDATION LOGIC
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Define validation patterns
            const patterns = {
                name: /^[A-Za-z\s\-\'\\]+$/,
                email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                mobile: /^[0-9]{10}$/,
                password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
                address: /^[A-Za-z0-9\s\-\.,#\/]*$/,
                medical: /^[A-Za-z0-9\s\-\.,#\/]*$/,
                allergy: /^[A-Za-z0-9\s\-\.,#\/]*$/,
                emergency: /^[0-9]{10}$/,
                age: /^[0-9]+$/
            };

            // Get all fields that need validation
            const fields = {
                patient_name: { pattern: patterns.name, required: true },
                email: { pattern: patterns.email, required: true },
                mobile: { pattern: patterns.mobile, required: false },
                password: { pattern: patterns.password, required: true },
                address: { pattern: patterns.address, required: false },
                medical_history: { pattern: patterns.medical, required: false },
                allergy: { pattern: patterns.allergy, required: false },
                emergency_contact: { pattern: patterns.emergency, required: false },
                age: { pattern: patterns.age, required: false }
            };

            // Function to validate a single field
            function validateField(fieldId) {
                const input = document.getElementById(fieldId);
                if (!input) return true;

                const value = input.value.trim();
                const fieldConfig = fields[fieldId];
                const isRequired = fieldConfig ? fieldConfig.required : false;
                const pattern = fieldConfig ? fieldConfig.pattern : null;

                const errorMsg = document.getElementById(fieldId + '_error');
                const successMsg = document.getElementById(fieldId + '_success');
                const icon = document.getElementById(fieldId + '_icon');

                // Reset states
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }

                // Check if empty and required
                if (isRequired && value === '') {
                    input.classList.add('error');
                    if (errorMsg) errorMsg.classList.add('show');
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // If optional and empty, it's valid
                if (!isRequired && value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }

                // Test against pattern
                if (pattern && !pattern.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) errorMsg.classList.add('show');
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // Special validation for age
                if (fieldId === 'age' && value) {
                    const ageNum = parseInt(value);
                    if (isNaN(ageNum) || ageNum < 0 || ageNum > 150) {
                        input.classList.add('error');
                        if (errorMsg) errorMsg.classList.add('show');
                        if (icon) icon.classList.add('invalid');
                        return false;
                    }
                }

                // All validations passed
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Mobile number validation - exactly 10 digits
            function validateMobile(input) {
                const value = input.value.trim();
                const errorMsg = document.getElementById('mobile_error');
                const successMsg = document.getElementById('mobile_success');
                const icon = document.getElementById('mobile_icon');
                
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }
                
                if (value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }
                
                const mobileRegex = /^[0-9]{10}$/;
                if (!mobileRegex.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) {
                        if (value.length > 0 && value.length < 10) {
                            errorMsg.querySelector('span').textContent = 'Please enter exactly 10 digits (currently ' + value.length + ' digits)';
                        } else if (value.length > 10) {
                            errorMsg.querySelector('span').textContent = 'Maximum 10 digits allowed (currently ' + value.length + ' digits)';
                        } else {
                            errorMsg.querySelector('span').textContent = 'Please enter exactly 10 digits (0-9 only)';
                        }
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }
                
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Emergency Contact validation
            function validateEmergency(input) {
                const value = input.value.trim();
                const errorMsg = document.getElementById('emergency_contact_error');
                const successMsg = document.getElementById('emergency_contact_success');
                const icon = document.getElementById('emergency_contact_icon');
                
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }
                
                if (value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }
                
                const emergencyRegex = /^[0-9]{10}$/;
                if (!emergencyRegex.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) {
                        if (value.length > 0 && value.length < 10) {
                            errorMsg.querySelector('span').textContent = 'Please enter exactly 10 digits (currently ' + value.length + ' digits)';
                        } else if (value.length > 10) {
                            errorMsg.querySelector('span').textContent = 'Maximum 10 digits allowed (currently ' + value.length + ' digits)';
                        } else {
                            errorMsg.querySelector('span').textContent = 'Please enter exactly 10 digits (0-9 only)';
                        }
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }
                
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Password strength checker
            function checkPasswordStrength(password) {
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');
                
                let score = 0;
                const checks = {
                    length: password.length >= 8,
                    upper: /[A-Z]/.test(password),
                    lower: /[a-z]/.test(password),
                    number: /\d/.test(password),
                    special: /[@$!%*?&]/.test(password)
                };

                document.getElementById('reqLength').className = `req-item ${checks.length ? 'met' : 'unmet'}`;
                document.getElementById('reqUpper').className = `req-item ${checks.upper ? 'met' : 'unmet'}`;
                document.getElementById('reqLower').className = `req-item ${checks.lower ? 'met' : 'unmet'}`;
                document.getElementById('reqNumber').className = `req-item ${checks.number ? 'met' : 'unmet'}`;
                document.getElementById('reqSpecial').className = `req-item ${checks.special ? 'met' : 'unmet'}`;

                if (checks.length) score++;
                if (checks.upper) score++;
                if (checks.lower) score++;
                if (checks.number) score++;
                if (checks.special) score++;

                if (password.length === 0) {
                    strengthBar.className = 'strength-bar';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#9ca3af';
                    return;
                }

                if (score <= 2) {
                    strengthBar.className = 'strength-bar weak';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#ef4444';
                } else if (score === 3) {
                    strengthBar.className = 'strength-bar fair';
                    strengthText.textContent = 'Fair';
                    strengthText.style.color = '#f59e0b';
                } else if (score === 4) {
                    strengthBar.className = 'strength-bar good';
                    strengthText.textContent = 'Good';
                    strengthText.style.color = '#3b82f6';
                } else {
                    strengthBar.className = 'strength-bar strong';
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = '#22c55e';
                }
            }

            // Attach event listeners
            Object.keys(fields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                input.addEventListener('blur', function() {
                    if (fieldId === 'mobile') {
                        validateMobile(this);
                    } else if (fieldId === 'emergency_contact') {
                        validateEmergency(this);
                    } else {
                        validateField(fieldId);
                    }
                });

                input.addEventListener('input', function() {
                    if (fieldId === 'mobile') {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 10) {
                            this.value = this.value.slice(0, 10);
                        }
                        validateMobile(this);
                    } else if (fieldId === 'emergency_contact') {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 10) {
                            this.value = this.value.slice(0, 10);
                        }
                        validateEmergency(this);
                    } else if (fieldId === 'password') {
                        checkPasswordStrength(this.value);
                        validateField(fieldId);
                    } else {
                        validateField(fieldId);
                    }
                });
            });

            // Form submission validation
            document.getElementById('patientForm').addEventListener('submit', function(e) {
                let isValid = true;

                Object.keys(fields).forEach(fieldId => {
                    if (fieldId === 'mobile') {
                        if (!validateMobile(document.getElementById('mobile'))) {
                            isValid = false;
                        }
                    } else if (fieldId === 'emergency_contact') {
                        if (!validateEmergency(document.getElementById('emergency_contact'))) {
                            isValid = false;
                        }
                    } else if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    const firstError = document.querySelector('.field-group input.error, .field-group textarea.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    </script>
</body>
</html>