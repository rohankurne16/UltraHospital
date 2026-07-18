<?php
// ============================================================
// DOCTOR SIDEBAR - PERMISSION BASED
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config
include '../config/hospital.php';
include '../config/superadmin.php';
include '../config/constants.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    return;
}

// Check if user is doctor
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role != 'doctor') {
    // If not doctor, redirect to main dashboard
    header('Location: ../dashboard.php');
    exit();
}

// Get user info
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['name'] ?? 'User';
$user_role_display = strtoupper($_SESSION['role'] ?? 'Doctor');
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$profile_image = $_SESSION['profile_image'] ?? '';
?>

<style>
/* ============================================================
   DOCTOR SIDEBAR STYLES
   ============================================================ */
#sidebar-container {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 256px;
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

/* Mobile */
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

/* Sidebar Internal */
.sidebar {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    font-family: 'Inter', sans-serif;
}

/* Header / Brand */
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

.sidebar-brand .brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}

.sidebar-brand .brand-icon {
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

.sidebar-brand .brand-name {
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
    transition: all 0.2s ease;
}
.sidebar-close:hover { background: #f1f5f9; color: #475569; }

@media (max-width: 1279px) {
    .sidebar-close { display: block; }
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    padding: 0.75rem 0.75rem;
    overflow-y: auto;
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

/* Dropdown */
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

/* Footer */
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
    font-size: 0.7rem;
    color: #94a3b8;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<!-- ============================================================
DOCTOR SIDEBAR HTML
============================================================ -->
<div id="sidebar-container">
    <aside class="sidebar" id="mainSidebar">
        
        <!-- Header -->
        <div class="sidebar-header">
            <a class="sidebar-brand" href="dashboard.php">
                <?php if ($hospital_logo): ?>
                    <img alt="Hospital Logo" src="<?php echo $hospital_logo; ?>" class="brand-logo" />
                <?php else: ?>
                    <span class="brand-icon">H</span>
                <?php endif; ?>
                <span class="brand-name"><?php echo $hospital_name; ?></span>
            </a>
            <button class="sidebar-close" id="sidebar-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            
            <!-- ============================================================
            DASHBOARD - requires dashboard-view
            ============================================================ -->
            <?php if (hasPermission('dashboard-view')): ?>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php endif; ?>

            <!-- ============================================================
            APPOINTMENTS - requires appointment-view
            ============================================================ -->
            <?php if (hasPermission('appointment-view')): ?>
            <a href="show_myappointment.php" class="sidebar-link <?php echo $current_page == 'show_myappointment.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <?php endif; ?>

            <!-- ============================================================
            PATIENTS - requires patient-view
            ============================================================ -->
            <?php if (hasPermission('patient-view')): ?>
            <a href="view_patient.php" class="sidebar-link <?php echo $current_page == 'view_patient.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Patients
            </a>
            <?php endif; ?>

            <!-- ============================================================
            OPD - requires opd-view
            ============================================================ -->
            <?php if (hasPermission('opd-view')): ?>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['opd_main.php', 'opd_visits.php']) ? 'active' : ''; ?>" onclick="toggleMenu('opdMenu')">
                    <span><i class="fas fa-stethoscope"></i> OPD</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="opdMenu" class="dropdown-menu <?php echo in_array($current_page, ['opd_main.php', 'opd_visits.php']) ? 'show' : ''; ?>">
                    <a href="opd_main.php" class="sidebar-link sub-link <?php echo $current_page == 'opd_main.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> OPD List
                    </a>
                    <a href="add_opd.php" class="sidebar-link sub-link <?php echo $current_page == 'add_opd.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> Add OPD
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            PRESCRIPTIONS - requires prescription-view
            ============================================================ -->
            <?php if (hasPermission('prescription-view')): ?>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['prescription_list.php', 'create_prescription.php']) ? 'active' : ''; ?>" onclick="toggleMenu('prescriptionMenu')">
                    <span><i class="fas fa-prescription"></i> Prescriptions</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="prescriptionMenu" class="dropdown-menu <?php echo in_array($current_page, ['prescription_list.php', 'create_prescription.php']) ? 'show' : ''; ?>">
                    <a href="prescription_list.php" class="sidebar-link sub-link <?php echo $current_page == 'prescription_list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All Prescriptions
                    </a>
                    <a href="create_prescription.php" class="sidebar-link sub-link <?php echo $current_page == 'create_prescription.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> New Prescription
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            LAB MASTER - requires lab-test-view
            ============================================================ -->
            <?php if (hasPermission('lab-test-view')): ?>
            <a href="doctor_lab_master.php" class="sidebar-link <?php echo $current_page == 'doctor_lab_master.php' ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i> Lab Master
            </a>
            <?php endif; ?>

            <!-- ============================================================
            REPORTS - requires reports-view
            ============================================================ -->
            <?php if (hasPermission('reports-view')): ?>
            <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php endif; ?>

            <!-- ============================================================
            SPECIALIZATIONS
            ============================================================ -->
            <?php if (hasPermission('doctor-view')): ?>
            <a href="specializations.php" class="sidebar-link <?php echo $current_page == 'specializations.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Specializations
            </a>
            <?php endif; ?>

            <!-- ============================================================
            MY PROFILE
            ============================================================ -->
            <a href="doctor_profile.php" class="sidebar-link <?php echo $current_page == 'doctor_profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> My Profile
            </a>

            <!-- ============================================================
            LOGOUT
            ============================================================ -->
            <a href="../auth/Logout.php" class="sidebar-link logout">
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
                    <p class="user-name"><?php echo $user_name; ?></p>
                    <p class="user-role"><?php echo $user_role_display; ?></p>
                </div>
            </div>
        </div>

    </aside>
</div>

<!-- ============================================================
JAVASCRIPT
============================================================ -->
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

// Auto open dropdown based on current page
document.addEventListener('DOMContentLoaded', function() {
    var currentPage = '<?php echo $current_page; ?>';
    var menuMap = {
        'opd_main.php': 'opdMenu',
        'add_opd.php': 'opdMenu',
        'opd_visits.php': 'opdMenu',
        'prescription_list.php': 'prescriptionMenu',
        'create_prescription.php': 'prescriptionMenu',
        'edit_prescription.php': 'prescriptionMenu',
        'view_prescription.php': 'prescriptionMenu'
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
    
    // Close sidebar on mobile
    var closeBtn = document.getElementById('sidebar-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            var container = document.getElementById('sidebar-container');
            if (container) {
                container.classList.remove('active');
            }
            var overlay = document.getElementById('sidebarOverlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        });
    }
});
</script>