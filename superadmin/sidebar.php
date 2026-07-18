<?php
// ============================================================
// SUPER ADMIN SIDEBAR
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config
include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] != 'SuperAdmin') {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['name'] ?? 'Super Admin';
$theme = $_SESSION['theme'] ?? 'light';
?>

<!-- ============================================================
SIDEBAR - SUPER ADMIN
============================================================ -->
<div class="superadmin-sidebar" id="sidebar">
    
    <!-- Toggle Button -->
    <button class="toggle-btn" id="toggleBtn" onclick="toggleSidebar()">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </button>
    
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-crown"></i>
        </div>
        <div class="brand-text">
            <h2>Super Admin</h2>
            <p>Full Access</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav>
        <!-- Dashboard -->
        <a href="dashboard.php" class="sidebar-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- Hospitals -->
        <a href="hospitals.php" class="sidebar-item <?php echo in_array($current_page, ['hospitals.php', 'add_hospital.php', 'edit_hospital.php', 'view_hospital.php']) ? 'active' : ''; ?>">
            <i class="fas fa-hospital"></i>
            <span>Hospitals</span>
        </a>
        
        <!-- Hospital Admins -->
        <a href="hospital_admins.php" class="sidebar-item <?php echo $current_page == 'hospital_admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i>
            <span>Hospital Admins</span>
        </a>
        
        <!-- Users -->
        <a href="users.php" class="sidebar-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        
        <!-- Labels -->
        <div class="sidebar-label">Administration</div>
        
        <!-- Roles -->
        <a href="role_list.php" class="sidebar-item <?php echo in_array($current_page, ['role_list.php', 'add_role.php', 'edit_role.php']) ? 'active' : ''; ?>">
            <i class="fas fa-user-tag"></i>
            <span>Roles</span>
        </a>
        
        <!-- Permissions -->
        <a href="permissions.php" class="sidebar-item <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>">
            <i class="fas fa-lock"></i>
            <span>Permissions</span>
        </a>
        
        <!-- System -->
        <div class="sidebar-label">System</div>
        
        <!-- Audit Logs -->
        <a href="audit_logs.php" class="sidebar-item <?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Audit Logs</span>
        </a>
        
        <!-- Login Logs -->
        <a href="login_logs.php" class="sidebar-item <?php echo $current_page == 'login_logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login Logs</span>
        </a>
        
        <!-- Settings -->
        <a href="settings.php" class="sidebar-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        
        <!-- Logout -->
        <div class="sidebar-divider"></div>
        <a href="../auth/logout.php" class="sidebar-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
    
    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 2)); ?>
            </div>
            <div class="user-details">
                <p class="user-name"><?php echo $user_name; ?></p>
                <p class="user-role">Super Admin</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
SIDEBAR CSS
============================================================ -->
<style>
/* ============================================================
   SIDEBAR CONTAINER
   ============================================================ */
.superadmin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
    z-index: 1000;
    padding: 1rem 0.75rem;
    overflow-y: auto;
    overflow-x: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    box-shadow: 2px 0 12px rgba(0,0,0,0.04);
}

.superadmin-sidebar.closed {
    width: 72px;
}

.superadmin-sidebar::-webkit-scrollbar {
    width: 4px;
}
.superadmin-sidebar::-webkit-scrollbar-thumb {
    background: #3b82f6;
    border-radius: 4px;
}

/* ============================================================
   TOGGLE BUTTON
   ============================================================ */
.toggle-btn {
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px 4px;
    margin-bottom: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.toggle-btn:hover {
    background: rgba(59, 130, 246, 0.08);
}

.toggle-btn .bar {
    height: 2.5px;
    border-radius: 2px;
    background: #475569;
    transition: all 0.3s ease;
}

.toggle-btn .bar:nth-child(1) { width: 24px; }
.toggle-btn .bar:nth-child(2) { width: 18px; }
.toggle-btn .bar:nth-child(3) { width: 12px; }

.superadmin-sidebar.closed .toggle-btn .bar:nth-child(1) {
    transform: rotate(45deg) translate(4px, 4px);
    width: 24px;
}
.superadmin-sidebar.closed .toggle-btn .bar:nth-child(2) {
    opacity: 0;
    width: 0;
}
.superadmin-sidebar.closed .toggle-btn .bar:nth-child(3) {
    transform: rotate(-45deg) translate(4px, -4px);
    width: 24px;
}

/* ============================================================
   BRAND
   ============================================================ */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0 0.5rem 1rem 0.5rem;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.brand-icon {
    width: 42px;
    min-width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    transition: all 0.3s ease;
}

.brand-text {
    opacity: 1;
    transition: opacity 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
}

.superadmin-sidebar.closed .brand-text {
    opacity: 0;
    width: 0;
}

.brand-text h2 {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.3px;
}

.brand-text p {
    font-size: 0.6rem;
    color: #94a3b8;
    margin: 0;
    letter-spacing: 0.5px;
}

/* ============================================================
   NAVIGATION
   ============================================================ */
.superadmin-sidebar nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* ============================================================
   SIDEBAR ITEMS
   ============================================================ */
.sidebar-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.8rem;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    color: #475569;
    transition: all 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    position: relative;
}

.sidebar-item i {
    width: 1.25rem;
    min-width: 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.sidebar-item span {
    opacity: 1;
    transition: opacity 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
}

.superadmin-sidebar.closed .sidebar-item span {
    opacity: 0;
    width: 0;
}

.sidebar-item:hover {
    background: rgba(59, 130, 246, 0.08);
    color: #3b82f6;
}

.sidebar-item:hover i {
    color: #3b82f6;
}

.sidebar-item.active {
    background: rgba(59, 130, 246, 0.08);
    color: #3b82f6;
}

.sidebar-item.active i {
    color: #3b82f6;
}

/* Active Indicator */
.sidebar-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 24px;
    background: #3b82f6;
    border-radius: 0 4px 4px 0;
}

.sidebar-item.logout {
    color: #ef4444;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
    margin-top: 0.25rem;
}

.sidebar-item.logout i {
    color: #ef4444;
}

.sidebar-item.logout:hover {
    background: rgba(239, 68, 68, 0.08);
    color: #dc2626;
}

.sidebar-item.logout:hover i {
    color: #dc2626;
}

/* ============================================================
   SIDEBAR LABEL
   ============================================================ */
.sidebar-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.5rem 0.8rem 0.3rem;
    color: #94a3b8;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    opacity: 1;
    transition: opacity 0.2s ease;
}

.superadmin-sidebar.closed .sidebar-label {
    opacity: 0;
    width: 0;
    padding: 0;
}

/* ============================================================
   DIVIDER
   ============================================================ */
.sidebar-divider {
    border-top: 1px solid #e2e8f0;
    margin: 0.5rem 0.5rem 0.25rem;
}

/* ============================================================
   FOOTER
   ============================================================ */
.sidebar-footer {
    padding: 0.75rem 0.5rem 0.25rem;
    border-top: 1px solid #e2e8f0;
    margin-top: auto;
    flex-shrink: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 36px;
    min-width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
    box-shadow: 0 2px 8px rgba(59,130,246,0.25);
}

.user-details {
    opacity: 1;
    transition: opacity 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
}

.superadmin-sidebar.closed .user-details {
    opacity: 0;
    width: 0;
}

.user-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.user-role {
    font-size: 0.65rem;
    color: #94a3b8;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .superadmin-sidebar {
        width: 240px;
    }
    .superadmin-sidebar.closed {
        width: 64px;
    }
    .brand-icon {
        width: 36px;
        min-width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    .toggle-btn .bar {
        height: 2px;
    }
    .toggle-btn .bar:nth-child(1) { width: 20px; }
    .toggle-btn .bar:nth-child(2) { width: 14px; }
    .toggle-btn .bar:nth-child(3) { width: 8px; }
    
    .superadmin-sidebar.closed .toggle-btn .bar:nth-child(1) {
        transform: rotate(45deg) translate(3px, 3px);
        width: 20px;
    }
    .superadmin-sidebar.closed .toggle-btn .bar:nth-child(3) {
        transform: rotate(-45deg) translate(3px, -3px);
        width: 20px;
    }
}
</style>

<!-- ============================================================
SIDEBAR JAVASCRIPT
============================================================ -->
<script>
// ============================================================
// TOGGLE SIDEBAR
// ============================================================
let isSidebarOpen = true;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (!sidebar || !mainContent) return;
    
    isSidebarOpen = !isSidebarOpen;
    
    if (isSidebarOpen) {
        sidebar.classList.remove('closed');
        mainContent.style.marginLeft = window.innerWidth <= 768 ? '240px' : '260px';
        mainContent.style.width = window.innerWidth <= 768 ? 'calc(100% - 240px)' : 'calc(100% - 260px)';
    } else {
        sidebar.classList.add('closed');
        mainContent.style.marginLeft = window.innerWidth <= 768 ? '64px' : '72px';
        mainContent.style.width = window.innerWidth <= 768 ? 'calc(100% - 64px)' : 'calc(100% - 72px)';
    }
}

// ============================================================
// RESPONSIVE CHECK
// ============================================================
function checkSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (!sidebar || !mainContent) return;
    
    if (window.innerWidth <= 768) {
        sidebar.style.width = isSidebarOpen ? '240px' : '64px';
        mainContent.style.marginLeft = isSidebarOpen ? '240px' : '64px';
        mainContent.style.width = isSidebarOpen ? 'calc(100% - 240px)' : 'calc(100% - 64px)';
    } else {
        sidebar.style.width = isSidebarOpen ? '260px' : '72px';
        mainContent.style.marginLeft = isSidebarOpen ? '260px' : '72px';
        mainContent.style.width = isSidebarOpen ? 'calc(100% - 260px)' : 'calc(100% - 72px)';
    }
}

// ============================================================
// CLOSE ON OUTSIDE CLICK (Mobile)
// ============================================================
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (!sidebar || !mainContent) return;
    
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickInsideMain = mainContent.contains(event.target);
    
    if (window.innerWidth <= 768 && isSidebarOpen && !isClickInsideSidebar && isClickInsideMain) {
        toggleSidebar();
    }
});

// ============================================================
// ESC KEY TO CLOSE
// ============================================================
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && isSidebarOpen && window.innerWidth <= 768) {
        toggleSidebar();
    }
});

// ============================================================
// INITIALIZE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (!sidebar || !mainContent) return;
    
    // Set initial state
    sidebar.classList.remove('closed');
    isSidebarOpen = true;
    
    const sidebarWidth = window.innerWidth <= 768 ? 240 : 260;
    const closedWidth = window.innerWidth <= 768 ? 64 : 72;
    
    sidebar.style.width = sidebarWidth + 'px';
    mainContent.style.marginLeft = sidebarWidth + 'px';
    mainContent.style.width = 'calc(100% - ' + sidebarWidth + 'px)';
    mainContent.style.transition = 'margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    mainContent.style.minHeight = '100vh';
    mainContent.style.padding = '1.5rem';
    mainContent.style.background = '#f8fafc';
    
    window.addEventListener('resize', checkSidebar);
});

// ============================================================
// ACTIVE PAGE HIGHLIGHT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const links = document.querySelectorAll('.sidebar-item');
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href === currentPage) {
            link.classList.add('active');
        }
    });
});
</script>