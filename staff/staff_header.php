<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "../config/hospital.php";

// Check login
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$logged_in_user_id = $_SESSION['id'];

// Fetch hospital settings
$hospital_name = "Hospital Management";
$hospital_logo = "assets/img/logo.png";

$sql_hospital = "SELECT * FROM hospital_settings LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
    $hospital_name = $hospital_data["hospital_name"] ?? "Hospital Management";
    $hospital_logo = $hospital_data["hospital_logo"] ?? "assets/img/logo.png";
}

// Fetch staff details
$sql = "SELECT
            s.*,
            r.role AS user_role,
            r.email AS user_email
        FROM staff s
        INNER JOIN register r
            ON s.register_id = r.id
        WHERE r.id = ?
        AND (s.delete_flag = 0 OR s.delete_flag IS NULL)
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare Failed : " . $conn->error);
}

$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $staff = $result->fetch_assoc();

    $staff_id      = $staff['staff_id'];
    $register_id   = $staff['register_id'];
    $staff_name    = $staff['name'];
    $staff_email   = $staff['user_email'];
    $staff_role    = $staff['user_role'];
    $staff_mobile  = $staff['mobile'];
    $staff_address = $staff['address'];
    $staff_status  = $staff['status'];
    $profile_image = $staff['profile_image'];
    
    // Set profile photo path
    $staff_photo = "assets/img/default_avatar.png";
    if (!empty($profile_image) && file_exists($profile_image)) {
        $staff_photo = $profile_image;
    } elseif (!empty($profile_image) && file_exists("../" . $profile_image)) {
        $staff_photo = "../" . $profile_image;
    } elseif (!empty($profile_image) && file_exists("../../" . $profile_image)) {
        $staff_photo = "../../" . $profile_image;
    }

} else {
    // Try to get from register table directly if not in staff
    $sql_register = "SELECT id, name, email, role FROM register WHERE id = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
    $stmt_register = $conn->prepare($sql_register);
    if ($stmt_register) {
        $stmt_register->bind_param("i", $logged_in_user_id);
        $stmt_register->execute();
        $result_register = $stmt_register->get_result();
        
        if ($result_register->num_rows > 0) {
            $user = $result_register->fetch_assoc();
            $staff_name = $user['name'];
            $staff_email = $user['email'];
            $staff_role = $user['role'];
            $staff_id = $user['id'];
            $staff_photo = "assets/img/default_avatar.png";
        } else {
            session_destroy();
            header("Location: ../index.php");
            exit();
        }
        $stmt_register->close();
    } else {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
}

$stmt->close();

// Determine role class for styling
$role_class = 'role-' . strtolower(str_replace(' ', '_', $staff_role));
?>

<header class="flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center gap-4">
        <button class="lg:hidden" onclick="toggleSidebar()">
            <i data-lucide="menu" class="w-6 h-6 text-gray-600"></i>
        </button>
        <a href="#" class="flex items-center gap-2">
            <img src="<?php echo htmlspecialchars($hospital_logo); ?>" alt="Logo" class="h-8 w-auto" onerror="this.src='assets/img/logo.png'">
            <span class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($hospital_name); ?></span>
        </a>
    </div>

    <div class="flex items-center gap-4">
        <!-- Notifications -->
        <button class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>

        <!-- User Profile Dropdown -->
        <div class="relative group">
            <button class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 focus:outline-none transition-colors">
                <img class="h-8 w-8 rounded-full object-cover border-2 border-gray-200" 
                     src="<?php echo htmlspecialchars($staff_photo); ?>" 
                     alt="<?php echo htmlspecialchars($staff_name); ?>"
                     onerror="this.src='assets/img/default_avatar.png'">
                <span class="font-medium text-gray-700 hidden md:block"><?php echo htmlspecialchars($staff_name); ?></span>
                <span class="hidden md:inline-block text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                    <?php echo htmlspecialchars($staff_role); ?>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500 hidden md:block"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                <div class="py-1">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($staff_name); ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($staff_email); ?></p>
                        <p class="text-xs text-gray-400 mt-1">
                            <span class="inline-block px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs">
                                <?php echo htmlspecialchars($staff_role); ?>
                            </span>
                        </p>
                    </div>
                    
                    <?php if ($staff_role == 'admin'): ?>
                    <a href="../admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i data-lucide="user" class="w-4 h-4 inline-block mr-2"></i>Profile
                    </a>
                    <a href="../admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i data-lucide="settings" class="w-4 h-4 inline-block mr-2"></i>Settings
                    </a>
                    <?php elseif ($staff_role == 'doctor'): ?>
                    <a href="../doctors/doctor_profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i data-lucide="user" class="w-4 h-4 inline-block mr-2"></i>Profile
                    </a>
                    <a href="../doctors/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i data-lucide="settings" class="w-4 h-4 inline-block mr-2"></i>Settings
                    </a>
                    <?php else: ?>
                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i data-lucide="user" class="w-4 h-4 inline-block mr-2"></i>Profile
                    </a>
                    <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i data-lucide="settings" class="w-4 h-4 inline-block mr-2"></i>Settings
                    </a>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-200"></div>
                    <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors" onclick="return confirm('Are you sure you want to logout?')">
                        <i data-lucide="log-out" class="w-4 h-4 inline-block mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    // Function to toggle sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById("doctorSidebar");
        const overlay = document.getElementById("sidebarOverlay");
        if (sidebar) {
            sidebar.classList.toggle("open");
        }
        if (overlay) {
            overlay.classList.toggle("open");
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.group');
        if (dropdown && !dropdown.contains(e.target)) {
            const menu = dropdown.querySelector('.group-hover\\:visible');
            if (menu) {
                menu.classList.remove('opacity-100', 'visible');
                menu.classList.add('opacity-0', 'invisible');
            }
        }
    });
</script>