<?php 
session_start(); 
include "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

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
$doctorQuery = "SELECT doctor_id, doctor_name FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY doctor_name ASC";
$doctorResult = $conn->query($doctorQuery);
$doctors = array();
if ($doctorResult && $doctorResult->num_rows > 0) {
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Fetch wards for dropdown
$wardQuery = "SELECT ward_id, ward_name FROM wards WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY ward_name ASC";
$wardResult = $conn->query($wardQuery);
$wards = array();
if ($wardResult && $wardResult->num_rows > 0) {
    while ($row = $wardResult->fetch_assoc()) {
        $wards[] = $row;
    }
}

$message = "";
$messageType = "";

// Generate admission number
$admissionNoQuery = "SELECT COUNT(*) AS count FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL)";
$admissionNoResult = $conn->query($admissionNoQuery);
$admissionCount = $admissionNoResult->fetch_assoc();
$admission_number = "IPD-" . str_pad(($admissionCount['count'] + 1), 4, "0", STR_PAD_LEFT);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $admission_no = mysqli_real_escape_string($conn, $_POST['admission_no']);
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $ward_id = mysqli_real_escape_string($conn, $_POST['ward_id']);
    $room_no = mysqli_real_escape_string($conn, $_POST['room_no']);
    $bed_no = mysqli_real_escape_string($conn, $_POST['bed_no']);
    $admission_date = mysqli_real_escape_string($conn, $_POST['admission_date']);
    $disease_reason = mysqli_real_escape_string($conn, $_POST['disease_reason']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($patient_id) || empty($doctor_id) || empty($admission_date)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $insertQuery = "INSERT INTO ipd_admissions (
            admission_no, patient_id, doctor_id, ward_id, room_no, bed_no, 
            admission_date, disease_reason, status, delete_flag
        ) VALUES (
            '$admission_no', '$patient_id', '$doctor_id', '$ward_id', '$room_no', '$bed_no',
            '$admission_date', '$disease_reason', '$status', 0
        )";

        if ($conn->query($insertQuery) === TRUE) {
            $message = "Patient admitted successfully!";
            $messageType = "success";
            $_POST = array();
            $admissionCount['count']++;
            $admission_number = "IPD-" . str_pad(($admissionCount['count'] + 1), 4, "0", STR_PAD_LEFT);
            echo "<script>
                alert('Patient admitted successfully!');
                window.location='admission_list.php';
            </script>";
            exit();
        } else {
            $message = "Error admitting patient: " . $conn->error;
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
    <title>MedixPro - Admit Patient</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }
        
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .form-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .form-card .body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #0f172a; margin-bottom: 4px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; transition: all 0.2s ease; outline: none; background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-group input[readonly] { background: #f1f5f9; cursor: not-allowed; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
        @media (max-width: 768px) { .form-card .body { padding: 16px; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff/staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-4xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="admission_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Admit Patient</h1>
                            <p class="text-gray-500">Register a new patient for IPD admission</p>
                        </div>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?> fade-in">
                            <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-card fade-in">
                        <div class="header">
                            <h3>Patient Admission Details</h3>
                        </div>
                        <div class="body">
                            <form action="admit_patient.php" method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="admission_no">Admission No</label>
                                        <input type="text" id="admission_no" name="admission_no" 
                                               value="<?php echo $admission_number; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="admission_date">Admission Date <span class="required">*</span></label>
                                        <input type="date" id="admission_date" name="admission_date" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="patient_id">Patient <span class="required">*</span></label>
                                        <select id="patient_id" name="patient_id" required>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['patient_id']; ?>" 
                                                    <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($patient['patient_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="doctor_id">Doctor <span class="required">*</span></label>
                                        <select id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            <?php foreach ($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['doctor_id']; ?>" 
                                                    <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="form-group">
                                        <label for="ward_id">Ward</label>
                                        <select id="ward_id" name="ward_id">
                                            <option value="">Select Ward</option>
                                            <?php foreach ($wards as $ward): ?>
                                                <option value="<?php echo $ward['ward_id']; ?>" 
                                                    <?php echo (isset($_POST['ward_id']) && $_POST['ward_id'] == $ward['ward_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ward['ward_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="room_no">Room No</label>
                                        <input type="text" id="room_no" name="room_no" placeholder="Enter room number"
                                               value="<?php echo isset($_POST['room_no']) ? htmlspecialchars($_POST['room_no']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="bed_no">Bed No</label>
                                        <input type="text" id="bed_no" name="bed_no" placeholder="Enter bed number"
                                               value="<?php echo isset($_POST['bed_no']) ? htmlspecialchars($_POST['bed_no']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="disease_reason">Disease / Reason for Admission</label>
                                    <textarea id="disease_reason" name="disease_reason" rows="3" 
                                              placeholder="Enter disease or reason for admission"><?php echo isset($_POST['disease_reason']) ? htmlspecialchars($_POST['disease_reason']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status">
                                        <option value="Admitted" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Admitted') ? 'selected' : ''; ?>>Admitted</option>
                                        <option value="Discharged" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Discharged') ? 'selected' : ''; ?>>Discharged</option>
                                    </select>
                                </div>

                                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                    <button type="submit" name="submit" class="btn-primary">
                                        <i data-lucide="hospital" class="w-4 h-4 inline mr-2"></i>
                                        Admit Patient
                                    </button>
                                    <button type="reset" class="btn-secondary">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4 inline mr-2"></i>
                                        Reset
                                    </button>
                                    <a href="admission_list.php" class="btn-secondary">
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

<?php $conn->close(); ?>