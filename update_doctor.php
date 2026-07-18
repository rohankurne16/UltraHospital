<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$message = "";
$error = "";

if ($doctor_id <= 0) {
    header("Location: update_doctor.php");
    exit();
}

$sql = "SELECT * FROM doctor
        WHERE doctor_id='$doctor_id'
        AND (delete_flag=0 OR delete_flag IS NULL)";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: update_doctor.php");
    exit();
}
$doctor = $result->fetch_assoc();

$register_id = $doctor['register_id'];
$register_sql = "SELECT * FROM register WHERE id = '$register_id'";
$register_result = $conn->query($register_sql);
$register = $register_result->fetch_assoc();

$doctor_image = $doctor['doctor_image'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    
    // Handle image upload
    if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] == 0 && !empty($_FILES['doctor_image']['name'])) {
        $folder = "documents/doctors/images/";
        
        // Create folder if not exists
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        
        $image_name =  basename($_FILES['doctor_image']['name']);
        $image_path = $folder . $image_name;
        
        if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $image_path)) {
            $doctor_image = "documents/doctors/images/" . $image_name;
        } else {
            $error = "Failed to upload image.";
        }
    }

    // Get form data
    $doctor_name = mysqli_real_escape_string($conn, $_POST['newdoctor_name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['newmobile']);
    $email = mysqli_real_escape_string($conn, $_POST['newemail']);
    $department = mysqli_real_escape_string($conn, $_POST['newdepartment']);
    $qualification = mysqli_real_escape_string($conn, $_POST['newqualification']);
    $specialization = mysqli_real_escape_string($conn, $_POST['newspecialization']);
    $experience = mysqli_real_escape_string($conn, $_POST['newexperience']);
    $consultation_fee = mysqli_real_escape_string($conn, $_POST['newconsultation_fee']);
    $timing = mysqli_real_escape_string($conn, $_POST['newtiming']);
    $address = mysqli_real_escape_string($conn, $_POST['newaddress']);
    $status = mysqli_real_escape_string($conn, $_POST['newstatus']);
    
    // Update doctor
    $sql = "UPDATE doctor SET 
            doctor_name='$doctor_name',
            doctor_image='$doctor_image', 
            mobile='$mobile', 
            email='$email', 
            department='$department', 
            qualification='$qualification', 
            specialization='$specialization', 
            experience='$experience', 
            consultation_fee='$consultation_fee', 
            timing='$timing', 
            address='$address', 
            status='$status' 
            WHERE doctor_id='$id'";
            
    if (mysqli_query($conn, $sql)) {
        // Update register table
        $sql2 = "UPDATE register SET name='$doctor_name', email='$email', modified_by='admin' WHERE id='$register_id'";
        if (mysqli_query($conn, $sql2)) {
            echo "<script>
                alert('Doctor updated successfully!');
                window.location.href='view_doctor.php?id=$id';
            </script>";
            exit();
        } else {
            $error = "Error updating register table: " . mysqli_error($conn);
        }
    } else {
        $error = "Error updating doctor: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - <?php echo $hospital['hospital_name'] ?></title>

    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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

        .form-card { 
            background: white; 
            border-radius: 20px; 
            border: 1px solid #e5e7eb; 
            overflow: hidden; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
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
            transition: all 0.2s ease; 
            text-decoration: none; 
            flex-shrink: 0;
        }
        .back-btn:hover { 
            background: #f3f4f6; 
            border-color: #d1d5db; 
        }

        .image-preview-wrapper { 
            position: relative; 
            width: 140px; 
            height: 140px; 
            border-radius: 50%; 
            overflow: hidden; 
            border: 4px solid #fff; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            background: #f8fafc; 
        }
        
        .image-placeholder { 
            width: 100%; 
            height: 100%; 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 48px; 
            font-weight: 700; 
            color: white; 
            text-transform: uppercase; 
        }

        .form-input {
            width: 100%; 
            padding: 12px 16px; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 12px; 
            font-size: 14px; 
            transition: all 0.2s ease; 
            outline: none; 
            background: white; 
            color: #0f172a; 
        }
        .form-input:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1); 
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
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
                <div class="max-w-5xl mx-auto w-full space-y-6">
                    <!-- Page Header -->
                    <div class="flex items-center gap-4">
                        <button id="mobile-toggle" class="xl:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a href="view_doctor.php?id=<?php echo $doctor_id; ?>" class="back-btn shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Doctor</h1>
                            <p class="text-gray-500 text-sm">Update information for <?php echo htmlspecialchars($doctor['doctor_name']); ?></p>
                        </div>
                    </div>

                    <div class="form-card w-full">
                        <!-- Card Header -->
                        <div class="p-6 md:p-8 border-b border-gray-100 bg-gray-50/50 flex items-center gap-4">
                            <div class="h-12 w-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shadow-sm">
                                <i class="fas fa-user-md text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 tracking-tight">Doctor Details</h3>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Update professional information</p>
                            </div>
                        </div>

                        <div class="p-6 md:p-10">
                            <?php if ($message): ?>
                                <div class="p-4 mb-8 bg-green-50 border border-green-100 text-green-700 rounded-2xl flex items-center gap-3">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="text-sm font-semibold"><?php echo $message; ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($error)): ?>
                                <div class="p-4 mb-8 bg-red-50 border border-red-100 text-red-700 rounded-2xl flex items-center gap-3">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span class="text-sm font-semibold"><?php echo $error; ?></span>
                                </div>
                            <?php endif; ?>

                            <form action="" method="POST" enctype="multipart/form-data" class="space-y-10">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                                
                                <!-- Profile Image Section -->
                                <div class="flex flex-col items-center justify-center space-y-6 pb-8 border-b border-gray-50">
                                    <div class="image-preview-wrapper" id="imageWrapper">
                                        <?php if (!empty($doctor['doctor_image'])): ?>
                                            <img src="<?php echo $doctor['doctor_image']; ?>" class="w-full h-full object-cover" id="imagePreview">
                                        <?php else: 
                                            $name_parts = explode(' ', $doctor['doctor_name']);
                                            $initials = '';
                                            foreach ($name_parts as $part) { $initials .= strtoupper(substr($part, 0, 1)); }
                                        ?>
                                            <div class="image-placeholder"><?php echo substr($initials, 0, 2); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-col items-center gap-3">
                                        <label class="cursor-pointer bg-white border border-gray-200 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                                            <i class="fas fa-camera"></i> Change Photo
                                            <input type="file" name="doctor_image" accept="image/*" class="hidden" onchange="previewImage(event)">
                                        </label>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Recommended: JPG, PNG (Max 5MB)</p>
                                    </div>
                                </div>

                                <!-- Form Fields Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Name</label>
                                        <input type="text" name="newdoctor_name" value="<?php echo htmlspecialchars($doctor['doctor_name']); ?>" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address</label>
                                        <input type="email" name="newemail" value="<?php echo htmlspecialchars($doctor['email']); ?>" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mobile Number</label>
                                        <input type="tel" name="newmobile" value="<?php echo htmlspecialchars($doctor['mobile']); ?>" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Department</label>
                                        <input type="text" name="newdepartment" value="<?php echo htmlspecialchars($doctor['department']); ?>" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Qualification</label>
                                        <input type="text" name="newqualification" value="<?php echo htmlspecialchars($doctor['qualification']); ?>" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Specialization</label>
                                        <input type="text" name="newspecialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Experience (Years)</label>
                                        <input type="number" name="newexperience" value="<?php echo htmlspecialchars($doctor['experience']); ?>" min="0" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Consultation Fee (₹)</label>
                                        <input type="number" name="newconsultation_fee" value="<?php echo htmlspecialchars($doctor['consultation_fee']); ?>" step="0.01" min="0" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Consultation Timing</label>
                                        <input type="text" name="newtiming" value="<?php echo htmlspecialchars($doctor['timing']); ?>" placeholder="e.g., 9:00 AM - 6:00 PM" class="form-input">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</label>
                                        <select name="newstatus" class="form-input bg-white">
                                            <option value="Active" <?php echo ($doctor['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($doctor['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Address</label>
                                    <textarea name="newaddress" rows="3" class="form-input resize-none"><?php echo htmlspecialchars($doctor['address']); ?></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-8 border-t border-gray-50">
                                    <a href="view_doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="w-full sm:w-auto text-center px-8 py-3 rounded-xl border border-gray-200 text-xs font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition order-2 sm:order-1">
                                        <i class="fas fa-times mr-2"></i> Cancel
                                    </a>
                                    <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-10 py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition order-1 sm:order-2">
                                        <i class="fas fa-save mr-2"></i> Update Doctor
                                    </button>
                                </div>
                            </form>
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
        });

        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imageWrapper').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" id="imagePreview">`;
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>
