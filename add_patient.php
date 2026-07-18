<?php

session_start(); 
include "config/hospital.php";

$hid=$_SESSION["hospital_id"];

$image_path = "";

$doctorsQuery = "SELECT doctor_id, doctor_name, department FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid' ORDER BY doctor_name ASC";
$doctorsResult = $conn->query($doctorsQuery);
$doctors = array();
if ($doctorsResult && $doctorsResult->num_rows > 0) {
    while ($row = $doctorsResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

if (isset($_POST['email'])) {
    
    $patient_name = $_POST['patient_name'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $blood_group = $_POST['blood_group'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $emergency_contact = $_POST['emergency_contact'];
    $medical_history = $_POST['medical_history'];
    $allergy = $_POST['allergy'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $status = 'Active';
    $doctor_id = isset($_POST['doctor_id']) && $_POST['doctor_id'] != '' ? $_POST['doctor_id'] : NULL;

    $password = $_POST['password'];

    $register = "INSERT INTO register(name, email, password, role, created_by, modified_by,hospital_id) VALUES('$patient_name','$email','$password','patient','Admin','Admin','$hid')";

    if($conn->query($register)){
        $register_id = $conn->insert_id;

        $folder = "documents/patients/images/";

        $image_name =basename($_FILES['patient_image']['name']);
        move_uploaded_file($_FILES['patient_image']['tmp_name'], $folder . $image_name);
        $image_path = "documents/patients/images/" . $image_name;

        move_uploaded_file($_FILES['patient_image']['tmp_name'], $image_path);
        
       $insert = "INSERT INTO patients(register_id, doctor_id, patient_name, date_of_birth, age, blood_group, gender, address, emergency_contact, medical_history, allergy, email, mobile, status, patient_image, delete_flag,hospital_id) VALUES('$register_id', " . ($doctor_id ? "'$doctor_id'" : "NULL") . ", '$patient_name','$dob','$age','$blood_group','$gender','$address','$emergency_contact','$medical_history','$allergy','$email','$mobile','$status','$image_path',0,'$hid')";

        if ($conn->query($insert) === true) {
            $patient_id = $conn->insert_id;
            
            if(isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {

                $document_name = $_POST['document_name'];
                $document_type = $_POST['document_type'];
                $note = $_POST['document_note'];
                $document_date = $_POST['document_date'];
        
                $upload_dir = "../documents/patients/document/";

                $file_name = $_FILES['document_file']['name'];
                $upload_file = $upload_dir . $file_name;
                
        
            } else {
                echo "<script>
                    alert('Patient added successfully');
                    window.location='patients.php';
                </script>";
                exit();
            }
        } else {
            echo "<script>alert('Unable to add patient. Error: " . $conn->error . "')</script>";
        }
    } else {
        die("Register Error : " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Add Patient</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            background-color: #f3f4f6;
            color: #111827;
        }
        .step-active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
        }
        .step-inactive {
            color: #6b7280;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
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
        }

        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50" >
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start" style="
    margin-top: 5%;">
            <?php include 'Sidebar.php'; ?>

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="patients.php" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Add New Patient</h1>
                            <p class="text-gray-500 text-sm">Complete the following forms to register a new patient in the system.</p>
                        </div>
                    </div>

                    <div class="flex border-b mb-8 overflow-x-auto custom-scrollbar">
                        <button onclick="showSection('personal')" type="button" id="btn-personal"
                            class="px-6 py-3 text-sm font-medium whitespace-nowrap step-active">
                            Personal Information
                        </button>
                    </div>

                    <form action="add_patient.php" method="POST" enctype="multipart/form-data">

                        <div class="bg-white rounded-xl border shadow-sm p-6 md:p-8">

                            <div id="section-personal" class="form-section active">
                                <h2 class="text-lg font-semibold mb-6">Personal Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="patient_name">Full Name <span class="text-red-500">*</span></label>
                                        <input id="patient_name" name="patient_name" placeholder="Enter full name"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                            required>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="doctor_id">Doctor</label>
                                        <select id="doctor_id" name="doctor_id"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="">Select Doctor (Optional)</option>
                                            <?php foreach($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?> - <?php echo htmlspecialchars($doctor['department']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-500">Select the primary doctor for this patient</p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="dob">Date of Birth</label>
                                        <input id="dob" type="date" name="dob"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="age">Age</label>
                                        <input id="age" type="number" name="age" placeholder="Enter age"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="blood_group">Blood Group</label>
                                        <select id="blood_group" name="blood_group"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="">Select Blood Group</option>
                                            <option>A+</option>
                                            <option>A-</option>
                                            <option>B+</option>
                                            <option>B-</option>
                                            <option>O+</option>
                                            <option>O-</option>
                                            <option>AB+</option>
                                            <option>AB-</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="gender">Gender</label>
                                        <select id="gender" name="gender"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="">Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="emergency_contact">Emergency Contact</label>
                                        <input id="emergency_contact" name="emergency_contact" placeholder="Enter emergency contact number"
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                    </div>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium" for="address">Address</label>
                                        <textarea id="address" name="address" placeholder="Enter address"
                                            class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="email">Email <span class="text-red-500">*</span></label>
                                            <input id="email" type="email" name="email" placeholder="Enter email address"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium">Password <span class="text-red-500">*</span></label>
                                            <input type="password" name="password" placeholder="Enter Login Password"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="mobile">Mobile Number</label>
                                            <input id="mobile" name="mobile" placeholder="Enter mobile number"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="medical_history">Medical History</label>
                                            <textarea id="medical_history" name="medical_history" placeholder="Previous medical conditions"
                                                class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="allergy">Allergies</label>
                                            <textarea id="allergy" name="allergy" placeholder="Known allergies"
                                                class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Patient Image</label>
                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                                            <input type="file" name="patient_image" accept="image/*">
                                            <p id="image_file_name" class="mt-2 text-sm text-green-600"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="submit"
                                    class="bg-blue-600 text-white px-8 py-2 rounded-md font-semibold hover:bg-blue-700 shadow-md transition">Submit
                                    Patient</button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        document.querySelector('input[name="patient_image"]').addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('image_file_name').innerHTML = '📄 ' + this.files[0].name;
            }
        });
    </script>

</body>
</html>