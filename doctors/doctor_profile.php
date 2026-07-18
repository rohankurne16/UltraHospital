<?php
session_start();
include "../config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$register_id = $_SESSION['id'];

// Doctor Details
$sql = "SELECT * FROM doctor
        WHERE register_id='$register_id'
        AND (delete_flag=0 OR delete_flag IS NULL)";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: ../auth/index.php");
    exit();
}

$row = mysqli_fetch_assoc($result);

$doctor_id = $row['doctor_id'];
$name = $row['doctor_name'];
$image = $row['doctor_image'];
$mobile = $row['mobile'];
$email = $row['email'];
$department = $row['department'];
$qualification = $row['qualification'];
$specialization = $row['specialization'];
$experience = $row['experience'];
$consultation_fee = $row['consultation_fee'];
$timing = $row['timing'];
$address = $row['address'];
$status = $row['status'];

// Total Patients
$patientCount = 0;
$sql = "SELECT COUNT(DISTINCT patient_id) AS total
        FROM appointments
        WHERE doctor_id='$doctor_id'
        AND (delete_flag=0 OR delete_flag IS NULL)";
$result = mysqli_query($conn, $sql);
if ($result) {
    $patientCount = mysqli_fetch_assoc($result)['total'];
}

// Total Appointments
$appointmentCount = 0;
$sql = "SELECT COUNT(*) AS total
        FROM appointments
        WHERE doctor_id='$doctor_id'
        AND (delete_flag=0 OR delete_flag IS NULL)";
$result = mysqli_query($conn, $sql);
if ($result) {
    $appointmentCount = mysqli_fetch_assoc($result)['total'];
}

// Today's Appointments
$todayCount = 0;
$sql = "SELECT COUNT(*) AS total
        FROM appointments
        WHERE doctor_id='$doctor_id'
        AND appointment_date=CURDATE()
        AND (delete_flag=0 OR delete_flag IS NULL)";
$result = mysqli_query($conn, $sql);
if ($result) {
    $todayCount = mysqli_fetch_assoc($result)['total'];
}

$status_class = ($status == "Active") ? "status-active" : "status-inactive";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $hospital['hospital_name'] ?> - Doctor Profile</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .tab-btn { transition: all 0.2s ease; }
        .tab-btn:hover { background-color: #f8fafc; }
        
        /* Back button center alignment */
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
            flex-shrink: 0;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .back-btn i {
            font-size: 18px;
            line-height: 1;
        }
        
        .header-title {
            display: flex;
            flex-direction: column;
        }
        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            line-height: 1.2;
        }
        .header-title p {
            font-size: 14px;
            color: #6b7280;
            margin: 2px 0 0 0;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include'header.php'; ?>      

        <div class="flex flex-1 items-start">
            <!-- Sidebar Navigation -->
            <?php include 'Sidebar.php'; ?> 

            <!-- Main Content Area -->
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="dashboard.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div class="header-title">
                            <h1>Doctor Profile</h1>
                            <p>View and manage your professional information.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Doctor Summary -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                                <div class="p-6 flex flex-col items-center text-center border-b">
                                    <?php if ($image && file_exists($image)): ?>
                                        <img src="<?php echo $image; ?>" width="220" height="220" alt="Doctor Image" class="rounded-full w-40 h-40 object-cover border-4 border-blue-100">
                                    <?php else: ?>
                                        <div class="w-40 h-40 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center text-white text-6xl font-bold">
                                            <?php echo strtoupper(substr($name, 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h2 class="text-xl font-bold text-gray-900 mt-4"> <?php echo $name ?></h2>
                                    <p class="text-sm text-gray-500"><?php echo $specialization; ?></p>
                                
                                    <div class="mt-4 flex gap-2">
                                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full"><?php echo $status ?></span>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full"><?php echo $department ?></span>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-envelope w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Email</p>
                                            <p class="text-sm font-medium"><?php echo $email ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-phone w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Phone</p>
                                            <p class="text-sm font-medium"><?php echo $mobile ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-graduation-cap w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Qualification</p>
                                            <p class="text-sm font-medium"><?php echo $qualification ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-clock w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Timing</p>
                                            <p class="text-sm font-medium"><?php echo $timing ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-map-marker-alt w-4 h-4 text-gray-400 mt-1"></i>
                                        <div>
                                            <p class="text-xs text-gray-400">Address</p>
                                            <p class="text-sm font-medium"><?php echo $address ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 flex gap-2">
                                    <button onclick="window.location.href='update_doctor.php?id=<?php echo $doctor_id; ?>'" class="flex-1 bg-white border px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition">
                                        <i class="fas fa-edit mr-2"></i> Edit
                                    </button>
                                    <button onclick="window.location.href='change_doctor_password.php'" class="flex-1 bg-blue-600 text-white border px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">
                                        <i class="fas fa-key mr-2"></i> Change Password
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Detailed Info Tabs -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-xl border shadow-sm">
                                <div class="flex border-b overflow-x-auto custom-scrollbar">
                                    <button id="overviewBtn" onclick="showTab('overview')"
                                        class="tab-btn px-6 py-4 border-b-2 border-blue-600 text-blue-600 font-medium">
                                        <i class="fas fa-info-circle mr-2"></i> Overview
                                    </button>
                                   
                                </div>

                                <div class="p-6 space-y-8">
                                    <!-- Overview Tab -->
                                    <div id="overview" class="tab-content">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            <div class="space-y-4">
                                                <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                                    Professional Info
                                                </h4>
                                                <div class="space-y-3">
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Department</span>
                                                        <span class="text-sm font-medium"><?php echo $department; ?></span>
                                                    </div>
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Specialization</span>
                                                        <span class="text-sm font-medium"><?php echo $specialization; ?></span>
                                                    </div>
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Experience</span>
                                                        <span class="text-sm font-medium"><?php echo $experience; ?> years</span>
                                                    </div>
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Qualification</span>
                                                        <span class="text-sm font-medium"><?php echo $qualification; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                    <i class="fas fa-rupee-sign text-green-500 mr-2"></i>
                                                    Consultation Details
                                                </h4>
                                                <div class="space-y-3">
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Consultation Fee</span>
                                                        <span class="text-sm font-medium">₹<?php echo number_format($consultation_fee, 2); ?></span>
                                                    </div>
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Consultation Timing</span>
                                                        <span class="text-sm font-medium"><?php echo $timing; ?></span>
                                                    </div>
                                                    <div class="flex justify-between py-2 border-b">
                                                        <span class="text-sm text-gray-500">Status</span>
                                                        <span class="text-sm font-medium">
                                                            <span class="px-2 py-1 rounded-full text-xs <?php echo $status_class; ?>">
                                                                <?php echo $status; ?>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Appointments Tab -->
                                    <div id="appointments" class="tab-content hidden">
                                        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-calendar-check mr-2 text-blue-500"></i> Recent Appointments</h3>
                                        <div class="overflow-x-auto">
                                            <table class="w-full border text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-3 text-left">No</th>
                                                        <th class="p-3 text-left">Patient</th>
                                                        <th class="p-3 text-left">Date</th>
                                                        <th class="p-3 text-left">Time</th>
                                                        <th class="p-3 text-left">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $appointmentQuery = "SELECT * FROM appointments WHERE doctor_name = ? AND (delete_flag = 0 OR delete_flag IS NULL) ORDER BY appointment_date DESC LIMIT 10";
                                                    $stmt = $conn->prepare($appointmentQuery);
                                                    $stmt->bind_param("s", $name);
                                                    $stmt->execute();
                                                    $appointmentResult = $stmt->get_result();
                                                    if ($appointmentResult->num_rows > 0) {
                                                        while ($app = $appointmentResult->fetch_assoc()) {
                                                            ?>
                                                            <tr class="border-b hover:bg-gray-50">
                                                                <td class="p-3"><?php echo $app['appointment_no']; ?></td>
                                                                <td class="p-3"><?php echo $app['patient_name']; ?></td>
                                                                <td class="p-3"><?php echo date('d-m-Y', strtotime($app['appointment_date'])); ?></td>
                                                                <td class="p-3"><?php echo $app['appointment_time']; ?></td>
                                                                <td class="p-3">
                                                                    <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                                                        <?php echo $app['status']; ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="5" class="text-center p-5 text-gray-500">No appointments found.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Patients Tab -->
                                    <div id="patients" class="tab-content hidden">
                                        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-users mr-2 text-blue-500"></i> My Patients</h3>
                                        <div class="overflow-x-auto">
                                            <table class="w-full border text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-3 text-left">Patient Name</th>
                                                        <th class="p-3 text-left">Mobile</th>
                                                        <th class="p-3 text-left">Last Visit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $patientQuery = "SELECT DISTINCT p.patient_name, p.mobile, MAX(a.appointment_date) as last_visit 
                                                                     FROM patients p 
                                                                     INNER JOIN appointments a ON p.patient_id = a.patient_id 
                                                                     WHERE a.doctor_name = ? AND (a.delete_flag=0 OR a.delete_flag IS NULL) 
                                                                     GROUP BY p.patient_id ORDER BY last_visit DESC LIMIT 10";
                                                    $stmt = $conn->prepare($patientQuery);
                                                    $stmt->bind_param("s", $name);
                                                    $stmt->execute();
                                                    $patientResult = $stmt->get_result();
                                                    if ($patientResult->num_rows > 0) {
                                                        while ($pat = $patientResult->fetch_assoc()) {
                                                            ?>
                                                            <tr class="border-b hover:bg-gray-50">
                                                                <td class="p-3 font-medium"><?php echo $pat['patient_name']; ?></td>
                                                                <td class="p-3"><?php echo $pat['mobile']; ?></td>
                                                                <td class="p-3"><?php echo date('d-m-Y', strtotime($pat['last_visit'])); ?></td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="3" class="text-center p-5 text-gray-500">No patients found.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Schedule Tab -->
                                    <div id="schedule" class="tab-content hidden">
                                        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Working Schedule</h3>
                                        <div class="bg-gray-50 rounded-lg p-6">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <h4 class="font-medium text-gray-700 mb-2"><i class="fas fa-clock mr-2 text-blue-500"></i> Consultation Hours</h4>
                                                    <p class="text-sm text-gray-600"><?php echo $timing; ?></p>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-700 mb-2"><i class="fas fa-rupee-sign mr-2 text-green-500"></i> Consultation Fee</h4>
                                                    <p class="text-sm text-gray-600">₹<?php echo number_format($consultation_fee, 2); ?></p>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <h4 class="font-medium text-gray-700 mb-2"><i class="fas fa-map-marker-alt mr-2 text-red-500"></i> Clinic Address</h4>
                                                    <p class="text-sm text-gray-600"><?php echo $address; ?></p>
                                                </div>
                                            </div>
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
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.add('hidden');
            });
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('text-gray-500');
            });
            document.getElementById(tab).classList.remove('hidden');
            document.getElementById(tab + 'Btn').classList.add('border-blue-600', 'text-blue-600');
            document.getElementById(tab + 'Btn').classList.remove('text-gray-500');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>