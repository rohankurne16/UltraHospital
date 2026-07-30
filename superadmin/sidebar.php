<?php
// ============================================================
// SUPER ADMIN SIDEBAR - MASTER FILE (FOR ALL PAGES)
// ============================================================

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// INCLUDE PERMISSION
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
    z-index: 1100;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(2px);
    z-index: 999;
    transition: all 0.3s ease;
}
.sidebar-overlay.active { display: block; }

.sidebar {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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
    flex: 1;
    min-width: 0;
}

.sidebar-brand .brand-icon {
    width: 42px;
    min-width: 42px;
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
    font-size: 1rem;
    color: #1e293b;
    letter-spacing: -0.3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.sidebar-brand .brand-name small {
    display: block;
    font-size: 0.6rem;
    font-weight: 400;
    color: #94a3b8;
    letter-spacing: 0.5px;
    text-transform: uppercase;
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
    flex-shrink: 0;
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
    padding: 0.5rem 0.5rem;
    overflow-y: auto;
}

/* ============================================================
   SIDEBAR ITEMS
   ============================================================ */
.sidebar-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0.8rem;
    border-radius: 10px;
    text-decoration: none;
    color: #475569;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.15s ease;
    margin-bottom: 1px;
    cursor: pointer;
    position: relative;
}

.sidebar-item i {
    width: 1.25rem;
    min-width: 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.sidebar-item span {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.sidebar-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background: #3b82f6;
    border-radius: 0 4px 4px 0;
}

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
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 0.6rem 0.8rem 0.3rem;
    color: #94a3b8;
    font-weight: 600;
    border-top: 1px solid #f1f5f9;
    margin-top: 0.25rem;
}

.sidebar-label:first-of-type {
    border-top: none;
    margin-top: 0;
    padding-top: 0.25rem;
}

/* ============================================================
   SIDEBAR BADGE
   ============================================================ */
.sidebar-badge {
    background: #3b82f6;
    color: white;
    font-size: 0.55rem;
    padding: 1px 8px;
    border-radius: 12px;
    font-weight: 600;
    flex-shrink: 0;
    min-width: 20px;
    text-align: center;
}

.sidebar-badge.danger {
    background: #dc2626;
}

/* ============================================================
   SIDEBAR FOOTER
   ============================================================ */
.sidebar-footer {
    padding: 0.65rem 1rem;
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
    width: 36px;
    min-width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.8rem;
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
    font-size: 0.8rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-footer .user-role {
    font-size: 0.55rem;
    color: #94a3b8;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.sidebar-footer .user-role i {
    color: #f59e0b;
    font-size: 0.5rem;
}

/* ============================================================
   MOBILE TOGGLE BUTTON
   ============================================================ */
.mobile-toggle-btn {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 998;
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    cursor: pointer;
    transition: all 0.2s ease;
}

.mobile-toggle-btn:hover {
    background: #f8fafc;
    color: #3b82f6;
}

@media (max-width: 1279px) {
    .mobile-toggle-btn {
        display: flex;
    }
}

@media (max-width: 768px) {
    #sidebar-container {
        width: 280px;
    }
}

@media (max-width: 480px) {
    #sidebar-container {
        width: 100%;
        max-width: 300px;
    }
}

/* ============================================================
   MAIN CONTENT ADJUSTMENT
   ============================================================ */
@media (max-width: 1279px) {
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
}

@media (min-width: 1280px) {
    .main-content {
        margin-left: 250px;
    }
}
</style>

<!-- ============================================================
     MOBILE TOGGLE BUTTON
     ============================================================ -->


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
    <div class="brand-logo">
        <img src="images/superadmin.jpg" alt="Super Admin Logo" width="45" height="45" style="border-radius:50%;">
    </div>

    <div class="brand-name">
        <span>Ultra Hospital</span>
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
            </a>
            
            <!-- Users -->
            <a href="users.php" class="sidebar-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            
            <!-- Doctors -->
            <a href="doctors.php" class="sidebar-item <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i>
                <span>Doctors</span>
            </a>
            
            <!-- Staff -->
            <a href="staff.php" class="sidebar-item <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Staff</span>
            </a>
            
            <!-- Departments -->
            <a href="departments.php" class="sidebar-item <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
            
            <!-- Subscriptions -->
            <a href="#" class="sidebar-item <?php echo $current_page == 'subscriptions.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Subscriptions</span>
            </a>

            <!-- ============================================================
            ACCESS CONTROL SECTION
            ============================================================ -->
            <div class="sidebar-label">Access Control</div>
            
            <!-- Roles -->
            <a href="role_list.php" class="sidebar-item <?php echo $current_page == 'role_list.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tag"></i>
                <span>Roles</span>
            </a>
            
            <!-- Permissions -->
            <a href="permissions.php" class="sidebar-item <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i>
                <span>Permissions</span>
            </a>

            
            <div class="sidebar-label">System</div>
            <a href="audit_logs.php" class="sidebar-item">
                <i class="fas fa-history"></i><span>Audit Logs</span>
            </a>

            <a href="login_logs.php" class="sidebar-item">
            <i class="fas fa-sign-in-alt"></i><span>Login Logs</span>
        </a>
            
            
           <div class="sidebar-label">Profile</div>
<a href="profile.php" class="sidebar-item">
    <i class="fas fa-user"></i><span> Profile</span>
</a>

            <!-- ============================================================
            LOGOUT
            ============================================================ -->
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
                        <i class="fas fa-crown"></i> Super Admin
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
JAVASCRIPT – SMART HIGHLIGHT + MOBILE TOGGLE (NO AJAX)
============================================================ -->
<script>
(function() {
    'use strict';

    // ---- CONFIG ----
    // Map URL patterns to the selector of the sidebar item that should be active.
    // The pattern can be a string (exact match) or a RegExp.
    const routeMap = [
        { pattern: /dashboard\.php/, selector: 'a[href="dashboard.php"]' },
        { pattern: /hospitals?\.php/i, selector: 'a[href="hospitals.php"]' }, // matches hospitals.php, add_hospital.php, edit_hospital.php, etc.
        { pattern: /users?\.php/i, selector: 'a[href="users.php"]' },
        { pattern: /doctors?\.php/i, selector: 'a[href="doctors.php"]' },
        { pattern: /staff\.php/i, selector: 'a[href="staff.php"]' },
        { pattern: /departments?\.php/i, selector: 'a[href="departments.php"]' },
        { pattern: /subscriptions?\.php/i, selector: 'a[href="#"]' }, // adjust if you have a real page
        { pattern: /role_list\.php/i, selector: 'a[href="role_list.php"]' },
        { pattern: /permissions?\.php/i, selector: 'a[href="permissions.php"]' },
        { pattern: /audit_logs\.php/i, selector: 'a[href="audit_logs.php"]' },
        { pattern: /login_logs\.php/i, selector: 'a[href="login_logs.php"]' },
        { pattern: /profile\.php/i, selector: 'a[href="profile.php"]' }
    ];

    // ---- HIGHLIGHT SIDEBAR ITEM BASED ON CURRENT URL ----
    function highlightSidebar() {
        // Remove all active classes from sidebar items
        document.querySelectorAll('.sidebar-item').forEach(function(el) {
            el.classList.remove('active');
        });

        const currentPath = window.location.pathname + window.location.search;
        let matched = false;

        // Try to match using the routeMap
        for (let route of routeMap) {
            if (route.pattern.test(currentPath) || route.pattern.test(window.location.pathname)) {
                const target = document.querySelector(route.selector);
                if (target) {
                    target.classList.add('active');
                    matched = true;
                    break;
                }
            }
        }

        // If no match, fallback to the PHP-generated active class (kept as backup)
        if (!matched) {
            const filename = currentPath.split('/').pop().split('?')[0];
            document.querySelectorAll('.sidebar-item').forEach(function(el) {
                const href = el.getAttribute('href');
                if (href && href === filename) {
                    el.classList.add('active');
                }
            });
        }
    }

    // ---- CLOSE SIDEBAR FUNCTION ----
    function closeSidebar() {
        const sidebar = document.getElementById('sidebar-container');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // ---- TOGGLE SIDEBAR (for mobile) ----
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar-container');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        }
    }

    // ---- INITIAL HIGHLIGHT ON PAGE LOAD ----
    document.addEventListener('DOMContentLoaded', function() {
        highlightSidebar();
    });

    // Also call highlight after a small delay in case of dynamic content
    setTimeout(highlightSidebar, 100);

    // ---- BIND CLOSE BUTTON AND OVERLAY ----
    document.getElementById('sidebar-close')?.addEventListener('click', closeSidebar);
    document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1279) closeSidebar();
    });

    // ---- EXPOSE TOGGLE FOR MOBILE BUTTON (if you have one) ----
    window.toggleSidebar = toggleSidebar;

})();
</script>