<?php 
session_start(); 
include "../config/db.php";



// Fetch admitted patients for discharge
$patientQuery = "SELECT a.*, p.patient_name, p.mobile, d.doctor_name 
                 FROM ipd_admission a
                 LEFT JOIN patients p ON a.patient_id = p.patient_id
                 LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
                 WHERE a.status = 'Admitted' AND (a.delete_flag=0 OR a.delete_flag IS NULL)
                 ORDER BY a.admission_date DESC";
$patientResult = $conn->query($patientQuery);
$patients = array();
if ($patientResult && $patientResult->num_rows > 0) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $admission_id = mysqli_real_escape_string($conn, $_POST['admission_id']);
    $discharge_date = mysqli_real_escape_string($conn, $_POST['discharge_date']);
    $discharge_reason = mysqli_real_escape_string($conn, $_POST['discharge_reason']);
    $discharge_notes = mysqli_real_escape_string($conn, $_POST['discharge_notes']);

    if (empty($admission_id) || empty($discharge_date)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $updateQuery = "UPDATE ipd_admission SET 
                        status = 'Discharged', 
                        discharge_date = '$discharge_date',
                        discharge_reason = '$discharge_reason',
                        discharge_notes = '$discharge_notes'
                        WHERE id = '$admission_id'";
        if ($conn->query($updateQuery) === TRUE) {
            echo "<script>alert('Patient discharged successfully!'); window.location='discharge_list.php';</script>";
            exit();
        } else {
            $message = "Error discharging patient: " . $conn->error;
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
    <title>MedixPro - Discharge Summary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .form-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .form-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .form-card .body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #0f172a; margin-bottom: 4px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s ease; outline: none; background: white; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        .patient-card { background: #f8fafc; border-radius: 8px; padding: 16px; border: 1px solid #e2e8f0; margin-bottom: 16px; }
        .patient-card:hover { border-color: #3b82f6; }
        .patient-card .name { font-weight: 600; color: #0f172a; }
        .patient-card .info { font-size: 13px; color: #64748b; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
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
                        <a href="discharge_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Discharge Summary</h1>
                            <p class="text-gray-500">Discharge a patient from the hospital</p>
                        </div>
                    </div>
                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                            <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="form-card">
                        <div class="header"><h3>Select Patient for Discharge</h3></div>
                        <div class="body">
                            <form action="discharge_summary.php" method="POST">
                                <div class="form-group">
                                    <label for="admission_id">Patient <span class="required">*</span></label>
                                    <select id="admission_id" name="admission_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['id']; ?>">
                                                <?php echo htmlspecialchars($patient['patient_name']); ?> - 
                                                Ward: <?php echo htmlspecialchars($patient['ward_id'] ?? 'N/A'); ?> 
                                                (<?php echo htmlspecialchars($patient['admission_no']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="discharge_date">Discharge Date <span class="required">*</span></label>
                                    <input type="date" id="discharge_date" name="discharge_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="discharge_reason">Discharge Reason</label>
                                    <input type="text" id="discharge_reason" name="discharge_reason" placeholder="Reason for discharge">
                                </div>
                                <div class="form-group">
                                    <label for="discharge_notes">Discharge Notes</label>
                                    <textarea id="discharge_notes" name="discharge_notes" rows="3" placeholder="Additional notes about discharge"></textarea>
                                </div>
                                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
                                    <button type="submit" name="submit" class="btn-primary">
                                        <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i> Discharge Patient
                                    </button>
                                    <a href="discharge_list.php" class="btn-secondary">
                                        <i data-lucide="list" class="w-4 h-4 inline mr-2"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php $conn->close(); ?>