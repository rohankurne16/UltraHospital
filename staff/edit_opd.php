<?php 
session_start(); 
include "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: opd_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$message = "";
$messageType = "";

// Fetch OPD record
$opdQuery = "SELECT o.*, p.patient_name, d.doctor_name, d.department 
             FROM opd o
             LEFT JOIN patients p ON o.patient_id = p.patient_id
             LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
             WHERE o.id = '$id' AND (o.delete_flag=0 OR o.delete_flag IS NULL)";
$opdResult = $conn->query($opdQuery);

if ($opdResult->num_rows == 0) {
    header("Location: opd_list.php");
    exit();
}

$opdData = $opdResult->fetch_assoc();

// Fetch patients for dropdown
$patientQuery = "SELECT patient_id, patient_name FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY patient_name ASC";
$patientResult = $conn->query($patientQuery);
$patients = array();
if ($patientResult && $patientResult->num_rows > 0) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch doctors for dropdown
$doctorQuery = "SELECT doctor_id, doctor_name, department FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY doctor_name ASC";
$doctorResult = $conn->query($doctorQuery);
$doctors = array();
if ($doctorResult && $doctorResult->num_rows > 0) {
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $opd_no = mysqli_real_escape_string($conn, $_POST['opd_no']);
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
    $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
    $doctor_note = mysqli_real_escape_string($conn, $_POST['doctor_note']);

    if (empty($opd_no) || empty($patient_id) || empty($doctor_id) || empty($visit_date)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $updateQuery = "UPDATE opd SET 
            opd_no = '$opd_no',
            patient_id = '$patient_id',
            doctor_id = '$doctor_id',
            visit_date = '$visit_date',
            symptoms = '$symptoms',
            diagnosis = '$diagnosis',
            bp = '$bp',
            pulse = '$pulse',
            weight = '$weight',
            temperature = '$temperature',
            doctor_note = '$doctor_note'
            WHERE id = '$id'";

        if ($conn->query($updateQuery) === TRUE) {
            $message = "OPD record updated successfully!";
            $messageType = "success";
            
            // Refresh data
            $opdQuery = "SELECT o.*, p.patient_name, d.doctor_name, d.department 
                         FROM opd o
                         LEFT JOIN patients p ON o.patient_id = p.patient_id
                         LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
                         WHERE o.id = '$id' AND (o.delete_flag=0 OR o.delete_flag IS NULL)";
            $opdResult = $conn->query($opdQuery);
            $opdData = $opdResult->fetch_assoc();
            
            echo "<script>
                alert('OPD record updated successfully!');
                window.location='opd_list.php';
            </script>";
            exit();
        } else {
            $message = "Error updating OPD record: " . $conn->error;
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Edit OPD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .form-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .form-card .body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #0f172a; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; transition: all 0.2s ease; outline: none; background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        .selected-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; }
        .selected-info .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .selected-info .value { font-size: 14px; font-weight: 600; color: #0f172a; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .form-card .body { padding: 16px; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-4xl mx-auto w-full">
                    <!-- Page Header -->
                    <div class="mb-6 flex items-center gap-4">
                        <a href="opd_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit OPD Record</h1>
                            <p class="text-gray-500">Update the OPD visit details</p>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?> fade-in">
                            <i data-lucide="<?php echo ($messageType === 'success') ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Edit Form -->
                    <div class="form-card fade-in">
                        <div class="header">
                            <h3>OPD #<?php echo htmlspecialchars($opdData['opd_no']); ?></h3>
                        </div>
                        <div class="body">
                            <form action="edit_opd.php?id=<?php echo $id; ?>" method="POST">
                                <!-- Row 1: OPD No & Visit Date -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="opd_no">OPD Number <span class="text-red-500">*</span></label>
                                        <input type="text" id="opd_no" name="opd_no" 
                                               value="<?php echo htmlspecialchars($opdData['opd_no']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="visit_date">Visit Date <span class="text-red-500">*</span></label>
                                        <input type="date" id="visit_date" name="visit_date" 
                                               value="<?php echo htmlspecialchars($opdData['visit_date']); ?>" required>
                                    </div>
                                </div>

                                <!-- Row 2: Patient & Doctor -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="patient_id">Patient <span class="text-red-500">*</span></label>
                                        <select id="patient_id" name="patient_id" required>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['patient_id']; ?>" 
                                                    <?php echo ($patient['patient_id'] == $opdData['patient_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($patient['patient_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="doctor_id">Doctor <span class="text-red-500">*</span></label>
                                        <select id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            <?php foreach ($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['doctor_id']; ?>" 
                                                    <?php echo ($doctor['doctor_id'] == $opdData['doctor_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?> (<?php echo htmlspecialchars($doctor['department']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Row 3: Symptoms & Diagnosis -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="symptoms">Symptoms</label>
                                        <textarea id="symptoms" name="symptoms" rows="2" 
                                                  placeholder="Enter symptoms"><?php echo htmlspecialchars($opdData['symptoms']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="diagnosis">Diagnosis</label>
                                        <textarea id="diagnosis" name="diagnosis" rows="2" 
                                                  placeholder="Enter diagnosis"><?php echo htmlspecialchars($opdData['diagnosis']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Row 4: Vital Signs -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="form-group">
                                        <label for="bp">Blood Pressure</label>
                                        <input type="text" id="bp" name="bp" placeholder="120/80"
                                               value="<?php echo htmlspecialchars($opdData['bp']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="pulse">Pulse (bpm)</label>
                                        <input type="text" id="pulse" name="pulse" placeholder="72"
                                               value="<?php echo htmlspecialchars($opdData['pulse']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="weight">Weight (kg)</label>
                                        <input type="text" id="weight" name="weight" placeholder="70"
                                               value="<?php echo htmlspecialchars($opdData['weight']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="temperature">Temperature (°F)</label>
                                        <input type="text" id="temperature" name="temperature" placeholder="98.6"
                                               value="<?php echo htmlspecialchars($opdData['temperature']); ?>">
                                    </div>
                                </div>

                                <!-- Row 5: Doctor's Note -->
                                <div class="form-group">
                                    <label for="doctor_note">Doctor's Note</label>
                                    <textarea id="doctor_note" name="doctor_note" rows="2" 
                                              placeholder="Additional notes"><?php echo htmlspecialchars($opdData['doctor_note']); ?></textarea>
                                </div>

                                <!-- Selected Info -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div class="selected-info">
                                        <div class="label">Selected Patient</div>
                                        <div class="value"><?php echo htmlspecialchars($opdData['patient_name']); ?></div>
                                    </div>
                                    <div class="selected-info">
                                        <div class="label">Selected Doctor</div>
                                        <div class="value"><?php echo htmlspecialchars($opdData['doctor_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($opdData['department']); ?></div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                    <button type="submit" name="submit" class="btn-primary">
                                        <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                                        Update OPD
                                    </button>
                                    <a href="view_opd.php?id=<?php echo $id; ?>" class="btn-secondary">
                                        <i data-lucide="eye" class="w-4 h-4 inline mr-2"></i>
                                        View
                                    </a>
                                    <a href="opd_list.php" class="btn-secondary">
                                        <i data-lucide="list" class="w-4 h-4 inline mr-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>