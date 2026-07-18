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

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $medicine_name = mysqli_real_escape_string($conn, $_POST['medicine_name']);
    $dosage = mysqli_real_escape_string($conn, $_POST['dosage']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $days = mysqli_real_escape_string($conn, $_POST['days']);
    $timing = mysqli_real_escape_string($conn, $_POST['timing']);
    $advice = mysqli_real_escape_string($conn, $_POST['advice']);
    $followup_date = mysqli_real_escape_string($conn, $_POST['followup_date']);

    if (empty($patient_id) || empty($doctor_id) || empty($medicine_name)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $insertQuery = "INSERT INTO prescriptions (
            patient_id, doctor_id, medicine_name, dosage, frequency, 
            days, timing, advice, followup_date, delete_flag
        ) VALUES (
            '$patient_id', '$doctor_id', '$medicine_name', '$dosage', '$frequency',
            '$days', '$timing', '$advice', '$followup_date', 0
        )";

        if ($conn->query($insertQuery) === TRUE) {
            $message = "Prescription created successfully!";
            $messageType = "success";
            $_POST = array();
            echo "<script>
                alert('Prescription created successfully!');
                window.location='prescription_list.php';
            </script>";
            exit();
        } else {
            $message = "Error creating prescription: " . $conn->error;
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
    <title>MedixPro - Create Prescription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        
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
                        <a href="prescription_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Create Prescription</h1>
                            <p class="text-gray-500">Create a new prescription for a patient</p>
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
                            <h3>Prescription Details</h3>
                        </div>
                        <div class="body">
                            <form action="create_prescription.php" method="POST">
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

                                <div class="form-group">
                                    <label for="medicine_name">Medicine Name <span class="required">*</span></label>
                                    <input type="text" id="medicine_name" name="medicine_name" 
                                           placeholder="Enter medicine name" required
                                           value="<?php echo isset($_POST['medicine_name']) ? htmlspecialchars($_POST['medicine_name']) : ''; ?>">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="form-group">
                                        <label for="dosage">Dosage</label>
                                        <input type="text" id="dosage" name="dosage" 
                                               placeholder="e.g., 500mg"
                                               value="<?php echo isset($_POST['dosage']) ? htmlspecialchars($_POST['dosage']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="frequency">Frequency</label>
                                        <input type="text" id="frequency" name="frequency" 
                                               placeholder="e.g., Twice daily"
                                               value="<?php echo isset($_POST['frequency']) ? htmlspecialchars($_POST['frequency']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="days">Days</label>
                                        <input type="number" id="days" name="days" 
                                               placeholder="Number of days" min="1"
                                               value="<?php echo isset($_POST['days']) ? htmlspecialchars($_POST['days']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="timing">Timing</label>
                                        <input type="text" id="timing" name="timing" 
                                               placeholder="e.g., Morning, After meals"
                                               value="<?php echo isset($_POST['timing']) ? htmlspecialchars($_POST['timing']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="followup_date">Follow-up Date</label>
                                        <input type="date" id="followup_date" name="followup_date"
                                               value="<?php echo isset($_POST['followup_date']) ? htmlspecialchars($_POST['followup_date']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="advice">Advice / Special Instructions</label>
                                    <textarea id="advice" name="advice" rows="3" 
                                              placeholder="Enter any additional advice or instructions"><?php echo isset($_POST['advice']) ? htmlspecialchars($_POST['advice']) : ''; ?></textarea>
                                </div>

                                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                    <button type="submit" name="submit" class="btn-primary">
                                        <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                                        Create Prescription
                                    </button>
                                    <button type="reset" class="btn-secondary">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4 inline mr-2"></i>
                                        Reset
                                    </button>
                                    <a href="prescription_list.php" class="btn-secondary">
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