<?php 
session_start(); 
include "../config/db.php";

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: prescription_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$prescriptionQuery = "SELECT p.*, pat.patient_name, d.doctor_name 
                      FROM prescriptions p
                      LEFT JOIN patients pat ON p.patient_id = pat.patient_id
                      LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
                      WHERE p.id = '$id' AND (p.delete_flag=0 OR p.delete_flag IS NULL)";
$prescriptionResult = $conn->query($prescriptionQuery);

if ($prescriptionResult->num_rows == 0) {
    header("Location: prescription_list.php");
    exit();
}

$prescription = $prescriptionResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - View Prescription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .detail-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .detail-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .detail-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .detail-card .body { padding: 20px 24px; }
        .detail-item { display: flex; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .detail-item:last-child { border-bottom: none; }
        .detail-item .label { font-size: 13px; color: #64748b; width: 140px; flex-shrink: 0; font-weight: 500; }
        .detail-item .value { font-size: 14px; color: #0f172a; font-weight: 500; }
        .action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        .action-btn:hover { transform: translateY(-1px); }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
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
                        <a href="prescription_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Prescription Details</h1>
                            <p class="text-gray-500">View complete prescription information</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="detail-card">
                            <div class="header"><h3>Prescription Information</h3></div>
                            <div class="body">
                                <div class="detail-item"><span class="label">Patient</span><span class="value"><?php echo htmlspecialchars($prescription['patient_name']); ?></span></div>
                                <div class="detail-item"><span class="label">Doctor</span><span class="value"><?php echo htmlspecialchars($prescription['doctor_name']); ?></span></div>
                                <div class="detail-item"><span class="label">Medicine</span><span class="value font-semibold text-blue-600"><?php echo htmlspecialchars($prescription['medicine_name']); ?></span></div>
                                <div class="detail-item"><span class="label">Dosage</span><span class="value"><?php echo htmlspecialchars($prescription['dosage'] ?? 'N/A'); ?></span></div>
                                <div class="detail-item"><span class="label">Frequency</span><span class="value"><?php echo htmlspecialchars($prescription['frequency'] ?? 'N/A'); ?></span></div>
                                <div class="detail-item"><span class="label">Duration (Days)</span><span class="value"><?php echo htmlspecialchars($prescription['days'] ?? 'N/A'); ?></span></div>
                                <div class="detail-item"><span class="label">Timing</span><span class="value"><?php echo htmlspecialchars($prescription['timing'] ?? 'N/A'); ?></span></div>
                                <div class="detail-item"><span class="label">Follow-up Date</span><span class="value"><?php echo $prescription['followup_date'] ? date('M d, Y', strtotime($prescription['followup_date'])) : 'N/A'; ?></span></div>
                                <div class="detail-item"><span class="label">Advice</span><span class="value"><?php echo htmlspecialchars($prescription['advice'] ?? 'No advice provided'); ?></span></div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="detail-card">
                                <div class="header"><h3>Actions</h3></div>
                                <div class="body space-y-3">
                                    <a href="edit_prescription.php?id=<?php echo $id; ?>" class="action-btn btn-primary justify-center w-full">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Prescription
                                    </a>
                                    <a href="prescription_list.php" class="action-btn btn-secondary justify-center w-full">
                                        <i data-lucide="list" class="w-4 h-4"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>