<?php
session_start();
include "../config/hospital.php";

$message = "";
$error = "";

if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["id"];

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['change_password'])) {
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation rules
    $errors = [];
    
    // Check if password is empty
    if (empty($new_password) || empty($confirm_password)) {
        $errors[] = "Both password fields are required.";
    }
    
    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }
    
    // Check minimum length
    if (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // Check maximum length (optional but recommended)
    if (strlen($new_password) > 50) {
        $errors[] = "Password must not exceed 50 characters.";
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $new_password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $new_password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "Password must contain at least one number.";
    }
    
    // Check for at least one special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?\\\\|`~]/', $new_password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&* etc.).";
    }
    
    // Check for common weak passwords
    $weak_passwords = ['password', '12345678', 'qwerty123', 'admin123', 'letmein', 'welcome', 'password123', 'abc123456'];
    if (in_array(strtolower($new_password), $weak_passwords)) {
        $errors[] = "Password is too common. Please choose a stronger password.";
    }
    
    // Check if password contains username or common patterns
    if (strpos(strtolower($new_password), strtolower($user_id)) !== false) {
        $errors[] = "Password should not contain your user ID.";
    }
    
    // If there are any errors, display them
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE register SET password='$hashed_password' WHERE id='$user_id'";
        if ($conn->query($update_sql)) {
            $message = "Password updated successfully. Please use your new password on next login.";
        } else {
            $error = "Error updating password: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo $hospital['hospital_name'] ?> </title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
        .password-requirement {
            transition: all 0.3s ease;
            padding: 4px 0;
        }
        .password-requirement.met {
            color: #10b981;
        }
        .password-requirement.unmet {
            color: #ef4444;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <?php include '../header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-2xl mx-auto">
                    
                    <!-- Header -->
                    <div class="flex flex-col gap-5 mb-8">
                        <div class="flex items-center flex-wrap gap-4">
                            <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800" href="dashboard.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                                <span class="sr-only">Back</span>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Change Password</h1>
                                <p class="text-gray-500 text-sm">Update your account security credentials.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form Card -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg p-6 shadow-sm">
                        <form action="change_pass.php" method="POST" class="space-y-5" id="passwordForm">
                            
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-neutral-300">New Password</label>
                                <input type="password" name="new_password" id="new_password" required 
                                    class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                                    placeholder="Enter new password" 
                                    minlength="8" maxlength="50"
                                    onkeyup="validatePassword(this.value)">
                                
                                <!-- Password Strength Bar -->
                                <div class="password-strength" id="strengthBar" style="background-color: #e5e7eb;"></div>
                                
                                <!-- Password Requirements -->
                                <div class="mt-3 space-y-1 text-sm">
                                    <div id="reqLength" class="password-requirement unmet">
                                        <span class="inline-block w-4">✗</span> At least 8 characters
                                    </div>
                                    <div id="reqUppercase" class="password-requirement unmet">
                                        <span class="inline-block w-4">✗</span> At least one uppercase letter
                                    </div>
                                    <div id="reqLowercase" class="password-requirement unmet">
                                        <span class="inline-block w-4">✗</span> At least one lowercase letter
                                    </div>
                                    <div id="reqNumber" class="password-requirement unmet">
                                        <span class="inline-block w-4">✗</span> At least one number
                                    </div>
                                    <div id="reqSpecial" class="password-requirement unmet">
                                        <span class="inline-block w-4">✗</span> At least one special character (!@#$%^&* etc.)
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-neutral-300">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" required 
                                    class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                                    placeholder="Repeat new password"
                                    onkeyup="validateConfirmPassword(this.value)">
                                <div id="passwordMatch" class="text-sm mt-1 hidden"></div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" name="change_password" id="submitBtn"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Update Password
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Security Tips -->
                    <div class="mt-8 p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-lg">
                        <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400 mb-2 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                            Security Tip
                        </h4>
                        <p class="text-xs text-amber-700 dark:text-amber-500 leading-relaxed">
                            Use a strong password that includes a mix of uppercase letters, numbers, and special characters. Avoid using easily guessable information like your name or date of birth. We recommend using a password manager to generate and store strong passwords.
                        </p>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function validatePassword(password) {
            // Requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};:'",.<>\/?\\|`~]/.test(password);
            
            // Update requirement indicators
            updateRequirement('reqLength', hasLength);
            updateRequirement('reqUppercase', hasUppercase);
            updateRequirement('reqLowercase', hasLowercase);
            updateRequirement('reqNumber', hasNumber);
            updateRequirement('reqSpecial', hasSpecial);
            
            // Update strength bar
            const strength = [hasLength, hasUppercase, hasLowercase, hasNumber, hasSpecial].filter(Boolean).length;
            updateStrengthBar(strength);
            
            // Check confirm password match if confirm field has value
            const confirmPassword = document.getElementById('confirm_password').value;
            if (confirmPassword) {
                validateConfirmPassword(confirmPassword);
            }
            
            // Enable/disable submit button
            updateSubmitButton();
        }
        
        function validateConfirmPassword(confirmPassword) {
            const newPassword = document.getElementById('new_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!confirmPassword) {
                matchDiv.classList.add('hidden');
                matchDiv.textContent = '';
                updateSubmitButton();
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.className = 'text-sm mt-1 text-green-600';
                matchDiv.textContent = '✓ Passwords match';
                matchDiv.classList.remove('hidden');
            } else {
                matchDiv.className = 'text-sm mt-1 text-red-600';
                matchDiv.textContent = '✗ Passwords do not match';
                matchDiv.classList.remove('hidden');
            }
            
            updateSubmitButton();
        }
        
        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            if (met) {
                element.className = 'password-requirement met';
                element.innerHTML = '<span class="inline-block w-4">✓</span> ' + element.textContent.substring(2);
            } else {
                element.className = 'password-requirement unmet';
                element.innerHTML = '<span class="inline-block w-4">✗</span> ' + element.textContent.substring(2);
            }
        }
        
        function updateStrengthBar(strength) {
            const bar = document.getElementById('strengthBar');
            const colors = ['#ef4444', '#f59e0b', '#f59e0b', '#3b82f6', '#10b981'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            const labels = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            
            bar.style.width = widths[strength - 1] || '0%';
            bar.style.backgroundColor = colors[strength - 1] || '#e5e7eb';
            bar.title = labels[strength - 1] || '';
            
            // Show strength label
            let labelElement = document.getElementById('strengthLabel');
            if (!labelElement) {
                labelElement = document.createElement('div');
                labelElement.id = 'strengthLabel';
                labelElement.className = 'text-xs mt-1 text-gray-500';
                bar.parentNode.appendChild(labelElement);
            }
            
            if (strength > 0) {
                labelElement.textContent = 'Password Strength: ' + labels[strength - 1];
                labelElement.style.color = colors[strength - 1];
            } else if (document.getElementById('new_password').value.length > 0) {
                labelElement.textContent = 'Password Strength: Very Weak';
                labelElement.style.color = '#ef4444';
            } else {
                labelElement.textContent = '';
            }
        }
        
        function updateSubmitButton() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            
            const isLengthValid = newPassword.length >= 8;
            const hasUppercase = /[A-Z]/.test(newPassword);
            const hasLowercase = /[a-z]/.test(newPassword);
            const hasNumber = /[0-9]/.test(newPassword);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};:'",.<>\/?\\|`~]/.test(newPassword);
            const passwordsMatch = newPassword === confirmPassword && confirmPassword.length > 0;
            
            const isValid = isLengthValid && hasUppercase && hasLowercase && 
                           hasNumber && hasSpecial && passwordsMatch;
            
            submitBtn.disabled = !isValid;
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state
            updateSubmitButton();
            
            // Add form validation on submit
            document.getElementById('passwordForm').addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                // Client-side validation before submitting
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
                
                // Check for required patterns
                if (!/[A-Z]/.test(newPassword)) {
                    e.preventDefault();
                    alert('Password must contain at least one uppercase letter!');
                    return false;
                }
                
                if (!/[a-z]/.test(newPassword)) {
                    e.preventDefault();
                    alert('Password must contain at least one lowercase letter!');
                    return false;
                }
                
                if (!/[0-9]/.test(newPassword)) {
                    e.preventDefault();
                    alert('Password must contain at least one number!');
                    return false;
                }
                
                if (!/[!@#$%^&*()_+\-=\[\]{};:'",.<>\/?\\|`~]/.test(newPassword)) {
                    e.preventDefault();
                    alert('Password must contain at least one special character!');
                    return false;
                }
            });
        });
    </script>
    
    <?php $conn->close(); ?>
</body>
</html>