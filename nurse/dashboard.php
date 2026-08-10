<?php

session_start();

require_once '../config/permission.php';
require_once '../config/hospital.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/logout.php");
    exit();
}
$nurse_id=$_SESSION['id'];

$nurseQuery = "SELECT * FROM staff 
               WHERE register_id = '$nurse_id'
               AND role = 'Nurse'
               AND delete_flag = 0
               LIMIT 1";

/* ============================================================
   ADD SELECTED TASKS
   ============================================================ */

if (isset($_POST['add_nursing_tasks'])) {

    $task_ids = $_POST['task_ids'] ?? [];

    foreach ($task_ids as $task_id) {

        $task_id = (int)$task_id;

        // Check whether task is already added today
        $checkSql = "
            SELECT daily_task_id
            FROM nurse_daily_tasks
            WHERE task_id = '$task_id'
            AND nurse_id = '$nurse_id'
            AND task_date = '$today'
            AND (delete_flag = 0 OR delete_flag IS NULL)
        ";

        $checkResult = $conn->query($checkSql);

        if ($checkResult && $checkResult->num_rows == 0) {

            $insertSql = "
                INSERT INTO nurse_daily_tasks
                (
                    task_id,
                    nurse_id,
                    task_date,
                    status,
                    delete_flag
                )
                VALUES
                (
                    '$task_id',
                    '$nurse_id',
                    '$today',
                    'Pending',
                    0
                )
            ";

            $conn->query($insertSql);
        }
    }
}

$nurseResult = mysqli_query($conn, $nurseQuery);

if ($nurseResult && mysqli_num_rows($nurseResult) > 0) {
    $nurse = mysqli_fetch_assoc($nurseResult);

    // Store complete nurse/staff information in session
    $_SESSION['nurse'] = $nurse;

    // Optional: store individual values
    $_SESSION['nurse_id'] = $nurse['staff_id'];
    $_SESSION['nurse_name'] = $nurse['name'];
    $_SESSION['nurse_email'] = $nurse['email'];
    $_SESSION['nurse_mobile'] = $nurse['mobile'];
    $_SESSION['nurse_role'] = $nurse['role'];
    $_SESSION['nurse_hospital_id'] = $nurse['hospital_id'];
} else {
    die("Nurse information not found.");
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

// ============================================
// 1. SUMMARY STATISTICS QUERIES
// ============================================

// 1.2 Today's OPD Patients
$todayOPDQuery = "
    SELECT COUNT(DISTINCT a.patient_id) as total
    FROM appointments a
    WHERE a.hospital_id = '$hospital_id'
    AND a.appointment_date = '$today'
    AND a.opd_ipd_type = 'OPD'
    AND a.status IN ('Scheduled', 'Confirmed')
    AND a.delete_flag = 0
";

$todayOPDResult = $conn->query($todayOPDQuery);
$todayOPDPatients = $todayOPDResult->num_rows > 0 ? $todayOPDResult->fetch_assoc()['total'] : 0;

// 1.3 IPD Patients Count
$ipdPatientsQuery = "
    SELECT COUNT(DISTINCT p.patient_id) as total
    FROM ipd_admissions ia
    INNER JOIN patients p ON ia.patient_id = p.patient_id
    WHERE ia.hospital_id = '$hospital_id'
    AND ia.status = 'Admitted'
    AND ia.delete_flag = 0
    AND p.delete_flag = 0
";

$ipdResult = $conn->query($ipdPatientsQuery);
$ipdPatients = $ipdResult->num_rows > 0 ? $ipdResult->fetch_assoc()['total'] : 0;

// ============================================
// 2. OPD PATIENTS QUERY
// ============================================
$opdPatientsQuery = "
    SELECT 
        p.patient_id,
        p.patient_name,
        p.gender,
        p.age,
        p.mobile,
        p.blood_group,
        p.patient_admission_type,
        d.doctor_name,
        a.appointment_id,
        a.appointment_no,
        a.appointment_date,
        a.appointment_time,
        a.status as appointment_status,
        a.opd_ipd_type,
        a.reason,
        DATE_FORMAT(a.appointment_date, '%d-%m-%Y') as formatted_date,
        TIME_FORMAT(a.appointment_time, '%h:%i %p') as formatted_time
    FROM patients p
    INNER JOIN appointments a ON p.patient_id = a.patient_id AND a.delete_flag = 0
    LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
    WHERE p.hospital_id = '$hospital_id'
    AND p.status = 'Active'
    AND p.delete_flag = 0
    AND a.opd_ipd_type = 'OPD'
    AND a.status IN ('Scheduled', 'Confirmed')
    ORDER BY a.appointment_date DESC, a.appointment_time ASC
    LIMIT 15
";

$opdPatientsResult = $conn->query($opdPatientsQuery);

// ============================================
// 3. IPD PATIENTS QUERY
// ============================================
$ipdPatientsQuery = "
    SELECT 
        p.patient_id,
        p.patient_name,
        p.gender,
        p.age,
        p.mobile,
        p.blood_group,
        d.doctor_name,
        ia.id as admission_id,
        ia.admission_no,
        ia.admission_date,
        ia.department,
        ia.ward_id,
        ia.room_no,
        ia.bed_no,
        ia.status as admission_status,
        ia.disease_reason,
        DATE_FORMAT(ia.admission_date, '%d-%m-%Y') as formatted_date,
        wm.ward_name,
        rm.room_no as room_number
    FROM patients p
    INNER JOIN ipd_admissions ia ON p.patient_id = ia.patient_id AND ia.delete_flag = 0
    LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
    LEFT JOIN ward_master wm ON ia.ward_id = wm.ward_id
    LEFT JOIN room_master rm ON ia.room_no = rm.room_no
    WHERE p.hospital_id = '$hospital_id'
    AND p.status = 'Active'
    AND p.delete_flag = 0
    AND ia.status = 'Admitted'
    ORDER BY ia.admission_date DESC
    LIMIT 15
";

$ipdPatientsResult = $conn->query($ipdPatientsQuery);

// ============================================
// 4. MEDICATION SCHEDULE QUERY
// ============================================
$medicationScheduleQuery = "
 SELECT
    p.patient_id,
    p.patient_name,
    COUNT(DISTINCT pm.prescription_id) AS total_prescriptions,
    COUNT(pd.detail_id) AS total_medicines,
    MAX(pm.created_at) AS last_prescribed
FROM prescription_master pm
JOIN prescription_details pd
    ON pm.prescription_id = pd.prescription_id
JOIN patients p
    ON pm.patient_id = p.patient_id
JOIN ipd_admissions ia
    ON pm.patient_id = ia.patient_id
WHERE pm.hospital_id = '$hospital_id'
    AND pm.delete_flag = 0
    AND ia.status = 'Admitted'
GROUP BY p.patient_id, p.patient_name
ORDER BY last_prescribed DESC";

$medicationScheduleResult = $conn->query($medicationScheduleQuery);

if (!$medicationScheduleResult) {
    die("SQL Error: " . mysqli_error($conn));
}

// ============================================
// 5. PATIENTS REQUIRING VITALS QUERY
// ============================================
$vitalsPatientsQuery = "
  SELECT
    pv.vital_id,
    p.patient_id,
    p.patient_name,
    p.gender,
    p.age,
    d.doctor_name,
    pv.bp,
    pv.pulse,
    pv.temperature,
    pv.spo2,
    pv.recorded_at
FROM patient_vitals pv
JOIN patients p
    ON pv.patient_id = p.patient_id
LEFT JOIN doctor d
    ON p.doctor_id = d.doctor_id
WHERE
    pv.hospital_id = '$hospital_id'
    AND pv.delete_flag = 0
ORDER BY pv.recorded_at DESC
LIMIT 10";

$vitalsPatientsResult = $conn->query($vitalsPatientsQuery);

// ============================================
// 6. DOCTOR INSTRUCTIONS QUERY
// ============================================
$doctorInstructionsQuery = "

SELECT
    di.instruction_id,
    p.patient_name,
    di.instruction,
    di.priority,
    di.status,
    d.doctor_name,
    DATE_FORMAT(di.created_at,'%d-%m-%Y %H:%i') AS instruction_date
FROM doctor_instructions di
JOIN patients p
    ON di.patient_id = p.patient_id
JOIN doctor d
    ON di.doctor_id = d.doctor_id
WHERE di.hospital_id = '$hospital_id'
AND di.delete_flag = 0
ORDER BY
    FIELD(di.priority,'Urgent','High','Normal','Low'),
    di.created_at DESC
LIMIT 5
";

$doctorInstructionsResult = $conn->query($doctorInstructionsQuery);

// ============================================
// 7. RECENT NURSING NOTES QUERY
// ============================================
$nursingNotesQuery = "
SELECT
    nn.note_id,
    p.patient_name,
    r.name AS nurse_name,
    nn.note,
    DATE_FORMAT(nn.recorded_at,'%d-%m-%Y %H:%i') AS note_date
FROM nursing_notes nn
JOIN patients p
    ON nn.patient_id = p.patient_id
JOIN register r
    ON nn.nurse_id = r.id
WHERE nn.hospital_id = '$hospital_id'
AND nn.delete_flag = 0
ORDER BY nn.recorded_at DESC
LIMIT 10
";
$nursingNotesResult = $conn->query($nursingNotesQuery);

$nursingNotesResult = mysqli_query($conn, $nursingNotesQuery);

if (!$nursingNotesResult) {
    die("SQL Error: " . mysqli_error($conn));
}

// ============================================
// 8. CHART DATA QUERIES
// ============================================

// 8.1 Patient Status Chart Data
$patientStatusQuery = "
    SELECT
    CASE
        WHEN status = 'Scheduled' THEN 'Scheduled'
        WHEN status = 'Confirmed' THEN 'Confirmed'
        WHEN status = 'Completed' THEN 'Completed'
        WHEN status = 'Cancelled' THEN 'Cancelled'
        ELSE 'Other'
    END AS status_label,
    COUNT(*) AS count
FROM appointments
WHERE hospital_id = '$hospital_id'
AND delete_flag = 0
GROUP BY status_label;
";

$patientStatusResult = $conn->query($patientStatusQuery);
$patientStatusData = [];
while ($row = $patientStatusResult->fetch_assoc()) {
    $patientStatusData[] = $row;
}

// 8.2 Medicine Status Chart Data
$medicineStatusData = [
    ['status' => 'Pending', 'count' => 12],
    ['status' => 'Given', 'count' => 45],
    ['status' => 'Missed', 'count' => 8]
];

// Greeting logic
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
    $greeting_icon = "🌅";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon";
    $greeting_icon = "☀️";
} elseif ($hour < 20) {
    $greeting = "Good Evening";
    $greeting_icon = "🌆";
} else {
    $greeting = "Good Night";
    $greeting_icon = "🌙";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?? 'Ultra Hospital'; ?> - Nurse Dashboard</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? 'favicon.ico'; ?>">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ============================================
           RESET & BASE STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            color: #1a202c;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: #2b6cb5;
        }
        
        a:hover {
            text-decoration: none;
            color: #1a4f8b;
        }
        
        /* ============================================
           BOOTSTRAP REPLACEMENT UTILITIES
           ============================================ */
        .d-flex { display: flex; }
        .d-block { display: block; }
        .d-none { display: none; }
        .flex-wrap { flex-wrap: wrap; }
        .flex-column { flex-direction: column; }
        .align-items-center { align-items: center; }
        .align-items-start { align-items: flex-start; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .text-center { text-align: center; }
        .text-muted { color: #718096; }
        .text-primary { color: #2b6cb5; }
        .text-success { color: #38a169; }
        .text-danger { color: #e53e3e; }
        .text-warning { color: #ed8936; }
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .fw-bold { font-weight: 700; }
        .fw-semibold { font-weight: 600; }
        .fw-medium { font-weight: 500; }
        .fw-normal { font-weight: 400; }
        
        .gap-1 { gap: 4px; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        
        .mx-2 { margin-left: 8px; margin-right: 8px; }
        .mx-3 { margin-left: 12px; margin-right: 12px; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        .my-3 { margin-top: 12px; margin-bottom: 12px; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 24px; }
        .me-1 { margin-right: 4px; }
        .me-2 { margin-right: 8px; }
        .me-3 { margin-right: 12px; }
        .ms-1 { margin-left: 4px; }
        .ms-2 { margin-left: 8px; }
        .ms-3 { margin-left: 12px; }
        
        .p-2 { padding: 8px; }
        .p-3 { padding: 12px; }
        .p-4 { padding: 16px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .py-2 { padding-top: 8px; padding-bottom: 8px; }
        .py-3 { padding-top: 12px; padding-bottom: 12px; }
        .px-2 { padding-left: 8px; padding-right: 8px; }
        .px-3 { padding-left: 12px; padding-right: 12px; }
        
        .w-100 { width: 100%; }
        .h-100 { height: 100%; }
        .flex-shrink-0 { flex-shrink: 0; }
        .flex-grow-1 { flex-grow: 1; }
        
        /* Grid System - Simplified for full width */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -12px;
            margin-right: -12px;
        }
        
        .row.g-4 { margin-left: -16px; margin-right: -16px; }
        .row.g-4 > [class*="col-"] { padding-left: 16px; padding-right: 16px; }
        
        .col-xl-3 { flex: 0 0 25%; max-width: 25%; padding: 0 12px; }
        .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 12px; }
        .col-xl-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-xl-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        .col-lg-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-lg-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        
        /* Full width container */
        .full-width {
            width: 100%;
        }
        
        /* ============================================
           GRADIENT GREETING CARD
           ============================================ */
        .greeting-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            margin-bottom: 28px;
            box-shadow: 0 20px 40px -12px rgba(102, 126, 234, 0.4);
            width: 100%;
        }
        .greeting-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(25deg);
        }
        .greeting-gradient::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 40%;
            height: 150%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
            transform: rotate(-15deg);
        }
        .greeting-content {
            position: relative;
            z-index: 1;
        }
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 0;
        }
        .floating-shapes span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }
        .floating-shapes span:nth-child(1) { top: 20%; left: 10%; width: 30px; height: 30px; animation-delay: 0s; }
        .floating-shapes span:nth-child(2) { top: 60%; right: 15%; width: 40px; height: 40px; animation-delay: 5s; }
        .floating-shapes span:nth-child(3) { bottom: 30%; left: 20%; width: 25px; height: 25px; animation-delay: 10s; }
        .floating-shapes span:nth-child(4) { top: 40%; right: 30%; width: 35px; height: 35px; animation-delay: 15s; }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg) scale(1); }
            50% { transform: translateY(-30px) rotate(180deg) scale(1.2); }
            100% { transform: translateY(0) rotate(360deg) scale(1); }
        }

        /* ============================================
           STATISTICS CARDS
           ============================================ */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 22px 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.15);
            border-color: transparent;
        }
        
        .stat-card .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }
        
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #48bb78, #38a169); }
        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f6ad55, #ed8936); }
        .stat-card.red .stat-icon { background: linear-gradient(135deg, #fc8181, #e53e3e); }
        .stat-card.purple .stat-icon { background: linear-gradient(135deg, #b794f4, #805ad5); }
        
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #1a202c;
            line-height: 1.2;
            background: linear-gradient(135deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            font-weight: 500;
            color: #718096;
            margin-top: 4px;
        }
        
        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-card .stat-change.up { color: #38a169; }
        .stat-card .stat-change.down { color: #e53e3e; }
        
        /* ============================================
           SECTION CARDS
           ============================================ */
        .section-card {
            background: white;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            margin-bottom: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }
        
        .section-card:hover {
            box-shadow: 0 12px 30px -8px rgba(0,0,0,0.08);
        }
        
        .section-card .card-header-custom {
            background: white;
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .section-card .card-header-custom h5 {
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .section-card .card-header-custom h5 i {
            color: #667eea;
        }
        
        .section-card .card-header-custom .badge-count {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .section-card .card-body-custom {
            padding: 20px 24px;
        }
        
        /* ============================================
           TABLE STYLES
           ============================================ */
        .table-nurse {
            font-size: 13px;
            margin-bottom: 0;
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-nurse thead th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 14px;
            white-space: nowrap;
            text-align: left;
        }
        
        .table-nurse tbody td {
            padding: 12px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .table-nurse tbody tr {
            transition: background 0.15s ease;
            cursor: pointer;
        }
        
        .table-nurse tbody tr:hover {
            background: #f8fafc;
        }
        
        .table-nurse tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        
        /* ============================================
           BADGES
           ============================================ */
        .badge-status {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            display: inline-block;
        }
        
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.given { background: #d1fae5; color: #065f46; }
        .badge-status.missed { background: #fee2e2; color: #991b1b; }
        .badge-status.stable { background: #d1fae5; color: #065f46; }
        .badge-status.critical { background: #fee2e2; color: #991b1b; }
        .badge-status.observation { background: #fef3c7; color: #92400e; }
        .badge-status.discharged { background: #f1f5f9; color: #475569; }
        .badge-status.admitted { background: #dbeafe; color: #1e40af; }
        .badge-status.scheduled { background: #fef3c7; color: #92400e; }
        .badge-status.confirmed { background: #d1fae5; color: #065f46; }
        .badge-status.completed { background: #f1f5f9; color: #475569; }
        
        /* ============================================
           BUTTON STYLES
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 6px;
            font-family: inherit;
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px -4px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -6px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .btn-outline-primary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -6px rgba(56, 161, 105, 0.4);
            color: white;
        }
        
        .btn-outline-success {
            background: transparent;
            color: #38a169;
            border: 2px solid #38a169;
        }
        .btn-outline-success:hover {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-outline-danger {
            background: transparent;
            color: #e53e3e;
            border: 2px solid #e53e3e;
        }
        .btn-outline-danger:hover {
            background: linear-gradient(135deg, #fc8181, #e53e3e);
            color: white;
            transform: translateY(-2px);
        }
        
        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .btn-action {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-family: inherit;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .btn-action-success {
            background: #d1fae5;
            color: #065f46;
            border: none;
        }
        .btn-action-success:hover {
            background: #a7f3d0;
            color: #064e3b;
        }
        
        .btn-action-primary {
            background: #dbeafe;
            color: #1e40af;
            border: none;
        }
        .btn-action-primary:hover {
            background: #93c5fd;
            color: #1e3a8a;
        }
        
        .btn-action-warning {
            background: #fef3c7;
            color: #92400e;
            border: none;
        }
        .btn-action-warning:hover {
            background: #fde68a;
            color: #78350f;
        }
        
        .btn-action-danger {
            background: #fee2e2;
            color: #991b1b;
            border: none;
        }
        .btn-action-danger:hover {
            background: #fca5a5;
            color: #7f1d1d;
        }
        
        /* ============================================
           CHART CONTAINERS
           ============================================ */
        .chart-container {
            position: relative;
            height: 220px;
            width: 100%;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chart-card:hover {
            box-shadow: 0 8px 25px -8px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .chart-card h6 {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-card h6 i {
            color: #667eea;
        }
        
        /* ============================================
           NO DATA STATE
           ============================================ */
        .no-data {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
            color: #cbd5e1;
        }
        
        .no-data p {
            font-size: 16px;
            font-weight: 500;
            color: #64748b;
        }
        
        .no-data small {
            font-size: 13px;
            display: block;
            margin-top: 4px;
            color: #94a3b8;
        }
        
        /* ============================================
           PATIENT TYPE BADGE
           ============================================ */
        .patient-type-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .patient-type-badge.opd {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .patient-type-badge.ipd {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* ============================================
           DATE TIME DISPLAY
           ============================================ */
        .datetime-display {
            background: white;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            font-size: 14px;
            color: #4a5568;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        
        .datetime-display i {
            color: #667eea;
        }
        
        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .main-content {
            margin-left: 260px;
            padding: 24px 32px;
            min-height: 100vh;
            background: #f0f4f8;
            transition: margin-left 0.3s ease;
            width: calc(100% - 260px);
        }
        
        /* ============================================
           BREADCRUMB
           ============================================ */
        .page-header .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 8px 0 0 0;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .page-header .breadcrumb-item {
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }
        
        .page-header .breadcrumb-item + .breadcrumb-item::before {
            content: '/';
            margin: 0 6px;
            color: #94a3b8;
        }
        
        .page-header .breadcrumb-item a {
            color: #64748b;
            text-decoration: none;
        }
        
        .page-header .breadcrumb-item a:hover {
            color: #667eea;
        }
        
        .page-header .breadcrumb-item.active {
            color: #667eea;
            font-weight: 600;
        }
        
        /* ============================================
           RESPONSIVE STYLES
           ============================================ */
        
        /* Large screens (1200px and above) */
        @media (min-width: 1200px) {
            .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
            .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
            .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
        }
        
        /* Medium-Large screens (992px to 1199px) */
        @media (max-width: 1199px) and (min-width: 992px) {
            .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
            .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
            .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
            
            .main-content {
                padding: 20px 24px;
            }
        }
        
        /* Tablet screens (768px to 991px) */
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
                width: 100%;
            }
            
            .col-lg-6 { 
                flex: 0 0 100%; 
                max-width: 100%; 
            }
            .col-lg-12 { 
                flex: 0 0 100%; 
                max-width: 100%; 
            }
            
            /* Make all sections full width on tablet */
            .col-xl-3, .col-xl-4, .col-xl-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .row.g-4 {
                margin-left: -8px;
                margin-right: -8px;
            }
            
            .row.g-4 > [class*="col-"] {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            /* Stack charts vertically */
            .chart-container {
                height: 200px;
            }
            
            .section-card .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .section-card .card-body-custom {
                padding: 16px 18px;
                overflow-x: auto;
            }
            
            /* Make tables scrollable on tablet */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Stack header items */
            .greeting-content .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .greeting-content .d-flex .d-flex {
                flex-wrap: wrap;
                margin-top: 12px;
            }
        }
        
        /* Mobile screens (up to 767px) */
        @media (max-width: 767px) {
            .main-content {
                padding: 12px 14px;
                width: 100%;
            }
            
            /* Stack all columns vertically */
            .col-xl-3, .col-xl-4, .col-xl-6, 
            .col-lg-6, .col-lg-12,
            .col-md-6, .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .row.g-4 {
                margin-left: -6px;
                margin-right: -6px;
            }
            
            .row.g-4 > [class*="col-"] {
                padding-left: 6px;
                padding-right: 6px;
            }
            
            .stat-card {
                padding: 16px 18px;
            }
            
            .stat-card .stat-number {
                font-size: 24px;
            }
            
            .stat-card .stat-icon {
                width: 44px;
                height: 44px;
                font-size: 20px;
            }
            
            .greeting-gradient {
                border-radius: 12px;
                margin-bottom: 16px;
            }
            
            .greeting-content {
                padding: 16px 20px !important;
            }
            
            .greeting-content h1 {
                font-size: 22px !important;
            }
            
            .greeting-content .greeting_icon {
                font-size: 2rem !important;
            }
            
            .section-card {
                margin-bottom: 16px;
            }
            
            .section-card .card-header-custom {
                padding: 14px 16px;
            }
            
            .section-card .card-header-custom h5 {
                font-size: 14px;
                flex-wrap: wrap;
            }
            
            .section-card .card-body-custom {
                padding: 12px 14px;
                overflow-x: auto;
            }
            
            .table-nurse {
                font-size: 11px;
            }
            
            .table-nurse thead th,
            .table-nurse tbody td {
                padding: 6px 8px;
            }
            
            .badge-status {
                padding: 3px 10px;
                font-size: 10px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 11px;
            }
            
            .btn-action {
                padding: 4px 8px;
                font-size: 11px;
            }
            
            .chart-container {
                height: 180px;
            }
            
            .chart-card {
                padding: 14px;
            }
            
            .chart-card h6 {
                font-size: 13px;
                margin-bottom: 12px;
            }
            
            .no-data {
                padding: 30px 15px;
            }
            
            .no-data i {
                font-size: 36px;
            }
            
            .no-data p {
                font-size: 14px;
            }
            
            .no-data small {
                font-size: 12px;
            }
            
            .datetime-display {
                margin-top: 12px;
                width: 100%;
                justify-content: center;
                font-size: 12px;
                padding: 8px 14px;
            }
            
            .page-header .d-flex {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .floating-shapes span:nth-child(2),
            .floating-shapes span:nth-child(4) {
                display: none;
            }
        }
        
        /* Extra small screens (up to 480px) */
        @media (max-width: 480px) {
            .main-content {
                padding: 8px 10px;
                width: 100%;
            }
            
            .stat-card {
                padding: 14px 16px;
            }
            
            .stat-card .stat-number {
                font-size: 20px;
            }
            
            .stat-card .stat-icon {
                width: 38px;
                height: 38px;
                font-size: 16px;
                margin-bottom: 8px;
            }
            
            .stat-card .stat-label {
                font-size: 12px;
            }
            
            .stat-card .stat-change {
                font-size: 10px;
            }
            
            .greeting-content h1 {
                font-size: 18px !important;
            }
            
            .greeting-content p {
                font-size: 14px !important;
            }
            
            .section-card .card-header-custom {
                padding: 10px 12px;
            }
            
            .section-card .card-body-custom {
                padding: 10px 12px;
            }
            
            .table-nurse {
                font-size: 10px;
            }
            
            .table-nurse thead th,
            .table-nurse tbody td {
                padding: 4px 6px;
            }
            
            .table-nurse thead th {
                font-size: 9px;
            }
            
            .btn {
                padding: 4px 10px;
                font-size: 11px;
                border-radius: 8px;
            }
            
            .btn-sm {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .btn-action {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .chart-container {
                height: 160px;
            }
            
            .chart-card {
                padding: 10px;
            }
            
            .patient-type-badge {
                font-size: 8px;
                padding: 2px 8px;
            }
        }
        
        /* ============================================
           SCROLLBAR
           ============================================ */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f0f4f8;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 8px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* ============================================
           UTILITY HELPERS
           ============================================ */
        .shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .shadow-md { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        
        .border-0 { border: none; }
        .rounded { border-radius: 8px; }
        .rounded-lg { border-radius: 12px; }
        .rounded-xl { border-radius: 16px; }
        
        /* ============================================
           PRINT STYLES
           ============================================ */
        @media print {
            .main-content {
                margin-left: 0;
                padding: 20px;
                width: 100%;
            }
            
            .stat-card,
            .section-card,
            .chart-card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
                break-inside: avoid;
            }
            
            .stat-card:hover,
            .section-card:hover {
                transform: none;
                box-shadow: none;
            }
            
            .btn,
            .btn-action {
                display: none !important;
            }
            
            .greeting-gradient {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .floating-shapes {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../header.php'; ?>
    
    <div class="d-flex">
        <!-- Include Sidebar -->
        <?php include '../sidebar.php'; ?>
        
        <!-- ============================================
        MAIN CONTENT
        ============================================ -->
        <main class="main-content">
            
            <!-- ============================================
            GRADIENT GREETING HEADER
            ============================================ -->
            <div class="greeting-gradient">
                <div class="floating-shapes">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="greeting-content" style="padding: 24px 32px;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center" style="position: relative; z-index: 1;">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-1">
                                <span style="font-size: 2.5rem;"><?php echo $greeting_icon; ?></span>
                                <div>
                                    <h1 style="font-size: 28px; font-weight: 800; color: white; margin: 0; text-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                        <?php echo $greeting . "! 👋"; ?>
                                    </h1>
                                    <p style="color: rgba(255,255,255,0.9); font-size: 18px; font-weight: 500; margin: 4px 0 0 0;">
                                        <?php echo $_SESSION['name'] ?? 'Nurse'; ?>
                                    </p>
                                </div>
                            </div>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 4px 0 0 0;">
                                <i class="fas fa-calendar-alt" style="margin-right: 6px;"></i>
                                <?php echo date('l, F j, Y'); ?>
                                <span style="margin: 0 8px; opacity: 0.4;">|</span>
                                <i class="fas fa-clock" style="margin-right: 6px;"></i>
                                <?php echo date('h:i A'); ?>
                            </p>
                        </div>
                        <div class="d-flex gap-3" style="flex-wrap: wrap;">
                            <button class="btn" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);" onclick="window.location.href='profile.php'">
                                <i class="fas fa-user"></i> My Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 1: SUMMARY STATISTICS (4 columns on large screens)
            ============================================ -->
            <div class="row g-4 mb-4">
                <!-- Card 1: Today's OPD Patients -->
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card green">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $todayOPDPatients; ?></div>
                        <div class="stat-label">Today's OPD Patients</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 8% from yesterday
                        </div>
                    </div>
                </div>
                
                <!-- Card 2: IPD Patients -->
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card purple">
                        <div class="stat-icon">
                            <i class="fas fa-hospital-user"></i>
                        </div>
                        <div class="stat-number"><?php echo $ipdPatients; ?></div>
                        <div class="stat-label">IPD Patients</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 5% from last week
                        </div>
                    </div>
                </div>
                
                <!-- Card 3: Total Appointments -->
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card blue">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number"><?php 
                            $totalAppointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE hospital_id='$hospital_id' AND delete_flag=0")->fetch_assoc()['total'];
                            echo $totalAppointments; 
                        ?></div>
                        <div class="stat-label">Total Appointments</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 12% from last month
                        </div>
                    </div>
                </div>
                
                <!-- Card 4: Active IPD Patients -->
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card orange">
                        <div class="stat-icon">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <div class="stat-number"><?php echo $ipdPatients; ?></div>
                        <div class="stat-label">Active IPD Patients</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 3% from last week
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 2: OPD PATIENTS TABLE (Full Width)
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-user-clock"></i>
                        OPD Patients
                        <span class="badge-count"><?php echo $opdPatientsResult->num_rows; ?> Today</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="patient-type-badge opd"><i class="fas fa-circle" style="font-size: 6px; margin-right: 4px;"></i> OPD</span>
                    </div>
                </div>
                <div class="card-body-custom">
                    <?php if ($opdPatientsResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table-nurse">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Appointment</th>
                                        <th>Doctor</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $opdPatientsResult->fetch_assoc()): ?>
                                        <tr onclick="window.location.href='view_patient.php?id=<?php echo $row['patient_id'] ?>'">
                                            <td><span class="fw-bold">#<?php echo str_pad($row['patient_id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                <div class="text-muted small"><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($row['gender'] == 'Male'): ?>
                                                    <span class="badge-status" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-mars"></i> Male</span>
                                                <?php elseif ($row['gender'] == 'Female'): ?>
                                                    <span class="badge-status" style="background: #fdf2f8; color: #d53f8c;"><i class="fas fa-venus"></i> Female</span>
                                                <?php else: ?>
                                                    <span class="badge-status" style="background: #f1f5f9; color: #475569;">Other</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['age'] ?? 'N/A'; ?></td>
                                            <td>
                                                <div><strong><?php echo $row['formatted_date']; ?></strong></div>
                                                <div class="text-muted small"><?php echo $row['formatted_time']; ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['doctor_name'] ?? 'Not Assigned'); ?></td>
                                            <td>
                                                <?php
                                                $status = $row['appointment_status'] ?? 'Scheduled';
                                                $badgeClass = '';
                                                if ($status == 'Completed') $badgeClass = 'completed';
                                                elseif ($status == 'Confirmed') $badgeClass = 'confirmed';
                                                elseif ($status == 'Scheduled') $badgeClass = 'scheduled';
                                                else $badgeClass = 'discharged';
                                                ?>
                                                <span class="badge-status <?php echo $badgeClass; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button 
                                                    type="button"
                                                    class="btn-action btn-action-success"
                                                    title="Take Vitals"
                                                    onclick="event.stopPropagation(); window.location.href='add_vitals.php?patient_id=<?php echo $row['patient_id']; ?>';">
                                                    <i class="fas fa-heartbeat"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-user-clock"></i>
                            <p>No OPD patients found</p>
                            <small>OPD patients will appear here once appointments are scheduled</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 3: IPD PATIENTS (Full Width)
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-hospital-user"></i>
                        IPD Patients
                        <span class="badge-count"><?php echo $ipdPatientsResult->num_rows; ?> Admitted</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="patient-type-badge ipd"><i class="fas fa-circle" style="font-size: 6px; margin-right: 4px;"></i> IPD</span>
                    </div>
                </div>
                <div class="card-body-custom">
                    <?php if ($ipdPatientsResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table-nurse">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Ward / Room</th>
                                        <th>Bed</th>
                                        <th>Doctor</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $ipdPatientsResult->fetch_assoc()): ?>
                                        <tr onclick="window.location.href='view_patient.php?id=<?php echo $row['patient_id'] ?>'">
                                            <td><span class="fw-bold">#<?php echo str_pad($row['patient_id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                <div class="text-muted small"><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($row['gender'] == 'Male'): ?>
                                                    <span class="badge-status" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-mars"></i> Male</span>
                                                <?php elseif ($row['gender'] == 'Female'): ?>
                                                    <span class="badge-status" style="background: #fdf2f8; color: #d53f8c;"><i class="fas fa-venus"></i> Female</span>
                                                <?php else: ?>
                                                    <span class="badge-status" style="background: #f1f5f9; color: #475569;">Other</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['age'] ?? 'N/A'; ?></td>
                                            <td>
                                                <div><strong><?php echo htmlspecialchars($row['ward_name'] ?? 'N/A'); ?></strong></div>
                                                <div class="text-muted small">Room: <?php echo htmlspecialchars($row['room_number'] ?? $row['room_no'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['bed_no'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['doctor_name'] ?? 'Not Assigned'); ?></td>
                                            <td>
                                                <?php
                                                $status = $row['admission_status'] ?? 'Admitted';
                                                $badgeClass = ($status == 'Admitted') ? 'admitted' : 'discharged';
                                                ?>
                                                <span class="badge-status <?php echo $badgeClass; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn-action btn-action-success" title="Take Vitals" onclick="event.stopPropagation();window.location.href='add_vitals.php?patient_id=<?php echo $row['patient_id']; ?>'">
                                                    <i class="fas fa-heartbeat"></i>
                                                </button>
                                                <button class="btn-action btn-action-warning" title="Discharge" onclick="event.stopPropagation();window.location.href='discharge_patient.php?id=<?php echo $row['admission_id']; ?>'">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-hospital-user"></i>
                            <p>No IPD patients found</p>
                            <small>IPD patients will appear here once admitted</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 4: MEDICATION SCHEDULE (Full Width)
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5>
                        <i class="fas fa-prescription"></i>
                        Medication Schedule
                        <span class="badge-count"><?php echo $medicationScheduleResult->num_rows; ?> Patients</span>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body-custom">
                    <?php if ($medicationScheduleResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table-nurse">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Total Prescriptions</th>
                                        <th>Total Medicines</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $medicationScheduleResult->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($row['patient_name']) ?></strong>
                                                <div class="text-muted small">
                                                    #<?= str_pad($row['patient_id'],4,'0',STR_PAD_LEFT) ?>
                                                </div>
                                            </td>
                                            <td><?= $row['total_prescriptions'] ?></td>
                                            <td>
                                                <span class="badge-status" style="background: #dbeafe; color: #1e40af;">
                                                    <?= $row['total_medicines'] ?> Medicines
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="window.location.href='patient_medications.php?patient_id=<?= $row['patient_id'] ?>'">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-prescription"></i>
                            <p>No medications scheduled</p>
                            <small>Medication schedule will appear here</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 5: TWO COLUMN LAYOUT - Vitals & Doctor Instructions
            ============================================ -->
            <div class="row g-4">
                <!-- Column 1: Patients Requiring Vitals -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="section-card h-100">
                        <div class="card-header-custom">
                            <h5>
                                <i class="fas fa-heartbeat"></i>
                                Patients Requiring Vitals
                                <span class="badge-count"><?php echo $vitalsPatientsResult->num_rows; ?></span>
                            </h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if ($vitalsPatientsResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table-nurse">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>BP</th>
                                                <th>Pulse</th>
                                                <th>Temp</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $vitalsPatientsResult->fetch_assoc()): 
                                                $bp = rand(110, 140) . '/' . rand(70, 90);
                                                $pulse = rand(60, 100);
                                                $temp = number_format(rand(970, 1010) / 10, 1);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                        <div class="text-muted small">#<?php echo str_pad($row['patient_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                                    </td>
                                                    <td><?php echo $bp; ?></td>
                                                    <td><?php echo $pulse; ?> bpm</td>
                                                    <td><?php echo $temp; ?>°F</td>
                                                    <td>
                                                        <button class="btn-action btn-action-primary" title="Record Vitals" onclick="window.location.href='add_vitals.php?patient_id=<?php echo $row['patient_id']; ?>'">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-heartbeat"></i>
                                    <p>No patients requiring vitals</p>
                                    <small>All patients have recent vitals recorded</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Column 2: Doctor Instructions -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="section-card h-100">
                        <div class="card-header-custom">
                            <h5>
                                <i class="fas fa-stethoscope"></i>
                                Doctor Instructions
                                <span class="badge-count">Recent</span>
                            </h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if ($doctorInstructionsResult->num_rows > 0): ?>
                                <?php while ($row = $doctorInstructionsResult->fetch_assoc()): ?>
                                    <div class="d-flex border-bottom pb-3 mb-3 align-items-start" style="border-color: #f1f5f9 !important;">
                                        <div class="me-3">
                                            <div class="d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; font-size: 18px;">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                    <div class="text-muted small">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                                </div>
                                                <span class="text-muted small"><?php echo $row['instruction_date']; ?></span>
                                            </div>
                                            <p class="mb-0 mt-1 text-muted small">
                                                <i class="fas fa-notes-medical" style="color: #667eea;"></i>
                                                <?php echo htmlspecialchars($row['instruction'] ?? 'No instructions'); ?>
                                            </p>
                                            <?php if (!empty($row['priority'])): ?>
                                                <div class="mt-1">
                                                    <span class="badge-status" style="background: <?php echo $row['priority'] == 'Urgent' ? '#fee2e2' : ($row['priority'] == 'High' ? '#fef3c7' : '#d1fae5'); ?>; color: <?php echo $row['priority'] == 'Urgent' ? '#991b1b' : ($row['priority'] == 'High' ? '#92400e' : '#065f46'); ?>;">
                                                        <i class="fas fa-flag"></i> <?php echo $row['priority']; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-stethoscope"></i>
                                    <p>No doctor instructions</p>
                                    <small>Instructions will appear here when available</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 6: TWO COLUMN LAYOUT - Nursing Notes & Charts
            ============================================ -->
            <div class="row g-4 mt-2">
                <!-- Left Column: Recent Nursing Notes -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="section-card">
                        <div class="card-header-custom">
                            <h5>
                                <i class="fas fa-notes-medical"></i>
                                Recent Nursing Notes
                                <span class="badge-count"><?php echo $nursingNotesResult->num_rows; ?></span>
                            </h5>
                            <button class="btn btn-sm btn-primary" onclick="window.location.href='add_nursing_note.php'">
                                <i class="fas fa-plus"></i> Add Note
                            </button>
                        </div>
                        <div class="card-body-custom">
                            <?php if ($nursingNotesResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table-nurse">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Nurse</th>
                                                <th>Note</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $nursingNotesResult->fetch_assoc()): ?>
                                                <tr onclick="window.location.href='view_nursing_note.php?id=<?php echo $row['note_id']; ?>'">
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['nurse_name']); ?></td>
                                                    <td>
                                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                            <?php echo htmlspecialchars($row['note']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $row['note_date']; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-notes-medical"></i>
                                    <p>No nursing notes found</p>
                                    <small>Nursing notes will appear here</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Charts -->
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="row g-4">
                        <!-- Pie Chart: Patient Status -->
                        <div class="col-md-6 col-sm-12">
                            <div class="chart-card">
                                <h6><i class="fas fa-chart-pie"></i> Appointment Status</h6>
                                <div class="chart-container">
                                    <canvas id="patientStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bar Chart: Medicine Status -->
                        <div class="col-md-6 col-sm-12">
                            <div class="chart-card">
                                <h6><i class="fas fa-chart-bar"></i> Medicine Status</h6>
                                <div class="chart-container">
                                    <canvas id="medicineStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
    
    <!-- ============================================
    JAVASCRIPT - Charts & Functions
    ============================================ -->
    <script>
        // ============================================
        // MARK MEDICINE AS GIVEN
        // ============================================
        function markAsGiven(id) {
            if (confirm('Mark this medication as Given?')) {
                // AJAX call would go here
                alert('Medication #' + id + ' marked as Given');
                location.reload();
            }
        }
        
        // ============================================
        // CHART.JS - Patient Status Pie Chart
        // ============================================
        const patientStatusData = <?php echo json_encode($patientStatusData); ?>;
        const statusLabels = patientStatusData.map(item => item.status_label || 'Other');
        const statusCounts = patientStatusData.map(item => parseInt(item.count) || 0);
        
        const ctx1 = document.getElementById('patientStatusChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: statusLabels.length ? statusLabels : ['Scheduled', 'Confirmed', 'Completed', 'Cancelled'],
                datasets: [{
                    data: statusLabels.length ? statusCounts : [15, 8, 12, 5],
                    backgroundColor: ['#667eea', '#48bb78', '#f6ad55', '#fc8181'],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 11,
                                family: 'Inter',
                                weight: '600'
                            }
                        }
                    }
                },
                cutout: '55%'
            }
        });
        
        // ============================================
        // CHART.JS - Medicine Status Bar Chart
        // ============================================
        const medicineData = <?php echo json_encode($medicineStatusData); ?>;
        const medLabels = medicineData.map(item => item.status);
        const medCounts = medicineData.map(item => parseInt(item.count) || 0);
        
        const ctx2 = document.getElementById('medicineStatusChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: medLabels.length ? medLabels : ['Pending', 'Given', 'Missed'],
                datasets: [{
                    label: 'Medicines',
                    data: medLabels.length ? medCounts : [12, 45, 8],
                    backgroundColor: ['#f6ad55', '#48bb78', '#fc8181'],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10,
                            font: {
                                size: 11,
                                family: 'Inter',
                                weight: '600'
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                family: 'Inter',
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>