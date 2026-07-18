<?php
session_start();
include "config/hospital.php";

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $view_patient = "select * from patients where patient_id='$id'";

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

            $bed_query = "
SELECT
    ba.bed_id,
    b.bed_no,
    r.room_no,
    w.ward_name
FROM bed_allocation ba
INNER JOIN bed_master b ON ba.bed_id = b.bed_id
INNER JOIN room_master r ON b.room_id = r.room_id
INNER JOIN ward_master w ON r.ward_id = w.ward_id
WHERE ba.patient_id='$patient_id'
AND ba.status='Occupied'
LIMIT 1";

$bed_result = $conn->query($bed_query);
$bed_info = ($bed_result && $bed_result->num_rows > 0) ? $bed_result->fetch_assoc() : null;
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Patient Profile</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .tab-btn-active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            background: linear-gradient(to top, rgba(37, 99, 235, 0.05), transparent);
        }

        .status-badge-active {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #d1fae5;
        }

        .status-badge-inactive {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
        }

        .data-table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            color: #6b7280;
            padding: 0.75rem 1.5rem;
            background-color: #f9fafb;
            border-bottom: 1px solid #f3f4f6;
        }

        .data-table tbody td {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .action-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="h-full text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col" >
        <?php include 'header.php'; ?> 

        <div class="flex flex-1 overflow-hidden"style="
    margin-top: 5%;" >
            <?php include 'Sidebar.php'; ?> 

            <main class="flex-1 overflow-y-auto xl:ml-64 bg-gray-50/50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    
                    <!-- Page Header -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                        <div class="flex items-center gap-4">
                            <a href="patients.php" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-100 hover:bg-blue-50 transition-all">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                               
                                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Patient Profile</h1>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            
                            <button onclick="window.location.href='update_patient.php?id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 shadow-sm shadow-blue-200 transition-all action-btn">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                <span>Edit Patient</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <!-- Left Column: Patient Summary -->
                        <div class="lg:col-span-4 space-y-6">
                            
                            <!-- Identity Card -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="relative h-24 bg-gradient-to-r from-blue-600 to-indigo-600">
                                    <div class="absolute -bottom-12 left-1/2 -translate-x-1/2">
                                        <?php 
                                            $img_path = $image;
                                            if (!empty($img_path) && file_exists($img_path)): 
                                        ?>
                                            <img src="<?php echo $img_path; ?>" class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-md bg-white">
                                        <?php else: ?>
                                            <div class="w-24 h-24 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-2xl border-4 border-white shadow-md">
                                                <?php echo strtoupper(substr($name, 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="pt-14 pb-6 px-6 text-center border-b border-gray-50">
                                    <h2 class="text-xl font-bold text-gray-900"><?php echo $name ?></h2>
                                    <p class="text-sm text-gray-500 font-medium mt-0.5">ID: #<?php echo $id ?></p>
                                    
                                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                                        <span class="px-2.5 py-1 <?php echo $status == 'Active' ? 'status-badge-active' : 'status-badge-inactive'; ?> text-xs font-semibold rounded-full uppercase tracking-wider">
                                            <?php echo $status ?>
                                        </span>
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-100">
                                            <?php echo $blood_group ?>
                                        </span>
                                        <span class="px-2.5 py-1 bg-gray-50 text-gray-600 text-xs font-semibold rounded-full border border-gray-100">
                                            <?php echo $gender ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-6 space-y-5">
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-blue-50 rounded-lg">
                                            <i data-lucide="mail" class="w-4 h-4 text-blue-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address</p>
                                            <p class="text-sm font-semibold text-gray-900 break-all"><?php echo $email ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-indigo-50 rounded-lg">
                                            <i data-lucide="phone" class="w-4 h-4 text-indigo-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Phone Number</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo $mobile ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-purple-50 rounded-lg">
                                            <i data-lucide="calendar" class="w-4 h-4 text-purple-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date of Birth</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo date("F j, Y", strtotime($dob)); ?> (<?php echo $age; ?> yrs)</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-amber-50 rounded-lg">
                                            <i data-lucide="map-pin" class="w-4 h-4 text-amber-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Residential Address</p>
                                            <p class="text-sm font-semibold text-gray-900 leading-relaxed"><?php echo $address ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-gray-50/50 flex gap-3 border-t border-gray-100">
                                   
                                    <button class="flex-1 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-100">
                                        Send Message
                                    </button>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="p-2 bg-red-50 rounded-lg text-red-600">
                                        <i data-lucide="phone-call" class="w-5 h-5"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900">Emergency Contact</h3>
                                </div>
                                <div class="p-4 bg-red-50/50 rounded-xl border border-red-100">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-red-800/60 uppercase tracking-wider">Primary Contact</span>
                                        <a href="tel:<?php echo $emergency_contact ?>" class="text-sm font-bold text-red-700 hover:underline"><?php echo $emergency_contact ?></a>
                                    </div>
                                </div>
                            </div>

                            <!-- Bed Info Widget -->
                            <?php if($bed_info){ ?>
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                                        <i data-lucide="bed" class="w-5 h-5"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900">Inpatient Details</h3>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-center">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Ward</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo $bed_info['ward_name']; ?></p>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-center">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Room</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo $bed_info['room_no']; ?></p>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-center">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Bed</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo $bed_info['bed_no']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>

                        <!-- Right Column: Tabs and Details -->
                        <div class="lg:col-span-8 space-y-6">
                            
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col min-h-[600px]">
                                <!-- Tabs Navigation -->
                                <div class="flex border-b border-gray-100 overflow-x-auto custom-scrollbar bg-gray-50/30">
                                    <button id="overviewBtn" onclick="showTab('overview')"
                                        class="tab-btn flex items-center gap-2 px-6 py-4 text-sm font-semibold transition-all whitespace-nowrap tab-btn-active">
                                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                                        Medical Overview
                                    </button>

                                    <button id="appointmentsBtn" onclick="showTab('appointments')"
                                        class="tab-btn flex items-center gap-2 px-6 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 transition-all whitespace-nowrap">
                                        <i data-lucide="calendar-days" class="w-4 h-4"></i>
                                        Appointments
                                    </button>

                                    <button id="documentBtn" onclick="showTab('document')"
                                        class="tab-btn flex items-center gap-2 px-6 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 transition-all whitespace-nowrap">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                        Documents
                                    </button>

                                    <button id="billingBtn" onclick="showTab('billing')"
                                        class="tab-btn flex items-center gap-2 px-6 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 transition-all whitespace-nowrap">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                                        Billing
                                    </button>
                                </div>

                                <!-- Tab Contents -->
                                <div class="p-6 flex-1">
                                    
                                    <!-- Overview Tab -->
                                    <div id="overview" class="tab-content animate-in fade-in duration-300">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                                        <span class="w-2 h-6 bg-red-500 rounded-full"></span>
                                                        Known Allergies
                                                    </h4>
                                                </div>
                                                <div class="flex flex-wrap gap-2 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                                    <?php 
                                                    $has_allergies = false;
                                                    foreach ($allergies as $allergy) { 
                                                        if(trim($allergy) != ''): 
                                                            $has_allergies = true;
                                                    ?>
                                                        <span class="px-3 py-1.5 bg-white text-red-700 text-xs font-bold rounded-lg border border-red-100 shadow-sm">
                                                            <?php echo trim($allergy); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    <?php } ?>
                                                    <?php if(!$has_allergies): ?>
                                                        <p class="text-sm text-gray-400 italic">No allergies recorded.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                                        <span class="w-2 h-6 bg-blue-500 rounded-full"></span>
                                                        Current Medications
                                                    </h4>
                                                </div>
                                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                                    <ul class="space-y-3">
                                                        <?php 
                                                        $has_meds = false;
                                                        foreach ($medications as $medi) { 
                                                            if(trim($medi) != ''): 
                                                                $has_meds = true;
                                                        ?>
                                                            <li class="text-sm text-gray-700 flex items-center gap-3 bg-white p-2.5 rounded-xl border border-gray-100 shadow-sm">
                                                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                                <span class="font-medium"><?php echo trim($medi); ?></span>
                                                            </li>
                                                            <?php endif; ?>
                                                        <?php } ?>
                                                        <?php if(!$has_meds): ?>
                                                            <p class="text-sm text-gray-400 italic">No active medications.</p>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Appointments Tab -->
                                    <div id="appointments" class="tab-content hidden animate-in fade-in duration-300">
                                        <div class="flex items-center justify-between mb-6">
                                            <h3 class="text-lg font-bold text-gray-900">Appointment History</h3>
                                         
                                        </div>
                                        <div class="overflow-x-auto custom-scrollbar -mx-6">
                                            <table class="w-full data-table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Doctor</th>
                                                        <th>Department</th>
                                                        <th>Date & Time</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php                                                                                  
                                                        $patient_appointment = "select a.*, d.doctor_name from appointments a left join doctor d on a.doctor_id=d.doctor_id where a.patient_id='$patient_id' and (a.delete_flag=0 or a.delete_flag is null) order by a.appointment_date desc";
                                                        $appointment_info = $conn->query($patient_appointment);
                                                        if($appointment_info->num_rows > 0) {
                                                            while($app = $appointment_info->fetch_assoc()) {
                                                                $status_style = match($app['status']) {
                                                                    'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                                    'Pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                                    'Cancelled' => 'bg-red-50 text-red-700 border-red-100',
                                                                    default => 'bg-gray-50 text-gray-700 border-gray-100'
                                                                };
                                                    ?>
                                                  <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer"
                                                    onclick="window.location='view_appointment.php?id=<?php echo $app['appointment_id']; ?>'">

                                                    <td class="font-semibold text-gray-900">
                                                        #<?php echo $app['appointment_no']; ?>
                                                    </td>

                                                    <td>
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-[10px] font-bold">
                                                                <?php echo strtoupper(substr($app['doctor_name'], 0, 2)); ?>
                                                            </div>
                                                            <span class="font-medium"><?php echo $app['doctor_name']; ?></span>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="text-xs font-medium px-2 py-1 bg-gray-100 rounded-md text-gray-600">
                                                            <?php echo $app['department']; ?>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="flex flex-col">
                                                            <span class="font-medium">
                                                                <?php echo date("d M, Y", strtotime($app['appointment_date'])); ?>
                                                            </span>
                                                            <span class="text-xs text-gray-400">
                                                                <?php echo $app['appointment_time']; ?>
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider border <?php echo $status_style; ?>">
                                                            <?php echo $app['opd_ipd_type']; ?>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border <?php echo $status_style; ?>">
                                                            <?php echo $app['status']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                
                                                    <?php
                                                            }
                                                        } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="5">
                                                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                                    <i data-lucide="calendar-x" class="w-8 h-8 text-gray-300"></i>
                                                                </div>
                                                                <p class="text-gray-500 font-medium">No appointment history found</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Documents Tab -->
                                    <div id="document" class="tab-content hidden animate-in fade-in duration-300">
                                        <div class="flex items-center justify-between mb-6">
                                             <h3 class="text-lg font-bold text-gray-900">Medical Records & Documents</h3>
                                        </div>
                                        <div class="overflow-x-auto custom-scrollbar -mx-6">
                                            <table class="w-full data-table">
                                                <thead>
                                                    <tr>
                                                        <th>Document Name</th>
                                                        <th>Type</th>
                                                        <th>Date Added</th>
                                                        
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Define the columns for medical files
                                                    $medical_file_columns = [
                                                        'prescription_file' => 'Prescription',
                                                        'lab_report_file' => 'Lab Report',
                                                        'xray_file' => 'X-Ray',
                                                        'mri_file' => 'MRI Scan',
                                                        'ctscan_file' => 'CT Scan',
                                                        'other_document' => 'Other Document'
                                                    ];

                                                    $all_docs = [];
                                                    $unique_files = []; // To prevent duplicates

                                                    // 1. Fetch from patient_documents (Personal Docs)
                                                    $document_query = "select * from patient_documents where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) order by document_date DESC";
                                                    $document_result = $conn->query($document_query);
                                                    if($document_result && $document_result->num_rows > 0){
                                                        while($doc = $document_result->fetch_assoc()){
                                                            $all_docs[] = [
                                                                'name' => $doc['document_name'],
                                                                'type' => $doc['document_type'],
                                                                'date' => $doc['document_date'],
                                                                'file' => $doc['upload_file'],
                                                                'note' => $doc['note'] ?? '',
                                                                'path_prefix' => '/UltraHospital-main/'
                                                            ];
                                                        }
                                                    }

                                                    // 2. Fetch from IPD Admissions (Medical Docs)
                                                    $ipd_query = "select * from ipd_admissions where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) order by admission_date DESC";
                                                    $ipd_result = $conn->query($ipd_query);
                                                    if($ipd_result && $ipd_result->num_rows > 0){
                                                        while($ipd = $ipd_result->fetch_assoc()){
                                                            foreach($medical_file_columns as $col => $label){
                                                                if(!empty($ipd[$col]) && !isset($unique_files[$ipd[$col]])){
                                                                    $all_docs[] = [
                                                                        'name' => $label . " (IPD)",
                                                                        'type' => 'Medical',
                                                                        'date' => $ipd['admission_date'],
                                                                        'file' => "uploads/documents/" . $ipd[$col],
                                                                        'note' => $ipd['disease_reason'] ?? '',
                                                                        'path_prefix' => '/UltraHospital-main/'
                                                                    ];
                                                                    $unique_files[$ipd[$col]] = true;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // 3. Fetch from Appointments/OPD (Medical Docs) - Skip if already found in IPD
                                                    $opd_query = "select * from appointments where patient_id='$patient_id' and opd_ipd_type='OPD' and (delete_flag=0 or delete_flag is null) order by appointment_date DESC";
                                                    $opd_result = $conn->query($opd_query);
                                                    if($opd_result && $opd_result->num_rows > 0){
                                                        while($opd = $opd_result->fetch_assoc()){
                                                            foreach($medical_file_columns as $col => $label){
                                                                if(!empty($opd[$col]) && !isset($unique_files[$opd[$col]])){
                                                                    $all_docs[] = [
                                                                        'name' => $label . " (OPD)",
                                                                        'type' => 'Medical',
                                                                        'date' => $opd['appointment_date'],
                                                                        'file' => "uploads/documents/" . $opd[$col],
                                                                        'note' => $opd['reason'] ?? '',
                                                                        'path_prefix' => '/UltraHospital-main/'
                                                                    ];
                                                                    $unique_files[$opd[$col]] = true;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // Sort all by date descending
                                                    usort($all_docs, function($a, $b) {
                                                        return strtotime($b['date']) - strtotime($a['date']);
                                                    });

                                                    if(count($all_docs) > 0){
                                                        foreach($all_docs as $doc){
                                                            $file_ext = pathinfo($doc['file'], PATHINFO_EXTENSION);
                                                            $icon = match(strtolower($file_ext)) {
                                                                'pdf' => 'file-text',
                                                                'jpg', 'jpeg', 'png' => 'image',
                                                                default => 'file'
                                                            };
                                                    ?>
                                                     <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer" onclick="window.open('<?php echo $doc['path_prefix'] . $doc['file']; ?>', '_blank')">

                                                            <td>
                                                                <div class="flex items-center gap-3">
                                                                    <div class="p-2 bg-gray-100 rounded-lg text-gray-500">
                                                                        <i data-lucide="<?php echo $icon; ?>" class="w-4 h-4"></i>
                                                                    </div>
                                                                    <div>
                                                                        <p class="font-semibold text-gray-900">
                                                                            <?php echo $doc['name']; ?>
                                                                        </p>
                                                                        <p class="text-[10px] text-gray-400 max-w-[200px] truncate">
                                                                            <?php echo $doc['note']; ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <span class="text-xs font-medium text-gray-600">
                                                                    <?php echo strtoupper($doc['type']); ?>
                                                                </span>
                                                            </td>

                                                            <td>
                                                                <span class="text-sm text-gray-500">
                                                                    <?php echo date("d M, Y", strtotime($doc['date'])); ?>
                                                                </span>
                                                            </td>

                                                        </tr>
                                                    <?php
                                                        }
                                                    } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="4">
                                                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                                    <i data-lucide="folder-open" class="w-8 h-8 text-gray-300"></i>
                                                                </div>
                                                                <p class="text-gray-500 font-medium">No documents uploaded yet</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>


                                    <!-- Billing Tab -->
                                    <div id="billing" class="tab-content hidden animate-in fade-in duration-300">
                                        <div class="flex flex-col items-center justify-center py-20 text-center">
                                            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                                                <i data-lucide="receipt-text" class="w-10 h-10 text-gray-200"></i>
                                            </div>
                                            <h3 class="text-lg font-bold text-gray-900 mb-2">No Invoices Found</h3>
                                            <p class="text-gray-500 max-w-xs mx-auto">There are currently no billing records or pending invoices for this patient.</p>
                                            <button class="mt-8 px-6 py-2.5 bg-gray-900 text-white rounded-xl font-semibold text-sm hover:bg-gray-800 transition-all shadow-lg shadow-gray-200">
                                                Create New Invoice
                                            </button>
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
        // Initialize Lucide Icons
        lucide.createIcons();

        function showTab(tabId){
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(function(content){
                content.classList.add('hidden');
            });

            // Reset all tab buttons
            document.querySelectorAll('.tab-btn').forEach(function(btn){
                btn.classList.remove('tab-btn-active', 'text-blue-600');
                btn.classList.add('text-gray-500');
            });

            // Show selected tab content
            const activeTab = document.getElementById(tabId);
            activeTab.classList.remove('hidden');
            
            // Set active button style
            const activeBtn = document.getElementById(tabId + 'Btn');
            activeBtn.classList.add('tab-btn-active', 'text-blue-600');
            activeBtn.classList.remove('text-gray-500');
            
            // Re-render icons for dynamic content if needed
            lucide.createIcons();
        }
    </script>
</body>
</html>

<?php
        }
    }
}
?>
