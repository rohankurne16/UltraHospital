<?php
// ============================================================
// DYNAMIC SIDEBAR (sidebar.php)
// ============================================================

// Include permission config
require_once __DIR__ . '/config/permission.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info from session
$user_role = $_SESSION['role'] ?? 'Guest';
$user_name = $_SESSION['name'] ?? 'User';
$profile_image = $_SESSION['profile_image'] ?? '';
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Ultra Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';

// Check if Super Admin
$is_super_admin = isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'super admin';

// Get permissions
$permission_names = $_SESSION['permissions'] ?? [];
?>

<style>
/* ============================================================
   SIDEBAR STYLES
   ============================================================ */
#sidebar-container {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    font-family: 'Inter', sans-serif;
    z-index: 1000;
    transition: transform 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.03);
}

#sidebar-container::-webkit-scrollbar { width: 4px; }
#sidebar-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

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

.sidebar-header {
    padding: 1.25rem 1rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
    background: #fafbfc;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}

.brand-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

.brand-name {
    font-weight: 700;
    font-size: 1.15rem;
    color: #1e293b;
    letter-spacing: -0.3px;
    white-space: nowrap;
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
}
.sidebar-close:hover { background: #f1f5f9; color: #475569; }

@media (max-width: 1279px) {
    .sidebar-close { display: block; }
}

.sidebar-nav {
    flex: 1;
    padding: 0.75rem 0.75rem;
    overflow-y: auto;
}

.sidebar-section-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.75rem 0.8rem 0.4rem;
    color: #94a3b8;
    font-weight: 600;
    border-top: 1px solid #f1f5f9;
    margin-top: 0.5rem;
}

.sidebar-section-label:first-of-type {
    border-top: none;
    margin-top: 0;
}

.sidebar-link {
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
}

.sidebar-link i {
    width: 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 1.1rem;
    transition: all 0.2s ease;
}

.sidebar-link:hover {
    background: #f1f5f9;
    color: #1e293b;
}
.sidebar-link:hover i { color: #3b82f6; }

.sidebar-link.active {
    background: #eff6ff;
    color: #3b82f6;
}
.sidebar-link.active i { color: #3b82f6; }

.sidebar-link.sub-link {
    padding-left: 2.2rem;
    font-size: 0.85rem;
}
.sidebar-link.sub-link i {
    width: 1rem;
    font-size: 0.8rem;
}

.sidebar-link.logout {
    color: #ef4444;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
    margin-top: 0.5rem;
}
.sidebar-link.logout i { color: #ef4444; }
.sidebar-link.logout:hover {
    background: #fef2f2;
    color: #dc2626;
}
.sidebar-link.logout:hover i { color: #dc2626; }

.sidebar-dropdown {
    margin-bottom: 2px;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.65rem 0.8rem;
    background: none;
    border: none;
    border-radius: 10px;
    color: #475569;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dropdown-toggle span {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.dropdown-toggle span i {
    width: 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 1.1rem;
}

.dropdown-toggle .dropdown-arrow {
    color: #94a3b8;
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.dropdown-toggle:hover {
    background: #f1f5f9;
    color: #1e293b;
}
.dropdown-toggle:hover span i { color: #3b82f6; }

.dropdown-toggle.active {
    background: #eff6ff;
    color: #3b82f6;
}
.dropdown-toggle.active span i { color: #3b82f6; }

.dropdown-menu {
    padding-left: 0.5rem;
    margin-left: 0.5rem;
    border-left: 2px solid #e2e8f0;
    display: none;
}
.dropdown-menu.show { display: block; }

.sidebar-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
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

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.7rem;
    color: #94a3b8;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.super-admin-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    font-size: 0.6rem;
    padding: 2px 10px;
    border-radius: 12px;
    font-weight: 600;
    margin-left: 0.5rem;
}

.role-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.6rem;
    font-weight: 600;
    background: #dbeafe;
    color: #3b82f6;
}
</style>

<!-- ============================================================
SIDEBAR HTML
============================================================ -->
<div id="sidebar-container">
    <aside class="sidebar" id="mainSidebar">
        
        <!-- Header -->
        <div class="sidebar-header">
            <a class="sidebar-brand" href="<?php echo getDashboardUrl($user_role); ?>">
                <?php if ($hospital_logo): ?>
                    <img alt="Hospital Logo" src="<?php echo $hospital_logo; ?>" class="brand-logo" />
                <?php else: ?>
                    <span class="brand-icon">H</span>
                <?php endif; ?>
                <span class="brand-name"><?php echo htmlspecialchars($hospital_name); ?></span>
                <?php if ($is_super_admin): ?>
                <span class="super-admin-badge">👑 SA</span>
                <?php endif; ?>
            </a>
            <button class="sidebar-close" id="sidebar-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            
            <!-- ============================================================
            DASHBOARD
            ============================================================ -->
            <?php if ($is_super_admin || hasPerm('dashboard-view')): ?>
            <div class="sidebar-section-label">Dashboard</div>
            <a href="<?php echo getDashboardUrl($user_role); ?>" class="sidebar-link <?php echo $current_page == basename(getDashboardUrl($user_role)) ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php endif; ?>

            <!-- ============================================================
            PATIENTS
            ============================================================ -->
            <?php if (hasPerm('patient-view')): ?>
            <div class="sidebar-section-label">Patients</div>
            <a href="patients.php" class="sidebar-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Patients
            </a>
            <?php endif; ?>

            <!-- ============================================================
            APPOINTMENTS
            ============================================================ -->
            <?php if (hasPerm('appointment-view')): ?>
            <div class="sidebar-section-label">Appointments</div>
            <a href="appointments.php" class="sidebar-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <?php endif; ?>

            <!-- ============================================================
            OPD
            ============================================================ -->
            <?php if (hasPerm('opd-view')): ?>
            <div class="sidebar-section-label">OPD</div>
            <a href="opd.php" class="sidebar-link <?php echo $current_page == 'opd.php' ? 'active' : ''; ?>">
                <i class="fas fa-stethoscope"></i> OPD
            </a>
            <?php endif; ?>

            <!-- ============================================================
            IPD
            ============================================================ -->
            <?php if (hasPerm('ipd-view')): ?>
            <div class="sidebar-section-label">IPD</div>
            <a href="ipd_admissions.php" class="sidebar-link <?php echo $current_page == 'ipd_admissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital-user"></i> IPD
            </a>
            <?php endif; ?>

            <!-- ============================================================
            PRESCRIPTIONS
            ============================================================ -->
            <?php if (hasPerm('prescription-view')): ?>
            <div class="sidebar-section-label">Prescriptions</div>
            <a href="prescriptions.php" class="sidebar-link <?php echo $current_page == 'prescriptions.php' ? 'active' : ''; ?>">
                <i class="fas fa-prescription"></i> Prescriptions
            </a>
            <?php endif; ?>

            <!-- ============================================================
            LABORATORY
            ============================================================ -->
            <?php if (hasAnyPerm(['lab-orders-view', 'lab-reports-view'])): ?>
            <div class="sidebar-section-label">Laboratory</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['lab_order.php', 'lab_report.php']) ? 'active' : ''; ?>" onclick="toggleMenu('labMenu')">
                    <span><i class="fas fa-flask"></i> Laboratory</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="labMenu" class="dropdown-menu <?php echo in_array($current_page, ['lab_order.php', 'lab_report.php']) ? 'show' : ''; ?>">
                    <?php if (hasPerm('lab-orders-view')): ?>
                    <a href="lab_order.php" class="sidebar-link sub-link <?php echo $current_page == 'lab_order.php' ? 'active' : ''; ?>"><i class="fas fa-vial"></i> Lab Orders</a>
                    <?php endif; ?>
                    <?php if (hasPerm('lab-reports-view')): ?>
                    <a href="lab_report.php" class="sidebar-link sub-link <?php echo $current_page == 'lab_report.php' ? 'active' : ''; ?>"><i class="fas fa-file-medical"></i> Lab Reports</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            PHARMACY
            ============================================================ -->
            <?php if (hasAnyPerm(['pharmacy-view', 'pharmacy-stock', 'pharmacy-sales'])): ?>
            <div class="sidebar-section-label">Pharmacy</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['pharmacy_stock.php', 'pharmacy_sales.php']) ? 'active' : ''; ?>" onclick="toggleMenu('pharmacyMenu')">
                    <span><i class="fas fa-prescription-bottle"></i> Pharmacy</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="pharmacyMenu" class="dropdown-menu <?php echo in_array($current_page, ['pharmacy_stock.php', 'pharmacy_sales.php']) ? 'show' : ''; ?>">
                    <?php if (hasPerm('pharmacy-stock')): ?>
                    <a href="pharmacy_stock.php" class="sidebar-link sub-link <?php echo $current_page == 'pharmacy_stock.php' ? 'active' : ''; ?>"><i class="fas fa-boxes"></i> Stock</a>
                    <?php endif; ?>
                    <?php if (hasPerm('pharmacy-sales')): ?>
                    <a href="pharmacy_sales.php" class="sidebar-link sub-link <?php echo $current_page == 'pharmacy_sales.php' ? 'active' : ''; ?>"><i class="fas fa-cash-register"></i> Sales</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            BILLING
            ============================================================ -->
            <?php if (hasAnyPerm(['billing-view', 'payment-view'])): ?>
            <div class="sidebar-section-label">Billing</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['billing.php', 'payments.php']) ? 'active' : ''; ?>" onclick="toggleMenu('billingMenu')">
                    <span><i class="fas fa-file-invoice-dollar"></i> Billing</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="billingMenu" class="dropdown-menu <?php echo in_array($current_page, ['billing.php', 'payments.php']) ? 'show' : ''; ?>">
                    <?php if (hasPerm('billing-view')): ?>
                    <a href="billing.php" class="sidebar-link sub-link <?php echo $current_page == 'billing.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice"></i> Billing</a>
                    <?php endif; ?>
                    <?php if (hasPerm('payment-view')): ?>
                    <a href="payments.php" class="sidebar-link sub-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payments</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            REPORTS
            ============================================================ -->
            <?php if (hasPerm('reports-view')): ?>
            <div class="sidebar-section-label">Reports</div>
            <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php endif; ?>

            <!-- ============================================================
            AMBULANCE
            ============================================================ -->
            <?php if (hasPerm('ambulance-view')): ?>
            <div class="sidebar-section-label">Ambulance</div>
            <a href="ambulance.php" class="sidebar-link <?php echo $current_page == 'ambulance.php' ? 'active' : ''; ?>">
                <i class="fas fa-ambulance"></i> Ambulance
            </a>
            <?php endif; ?>

            <!-- ============================================================
            ACCOUNTS
            ============================================================ -->
            <?php if (hasAnyPerm(['accounts-view', 'accounts-income', 'accounts-expense', 'accounts-salary', 'accounts-ledger'])): ?>
            <div class="sidebar-section-label">Accounts</div>
            <a href="accounts.php" class="sidebar-link <?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>">
                <i class="fas fa-coins"></i> Accounts
            </a>
            <?php endif; ?>

            <!-- ============================================================
            MASTERS (Admin/Super Admin Only)
            ============================================================ -->
            <?php if (($is_super_admin || $user_role == 'admin') && hasAnyPerm(['master-department', 'master-doctor', 'master-staff', 'master-ward', 'master-medicine', 'master-lab-test'])): ?>
            <div class="sidebar-section-label">Masters</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['departments.php', 'doctors.php', 'staff.php', 'wards.php', 'medicine.php', 'lab_tests.php']) ? 'active' : ''; ?>" onclick="toggleMenu('mastersMenu')">
                    <span><i class="fas fa-database"></i> Masters</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="mastersMenu" class="dropdown-menu <?php echo in_array($current_page, ['departments.php', 'doctors.php', 'staff.php', 'wards.php', 'medicine.php', 'lab_tests.php']) ? 'show' : ''; ?>">
                    <?php if (hasPerm('master-department') || $is_super_admin): ?>
                    <a href="departments.php" class="sidebar-link sub-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>"><i class="fas fa-building"></i> Departments</a>
                    <?php endif; ?>
                    <?php if (hasPerm('master-doctor') || $is_super_admin): ?>
                    <a href="doctors.php" class="sidebar-link sub-link <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>"><i class="fas fa-user-md"></i> Doctors</a>
                    <?php endif; ?>
                    <?php if (hasPerm('master-staff') || $is_super_admin): ?>
                    <a href="staff.php" class="sidebar-link sub-link <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Staff</a>
                    <?php endif; ?>
                    <?php if (hasPerm('master-ward') || $is_super_admin): ?>
                    <a href="wards.php" class="sidebar-link sub-link <?php echo $current_page == 'wards.php' ? 'active' : ''; ?>"><i class="fas fa-bed"></i> Wards</a>
                    <?php endif; ?>
                    <?php if (hasPerm('master-medicine') || $is_super_admin): ?>
                    <a href="medicine.php" class="sidebar-link sub-link <?php echo $current_page == 'medicine.php' ? 'active' : ''; ?>"><i class="fas fa-pills"></i> Medicines</a>
                    <?php endif; ?>
                    <?php if (hasPerm('master-lab-test') || $is_super_admin): ?>
                    <a href="lab_tests.php" class="sidebar-link sub-link <?php echo $current_page == 'lab_tests.php' ? 'active' : ''; ?>"><i class="fas fa-flask"></i> Lab Tests</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SYSTEM (Admin/Super Admin Only)
            ============================================================ -->
            <?php if (($is_super_admin || $user_role == 'admin') && hasAnyPerm(['system-settings', 'system-audit', 'system-users', 'system-roles', 'system-permissions'])): ?>
            <div class="sidebar-section-label">System</div>
            
            <?php if (hasPerm('system-audit') || $is_super_admin): ?>
            <a href="audit_logs.php" class="sidebar-link <?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Audit Logs
            </a>
            <?php endif; ?>
            
            <?php if (hasPerm('system-settings') || $is_super_admin): ?>
            <a href="settings.php" class="sidebar-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
            <?php endif; ?>
            
            <?php if (hasPerm('system-roles') || $is_super_admin): ?>
            <a href="roles.php" class="sidebar-link <?php echo $current_page == 'roles.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tag"></i> Roles
            </a>
            <?php endif; ?>
            
            <?php if (hasPerm('system-permissions') || $is_super_admin): ?>
            <a href="permissions.php" class="sidebar-link <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i> Permissions
            </a>
            <?php endif; ?>
            
            <?php if (hasPerm('system-users') || $is_super_admin): ?>
            <a href="register.php" class="sidebar-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> Users
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
            PROFILE & LOGOUT - Always visible
            ============================================================ -->
            <div class="sidebar-section-label">Account</div>
            
            <a href="update_adminprofile.php" class="sidebar-link <?php echo $current_page == 'update_adminprofile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> My Profile
            </a>

            <a href="auth/Logout.php" class="sidebar-link logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>

        </nav>

        <!-- Footer -->
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
                        <?php echo htmlspecialchars(strtoupper($user_role)); ?>
                        <?php if ($is_super_admin): ?>
                        <span class="super-admin-badge" style="font-size:0.6rem;background:#fef3c7;color:#b45309;padding:2px 8px;border-radius:12px;display:inline-block;margin-left:4px;">👑 SA</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

    </aside>
</div>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
function toggleMenu(menuId) {
    var menu = document.getElementById(menuId);
    if (menu) {
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            menu.classList.add('show');
            var arrow = menu.parentElement.querySelector('.dropdown-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        } else {
            menu.style.display = 'none';
            menu.classList.remove('show');
            var arrow = menu.parentElement.querySelector('.dropdown-arrow');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var currentPage = '<?php echo $current_page; ?>';
    var menuMap = {
        'departments.php': 'mastersMenu',
        'doctors.php': 'mastersMenu',
        'staff.php': 'mastersMenu',
        'wards.php': 'mastersMenu',
        'medicine.php': 'mastersMenu',
        'lab_tests.php': 'mastersMenu',
        'lab_order.php': 'labMenu',
        'lab_report.php': 'labMenu',
        'pharmacy_stock.php': 'pharmacyMenu',
        'pharmacy_sales.php': 'pharmacyMenu',
        'billing.php': 'billingMenu',
        'payments.php': 'billingMenu'
    };
    
    if (menuMap[currentPage]) {
        var menu = document.getElementById(menuMap[currentPage]);
        if (menu) {
            menu.style.display = 'block';
            menu.classList.add('show');
            var arrow = menu.parentElement.querySelector('.dropdown-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        }
    }
    
    var closeBtn = document.getElementById('sidebar-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            document.getElementById('sidebar-container').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
    }
    
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            document.getElementById('sidebar-container').classList.remove('active');
            overlay.classList.remove('active');
        });
    }
});

function toggleSidebar() {
    document.getElementById('sidebar-container').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
</script>