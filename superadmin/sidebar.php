<?php
// ============================================================
// SUPER ADMIN SIDEBAR
// ============================================================

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// ONLY ONE INCLUDE - ALL FUNCTIONS ARE IN permission.php
// ============================================================
require_once '../config/permission.php';

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    return;
}

// Check if Super Admin
$is_super_admin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'super admin';
if (!$is_super_admin) {
    return;
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['name'] ?? 'Super Admin';
$hospital_name = $_SESSION['hospital_name'] ?? 'Ultra Hospital';
$hospital_logo = $_SESSION['hospital_logo'] ?? '';
$profile_image = $_SESSION['profile_image'] ?? '';
?>

<style>
/* ============================================================
   SUPER ADMIN SIDEBAR STYLES
   ============================================================ */
#sidebar-container {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    z-index: 1000;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    transition: transform 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
}

#sidebar-container::-webkit-scrollbar { width: 4px; }
#sidebar-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
#sidebar-container::-webkit-scrollbar-track { background: transparent; }

@media (max-width: 1279px) {
    #sidebar-container {
        transform: translateX(-100%);
        width: 280px;
        box-shadow: 4px 0 20px rgba(0,0,0,0.1);
    }
    #sidebar-container.active { transform: translateX(0); }
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}
.sidebar-overlay.active { display: block; }

.sidebar {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    font-family: 'Inter', sans-serif;
}

/* ============================================================
   SIDEBAR HEADER / BRAND
   ============================================================ */
.sidebar-header {
    padding: 1.25rem 1rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    background: #fafbfc;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.sidebar-brand .brand-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    flex-shrink: 0;
}

.sidebar-brand .brand-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e293b;
    letter-spacing: -0.3px;
    white-space: nowrap;
}

.sidebar-brand .brand-name small {
    display: block;
    font-size: 0.6rem;
    font-weight: 400;
    color: #94a3b8;
    letter-spacing: 0.5px;
}

.sidebar-close {
    display: none;
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}
.sidebar-close:hover { background: #f1f5f9; color: #475569; }

@media (max-width: 1279px) {
    .sidebar-close { display: block; }
}

/* ============================================================
   SIDEBAR NAVIGATION
   ============================================================ */
.sidebar-nav {
    flex: 1;
    padding: 0.75rem 0.75rem;
    overflow-y: auto;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.8rem;
    border-radius: 10px;
    text-decoration: none;
    color: #475569;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 2px;
    cursor: pointer;
}

.sidebar-item i {
    width: 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 1.1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.sidebar-item:hover {
    background: #f1f5f9;
    color: #1e293b;
}
.sidebar-item:hover i { color: #3b82f6; }

.sidebar-item.active {
    background: #eff6ff;
    color: #3b82f6;
}
.sidebar-item.active i { color: #3b82f6; }

.sidebar-item.logout {
    color: #ef4444;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
    margin-top: 0.5rem;
}
.sidebar-item.logout i { color: #ef4444; }
.sidebar-item.logout:hover {
    background: #fef2f2;
    color: #dc2626;
}
.sidebar-item.logout:hover i { color: #dc2626; }

/* ============================================================
   SIDEBAR LABELS
   ============================================================ */
.sidebar-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.75rem 0.8rem 0.4rem;
    color: #94a3b8;
    font-weight: 600;
    border-top: 1px solid #f1f5f9;
    margin-top: 0.5rem;
}

.sidebar-label:first-of-type {
    border-top: none;
    margin-top: 0;
}

/* ============================================================
   SIDEBAR BADGE
   ============================================================ */
.sidebar-badge {
    background: #3b82f6;
    color: white;
    font-size: 0.6rem;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: auto;
    font-weight: 600;
}

.sidebar-badge.danger {
    background: #dc2626;
}

/* ============================================================
   SIDEBAR FOOTER
   ============================================================ */
.sidebar-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    background: #f8f9fa;
}

.sidebar-footer .user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sidebar-footer .user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(59,130,246,0.25);
}

.sidebar-footer .user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.sidebar-footer .user-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-footer .user-role {
    font-size: 0.65rem;
    color: #94a3b8;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    #sidebar-container {
        width: 280px;
    }
}

/* ============================================================
   SUPER ADMIN BADGE
   ============================================================ */
.super-admin-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    font-size: 0.55rem;
    padding: 2px 10px;
    border-radius: 12px;
    font-weight: 700;
    margin-left: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<!-- ============================================================
     SIDEBAR HTML
     ============================================================ -->
<div id="sidebar-container">
    <aside class="sidebar">
        
        <!-- ============================================================
        HEADER - BRAND
        ============================================================ -->
        <div class="sidebar-header">
            <a class="sidebar-brand" href="dashboard.php">
                <span class="brand-icon">
                    <i class="fas fa-crown"></i>
                </span>
                <div class="brand-name">
                    <?php echo htmlspecialchars($hospital_name); ?>
                    <small>Super Admin Panel</small>
                </div>
            </a>
            <button class="sidebar-close" id="sidebar-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- ============================================================
        NAVIGATION
        ============================================================ -->
        <nav class="sidebar-nav">
            
            <!-- ============================================================
            MAIN SECTION
            ============================================================ -->
            <div class="sidebar-label">Main</div>
            
            <!-- Dashboard -->
            <a href="dashboard.php" class="sidebar-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <!-- ============================================================
            MANAGEMENT SECTION
            ============================================================ -->
            <div class="sidebar-label">Management</div>
            
            <!-- Hospitals -->
            <a href="hospitals.php" class="sidebar-item <?php echo $current_page == 'hospitals.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i>
                <span>Hospitals</span>
                <?php 
                $hospital_count = getCount('hospital_master');
                if ($hospital_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $hospital_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Users -->
            <a href="users.php" class="sidebar-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
                <?php 
                $user_count = getCount('register');
                if ($user_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $user_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Doctors -->
            <a href="doctors.php" class="sidebar-item <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i>
                <span>Doctors</span>
                <?php 
                $doctor_count = getCount('doctor');
                if ($doctor_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $doctor_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Staff -->
            <a href="staff.php" class="sidebar-item <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Staff</span>
                <?php 
                $staff_count = getCount('staff');
                if ($staff_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $staff_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Departments -->
            <a href="departments.php" class="sidebar-item <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Departments</span>
                <?php 
                $dept_count = getCount('department');
                if ($dept_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $dept_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Subscriptions -->
            <a href="subscriptions.php" class="sidebar-item <?php echo $current_page == 'subscriptions.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Subscriptions</span>
                <?php 
                $sub_count = getCount('subscriptions');
                if ($sub_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $sub_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- ============================================================
            ACCESS CONTROL SECTION
            ============================================================ -->
            <div class="sidebar-label">Access Control</div>
            
            <!-- Roles -->
            <a href="role_list.php" class="sidebar-item <?php echo $current_page == 'role_list.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tag"></i>
                <span>Roles</span>
                <?php 
                $role_count = getCount('roles');
                if ($role_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $role_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Permissions -->
            <a href="permissions.php" class="sidebar-item <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i>
                <span>Permissions</span>
                <?php 
                $perm_count = getCount('permissions');
                if ($perm_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $perm_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- ============================================================
            SYSTEM SECTION
            ============================================================ -->
            <div class="sidebar-label">System</div>
            
            <!-- Audit Logs -->
            <a href="audit_logs.php" class="sidebar-item <?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Audit Logs</span>
                <?php 
                $audit_count = getCount('audit_logs');
                if ($audit_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $audit_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Login Logs -->
            <a href="login_logs.php" class="sidebar-item <?php echo $current_page == 'login_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login Logs</span>
                <?php 
                $login_count = getCount('login_logs');
                if ($login_count > 0): 
                ?>
                <span class="sidebar-badge"><?php echo $login_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Settings -->
            <a href="settings.php" class="sidebar-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>

            <!-- ============================================================
            ACCOUNT SECTION
            ============================================================ -->
            <div class="sidebar-label">Account</div>
            
            <!-- Profile -->
            <a href="profile.php" class="sidebar-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
            
            <!-- Change Password -->
            <a href="change_password.php" class="sidebar-item <?php echo $current_page == 'change_password.php' ? 'active' : ''; ?>">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>

            <!-- Logout -->
            <a href="../auth/Logout.php" class="sidebar-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>

        </nav>

        <!-- ============================================================
        FOOTER - USER INFO
        ============================================================ -->
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (!empty($profile_image) && file_exists($profile_image)): ?>
                        <img src="<?php echo $profile_image; ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="user-role">
                        <i class="fas fa-crown" style="color:#f59e0b;font-size:0.6rem;"></i> 
                        SUPER ADMIN
                    </p>
                </div>
            </div>
        </div>

    </aside>
</div>

<!-- ============================================================
MOBILE OVERLAY
============================================================ -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============================================================
JAVASCRIPT
============================================================ -->
<script>
/**
 * Close sidebar on mobile
 */
document.getElementById('sidebar-close')?.addEventListener('click', function() {
    document.getElementById('sidebar-container').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
});

/**
 * Close sidebar on overlay click
 */
document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
    document.getElementById('sidebar-container').classList.remove('active');
    this.classList.remove('active');
});

/**
 * Toggle sidebar on mobile
 */
function toggleSidebar() {
    document.getElementById('sidebar-container').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

/**
 * Close sidebar on Escape key
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('sidebar-container')?.classList.remove('active');
        document.getElementById('sidebarOverlay')?.classList.remove('active');
    }
});

/**
 * Auto-close sidebar on resize to desktop
 */
window.addEventListener('resize', function() {
    if (window.innerWidth > 1279) {
        document.getElementById('sidebar-container')?.classList.remove('active');
        document.getElementById('sidebarOverlay')?.classList.remove('active');
    }
});

// Prevent default for empty links
document.querySelectorAll('a[href="#"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
    });
});

// Highlight current page in sidebar
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar-item').forEach(function(item) {
        const href = item.getAttribute('href');
        if (href && href === currentPage) {
            item.classList.add('active');
        }
    });
});
</script>