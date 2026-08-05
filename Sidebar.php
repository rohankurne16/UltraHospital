<?php
// ============================================================
// DYNAMIC SIDEBAR (Sidebar.php) - FINAL CSS FIX
// ============================================================

// Include permission config (this defines hasPerm, hasAnyPerm, etc.)
require_once __DIR__ . '/config/permission.php';
include "config/encryption.php";

// Get current page
 $current_page = basename($_SERVER['PHP_SELF']);

// Get user info from session
 $user_role = $_SESSION['role'] ?? 'Guest';
 $user_name = $_SESSION['name'] ?? 'User';
 $profile_image = $_SESSION['profile_image'] ?? '';
 $hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Ultra Hospital';
 $hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';

 $hid = $_SESSION['hospital_id'] ?? '';
 $encrypted_hid = encryptId($hid);

// Check if Super Admin
 $is_super_admin = isset($_SESSION['role']) && (strtolower(trim($_SESSION['role'])) === 'super admin' || strtolower(trim($_SESSION['role'])) === 'superadmin');

// NOTE: hasPerm(), hasAnyPerm(), getDashboardUrl() are already defined
// in config/permission.php - DO NOT redefine them here!
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

#sidebar-overlay.active { display: block; }

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
.dropdown-toggle:hover { background: #f1f5f9; }
.dropdown-toggle span { display: flex; align-items: center; gap: 0.75rem; }
.dropdown-toggle i { width: 1.25rem; text-align: center; color: #94a3b8; font-size: 1rem; flex-shrink: 0; }
.dropdown-arrow { transition: transform 0.3s ease; font-size: 0.75rem; color: #94a3b8; }

/* NEW CSS: Highlight parent dropdown when child is active */
.dropdown-toggle.active-dropdown { 
    background: #eff6ff; 
    color: #3b82f6; 
    font-weight: 600;
}
.dropdown-toggle.active-dropdown i { 
    color: #3b82f6; 
}

.dropdown-menu { 
    padding-left: 0.5rem; 
    margin-left: 0.5rem; 
    border-left: 2px solid #e2e8f0; 
    display: none;
    overflow: hidden;
}
.dropdown-menu.show { display: block; }
.dropdown-menu .sub-link { padding-left: 1.8rem; }
.dropdown-menu .sub-link i { width: 1rem; font-size: 0.85rem; }

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
    width: 38px; height: 38px; min-width: 38px; border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex; align-items: center; justify-content: center;
    color: #ffffff; font-weight: 700; font-size: 0.85rem;
    overflow: hidden; position: relative;
}

.user-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

.user-info { overflow: hidden; display: flex; flex-direction: column; min-width: 0; }
.user-name { font-size: 0.9rem; font-weight: 600; color: #1e293b; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 0.7rem; color: #94a3b8; margin: 0; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

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
                    <?php if (strtolower(trim($user_role)) == 'admin') { ?>
                        <img alt="Hospital Logo" src="<?php echo $hospital_logo; ?>" class="brand-logo" />
                    <?php } else { ?>
                        <img alt="Hospital Logo" src="../<?php echo $hospital_logo; ?>" class="brand-logo" />
                    <?php } ?>
                <?php else: ?>
                    <span class="brand-icon">H</span>
                <?php endif; ?>
                <span class="brand-name"><?php echo htmlspecialchars($hospital_name); ?></span>
            </a>
            <button class="mobile-close-btn" onclick="toggleSidebar()">
                <i class="fas fa-times" style="font-size: 1.25rem;"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            <!-- ==================== MAIN ==================== -->
            <?php if (hasPerm('dashboard-view')): ?>
            <div class="sidebar-section-label">Main</div>
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php endif; ?>

            <!-- ==================== PATIENT ==================== -->
            <?php if (hasAnyPerm(['patient-view', 'patient-create', 'opd-visit-view', 'ipd-admission-view', 'referral-view', 'call-patient-view'])): ?>
            <div class="sidebar-section-label">Patient</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('patientMenu')">
                    <span><i class="fas fa-user-injured"></i> Patients</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="patientMenu" class="dropdown-menu">
                    <?php if (hasPerm('patient-create')): ?>
                    <a href="patient_registration.php" class="sidebar-link sub-link">
                        <i class="fas fa-user-plus"></i> Patient Registration
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('patient-view')): ?>
                    <a href="patients.php" class="sidebar-link sub-link">
                        <i class="fas fa-users"></i> All Patients
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('opd-visit-view')): ?>
                    <a href="add_patient.php" class="sidebar-link sub-link">
                        <i class="fas fa-stethoscope"></i> OPD
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('ipd-admission-view')): ?>
                    <a href="add_ipd_patient.php" class="sidebar-link sub-link">
                        <i class="fas fa-hospital-user"></i> IPD
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('referral-view')): ?>
                    <a href="referrals.php" class="sidebar-link sub-link">
                        <i class="fas fa-share-alt"></i> Referrals
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('call-patient-view')): ?>
                    <a href="add_call_patient.php" class="sidebar-link sub-link">
                        <i class="fas fa-phone"></i> Call
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== APPOINTMENTS ==================== -->
            <?php if (hasPerm('appointment-view')): ?>
            <a href="appointments.php" class="sidebar-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <?php endif; ?>

            <!-- ==================== PRESCRIPTIONS ==================== -->
            <?php if (hasPerm('prescription-view')): ?>
            <a href="prescriptions.php" class="sidebar-link <?php echo $current_page == 'prescriptions.php' ? 'active' : ''; ?>">
                <i class="fas fa-prescription"></i> Prescriptions
            </a>
            <?php endif; ?>

            <!-- ==================== OPERATION THEATRE ==================== -->
            <?php if (hasAnyPerm(['surgery-view', 'surgery-create'])): ?>
            <div class="sidebar-section-label">Operation Theatre</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('surgeryMenu')">
                    <span><i class="fas fa-procedures"></i> Surgery</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="surgeryMenu" class="dropdown-menu">
                    <?php if (hasPerm('surgery-view')): ?>
                    <a href="surgeries.php" class="sidebar-link sub-link">
                        <i class="fas fa-list"></i> Surgery List
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('surgery-create')): ?>
                    <a href="add_surgery.php" class="sidebar-link sub-link">
                        <i class="fas fa-plus-circle"></i> Schedule Surgery
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== SERVICES ==================== -->
            <?php if (hasAnyPerm(['lab-orders-view', 'lab-reports-view', 'lab-master-view', 'stock-view', 'medicine-sales-view'])): ?>
            <div class="sidebar-section-label">Services</div>

            <?php if (hasAnyPerm(['lab-orders-view', 'lab-reports-view', 'lab-master-view'])): ?>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('labMenu')">
                    <span><i class="fas fa-flask"></i> Laboratory</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="labMenu" class="dropdown-menu">
                    <?php if (hasPerm('lab-master-view')): ?>
                    <a href="lab_test_master.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-alt"></i> Lab Master
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('lab-orders-view')): ?>
                    <a href="lab_order.php" class="sidebar-link sub-link">
                        <i class="fas fa-vial"></i> Lab Orders
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('lab-reports-view')): ?>
                    <a href="lab_report.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-medical"></i> Lab Reports
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasAnyPerm(['stock-view', 'medicine-sales-view'])): ?>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('pharmacyMenu')">
                    <span><i class="fas fa-prescription-bottle"></i> Pharmacy</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="pharmacyMenu" class="dropdown-menu">
                    <?php if (hasPerm('stock-view')): ?>
                    <a href="#" class="sidebar-link sub-link">
                        <i class="fas fa-boxes"></i> Stock
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('medicine-sales-view')): ?>
                    <a href="#" class="sidebar-link sub-link">
                        <i class="fas fa-cash-register"></i> Sales
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ==================== BILLING ==================== -->
            <?php if (hasAnyPerm(['billing-view', 'billing-create', 'advance-deposit-view'])): ?>
            <div class="sidebar-section-label">Finance</div>
            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('billingMenu')">
                    <span><i class="fas fa-file-invoice-dollar"></i> Billing</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="billingMenu" class="dropdown-menu">
                    <?php if (hasPerm('billing-view')): ?>
                    <a href="billing.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-invoice-dollar"></i> All Bills
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('billing-create')): ?>
                    <a href="create_bill.php" class="sidebar-link sub-link">
                        <i class="fas fa-plus-circle"></i> Create Bill
                    </a>
                    <?php endif; ?>
                    <?php if (hasPerm('advance-deposit-view')): ?>
                    <a href="advance_deposits.php" class="sidebar-link sub-link">
                        <i class="fas fa-wallet"></i> Advance Deposits
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== MANAGEMENT ==================== -->
            <?php if (hasAnyPerm(['doctor-view', 'staff-view', 'department-view', 'hospital-view', 'ward-view', 'ward-create', 'ward-edit', 'ward-delete'])): ?>
            <div class="sidebar-section-label">Management</div>
            
            <?php if (hasPerm('doctor-view')): ?>
            <a href="doctors.php" class="sidebar-link <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Doctors
            </a>
            <?php endif; ?>

            <?php if (hasPerm('staff-view')): ?>
            <a href="staff.php" class="sidebar-link <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> Staff
            </a>
            <?php endif; ?>

            <?php if (hasPerm('department-view')): ?>
            <a href="departments.php" class="sidebar-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Departments
            </a>
            <?php endif; ?>

            <?php if (hasAnyPerm(['ward-view', 'ward-create', 'ward-edit', 'ward-delete'])): ?>
            <a href="ward_master.php" class="sidebar-link <?php echo $current_page == 'ward_master.php' ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i> Ward Master
            </a>
            <?php endif; ?>

            <?php if (hasPerm('hospital-view')): ?>
            <a href="general_settings.php" class="sidebar-link <?php echo $current_page == 'hospitals.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i> Hospitals
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ==================== ADMIN ==================== -->
            <?php if (hasAnyPerm(['role-view', 'permission-view', 'audit-log-view'])): ?>
            <div class="sidebar-section-label">Admin</div>

            <?php if (hasPerm('role-view')): ?>
            <a href="roles.php" class="sidebar-link <?php echo $current_page == 'roles.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tag"></i> Roles
            </a>
            <?php endif; ?>

            <?php if (hasPerm('permission-view')): ?>
            <a href="permissions.php" class="sidebar-link <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i> Permissions
            </a>
            <?php endif; ?>

            <?php if (hasPerm('audit-log-view')): ?>
            <a href="audit_logs.php" class="sidebar-link <?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i> Audit Logs
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ==================== REPORTS ==================== -->
            <?php if (hasPerm('report-view')): ?>
            <div class="sidebar-section-label">Reports</div>
            <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php endif; ?>



            
<!-- ============================================================ -->
<!-- ===== HR SECTION ===== -->
<!-- ============================================================ -->
<?php 
// ========== FIX: Define $pending_count before using ==========
$pending_count = 0;
if (isset($conn) && isset($hospital_id) && $hospital_id > 0) {
    $pending_sql = "SELECT COUNT(*) as count FROM leave_requests WHERE hospital_id = $hospital_id AND status = 'Pending' AND delete_flag = 0";
    $pending_result = $conn->query($pending_sql);
    if ($pending_result && $pending_result->num_rows > 0) {
        $pending_count = $pending_result->fetch_assoc()['count'] ?? 0;
    }
}

if (hasAnyPerm(['hr-dashboard-view', 'hr-attendance-view', 'hr-leave-view', 'hr-payroll-view', 'hr-recruitment-view', 'hr-employee-view', 'hr-department-view', 'hr-designation-view', 'hr-shift-view', 'hr-holiday-view', 'hr-performance-view', 'hr-training-view', 'hr-report-view'])): 
?>
<div class="sidebar-section-label">Human Resources</div>

<!-- ========== 1. HR DASHBOARD ========== -->
<?php if (hasPerm('hr-dashboard-view')): ?>
<a href="HR/dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/HR/') !== false ? 'active' : ''; ?>">
    <i class="fas fa-tachometer-alt"></i> Dashboard
    <?php if ($pending_count > 0): ?>
    <span class="nav-badge"><?php echo $pending_count; ?></span>
    <?php endif; ?>
</a>
<?php endif; ?>


<!-- ========== 2. EMPLOYEE MANAGEMENT ========== -->
<?php if (hasPerm('hr-employee-view')): ?>
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('employeeMenu')">
        <span><i class="fas fa-user-tie"></i>Employee Manage
    </span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="employeeMenu" class="dropdown-menu">
        <a href="employees.php" class="sidebar-link sub-link">
            <i class="fas fa-users"></i> Employee Master
        </a>
        <a href="employee_documents_list.php" class="sidebar-link sub-link">
            <i class="fas fa-file-alt"></i> Employee Documents
        </a>
    </div>
</div>
<?php endif; ?>


<!-- ========== 3. DEPARTMENT MASTER ========== -->
<?php if (hasPerm('hr-department-view')): ?>
<a href="departments.php" class="sidebar-link sub-link">
    <i class="fas fa-building"></i> Department Master
</a>
<?php endif; ?>



<!-- ========== 4. DESIGNATION MASTER ========== -->
<?php if (hasPerm('hr-designation-view')): ?>
<a href="HR/designations.php" class="sidebar-link sub-link">
    <i class="fas fa-briefcase"></i> Designation Master
</a>
<?php endif; ?>



<!-- ========== 5. SHIFT MASTER ========== -->
<?php if (hasPerm('hr-shift-view')): ?>
<a href="HR/shifts.php" class="sidebar-link sub-link">
    <i class="fas fa-clock"></i> Shift Master
</a>
<?php endif; ?>



<!-- ========== 6. ATTENDANCE ========== -->
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('attendanceMenu')">
        <span><i class="fas fa-clipboard-check"></i> Attendance</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="attendanceMenu" class="dropdown-menu">
        <?php if (hasPerm('hr-attendance-view')): ?>
        <a href="mark_attendance.php" class="sidebar-link sub-link">
            <i class="fas fa-check-double"></i> Mark Attendance
        </a>
        <a href="attendance_report.php" class="sidebar-link sub-link">
            <i class="fas fa-file-alt"></i> Attendance Report
        </a>
        <?php endif; ?>
    </div>
</div>



<!-- ========== 7. LEAVE MANAGEMENT ========== -->
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('leaveMenu')">
        <span><i class="fas fa-calendar-minus"></i> Leave Management</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
        <?php if ($pending_count > 0): ?>
        <span class="nav-badge"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </button>
    <div id="leaveMenu" class="dropdown-menu">
        <?php if (hasPerm('hr-leave-view')): ?>
        <a href="leave_types.php" class="sidebar-link sub-link">
            <i class="fas fa-tags"></i> Leave Type
        </a>
        <a href="leave_requests.php" class="sidebar-link sub-link">
            <i class="fas fa-list"></i> Leave Request
        </a>
        <a href="leave_requests.php?status=Pending" class="sidebar-link sub-link">
            <i class="fas fa-clock"></i> Leave Approval
            <?php if ($pending_count > 0): ?>
            <span class="nav-badge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
</div>



<!-- ========== 8. HOLIDAY MASTER ========== -->
<?php if (hasPerm('hr-holiday-view')): ?>
<a href="HR/holidays.php" class="sidebar-link sub-link">
    <i class="fas fa-gift"></i> Holiday Master
</a>
<?php endif; ?>



<!-- ========== 9. PAYROLL ========== -->
<?php if (hasPerm('hr-payroll-view')): ?>
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('payrollMenu')">
        <span><i class="fas fa-money-bill-wave"></i> Payroll</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="payrollMenu" class="dropdown-menu">
        <a href="salary_structure.php" class="sidebar-link sub-link">
            <i class="fas fa-cogs"></i> Salary Structure
        </a>
        <a href="generate_salary.php" class="sidebar-link sub-link">
            <i class="fas fa-file-invoice-dollar"></i> Generate Salary
        </a>
        <a href="salary_slip.php" class="sidebar-link sub-link">
            <i class="fas fa-file-pdf"></i> Salary Slip
        </a>
        <a href="salary_history.php" class="sidebar-link sub-link">
            <i class="fas fa-history"></i> Salary History
        </a>
    </div>
</div>
<?php endif; ?>



<!-- ========== 10. PERFORMANCE ========== -->
<?php if (hasPerm('hr-performance-view')): ?>
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('performanceMenu')">
        <span><i class="fas fa-star"></i> Performance</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="performanceMenu" class="dropdown-menu">
        <a href="HR/performance.php" class="sidebar-link sub-link">
            <i class="fas fa-chart-line"></i> Employee Rating
        </a>
        <a href="HR/kpi.php" class="sidebar-link sub-link">
            <i class="fas fa-bullseye"></i> KPI Management
        </a>
        <a href="HR/appraisal.php" class="sidebar-link sub-link">
            <i class="fas fa-arrow-up"></i> Appraisal
        </a>
        <a href="HR/promotion.php" class="sidebar-link sub-link">
            <i class="fas fa-arrow-up"></i> Promotion
        </a>
        <a href="HR/warning_letter.php" class="sidebar-link sub-link">
            <i class="fas fa-exclamation-triangle"></i> Warning Letter
        </a>
    </div>
</div>
<?php endif; ?>



<!-- ========== 11. RECRUITMENT ========== -->
<?php if (hasPerm('hr-recruitment-view')): ?>
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('recruitmentMenu')">
        <span><i class="fas fa-user-plus"></i> Recruitment</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="recruitmentMenu" class="dropdown-menu">
        <a href="job_openings.php" class="sidebar-link sub-link">
            <i class="fas fa-bullhorn"></i> Job Openings
        </a>
        <a href="candidates.php" class="sidebar-link sub-link">
            <i class="fas fa-users"></i> Candidates
        </a>
        <a href="interview_schedule.php" class="sidebar-link sub-link">
            <i class="fas fa-calendar-plus"></i> Interview Schedule
        </a>
        <a href="selection.php" class="sidebar-link sub-link">
            <i class="fas fa-check-circle"></i> Selection
        </a>
        <a href="offer_letter.php" class="sidebar-link sub-link">
            <i class="fas fa-file-signature"></i> Offer Letter
        </a>
    </div>
</div>
<?php endif; ?>




<!-- ========== 12. TRAINING ========== -->
<?php if (hasPerm('hr-training-view')): ?>
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('trainingMenu')">
        <span><i class="fas fa-graduation-cap"></i> Training</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="trainingMenu" class="dropdown-menu">
        <a href="HR/training.php" class="sidebar-link sub-link">
            <i class="fas fa-list"></i> Training List
        </a>
        <a href="HR/add_training.php" class="sidebar-link sub-link">
            <i class="fas fa-plus"></i> Add Training
        </a>
        <a href="HR/training_attendance.php" class="sidebar-link sub-link">
            <i class="fas fa-clipboard-check"></i> Training Attendance
        </a>
        <a href="HR/certificates.php" class="sidebar-link sub-link">
            <i class="fas fa-certificate"></i> Certificates
        </a>
    </div>
</div>
<?php endif; ?>



<!-- ========== 13. REPORTS ========== -->
<?php if (hasPerm('hr-report-view')): ?>
<div class="sidebar-dropdown">
    <button class="dropdown-toggle" onclick="toggleMenu('reportsMenu')">
        <span><i class="fas fa-chart-bar"></i> Reports</span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    <div id="reportsMenu" class="dropdown-menu">
        <a href="HR/reports/employee_report.php" class="sidebar-link sub-link">
            <i class="fas fa-users"></i> Employee Report
        </a>
        <a href="HR/reports/attendance_report.php" class="sidebar-link sub-link">
            <i class="fas fa-clipboard-check"></i> Attendance Report
        </a>
        <a href="HR/reports/leave_report.php" class="sidebar-link sub-link">
            <i class="fas fa-calendar-minus"></i> Leave Report
        </a>
        <a href="HR/reports/payroll_report.php" class="sidebar-link sub-link">
            <i class="fas fa-money-bill-wave"></i> Payroll Report
        </a>
        <a href="HR/reports/department_report.php" class="sidebar-link sub-link">
            <i class="fas fa-building"></i> Department Report
        </a>
        <a href="HR/reports/joining_report.php" class="sidebar-link sub-link">
            <i class="fas fa-user-plus"></i> Joining Report
        </a>
        <a href="HR/reports/resignation_report.php" class="sidebar-link sub-link">
            <i class="fas fa-user-minus"></i> Resignation Report
        </a>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>









            <!-- ============================================================ -->
            <!-- ===== NEW: ACCOUNTANT SECTION ===== -->
            <!-- ============================================================ -->
            <?php if (hasAnyPerm(['accountant-dashboard-view', 'accountant-salary-view', 'accountant-expense-view', 'accountant-payment-view', 'accountant-invoice-view', 'accountant-ledger-view'])): ?>
            <div class="sidebar-section-label">Accounting</div>

            <?php if (hasPerm('accountant-dashboard-view')): ?>
            <a href="accounts/dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/accounts/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calculator"></i> Accounts Dashboard
            </a>
            <?php endif; ?>

            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('accountantMenu')">
                    <span><i class="fas fa-coins"></i> Finance & Accounts</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="accountantMenu" class="dropdown-menu">
                    <?php if (hasPerm('accountant-salary-view')): ?>
                    <a href="accounts/salary_management.php" class="sidebar-link sub-link">
                        <i class="fas fa-money-bill-wave"></i> Salary Management
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('accountant-expense-view')): ?>
                    <a href="accounts/expenses.php" class="sidebar-link sub-link">
                        <i class="fas fa-receipt"></i> Expenses
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('accountant-payment-view')): ?>
                    <a href="accounts/payments.php" class="sidebar-link sub-link">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('accountant-invoice-view')): ?>
                    <a href="accounts/invoices.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-invoice-dollar"></i> Invoices
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('accountant-ledger-view')): ?>
                    <a href="accounts/ledger.php" class="sidebar-link sub-link">
                        <i class="fas fa-book"></i> Ledger
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('billing-view')): ?>
                    <a href="billing.php" class="sidebar-link sub-link">
                        <i class="fas fa-file-invoice-dollar"></i> Patient Billing
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('advance-deposit-view')): ?>
                    <a href="advance_deposits.php" class="sidebar-link sub-link">
                        <i class="fas fa-wallet"></i> Advance Deposits
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- ===== NEW: PHARMACIST SECTION ===== -->
            <!-- ============================================================ -->
            <?php if (hasAnyPerm(['pharmacist-dashboard-view', 'pharmacist-medicine-view', 'pharmacist-prescription-view', 'pharmacist-supplier-view', 'pharmacist-expiry-view'])): ?>
            <div class="sidebar-section-label">Pharmacy</div>

            <?php if (hasPerm('pharmacist-dashboard-view')): ?>
            <a href="pharmacy/dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/pharmacy/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-prescription-bottle-alt"></i> Pharmacy Dashboard
            </a>
            <?php endif; ?>

            <div class="sidebar-dropdown">
                <button class="dropdown-toggle" onclick="toggleMenu('pharmacistMenu')">
                    <span><i class="fas fa-pills"></i> Medicine Management</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div id="pharmacistMenu" class="dropdown-menu">
                    <?php if (hasPerm('pharmacist-medicine-view')): ?>
                    <a href="pharmacy/medicines.php" class="sidebar-link sub-link">
                        <i class="fas fa-capsules"></i> Medicine Inventory
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('pharmacist-prescription-view')): ?>
                    <a href="pharmacy/dispense.php" class="sidebar-link sub-link">
                        <i class="fas fa-prescription"></i> Dispense Prescriptions
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('pharmacist-supplier-view')): ?>
                    <a href="pharmacy/suppliers.php" class="sidebar-link sub-link">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('pharmacist-expiry-view')): ?>
                    <a href="pharmacy/expiry_tracking.php" class="sidebar-link sub-link">
                        <i class="fas fa-exclamation-triangle"></i> Expiry Tracking
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('medicine-sales-view')): ?>
                    <a href="pharmacy/sales.php" class="sidebar-link sub-link">
                        <i class="fas fa-cash-register"></i> Sales
                    </a>
                    <?php endif; ?>

                    <?php if (hasPerm('stock-view')): ?>
                    <a href="pharmacy/stock.php" class="sidebar-link sub-link">
                        <i class="fas fa-boxes"></i> Stock Management
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== ACCOUNT ==================== -->
            <?php
            $role_lower = strtolower(trim($user_role));
            switch ($role_lower) {
                case 'super admin': $profilePage = "superadmin/dashboard.php"; break;
                case 'admin': $profilePage = "dashboard.php"; break;
                case 'doctor': $profilePage = "update_adminprofile.php"; break;
                case 'nurse': case 'ward boy': case 'staff': $profilePage = "staff/dashboard.php"; break;
                case 'hr': $profilePage = "HR/dashboard.php"; break;
                case 'lab technician': $profilePage = "labtechnician/update_profile.php"; break;
                case 'patient': $profilePage = "patients/profile.php"; break;
                case 'billing staff': $profilePage = "staff/billing_profile.php"; break;
                case 'accountant': $profilePage = "update_adminprofile.php"; break;
                case 'pharmacist': $profilePage = "staff/pharmacist_profile.php"; break;
                case 'receptionist': $profilePage = "staff/reception_profile.php"; break;
                default: $profilePage = "dashboard.php";
            }
            ?>

            <div class="sidebar-section-label">Account</div>
            <a href="<?php echo $profilePage; ?>"
               class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == basename($profilePage) ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Update Profile
            </a>

            <?php if (hasPerm('settings-view')): ?>
            <a href="general_settings.php" class="sidebar-link <?php echo $current_page == 'general_settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
            <?php endif; ?>

            <div class="logout-section">
                <?php if(strtolower(trim($user_role)) == 'admin'){ ?>
                    <a href="auth/logout.php?hid=<?php echo $encrypted_hid; ?>" class="sidebar-link logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php } else { ?>
                    <a href="../auth/logout.php?hid=<?php echo $encrypted_hid; ?>" class="sidebar-link logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php } ?>
            </div>
        </nav>

        <!-- Footer -->
        <div class="sidebar-footer">
            <div class="user-avatar">
                <?php if (!empty($profile_image)): ?>
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
    var e = window.event || event;
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const menu = document.getElementById(menuId);
    if (!menu) return;
    
    const btn = menu.previousElementSibling;
    const arrow = btn ? btn.querySelector('.dropdown-arrow') : null;
    
    menu.classList.toggle('show');
    
    if (arrow) {
        arrow.style.transform = menu.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

function toggleSidebar() {
    var e = window.event || event;
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const sidebar = document.getElementById('sidebar-container');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar) sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebarContainer = document.getElementById('sidebar-container');
    
    // 1. Auto-highlight active link and open parent dropdown automatically
    const currentFullPath = window.location.pathname;
    const allLinks = document.querySelectorAll('.sidebar-link');
    
    allLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;
        
        // Normalize href (remove leading slashes for comparison)
        const cleanHref = href.startsWith('/') ? href.substring(1) : href;
        
        // If the current URL ends with the link's href, it is the active link
        if (currentFullPath.endsWith(cleanHref)) {
            link.classList.add('active');
            
            // Find if it's inside a dropdown menu
            const parentMenu = link.closest('.dropdown-menu');
            if (parentMenu) {
                parentMenu.classList.add('show'); // Open dropdown
                
                const toggleBtn = parentMenu.previousElementSibling;
                if (toggleBtn) {
                    toggleBtn.classList.add('active-dropdown'); // Highlight parent toggle
                    const arrow = toggleBtn.querySelector('.dropdown-arrow');
                    if (arrow) {
                        arrow.style.transform = 'rotate(180deg)'; // Rotate arrow
                    }
                    
                    // Handle nested dropdowns (if sub-menu is inside another dropdown)
                    let parentDropdown = toggleBtn.closest('.dropdown-menu');
                    while (parentDropdown) {
                        parentDropdown.classList.add('show');
                        const parentToggle = parentDropdown.previousElementSibling;
                        if (parentToggle) {
                            parentToggle.classList.add('active-dropdown');
                            const parentArrow = parentToggle.querySelector('.dropdown-arrow');
                            if (parentArrow) parentArrow.style.transform = 'rotate(180deg)';
                        }
                        parentDropdown = parentToggle ? parentToggle.closest('.dropdown-menu') : null;
                    }
                }
            }
        }
    });

    // 2. Restore Scroll Position
    if (sidebarContainer) {
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('sidebarScrollPos', sidebarContainer.scrollTop);
        });
        
        const savedScrollPos = sessionStorage.getItem('sidebarScrollPos');
        if (savedScrollPos !== null) {
            setTimeout(function() {
                sidebarContainer.scrollTop = parseInt(savedScrollPos);
            }, 100);
        }
    }
});
</script>