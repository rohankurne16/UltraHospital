 <?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../config/hospital.php';

if (!isset($_SESSION["id"])) {
    header("Location:../index.php");
    exit();
}

$id = $_SESSION["id"];

// Initialize variables with default values
$doctor_image = '';
$doctor_dept = '';
$doctor_name = '';

// Get doctor information
$doctor_info = "SELECT * FROM doctor WHERE register_id='$id'";
$doctor_data = $conn->query($doctor_info);

if ($doctor_data && $doctor_data->num_rows > 0) {
    $res = $doctor_data->fetch_assoc();
    $doctor_image = isset($res["doctor_image"]) ? $res["doctor_image"] : '';
    $doctor_dept = isset($res["department"]) ? $res["department"] : '';
    $doctor_name = isset($res["doctor_name"]) ? $res["doctor_name"] : '';
}
?>
<aside class="!fixed h-full left-0 bottom-0 z-50 flex w-64 flex-col border-r bg-white transition-transform duration-300 ease-in-out translate-x-0 hidden xl:flex" id="doctorSidebar">
    <!-- Sidebar Header -->
    <div class="flex py-4 items-center justify-between px-6">
        <a class="flex items-center space-x-2" href="dashboard.php">
            <div>
                <img src="../<?php echo isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : ''; ?>" height="70" width="70" alt="Hospital Logo">
            </div>
            <span class="font-bold inline-block text-xl tracking-tight"><?php echo isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital'; ?></span>
        </a>
    </div>

    <!-- Sidebar Navigation -->
    <div class="flex-1 py-2 h-full overflow-y-auto custom-scrollbar">
        <nav class="space-y-1 px-2">
            <!-- Dashboard -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-blue-50 text-blue-600' : 'text-neutral-800 hover:bg-gray-100'; ?>" href="dashboard.php">
                    <i data-lucide="layout-dashboard" class="mr-2 h-4 w-4"></i>
                    Dashboard
                </a>
            </div>

            <!-- Appointments -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'show_myappointment.php') ? 'bg-blue-50 text-blue-600' : 'text-neutral-800 hover:bg-gray-100'; ?>" href="show_myappointment.php">
                    <i data-lucide="calendar" class="mr-2 h-4 w-4"></i>
                    Appointments
                </a>
            </div>

            <!-- My Patients -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'all_patients.php') ? 'bg-blue-50 text-blue-600' : 'text-neutral-800 hover:bg-gray-100'; ?>" href="all_patients.php">
                    <i data-lucide="user-round" class="mr-2 h-4 w-4"></i>
                    My Patients
                </a>
            </div>

            <!-- Prescriptions -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'prescription_list.php') ? 'bg-blue-50 text-blue-600' : 'text-neutral-800 hover:bg-gray-100'; ?>" href="prescription_list.php">
                    <i data-lucide="pill" class="mr-2 h-4 w-4"></i>
                    Prescriptions
                </a>
            </div>

            <!-- OPD -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'opd_main.php') ? 'bg-blue-50 text-blue-600' : 'text-neutral-800 hover:bg-gray-100'; ?>" href="opd_main.php">
                    <i data-lucide="stethoscope" class="mr-2 h-4 w-4"></i>
                    OPD
                </a>
            </div>

            <!-- Profile -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'doctor_profile.php') ? 'bg-blue-50 text-blue-600' : 'text-neutral-800 hover:bg-gray-100'; ?>" href="doctor_profile.php">
                    <i data-lucide="user" class="mr-2 h-4 w-4"></i>
                    Profile
                </a>
            </div>

            <!-- Divider -->
            <div class="border-t my-4"></div>

            <!-- Logout -->
            <div class="space-y-1">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-red-50 hover:text-red-600" href="../index.php" onclick="return confirm('Are you sure you want to logout?')">
                    <i data-lucide="log-out" class="mr-2 h-4 w-4"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Sidebar Footer - Doctor Info -->
    <div class="border-t p-4 shrink-0">
        <div class="flex items-center gap-3">
            <?php if (!empty($doctor_image) && file_exists($doctor_image)): ?>
                <img src="<?php echo htmlspecialchars($doctor_image); ?>" class="w-10 h-10 rounded-full object-cover" alt="Doctor Image">
            <?php elseif (!empty($doctor_image) && file_exists("../" . $doctor_image)): ?>
                <img src="../<?php echo htmlspecialchars($doctor_image); ?>" class="w-10 h-10 rounded-full object-cover" alt="Doctor Image">
            <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                    <?php echo !empty($doctor_name) ? strtoupper(substr($doctor_name, 0, 1)) : 'D'; ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?php echo !empty($doctor_name) ? htmlspecialchars($doctor_name) : 'Doctor'; ?></p>
                <p class="text-xs text-gray-500 truncate"><?php echo !empty($doctor_dept) ? htmlspecialchars($doctor_dept) : 'Department'; ?></p>
            </div>
        </div>
    </div>
</aside>

<style>
    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 10px;
    }

    /* Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.3);
        z-index: 45;
    }
    .sidebar-overlay.open {
        display: block;
    }

    @media (max-width: 1024px) {
        #doctorSidebar {
            transform: translateX(-100%);
        }
        #doctorSidebar.open {
            transform: translateX(0);
        }
        #doctorSidebar {
            display: flex !important;
        }
    }
</style>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('doctorSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
        if (overlay) {
            overlay.classList.toggle('open');
        }
    }

    // Close sidebar on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            const sidebar = document.getElementById('doctorSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) {
                sidebar.classList.remove('open');
            }
            if (overlay) {
                overlay.classList.remove('open');
            }
        }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('doctorSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (window.innerWidth <= 1024) {
            if (sidebar && !sidebar.contains(e.target) && !e.target.closest('.header-toggle')) {
                sidebar.classList.remove('open');
                if (overlay) {
                    overlay.classList.remove('open');
                }
            }
        }
    });

    // Highlight current page
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const currentPage = currentPath.split('/').pop();
        const navLinks = document.querySelectorAll('#doctorSidebar .nav-link');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPage === href) {
                link.classList.add('bg-blue-50', 'text-blue-600');
                link.classList.remove('text-neutral-800');
            }
        });
    });
</script>