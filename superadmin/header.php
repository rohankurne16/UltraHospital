<?php
// ============================================================
// HEADER.PHP - Super Admin Header with Status Messages
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

// Get current theme
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Your head content -->
</head>
<body>

<!-- ============================================================
     STATUS MESSAGES
     ============================================================ -->
<?php if ($success): ?>
    <div class="success-msg" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:1rem;border-radius:10px;margin-bottom:1rem;">
        <i class="fas fa-check-circle mr-2"></i> 
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
    <div class="success-msg" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:1rem;border-radius:10px;margin-bottom:1rem;">
        <i class="fas fa-check-circle mr-2"></i> Role updated successfully!
    </div>
<?php endif; ?>

<?php if ($deleted): ?>
    <div class="success-msg" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:1rem;border-radius:10px;margin-bottom:1rem;">
        <i class="fas fa-check-circle mr-2"></i> Role deleted successfully!
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="error-msg" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;padding:1rem;border-radius:10px;margin-bottom:1rem;">
        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<!-- ============================================================
     HEADER CONTENT
     ============================================================ -->
<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary"><?php echo $page_title ?? 'Dashboard'; ?></h1>
        <p class="text-secondary text-sm"><?php echo $page_subtitle ?? 'Welcome to Super Admin Panel'; ?></p>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        <span class="text-secondary text-sm"><?php echo date('l, d M Y'); ?></span>
        
      
        
        <!-- User Avatar -->
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
            <?php echo isset($_SESSION['name']) ? substr($_SESSION['name'], 0, 2) : 'SA'; ?>
        </div>
    </div>
</div>

<!-- Theme Toggle Script -->

</body>
</html>