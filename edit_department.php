<?php 
    session_start();
    include 'config/hospital.php'; 

    if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
        header("Location:auth/logout.php");
        exit();
    }

    $hid = $_SESSION["hospital_id"];

    $message = "";
    $messageType = "";

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: departments.php");
        exit();
    }

    $dept_id = mysqli_real_escape_string($conn, $_GET['id']);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $dept_name = mysqli_real_escape_string($conn, $_POST['department_name']);
       
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if (empty($dept_name)) {
            $message = "Department name is required!";
            $messageType = "error";
        } else {
            $update_sql = "update department set department_name = '$dept_name', description = '$description', status = '$status' where id = '$dept_id' and hospital_id = '$hid'";
            if ($conn->query($update_sql) === TRUE) {
                $message = "Department updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
        }
    }
    $find_dept_id = "select * from department where id = '$dept_id' and hospital_id = '$hid' and delete_flag = 0";
    $dept_res = $conn->query($find_dept_id);
    $dept = $dept_res->fetch_assoc();

    if (!$dept) {
        echo "Department not found.";
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department - <?php echo $hospital['hospital_name'] ?></title>
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
                <div class="max-w-4xl mx-auto">
                    
                    <div class="mb-8 flex items-center gap-4">
                        <button id="mobile-toggle" class="xl:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a href="departments.php" class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </a>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Edit Department</h1>
                            <p class="text-gray-500 text-sm md:text-base">Update the details for the <?php echo $dept['department_name']; ?> department.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border <?php echo $messageType === 'success' ? 'bg-green-50 border-green-100 text-green-700 dark:bg-green-900/10 dark:border-green-900/20 dark:text-green-400' : 'bg-red-50 border-red-100 text-red-700 dark:bg-red-900/10 dark:border-red-900/20 dark:text-red-400'; ?>">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="<?php echo $messageType === 'success' ? 'm9 12 2 2 4-4' : 'm15 9-6 6M9 9l6 6'; ?>"/></svg>
                            <p class="font-bold text-sm md:text-base"><?php echo $message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-sm overflow-hidden">
                        <form action="edit_department.php?id=<?php echo $dept_id; ?>" method="POST" class="p-6 md:p-8 lg:p-12">
                            <div class="grid grid-cols-1 gap-6 md:gap-8">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                    <div class="space-y-2">
                                        <label for="department_name" class="text-xs font-bold uppercase tracking-widest text-gray-400">Department Name <span class="text-red-500">*</span></label>
                                        <input type="text" id="department_name" name="department_name" value="<?php echo $dept['department_name']; ?>" required placeholder="e.g. Cardiology" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm md:text-base">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="status" class="text-xs font-bold uppercase tracking-widest text-gray-400">Status</label>
                                        <select id="status" name="status" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm md:text-base">
                                            <option value="Active" <?php echo $dept['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $dept['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label for="description" class="text-xs font-bold uppercase tracking-widest text-gray-400">Description</label>
                                    <textarea id="description" name="description" rows="5" placeholder="Describe the department's scope..." class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none text-sm md:text-base"><?php echo $dept['description']; ?></textarea>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-4 border-t dark:border-neutral-800">
                                    <a href="departments.php" class="text-gray-500 hover:text-gray-700 font-bold text-sm order-2 sm:order-1">Cancel</a>
                                    <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20 order-1 sm:order-2">
                                        Update Department
                                    </button>
                                </div>

                            </div>
                        </form>
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
