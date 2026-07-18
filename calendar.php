<?php
session_start();
include('config/hospital.php');

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

$doctor_id = $_GET['id'];
$doctor_id = $conn->real_escape_string($doctor_id);

$date = $_GET['date'] ?? date('Y-m-d');
$month = date('n', strtotime($date));
$year = date('Y', strtotime($date));
$today = date('Y-m-d');

$doctor_query = "SELECT * FROM doctor WHERE doctor_id = '$doctor_id' AND delete_flag = 0";
$doctor_result = $conn->query($doctor_query);

if (!$doctor_result) {
    die("SQL Error: " . $conn->error);
}

$doctor = $doctor_result->fetch_assoc();

if (!$doctor) {
    $first_doc_query = "SELECT doctor_id, doctor_name FROM doctor WHERE delete_flag = 0 LIMIT 1";
    $first_doc_result = $conn->query($first_doc_query);
    if ($first_doc_result && $first_doc_result->num_rows > 0) {
        $first_doc = $first_doc_result->fetch_assoc();
        header("Location: ?id=" . $first_doc['doctor_id']);
        exit();
    } else {
        die("No doctors found in the system.");
    }
}

$doctor_name = $doctor['doctor_name'];

$months = [
    1 => "January", 2 => "February", 3 => "March", 4 => "April",
    5 => "May", 6 => "June", 7 => "July", 8 => "August",
    9 => "September", 10 => "October", 11 => "November", 12 => "December"
];

$days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date("t", $firstDay);
$startDay = date("w", $firstDay);

$prevMonthDate = date('Y-m-d', strtotime("-1 month", strtotime("$year-$month-01")));
$nextMonthDate = date('Y-m-d', strtotime("+1 month", strtotime("$year-$month-01")));

$prevMonth = date('n', strtotime($prevMonthDate));
$prevYear = date('Y', strtotime($prevMonthDate));

$nextMonth = date('n', strtotime($nextMonthDate));
$nextYear = date('Y', strtotime($nextMonthDate));

$start_date = date('Y-m-01', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime("$year-$month-01"));

$appointment_query = "SELECT a.*, 
                      p.patient_name as patient_display_name, 
                      p.mobile as patient_phone,
                      d.department_name
                      FROM appointments a
                      LEFT JOIN patients p ON a.patient_id = p.patient_id
                      LEFT JOIN department d ON a.department = d.department_name
                      WHERE a.doctor_id = '$doctor_id' 
                      AND a.delete_flag = 0
                      AND a.appointment_date BETWEEN '$start_date' AND '$end_date'
                      ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$appointments_result = $conn->query($appointment_query);

if (!$appointments_result) {
    die("Appointment Query Error: " . $conn->error);
}

$appointments_by_date = [];
while ($app = $appointments_result->fetch_assoc()) {
    $date_key = $app['appointment_date'];
    if (!isset($appointments_by_date[$date_key])) {
        $appointments_by_date[$date_key] = [];
    }
    $appointments_by_date[$date_key][] = $app;
}

$stats_query = "SELECT 
                 COUNT(*) as total,
                 SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                 SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                 SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                 FROM appointments 
                 WHERE doctor_id = '$doctor_id' 
                 AND delete_flag = 0
                 AND appointment_date BETWEEN '$start_date' AND '$end_date'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$all_doctors_query = "SELECT doctor_id, doctor_name FROM doctor WHERE delete_flag = 0 ORDER BY doctor_name ASC";
$all_doctors_result = $conn->query($all_doctors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Calendar - <?php echo htmlspecialchars($doctor_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
     <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
            font-family: 'Inter', Arial, Helvetica, sans-serif;
            overflow-x: hidden;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .header-section {
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .content-wrapper {
            display: flex;
            flex: 1;
            width: 100%;
        }

        .sidebar-section {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 256px;
            z-index: 40;
            overflow-y: auto;
        }

        .main-content {
            margin-left: 256px;
            margin-top: 0;
            flex: 1;
            width: calc(100% - 256px);
            padding: 24px;
            overflow-y: auto;
        }

        @media (max-width: 1024px) {
            .sidebar-section {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
        }

        @media (max-width: 768px) {
            .sidebar-section {
                transform: translateX(-100%);
                width: 100%;
                transition: transform 0.3s ease;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
        }

        .calendar-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .calendar-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
            flex-wrap: wrap;
            gap: 10px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toolbar-btn,
        .toolbar-btn-icon {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            cursor: pointer;
        }

        .toolbar-btn {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
        }

        .toolbar-btn-icon {
            width: 38px;
            height: 38px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .toolbar-btn:hover,
        .toolbar-btn-icon:hover {
            background: #2563eb;
            color: #fff;
        }

        .month-label {
            font-size: 24px;
            font-weight: 700;
            margin-left: 10px;
            color: #111827;
        }

        .calendar-grid {
            padding: 20px;
        }

        .weekdays,
        .days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .weekday {
            text-align: center;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #111827;
        }

        .day-cell {
            position: relative;
            min-height: 120px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
            overflow: hidden;
            transition: 0.3s;
        }

        .day-cell:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }

        .empty {
            background: #f9fafb;
            opacity: 0.5;
        }

        .today {
            background: #dbeafe;
            border: 2px solid #2563eb;
        }

        .day-number {
            float: right;
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 5px;
        }

        .event {
            margin-top: 4px;
            background: #2563eb;
            color: #fff;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 11px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: all 0.2s;
            clear: both;
        }

        .event:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .event-status-scheduled { background: #2563eb; }
        .event-status-completed { background: #16a34a; }
        .event-status-cancelled { background: #dc2626; }

        .doctor-select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .doctor-select:hover { 
            border-color: #2563eb; 
        }

        .doctor-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .add-event-btn {
            background: #2563eb;
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .add-event-btn:hover { 
            background: #1d4ed8; 
            color: #fff; 
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: center;
        }

        .stat-number { 
            font-size: 24px; 
            font-weight: 700; 
        }

        .stat-label { 
            font-size: 12px; 
            color: #6b7280; 
            font-weight: 600; 
            text-transform: uppercase; 
        }

        .tooltip-content {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            min-width: 220px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 100;
            color: #111827;
            text-align: left;
        }

        .event-wrapper { 
            position: relative; 
        }

        .event-wrapper:hover .tooltip-content { 
            display: block; 
        }

        .doctor-info-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-top:30px;
        }

        .doctor-info-left h3 {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .doctor-info-left p {
            font-size: 14px;
            color: #6b7280;
        }

        .doctor-info-right {
            text-align: right;
        }

        .doctor-info-right .big-number {
            font-size: 32px;
            font-weight: 700;
            color: #2563eb;
        }

        .doctor-info-right .small-text {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .legend-title {
            font-size: 13px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            margin-right: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .legend-text {
            font-size: 13px;
            color: #111827;
        }

        @media (max-width: 1024px) {
            .calendar-grid { 
                overflow-x: auto; 
            }
            .weekdays, .days-grid { 
                min-width: 800px; 
            }
            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 20px;
            }
            .doctor-info-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .doctor-info-right {
                text-align: left;
                margin-top: 12px;
            }
            .toolbar-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            .toolbar-btn-icon {
                width: 34px;
                height: 34px;
            }
            .month-label {
                font-size: 18px;
                margin-left: 8px;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Header Section -->
    <div class="header-section">
        <?php include('header.php'); ?>
    </div>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Sidebar Section -->
        <div class="sidebar-section">
            <?php include('Sidebar.php'); ?>
        </div>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="max-w-full">
                
                

                 <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <a href="dashboard.php" class="p-2 border rounded-md hover:bg-gray-100 transition">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight"> Appointment Calendar</h1>
                            </div>
                        </div>
                    </div>

                <!-- Doctor Info Card -->
                <div class="doctor-info-card">
                    <div class="doctor-info-left">
                        <h3> <?php echo htmlspecialchars($doctor_name); ?></h3>
                        <p><?php echo htmlspecialchars($doctor['specialization'] ?? 'Medical Professional'); ?></p>
                    </div>
                    <div class="doctor-info-right">
                        <div class="big-number"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="small-text">Appointments This Month</div>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number text-blue-600"><?php echo $stats['scheduled'] ?? 0; ?></div>
                        <div class="stat-label">Scheduled</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-green-600"><?php echo $stats['completed'] ?? 0; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-red-600"><?php echo $stats['cancelled'] ?? 0; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>

                <!-- Calendar Card -->
                <div class="calendar-card">
                    <div class="calendar-toolbar">
                        <div class="toolbar-left">
                            <a class="toolbar-btn-icon" href="?id=<?php echo $doctor_id; ?>&date=<?php echo $prevYear . '-' . sprintf('%02d', $prevMonth) . '-01'; ?>" title="Previous Month">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <a class="toolbar-btn" href="?id=<?php echo $doctor_id; ?>&date=<?php echo date('Y-m-d'); ?>">
                                <i class="fa-regular fa-calendar-check"></i> Today
                            </a>
                            <a class="toolbar-btn-icon" href="?id=<?php echo $doctor_id; ?>&date=<?php echo $nextYear . '-' . sprintf('%02d', $nextMonth) . '-01'; ?>" title="Next Month">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                            <span class="month-label">
                                <?php echo $months[$month] . " " . $year; ?>
                            </span>
                        </div>
                        <div class="toolbar-right">
                            <select class="doctor-select" onchange="window.location.href='?id='+this.value+'&date=<?php echo $date; ?>'">
                                <option value="">-- Select Doctor --</option>
                                <?php 
                                $all_doctors_result->data_seek(0);
                                while($doc = $all_doctors_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $doc['doctor_id']; ?>" <?php echo $doc['doctor_id'] == $doctor_id ? 'selected' : ''; ?>>
                                         <?php echo htmlspecialchars($doc['doctor_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <a href="appointments.php" class="add-event-btn">
                                <i class="fa-solid fa-plus"></i> New Appointment
                            </a>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <div class="weekdays">
                            <?php foreach($days as $d): ?>
                                <div class="weekday"><?php echo $d; ?></div>
                            <?php endforeach; ?>
                        </div>

                        <div class="days-grid">
                            <?php
                            // Empty cells before first day
                            for ($i = 0; $i < $startDay; $i++) {
                                echo "<div class='day-cell empty'></div>";
                            }

                            $currentDay = 1;
                            while ($currentDay <= $daysInMonth) {
                                $fullDate = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($currentDay, 2, "0", STR_PAD_LEFT);
                                
                                $class = "day-cell";
                                if ($fullDate == $today) {
                                    $class .= " today";
                                }
                                
                                $hasAppointments = isset($appointments_by_date[$fullDate]);
                                ?>
                                
                                <div class="<?php echo $class; ?>" data-date="<?php echo $fullDate; ?>">
                                    <span class="day-number"><?php echo $currentDay; ?></span>
                                    
                                    <?php if ($hasAppointments): ?>
                                        <?php foreach ($appointments_by_date[$fullDate] as $appointment): 
                                            $statusClass = 'event-status-' . strtolower($appointment['status']);
                                            $time = date("g:i A", strtotime($appointment['appointment_time']));
                                            $patientName = $appointment['patient_display_name'] ?? 'Unknown Patient';
                                        ?>
                                            <div class="event-wrapper">
                                                <div class="event <?php echo $statusClass; ?>" 
                                                     onclick="window.location.href='view_appointment.php?id=<?php echo $appointment['appointment_id'] ?? $appointment['id']; ?>'">
                                                    <?php echo $time; ?> - <?php echo htmlspecialchars(substr($patientName, 0, 15)); ?>
                                                </div>
                                                
                                                <!-- Tooltip -->
                                                <div class="tooltip-content">
                                                    <div class="flex flex-col gap-2">
                                                        <div class="border-b pb-2">
                                                            <p class="font-bold text-sm"><?php echo htmlspecialchars($patientName); ?></p>
                                                            <p class="text-xs text-gray-500"><?php echo $appointment['patient_phone'] ?? 'No phone'; ?></p>
                                                        </div>
                                                        <div>
                                                            <p><strong class="text-xs">Time:</strong> <span class="text-xs"><?php echo $time; ?></span></p>
                                                            <p><strong class="text-xs">Status:</strong> <span class="text-xs"><?php echo $appointment['status']; ?></span></p>
                                                            <p><strong class="text-xs">Dept:</strong> <span class="text-xs"><?php echo htmlspecialchars($appointment['department_name'] ?? 'N/A'); ?></span></p>
                                                        </div>
                                                        <div class="flex gap-2 mt-2 pt-2 border-t">
                                                            <a href="view_appointment.php?id=<?php echo $appointment['appointment_id'] ?? $appointment['id']; ?>" class="text-xs text-blue-600 hover:underline font-bold">View</a>
                                                            <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id'] ?? $appointment['id']; ?>" class="text-xs text-green-600 hover:underline font-bold">Edit</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $currentDay++;
                            }

                            // Fill remaining cells
                            $totalCells = $startDay + $daysInMonth;
                            $remainingDays = ($totalCells > 35) ? 42 - $totalCells : 35 - $totalCells;
                            for ($i = 0; $i < $remainingDays; $i++) {
                                echo "<div class='day-cell empty'></div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="legend">
                    <span class="legend-title">Status Legend:</span>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #2563eb;"></span>
                        <span class="legend-text">Scheduled</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #16a34a;"></span>
                        <span class="legend-text">Completed</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #dc2626;"></span>
                        <span class="legend-text">Cancelled</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #dbeafe; border: 2px solid #2563eb;"></span>
                        <span class="legend-text">Today</span>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

</body>
<script>
      lucide.createIcons();
</script>
</html>
