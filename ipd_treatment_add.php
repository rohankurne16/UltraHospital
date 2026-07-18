<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

$message = "";
$messageType = "";

// Get IPD ID from URL if passed
$ipd_id_param = isset($_GET['ipd_id']) ? intval($_GET['ipd_id']) : 0;

// Fetch IPD admissions
$ipd_admissions = [];
$result = $conn->query("SELECT a.id, a.admission_no, a.patient_id, a.doctor_id, a.ward_id, a.room_no, a.bed_no,
                        p.patient_name, d.doctor_name, w.ward_name
                        FROM ipd_admissions a
                        LEFT JOIN patients p ON a.patient_id = p.patient_id
                        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
                        LEFT JOIN ward_master w ON a.ward_id = w.ward_id
                        WHERE a.delete_flag = 0 AND a.status = 'Admitted'
                        ORDER BY a.admission_no DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ipd_admissions[] = $row;
    }
}

// Fetch doctors
$doctors = [];
$result = $conn->query("SELECT doctor_id, doctor_name FROM doctor WHERE delete_flag = 0 ORDER BY doctor_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ipd_id = mysqli_real_escape_string($conn, $_POST['ipd_id'] ?? '');
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id'] ?? '');
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Active');
    
    // Get patient_id from ipd_admission
    $patient_id = 0;
    if (!empty($ipd_id)) {
        $result = $conn->query("SELECT patient_id FROM ipd_admissions WHERE id = '$ipd_id'");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $patient_id = $row['patient_id'];
        }
    }
    
    if (empty($ipd_id) || empty($doctor_id)) {
        $message = "Please fill all required fields!";
        $messageType = "error";
    } else {
        // Check if treatment already exists for this IPD
        $check = $conn->query("SELECT treatment_master_id FROM ipd_treatment_master WHERE ipd_id = '$ipd_id' AND delete_flag = 0");
        if ($check && $check->num_rows > 0) {
            $message = "Treatment already exists for this IPD admission!";
            $messageType = "error";
        } else {
            $sql = "INSERT INTO ipd_treatment_master (
                        ipd_id, patient_id, doctor_id, diagnosis, status
                    ) VALUES (
                        '$ipd_id', '$patient_id', '$doctor_id', '$diagnosis', '$status'
                    )";
            
            if ($conn->query($sql)) {
                $master_id = $conn->insert_id;
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Treatment started successfully!'];
                header("Location: ipd_treatment_daily_add.php?master_id=$master_id");
                exit();
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $hospital['hospital_name'] ?> -IPD treatment add</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { width: 260px; height: 100vh; background: #f8f3f3; position: fixed; left: 0; top: 0; color: #0b0707; z-index: 50; overflow-y: auto; }
        .header { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; position: fixed; left: 260px; right: 0; top: 0; z-index: 40; display: flex; align-items: center; padding: 0 1.5rem; }
        .main-content { margin-left: 260px; padding: 84px 28px 20px 28px; min-height: 100vh; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; max-width: 800px; margin: 0 auto; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; position: static; }
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
        .form-group input[readonly] { background: #f1f5f9; cursor: not-allowed; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } .header { left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar"><?php include 'Sidebar.php'; ?></div>
    <header class="header"><div class="flex items-center justify-between w-full"><?php include 'header.php'; ?></div></header>

    <main class="main-content">
        <!-- Page Header with Back Button -->
        <div class="mb-6 flex items-center gap-4">
            <a href="ipd_treatment_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 p-2 transition-colors shadow-sm">
                <i class="fas fa-arrow-left text-gray-600"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Start IPD Treatment</h1>
                <p class="text-gray-500">Create new treatment record for IPD patient</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="header"><h3 class="font-semibold text-gray-800">Treatment Details</h3></div>
            <div class="body">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>IPD Admission <span class="required">*</span></label>
                            <select id="ipd_id" name="ipd_id" required>
                                <option value="">Select IPD Admission</option>
                                <?php foreach ($ipd_admissions as $ipd): ?>
                                    <option value="<?php echo $ipd['id']; ?>" <?php echo ($ipd_id_param == $ipd['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ipd['admission_no']); ?> - <?php echo htmlspecialchars($ipd['patient_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Doctor <span class="required">*</span></label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['doctor_id']; ?>"><?php echo htmlspecialchars($doctor['doctor_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Diagnosis</label>
                            <textarea id="diagnosis" name="diagnosis" rows="3"><?php echo isset($_POST['diagnosis']) ? htmlspecialchars($_POST['diagnosis']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Discharged">Discharged</option>
                                <option value="Transferred">Transferred</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                        <button type="submit" class="btn-primary">Start Treatment</button>
                        <a href="ipd_treatment_list.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>