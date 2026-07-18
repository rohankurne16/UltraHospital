<?php 
session_start(); 
include "../config/db.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: opd_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$opdQuery = "SELECT o.*, p.patient_name, p.gender, p.age, p.blood_group, p.mobile, p.email, 
             d.doctor_name, d.department
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - View OPD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .detail-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .detail-card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .detail-card .header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .detail-card .body { padding: 20px 24px; }
        .detail-item { display: flex; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .detail-item:last-child { border-bottom: none; }
        .detail-item .label { font-size: 13px; color: #64748b; width: 140px; flex-shrink: 0; font-weight: 500; }
        .detail-item .value { font-size: 14px; color: #0f172a; font-weight: 500; }
        .vital-badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; margin: 2px; }
        .vital-badge-blue { background: #dbeafe; color: #1e40af; }
        .vital-badge-green { background: #d1fae5; color: #065f46; }
        .vital-badge-amber { background: #fef3c7; color: #92400e; }
        .vital-badge-purple { background: #e0e7ff; color: #3730a3; }
        .vital-badge-red { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex items-center gap-4">
                            <a href="opd_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">OPD Details</h1>
                                <p class="text-gray-500">View complete Outpatient Department visit details.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="detail-card">
                                <div class="header">
                                    <div class="flex items-center justify-between">
                                        <h3>OPD Visit Information</h3>
                                        <span class="text-sm text-gray-500">#<?php echo htmlspecialchars($opdData['opd_no']); ?></span>
                                    </div>
                                </div>
                                <div class="body">
                                    <div class="detail-item">
                                        <span class="label">OPD Number</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['opd_no']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Visit Date</span>
                                        <span class="value"><?php echo date('l, F j, Y', strtotime($opdData['visit_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient Name</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['patient_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Doctor Name</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['doctor_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Department</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['department']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-card">
                                <div class="header"><h3>Clinical Information</h3></div>
                                <div class="body">
                                    <div class="detail-item">
                                        <span class="label">Symptoms</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['symptoms']) ?: 'N/A'; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Diagnosis</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['diagnosis']) ?: 'N/A'; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Doctor's Note</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['doctor_note']) ?: 'N/A'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="detail-card">
                                <div class="header"><h3>Patient Information</h3></div>
                                <div class="body">
                                    <div class="detail-item">
                                        <span class="label">Name</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['patient_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Gender</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['gender'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Age</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['age'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Blood Group</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['blood_group'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Mobile</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['mobile'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Email</span>
                                        <span class="value"><?php echo htmlspecialchars($opdData['email'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-card">
                                <div class="header"><h3>Vital Signs</h3></div>
                                <div class="body">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($opdData['bp']): ?>
                                            <span class="vital-badge vital-badge-blue">🫀 BP: <?php echo htmlspecialchars($opdData['bp']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($opdData['pulse']): ?>
                                            <span class="vital-badge vital-badge-red">❤️ Pulse: <?php echo htmlspecialchars($opdData['pulse']); ?> bpm</span>
                                        <?php endif; ?>
                                        <?php if ($opdData['weight']): ?>
                                            <span class="vital-badge vital-badge-green">⚖️ Weight: <?php echo htmlspecialchars($opdData['weight']); ?> kg</span>
                                        <?php endif; ?>
                                        <?php if ($opdData['temperature']): ?>
                                            <span class="vital-badge vital-badge-amber">🌡️ Temp: <?php echo htmlspecialchars($opdData['temperature']); ?> °F</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$opdData['bp'] && !$opdData['pulse'] && !$opdData['weight'] && !$opdData['temperature']): ?>
                                        <p class="text-gray-400 text-sm">No vital signs recorded</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <a href="edit_opd.php?id=<?php echo $id; ?>" 
                                   class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                    <i data-lucide="edit-2" class="w-4 h-4 mr-2"></i> Edit
                                </a>
                                <a href="opd_list.php" 
                                   class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-all shadow-sm">
                                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back
                                </a>
                            </div>
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