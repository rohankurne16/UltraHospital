<?php
include '../config/permission.php';

$page_title = 'My Profile';
$page_subtitle = 'View and manage your account information';

$theme = $_SESSION['theme'] ?? 'light';

// Get current user data (assuming you have user session variables)
$user_id = $_SESSION['user_id'] ?? 0;
$user_query = "SELECT * FROM user_master WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $update_query = "UPDATE user_master SET 
                     full_name = '$full_name',
                     email = '$email',
                     phone = '$phone',
                     address = '$address'
                     WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_query)) {
        logAudit('Profile', "Updated profile for User ID $user_id");
        $success = "Profile updated successfully!";
        // Refresh user data
        $user_result = mysqli_query($conn, $user_query);
        $user_data = mysqli_fetch_assoc($user_result);
    } else {
        $error = "Update Error: " . mysqli_error($conn);
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $user_data['password']) || $current_password === $user_data['password']) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = "UPDATE user_master SET password = '$hashed_password' WHERE user_id = $user_id";
            if (mysqli_query($conn, $update_pass)) {
                logAudit('Profile', "Changed password for User ID $user_id");
                $pass_success = "Password changed successfully!";
            } else {
                $pass_error = "Error updating password: " . mysqli_error($conn);
            }
        } else {
            $pass_error = "New passwords do not match!";
        }
    } else {
        $pass_error = "Current password is incorrect!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        body.light { background: #f1f5f9; }
        body.dark { background: #0a0a0a; }
   
        .content-card {
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; }
        body.dark .content-card { background: #1a1a1a; border: 1px solid #2a2a2a; }
        
        .form-control {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }
        body.light .form-control { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.dark .form-control { background: #1e1e1e; border: 1px solid #2a2a2a; color: #f1f5f9; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        body.dark .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        
        .btn-secondary {
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        .btn-secondary:hover { opacity: 0.8; }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            font-size: 3rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.3);
        }

        .success-msg { 
            background: rgba(34, 197, 94, 0.1); 
            border: 1px solid rgba(34, 197, 94, 0.3); 
            color: #22c55e; 
            padding: 1rem; 
            border-radius: 10px; 
            margin-bottom: 1rem; 
        }
        .error-msg { 
            background: rgba(239, 68, 68, 0.1); 
            border: 1px solid rgba(239, 68, 68, 0.3); 
            color: #ef4444; 
            padding: 1rem; 
            border-radius: 10px; 
            margin-bottom: 1rem; 
        }

        .main-content { margin-left: 18%; margin-top: 2%; padding: 2rem; }
        @media(max-width: 768px) { .main-content { margin-left: 0 !important; padding: 1rem; } }
        
        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            font-weight: 600;
        }
        .info-value {
            font-weight: 500;
            margin-top: 0.25rem;
        }
        body.light .info-value { color: #1e293b; }
        body.dark .info-value { color: #f1f5f9; }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
            color: #94a3b8;
        }
        .tab-btn.active {
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: #3b82f6;
        }
        .tab-btn:hover { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="<?php echo $theme; ?>">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <a href="dashboard.php" class="btn btn-primary" style="margin-bottom:2%;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <?php if (!empty($success)): ?>
            <div class="success-msg"><i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="content-card" style="text-align: center;">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 700; <?php echo $theme == 'dark' ? 'color: #f1f5f9;' : 'color: #1e293b;'; ?>">
                <?php echo htmlspecialchars($user_data['full_name'] ?? 'Admin User'); ?>
            </h2>
            <p style="color: #94a3b8; font-size: 0.9rem;">
                <?php echo htmlspecialchars($user_data['role'] ?? 'Super Admin'); ?>
            </p>
            <div style="margin-top: 0.75rem; display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                <span class="status-badge status-active" style="background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">
                    <i class="fas fa-circle" style="font-size: 0.4rem; margin-right: 0.25rem;"></i>
                    Active
                </span>
                <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">
                    <i class="fas fa-shield-alt" style="margin-right: 0.25rem;"></i>
                    <?php echo htmlspecialchars($user_data['role'] ?? 'Admin'); ?>
                </span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="content-card">
            <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding-bottom: 0.5rem; flex-wrap: wrap;">
                <button class="tab-btn active" onclick="showTab('profile')">
                    <i class="fas fa-user"></i> Profile Info
                </button>
                <button class="tab-btn" onclick="showTab('security')">
                    <i class="fas fa-lock"></i> Security
                </button>
                <button class="tab-btn" onclick="showTab('activity')">
                    <i class="fas fa-clock"></i> Activity Log
                </button>
            </div>

            <!-- Profile Info Tab -->
            <div id="tab-profile" class="tab-content active" style="padding-top: 1.5rem;">
                <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label class="info-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label class="info-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label class="info-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="info-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" disabled style="opacity: 0.6;">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="info-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <button type="submit" name="update_profile" class="btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Tab -->
            <div id="tab-security" class="tab-content" style="padding-top: 1.5rem;">
                <?php if (!empty($pass_success)): ?>
                    <div class="success-msg"><i class="fas fa-check-circle mr-2"></i> <?php echo $pass_success; ?></div>
                <?php endif; ?>
                <?php if (!empty($pass_error)): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $pass_error; ?></div>
                <?php endif; ?>
                
                <form method="POST" style="max-width: 500px;">
                    <div style="margin-bottom: 1rem;">
                        <label class="info-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="info-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <small style="color: #94a3b8; font-size: 0.75rem;">Minimum 8 characters</small>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label class="info-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
                
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;">
                    <h4 style="font-weight: 600; margin-bottom: 0.5rem; <?php echo $theme == 'dark' ? 'color: #f1f5f9;' : 'color: #1e293b;'; ?>">
                        <i class="fas fa-shield-alt" style="color: #3b82f6;"></i> Two-Factor Authentication
                    </h4>
                    <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 1rem;">
                        Enhance your account security by enabling two-factor authentication.
                    </p>
                    <button class="btn-secondary">
                        <i class="fas fa-enable"></i> Enable 2FA
                    </button>
                </div>
            </div>

            <!-- Activity Log Tab -->
            <div id="tab-activity" class="tab-content" style="padding-top: 1.5rem;">
                <?php
                // Get recent activity logs
                $audit_query = "SELECT * FROM audit_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10";
                $audit_result = mysqli_query($conn, $audit_query);
                ?>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Activity</th>
                                <th>Module</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($audit_result) > 0): ?>
                                <?php while($log = mysqli_fetch_assoc($audit_result)): ?>
                                    <tr class="table-row">
                                        <td style="font-size: 0.85rem; color: #94a3b8;">
                                            <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td style="font-weight: 500; <?php echo $theme == 'dark' ? 'color: #f1f5f9;' : 'color: #1e293b;'; ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </td>
                                        <td style="color: #94a3b8; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($log['module'] ?? 'N/A'); ?>
                                        </td>
                                        <td style="color: #94a3b8; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                        <i class="fas fa-history" style="font-size: 2rem; opacity: 0.2; display: block; margin-bottom: 0.5rem;"></i>
                                        No recent activity found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Account Summary -->
        <div class="content-card">
            <h4 style="font-weight: 600; margin-bottom: 1rem; <?php echo $theme == 'dark' ? 'color: #f1f5f9;' : 'color: #1e293b;'; ?>">
                <i class="fas fa-info-circle" style="color: #3b82f6;"></i> Account Details
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div>
                    <div class="info-label">Account Created</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($user_data['created_at'] ?? 'now')); ?></div>
                </div>
                <div>
                    <div class="info-label">Last Login</div>
                    <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($user_data['last_login'] ?? 'now')); ?></div>
                </div>
                <div>
                    <div class="info-label">Account Status</div>
                    <div class="info-value">
                        <span class="status-badge status-active">
                            <i class="fas fa-circle" style="font-size: 0.4rem; margin-right: 0.25rem;"></i>
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>