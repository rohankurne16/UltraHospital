<?php
session_start();
include "../config/hospital.php";
include "../config/send_registration_email.php";

$hid = $_SESSION["hospital_id"];

// Initialize variables
$message = "";
$message_type = "";

// Check if user is super admin to show hospital dropdown
$user_role = $_SESSION['role'] ?? '';
$is_super_admin = ($user_role == 'Super Admin');

// Fetch hospitals for dropdown (only for super admin)
$hospitals_query = null;
if ($is_super_admin) {
    $hospitals_query = mysqli_query($conn, "
        SELECT hospital_id, hospital_name, city 
        FROM hospital_master 
        WHERE delete_flag = 0 AND status = 'Active' 
        ORDER BY hospital_name ASC
    ");
}

// Fetch departments based on selected hospital or session
// If POST has hospital_id, use that, otherwise use session
$selected_hospital_id = isset($_POST['hospital_id']) && !empty($_POST['hospital_id']) 
    ? (int)$_POST['hospital_id'] 
    : $hid;

// Fetch active departments for the selected hospital
$department_query = mysqli_query($conn, "
    SELECT department_name
    FROM department
    WHERE status = 'Active'
    AND hospital_id='$selected_hospital_id'
    AND (delete_flag = 0 OR delete_flag IS NULL)
    ORDER BY department_name ASC
");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    // Get hospital_id from POST or use session
    $hospital_id = isset($_POST['hospital_id']) && !empty($_POST['hospital_id']) 
        ? (int)$_POST['hospital_id'] 
        : $hid;
    
    $doctor_name = trim($_POST['doctor_name']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $department = trim($_POST['department']);
    $qualification = trim($_POST['qualification']);
    $specialization = trim($_POST['specialization']);
    $experience = trim($_POST['experience']);
    $consultation_fee = trim($_POST['consultation_fee']);
    $timing = trim($_POST['timing']);
    $address = trim($_POST['address']);
    $status = $_POST['status'];

    // Server-side Validation - STRICT
    $errors = [];
    
    // Doctor Name - Required, letters, spaces, hyphens, apostrophes
    if (empty($doctor_name)) {
        $errors[] = "Doctor Name is required.";
    } elseif (!preg_match("/^[A-Za-z\s'-]+$/", $doctor_name)) {
        $errors[] = "Invalid Doctor Name. Only letters, spaces, hyphens, and apostrophes are allowed.";
    } elseif (strlen($doctor_name) < 2 || strlen($doctor_name) > 100) {
        $errors[] = "Doctor Name must be between 2 and 100 characters.";
    }
    
    // Email - Required, valid format
    if (empty($email)) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid Email Address format.";
    } elseif (strlen($email) > 255) {
        $errors[] = "Email Address must not exceed 255 characters.";
    }
    
    // Mobile - Optional, but if provided must be exactly 10 digits
    if (!empty($mobile)) {
        $mobile_clean = preg_replace('/[\s\-+]/', '', $mobile);
        if (!preg_match('/^[0-9]{10}$/', $mobile_clean)) {
            $errors[] = "Invalid Mobile Number. Must be exactly 10 digits.";
        }
    }
    
    // Password - Required, strong
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    } elseif (!preg_match('/[@$!%*?&]/', $password)) {
        $errors[] = "Password must contain at least one special character (@$!%*?&).";
    }
    
    // Department - Required
    if (empty($department)) {
        $errors[] = "Department is required.";
    }
    
    // Qualification - Optional, but if provided must be valid
    if (!empty($qualification) && !preg_match('/^[A-Za-z\s\-\.,]+$/', $qualification)) {
        $errors[] = "Invalid Qualification. Only letters, spaces, hyphens, commas, and periods are allowed.";
    }
    
    // Specialization - Optional, but if provided must be valid
    if (!empty($specialization) && !preg_match('/^[A-Za-z\s\-\.,]+$/', $specialization)) {
        $errors[] = "Invalid Specialization. Only letters, spaces, hyphens, commas, and periods are allowed.";
    }
    
    // Experience - Optional, must be non-negative number
    if (!empty($experience) && (!is_numeric($experience) || $experience < 0 || $experience > 100)) {
        $errors[] = "Invalid Experience. Must be a non-negative number between 0 and 100.";
    }
    
    // Consultation Fee - Optional, must be non-negative number
    if (!empty($consultation_fee) && (!is_numeric($consultation_fee) || $consultation_fee < 0 || $consultation_fee > 999999)) {
        $errors[] = "Invalid Consultation Fee. Must be a non-negative number up to 999999.";
    }
    
    // Timing - Optional, but if provided must be valid
    if (!empty($timing) && !preg_match('/^[A-Za-z0-9\s\-\.,:]+$/', $timing)) {
        $errors[] = "Invalid Timing. Only letters, numbers, spaces, hyphens, commas, and colons are allowed.";
    }
    
    // Address - Optional, but if provided must be valid
    if (!empty($address) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $address)) {
        $errors[] = "Invalid Address. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
    }
    
    // Status - Must be valid
    if (!in_array($status, ['Active', 'Inactive', 'On Leave'])) {
        $errors[] = "Invalid Status selected.";
    }
    
    // Hospital - Required for super admin
    if ($is_super_admin && empty($hospital_id)) {
        $errors[] = "Please select a hospital.";
    }
    
    // Image Upload Handling
    $doctor_image = "";
    $image_error = false;
    
    if (!empty($_FILES['doctor_image']['name'])) {
        if ($_FILES['doctor_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['doctor_image']['type'], $allowed_types)) {
                $errors[] = "Invalid image format. Only JPG, PNG, GIF, and WEBP are allowed.";
                $image_error = true;
            } elseif ($_FILES['doctor_image']['size'] > $max_size) {
                $errors[] = "Image size exceeds 2MB limit.";
                $image_error = true;
            } else {
                $folder = "documents/doctors/images/";
                
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['doctor_image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'doctor_' . time() . '_' . uniqid() . '.' . $file_extension;
                $image_path = $folder . $new_filename;
                
                if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $image_path)) {
                    $doctor_image = "documents/doctors/images/" . $new_filename;
                } else {
                    $errors[] = "Failed to upload image. Please check folder permissions.";
                    $image_error = true;
                }
            }
        } else {
            $errors[] = "Image upload failed. Error code: " . $_FILES['doctor_image']['error'];
            $image_error = true;
        }
    }

    // Only proceed if no errors
    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // Check if email already exists
            $check_email = $conn->prepare("SELECT register_id FROM register WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $check_email->store_result();
            
            if ($check_email->num_rows > 0) {
                throw new Exception("Email address already registered. Please use a different email.");
            }
            $check_email->close();
            
            // Insert into register table
            $stmt_reg = $conn->prepare("INSERT INTO register (name, email, password, role, created_by, modified_by, hospital_id) VALUES (?, ?, ?, 'doctor', 'Admin', 'Admin', ?)");
            $stmt_reg->bind_param("sssi", $doctor_name, $email, $password, $hospital_id);
            
            if ($stmt_reg->execute()) {
                $register_id = $conn->insert_id;

                $sql = "INSERT INTO doctor
(
    register_id,
    doctor_name,
    doctor_image,
    mobile,
    email,
    department,
    qualification,
    specialization,
    experience,
    consultation_fee,
    timing,
    address,
    status,
    hospital_id
)
VALUES
(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt_doc = $conn->prepare($sql);

if (!$stmt_doc) {
    die("<pre>" . $conn->error . "\n\nSQL:\n" . $sql . "</pre>");
}

$stmt_doc->bind_param(
    "issssssssdsssi",
    $register_id,
    $doctor_name,
    $doctor_image,
    $mobile,
    $email,
    $department,
    $qualification,
    $specialization,
    $experience,
    $consultation_fee,
    $timing,
    $address,
    $status,
    $hospital_id
);

                // Insert into doctor table
               
                if ($stmt_doc->execute()) {
                    $conn->commit();

                    // Send registration email
                    $mailSent = sendRegistrationEmail(
                        $conn,
                        $hospital_id,
                        $doctor_name,
                        $email,
                        $password
                    );

                    if (!$mailSent) {
                        error_log("Doctor registration email could not be sent to: " . $email);
                    }

                    $_SESSION['success_message'] = "Doctor added successfully!";
                    header("Location: doctors.php?msg=Doctor added");
                    exit();
                } else {
                    throw new Exception("Unable to Add Doctor details: " . $stmt_doc->error);
                }
            } else {
                throw new Exception("Unable to Register user: " . $stmt_reg->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor - <?php echo $hospital['hospital_name'] ?></title> 
    
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 (Free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .required-star { color: #ef4444; margin-left: 2px; }
        
        /* Validation Styles */
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .input-wrapper .input-icon.valid {
            color: #22c55e;
            opacity: 1;
        }
        
        .input-wrapper .input-icon.invalid {
            color: #ef4444;
            opacity: 1;
        }
        
        .validation-message {
            font-size: 11px;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        
        .validation-message.show {
            display: flex;
        }
        
        .validation-message.error {
            color: #ef4444;
        }
        
        .validation-message.success {
            color: #22c55e;
        }
        
        .validation-message .msg-icon {
            font-size: 12px;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input.error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .form-input.success {
            border-color: #22c55e !important;
            background-color: #f0fdf4 !important;
        }
        
        .form-input:focus.error {
            ring-color: #ef4444 !important;
        }
        
        .form-input:focus.success {
            ring-color: #22c55e !important;
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
        
        .password-requirements .req-item .req-icon {
            font-size: 10px;
        }
        
        .password-requirements .req-item.met {
            color: #22c55e;
        }
        
        .password-requirements .req-item.unmet {
            color: #9ca3af;
        }

        /* Hospital selection specific styles */
        .hospital-select-wrapper {
            position: relative;
        }
        
        .hospital-select-wrapper select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        
        /* Icon wrapper for consistent sizing */
        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
        }
        
        .icon-wrapper-lg {
            width: 24px;
            height: 24px;
        }
        
        /* Form label icon */
        .label-icon {
            margin-right: 6px;
            font-size: 13px;
            color: #6b7280;
        }
        
        /* Remove Lucide-specific styles */
        [data-lucide] {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50 ">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>

            <!-- Main Content Area -->
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <div class="max-w-5xl mx-auto w-full" style="margin-top:60px;">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="doctors.php" class="p-2.5 border border-gray-200 rounded-xl hover:bg-white transition shadow-sm">
                            <i class="fas fa-arrow-left text-gray-500"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add New Doctor</h1>
                            <p class="text-gray-500 text-sm">Register a new medical professional with the required details.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="p-4 mb-8 rounded-2xl border <?php echo ($message_type == 'error') ? 'bg-red-50 text-red-700 border-red-100' : 'bg-green-50 text-green-700 border-green-100'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <div class="flex items-center gap-3">
                                <i class="fas <?php echo ($message_type == 'error') ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                                <span class="text-sm font-semibold"><?php echo $message; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Container -->
                    <form action="add_doctor.php" method="POST" enctype="multipart/form-data" id="doctorForm" novalidate>
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-6 md:p-10 space-y-12">
                                
                                <!-- Hospital Selection (Only for Super Admin) -->
                                <?php if ($is_super_admin && $hospitals_query && mysqli_num_rows($hospitals_query) > 0): ?>
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-green-50 rounded-lg text-green-600">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Hospital Selection</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-hospital label-icon"></i>Select Hospital <span class="required-star">*</span>
                                            </label>
                                            <div class="hospital-select-wrapper">
                                                <select name="hospital_id" id="hospital_id" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white" required>
                                                    <option value="">-- Select Hospital --</option>
                                                    <?php 
                                                    mysqli_data_seek($hospitals_query, 0);
                                                    while($hosp = mysqli_fetch_assoc($hospitals_query)): 
                                                    ?>
                                                        <option value="<?php echo $hosp['hospital_id']; ?>">
                                                            <?php echo htmlspecialchars($hosp['hospital_name']); ?>
                                                            <?php if ($hosp['city']): ?>
                                                                (<?php echo htmlspecialchars($hosp['city']); ?>)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="validation-message error" id="hospitalError">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Please select a hospital</span>
                                            </div>
                                            <small class="text-xs text-gray-400">
                                                <i class="fas fa-info-circle"></i> 
                                                Select the hospital where this doctor will be associated
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Basic Info Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Basic Information</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <!-- Full Name -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-user label-icon"></i>Full Name<span class="required-star">*</span>
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="doctor_name" id="doctor_name" placeholder="Dr. John Doe" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^[A-Za-z\s\-\'\\]+$" 
                                                    data-validation="name"
                                                    title="Only letters, spaces, hyphens, and apostrophes are allowed."
                                                    minlength="2" maxlength="100">
                                                <i class="fas fa-check-circle input-icon" id="doctor_name_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="doctor_name_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Only letters, spaces, hyphens, and apostrophes are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="doctor_name_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid name format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-envelope label-icon"></i>Email Address<span class="required-star">*</span>
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="email" id="email" type="email" placeholder="doctor@hospital.com" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                                    data-validation="email"
                                                    title="Please enter a valid email address."
                                                    maxlength="255">
                                                <i class="fas fa-check-circle input-icon" id="email_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="email_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Please enter a valid email address (e.g., doctor@hospital.com)</span>
                                            </div>
                                            <div class="validation-message success" id="email_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid email address</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-phone label-icon"></i>Mobile Number
                                            </label>
                                            <div class="input-wrapper">
                                                <input 
                                                    name="mobile" 
                                                    id="mobile" 
                                                    type="tel" 
                                                    placeholder="9876543210" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="[0-9]{10}" 
                                                    maxlength="10"
                                                    minlength="10"
                                                    data-validation="mobile"
                                                    title="Please enter exactly 10 digits (0-9)">
                                                <i class="fas fa-check-circle input-icon" id="mobile_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="mobile_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Please enter exactly 10 digits (0-9)</span>
                                            </div>
                                            <div class="validation-message success" id="mobile_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid 10-digit mobile number</span>
                                            </div>
                                            <small class="text-xs text-gray-400"><i class="fas fa-info-circle"></i> Enter exactly 10 digits (e.g., 9876543210)</small>
                                        </div>
                                        
                                        <!-- Password -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-lock label-icon"></i>Password<span class="required-star">*</span>
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="password" id="password" type="password" placeholder="Set secure password" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                                    data-validation="password"
                                                    title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character."
                                                    minlength="8" maxlength="128">
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
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Must be at least 8 characters with uppercase, lowercase, number & special character</span>
                                            </div>
                                            <div class="validation-message success" id="password_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Strong password</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Status -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-circle label-icon"></i>Status
                                            </label>
                                            <select name="status" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="Active"><i class="fas fa-check-circle text-green-500"></i> Active</option>
                                                <option value="Inactive"><i class="fas fa-times-circle text-red-500"></i> Inactive</option>
                                                <option value="On Leave"><i class="fas fa-clock text-yellow-500"></i> On Leave</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Details Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Professional Details</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <!-- Department -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-building label-icon"></i>Department <span class="required-star">*</span>
                                            </label>
                                            <select name="department" id="department" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white" required>
                                                <option value="">-- Select Department --</option>
                                                <?php 
                                                if ($department_query && mysqli_num_rows($department_query) > 0) {
                                                    mysqli_data_seek($department_query, 0);
                                                    while($dept = mysqli_fetch_assoc($department_query)) { 
                                                ?>
                                                    <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                                    </option>
                                                <?php 
                                                    }
                                                } else { ?>
                                                    <option value="" disabled>No departments available for this hospital</option>
                                                <?php } ?>
                                            </select>
                                            <?php if (!$department_query || mysqli_num_rows($department_query) == 0): ?>
                                            <small class="text-xs text-amber-600">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                No departments found for this hospital. Please add departments first.
                                            </small>
                                            <?php endif; ?>
                                            <div class="validation-message error" id="department_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Please select a department</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Specialization -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-stethoscope label-icon"></i>Specialization
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="specialization" id="specialization" placeholder="e.g. Interventional Cardiology" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="^[A-Za-z\s\-\.,]+$"
                                                    data-validation="specialization"
                                                    title="Only letters, spaces, hyphens, commas, and periods are allowed."
                                                    maxlength="100">
                                                <i class="fas fa-check-circle input-icon" id="specialization_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="specialization_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Only letters, spaces, hyphens, commas, and periods are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="specialization_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid specialization format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Qualification -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-graduation-cap label-icon"></i>Qualification
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="qualification" id="qualification" placeholder="e.g. MBBS, MD" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="^[A-Za-z\s\-\.,]+$"
                                                    data-validation="qualification"
                                                    title="Only letters, spaces, hyphens, commas, and periods are allowed."
                                                    maxlength="100">
                                                <i class="fas fa-check-circle input-icon" id="qualification_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="qualification_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Only letters, spaces, hyphens, commas, and periods are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="qualification_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid qualification format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Experience -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-clock label-icon"></i>Experience (Years)
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="experience" id="experience" type="number" placeholder="e.g. 10" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    min="0" max="100"
                                                    data-validation="experience"
                                                    title="Experience must be a non-negative number up to 100 years.">
                                                <i class="fas fa-check-circle input-icon" id="experience_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="experience_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Must be a non-negative number (0-100 years)</span>
                                            </div>
                                            <div class="validation-message success" id="experience_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid experience</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Consultation Fee -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-rupee-sign label-icon"></i>Consultation Fee (₹)
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="consultation_fee" id="consultation_fee" type="number" placeholder="e.g. 500" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    min="0" max="999999"
                                                    data-validation="fee"
                                                    title="Consultation fee must be a non-negative number.">
                                                <i class="fas fa-check-circle input-icon" id="consultation_fee_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="consultation_fee_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Must be a non-negative number</span>
                                            </div>
                                            <div class="validation-message success" id="consultation_fee_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid fee amount</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Timing -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-calendar-alt label-icon"></i>Available Timing
                                            </label>
                                            <div class="input-wrapper">
                                                <input name="timing" id="timing" placeholder="e.g. Mon-Fri, 9AM - 5PM" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="^[A-Za-z0-9\s\-\.,:]+$"
                                                    data-validation="timing"
                                                    title="Only letters, numbers, spaces, hyphens, commas, and colons are allowed."
                                                    maxlength="100">
                                                <i class="fas fa-check-circle input-icon" id="timing_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="timing_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Only letters, numbers, spaces, hyphens, commas, and colons are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="timing_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid timing format</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & Media Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                                            <i class="fas fa-map-pin"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Location & Media</h2>
                                    </div>
                                    
                                    <div class="space-y-8">
                                        <!-- Address -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-address-card label-icon"></i>Full Address
                                            </label>
                                            <div class="input-wrapper">
                                                <textarea name="address" id="address" placeholder="Enter complete clinic or residential address" 
                                                    class="form-input w-full min-h-[100px] p-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all resize-none" 
                                                    pattern="^[A-Za-z0-9\s\-\.,#\/]+$"
                                                    data-validation="address"
                                                    maxlength="500"></textarea>
                                            </div>
                                            <div class="validation-message error" id="address_error">
                                                <i class="fas fa-exclamation-circle msg-icon"></i>
                                                <span>Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="address_success">
                                                <i class="fas fa-check-circle msg-icon"></i>
                                                <span>Valid address format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Profile Photo -->
                                        <div class="space-y-4">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                <i class="fas fa-camera label-icon"></i>Profile Photo
                                            </label>
                                            <div class="flex flex-col sm:flex-row items-center gap-6 p-6 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                                <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center text-gray-300 border-2 border-white shadow-md overflow-hidden">
                                                    <i class="fas fa-camera text-2xl"></i>
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <input type="file" name="doctor_image" id="doctor_image" accept="image/*" class="w-full text-xs file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all">
                                                    <div class="validation-message error" id="doctor_image_error">
                                                        <i class="fas fa-exclamation-circle msg-icon"></i>
                                                        <span>Invalid image file</span>
                                                    </div>
                                                    <p class="text-[10px] text-gray-400 font-medium mt-3 uppercase tracking-wider">
                                                        <i class="fas fa-info-circle"></i> Recommended: Square image, max 2MB (JPG, PNG, GIF, WEBP)
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="px-6 md:px-10 py-8 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-4">
                                <button type="reset" class="w-full sm:w-auto px-8 py-3 rounded-xl border border-gray-200 text-gray-500 font-bold text-xs uppercase tracking-widest hover:bg-white transition text-center order-2 sm:order-1">
                                    <i class="fas fa-undo-alt mr-2"></i>Reset Form
                                </button>
                                <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2">
                                    <i class="fas fa-plus-circle mr-2"></i>Register Doctor
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle Logic
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

            // Close sidebar on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            // ============================================================
            // VALIDATION LOGIC
            // ============================================================
            
            // Define validation patterns
            const patterns = {
                name: /^[A-Za-z\s\-\'\\]+$/,
                email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                mobile: /^[0-9]{10}$/,
                password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
                specialization: /^[A-Za-z\s\-\.,]*$/,
                qualification: /^[A-Za-z\s\-\.,]*$/,
                timing: /^[A-Za-z0-9\s\-\.,:]*$/,
                address: /^[A-Za-z0-9\s\-\.,#\/]*$/
            };

            // Get all fields that need validation
            const fields = {
                doctor_name: { pattern: patterns.name, required: true, min: 2, max: 100 },
                email: { pattern: patterns.email, required: true, max: 255 },
                mobile: { pattern: patterns.mobile, required: false },
                password: { pattern: patterns.password, required: true, min: 8, max: 128 },
                specialization: { pattern: patterns.specialization, required: false, max: 100 },
                qualification: { pattern: patterns.qualification, required: false, max: 100 },
                timing: { pattern: patterns.timing, required: false, max: 100 },
                address: { pattern: patterns.address, required: false, max: 500 }
            };

            // Number fields validation
            const numberFields = ['experience', 'consultation_fee'];
            const numberRanges = {
                experience: { min: 0, max: 100 },
                consultation_fee: { min: 0, max: 999999 }
            };

            // Function to validate a single field
            function validateField(fieldId) {
                const input = document.getElementById(fieldId);
                if (!input) return true;

                const value = input.value.trim();
                const fieldConfig = fields[fieldId];
                const isRequired = fieldConfig ? fieldConfig.required : false;
                const pattern = fieldConfig ? fieldConfig.pattern : null;
                const minLength = fieldConfig ? fieldConfig.min : null;
                const maxLength = fieldConfig ? fieldConfig.max : null;

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
                    if (errorMsg) {
                        errorMsg.querySelector('span').textContent = 'This field is required';
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // If optional and empty, it's valid
                if (!isRequired && value === '') {
                    input.classList.add('success');
                    if (successMsg) {
                        successMsg.querySelector('span').textContent = 'Optional field (empty is valid)';
                        successMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('valid');
                    return true;
                }

                // Check min/max length
                if (minLength && value.length < minLength) {
                    input.classList.add('error');
                    if (errorMsg) {
                        errorMsg.querySelector('span').textContent = 'Minimum ' + minLength + ' characters required';
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }
                if (maxLength && value.length > maxLength) {
                    input.classList.add('error');
                    if (errorMsg) {
                        errorMsg.querySelector('span').textContent = 'Maximum ' + maxLength + ' characters allowed';
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // Test against pattern
                if (pattern && !pattern.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) {
                        // Get the title attribute for more specific error message
                        const title = input.getAttribute('title') || 'Invalid format';
                        const errorSpan = errorMsg.querySelector('span');
                        if (errorSpan) errorSpan.textContent = title;
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // Special validation for number fields
                if (numberFields.includes(fieldId)) {
                    const numValue = parseFloat(value);
                    const range = numberRanges[fieldId];
                    if (isNaN(numValue) || numValue < range.min || numValue > range.max) {
                        input.classList.add('error');
                        if (errorMsg) {
                            errorMsg.querySelector('span').textContent = 'Must be between ' + range.min + ' and ' + range.max;
                            errorMsg.classList.add('show');
                        }
                        if (icon) icon.classList.add('invalid');
                        return false;
                    }
                    
                    // Check if it's a whole number for experience
                    if (fieldId === 'experience' && !Number.isInteger(numValue)) {
                        input.classList.add('error');
                        if (errorMsg) {
                            errorMsg.querySelector('span').textContent = 'Experience must be a whole number';
                            errorMsg.classList.add('show');
                        }
                        if (icon) icon.classList.add('invalid');
                        return false;
                    }
                }

                // All validations passed
                input.classList.add('success');
                if (successMsg) {
                    const successSpan = successMsg.querySelector('span');
                    if (successSpan) successSpan.textContent = '✓ Valid';
                    successMsg.classList.add('show');
                }
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

                // Update requirement indicators
                document.getElementById('reqLength').className = `req-item ${checks.length ? 'met' : 'unmet'}`;
                document.getElementById('reqUpper').className = `req-item ${checks.upper ? 'met' : 'unmet'}`;
                document.getElementById('reqLower').className = `req-item ${checks.lower ? 'met' : 'unmet'}`;
                document.getElementById('reqNumber').className = `req-item ${checks.number ? 'met' : 'unmet'}`;
                document.getElementById('reqSpecial').className = `req-item ${checks.special ? 'met' : 'unmet'}`;

                // Calculate score
                if (checks.length) score++;
                if (checks.upper) score++;
                if (checks.lower) score++;
                if (checks.number) score++;
                if (checks.special) score++;

                // Update strength bar
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

            // Attach event listeners for real-time validation
            Object.keys(fields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                // Validate on blur
                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });

                // Validate on input for better UX
                input.addEventListener('input', function() {
                    validateField(fieldId);
                    
                    // Special handling for password
                    if (fieldId === 'password') {
                        checkPasswordStrength(this.value);
                    }
                });
            });

            // Special handling for password field
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
            }

            // Number fields validation
            numberFields.forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                input.addEventListener('input', function() {
                    // Prevent negative values
                    if (this.value.startsWith('-')) {
                        this.value = this.value.slice(1);
                    }
                    validateField(fieldId);
                });

                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });
            });

            // Department validation
            const departmentSelect = document.getElementById('department');
            if (departmentSelect) {
                departmentSelect.addEventListener('change', function() {
                    const errorMsg = document.getElementById('department_error');
                    if (this.value === '') {
                        this.classList.add('error');
                        if (errorMsg) errorMsg.classList.add('show');
                    } else {
                        this.classList.remove('error');
                        if (errorMsg) errorMsg.classList.remove('show');
                    }
                });
            }

            // Image validation
            const imageInput = document.getElementById('doctor_image');
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    const errorMsg = document.getElementById('doctor_image_error');
                    const file = this.files[0];
                    
                    if (file) {
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        const maxSize = 2 * 1024 * 1024; // 2MB
                        
                        if (!allowedTypes.includes(file.type)) {
                            errorMsg.querySelector('span').textContent = 'Invalid format. Only JPG, PNG, GIF, WEBP allowed.';
                            errorMsg.classList.add('show');
                            this.value = '';
                        } else if (file.size > maxSize) {
                            errorMsg.querySelector('span').textContent = 'File size exceeds 2MB limit.';
                            errorMsg.classList.add('show');
                            this.value = '';
                        } else {
                            errorMsg.classList.remove('show');
                        }
                    } else {
                        errorMsg.classList.remove('show');
                    }
                });
            }

            // Form submission validation
            document.getElementById('doctorForm').addEventListener('submit', function(e) {
                let isValid = true;

                // Validate hospital selection for super admin
                <?php if ($is_super_admin): ?>
                const hospitalSelect = document.getElementById('hospital_id');
                const hospitalError = document.getElementById('hospitalError');
                if (!hospitalSelect || !hospitalSelect.value) {
                    isValid = false;
                    if (hospitalSelect) {
                        hospitalSelect.classList.add('error');
                        hospitalSelect.style.borderColor = '#ef4444';
                    }
                    if (hospitalError) {
                        hospitalError.classList.add('show');
                    }
                } else {
                    if (hospitalSelect) {
                        hospitalSelect.classList.remove('error');
                        hospitalSelect.style.borderColor = '';
                    }
                    if (hospitalError) {
                        hospitalError.classList.remove('show');
                    }
                }
                <?php endif; ?>

                // Validate department
                const deptSelect = document.getElementById('department');
                if (deptSelect && deptSelect.value === '') {
                    isValid = false;
                    deptSelect.classList.add('error');
                    const deptError = document.getElementById('department_error');
                    if (deptError) deptError.classList.add('show');
                }

                // Validate all fields
                Object.keys(fields).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                // Validate number fields
                numberFields.forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = document.querySelector('.form-input.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    <?php if ($is_super_admin): ?>
                    const hospitalErrorEl = document.getElementById('hospital_id');
                    if (hospitalErrorEl && hospitalErrorEl.classList.contains('error')) {
                        hospitalErrorEl.focus();
                        hospitalErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    <?php endif; ?>
                }
            });

            // Reset form - clear validation states
            document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
                setTimeout(() => {
                    document.querySelectorAll('.form-input').forEach(input => {
                        input.classList.remove('error', 'success');
                    });
                    document.querySelectorAll('.validation-message').forEach(msg => {
                        msg.classList.remove('show');
                    });
                    document.querySelectorAll('.input-icon').forEach(icon => {
                        icon.classList.remove('valid', 'invalid');
                    });
                    // Reset password strength
                    const strengthBar = document.getElementById('strengthBar');
                    const strengthText = document.getElementById('strengthText');
                    if (strengthBar) strengthBar.className = 'strength-bar';
                    if (strengthText) {
                        strengthText.textContent = 'Weak';
                        strengthText.style.color = '#9ca3af';
                    }
                    // Reset hospital selection
                    <?php if ($is_super_admin): ?>
                    const hospitalSelect = document.getElementById('hospital_id');
                    if (hospitalSelect) {
                        hospitalSelect.classList.remove('error');
                        hospitalSelect.style.borderColor = '';
                    }
                    const hospitalError = document.getElementById('hospitalError');
                    if (hospitalError) {
                        hospitalError.classList.remove('show');
                    }
                    <?php endif; ?>
                    // Reset department
                    const deptSelect = document.getElementById('department');
                    if (deptSelect) {
                        deptSelect.classList.remove('error');
                        const deptError = document.getElementById('department_error');
                        if (deptError) deptError.classList.remove('show');
                    }
                }, 10);
            });

            // Mobile number validation - exactly 10 digits
            function validateMobile(input) {
                const value = input.value.trim();
                const errorMsg = document.getElementById('mobile_error');
                const successMsg = document.getElementById('mobile_success');
                const icon = document.getElementById('mobile_icon');
                
                // Reset states
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }
                
                // If empty, it's optional
                if (value === '') {
                    input.classList.add('success');
                    if (successMsg) {
                        successMsg.querySelector('span').textContent = 'Optional (empty is valid)';
                        successMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('valid');
                    return true;
                }
                
                // Check if exactly 10 digits
                const mobileRegex = /^[0-9]{10}$/;
                if (!mobileRegex.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) {
                        const span = errorMsg.querySelector('span');
                        if (value.length > 0 && value.length < 10) {
                            span.textContent = 'Please enter exactly 10 digits (currently ' + value.length + ' digits)';
                        } else if (value.length > 10) {
                            span.textContent = 'Maximum 10 digits allowed (currently ' + value.length + ' digits)';
                        } else {
                            span.textContent = 'Please enter exactly 10 digits (0-9 only)';
                        }
                        errorMsg.classList.add('show');
                    }
                    if (icon) icon.classList.add('invalid');
                    return false;
                }
                
                // Valid
                input.classList.add('success');
                if (successMsg) {
                    successMsg.querySelector('span').textContent = 'Valid 10-digit mobile number';
                    successMsg.classList.add('show');
                }
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Attach to mobile input
            const mobileInput = document.getElementById('mobile');
            if (mobileInput) {
                // Validate on input (real-time)
                mobileInput.addEventListener('input', function() {
                    // Remove non-digits
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limit to 10 characters
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }
                    
                    validateMobile(this);
                });
                
                // Validate on blur
                mobileInput.addEventListener('blur', function() {
                    validateMobile(this);
                });
                
                // Prevent pasting non-digits
                mobileInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedText.replace(/[^0-9]/g, '');
                    if (digits.length > 0) {
                        this.value = digits.slice(0, 10);
                        validateMobile(this);
                    }
                });
            }

            // Hospital selection validation on change
            <?php if ($is_super_admin): ?>
            const hospitalSelect = document.getElementById('hospital_id');
            if (hospitalSelect) {
                hospitalSelect.addEventListener('change', function() {
                    const hospitalError = document.getElementById('hospitalError');
                    if (this.value) {
                        this.classList.remove('error');
                        this.style.borderColor = '';
                        if (hospitalError) {
                            hospitalError.classList.remove('show');
                        }
                    }
                });
            }
            <?php endif; ?>

            // ============================================================
            // DYNAMIC DEPARTMENT LOADING ON HOSPITAL CHANGE
            // ============================================================
            <?php if ($is_super_admin): ?>
            const hospitalSelect2 = document.getElementById('hospital_id');
            const departmentSelect2 = document.getElementById('department');
            
            if (hospitalSelect2 && departmentSelect2) {
                hospitalSelect2.addEventListener('change', function() {
                    const hospitalId = this.value;
                    
                    // Clear current department options
                    departmentSelect2.innerHTML = '<option value="">Loading departments...</option>';
                    
                    if (hospitalId) {
                        // Fetch departments for selected hospital
                        fetch('get_departments.php?hospital_id=' + hospitalId)
                            .then(response => response.json())
                            .then(data => {
                                departmentSelect2.innerHTML = '<option value="">-- Select Department --</option>';
                                
                                if (data.success && data.departments && data.departments.length > 0) {
                                    data.departments.forEach(dept => {
                                        const option = document.createElement('option');
                                        option.value = dept.department_name;
                                        option.textContent = dept.department_name;
                                        departmentSelect2.appendChild(option);
                                    });
                                    // Remove error if any
                                    departmentSelect2.classList.remove('error');
                                    const deptError = document.getElementById('department_error');
                                    if (deptError) deptError.classList.remove('show');
                                } else {
                                    const option = document.createElement('option');
                                    option.value = '';
                                    option.textContent = 'No departments available';
                                    option.disabled = true;
                                    departmentSelect2.appendChild(option);
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching departments:', error);
                                departmentSelect2.innerHTML = '<option value="">-- Select Department --</option>';
                                const option = document.createElement('option');
                                option.value = '';
                                option.textContent = 'Error loading departments';
                                option.disabled = true;
                                departmentSelect2.appendChild(option);
                            });
                    } else {
                        // Reset to initial state
                        departmentSelect2.innerHTML = '<option value="">-- Select Department --</option>';
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No departments available';
                        option.disabled = true;
                        departmentSelect2.appendChild(option);
                    }
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>