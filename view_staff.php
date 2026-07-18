<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Get staff details with register information
    $view_staff = "SELECT s.*, r.reg_date 
                   FROM staff s 
                   LEFT JOIN register r ON s.email = r.email 
                   WHERE s.staff_id='$id' AND (s.delete_flag=0 OR s.delete_flag IS NULL)";
    $data = $conn->query($view_staff);

    if ($data && $data->num_rows > 0) {
        $row = $data->fetch_assoc();

        $staff_id = $row['staff_id'];
        $name = $row['name'];
        $image = $row['profile_image'];
    
        $staffrole = $row['role'];

        $email = $row['email'];
        $mobile = $row['mobile'];
        $address = $row['address'];
        $status = isset($row['status']) ? $row['status'] : 'Active';
        $created_at = isset($row['created_at']) ? $row['created_at'] : '';
        $reg_date = isset($row['reg_date']) ? $row['reg_date'] : $created_at;

        // Determine status class
        if ($status == 'Active') {
            $status_class = 'status-active';
        } elseif ($status == 'Suspended') {
            $status_class = 'status-suspended';
        } else {
            $status_class = 'status-inactive';
        }

        // Get role class for badge
        $role_class = 'role-' . strtolower(str_replace(' ', '_', $staffrole));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Staff Profile</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Sidebar and Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }

        /* Mobile Sidebar behavior */
        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            #sidebar-container.active {
                transform: translateX(0);
            }
            #main-content {
                margin-left: 0 !important;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Desktop Sidebar behavior */
        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 256px;
            }
        }

        #mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            cursor: pointer;
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef3c7; color: #b45309; }
        .status-suspended { background: #fecaca; color: #991b1b; }
        
        .tab-btn {
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        .tab-btn:hover {
            background: #f9fafb;
        }

        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .info-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: 40%;
        }
        .info-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .role-admin { background: #e0e7ff; color: #3730a3; }
        .role-doctor { background: #d1fae5; color: #065f46; }
        .role-nurse { background: #fef3c7; color: #92400e; }
        .role-receptionist { background: #fce4ec; color: #c62828; }
        .role-ward_boy { background: #e8eaf6; color: #283593; }
        .role-patient { background: #e3f2fd; color: #0d47a1; }
        
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?> 

        <div class="flex flex-1 items-start">
            <div id="sidebar-container">
                <?php include 'Sidebar.php'; ?> 
            </div>

            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <button id="mobile-toggle" class="xl:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a href="staff.php" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Staff Profile</h1>
                            <p class="text-gray-500 text-sm">View and manage staff member details.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                        <!-- Left Column - Profile Card -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="p-6 md:p-8 flex flex-col items-center text-center border-b border-gray-50">
                                    <?php 
                                        $image_path = $image;
                                        if (!empty($image_path) && file_exists($image_path)): 
                                    ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md" alt="Profile Image">
                                    <?php elseif (!empty($image_path) && file_exists("../" . $image_path)): ?>
                                        <img src="../<?php echo htmlspecialchars($image_path); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md" alt="Profile Image">
                                    <?php else: ?>
                                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl border-4 border-white shadow-md">
                                            <?php echo strtoupper(substr($name, 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <h2 class="text-xl font-bold text-gray-900 mt-4"><?php echo htmlspecialchars($name); ?></h2>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Staff ID: #<?php echo $staff_id; ?></p>
                                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
                                        <span class="role-badge <?php echo $role_class; ?>"><?php echo htmlspecialchars($staffrole); ?></span>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-500">
                                            <i data-lucide="mail" class="w-4 h-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Email Address</p>
                                            <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($email); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-500">
                                            <i data-lucide="phone" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Phone Number</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($mobile ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-purple-50 rounded-lg text-purple-500">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Address</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($address ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50/50 flex flex-col sm:flex-row gap-3">
                                    <button onclick="window.location.href='update_staff.php?id=<?php echo $staff_id; ?>'" class="flex-1 bg-white border border-gray-200 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-gray-50 transition shadow-sm">
                                        <i data-lucide="edit-2" class="w-3.5 h-3.5 inline mr-1.5"></i> Edit Profile
                                    </button>
                                    <button onclick="window.location.href='mailto:<?php echo $email; ?>'" class="flex-1 bg-blue-600 text-white px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-blue-700 transition shadow-lg shadow-blue-500/20">
                                        <i data-lucide="message-square" class="w-3.5 h-3.5 inline mr-1.5"></i> Message
                                    </button>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-4">Account Information</h3>
                                <table class="info-table">
                                    <tr>
                                        <td class="info-label">Current Role</td>
                                        <td class="info-value"><?php echo htmlspecialchars($staffrole); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="info-label">Account Status</td>
                                        <td class="info-value">
                                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="info-label">Unique Staff ID</td>
                                        <td class="info-value">#<?php echo $staff_id; ?></td>
                                    </tr>
                                    <?php if(!empty($reg_date)): ?>
                                    <tr>
                                        <td class="info-label">Join Date</td>
                                        <td class="info-value"><?php echo date('M d, Y', strtotime($reg_date)); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- Right Column - Tabs -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="flex border-b border-gray-100 overflow-x-auto custom-scrollbar bg-gray-50/30">
                                    <button id="overviewBtn" onclick="showTab('overview')" class="tab-btn px-6 py-4 border-b-2 border-blue-600 text-blue-600 font-bold text-xs uppercase tracking-widest">
                                        <i data-lucide="user" class="w-4 h-4 inline mr-2"></i> Overview
                                    </button>
                                    <button id="activityBtn" onclick="showTab('activity')" class="tab-btn px-6 py-4 text-gray-400 font-bold text-xs uppercase tracking-widest">
                                        <i data-lucide="clock" class="w-4 h-4 inline mr-2"></i> Activity Log
                                    </button>
                                    <button id="documentsBtn" onclick="showTab('documents')" class="tab-btn px-6 py-4 text-gray-400 font-bold text-xs uppercase tracking-widest">
                                        <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i> Documents
                                    </button>
                                </div>

                                <div class="p-6 md:p-8">
                                    <!-- Overview Tab -->
                                    <div id="overview" class="tab-content active">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
                                            <div class="space-y-6">
                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center">
                                                    <i data-lucide="info" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                    Personal Information
                                                </h4>
                                                <table class="info-table">
                                                    <tr>
                                                        <td class="info-label">Full Name</td>
                                                        <td class="info-value"><?php echo htmlspecialchars($name); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Email</td>
                                                        <td class="info-value truncate max-w-[150px]"><?php echo htmlspecialchars($email); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Phone</td>
                                                        <td class="info-value"><?php echo htmlspecialchars($mobile ?: 'Not provided'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Address</td>
                                                        <td class="info-value"><?php echo htmlspecialchars($address ?: 'Not provided'); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="space-y-6">
                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center">
                                                    <i data-lucide="briefcase" class="w-4 h-4 mr-2 text-indigo-500"></i>
                                                    Employment Details
                                                </h4>
                                                <table class="info-table">
                                                    <tr>
                                                        <td class="info-label">Designation</td>
                                                        <td class="info-value"><?php echo htmlspecialchars($staffrole); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Staff ID</td>
                                                        <td class="info-value">#<?php echo $staff_id; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Current Status</td>
                                                        <td class="info-value">
                                                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
                                                        </td>
                                                    </tr>
                                                    <?php if(!empty($reg_date)): ?>
                                                    <tr>
                                                        <td class="info-label">Join Date</td>
                                                        <td class="info-value">
                                                            <?php echo date('M d, Y', strtotime($reg_date)); ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Activity Log Tab -->
                                    <div id="activity" class="tab-content">
                                        <h3 class="text-lg font-bold text-gray-900 mb-6">Activity Log</h3>
                                        <div class="text-center py-16 text-gray-400 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                            <i data-lucide="clock" class="w-12 h-12 mx-auto text-gray-200 mb-4"></i>
                                            <p class="text-sm font-medium">No activity records found for this staff member.</p>
                                        </div>
                                    </div>

                                    <!-- Documents Tab -->
                                    <div id="documents" class="tab-content">
                                        <h3 class="text-lg font-bold text-gray-900 mb-6">Staff Documents</h3>
                                        <div class="text-center py-16 text-gray-400 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                            <i data-lucide="file-text" class="w-12 h-12 mx-auto text-gray-200 mb-4"></i>
                                            <p class="text-sm font-medium">No documents have been uploaded yet.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebarContainer = document.getElementById('sidebar-container');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            function openSidebar() {
                sidebarContainer.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

            // Handle close button inside Sidebar.php
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
        });

        function showTab(tab){
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(function(content){
                content.classList.remove('active');
            });

            // Remove active styles from all tabs
            document.querySelectorAll('.tab-btn').forEach(function(btn){
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('text-gray-400');
            });

            // Show selected tab
            const selectedTab = document.getElementById(tab);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Add active styles to selected tab
            const activeBtn = document.getElementById(tab + 'Btn');
            if (activeBtn) {
                activeBtn.classList.add('border-blue-600', 'text-blue-600');
                activeBtn.classList.remove('text-gray-400');
            }
        }

        // Set default tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure overview is visible by default
            const overviewTab = document.getElementById('overview');
            if (overviewTab) {
                overviewTab.classList.add('active');
            }
        });
    </script>
</body>
</html>

<?php
    } else {
        echo "<script>
            alert('Staff member not found!');
            window.location.href='staff.php';
        </script>";
        exit();
    }
} else {
    header("Location: staff.php");
    exit();
}
?>
