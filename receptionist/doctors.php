<?php
session_start();

$hid = (int)$_SESSION['hospital_id'];

include "../config/hospital.php";

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

// ============================================================
// AJAX REQUEST HANDLER – returns JSON for search
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search = mysqli_real_escape_string($conn, $search);

    if ($search !== '') {
        $sql = "SELECT * FROM doctor 
                WHERE (doctor_name LIKE '%$search%' OR department LIKE '%$search%') 
                AND hospital_id = '$hid' 
                AND status = 'Active' 
                AND (delete_flag = 0 OR delete_flag IS NULL) 
                ORDER BY created_at DESC";
    } else {
        $sql = "SELECT * FROM doctor 
                WHERE hospital_id = '$hid' 
                AND status = 'Active' 
                AND (delete_flag = 0 OR delete_flag IS NULL) 
                ORDER BY created_at DESC";
    }

    $results = mysqli_query($conn, $sql);
    if (!$results) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($conn)]);
        exit;
    }

    $html = '';
    $total = 0;
    if ($results->num_rows > 0) {
        while ($row = $results->fetch_assoc()) {
            $doctor_id = $row['doctor_id'];
            $name = $row['doctor_name'];
            $image = $row['doctor_image'];
            $specialization = $row['specialization'];
            $status = $row['status'];
            $department = $row['department'];
            $experience = $row['experience'];
            $mobile = $row['mobile'];
            $email = $row['email'];
            $status_class = ($status == "Active") ? "status-active" : "status-inactive";

            $html .= '<tr class="border-b border-gray-50 hover:bg-gray-50/50 transition fade-in doctor-row" data-doctor-id="' . $doctor_id . '" data-name="' . strtolower($name) . '" data-dept="' . strtolower($department) . '">';
            $html .= '<td class="px-6 py-4 align-middle"><div class="flex items-center gap-4">';
            if (!empty($image) && file_exists($image)) {
                $html .= '<img src="' . $image . '" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">';
            } else {
                $initials = strtoupper(substr($name, 0, 2));
                $html .= '<div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs border-2 border-white shadow-sm">' . $initials . '</div>';
            }
            $html .= '<div><p class="font-bold text-gray-900">' . htmlspecialchars($name) . '</p></div>';
            $html .= '</div></td>';
            $html .= '<td class="px-6 py-4 align-middle text-gray-600 font-medium">' . htmlspecialchars($specialization) . '</td>';
            $html .= '<td class="px-6 py-4 align-middle"><span class="status-badge ' . $status_class . '">' . $status . '</span></td>';
            $html .= '<td class="px-6 py-4 align-middle text-gray-600 font-medium">' . htmlspecialchars($department) . '</td>';
            $html .= '<td class="px-6 py-4 align-middle text-gray-600 font-medium">' . $experience . ' Years</td>';
            $html .= '<td class="px-6 py-4 align-middle">';
            $html .= '<div class="text-gray-900 font-bold">' . htmlspecialchars($mobile) . '</div>';
            $html .= '<div class="text-[10px] text-gray-400 font-medium truncate max-w-[150px]">' . htmlspecialchars($email) . '</div>';
            $html .= '</td>';
            $html .= '</tr>';
            $total++;
        }
    } else {
        $html .= '<tr id="noResultsRow">';
        $html .= '<td colspan="6" class="p-20 text-center text-gray-400">';
        $html .= '<div class="flex flex-col items-center gap-4">';
        $html .= '<div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-200 border border-dashed border-gray-200">';
        $html .= '<i data-lucide="user-x" class="w-10 h-10"></i>';
        $html .= '</div><div>';
        $html .= '<p class="font-bold text-gray-900 uppercase tracking-widest text-xs">No Active Doctors Found</p>';
        $html .= '<p class="text-xs font-medium mt-2 max-w-xs mx-auto">';
        if ($search !== '') {
            $html .= 'No results matching "' . htmlspecialchars($search) . '". Try a different search term.';
        } else {
            $html .= 'No active doctors registered yet.';
        }
        $html .= '</p></div>';
        if ($search !== '') {
            $html .= '<a href="doctors.php" class="text-blue-600 hover:underline text-xs font-bold uppercase tracking-widest">Clear search</a>';
        }
        $html .= '</div></td></tr>';
    }

    echo json_encode(['success' => true, 'html' => $html, 'total' => $total]);
    exit;
}

// ============================================================
// NORMAL PAGE LOAD – show full HTML with initial data
// ============================================================
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) != "") {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT * FROM doctor 
            WHERE (doctor_name LIKE '%$search%' OR department LIKE '%$search%') 
            AND hospital_id = '$hid' 
            AND status = 'Active' 
            AND (delete_flag = 0 OR delete_flag IS NULL) 
            ORDER BY created_at DESC";
} else {
    $sql = "SELECT * FROM doctor 
            WHERE hospital_id = '$hid' 
            AND status = 'Active' 
            AND (delete_flag = 0 OR delete_flag IS NULL) 
            ORDER BY created_at DESC";
}

$results = mysqli_query($conn, $sql);
$total_doctors = $results ? $results->num_rows : 0;
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1' />
    <title><?php echo $hospital['hospital_name'] ?> - Doctors List</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
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

        .action-icons { display: flex; align-items: center; justify-content: flex-end; gap: 4px; }
        .action-icon { display: inline-flex; align-items: center; justify-content: center; padding: 6px; border-radius: 8px; transition: all 0.2s; background: transparent; }
        .action-icon svg { width: 18px; height: 18px; }
        .action-icon.view-icon:hover { background: #eff6ff; }
        .action-icon.view-icon svg { color: #3b82f6; }
        .action-icon.edit-icon:hover { background: #f5f3ff; }
        .action-icon.edit-icon svg { color: #8b5cf6; }
        .action-icon.delete-icon:hover { background: #fef2f2; }
        .action-icon.delete-icon svg { color: #ef4444; }
        
        .status-badge { display: inline-block; padding: 4px 14px; border-radius: 9999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef3c7; color: #b45309; }
        
        .search-box { width: 100%; max-width: 380px; border-radius: 12px; border: 1px solid #030812; background: white; }
        
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
     
        .table-row-hidden { display: none !important; }
        .table-row-visible { animation: fadeIn 0.3s ease forwards; }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>

<body class='bg-gray-50 text-gray-900'>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class='flex min-h-screen flex-col bg-gray-50 '>
        <?php include '../header.php'; ?> 
        
        <div class='flex flex-1 items-start'>
            <?php include '../Sidebar.php'; ?>

            <main id="main-content" class='flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full'>
                <div class='max-w-7xl mx-auto w-full space-y-6'>
                    
                    <!-- Page Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <a href="dashboard.php" class="p-2.5 border border-gray-200 rounded-xl hover:bg-white transition shadow-sm">
                                <i data-lucide="arrow-left" class="w-5 h-5 text-gray-500"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Doctors</h1>
                                <p class="text-gray-500 text-sm">Manage medical professionals and schedules.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Table Container -->
                    <div class='rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden'>
                        <div class='p-6 border-b border-gray-50 bg-gray-50/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4'>
                            <div>
                                <h2 class='text-lg font-bold text-gray-900'>Doctors Directory</h2>
                                <p class='text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1'>Active medical professionals</p>
                            </div>
                          
                            <!-- Search input – uses AJAX -->
                            <div class="w-full md:w-auto">
                                <div class="relative search-box">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                                    </div>
                                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name or department..." class="block w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm outline-none transition-all" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class='overflow-x-auto custom-scrollbar'>
                            <table class='w-full text-sm' id="doctorsTable">
                                <thead>
                                    <tr class="border-b border-gray-50 bg-gray-50/50">
                                        <th class="h-12 px-6 text-left font-bold text-gray-400 text-[10px] uppercase tracking-widest">Doctor Name</th>
                                        <th class="h-12 px-6 text-left font-bold text-gray-400 text-[10px] uppercase tracking-widest">Specialization</th>
                                        <th class="h-12 px-6 text-left font-bold text-gray-400 text-[10px] uppercase tracking-widest">Status</th>
                                        <th class="h-12 px-6 text-left font-bold text-gray-400 text-[10px] uppercase tracking-widest">Department</th>
                                        <th class="h-12 px-6 text-left font-bold text-gray-400 text-[10px] uppercase tracking-widest">Experience</th>
                                        <th class="h-12 px-6 text-left font-bold text-gray-400 text-[10px] uppercase tracking-widest">Contact Info</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <?php
                                    if ($results && $results->num_rows > 0) {
                                        while ($row = $results->fetch_assoc()) {
                                            $doctor_id = $row['doctor_id'];
                                            $name = $row['doctor_name'];
                                            $image = $row['doctor_image'];
                                            $specialization = $row['specialization'];
                                            $status = $row['status'];
                                            $department = $row['department'];
                                            $experience = $row['experience'];
                                            $mobile = $row['mobile'];
                                            $email = $row['email'];
                                            $status_class = ($status == "Active") ? "status-active" : "status-inactive";
                                    ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition fade-in doctor-row" data-doctor-id="<?php echo $doctor_id; ?>" data-name="<?php echo strtolower($name); ?>" data-dept="<?php echo strtolower($department); ?>">
                                        <td class="px-6 py-4 align-middle">
                                            <div class="flex items-center gap-4">
                                                <?php 
                                                    if (!empty($image) && file_exists("../" . $image)): 
                                                ?>
                                                    <img src="../<?php echo $image; ?>" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs border-2 border-white shadow-sm">
                                                        <?php echo strtoupper(substr($name, 0, 2)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <p class="font-bold text-gray-900"><?php echo $name; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-middle text-gray-600 font-medium"><?php echo $specialization; ?></td>
                                        <td class="px-6 py-4 align-middle">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 align-middle text-gray-600 font-medium"><?php echo $department; ?></td>
                                        <td class="px-6 py-4 align-middle text-gray-600 font-medium"><?php echo $experience; ?> Years</td>
                                        <td class="px-6 py-4 align-middle">
                                            <div class="text-gray-900 font-bold"><?php echo $mobile; ?></div>
                                            <div class="text-[10px] text-gray-400 font-medium truncate max-w-[150px]"><?php echo $email; ?></div>
                                        </td>
                                    </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <tr id="noResultsRow">
                                        <td colspan="6" class="p-20 text-center text-gray-400">
                                            <div class="flex flex-col items-center gap-4">
                                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-200 border border-dashed border-gray-200">
                                                    <i data-lucide="user-x" class="w-10 h-10"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-900 uppercase tracking-widest text-xs">No Active Doctors Found</p>
                                                    <p class="text-xs font-medium mt-2 max-w-xs mx-auto">
                                                        <?php echo !empty($search) ? "No results matching \"" . htmlspecialchars($search) . "\". Try a different search term." : "No active doctors registered yet."; ?>
                                                    </p>
                                                </div>
                                                <?php if (!empty($search)): ?>
                                                    <a href="doctors.php" class="text-blue-600 hover:underline text-xs font-bold uppercase tracking-widest">Clear search</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Table Footer -->
                        <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div id="rowCountContainer" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                Showing <span class="text-gray-900" id="rowCount"><?php echo $total_doctors; ?></span> active medical professional<?php echo $total_doctors > 1 ? 's' : ''; ?>
                                <?php if (!empty($search)): ?>
                                    matching "<span class="text-gray-900"><?php echo htmlspecialchars($search); ?></span>"
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
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

            // ============================================================
            // AJAX SEARCH – single file (doctors.php?ajax=1)
            // ============================================================
            const searchInput = document.getElementById('search');
            const tableBody = document.getElementById('tableBody');
            const rowCountSpan = document.getElementById('rowCount');
            let searchTimeout = null;

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();

                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                if (searchTerm === '') {
                    fetchDoctors('');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetchDoctors(searchTerm);
                }, 300);
            });

            function fetchDoctors(searchTerm) {
                const url = 'doctors.php?ajax=1&search=' + encodeURIComponent(searchTerm);

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            tableBody.innerHTML = data.html;
                            rowCountSpan.textContent = data.total;
                            lucide.createIcons(); // re-init icons for new rows
                        } else {
                            console.error('Search error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('AJAX error:', error);
                    });
            }
        });
    </script>
</body>
</html>