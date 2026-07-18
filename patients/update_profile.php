<?php

 session_start(); 
include "../config/hospital.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $id = $_POST['newpatient_id'];
    $get = "SELECT * FROM patients WHERE patient_id='$id'";
    $res = mysqli_query($conn, $get);
    $patient = mysqli_fetch_assoc($res);
    $register_id = $patient['register_id'];
    $patient_image = $patient['patient_image'];

    if (!empty($_FILES['newpatient_image']['name'])) {

        $filename =  $_FILES['newpatient_image']['name'];

        $patient_image = "documents/patients/images/" . $filename;

        move_uploaded_file(
            $_FILES['newpatient_image']['tmp_name'],
            "../" . $patient_image
        );
    }
    $patient_name       = $_POST['newpatient_name'];
    $dob                = $_POST['newdob'];
    $age                = $_POST['newage'];
    $blood_group        = $_POST['newblood_group'];
    $gender             = $_POST['newgender'];
    $address            = mysqli_real_escape_string($conn, $_POST['newaddress']);
    $mobile             = $_POST['newmobile'];
    $email              = $_POST['newemail'];
    $emergency_contact  = $_POST['newemergency_contact'];
    $medical_history    = mysqli_real_escape_string($conn, $_POST['newmedical_history']);
    $allergy            = mysqli_real_escape_string($conn, $_POST['newallergy']);
    $status             = $_POST['newstatus'];

    $sql = "UPDATE patients SET patient_name='$patient_name', patient_image='$patient_image', date_of_birth='$dob', age='$age', blood_group='$blood_group', gender='$gender', address='$address', mobile='$mobile', email='$email', emergency_contact='$emergency_contact',medical_history='$medical_history', allergy='$allergy', status='$status' WHERE patient_id='$id'";

    if (mysqli_query($conn, $sql)) {
       
     $sql2 = "update register set name='$patient_name', email='$email', modified_by='Patient' where id='$register_id'";

        if(mysqli_query($conn, $sql2)){

            $_SESSION['name'] = $patient_name;
            $_SESSION['email'] = $email;
           header("Location: profile.php");
           exit();

        }else{

            die("Error: " . mysqli_error($conn));
        }
        
    }
}


if ($_SERVER['REQUEST_METHOD'] == "GET") {

    if(isset($_SESSION['id'])){

        $id = $_SESSION['id'];

        $res = mysqli_query($conn, "SELECT * FROM patients WHERE register_id='$id'");

        if(mysqli_num_rows($res)>0){

            $patient = mysqli_fetch_assoc($res);
            $register_id = $patient['register_id'];

        }else{

            $error = "Patient not found.";

        }

    }else{

        $error = "Invalid Patient ID.";

    }
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Patient Profile - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
        .input-focus { focus:outline-none; focus:ring-2; focus:ring-blue-500; focus:border-transparent; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-5xl mx-auto">
                    
                    <!-- Header -->
                    <div class="flex flex-col gap-5 mb-8">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-4">
                                <a class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:bg-gray-100 dark:hover:bg-neutral-800 size-11 transition-all shadow-sm" href="profile.php">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                                        <path d="m12 19-7-7 7-7"></path>
                                        <path d="M19 12H5"></path>
                                    </svg>
                                </a>
                                <div>
                                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Update Profile</h1>
                                    <p class="text-gray-500 dark:text-neutral-400 text-sm">Update information for <?php echo $patient ? $patient['patient_name'] : 'Patient'; ?></p>
                                </div>

                            
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl relative mb-6" role="alert">
                            <span class="block sm:inline"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl relative mb-6" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($patient): ?>

                    <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="newpatient_id" value="<?php echo $patient['patient_id']; ?>">

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                            
                            <!-- Left Column: Profile Card -->
                            <div class="lg:col-span-1 space-y-6">
                                <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-6 shadow-sm">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="relative group mb-4">
                                            <div class="size-32 rounded-full overflow-hidden border-4 border-gray-50 dark:border-neutral-800 shadow-md">
                                                <img id="preview-image" src="<?php echo $patient['patient_image'] ?  $patient['patient_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($patient['patient_name']) . '&background=random'; ?>" class="w-full h-full object-cover" alt="Patient">
                                            </div>
                                            <label for="newpatient_image" class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-full cursor-pointer shadow-lg transition-all">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                            </label>
                                            <input type="file" id="newpatient_image" name="newpatient_image" class="hidden" onchange="previewFile()">
                                        </div>
                                        <h2 class="text-xl font-bold"><?php echo $patient['patient_name']; ?></h2>
                                        <p class="text-sm text-gray-500 dark:text-neutral-400 mb-4">ID: #<?php echo $patient['patient_id']; ?></p>
                                        
                                        <div class="w-full pt-4 border-t border-gray-100 dark:border-neutral-800">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Account Status</label>
                                            <select name="newstatus" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                                <option value="Active" <?php if($patient['status']=="Active") echo "selected"; ?>>Active</option>
                                                <option value="Inactive" <?php if($patient['status']=="Inactive") echo "selected"; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Form Details -->
                            <div class="lg:col-span-2 space-y-6">
                                
                                <!-- Personal Details -->
                                <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-6 shadow-sm">
                                    <h3 class="text-lg font-semibold mb-6 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        Personal Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Full Name</label>
                                            <input type="text" name="newpatient_name" value="<?php echo $patient['patient_name']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Date of Birth</label>
                                            <input type="date" name="newdob" value="<?php echo $patient['date_of_birth']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Age</label>
                                            <input type="number" name="newage" value="<?php echo $patient['age']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Gender</label>
                                            <select name="newgender" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                                <option value="Male" <?php if($patient['gender']=="Male") echo "selected"; ?>>Male</option>
                                                <option value="Female" <?php if($patient['gender']=="Female") echo "selected"; ?>>Female</option>
                                                <option value="Other" <?php if($patient['gender']=="Other") echo "selected"; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Blood Group</label>
                                            <input type="text" name="newblood_group" value="<?php echo $patient['blood_group']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Details -->
                                <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-6 shadow-sm">
                                    <h3 class="text-lg font-semibold mb-6 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        Contact Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Email Address</label>
                                            <input type="email" name="newemail" value="<?php echo $patient['email']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Mobile Number</label>
                                            <input type="text" name="newmobile" value="<?php echo $patient['mobile']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Emergency Contact</label>
                                            <input type="text" name="newemergency_contact" value="<?php echo $patient['emergency_contact']; ?>" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Home Address</label>
                                            <textarea name="newaddress" rows="3" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none"><?php echo $patient['address']; ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Medical Information -->
                                <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-6 shadow-sm">
                                    <h3 class="text-lg font-semibold mb-6 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="M12 22v-5"/><path d="M9 18h6"/><path d="M10 22h4"/><path d="M18 12h-4V4a2 2 0 0 0-4 0v8H6a2 2 0 0 0-2 2v2c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2z"/></svg>
                                        Medical Details
                                    </h3>
                                    <div class="space-y-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Medical History</label>
                                            <textarea name="newmedical_history" rows="4" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none" placeholder="Enter patient medical history..."><?php echo $patient['medical_history']; ?></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">Allergies</label>
                                            <textarea name="newallergy" rows="3" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none" placeholder="List any allergies..."><?php echo $patient['allergy']; ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end gap-4 pt-4">
                                    <a href="../patients.php" class="px-6 py-2.5 rounded-xl border border-gray-200 dark:border-neutral-800 hover:bg-gray-100 dark:hover:bg-neutral-800 font-medium transition-all">Cancel</a>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl font-semibold shadow-lg shadow-blue-500/30 transition-all transform active:scale-95">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php else: ?>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 p-12 rounded-2xl text-center shadow-sm">
                            <div class="bg-red-50 dark:bg-red-900/20 size-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">Patient Not Found</h3>
                            <p class="text-gray-500 dark:text-neutral-400 mb-8"><?php echo $error ? $error : "The patient record you are looking for could not be found or the ID is invalid."; ?></p>
                            <a href="../patients.php" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-8 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/25">Return to Patient List</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function previewFile() {
            const preview = document.getElementById('preview-image');
            const file = document.querySelector('input[type=file]').files[0];
            const reader = new FileReader();

            reader.addEventListener("load", function () {
                preview.src = reader.result;
            }, false);

            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>
    <?php $conn->close(); ?>
</body>
</html>
    