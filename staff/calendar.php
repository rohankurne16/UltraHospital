<?php
session_start();
include('../config/db.php');

// Check if user is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/login.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
        }

        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .calendar-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .calendar-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            flex-wrap: wrap;
            gap: 12px;
        }

        .toolbar-left {
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
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }

        .toolbar-btn {
            padding: 8px 16px;
        }

        .toolbar-btn-icon {
            width: 38px;
            height: 38px;
        }

        .toolbar-btn:hover,
        .toolbar-btn-icon:hover {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        .month-label {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-left: 8px;
        }

        .calendar-grid {
            padding: 20px 24px;
        }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            margin-bottom: 6px;
        }

        .weekday {
            text-align: center;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
        }

        .days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .day-cell {
            position: relative;
            min-height: 100px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 8px 6px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .day-cell:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59,130,246,0.1);
        }

        .day-cell.active {
            min-height: 200px;
            border: 2px solid #3b82f6;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            z-index: 10;
            position: relative;
            overflow-y: auto;
        }

        .day-cell.active .day-number {
            font-size: 24px;
        }

        .empty {
            background: #f8fafc;
            border-color: #f1f5f9;
        }

        .today {
            background: #eff6ff;
            border: 2px solid #3b82f6;
        }

        .today .day-number {
            color: #3b82f6;
        }

        .day-number {
            float: right;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .event {
            margin-top: 4px;
            background: #3b82f6;
            color: #fff;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 10px;
            line-height: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .event:hover {
            opacity: 0.8;
        }

        .day-cell.active .event {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            padding: 6px 8px;
            font-size: 12px;
            line-height: 16px;
            margin-top: 6px;
        }

        .event:nth-of-type(1) { background: #3b82f6; }
        .event:nth-of-type(2) { background: #22c55e; }
        .event:nth-of-type(3) { background: #f59e0b; }
        .event:nth-of-type(4) { background: #ef4444; }
        .event:nth-of-type(5) { background: #8b5cf6; }

        .custom-event {
            margin-top: 4px;
            background: #8b5cf6;
            color: #fff;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 10px;
            line-height: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .day-cell.active .custom-event {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            padding: 6px 8px;
            font-size: 12px;
            line-height: 16px;
            margin-top: 6px;
        }

        .custom-event:nth-of-type(1) { background: #8b5cf6; }
        .custom-event:nth-of-type(2) { background: #ec4899; }
        .custom-event:nth-of-type(3) { background: #14b8a6; }

        .add-event-btn {
            padding: 8px 20px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .add-event-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .day-cell::-webkit-scrollbar {
            width: 4px;
        }

        .day-cell::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 1024px) {
            .main-content { 
                margin-left: 0; 
                padding: 16px; 
            }
            .calendar-grid {
                overflow-x: auto;
            }
            .weekdays,
            .days-grid {
                min-width: 700px;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 22px;
            }
            .month-label {
                font-size: 18px;
            }
            .toolbar-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            .toolbar-btn-icon {
                width: 34px;
                height: 34px;
            }
            .day-cell {
                min-height: 80px;
            }
            .calendar-toolbar {
                padding: 12px 16px;
            }
            .calendar-grid {
                padding: 12px 16px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 18px;
            }
            .day-cell {
                min-height: 60px;
                padding: 4px;
            }
            .day-number {
                font-size: 14px;
            }
            .event {
                font-size: 8px;
                padding: 2px 4px;
            }
            .weekday {
                font-size: 11px;
                padding: 6px;
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include 'staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <!-- Sidebar -->
            <?php include 'staff_sidebar.php'; ?>

            <!-- Main Content -->
            <main class="main-content">
                <div class="page-header">
                    <div class="page-title">
                        <i data-lucide="calendar" class="w-7 h-7 text-blue-600"></i>
                        Calendar
                    </div>
                </div>

                <div class="calendar-card fade-in">
                    <div class="calendar-toolbar">
                        <div class="toolbar-left">
                            <a class="toolbar-btn-icon" 
                               href="?date=<?php echo $prevYear . '-' . sprintf('%02d', $prevMonth) . '-01'; ?>">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>

                            <a class="toolbar-btn" href="?date=<?php echo date('Y-m-d'); ?>">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                                Today
                            </a>

                            <a class="toolbar-btn-icon" 
                               href="?date=<?php echo $nextYear . '-' . sprintf('%02d', $nextMonth) . '-01'; ?>">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>

                            <span class="month-label">
                                <?php echo $months[$month] . " " . $year; ?>
                            </span>
                        </div>

                        <div class="toolbar-right">
                            <a href="add_event.php" class="add-event-btn">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Add Event
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
                            // Empty cells before first day
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

                                // Check if day has any events
                                $hasEvents = false;
                                
                                // Check appointments
                                $apptCheck = "SELECT COUNT(*) as count FROM appointments 
                                              WHERE appointment_date='$fullDate' 
                                              AND (delete_flag=0 OR delete_flag IS NULL)";
                                $apptCheckResult = mysqli_query($conn, $apptCheck);
                                if ($apptCheckResult) {
                                    $apptCheckRow = mysqli_fetch_assoc($apptCheckResult);
                                    if ($apptCheckRow['count'] > 0) {
                                        $hasEvents = true;
                                    }
                                }

                                // Check add_events
                                $eventCheck = "SELECT COUNT(*) as count FROM add_events WHERE event_date='$fullDate'";
                                $eventCheckResult = mysqli_query($conn, $eventCheck);
                                if ($eventCheckResult) {
                                    $eventCheckRow = mysqli_fetch_assoc($eventCheckResult);
                                    if ($eventCheckRow['count'] > 0) {
                                        $hasEvents = true;
                                    }
                                }

                                echo "<div class='$class' data-date='$fullDate'>";
                                echo "<span class='day-number'>$currentDay</span>";

                                // Add Events from add_events table
                                $eventQuery = "SELECT event_name FROM add_events WHERE event_date='$fullDate'";
                                $eventResult = mysqli_query($conn, $eventQuery);
                                if ($eventResult && mysqli_num_rows($eventResult) > 0) {
                                    while ($row = mysqli_fetch_assoc($eventResult)) {
                                        echo "<div class='custom-event'>" . htmlspecialchars($row['event_name']) . "</div>";
                                    }
                                }

                                // Appointments
                                $sql = "SELECT appointment_time, patient_name, status 
                                        FROM appointments 
                                        WHERE appointment_date='$fullDate' 
                                        AND (delete_flag=0 OR delete_flag IS NULL)
                                        ORDER BY appointment_time ASC";
                                $result = mysqli_query($conn, $sql);

                                if ($result && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $time = date("h:i A", strtotime($row['appointment_time']));
                                        echo "<div class='event'>";
                                        echo $time . " " . htmlspecialchars($row['patient_name']);
                                        echo "</div>";
                                    }
                                }

                                echo "</div>";

                                $currentDay++;
                                $currentWeekDay++;
                            }

                            // Empty cells after last day
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
        lucide.createIcons();

        // Toggle active state on day cells
        document.querySelectorAll(".day-cell").forEach(function(cell) {
            cell.addEventListener("click", function(e) {
                if (this.classList.contains("empty")) return;

                // Close all other active cells
                document.querySelectorAll(".day-cell.active").forEach(function(c) {
                    if (c !== cell) {
                        c.classList.remove("active");
                    }
                });

                this.classList.toggle("active");

                // If active, scroll to make it visible
                if (this.classList.contains("active")) {
                    this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        // Close active cells when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.day-cell')) {
                document.querySelectorAll('.day-cell.active').forEach(function(c) {
                    c.classList.remove('active');
                });
            }
        });

        // Auto-expand today's cell on load
        document.addEventListener('DOMContentLoaded', function() {
            const todayCell = document.querySelector('.day-cell.today');
            if (todayCell) {
                setTimeout(function() {
                    todayCell.classList.add('active');
                }, 300);
            }
        });
    </script>
</body>
</html>