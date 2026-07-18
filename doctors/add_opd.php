<?php 
session_start(); 
include '../config/hospital.php';


if(!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}
if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}


$doctor_reg_id = $_SESSION['id'];

$getDoctor = "SELECT doctor_id FROM doctor WHERE register_id='$doctor_reg_id'";
$all_doctor_info = $conn->query($getDoctor);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_id = $doctor["doctor_id"];
}

$patientsSql = "SELECT patient_id, patient_name, mobile FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY patient_name";
$patientsResult = mysqli_query($conn, $patientsSql);

$opdNo = "OPD" . date('Ymd') . sprintf("%04d", rand(1, 9999));

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
    $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
    $doctor_note = mysqli_real_escape_string($conn, $_POST['doctor_note']);
    
    $insertSql = "INSERT INTO opd (opd_no, patient_id, doctor_id, visit_date, symptoms, diagnosis, bp, pulse, weight, temperature, doctor_note) 
                  VALUES ('$opdNo', '$patient_id', '$doctor_id', '$visit_date', '$symptoms', '$diagnosis', '$bp', '$pulse', '$weight', '$temperature', '$doctor_note')";
    
    if(mysqli_query($conn, $insertSql)) {
        $new_id = mysqli_insert_id($conn);
        $_SESSION['success_message'] = "OPD visit created successfully!";
        header("Location: opd_main.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error creating OPD visit: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Add OPD Visit</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .alert { animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>  
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-3xl mx-auto w-full">
                    
                    <?php if(isset($_SESSION['error_message'])): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-red-500 hover:text-red-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-8">
                        <div class="flex items-center gap-4">
                            <a href="opd_main.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">New OPD Visit</h1>
                                <p class="text-gray-500 mt-1">Create a new outpatient department visit record.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 fade-in">
                        <form method="POST" action="">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient <span class="text-red-500">*</span></label>
                                    <select name="patient_id" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                        <option value="">Select Patient</option>
                                        <?php while($patient = mysqli_fetch_assoc($patientsResult)): ?>
                                            <option value="<?php echo $patient['patient_id']; ?>">
                                                <?php echo htmlspecialchars($patient['patient_name'] . ' (' . $patient['mobile'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">OPD Number</label>
                                    <input type="text" placeholder="Enter OPD No..." 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 " >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Visit Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="visit_date" value="DD-MM-YYYY ?>" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Symptoms</label>
                                    <textarea name="symptoms" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                              placeholder="Enter patient symptoms..."></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                    <textarea name="diagnosis" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                              placeholder="Enter diagnosis..."></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <h3 class="text-md font-semibold text-gray-700 mb-3 border-b pb-2">Vital Signs</h3>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Pressure (BP)</label>
                                    <input type="text" name="bp" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 120/80">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pulse (bpm)</label>
                                    <input type="number" name="pulse" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 72">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 75.5">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Temperature (°F)</label>
                                    <input type="number" step="0.1" name="temperature" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 98.6">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Doctor's Note</label>
                                    <textarea name="doctor_note" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                              placeholder="Enter doctor's notes..."></textarea>
                                </div>
                            </div>

                            <div class="flex gap-3 mt-6 pt-6 border-t">
                                <button type="submit" 
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all">
                                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                                    Save OPD Visit
                                </button>
                                <a href="opd_main.php" 
                                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-all">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>