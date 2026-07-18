<?php
 session_start();
include "../config/hospital.php";

if(!isset($_SESSION["id"])) {
    header("Location:../index.php");
}

if(isset($_SESSION['id'])){

$id = $_SESSION['id'];


$view_patient = "select * from patients where register_id='$id'";

$data= $conn->query($view_patient);
if($data->num_rows > 0){

    while($row = $data->fetch_assoc()) {

        $patient_id = $row['patient_id'];
        $name = $row['patient_name'];
        $image= $row['patient_image'];
        $dob = $row['date_of_birth'];
        $age = $row['age'];
        $blood_group = $row['blood_group'];
        $gender = $row['gender'];
        $address= $row['address'];
        $emergency_contact= $row['emergency_contact'];
        $medical_history= $row['medical_history'];
        $medications=explode(',',$medical_history);
        $allergy= $row['allergy'];
        $allergies = explode(",", $allergy);
        $email = $row['email'];
        $mobile = $row['mobile'];
        $status = isset($row['status']) ? $row['status'] : 'Active';
        $status_class = $status == 'Active' ? 'status-active' : 'status-inactive';

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Patient Profile</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .custom-scrollbar::-web
        
        kit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header Placeholder -->
         <?php include 'header.php'; ?> 

        <div class="flex flex-1 items-start">
            <!-- Sidebar Navigation -->
             <?php include 'Sidebar.php'; ?> 

            <!-- Main Content Area -->
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="dashboard.php" class="p-2 border rounded-md hover:bg-gray-100">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Patient Profile</h1>
                            <p class="text-gray-500">View and manage patient health records.</p>
                        
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Patient Summary -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                                <div class="p-6 flex flex-col items-center text-center border-b">

                                  <img src="<?php echo $image; ?>" width="220" height="220" alt="Patient Image">
                                                    
                                    <h2 class="text-xl font-bold text-gray-900"><?php echo $name ?></h2>
                                
                                    <div class="mt-4 flex gap-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full"><?php echo $status ?></span>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">Blood: <?php echo $blood_group ?></span>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="mail" class="w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400"></p>
                                            <p class="text-sm font-medium"><?php echo $email ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="phone" class="w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Phone</p>
                                            <p class="text-sm font-medium"><?php echo $mobile ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Date of Birth</p>
                                            <p class="text-sm font-medium"><?php echo $dob ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Address</p>
                                            <p class="text-sm font-medium"><?php echo $address ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 flex gap-2">
                                    <button onclick="window.location.href='update_profile.php'" class="flex-1 bg-white border px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition">Edit</button>
                                     <button onclick="window.location.href='change_pass.php'" class="flex-1 bg-blue-500 border px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition">Change Password</button>
           
                                </div>
                            </div>

                            <div class="bg-white rounded-xl border shadow-sm p-6">
                                <h3 class="font-bold text-gray-900 mb-4">Emergency Contact</h3>
                                <div class="space-y-3">
                                  
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500">Phone</span>
                                        <span class="text-sm font-medium"><?php echo $emergency_contact ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Detailed Info Tabs -->
                        <div class="lg:col-span-2 space-y-6">
                  
                            <div class="bg-white rounded-xl border shadow-sm">
                                <div class="flex border-b overflow-x-auto custom-scrollbar">
                                    <button id="overviewBtn" onclick="showTab('overview')"
                                        class="tab-btn px-6 py-4 border-b-2 border-blue-600 text-blue-600">
                                        Overview
                                    </button>

                                    <button id="appointmentsBtn" onclick="showTab('appointments')"
                                        class="tab-btn px-6 py-4 text-gray-500">
                                        Appointments
                                    </button>

                                     <button id="documentBtn" onclick="showTab('document')"
                                        class="tab-btn px-6 py-4 text-gray-500">
                                        Patient Documents
                                    </button>

                                    <button id="billingBtn" onclick="showTab('billing')"
                                        class="tab-btn px-6 py-4 text-gray-500">
                                        Billing
                                    </button>
                                </div>

                                <div class="p-6 space-y-8">
                                    <div id="overview" class="tab-content">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            <div class="space-y-4">
                                                <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                    <i data-lucide="alert-circle" class="w-4 h-4 mr-2 text-red-500"></i>
                                                    Known Allergies
                                                </h4>
                                            <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($allergies as $allergy) { ?>
                                                        <span class="px-3 py-1 bg-red-50 text-red-700 text-xs font-medium rounded-md border border-red-100">
                                                            <?php echo trim($allergy); ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                    <i data-lucide="pill" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                    Current Medications
                                                </h4>
                                                <ul class="space-y-2">
                                                    <?php foreach ($medications as $medi) { ?>
                                                        <li class="text-sm text-gray-600 flex items-center">
                                                    
                                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></span>
                                                            <?php echo trim($medi); ?>
                                                            
                                                        </li>
                                                    <?php } ?>

                                                    
                                                </ul>

                                            </div>
                                        </div>
</div>
                                        
                                    <div id="appointments" class="tab-content hidden">

                                            <h3 class="text-lg font-semibold mb-4">Appointment History</h3>

                                            <table class="w-full border">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-3 text-left">Appointment No</th>
                                                        <th class="p-3 text-left">Doctor</th>
                                                        <th class="p-3 text-left">Department</th>
                                                        <th class="p-3 text-left">Date</th>
                                                        <th class="p-3 text-left">Time</th>
                                                        <th class="p-3 text-left">Status</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                <?php                                                                                  
                                                        $patient_appointment = "select a.*, d.doctor_name from appointments a left join doctor d on a.doctor_id=d.doctor_id where a.patient_id='$patient_id' and (a.delete_flag=0 or a.delete_flag is null) order by a.appointment_date desc";
                                                        $appointment_info = $conn->query($patient_appointment);
                                                        if($appointment_info->num_rows > 0) {
                                                            while($app = $appointment_info->fetch_assoc()) {
                                                        ?>

                                                    <tr class="border-b">
                                                        <td class="p-3"><?php echo $app['appointment_no']; ?></td>
                                                        <td class="p-3"><?php echo $app['doctor_name']; ?></td>
                                                        <td class="p-3"><?php echo $app['department']; ?></td>
                                                        <td class="p-3"><?php echo $app['appointment_date']; ?></td>
                                                        <td class="p-3"><?php echo $app['appointment_time']; ?></td>
                                                        <td class="p-3"><?php echo $app['status']; ?></td>
                                                    </tr>

                                                <?php
                                                    }

                                                }else{
                                                ?>

                                                <tr>
                                                    <td colspan="6" class="text-center p-5">
                                                        No appointments found.
                                                    </td>
                                                </tr>

                                                <?php } ?>

                                                </tbody>

                                            </table>

                                        </div>

                                        
                                        <div id="document" class="tab-content hidden">

                                            <h3 class="text-lg font-semibold mb-4">Patient Documents</h3>

                                            <table class="w-full border">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-3 text-left">Document Name</th>
                                                        <th class="p-3 text-left">Type</th>
                                                        <th class="p-3 text-left">Document Date</th>
                                                        <th class="p-3 text-left">Note</th>
                                                        <th class="p-3 text-center">File</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                <?php

                                                $document_query = "select * from patient_documents where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) order by document_date DESC";

                                                $document_result = $conn->query($document_query);

                                                if($document_result->num_rows > 0){

                                                    while($doc = $document_result->fetch_assoc()){
                                                ?>

                                                    <tr class="border-b hover:bg-gray-50">
                                                        <td class="p-3">
                                                            <?php echo $doc['document_name']; ?>
                                                        </td>

                                                        <td class="p-3">
                                                            <?php echo $doc['document_type']; ?>
                                                        </td>

                                                        <td class="p-3">
                                                            <?php echo date("d-m-Y", strtotime($doc['document_date'])); ?>
                                                        </td>

                                                        <td class="p-3">
                                                            <?php echo !empty($doc['note']) ? $doc['note'] : "-"; ?>
                                                        </td>

                                                        <td class="p-3 text-center">
                                                            <a href="../documents/<?php echo $doc['upload_file']; ?>"
                                                            target="_blank"
                                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                                                View
                                                            </a>
                                                        </td>
                                                    </tr>

                                                <?php
                                                    }

                                                }else{
                                                ?>

                                                    <tr>
                                                        <td colspan="5" class="text-center p-5">
                                                            No documents found.
                                                        </td>
                                                    </tr>

                                                <?php } ?>

                                                </tbody>

                                            </table>

                                        </div>
                                        

                                        <div id="billing" class="tab-content hidden">

                                            <h3 class="text-lg font-semibold">
                                            Billing Information
                                            </h3>
                                            <table class="w-full border">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-3 text-left">Service Name</th>
                                                        <th class="p-3 text-left">Total</th>
                                                        <th class="p-3 text-left">Discount</th>
                                                        <th class="p-3 text-left">Paid Amount</th>
                                                        <th class="p-3 text-center">Pending Amount</th>
                                                         <th class="p-3 text-center">View </th>
                                                    </tr>
                                                </thead>

                                                <tbody></tbody>
                                                <?php 
                                                    $fetch_billing="select * from billing where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null)";
                                                    $billing_info= $conn->query($fetch_billing);

                                                    if($billing_info->num_rows > 0){
                                                        while($billing = $billing_info->fetch_assoc()){
                                                    
                                                    ?> 
                                                    
                                                     <tr class="border-b hover:bg-gray-50">
                                                        <td class="p-3">
                                                            <?php echo $billing['service_name']; ?>
                                                        </td>

                                                        <td class="p-3">
                                                            <?php echo $billing['total']; ?>
                                                        </td>

                                                        <td class="p-3">
                                                            <?php echo !empty($billing['discount']) ? $billing['discount'] : "-"; ?>
                                                         </td>

                                                        <td class="p-3">
                                                            <?php echo $billing['paid_amount']; ?>
                                                         </td>

                                                         <td class="p-3">
                                                            <?php echo $billing['pending_amount']; ?>
                                                         </td>

                                                        <td class="p-3 text-center">
                                                            <a href="view_bill_detail.php?id=<?php echo $billing['id'] ?>"
                                                            target="_blank"
                                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                                                View
                                                            </a>
                                                        </td>
                                                    </tr>

                                                <?php
                                                    }

                                                }else{
                                                ?>

                                                    <tr>
                                                        <td colspan="5" class="text-center p-5">
                                                            No Bills found.
                                                        </td>
                                                    </tr>

                                                <?php } ?>

                                                </tbody>

                                            </table>

                                        </div>


                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
 

                function showTab(tab){

                    document.querySelectorAll('.tab-content').forEach(function(content){
                        content.classList.add('hidden');
                    });

                    document.querySelectorAll('.tab-btn').forEach(function(btn){
                        btn.classList.remove('border-blue-600','text-blue-600');
                        btn.classList.add('text-gray-500');
                    });

                    document.getElementById(tab).classList.remove('hidden');

                    document.getElementById(tab+'Btn').classList.add('border-blue-600','text-blue-600');
                    document.getElementById(tab+'Btn').classList.remove('text-gray-500');
                }

    </script>
</body>
</html>

<?php
  }

}

}
?>