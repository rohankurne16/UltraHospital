<?php
session_start();
include('../config/hospital.php');

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$date = $_GET['date'] ?? date('Y-m-d');

$month = date('n', strtotime($date));
$year  = date('Y', strtotime($date));

$today = date('Y-m-d');

$months = [
    1 => "January", 2 => "February", 3 => "March", 4 => "April",
    5 => "May", 6 => "June", 7 => "July", 8 => "August",
    9 => "September", 10 => "October", 11 => "November", 12 => "December"
];

$days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date("t", $firstDay);
$startDay = date("w", $firstDay);

$prevMonth = $month - 1;
$prevYear = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Get user role
$user_id = $_SESSION['id'];
$user_sql = "SELECT * FROM register WHERE id = '$user_id'";
$user_result = $conn->query($user_sql);
$user_role = '';
if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_role = isset($user['role']) ? $user['role'] : '';
}

// Get doctor_id if user is doctor
$doctor_id = 0;
if ($user_role == 'doctor' || $user_role == 'Doctor') {
    $doctor_sql = "SELECT doctor_id FROM doctor WHERE register_id = '$user_id'";
    $doctor_result = $conn->query($doctor_sql);
    if ($doctor_result && $doctor_result->num_rows > 0) {
        $doctor = $doctor_result->fetch_assoc();
        $doctor_id = isset($doctor['doctor_id']) ? $doctor['doctor_id'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="icon" type="image/png" href="../<?php echo isset($hospital['hospital_logo']) ? $hospital['hospital_logo'] : ''; ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f4f6f9;
            font-family: 'Inter', Arial, Helvetica, sans-serif;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
        }
        
        .calendar-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
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
            transition: .3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .toolbar-btn {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .toolbar-btn-icon {
            width: 38px;
            height: 38px;
        }
        
        .toolbar-btn:hover,
        .toolbar-btn-icon:hover {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        
        .add-event-btn {
            padding: 8px 20px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-event-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,.3);
        }
        
        .month-label {
            font-size: 24px;
            font-weight: 700;
            margin-left: 10px;
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
        }
        
        .day-cell {
            position: relative;
            height: 120px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: .3s;
        }
        
        .day-cell:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 8px rgba(37,99,235,.1);
        }
        
        .day-cell.active {
            height: 280px;
            overflow-y: auto;
            border: 2px solid #2563eb;
            box-shadow: 0 10px 20px rgba(0,0,0,.15);
            z-index: 10;
            position: relative;
        }
        
        .empty {
            background: #f9fafb;
            cursor: default;
        }
        
        .empty:hover {
            border-color: #e5e7eb;
            box-shadow: none;
        }
        
        .today {
            background: #dbeafe;
            border: 2px solid #2563eb;
        }
        
        .day-number {
            float: right;
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }
        
        .event {
            margin-top: 4px;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 10px;
            line-height: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
        }
        
        .day-cell.active .event {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            padding: 6px 8px;
            font-size: 12px;
            line-height: 18px;
        }
        
        .event-appointment { background: #2563eb; }
        .event-opd { background: #16a34a; }
        .event-ipd { background: #7c3aed; }
        .event-custom { background: #f59e0b; }
        .event-other { background: #dc2626; }
        
        .event-label {
            font-weight: 600;
            margin-right: 4px;
        }
        
        .day-cell::-webkit-scrollbar {
            width: 5px;
        }
        
        .day-cell::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        .day-cell::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .event-count {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        @media (max-width: 1024px) {
            .calendar-card {
                overflow-x: auto;
            }
            .weekdays,
            .days-grid {
                min-width: 950px;
            }
        }
        
        @media (max-width: 768px) {
            .month-label {
                font-size: 18px;
            }
            .toolbar-btn {
                padding: 6px 10px;
                font-size: 12px;
            }
            .toolbar-btn-icon {
                width: 34px;
                height: 34px;
            }
            .day-cell {
                height: 100px;
            }
            .page-title {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="min-h-screen bg-gray-50 dark:bg-[#131212]">
        <?php include('header.php'); ?>
        <div class="flex flex-1 items-start">
            <?php include('Sidebar.php'); ?>
            <main class="xl:ml-64 p-6 pt-24 w-full">
                <div class="page-header">
                    <h2 class="page-title">
                        <i class="fa-solid fa-calendar-days"></i> Calendar
                    </h2>
                </div>

                <div class="calendar-card">
                    <div class="calendar-toolbar">
                        <div class="toolbar-left">
                            <a class="toolbar-btn-icon" href="?date=<?php echo $prevYear . '-' . sprintf('%02d', $prevMonth) . '-01'; ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <a class="toolbar-btn" href="?date=<?php echo date('Y-m-d'); ?>">
                                Today
                            </a>
                            <a class="toolbar-btn-icon" href="?date=<?php echo $nextYear . '-' . sprintf('%02d', $nextMonth) . '-01'; ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                            <span class="month-label">
                                <?php echo $months[$month] . " " . $year; ?>
                            </span>
                        </div>
                        <div class="toolbar-right">
                            <a href="add_event.php" class="add-event-btn">
                                <i class="fa-solid fa-plus"></i> Add Event
                            </a>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <div class="weekdays">
                            <?php foreach ($days as $d) {
                                echo "<div class='weekday'>$d</div>";
                            } ?>
                        </div>

                        <div class="days-grid">
                            <?php
                            for ($i = 0; $i < $startDay; $i++) {
                                echo "<div class='day-cell empty'></div>";
                            }

                            $currentDay = 1;
                            $currentWeekDay = $startDay;

                            while ($currentDay <= $daysInMonth) {
                                $fullDate = $year . "-" .
                                    str_pad($month, 2, "0", STR_PAD_LEFT) . "-" .
                                    str_pad($currentDay, 2, "0", STR_PAD_LEFT);

                                $class = "day-cell";
                                if ($fullDate == $today) {
                                    $class .= " today";
                                }

                                echo "<div class='$class' data-date='$fullDate'>";
                                echo "<span class='day-number'>$currentDay</span>";

                                $event_count = 0;

                                // 1. Get Custom Events
                                $event_sql = "SELECT event_name FROM add_events WHERE event_date='$fullDate'";
                                $event_result = mysqli_query($conn, $event_sql);
                                while ($row = mysqli_fetch_assoc($event_result)) {
                                    echo "<div class='event event-custom'>";
                                    echo "<span class='event-label'>📌</span> " . htmlspecialchars($row['event_name']);
                                    echo "</div>";
                                    $event_count++;
                                }

                                // 2. Get Appointments (for all users, filter by doctor if logged in as doctor)
                                $appointment_sql = "SELECT a.appointment_time, a.patient_name, a.status 
                                                    FROM appointments a 
                                                    WHERE a.appointment_date='$fullDate' 
                                                    AND (a.delete_flag=0 OR a.delete_flag IS NULL)";
                                
                                if ($doctor_id > 0) {
                                    $appointment_sql .= " AND a.doctor_id='$doctor_id'";
                                }
                                
                                $appointment_sql .= " ORDER BY a.appointment_time ASC";

                                $appointment_result = mysqli_query($conn, $appointment_sql);
                                if (mysqli_num_rows($appointment_result) > 0) {
                                    while ($row = mysqli_fetch_assoc($appointment_result)) {
                                        $time = date("h:i A", strtotime($row['appointment_time']));
                                        echo "<div class='event event-appointment'>";
                                        echo "<span class='event-label'>🩺</span> " . $time . " - " . htmlspecialchars($row['patient_name']);
                                        echo "</div>";
                                        $event_count++;
                                    }
                                }

                                // 3. Get OPD Records
                                $opd_sql = "SELECT o.patient_name, o.visit_time, o.visit_date 
                                            FROM opd_records o 
                                            WHERE o.visit_date='$fullDate'";
                                
                                if ($doctor_id > 0) {
                                    $opd_sql .= " AND o.doctor_id='$doctor_id'";
                                }
                                
                                $opd_sql .= " ORDER BY o.visit_time ASC";

                                $opd_result = mysqli_query($conn, $opd_sql);
                                if (mysqli_num_rows($opd_result) > 0) {
                                    while ($row = mysqli_fetch_assoc($opd_result)) {
                                        $time = date("h:i A", strtotime($row['visit_time']));
                                        echo "<div class='event event-opd'>";
                                        echo "<span class='event-label'>🏥</span> OPD: " . $time . " - " . htmlspecialchars($row['patient_name']);
                                        echo "</div>";
                                        $event_count++;
                                    }
                                }

                                // 4. Get IPD (Inpatient) Records
                                $ipd_sql = "SELECT i.patient_name, i.admission_date, i.discharge_date 
                                            FROM ipd_records i 
                                            WHERE i.admission_date='$fullDate'";
                                
                                if ($doctor_id > 0) {
                                    $ipd_sql .= " AND i.doctor_id='$doctor_id'";
                                }

                                $ipd_result = mysqli_query($conn, $ipd_sql);
                                if (mysqli_num_rows($ipd_result) > 0) {
                                    while ($row = mysqli_fetch_assoc($ipd_result)) {
                                        echo "<div class='event event-ipd'>";
                                        echo "<span class='event-label'>🏨</span> IPD: " . htmlspecialchars($row['patient_name']);
                                        echo "</div>";
                                        $event_count++;
                                    }
                                }

                                // Show event count if more than 3 events
                                if ($event_count > 3) {
                                    echo "<div class='event-count'>+$event_count</div>";
                                }

                                echo "</div>";

                                $currentDay++;
                                $currentWeekDay++;
                            }

                            while ($currentWeekDay < 42) {
                                echo "<div class='day-cell empty'></div>";
                                $currentWeekDay++;
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Day cell click handler
            document.querySelectorAll(".day-cell:not(.empty)").forEach(function(cell) {
                cell.addEventListener("click", function() {
                    // Remove active class from all cells
                    document.querySelectorAll(".day-cell").forEach(function(c) {
                        c.classList.remove("active");
                    });
                    // Add active class to clicked cell
                    this.classList.add("active");
                });
            });

            // Close active cell when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.day-cell')) {
                    document.querySelectorAll(".day-cell").forEach(function(c) {
                        c.classList.remove("active");
                    });
                }
            });
        });
    </script>
</body>
</html>