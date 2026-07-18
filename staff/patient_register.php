<?php

session_start(); 
include "../config/hospital.php";

$image_path = "";

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

    $password = $_POST['password'];

    $register = "insert into register(name,email,password,role,created_by,modified_by)values('$patient_name','$email','$password','patient','Admin','Admin')";

        if($conn->query($register)){
            $register_id = $conn->insert_id;

        $folder = "../documents/patients/images/";

        $image_name = $_FILES['patient_image']['name'];

        $image_path = $folder . $image_name;

        move_uploaded_file($_FILES['patient_image']['tmp_name'], $image_path);
        
        $insert = "insert into patients(register_id,patient_name, date_of_birth, age, blood_group, gender, address, emergency_contact, medical_history, allergy, email, mobile, status,patient_image) 
                values('$register_id','$patient_name','$dob','$age','$blood_group','$gender','$address','$emergency_contact','$medical_history','$allergy','$email','$mobile','$status','$image_path')";
     

        if ($conn->query($insert) === true) {
            $patient_id = $conn->insert_id;
            
            if(isset($_FILES['document_file'])) {

                $document_name = $_POST['document_name'];
                $document_type = $_POST['document_type'];
                $note = $_POST['document_note'];
                $document_date = $_POST['document_date'];
        
                
                $upload_dir = "../documents/patients/document/";

                $file_name = $_FILES['document_file']['name'];
            
                $upload_file = $upload_dir . $file_name;
                
                if(move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_file)) {

                    $insert_doc = "insert into patient_documents(patient_id, document_name, document_type, upload_file, note, document_date) 
                                values('$patient_id','$document_name','$document_type','$upload_file','$note','$document_date')";
                    
                    if ($conn->query($insert_doc) === true) {
                    
                        header("Location:../patients.php");
                    } else {
                        echo "<script>alert('Patient added but document upload failed!')</script>";
                    }
                } else {
                    echo "<script>alert('File upload failed!')</script>";
                }
            } else {
              echo "<script>
                  
                    alert('Patient added successfully');
                      window.location='../patients.php';
                </script>";
                    exit();
            }
        }
         else {
            echo "<script>alert('Unable to add patient. Error: " . $conn->error . "')</script>";
        }
    }
    else {

        die("Register Error : " . $conn->error);

    }
}

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MedixPro - Add Patient</title>
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
        </style>
    </head>

    <body class="bg-gray-50 text-gray-900">
        <div class="flex min-h-screen flex-col bg-gray-50">
            <!-- Header -->
            <?php include '../staff/staff_header.php'; ?>

            <div class="flex flex-1 items-start">
                <?php include '../staff/staff_sidebar.php'; ?>

                <!-- Main Content Area -->
                <main class="flex-1 xl:ml-64 p-4 md:p-8">
                    <div class="max-w-5xl mx-auto w-full">
                        <div class="mb-8">
                            <h1 class="text-2xl font-bold text-gray-900">Add New Patient</h1>
                            <p class="text-gray-500">Complete the following forms to register a new patient in the system.</p>
                        </div>

                        <!-- Step Navigation -->
                        <div class="flex border-b mb-8 overflow-x-auto custom-scrollbar">
                            <button onclick="showSection('personal')" type="button" id="btn-personal"
                                class="px-6 py-3 text-sm font-medium whitespace-nowrap step-active">
                                Personal Information
                            </button>
                            <button onclick="showSection('document')" type="button" id="btn-document"
                                class="px-6 py-3 text-sm font-medium whitespace-nowrap step-inactive">
                                Document Upload
                            </button>
                        </div>

                        <!-- Form Container -->
                        <form action="patient_register.php" method="POST" enctype="multipart/form-data">

                            <div class="bg-white rounded-xl border shadow-sm p-6 md:p-8">

                                <!-- Section 0: Personal Information -->
                                <div id="section-personal" class="form-section active">
                                    <h2 class="text-lg font-semibold mb-6">Personal Details</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="patient_name">Full Name</label>
                                            <input id="patient_name" name="patient_name" placeholder="Enter full name"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="dob">Date of Birth</label>
                                            <input id="dob" type="date" name="dob"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="age">Age</label>
                                            <input id="age" type="number" name="age" placeholder="Enter age"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="blood_group">Blood Group</label>
                                            <select id="blood_group" name="blood_group"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
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
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                                <option value="">Select gender</option>
                                                <option>Male</option>
                                                <option>Female</option>
                                                <option>Other</option>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="emergency_contact">Emergency Contact</label>
                                            <input id="emergency_contact" name="emergency_contact" placeholder="Enter emergency contact number"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                    </div>

                                    <div class="mt-6 space-y-4">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="address">Address</label>
                                            <textarea id="address" name="address" placeholder="Enter address"
                                                class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required></textarea>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium" for="email">Email</label>
                                                <input id="email" type="email" name="email" placeholder="Enter email address"
                                                    class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    required>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium">Password</label>
                                                <input type="password"
                                                    name="password"
                                                    placeholder="Enter Login Password"
                                                    class="w-full h-10 px-3 rounded-md border border-gray-300"
                                                    required>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium" for="mobile">Mobile Number</label>
                                                <input id="mobile" name="mobile" placeholder="Enter mobile number"
                                                    class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                    required>
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
                                            
                                                <p id="file_name_display" class="mt-2 text-sm text-green-600"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-8 flex justify-end">
                                        <button type="button" onclick="showSection('document')"
                                            class="bg-blue-600 text-white px-6 py-2 rounded-md font-medium hover:bg-blue-700 transition">Next:
                                            Document Upload</button>
                                    </div>
                                </div>

                                <!-- Section 1: Document Upload -->
                                <div id="section-document" class="form-section">
                                    <h2 class="text-lg font-semibold mb-6">Document Upload</h2>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="document_name">Document Name</label>
                                            <input id="document_name" name="document_name" placeholder="e.g., Medical Report, ID Card"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="document_type">Document Type</label>
                                            <select id="document_type" name="document_type"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                                <option value="">Select Document Type</option>
                                                <option>Medical Report</option>
                                                <option>ID Proof</option>
                                                <option>Prescription</option>
                                                <option>Insurance Card</option>
                                                <option>Lab Report</option>
                                                <option>Consent Form</option>
                                                <option>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-6 space-y-4">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="document_date">Document Date</label>
                                            <input id="document_date" type="date" name="document_date"
                                                class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                required>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium" for="document_note">Note (Optional)</label>
                                            <textarea id="document_note" name="document_note" placeholder="Any additional notes about this document"
                                                class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium">Upload Document</label>
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                                                <i data-lucide="upload-cloud" class="w-12 h-12 mx-auto text-blue-500 mb-3"></i>
                                                <p class="font-medium text-gray-700">Click to upload or drag and drop</p>
                                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, PDF (Max 5MB)</p>
                                                <input type="file" id="document_file" name="document_file" 
                                                    accept=".jpg,.jpeg,.png,.pdf" 
                                                    class="hidden" required>
                                                <button type="button" onclick="document.getElementById('document_file').click()"
                                                    class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                                                    Choose File
                                                </button>
                                                <p id="file_name_display" class="mt-2 text-sm text-green-600"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-8 flex justify-between">
                                        <button type="button" onclick="showSection('personal')"
                                            class="border border-gray-300 px-6 py-2 rounded-md font-medium hover:bg-gray-50 transition">Back</button>
                                        <button type="submit"
                                            class="bg-blue-600 text-white px-8 py-2 rounded-md font-semibold hover:bg-blue-700 shadow-md transition">Submit
                                            Patient</button>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Initialize Lucide Icons
            lucide.createIcons();

            function showSection(sectionId) {
                // Hide all sections
                document.querySelectorAll('.form-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Show target section
                document.getElementById('section-' + sectionId).classList.add('active');

                const tabs = ['personal', 'document'];
                tabs.forEach(tab => {
                    const btn = document.getElementById('btn-' + tab);
                    if (tab === sectionId) {
                        btn.classList.add('step-active');
                        btn.classList.remove('step-inactive');
                    } else {
                        btn.classList.remove('step-active');
                        btn.classList.add('step-inactive');
                    }
                });

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // Display filename when file is selected
            document.getElementById('document_file').addEventListener('change', function() {
                if (this.files.length > 0) {
                    document.getElementById('file_name_display').innerHTML = '📄 ' + this.files[0].name;
                }
            });

        
        </script>

    </body>

    </html>