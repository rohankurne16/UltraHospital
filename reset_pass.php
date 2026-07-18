<?php
session_start();
include "config/hospital.php"; 

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: send_reset_link.php");
    exit();
}

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: send_reset_link.php");
    exit();
}

include "config/hospital.php";



$hospital_name = $hospital['hospital_name'];
$hospital_logo = $hospital['hospital_logo'];

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "error";
    } elseif ($new_password != $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE register SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['reset_user_id']);

        if ($stmt->execute()) {
            $message = "Password changed successfully! Redirecting to login...";
            $message_type = "success";
            
            // Clear session data
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            
            // Redirect after 2 seconds
            echo "<meta http-equiv='refresh' content='2;url=index.php'>";
        } else {
            $message = "Error updating password. Please try again.";
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password - <?php echo htmlspecialchars($hospital_name); ?></title>
    <meta name="description" content="Reset your password for <?php echo htmlspecialchars($hospital_name); ?> Management System" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    
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
        .logo-wrapper { display: flex; justify-content: center; margin-bottom: 1.5rem; }
        .logo-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid #e2e8f0;
            background: #f8fafc;
        }
        .logo-icon img { width: 100%; height: 100%; object-fit: cover; }
        .brand-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.5px;
        }
        .brand-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            font-weight: 400;
        }
        .welcome-text { text-align: center; margin: 1.75rem 0 0.5rem 0; }
        .welcome-text h2 { 
            color: #1e293b; 
            font-size: 1.25rem; 
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
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
        
        .password-requirements {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.5rem;
            padding-left: 0.25rem;
        }
        .password-requirements i {
            margin-right: 0.25rem;
        }
        .password-requirements .valid {
            color: #22c55e;
        }
        .password-requirements .invalid {
            color: #ef4444;
        }
        
        .reset-btn {
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
        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
        }
        .reset-btn.loading { opacity: 0.7; pointer-events: none; }
        .reset-btn.loading::before {
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
        
        .back-link { 
            text-align: center; 
            color: #64748b; 
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        .back-link a { 
            color: #3b82f6; 
            font-weight: 600; 
            text-decoration: none; 
            transition: color 0.3s ease; 
        }
        .back-link a:hover { 
            color: #2563eb; 
            text-decoration: underline; 
        }
        
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
        .error-message i { color: #dc2626; margin-top: 0.15rem; }
        
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
        .success-message i { color: #16a34a; }
        
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
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <img src="<?php echo htmlspecialchars($hospital_logo); ?>" width="150" height="150" alt="<?php echo htmlspecialchars($hospital_name); ?> Logo" onerror="this.src='documents/hospital/logo.png'">
                </div>
            </div>
            
            <div class="brand-title"><?php echo htmlspecialchars($hospital_name); ?></div>
            <div class="brand-subtitle">Hospital Management System with AI</div>
            
            <div class="welcome-text">
                <h2>
                    <i class="fas fa-lock text-blue-500 text-xl"></i>
                    Reset Password
                </h2>
                <p>Create a new secure password for your account</p>
            </div>
            
            <form method="POST" id="resetForm">
                <?php if (!empty($message)): ?>
                    <div class="<?php echo $message_type == 'error' ? 'error-message' : 'success-message'; ?>" id="statusMessage">
                        <i class="fas <?php echo $message_type == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min 6 characters)" required minlength="6">
                    <i class="fas fa-key input-icon"></i>
                    <button type="button" id="toggleNewPassword" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="password-requirements" id="passwordRequirements">
                    <span id="lengthCheck" class="invalid">
                        <i class="fas fa-circle"></i> At least 6 characters
                    </span>
                    <span class="mx-2">•</span>
                    <span id="uppercaseCheck" class="invalid">
                        <i class="fas fa-circle"></i> Uppercase letter
                    </span>
                    <span class="mx-2">•</span>
                    <span id="numberCheck" class="invalid">
                        <i class="fas fa-circle"></i> Number
                    </span>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your new password" required>
                    <i class="fas fa-check-circle input-icon"></i>
                    <button type="button" id="toggleConfirmPassword" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" class="reset-btn" id="resetBtn">
                    <i class="fas fa-sync-alt mr-2"></i> Reset Password
                </button>
                
                <div class="divider">
                    <span>Remember your password?</span>
                </div>
                
                <div class="back-link">
                    <a href="index.php"><i class="fas fa-arrow-left mr-1"></i> Back to Login</a>
                </div>
            </form>
            
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Secured connection</span>
                <i class="fas fa-lock ml-1"></i>
                <span>Encrypted data</span>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const toggleNewPassword = document.getElementById('toggleNewPassword');
            const newPasswordInput = document.getElementById('new_password');
            const newIcon = toggleNewPassword.querySelector('i');
            
            toggleNewPassword.addEventListener('click', function() {
                if (newPasswordInput.type === 'password') {
                    newPasswordInput.type = 'text';
                    newIcon.classList.remove('fa-eye');
                    newIcon.classList.add('fa-eye-slash');
                } else {
                    newPasswordInput.type = 'password';
                    newIcon.classList.remove('fa-eye-slash');
                    newIcon.classList.add('fa-eye');
                }
            });
            
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const confirmIcon = toggleConfirmPassword.querySelector('i');
            
            toggleConfirmPassword.addEventListener('click', function() {
                if (confirmPasswordInput.type === 'password') {
                    confirmPasswordInput.type = 'text';
                    confirmIcon.classList.remove('fa-eye');
                    confirmIcon.classList.add('fa-eye-slash');
                } else {
                    confirmPasswordInput.type = 'password';
                    confirmIcon.classList.remove('fa-eye-slash');
                    confirmIcon.classList.add('fa-eye');
                }
            });
            
            // Password strength validation
            const newPassword = document.getElementById('new_password');
            const lengthCheck = document.getElementById('lengthCheck');
            const uppercaseCheck = document.getElementById('uppercaseCheck');
            const numberCheck = document.getElementById('numberCheck');
            
            newPassword.addEventListener('input', function() {
                const val = this.value;
                
                // Length check
                if (val.length >= 6) {
                    lengthCheck.className = 'valid';
                    lengthCheck.innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters';
                } else {
                    lengthCheck.className = 'invalid';
                    lengthCheck.innerHTML = '<i class="fas fa-circle"></i> At least 6 characters';
                }
                
                // Uppercase check
                if (/[A-Z]/.test(val)) {
                    uppercaseCheck.className = 'valid';
                    uppercaseCheck.innerHTML = '<i class="fas fa-check-circle"></i> Uppercase letter';
                } else {
                    uppercaseCheck.className = 'invalid';
                    uppercaseCheck.innerHTML = '<i class="fas fa-circle"></i> Uppercase letter';
                }
                
                // Number check
                if (/[0-9]/.test(val)) {
                    numberCheck.className = 'valid';
                    numberCheck.innerHTML = '<i class="fas fa-check-circle"></i> Number';
                } else {
                    numberCheck.className = 'invalid';
                    numberCheck.innerHTML = '<i class="fas fa-circle"></i> Number';
                }
            });
            
            // Auto-hide status message
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
            
            // Form submission
            const form = document.getElementById('resetForm');
            const resetBtn = document.getElementById('resetBtn');
            
            form.addEventListener('submit', function(e) {
                const newPass = document.getElementById('new_password').value.trim();
                const confirmPass = document.getElementById('confirm_password').value.trim();
                
                if (!newPass || !confirmPass) {
                    e.preventDefault();
                    alert('Please fill in all fields.');
                    return;
                }
                
                if (newPass.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    return;
                }
                
                if (newPass !== confirmPass) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return;
                }
                
                resetBtn.classList.add('loading');
                resetBtn.innerHTML = '';
            });
        });
    </script>
</body>
</html>