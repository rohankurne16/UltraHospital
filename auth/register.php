<?php 
include '../config/hospital.php';

$error_message = "";
$success_message = "";
$form_data = [];

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];
    $confirmpass = $_POST['confirmpass'];
    
    $form_data = [
        'username' => htmlspecialchars($name),
        'email' => htmlspecialchars($email)
    ];

    $errors = [];

    if (empty($name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters";
    } elseif (strlen($name) > 50) {
        $errors[] = "Name cannot exceed 50 characters";
    }

    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (empty($pass)) {
        $errors[] = "Password is required";
    } elseif (strlen($pass) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $pass)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $pass)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $pass)) {
        $errors[] = "Password must contain at least one number";
    }

    if ($pass !== $confirmpass) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        $conn = new mysqli($servername, $username, $dbpass, $dbname);

        if ($conn->connect_error) {
            $error_message = "Connection failed: " . $conn->connect_error;
        } else {
            $check_stmt = $conn->prepare("SELECT email FROM register WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error_message = "This email is already registered. Please login instead.";
            } else {
                $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                
                $insert_stmt = $conn->prepare("INSERT INTO register (name, email, password, created_by, modified_by, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'patient', 'Active', NOW())");
                $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $name, $name);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Registration successful! Redirecting to login...";
                    echo "<meta http-equiv='refresh' content='2;url=../index.php?registered=success'>";
                } else {
                    $error_message = "Registration failed. Please try again later.";
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
            $conn->close();
        }
    } else {
        $error_message = implode(". ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $hospital['hospital_name'] ?> - Create Account</title>
    <meta name="description" content="Register for MedixPro Clinic Management System" />
    <link rel="icon" href="../<?php echo $hospital['hospital_logo'] ?>" type="image/x-icon" />
     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .register-container {
            width: 100%;
            max-width: 440px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .register-card::before {
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
        
        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
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
        
        .input-group {
            position: relative;
            margin-bottom: 1.25rem;
        }
        
        .input-group label {
            display: block;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .input-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 68%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.3s ease;
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
        
        .input-group input.input-error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .input-group input.input-error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        
        .input-group input::placeholder {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            transition: color 0.3s ease;
            z-index: 2;
        }
        
        .toggle-password:hover {
            color: #475569;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            display: flex;
            gap: 4px;
            height: 4px;
        }
        
        .password-strength .bar {
            flex: 1;
            background: #e2e8f0;
            border-radius: 2px;
            transition: background 0.3s ease;
        }
        
        .password-strength .bar.weak { background: #ef4444; }
        .password-strength .bar.medium { background: #f59e0b; }
        .password-strength .bar.strong { background: #22c55e; }
        .password-strength .bar.very-strong { background: #10b981; }
        
        .register-btn {
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
            margin-top: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .register-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .register-btn:hover::after {
            left: 100%;
        }
        
        .register-btn.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .register-btn.loading::before {
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
        
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.25rem 0 0.5rem 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            margin-top: 1px;
            accent-color: #3b82f6;
            cursor: pointer;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .checkbox-group label {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
            cursor: pointer;
        }
        
        .checkbox-group label a {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .checkbox-group label a:hover {
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
        
        .error-message i {
            color: #dc2626;
            margin-top: 2px;
        }
        
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
        
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.25rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.25rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .login-link a {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .register-card {
                padding: 2rem 1.25rem;
            }
            
            .brand-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <img src="../<?php echo $hospital['hospital_logo'] ?>" width="150" height="150" title="Hospital_Logo" alt="Hospital_Logo">
                </div>
            </div>
            
            <div class="brand-title"><?php echo $hospital['hospital_name'] ?></div>
            <div class="brand-subtitle">Create your account</div>
            
            <form action="register.php" method="POST" autocomplete="off" id="registerForm" style="margin-top: 1.75rem;">
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message" id="errorMessage">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message" id="successMessage">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="input-group">
                    <label for="name">
                        <i class="fas fa-user" style="margin-right: 8px; color: #3b82f6;"></i>
                        Full Name <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="username"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="Username"
                        value="<?php echo isset($form_data['username']) ? $form_data['username'] : ''; ?>"
                        required
                    >
                    <i class="fas fa-user input-icon"></i>
                </div>
                
                <div class="input-group">
                    <label for="email">
                        <i class="fas fa-envelope" style="margin-right: 8px; color: #3b82f6;"></i>
                        Email Address <span class="required">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="user@example.com"
                        value="<?php echo isset($form_data['email']) ? $form_data['email'] : ''; ?>"
                        required
                    >
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                
                <div class="input-group">
                    <label for="password">
                        <i class="fas fa-lock" style="margin-right: 8px; color: #3b82f6;"></i>
                        Password <span class="required">*</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="pass"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                        required
                    >

                    <i class="fas fa-key input-icon" style="margin-top: -6%;"></i>
                    <button
                        type="button"
                        id="togglePassword"
                        class="toggle-password"
                        aria-label="Toggle password visibility"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="password-strength" id="passwordStrength">
                        <div class="bar" data-index="0"></div>
                        <div class="bar" data-index="1"></div>
                        <div class="bar" data-index="2"></div>
                        <div class="bar" data-index="3"></div>
                    </div>
                    <div style="margin-top: 4px; font-size: 0.7rem; color: #94a3b8; text-align: right;" id="strengthText">
                        <span>Password strength</span>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="confirmPassword">
                        <i class="fas fa-check-circle" style="margin-right: 8px; color: #3b82f6;"></i>
                        Confirm Password <span class="required">*</span>
                    </label>
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmpass"
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                        required
                    >
                    <i class="fas fa-shield-alt input-icon" style="margin-top:0%"></i>
                    <button
                        type="button"
                        id="toggleConfirmPassword"
                        class="toggle-password"
                        aria-label="Toggle confirm password visibility"
                    >
                        <i class="fas fa-eye"  style=   "    margin-top: 162%"></i>
                    </button>
                    <div style="margin-top: 4px; font-size: 0.7rem; color: #94a3b8;" id="passwordMatch"></div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the 
                        <a href="#">Terms of Service</a> 
                        and 
                        <a href="#">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="register-btn" id="registerBtn">
                    <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
                    Create Account
                </button>
                
                <div class="divider">
                    <span>Already have an account?</span>
                </div>
                
                <div class="login-link">
                    <a href="../index.php">
                        <i class="fas fa-sign-in-alt" style="margin-right: 6px;"></i>
                        Sign in to your account
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirm = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirmPassword');
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const registerForm = document.getElementById('registerForm');
            const registerBtn = document.getElementById('registerBtn');
            const termsCheckbox = document.getElementById('terms');
            
            togglePassword.addEventListener('click', function() {
                toggleVisibility(passwordInput, this);
            });
            
            toggleConfirm.addEventListener('click', function() {
                toggleVisibility(confirmInput, this);
            });
            
            function toggleVisibility(input, button) {
                const icon = button.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                updateStrengthIndicator(strength, password);
                
                if (confirmInput.value.length > 0) {
                    checkPasswordMatch();
                }
            });
            
            confirmInput.addEventListener('input', function() {
                checkPasswordMatch();
            });
            
            function checkPasswordStrength(password) {
                let score = 0;
                
                if (password.length === 0) return 0;
                if (password.length >= 8) score++;
                if (password.length >= 12) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^A-Za-z0-9]/.test(password)) score++;
                
                return Math.min(4, Math.floor(score / 1.5));
            }
            
            function updateStrengthIndicator(strength, password) {
                const bars = passwordStrength.querySelectorAll('.bar');
                const levels = ['weak', 'medium', 'strong', 'very-strong'];
                const labels = ['Weak', 'Medium', 'Strong', 'Very Strong'];
                const colors = ['#ef4444', '#f59e0b', '#22c55e', '#10b981'];
                
                bars.forEach((bar, index) => {
                    bar.className = 'bar';
                    if (index < strength) {
                        bar.classList.add(levels[strength - 1] || 'weak');
                    }
                });
                
                if (password.length > 0) {
                    const label = labels[strength - 1] || 'Weak';
                    const color = colors[strength - 1] || '#ef4444';
                    strengthText.innerHTML = `<span style="color: ${color}; font-weight: 600;">${label}</span>`;
                } else {
                    strengthText.innerHTML = `<span>Password strength</span>`;
                }
            }
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (confirm.length === 0) {
                    passwordMatch.innerHTML = '';
                    return;
                }
                
                if (password === confirm) {
                    passwordMatch.innerHTML = '<span style="color: #22c55e;"><i class="fas fa-check-circle"></i> Passwords match</span>';
                } else {
                    passwordMatch.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Passwords do not match</span>';
                }
            }
            
            registerForm.addEventListener('submit', function(e) {
                if (!termsCheckbox.checked) {
                    e.preventDefault();
                    showError('Please agree to the Terms of Service and Privacy Policy');
                    return;
                }
                
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (password !== confirm) {
                    e.preventDefault();
                    showError('Passwords do not match');
                    return;
                }
                
                const strength = checkPasswordStrength(password);
                if (strength < 2) {
                    e.preventDefault();
                    showError('Please choose a stronger password (at least 8 characters with uppercase, lowercase, and numbers)');
                    return;
                }
                
                registerBtn.classList.add('loading');
                registerBtn.innerHTML = '';
                
                setTimeout(() => {
                    registerBtn.classList.remove('loading');
                }, 3000);
            });
            
            function showError(message) {
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.querySelector('span').textContent = message;
                    existingError.style.display = 'flex';
                    existingError.style.opacity = '1';
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${message}</span>`;
                    registerForm.insertBefore(errorDiv, registerForm.firstChild);
                    
                    setTimeout(() => {
                        errorDiv.style.transition = 'opacity 0.5s ease';
                        errorDiv.style.opacity = '0';
                        setTimeout(() => {
                            errorDiv.style.display = 'none';
                        }, 500);
                    }, 5000);
                }
            }
            
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.transition = 'opacity 0.5s ease';
                    errorMsg.style.opacity = '0';
                    setTimeout(() => {
                        errorMsg.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            const successMsg = document.getElementById('successMessage');
            if (successMsg) {
                setTimeout(() => {
                    successMsg.style.transition = 'opacity 0.5s ease';
                    successMsg.style.opacity = '0';
                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>