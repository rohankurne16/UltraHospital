<?php
session_start();
include "config/hospital.php";
include "config/permission.php";

// FIX: Check permission properly
checkPermission('staff-edit');

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $hid = (int)$_SESSION['hospital_id'];

    $fetch_sql = "SELECT * FROM staff
                  WHERE staff_id = '$id'
                  AND hospital_id = '$hid'
                  AND (delete_flag IS NULL OR delete_flag = 0)";
    $fetch_result = $conn->query($fetch_sql);
    
    if($fetch_result->num_rows > 0) {
        $staff_data = $fetch_result->fetch_assoc();
    } else {
        echo "<script>alert('Staff member not found'); window.location='staff.php';</script>";
        exit();
    }
}

if(isset($_POST['update'])) {
    $staff_id = $_POST['staff_id'];
    $name = $_POST['name'];
    $role = $_POST['role'];
 
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    $profile_image = $_POST['old_profile_image'];

    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){

        $folder = "documents/staff/images/";

        if(!file_exists($folder)){
            mkdir($folder,0777,true);
        }

        $image_name = basename($_FILES['profile_image']['name']);
        $image_path = $folder . $image_name;

        if(move_uploaded_file($_FILES['profile_image']['tmp_name'],$image_path)){

            // Delete old image
            if(!empty($_POST['old_profile_image'])){
                unlink($_POST['old_profile_image']);
            }

            $profile_image = $image_path;
        }
    }
    
    $update_sql = "UPDATE staff SET
    name = '$name',
    role = '$role',
    email = '$email',
    mobile = '$mobile',
    address = '$address',
    status = '$status',
    profile_image = '$profile_image',
    updated_at = CURRENT_TIMESTAMP()
    WHERE staff_id = '$staff_id'
    AND hospital_id = '$hospital_id'";
    
    if($conn->query($update_sql)) {
        echo "<script>alert('Staff member updated successfully'); window.location='staff.php';</script>";
    } else {
        echo "<script>alert('Error updating staff: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Staff - <?php echo $hospital['hospital_name'] ?></title>
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
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?> 

        <div class="flex flex-1 items-start">
          
                <?php include 'Sidebar.php'; ?> 
           

            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <div class="max-w-4xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                    
                        <a href="staff.php" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Update Staff</h1>
                            <p class="text-gray-500 text-sm">Edit staff member information.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <form action="update_staff.php" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 lg:p-10">
                            <input type="hidden" name="staff_id" value="<?php echo $staff_data['staff_id']; ?>">
                            <input type="hidden" name="old_profile_image" value="<?php echo $staff_data['profile_image']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" for="name">Full Name <span class="text-red-500">*</span></label>
                                    <input id="name" name="name" value="<?php echo htmlspecialchars($staff_data['name']); ?>" placeholder="Enter full name"
                                        class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" required>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" for="role">Role <span class="text-red-500">*</span></label>
                                    <select id="role" name="role"
                                        class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                        <option value="Nurse" <?php echo ($staff_data['role'] == 'Nurse') ? 'selected' : ''; ?>>Nurse</option>
                                        <option value="Receptionist" <?php echo ($staff_data['role'] == 'Receptionist') ? 'selected' : ''; ?>>Receptionist</option>
                                        <option value="Ward_Boy" <?php echo ($staff_data['role'] == 'Ward_Boy') ? 'selected' : ''; ?>>Ward Boy</option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" for="email">Email <span class="text-red-500">*</span></label>
                                    <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($staff_data['email']); ?>" placeholder="Enter email address"
                                        class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" required>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" for="mobile">Mobile Number <span class="text-red-500">*</span></label>
                                    <input id="mobile" name="mobile" value="<?php echo htmlspecialchars($staff_data['mobile']); ?>" placeholder="Enter mobile number"
                                        class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" for="status">Status</label>
                                    <select id="status" name="status"
                                        class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                        <option value="Active" <?php echo ($staff_data['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($staff_data['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="Suspended" <?php echo ($staff_data['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>

                                <div class="space-y-2 md:col-span-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" for="address">Address</label>
                                    <textarea id="address" name="address" placeholder="Enter address"
                                        class="w-full min-h-[100px] p-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all resize-none"><?php echo htmlspecialchars($staff_data['address']); ?></textarea>
                                </div>

                                <div class="space-y-4 md:col-span-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Profile Image</label>
                                    <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                        <?php if(!empty($staff_data['profile_image']) && file_exists($staff_data['profile_image'])): ?>
                                            <img src="<?php echo $staff_data['profile_image']; ?>" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md">
                                        <?php else: ?>
                                            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-2xl border-4 border-white shadow-md">
                                                <?php echo strtoupper(substr($staff_data['name'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1 w-full">
                                            <input type="file" name="profile_image" accept="image/*"
                                                class="w-full text-xs file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all">
                                            <p class="text-[10px] text-gray-400 font-medium mt-2">Leave empty to keep current image. Supported: JPG, PNG, GIF.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 flex flex-col sm:flex-row justify-end gap-4 border-t border-gray-50 pt-8">
                                <a href="staff.php" class="w-full sm:w-auto px-8 py-3 rounded-xl border border-gray-200 text-gray-500 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 transition text-center order-2 sm:order-1">Cancel</a>
                                <button type="submit" name="update" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2">Update Staff Details</button>
                            </div>
                        </form>
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
        });
    </script>
</body>
</html>