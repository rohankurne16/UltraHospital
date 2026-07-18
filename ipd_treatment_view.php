<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid treatment ID!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

// Fetch treatment master details
$sql = "SELECT tm.*, 
        p.patient_name, p.patient_id as patient_code, p.age, p.gender, p.mobile, p.email, p.address,
        d.doctor_name, d.specialization,
        a.admission_no, a.ward_id, a.room_no, a.bed_no, a.admission_date,
        w.ward_name
        FROM ipd_treatment_master tm
        LEFT JOIN patients p ON tm.patient_id = p.patient_id
        LEFT JOIN doctor d ON tm.doctor_id = d.doctor_id
        LEFT JOIN ipd_admissions a ON tm.ipd_id = a.id
        LEFT JOIN ward_master w ON a.ward_id = w.ward_id
        WHERE tm.treatment_master_id = '$id' AND tm.delete_flag = 0";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Treatment not found!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

$treatment = $result->fetch_assoc();

// ========== FETCH DAILY TREATMENTS WITH VITALS AND MEDICINES ==========
$daily_treatments = [];
$daily_result = $conn->query("SELECT * FROM ipd_treatment_daily WHERE treatment_master_id = '$id' AND delete_flag = 0 ORDER BY day_number ASC");

if ($daily_result && $daily_result->num_rows > 0) {
    while ($row = $daily_result->fetch_assoc()) {
        $daily_id = $row['treatment_daily_id'];
        
        // Fetch vitals for this day using treatment_daily_id
        $vitals = [];
        $vital_result = $conn->query("SELECT * FROM ipd_vitals WHERE treatment_daily_id = '$daily_id' AND delete_flag = 0");
        if ($vital_result && $vital_result->num_rows > 0) {
            while ($v = $vital_result->fetch_assoc()) {
                $vitals[] = $v;
            }
        }
        $row['vitals'] = $vitals;
        
        // Fetch medicines for this day using treatment_daily_id
        $medicines = [];
        $med_result = $conn->query("SELECT * FROM ipd_medicine_chart WHERE treatment_daily_id = '$daily_id' AND delete_flag = 0");
        if ($med_result && $med_result->num_rows > 0) {
            while ($m = $med_result->fetch_assoc()) {
                $medicines[] = $m;
            }
        }
        $row['medicines'] = $medicines;
        
        $daily_treatments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View IPD Treatment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { width: 260px; height: 100vh; background: #f8f3f3; position: fixed; left: 0; top: 0; color: #0b0707; z-index: 50; overflow-y: auto; }
        .header { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; position: fixed; left: 260px; right: 0; top: 0; z-index: 40; display: flex; align-items: center; padding: 0 1.5rem; }
        .main-content { margin-left: 260px; padding: 84px 28px 20px 28px; min-height: 100vh; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 20px; }
        .card .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; position: static; font-weight: 600; font-size: 16px; display: flex; justify-content: space-between; align-items: center; }
        .card .body { padding: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-item { padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .info-item .label { font-size: 12px; color: #6b7280; font-weight: 500; }
        .info-item .value { font-size: 14px; color: #0f172a; font-weight: 500; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-discharged { background: #fee2e2; color: #991b1b; }
        .badge-transferred { background: #fef3c7; color: #92400e; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { padding: 10px 24px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-success:hover { background: #16a34a; }
        .btn-print { padding: 8px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .btn-print:hover { background: #4f46e5; }
        
        .timeline-day { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s;
        }
        .timeline-day:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .timeline-day .day-header { 
            font-weight: 700; 
            color: #0f172a; 
            border-bottom: 2px solid #3b82f6; 
            padding-bottom: 10px; 
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .day-date {
            font-weight: 400;
            color: #6b7280;
            font-size: 14px;
        }
        .timeline-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 16px; 
            margin-bottom: 12px;
        }
        .timeline-grid .notes-box {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .timeline-grid .notes-box .title {
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            margin-bottom: 4px;
        }
        .timeline-grid .notes-box .content {
            font-size: 14px;
            color: #0f172a;
            line-height: 1.6;
        }
        
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-top: 8px;
        }
        .vital-item {
            background: #f1f5f9;
            padding: 10px 14px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .vital-item .vital-value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .vital-item .vital-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }
        
        .medicine-table {
            width: 100%;
            font-size: 14px;
            border-collapse: collapse;
        }
        .medicine-table thead {
            background: #f1f5f9;
        }
        .medicine-table th {
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        .medicine-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .medicine-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .sub-section-title {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
            margin: 12px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sub-section-title i {
            color: #3b82f6;
        }
        .no-data {
            color: #94a3b8;
            font-style: italic;
            padding: 8px 0;
        }
        
        @media (max-width: 1024px) { 
            .main-content { margin-left: 0; padding: 16px; } 
            .header { left: 0; } 
        }
        @media (max-width: 768px) { 
            .info-grid, .timeline-grid { grid-template-columns: 1fr; }
            .vitals-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 480px) {
            .vitals-grid { grid-template-columns: repeat(2, 1fr); }
            .medicine-table { font-size: 12px; }
            .medicine-table th, .medicine-table td { padding: 6px 8px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar"><?php include 'Sidebar.php'; ?></div>

    <!-- Header -->
    <header class="header"><div class="flex items-center justify-between w-full"><?php include 'header.php'; ?></div></header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header with Back Button -->
        <div class="mb-6 flex items-center gap-4 flex-wrap">
            <a href="ipd_treatment_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm" style="padding:1%">
                <i class="fas fa-arrow-left text-gray-600"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Treatment Details</h1>
                <p class="text-gray-500">Treatment #<?php echo $treatment['treatment_master_id']; ?></p>
            </div>
            <div class="ml-auto flex gap-2 flex-wrap">
                <a href="ipd_treatment_daily_add.php?master_id=<?php echo $id; ?>" class="btn-success">
                    <i class="fas fa-plus mr-2"></i> Add Today's Treatment
                </a>
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>
        </div>

        <!-- Patient & Doctor Details -->
        <div class="card">
            <div class="header">Patient & Doctor Details</div>
            <div class="body">
                <div class="info-grid">
                    <div class="info-item"><div class="label">Patient Name</div><div class="value"><?php echo htmlspecialchars($treatment['patient_name'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Patient ID</div><div class="value"><?php echo htmlspecialchars($treatment['patient_code'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Age</div><div class="value"><?php echo htmlspecialchars($treatment['age'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Gender</div><div class="value"><?php echo htmlspecialchars($treatment['gender'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Mobile</div><div class="value"><?php echo htmlspecialchars($treatment['mobile'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Email</div><div class="value"><?php echo htmlspecialchars($treatment['email'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Address</div><div class="value"><?php echo htmlspecialchars($treatment['address'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Doctor</div><div class="value"><?php echo htmlspecialchars($treatment['doctor_name'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Specialization</div><div class="value"><?php echo htmlspecialchars($treatment['specialization'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Diagnosis</div><div class="value"><?php echo nl2br(htmlspecialchars($treatment['diagnosis'] ?? 'Not specified')); ?></div></div>
                </div>
            </div>
        </div>

        <!-- Admission Details -->
        <div class="card">
            <div class="header">Admission Details</div>
            <div class="body">
                <div class="info-grid">
                    <div class="info-item"><div class="label">Admission No</div><div class="value"><?php echo htmlspecialchars($treatment['admission_no'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Admission Date</div><div class="value"><?php echo date('d-m-Y', strtotime($treatment['admission_date'] ?? date('Y-m-d'))); ?></div></div>
                    <div class="info-item"><div class="label">Ward</div><div class="value"><?php echo htmlspecialchars($treatment['ward_name'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Room</div><div class="value"><?php echo htmlspecialchars($treatment['room_no'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Bed</div><div class="value"><?php echo htmlspecialchars($treatment['bed_no'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="label">Treatment Status</div><div class="value"><span class="badge badge-<?php echo strtolower($treatment['status']); ?>"><?php echo $treatment['status']; ?></span></div></div>
                </div>
            </div>
        </div>

        <!-- Daily Treatment Timeline -->
        <div class="card">
            <div class="header">
                <span>Daily Treatment Timeline</span>
                <span class="text-sm text-gray-500">Total Days: <?php echo count($daily_treatments); ?></span>
            </div>
            <div class="body">
                <?php if (count($daily_treatments) > 0): ?>
                    <?php foreach ($daily_treatments as $day): ?>
                        <div class="timeline-day">
                            <div class="day-header">
                                <span>
                                    Day <?php echo $day['day_number']; ?>
                                    <span class="day-date">| <?php echo date('d-m-Y', strtotime($day['treatment_date'])); ?></span>
                                </span>
                            </div>
                            
                            <div class="timeline-grid">
                                <div class="notes-box">
                                    <div class="title"><i class="fas fa-user-md mr-1"></i> Doctor Notes</div>
                                    <div class="content"><?php echo nl2br(htmlspecialchars($day['daily_doctor_notes'] ?? 'Not specified')); ?></div>
                                </div>
                                <div class="notes-box">
                                    <div class="title"><i class="fas fa-user-nurse mr-1"></i> Nursing Notes</div>
                                    <div class="content"><?php echo nl2br(htmlspecialchars($day['nursing_notes'] ?? 'Not specified')); ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($day['remarks'])): ?>
                                <div class="mt-2 mb-3">
                                    <div class="text-sm font-medium text-gray-600"><i class="fas fa-comment mr-1"></i> Remarks</div>
                                    <div class="text-sm bg-gray-50 p-3 rounded border border-gray-200"><?php echo nl2br(htmlspecialchars($day['remarks'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- ========== VITALS SECTION ========== -->
                            <div class="mt-4">
                                <div class="sub-section-title">
                                    <i class="fas fa-heartbeat"></i> Vitals
                                </div>
                                <?php if (count($day['vitals']) > 0): ?>
                                    <?php foreach ($day['vitals'] as $vital): ?>
                                        <div class="vitals-grid">
                                            <div class="vital-item">
                                                <div class="vital-value"><?php echo $vital['temperature'] ?? '-'; ?>°C</div>
                                                <div class="vital-label">Temperature</div>
                                            </div>
                                            <div class="vital-item">
                                                <div class="vital-value"><?php echo $vital['pulse'] ?? '-'; ?></div>
                                                <div class="vital-label">Pulse (bpm)</div>
                                            </div>
                                            <div class="vital-item">
                                                <div class="vital-value"><?php echo $vital['blood_pressure'] ?? '-'; ?></div>
                                                <div class="vital-label">Blood Pressure</div>
                                            </div>
                                            <div class="vital-item">
                                                <div class="vital-value"><?php echo $vital['respiratory_rate'] ?? '-'; ?></div>
                                                <div class="vital-label">Respiratory Rate</div>
                                            </div>
                                            <div class="vital-item">
                                                <div class="vital-value"><?php echo $vital['spo2'] ?? '-'; ?>%</div>
                                                <div class="vital-label">SpO₂</div>
                                            </div>
                                            <div class="vital-item">
                                                <div class="vital-value"><?php echo $vital['weight'] ?? '-'; ?> kg</div>
                                                <div class="vital-label">Weight</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data">No Vitals Recorded for this day.</div>
                                <?php endif; ?>
                            </div>

                            <!-- ========== MEDICINE CHART SECTION ========== -->
                            <div class="mt-4">
                                <div class="sub-section-title">
                                    <i class="fas fa-pills"></i> Medicine Chart
                                </div>
                                <?php if (count($day['medicines']) > 0): ?>
                                    <div class="overflow-x-auto">
                                        <table class="medicine-table">
                                            <thead>
                                                <tr>
                                                    <th>Medicine Name</th>
                                                    <th>Dosage</th>
                                                    <th>Frequency</th>
                                                    <th>Days</th>
                                                    <th>Route</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($day['medicines'] as $med): ?>
                                                    <tr>
                                                        <td class="font-medium"><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($med['dosage'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($med['frequency'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($med['number_of_days'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($med['route'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($med['remarks'] ?? '-'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">No Medicines Prescribed for this day.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No daily treatments added yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>