<?php
// ============================================================
// HEADER - PROFESSIONAL WITH PERMISSION CHECK
// ============================================================

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config files for permission checks
include_once 'config/hospital.php';
include_once 'config/superadmin.php';
include_once 'config/constants.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    return;
}

// Get user profile information
$profile_image = $_SESSION['profile_image'] ?? '';
$user_name = $_SESSION['name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'Guest';
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$theme = $_SESSION['theme'] ?? 'light';

// Check if Super Admin
$is_super_admin = isset($_SESSION['role']) && $_SESSION['role'] === SUPER_ADMIN_ROLE;

// Check if Admin
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === ADMIN_ROLE || $_SESSION['role'] === 'admin');

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Dashboard subtitle based on role
$dashboard_subtitle = $is_super_admin ? 'Super Admin Dashboard' : ($is_admin ? 'Admin Dashboard' : 'Dashboard');

// Quick actions based on permissions
$quick_actions = [];
if (hasPermission('patient-view')) {
    $quick_actions[] = ['label' => 'Patients', 'icon' => 'fa-user', 'url' => 'patients.php'];
}
if (hasPermission('appointment-view')) {
    $quick_actions[] = ['label' => 'Appointments', 'icon' => 'fa-calendar-check', 'url' => 'appointments.php'];
}
if (hasPermission('doctor-view')) {
    $quick_actions[] = ['label' => 'Doctors', 'icon' => 'fa-user-md', 'url' => 'doctors.php'];
}
if (hasPermission('department-view')) {
    $quick_actions[] = ['label' => 'Departments', 'icon' => 'fa-building', 'url' => 'departments.php'];
}
?>

<style>
    /* ============================================================
       HEADER STYLES - PROFESSIONAL & RESPONSIVE
       ============================================================ */
    .header-container {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 64px;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        width: 100%;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
        min-width: 0;
    }

    .header-brand {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        flex-shrink: 0;
    }

    .header-brand .brand-logo {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .header-brand .brand-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(59,130,246,0.25);
    }

    .header-brand .brand-icon.superadmin {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 4px 12px rgba(245,158,11,0.3);
    }

    .header-brand-text {
        min-width: 0;
        flex: 1;
    }

    .header-brand-text h1 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-brand-text p {
        font-size: 0.65rem;
        color: #94a3b8;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-brand-text p.superadmin {
        color: #f59e0b;
        font-weight: 600;
    }

    .header-divider {
        width: 1px;
        height: 28px;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    /* Quick Actions */
    .header-quick-actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
        flex: 1;
    }

    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.6rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 500;
        color: #475569;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .quick-action-btn:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #3b82f6;
        transform: translateY(-1px);
    }

    .quick-action-btn i {
        font-size: 0.65rem;
        color: #94a3b8;
    }

    .quick-action-btn:hover i {
        color: #3b82f6;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
    }

    .header-date {
        font-size: 0.75rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }

    .header-date i {
        color: #cbd5e1;
    }

    /* Profile Button */
    .profile-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.5rem 0.25rem 0.25rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
        position: relative;
        flex-shrink: 0;
    }

    .profile-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .profile-btn .avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.7rem;
        flex-shrink: 0;
        overflow: hidden;
    }

    .profile-btn .avatar.superadmin {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .profile-btn .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-btn .info {
        display: flex;
        flex-direction: column;
        gap: 0.05rem;
        min-width: 0;
    }

    .profile-btn .info .name {
        font-size: 0.75rem;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80px;
    }

    .profile-btn .info .role {
        font-size: 0.6rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80px;
    }

    .profile-btn .info .role.superadmin {
        color: #f59e0b;
        font-weight: 600;
    }

    .profile-btn .chevron {
        font-size: 0.6rem;
        color: #94a3b8;
        transition: transform 0.3s ease;
    }

    .profile-btn .chevron.open {
        transform: rotate(180deg);
    }

    /* Profile Dropdown */
    .profile-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        min-width: 220px;
        z-index: 1000;
        display: none;
        overflow: hidden;
        animation: slideDown 0.2s ease;
    }

    .profile-dropdown.active {
        display: block;
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

    .profile-dropdown-header {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .profile-dropdown-header .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
        overflow: hidden;
    }

    .profile-dropdown-header .avatar.superadmin {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .profile-dropdown-header .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-dropdown-header .info h4 {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .profile-dropdown-header .info p {
        font-size: 0.7rem;
        color: #94a3b8;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .profile-dropdown-header .info p.superadmin {
        color: #f59e0b;
        font-weight: 600;
    }

    .profile-dropdown-body {
        padding: 0.25rem 0;
    }

    .profile-dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        color: #475569;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .profile-dropdown-item:hover {
        background: #f8fafc;
        color: #3b82f6;
    }

    .profile-dropdown-item i {
        width: 18px;
        text-align: center;
        font-size: 0.85rem;
        color: #94a3b8;
    }

    .profile-dropdown-item:hover i {
        color: #3b82f6;
    }

    .profile-dropdown-footer {
        padding: 0.25rem 0;
        border-top: 1px solid #e2e8f0;
    }

    .profile-dropdown-logout {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        color: #dc2626;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .profile-dropdown-logout:hover {
        background: #fef2f2;
    }

    .profile-dropdown-logout i {
        width: 18px;
        text-align: center;
        color: #dc2626;
    }

    .profile-dropdown-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 0.25rem 0;
    }

    /* ============================================================
       RESPONSIVE DESIGN
       ============================================================ */
    
    /* Tablet */
    @media (max-width: 1024px) {
        .header-quick-actions {
            display: none;
        }
        .header-divider {
            display: none;
        }
        .header-brand-text h1 {
            font-size: 0.9rem;
        }
        .header-brand-text p {
            font-size: 0.6rem;
        }
        .header-date {
            font-size: 0.7rem;
        }
        .profile-btn .info .name {
            max-width: 60px;
        }
        .profile-btn .info .role {
            max-width: 60px;
        }
    }

    /* Mobile */
    @media (max-width: 768px) {
        .header-container {
            padding: 0.4rem 0.75rem;
            min-height: 56px;
        }
        .header-brand .brand-logo {
            width: 30px;
            height: 30px;
        }
        .header-brand .brand-icon {
            width: 30px;
            height: 30px;
            font-size: 0.85rem;
        }
        .header-brand-text h1 {
            font-size: 0.8rem;
        }
        .header-brand-text p {
            display: none;
        }
        .header-date {
            display: none;
        }
        .profile-btn .info {
            display: none;
        }
        .profile-btn {
            padding: 0.2rem;
            border: none;
            background: transparent;
        }
        .profile-btn:hover {
            background: transparent;
            box-shadow: none;
        }
        .profile-btn .avatar {
            width: 28px;
            height: 28px;
            font-size: 0.65rem;
        }
        .profile-dropdown {
            min-width: 200px;
            right: -5px;
        }
        .header-left {
            gap: 0.5rem;
        }
        .header-right {
            gap: 0.5rem;
        }
    }

    /* Small Mobile */
    @media (max-width: 480px) {
        .header-container {
            padding: 0.3rem 0.5rem;
            min-height: 48px;
        }
        .header-brand .brand-logo {
            width: 26px;
            height: 26px;
        }
        .header-brand .brand-icon {
            width: 26px;
            height: 26px;
            font-size: 0.7rem;
        }
        .header-brand-text h1 {
            font-size: 0.7rem;
        }
        .profile-btn .avatar {
            width: 24px;
            height: 24px;
            font-size: 0.55rem;
        }
        .profile-dropdown {
            min-width: 180px;
            right: -5px;
        }
    }

    /* Hamburger Menu Button for Mobile */
    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        color: #475569;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.25rem;
    }

    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: block;
        }
    }
</style>

<!-- ============================================================
HEADER HTML
============================================================ -->
<header class="header-container">
    <!-- Left Side -->
    <div class="header-left">
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>

        <a class="header-brand" href="dashboard.php">
            <?php if ($hospital_logo): ?>
                <img alt="Hospital Logo" src="<?php echo htmlspecialchars($hospital_logo); ?>" class="brand-logo" />
            <?php else: ?>
                <div class="brand-icon <?php echo $is_super_admin ? 'superadmin' : ''; ?>">
                    <?php if ($is_super_admin): ?>
                        <i class="fas fa-crown"></i>
                    <?php else: ?>
                        <i class="fas fa-hospital"></i>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="header-brand-text">
                <h1><?php echo htmlspecialchars($hospital_name); ?></h1>
                <p class="<?php echo $is_super_admin ? 'superadmin' : ''; ?>">
                    <?php echo $is_super_admin ? 'Super Admin Panel' : $dashboard_subtitle; ?>
                </p>
            </div>
        </a>

        <?php if (!empty($quick_actions)): ?>
            <div class="header-divider"></div>
            <div class="header-quick-actions">
                <?php foreach ($quick_actions as $action): ?>
                    <a href="<?php echo htmlspecialchars($action['url']); ?>" class="quick-action-btn">
                        <i class="fas <?php echo htmlspecialchars($action['icon']); ?>"></i>
                        <?php echo htmlspecialchars($action['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Side -->
    <div class="header-right">
        <div class="header-date">
            <i class="fas fa-calendar-alt"></i>
            <?php echo date('M d, Y'); ?>
        </div>

        <!-- Profile Button -->
        <div style="position: relative;">
            <button class="profile-btn" id="profileBtn">
                <div class="avatar <?php echo $is_super_admin ? 'superadmin' : ''; ?>">
                    <?php if (!empty($profile_image) && file_exists($profile_image)): ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="info">
                    <span class="name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="role <?php echo $is_super_admin ? 'superadmin' : ''; ?>">
                        <?php echo htmlspecialchars($user_role); ?>
                    </span>
                </div>
                <i class="fas fa-chevron-down chevron" id="chevronIcon"></i>
            </button>

            <!-- Profile Dropdown -->
            <div class="profile-dropdown" id="profileDropdown">
                <!-- Header -->
                <div class="profile-dropdown-header">
                    <div class="avatar <?php echo $is_super_admin ? 'superadmin' : ''; ?>">
                        <?php if (!empty($profile_image) && file_exists($profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="info">
                        <h4><?php echo htmlspecialchars($user_name); ?></h4>
                        <p class="<?php echo $is_super_admin ? 'superadmin' : ''; ?>">
                            <?php echo htmlspecialchars($user_role); ?>
                        </p>
                    </div>
                </div>

                <!-- Body -->
                <div class="profile-dropdown-body">
                    <a href="update_adminprofile.php" class="profile-dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    
                    <?php if (hasPermission('system-settings') || $is_super_admin): ?>
                    <a href="settings.php" class="profile-dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if ($is_super_admin): ?>
                <div class="profile-dropdown-divider"></div>
                <div class="profile-dropdown-body">
                    <a href="superadmin/dashboard.php" class="profile-dropdown-item">
                        <i class="fas fa-crown"></i>
                        <span>Super Admin Dashboard</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="profile-dropdown-footer">
                    <a href="../auth/Logout.php" class="profile-dropdown-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ============================================================
JAVASCRIPT
============================================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Profile Dropdown
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const chevronIcon = document.getElementById('chevronIcon');

        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('active');
                if (chevronIcon) {
                    chevronIcon.classList.toggle('open');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!profileBtn.contains(event.target) && !profileDropdown.contains(event.target)) {
                    profileDropdown.classList.remove('active');
                    if (chevronIcon) {
                        chevronIcon.classList.remove('open');
                    }
                }
            });

            // Close dropdown on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && profileDropdown.classList.contains('active')) {
                    profileDropdown.classList.remove('active');
                    if (chevronIcon) {
                        chevronIcon.classList.remove('open');
                    }
                }
            });
        }

        // Mobile Menu Toggle (for sidebar)
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebarContainer = document.getElementById('sidebar-container');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileMenuBtn && sidebarContainer && sidebarOverlay) {
            mobileMenuBtn.addEventListener('click', function() {
                sidebarContainer.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }
    });
</script>