<?php 
session_start();
include 'config/hospital.php';
include 'config/permission.php';
checkPermission('prescriptions-view'); 

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

$view = isset($_GET['view']) ? $_GET['view'] : 'month';
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$timestamp = strtotime($currentDate);

if($view == "day"){
    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 day'));
    $title = date('d M Y', $timestamp);
}
elseif($view == "week"){
    $prevDate = date('Y-m-d', strtotime($currentDate.' -7 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +7 day'));
    $weekStart = date('d M', strtotime('monday this week', $timestamp));
    $weekEnd   = date('d M Y', strtotime('sunday this week', $timestamp));
    $title = $weekStart." - ".$weekEnd;
}
else{
    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 month'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 month'));
    $title = date('F Y', $timestamp);
}

switch($view){
    case "day":
        $dateCondition = "DATE(p.created_at)='".date('Y-m-d',$timestamp)."'";
        break;
    case "week":
        $dateCondition = "YEARWEEK(p.created_at,1)=YEARWEEK('$currentDate',1)";
        break;
    default:
        $dateCondition = "MONTH(p.created_at)='".date('m',$timestamp)."'
                          AND YEAR(p.created_at)='".date('Y',$timestamp)."'";
        break;
}

if (!isset($_SESSION['id'])) {
    header("Location: auth/logout.php");
    exit();
}

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE prescriptions SET delete_flag = 1 WHERE id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Prescription deleted successfully!');
            window.location.href='prescriptions.php';
        </script>";
        exit();
    }
}

$totalQuery = "SELECT COUNT(*) AS total FROM prescriptions WHERE (delete_flag=0 OR delete_flag IS NULL)";
$totalResult = $conn->query($totalQuery);
$totalCount = $totalResult->fetch_assoc();

$follow_up = "SELECT COUNT(*) AS total_follow FROM prescriptions WHERE followup_date = CURDATE() + INTERVAL 1 DAY AND (delete_flag = 0 OR delete_flag IS NULL)";
$follow = $conn->query($follow_up);
$followCount = $follow->fetch_assoc();

$todayQuery = "SELECT COUNT(*) AS total FROM prescriptions WHERE (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = CURDATE()";
$todayResult = $conn->query($todayQuery);
$todayCount = $todayResult->fetch_assoc();

$filter = isset($_GET['filter']) ? $_GET['filter'] : "all";
if($filter=="today"){
    $prescriptionQuery = "
        SELECT p.*, pat.patient_name
        FROM prescriptions p
        LEFT JOIN patients pat ON p.patient_id = pat.patient_id
        WHERE DATE(p.created_at)=CURDATE()
        AND $dateCondition
        AND (p.delete_flag=0 OR p.delete_flag IS NULL)
        ORDER BY p.created_at DESC";
}
elseif($filter=="tomorrow"){
    $prescriptionQuery = "
        SELECT p.*, pat.patient_name
        FROM prescriptions p
        LEFT JOIN patients pat ON p.patient_id = pat.patient_id
        WHERE followup_date = DATE_ADD(CURDATE(),INTERVAL 1 DAY)
        AND $dateCondition
        AND (p.delete_flag=0 OR p.delete_flag IS NULL)
        ORDER BY p.created_at DESC";
}
else{
    $prescriptionQuery = "
        SELECT p.*, pat.patient_name
        FROM prescriptions p
        LEFT JOIN patients pat ON p.patient_id = pat.patient_id
        WHERE $dateCondition
        AND (p.delete_flag=0 OR p.delete_flag IS NULL)
        ORDER BY p.created_at DESC";
}

$prescriptionResult = $conn->query($prescriptionQuery);
$prescriptionCount = $prescriptionResult->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Prescriptions</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        
        /* Sidebar and Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }

        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            #sidebar-container.active {
                transform: translateX(0);
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 260px;
            }
        }

        #mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            cursor: pointer;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
            width: 100%;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 12px;
            }
        }

        /* Mobile Toggle Button */
        #mobile-toggle {
            display: none;
        }

        @media (max-width: 1279px) {
            #mobile-toggle {
                display: flex;
            }
        }

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
            flex-shrink: 0;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .back-btn i {
            font-size: 18px;
            line-height: 1;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .card-body {
            padding: 20px 24px;
        }

        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            border-radius: 6px;
        }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-view { color: #3b82f6; }
        .action-btn-view:hover { background: #dbeafe; }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; }

        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }

        /* Responsive Table */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        /* Mobile Card View for Table */
        @media (max-width: 768px) {
            table {
                min-width: 100%;
            }
            
            table thead {
                display: none;
            }
            
            table tbody tr {
                display: block;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                margin-bottom: 12px;
                padding: 12px 14px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }
            
            table tbody tr td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 13px;
                gap: 8px;
                flex-wrap: wrap;
            }
            
            table tbody tr td:last-child {
                border-bottom: none;
                padding-top: 10px;
                justify-content: flex-end;
                gap: 6px;
            }
            
            table tbody tr td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                flex-shrink: 0;
                min-width: 80px;
            }
            
            table tbody tr td:last-child::before {
                display: none;
            }
            
            table tbody tr td:first-child {
                font-weight: 700;
                font-size: 14px;
                color: #0f172a;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 8px;
                margin-bottom: 4px;
            }
            
            table tbody tr td:first-child::before {
                font-weight: 600;
                color: #3b82f6;
            }
        }

        /* Search Input Responsive */
        #searchInput {
            width: 100%;
            max-width: 280px;
        }

        @media (max-width: 640px) {
            #searchInput {
                max-width: 100%;
            }
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
            .card-header h2 {
                font-size: 16px;
            }
        }

        /* Navigation Controls Responsive */
        .nav-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 640px) {
            .nav-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .nav-controls .flex {
                justify-content: center;
            }
            .nav-controls .view-buttons {
                justify-content: center;
            }
        }

        .stat-cards {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        @media (max-width: 480px) {
            .stat-cards {
                grid-template-columns: 1fr;
            }
            .stat-card .stat-number {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
         
                <?php include 'Sidebar.php'; ?>
       
            
            <main class="main-content w-full">
                <div class="w-full">
                    <!-- Page Header -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                               
                                <a href="dashboard.php" class="back-btn">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">Prescriptions</h1>
                                    <p class="text-gray-500 text-xs md:text-sm">Manage patient prescriptions and medications.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Cards -->
                    <div class="stat-cards mb-6">
                        <a href="?filter=all" class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Prescriptions</div>
                                    <div class="stat-number"><?php echo $totalCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-blue-50 text-blue-600">
                                    <i data-lucide="file-text" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </a>
                        <a href="?filter=today" class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Today's Prescriptions</div>
                                    <div class="stat-number"><?php echo $todayCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-green-50 text-green-600">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </a>
                        <a href="?filter=tomorrow" class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Tomorrow's Follow-ups</div>
                                    <div class="stat-number"><?php echo $followCount['total_follow']; ?></div>
                                </div>
                                <div class="stat-icon bg-purple-50 text-purple-600">
                                    <i data-lucide="calendar-check" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Navigation Controls -->
                    <div class="bg-white rounded-xl border shadow-sm p-4 mt-5 mb-5">
                        <div class="nav-controls">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="prescriptions.php?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>"
                                   class="p-2 border rounded-lg hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                         fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="2">
                                        <path d="M15 18l-6-6 6-6"/>
                                    </svg>
                                </a>

                                <a href="prescriptions.php?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>"
                                   class="px-4 py-2 border rounded-lg hover:bg-gray-100 font-medium text-sm">
                                    <?php echo $title; ?>
                                </a>

                                <a href="prescriptions.php?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>"
                                   class="p-2 border rounded-lg hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                         fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="2">
                                        <path d="M9 6l6 6-6 6"/>
                                    </svg>
                                </a>
                            </div>

                            <div class="view-buttons flex rounded-lg border overflow-hidden">
                                <a href="prescriptions.php?view=day&date=<?php echo $currentDate; ?>"
                                   class="px-3 py-2 text-sm <?php echo ($view=='day')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?>">
                                    Day
                                </a>
                                <a href="prescriptions.php?view=week&date=<?php echo $currentDate; ?>"
                                   class="px-3 py-2 text-sm <?php echo ($view=='week')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?>">
                                    Week
                                </a>
                                <a href="prescriptions.php?view=month&date=<?php echo $currentDate; ?>"
                                   class="px-3 py-2 text-sm <?php echo ($view=='month')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?>">
                                    Month
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Table Card -->
                    <div class="card w-full">
                        <div class="card-header">
                            <h2 class="text-lg md:text-xl font-bold">
                                <?php
                                if($filter=="today"){
                                    echo "Today's Prescriptions";
                                }
                                elseif($filter=="tomorrow"){
                                    echo "Tomorrow Follow-up Prescriptions";
                                }
                                else{
                                    echo "All Prescriptions";
                                }
                                ?>
                            </h2>
                            <input type="text" id="searchInput" placeholder="Search prescriptions..." 
                                   class="w-full sm:w-64 pl-4 pr-4 py-2 text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500"
                                   onkeyup="searchPrescriptions()">
                        </div>

                        <div class="card-body overflow-x-auto p-4">
                            <?php if ($prescriptionCount > 0): ?>
                            <div class="table-wrapper">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 bg-gray-50">
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Patient</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Medicine</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Dosage</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Follow-up</th>
                                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prescriptionTableBody">
                                        <?php $i = 1; while ($row = $prescriptionResult->fetch_assoc()): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all cursor-pointer"
                                            onclick="window.location='view_prescription.php?id=<?php echo $row['id']; ?>'">
                                            <td data-label="#" class="px-4 py-3"><?php echo $i++; ?></td>
                                            <td data-label="Date" class="px-4 py-3 font-medium">
                                                <?php echo date('d-m-Y', strtotime($row['created_at'])); ?>
                                            </td>
                                            <td data-label="Patient" class="px-4 py-3 font-medium">
                                                <?php echo htmlspecialchars($row['patient_name']); ?>
                                            </td>
                                            <td data-label="Medicine" class="px-4 py-3">
                                                <?php echo htmlspecialchars($row['medicine_name']); ?>
                                            </td>
                                            <td data-label="Dosage" class="px-4 py-3">
                                                <?php echo htmlspecialchars($row['dosage']); ?>
                                            </td>
                                            <td data-label="Follow-up" class="px-4 py-3">
                                                <?php echo $row['followup_date'] ? date('d-m-Y', strtotime($row['followup_date'])) : '—'; ?>
                                            </td>
                                            <td data-label="Actions" class="px-4 py-3 text-center" onclick="event.stopPropagation();">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="edit_prescription.php?id=<?php echo $row['id']; ?>"
                                                       class="action-btn action-btn-edit" title="Edit">
                                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                       onclick="confirmDelete(<?php echo $row['id']; ?>)"
                                                       class="action-btn action-btn-delete" title="Delete">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="py-12 text-center text-gray-500">
                                <i data-lucide="file-text" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">No prescriptions found</p>
                                <p class="text-sm text-gray-400">Create your first prescription now.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer Stats -->
                    <div class="mt-4 flex flex-wrap justify-between items-center gap-3 text-sm text-gray-500">
                        <span>Showing <?php echo $prescriptionCount; ?> prescription(s)</span>
                        <span>Last updated: <?php echo date('d M Y, h:i A'); ?></span>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this prescription?")) {
                window.location.href = "prescriptions.php?delete_id=" + id;
            }
        }

        function searchPrescriptions() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#prescriptionTableBody tr');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }

        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebarContainer = document.getElementById('sidebar-container');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            function openSidebar() {
                sidebarContainer.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobileToggle) {
                mobileToggle.addEventListener('click', openSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1280) {
                    closeSidebar();
                }
            });
        });
    </script>
</body>
</html>