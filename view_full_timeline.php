<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION['hospital_id'])) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];
$hid = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];
$hospital_logo = $_SESSION['hospital_logo'];

// Fetch Patient Name
$patient_query = "SELECT patient_name FROM patients WHERE patient_id='$patient_id' AND hospital_id='$hid'";
$patient_result = $conn->query($patient_query);

if($patient_result && $patient_result->num_rows > 0){
    $patient = $patient_result->fetch_assoc();
    $patient_name = $patient['patient_name'];
}else{
    header("Location: patients.php");
    exit();
}

// Get hospital info
$hospital_query = mysqli_query($conn, "SELECT * FROM hospital_master WHERE hospital_id = '$hid'");
$hospital = mysqli_fetch_assoc($hospital_query);

// FULL TIMELINE QUERY
$timeline_query = "
(SELECT 
    'appointment' as event_type,
    a.appointment_date as event_date,
    a.appointment_time as event_time,
    CONCAT('Appointment - ', a.status) as title,
    CONCAT(COALESCE(d.doctor_name,'Unknown'),' • ',a.opd_ipd_type,' Consultation') as description,
    a.created_at as created_date
FROM appointments a
LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
WHERE a.patient_id='$patient_id'
AND a.hospital_id='$hid'
AND (a.delete_flag=0 OR a.delete_flag IS NULL))

UNION ALL

(SELECT
    'prescription' as event_type,
    p.created_at as event_date,
    NULL as event_time,
    'Prescription Created' as title,
    CONCAT('Prescription Added') as description,
    p.created_at as created_date
FROM prescriptions p
WHERE p.patient_id='$patient_id'
AND p.hospital_id='$hid'
AND (p.delete_flag=0 OR p.delete_flag IS NULL))

UNION ALL

(SELECT
    'surgery' as event_type,
    s.surgery_date as event_date,
    s.surgery_time as event_time,
    CONCAT('Surgery - ',s.surgery_title) as title,
    CONCAT(
        COALESCE(s.surgeon_name,'Unknown'),
        ' • ',
        COALESCE(s.surgery_type,'Surgery')
    ) as description,
    s.created_at as created_date
FROM surgeries s
WHERE s.patient_id='$patient_id'
AND s.hospital_id='$hid'
AND (s.delete_flag=0 OR s.delete_flag IS NULL))

UNION ALL

(SELECT
    'diagnosis' as event_type,
    p2.created_at as event_date,
    NULL as event_time,
    'Diagnosis Added' as title,
    p2.medical_history as description,
    p2.created_at as created_date
FROM patients p2
WHERE p2.patient_id='$patient_id'
AND p2.hospital_id='$hid'
AND p2.medical_history IS NOT NULL
AND p2.medical_history!='')

UNION ALL

(SELECT
    'registration' as event_type,
    p3.created_at as event_date,
    NULL as event_time,
    'Patient Registered' as title,
    'Patient registration completed' as description,
    p3.created_at as created_date
FROM patients p3
WHERE p3.patient_id='$patient_id'
AND p3.hospital_id='$hid')

ORDER BY created_date DESC
";

$result = $conn->query($timeline_query);
$timeline_events=[];

if($result && $result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $timeline_events[]=$row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital['hospital_name']); ?> - Patient Timeline</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital['hospital_logo']); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f9fafb;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Main wrapper - accounts for header and sidebar */
        .main-wrapper {
            margin-left: 250px;
            margin-top: 70px;
            padding: 20px;
            min-height: 100vh;
            background: #f9fafb;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 10px;
                margin-top: 60px;
            }
        }

        .timeline-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0;
        }

        .timeline-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
        }

        .timeline-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .timeline-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .timeline-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }

        .timeline-title .badge {
            background: #eff6ff;
            color: #3b82f6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .timeline-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-download {
            background: #3b82f6;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-download:active {
            transform: translateY(0);
        }

        .btn-back {
            background: #f3f4f6;
            color: #374151;
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        /* Timeline with progress bar */
        .timeline-wrapper {
            position: relative;
            padding-left: 40px;
        }

        /* Progress Bar (Vertical line on the left) */
        .timeline-progress {
            position: absolute;
            left: 14px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: visible;
        }

        .timeline-progress-fill {
            width: 100%;
            background: linear-gradient(180deg, #3b82f6, #8b5cf6);
            border-radius: 4px;
            transition: height 1.5s ease;
            position: relative;
        }

        .timeline-progress-fill::after {
            content: '';
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 10px;
            height: 10px;
            background: #8b5cf6;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(139, 92, 246, 0.5);
        }

        /* Individual timeline items */
        .timeline-item {
            position: relative;
            padding-bottom: 32px;
            padding-left: 24px;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s ease forwards;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -32px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .timeline-item:hover::before {
            transform: scale(1.2);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }

        .timeline-item.completed::before {
            border-color: #3b82f6;
            background: #3b82f6;
        }

        .timeline-item.active::before {
            border-color: #8b5cf6;
            background: #8b5cf6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.3);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(139, 92, 246, 0.1);
            }
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Stagger animation delay */
        .timeline-item:nth-child(1) { animation-delay: 0.1s; }
        .timeline-item:nth-child(2) { animation-delay: 0.2s; }
        .timeline-item:nth-child(3) { animation-delay: 0.3s; }
        .timeline-item:nth-child(4) { animation-delay: 0.4s; }
        .timeline-item:nth-child(5) { animation-delay: 0.5s; }
        .timeline-item:nth-child(6) { animation-delay: 0.6s; }
        .timeline-item:nth-child(7) { animation-delay: 0.7s; }
        .timeline-item:nth-child(8) { animation-delay: 0.8s; }
        .timeline-item:nth-child(9) { animation-delay: 0.9s; }
        .timeline-item:nth-child(10) { animation-delay: 1.0s; }

        .timeline-item-content {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #f3f4f6;
            transition: all 0.3s ease;
            position: relative;
        }

        .timeline-item-content:hover {
            background: white;
            border-color: #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateX(4px);
        }

        .timeline-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 8px;
        }

        .timeline-item-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .timeline-item-title h3 {
            font-weight: 600;
            color: #111827;
            font-size: 1rem;
        }

        .timeline-item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-appointment { background: #eff6ff; color: #3b82f6; }
        .icon-prescription { background: #ecfdf5; color: #10b981; }
        .icon-surgery { background: #f5f3ff; color: #8b5cf6; }
        .icon-diagnosis { background: #fef2f2; color: #ef4444; }
        .icon-registration { background: #f3f4f6; color: #6b7280; }

        .timeline-item-date {
            font-size: 0.75rem;
            color: #6b7280;
            white-space: nowrap;
            background: white;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
        }

        .timeline-item-date i {
            margin-right: 4px;
        }

        .timeline-item-description {
            color: #4b5563;
            font-size: 0.875rem;
            margin-top: 8px;
            line-height: 1.6;
        }

        .timeline-item-description p {
            margin: 0;
        }

        .timeline-item-tags {
            display: flex;
            gap: 6px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .tag {
            font-size: 0.7rem;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 500;
        }

        .tag-blue { background: #eff6ff; color: #3b82f6; }
        .tag-green { background: #ecfdf5; color: #10b981; }
        .tag-purple { background: #f5f3ff; color: #8b5cf6; }
        .tag-red { background: #fef2f2; color: #ef4444; }
        .tag-gray { background: #f3f4f6; color: #6b7280; }

        .timeline-empty {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .timeline-empty i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 16px;
            display: block;
        }

        /* Stats bar */
        .timeline-stats {
            display: flex;
            gap: 24px;
            padding: 16px 20px;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-item .stat-number {
            font-weight: 700;
            font-size: 1.125rem;
            color: #111827;
        }

        .stat-item .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .stat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .stat-dot-blue { background: #3b82f6; }
        .stat-dot-green { background: #10b981; }
        .stat-dot-purple { background: #8b5cf6; }
        .stat-dot-red { background: #ef4444; }

        /* Download progress overlay */
        .download-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .download-overlay.active {
            display: flex;
        }

        .download-modal {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .download-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .download-modal h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .download-modal p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 10px;
                margin-top: 60px;
            }

            .timeline-card {
                padding: 16px;
            }

            .timeline-wrapper {
                padding-left: 28px;
            }

            .timeline-progress {
                left: 8px;
            }

            .timeline-item {
                padding-left: 16px;
            }

            .timeline-item::before {
                left: -24px;
                width: 12px;
                height: 12px;
            }

            .timeline-header {
                flex-direction: column;
                align-items: stretch;
            }

            .timeline-title h1 {
                font-size: 1.125rem;
            }

            .timeline-actions {
                justify-content: stretch;
            }

            .btn-download, .btn-back {
                justify-content: center;
                flex: 1;
            }

            .timeline-stats {
                gap: 12px;
                padding: 12px 16px;
            }

            .stat-item .stat-number {
                font-size: 0.9rem;
            }

            .timeline-item-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .timeline-item-date {
                font-size: 0.7rem;
                padding: 2px 10px;
            }

            .timeline-item-content {
                padding: 12px 16px;
            }
        }

        @media print {
            .btn-download, .btn-back, .timeline-actions {
                display: none !important;
            }
            
            .timeline-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            
            .timeline-card::before {
                display: none !important;
            }
            
            .timeline-item-content {
                background: white !important;
                border: 1px solid #e5e7eb !important;
                box-shadow: none !important;
            }
            
            .timeline-item {
                opacity: 1 !important;
                transform: none !important;
                animation: none !important;
            }
            
            .timeline-item.active::before {
                animation: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'Sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="timeline-container">
            <div class="timeline-card" id="timelineCard">
                <!-- Header -->
                <div class="timeline-header">
                    <div class="timeline-title">
                        <h1 id="patientName"><?php echo htmlspecialchars($patient_name); ?></h1>
                        <span class="badge">Full Timeline</span>
                    </div>
                    <div class="timeline-actions">
                        <a href="view_patient.php?id=<?php echo $patient_id; ?>" class="btn-back">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Back
                        </a>
                        <button onclick="downloadTimeline()" class="btn-download">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            Download PDF
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <?php if (!empty($timeline_events)): ?>
                <div class="timeline-stats" id="timelineStats">
                    <?php 
                    $counts = array_count_values(array_column($timeline_events, 'event_type'));
                    $total = count($timeline_events);
                    ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total; ?></span>
                        <span class="stat-label">Total Events</span>
                    </div>
                    <?php if (isset($counts['appointment'])): ?>
                    <div class="stat-item">
                        <span class="stat-dot stat-dot-blue"></span>
                        <span class="stat-number"><?php echo $counts['appointment']; ?></span>
                        <span class="stat-label">Appointments</span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($counts['prescription'])): ?>
                    <div class="stat-item">
                        <span class="stat-dot stat-dot-green"></span>
                        <span class="stat-number"><?php echo $counts['prescription']; ?></span>
                        <span class="stat-label">Prescriptions</span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($counts['surgery'])): ?>
                    <div class="stat-item">
                        <span class="stat-dot stat-dot-purple"></span>
                        <span class="stat-number"><?php echo $counts['surgery']; ?></span>
                        <span class="stat-label">Surgeries</span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($counts['diagnosis'])): ?>
                    <div class="stat-item">
                        <span class="stat-dot stat-dot-red"></span>
                        <span class="stat-number"><?php echo $counts['diagnosis']; ?></span>
                        <span class="stat-label">Diagnoses</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Timeline -->
                <div class="timeline-wrapper">
                    <!-- Progress Bar -->
                    <div class="timeline-progress">
                        <div class="timeline-progress-fill" id="progressFill" style="height: 0%;"></div>
                    </div>

                    <?php if (!empty($timeline_events)): ?>
                        <?php 
                        $total_events = count($timeline_events);
                        $index = 0;
                        foreach($timeline_events as $event): 
                            $index++;
                            $progress = ($index / $total_events) * 100;
                            
                            $icons = [
                                "appointment" => "calendar",
                                "prescription" => "pill",
                                "surgery" => "scissors",
                                "diagnosis" => "stethoscope",
                                "registration" => "user-plus"
                            ];

                            $colors = [
                                "appointment" => "icon-appointment",
                                "prescription" => "icon-prescription",
                                "surgery" => "icon-surgery",
                                "diagnosis" => "icon-diagnosis",
                                "registration" => "icon-registration"
                            ];

                            $tag_colors = [
                                "appointment" => "tag-blue",
                                "prescription" => "tag-green",
                                "surgery" => "tag-purple",
                                "diagnosis" => "tag-red",
                                "registration" => "tag-gray"
                            ];

                            $event_type_labels = [
                                "appointment" => "Appointment",
                                "prescription" => "Prescription",
                                "surgery" => "Surgery",
                                "diagnosis" => "Diagnosis",
                                "registration" => "Registration"
                            ];

                            $item_class = 'timeline-item';
                            if ($index == $total_events) {
                                $item_class .= ' active';
                            } elseif ($index < $total_events) {
                                $item_class .= ' completed';
                            }
                        ?>
                        <div class="<?php echo $item_class; ?>" data-progress="<?php echo $progress; ?>">
                            <div class="timeline-item-content">
                                <div class="timeline-item-header">
                                    <div class="timeline-item-title">
                                        <div class="timeline-item-icon <?php echo $colors[$event['event_type']]; ?>">
                                            <i data-lucide="<?php echo $icons[$event['event_type']]; ?>" class="w-4 h-4"></i>
                                        </div>
                                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    </div>
                                    <span class="timeline-item-date">
                                        <i data-lucide="calendar" class="w-3 h-3 inline"></i>
                                        <?php echo date("d M Y", strtotime($event['event_date'])); ?>
                                        <?php if (!empty($event['event_time'])): ?>
                                            <i data-lucide="clock" class="w-3 h-3 inline ml-1"></i>
                                            <?php echo date("h:i A", strtotime($event['event_time'])); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="timeline-item-description">
                                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                </div>
                                <div class="timeline-item-tags">
                                    <span class="tag <?php echo $tag_colors[$event['event_type']]; ?>">
                                        <?php echo $event_type_labels[$event['event_type']]; ?>
                                    </span>
                                    <?php if (!empty($event['event_time'])): ?>
                                    <span class="tag tag-gray">
                                        <i data-lucide="clock" class="w-3 h-3 inline"></i>
                                        <?php echo date("h:i A", strtotime($event['event_time'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="timeline-empty">
                            <i data-lucide="inbox" class="w-16 h-16 mx-auto"></i>
                            <p>No timeline events found for this patient.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Download Overlay -->
    <div class="download-overlay" id="downloadOverlay">
        <div class="download-modal">
            <div class="download-spinner"></div>
            <h3>Generating Document</h3>
            <p>Please wait while we prepare your timeline document...</p>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Animate progress bar on load
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.timeline-item');
            const progressFill = document.getElementById('progressFill');
            
            if (items.length > 0) {
                // Set initial progress
                setTimeout(() => {
                    const lastItem = items[items.length - 1];
                    if (lastItem) {
                        const progress = parseFloat(lastItem.dataset.progress) || 100;
                        progressFill.style.height = progress + '%';
                    }
                }, 300);
            }

            // Animate items on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateX(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.timeline-item').forEach(item => {
                observer.observe(item);
            });
        });

        function downloadTimeline() {
            const overlay = document.getElementById('downloadOverlay');
            overlay.classList.add('active');

            const card = document.getElementById('timelineCard');
            
            // Set up for clean capture
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            
            // Force all timeline items to be visible for capture
            document.querySelectorAll('.timeline-item').forEach(item => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
                item.style.animation = 'none';
            });

            // Add a print class for better rendering
            card.classList.add('printing');

            // Use html2canvas with better quality
            html2canvas(card, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                windowWidth: card.scrollWidth,
                windowHeight: card.scrollHeight,
                onclone: function(document) {
                    // Ensure all icons are rendered
                    document.querySelectorAll('[data-lucide]').forEach(el => {
                        el.style.display = 'inline-block';
                    });
                }
            }).then(function(canvas) {
                // Remove print class
                card.classList.remove('printing');
                
                // Create download link
                const link = document.createElement('a');
                link.download = `Patient_Timeline_${document.getElementById('patientName').textContent.trim()}_${new Date().toISOString().split('T')[0]}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                // Restore styles
                document.body.style.overflow = originalOverflow;
                overlay.classList.remove('active');
            }).catch(function(err) {
                console.error('Error generating document:', err);
                document.body.style.overflow = originalOverflow;
                overlay.classList.remove('active');
                alert('There was an error generating the document. Please try again.');
            });
        }

        // Keyboard shortcut for download (Ctrl+D)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                downloadTimeline();
            }
        });

        // Add print styles for better PDF generation
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                .timeline-item {
                    break-inside: avoid;
                    page-break-inside: avoid;
                    opacity: 1 !important;
                    transform: none !important;
                    animation: none !important;
                }
                .timeline-progress-fill {
                    height: 100% !important;
                }
                .download-overlay {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>