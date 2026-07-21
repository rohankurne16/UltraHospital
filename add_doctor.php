<?php
session_start();
include "config/hospital.php";
include "config/send_registration_email.php";

$hid=$_SESSION["hospital_id"];

// Initialize variables
$message = "";
$message_type = "";

// Fetch active departments for the dropdown
$department_query = mysqli_query($conn, "
    SELECT department_name
    FROM department
    WHERE status = 'Active'
    and hospital_id='$hid'
    AND (delete_flag = 0 OR delete_flag IS NULL)
    ORDER BY department_name ASC
");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $doctor_name = $_POST['doctor_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $department = $_POST['department'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $experience = $_POST['experience'];
    $consultation_fee = $_POST['consultation_fee'];
    $timing = $_POST['timing'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    // Server-side Validation
   if (!preg_match("/^[A-Za-z\s'-]+$/", $doctor_name)) {
        $message = "Invalid Doctor Name. Only letters, spaces, hyphens, and apostrophes are allowed.";
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
    } elseif (!empty($specialization) && !preg_match('/^[A-Za-z\s\-\.,]+$/', $specialization)) {
        $message = "Invalid Specialization. Only letters, spaces, hyphens, commas, and periods are allowed.";
        $message_type = "error";
    } elseif (!empty($qualification) && !preg_match('/^[A-Za-z\s\-\.,]+$/', $qualification)) {
        $message = "Invalid Qualification. Only letters, spaces, hyphens, commas, and periods are allowed.";
        $message_type = "error";
    } elseif (!empty($experience) && (!is_numeric($experience) || $experience < 0)) {
        $message = "Invalid Experience. Must be a non-negative number.";
        $message_type = "error";
    } elseif (!empty($consultation_fee) && (!is_numeric($consultation_fee) || $consultation_fee < 0)) {
        $message = "Invalid Consultation Fee. Must be a non-negative number.";
        $message_type = "error";
    } elseif (!empty($timing) && !preg_match('/^[A-Za-z0-9\s\-\.,:]+$/', $timing)) {
        $message = "Invalid Timing. Only letters, numbers, spaces, hyphens, commas, and colons are allowed.";
        $message_type = "error";
    } elseif (!empty($address) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $address)) {
        $message = "Invalid Address. Only letters, numbers, spaces, hyphens, commas, periods, hash, and slashes are allowed.";
        $message_type = "error";
    } elseif (!in_array($status, ['Active', 'Inactive', 'On Leave'])) {
        $message = "Invalid Status selected.";
        $message_type = "error";
    }
    
    // Image Upload Handling
    $doctor_image = "";
    if (!empty($_FILES['doctor_image']['name']) && $_FILES['doctor_image']['error'] == 0) {
        $folder = "documents/doctors/images/";
        
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        
        $image_name = basename($_FILES['doctor_image']['name']);
        $image_path = $folder . $image_name;
        
        if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $image_path)) {
            $doctor_image = "documents/doctors/images/" . $image_name;
        } else {
            $message = "Failed to upload image. Please check folder permissions.";
            $message_type = "error";
        }
    }

    // Only proceed if no error or image upload success
    if (empty($message) || $message_type != "error") {
        $conn->begin_transaction();

        try {
            $stmt_reg = $conn->prepare("INSERT INTO register (name, email, password, role, created_by, modified_by, hospital_id) VALUES (?, ?, ?, 'doctor', 'Admin', 'Admin', ?)");
            $stmt_reg->bind_param("sssi", $doctor_name, $email, $password, $hid);
            
            if ($stmt_reg->execute()) {
                $register_id = $conn->insert_id;

                $stmt_doc = $conn->prepare("INSERT INTO doctor (register_id, doctor_name, doctor_image, mobile, email, department, qualification, specialization, experience, consultation_fee, timing, address, status, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_doc->bind_param("issssssssisssi", $register_id, $doctor_name, $doctor_image, $mobile, $email, $department, $qualification, $specialization, $experience, $consultation_fee, $timing, $address, $status, $hid);
                
                if ($stmt_doc->execute()) {
                    $conn->commit();

                    $mailSent = sendRegistrationEmail(
                        $conn,
                        $hid,
                        $doctor_name,
                        $email,
                        $password
                    );

                    if (!$mailSent) {
                        error_log("Doctor registration email could not be sent to: " . $email);
                    }

                    header("Location: doctors.php?msg=Doctor added");
                    exit();
                } else {
                    throw new Exception("Unable to Add Doctor details.");
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
    <title>Add Doctor - <?php echo $hospital['hospital_name'] ?></title> 
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <div class="max-w-5xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                       
                        <a href="doctors.php" class="p-2.5 border border-gray-200 rounded-xl hover:bg-white transition shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5 text-gray-500"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add New Doctor</h1>
                            <p class="text-gray-500 text-sm">Register a new medical professional with the required details.</p>
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
                    <form action="add_doctor.php" method="POST" enctype="multipart/form-data" id="doctorForm" novalidate>
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-6 md:p-10 space-y-12">
                                
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
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Name<span class="required-star">*</span></label>
                                            <div class="input-wrapper">
                                                <input name="doctor_name" id="doctor_name" placeholder="Dr. John Doe" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^[A-Za-z\s\-\'\\]+$" 
                                                    data-validation="name"
                                                    title="Only letters, spaces, hyphens, and apostrophes are allowed.">
                                                <i class="fas fa-check-circle input-icon" id="doctor_name_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="doctor_name_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, spaces, hyphens, and apostrophes are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="doctor_name_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid name format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address<span class="required-star">*</span></label>
                                            <div class="input-wrapper">
                                                <input name="email" id="email" type="email" placeholder="doctor@hospital.com" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                                    data-validation="email"
                                                    title="Please enter a valid email address.">
                                                <i class="fas fa-check-circle input-icon" id="email_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="email_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Please enter a valid email address (e.g., doctor@hospital.com)</span>
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
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Password<span class="required-star">*</span></label>
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
                                        
                                        <!-- Status -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</label>
                                            <select name="status" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                                <option value="On Leave">On Leave</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Details Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                            <i data-lucide="briefcase" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Professional Details</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <!-- Department -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Department <span class="required-star">*</span></label>
                                            <select name="department" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white" required>
                                                <option value="">-- Select Department --</option>
                                                <?php while($dept = mysqli_fetch_assoc($department_query)) { ?>
                                                    <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Specialization -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Specialization</label>
                                            <div class="input-wrapper">
                                                <input name="specialization" id="specialization" placeholder="e.g. Interventional Cardiology" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="^[A-Za-z\s\-\.,]+$"
                                                    data-validation="specialization"
                                                    title="Only letters, spaces, hyphens, commas, and periods are allowed.">
                                                <i class="fas fa-check-circle input-icon" id="specialization_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="specialization_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, spaces, hyphens, commas, and periods are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="specialization_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid specialization format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Qualification -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Qualification</label>
                                            <div class="input-wrapper">
                                                <input name="qualification" id="qualification" placeholder="e.g. MBBS, MD" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="^[A-Za-z\s\-\.,]+$"
                                                    data-validation="qualification"
                                                    title="Only letters, spaces, hyphens, commas, and periods are allowed.">
                                                <i class="fas fa-check-circle input-icon" id="qualification_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="qualification_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, spaces, hyphens, commas, and periods are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="qualification_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid qualification format</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Experience -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Experience (Years)</label>
                                            <div class="input-wrapper">
                                                <input name="experience" id="experience" type="number" placeholder="e.g. 10" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    min="0"
                                                    data-validation="experience"
                                                    title="Experience must be a non-negative number.">
                                                <i class="fas fa-check-circle input-icon" id="experience_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="experience_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Must be a non-negative number</span>
                                            </div>
                                            <div class="validation-message success" id="experience_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid experience</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Consultation Fee -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Consultation Fee (₹)</label>
                                            <div class="input-wrapper">
                                                <input name="consultation_fee" id="consultation_fee" type="number" placeholder="e.g. 500" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    min="0"
                                                    data-validation="fee"
                                                    title="Consultation fee must be a non-negative number.">
                                                <i class="fas fa-check-circle input-icon" id="consultation_fee_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="consultation_fee_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Must be a non-negative number</span>
                                            </div>
                                            <div class="validation-message success" id="consultation_fee_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid fee amount</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Timing -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Available Timing</label>
                                            <div class="input-wrapper">
                                                <input name="timing" id="timing" placeholder="e.g. Mon-Fri, 9AM - 5PM" 
                                                    class="form-input w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" 
                                                    pattern="^[A-Za-z0-9\s\-\.,:]+$"
                                                    data-validation="timing"
                                                    title="Only letters, numbers, spaces, hyphens, commas, and colons are allowed.">
                                                <i class="fas fa-check-circle input-icon" id="timing_icon"></i>
                                            </div>
                                            <div class="validation-message error" id="timing_error">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span>Only letters, numbers, spaces, hyphens, commas, and colons are allowed.</span>
                                            </div>
                                            <div class="validation-message success" id="timing_success">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Valid timing format</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & Media Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Location & Media</h2>
                                    </div>
                                    
                                    <div class="space-y-8">
                                        <!-- Address -->
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Address</label>
                                            <div class="input-wrapper">
                                                <textarea name="address" id="address" placeholder="Enter complete clinic or residential address" 
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
                                                    <input type="file" name="doctor_image" accept="image/*" class="w-full text-xs file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all">
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
                                <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2">Register Doctor</button>
                            </div>
                        </div>
                    </form>
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

            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
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
                specialization: /^[A-Za-z\s\-\.,]+$/,
                qualification: /^[A-Za-z\s\-\.,]+$/,
                timing: /^[A-Za-z0-9\s\-\.,:]+$/,
                address: /^[A-Za-z0-9\s\-\.,#\/]+$/
            };

            // Get all fields that need validation
            const fields = {
                doctor_name: { pattern: patterns.name, required: true },
                email: { pattern: patterns.email, required: true },
                mobile: { pattern: patterns.mobile, required: false },
                password: { pattern: patterns.password, required: true },
                specialization: { pattern: patterns.specialization, required: false },
                qualification: { pattern: patterns.qualification, required: false },
                timing: { pattern: patterns.timing, required: false },
                address: { pattern: patterns.address, required: false }
            };

            // Number fields validation
            const numberFields = ['experience', 'consultation_fee'];

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

                // Special validation for number fields
                if (numberFields.includes(fieldId)) {
                    const numValue = parseFloat(value);
                    if (isNaN(numValue) || numValue < 0) {
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
                        // Also validate password field
                        validateField('password');
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
                    validateField(fieldId);
                });

                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });
            });

            // Form submission validation
            document.getElementById('doctorForm').addEventListener('submit', function(e) {
                let isValid = true;

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
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }
                
                // Check if exactly 10 digits
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
                
                // Valid
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
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
        });
    </script>
</body>
</html>