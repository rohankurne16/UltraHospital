<?php

// ============================================================
// DYNAMIC SIDEBAR (sidebar.php) - FINAL CSS FIX
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
$is_super_admin = isset($_SESSION['role']) && (strtolower(trim($_SESSION['role'])) === 'super admin' || strtolower(trim($_SESSION['role'])) === 'superadmin');

/**
 * Helper to check if user has a specific permission
 */
if (!function_exists('hasPerm')) {
    function hasPerm($permission) {
        if (isset($_SESSION['role']) && (strtolower(trim($_SESSION['role'])) === 'super admin' || strtolower(trim($_SESSION['role'])) === 'superadmin')) {
            return true;
        }
        $user_perms = $_SESSION['permissions'] ?? [];
        return !empty($user_perms) && in_array($permission, $user_perms);
    }
}

if (!function_exists('hasAnyPerm')) {
    function hasAnyPerm($permissions) {
        foreach ($permissions as $p) {
            if (hasPerm($p)) return true;
        }
        return false;
    }
}

if (!function_exists('getDashboardUrl')) {
    function getDashboardUrl($role) {
        $role = strtolower(trim($role));
        if ($role == 'super admin' || $role == 'superadmin') return 'superadmin/dashboard.php';
        return 'dashboard.php';
    }
}

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
}

#sidebar-container::-webkit-scrollbar { width: 4px; }
#sidebar-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* Overlay for mobile */
#sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
}

#sidebar-overlay.active {
    display: block;
}

@media (max-width: 1279px) {
    #sidebar-container { transform: translateX(-100%); width: 280px; }
    #sidebar-container.active { transform: translateX(0); }
}

.sidebar-header {
    padding: 1.25rem 1rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-shrink: 0;
    background: #fafbfc;
}

.sidebar-brand { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
.brand-logo { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; }
.brand-icon { width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; }
.brand-name { font-weight: 700; font-size: 1.15rem; color: #1e293b; white-space: nowrap; }

/* Mobile Close Button */
.mobile-close-btn {
    display: none;
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 5px;
}

@media (max-width: 1279px) {
    .mobile-close-btn { display: block; }
}

.sidebar-nav { 
    flex: 1; 
    padding: 0.75rem 0.75rem; 
    overflow-y: auto; 
    overflow-x: hidden;
}
.sidebar-section-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.75rem 0.8rem 0.4rem; color: #94a3b8; font-weight: 600; margin-top: 0.5rem; }

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
    font-size: 1rem;
    flex-shrink: 0;
}
.sidebar-link:hover { background: #f1f5f9; color: #1e293b; }
.sidebar-link.active { background: #eff6ff; color: #3b82f6; }
.sidebar-link.active i { color: #3b82f6; }

.sidebar-dropdown { margin-bottom: 2px; }
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
.dropdown-toggle:hover {
    background: #f1f5f9;
}
.dropdown-toggle span {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.dropdown-toggle i {
    width: 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 1rem;
    flex-shrink: 0;
}
.dropdown-arrow {
    transition: transform 0.3s ease;
    font-size: 0.75rem;
    color: #94a3b8;
}
.dropdown-menu { 
    padding-left: 0.5rem; 
    margin-left: 0.5rem; 
    border-left: 2px solid #e2e8f0; 
    display: none;
    overflow: hidden;
}
.dropdown-menu.show { 
    display: block; 
}
.dropdown-menu .sub-link {
    padding-left: 1.8rem;
}
.dropdown-menu .sub-link i {
    width: 1rem;
    font-size: 0.85rem;
}

/* FOOTER & AVATAR FIX */
.sidebar-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    overflow: hidden;
}

.user-avatar {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    overflow: hidden;
    position: relative;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.user-info {
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
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
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.logout-section { border-top: 1px solid #e2e8f0; margin-top: 1rem; padding-top: 0.5rem; }
.sidebar-link.logout { color: #ef4444; }
.sidebar-link.logout:hover { background: #fef2f2; color: #dc2626; }
.sidebar-link.logout i { color: #ef4444; }
.sidebar-link.logout:hover i { color: #dc2626; }
</style>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<div id="sidebar-container">
    <aside class="sidebar" id="mainSidebar">
        
        <!-- Header -->
        <div class="sidebar-header">
            <a class="sidebar-brand" href="<?php echo getDashboardUrl($user_role); ?>">
                <?php if ($hospital_logo): ?>
                  <?php if($role=='admin'){ ?>
                     <img alt="Hospital Logo" src="<?php echo $hospital_logo; ?>" class="brand-logo" />
                <?php   }
                   else{ ?>
                     <img alt="Hospital Logo" src="../<?php echo $hospital_logo; ?>" class="brand-logo" />
                 <?php  } ?>
                <?php else: ?>
                    <span class="brand-icon">H</span>
                <?php endif; ?>
                <span class="brand-name"><?php echo htmlspecialchars($hospital_name); ?></span>
            </a>
            
            <!-- Mobile Close Button -->
            <button class="mobile-close-btn" onclick="toggleSidebar()">
                <i class="fas fa-times" style="font-size: 1.25rem;"></i>
            </button>
        </div>

         
        <!-- Navigation -->
        <nav class="sidebar-nav">
            
            <?php if (hasPerm('Dashboard View')): ?>
            <div class="sidebar-section-label">Main</div>
            <a href="dashboard.php" class="sidebar-link ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php endif; ?>

            <?php if (hasAnyPerm(['Patient View', 'Patient Add', 'OPD Visit View', 'IPD Admission View', 'Referral View', 'Call View', 'Call Create'])): ?>
            <div class="sidebar-section-label">Patient</div>

            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('patientMenu')">
                    <span><i class="fas fa-user-injured"></i> Patients</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div id="patientMenu" class="dropdown-menu">
                    <?php if (hasPerm('Patient Add')): ?>
                    <a href="patient_registration.php" class="sidebar-link sub-link">
                        <i class="fas fa-user-plus"></i> Patient Registration
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('Patient View')): ?>
                    <a href="patients.php" class="sidebar-link sub-link">
                        <i class="fas fa-users"></i> All Patients
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('OPD Visit View')): ?>
                    <a href="add_patient.php" class="sidebar-link sub-link">
                        <i class="fas fa-stethoscope"></i> OPD
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('IPD Admission View')): ?>
                    <a href="add_ipd_patient.php" class="sidebar-link sub-link">
                        <i class="fas fa-hospital-user"></i> IPD
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('Referral View')): ?>
                    <a href="referrals.php" class="sidebar-link sub-link">
                        <i class="fas fa-share-alt"></i> Referrals
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('Call Patient View')): ?>
                    <a href="add_call_patient.php" class="sidebar-link sub-link">
                        <i class="fas fa-phone"></i> Call
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPerm('Appointment View')): ?>
            <a href="appointments.php" class="sidebar-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <?php endif; ?>

            <?php if (hasPerm('OPD Visit View')): ?>
            <a href="show_opd_appointments.php" class="sidebar-link <?php echo $current_page == 'show_opd_appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-stethoscope"></i> OPD
            </a>
            <?php endif; ?>

            <?php if (hasPerm('IPD Admission View')): ?>
            <a href="show_ipd_appointments.php" class="sidebar-link <?php echo $current_page == 'show_ipd_appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital-user"></i> IPD
            </a>
            <?php endif; ?>

            <?php if (hasPerm('Prescription View')): ?>
            <a href="prescriptions.php" class="sidebar-link <?php echo $current_page == 'prescriptions.php' ? 'active' : ''; ?>">
                <i class="fas fa-prescription"></i> Prescriptions
            </a>
            <?php endif; ?>

            <?php if (hasAnyPerm(['Surgery View', 'Surgery Create'])): ?>
            <div class="sidebar-section-label">Operation Theatre</div>

            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('surgeryMenu')">
                    <span><i class="fas fa-procedures"></i> Surgery</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div id="surgeryMenu" class="dropdown-menu">
                    <?php if (hasPerm('Surgery View')): ?>
                    <a href="surgeries.php" class="sidebar-link sub-link">
                        <i class="fas fa-list"></i> Surgery List
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('Surgery Create')): ?>
                    <a href="add_surgery.php" class="sidebar-link sub-link">
                        <i class="fas fa-plus-circle"></i> Schedule Surgery
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasAnyPerm(['Lab Orders View', 'Lab Reports View'])): ?>
            <div class="sidebar-section-label">Services</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('labMenu')">
                    <span><i class="fas fa-flask"></i> Laboratory</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="labMenu" class="dropdown-menu">
                    <?php if (hasPerm('Lab Master View')): ?>
                    <a href="lab_test_master.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-alt"></i> Lab Master
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('Lab Orders View')): ?>
                    <a href="lab_order.php" class="sidebar-link sub-link">
                        <i class="fas fa-vial"></i> Lab Orders
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('Lab Reports View')): ?>
                    <a href="lab_report.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-medical"></i> Lab Reports
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasAnyPerm(['Medicine View', 'Stock View', 'Medicine Sales View'])): ?>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('pharmacyMenu')">
                    <span><i class="fas fa-prescription-bottle"></i> Pharmacy</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="pharmacyMenu" class="dropdown-menu">
                    <?php if (hasPerm('Stock View')): ?>
                    <a href="pharmacy_stock.php" class="sidebar-link sub-link">
                        <i class="fas fa-boxes"></i> Stock
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('Medicine Sales View')): ?>
                    <a href="pharmacy_sales.php" class="sidebar-link sub-link">
                        <i class="fas fa-cash-register"></i> Sales
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasAnyPerm(['Doctor View', 'Staff View', 'Department View', 'Hospital View'])): ?>
            <div class="sidebar-section-label">Management</div>
            
            <?php if (hasPerm('Doctor View')): ?>
            <a href="doctors.php" class="sidebar-link <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Doctors
            </a>
            <?php endif; ?>

            <?php if (hasPerm('Staff View')): ?>
            <a href="staff.php" class="sidebar-link <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> Staff
            </a>
            <?php endif; ?>

            <?php if (hasPerm('Department View')): ?>
            <a href="departments.php" class="sidebar-link <?php echo $current_page == 'department.php' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Departments
            </a>
            <?php endif; ?>

            <?php if (hasAnyPerm(['Ward View', 'Ward Create', 'Ward Edit', 'Ward Delete'])): ?>
            <a href="ward_master.php" class="sidebar-link <?php echo $current_page == 'ward_master.php' ? 'active' : ''; ?>">
                <i class="fas fa-procedures"></i> Ward Master
            </a>
            <?php endif; ?>

            <?php if (hasPerm('Hospital View')): ?>
            <a href="general_settings.php" class="sidebar-link <?php echo $current_page == 'hospitals.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i> Hospitals
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <?php
            $role = strtolower($_SESSION['role']);

            switch ($role) {
                case 'super admin':
                    $profilePage = "superadmin/dashboard.php";
                    break;

                case 'admin':
                    $profilePage = "dashboard.php"; 
                    break;

                case 'doctor':
                    $profilePage = "doctors/dashboard.php";
                    break;

                case 'nurse':
                    $profilePage = "staff/dashboard.php";
                    break;

                case 'ward boy':
                    $profilePage = "staff/dashboard.php";
                    break;

                case 'lab technician':
                    $profilePage = "labtechnician/update_profile.php";
                    break;

                case 'patient':
                    $profilePage = "patients/profile.php";
                    break;

                case 'billing staff':
                    $profilePage = "staff/billing_profile.php";
                    break;

                case 'accountant':
                    $profilePage = "staff/accountant_profile.php";
                    break;

                case 'pharmacist':
                    $profilePage = "staff/pharmacist_profile.php";
                    break;

                case 'staff':
                    $profilePage = "staff/profile.php";
                    break;

                case 'receptionist':
                    $profilePage = "staff/reception_profile.php";
                    break;

                default:
                    $profilePage = "dashboard.php";
            }
            ?>

            <div class="sidebar-section-label">Account</div>

            <a href="update_adminprofile.php"
               class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'update_adminprofile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                Update Profile
            </a>

            <div class="logout-section">
                <?php if($role=='admin'){ ?>
                    <a href="auth/logout.php" class="sidebar-link logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
             <?php   } else{ ?>
                      <a href="../auth/logout.php" class="sidebar-link logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
           <?php  }?>
            </div>

        </nav>

        <!-- Footer -->
        <div class="sidebar-footer">
            <div class="user-avatar">
                <?php if ($profile_image): ?>
                    <img src="<?php echo $profile_image; ?>" alt="User">
                <?php else: ?>
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="user-role"><?php echo htmlspecialchars($user_role); ?></p>
            </div>
        </div>

    </aside>
</div>

<script>
function toggleMenu(menuId) {
    event.preventDefault();
    event.stopPropagation();
    
    const menu = document.getElementById(menuId);
    if (!menu) return;
    
    const btn = menu.previousElementSibling;
    const arrow = btn ? btn.querySelector('.dropdown-arrow') : null;
    
    // Toggle the menu
    menu.classList.toggle('show');
    
    // Rotate arrow
    if (arrow) {
        arrow.style.transform = menu.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

/**
 * Universal Mobile Sidebar Toggle
 */
function toggleSidebar() {
    event.preventDefault();
    event.stopPropagation();
    
    const sidebar = document.getElementById('sidebar-container');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar) {
        sidebar.classList.toggle('active');
    }
    if (overlay) {
        overlay.classList.toggle('active');
    }
}

// Prevent sidebar from scrolling to top when clicking links
document.addEventListener('DOMContentLoaded', function() {
    // Get all sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar-link, .dropdown-toggle');
    
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Allow dropdown toggles to work
            if (this.classList.contains('dropdown-toggle')) {
                return;
            }
            // For regular links, prevent default behavior that might cause scroll
            // The link will still navigate, but we prevent any extra scroll effects
            e.stopPropagation();
        });
    });
    
    // Preserve scroll position on page load
    const sidebarContainer = document.getElementById('sidebar-container');
    if (sidebarContainer) {
        // Store scroll position before unload
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('sidebarScrollPos', sidebarContainer.scrollTop);
        });
        
        // Restore scroll position after load
        const savedScrollPos = sessionStorage.getItem('sidebarScrollPos');
        if (savedScrollPos !== null) {
            setTimeout(function() {
                sidebarContainer.scrollTop = parseInt(savedScrollPos);
            }, 100);
        }
    }
});

// Prevent any anchor from causing scroll to top within sidebar
document.addEventListener('click', function(e) {
    const target = e.target.closest('a');
    if (target && target.closest('#sidebar-container')) {
        // Allow normal navigation but prevent any scroll reset
        const currentScroll = document.getElementById('sidebar-container')?.scrollTop || 0;
        setTimeout(function() {
            const container = document.getElementById('sidebar-container');
            if (container) {
                container.scrollTop = currentScroll;
            }
        }, 50);
    }
});
</script>