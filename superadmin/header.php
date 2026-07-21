<?php
// ============================================================
// HEADER.PHP - Professional Header with Responsive Sidebar
// ============================================================

// Define variables with default values if not set
if (!isset($success)) {
    $success = isset($_GET['success']) ? true : false;
}

if (!isset($updated)) {
    $updated = isset($_GET['updated']) ? true : false;
}

if (!isset($deleted)) {
    $deleted = isset($_GET['deleted']) ? true : false;
}

if (!isset($error_msg)) {
    $error_msg = '';
    $error = isset($_GET['error']) ? $_GET['error'] : '';
    
    if ($error == 'system_role') {
        $error_msg = 'System roles cannot be deleted.';
    } elseif ($error == 'users_assigned') {
        $error_msg = 'Cannot delete this role because users are assigned to it.';
    } elseif ($error == 'delete_failed') {
        $error_msg = 'Failed to delete the role. Please try again.';
    } elseif ($error == 'permission_error') {
        $error_msg = 'You do not have permission to perform this action.';
    }
}

// Get user info
$user_name = $_SESSION['name'] ?? 'Admin';
$user_avatar = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
$user_role = $_SESSION['role'] ?? 'Super Admin';
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Your head content -->
    <style>
/* ================================
   Header Styles
================================ */

body.sidebar-collapsed .header-container{
    left:80px;
}

body.sidebar-collapsed .main-content{
    margin-left:80px;
}   

.header-container{
    position: fixed;
    top: 0;
       left: 251px;
    right: -1%;
    height: 77px;
    background: rgba(255, 255, 255, .97);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    z-index: 1000;
    transition: all .3s ease;
    padding: 0 30px;
    display: flex;
    align-items: center;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

/* Left Section */
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Mobile Toggle Button */
.mobile-toggle-btn {
    display: none;
    background: none;
    border: none;
    outline: none;
    color: #1e293b;
    font-size: 20px;
    cursor: pointer;
    padding: 8px 10px;
    border-radius: 10px;
    transition: all 0.3s ease;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.mobile-toggle-btn:hover {
    background: #f1f5f9;
}

.mobile-toggle-btn:active {
    transform: scale(0.95);
}

.mobile-toggle-btn i {
    transition: transform 0.3s ease;
}

.mobile-toggle-btn:hover i {
    transform: rotate(90deg);
}

@media(max-width:768px){
    .header-container{
        left:0;
        width:100%;
        padding:0 15px;
        height:65px;
    }

    .main-content{
        margin-left:0;
        margin-top:65px;
    }

    .mobile-toggle-btn{
        display:flex;
    }
}

/* Header Title */
.header-title h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    line-height: 1.2;
}

.header-title h1 .highlight {
    color: #3b82f6;
}

.header-title p {
    font-size: 14px;
    color: #94a3b8;
    margin: 2px 0 0 0;
}

/* Right Section */
.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-left: auto;
}

/* Date */
.header-date {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #64748b;
    padding: 6px 14px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e8edf4;
}

.header-date i {
    color: #3b82f6;
    font-size: 14px;
}

/* User Profile */
.header-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 6px 12px 6px 6px;
    border-radius: 50px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    position: relative;
}

.header-profile:hover {
    background: #f8fafc;
    border-color: #e8edf4;
}

.header-profile .user-info {
    text-align: right;
    line-height: 1.2;
}

.header-profile .user-name {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
}

.header-profile .user-role {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
}

.header-profile .avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    color: #ffffff;
    flex-shrink: 0;
    position: relative;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.header-profile:hover .avatar {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.header-profile .avatar .status-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 10px;
    height: 10px;
    background: #22c55e;
    border-radius: 50%;
    border: 2px solid #ffffff;
}

.header-profile .avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

/* Notification Bell */
.notification-bell {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e8edf4;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #64748b;
}

.notification-bell:hover {
    background: #f1f5f9;
    color: #0f172a;
    transform: translateY(-1px);
}

.notification-bell .badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
}

/* ================================
   Status Messages
================================ */

.success-msg, .error-msg {
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-msg i, .error-msg i {
    font-size: 18px;
}

/* ================================
   Mobile Responsive
================================ */

@media (max-width: 1024px) {
    .header-container {
        padding: 14px 20px;
    }
    
    .header-title h1 {
        font-size: 20px;
    }
}

@media (max-width: 768px) {
    .header-container {
        padding: 12px 16px;
    }
    
    .mobile-toggle-btn {
        display: flex;
    }
    
    .header-title h1 {
        font-size: 18px;
    }
    
    .header-title p {
        display: none;
    }
    
    .header-date {
        display: none;
    }
    
    .header-profile .user-info {
        display: none;
    }
    
    .header-profile {
        padding: 4px;
    }
    
    .header-profile .avatar {
        width: 38px;
        height: 38px;
        font-size: 14px;
    }
    
    .notification-bell {
        width: 38px;
        height: 38px;
    }
    
    .notification-bell .badge {
        width: 16px;
        height: 16px;
        font-size: 9px;
        top: -3px;
        right: -3px;
    }
    
    .main-content{
        margin-left:0;
        margin-top:65px;
    }
    
    .success-msg, .error-msg {
        padding: 12px 16px;
        font-size: 13px;
        border-radius: 10px;
    }
}

@media (max-width: 480px) {
    .header-container {
        padding: 10px 12px;
    }
    
    .header-title h1 {
        font-size: 16px;
    }
    
    .header-right {
        gap: 8px;
    }
    
    .notification-bell {
        width: 34px;
        height: 34px;
        font-size: 14px;
    }
    
    .header-profile .avatar {
        width: 34px;
        height: 34px;
        font-size: 12px;
    }
}

/* ================================
   Sidebar Z-Index
================================ */

.sidebar {
    z-index: 999;
}

.mobile-toggle-btn {
    z-index: 1000;
}

/* Dark overlay for mobile */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
    z-index: 998;
}

.sidebar-overlay.active {
    display: block;
}
    </style>
</head>
<body>

<!-- ============================================================
     SIDEBAR OVERLAY
     ============================================================ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ============================================================
     STATUS MESSAGES
     ============================================================ -->
<div class="container mx-auto px-4 md:px-6">
    <?php if ($success): ?>
        <div class="success-msg" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#16a34a;">
            <i class="fas fa-check-circle"></i> 
            <?php 
                if (strpos($_SERVER['REQUEST_URI'], 'add_role') !== false) {
                    echo 'Role created successfully!';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'edit_role') !== false) {
                    echo 'Role updated successfully!';
                } else {
                    echo 'Operation completed successfully!';
                }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($updated): ?>
        <div class="success-msg" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#16a34a;">
            <i class="fas fa-check-circle"></i> Role updated successfully!
        </div>
    <?php endif; ?>

    <?php if ($deleted): ?>
        <div class="success-msg" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#16a34a;">
            <i class="fas fa-check-circle"></i> Role deleted successfully!
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="error-msg" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#dc2626;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     HEADER CONTENT
     ============================================================ -->
<header class="header-container">
    <div class="header-content">
        <!-- Left Section -->
        

        <!-- Right Section -->
        <div class="header-right">
            <!-- Mobile Toggle Button - Near User -->
            <button class="mobile-toggle-btn" id="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Notification Bell -->
            <div class="notification-bell" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </div>
            
            <!-- Date -->
            <span class="header-date">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('l, d M Y'); ?>
            </span>

            <!-- User Profile -->
            <div class="header-profile" title="<?php echo $user_name; ?>">
                <div class="user-info">
                    <div class="user-name"><?php echo $user_name; ?></div>
                    <div class="user-role"><?php echo $user_role; ?></div>
                </div>
                <div class="avatar">
                    <?php if (!empty($user_avatar)): ?>
                        <img src="<?php echo $user_avatar; ?>" alt="<?php echo $user_name; ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    <?php endif; ?>
                    <span class="status-dot"></span>
                </div>
            </div>
        </div>
    </div>
</header>

</body>
</html>