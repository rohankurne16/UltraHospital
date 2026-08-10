<?php
session_start();
include '../config/hospital.php';

// ========== CHECK SESSION & HR ACCESS ==========
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['id'] ?? 0;

$allowed_roles = ['HR', 'Admin', 'Super Admin'];
if (!in_array($user_role, $allowed_roles)) {
    header('Location: ../dashboard.php');
    exit();
}

// ========== GET HOSPITAL DATA ==========
$hospitalData = [];
$hospStmt = $conn->prepare("SELECT * FROM hospital_master WHERE hospital_id = ? LIMIT 1");
if ($hospStmt) {
    $hospStmt->bind_param('i', $hospital_id);
    $hospStmt->execute();
    $hospResult = $hospStmt->get_result();
    if ($hospResult->num_rows > 0) {
        $hospitalData = $hospResult->fetch_assoc();
    }
    $hospStmt->close();
}
$hospital_name = $hospitalData['hospital_name'] ?? 'MedixPro';
$hospital_logo = $hospitalData['hospital_logo'] ?? '../documents/hospital/logo.png';

// ========== PAGE VARIABLES ==========
$page_title = "Add Staff / Doctor";
$error = '';
$success = '';
$form_data = [];

// ========== GET ROLES ==========
$roles = [];
$role_sql = "SELECT role_id, role_name, role_slug, description 
             FROM roles
             WHERE delete_flag = 0 
             AND role_name NOT IN ('Patient', 'Ward Boy')
             ORDER BY role_name";
$role_result = $conn->query($role_sql);

if (!$role_result) {
    $error = "Database error: " . $conn->error;
} else {
    while ($row = $role_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// ========== GET DEPARTMENTS ==========
$departments = [];
$dept_sql = "SELECT id, department_name FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_sql);
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ========== GENERATE REGISTER ID ==========
function generateRegisterId($conn) {
    $sql = "SELECT MAX(id) as max_id FROM register";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ($row['max_id'] ?? 1000) + 1;
    }
    return 1001;
}

// ========== GENERATE STAFF ID ==========
function generateStaffId($conn, $hospital_id) {
    $sql = "SELECT MAX(staff_id) as max_id FROM staff WHERE hospital_id = $hospital_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ($row['max_id'] ?? 0) + 1;
    }
    return 1;
}

// ========== GENERATE DOCTOR ID ==========
function generateDoctorId($conn, $hospital_id) {
    $sql = "SELECT MAX(doctor_id) as max_id FROM doctors WHERE hospital_id = $hospital_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ($row['max_id'] ?? 0) + 1;
    }
    return 1;
}

$register_id = generateRegisterId($conn);
$staff_id = generateStaffId($conn, $hospital_id);
$doctor_id = generateDoctorId($conn, $hospital_id);

// ========== PROCESS FORM SUBMISSION ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = $first_name . ' ' . $last_name;
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    $department_id = intval($_POST['department_id'] ?? 0);
    $joining_date = $_POST['joining_date'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $qualification = trim($_POST['qualification'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);
    $timing = trim($_POST['timing'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    
    // Calculate age from DOB
    $age = 0;
    if (!empty($dob)) {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $age = $birthDate->diff($today)->y;
    }
    
    // Store form data for repopulation
    $form_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'mobile' => $mobile,
        'gender' => $gender,
        'dob' => $dob,
        'role_id' => $role_id,
        'department_id' => $department_id,
        'joining_date' => $joining_date,
        'status' => $status,
        'address' => $address,
        'emergency_contact' => $emergency_contact,
        'emergency_phone' => $emergency_phone,
        'qualification' => $qualification,
        'specialization' => $specialization,
        'experience' => $experience,
        'consultation_fee' => $consultation_fee,
        'timing' => $timing,
        'blood_group' => $blood_group
    ];
    
    // Validate
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($mobile)) $errors[] = "Mobile number is required";
    if (!preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = "Mobile number must be 10 digits";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($dob)) $errors[] = "Date of birth is required";
    if ($role_id <= 0) $errors[] = "Role is required";
    if ($department_id <= 0) $errors[] = "Department is required";
    if (empty($joining_date)) $errors[] = "Joining date is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    
    // Check if email already exists in register
    if (empty($errors)) {
        $check_sql = "SELECT id FROM register WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param('s', $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $errors[] = "Email already exists in the system";
            }
            $check_stmt->close();
        }
    }
    
    // Check if mobile already exists
    if (empty($errors)) {
        // Check in register table
        $check_sql = "SELECT id FROM register WHERE mobile = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param('s', $mobile);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $errors[] = "Mobile number already exists in the system";
            }
            $check_stmt->close();
        }
    }
    
    // If no errors, insert into all tables
    if (empty($errors)) {
        // Get role details
        $role_name = '';
        $role_slug = '';
        foreach ($roles as $role) {
            if ($role['role_id'] == $role_id) {
                $role_name = $role['role_name'];
                $role_slug = $role['role_slug'];
                break;
            }
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. INSERT INTO register table (Common for all) - Using 'id' instead of 'register_id'
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $reg_sql = "INSERT INTO register (
                id, role_id, name, email, password, 
                role, hospital_id, reg_date, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            $reg_stmt = $conn->prepare($reg_sql);
            if (!$reg_stmt) {
                throw new Exception("Register prepare failed: " . $conn->error);
            }
            $reg_stmt->bind_param(
                'iissssii',
                $register_id,
                $role_id,
                $full_name,
                $email,
                $hashed_password,
                $role_name,
                $hospital_id,
                $user_id
            );
            $reg_stmt->execute();
            $reg_stmt->close();
            
            // ========== CHECK IF ROLE IS DOCTOR ==========
            if ($role_slug == 'doctor' || $role_id == 3) {
                // ===== INSERT INTO DOCTORS TABLE =====
                $doc_sql = "INSERT INTO doctor (
                    doctor_id, register_id, doctor_name, mobile, email, department, 
                    qualification, specialization, experience, consultation_fee,
                    timing, address, status, hospital_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $doc_stmt = $conn->prepare($doc_sql);
                if (!$doc_stmt) {
                    throw new Exception("Doctor prepare failed: " . $conn->error);
                }
                $doc_stmt->bind_param(
                    'iissssssdsissi',
                    $doctor_id,
                    $register_id,
                    $full_name,
                    $mobile,
                    $email,
                    $department_id,
                    $qualification,
                    $specialization,
                    $experience,
                    $consultation_fee,
                    $timing,
                    $address,
                    $status,
                    $hospital_id
                );
                $doc_stmt->execute();
                $doc_stmt->close();
                
                $success = "Doctor added successfully!<br>
                           Doctor ID: " . $doctor_id . "<br>
                           Register ID: " . $register_id . "<br>
                           Role: " . $role_name;
                
                // Generate new IDs for next entry
                $doctor_id = generateDoctorId($conn, $hospital_id);
                
            } else {
                // ===== INSERT INTO STAFF TABLE (For all other roles) =====
                $staff_sql = "INSERT INTO staff (
                    staff_id, register_id, name, mobile, email, role, address,
                    date_of_birth, age, gender, blood_group, qualification, specialization, 
                    department, experience, shift_timing, emergency_contact, 
                    status, hospital_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $staff_stmt = $conn->prepare($staff_sql);
                if (!$staff_stmt) {
                    throw new Exception("Staff prepare failed: " . $conn->error);
                }
                
                $staff_stmt->bind_param(
                    'iissssssissssissssi',
                    $staff_id,
                    $register_id,
                    $full_name,
                    $mobile,
                    $email,
                    $role_name,
                    $address,
                    $dob,
                    $age,
                    $gender,
                    $blood_group,
                    $qualification,
                    $specialization,
                    $department_id,
                    $experience,
                    $timing,
                    $emergency_contact,
                    $status,
                    $hospital_id
                );
                $staff_stmt->execute();
                $staff_stmt->close();
                
                $success = "Staff added successfully!<br>
                           Staff ID: " . $staff_id . "<br>
                           Register ID: " . $register_id . "<br>
                           Role: " . $role_name;
                
                // Generate new IDs for next entry
                $staff_id = generateStaffId($conn, $hospital_id);
            }
            
            // Commit transaction
            $conn->commit();
            
            // Reset form data
            $form_data = [];
            // Generate new register ID for next entry
            $register_id = generateRegisterId($conn);
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding: " . $e->getMessage();
        }
    } else {
        $error = implode(", ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Add Staff / Doctor</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        
        .main-content {
            width: 100%;
            margin-left: 0;
            padding: 20px 28px;
            min-height: 100vh;
        }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: #2563eb; }
        
        .btn-outline {
            background: transparent;
            color: #6b7280;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-outline:hover { background: #f3f4f6; }
        
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .form-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-control.error {
            border-color: #ef4444;
        }
        .form-control.error:focus {
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
        }
        .form-control:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }
        select.form-control {
            appearance: auto;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #f3f4f6;
        }
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .form-section-title {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title i {
            color: #3b82f6;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .alert-danger {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert i {
            font-size: 18px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        
        .id-display {
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            display: inline-block;
        }
        
        .password-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .role-badge-superadmin { background: #fef3c7; color: #92400e; }
        .role-badge-admin { background: #dbeafe; color: #1e40af; }
        .role-badge-doctor { background: #d1fae5; color: #065f46; }
        .role-badge-nurse { background: #fce7f3; color: #9d174d; }
        .role-badge-labtechnician { background: #e0e7ff; color: #3730a3; }
        .role-badge-billingstaff { background: #fef3c7; color: #92400e; }
        .role-badge-accountant { background: #d1fae5; color: #065f46; }
        .role-badge-pharmacist { background: #fce7f3; color: #9d174d; }
        .role-badge-staff { background: #f3f4f6; color: #374151; }
        .role-badge-receptionist { background: #dbeafe; color: #1e40af; }
        .role-badge-hr { background: #e0e7ff; color: #3730a3; }
        
        .conditional-fields {
            display: none;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-top: 8px;
        }
        .conditional-fields.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- ========== INCLUDE HR SIDEBAR ========== -->
<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <!-- ========== INCLUDE HEADER ========== -->
    <?php include '../header.php'; ?>
    
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-user-plus mr-3 text-white"></i> Add New Staff / Doctor</h1>
                        <p>Create a new staff or doctor record with role, personal, and professional details</p>
                    </div>
                    <a href="staff_list.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Add Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-tie mr-2 text-blue-500"></i> Registration Form</h3>
                    <div>
                        <span class="id-display">
                            <i class="fas fa-hashtag mr-1"></i> Register ID: <?php echo htmlspecialchars($register_id); ?>
                        </span>
                        <?php if (isset($_POST['role_id']) && $_POST['role_id'] == 3): ?>
                        <span class="id-display ml-2">
                            <i class="fas fa-stethoscope mr-1"></i> Doctor ID: <?php echo htmlspecialchars($doctor_id); ?>
                        </span>
                        <?php else: ?>
                        <span class="id-display ml-2">
                            <i class="fas fa-id-card mr-1"></i> Staff ID: <?php echo htmlspecialchars($staff_id); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="staffForm">
                        <!-- ===== PERSONAL INFORMATION ===== -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-user-circle"></i> Personal Information
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name <span class="required">*</span></label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" 
                                           placeholder="Enter first name" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name <span class="required">*</span></label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" 
                                           placeholder="Enter last name" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email Address <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                           placeholder="Enter email address" required>
                                </div>
                                <div class="form-group">
                                    <label>Mobile Number <span class="required">*</span></label>
                                    <input type="tel" name="mobile" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['mobile'] ?? ''); ?>" 
                                           placeholder="Enter 10-digit mobile number" 
                                           pattern="[0-9]{10}" maxlength="10" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gender <span class="required">*</span></label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($form_data['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($form_data['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($form_data['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth <span class="required">*</span></label>
                                    <input type="date" name="dob" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['dob'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Blood Group</label>
                                    <select name="blood_group" class="form-control">
                                        <option value="">Select Blood Group</option>
                                        <option value="A+" <?php echo ($form_data['blood_group'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo ($form_data['blood_group'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo ($form_data['blood_group'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo ($form_data['blood_group'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo ($form_data['blood_group'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo ($form_data['blood_group'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo ($form_data['blood_group'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo ($form_data['blood_group'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['address'] ?? ''); ?>" 
                                           placeholder="Enter address">
                                </div>
                            </div>
                        </div>

                        <!-- ===== ROLE & PROFESSIONAL INFORMATION ===== -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-briefcase"></i> Role & Professional Information
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Role <span class="required">*</span></label>
                                    <select name="role_id" class="form-control" id="roleSelect" required>
                                        <option value="">Select Role</option>
                                        <?php 
                                        if (!empty($roles)) {
                                            foreach ($roles as $role): 
                                                $badge_class = '';
                                                switch($role['role_slug']) {
                                                    case 'superadmin': $badge_class = 'role-badge-superadmin'; break;
                                                    case 'admin': $badge_class = 'role-badge-admin'; break;
                                                    case 'doctor': $badge_class = 'role-badge-doctor'; break;
                                                    case 'nurse': $badge_class = 'role-badge-nurse'; break;
                                                    case 'labtechnician': $badge_class = 'role-badge-labtechnician'; break;
                                                    case 'billingstaff': $badge_class = 'role-badge-billingstaff'; break;
                                                    case 'accountant': $badge_class = 'role-badge-accountant'; break;
                                                    case 'pharmacist': $badge_class = 'role-badge-pharmacist'; break;
                                                    case 'staff': $badge_class = 'role-badge-staff'; break;
                                                    case 'receptionist': $badge_class = 'role-badge-receptionist'; break;
                                                    
                                                    default: $badge_class = 'role-badge-staff';
                                                }
                                        ?>
                                            <option value="<?php echo $role['role_id']; ?>" 
                                                    data-role="<?php echo $role['role_slug']; ?>"
                                                    <?php echo ($form_data['role_id'] ?? 0) == $role['role_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['role_name']); ?>
                                            </option>
                                        <?php 
                                            endforeach;
                                        } else {
                                            echo '<option value="" disabled>No roles available</option>';
                                        }
                                        ?>
                                    </select>
                                    <div id="rolePreview" class="mt-2" style="display: none;">
                                        <span class="role-badge" id="roleBadge"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Department <span class="required">*</span></label>
                                    <select name="department_id" class="form-control" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo ($form_data['department_id'] ?? 0) == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Joining Date <span class="required">*</span></label>
                                    <input type="date" name="joining_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['joining_date'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="required">*</span></label>
                                    <select name="status" class="form-control" required>
                                        <option value="Active" <?php echo ($form_data['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($form_data['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="On Leave" <?php echo ($form_data['status'] ?? '') == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                        <option value="Terminated" <?php echo ($form_data['status'] ?? '') == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ===== DOCTOR-SPECIFIC FIELDS ===== -->
                        <div class="form-section conditional-fields" id="doctorFields">
                            <div class="form-section-title">
                                <i class="fas fa-stethoscope"></i> Doctor Details
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="qualification" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['qualification'] ?? ''); ?>" 
                                           placeholder="e.g., MBBS, MD">
                                </div>
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <input type="text" name="specialization" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['specialization'] ?? ''); ?>" 
                                           placeholder="e.g., Cardiologist">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Experience (Years)</label>
                                    <input type="number" name="experience" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['experience'] ?? ''); ?>" 
                                           placeholder="Enter years of experience" min="0" step="0.5">
                                </div>
                                <div class="form-group">
                                    <label>Consultation Fee</label>
                                    <input type="number" name="consultation_fee" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['consultation_fee'] ?? ''); ?>" 
                                           placeholder="Enter consultation fee" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Shift Timing</label>
                                    <input type="text" name="timing" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['timing'] ?? ''); ?>" 
                                           placeholder="e.g., 9:00 AM to 5:00 PM">
                                </div>
                                <div class="form-group">
                                    <!-- Empty for spacing -->
                                </div>
                            </div>
                        </div>

                        <!-- ===== EMERGENCY CONTACT ===== -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-phone-alt"></i> Emergency Contact
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['emergency_contact'] ?? ''); ?>" 
                                           placeholder="Enter emergency contact name">
                                </div>
                                <div class="form-group">
                                    <label>Emergency Phone Number</label>
                                    <input type="tel" name="emergency_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['emergency_phone'] ?? ''); ?>" 
                                           placeholder="Enter emergency phone number">
                                </div>
                            </div>
                        </div>

                        <!-- ===== LOGIN CREDENTIALS ===== -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-lock"></i> Login Credentials
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Password <span class="required">*</span></label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Enter password (min 6 characters)" 
                                           minlength="6" required>
                                    <div class="password-hint">
                                        <i class="fas fa-info-circle"></i> Password must be at least 6 characters long
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password <span class="required">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           placeholder="Confirm password" required>
                                </div>
                            </div>
                        </div>

                        <!-- ===== ACTION BUTTONS ===== -->
                        <div class="action-buttons">
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button type="reset" class="btn-outline">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                            <a href="staff_list.php" class="btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// ========== ROLE PREVIEW ==========
const roleSelect = document.getElementById('roleSelect');
const rolePreview = document.getElementById('rolePreview');
const roleBadge = document.getElementById('roleBadge');
const doctorFields = document.getElementById('doctorFields');

function updateRolePreview() {
    const selected = roleSelect.options[roleSelect.selectedIndex];
    if (selected && selected.value) {
        const roleName = selected.text;
        const roleSlug = selected.dataset.role;
        const badgeClass = getBadgeClass(roleSlug);
        roleBadge.textContent = 'Selected Role: ' + roleName;
        roleBadge.className = 'role-badge ' + badgeClass;
        rolePreview.style.display = 'block';
        
        // Show/hide doctor fields
        if (roleSlug === 'doctor') {
            doctorFields.classList.add('show');
        } else {
            doctorFields.classList.remove('show');
        }
    } else {
        rolePreview.style.display = 'none';
        doctorFields.classList.remove('show');
    }
}

function getBadgeClass(roleSlug) {
    const classes = {
        'superadmin': 'role-badge-superadmin',
        'admin': 'role-badge-admin',
        'doctor': 'role-badge-doctor',
        'nurse': 'role-badge-nurse',
        'labtechnician': 'role-badge-labtechnician',
        'billingstaff': 'role-badge-billingstaff',
        'accountant': 'role-badge-accountant',
        'pharmacist': 'role-badge-pharmacist',
        'staff': 'role-badge-staff',
        'receptionist': 'role-badge-receptionist',
        
    };
    return classes[roleSlug] || 'role-badge-staff';
}

roleSelect.addEventListener('change', updateRolePreview);
// Initial update
updateRolePreview();

// ========== FORM VALIDATION ==========
document.getElementById('staffForm').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]');
    const confirm = document.querySelector('input[name="confirm_password"]');
    
    if (password.value !== confirm.value) {
        e.preventDefault();
        alert('Password and Confirm Password do not match!');
        confirm.focus();
        return false;
    }
    
    if (password.value.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        password.focus();
        return false;
    }
    
    return true;
});

// ========== MOBILE NUMBER FORMATTING ==========
document.querySelector('input[name="mobile"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
});

// ========== CONFIRM BEFORE RESET ==========
document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to reset all form fields?')) {
        e.preventDefault();
    }
});

// ========== DATE VALIDATION ==========
document.querySelector('input[name="dob"]').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    if (age < 18) {
        alert('Person must be at least 18 years old!');
        this.value = '';
    }
});


console.log('Add Form Loaded Successfully!');
</script>

</body>
</html>