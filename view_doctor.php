<?php
session_start();
include "config/hospital.php";

$doctor_name = isset($_GET['name']) ? urldecode($_GET['name']) : '';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM doctor WHERE doctor_name = '$doctor_name'";
$result = $conn->query($sql);
$doctor = $result->fetch_assoc();

if (!$doctor && isset($_GET['id'])) {
    $doctor_id = intval($_GET['id']);
    $sql = "SELECT * FROM doctor WHERE doctor_id = $doctor_id";
    $result = $conn->query($sql);
    $doctor = $result->fetch_assoc();
    $doctor_name = $doctor['doctor_name'] ?? '';
}

$doctor_id = $doctor['doctor_id'] ?? 0;

// Get appointments for this doctor
$appointments_sql = "SELECT a.*, p.patient_name, p.patient_id, p.mobile, p.email 
                    FROM appointments a 
                    LEFT JOIN patients p ON a.patient_id = p.patient_id 
                    WHERE a.doctor_id='$doctor_id' AND (a.delete_flag=0 OR a.delete_flag IS NULL) 
                    ORDER BY a.appointment_date DESC, a.appointment_time ASC";
$appointments_result = $conn->query($appointments_sql);
$appointments = [];
if ($appointments_result && $appointments_result->num_rows > 0) {
    while($row = $appointments_result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Get patients for this doctor
$patients_sql = "SELECT p.*, r.name as register_name 
                FROM patients p 
                LEFT JOIN register r ON p.register_id = r.id 
                WHERE p.doctor_id='$doctor_id' AND (p.delete_flag=0 OR p.delete_flag IS NULL)
                ORDER BY p.patient_id DESC";
$patients_result = $conn->query($patients_sql);
$patients = [];
if ($patients_result && $patients_result->num_rows > 0) {
    while($row = $patients_result->fetch_assoc()) {
        $patients[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Doctor Profile - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Sidebar and Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }

        /* Mobile Sidebar behavior */
        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            #sidebar-container.active {
                transform: translateX(0);
            }
            #main-content {
                margin-left: 0 !important;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Desktop Sidebar behavior */
        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 256px;
            }
        }

        #mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            cursor: pointer;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
        }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-confirmed { background: #fef3c7; color: #b45309; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .clickable-row { cursor: pointer; transition: all 0.2s ease; }
        .clickable-row:hover { background-color: #f9fafb; }
        
        .blood-group-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .blood-a { background: #fee2e2; color: #991b1b; }
        .blood-b { background: #dbeafe; color: #1e40af; }
        .blood-o { background: #dcfce7; color: #15803d; }
        .blood-ab { background: #fef3c7; color: #b45309; }
        
        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #64748b;
            margin-left: 8px;
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
            transition: all 0.2s ease; 
            text-decoration: none; 
            flex-shrink: 0;
        }
        .back-btn:hover { 
            background: #f3f4f6; 
            border-color: #d1d5db; 
        }

        .custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?> 
        <div class="flex flex-1 items-start">
         
                <?php include 'Sidebar.php'; ?>
         
            
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
                <?php if ($doctor): ?>
                <div class="max-w-7xl mx-auto w-full space-y-6">
                    <!-- Page Header -->
                    <div class="flex items-center gap-4">
                      
                        <a href="doctors.php" class="back-btn shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Doctor Profile</h1>
                            <p class="text-gray-500 text-sm">Comprehensive overview of medical records and schedules.</p>
                        </div>
                    </div>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                        <!-- Left Column - Doctor Profile Card -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="p-8 flex flex-col items-center text-center border-b border-gray-50">
                                    <div class="relative">
                                        <div class="h-24 w-24 rounded-full border-4 border-white shadow-lg overflow-hidden">
                                            <?php if (!empty($doctor['doctor_image'])) { ?>
                                                <img src="<?php echo $doctor['doctor_image']; ?>" alt="Doctor Image" class="h-full w-full object-cover">
                                            <?php } else { ?>
                                                <div class="h-full w-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-indigo-600 text-3xl font-bold text-white">
                                                    <?php echo strtoupper(substr($doctor['doctor_name'], 0, 1)); ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="absolute bottom-0 right-0 h-6 w-6 rounded-full border-4 border-white <?php echo strtolower($doctor['status']) == 'active' ? 'bg-green-500' : 'bg-red-500'; ?>"></div>
                                    </div>
                                    <h2 class="text-xl font-bold text-gray-900 mt-4 tracking-tight">
                                        <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                                    </h2>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                        <?php echo htmlspecialchars($doctor['specialization']); ?>
                                    </p>
                                    <div class="mt-4">
                                        <span class="status-badge <?php echo strtolower($doctor['status']) == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo htmlspecialchars($doctor['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-6 space-y-5">
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-500">
                                            <i data-lucide="mail" class="w-4 h-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Email Address</p>
                                            <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($doctor['email']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-500">
                                            <i data-lucide="phone" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Phone Number</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($doctor['mobile']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-purple-50 rounded-lg text-purple-500">
                                            <i data-lucide="building" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Department</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($doctor['department']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-pink-50 rounded-lg text-pink-500">
                                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Qualification</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($doctor['qualification']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-amber-50 rounded-lg text-amber-500">
                                            <i data-lucide="clock" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Experience</p>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($doctor['experience']); ?> Years</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-emerald-50 rounded-lg text-emerald-500">
                                            <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Consultation Fee</p>
                                            <p class="text-sm font-semibold text-gray-900">₹<?php echo htmlspecialchars($doctor['consultation_fee']); ?></p>
                                        </div>
                                    </div>

                                    <?php if (!empty($doctor['timing'])): ?>
                                    <div class="pt-5 border-t border-gray-50">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Available Timing</p>
                                        <div class="text-sm font-medium text-gray-700 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                            <?php echo htmlspecialchars($doctor['timing']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex flex-col sm:flex-row gap-3 pt-5">
                                        <button class="flex-1 bg-white border border-gray-200 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-gray-50 transition shadow-sm">
                                            <i data-lucide="message-square" class="w-3.5 h-3.5 inline mr-1.5"></i> Message
                                        </button>
                                        <button type="button" onclick="window.location.href='update_doctor.php?id=<?php echo $doctor_id?>'" class="flex-1 bg-blue-600 text-white px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-blue-700 transition shadow-lg shadow-blue-500/20">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5 inline mr-1.5"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Tabs Content -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <!-- Tabs Header -->
                                <div class="flex border-b border-gray-100 overflow-x-auto custom-scrollbar bg-gray-50/30">
                                    <button id="tab-overview" onclick="showTab('overview')" class="flex-1 min-w-[120px] px-6 py-4 text-xs font-bold uppercase tracking-widest transition-all border-b-2 data-[state=active]:border-blue-600 data-[state=active]:text-blue-600 data-[state=inactive]:border-transparent data-[state=inactive]:text-gray-400" data-state="active">
                                        <i data-lucide="user" class="w-4 h-4 inline mr-2"></i> Overview
                                    </button>
                                    <button id="tab-appointments" onclick="showTab('appointments')" class="flex-1 min-w-[150px] px-6 py-4 text-xs font-bold uppercase tracking-widest transition-all border-b-2 data-[state=active]:border-blue-600 data-[state=active]:text-blue-600 data-[state=inactive]:border-transparent data-[state=inactive]:text-gray-400" data-state="inactive">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-2"></i> Appointments
                                        <span class="tab-count"><?php echo count($appointments); ?></span>
                                    </button>
                                    <button id="tab-patients" onclick="showTab('patients')" class="flex-1 min-w-[120px] px-6 py-4 text-xs font-bold uppercase tracking-widest transition-all border-b-2 data-[state=active]:border-blue-600 data-[state=active]:text-blue-600 data-[state=inactive]:border-transparent data-[state=inactive]:text-gray-400" data-state="inactive">
                                        <i data-lucide="users" class="w-4 h-4 inline mr-2"></i> Patients
                                        <span class="tab-count"><?php echo count($patients); ?></span>
                                    </button>
                                </div>

                                <div class="p-6 md:p-8">
                                    <!-- Overview Tab -->
                                    <div id="content-overview" class="tab-content active space-y-8">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                            <div class="space-y-4">
                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center">
                                                    <i data-lucide="info" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                    Medical Credentials
                                                </h4>
                                                <div class="space-y-3 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-gray-500">Specialization</span>
                                                        <span class="text-xs font-bold text-gray-900"><?php echo htmlspecialchars($doctor['specialization']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-gray-500">Qualification</span>
                                                        <span class="text-xs font-bold text-gray-900"><?php echo htmlspecialchars($doctor['qualification']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-gray-500">Years Active</span>
                                                        <span class="text-xs font-bold text-gray-900"><?php echo htmlspecialchars($doctor['experience']); ?> Years</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center">
                                                    <i data-lucide="activity" class="w-4 h-4 mr-2 text-indigo-500"></i>
                                                    Practice Details
                                                </h4>
                                                <div class="space-y-3 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-gray-500">Department</span>
                                                        <span class="text-xs font-bold text-gray-900"><?php echo htmlspecialchars($doctor['department']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-gray-500">Consultation Fee</span>
                                                        <span class="text-xs font-bold text-emerald-600">₹<?php echo htmlspecialchars($doctor['consultation_fee']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-gray-500">Availability</span>
                                                        <span class="text-xs font-bold text-gray-900"><?php echo strtolower($doctor['status']) == 'active' ? 'Full Time' : 'Unavailable'; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-xl shadow-blue-500/20">
                                            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                                                <div class="text-center sm:text-left">
                                                    <h3 class="text-lg font-bold">Today's Schedule</h3>
                                                    <p class="text-blue-100 text-xs mt-1">Review your upcoming appointments and tasks.</p>
                                                </div>
                                                <a href="calendar.php?id=<?php echo $doctor_id ?>" class="bg-white text-blue-600 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-50 transition shadow-lg">
                                                    <i data-lucide="calendar-days" class="w-4 h-4 inline mr-2"></i>
                                                    View Calendar
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Appointments Tab -->
                                    <div id="content-appointments" class="tab-content space-y-6">
                                        <div class="flex justify-between items-center">
                                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Recent Appointments</h3>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total: <?php echo count($appointments); ?></span>
                                        </div>
                                        
                                        <?php if (count($appointments) > 0): ?>
                                            <div class="overflow-x-auto custom-scrollbar border border-gray-100 rounded-2xl">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="bg-gray-50/50 border-b border-gray-100">
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Patient Name</th>
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date & Time</th>
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Duration</th>
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-50">
                                                        <?php foreach($appointments as $app): ?>
                                                            <tr class="clickable-row" onclick="window.location.href='view_appointment.php?id=<?php echo $app['appointment_id']; ?>'">
                                                                <td class="px-6 py-4">
                                                                    <div class="font-bold text-gray-900"><?php echo htmlspecialchars($app['patient_name'] ?? 'N/A'); ?></div>
                                                                    <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mt-0.5">ID: #<?php echo $app['patient_id']; ?></div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="text-xs font-bold text-gray-700"><?php echo date('d M, Y', strtotime($app['appointment_date'])); ?></div>
                                                                    <div class="text-[10px] text-blue-500 font-bold uppercase tracking-wider mt-0.5"><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></div>
                                                                </td>
                                                                <td class="px-6 py-4 text-xs font-bold text-gray-600">
                                                                    <?php echo isset($app['duration']) ? $app['duration'] . ' MIN' : '30 MIN'; ?>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <?php 
                                                                    $status = strtolower($app['status'] ?? 'scheduled');
                                                                    $status_class = 'status-scheduled';
                                                                    if($status == 'completed') $status_class = 'status-completed';
                                                                    elseif($status == 'cancelled') $status_class = 'status-cancelled';
                                                                    elseif($status == 'confirmed') $status_class = 'status-confirmed';
                                                                    ?>
                                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                                        <?php echo $status; ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                                <i data-lucide="calendar" class="w-12 h-12 mx-auto text-gray-200 mb-4"></i>
                                                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">No Appointments Found</p>
                                                <a href="../appointments/add.php?doctor_name=<?php echo urlencode($doctor['doctor_name']); ?>" class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 uppercase tracking-widest mt-4 hover:underline">
                                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                                    Schedule First Appointment
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Patients Tab -->
                                    <div id="content-patients" class="tab-content space-y-6">
                                        <div class="flex justify-between items-center">
                                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Assigned Patients</h3>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total: <?php echo count($patients); ?></span>
                                        </div>

                                        <?php if (count($patients) > 0): ?>
                                            <div class="overflow-x-auto custom-scrollbar border border-gray-100 rounded-2xl">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="bg-gray-50/50 border-b border-gray-100">
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Patient Details</th>
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Contact Info</th>
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Medical Info</th>
                                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-50">
                                                        <?php foreach($patients as $patient): ?>
                                                            <tr class="clickable-row" onclick="window.location.href='view_patient.php?id=<?php echo $patient['patient_id']; ?>'">
                                                                <td class="px-6 py-4">
                                                                    <div class="flex items-center gap-4">
                                                                        <?php if(!empty($patient['patient_image']) && file_exists($patient['patient_image'])): ?>
                                                                            <img src="<?php echo htmlspecialchars($patient['patient_image']); ?>" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">
                                                                        <?php else: ?>
                                                                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs border-2 border-white shadow-sm">
                                                                                <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <div class="font-bold text-gray-900"><?php echo htmlspecialchars($patient['patient_name']); ?></div>
                                                                            <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">ID: #<?php echo $patient['patient_id']; ?></div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="flex items-center gap-2 text-xs font-bold text-gray-700">
                                                                        <i data-lucide="phone" class="w-3 h-3 text-gray-400"></i>
                                                                        <?php echo htmlspecialchars($patient['mobile'] ?? 'N/A'); ?>
                                                                    </div>
                                                                    <div class="flex items-center gap-2 text-[10px] font-medium text-gray-400 uppercase tracking-wider mt-1">
                                                                        <i data-lucide="mail" class="w-3 h-3 text-gray-300"></i>
                                                                        <?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <?php if(!empty($patient['blood_group'])): 
                                                                        $blood_class = 'blood-o';
                                                                        if(strpos($patient['blood_group'], 'A') !== false) $blood_class = 'blood-a';
                                                                        elseif(strpos($patient['blood_group'], 'B') !== false) $blood_class = 'blood-b';
                                                                        elseif(strpos($patient['blood_group'], 'AB') !== false) $blood_class = 'blood-ab';
                                                                    ?>
                                                                        <span class="blood-group-badge <?php echo $blood_class; ?>">
                                                                            <?php echo htmlspecialchars($patient['blood_group']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Age: <?php echo $patient['age']; ?> YRS</div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <?php
                                                                    $status = isset($patient['status']) ? trim($patient['status']) : 'Active';
                                                                    $status_class = ($status == 'Active') ? 'status-active' : 'status-inactive';
                                                                    ?>
                                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                                        <?php echo $status; ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                                <i data-lucide="users" class="w-12 h-12 mx-auto text-gray-200 mb-4"></i>
                                                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">No Patients Found</p>
                                                <a href="../patients/add.php?doctor_id=<?php echo $doctor_id; ?>" class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 uppercase tracking-widest mt-4 hover:underline">
                                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                                    Add First Patient
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-200 border border-dashed border-gray-200 mb-6">
                        <i data-lucide="user-x" class="w-10 h-10"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 uppercase tracking-widest">Doctor Not Found</h2>
                    <p class="text-sm font-medium text-gray-500 mt-2">The requested medical profile could not be located.</p>
                    <a href="doctors.php" class="mt-8 bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
                        Back to Directory
                    </a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebarContainer = document.getElementById('sidebar-container');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            function openSidebar() {
                sidebarContainer.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

            // Handle close button inside Sidebar.php
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
        });

        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected tab content
            const selectedContent = document.getElementById('content-' + tabName);
            if (selectedContent) {
                selectedContent.classList.add('active');
            }
            
            // Update tab buttons
            document.querySelectorAll('[role="tab"]').forEach(button => {
                button.dataset.state = 'inactive';
            });
            
            const selectedButton = document.getElementById('tab-' + tabName);
            if (selectedButton) {
                selectedButton.dataset.state = 'active';
            }
        }

        // Set default tab
        document.addEventListener('DOMContentLoaded', function() {
            showTab('overview');
        });
    </script>
</body>
</html>
