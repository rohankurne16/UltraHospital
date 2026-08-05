<?php
session_start();
include "../config/hospital.php";
include "../config/send_registration_email.php";

$hid = $_SESSION["hospital_id"];

// Fetch hospital name for title
$hospital_name = '';
if ($hid) {
    $query = "SELECT hospital_name FROM hospital_master WHERE hospital_id = $hid";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $hospital_name = $row['hospital_name'];
    }
}

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

// Staff roles
$staff_roles = array(
    "Nurse",
    "Ward Boy",
    "Lab Technician",
    "Patient",
    "Billing Staff",
    "Accountant",
    "Pharmacist",
    "Receptionist"
);

// Gender options
$gender_options = ['Male', 'Female', 'Other'];

// Blood group options
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

// Shift timing options
$shift_timings = [
    'Morning (7 AM - 3 PM)',
    'Evening (3 PM - 11 PM)',
    'Night (11 PM - 7 AM)',
    'Rotational',
    'Flexible'
];

// Nurse type options
$nurse_types = [
    'Staff Nurse',
    'Senior Nurse',
    'Head Nurse',
    'Assistant Nurse',
    'Trainee'
];

// Qualification options
$qualifications = [
    'B.Sc. Nursing',
    'M.Sc. Nursing',
    'G.N.M.',
    'A.N.M.',
    'Diploma in Nursing',
    'Ph.D. Nursing',
    'Other'
];

// Specialization options
$specializations = [
    'Critical Care',
    'Cardiac Care',
    'Pediatric',
    'Orthopedic',
    'Neurology',
    'Oncology',
    'Emergency',
    'ICU',
    'NICU',
    'General'
];

// Department options
$departments = [
    'ICU',
    'NICU',
    'Emergency',
    'Cardiology',
    'Neurology',
    'Orthopedics',
    'Pediatrics',
    'Oncology',
    'General Ward',
    'OT'
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    // Get hospital_id from POST or use session
    $hospital_id = isset($_POST['hospital_id']) && !empty($_POST['hospital_id']) 
        ? (int)$_POST['hospital_id'] 
        : $hid;
    
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $selectrole = $_POST['selectrole'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    // New fields
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $age = $_POST['age'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $blood_group = $_POST['blood_group'] ?? null;
    $qualification = $_POST['qualification'] ?? null;
    $specialization = $_POST['specialization'] ?? null;
    $department = $_POST['department'] ?? null;
    $experience = $_POST['experience'] ?? null;
    $shift_timing = $_POST['shift_timing'] ?? null;
    $emergency_contact = $_POST['emergency_contact'] ?? null;
    $nurse_type = $_POST['nurse_type'] ?? null;

    // Server-side Validation
    if (!preg_match("/^[A-Za-z\s'-]+$/", $name)) {
        $message = "Invalid Name. Only letters, spaces, hyphens, and apostrophes are allowed.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid Email Address.";
        $message_type = "error";
    } elseif (!empty($mobile) && !preg_match('/^[0-9]{10}$/', preg_replace('/[\s\-+]/', '', $mobile))) {
        $message = "Invalid Mobile Number. Must be exactly 10 digits.";
        $message_type = "error";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$/', $password)) {
        $message = "Invalid Password. Must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
        $message_type = "error";
    } elseif (!in_array($selectrole, $staff_roles)) {
        $message = "Invalid Role selected.";
        $message_type = "error";
    } elseif (!empty($address) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $address)) {
        $message = "Invalid Address. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
        $message_type = "error";
    } elseif (!in_array($status, ['Active', 'Inactive'])) {
        $message = "Invalid Status selected.";
        $message_type = "error";
    } elseif ($is_super_admin && empty($hospital_id)) {
        $message = "Please select a hospital.";
        $message_type = "error";
    }
    
    // Image Upload Handling
    $staff_image = "";
    if (!empty($_FILES['staff_image']['name']) && $_FILES['staff_image']['error'] == 0) {
        $folder = "documents/staff/images/";
        
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['staff_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'staff_' . time() . '_' . uniqid() . '.' . $file_extension;
        $image_path = $folder . $new_filename;
        
        if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $image_path)) {
            $staff_image = "documents/staff/images/" . $new_filename;
        } else {
            $message = "Failed to upload image. Please check folder permissions.";
            $message_type = "error";
        }
    }

    // Only proceed if no error or image upload success
    if (empty($message) || $message_type != "error") {
        $conn->begin_transaction();

        try {
            // Insert into register table
            $stmt_reg = $conn->prepare("INSERT INTO register (name, email, password, role, created_by, modified_by, hospital_id) VALUES (?, ?, ?, ?, 'Admin', 'Admin', ?)");
            $stmt_reg->bind_param("ssssi", $name, $email, $password, $selectrole, $hospital_id);
            
            if ($stmt_reg->execute()) {
                $register_id = $conn->insert_id;

                // Insert into staff table with all new fields
                $stmt_staff = $conn->prepare("INSERT INTO staff (
                    register_id, name, mobile, email, role, address, status, profile_image, hospital_id,
                    date_of_birth, age, gender, blood_group, qualification, specialization, 
                    department, experience, shift_timing, emergency_contact, nurse_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt_staff->bind_param(
                    "isssssssisssssssisss",
                    $register_id, 
                    $name, 
                    $mobile, 
                    $email, 
                    $selectrole, 
                    $address, 
                    $status, 
                    $staff_image, 
                    $hospital_id,
                    $date_of_birth,
                    $age,
                    $gender,
                    $blood_group,
                    $qualification,
                    $specialization,
                    $department,
                    $experience,
                    $shift_timing,
                    $emergency_contact,
                    $nurse_type
                );
                
                if ($stmt_staff->execute()) {
                    $conn->commit();

                    $mailSent = sendRegistrationEmail(
                        $conn,
                        $hospital_id,
                        $name,
                        $email,
                        $password
                    );

                    if (!$mailSent) {
                        error_log("Staff registration email could not be sent to: " . $email);
                    }

                    header("Location: staff.php?msg=Staff added");
                    exit();
                } else {
                    throw new Exception("Unable to Add Staff details.");
                }
            } else {
                throw new Exception("Unable to Register user.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff - <?php echo htmlspecialchars($hospital_name ?: 'Hospital'); ?></title> 
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
       
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
        #main-content {
            margin-left: 250px; 
            padding: 1.5rem; 
            min-height: 100vh;
            margin-top: 70px;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .required-star { color: #ef4444; margin-left: 2px; }
        
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

        /* Conditional field visibility */
        .nurse-fields {
            display: none;
        }
        .nurse-fields.visible {
            display: block;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50 ">
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'sidebar.php'; ?>

            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="staff.php" class="p-2.5 border border-gray-200 rounded-xl hover:bg-white transition shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5 text-gray-500"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add New Staff Member</h1>
                            <p class="text-gray-500 text-sm">Register a new staff member with the required details.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="p-4 mb-8 rounded-2xl border <?php echo ($message_type == 'error') ? 'bg-red-50 text-red-700 border-red-100' : 'bg-green-50 text-green-700 border-green-100'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <div class="flex items-center gap-3">
                                <i data-lucide="<?php echo ($message_type == 'error') ? 'alert-circle' : 'check-circle'; ?>" class="w-5 h-5"></i>
                                <span class="text-sm font-semibold"><?php echo $message; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Container -->
                    <form action="add_staff.php" method="POST" enctype="multipart/form-data" id="staffForm" novalidate>
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-6 md:p-10 space-y-12">
                                
                                <!-- Hospital Selection (Only for Super Admin) -->
                                <?php if ($is_super_admin && $hospitals_query && mysqli_num_rows($hospitals_query) > 0): ?>
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-green-50 rounded-lg text-green-600">
                                            <i data-lucide="building-2" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Hospital Selection</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Select Hospital <span class="required-star">*</span></label>
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
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Please select a hospital</span>
                                            </div>
                                            <small class="text-xs text-gray-400">
                                                <i class="fas fa-info-circle"></i> 
                                                Select the hospital where this staff member will be associated
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Basic Info Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                            <i data-lucide="user" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Basic Information</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <!-- Full Name -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Name <span class="required-star">*</span></label>
                                            <div class="input-wrapper">
                                                <input name="name" id="name" placeholder="Enter full name" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^[A-Za-z\s\-\'\\]+$" 
                                                    data-validation="name"
                                                    title="Only letters, spaces, hyphens, and apostrophes are allowed.">
                                                <i class="fas fa-check-circle input-icon" id="name_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="name_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, spaces, hyphens, and apostrophes are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="name_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid name format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address <span class="required-star">*</span></label>
                                            <div class="input-wrapper">
                                                <input name="email" id="email" type="email" placeholder="staff@hospital.com" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                                    data-validation="email"
                                                    title="Please enter a valid email address.">
                                                <i class="fas fa-check-circle input-icon" id="email_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="email_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Please enter a valid email address (e.g., staff@hospital.com)</span>
                                            </div>
                                            <div class="validation-message success" id="email_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid email address</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mobile Number</label>
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
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Please enter exactly 10 digits (0-9)</span>
                                            </div>
                                            <div class="validation-message success" id="mobile_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid 10-digit mobile number</span>
                                            </div>
                                            <small class="text-xs text-gray-400">Enter exactly 10 digits (e.g., 9876543210)</small>
                                        </div>
                                        
                                        <!-- Password -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Password <span class="required-star">*</span></label>
                                            <div class="input-wrapper">
                                                <input name="password" id="password" type="password" placeholder="Set secure password" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                                    data-validation="password"
                                                    title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.">
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
                                        
                                        <!-- Role -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Role <span class="required-star">*</span></label>
                                            <select name="selectrole" id="selectrole" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white" required>
                                                <option value="">Select Role</option>
                                                <?php foreach ($staff_roles as $role_option): ?>
                                                    <option value="<?php echo $role_option; ?>">
                                                        <?php echo $role_option; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Status -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</label>
                                            <select name="status" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </div>

                                        <!-- Gender -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Gender</label>
                                            <select name="gender" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Gender</option>
                                                <?php foreach ($gender_options as $gender): ?>
                                                    <option value="<?php echo $gender; ?>"><?php echo $gender; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Date of Birth -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date of Birth</label>
                                            <input name="date_of_birth" id="date_of_birth" type="date" 
                                                class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>

                                        <!-- Age -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Age</label>
                                            <input name="age" id="age" type="number" placeholder="e.g., 30" 
                                                class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all"
                                                min="18" max="65">
                                        </div>

                                        <!-- Blood Group -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Blood Group</label>
                                            <select name="blood_group" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Blood Group</option>
                                                <?php foreach ($blood_groups as $bg): ?>
                                                    <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Emergency Contact -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Emergency Contact</label>
                                            <input name="emergency_contact" id="emergency_contact" type="tel" placeholder="9876543210" 
                                                class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all"
                                                pattern="[0-9]{10}" maxlength="10" minlength="10">
                                            <small class="text-xs text-gray-400">Emergency contact number (10 digits)</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nurse Specific Fields (Conditional) -->
                                <div class="space-y-8 nurse-fields" id="nurseFields">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                                            <i data-lucide="heart-pulse" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Nurse Details</h2>
                                        <span class="ml-auto text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-semibold">Nurse Only</span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <!-- Nurse Type -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nurse Type</label>
                                            <select name="nurse_type" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Nurse Type</option>
                                                <?php foreach ($nurse_types as $nt): ?>
                                                    <option value="<?php echo $nt; ?>"><?php echo $nt; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Qualification -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Qualification</label>
                                            <select name="qualification" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Qualification</option>
                                                <?php foreach ($qualifications as $qual): ?>
                                                    <option value="<?php echo $qual; ?>"><?php echo $qual; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Specialization -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Specialization</label>
                                            <select name="specialization" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Specialization</option>
                                                <?php foreach ($specializations as $spec): ?>
                                                    <option value="<?php echo $spec; ?>"><?php echo $spec; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Department -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Department</label>
                                            <select name="department" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Experience -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Experience (Years)</label>
                                            <input name="experience" type="number" placeholder="e.g., 5" 
                                                class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all"
                                                min="0" max="50">
                                        </div>

                                        <!-- Shift Timing -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Shift Timing</label>
                                            <select name="shift_timing" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="">Select Shift</option>
                                                <?php foreach ($shift_timings as $shift): ?>
                                                    <option value="<?php echo $shift; ?>"><?php echo $shift; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Joining Date -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Joining Date</label>
                                            <input name="joining_date" type="date" 
                                                class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & Media Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Location & Media</h2>
                                    </div>
                                    
                                    <div class="space-y-8">
                                        <!-- Address -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Address</label>
                                            <div class="input-wrapper">
                                                <textarea name="address" id="address" placeholder="Enter complete address" 
                                                    class="form-input w-full min-h-[100px] p-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all resize-none" 
                                                    pattern="^[A-Za-z0-9\s\-\.,#\/]+$"
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
                                        
                                        <!-- Profile Photo -->
                                        <div class="space-y-4">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Profile Photo</label>
                                            <div class="flex flex-col sm:flex-row items-center gap-6 p-6 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                                <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center text-gray-300 border-2 border-white shadow-md overflow-hidden">
                                                    <i data-lucide="camera" class="w-8 h-8"></i>
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <input type="file" name="staff_image" accept="image/*" class="w-full text-xs file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all">
                                                    <p class="text-[10px] text-gray-400 font-medium mt-3 uppercase tracking-wider">Recommended: Square image, max 2MB (JPG, PNG)</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="px-6 md:px-10 py-8 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-4">
                                <button type="reset" class="w-full sm:w-auto px-8 py-3 rounded-xl border border-gray-200 text-gray-500 font-bold text-xs uppercase tracking-widest hover:bg-white transition text-center order-2 sm:order-1">Reset Form</button>
                                <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2">Register Staff</button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Toggle Nurse Fields based on role selection
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('selectrole');
            const nurseFields = document.getElementById('nurseFields');
            
            function toggleNurseFields() {
                if (roleSelect.value === 'Nurse') {
                    nurseFields.classList.add('visible');
                } else {
                    nurseFields.classList.remove('visible');
                }
            }
            
            // Check on load
            toggleNurseFields();
            
            // Check on change
            roleSelect.addEventListener('change', toggleNurseFields);
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
                address: /^[A-Za-z0-9\s\-\.,#\/]+$/
            };

            // Get all fields that need validation
            const fields = {
                name: { pattern: patterns.name, required: true },
                email: { pattern: patterns.email, required: true },
                mobile: { pattern: patterns.mobile, required: false },
                password: { pattern: patterns.password, required: true },
                address: { pattern: patterns.address, required: false }
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

                // All validations passed
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

                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });

                input.addEventListener('input', function() {
                    validateField(fieldId);
                    
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

            // Mobile number validation
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

            const mobileInput = document.getElementById('mobile');
            if (mobileInput) {
                mobileInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }
                    validateMobile(this);
                });
                
                mobileInput.addEventListener('blur', function() {
                    validateMobile(this);
                });
                
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

            // Age calculation from DOB
            const dobInput = document.getElementById('date_of_birth');
            const ageInput = document.getElementById('age');
            
            if (dobInput && ageInput) {
                dobInput.addEventListener('change', function() {
                    if (this.value) {
                        const birthDate = new Date(this.value);
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                        if (age >= 18 && age <= 65) {
                            ageInput.value = age;
                        } else {
                            ageInput.value = '';
                            alert('Age must be between 18 and 65 years');
                            this.value = '';
                        }
                    }
                });
            }

            // Form submission validation
            document.getElementById('staffForm').addEventListener('submit', function(e) {
                let isValid = true;

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

                Object.keys(fields).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    const firstError = document.querySelector('.form-input.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    <?php if ($is_super_admin): ?>
                    const hospitalError = document.getElementById('hospital_id');
                    if (hospitalError && hospitalError.classList.contains('error')) {
                        hospitalError.focus();
                        hospitalError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    <?php endif; ?>
                }
            });

            // Reset form
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
                    const strengthBar = document.getElementById('strengthBar');
                    const strengthText = document.getElementById('strengthText');
                    if (strengthBar) strengthBar.className = 'strength-bar';
                    if (strengthText) {
                        strengthText.textContent = 'Weak';
                        strengthText.style.color = '#9ca3af';
                    }
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
                }, 10);
            });

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
        });
    </script>
</body>
</html>