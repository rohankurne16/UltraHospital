<?php 
session_start();
include '../config/hospital.php';
include '../config/permission.php';
checkPermission('prescriptions-view'); 

$doctor_id = $_SESSION['doctor_id'];

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

$hid = $_SESSION["hospital_id"];
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
        $dateCondition = "DATE(pm.created_at)='".date('Y-m-d',$timestamp)."'";
        break;
    case "week":
        $dateCondition = "YEARWEEK(pm.created_at,1)=YEARWEEK('$currentDate',1)";
        break;
    default:
        $dateCondition = "MONTH(pm.created_at)='".date('m',$timestamp)."'
                          AND YEAR(pm.created_at)='".date('Y',$timestamp)."'";
        break;
}

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/logout.php");
    exit();
}

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE prescription_master SET delete_flag = 1 WHERE prescription_id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Prescription deleted successfully!');
            window.location.href='prescriptions.php';
        </script>";
        exit();
    }
}

// ========== COUNT STATISTICS ==========
$totalQuery = "SELECT COUNT(*) AS total FROM prescription_master 
               WHERE (delete_flag=0 OR delete_flag IS NULL) 
               AND hospital_id='$hid' AND doctor_id='$doctor_id'";
$totalResult = $conn->query($totalQuery);
$totalCount = $totalResult->fetch_assoc()['total'] ?? 0;

$todayQuery = "SELECT COUNT(*) AS total FROM prescription_master 
               WHERE (delete_flag=0 OR delete_flag IS NULL) 
               AND hospital_id='$hid' AND doctor_id='$doctor_id' 
               AND DATE(created_at)=CURDATE()";
$todayResult = $conn->query($todayQuery);
$todayCount = $todayResult->fetch_assoc()['total'] ?? 0;

$followQuery = "SELECT COUNT(*) AS total_follow FROM prescription_master 
                WHERE followup_date = CURDATE() + INTERVAL 1 DAY 
                AND (delete_flag=0 OR delete_flag IS NULL) 
                AND hospital_id='$hid' AND doctor_id='$doctor_id'";
$follow = $conn->query($followQuery);
$followCount = $follow->fetch_assoc()['total_follow'] ?? 0;

$weekQuery = "SELECT COUNT(*) AS total FROM prescription_master 
              WHERE (delete_flag=0 OR delete_flag IS NULL) 
              AND hospital_id='$hid' AND doctor_id='$doctor_id' 
              AND YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)";
$weekResult = $conn->query($weekQuery);
$weekCount = $weekResult->fetch_assoc()['total'] ?? 0;

$monthQuery = "SELECT COUNT(*) AS total FROM prescription_master 
               WHERE (delete_flag=0 OR delete_flag IS NULL) 
               AND hospital_id='$hid' AND doctor_id='$doctor_id' 
               AND MONTH(created_at)=MONTH(CURDATE()) 
               AND YEAR(created_at)=YEAR(CURDATE())";
$monthResult = $conn->query($monthQuery);
$monthCount = $monthResult->fetch_assoc()['total'] ?? 0;

// ========== FILTER LOGIC ==========
$filter = isset($_GET['filter']) ? $_GET['filter'] : "all";

if($filter=="today"){
    $prescriptionQuery = "
    SELECT pm.*, pat.patient_name,
    (SELECT GROUP_CONCAT(CONCAT(pd.medicine_name,' - ',pd.dosage,' - ',pd.frequency) SEPARATOR ', ')
     FROM prescription_details pd WHERE pd.prescription_id=pm.prescription_id) AS medicines
    FROM prescription_master pm
    LEFT JOIN patients pat ON pm.patient_id=pat.patient_id
    WHERE DATE(pm.created_at)=CURDATE()
    AND $dateCondition
    AND pm.hospital_id='$hid'
    AND pm.doctor_id='$doctor_id'
    AND (pm.delete_flag=0 OR pm.delete_flag IS NULL)
    ORDER BY pm.created_at DESC";
}
elseif($filter=="tomorrow"){
    $prescriptionQuery = "
    SELECT pm.*, pat.patient_name,
    (SELECT GROUP_CONCAT(CONCAT(pd.medicine_name,' - ',pd.dosage,' - ',pd.frequency) SEPARATOR ', ')
     FROM prescription_details pd WHERE pd.prescription_id=pm.prescription_id) AS medicines
    FROM prescription_master pm
    LEFT JOIN patients pat ON pm.patient_id=pat.patient_id
    WHERE pm.followup_date=DATE_ADD(CURDATE(),INTERVAL 1 DAY)
    AND $dateCondition
    AND pm.hospital_id='$hid'
    AND pm.doctor_id='$doctor_id'
    AND (pm.delete_flag=0 OR pm.delete_flag IS NULL)
    ORDER BY pm.created_at DESC";
}
elseif($filter=="week"){
    $prescriptionQuery = "
    SELECT pm.*, pat.patient_name,
    (SELECT GROUP_CONCAT(CONCAT(pd.medicine_name,' - ',pd.dosage,' - ',pd.frequency) SEPARATOR ', ')
     FROM prescription_details pd WHERE pd.prescription_id=pm.prescription_id) AS medicines
    FROM prescription_master pm
    LEFT JOIN patients pat ON pm.patient_id=pat.patient_id
    WHERE YEARWEEK(pm.created_at,1)=YEARWEEK(CURDATE(),1)
    AND pm.hospital_id='$hid'
    AND pm.doctor_id='$doctor_id'
    AND (pm.delete_flag=0 OR pm.delete_flag IS NULL)
    ORDER BY pm.created_at DESC";
}
elseif($filter=="month"){
    $prescriptionQuery = "
    SELECT pm.*, pat.patient_name,
    (SELECT GROUP_CONCAT(CONCAT(pd.medicine_name,' - ',pd.dosage,' - ',pd.frequency) SEPARATOR ', ')
     FROM prescription_details pd WHERE pd.prescription_id=pm.prescription_id) AS medicines
    FROM prescription_master pm
    LEFT JOIN patients pat ON pm.patient_id=pat.patient_id
    WHERE MONTH(pm.created_at)=MONTH(CURDATE())
    AND YEAR(pm.created_at)=YEAR(CURDATE())
    AND pm.hospital_id='$hid'
    AND pm.doctor_id='$doctor_id'
    AND (pm.delete_flag=0 OR pm.delete_flag IS NULL)
    ORDER BY pm.created_at DESC";
}
else{
    $prescriptionQuery = "
    SELECT pm.*, pat.patient_name,
    (SELECT GROUP_CONCAT(CONCAT(pd.medicine_name,' - ',pd.dosage,' - ',pd.frequency) SEPARATOR ', ')
     FROM prescription_details pd WHERE pd.prescription_id=pm.prescription_id) AS medicines
    FROM prescription_master pm
    LEFT JOIN patients pat ON pm.patient_id=pat.patient_id
    WHERE $dateCondition
    AND pm.hospital_id='$hid'
    AND pm.doctor_id='$doctor_id'
    AND (pm.delete_flag=0 OR pm.delete_flag IS NULL)
    ORDER BY pm.created_at DESC";
}

$prescriptionResult = $conn->query($prescriptionQuery);
if (!$prescriptionResult) {
    die("SQL Error: " . $conn->error);
}
$prescriptionCount = $prescriptionResult->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?? 'Hospital'; ?> - Prescriptions</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? ''; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.542.0/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; width: 100%; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) { .main-content { padding: 12px; } }

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
        .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }

        /* ===== TAB STYLES (Same as Appointments) ===== */
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
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: 14px; color: #64748b; font-weight: 500; }
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
        .card-body { padding: 20px 24px; }

        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: none;
        }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-view { background: #e0f2fe; color: #0284c7; }
        .action-btn-view:hover { background: #bae6fd; }
        .action-btn-edit { background: #fff7ed; color: #ea580c; }
        .action-btn-edit:hover { background: #fed7aa; }
        .action-btn-delete { background: #fee2e2; color: #dc2626; }
        .action-btn-delete:hover { background: #fecaca; }
        .action-btn i { width: 16px; height: 16px; }

        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }

        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }

        .stat-cards {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        @media (max-width: 768px) {
            table { min-width: 100%; }
            table thead { display: none; }
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
            table tbody tr td:last-child::before { display: none; }
            table tbody tr td:first-child {
                font-weight: 700;
                font-size: 14px;
                color: #0f172a;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 8px;
                margin-bottom: 4px;
            }
        }

        @media (max-width: 480px) {
            .stat-cards { grid-template-columns: 1fr; }
            .stat-card .stat-number { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
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
                            <a href="create_prescription.php"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>New Prescription</span>
                            </a>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    

                    <!-- Navigation Controls -->
                    <div class="bg-white rounded-xl border shadow-sm p-4 mt-5 mb-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="prescriptions.php?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>"
                                   class="p-2 border rounded-lg hover:bg-gray-100 transition-colors">
                                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                                </a>
                                <a href="prescriptions.php?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>"
                                   class="px-4 py-2 border rounded-lg hover:bg-gray-100 font-medium text-sm transition-colors">
                                    <?php echo $title; ?>
                                </a>
                                <a href="prescriptions.php?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>"
                                   class="p-2 border rounded-lg hover:bg-gray-100 transition-colors">
                                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
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

                    <!-- Prescriptions Table -->
                    <div class="card w-full">
                        <div class="card-header">
                            <h2 class="text-lg md:text-xl font-bold" id="table-title">
                                <?php
                                if($filter=="today") echo "Today's Prescriptions";
                                elseif($filter=="tomorrow") echo "Tomorrow Follow-up Prescriptions";
                                elseif($filter=="week") echo "This Week's Prescriptions";
                                elseif($filter=="month") echo "This Month's Prescriptions";
                                else echo "All Prescriptions";
                                ?>
                            </h2>
                            <input type="text" id="searchInput" placeholder="Search prescriptions..." 
                                   class="w-full sm:w-64 pl-4 pr-4 py-2 text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500"
                                   onkeyup="searchPrescriptions()">
                        </div>

                        <!-- ===== TABS (Same as Appointments) ===== -->
                        <div class="tabs-wrapper">
                            <div class="flex flex-wrap gap-2 mb-6" style="flex-wrap: nowrap;">
                                <div class="tab-btn <?php echo ($filter=='all')?'tab-active':'tab-inactive'; ?>" 
                                     id="tab-all" onclick="window.location.href='?filter=all'">
                                    All <span class="badge-count"><?php echo $totalCount; ?></span>
                                </div>
                                <div class="tab-btn <?php echo ($filter=='today')?'tab-active':'tab-inactive'; ?>" 
                                     id="tab-today" onclick="window.location.href='?filter=today'">
                                    Today <span class="badge-count"><?php echo $todayCount; ?></span>
                                </div>
                                <div class="tab-btn <?php echo ($filter=='tomorrow')?'tab-active':'tab-inactive'; ?>" 
                                     id="tab-tomorrow" onclick="window.location.href='?filter=tomorrow'">
                                    Follow-up <span class="badge-count"><?php echo $followCount; ?></span>
                                </div>
                                <div class="tab-btn <?php echo ($filter=='week')?'tab-active':'tab-inactive'; ?>" 
                                     id="tab-week" onclick="window.location.href='?filter=week'">
                                    Week <span class="badge-count"><?php echo $weekCount; ?></span>
                                </div>
                                <div class="tab-btn <?php echo ($filter=='month')?'tab-active':'tab-inactive'; ?>" 
                                     id="tab-month" onclick="window.location.href='?filter=month'">
                                    Month <span class="badge-count"><?php echo $monthCount; ?></span>
                                </div>
                            </div>
                        </div>
                        <!-- ===== END TABS ===== -->

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
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Follow-up</th>
                                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prescriptionTableBody">
                                        <?php $i = 1; while ($row = $prescriptionResult->fetch_assoc()): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all cursor-pointer"
                                            onclick="window.location='view_prescription.php?id=<?php echo $row['prescription_id']; ?>'">
                                            <td data-label="#" class="px-4 py-3"><?php echo $i++; ?></td>
                                            <td data-label="Date" class="px-4 py-3 font-medium">
                                                <?php echo date('d-m-Y', strtotime($row['created_at'])); ?>
                                            </td>
                                            <td data-label="Patient" class="px-4 py-3 font-medium">
                                                <?php echo htmlspecialchars($row['patient_name']); ?>
                                            </td>
                                            <td data-label="Medicine" class="px-4 py-3">
                                                <?php echo htmlspecialchars($row['medicines']); ?>
                                            </td>
                                            <td data-label="Follow-up" class="px-4 py-3">
                                                <?php echo $row['followup_date'] ? date('d-m-Y', strtotime($row['followup_date'])) : '—'; ?>
                                            </td>
                                            <td data-label="Actions" class="px-4 py-3 text-center" onclick="event.stopPropagation();">
                                                <div class="flex items-center justify-center gap-2">
                                                   
                                                    <a href="edit_prescription.php?id=<?php echo $row['prescription_id']; ?>"
                                                       class="action-btn action-btn-edit" title="Edit">
                                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                       onclick="confirmDelete(<?php echo $row['prescription_id']; ?>)"
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
                        <span>Showing <span id="visibleCount"><?php echo $prescriptionCount; ?></span> prescription(s)</span>
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
            let visibleCount = 0;
            
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                if (text.includes(input)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.getElementById('visibleCount').textContent = visibleCount;
        }

        // Keyboard shortcut for search (Ctrl+F)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });
    </script>
</body>
</html>