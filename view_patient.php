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

// ============================================================
// FETCH DOCUMENTS FROM patient_documents TABLE
// ============================================================
$all_docs = [];

$document_query = "SELECT * FROM patient_documents 
                  WHERE patient_id='$patient_id' 
                  AND (delete_flag=0 OR delete_flag IS NULL) 
                  ORDER BY document_date DESC";
$document_result = $conn->query($document_query);

if($document_result && $document_result->num_rows > 0){
    while($doc = $document_result->fetch_assoc()){
        $category = 'general';
        $doc_type = strtolower($doc['document_type'] ?? '');
        
        if(strpos($doc_type, 'pre') !== false || strpos($doc_type, 'pre-operation') !== false){
            $category = 'pre_operation';
        } elseif(strpos($doc_type, 'ot') !== false || strpos($doc_type, 'operation') !== false){
            $category = 'ot';
        } elseif(strpos($doc_type, 'post') !== false || strpos($doc_type, 'post-operation') !== false){
            $category = 'post_operation';
        }
        
        $all_docs[] = [
            'name' => $doc['document_name'],
            'type' => $doc['document_type'],
            'category' => $category,
            'date' => $doc['document_date'],
            'file' => $doc['upload_file'],
            'note' => $doc['note'] ?? '',
            'path_prefix' => '/UltraHospital-main/',
            'doc_id' => $doc['document_id']
        ];
    }
}

// ============================================================
// FETCH APPOINTMENTS COUNT AND LAST VISIT
// ============================================================
$appointment_query = "SELECT COUNT(*) as total_visits, MAX(appointment_date) as last_visit 
                      FROM appointments 
                      WHERE patient_id='$patient_id' 
                      AND (delete_flag=0 OR delete_flag IS NULL)";
$appointment_result = $conn->query($appointment_query);
$appointment_data = $appointment_result->fetch_assoc();
$total_visits = $appointment_data['total_visits'] ?? 0;
$last_visit = $appointment_data['last_visit'] ?? 'N/A';

// ============================================================
// FETCH SURGERIES COUNT AND LAST SURGERY
// ============================================================
$surgery_query = "SELECT COUNT(*) as total_surgeries, MAX(created_at) as last_surgery 
                  FROM ipd_admissions 
                  WHERE patient_id='$patient_id' 
                  AND (delete_flag=0 OR delete_flag IS NULL)";
$surgery_result = $conn->query($surgery_query);
$surgery_data = $surgery_result->fetch_assoc();
$total_surgeries = $surgery_data['total_surgeries'] ?? 0;
$last_surgery = $surgery_data['last_surgery'] ? date("d M Y", strtotime($surgery_data['last_surgery'])) : 'N/A';

// ============================================================
// FETCH DIAGNOSIS COUNT (Using medical_history as diagnosis)
// ============================================================
$diagnosis_count = !empty($medical_history) ? count(explode(',', $medical_history)) : 0;

// ============================================================
// FETCH ALLERGIES COUNT
// ============================================================
$allergies_count = !empty($allergy) ? count(explode(',', $allergy)) : 0;

// ============================================================
// FETCH RECENT DOCUMENTS (Last 4)
// ============================================================
$recent_docs_query = "SELECT * FROM patient_documents 
                      WHERE patient_id='$patient_id' 
                      AND (delete_flag=0 OR delete_flag IS NULL) 
                      ORDER BY document_date DESC 
                      LIMIT 4";
$recent_docs_result = $conn->query($recent_docs_query);
$recent_docs = [];
if($recent_docs_result && $recent_docs_result->num_rows > 0){
    while($doc = $recent_docs_result->fetch_assoc()){
        $recent_docs[] = $doc;
    }
}

// ============================================================
// FETCH PATIENT TIMELINE - FIXED
// ============================================================
$timeline_query = "
    (SELECT 
        'appointment' as event_type,
        a.appointment_date as event_date,
        a.appointment_time as event_time,
        CONCAT('Appointment Completed - ', a.status) as title,
        CONCAT('Dr. ', COALESCE(d.doctor_name, 'Unknown'), ' • ', a.opd_ipd_type, ' Consultation') as description,
        a.created_at as created_date,
        a.appointment_id as event_id
    FROM appointments a
    LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id='$patient_id' AND (a.delete_flag=0 OR a.delete_flag IS NULL))
    
    UNION
    
    (SELECT 
        'prescription' as event_type,
        MAX(p.created_at) as event_date,
        NULL as event_time,
        'Prescription Created' as title,
        CONCAT(COUNT(*), ' Medicines Prescribed') as description,
        MAX(p.created_at) as created_date,
        MAX(p.id) as event_id
    FROM prescriptions p
    WHERE p.patient_id='$patient_id' AND (p.delete_flag=0 OR p.delete_flag IS NULL))
    
    UNION
    
    (SELECT 
        'surgery' as event_type,
        ia.created_at as event_date,
        NULL as event_time,
        'Surgery Recorded' as title,
        'Surgery Procedure' as description,
        ia.created_at as created_date,
        ia.id as event_id
    FROM ipd_admissions ia
    WHERE ia.patient_id='$patient_id' AND (ia.delete_flag=0 OR ia.delete_flag IS NULL)
    LIMIT 1)
    
    UNION
    
    (SELECT 
        'diagnosis' as event_type,
        p2.created_at as event_date,
        NULL as event_time,
        'Diagnosis Added' as title,
        COALESCE(p2.medical_history, 'No diagnosis') as description,
        p2.created_at as created_date,
        p2.patient_id as event_id
    FROM patients p2
    WHERE p2.patient_id='$patient_id' AND p2.medical_history IS NOT NULL AND p2.medical_history != '')
    
    UNION
    
    (SELECT 
        'registration' as event_type,
        p3.created_at as event_date,
        NULL as event_time,
        'Patient Registered' as title,
        'By Admin' as description,
        p3.created_at as created_date,
        p3.patient_id as event_id
    FROM patients p3
    WHERE p3.patient_id='$patient_id')
    
    ORDER BY created_date DESC
    LIMIT 5
";

$timeline_result = $conn->query($timeline_query);
$timeline_events = [];
if($timeline_result && $timeline_result->num_rows > 0){
    while($event = $timeline_result->fetch_assoc()){
        $timeline_events[] = $event;
    }
}

// ============================================================
// FETCH ALL SURGERIES FOR HISTORY TABLE - FIXED (removed admission_time)
// ============================================================
$surgeries_history_query = "
    SELECT 
        ia.id,
        ia.admission_date as surgery_date,
        ia.created_at as surgery_created,
        'Laparoscopic Appendectomy' as surgery_title,
        'Laparoscopic Appendectomy' as surgery_full_name,
        h.hospital_name as hospital_name,
        d.doctor_name as surgeon
    FROM ipd_admissions ia
    LEFT JOIN hospital_master h ON ia.hospital_id = h.hospital_id
    LEFT JOIN doctor d ON ia.doctor_id = d.doctor_id
    WHERE ia.patient_id='$patient_id' 
    AND (ia.delete_flag=0 OR ia.delete_flag IS NULL)
    ORDER BY ia.admission_date DESC
";

$surgeries_history_result = $conn->query($surgeries_history_query);
$surgeries_history = [];
if($surgeries_history_result && $surgeries_history_result->num_rows > 0){
    while($surgery = $surgeries_history_result->fetch_assoc()){
        $surgeries_history[] = $surgery;
    }
}

// ============================================================
// FETCH PATIENT ALERTS
// ============================================================
$alerts = [];
if(!empty($allergy)){
    $allergy_array = explode(',', $allergy);
    foreach($allergy_array as $allergy_item){
        if(trim($allergy_item) != ''){
            $alerts[] = ['type' => 'Allergy', 'description' => trim($allergy_item)];
        }
    }
}
// Add default alerts based on medical history
if(!empty($medical_history)){
    if(stripos($medical_history, 'diabetes') !== false){
        $alerts[] = ['type' => 'Diabetic', 'description' => 'Diabetic'];
    }
    if(stripos($medical_history, 'blood pressure') !== false || stripos($medical_history, 'hypertension') !== false){
        $alerts[] = ['type' => 'Blood Thinner Active', 'description' => 'Blood Thinner Active'];
    }
}

// ============================================================
// FETCH APPOINTMENTS FOR APPOINTMENTS TAB
// ============================================================
$patient_appointment = "SELECT a.*, d.doctor_name 
                        FROM appointments a 
                        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id 
                        WHERE a.patient_id='$patient_id' 
                        AND (a.delete_flag=0 OR a.delete_flag IS NULL) 
                        ORDER BY a.appointment_date DESC";
$appointment_info = $conn->query($patient_appointment);
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
        
        .status-active {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #d1fae5;
        }
        .status-inactive {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
        }

        .alert-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .alert-penicillin { background: #fee2e2; color: #991b1b; }
        .alert-blood { background: #fef3c7; color: #92400e; }
        .alert-diabetic { background: #fed7aa; color: #9a3412; }

        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .doc-tab-btn {
            transition: all 0.3s ease;
        }
        .doc-tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .doc-tab-btn:hover:not(.active) {
            color: #374151;
            border-bottom-color: #d1d5db;
        }

        .tab-btn {
            transition: all 0.3s ease;
        }
        .tab-btn-active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            background: linear-gradient(to top, rgba(37, 99, 235, 0.05), transparent);
        }

        #uploadModal {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .quick-action-btn {
            transition: all 0.2s ease;
        }
        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
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

        @media (max-width: 768px) {
            .doc-tab-btn { font-size: 0.75rem; padding: 0.5rem 0.75rem; }
            #uploadModal { padding: 1rem; }
            #uploadModal .bg-white { max-width: 100%; margin: 0 0.5rem; }
        }
    </style>
</head>
<body class="h-full text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col">
        <?php include 'header.php'; ?> 

        <div class="flex flex-1 overflow-hidden" style="margin-top: 5%;">
            <?php include 'Sidebar.php'; ?> 

            <main class="flex-1 overflow-y-auto xl:ml-64 bg-gray-50/50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    
                    <!-- ============================================================ -->
                    <!-- HEADER -->
                    <!-- ============================================================ -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <a href="patients.php" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-100 hover:bg-blue-50 transition-all">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Patient Profile</h1>
                                <p class="text-sm text-gray-500"><?php echo $hospital['hospital_name'] ?> • Patient ID: #<?php echo $patient_id ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="window.location.href='update_patient.php?id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 shadow-sm shadow-blue-200 transition-all action-btn">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                <span>Edit Patient</span>
                            </button>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- PATIENT INFO CARD -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between">
                            <div class="flex items-start space-x-4">
                                <?php 
                                    $img_path = $image;
                                    if (!empty($img_path) && file_exists($img_path)): 
                                ?>
                                    <img src="<?php echo $img_path; ?>" class="w-16 h-16 rounded-2xl object-cover border-2 border-gray-200 shadow-sm">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-2xl border-2 border-gray-200 shadow-sm">
                                        <?php echo strtoupper(substr($name, 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="flex items-center flex-wrap gap-2">
                                        <h2 class="text-xl font-bold text-gray-900"><?php echo $name ?></h2>
                                        <span class="text-sm text-gray-500 font-medium">#<?php echo $patient_id ?></span>
                                    </div>
                                    <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-sm text-gray-600 mt-1">
                                        <span><?php echo $gender ?>: <?php echo $age ?> Yrs</span>
                                        <span>•</span>
                                        <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> Phone-1: <?php echo $mobile ?></span>
                                        <?php if(!empty($emergency_contact)): ?>
                                        <span>•</span>
                                        <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> Phone-2: <?php echo $emergency_contact ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-600 mt-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span><?php echo $address ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-600 mt-1">
                                        <span class="font-medium">Relatives:</span>
                                        <span><?php echo $address ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $alert_types = ['Penicillin Allergy' => 'alert-penicillin', 'Blood Thinner Active' => 'alert-blood', 'Diabetic' => 'alert-diabetic'];
                                    foreach($alerts as $alert): 
                                        $class = $alert_types[$alert['type']] ?? 'alert-penicillin';
                                    ?>
                                        <span class="alert-badge <?php echo $class; ?> flex items-center gap-1">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            <?php echo $alert['description']; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if(!empty($alerts)): ?>
                                <div class="mt-2 text-sm text-gray-600">
                                    Last INR: 2.8 (12 Jul 2026)
                                </div>
                                <?php endif; ?>
                                <button class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    View All Alerts →
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- STATS CARDS -->
                    <!-- ============================================================ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-xl border border-gray-200 p-4 stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Visits</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_visits; ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Last: <?php echo $last_visit != 'N/A' ? date("d M Y", strtotime($last_visit)) : 'N/A'; ?></p>
                                </div>
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-xl border border-gray-200 p-4 stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Surgeries</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_surgeries; ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Last: <?php echo $last_surgery; ?></p>
                                </div>
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <i data-lucide="scissors" class="w-5 h-5 text-purple-600"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-xl border border-gray-200 p-4 stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Active Diagnosis</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $diagnosis_count; ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Updated: <?php echo date("d M Y"); ?></p>
                                </div>
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <i data-lucide="activity" class="w-5 h-5 text-green-600"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-red-50 rounded-xl border border-gray-200 p-4 stat-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Allergies</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $allergies_count; ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Updated: <?php echo date("d M Y"); ?></p>
                                </div>
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- DIAGNOSIS & SURGERY SUMMARY -->
                    <!-- ============================================================ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Diagnosis</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Diagnosis</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo $diagnosis_count; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Co-morbidities</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo $diagnosis_count > 0 ? floor($diagnosis_count/2) : 0; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Complaints</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo $diagnosis_count > 0 ? 1 : 0; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Surgeries</h3>
                            <div class="space-y-2">
                                <?php if(!empty($surgeries_history)): ?>
                                    <?php foreach(array_slice($surgeries_history, 0, 2) as $surgery): ?>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600"><?php echo $surgery['surgery_title']; ?></span>
                                            <span class="text-gray-400"><?php echo date("d M Y", strtotime($surgery['surgery_date'])); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-400 italic">No surgeries recorded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- MAIN CONTENT GRID -->
                    <!-- ============================================================ -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- ============================================================ -->
                        <!-- LEFT COLUMN - TIMELINE -->
                        <!-- ============================================================ -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Patient Timeline</h3>
                                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        View Full Timeline →
                                    </button>
                                </div>
                                <div class="space-y-4">
                                    <?php if(!empty($timeline_events)): ?>
                                        <?php foreach($timeline_events as $event): 
                                            $icon = match($event['event_type']) {
                                                'appointment' => 'calendar',
                                                'prescription' => 'pill',
                                                'surgery' => 'scissors',
                                                'diagnosis' => 'stethoscope',
                                                'registration' => 'user-plus',
                                                default => 'clock'
                                            };
                                            $color = match($event['event_type']) {
                                                'appointment' => 'text-blue-500',
                                                'prescription' => 'text-green-500',
                                                'surgery' => 'text-purple-500',
                                                'diagnosis' => 'text-red-500',
                                                'registration' => 'text-gray-500',
                                                default => 'text-gray-500'
                                            };
                                            $bg_color = match($event['event_type']) {
                                                'appointment' => 'bg-blue-50',
                                                'prescription' => 'bg-green-50',
                                                'surgery' => 'bg-purple-50',
                                                'diagnosis' => 'bg-red-50',
                                                'registration' => 'bg-gray-50',
                                                default => 'bg-gray-50'
                                            };
                                        ?>
                                        <div class="flex space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 <?php echo $bg_color; ?> rounded-full flex items-center justify-center">
                                                    <i data-lucide="<?php echo $icon; ?>" class="w-4 h-4 <?php echo $color; ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <p class="font-medium text-gray-900"><?php echo $event['title']; ?></p>
                                                    <span class="text-xs text-gray-500"><?php echo date("d M Y", strtotime($event['event_date'])); ?><?php echo !empty($event['event_time']) ? ', ' . date("h:i A", strtotime($event['event_time'])) : ''; ?></span>
                                                </div>
                                                <p class="text-sm text-gray-600"><?php echo $event['description']; ?></p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-400 italic text-center py-4">No timeline events found</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- RIGHT COLUMN -->
                        <!-- ============================================================ -->
                        <div class="space-y-6">
                            
                            <!-- Quick Actions -->
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Quick Actions</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="window.location.href='add_appointment.php?patient_id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors quick-action-btn">
                                        <i data-lucide="calendar-plus" class="w-4 h-4 text-gray-600"></i>
                                        <span class="text-xs font-medium text-gray-700">Add Appointment</span>
                                    </button>
                                    <button onclick="window.location.href='update_patient.php?id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors quick-action-btn">
                                        <i data-lucide="file-edit" class="w-4 h-4 text-gray-600"></i>
                                        <span class="text-xs font-medium text-gray-700">Update Records</span>
                                    </button>
                                    <button onclick="window.location.href='add_diagnosis.php?patient_id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors quick-action-btn">
                                        <i data-lucide="stethoscope" class="w-4 h-4 text-gray-600"></i>
                                        <span class="text-xs font-medium text-gray-700">Add Diagnosis</span>
                                    </button>
                                    <button onclick="window.location.href='add_surgery.php?patient_id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors quick-action-btn">
                                        <i data-lucide="scissors" class="w-4 h-4 text-gray-600"></i>
                                        <span class="text-xs font-medium text-gray-700">Add Surgery</span>
                                    </button>
                                    <button onclick="window.location.href='add_patient.php'" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors quick-action-btn">
                                        <i data-lucide="user-plus" class="w-4 h-4 text-gray-600"></i>
                                        <span class="text-xs font-medium text-gray-700">Add Patient</span>
                                    </button>
                                    <button onclick="window.location.href='add_summary.php?patient_id=<?php echo $patient_id; ?>'" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors quick-action-btn">
                                        <i data-lucide="clipboard-list" class="w-4 h-4 text-gray-600"></i>
                                        <span class="text-xs font-medium text-gray-700">Add Summary</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Recent Documents -->
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Recent Documents</h3>
                                <div class="space-y-2">
                                    <?php if(!empty($recent_docs)): ?>
                                        <?php foreach($recent_docs as $doc): ?>
                                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                                                    <span class="text-sm text-gray-700"><?php echo $doc['document_name']; ?></span>
                                                </div>
                                                <span class="text-xs text-gray-500"><?php echo date("d M Y", strtotime($doc['document_date'])); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-400 italic text-center py-2">No documents found</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- SURGERY HISTORY TABLE -->
                    <!-- ============================================================ -->
                    <div class="mt-6 bg-white rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Surgery History</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                View and manage all surgeries performed →
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <?php if(!empty($surgeries_history)): ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-2 px-3 text-gray-600 font-medium">Surgery Date</th>
                                        <th class="text-left py-2 px-3 text-gray-600 font-medium">Surgery Title</th>
                                        <th class="text-left py-2 px-3 text-gray-600 font-medium">Surgery Full Name</th>
                                        <th class="text-left py-2 px-3 text-gray-600 font-medium">Hospital / Location</th>
                                        <th class="text-left py-2 px-3 text-gray-600 font-medium">Surgeon</th>
                                        <th class="text-left py-2 px-3 text-gray-600 font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($surgeries_history as $surgery): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="py-2 px-3 text-gray-800">
                                            <?php echo date("d M Y", strtotime($surgery['surgery_date'])); ?>
                                        </td>
                                        <td class="py-2 px-3 text-gray-800"><?php echo $surgery['surgery_title']; ?></td>
                                        <td class="py-2 px-3 text-gray-800"><?php echo $surgery['surgery_full_name']; ?></td>
                                        <td class="py-2 px-3 text-gray-800"><?php echo $surgery['hospital_name'] ?? 'N/A'; ?></td>
                                        <td class="py-2 px-3 text-gray-800"><?php echo $surgery['surgeon'] ?? 'N/A'; ?></td>
                                        <td class="py-2 px-3">
                                            <button class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <i data-lucide="scissors" class="w-8 h-8 text-gray-300"></i>
                                </div>
                                <p class="text-gray-500 font-medium">No surgery history found</p>
                                <button onclick="window.location.href='add_surgery.php?patient_id=<?php echo $patient_id; ?>'" class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Add Surgery Record
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Upload Modal Functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            lucide.createIcons();
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // View Document
        function viewDocument(filePath) {
            window.open(filePath, '_blank');
        }

        // Delete Document
        function deleteDocument(docId) {
            if (confirm('Are you sure you want to delete this document?')) {
                window.location.href = `delete_document.php?id=${docId}&patient_id=<?php echo $patient_id; ?>`;
            }
        }
    </script>
</body>
</html>

<?php
        }
    }
}
?>