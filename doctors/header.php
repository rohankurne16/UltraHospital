<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/hospital.php');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_SESSION['id'];

// Get user information
$user_sql = "SELECT * FROM register WHERE id = '$id'";
$user_result = $conn->query($user_sql);

// Initialize variables with default values
$user_name = 'User';
$user_image = '';
$user_role = '';

if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_name = isset($user['name']) ? $user['name'] : 'User';
    $user_image = isset($user['image']) ? $user['image'] : '';
    $user_role = isset($user['role']) ? $user['role'] : '';
    
    // If user is doctor, get doctor details
    if ($user_role == 'doctor' || $user_role == 'Doctor') {
        $doctor_sql = "SELECT * FROM doctor WHERE register_id = '$id'";
        $doctor_result = $conn->query($doctor_sql);
        if ($doctor_result && $doctor_result->num_rows > 0) {
            $doctor = $doctor_result->fetch_assoc();
            $user_name = isset($doctor['doctor_name']) ? $doctor['doctor_name'] : $user_name;
            $user_image = isset($doctor['doctor_image']) ? $doctor['doctor_image'] : $user_image;
        }
    }
}

// Get patient ID if needed
$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : 0;

// Get hospital name and logo with fallbacks
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?></title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital_logo); ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        .header-toggle {
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .header-toggle:hover {
            background: #f3f4f6;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: white;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Dropdown animation */
        #userDropdown {
            transition: all 0.2s ease;
        }
        #userDropdown:not(.hidden) {
            animation: slideDown 0.2s ease;
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
    </style>
</head>
<body>
    <header class="fixed top-0 left-0 right-0 z-40 bg-white border-b border-gray-200 shadow-sm" id="mainHeader">
        <div class="flex h-16 items-center justify-between px-4 md:px-6">
            <!-- Left Section -->
            <div class="flex items-center gap-4">
                <!-- Mobile Toggle -->
                <button class="header-toggle xl:hidden" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                
                <!-- Logo -->
                <a href="dashboard.php" class="flex items-center gap-2">
                    <?php if (!empty($hospital_logo)): ?>
                        <img src="../<?php echo htmlspecialchars($hospital_logo); ?>" 
                             alt="Logo" 
                             class="h-10 w-10 object-contain"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <span class="hidden sm:block font-bold text-lg text-gray-800">
                        <?php echo htmlspecialchars($hospital_name); ?>
                    </span>
                </a>
            </div>

            <!-- Right Section -->
            <div class="flex items-center gap-3">
                <!-- Notifications -->
                <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors" title="Notifications">
                    <i data-lucide="bell" class="w-5 h-5 text-gray-600"></i>
                    <span class="notification-badge">3</span>
                </button>
                
                <!-- User Profile -->
                <div class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 px-3 py-1.5 rounded-lg transition-colors" onclick="toggleDropdown()" id="profileButton">
                    <?php if (!empty($user_image) && file_exists($user_image)): ?>
                        <img src="<?php echo htmlspecialchars($user_image); ?>" 
                             alt="<?php echo htmlspecialchars($user_name); ?>" 
                             class="user-avatar">
                    <?php elseif (!empty($user_image) && file_exists("../" . $user_image)): ?>
                        <img src="../<?php echo htmlspecialchars($user_image); ?>" 
                             alt="<?php echo htmlspecialchars($user_name); ?>" 
                             class="user-avatar">
                    <?php else: ?>
                        <div class="user-initials">
                            <?php 
                            $initials = '';
                            $name_parts = explode(' ', $user_name);
                            foreach ($name_parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            echo htmlspecialchars(substr($initials, 0, 2));
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="hidden md:block text-left">
                        <p class="text-sm font-medium text-gray-800 leading-tight"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-xs text-gray-500 leading-tight"><?php echo htmlspecialchars($user_role ?: 'User'); ?></p>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 hidden md:block"></i>
                </div>
            </div>
        </div>
        
        <!-- Dropdown Menu -->
        <div id="userDropdown" class="absolute right-4 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 hidden z-50">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user_role ?: 'User'); ?></p>
            </div>
            <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors">
                <i data-lucide="user" class="w-4 h-4 text-gray-500"></i>
                <span class="text-sm text-gray-700">My Profile</span>
            </a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors">
                <i data-lucide="settings" class="w-4 h-4 text-gray-500"></i>
                <span class="text-sm text-gray-700">Settings</span>
            </a>
            <div class="border-t border-gray-200 my-1"></div>
            <a href="../logout.php" class="flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 transition-colors text-red-600" onclick="return confirm('Are you sure you want to logout?')">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </div>
    </header>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const profileButton = document.getElementById('profileButton');
            if (dropdown && !dropdown.classList.contains('hidden')) {
                if (!dropdown.contains(e.target) && !profileButton.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });

        // Toggle sidebar (for mobile)
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

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 1280) {
                const sidebar = document.getElementById('doctorSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar && sidebar.classList.contains('open')) {
                    if (!sidebar.contains(e.target) && !e.target.closest('.header-toggle')) {
                        sidebar.classList.remove('open');
                        if (overlay) {
                            overlay.classList.remove('open');
                        }
                    }
                }
            }
        });

        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1280) {
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
    </script>
</body>
</html>