<?php 
session_start(); 
include '../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../../appointments_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT * FROM appointments WHERE appointment_id = '$id' AND (delete_flag=0 OR delete_flag IS NULL)";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: ../../appointments_list.php");
    exit();
}

$appointment = $result->fetch_assoc();

$statusClass = 'status-scheduled';
if (strtolower($appointment['status']) == 'completed') {
    $statusClass = 'status-completed';
} elseif (strtolower($appointment['status']) == 'cancelled') {
    $statusClass = 'status-cancelled';
} elseif (strtolower($appointment['status']) == 'confirmed') {
    $statusClass = 'status-confirmed';
} elseif (strtolower($appointment['status']) == 'in progress') {
    $statusClass = 'status-in-progress';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - View Appointment</title>
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
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../staff_sidebar.php'; ?>
            <main class="main-content">
                <div class="max-w-4xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="appointments_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Appointment Details</h1>
                            <p class="text-gray-500">View complete appointment information</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2">
                            <div class="detail-card">
                                <div class="header"><h3>Appointment Information</h3></div>
                                <div class="body">
                                    <div class="detail-item"><span class="label">Appointment No</span><span class="value font-semibold text-blue-600"><?php echo htmlspecialchars($appointment['appointment_no']); ?></span></div>
                                    <div class="detail-item"><span class="label">Patient</span><span class="value"><?php echo htmlspecialchars($appointment['patient_name']); ?></span></div>
                                    <div class="detail-item"><span class="label">Doctor</span><span class="value"><?php echo htmlspecialchars($appointment['doctor_name']); ?></span></div>
                                    <div class="detail-item"><span class="label">Department</span><span class="value"><?php echo htmlspecialchars($appointment['department']); ?></span></div>
                                    <div class="detail-item"><span class="label">Visit Type</span><span class="value"><?php echo htmlspecialchars($appointment['appointment_type']); ?></span></div>
                                    <div class="detail-item"><span class="label">Date</span><span class="value"><?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?></span></div>
                                    <div class="detail-item"><span class="label">Time</span><span class="value"><?php echo htmlspecialchars($appointment['appointment_time']); ?></span></div>
                                    <div class="detail-item"><span class="label">Duration</span><span class="value"><?php echo htmlspecialchars($appointment['duration']); ?> minutes</span></div>
                                    <div class="detail-item"><span class="label">Reason</span><span class="value"><?php echo htmlspecialchars($appointment['reason']) ?: 'N/A'; ?></span></div>
                                    <div class="detail-item"><span class="label">Notes</span><span class="value"><?php echo htmlspecialchars($appointment['notes']) ?: 'N/A'; ?></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="detail-card">
                                <div class="header"><h3>Status</h3></div>
                                <div class="body">
                                    <div class="text-center py-4">
                                        <span class="status-badge <?php echo $statusClass; ?> text-lg px-6 py-2"><?php echo htmlspecialchars($appointment['status']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-card">
                                <div class="header"><h3>Actions</h3></div>
                                <div class="body space-y-3">
                                    <a href="update_appointment.php?id=<?php echo $id; ?>" class="btn-primary block text-center">
                                        <i data-lucide="edit-2" class="w-4 h-4 inline mr-2"></i> Edit Appointment
                                    </a>
                                    <a href="appointments_list.php" class="btn-secondary block text-center">
                                        <i data-lucide="list" class="w-4 h-4 inline mr-2"></i> Back to List
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
<?php $conn->close(); ?>