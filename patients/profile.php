<?php
session_start();
include "../config/hospital.php";

if(!isset($_SESSION["id"])) {
    header("Location:../index.php");
    exit();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        
        /* Tab styles */
        .tab-btn {
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 500;
            border-bottom-width: 2px;
            border-bottom-style: solid;
        }
        .tab-btn:hover {
            background-color: #f8fafc;
        }
        .tab-active {
            border-bottom-color: #2563eb !important;
            color: #2563eb !important;
        }
        .tab-inactive {
            border-bottom-color: transparent !important;
            color: #6b7280 !important;
        }
        .tab-inactive:hover {
            color: #374151 !important;
            border-bottom-color: #d1d5db !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include '../header.php'; ?> 

        <div class="flex flex-1 items-start">
            <!-- Sidebar Navigation -->
            <?php include '../Sidebar.php'; ?> 

            <!-- Main Content Area -->
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="dashboard.php" class="p-2 border rounded-md hover:bg-gray-100 transition">
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
                                    <?php if(!empty($image) && file_exists("../".$image)): ?>
                                        <img src="../<?php echo $image; ?>" width="120" height="120" alt="Patient Image" class="rounded-full w-32 h-32 object-cover border-4 border-blue-100">
                                    <?php else: ?>
                                        <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-4xl text-blue-600 border-4 border-blue-100">
                                            <?php echo strtoupper(substr($name, 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <h2 class="text-xl font-bold text-gray-900 mt-4"><?php echo $name ?></h2>
                                    <div class="mt-4 flex gap-2 flex-wrap justify-center">
                                        <span class="px-3 py-1 <?php echo $status == 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> text-xs font-medium rounded-full">
                                            <?php echo $status ?>
                                        </span>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                            Blood: <?php echo $blood_group ?>
                                        </span>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
                                            <?php echo $gender ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="mail" class="w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Email</p>
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
                                            <p class="text-sm font-medium"><?php echo date('d M Y', strtotime($dob)); ?></p>
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
                                <div class="p-4 bg-gray-50 flex gap-2 border-t">
                                    <button onclick="window.location.href='update_adminprofile.php'" class="flex-1 bg-white border border-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition">
                                        <i class="fas fa-edit mr-1"></i> Edit Profile
                                    </button>
                                    <button onclick="window.location.href='change_pass.php'" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                                        <i class="fas fa-key mr-1"></i> Change Password
                                    </button>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl border shadow-sm p-6">
                                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                    <i data-lucide="phone-call" class="w-4 h-4 mr-2 text-red-500"></i>
                                    Emergency Contact
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-2 bg-red-50 rounded-lg">
                                        <span class="text-sm text-gray-600">📞 Phone</span>
                                        <span class="text-sm font-semibold text-gray-900"><?php echo $emergency_contact ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Detailed Info Tabs -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                                <!-- Tab Navigation -->
                                <div class="flex border-b overflow-x-auto custom-scrollbar bg-gray-50/50">
                                    <button id="overviewBtn" onclick="showTab('overview')"
                                        class="tab-btn tab-active px-6 py-4 text-sm font-medium transition-colors relative">
                                        <i data-lucide="layout-dashboard" class="w-4 h-4 inline mr-2"></i>
                                        Overview
                                    </button>

                                    <button id="appointmentsBtn" onclick="showTab('appointments')"
                                        class="tab-btn tab-inactive px-6 py-4 text-sm font-medium transition-colors relative">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-2"></i>
                                        Appointments
                                    </button>

                                    <button id="documentBtn" onclick="showTab('document')"
                                        class="tab-btn tab-inactive px-6 py-4 text-sm font-medium transition-colors relative">
                                        <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i>
                                        Documents
                                    </button>

                                    <button id="billingBtn" onclick="showTab('billing')"
                                        class="tab-btn tab-inactive px-6 py-4 text-sm font-medium transition-colors relative">
                                        <i data-lucide="credit-card" class="w-4 h-4 inline mr-2"></i>
                                        Billing
                                    </button>
                                </div>

                                <div class="p-6">
                                    <!-- Overview Tab -->
                                    <div id="overview" class="tab-content">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            <div class="space-y-4">
                                                <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                    <i data-lucide="alert-circle" class="w-4 h-4 mr-2 text-red-500"></i>
                                                    Known Allergies
                                                </h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php 
                                                    if(!empty($allergies) && !empty($allergies[0])) {
                                                        foreach ($allergies as $allergy) { 
                                                            if(trim($allergy) != '') {
                                                    ?>
                                                        <span class="px-3 py-1 bg-red-50 text-red-700 text-xs font-medium rounded-md border border-red-100">
                                                            <?php echo trim($allergy); ?>
                                                        </span>
                                                    <?php 
                                                            }
                                                        }
                                                    } else { 
                                                    ?>
                                                        <span class="text-sm text-gray-400">No allergies recorded</span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                    <i data-lucide="pill" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                    Current Medications
                                                </h4>
                                                <ul class="space-y-2">
                                                    <?php 
                                                    if(!empty($medications) && !empty($medications[0])) {
                                                        foreach ($medications as $medi) { 
                                                            if(trim($medi) != '') {
                                                    ?>
                                                        <li class="text-sm text-gray-600 flex items-center">
                                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></span>
                                                            <?php echo trim($medi); ?>
                                                        </li>
                                                    <?php 
                                                            }
                                                        }
                                                    } else { 
                                                    ?>
                                                        <li class="text-sm text-gray-400">No medications recorded</li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Appointments Tab -->
                                    <div id="appointments" class="tab-content hidden">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Appointment History</h3>
                                            <span class="text-sm text-gray-500">Total: <?php echo isset($appointment_info) ? $appointment_info->num_rows : 0; ?> appointments</span>
                                        </div>

                                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Appointment No</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Doctor</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Department</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Time</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php                                                                                  
                                                        $patient_appointment = "select a.*, d.doctor_name from appointments a left join doctor d on a.doctor_id=d.doctor_id where a.patient_id='$patient_id' and (a.delete_flag=0 or a.delete_flag is null) order by a.appointment_date desc";
                                                        $appointment_info = $conn->query($patient_appointment);
                                                        if($appointment_info && $appointment_info->num_rows > 0) {
                                                            $rowCount = 0;
                                                            while($app = $appointment_info->fetch_assoc()) {
                                                                $rowClass = ($rowCount % 2 == 0) ? 'bg-white' : 'bg-gray-50';
                                                                
                                                                // Status badge styling
                                                                $status = strtolower($app['status']);
                                                                $statusColors = [
                                                                    'scheduled' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                                    'confirmed' => 'bg-green-100 text-green-700 border-green-200',
                                                                    'completed' => 'bg-purple-100 text-purple-700 border-purple-200',
                                                                    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                                                    'in progress' => 'bg-yellow-100 text-yellow-700 border-yellow-200'
                                                                ];
                                                                $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                                    ?>
                                                     <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors cursor-pointer <?php echo $rowClass; ?>"
    onclick="window.location.href='view_appointment.php?id=<?php echo $app['appointment_id']; ?>'">
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                                <?php echo $app['appointment_no']; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <?php echo $app['doctor_name']; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <?php echo $app['department']; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <?php echo date('d M Y', strtotime($app['appointment_date'])); ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span class="px-3 py-1 text-xs font-medium rounded-full border <?php echo $statusColor; ?>">
                                                                    <?php echo ucfirst($app['status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                                $rowCount++;
                                                            }
                                                        } else {
                                                    ?>
                                                        <tr>
                                                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                                                <div class="flex flex-col items-center justify-center">
                                                                    <i data-lucide="calendar-x" class="w-12 h-12 text-gray-300 mb-2"></i>
                                                                    <p class="text-sm font-medium">No appointments found</p>
                                                                    <p class="text-xs text-gray-400">Schedule your first appointment today</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Documents Tab -->
                                    <div id="document" class="tab-content hidden">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Patient Documents</h3>
                                            <span class="text-sm text-gray-500">Total: <?php echo isset($document_result) ? $document_result->num_rows : 0; ?> documents</span>
                                        </div>

                                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Document Name</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Document Date</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Note</th>
                                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">File</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                        $document_query = "select * from patient_documents where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) order by document_date DESC";
                                                        $document_result = $conn->query($document_query);

                                                        if($document_result && $document_result->num_rows > 0){
                                                            $rowCount = 0;
                                                            while($doc = $document_result->fetch_assoc()){
                                                                $rowClass = ($rowCount % 2 == 0) ? 'bg-white' : 'bg-gray-50';
                                                                
                                                                // File type icon
                                                                $fileType = strtolower(pathinfo($doc['upload_file'], PATHINFO_EXTENSION));
                                                                $fileIcons = [
                                                                    'pdf' => 'fa-file-pdf text-red-500',
                                                                    'doc' => 'fa-file-word text-blue-500',
                                                                    'docx' => 'fa-file-word text-blue-500',
                                                                    'xls' => 'fa-file-excel text-green-500',
                                                                    'xlsx' => 'fa-file-excel text-green-500',
                                                                    'jpg' => 'fa-file-image text-purple-500',
                                                                    'jpeg' => 'fa-file-image text-purple-500',
                                                                    'png' => 'fa-file-image text-purple-500',
                                                                    'gif' => 'fa-file-image text-purple-500'
                                                                ];
                                                                $fileIcon = $fileIcons[$fileType] ?? 'fa-file text-gray-500';
                                                    ?>
                                                        <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors <?php echo $rowClass; ?>">
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                                <i class="fas <?php echo $fileIcon; ?> mr-2"></i>
                                                                <?php echo $doc['document_name']; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                                                    <?php echo $doc['document_type']; ?>
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <?php echo date("d M Y", strtotime($doc['document_date'])); ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">
                                                                <?php echo !empty($doc['note']) ? $doc['note'] : '<span class="text-gray-400">-</span>'; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <a href="../<?php echo $doc['upload_file']; ?>"
                                                                    target="_blank"
                                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors shadow-sm hover:shadow">
                                                                    <i class="fas fa-eye mr-1"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                                $rowCount++;
                                                            }
                                                        } else {
                                                    ?>
                                                        <tr>
                                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                                                <div class="flex flex-col items-center justify-center">
                                                                    <i data-lucide="file-text" class="w-12 h-12 text-gray-300 mb-2"></i>
                                                                    <p class="text-sm font-medium">No documents found</p>
                                                                    <p class="text-xs text-gray-400">Upload your medical documents</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Billing Tab -->
                                    <div id="billing" class="tab-content hidden">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Billing Information</h3>
                                            <span class="text-sm text-gray-500">Total: <?php echo isset($billing_info) ? $billing_info->num_rows : 0; ?> bills</span>
                                        </div>

                                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Service Name</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Total</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Discount</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Paid Amount</th>
                                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Pending Amount</th>
                                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                        $fetch_billing = "select * from billing where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null)";
                                                        $billing_info = $conn->query($fetch_billing);

                                                        if($billing_info && $billing_info->num_rows > 0){
                                                            $rowCount = 0;
                                                            while($billing = $billing_info->fetch_assoc()){
                                                                $rowClass = ($rowCount % 2 == 0) ? 'bg-white' : 'bg-gray-50';
                                                                
                                                                // Determine payment status
                                                                $pending = floatval($billing['pending_amount']);
                                                                $paymentStatus = '';
                                                                $statusColor = '';
                                                                if($pending == 0) {
                                                                    $paymentStatus = 'Paid';
                                                                    $statusColor = 'bg-green-100 text-green-700 border-green-200';
                                                                } elseif($pending > 0 && $pending < floatval($billing['total'])) {
                                                                    $paymentStatus = 'Partial';
                                                                    $statusColor = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                                                } else {
                                                                    $paymentStatus = 'Unpaid';
                                                                    $statusColor = 'bg-red-100 text-red-700 border-red-200';
                                                                }
                                                    ?>
                                                        <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors <?php echo $rowClass; ?>">
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                                <?php echo $billing['service_name']; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                                ₹<?php echo number_format($billing['total'], 2); ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                                <?php echo !empty($billing['discount']) ? '₹'.number_format($billing['discount'], 2) : '<span class="text-gray-400">-</span>'; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-green-600 font-semibold">
                                                                ₹<?php echo number_format($billing['paid_amount'], 2); ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <?php if($pending > 0): ?>
                                                                    <span class="text-red-600 font-semibold">₹<?php echo number_format($pending, 2); ?></span>
                                                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full border <?php echo $statusColor; ?>">
                                                                        <?php echo $paymentStatus; ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="px-3 py-1 text-xs font-medium rounded-full border <?php echo $statusColor; ?>">
                                                                        <?php echo $paymentStatus; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <a href="view_bill_detail.php?id=<?php echo $billing['id'] ?>"
                                                                    target="_blank"
                                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors shadow-sm hover:shadow">
                                                                    <i class="fas fa-file-invoice mr-1"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                                $rowCount++;
                                                            }
                                                        } else {
                                                    ?>
                                                        <tr>
                                                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                                                <div class="flex flex-col items-center justify-center">
                                                                    <i data-lucide="credit-card" class="w-12 h-12 text-gray-300 mb-2"></i>
                                                                    <p class="text-sm font-medium">No bills found</p>
                                                                    <p class="text-xs text-gray-400">Your billing history will appear here</p>
                                                                </div>
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
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Set default tab
            showTab('overview');
        });

        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active styles from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });

            // Show selected tab content
            const selectedContent = document.getElementById(tabName);
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
            }

            // Highlight selected tab button
            let selectedButton = null;
            switch(tabName) {
                case 'overview':
                    selectedButton = document.getElementById('overviewBtn');
                    break;
                case 'appointments':
                    selectedButton = document.getElementById('appointmentsBtn');
                    break;
                case 'document':
                    selectedButton = document.getElementById('documentBtn');
                    break;
                case 'billing':
                    selectedButton = document.getElementById('billingBtn');
                    break;
                default:
                    selectedButton = document.getElementById('overviewBtn');
            }

            if (selectedButton) {
                selectedButton.classList.remove('tab-inactive');
                selectedButton.classList.add('tab-active');
            }

           
        }

        // Handle browser back/forward buttons
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash.replace('#', '');
            if (hash && ['overview', 'appointments', 'document', 'billing'].includes(hash)) {
                showTab(hash);
            }
        });
    </script>
</body>
</html>

<?php
  }
}
}
?>