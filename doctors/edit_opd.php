<?php
session_start();
include '../config/hospital.php';

if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}

if(!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$opd_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;
$doctor_reg_id = $_SESSION['id'];

$getDoctor = "SELECT doctor_id FROM doctor WHERE register_id='$doctor_reg_id'";
$all_doctor_info = $conn->query($getDoctor);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_id = $doctor["doctor_id"];
}

$sql = "SELECT o.*, p.patient_name, p.mobile, p.gender, p.age, p.address, p.blood_group, p.email 
        FROM opd o 
        LEFT JOIN patients p ON o.patient_id = p.patient_id 
        WHERE o.id='$opd_id' 
        AND o.doctor_id='$doctor_id' 
        AND (o.delete_flag=0 OR o.delete_flag IS NULL)";

$result = mysqli_query($conn, $sql);
$opd = mysqli_fetch_assoc($result);

if(!$opd) {
    $_SESSION['error_message'] = "OPD record not found.";
    header("Location: opd.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
    $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
    $doctor_note = mysqli_real_escape_string($conn, $_POST['doctor_note']);
    
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $updateSql = "UPDATE opd SET 
                  visit_date = '$visit_date', 
                  symptoms = '$symptoms', 
                  diagnosis = '$diagnosis', 
                  bp = '$bp', 
                  pulse = '$pulse', 
                  weight = '$weight', 
                  temperature = '$temperature', 
                  doctor_note = '$doctor_note', 
                  modified_at = NOW() 
                  WHERE id = '$opd_id' AND doctor_id = '$doctor_id'";
    
    if(mysqli_query($conn, $updateSql)) {
        $patient_id = $opd['patient_id'];
        $updatePatientSql = "UPDATE patients SET 
                             patient_name = '$patient_name', 
                             mobile = '$mobile', 
                             gender = '$gender', 
                             age = '$age', 
                             address = '$address', 
                             blood_group = '$blood_group', 
                             email = '$email' 
                             WHERE patient_id = '$patient_id'";
        
        if(mysqli_query($conn, $updatePatientSql)) {
            $_SESSION['success_message'] = "OPD record updated successfully!";
            header("Location: view_opd.php?id=" . $opd_id);
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating patient data: " . mysqli_error($conn);
            header("Location: opd_edit.php?id=" . $opd_id);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Error updating OPD record: " . mysqli_error($conn);
        header("Location: opd_edit.php?id=" . $opd_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Edit OPD Record</title>
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
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
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
                            <a href="opd_main.php?id=<?php echo $opd_id; ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Edit OPD Record</h1>
                                <p class="text-gray-500 mt-1">Update OPD record for <?php echo htmlspecialchars($opd['patient_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 fade-in">
                        <form method="POST" action="">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <div class="md:col-span-2">
                                    <h3 class="text-md font-semibold text-gray-700 mb-3 border-b pb-2">Patient Information</h3>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="patient_name" value="<?php echo htmlspecialchars($opd['patient_name'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" required>     
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile <span class="text-red-500">*</span></label>
                                    <input type="text" name="mobile" value="<?php echo htmlspecialchars($opd['mobile'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" required>     
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                                    <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" required>
                                        <option value="Male" <?php echo ($opd['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($opd['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($opd['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Age <span class="text-red-500">*</span></label>
                                    <input type="number" name="age" value="<?php echo htmlspecialchars($opd['age'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" required>     
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                                    <select name="blood_group" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                        <option value="">Select Blood Group</option>
                                        <option value="A+" <?php echo ($opd['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo ($opd['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo ($opd['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo ($opd['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo ($opd['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo ($opd['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo ($opd['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo ($opd['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($opd['email'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">     
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"><?php echo htmlspecialchars($opd['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <h3 class="text-md font-semibold text-gray-700 mb-3 border-b pb-2">OPD Information</h3>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Visit Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="visit_date" value="<?php echo $opd['visit_date']; ?>" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">OPD Number</label>
                                    <input type="text" value="<?php echo htmlspecialchars($opd['opd_no']); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" disabled>    
                                </div>

                                <div class="md:col-span-2">
                                    <h3 class="text-md font-semibold text-gray-700 mb-3 border-b pb-2">Clinical Details</h3>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Symptoms</label>
                                    <textarea name="symptoms" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                              placeholder="Enter patient symptoms..."><?php echo htmlspecialchars($opd['symptoms'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                    <textarea name="diagnosis" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                              placeholder="Enter diagnosis..."><?php echo htmlspecialchars($opd['diagnosis'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <h3 class="text-md font-semibold text-gray-700 mb-3 border-b pb-2">Vital Signs</h3>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Pressure (BP)</label>
                                    <input type="text" name="bp" value="<?php echo htmlspecialchars($opd['bp'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 120/80">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pulse (bpm)</label>
                                    <input type="number" name="pulse" value="<?php echo htmlspecialchars($opd['pulse'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 72">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight" value="<?php echo htmlspecialchars($opd['weight'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 75.5">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Temperature (°F)</label>
                                    <input type="number" step="0.1" name="temperature" value="<?php echo htmlspecialchars($opd['temperature'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           placeholder="e.g., 98.6">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Doctor's Note</label>
                                    <textarea name="doctor_note" rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                              placeholder="Enter doctor's notes..."><?php echo htmlspecialchars($opd['doctor_note'] ?? ''); ?></textarea>
                                </div>
                            </div>
<div class="flex gap-3 mt-6 pt-6 border-t">
    <button type="butt" 
            onclick="window.location.href='opd_main.php'"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all">
        <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
        Update Record
    </button>

    <button type="button" 
            onclick="window.location.href='opd_main.php'"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-all">
        Cancel
    </button>
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