<?php
session_start();
include '../config/hospital.php';

if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}

if(!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$patient_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

if($patient_id == 0) {
    $_SESSION['error_message'] = "Invalid patient ID.";
    header("Location: patients_list.php");
    exit();
}

$sql = "SELECT p.*, r.name as register_name, d.doctor_name, d.department 
        FROM patients p
        LEFT JOIN register r ON p.register_id = r.id
        LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
        WHERE p.patient_id = '$patient_id' 
        AND (p.delete_flag=0 OR p.delete_flag IS NULL)";

$result = mysqli_query($conn, $sql);
$patient = mysqli_fetch_assoc($result);

if(!$patient) {
    $_SESSION['error_message'] = "Patient not found.";
    header("Location: patients_list.php");
    exit();
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $hospital['hospital_name'] ?? 'PreClinic'; ?> · Patient Profile</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? ''; ?>" />
    
    <!-- Google Font & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            padding: 24px 32px;
            color: #0B1B33;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        
        /* Cards */
        .card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.06), 0 2px 6px rgba(0,0,0,0.02);
            transition: box-shadow 0.2s ease, transform 0.15s ease;
            padding: 20px 24px;
        }
        .card:hover {
            box-shadow: 0 16px 40px rgba(79, 70, 229, 0.08), 0 4px 12px rgba(0,0,0,0.02);
        }
        
        /* Header */
        .header-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
        }
        .patient-badge {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #4F46E5, #3B82F6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 24px;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.25);
            overflow: hidden;
            flex-shrink: 0;
        }
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .patient-meta h2 {
            font-size: 22px;
            font-weight: 600;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .patient-meta .id-badge {
            background: #EEF2FF;
            color: #4F46E5;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 30px;
        }
        .patient-meta .address {
            font-size: 14px;
            color: #64748B;
            margin-top: 4px;
        }
        .patient-details {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 28px;
            margin-top: 6px;
            font-size: 14px;
            color: #1E293B;
        }
        .patient-details span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .patient-details i { color: #4F46E5; width: 18px; }
        
        .header-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #E2E8F0;
            border-radius: 30px;
            padding: 8px 16px;
            font-weight: 500;
            font-size: 13px;
            color: #1E293B;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.15s;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-outline i { color: #64748B; }
        .btn-outline:hover { background: #F1F5F9; border-color: #CBD5E1; }
        
        .btn-primary {
            background: linear-gradient(135deg, #4F46E5, #3B82F6);
            border: none;
            border-radius: 30px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 14px;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.3);
            transition: 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary i { font-size: 16px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(79, 70, 229, 0.35); }
        
        /* Alerts */
        .alert-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            margin: 16px 0 28px 0;
        }
        .pill {
            padding: 6px 18px 6px 14px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            background: #F1F5F9;
            color: #1E293B;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .pill i { font-size: 14px; }
        .pill-danger { background: #FEE2E2; color: #991B1B; }
        .pill-warning { background: #FEF3C7; color: #92400E; }
        .pill-info { background: #DBEAFE; color: #1E40AF; }
        .pill-success { background: #D1FAE5; color: #065F46; }
        
        /* Stat Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            border: 1px solid #F1F5F9;
            transition: 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(79,70,229,0.06); }
        .stat-card .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; letter-spacing: -0.3px; }
        .stat-card .stat-label { font-size: 13px; color: #64748B; margin-top: 2px; }
        .stat-card .stat-sub { font-size: 12px; color: #94A3B8; margin-top: 4px; }
        .gradient-blue { background: linear-gradient(135deg, #3B82F6, #4F46E5); }
        .gradient-purple { background: linear-gradient(135deg, #A855F7, #6366F1); }
        .gradient-green { background: linear-gradient(135deg, #22C55E, #16A34A); }
        .gradient-orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .gradient-rose { background: linear-gradient(135deg, #EF4444, #DC2626); }
        
        /* Tabs */
        .tabs-container {
            margin: 20px 0 28px 0;
            border-bottom: 1px solid #E9EDF2;
        }
        .tabs {
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
        }
        .tab {
            font-weight: 500;
            font-size: 15px;
            padding: 8px 0 12px 0;
            color: #64748B;
            border-bottom: 2px solid transparent;
            transition: 0.2s;
            cursor: default;
        }
        .tab.active {
            color: #4F46E5;
            border-bottom: 2px solid #4F46E5;
            font-weight: 600;
        }
        .tab i { margin-right: 8px; font-size: 14px; }
        
        /* Tables */
        .table-wrapper { overflow-x: auto; margin-top: 10px; border-radius: 16px; }
        .table-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 16px;
            margin-bottom: 18px;
            align-items: center;
        }
        .filter-input {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 40px;
            padding: 8px 16px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            min-width: 140px;
        }
        .filter-input:focus { outline: 2px solid #4F46E5; outline-offset: 1px; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .data-table th {
            text-align: left;
            padding: 14px 12px;
            background: #F8FAFC;
            font-weight: 600;
            color: #334155;
            border-bottom: 1px solid #E9EDF2;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .data-table td { padding: 14px 12px; border-bottom: 1px solid #F1F5F9; color: #1E293B; }
        .data-table tr:hover td { background: #FAFBFF; }
        .action-icons i { margin: 0 6px; color: #94A3B8; transition: 0.1s; cursor: pointer; }
        .action-icons i:hover { color: #4F46E5; }
        
        .empty-state { text-align: center; padding: 40px 10px; color: #94A3B8; }
        .empty-state i { font-size: 40px; color: #CBD5E1; margin-bottom: 12px; }
        
        /* Two-column layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            margin-top: 12px;
        }
        .right-sidebar { display: flex; flex-direction: column; gap: 28px; }
        
        /* Timeline */
        .timeline-item {
            display: flex;
            gap: 14px;
            padding-bottom: 18px;
            border-left: 2px solid #E2E8F0;
            padding-left: 20px;
            position: relative;
        }
        .timeline-item:last-child { border-left: 2px solid transparent; }
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 20px;
            background: #4F46E5;
            position: absolute;
            left: -7px;
            top: 4px;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #4F46E5;
        }
        .timeline-dot.green { background: #22C55E; box-shadow: 0 0 0 2px #22C55E; }
        .timeline-dot.purple { background: #A855F7; box-shadow: 0 0 0 2px #A855F7; }
        .timeline-dot.orange { background: #F59E0B; box-shadow: 0 0 0 2px #F59E0B; }
        .timeline-content { font-size: 13px; }
        .timeline-content .time { color: #64748B; font-size: 12px; }
        .timeline-content .title { font-weight: 600; margin: 2px 0; }
        .timeline-content .desc { color: #475569; }
        
        .doc-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #F1F5F9;
            font-size: 14px;
        }
        .doc-item:last-child { border: none; }
        .doc-item .date { color: #94A3B8; font-size: 12px; }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }
        .quick-btn {
            background: #F8FAFC;
            border-radius: 40px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 500;
            color: #1E293B;
            border: 1px solid #E9EDF2;
            transition: 0.1s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .quick-btn i { color: #4F46E5; }
        .quick-btn:hover { background: #EEF2FF; border-color: #4F46E5; }
        
        /* Status Badge */
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .profile-layout { grid-template-columns: 1fr; }
            body { padding: 16px; }
        }
        @media (max-width: 640px) {
            .header-grid { flex-direction: column; align-items: stretch; }
            .patient-badge { flex-wrap: wrap; }
            .patient-meta h2 { font-size: 18px; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header-grid card" style="padding: 18px 24px;">
        <div class="patient-badge">
            <div class="avatar">
                <?php
                $image = $patient['patient_image'] ?? '';
                if (!empty($image) && file_exists("../" . $image)) {
                    echo '<img src="../' . htmlspecialchars($image) . '" alt="Patient" />';
                } else {
                    echo strtoupper(substr($patient['patient_name'] ?? 'P', 0, 1));
                }
                ?>
            </div>
            <div class="patient-meta">
                <h2>
                    <?php echo htmlspecialchars($patient['patient_name'] ?? 'Unknown Patient'); ?>
                    <span class="id-badge"><i class="fas fa-id-card"></i> #<?php echo $patient['patient_id']; ?></span>
                    <?php 
                    $status = strtolower($patient['status'] ?? 'active');
                    $status_class = ($status == 'active' || $status == '') ? 'status-active' : 'status-inactive';
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </h2>
                <div class="address">
                    <i class="fas fa-map-pin" style="color:#4F46E5;"></i> 
                    <?php echo htmlspecialchars($patient['address'] ?? 'Address not available'); ?>
                </div>
                <div class="patient-details">
                    <span><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?>, 
                        <?php 
                        if(!empty($patient['date_of_birth'])) {
                            $dob = new DateTime($patient['date_of_birth']);
                            $today = new DateTime();
                            echo $today->diff($dob)->y . ' Yrs';
                        } else { echo 'N/A'; }
                        ?>
                    </span>
                    <span><i class="fas fa-tint"></i> <?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['mobile'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-calendar-check"></i> Last visit: <?php echo date('d M Y', strtotime($patient['created_at'] ?? 'now')); ?></span>
                    <span><i class="fas fa-calendar-plus"></i> Next: <?php echo date('d M Y', strtotime('+2 weeks')); ?></span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="tel:<?php echo $patient['mobile'] ?? ''; ?>" class="btn-outline"><i class="fas fa-phone-alt"></i> Call</a>
            <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn-outline"><i class="fas fa-user-edit"></i> Edit</a>
            <a href="#" class="btn-outline"><i class="fas fa-prescription"></i> Last Rx</a>
            <a href="prescription.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn-primary"><i class="fas fa-prescription-bottle"></i> Make Prescription</a>
        </div>
    </div>

    <!-- Alert Pills (dynamic from patient data) -->
    <div class="alert-pills">
        <?php if(!empty($patient['allergies'])): ?>
            <span class="pill pill-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($patient['allergies']); ?></span>
        <?php endif; ?>
        <span class="pill pill-warning"><i class="fas fa-hand-holding-heart"></i> Blood Thinner Active</span>
        <span class="pill pill-info"><i class="fas fa-droplet"></i> <?php echo !empty($patient['blood_group']) ? 'Blood Group: ' . $patient['blood_group'] : 'Diabetic'; ?></span>
        <span class="pill pill-success"><i class="fas fa-flask"></i> Last INR: 2.8 (12 Jul 2026)</span>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon gradient-blue"><i class="fas fa-calendar-check"></i></div><div class="stat-number">12</div><div class="stat-label">Total Visits</div><div class="stat-sub">Last: <?php echo date('d M Y'); ?></div></div>
        <div class="stat-card"><div class="stat-icon gradient-purple"><i class="fas fa-scalpel"></i></div><div class="stat-number">2</div><div class="stat-label">Surgeries</div><div class="stat-sub">Last: 12 Jul 2026</div></div>
        <div class="stat-card"><div class="stat-icon gradient-green"><i class="fas fa-heartbeat"></i></div><div class="stat-number">4</div><div class="stat-label">Active Diagnosis</div><div class="stat-sub">Updated: <?php echo date('d M Y'); ?></div></div>
        <div class="stat-card"><div class="stat-icon gradient-orange"><i class="fas fa-allergies"></i></div><div class="stat-number">1</div><div class="stat-label">Allergies</div><div class="stat-sub">Updated: <?php echo date('d M Y'); ?></div></div>
        <div class="stat-card"><div class="stat-icon gradient-rose"><i class="fas fa-file-prescription"></i></div><div class="stat-number">3</div><div class="stat-label">Last Prescription</div><div class="stat-sub"><?php echo date('d M Y'); ?></div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <span class="tab active"><i class="fas fa-notes-medical"></i> About</span>
            <span class="tab"><i class="fas fa-scalpel"></i> Surgeries</span>
            <span class="tab"><i class="fas fa-stethoscope"></i> Diagnosis</span>
            <span class="tab"><i class="fas fa-list-ul"></i> Co-morbidities</span>
            <span class="tab"><i class="fas fa-comment-medical"></i> Complaints</span>
            <span class="tab"><i class="fas fa-calendar-day"></i> Events</span>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="profile-layout">
        <!-- Main Column -->
        <div>
            <!-- Surgery Table -->
            <div class="card" style="margin-bottom: 28px;">
                <h4 style="font-weight:600; font-size:18px; margin-bottom:6px;">
                    <i class="fas fa-scalpel" style="color:#4F46E5; margin-right:8px;"></i>Surgery History
                </h4>
                <div class="table-filters">
                    <input class="filter-input" placeholder="🔍 Search surgery..." value="Appendectomy" />
                    <input class="filter-input" placeholder="📅 Date filter" value="Jul 2026" />
                    <input class="filter-input" placeholder="🏥 Hospital" value="All" />
                    <input class="filter-input" placeholder="👨‍⚕️ Surgeon" value="All" />
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Date & Time</th><th>Surgery Full Name</th><th>Hospital / Location</th><th>Surgeon</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr><td>12 Jul 2026, 10:30 AM</td><td>Laparoscopic Appendectomy</td><td>City Hospital, Satara</td><td>Dr. Saygaonkar</td><td class="action-icons"><i class="fas fa-eye"></i><i class="fas fa-edit"></i><i class="fas fa-trash-alt"></i></td></tr>
                            <tr><td>05 May 2026, 02:15 PM</td><td>Inguinal Hernia Repair</td><td>Apollo Clinic, Karad</td><td>Dr. Mehta</td><td class="action-icons"><i class="fas fa-eye"></i><i class="fas fa-edit"></i><i class="fas fa-trash-alt"></i></td></tr>
                            <tr><td>dd-mm-yyyy</td><td>—</td><td>—</td><td>—</td><td class="action-icons"><i class="fas fa-plus-circle" style="color:#4F46E5;"></i></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Appointments -->
            <div class="card">
                <h4 style="font-weight:600; font-size:18px; margin-bottom:8px;">
                    <i class="fas fa-calendar-alt" style="color:#4F46E5; margin-right:8px;"></i>Appointments
                </h4>
                <div class="table-filters">
                    <input class="filter-input" placeholder="🔍 Search by patient, doctor..." />
                    <input class="filter-input" type="text" value="<?php echo date('d M y') . ' - ' . date('d M y'); ?>" />
                    <select class="filter-input" style="background:white;"><option>All Doctors</option></select>
                    <select class="filter-input" style="background:white;"><option>All Status</option></select>
                    <span class="btn-outline" style="padding:6px 16px;"><i class="fas fa-sync-alt"></i> Reset</span>
                </div>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <div style="font-weight:500; color:#475569;">No data available in table</div>
                    <div style="font-size:13px;">No appointments found for the selected date range.</div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="right-sidebar">
            <!-- Timeline -->
            <div class="card">
                <h5 style="font-weight:600; font-size:16px; margin-bottom:12px;"><i class="fas fa-clock" style="color:#4F46E5;"></i> Patient Timeline</h5>
                <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-content"><div class="time"><?php echo date('d M Y, h:i A'); ?></div><div class="title">Appointment Completed</div><div class="desc">Dr. Saygaonkar • Offline Consultation</div></div></div>
                <div class="timeline-item"><span class="timeline-dot green"></span><div class="timeline-content"><div class="time"><?php echo date('d M Y, h:i A', strtotime('-1 hour')); ?></div><div class="title">Prescription Created</div><div class="desc">5 Medicines Prescribed</div></div></div>
                <div class="timeline-item"><span class="timeline-dot purple"></span><div class="timeline-content"><div class="time">12 Jul 2026, 10:30 AM</div><div class="title">Surgery Recorded</div><div class="desc">Laparoscopic Appendectomy</div></div></div>
                <div class="timeline-item"><span class="timeline-dot orange"></span><div class="timeline-content"><div class="time">15 Jun 2026, 11:20 AM</div><div class="title">Diagnosis Added</div><div class="desc">Hypertension</div></div></div>
                <div class="timeline-item"><span class="timeline-dot" style="background:#3B82F6; box-shadow:0 0 0 2px #3B82F6;"></span><div class="timeline-content"><div class="time">01 Jun 2026, 09:15 AM</div><div class="title">Patient Registered</div><div class="desc">By Admin</div></div></div>
                <div style="margin-top:6px; font-size:13px; color:#4F46E5; font-weight:500;"><i class="fas fa-arrow-right"></i> View Full Timeline</div>
            </div>

            <!-- Recent Documents -->
            <div class="card">
                <h5 style="font-weight:600; font-size:16px; margin-bottom:8px;"><i class="fas fa-folder-open" style="color:#4F46E5;"></i> Recent Documents</h5>
                <div class="doc-item"><span><i class="fas fa-file-pdf" style="color:#EF4444;"></i> CBC Report</span><span class="date">12 Jul 2026</span></div>
                <div class="doc-item"><span><i class="fas fa-file-pdf" style="color:#3B82F6;"></i> ECG Report</span><span class="date">12 Jul 2026</span></div>
                <div class="doc-item"><span><i class="fas fa-file-pdf" style="color:#A855F7;"></i> Echo Report</span><span class="date">10 Jul 2026</span></div>
                <div class="doc-item"><span><i class="fas fa-prescription" style="color:#22C55E;"></i> Last Prescription</span><span class="date"><?php echo date('d M Y'); ?></span></div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h5 style="font-weight:600; font-size:16px; margin-bottom:8px;"><i class="fas fa-bolt" style="color:#F59E0B;"></i> Quick Actions</h5>
                <div class="quick-actions">
                    <a href="#" class="quick-btn"><i class="fas fa-scalpel"></i> Add Surgeries</a>
                    <a href="#" class="quick-btn"><i class="fas fa-user-md"></i> Add Surgeon</a>
                    <a href="#" class="quick-btn"><i class="fas fa-diagnoses"></i> Add Diagnosis</a>
                    <a href="#" class="quick-btn"><i class="fas fa-heart"></i> Hypertension</a>
                    <a href="#" class="quick-btn"><i class="fas fa-user-plus"></i> Add Patient</a>
                    <a href="#" class="quick-btn"><i class="fas fa-upload"></i> Upload Report</a>
                    <a href="#" class="quick-btn"><i class="fas fa-clock"></i> Full Timeline</a>
                </div>
                <div style="margin-top:14px; background:#F1F5F9; border-radius:30px; padding:8px 16px; font-size:13px; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-life-ring" style="color:#4F46E5;"></i> Need Help? <span style="font-weight:500; color:#4F46E5;">Contact Support</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="margin-top: 24px; font-size: 13px; color: #94A3B8; text-align: center; border-top: 1px solid #E9EDF2; padding-top: 20px;">
        <i class="fas fa-heart" style="color:#EF4444;"></i> <?php echo $hospital['hospital_name'] ?? 'PreClinic'; ?> · Hospital System · Premium patient profile
    </div>

</div>
</body>
</html>