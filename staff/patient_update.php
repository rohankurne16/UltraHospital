<?php

 session_start(); 
include "../config/db.php";

$message = "";
$error = "";



if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $id = $_POST['newpatient_id'];

    
    $get = "SELECT * FROM patients WHERE patient_id='$id'";
    $res = mysqli_query($conn, $get);
    $patient = mysqli_fetch_assoc($res);

    $patient_image = $patient['patient_image'];

    
    if (!empty($_FILES['newpatient_image']['name'])) {

        $filename =  $_FILES['newpatient_image']['name'];

        $patient_image = "documents/patients/images/" . $filename;

        move_uploaded_file(
            $_FILES['newpatient_image']['tmp_name'],
            "../" . $patient_image
        );
    }


    

    
    $patient_name       = mysqli_real_escape_string($conn, $_POST['newpatient_name']);
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

        $message = "Patient updated successfully.";

       
        $res = mysqli_query($conn, "SELECT * FROM patients WHERE patient_id='$id'");
        $patient = mysqli_fetch_assoc($res);

        header("Location:../staff/patients_list.php?msg=success");

    } else {

        $error = mysqli_error($conn);
    }
}


if ($_SERVER['REQUEST_METHOD'] == "GET") {

    if(isset($_GET['id'])){

        $id = $_GET['id'];

        $res = mysqli_query($conn, "SELECT * FROM patients WHERE patient_id='$id'");

        if(mysqli_num_rows($res)>0){

            $patient = mysqli_fetch_assoc($res);

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
    <title>Update Appointment - MedixPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <?php include '../staff/staff_header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>
            

            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <!-- Header -->
                    <div class="flex flex-col gap-5 mb-8">
                        <div class="flex items-center flex-wrap gap-4">
                            <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors" href="../patients.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                                <span class="sr-only">Back</span>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Update Patient</h1>
                                <p class="text-gray-500 text-sm">Edit the details for the Patient.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($patient): ?>

                  <form action="patient_update.php?id=<?php echo $patient['patient_id']; ?>" method="POST" enctype="multipart/form-data">

                        <input type="hidden" name="newpatient_id" value="<?php echo $patient['patient_id']; ?>">

                        <!-- Patient Name -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Patient Name</label>
                            <input type="text" name="newpatient_name"
                                value="<?php echo $patient['patient_name']; ?>"
                                class="w-full border rounded-lg p-2" required>
                        </div>

                        <!-- Patient Image -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Patient Image</label>

                          <img src="<?php echo $patient['patient_image']; ?>" width="80" class="mb-2 rounded">

                            <input type="file" name="newpatient_image"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Date of Birth -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Date of Birth</label>
                            <input type="date" name="newdob"
                                value="<?php echo $patient['date_of_birth']; ?>"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Age -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Age</label>
                            <input type="number" name="newage"
                                value="<?php echo $patient['age']; ?>"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Blood Group -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Blood Group</label>
                            <input type="text" name="newblood_group"
                                value="<?php echo $patient['blood_group']; ?>"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Gender -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Gender</label>

                            <select name="newgender" class="w-full border rounded-lg p-2">
                                <option value="Male" <?php if($patient['gender']=="Male") echo "selected"; ?>>Male</option>
                                <option value="Female" <?php if($patient['gender']=="Female") echo "selected"; ?>>Female</option>
                                <option value="Other" <?php if($patient['gender']=="Other") echo "selected"; ?>>Other</option>
                            </select>
                        </div>

                        <!-- Address -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Address</label>
                            <textarea name="newaddress" class="w-full border rounded-lg p-2"><?php echo $patient['address']; ?></textarea>
                        </div>

                        <!-- Mobile -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Mobile</label>
                            <input type="text" name="newmobile"
                                value="<?php echo $patient['mobile']; ?>"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Email</label>
                            <input type="email" name="newemail"
                                value="<?php echo $patient['email']; ?>"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Emergency Contact -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Emergency Contact</label>
                            <input type="text" name="newemergency_contact"
                                value="<?php echo $patient['emergency_contact']; ?>"
                                class="w-full border rounded-lg p-2">
                        </div>

                        <!-- Medical History -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Medical History</label>
                            <textarea name="newmedical_history"
                                class="w-full border rounded-lg p-2"><?php echo $patient['medical_history']; ?></textarea>
                        </div>

                        <!-- Allergy -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Allergy</label>
                            <textarea name="newallergy"
                                class="w-full border rounded-lg p-2"><?php echo $patient['allergy']; ?></textarea>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Status</label>

                            <select name="newstatus" class="w-full border rounded-lg p-2">
                                <option value="Active" <?php if($patient['status']=="Active") echo "selected"; ?>>Active</option>
                                <option value="Inactive" <?php if($patient['status']=="Inactive") echo "selected"; ?>>Inactive</option>
                            </select>
                        </div>

                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            Update Patient
                        </button>

                    </form>


                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-8 rounded-lg text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h3 class="text-lg font-bold mb-2">Error Loading Appointment</h3>
                            <p><?php echo $error ? $error : "Please provide a valid appointment ID."; ?></p>
                            <a href="appointments.php" class="inline-flex items-center justify-center mt-6 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-all">Return to List</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <?php $conn->close(); ?>
</body>
</html>
