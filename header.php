<?php
// ============================================================
// DYNAMIC HEADER - ENTERPRISE LEVEL
// ============================================================

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// FIX: Include required config files
// ============================================================
require_once 'config/hospital.php';
require_once 'config/permission.php';

// ============================================================
// FIX: Define missing functions if they don't exist
// ============================================================

if (!function_exists('getUserProfile')) {
    function getUserProfile($user_id) {
        global $conn;
        
        // Check if connection exists and is open
        if (!isset($conn) || $conn === null || !$conn->ping()) {
            // Connection is closed or not available, use session data
            return [
                'name' => $_SESSION['name'] ?? 'User', 
                'profile_image' => $_SESSION['profile_image'] ?? ''
            ];
        }
        
        // Get profile image from admin_profile table
        $query = "SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    u.role_id,
                    COALESCE(ap.profile_image, '') as profile_image
                  FROM register u
                  LEFT JOIN admin_profile ap ON u.id = ap.register_id
                  WHERE u.id = '$user_id' 
                  AND (u.delete_flag = 0 OR u.delete_flag IS NULL)";
        
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $data = mysqli_fetch_assoc($result);
            
            // Update session with profile image if exists
            if (!empty($data['profile_image']) && file_exists($data['profile_image'])) {
                $_SESSION['profile_image'] = $data['profile_image'];
            } else {
                $_SESSION['profile_image'] = '';
            }
            
            return $data;
        }
        
        // Fallback to session data
        return [
            'name' => $_SESSION['name'] ?? 'User', 
            'profile_image' => $_SESSION['profile_image'] ?? ''
        ];
    }
}

if (!function_exists('getNotificationCount')) {
    function getNotificationCount($user_id) {
        global $conn;

        // Check if connection exists and is open
        if (!isset($conn) || $conn === null || !$conn->ping()) {
            return 0;
        }

        $check_query = "SHOW COLUMNS FROM audit_logs LIKE 'is_read'";
        $check_result = mysqli_query($conn, $check_query);
        $has_is_read = ($check_result && mysqli_num_rows($check_result) > 0);

        if ($has_is_read) {
            $query = "SELECT COUNT(*) as count
                      FROM audit_logs
                      WHERE register_id = '$user_id'
                      AND is_read = 0
                      AND (delete_flag = 0 OR delete_flag IS NULL)";
        } else {
            $query = "SELECT COUNT(*) as count
                      FROM audit_logs
                      WHERE register_id = '$user_id'
                      AND (delete_flag = 0 OR delete_flag IS NULL)";
        }

        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['count'];
        }

        return 0;
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission_name) {
        global $permission_names;
        
        // If $permission_names is not set, get from session
        if (!isset($permission_names)) {
            if (isset($_SESSION['permissions']) && !empty($_SESSION['permissions'])) {
                $session_perms = $_SESSION['permissions'];
                if (is_array($session_perms)) {
                    foreach ($session_perms as $perm) {
                        if (is_array($perm) && isset($perm['permission_name'])) {
                            $permission_names[] = $perm['permission_name'];
                        } elseif (is_string($perm)) {
                            $permission_names[] = $perm;
                        }
                    }
                }
            } else {
                $permission_names = [];
            }
        }
        
        // Super Admin has all permissions
        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'super admin') {
            return true;
        }
        
        return in_array($permission_name, $permission_names);
    }
}

// ============================================================
// Get user data
// ============================================================
$profile_id = $_SESSION['register_id'] ?? $_SESSION['id'] ?? 0;
$user_id = $_SESSION['id'] ?? 0;
$role_id = $_SESSION['role_id'] ?? 0;
$role_name = $_SESSION['role'] ?? '';
$user_profile = getUserProfile($profile_id);
$notification_count = getNotificationCount($user_id);

// Get permissions
if (function_exists('getUserPermissions')) {
    $user_permissions = getUserPermissions($profile_id);
} else {
    $user_permissions = [];
}

if (is_array($user_permissions)) {
    if (isset($user_permissions[0]) && is_array($user_permissions[0])) {
        $permission_names = array_column($user_permissions, 'permission_name');
    } else {
        $permission_names = $user_permissions;
    }
} else {
    $permission_names = [];
}
$theme = $_SESSION['theme'] ?? 'light';

// Store permissions in session for future use
if (!isset($_SESSION['permissions']) || empty($_SESSION['permissions'])) {
    $_SESSION['permissions'] = $permission_names;
}

// Ensure profile image is in session
if (!isset($_SESSION['profile_image']) || empty($_SESSION['profile_image'])) {
    $_SESSION['profile_image'] = $user_profile['profile_image'] ?? '';
}

?>

<style>
.dynamic-header {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 70px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: margin-left 0.3s ease;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.header-left .hamburger {
    display: none;
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #475569;
    cursor: pointer;
    padding: 8px;
}

.header-left .breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #94a3b8;
}

.header-left .breadcrumb a {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.2s ease;
}

.header-left .breadcrumb a:hover {
    color: #2563eb;
}

.header-left .breadcrumb .separator {
    color: #cbd5e1;
    font-size: 0.6rem;
}

.header-left .breadcrumb .current {
    color: #1e293b;
    font-weight: 500;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-right .nav-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-right .nav-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    background: none;
    border: none;
    cursor: pointer;
}

.header-right .nav-link:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.header-right .nav-link i {
    font-size: 1.1rem;
}

.header-right .nav-link .badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc2626;
    color: white;
    font-size: 0.6rem;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    transform: translate(4px, -4px);
}

.header-right .nav-link .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    overflow: hidden;
    flex-shrink: 0;
}

.header-right .nav-link .avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.header-right .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 240px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
    padding: 8px 0;
    display: none;
    margin-top: 8px;
    z-index: 1000;
}

.header-right .dropdown-menu.open {
    display: block;
    animation: slideDown 0.2s ease;
}

.header-right .dropdown-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    color: #475569;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.header-right .dropdown-menu .dropdown-item:hover {
    background: #f8fafc;
    color: #1e293b;
}

.header-right .dropdown-menu .dropdown-item i {
    width: 20px;
    text-align: center;
    color: #94a3b8;
}

.header-right .dropdown-menu .dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 4px 12px;
}

.header-right .dropdown-menu .dropdown-header {
    padding: 8px 16px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .dynamic-header {
        padding: 10px 16px;
    }
    
    .header-left .hamburger {
        display: block;
    }
    
    .header-left .breadcrumb {
        font-size: 0.7rem;
    }
    
    .header-right .nav-link .label {
        display: none;
    }
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<header class="dynamic-header">
    <!-- Left Side -->
    <div class="header-left">
        <button class="hamburger" onclick="toggleMobileSidebar()">
            <i class="fas fa-bars"></i>
        </button>
       
    </div>

    <!-- Right Side -->
    <div class="header-right">
      

        <!-- Notifications -->
        <?php if (hasPermission('notifications-view')): ?>
        <div class="nav-item">
            <button class="nav-link" onclick="toggleDropdown('notificationDropdown')" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($notification_count > 0): ?>
                <span class="badge"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu" id="notificationDropdown">
                <div class="dropdown-header">
                    Notifications
                    <?php if ($notification_count > 0): ?>
                    <span style="float:right;color:#3b82f6;cursor:pointer;" onclick="markAllRead()">Mark all read</span>
                    <?php endif; ?>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-item" style="justify-content:center;color:#94a3b8;">
                    <i class="fas fa-check-circle"></i> No new notifications
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reports - Only if user has permission -->
        <?php if (hasPermission('reports-view')): ?>
        <div class="nav-item">
            <button class="nav-link" onclick="toggleDropdown('reportsDropdown')" title="Reports">
                <i class="fas fa-chart-bar"></i>
                <span class="label">Reports</span>
            </button>
            <div class="dropdown-menu" id="reportsDropdown">
                <div class="dropdown-header">Reports</div>
                <div class="dropdown-divider"></div>
                <?php if (hasPermission('reports-patient')): ?>
                <a href="reports/patients.php" class="dropdown-item">
                    <i class="fas fa-user-injured"></i> Patient Reports
                </a>
                <?php endif; ?>
                <?php if (hasPermission('reports-appointment')): ?>
                <a href="reports/appointments.php" class="dropdown-item">
                    <i class="fas fa-calendar-check"></i> Appointment Reports
                </a>
                <?php endif; ?>
                <?php if (hasPermission('reports-billing')): ?>
                <a href="reports/billing.php" class="dropdown-item">
                    <i class="fas fa-file-invoice-dollar"></i> Billing Reports
                </a>
                <?php endif; ?>
                <?php if (hasPermission('reports-financial')): ?>
                <a href="reports/financial.php" class="dropdown-item">
                    <i class="fas fa-coins"></i> Financial Reports
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Profile -->
        <div class="nav-item">
            <button class="nav-link" onclick="toggleDropdown('profileDropdown')" title="Profile">
                <?php 
                // Get profile image from session
                $profile_image = $_SESSION['profile_image'] ?? '';
                $user_name = $_SESSION['name'] ?? 'User';
                ?>
                <?php if (!empty($profile_image) && file_exists($profile_image)): ?>
                <div class="avatar">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                </div>
                <?php else: ?>
                <div class="avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <?php endif; ?>
                <span class="label"><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fas fa-chevron-down" style="font-size:0.6rem;color:#94a3b8;"></i>
            </button>
            <div class="dropdown-menu" id="profileDropdown">
                <div class="dropdown-header">
                    <?php echo htmlspecialchars($user_name); ?>
                    <div style="font-weight:400;font-size:0.75rem;color:#94a3b8;text-transform:capitalize;">
                        <?php echo htmlspecialchars($role_name); ?>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="update_adminprofile.php" class="dropdown-item">
                    <i class="fas fa-user"></i> My Profile
                </a>
               
                <?php if (hasPermission('system-settings') || strtolower($role_name) == 'super admin'): ?>
                <div class="dropdown-divider"></div>
                <a href="settings.php" class="dropdown-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <?php endif; ?>
                <?php if (hasPermission('system-users') || strtolower($role_name) == 'super admin'): ?>
                <a href="register.php" class="dropdown-item">
                    <i class="fas fa-users-cog"></i> User Management
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="auth/Logout.php" class="dropdown-item" style="color:#dc2626;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<script>
/**
 * Toggle dropdown menu
 */
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(d => {
        if (d.id !== id) d.classList.remove('open');
    });
    
    dropdown.classList.toggle('open');
}

/**
 * Close all dropdowns on click outside
 */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-item')) {
        document.querySelectorAll('.dropdown-menu').forEach(d => {
            d.classList.remove('open');
        });
    }
});

/**
 * Toggle mobile sidebar
 */
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar-container');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) {
        sidebar.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
}

/**
 * Mark all notifications as read
 */
function markAllRead() {
    fetch('ajax/mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ all: true })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.badge');
            if (badge) badge.remove();
            location.reload();
        }
    });
}

// Close dropdowns on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.remove('open'));
    }
});

// Refresh profile image on page load if session has profile image
document.addEventListener('DOMContentLoaded', function() {
    // Check if profile image exists in session
    const profileImage = '<?php echo $_SESSION['profile_image'] ?? ''; ?>';
    if (profileImage) {
        // Profile image is already in session, no need to do anything
        console.log('Profile image loaded from session');
    }
});
</script>