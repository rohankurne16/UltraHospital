<?php
session_start();
include "../config/hospital.php";

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
$last_visit = $appointment_data['last_visit'] ?? '-';

// ============================================================
// FETCH SURGERIES COUNT AND LAST SURGERY
// ============================================================
$surgery_query = "SELECT COUNT(*) as total_surgeries, MAX(created_at) as last_surgery 
                  FROM surgeries 
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
// FETCH ALL SURGERIES FOR HISTORY TABLE
// ============================================================
$hospital_id = $_SESSION['hospital_id'];

$surgeries_history_query = "
SELECT
    s.surgery_id,
    s.surgery_no,
    s.surgery_title,
    s.surgery_full_name,
    s.surgery_date,
    s.surgery_time,
    s.status,
    s.surgery_type,
    s.surgery_category,
    s.surgeon_name,
    h.hospital_name,
    d.doctor_name
FROM surgeries s
LEFT JOIN hospital_master h
    ON s.hospital_id = h.hospital_id
LEFT JOIN doctor d
    ON s.doctor_id = d.doctor_id
WHERE s.patient_id = '$patient_id'
AND s.hospital_id = '$hospital_id'
AND (s.delete_flag = 0 OR s.delete_flag IS NULL)
ORDER BY s.surgery_date DESC, s.created_at DESC
";

$surgeries_history_result = mysqli_query($conn, $surgeries_history_query);

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

        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background: #f8fafc;
        }

        .action-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Patient timeline */
        .timeline-list { position: relative; }
        .timeline-list::before {
            content: '';
            position: absolute;
            top: 0.75rem;
            bottom: 0.75rem;
            left: 0.75rem;
            width: 2px;
            background: linear-gradient(to bottom, #bfdbfe, #e5e7eb 85%, transparent);
        }
        .timeline-item {
            position: relative;
            padding-left: 2.75rem;
            padding-bottom: 1.25rem;
        }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-marker {
            position: absolute;
            z-index: 1;
            top: 0.15rem;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.55rem;
            height: 1.55rem;
            border: 4px solid #fff;
            border-radius: 9999px;
            box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.35), 0 3px 8px rgba(15, 23, 42, 0.08);
        }
        .timeline-card {
            border: 1px solid #eef2f7;
            border-radius: 0.875rem;
            background: #fff;
            padding: 0.9rem 1rem;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .timeline-card:hover {
            transform: translateY(-2px);
            border-color: #dbeafe;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }
        .timeline-date {
            flex-shrink: 0;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            line-height: 1.35;
            text-align: right;
        }
        @media (max-width: 640px) {
            .timeline-item { padding-left: 2.35rem; }
            .timeline-list::before { left: 0.65rem; }
            .timeline-marker { width: 1.35rem; height: 1.35rem; }
            .timeline-card { padding: 0.8rem; }
            .timeline-card > div:first-child { flex-direction: column; gap: 0.45rem; }
            .timeline-date { text-align: left; }
        }

        @media (max-width: 768px) {
            .doc-tab-btn { font-size: 0.75rem; padding: 0.5rem 0.75rem; }
        }
    </style>
</head>
<body class="h-full text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col">
        <?php include '../header.php'; ?> 

        <div class="flex flex-1 overflow-hidden">
            <?php include '../Sidebar.php'; ?> 

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
                                        <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> Self Number: <?php echo $mobile ?></span>
                                        <?php if(!empty($emergency_contact)): ?>
                                        <span>•</span>
                                        <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> Relative Number: <?php echo $emergency_contact ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-600 mt-2">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-blue-500"></i>
                                        <span class="font-medium"><?php echo $address ?></span>
                                    </div>

                                    <div class="flex items-center gap-3 text-sm text-gray-600 mt-2">
                                        <span class="font-medium text-gray-700">Blood Group:</span>
                                        <span class="px-2 py-1 bg-red-100 text-red-600 rounded-md font-semibold">
                                            <?php echo $blood_group ?>
                                        </span>

                                        <span class="text-gray-400">•</span>

                                        <span class="font-medium text-gray-700">DOB:</span>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md">
                                            <?php echo $dob ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-4 mt-3 text-sm">
                                        <div class="flex items-center gap-1 bg-green-50 px-3 py-1 rounded-lg">
                                            <span class="font-semibold text-green-700">Last Visit:</span>
                                            <span class="text-gray-700"><?php echo $last_visit ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- ALERTS -->
                    <!-- ============================================================ -->
                    <?php if (!empty($alerts)): ?>
                    <div class="bg-red-100 border border-red-500 rounded-lg p-3 mt-3 mb-6">
                        <div class="flex flex-wrap gap-4">
                            <?php foreach ($alerts as $alert): ?>
                                <div class="flex items-center text-red-700 font-medium">
                                    <i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>
                                    <span>
                                        <strong><?php echo htmlspecialchars($alert['type']); ?>:</strong>
                                        <?php echo htmlspecialchars($alert['description']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

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
                    <!-- LEFT COLUMN - TIMELINE & SURGERY HISTORY -->
                    <!-- ============================================================ -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- LEFT COLUMN (2/3 width) -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- ============================================================ -->
                            <!-- DIAGNOSIS & SURGERY SUMMARY -->
                            <!-- ============================================================ -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-xl border border-gray-200 p-4">
                                    <h3 class="text-sm font-medium text-gray-700 mb-3">Diagnosis</h3>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600">Diagnosis</p>
                                            <p class="text-xl font-semibold text-gray-900"><?php echo $diagnosis_count; ?></p>
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
                            <!-- PATIENT TIMELINE -->
                            <!-- ============================================================ -->
                            <?php
                            $timeline_query = "
    (SELECT 
        'appointment' as event_type,
        a.appointment_date as event_date,
        a.appointment_time as event_time,
        CONCAT('Appointment - ', a.status) as title,
        CONCAT(COALESCE(d.doctor_name, 'Unknown'), ' • ', a.opd_ipd_type, ' Consultation') as description,
        a.created_at as created_date,
        a.appointment_id as event_id,
        a.status as event_status
    FROM appointments a
    LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = $patient_id 
    AND (a.delete_flag = 0 OR a.delete_flag IS NULL))
    
    UNION
    
    (SELECT 
        'prescription' as event_type,
        p.created_at as event_date,
        NULL as event_time,
        'Prescription Created' as title,
        CONCAT(COUNT(pd.detail_id), ' Medicines Prescribed') as description,
        p.created_at as created_date,
        p.prescription_id as event_id,
        NULL as event_status
    FROM prescription_master p
    LEFT JOIN prescription_details pd ON p.prescription_id = pd.prescription_id
    WHERE p.patient_id = $patient_id 
    AND (p.delete_flag = 0 OR p.delete_flag IS NULL)
    GROUP BY p.prescription_id)
    
    UNION
    
    (SELECT 
        'surgery' as event_type,
        s.surgery_date as event_date,
        s.surgery_time as event_time,
        CONCAT('Surgery - ', s.status) as title,
        s.surgery_title as description,
        s.created_at as created_date,
        s.surgery_id as event_id,
        s.status as event_status
    FROM surgeries s
    WHERE s.patient_id = $patient_id 
    AND (s.delete_flag = 0 OR s.delete_flag IS NULL))
    
    UNION
    
    (SELECT 
        'vitals' as event_type,
        v.recorded_at as event_date,
        NULL as event_time,
        'Vitals Recorded' as title,
        CONCAT('BP: ', v.bp, ' | Pulse: ', v.pulse, ' | Temp: ', v.temperature, '°C') as description,
        v.recorded_at as created_date,
        v.vital_id as event_id,
        NULL as event_status
    FROM patient_vitals v
    WHERE v.patient_id = $patient_id 
    AND (v.delete_flag = 0 OR v.delete_flag IS NULL))
    
    UNION
    
    (SELECT 
        'lab_report' as event_type,
        lr.created_at as event_date,
        NULL as event_time,
        'Lab Report' as title,
        CONCAT('Report #', lr.report_no) as description,
        lr.created_at as created_date,
        lr.report_id as event_id,
        lr.report_status as event_status
    FROM lab_reports lr
    WHERE lr.patient_id = $patient_id 
    AND (lr.delete_flag = 0 OR lr.delete_flag IS NULL))
    
    UNION
    
    (SELECT 
        'diagnosis' as event_type,
        p2.created_at as event_date,
        NULL as event_time,
        'Diagnosis Added' as title,
        COALESCE(p2.medical_history, 'No diagnosis') as description,
        p2.created_at as created_date,
        p2.patient_id as event_id,
        NULL as event_status
    FROM patients p2
    WHERE p2.patient_id = $patient_id 
    AND p2.medical_history IS NOT NULL 
    AND p2.medical_history != '')
    
    UNION
    
    (SELECT 
        'registration' as event_type,
        p3.created_at as event_date,
        NULL as event_time,
        'Patient Registered' as title,
        CONCAT('Registered as ', p3.patient_admission_type, ' patient') as description,
        p3.created_at as created_date,
        p3.patient_id as event_id,
        NULL as event_status
    FROM patients p3
    WHERE p3.patient_id = $patient_id)
    
    ORDER BY created_date DESC
    LIMIT 10
";

$timeline_result = $conn->query($timeline_query);

if (!$timeline_result) {
    die("Timeline Query Error: " . $conn->error);
}

$timeline_events = [];
if ($timeline_result && $timeline_result->num_rows > 0) {
    while ($event = $timeline_result->fetch_assoc()) {
        $timeline_events[] = $event;
    }
}

if (empty($timeline_events)) {
    $default_sql = "
        SELECT 
            'registration' as event_type,
            created_at as event_date,
            NULL as event_time,
            'Patient Registered' as title,
            CONCAT('Registered as ', patient_admission_type, ' patient') as description,
            created_at as created_date,
            patient_id as event_id,
            NULL as event_status
        FROM patients
        WHERE patient_id = $patient_id
    ";
    
    $default_result = $conn->query($default_sql);
    if ($default_result && $default_result->num_rows > 0) {
        while ($event = $default_result->fetch_assoc()) {
            $timeline_events[] = $event;
        }
    }
}
?>

                            <?php if (!empty($timeline_events)): ?>
                            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                            <i data-lucide="clock" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h2 class="font-semibold text-gray-900">Patient Timeline</h2>
                                            <p class="text-xs text-gray-500">Recent activities and events</p>
                                        </div>
                                    </div>
                                </div>
                                                                <div class="p-4 sm:p-6">
                                    <div class="timeline-list">
                                        <?php foreach ($timeline_events as $index => $event): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker  
                                                <?php 
                                                switch($event['event_type']) {
                                                    case 'appointment':
                                                        echo 'border-blue-500 bg-blue-100';
                                                        break;
                                                    case 'prescription':
                                                        echo 'border-green-500 bg-green-100';
                                                        break;
                                                    case 'surgery':
                                                        echo 'border-red-500 bg-red-100';
                                                        break;
                                                    case 'vitals':
                                                        echo 'border-purple-500 bg-purple-100';
                                                        break;
                                                    case 'lab_report':
                                                        echo 'border-yellow-500 bg-yellow-100';
                                                        break;
                                                    case 'diagnosis':
                                                        echo 'border-indigo-500 bg-indigo-100';
                                                        break;
                                                    default:
                                                        echo 'border-gray-500 bg-gray-100';
                                                }
                                                ?>
                                            "></div>
                                            
                                            <div class="timeline-card">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h3 class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($event['title'] ?? 'N/A'); ?>
                                                        </h3>
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            <?php echo htmlspecialchars($event['description'] ?? ''); ?>
                                                        </p>
                                                    </div>
                                                    <span class="timeline-date">
                                                        <?php 
                                                        $date = $event['event_date'] ?? $event['created_date'];
                                                        if ($date) {
                                                            echo date('M d, Y', strtotime($date));
                                                            if (!empty($event['event_time'])) {
                                                                echo ' at ' . date('h:i A', strtotime($event['event_time']));
                                                            }
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($event['event_status'])): ?>
                                                <div class="mt-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                        <?php 
                                                        $status = strtolower($event['event_status']);
                                                        if ($status == 'scheduled' || $status == 'pending') {
                                                            echo 'bg-yellow-100 text-yellow-800';
                                                        } elseif ($status == 'completed' || $status == 'confirmed') {
                                                            echo 'bg-green-100 text-green-800';
                                                        } elseif ($status == 'cancelled') {
                                                            echo 'bg-red-100 text-red-800';
                                                        } else {
                                                            echo 'bg-gray-100 text-gray-800';
                                                        }
                                                        ?>
                                                    ">
                                                        <?php echo htmlspecialchars($event['event_status']); ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                            <i data-lucide="clock" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h2 class="font-semibold text-gray-900">Patient Timeline</h2>
                                            <p class="text-xs text-gray-500">No activities recorded yet</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-12 text-center">
                                    <i data-lucide="calendar" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                    <p class="text-gray-500">No timeline events found for this patient.</p>
                                    <p class="text-sm text-gray-400 mt-1">Events will appear here as they are recorded.</p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- ============================================================ -->
                            <!-- SURGERY HISTORY TABLE -->
                            <!-- ============================================================ -->
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Surgery History</h3>
                                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium" onclick="window.location='surgeries.php'">
                                        View all surgeries →
                                    </button>
                                </div>
                                <div class="overflow-x-auto">
                                    <?php if(!empty($surgeries_history)): ?>
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="text-left py-2 px-3 text-gray-600 font-medium">Surgery Date</th>
                                                <th class="text-left py-2 px-3 text-gray-600 font-medium">Title</th>
                                                <th class="text-left py-2 px-3 text-gray-600 font-medium">Full Name</th>
                                                <th class="text-left py-2 px-3 text-gray-600 font-medium">Hospital</th>
                                                <th class="text-left py-2 px-3 text-gray-600 font-medium">Surgeon</th>
                                                <th class="text-left py-2 px-3 text-gray-600 font-medium">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($surgeries_history as $surgery): ?>
                                            <tr class="border-b border-gray-100 clickable-row" onclick="window.location='view_surgery.php?id=<?php echo $surgery['surgery_id']; ?>'">
                                                <td class="py-2 px-3 text-gray-800">
                                                    <?php echo date("d M Y", strtotime($surgery['surgery_date'])); ?>
                                                </td>
                                                <td class="py-2 px-3 text-gray-800"><?php echo $surgery['surgery_title']; ?></td>
                                                <td class="py-2 px-3 text-gray-800"><?php echo $surgery['surgery_full_name']; ?></td>
                                                <td class="py-2 px-3 text-gray-800"><?php echo $surgery['hospital_name'] ?? 'N/A'; ?></td>
                                                <td class="py-2 px-3 text-gray-800"><?php echo $surgery['surgeon_name'] ?? 'N/A'; ?></td>
                                                <td class="py-2 px-3">
                                                    <button class="text-blue-600 hover:text-blue-800 font-medium text-xs" onclick="event.stopPropagation(); window.location='view_surgery.php?id=<?php echo $surgery['surgery_id']; ?>'">
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
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- RIGHT COLUMN (1/3 width) - RECENT DOCUMENTS -->
                        <!-- ============================================================ -->
                        <div class="space-y-6">
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Recent Documents</h3>
                                <div class="space-y-2">
                                    <?php if(!empty($recent_docs)): ?>
                                        <?php foreach($recent_docs as $doc): ?>
                                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 hover:bg-gray-50 rounded-lg px-2 transition group">
                                                <div onclick="viewDocument('<?php echo $doc['upload_file']; ?>')" 
                                                    class="flex items-center gap-2 flex-1 cursor-pointer">
                                                    <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                                                    <span class="text-sm text-blue-600 hover:underline">
                                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xs text-gray-500">
                                                        <?php echo date("d M Y", strtotime($doc['document_date'])); ?>
                                                    </span>
                                                    <button onclick="downloadDocument('<?php echo $doc['upload_file']; ?>', '<?php echo htmlspecialchars($doc['document_name']); ?>')" 
                                                            class="text-gray-400 hover:text-blue-600 transition-colors p-1 rounded hover:bg-blue-50"
                                                            title="Download document">
                                                        <i data-lucide="download" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-400 italic text-center py-2">
                                            No documents found
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- DOCUMENTS SECTION - FULL WIDTH -->
                    <!-- ============================================================ -->
                    <?php
                    $active_tab = $_GET['doc_tab'] ?? 'Pre-Operation';

                    $doc_query = "
                    SELECT pd.*, r.name as uploaded_by_name
                    FROM patient_documents pd
                    LEFT JOIN register r ON pd.uploaded_by = r.id
                    WHERE pd.patient_id='$patient_id'
                    AND pd.document_category='$active_tab'
                    AND (pd.delete_flag=0 OR pd.delete_flag IS NULL)
                    ORDER BY pd.document_date DESC
                    ";

                    $doc_result = mysqli_query($conn,$doc_query);

                    $pre_count = mysqli_num_rows(mysqli_query($conn,"
                    SELECT document_id
                    FROM patient_documents
                    WHERE patient_id='$patient_id'
                    AND document_category='Pre-Operation'
                    AND (delete_flag=0 OR delete_flag IS NULL)
                    "));

                    $ot_count = mysqli_num_rows(mysqli_query($conn,"
                    SELECT document_id
                    FROM patient_documents
                    WHERE patient_id='$patient_id'
                    AND document_category='OT'
                    AND (delete_flag=0 OR delete_flag IS NULL)
                    "));

                    $post_count = mysqli_num_rows(mysqli_query($conn,"
                    SELECT document_id
                    FROM patient_documents
                    WHERE patient_id='$patient_id'
                    AND document_category='Post-Operation'
                    AND (delete_flag=0 OR delete_flag IS NULL)
                    "));
                    ?>
                   
                    <div class="bg-white rounded-xl border border-gray-200 p-5 mt-6">
                        <div class="flex justify-between items-center mb-5">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">
                                    Documents
                                </h2>
                                <p class="text-sm text-gray-500">
                                    View and manage patient documents
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-6 border-b border-gray-200 mb-5 overflow-x-auto">
                            <a href="?id=<?php echo $patient_id; ?>&doc_tab=Pre-Operation"
                            class="flex items-center gap-2 px-4 py-3 text-sm border-b-2
                            <?php echo ($active_tab=='Pre-Operation')
                                    ? 'font-semibold text-blue-600 border-blue-600'
                                    : 'text-gray-500 border-transparent'; ?>">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                PRE-OT
                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs">
                                    <?php echo $pre_count; ?>
                                </span>
                            </a>

                            <a href="?id=<?php echo $patient_id; ?>&doc_tab=OT"
                            class="flex items-center gap-2 px-4 py-3 text-sm border-b-2
                            <?php echo ($active_tab=='OT')
                                    ? 'font-semibold text-blue-600 border-blue-600'
                                    : 'text-gray-500 border-transparent'; ?>">
                                <i data-lucide="folder" class="w-4 h-4"></i>
                                OT
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">
                                    <?php echo $ot_count; ?>
                                </span>
                            </a>

                            <a href="?id=<?php echo $patient_id; ?>&doc_tab=Post-Operation"
                            class="flex items-center gap-2 px-4 py-3 text-sm border-b-2
                            <?php echo ($active_tab=='Post-Operation')
                                    ? 'font-semibold text-blue-600 border-blue-600'
                                    : 'text-gray-500 border-transparent'; ?>">
                                <i data-lucide="image" class="w-4 h-4"></i>
                                POST-OT
                                <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs">
                                    <?php echo $post_count; ?>
                                </span>
                            </a>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-gray-600">
                                        <th class="text-left py-3">Document Date</th>
                                        <th class="text-left py-3">Document Title</th>
                                        <th class="text-left py-3">Document Type</th>
                                        <th class="text-left py-3">Uploaded By</th>
                                        <th class="text-left py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($doc_result)>0){ ?>
                                    <?php while($doc=mysqli_fetch_assoc($doc_result)){ ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3">
                                            <?php echo date("d M Y",strtotime($doc['document_date'])); ?>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                                    <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800">
                                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <?php echo basename($doc['upload_file']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">
                                                <?php echo htmlspecialchars($doc['document_type']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <?php echo htmlspecialchars($doc['uploaded_by_name']); ?>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    onclick="viewDocument('<?php echo $doc['upload_file']; ?>')"
                                                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs">
                                                    View
                                                </button>
                                                <button
                                                    onclick="downloadDocument('<?php echo $doc['upload_file']; ?>','<?php echo htmlspecialchars($doc['document_name']); ?>')"
                                                    class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs">
                                                    Download
                                                </button>
                                                <a href="delete_patient_document.php?id=<?php echo $doc['document_id']; ?>&patient_id=<?php echo $patient_id; ?>"
                                                onclick="return confirm('Delete this document?')"
                                                class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                                    <i data-lucide="folder-open" class="w-8 h-8 text-gray-400"></i>
                                                </div>
                                                <h3 class="text-base font-semibold">No Documents Found</h3>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    No documents available under
                                                    <strong><?php echo $active_tab; ?></strong>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // View Document
        function viewDocument(filePath) {
            if(filePath && filePath !== '') {
                window.open(filePath, '_blank');
            } else {
                alert("Document file not available");
            }
        }

        // Download Document
        function downloadDocument(filePath, documentName) {
            if(!filePath || filePath.trim() === '') {
                alert("Document file not available");
                return;
            }

            let link = document.createElement('a');
            link.href = filePath;
            link.download = documentName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>

<?php
        }
    }
}
else{
    header('Location:index.php');
}
?>