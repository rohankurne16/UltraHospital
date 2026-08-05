<?php
session_start();
include('../config/hospital.php');

if (!isset($_SESSION['hospital_id'])) {
    header("Location: ../index.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get nurse name
$nurseName = '';
$nurseQuery = "SELECT name FROM register WHERE id = " . $_SESSION['id'] . " AND (delete_flag=0 OR delete_flag IS NULL)";
$nurseResult = mysqli_query($conn, $nurseQuery);
if ($nurseResult && mysqli_num_rows($nurseResult) > 0) {
    $nurseData = mysqli_fetch_assoc($nurseResult);
    $nurseName = $nurseData['name'] ?? '';
}

// Get Filters from URL
$view = isset($_GET['view']) ? $_GET['view'] : 'month';
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$timestamp = strtotime($currentDate);

// Date Conditions Logic
if($view == "day"){
    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 day'));
    $title = date('d M Y', $timestamp);
    $dateCondition = "DATE(s.surgery_date)='".date('Y-m-d',$timestamp)."'";
}
elseif($view == "week"){
    $prevDate = date('Y-m-d', strtotime($currentDate.' -7 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +7 day'));
    $weekStart = date('d M', strtotime('monday this week', $timestamp));
    $weekEnd   = date('d M Y', strtotime('sunday this week', $timestamp));
    $title = $weekStart." - ".$weekEnd;
    $dateCondition = "YEARWEEK(s.surgery_date,1)=YEARWEEK('$currentDate',1)";
}
else{
    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 month'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 month'));
    $title = date('F Y', $timestamp);
    $dateCondition = "MONTH(s.surgery_date)='".date('m',$timestamp)."' AND YEAR(s.surgery_date)='".date('Y',$timestamp)."'";
}

// Get counts for tabs
$statusCounts = ['all' => 0, 'Scheduled' => 0, 'Completed' => 0, 'Cancelled' => 0];
$countSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN s.status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_count,
                SUM(CASE WHEN s.status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN s.status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
             FROM surgeries s
             WHERE s.hospital_id='$hospital_id' AND s.delete_flag=0 AND ($dateCondition)";
$countRes = mysqli_query($conn, $countSql);
if ($countRes) {
    $counts = mysqli_fetch_assoc($countRes);
    $statusCounts['all'] = $counts['total'] ?? 0;
    $statusCounts['Scheduled'] = $counts['scheduled_count'] ?? 0;
    $statusCounts['Completed'] = $counts['completed_count'] ?? 0;
    $statusCounts['Cancelled'] = $counts['cancelled_count'] ?? 0;
}

// Main Query
$query = "
SELECT
    s.*,
    p.patient_name,
    d.doctor_name
FROM surgeries s
LEFT JOIN patients p ON s.patient_id = p.patient_id
LEFT JOIN doctor d ON s.doctor_id = d.doctor_id
WHERE s.hospital_id = '$hospital_id'
AND s.delete_flag = 0
AND ($dateCondition)
";

if ($status_filter !== 'all') {
    $query .= " AND s.status = '$status_filter'";
}

if (!empty($search_term)) {
    $safe_search = mysqli_real_escape_string($conn, $search_term);
    $query .= " AND (s.surgery_title LIKE '%$safe_search%' OR p.patient_name LIKE '%$safe_search%' OR d.doctor_name LIKE '%$safe_search%')";
}

$query .= " ORDER BY s.surgery_date DESC, s.surgery_time DESC";
$resu = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surgeries List | <?php echo $hospital['hospital_name'] ?></title>
 
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        @media (min-width: 1280px) {
            .xl-ml-64 { margin-left: 16rem; }
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .bg-scheduled { background: #fef3c7; color: #b45309; }
        .bg-completed { background: #dcfce7; color: #15803d; }
        .bg-cancelled { background: #fecaca; color: #b91c1c; }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            transition: all 0.2s ease;
        }
        .back-btn:hover { background: #f3f4f6; }

        .nav-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: white;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            transition: all 0.2s ease;
        }
        .nav-arrow:hover { background: #f3f4f6; }

        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background: #f8fafc;
        }

        /* ===== TAB STYLES ===== */
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #64748b;
            text-decoration: none;
            user-select: none;
            white-space: nowrap;
        }
        .tab-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .tab-btn .badge-count {
            background: #e2e8f0;
            color: #64748b;
            padding: 1px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .tab-active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .tab-active:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-color: #2563eb;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
        }
        .tab-active .badge-count {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        .tab-inactive {
            background: #fff;
            color: #64748b;
            border-color: #e5e7eb;
        }
        .tab-inactive .badge-count {
            background: #e2e8f0;
            color: #64748b;
        }
        .tabs-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding: 16px 9px;
            margin-bottom: -31px;
        }
        .tabs-wrapper::-webkit-scrollbar {
            height: 4px;
        }
        .tabs-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .tabs-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .tabs-wrapper .flex-wrap {
            flex-wrap: nowrap;
        }
        @media (min-width: 640px) {
            .tabs-wrapper .flex-wrap {
                flex-wrap: wrap;
            }
        }
        /* ===== END TAB STYLES ===== */
    </style>
</head>

<body class="bg-gray-50 text-gray-900">

<?php include '../header.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50">
    <div class="flex flex-1 items-start">
        <?php include '../Sidebar.php'; ?>

        <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl-ml-64">
            <div class="flex flex-col gap-5">
                
                <!-- Header Section -->
                <div class="flex flex-col gap-4">

                    <!-- Title -->
                    <div class="flex items-center gap-4">
                        <a href="dashboard.php" class="back-btn shrink-0">
                            <i data-lucide="arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold">
                                Surgeries List
                            </h1>
                            <p class="text-gray-500 text-xs md:text-sm">Welcome, <?php echo htmlspecialchars($nurseName ?: 'Nurse'); ?> - View all hospital surgeries.</p>
                        </div>
                    </div>

                    <!-- Date + View Filter Box -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">

                        <div class="flex flex-col md:flex-row items-center justify-between gap-4">

                            <!-- Date Navigation -->
                            <div class="flex items-center gap-2">
                                <a href="?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo $search_term; ?>"
                                   class="nav-arrow">
                                    <i data-lucide="chevron-left" size="16"></i>
                                </a>

                                <span class="text-sm font-semibold text-gray-700 px-3">
                                    <?php echo $title; ?>
                                </span>

                                <a href="?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo $search_term; ?>"
                                   class="nav-arrow">
                                    <i data-lucide="chevron-right" size="16"></i>
                                </a>
                            </div>

                            <!-- Day Week Month -->
                            <div class="flex bg-gray-100 p-1 rounded-lg">
                                <a href="?view=day&date=<?php echo $currentDate; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo $search_term; ?>"
                                   class="px-4 py-2 text-sm rounded-md <?php echo $view=='day' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-white'; ?>">
                                    Day
                                </a>
                                <a href="?view=week&date=<?php echo $currentDate; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo $search_term; ?>"
                                   class="px-4 py-2 text-sm rounded-md <?php echo $view=='week' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-white'; ?>">
                                    Week
                                </a>
                                <a href="?view=month&date=<?php echo $currentDate; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo $search_term; ?>"
                                   class="px-4 py-2 text-sm rounded-md <?php echo $view=='month' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-white'; ?>">
                                    Month
                                </a>
                            </div>

                        </div>

                    </div>

                    <!-- Search Box -->
                    <div class="flex justify-end">
                        <form action="" method="GET" class="relative w-full sm:w-64">
                            <input type="hidden" name="view" value="<?php echo $view; ?>">
                            <input type="hidden" name="date" value="<?php echo $currentDate; ?>">
                            <input type="hidden" name="status_filter" value="<?php echo $status_filter; ?>">

                            <input type="text"
                                   name="search"
                                   placeholder="Search surgeries..."
                                   value="<?php echo $search_term; ?>"
                                   class="w-full pl-10 pr-4 py-2 border rounded-lg shadow-sm text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">

                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i data-lucide="search" size="18"></i>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- Table Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    <!-- ===== TABS ===== -->
                    <div class="tabs-wrapper">
                        <div class="flex flex-wrap gap-2" style="flex-wrap: nowrap;">
                            <div class="tab-btn <?php echo ($status_filter=='all')?'tab-active':'tab-inactive'; ?>" 
                                 onclick="window.location.href='?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&status_filter=all&search=<?php echo $search_term; ?>'">
                                All <span class="badge-count"><?php echo $statusCounts['all']; ?></span>
                            </div>
                            <div class="tab-btn <?php echo ($status_filter=='Scheduled')?'tab-active':'tab-inactive'; ?>" 
                                 onclick="window.location.href='?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&status_filter=Scheduled&search=<?php echo $search_term; ?>'">
                                Scheduled <span class="badge-count"><?php echo $statusCounts['Scheduled']; ?></span>
                            </div>
                            <div class="tab-btn <?php echo ($status_filter=='Completed')?'tab-active':'tab-inactive'; ?>" 
                                 onclick="window.location.href='?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&status_filter=Completed&search=<?php echo $search_term; ?>'">
                                Completed <span class="badge-count"><?php echo $statusCounts['Completed']; ?></span>
                            </div>
                            <div class="tab-btn <?php echo ($status_filter=='Cancelled')?'tab-active':'tab-inactive'; ?>" 
                                 onclick="window.location.href='?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&status_filter=Cancelled&search=<?php echo $search_term; ?>'">
                                Cancelled <span class="badge-count"><?php echo $statusCounts['Cancelled']; ?></span>
                            </div>
                        </div>
                    </div>
                    <!-- ===== END TABS ===== -->

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 text-gray-500 text-[10px] md:text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 md:px-6 py-4 font-semibold">Surgery</th>
                                    <th class="px-4 md:px-6 py-4 font-semibold hidden sm:table-cell">Patient</th>
                                    <th class="px-4 md:px-6 py-4 font-semibold hidden md:table-cell">Doctor</th>
                                    <th class="px-4 md:px-6 py-4 font-semibold">Schedule</th>
                                    <th class="px-4 md:px-6 py-4 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if(mysqli_num_rows($resu) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($resu)): 
                                        $status = $row['status'];
                                        $status_class = "bg-gray-100 text-gray-600";
                                        if($status == "Completed") $status_class = "bg-completed";
                                        elseif($status == "Scheduled") $status_class = "bg-scheduled";
                                        elseif($status == "Cancelled") $status_class = "bg-cancelled";
                                    ?>
                               
                                    <tr class="clickable-row" onclick="window.location='view_surgery.php?id=<?php echo $row['surgery_id']; ?>'">
                                        <td class="px-4 md:px-6 py-4">
                                            <div class="text-sm font-semibold text-gray-900 truncate max-w-[120px] md:max-w-none"><?php echo htmlspecialchars($row['surgery_title']); ?></div>
                                            <div class="text-[10px] text-gray-500 sm:hidden">P: <?php echo htmlspecialchars($row['patient_name']); ?></div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 hidden sm:table-cell">
                                            <div class="text-sm font-medium text-blue-600"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 hidden md:table-cell">
                                            <div class="text-sm text-gray-700 flex items-center gap-1">
                                                <i data-lucide="user-round" size="14"></i>
                                                <?php echo htmlspecialchars($row['doctor_name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4">
                                            <div class="text-xs md:text-sm text-gray-900"><?php echo date("d M y", strtotime($row['surgery_date'])); ?></div>
                                            <div class="text-[10px] md:text-xs text-gray-500"><?php echo date("h:i A", strtotime($row['surgery_time'])); ?></div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4">
                                            <span class="status-badge text-[10px] md:text-xs">
                                                <span class="<?php echo $status_class; ?> px-2 py-1 rounded-full"><?php echo $status; ?></span>
                                            </span>
                                        </td>
                                    </tr>
                                    
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <i data-lucide="inbox" size="40" class="text-gray-300 mb-2"></i>
                                                <p class="text-gray-500 text-sm">No surgeries found</p>
                                                <p class="text-gray-400 text-xs mt-1">No surgeries available for the selected period.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Stats -->
                <div class="flex flex-wrap justify-between items-center gap-3 text-sm text-gray-500">
                    <span>Showing <span id="visibleCount"><?php echo mysqli_num_rows($resu); ?></span> surgery/surgeries</span>
                    <span>Last updated: <?php echo date('d M Y, h:i A'); ?></span>
                </div>

                <footer class="mt-8 text-center text-gray-400 text-[10px] md:text-xs pb-6">
                    &copy; <?php echo date('Y'); ?> Hospital Management System
                </footer>
            </div>
        </main>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>