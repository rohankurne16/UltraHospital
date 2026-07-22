<?php
session_start(); 
include "config/hospital.php";

$message = "";
$error = "";
$validation_errors = [];
$form_data = []; // Store form data for repopulation

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = $_POST['newpatient_id'];

    $get = "select * from patients where patient_id='$id'";
    $res = mysqli_query($conn, $get);
    $patient = mysqli_fetch_assoc($res);

    $register_id = $patient["register_id"];
    $patient_image = $patient['patient_image'];

    // Validation - Store all POST data
    $form_data = [
        'patient_name' => trim($_POST['newpatient_name'] ?? ''),
        'dob' => $_POST['newdob'] ?? '',
        'age' => $_POST['newage'] ?? '',
        'blood_group' => $_POST['newblood_group'] ?? '',
        'gender' => $_POST['newgender'] ?? '',
        'address' => trim($_POST['newaddress'] ?? ''),
        'mobile' => $_POST['newmobile'] ?? '',
        'email' => $_POST['newemail'] ?? '',
        'emergency_contact' => $_POST['newemergency_contact'] ?? '',
        'medical_history' => trim($_POST['newmedical_history'] ?? ''),
        'allergy' => trim($_POST['newallergy'] ?? ''),
        'status' => $_POST['newstatus'] ?? 'Active'
    ];

    // Extract individual variables
    $patient_name = $form_data['patient_name'];
    $dob = $form_data['dob'];
    $age = $form_data['age'];
    $blood_group = $form_data['blood_group'];
    $gender = $form_data['gender'];
    $address = $form_data['address'];
    $mobile = $form_data['mobile'];
    $email = $form_data['email'];
    $emergency_contact = $form_data['emergency_contact'];
    $medical_history = $form_data['medical_history'];
    $allergy = $form_data['allergy'];
    $status = $form_data['status'];

    // Validate Patient Name
    if (empty($patient_name)) {
        $validation_errors['patient_name'] = "Patient name is required.";
    } elseif (strlen($patient_name) < 2) {
        $validation_errors['patient_name'] = "Patient name must be at least 2 characters.";
    } elseif (strlen($patient_name) > 100) {
        $validation_errors['patient_name'] = "Patient name cannot exceed 100 characters.";
    }

    // Validate Date of Birth (if provided)
    if (!empty($dob)) {
        $dob_timestamp = strtotime($dob);
        if ($dob_timestamp === false) {
            $validation_errors['dob'] = "Invalid date format.";
        } elseif ($dob_timestamp > time()) {
            $validation_errors['dob'] = "Date of birth cannot be in the future.";
        }
    }

    // Validate Age
    if (!empty($age)) {
        if (!is_numeric($age) || $age < 0 || $age > 121) {
            $validation_errors['age'] = "Age must be a number between 0 and 120.";
        }
    }

    // Validate Blood Group
    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', ''];
    if (!empty($blood_group) && !in_array($blood_group, $valid_blood_groups)) {
        $validation_errors['blood_group'] = "Invalid blood group. Valid groups: A+, A-, B+, B-, AB+, AB-, O+, O-";
    }

    // Validate Gender
    if (empty($gender)) {
        $validation_errors['gender'] = "Gender is required.";
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $validation_errors['gender'] = "Invalid gender selection.";
    }

    // Validate Address
    if (!empty($address) && strlen($address) > 500) {
        $validation_errors['address'] = "Address cannot exceed 500 characters.";
    }

    // Validate Mobile
    if (!empty($mobile)) {
        // Remove any spaces for validation
        $mobile_original = trim($mobile);
        
        // Remove all non-numeric characters except +
        $mobile_clean = preg_replace('/[^0-9+]/', '', $mobile_original);
        
        // Check if it has +91 prefix
        $is_valid_mobile = false;
        $mobile_for_db = '';
        
        // Check for +91 followed by exactly 10 digits (total 13 characters including +)
        if (preg_match('/^\+91[0-9]{10}$/', $mobile_clean)) {
            // +91 followed by exactly 10 digits (total 13 chars)
            $is_valid_mobile = true;
            // Extract only the 10 digits after +91
            $mobile_for_db = preg_replace('/[^0-9]/', '', $mobile_clean);
        } 
        // Check for exactly 10 digits without prefix
        elseif (preg_match('/^[0-9]{10}$/', $mobile_clean)) {
            // Exactly 10 digits without prefix
            $is_valid_mobile = true;
            $mobile_for_db = $mobile_clean;
        }
        // Check for exactly 12 digits (91 + 10 digits) without +
        elseif (preg_match('/^91[0-9]{10}$/', $mobile_clean)) {
            // 91 followed by exactly 10 digits (total 12 chars)
            $is_valid_mobile = true;
            $mobile_for_db = $mobile_clean;
        }
        
        if (!$is_valid_mobile) {
            $validation_errors['mobile'] = "Mobile number must be exactly 10 digits (e.g., 9876543210) or 13 digits with +91 prefix (e.g., +919876543210).";
        } else {
            // Check if mobile already exists for another patient
            $check_mobile = "SELECT patient_id FROM patients WHERE mobile = ? AND patient_id != ? AND delete_flag = 0 AND hospital_id = ?";
            $stmt = $conn->prepare($check_mobile);
            
            // Try checking with the 10-digit format first
            $mobile_10_digit = '';
            if (strlen($mobile_for_db) == 12 && substr($mobile_for_db, 0, 2) == '91') {
                $mobile_10_digit = substr($mobile_for_db, 2);
            } elseif (strlen($mobile_for_db) == 11 && substr($mobile_for_db, 0, 1) == '0') {
                $mobile_10_digit = substr($mobile_for_db, 1);
            } else {
                $mobile_10_digit = $mobile_for_db;
            }
            
            // Check if the 10-digit version exists in the database
            $stmt->bind_param("sii", $mobile_10_digit, $id, $patient['hospital_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $validation_errors['mobile'] = "Mobile number already exists for another patient.";
            } else {
                // Also check if the number with 91 prefix exists
                if ($mobile_10_digit != $mobile_for_db) {
                    $stmt->bind_param("sii", $mobile_for_db, $id, $patient['hospital_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $validation_errors['mobile'] = "Mobile number already exists for another patient.";
                    }
                }
            }
            
            // If no duplicate found, update mobile with the original format
            if (empty($validation_errors['mobile'])) {
                $form_data['mobile'] = $mobile_for_db;
                $mobile = $mobile_for_db;
            }
        }
    }

    // Validate Email
    if (!empty($email)) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors['email'] = "Invalid email format.";
        } elseif (strlen($email) > 100) {
            $validation_errors['email'] = "Email cannot exceed 100 characters.";
        }
        // Check if email already exists for another patient
        if (empty($validation_errors['email'])) {
            $check_email = "SELECT patient_id FROM patients WHERE email = ? AND patient_id != ? AND delete_flag = 0 AND hospital_id = ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("sii", $email, $id, $patient['hospital_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $validation_errors['email'] = "Email already exists for another patient.";
            }
        }
    }

    // Validate Emergency Contact
    if (!empty($emergency_contact)) {
        // Remove any spaces for validation
        $emergency_original = trim($emergency_contact);
        
        // Remove all non-numeric characters except +
        $emergency_clean = preg_replace('/[^0-9+]/', '', $emergency_original);
        
        // Check if it has +91 prefix
        $is_valid_emergency = false;
        
        if (preg_match('/^\+91[0-9]{10}$/', $emergency_clean)) {
            // +91 followed by exactly 10 digits (total 13 chars)
            $is_valid_emergency = true;
        } elseif (preg_match('/^[0-9]{10}$/', $emergency_clean)) {
            // Exactly 10 digits without prefix
            $is_valid_emergency = true;
        } elseif (preg_match('/^91[0-9]{10}$/', $emergency_clean)) {
            // 91 followed by exactly 10 digits (total 12 chars)
            $is_valid_emergency = true;
        }
        
        if (!$is_valid_emergency) {
            $validation_errors['emergency_contact'] = "Emergency contact must be exactly 10 digits (e.g., 9876543210) or 13 digits with +91 prefix (e.g., +919876543210).";
        }
    }

    // Validate Status
    if (!in_array($status, ['Active', 'Inactive'])) {
        $validation_errors['status'] = "Invalid status selection.";
    }

    // Validate Medical History (max length)
    if (!empty($medical_history) && strlen($medical_history) > 1000) {
        $validation_errors['medical_history'] = "Medical history cannot exceed 1000 characters.";
    }

    // Validate Allergy (max length)
    if (!empty($allergy) && strlen($allergy) > 500) {
        $validation_errors['allergy'] = "Allergy information cannot exceed 500 characters.";
    }

    // Image validation
    if (isset($_FILES['newpatient_image']) && $_FILES['newpatient_image']['error'] == 0) {
        $file = $_FILES['newpatient_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $validation_errors['image'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($file['size'] > $max_file_size) {
            $validation_errors['image'] = "Image size cannot exceed 5MB.";
        }
    }

    // If no validation errors, proceed with update
    if (empty($validation_errors)) {
        if (isset($_FILES['newpatient_image']) && $_FILES['newpatient_image']['error'] == 0) {
            $folder = "documents/patients/images/";
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['newpatient_image']['name']);
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            if (move_uploaded_file($_FILES['newpatient_image']['tmp_name'], $folder . $filename)) {
                // Delete old image if exists
                if (!empty($patient_image) && file_exists($patient_image)) {
                    unlink($patient_image);
                }
                $patient_image = "documents/patients/images/" . $filename;
            } else {
                $error = "Image upload failed!";
            }
        }

        if (empty($error)) {
            $patient_name = mysqli_real_escape_string($conn, $patient_name);
            $address = mysqli_real_escape_string($conn, $address);
            $medical_history = mysqli_real_escape_string($conn, $medical_history);
            $allergy = mysqli_real_escape_string($conn, $allergy);
            $mobile = mysqli_real_escape_string($conn, $mobile);
            $email = mysqli_real_escape_string($conn, $email);
            $emergency_contact = mysqli_real_escape_string($conn, $emergency_contact);

            $sql = "update patients set patient_name='$patient_name', patient_image='$patient_image', date_of_birth='$dob', age='$age', blood_group='$blood_group', gender='$gender', address='$address', mobile='$mobile', email='$email', emergency_contact='$emergency_contact', medical_history='$medical_history', allergy='$allergy', status='$status' where patient_id='$id'";

            if (mysqli_query($conn, $sql)) {
                $sql2 = "update register set name='$patient_name', email='$email', modified_by='Admin' where id='$register_id'";
                if(mysqli_query($conn, $sql2)){
                    $patient_id = $id;
                    $hospital_id = $patient['hospital_id'];

                    // Remove old alerts
                    mysqli_query($conn, "
                        DELETE FROM patient_alerts
                        WHERE patient_id='$patient_id'
                        AND alert_type IN ('Allergy','Medical History')
                    ");

                    // Insert Allergy
                    if (!empty($allergy)) {
                        mysqli_query($conn, "
                            INSERT INTO patient_alerts
                            (patient_id, hospital_id, alert_type, description, status, created_by)
                            VALUES
                            ('$patient_id', '$hospital_id', 'Allergy', '$allergy', 'Active', 'Admin')
                        ");
                    }

                    // Insert Medical History
                    if (!empty($medical_history)) {
                        mysqli_query($conn, "
                            INSERT INTO patient_alerts
                            (patient_id, hospital_id, alert_type, description, status, created_by)
                            VALUES
                            ('$patient_id', '$hospital_id', 'Medical History', '$medical_history', 'Active', 'Admin')
                        ");
                    }

                    header("Location:patients.php?msg=success");
                    exit();
                } else {
                    $error = "Failed to update register record: " . mysqli_error($conn);
                }
            } else {
                $error = mysqli_error($conn);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $id = intval($id);
        $res = mysqli_query($conn, "select * from patients where patient_id='$id'");
        if(mysqli_num_rows($res)>0){
            $patient = mysqli_fetch_assoc($res);
            // Initialize form_data with database values for GET request
            $form_data = [
                'patient_name' => $patient['patient_name'] ?? '',
                'dob' => $patient['date_of_birth'] ?? '',
                'age' => $patient['age'] ?? '',
                'blood_group' => $patient['blood_group'] ?? '',
                'gender' => $patient['gender'] ?? '',
                'address' => $patient['address'] ?? '',
                'mobile' => $patient['mobile'] ?? '',
                'email' => $patient['email'] ?? '',
                'emergency_contact' => $patient['emergency_contact'] ?? '',
                'medical_history' => $patient['medical_history'] ?? '',
                'allergy' => $patient['allergy'] ?? '',
                'status' => $patient['status'] ?? 'Active'
            ];
        } else {
            $error = "Patient not found.";
        }
    } else {
        $error = "Invalid Patient ID.";
    }
}

// If there are validation errors, use the submitted data instead of database data
if (!empty($validation_errors) && !empty($form_data)) {
    // form_data already contains the submitted data
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $hospital['hospital_name'] ?>- Edit Patient</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 32px; min-height: 100vh; transition: 0.3s; }
        .form-container { width: 100%; margin: 0 auto; max-width: 1200px; }
        .form-card { background: white; border-radius: 20px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; }
        .form-card .header { padding: 24px 32px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; display: flex; align-items: center; gap: 12px; }
        .form-card .header .header-icon { width: 44px; height: 44px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; }
        .form-card .header h3 { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
        .form-card .header .subtitle { font-size: 14px; color: #64748b; font-weight: 400; }
        .form-card .body { padding: 32px 40px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .full-width { grid-column: 1 / -1; }
        .field-group label { font-weight: 600; font-size: 14px; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .field-group label i { color: #3b82f6; width: 18px; }
        .field-group label .required { color: #ef4444; font-weight: 700; }
        .field-group input, .field-group select, .field-group textarea { padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #fcfdfe; font-size: 15px; outline: none; transition: 0.2s; width: 100%; }
        .field-group input:focus, .field-group select:focus, .field-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
        .field-group input.error, .field-group select.error, .field-group textarea.error { border-color: #ef4444; background: #fef2f2; }
        .field-group .error-message { font-size: 12px; color: #ef4444; margin-top: 6px; display: flex; align-items: center; gap: 4px; }
        .field-group .error-message i { font-size: 12px; }
        .image-upload-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 40px; }
        .image-preview-wrapper { position: relative; width: 140px; height: 140px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.1); overflow: hidden; background: #f1f5f9; cursor: pointer; transition: 0.3s; }
        .image-preview-wrapper:hover { transform: scale(1.03); border-color: #3b82f6; }
        .image-preview-wrapper.error { border-color: #ef4444; }
        .image-preview { width: 100%; height: 100%; object-fit: cover; }
        .camera-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: #fff; transition: 0.3s; opacity: 0; }
        .image-preview-wrapper:hover .camera-overlay { opacity: 1; }
        .btn-primary { background: #3b82f6; color: #fff; padding: 12px 32px; border-radius: 40px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .back-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; color: #374151; transition: all 0.2s ease; text-decoration: none; }
        .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .validation-summary { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
        .validation-summary h4 { color: #991b1b; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .validation-summary ul { margin: 0; padding-left: 24px; color: #991b1b; }
        .validation-summary ul li { margin-bottom: 4px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .form-card .body { padding: 20px; } }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>  
    <div class="flex min-h-screen">
        <?php include 'Sidebar.php'; ?>  
        <main class="main-content w-full">
            <div class="form-container">
                <div class="flex items-center gap-4 mb-8">
                    <a href="patients.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Edit Patient</h1>
                        <p class="text-slate-500 mt-1">Update information for <?php echo htmlspecialchars($form_data['patient_name'] ?? 'Patient'); ?></p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($validation_errors)): ?>
                    <div class="validation-summary">
                        <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
                        <ul>
                            <?php foreach ($validation_errors as $field => $message): ?>
                                <li><?php echo htmlspecialchars($message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($patient || !empty($form_data)): ?>
                <div class="form-card">
                    <div class="header">
                        <div class="header-icon"><i class="fas fa-user"></i></div>
                        <div>
                            <h3>Patient Details</h3>
                            <div class="subtitle">Update personal and medical information</div>
                        </div>
                    </div>

                    <div class="body">
                        <form action="update_patient.php?id=<?php echo $patient['patient_id'] ?? $id ?? 0; ?>" method="POST" enctype="multipart/form-data" id="patientForm" novalidate>
                            <input type="hidden" name="newpatient_id" value="<?php echo $patient['patient_id'] ?? $id ?? 0; ?>">
                            
                            <div class="image-upload-container">
                                <div class="image-preview-wrapper <?php echo isset($validation_errors['image']) ? 'error' : ''; ?>" onclick="document.getElementById('imageInput').click()">
                                    <?php if (!empty($patient['patient_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['patient_image']); ?>" class="image-preview" id="imagePreview">
                                    <?php else: ?>
                                        <div class="flex items-center justify-center h-full bg-blue-600 text-white text-4xl font-bold">
                                            <?php echo strtoupper(substr($form_data['patient_name'] ?? '?', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="camera-overlay">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                <input type="file" id="imageInput" name="newpatient_image" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(event)">
                                <?php if (isset($validation_errors['image'])): ?>
                                    <p class="text-xs text-red-500 mt-2"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['image']); ?></p>
                                <?php else: ?>
                                    <p class="text-xs text-slate-400 mt-3">Click to update photo (JPG, PNG, GIF, WEBP - Max 5MB)</p>
                                <?php endif; ?>
                            </div>

                            <div class="form-grid">
                                <div class="field-group">
                                    <label><i class="fas fa-user"></i> Patient Name <span class="required">*</span></label>
                                    <input type="text" name="newpatient_name" value="<?php echo htmlspecialchars($form_data['patient_name'] ?? ''); ?>" 
                                        class="<?php echo isset($validation_errors['patient_name']) ? 'error' : ''; ?>"
                                        maxlength="100" required />
                                    <?php if (isset($validation_errors['patient_name'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['patient_name']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                                    <input type="date" name="newdob" value="<?php echo $form_data['dob'] ?? ''; ?>" 
                                        class="<?php echo isset($validation_errors['dob']) ? 'error' : ''; ?>" />
                                    <?php if (isset($validation_errors['dob'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['dob']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-birthday-cake"></i> Age</label>
                                    <input type="number" name="newage" value="<?php echo $form_data['age'] ?? ''; ?>" 
                                        class="<?php echo isset($validation_errors['age']) ? 'error' : ''; ?>"
                                        min="0" max="150" />
                                    <?php if (isset($validation_errors['age'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['age']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-tint"></i> Blood Group</label>
                                    <input type="text" name="newblood_group" value="<?php echo htmlspecialchars($form_data['blood_group'] ?? ''); ?>" 
                                        class="<?php echo isset($validation_errors['blood_group']) ? 'error' : ''; ?>"
                                        placeholder="e.g., A+, B-, O+" />
                                    <?php if (isset($validation_errors['blood_group'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['blood_group']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-venus-mars"></i> Gender <span class="required">*</span></label>
                                    <select name="newgender" class="<?php echo isset($validation_errors['gender']) ? 'error' : ''; ?>" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php if(($form_data['gender'] ?? '')=="Male") echo "selected"; ?>>Male</option>
                                        <option value="Female" <?php if(($form_data['gender'] ?? '')=="Female") echo "selected"; ?>>Female</option>
                                        <option value="Other" <?php if(($form_data['gender'] ?? '')=="Other") echo "selected"; ?>>Other</option>
                                    </select>
                                    <?php if (isset($validation_errors['gender'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['gender']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-phone"></i> Mobile</label>
                                    <input type="tel" name="newmobile" value="<?php echo htmlspecialchars($form_data['mobile'] ?? ''); ?>" 
                                        class="<?php echo isset($validation_errors['mobile']) ? 'error' : ''; ?>"
                                        placeholder="10-13 digits" maxlength="13" />
                                    <?php if (isset($validation_errors['mobile'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['mobile']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" name="newemail" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                        class="<?php echo isset($validation_errors['email']) ? 'error' : ''; ?>"
                                        maxlength="100" />
                                    <?php if (isset($validation_errors['email'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['email']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-phone-square"></i> Emergency Contact</label>
                                    <input type="tel" name="newemergency_contact" value="<?php echo htmlspecialchars($form_data['emergency_contact'] ?? ''); ?>" 
                                        class="<?php echo isset($validation_errors['emergency_contact']) ? 'error' : ''; ?>"
                                        placeholder="10-13 digits" maxlength="13" />
                                    <?php if (isset($validation_errors['emergency_contact'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['emergency_contact']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-toggle-on"></i> Status <span class="required">*</span></label>
                                    <select name="newstatus" class="<?php echo isset($validation_errors['status']) ? 'error' : ''; ?>" required>
                                        <option value="Active" <?php if(($form_data['status'] ?? '')=="Active") echo "selected"; ?>>Active</option>
                                        <option value="Inactive" <?php if(($form_data['status'] ?? '')=="Inactive") echo "selected"; ?>>Inactive</option>
                                    </select>
                                    <?php if (isset($validation_errors['status'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['status']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="field-group full-width">
                                    <label><i class="fas fa-map-marker-alt"></i> Address</label>
                                    <textarea name="newaddress" rows="2" maxlength="500" 
                                        class="<?php echo isset($validation_errors['address']) ? 'error' : ''; ?>"
                                        oninput="updateCharCount(this, 'addressCount', 500)"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                                    <?php if (isset($validation_errors['address'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['address']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-slate-400 mt-1"><span id="addressCount">0</span>/500 characters</div>
                                </div>

                                <div class="field-group full-width">
                                    <label><i class="fas fa-history"></i> Medical History</label>
                                    <textarea name="newmedical_history" rows="2" maxlength="1000" 
                                        class="<?php echo isset($validation_errors['medical_history']) ? 'error' : ''; ?>"
                                        oninput="updateCharCount(this, 'medicalCount', 1000)"><?php echo htmlspecialchars($form_data['medical_history'] ?? ''); ?></textarea>
                                    <?php if (isset($validation_errors['medical_history'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['medical_history']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-slate-400 mt-1"><span id="medicalCount">0</span>/1000 characters</div>
                                </div>

                                <div class="field-group full-width">
                                    <label><i class="fas fa-allergies"></i> Allergy</label>
                                    <textarea name="newallergy" rows="2" maxlength="500" 
                                        class="<?php echo isset($validation_errors['allergy']) ? 'error' : ''; ?>"
                                        oninput="updateCharCount(this, 'allergyCount', 500)"><?php echo htmlspecialchars($form_data['allergy'] ?? ''); ?></textarea>
                                    <?php if (isset($validation_errors['allergy'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['allergy']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-slate-400 mt-1"><span id="allergyCount">0</span>/500 characters</div>
                                </div>
                            </div>

                            <div class="mt-10 flex justify-end gap-4 border-t pt-8">
                                <button type="button" class="px-6 py-2.5 rounded-full font-semibold text-slate-600 hover:bg-slate-100 transition" onclick="window.history.back()">Cancel</button>
                                <button type="submit" class="btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Update Patient
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, PNG, GIF, and WEBP images are allowed.');
                event.target.value = '';
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size cannot exceed 5MB.');
                event.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.querySelector('.image-preview-wrapper');
                wrapper.innerHTML = `
                    <img src="${e.target.result}" class="image-preview" id="imagePreview">
                    <div class="camera-overlay"><i class="fas fa-camera"></i></div>
                `;
                wrapper.classList.remove('error');
            };
            reader.readAsDataURL(file);
        }

        function updateCharCount(element, counterId, maxLength) {
            const count = element.value.length;
            document.getElementById(counterId).textContent = count;
            
            if (count > maxLength) {
                element.classList.add('error');
                document.getElementById(counterId).style.color = '#ef4444';
            } else {
                element.classList.remove('error');
                document.getElementById(counterId).style.color = '#64748b';
            }
        }

        // Initialize character counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize address count
            const addressTextarea = document.querySelector('textarea[name="newaddress"]');
            if (addressTextarea) {
                document.getElementById('addressCount').textContent = addressTextarea.value.length;
            }

            // Initialize medical history count
            const medicalTextarea = document.querySelector('textarea[name="newmedical_history"]');
            if (medicalTextarea) {
                document.getElementById('medicalCount').textContent = medicalTextarea.value.length;
            }

            // Initialize allergy count
            const allergyTextarea = document.querySelector('textarea[name="newallergy"]');
            if (allergyTextarea) {
                document.getElementById('allergyCount').textContent = allergyTextarea.value.length;
            }

            // Form validation on submit
            const form = document.getElementById('patientForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                });
            }
        });
    </script>
</body>
</html>