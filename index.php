<?php
session_start();
include 'config/superadmin.php';
include "config/hospital.php";

$status = "";
$status_type = "";
$entered_email = "";

$hospital_id = '';
$hospital = null;

if (isset($_GET['hid']) && !empty($_GET['hid'])) {
  
}

if (!empty($hospital_id) && is_numeric($hospital_id)) {
    $getHospital = "SELECT * FROM hospital_master WHERE hospital_id='$hospital_id'";
    $result = mysqli_query($conn, $getHospital);
    
    if(mysqli_num_rows($result) > 0){
        $hospital = mysqli_fetch_assoc($result);
    }
}

if (!$hospital) {
    $hospital = [
        'hospital_name' => 'Healthcare Management System',
        'hospital_logo' => null,
        'address' => '',
        'phone' => '',
        'city' => '',
        'state' => '',
        'country' => ''
    ];
}

// Session status messages
if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];
    $status_type = $_SESSION['status_type'] ?? 'error';

    if (isset($_SESSION['entered_email'])) {
        $entered_email = $_SESSION['entered_email'];
        unset($_SESSION['entered_email']);
    }
    unset($_SESSION['status']);
    unset($_SESSION['status_type']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);

    // Validation
    if (empty($email) || empty($pass)) {
        $_SESSION['status'] = "Please fill in all fields.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = "Please enter a valid email.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php");
        exit();
    }

    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    
    if ($email == "superadmin@gmail.com" && $pass == "1234") {
        $_SESSION['id'] = 999;
        $_SESSION['name'] = "Super Admin";
        $_SESSION['email'] = $email;
        $_SESSION['role'] = "SuperAdmin";
        $_SESSION['role_id'] = 1;
        $_SESSION['hospital_id'] = NULL;
        $_SESSION['login_time'] = time();

    

        // Insert login log
        $register_id = 999;
        $hospital_id = 'NULL';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $browser = $_SERVER['HTTP_USER_AGENT'];
        
        $device = 'Desktop';
        if (strpos($browser, 'Mobile') !== false || strpos($browser, 'Android') !== false) {
            $device = 'Mobile';
        } elseif (strpos($browser, 'iPad') !== false || strpos($browser, 'Tablet') !== false) {
            $device = 'Tablet';
        }
        
        $login_sql = "INSERT INTO login_logs (register_id, hospital_id, ip_address, browser, device) 
                      VALUES ('$register_id', $hospital_id, '$ip_address', '$browser', '$device')";
        mysqli_query($conn, $login_sql);

        header("Location: superadmin/dashboard.php");
        exit();
    }
  
    $stmt = $conn->prepare("SELECT * FROM register WHERE email = ? AND (delete_flag=0 OR delete_flag IS NULL)");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $_SESSION['status'] = "Account not found.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php");
        exit();
    }

    $row = $result->fetch_assoc();


    $role_id = $row['role_id'] ?? 0;

    if (empty($role_id) || $role_id == 0) {
        $role_name = trim(strtolower($row['role']));
        
        $role_query = "SELECT role_id FROM roles 
                       WHERE LOWER(role_slug) = '$role_name' 
                       OR LOWER(role_name) = '$role_name' 
                       AND delete_flag = 0";
        $role_result = mysqli_query($conn, $role_query);
        if ($role_data = mysqli_fetch_assoc($role_result)) {
            $role_id = $role_data['role_id'];
            
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
            $update_sql = "UPDATE register SET role_id = '$role_id' WHERE id = '{$row['id']}'";
            mysqli_query($conn, $update_sql);
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    if (empty($role_id) || $role_id == 0) {
        $role_id = 8; // Patient
    }

    if (password_verify($pass, $row['password']) || $pass == $row['password']) {

    
        $_SESSION['id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['role_id'] = $role_id;
        $_SESSION['hospital_id'] = $row['hospital_id'];
        $_SESSION['login_time'] = time();

    
        include 'config/superadmin.php';
        
        // SuperAdmin ला सर्व permissions
        if ($_SESSION['role'] == 'SuperAdmin' || strtolower($_SESSION['role']) == 'superadmin') {
            $_SESSION['permissions'] = getAllPermissions();
        } else {
            // इतर roles साठी role_id वापरून permissions मिळवा
            $_SESSION['permissions'] = getUserPermissions($_SESSION['role_id']);
        }
      
        $register_id = $row['id'];
        $hospital_id = $row['hospital_id'] ?? 'NULL';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $browser = $_SERVER['HTTP_USER_AGENT'];
        
        $device = 'Desktop';
        if (strpos($browser, 'Mobile') !== false || strpos($browser, 'Android') !== false) {
            $device = 'Mobile';
        } elseif (strpos($browser, 'iPad') !== false || strpos($browser, 'Tablet') !== false) {
            $device = 'Tablet';
        }
        
        $login_sql = "INSERT INTO login_logs (register_id, hospital_id, ip_address, browser, device) 
                      VALUES ('$register_id', $hospital_id, '$ip_address', '$browser', '$device')";
        mysqli_query($conn, $login_sql);
      
        $role = strtolower(trim($row['role']));

        switch ($role) {
            case 'superadmin':
                header("Location: superadmin/dashboard.php");
                exit();
                
            case 'admin':
            case 'hospitaladmin':
                header("Location: dashboard.php");
                exit();
                
            case 'doctor':
                header("Location: doctors/dashboard.php");
                exit();
                
            case 'nurse':
            case 'receptionist':
            case 'wardboy':
            case 'ward boy':
                header("Location: staff/staff_dashboard.php");
                exit();
                
            case 'lab technician':
            case 'labtechnician':
                if (file_exists("lab_dashboard.php")) {
                    header("Location: lab_dashboard.php");
                } elseif (file_exists("lab/lab_dashboard.php")) {
                    header("Location: lab/lab_dashboard.php");
                } elseif (file_exists("staff/lab_dashboard.php")) {
                    header("Location: staff/lab_dashboard.php");
                } else {
                    header("Location: staff/staff_dashboard.php");
                }
                exit();
                
            case 'patient':
                header("Location: patients/dashboard.php");
                exit();
                
            case 'billing staff':
            case 'billingstaff':
                header("Location: billing/dashboard.php");
                exit();
                
            case 'accountant':
                header("Location: accountant/dashboard.php");
                exit();
                
            case 'pharmacist':
                header("Location: pharmacy/dashboard.php");
                exit();
                
            default:
                $_SESSION['status'] = "Unknown user role. Please contact support.";
                $_SESSION['status_type'] = "error";
                $_SESSION['entered_email'] = $email;
                header("Location: index.php");
                exit();
        }
      
        
    } else {
        $_SESSION['status'] = "Invalid password.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php");
        exit();
    }
}

if (isset($_SESSION['id'])) {
    $profile = $conn->query("
        SELECT ap.profile_image, r.name, r.role
        FROM admin_profile ap
        INNER JOIN register r ON ap.register_id = r.id
        WHERE ap.register_id='".$_SESSION['id']."'
    ");

    if($profile && $profile->num_rows > 0){
        $row = $profile->fetch_assoc();
        $_SESSION['profile_image'] = $row['profile_image'];
        $_SESSION['role'] = $row['role'];
    }
}


function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 3);
}

function getLogoPath($logo) {
    if (empty($logo)) {
        return null;
    }
    
    if (filter_var($logo, FILTER_VALIDATE_URL) || strpos($logo, 'data:image') === 0) {
        return $logo;
    }
    
    $logo = ltrim($logo, '/');
    
    $base_paths = [
        '',
        '../',
        '../../',
        '../../../',
        $_SERVER['DOCUMENT_ROOT'] . '/',
    ];
    
    foreach ($base_paths as $base) {
        $full_path = $base . $logo;
        if (file_exists($full_path)) {
            return $full_path;
        }
    }
    
    $upload_paths = [
        'uploads/' . $logo,
        'uploads/hospital_logos/' . basename($logo),
        'assets/images/' . basename($logo),
        'images/' . basename($logo),
        'documents/hospital/' . basename($logo),
        'hospital_logos/' . basename($logo),
    ];
    
    foreach ($upload_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

$logo_path = '';
if (!empty($hospital['hospital_logo'])) {
    $logo_path = $hospital['hospital_logo'];
}
$hospital_initials = getInitials($hospital['hospital_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo !empty($hospital['hospital_name']) ? htmlspecialchars($hospital['hospital_name']) : 'Secure Login'; ?></title>
    <meta name="description" content="Secure Login System" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container { width: 100%; max-width: 420px; animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
        }
        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .logo-wrapper { 
            display: flex; 
            justify-content: center; 
            margin-bottom: 1.5rem; 
        }
        .logo-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: 3px solid #e2e8f0;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            padding: 0;
        }
        .logo-icon .hospital-initials {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .logo-icon .default-icon {
            font-size: 3.5rem;
            color: white;
            opacity: 0.9;
        }
        .brand-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .hospital-location {
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .hospital-location i {
            color: #94a3b8;
            margin: 0 2px;
        }
        .hospital-phone {
            text-align: center;
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 0.1rem;
        }
        .brand-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            font-weight: 400;
        }
        .welcome-text { text-align: center; margin: 1.75rem 0 0.5rem 0; }
        .welcome-text h2 { color: #1e293b; font-size: 1.25rem; font-weight: 700; }
        .welcome-text p { color: #64748b; font-size: 0.875rem; margin-top: 0.25rem; }
        
        .input-group { position: relative; margin-bottom: 1.25rem; }
        .input-group label {
            display: block;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 69%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }
        .input-group input {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 44px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #1e293b;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
            position: relative;
            z-index: 1;
        }
        .input-group input:focus {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .input-group input::placeholder { color: #94a3b8; font-size: 0.9rem; }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 73%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            z-index: 2;
        }
        .toggle-password:hover { color: #475569; }
        
        .forgot-link {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease;
            display: inline-block;
            margin: 0.5rem 0 1rem 0;
        }
        .forgot-link:hover { color: #3b82f6; }
        
        .login-btn {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.7rem;
            position: relative;
            overflow: hidden;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
        }
        .login-btn.loading { opacity: 0.7; pointer-events: none; }
        .login-btn.loading::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        @keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        .divider span {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .register-link { text-align: center; color: #64748b; font-size: 0.9rem; }
        .register-link a { color: #3b82f6; font-weight: 600; text-decoration: none; transition: color 0.3s ease; }
        .register-link a:hover { color: #2563eb; text-decoration: underline; }
        
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 1rem;
            animation: shake 0.5s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        .error-message i { color: #dc2626; }
        
        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.5s ease;
        }
        
        .security-badge {
            text-align: center;
            margin-top: 1.5rem;
            color: #94a3b8;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .security-badge i { color: #22c55e; }
        
        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.25rem; }
            .brand-title { font-size: 1.5rem; }
            .logo-icon {
                width: 80px;
                height: 80px;
            }
            .logo-icon .hospital-initials {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <?php if ($logo_path): ?>
                     <img src="<?php echo $logo_path; ?>"
     alt="Hospital Logo"
     onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'hospital-initials\'><?php echo $hospital_initials; ?></span>';">
                    <?php else: ?>
                        <span class="hospital-initials"><?php echo $hospital_initials; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($hospital['hospital_name'])): ?>
                <div class="brand-title"><?php echo htmlspecialchars($hospital['hospital_name']); ?></div>
                
                <?php if (!empty($hospital['city']) || !empty($hospital['state']) || !empty($hospital['country'])): ?>
                    <div class="hospital-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php 
                        $location_parts = [];
                        if (!empty($hospital['city'])) $location_parts[] = $hospital['city'];
                        if (!empty($hospital['state'])) $location_parts[] = $hospital['state'];
                        if (!empty($hospital['country'])) $location_parts[] = $hospital['country'];
                        echo htmlspecialchars(implode(', ', $location_parts));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($hospital['phone'])): ?>
                    <div class="hospital-phone">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($hospital['phone']); ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="brand-title">Login</div>
                <div class="brand-subtitle">Secure Access Control System</div>
            <?php endif; ?>
            
            <div class="welcome-text">
                <h2>Welcome Back</h2>
                <p>Please enter your credentials to continue</p>
            </div>
            
            <form action="index.php" method="POST" autocomplete="on" id="loginForm">
                <?php if (!empty($status)): ?>
                    <div class="<?php echo $status_type == 'error' ? 'error-message' : 'success-message'; ?>" id="statusMessage">
                        <i class="fas <?php echo $status_type == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($status); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" autocomplete="on" placeholder="your@email.com" value="<?php echo !empty($entered_email) ? htmlspecialchars($entered_email) : ''; ?>" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
                    <i class="fas fa-key input-icon"></i>
                    <button type="button" id="togglePassword" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="flex items-center justify-between">
                    <a href="send_reset_link.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
                
                
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const icon = toggleBtn.querySelector('i');
            
            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
            
            function hideMessage(elementId) {
                const msgElement = document.getElementById(elementId);
                if (msgElement) {
                    setTimeout(function() {
                        msgElement.style.transition = 'opacity 0.5s ease';
                        msgElement.style.opacity = '0';
                        setTimeout(function() { msgElement.style.display = 'none'; }, 500);
                    }, 5000);
                }
            }
            
            hideMessage('statusMessage');
            
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();
                
                if (!email || !password) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                    return;
                }
                
                loginBtn.classList.add('loading');
                loginBtn.innerHTML = '';
            });
        });
    </script>
</body>
</html>