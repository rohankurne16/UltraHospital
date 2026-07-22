<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$message = "";
$error = "";
$validation_errors = [];
$form_data = []; // Store form data for repopulation

if ($doctor_id <= 0) {
    header("Location: update_doctor.php");
    exit();
}

$sql = "SELECT * FROM doctor
        WHERE doctor_id='$doctor_id'
        AND (delete_flag=0 OR delete_flag IS NULL)";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: update_doctor.php");
    exit();
}
$doctor = $result->fetch_assoc();

$register_id = $doctor['register_id'];
$register_sql = "SELECT * FROM register WHERE id = '$register_id'";
$register_result = $conn->query($register_sql);
$register = $register_result->fetch_assoc();

$doctor_image = $doctor['doctor_image'];

// Initialize form_data with database values
$form_data = [
    'doctor_name' => $doctor['doctor_name'] ?? '',
    'mobile' => $doctor['mobile'] ?? '',
    'email' => $doctor['email'] ?? '',
    'department' => $doctor['department'] ?? '',
    'qualification' => $doctor['qualification'] ?? '',
    'specialization' => $doctor['specialization'] ?? '',
    'experience' => $doctor['experience'] ?? '',
    'consultation_fee' => $doctor['consultation_fee'] ?? '',
    'timing' => $doctor['timing'] ?? '',
    'address' => $doctor['address'] ?? '',
    'status' => $doctor['status'] ?? 'Active'
];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    
    // Get form data and store in form_data
    $form_data = [
        'doctor_name' => trim($_POST['newdoctor_name'] ?? ''),
        'mobile' => trim($_POST['newmobile'] ?? ''),
        'email' => trim($_POST['newemail'] ?? ''),
        'department' => trim($_POST['newdepartment'] ?? ''),
        'qualification' => trim($_POST['newqualification'] ?? ''),
        'specialization' => trim($_POST['newspecialization'] ?? ''),
        'experience' => trim($_POST['newexperience'] ?? ''),
        'consultation_fee' => trim($_POST['newconsultation_fee'] ?? ''),
        'timing' => trim($_POST['newtiming'] ?? ''),
        'address' => trim($_POST['newaddress'] ?? ''),
        'status' => $_POST['newstatus'] ?? 'Active'
    ];
    
    // Extract variables
    $doctor_name = $form_data['doctor_name'];
    $mobile = $form_data['mobile'];
    $email = $form_data['email'];
    $department = $form_data['department'];
    $qualification = $form_data['qualification'];
    $specialization = $form_data['specialization'];
    $experience = $form_data['experience'];
    $consultation_fee = $form_data['consultation_fee'];
    $timing = $form_data['timing'];
    $address = $form_data['address'];
    $status = $form_data['status'];
    
    // Validate Doctor Name
    if (empty($doctor_name)) {
        $validation_errors['doctor_name'] = "Doctor name is required.";
    } elseif (strlen($doctor_name) < 2) {
        $validation_errors['doctor_name'] = "Doctor name must be at least 2 characters.";
    } elseif (strlen($doctor_name) > 100) {
        $validation_errors['doctor_name'] = "Doctor name cannot exceed 100 characters.";
    }
    
    // Validate Mobile
    if (!empty($mobile)) {
        $mobile_original = trim($mobile);
        $mobile_clean = preg_replace('/[^0-9+]/', '', $mobile_original);
        
        $is_valid_mobile = false;
        $mobile_for_db = '';
        
        if (preg_match('/^\+91[0-9]{10}$/', $mobile_clean)) {
            $is_valid_mobile = true;
            $mobile_for_db = preg_replace('/[^0-9]/', '', $mobile_clean);
        } elseif (preg_match('/^[0-9]{10}$/', $mobile_clean)) {
            $is_valid_mobile = true;
            $mobile_for_db = $mobile_clean;
        } elseif (preg_match('/^91[0-9]{10}$/', $mobile_clean)) {
            $is_valid_mobile = true;
            $mobile_for_db = $mobile_clean;
        }
        
        if (!$is_valid_mobile) {
            $validation_errors['mobile'] = "Mobile number must be exactly 10 digits (e.g., 9876543210) or 13 digits with +91 prefix (e.g., +919876543210).";
        } else {
            // Check if mobile already exists for another doctor
            $check_mobile = "SELECT doctor_id FROM doctor WHERE mobile = ? AND doctor_id != ? AND (delete_flag=0 OR delete_flag IS NULL)";
            $stmt = $conn->prepare($check_mobile);
            $stmt->bind_param("si", $mobile_for_db, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $validation_errors['mobile'] = "Mobile number already exists for another doctor.";
            } else {
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
        // Check if email already exists for another doctor
        if (empty($validation_errors['email'])) {
            $check_email = "SELECT doctor_id FROM doctor WHERE email = ? AND doctor_id != ? AND (delete_flag=0 OR delete_flag IS NULL)";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("si", $email, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $validation_errors['email'] = "Email already exists for another doctor.";
            }
        }
    }
    
    // Validate Department
    if (!empty($department)) {
        if (strlen($department) > 100) {
            $validation_errors['department'] = "Department cannot exceed 100 characters.";
        } elseif (!preg_match('/^[a-zA-Z\s\-&,.]+$/', $department)) {
            $validation_errors['department'] = "Department name contains invalid characters.";
        }
    }
    
    // Validate Qualification
    if (!empty($qualification)) {
        if (strlen($qualification) > 200) {
            $validation_errors['qualification'] = "Qualification cannot exceed 200 characters.";
        } elseif (strlen($qualification) < 2) {
            $validation_errors['qualification'] = "Qualification must be at least 2 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-&,.\/()]+$/', $qualification)) {
            $validation_errors['qualification'] = "Qualification contains invalid characters.";
        }
    }
    
    // Validate Specialization
    if (!empty($specialization)) {
        if (strlen($specialization) > 200) {
            $validation_errors['specialization'] = "Specialization cannot exceed 200 characters.";
        } elseif (strlen($specialization) < 2) {
            $validation_errors['specialization'] = "Specialization must be at least 2 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-&,.\/()]+$/', $specialization)) {
            $validation_errors['specialization'] = "Specialization contains invalid characters.";
        }
    }
    
    // Validate Experience
    if (!empty($experience)) {
        $experience_clean = preg_replace('/[^0-9.]/', '', $experience);
        if (!is_numeric($experience_clean) || $experience_clean < 0 || $experience_clean > 70) {
            $validation_errors['experience'] = "Experience must be a number between 0 and 70 years.";
        } elseif (!preg_match('/^[0-9]+(\.[0-9])?$/', $experience_clean)) {
            $validation_errors['experience'] = "Experience must be a valid number (e.g., 5 or 5.5).";
        } else {
            $form_data['experience'] = $experience_clean;
            $experience = $experience_clean;
        }
    }
    
    // Validate Consultation Fee
    if (!empty($consultation_fee)) {
        $consultation_fee_clean = preg_replace('/[^0-9.]/', '', $consultation_fee);
        if (!is_numeric($consultation_fee_clean) || $consultation_fee_clean < 0) {
            $validation_errors['consultation_fee'] = "Consultation fee must be a valid positive number.";
        } elseif ($consultation_fee_clean > 999999) {
            $validation_errors['consultation_fee'] = "Consultation fee cannot exceed ₹999,999.";
        } elseif (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $consultation_fee_clean)) {
            $validation_errors['consultation_fee'] = "Consultation fee must be a valid amount (e.g., 500 or 500.50).";
        } else {
            $form_data['consultation_fee'] = number_format((float)$consultation_fee_clean, 2, '.', '');
            $consultation_fee = $form_data['consultation_fee'];
        }
    }
    
    // Validate Consultation Timing
    if (!empty($timing)) {
        if (strlen($timing) > 100) {
            $validation_errors['timing'] = "Consultation timing cannot exceed 100 characters.";
        } elseif (strlen($timing) < 3) {
            $validation_errors['timing'] = "Consultation timing must be at least 3 characters.";
        } elseif (!preg_match('/^[0-9:AMP\s\-]+$/', $timing)) {
            $validation_errors['timing'] = "Consultation timing contains invalid characters. Use format like 9:00 AM - 6:00 PM.";
        }
    }
    
    // Validate Status
    if (!in_array($status, ['Active', 'Inactive'])) {
        $validation_errors['status'] = "Invalid status selection.";
    }
    
    // Validate Address
    if (!empty($address) && strlen($address) > 500) {
        $validation_errors['address'] = "Address cannot exceed 500 characters.";
    }
    
    // Handle image upload
    if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] == 0 && !empty($_FILES['doctor_image']['name'])) {
        $file = $_FILES['doctor_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $validation_errors['image'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($file['size'] > $max_file_size) {
            $validation_errors['image'] = "Image size cannot exceed 5MB.";
        } else {
            $folder = "documents/doctors/images/";
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            $filename = time() . '_' . basename($file['name']);
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            if (move_uploaded_file($file['tmp_name'], $folder . $filename)) {
                // Delete old image if exists
                if (!empty($doctor['doctor_image']) && file_exists($doctor['doctor_image'])) {
                    unlink($doctor['doctor_image']);
                }
                $doctor_image = "documents/doctors/images/" . $filename;
            } else {
                $validation_errors['image'] = "Failed to upload image.";
            }
        }
    }
    
    // If no validation errors, proceed with update
    if (empty($validation_errors)) {
        // Escape strings for database
        $doctor_name = mysqli_real_escape_string($conn, $doctor_name);
        $mobile = mysqli_real_escape_string($conn, $mobile);
        $email = mysqli_real_escape_string($conn, $email);
        $department = mysqli_real_escape_string($conn, $department);
        $qualification = mysqli_real_escape_string($conn, $qualification);
        $specialization = mysqli_real_escape_string($conn, $specialization);
        $experience = mysqli_real_escape_string($conn, $experience);
        $consultation_fee = mysqli_real_escape_string($conn, $consultation_fee);
        $timing = mysqli_real_escape_string($conn, $timing);
        $address = mysqli_real_escape_string($conn, $address);
        $status = mysqli_real_escape_string($conn, $status);
        $doctor_image = mysqli_real_escape_string($conn, $doctor_image);
        
        // Update doctor
        $sql = "UPDATE doctor SET 
                doctor_name='$doctor_name',
                doctor_image='$doctor_image', 
                mobile='$mobile', 
                email='$email', 
                department='$department', 
                qualification='$qualification', 
                specialization='$specialization', 
                experience='$experience', 
                consultation_fee='$consultation_fee', 
                timing='$timing', 
                address='$address', 
                status='$status' 
                WHERE doctor_id='$id'";
                
        if (mysqli_query($conn, $sql)) {
            // Update register table
            $sql2 = "UPDATE register SET name='$doctor_name', email='$email', modified_by='admin' WHERE id='$register_id'";
            if (mysqli_query($conn, $sql2)) {
                echo "<script>
                    alert('Doctor updated successfully!');
                    window.location.href='view_doctor.php?id=$id';
                </script>";
                exit();
            } else {
                $error = "Error updating register table: " . mysqli_error($conn);
            }
        } else {
            $error = "Error updating doctor: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - <?php echo $hospital['hospital_name'] ?></title>

    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Sidebar and Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }

        /* Mobile Sidebar behavior */
        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            #sidebar-container.active {
                transform: translateX(0);
            }
            #main-content {
                margin-left: 0 !important;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Desktop Sidebar behavior */
        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 256px;
            }
        }

        #mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            cursor: pointer;
        }

        .form-card { 
            background: white; 
            border-radius: 20px; 
            border: 1px solid #e5e7eb; 
            overflow: hidden; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
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
            transition: all 0.2s ease; 
            text-decoration: none; 
            flex-shrink: 0;
        }
        .back-btn:hover { 
            background: #f3f4f6; 
            border-color: #d1d5db; 
        }

        .image-preview-wrapper { 
            position: relative; 
            width: 140px; 
            height: 140px; 
            border-radius: 50%; 
            overflow: hidden; 
            border: 4px solid #fff; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            background: #f8fafc; 
        }
        
        .image-placeholder { 
            width: 100%; 
            height: 100%; 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 48px; 
            font-weight: 700; 
            color: white; 
            text-transform: uppercase; 
        }

        .form-input {
            width: 100%; 
            padding: 12px 16px; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 12px; 
            font-size: 14px; 
            transition: all 0.2s ease; 
            outline: none; 
            background: white; 
            color: #0f172a; 
        }
        .form-input:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1); 
        }
        .form-input.error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .form-input.success {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        .error-message {
            font-size: 12px;
            color: #ef4444;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .error-message i {
            font-size: 12px;
        }
        .help-text {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }
        .validation-summary {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .validation-summary h4 {
            color: #991b1b;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .validation-summary ul {
            margin: 0;
            padding-left: 24px;
            color: #991b1b;
        }
        .validation-summary ul li {
            margin-bottom: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
        
                <?php include 'Sidebar.php'; ?>
           
            
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <div class="max-w-5xl mx-auto w-full space-y-6">
                    <!-- Page Header -->
                    <div class="flex items-center gap-4">
                       
                        <a href="view_doctor.php?id=<?php echo $doctor_id; ?>" class="back-btn shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Doctor</h1>
                            <p class="text-gray-500 text-sm">Update information for <?php echo htmlspecialchars($form_data['doctor_name'] ?? $doctor['doctor_name']); ?></p>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="p-4 bg-red-50 border border-red-100 text-red-700 rounded-2xl flex items-center gap-3">
                            <i class="fas fa-exclamation-circle"></i>
                            <span class="text-sm font-semibold"><?php echo htmlspecialchars($error); ?></span>
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

                    <div class="form-card w-full">
                        <!-- Card Header -->
                        <div class="p-6 md:p-8 border-b border-gray-100 bg-gray-50/50 flex items-center gap-4">
                            <div class="h-12 w-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shadow-sm">
                                <i class="fas fa-user-md text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 tracking-tight">Doctor Details</h3>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Update professional information</p>
                            </div>
                        </div>

                        <div class="p-6 md:p-10">
                            <form action="update_doctor.php?id=<?php echo $doctor_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-10">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                                
                                <!-- Profile Image Section -->
                                <div class="flex flex-col items-center justify-center space-y-6 pb-8 border-b border-gray-50">
                                    <div class="image-preview-wrapper <?php echo isset($validation_errors['image']) ? 'border-red-500' : ''; ?>" id="imageWrapper">
                                        <?php if (!empty($doctor['doctor_image']) && empty($validation_errors)): ?>
                                            <img src="<?php echo $doctor['doctor_image']; ?>" class="w-full h-full object-cover" id="imagePreview">
                                        <?php elseif (!empty($doctor['doctor_image'])): 
                                            // Keep existing image if validation fails
                                        ?>
                                            <img src="<?php echo $doctor['doctor_image']; ?>" class="w-full h-full object-cover" id="imagePreview">
                                        <?php else: 
                                            $name_parts = explode(' ', $form_data['doctor_name'] ?? $doctor['doctor_name']);
                                            $initials = '';
                                            foreach ($name_parts as $part) { $initials .= strtoupper(substr($part, 0, 1)); }
                                        ?>
                                            <div class="image-placeholder"><?php echo substr($initials, 0, 2); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-col items-center gap-3">
                                        <label class="cursor-pointer bg-white border border-gray-200 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                                            <i class="fas fa-camera"></i> Change Photo
                                            <input type="file" name="doctor_image" accept="image/*" class="hidden" onchange="previewImage(event)">
                                        </label>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Recommended: JPG, PNG (Max 5MB)</p>
                                        <?php if (isset($validation_errors['image'])): ?>
                                            <p class="text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['image']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Form Fields Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="newdoctor_name" value="<?php echo htmlspecialchars($form_data['doctor_name'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['doctor_name']) ? 'error' : ''; ?>" 
                                            maxlength="100" required />
                                        <?php if (isset($validation_errors['doctor_name'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['doctor_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address</label>
                                        <input type="email" name="newemail" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['email']) ? 'error' : ''; ?>" 
                                            maxlength="100" />
                                        <?php if (isset($validation_errors['email'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['email']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mobile Number</label>
                                        <input type="tel" name="newmobile" value="<?php echo htmlspecialchars($form_data['mobile'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['mobile']) ? 'error' : ''; ?>" 
                                            placeholder="10-13 digits" maxlength="13" />
                                        <?php if (isset($validation_errors['mobile'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['mobile']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Department</label>
                                        <input type="text" name="newdepartment" value="<?php echo htmlspecialchars($form_data['department'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['department']) ? 'error' : ''; ?>" 
                                            maxlength="100" />
                                        <?php if (isset($validation_errors['department'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['department']); ?></div>
                                        <?php endif; ?>
                                        <div class="help-text">Allowed: letters, spaces, hyphens, ampersand, comma, period</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Qualification</label>
                                        <input type="text" name="newqualification" value="<?php echo htmlspecialchars($form_data['qualification'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['qualification']) ? 'error' : ''; ?>" 
                                            maxlength="200" placeholder="e.g., MBBS, MD, MS" />
                                        <?php if (isset($validation_errors['qualification'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['qualification']); ?></div>
                                        <?php endif; ?>
                                        <div class="help-text">Min 2 chars, Max 200 chars. Allowed: letters, numbers, spaces, hyphens, &, comma, period, slash, parentheses</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Specialization</label>
                                        <input type="text" name="newspecialization" value="<?php echo htmlspecialchars($form_data['specialization'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['specialization']) ? 'error' : ''; ?>" 
                                            maxlength="200" placeholder="e.g., Cardiologist, Neurologist" />
                                        <?php if (isset($validation_errors['specialization'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['specialization']); ?></div>
                                        <?php endif; ?>
                                        <div class="help-text">Min 2 chars, Max 200 chars. Allowed: letters, numbers, spaces, hyphens, &, comma, period, slash, parentheses</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Experience (Years)</label>
                                        <input type="text" name="newexperience" value="<?php echo htmlspecialchars($form_data['experience'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['experience']) ? 'error' : ''; ?>" 
                                            placeholder="e.g., 5 or 5.5" maxlength="5" />
                                        <?php if (isset($validation_errors['experience'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['experience']); ?></div>
                                        <?php endif; ?>
                                        <div class="help-text">Enter number between 0 and 70 (e.g., 5 or 5.5)</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Consultation Fee (₹)</label>
                                        <input type="text" name="newconsultation_fee" value="<?php echo htmlspecialchars($form_data['consultation_fee'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['consultation_fee']) ? 'error' : ''; ?>" 
                                            placeholder="e.g., 500 or 500.50" maxlength="10" />
                                        <?php if (isset($validation_errors['consultation_fee'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['consultation_fee']); ?></div>
                                        <?php endif; ?>
                                        <div class="help-text">Enter a valid amount (Max ₹999,999). Example: 500 or 500.50</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Consultation Timing</label>
                                        <input type="text" name="newtiming" value="<?php echo htmlspecialchars($form_data['timing'] ?? ''); ?>" 
                                            class="form-input <?php echo isset($validation_errors['timing']) ? 'error' : ''; ?>" 
                                            placeholder="e.g., 9:00 AM - 6:00 PM" maxlength="100" />
                                        <?php if (isset($validation_errors['timing'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['timing']); ?></div>
                                        <?php endif; ?>
                                        <div class="help-text">Format: 9:00 AM - 6:00 PM (Min 3 chars, Max 100 chars)</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status <span class="text-red-500">*</span></label>
                                        <select name="newstatus" class="form-input <?php echo isset($validation_errors['status']) ? 'error' : ''; ?>" required>
                                            <option value="Active" <?php echo (($form_data['status'] ?? '') == 'Active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo (($form_data['status'] ?? '') == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <?php if (isset($validation_errors['status'])): ?>
                                            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['status']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Address</label>
                                    <textarea name="newaddress" rows="3" class="form-input <?php echo isset($validation_errors['address']) ? 'error' : ''; ?> resize-none" maxlength="500"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                                    <?php if (isset($validation_errors['address'])): ?>
                                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($validation_errors['address']); ?></div>
                                    <?php endif; ?>
                                    <div class="help-text">Max 500 characters</div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-8 border-t border-gray-50">
                                    <a href="view_doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="w-full sm:w-auto text-center px-8 py-3 rounded-xl border border-gray-200 text-xs font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition order-2 sm:order-1">
                                        <i class="fas fa-times mr-2"></i> Cancel
                                    </a>
                                    <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-10 py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2" id="submitBtn">
                                        <i class="fas fa-save mr-2"></i> Update Doctor
                                    </button>
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

        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebarContainer = document.getElementById('sidebar-container');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            function openSidebar() {
                sidebarContainer.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

            // Handle close button inside Sidebar.php
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            // Form validation on submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
                });
            }
        });

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
                document.getElementById('imageWrapper').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" id="imagePreview">`;
                document.getElementById('imageWrapper').classList.remove('border-red-500');
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>