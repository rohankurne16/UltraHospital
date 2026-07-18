<?php
session_start();
include "config/hospital.php";

$hid=$_SESSION["hospital_id"];

// Initialize variables
$message = "";
$message_type = "";

// Fetch active departments for the dropdown
$department_query = mysqli_query($conn, "
    SELECT department_name
    FROM department
    WHERE status = 'Active'
    and hospital_id='$hid'
    AND (delete_flag = 0 OR delete_flag IS NULL)
    ORDER BY department_name ASC
");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $doctor_name = $_POST['doctor_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $department = $_POST['department'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $experience = $_POST['experience'];
    $consultation_fee = $_POST['consultation_fee'];
    $timing = $_POST['timing'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    // Image Upload Handling - FIXED
    $doctor_image = "";
    if (!empty($_FILES['doctor_image']['name']) && $_FILES['doctor_image']['error'] == 0) {
        // Create folder if not exists - use correct path from current directory
        $folder = "documents/doctors/images/";
        
        // Check if folder exists, if not create it
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        
        // Generate unique filename to avoid conflicts
        $image_name =basename($_FILES['doctor_image']['name']);
        $image_path = $folder . $image_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $image_path)) {
            
            $doctor_image = "documents/doctors/images/" . $image_name;
        } else {
            $message = "Failed to upload image. Please check folder permissions.";
            $message_type = "error";
        }
    }

    // Only proceed if no error or image upload success
    if (empty($message) || $message_type != "error") {
        // Use transactions for data integrity
        $conn->begin_transaction();

        try {
            // 1. Insert into register table
           ;
            $stmt_reg = $conn->prepare("INSERT INTO register (name, email, password, role, created_by, modified_by,hospital_id) VALUES (?, ?, ?, 'doctor', 'Admin', 'Admin',$hid)");
            $stmt_reg->bind_param("sss", $doctor_name, $email, $password);
            
            if ($stmt_reg->execute()) {
                $register_id = $conn->insert_id;

                // 2. Insert into doctor table - store only filename
                $stmt_doc = $conn->prepare("INSERT INTO doctor (register_id, doctor_name, doctor_image, mobile, email, department, qualification, specialization, experience, consultation_fee, timing, address, status,hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");
                $stmt_doc->bind_param("issssssssissss", $register_id, $doctor_name, $doctor_image, $mobile, $email, $department, $qualification, $specialization, $experience, $consultation_fee, $timing, $address, $status,$hid);
                
                if ($stmt_doc->execute()) {
                    $conn->commit();
                    header("Location: doctors.php?msg=Doctor added successfully");
                    exit();
                } else {
                    throw new Exception("Unable to Add Doctor details.");
                }
            } else {
                throw new Exception("Unable to Register user.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor - <?php echo $hospital['hospital_name'] ?></title> 
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
        .required-star { color: #ef4444; margin-left: 2px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50 ">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <div id="sidebar-container">
                <?php include 'Sidebar.php'; ?>
            </div>

            <!-- Main Content Area -->
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <button id="mobile-toggle" class="xl:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a href="doctors.php" class="p-2.5 border border-gray-200 rounded-xl hover:bg-white transition shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5 text-gray-500"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add New Doctor</h1>
                            <p class="text-gray-500 text-sm">Register a new medical professional with the required details.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="p-4 mb-8 rounded-2xl border <?php echo ($message_type == 'error') ? 'bg-red-50 text-red-700 border-red-100' : 'bg-green-50 text-green-700 border-green-100'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <div class="flex items-center gap-3">
                                <i data-lucide="<?php echo ($message_type == 'error') ? 'alert-circle' : 'check-circle'; ?>" class="w-5 h-5"></i>
                                <span class="text-sm font-semibold"><?php echo $message; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Container -->
                    <form action="add_doctor.php" method="POST" enctype="multipart/form-data">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-6 md:p-10 space-y-12">
                                
                                <!-- Basic Info Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                            <i data-lucide="user" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Basic Information</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Name<span class="required-star">*</span></label>
                                            <input name="doctor_name" placeholder="Dr. John Doe" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address<span class="required-star">*</span></label>
                                            <input name="email" type="email" placeholder="doctor@hospital.com" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mobile Number</label>
                                            <input name="mobile" placeholder="+1 234 567 890" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Password<span class="required-star">*</span></label>
                                            <input name="password" type="password" placeholder="Set secure password" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</label>
                                            <select name="status" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white">
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                                <option value="On Leave">On Leave</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Details Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                            <i data-lucide="briefcase" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Professional Details</h2>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Department <span class="required-star">*</span></label>
                                            <select name="department" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all bg-white" required>
                                                <option value="">-- Select Department --</option>
                                                <?php while($dept = mysqli_fetch_assoc($department_query)) { ?>
                                                    <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Specialization</label>
                                            <input name="specialization" placeholder="e.g. Interventional Cardiology" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Qualification</label>
                                            <input name="qualification" placeholder="e.g. MBBS, MD" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Experience (Years)</label>
                                            <input name="experience" type="number" placeholder="e.g. 10" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Consultation Fee (₹)</label>
                                            <input name="consultation_fee" type="number" placeholder="e.g. 500" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Available Timing</label>
                                            <input name="timing" placeholder="e.g. Mon-Fri, 9AM - 5PM" class="w-full h-12 px-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all">
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & Media Section -->
                                <div class="space-y-8">
                                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                                        <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                                        </div>
                                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Location & Media</h2>
                                    </div>
                                    
                                    <div class="space-y-8">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Address</label>
                                            <textarea name="address" placeholder="Enter complete clinic or residential address" class="w-full min-h-[100px] p-4 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all resize-none"></textarea>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Profile Photo</label>
                                            <div class="flex flex-col sm:flex-row items-center gap-6 p-6 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                                <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center text-gray-300 border-2 border-white shadow-md overflow-hidden">
                                                    <i data-lucide="camera" class="w-8 h-8"></i>
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <input type="file" name="doctor_image" accept="image/*" class="w-full text-xs file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all">
                                                    <p class="text-[10px] text-gray-400 font-medium mt-3 uppercase tracking-wider">Recommended: Square image, max 2MB (JPG, PNG)</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="px-6 md:px-10 py-8 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-4">
                                <button type="reset" class="w-full sm:w-auto px-8 py-3 rounded-xl border border-gray-200 text-gray-500 font-bold text-xs uppercase tracking-widest hover:bg-white transition text-center order-2 sm:order-1">Reset Form</button>
                                <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2">Register Doctor</button>
                            </div>
                        </div>
                    </form>
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
