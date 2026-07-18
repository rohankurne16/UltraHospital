<?php 
    session_start();
    include 'config/hospital.php'; 

    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../departments.php");
        exit();
    }

    $dept_id = $_GET['id'];

    $find_dept_id = "select * from department where id = '$dept_id' and delete_flag = 0";
    $dept_res = $conn->query($find_dept_id);
    $dept = $dept_res->fetch_assoc();

    if (!$dept) {
        echo "Department not found.";
        exit();
    }

    $dept_name = $dept['department_name'];
    $find_doctors = "select * from doctor where department = '$dept_name' and delete_flag = 0 order by doctor_name";
    $doctors_res = $conn->query($find_doctors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dept['department_name']; ?> Details - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col">
         <?php include('header.php') ?>
        
        <div class="flex flex-1 items-start">
            <div id="sidebar-container">
                <?php include('Sidebar.php') ?>
            </div>
            
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                        <div class="flex items-center gap-4">
                            <button id="mobile-toggle" class="xl:hidden">
                                <i class="fas fa-bars"></i>
                            </button>
                            <a href="departments.php" class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1"><?php echo $dept['department_name']; ?></h1>
                                <p class="text-gray-500 text-sm md:text-base">Department Overview and Staff Directory</p>
                            </div>
                        </div>
                        <div class="flex w-full md:w-auto">
                            <a href="edit_department.php?id=<?php echo $dept['id']; ?>" class="w-full md:w-auto bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 text-gray-700 dark:text-neutral-200 px-6 py-2.5 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                Edit Details
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8 mb-8">
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-6 shadow-sm">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-6">Department Info</h3>
                                <div class="space-y-6">
                                   
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Status</p>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $dept['status'] === 'Active' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'; ?>">
                                            <?php echo $dept['status']; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Description</p>
                                        <p class="text-sm text-gray-600 dark:text-neutral-400 leading-relaxed italic">
                                            "<?php echo $dept['description'] ?: 'No description provided for this department.'; ?>"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-sm overflow-hidden">
                                <div class="p-6 border-b dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <h2 class="text-xl font-bold">Medical Staff Directory</h2>
                                    <span class="text-[10px] font-bold bg-gray-100 dark:bg-neutral-800 px-3 py-1.5 rounded-full text-gray-500 uppercase tracking-wider w-fit">
                                        <?php echo $doctors_res->num_rows; ?> Doctors Assigned
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left min-w-[400px]">
                                        <thead class="bg-gray-50 dark:bg-neutral-800/50">
                                            <tr class="text-[10px] font-bold uppercase text-gray-400 dark:text-neutral-500 tracking-widest">
                                                <th class="p-4">Doctor Name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y dark:divide-neutral-800">
                                            <?php if ($doctors_res->num_rows > 0): ?>
                                                <?php while($doc = $doctors_res->fetch_assoc()): ?>
                                                <tr class="text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/30 transition-colors cursor-pointer" onclick="window.location.href='view_doctor.php?id=<?php echo $doc['doctor_id']; ?>'">
                                                    <td class="p-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 flex-shrink-0">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="font-bold truncate"> <?php echo $doc['doctor_name']; ?></p>
                                                                <p class="text-xs text-gray-500 truncate"><?php echo $doc['specialization'] ?: 'General Practitioner'; ?></p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td class="p-12 text-center text-gray-500 italic">
                                                        No doctors are currently assigned to this department.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>
