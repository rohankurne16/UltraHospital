<?php 
session_start(); 
include "../config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: prescription_list.php");
    exit();
}

$doctor_register_id = $_SESSION["id"];

$sql = "SELECT * FROM doctor WHERE register_id='$doctor_register_id'";
$all_doctor_info = $conn->query($sql);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_name = $doctor["doctor_name"];
    $doctor_id = $doctor["doctor_id"];
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
   
    $medicine_name = mysqli_real_escape_string($conn, $_POST['medicine_name']);
    $dosage = mysqli_real_escape_string($conn, $_POST['dosage']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $days = mysqli_real_escape_string($conn, $_POST['days']);
    $timing = mysqli_real_escape_string($conn, $_POST['timing']);
    $advice = mysqli_real_escape_string($conn, $_POST['advice']);
    $followup_date = mysqli_real_escape_string($conn, $_POST['followup_date']);

    $update_query = "UPDATE prescriptions SET 
                     patient_id = '$patient_id', 
                     medicine_name = '$medicine_name', 
                     dosage = '$dosage', 
                     frequency = '$frequency', 
                     days = '$days', 
                     timing = '$timing', 
                     advice = '$advice', 
                     followup_date = '$followup_date', 
                     modified_at = NOW() 
                     WHERE id = '$id'";

    if ($conn->query($update_query)) {
        echo "<script>
            alert('Prescription updated successfully!');
            window.location.href='prescription_list.php';
        </script>";
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

$fetch_query = "SELECT * FROM prescriptions WHERE id = '$id'";
$result = $conn->query($fetch_query);
if (!$result || $result->num_rows == 0) {
    header("Location: prescription_list.php");
    exit();
}
$data = $result->fetch_assoc();

$patients_result = $conn->query("SELECT patient_id, patient_name FROM patients WHERE delete_flag = 0 OR delete_flag IS NULL");

// Get patient name for selected patient
$selected_patient_name = '';
if (!empty($data['patient_id'])) {
    $patientQuery = "SELECT patient_name FROM patients WHERE patient_id = '" . $data['patient_id'] . "'";
    $patientResult = $conn->query($patientQuery);
    if ($patientResult && $patientResult->num_rows > 0) {
        $patientData = $patientResult->fetch_assoc();
        $selected_patient_name = $patientData['patient_name'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Edit Prescription</title>
    
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .card-header h3 { font-size: 18px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #475569; margin-bottom: 6px; }
        .form-label .required { color: #ef4444; }
        .form-input { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-input:disabled { background: #f1f5f9; cursor: not-allowed; }
        .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s; min-height: 80px; resize: vertical; }
        .form-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 640px) { .btn-actions { flex-direction: column; } .btn-actions a, .btn-actions button { width: 100%; justify-content: center; } }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
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
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content w-full">
                <div class="max-w-4xl mx-auto fade-in">
                    <div class="mb-8">
                        <div class="flex items-center gap-4">
                            <a href="prescription_list.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Edit Prescription</h1>
                                <p class="text-gray-500">Modify prescription details for #<?php echo $id; ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle w-5 h-5"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3><i class="fas fa-edit mr-2 text-blue-500"></i> Update Details</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="form-label">Patient <span class="required">*</span></label>
                                        <input type="text" 
                                               name="patient_name" 
                                               class="form-input" 
                                               value="<?php echo htmlspecialchars($selected_patient_name); ?>" 
                                               placeholder="Enter patient name" 
                                               disabled>
                                        <input type="hidden" name="patient_id" value="<?php echo $data['patient_id']; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Doctor <span class="required">*</span></label>
                                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($doctor_name); ?>" disabled>
                                        <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                                    </div>

                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">Medicine Name <span class="required">*</span></label>
                                        <input type="text" name="medicine_name" class="form-input" value="<?php echo htmlspecialchars($data['medicine_name']); ?>" placeholder="e.g. Paracetamol" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Dosage <span class="required">*</span></label>
                                        <input type="text" name="dosage" class="form-input" value="<?php echo htmlspecialchars($data['dosage']); ?>" placeholder="e.g. 500mg" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Frequency <span class="required">*</span></label>
                                        <input type="text" name="frequency" class="form-input" value="<?php echo htmlspecialchars($data['frequency']); ?>" placeholder="e.g. Twice a day" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Duration (Days) <span class="required">*</span></label>
                                        <input type="number" name="days" class="form-input" value="<?php echo htmlspecialchars($data['days']); ?>" placeholder="e.g. 7" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Timing <span class="required">*</span></label>
                                        <select name="timing" class="form-input" required>
                                            <option value="Morning" <?php echo ($data['timing'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Afternoon" <?php echo ($data['timing'] == 'Afternoon') ? 'selected' : ''; ?>>Afternoon</option>
                                            <option value="Evening" <?php echo ($data['timing'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                            <option value="Night" <?php echo ($data['timing'] == 'Night') ? 'selected' : ''; ?>>Night</option>
                                            <option value="M-A-N" <?php echo ($data['timing'] == 'M-A-N') ? 'selected' : ''; ?>>M-A-N</option>
                                            <option value="M-N" <?php echo ($data['timing'] == 'M-N') ? 'selected' : ''; ?>>M-N</option>
                                        </select>
                                    </div>

                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">Advice / Instructions</label>
                                        <textarea name="advice" class="form-textarea" placeholder="e.g. Take after meal"><?php echo htmlspecialchars($data['advice']); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Follow-up Date</label>
                                        <input type="date" name="followup_date" class="form-input" value="<?php echo $data['followup_date']; ?>">
                                    </div>
                                </div>

                                <div class="mt-8 flex items-center justify-end gap-4">
                                    <a href="prescription_list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-all">
                                        <i class="fas fa-times mr-2"></i> Cancel
                                    </a>
                                    <button type="submit" name="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                        <i class="fas fa-save mr-2"></i> Update Prescription
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Any additional JavaScript can go here
        });
    </script>
</body>
</html>