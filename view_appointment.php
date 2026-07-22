<?php
session_start();
include 'config/hospital.php';

include 'config/permission.php';
     checkPermission('appointment-view');

$conn->set_charset("utf8");

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$appointment_details = null;
$patient_details = null;
$doctor_details = null;
$ward_details = null;
$bed_details = null;
$allocation_details = null;
$error_message = null;
$redirect_page = 'show_opd_appointments.php'; // Default redirect

if ($appointment_id > 0) {
    $sql_main = "SELECT
                    a.appointment_id,
                    a.appointment_no,
                    a.appointment_type,
                    a.opd_ipd_type,
                    a.department AS appointment_department,
                    a.appointment_date,
                    a.appointment_time,
                    a.duration,
                    a.status,
                    a.reason AS appointment_reason,
                    a.notes AS appointment_notes,
                    
                    p.patient_id,
                    p.patient_image AS patient_photo,
                    p.patient_name,
                    p.age,
                    p.gender,
                    p.date_of_birth,
                    p.blood_group,
                    p.mobile AS patient_mobile,
                    p.email AS patient_email,
                    p.address AS patient_address,
                    p.emergency_contact,
                    p.allergy AS patient_allergies,
                    p.medical_history AS patient_medical_history,
                    p.status AS patient_status,
                    
                    d.doctor_id,
                    d.doctor_image AS doctor_photo,
                    d.doctor_name,
                    d.department AS doctor_department,
                    d.specialization,
                    d.qualification,
                    d.experience,
                    d.consultation_fee,
                    d.timing,
                    d.mobile AS doctor_mobile,
                    d.email AS doctor_email
                FROM
                    appointments a
                LEFT JOIN
                    patients p ON a.patient_id = p.patient_id
                LEFT JOIN
                    doctor d ON a.doctor_id = d.doctor_id
                WHERE
                    a.appointment_id = ? AND (a.delete_flag = 0 OR a.delete_flag IS NULL)";

    $stmt = $conn->prepare($sql_main);
    if ($stmt) {
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
                
            $appointment_details = [
                'appointment_id' => $data['appointment_id'],
                'appointment_no' => $data['appointment_no'],
                'appointment_type' => $data['appointment_type'],
                'opd_ipd_type' => $data['opd_ipd_type'],
                'department' => $data['appointment_department'],
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'duration' => $data['duration'],
                'status' => $data['status'],
                'reason' => $data['appointment_reason'],
                'notes' => $data['appointment_notes'],
            ];

            
            // Set redirect page based on OPD/IPD type
            if ($data['opd_ipd_type'] == 'IPD') {
                $redirect_page = 'show_ipd_appointments.php';
                
                // Fetch ward, bed, and allocation details for IPD
                $patient_id = $data['patient_id'];
                
                // Get bed allocation details
                $allocation_sql = "SELECT 
                                    ba.allocation_id,
                                    ba.patient_id,
                                    ba.bed_id,
                                    ba.admit_date,
                                    ba.discharge_date,
                                    ba.status as allocation_status,
                                    b.bed_no,
                                    b.bed_type,
                                    b.status as bed_status,
                                    w.ward_name,
                                    w.ward_type,
                                    w.floor_no,
                                    w.status as ward_status
                                FROM bed_allocation ba
                                LEFT JOIN bed_master b ON ba.bed_id = b.bed_id
                                LEFT JOIN ward_master w ON b.room_id = w.ward_id
                                WHERE ba.patient_id = ? 
                                AND (ba.status = 'Occupied' OR ba.status = 'Active')
                                ORDER BY ba.allocation_id DESC LIMIT 1";

                $alloc_stmt = $conn->prepare($allocation_sql);
                if ($alloc_stmt) {
                    $alloc_stmt->bind_param("i", $patient_id);
                    $alloc_stmt->execute();
                    $alloc_result = $alloc_stmt->get_result();
                    
                    if ($alloc_result && $alloc_result->num_rows > 0) {
                        $alloc_data = $alloc_result->fetch_assoc();
                        
                        $allocation_details = [
                            'allocation_id' => $alloc_data['allocation_id'] ?? null,
                            'patient_id' => $alloc_data['patient_id'] ?? null,
                            'bed_id' => $alloc_data['bed_id'] ?? null,
                            'admit_date' => $alloc_data['admit_date'] ?? null,
                            'discharge_date' => $alloc_data['discharge_date'] ?? null,
                            'allocation_status' => $alloc_data['allocation_status'] ?? null
                        ];
                        
                        $bed_details = [
                            'bed_no' => $alloc_data['bed_no'] ?? 'N/A',
                            'bed_type' => $alloc_data['bed_type'] ?? 'N/A',
                            'status' => $alloc_data['bed_status'] ?? 'N/A'
                        ];
                        
                        $ward_details = [
                            'ward_name' => $alloc_data['ward_name'] ?? 'N/A',
                            'ward_type' => $alloc_data['ward_type'] ?? 'N/A',
                            'floor_no' => $alloc_data['floor_no'] ?? 'N/A',
                            'status' => $alloc_data['ward_status'] ?? 'N/A'
                        ];
                    }
                    $alloc_stmt->close();
                }
                
            } elseif ($data['opd_ipd_type'] == 'OPD') {
                $redirect_page = 'show_opd_appointments.php';
            }
            
            $patient_details = [
                'patient_id' => $data['patient_id'],
                'patient_photo' => $data['patient_photo'],
                'patient_name' => $data['patient_name'],
                'age' => $data['age'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'],
                'blood_group' => $data['blood_group'],
                'mobile' => $data['patient_mobile'],
                'email' => $data['patient_email'],
                'address' => $data['patient_address'],
                'emergency_contact' => $data['emergency_contact'],
                'allergies' => $data['patient_allergies'],
                'medical_history' => $data['patient_medical_history'],
                'status' => $data['patient_status'],
            ];
            
            $doctor_details = [
                'doctor_id' => $data['doctor_id'],
                'doctor_photo' => $data['doctor_photo'],
                'doctor_name' => $data['doctor_name'],
                'department' => $data['doctor_department'],
                'specialization' => $data['specialization'],
                'qualification' => $data['qualification'],
                'experience' => $data['experience'],
                'consultation_fee' => $data['consultation_fee'],
                'timing' => $data['timing'],
                'mobile' => $data['doctor_mobile'],
                'email' => $data['doctor_email'],
            ];
        } else {
            $error_message = "Appointment not found or has been deleted.";
        }
        $stmt->close();
    } else {
        $error_message = "Database query error.";
    }
} else {
    $error_message = "Invalid appointment ID.";
}

function getStatusClass($status) {
    switch ($status) {
        case 'Scheduled': return 'status-scheduled';
        case 'Confirmed': return 'status-confirmed';
        case 'Completed': return 'status-completed';
        case 'Cancelled': return 'status-cancelled';
        case 'In Progress': return 'status-in-progress';
        default: return '';
    }
}

function formatDate($date_str) {
    if (empty($date_str) || $date_str == '0000-00-00') return 'N/A';
    return date('d M Y', strtotime($date_str));
}

function formatTime($time_str) {
    if (empty($time_str)) return 'N/A';
    return date('h:i A', strtotime($time_str));
}

// Get hospital settings for logo
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'Hospital';
$hospital_logo = isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : 'assets/img/logo.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <title><?php echo $hospital['hospital_name'] ?> - Appointment Details</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #1f2937;
        }
        .back-btn:hover {
            background: #f3f4f6;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .status-available { background: #d1fae5; color: #065f46; }
        .status-occupied { background: #fee2e2; color: #991b1b; }
        
        .info-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .info-card .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-card .card-body {
            padding: 24px;
        }
        .info-item {
            background: #f9fafb;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .info-item.ward-info {
            border-left-color: #8b5cf6;
        }
        .info-item.bed-info {
            border-left-color: #10b981;
        }
        .info-item.allocation-info {
            border-left-color: #f59e0b;
        }
        .info-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3b82f6;
        }
        .profile-image-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            color: white;
            border: 3px solid #3b82f6;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px 20px;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ward-status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .ward-status-available { background: #d1fae5; color: #065f46; }
        .ward-status-occupied { background: #fee2e2; color: #991b1b; }
        .ward-status-maintenance { background: #fef3c7; color: #92400e; }
        
        .ipd-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
            .ipd-info-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .grid-cols-2 { grid-template-columns: 1fr; }
            .ipd-info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            
            <main class="main-content w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <!-- Page Header -->
                    <div class="flex items-center gap-4 mb-6">
                        <a href="<?php echo htmlspecialchars($redirect_page); ?>" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Appointment Details</h1>
                            <p class="text-gray-500 text-sm">View complete appointment information</p>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="error-message">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php else: ?>

                        <!-- Status Bar -->
                        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex flex-wrap items-center justify-between gap-4 shadow-sm">
                            <div class="flex items-center gap-4 flex-wrap">
                                <span class="text-sm text-gray-500">Appointment #</span>
                                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($appointment_details['appointment_no'] ?? 'N/A'); ?></span>
                                <span class="text-gray-300">|</span>
                                <span class="text-sm text-gray-500">Type</span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo ($appointment_details['opd_ipd_type'] == 'IPD') ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo htmlspecialchars($appointment_details['opd_ipd_type'] ?? 'OPD'); ?>
                                </span>
                                <span class="text-gray-300">|</span>
                                <span class="text-sm text-gray-500">Status</span>
                                <span class="status-badge <?php echo getStatusClass($appointment_details['status'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($appointment_details['status'] ?? 'Pending'); ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-4 flex-wrap">
                                <span class="text-sm text-gray-500">Date</span>
                                <span class="font-semibold text-gray-900">
                                    <?php echo formatDate($appointment_details['appointment_date'] ?? ''); ?>
                                </span>
                                <span class="text-gray-300">|</span>
                                <span class="text-sm text-gray-500">Time</span>
                                <span class="font-semibold text-gray-900">
                                    <?php echo formatTime($appointment_details['appointment_time'] ?? ''); ?>
                                </span>
                            </div>
                        </div>

                        <!-- IPD Ward, Bed & Allocation Information - Only shown for IPD -->
                        <?php if ($appointment_details['opd_ipd_type'] == 'IPD'): ?>
                        <div class="bg-white rounded-lg border border-purple-200 p-4 mb-6 shadow-sm">
                            <div class="flex items-center gap-2 mb-3">
                                <i data-lucide="hospital" class="w-5 h-5 text-purple-600"></i>
                                <h3 class="font-semibold text-gray-900">IPD Admission Details</h3>
                                <span class="ml-auto text-xs text-gray-500">Bed Allocation</span>
                            </div>
                            
                            <?php if ($ward_details && $bed_details && $allocation_details): ?>
                            <div class="ipd-info-grid">
                                <!-- Ward Information -->
                                <div class="info-item ward-info">
                                    <div class="info-label"><i class="fas fa-building mr-1"></i> Ward</div>
                                    <div class="info-value"><?php echo htmlspecialchars($ward_details['ward_name'] ?? 'N/A'); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Type: <?php echo htmlspecialchars($ward_details['ward_type'] ?? 'N/A'); ?> | 
                                        Floor: <?php echo htmlspecialchars($ward_details['floor_no'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="mt-1">
                                        <span class="ward-status-badge ward-status-<?php echo strtolower($ward_details['status'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($ward_details['status'] ?? 'N/A'); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Bed Information -->
                                <div class="info-item bed-info">
                                    <div class="info-label"><i class="fas fa-bed mr-1"></i> Bed</div>
                                    <div class="info-value"><?php echo htmlspecialchars($bed_details['bed_no'] ?? 'N/A'); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Type: <?php echo htmlspecialchars($bed_details['bed_type'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="mt-1">
                                        <span class="ward-status-badge ward-status-<?php echo strtolower($bed_details['status'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($bed_details['status'] ?? 'N/A'); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Allocation Information -->
                                <div class="info-item allocation-info">
                                    <div class="info-label"><i class="fas fa-calendar-check mr-1"></i> Allocation</div>
                                    <div class="info-value">Admit: <?php echo formatDate($allocation_details['admit_date'] ?? ''); ?></div>
                                    <?php if (!empty($allocation_details['discharge_date']) && $allocation_details['discharge_date'] != '0000-00-00'): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Discharge: <?php echo formatDate($allocation_details['discharge_date']); ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-xs text-green-600 mt-1">
                                        <i class="fas fa-check-circle"></i> Currently Admitted
                                    </div>
                                    <?php endif; ?>
                                    <div class="mt-1">
                                        <span class="ward-status-badge ward-status-<?php echo strtolower($allocation_details['allocation_status'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($allocation_details['allocation_status'] ?? 'N/A'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>
                                No active bed allocation found for this patient.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Appointment Details -->
                        <div class="info-card mb-6">
                            <div class="card-header">
                                <i data-lucide="calendar" class="w-5 h-5"></i>
                                Appointment Information
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="info-item">
                                        <div class="info-label">Appointment No</div>
                                        <div class="info-value"><?php echo htmlspecialchars($appointment_details['appointment_no'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Type</div>
                                        <div class="info-value"><?php echo htmlspecialchars($appointment_details['appointment_type'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Department</div>
                                        <div class="info-value"><?php echo htmlspecialchars($appointment_details['department'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Duration</div>
                                        <div class="info-value"><?php echo htmlspecialchars($appointment_details['duration'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($appointment_details['reason'])): ?>
                                    <div class="mt-4">
                                        <div class="info-label">Reason for Visit</div>
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mt-1">
                                            <?php echo htmlspecialchars($appointment_details['reason']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($appointment_details['notes'])): ?>
                                    <div class="mt-4">
                                        <div class="info-label">Notes</div>
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mt-1">
                                            <?php echo htmlspecialchars($appointment_details['notes']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Patient and Doctor Details -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Patient Details -->
                            <div class="info-card">
                                <div class="card-header">
                                    <i data-lucide="user" class="w-5 h-5"></i>
                                    Patient Information
                                </div>
                                <div class="card-body">
                                    <div class="flex items-center gap-4 mb-4">
                                        <?php if (!empty($patient_details['patient_photo']) && file_exists($patient_details['patient_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($patient_details['patient_photo']); ?>" alt="Patient" class="profile-image">
                                        <?php else: ?>
                                            <div class="profile-image-placeholder">
                                                <?php echo strtoupper(substr($patient_details['patient_name'] ?? 'P', 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($patient_details['patient_name'] ?? 'N/A'); ?></h3>
                                            <p class="text-sm text-gray-500">Patient ID: #<?php echo htmlspecialchars($patient_details['patient_id'] ?? 'N/A'); ?></p>
                                            <span class="status-badge <?php echo getStatusClass($patient_details['status'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($patient_details['status'] ?? 'Active'); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="info-item">
                                            <div class="info-label">Age</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient_details['age'] ?? 'N/A'); ?> yrs</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Gender</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient_details['gender'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">DOB</div>
                                            <div class="info-value"><?php echo formatDate($patient_details['date_of_birth'] ?? ''); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Blood Group</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient_details['blood_group'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Mobile</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient_details['mobile'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?php echo htmlspecialchars($patient_details['email'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>

                                    <?php if (!empty($patient_details['address'])): ?>
                                        <div class="mt-3">
                                            <div class="info-label">Address</div>
                                            <div class="bg-gray-50 p-2 rounded-lg border border-gray-200 mt-1 text-sm">
                                                <?php echo htmlspecialchars($patient_details['address']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($patient_details['emergency_contact'])): ?>
                                        <div class="mt-3">
                                            <div class="info-label">Emergency Contact</div>
                                            <div class="text-sm font-medium"><?php echo htmlspecialchars($patient_details['emergency_contact']); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($patient_details['allergies'])): ?>
                                        <div class="mt-3">
                                            <div class="info-label text-red-600">Allergies</div>
                                            <div class="bg-red-50 p-2 rounded-lg border border-red-200 mt-1 text-sm text-red-700">
                                                <?php echo htmlspecialchars($patient_details['allergies']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($patient_details['medical_history'])): ?>
                                        <div class="mt-3">
                                            <div class="info-label">Medical History</div>
                                            <div class="bg-gray-50 p-2 rounded-lg border border-gray-200 mt-1 text-sm">
                                                <?php echo htmlspecialchars($patient_details['medical_history']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Doctor Details -->
                            <div class="info-card">
                                <div class="card-header">
                                    <i data-lucide="stethoscope" class="w-5 h-5"></i>
                                    Doctor Information
                                </div>
                                <div class="card-body">
                                    <div class="flex items-center gap-4 mb-4">
                                        <?php if (!empty($doctor_details['doctor_photo']) && file_exists($doctor_details['doctor_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($doctor_details['doctor_photo']); ?>" alt="Doctor" class="profile-image">
                                        <?php else: ?>
                                            <div class="profile-image-placeholder">
                                                <?php echo strtoupper(substr($doctor_details['doctor_name'] ?? 'D', 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900"> <?php echo htmlspecialchars($doctor_details['doctor_name'] ?? 'N/A'); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($doctor_details['department'] ?? 'N/A'); ?></p>
                                            <p class="text-sm text-purple-600"><?php echo htmlspecialchars($doctor_details['specialization'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="info-item">
                                            <div class="info-label">Qualification</div>
                                            <div class="info-value"><?php echo htmlspecialchars($doctor_details['qualification'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Experience</div>
                                            <div class="info-value"><?php echo htmlspecialchars($doctor_details['experience'] ?? 'N/A'); ?> yrs</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Consultation Fee</div>
                                            <div class="info-value">₹<?php echo htmlspecialchars($doctor_details['consultation_fee'] ?? '0'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Timing</div>
                                            <div class="info-value"><?php echo htmlspecialchars($doctor_details['timing'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Mobile</div>
                                            <div class="info-value"><?php echo htmlspecialchars($doctor_details['mobile'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?php echo htmlspecialchars($doctor_details['email'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Toast function
        function showToast(type, message) {
            let colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            let textColor = type === 'warning' ? '#000' : '#fff';
            
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                close: true,
                stopOnFocus: true,
                style: {
                    background: colors[type] || '#28a745',
                    color: textColor
                }
            }).showToast();
        }

        <?php if(isset($_SESSION['toast'])): ?>
            (function() {
                let type = "<?= $_SESSION['toast']['type']; ?>";
                let message = "<?= $_SESSION['toast']['message']; ?>";
                let colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    warning: '#ffc107',
                    info: '#17a2b8'
                };
                let textColor = type === 'warning' ? '#000' : '#fff';
                
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    close: true,
                    stopOnFocus: true,
                    style: {
                        background: colors[type] || '#28a745',
                        color: textColor
                    }
                }).showToast();
            })();
        <?php unset($_SESSION['toast']); endif; ?>
    </script>

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>