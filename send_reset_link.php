<?php
session_start();
include "config/hospital.php";

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$hospital_name = $hospital['hospital_name'] ?? 'Hospital';
$hospital_logo = $hospital['hospital_logo'] ?? 'documents/hospital/logo.png';

$status = "";
$status_type = "";
$error = "";
$email = "";
$show_otp = false;

if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];
    $status_type = $_SESSION['status_type'] ?? 'error';
    unset($_SESSION['status']);
    unset($_SESSION['status_type']);
}

// Handle Email Submission
if(isset($_POST['submit_email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM register WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // 1. Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $uid = $result->fetch_assoc();
          
        $id = $uid['id'];
        $_SESSION['reset_user_id'] = $id;

      $template_name = "password_reset";

        $template_stmt = $conn->prepare("SELECT subject, body FROM email_templates WHERE template_name='reset_password'");
        $template_stmt->bind_param("s", $template_name);
        $template_stmt->execute();
        $template_result = $template_stmt->get_result();
        
        if ($template_result && $template_result->num_rows > 0) {
        $template = $template_result->fetch_assoc();

        $subject = str_replace(
            "{HOSPITAL_NAME}",
            $hospital_name,
            $template['subject']
        );

        $message = str_replace(
            ["{OTP}", "{HOSPITAL_NAME}"],
            [$otp, $hospital_name],
            $template['body']
        );
        } else {
            // Fallback if template is not found in database
            $subject = "Password Reset OTP - " . $hospital_name;
            $message = "Your OTP for password reset is: " . $otp;
        }
        $template_stmt->close();

        // 3. Send Email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ultrahospital8@gmail.com'; // Your Gmail address
            $mail->Password   = 'rjuk cjay cbeq wrub'; // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('ultrahospital8@gmail.com', $hospital_name);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();

            $show_otp = true;
            $status = "A 6-digit OTP has been sent to your email address.";
            $status_type = "success";

        } catch (Exception $e) {
            $error = "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $error = "Email address not found in our records.";
    }
    $stmt->close();
}

// Handle OTP Submission
if(isset($_POST['submit_otp'])) {
    $user_otp = $_POST['otp'];
    $stored_otp = $_SESSION['reset_otp'] ?? '';
    $email = $_SESSION['reset_email'] ?? '';

    if ($user_otp == $stored_otp) {
        $_SESSION['otp_verified'] = true;
        header('Location: reset_pass.php');
        exit();
    } else {
        $show_otp = true; 
        $error = "Invalid OTP. Please check your email and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password - <?php echo htmlspecialchars($hospital_name); ?></title>
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
        .welcome-text { text-align: center; margin: 1.75rem 0 1.5rem 0; }
        .welcome-text h2 { color: #1e293b; font-size: 1.25rem; font-weight: 700; }
        .welcome-text p { color: #64748b; font-size: 0.875rem; margin-top: 0.25rem; }
        
        .input-group { position: relative; margin-bottom: 1.5rem; }
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
        
        .back-link { text-align: center; margin-top: 1.5rem; color: #64748b; font-size: 0.9rem; }
        .back-link a { color: #3b82f6; font-weight: 600; text-decoration: none; transition: color 0.3s ease; }
        .back-link a:hover { color: #2563eb; text-decoration: underline; }
        
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
        
        .otp-section {
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <img src="<?php echo htmlspecialchars($hospital_logo); ?>" width="150" height="150" alt="Hospital Logo" onerror="this.src='documents/hospital/logo.png'">
                </div>
            </div>
            
            <div class="brand-title"><?php echo htmlspecialchars($hospital_name); ?></div>
            <div class="brand-subtitle">Hospital Management System with AI</div>
            
            <div class="welcome-text">
                <h2>Forgot Password</h2>
                <p><?php echo $show_otp ? "Enter the 6-digit code sent to your email" : "Enter your registered email address to receive a reset link"; ?></p>
            </div>
            
            <?php if (!empty($status)): ?>
                <div class="<?php echo $status_type == 'success' ? 'success-message' : 'error-message'; ?>">
                    <i class="fas <?php echo $status_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                    <span><?php echo htmlspecialchars($status); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$show_otp): ?>
                <!-- Email Form -->
                <form action="" method="POST">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            placeholder="Enter your registered email" 
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                        >
                    </div>
                    
                    <button type="submit" name="submit_email" class="login-btn">
                        Send OTP
                    </button>
                </form>
            <?php else: ?>
                <!-- OTP Form -->
                <div class="otp-section">
                    <form action="" method="POST">
                        <div class="input-group">
                            <label for="otp">Enter OTP</label>
                            <i class="fas fa-key input-icon"></i>
                            <input 
                                type="text" 
                                name="otp" 
                                id="otp" 
                                placeholder="Enter 6-digit OTP" 
                                maxlength="6"
                                pattern="\d{6}"
                                required
                            >
                        </div>
                        
                        <button type="submit" name="submit_otp" class="login-btn">
                            Verify OTP
                        </button>
                        
                        <div class="back-link">
                            <a href="">Resend OTP</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left mr-2"></i>Back to Login</a>
            </div>
            
        </div>
    </div>
</body>
</html>
