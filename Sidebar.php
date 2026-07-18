<?php
// ============================================================
// SIDEBAR - COMPLETE HOSPITAL MANAGEMENT
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config
include_once 'config/hospital.php';
include_once 'config/superadmin.php';
include_once 'config/constants.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    return;
}

// Get user info
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'Guest';
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
$profile_image = $_SESSION['profile_image'] ?? '';
$user_role_display = strtoupper($user_role);

// Check if Super Admin
$is_super_admin = isset($_SESSION['role']) && $_SESSION['role'] === SUPER_ADMIN_ROLE;

// If Super Admin, show different sidebar
if ($is_super_admin) {
    include 'sidebar.php';
    return;
}

// ============================================================
// PERMISSION ARRAYS - COMPLETE MODULES
// ============================================================

$dashboard_permissions = ['dashboard-view', 'dashboard-analytics', 'dashboard-reports'];

$master_permissions = [
    'department-view', 'department-create', 'department-edit', 'department-delete',
    'doctor-view', 'doctor-create', 'doctor-edit', 'doctor-delete',
    'staff-view', 'staff-create', 'staff-edit', 'staff-delete',
    'ward-view', 'ward-create', 'ward-edit', 'ward-delete',
    'medicine-view', 'medicine-create', 'medicine-edit', 'medicine-delete',
    'lab-test-view', 'lab-test-create', 'lab-test-edit', 'lab-test-delete'
];

$patient_permissions = [
    'patient-view', 'patient-registration', 'patient-edit', 'patient-delete', 'patient-history'
];

$opd_permissions = [
    'appointment-view', 'appointment-create', 'appointment-edit', 'appointment-delete',
    'opd-visit-view', 'opd-visit-create', 'opd-visit-edit', 'opd-visit-delete',
    'prescription-view', 'prescription-create', 'prescription-edit', 'prescription-delete'
];

$ipd_permissions = [
    'ipd-admission-view', 'ipd-admission-create', 'ipd-admission-edit', 'ipd-admission-delete',
    'ipd-treatment-view', 'ipd-treatment-create', 'ipd-treatment-edit', 'ipd-treatment-delete',
    'discharge-summary-view', 'discharge-summary-create', 'discharge-summary-edit', 'discharge-summary-delete'
];

$lab_permissions = [
    'lab-orders-view', 'lab-orders-create', 'lab-orders-edit', 'lab-orders-delete',
    'lab-reports-view', 'lab-reports-create', 'lab-reports-edit', 'lab-reports-delete'
];

$pharmacy_permissions = [
    'medicine-sales-view', 'medicine-sales-create', 'medicine-sales-edit', 'medicine-sales-delete',
    'stock-view', 'stock-create', 'stock-edit', 'stock-delete'
];

$billing_permissions = [
    'opd-billing-view', 'opd-billing-create', 'opd-billing-edit', 'opd-billing-delete',
    'ipd-billing-view', 'ipd-billing-create', 'ipd-billing-edit', 'ipd-billing-delete',
    'payments-view', 'payments-create', 'payments-edit', 'payments-delete'
];

$reports_permissions = [
    'reports-view', 'patient-reports', 'doctor-reports', 'billing-reports',
    'opd-reports', 'ipd-reports', 'laboratory-reports'
];

$admin_permissions = [
    'user-management', 'user-view', 'user-create', 'user-edit', 'user-delete',
    'role-management', 'role-view', 'role-create', 'role-edit', 'role-delete',
    'permission-management', 'permission-view', 'permission-create', 'permission-edit', 'permission-delete'
];

$system_permissions = [
    'audit-logs', 'audit-view', 'audit-export',
    'login-logs', 'system-settings', 'hospital-settings'
];

$accounts_permissions = [
    'income-view', 'expense-view', 'salary-view', 'ledger-view', 'financial-reports'
];

$ambulance_permissions = [
    'ambulance-view', 'ambulance-booking', 'ambulance-dispatch'
];
?>

<style>
/* ============================================================
   SIDEBAR STYLES - COMPLETE
   ============================================================ */
#sidebar-container {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
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

/* Section Label */
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
</style>

<!-- ============================================================
SIDEBAR HTML - COMPLETE
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
            SECTION: DASHBOARD
            ============================================================ -->
            <?php if (hasAnyPermission($dashboard_permissions)): ?>
            <div class="sidebar-section-label">Dashboard</div>
            
            <?php if (hasPermission('dashboard-view')): ?>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: PATIENTS
            ============================================================ -->
            <?php if (hasAnyPermission($patient_permissions)): ?>
            <div class="sidebar-section-label">Patients</div>
            
            <?php if (hasPermission('patient-view')): ?>
            <a href="patients.php" class="sidebar-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Patients
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: MASTERS
            ============================================================ -->
            <?php if (hasAnyPermission($master_permissions)): ?>
            <div class="sidebar-section-label">Masters</div>
            
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['departments.php', 'doctors.php', 'staff.php', 'ward_master.php', 'medicine.php', 'lab_tests.php']) ? 'active' : ''; ?>" onclick="toggleMenu('mastersMenu')">
                    <span><i class="fas fa-database"></i> Masters</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="mastersMenu" class="dropdown-menu <?php echo in_array($current_page, ['departments.php', 'doctors.php', 'staff.php', 'ward_master.php', 'medicine.php', 'lab_tests.php']) ? 'show' : ''; ?>">
                    <?php if (hasPermission('department-view')): ?>
                    <a href="departments.php" class="sidebar-link sub-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i> Departments
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('doctor-view')): ?>
                    <a href="doctors.php" class="sidebar-link sub-link <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('staff-view')): ?>
                    <a href="staff.php" class="sidebar-link sub-link <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Staff
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('ward-view')): ?>
                    <a href="ward_master.php" class="sidebar-link sub-link <?php echo $current_page == 'ward_master.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bed"></i> Wards
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('medicine-view')): ?>
                    <a href="medicine.php" class="sidebar-link sub-link <?php echo $current_page == 'medicine.php' ? 'active' : ''; ?>">
                        <i class="fas fa-pills"></i> Medicines
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('lab-test-view')): ?>
                    <a href="lab_tests.php" class="sidebar-link sub-link <?php echo $current_page == 'lab_tests.php' ? 'active' : ''; ?>">
                        <i class="fas fa-flask"></i> Lab Tests
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: OPD
            ============================================================ -->
            <?php if (hasAnyPermission($opd_permissions)): ?>
            <div class="sidebar-section-label">OPD</div>
            
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['appointments.php', 'opd_visits.php', 'prescriptions.php']) ? 'active' : ''; ?>" onclick="toggleMenu('opdMenu')">
                    <span><i class="fas fa-stethoscope"></i> OPD</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="opdMenu" class="dropdown-menu <?php echo in_array($current_page, ['appointments.php', 'opd_visits.php', 'prescriptions.php']) ? 'show' : ''; ?>">
                    <?php if (hasPermission('appointment-view')): ?>
                    <a href="appointments.php" class="sidebar-link sub-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('opd-visit-view')): ?>
                    <a href="opd_visits.php" class="sidebar-link sub-link <?php echo $current_page == 'opd_visits.php' ? 'active' : ''; ?>">
                        <i class="fas fa-notes-medical"></i> OPD Visit
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('prescription-view')): ?>
                    <a href="prescriptions.php" class="sidebar-link sub-link <?php echo $current_page == 'prescriptions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-prescription"></i> Prescriptions
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: IPD
            ============================================================ -->
            <?php if (hasAnyPermission($ipd_permissions)): ?>
            <div class="sidebar-section-label">IPD</div>
            
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['ipd_admissions.php', 'ipd_treatment.php', 'discharge_summary.php']) ? 'active' : ''; ?>" onclick="toggleMenu('ipdMenu')">
                    <span><i class="fas fa-hospital-user"></i> IPD</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="ipdMenu" class="dropdown-menu <?php echo in_array($current_page, ['ipd_admissions.php', 'ipd_treatment.php', 'discharge_summary.php']) ? 'show' : ''; ?>">
                    <?php if (hasPermission('ipd-admission-view')): ?>
                    <a href="ipd_admissions.php" class="sidebar-link sub-link <?php echo $current_page == 'ipd_admissions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> IPD Admission
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('ipd-treatment-view')): ?>
                    <a href="ipd_treatment.php" class="sidebar-link sub-link <?php echo $current_page == 'ipd_treatment.php' ? 'active' : ''; ?>">
                        <i class="fas fa-procedures"></i> IPD Treatment
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('discharge-summary-view')): ?>
                    <a href="discharge_summary.php" class="sidebar-link sub-link <?php echo $current_page == 'discharge_summary.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-export"></i> Discharge Summary
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: LABORATORY
            ============================================================ -->
            <?php if (hasAnyPermission($lab_permissions)): ?>
            <div class="sidebar-section-label">Laboratory</div>
            
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['lab_orders.php', 'lab_reports.php']) ? 'active' : ''; ?>" onclick="toggleMenu('labMenu')">
                    <span><i class="fas fa-flask"></i> Laboratory</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="labMenu" class="dropdown-menu <?php echo in_array($current_page, ['lab_orders.php', 'lab_reports.php']) ? 'show' : ''; ?>">
                    <?php if (hasPermission('lab-orders-view')): ?>
                    <a href="lab_orders.php" class="sidebar-link sub-link <?php echo $current_page == 'lab_orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-vial"></i> Lab Orders
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('lab-reports-view')): ?>
                    <a href="lab_reports.php" class="sidebar-link sub-link <?php echo $current_page == 'lab_reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-medical"></i> Lab Reports
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: PHARMACY
            ============================================================ -->
            <?php if (hasAnyPermission($pharmacy_permissions)): ?>
            <div class="sidebar-section-label">Pharmacy</div>
            
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['medicine_sales.php', 'stock.php']) ? 'active' : ''; ?>" onclick="toggleMenu('pharmacyMenu')">
                    <span><i class="fas fa-prescription-bottle"></i> Pharmacy</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="pharmacyMenu" class="dropdown-menu <?php echo in_array($current_page, ['medicine_sales.php', 'stock.php']) ? 'show' : ''; ?>">
                    <?php if (hasPermission('medicine-sales-view')): ?>
                    <a href="medicine_sales.php" class="sidebar-link sub-link <?php echo $current_page == 'medicine_sales.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cash-register"></i> Medicine Sales
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('stock-view')): ?>
                    <a href="stock.php" class="sidebar-link sub-link <?php echo $current_page == 'stock.php' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Stock
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: BILLING
            ============================================================ -->
            <?php if (hasAnyPermission($billing_permissions)): ?>
            <div class="sidebar-section-label">Billing</div>
            
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle <?php echo in_array($current_page, ['opd_billing.php', 'ipd_billing.php', 'payments.php']) ? 'active' : ''; ?>" onclick="toggleMenu('billingMenu')">
                    <span><i class="fas fa-file-invoice-dollar"></i> Billing</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="billingMenu" class="dropdown-menu <?php echo in_array($current_page, ['opd_billing.php', 'ipd_billing.php', 'payments.php']) ? 'show' : ''; ?>">
                    <?php if (hasPermission('opd-billing-view')): ?>
                    <a href="opd_billing.php" class="sidebar-link sub-link <?php echo $current_page == 'opd_billing.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i> OPD Billing
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('ipd-billing-view')): ?>
                    <a href="ipd_billing.php" class="sidebar-link sub-link <?php echo $current_page == 'ipd_billing.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i> IPD Billing
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('payments-view')): ?>
                    <a href="payments.php" class="sidebar-link sub-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: REPORTS
            ============================================================ -->
            <?php if (hasAnyPermission($reports_permissions)): ?>
            <div class="sidebar-section-label">Reports</div>
            
            <?php if (hasPermission('reports-view')): ?>
            <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: ACCOUNTS
            ============================================================ -->
            <?php if (hasAnyPermission($accounts_permissions)): ?>
            <div class="sidebar-section-label">Accounts</div>
            
            <?php if (hasPermission('income-view') || hasPermission('expense-view')): ?>
            <a href="accounts.php" class="sidebar-link <?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>">
                <i class="fas fa-coins"></i> Accounts
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
            SECTION: AMBULANCE
            ============================================================ -->
            <?php if (hasAnyPermission($ambulance_permissions)): ?>
            <div class="sidebar-section-label">Ambulance</div>
            
            <?php if (hasPermission('ambulance-view')): ?>
            <a href="ambulance.php" class="sidebar-link <?php echo $current_page == 'ambulance.php' ? 'active' : ''; ?>">
                <i class="fas fa-ambulance"></i> Ambulance
            </a>
            <?php endif; ?>
            <?php endif; ?>

           

            <!-- ============================================================
            SECTION: SYSTEM
            ============================================================ -->
            <?php if (hasAnyPermission($system_permissions)): ?>
            <div class="sidebar-section-label">System</div>
            
            <?php if (hasPermission('audit-view')): ?>
            <a href="audit_logs.php" class="sidebar-link <?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Audit Logs
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('login-logs')): ?>
            <a href="login_logs.php" class="sidebar-link <?php echo $current_page == 'login_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i> Login Logs
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('system-settings')): ?>
            <a href="settings.php" class="sidebar-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
            MY PROFILE
            ============================================================ -->
            <div class="sidebar-section-label">Account</div>
            
            <a href="update_adminprofile.php" class="sidebar-link <?php echo $current_page == 'update_adminprofile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> My Profile
            </a>

            <!-- ============================================================
            LOGOUT
            ============================================================ -->
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
        'departments.php': 'mastersMenu',
        'doctors.php': 'mastersMenu',
        'staff.php': 'mastersMenu',
        'ward_master.php': 'mastersMenu',
        'medicine.php': 'mastersMenu',
        'lab_tests.php': 'mastersMenu',
        'appointments.php': 'opdMenu',
        'opd_visits.php': 'opdMenu',
        'prescriptions.php': 'opdMenu',
        'ipd_admissions.php': 'ipdMenu',
        'ipd_treatment.php': 'ipdMenu',
        'discharge_summary.php': 'ipdMenu',
        'lab_orders.php': 'labMenu',
        'lab_reports.php': 'labMenu',
        'medicine_sales.php': 'pharmacyMenu',
        'stock.php': 'pharmacyMenu',
        'opd_billing.php': 'billingMenu',
        'ipd_billing.php': 'billingMenu',
        'payments.php': 'billingMenu',
        'users.php': 'adminMenu',
        'roles.php': 'adminMenu',
        'permissions.php': 'adminMenu',
        'audit_logs.php': 'systemMenu',
        'login_logs.php': 'systemMenu',
        'settings.php': 'systemMenu'
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