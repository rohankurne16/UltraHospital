<?php
    session_start(); 
    include "config/hospital.php";
    include "config/permission.php";

    checkPermission('patient-view');
   
    $hid = $_SESSION["hospital_id"];

    if(!$hid){

     header('Location:index.php');

    }

    if (!$conn) {
        die("Connection Failed : " . mysqli_connect_error());
    }

    $view = isset($_GET['view']) ? $_GET['view'] : 'month';
    $currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $patient_stage = isset($_GET['patient_stage']) ? $_GET['patient_stage'] : 'all';

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

    // Get counts for each patient stage
    $stageCounts = [
        'all' => 0,
        'Call' => 0,
        'OPD' => 0,
        'IPD' => 0,
        'Referral' => 0
    ];

    $countSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN p.patient_admission_type = 'Call' THEN 1 ELSE 0 END) as call_count,
                    SUM(CASE WHEN p.patient_admission_type = 'OPD' THEN 1 ELSE 0 END) as opd_count,
                    SUM(CASE WHEN p.patient_admission_type = 'IPD' THEN 1 ELSE 0 END) as ipd_count,
                    SUM(CASE WHEN p.patient_admission_type = 'Referral' THEN 1 ELSE 0 END) as referral_count
                 FROM patients p
                 INNER JOIN register r ON p.register_id = r.id
                 WHERE ($dateCondition)
                 AND p.hospital_id='$hid'
                 AND (p.delete_flag IS NULL OR p.delete_flag=0)
                 AND (r.delete_flag IS NULL OR r.delete_flag=0)";
    
    $countResult = $conn->query($countSql);
    if ($countResult && $countResult->num_rows > 0) {
        $counts = $countResult->fetch_assoc();
        $stageCounts['all'] = $counts['total'] ?? 0;
        $stageCounts['Call'] = $counts['call_count'] ?? 0;
        $stageCounts['OPD'] = $counts['opd_count'] ?? 0;
        $stageCounts['IPD'] = $counts['ipd_count'] ?? 0;
        $stageCounts['Referral'] = $counts['referral_count'] ?? 0;
    }

    // FIX: Only show patients with register_id (registered users)
    $base_sql = "SELECT p.*, r.name as register_name, r.email as register_email
                 FROM patients p
                 INNER JOIN register r ON p.register_id = r.id
                 WHERE ($dateCondition)
                 AND p.hospital_id='$hid'
                 AND (p.delete_flag IS NULL OR p.delete_flag=0)
                 AND (r.delete_flag IS NULL OR r.delete_flag=0)";

    // Filter by patient_stage
    if ($patient_stage !== 'all' && !empty($patient_stage)) {
        $base_sql .= " AND p.patient_admission_type = '$patient_stage'";
    }

    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (!empty($search_term)) {
        $search_term = mysqli_real_escape_string($conn, $search_term);
        $sql = $base_sql . " AND p.patient_name LIKE '%$search_term%'";
    } else {
        $sql = $base_sql;
    }

    // Add ORDER BY
    $sql .= " ORDER BY p.created_at DESC";

    $result = $conn->query($sql);

    // Get active filter for tab styling
    $activeTab = $patient_stage;

    
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1' />
    <title>Patient - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
  
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .action-icons {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }
        
        .action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            text-decoration: none;
        }
        
        .action-icon svg {
            width: 18px;
            height: 18px;
        }
        
        .action-icon.view-icon:hover { background: #eff6ff; }
        .action-icon.view-icon svg { color: #3b82f6; }
        
        .action-icon.edit-icon:hover { background: #f5f3ff; }
        .action-icon.edit-icon svg { color: #8b5cf6; }
        
        .action-icon.delete-icon:hover { background: #fef2f2; }
        .action-icon.delete-icon svg { color: #ef4444; }

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
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal-box {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f8fafc;
        }
        
        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        
        .modal-close {
            background: #f1f5f9;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
        }
        
        .modal-close:hover { background: #e2e8f0; }
        
        .btn {
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-label { font-weight: 600; color: #64748b; font-size: 14px; }
        .detail-value { color: #0f172a; font-size: 14px; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef3c7; color: #b45309; }
        
        .registered-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* Tab styles */
        .patient-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 14px;
            color: #6b7280;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .patient-tab:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .patient-tab.active {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }

        .patient-tab .tab-icon {
            width: 18px;
            height: 18px;
        }

        /* Count Box Styles */
        .count-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
            min-width: 100px;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #374151;
        }

        .count-box:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .count-box.active {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .count-box .count-number {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .count-box .count-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .count-box .count-icon {
            width: 24px;
            height: 24px;
            margin-bottom: 4px;
        }

        /* Color variants for count boxes */
        .count-box.total .count-number { color: #3b82f6; }
        .count-box.call .count-number { color: #8b5cf6; }
        .count-box.opd .count-number { color: #06b6d4; }
        .count-box.ipd .count-number { color: #10b981; }
        .count-box.referral .count-number { color: #f59e0b; }

        @media (max-width: 768px) {
            html,
            body {
                height: 100%;
                overflow-x: hidden;
                overflow-y: auto;
            }

            main {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .patient-tab {
                padding: 8px 14px;
                font-size: 12px;
            }
            
            .count-box {
                min-width: 70px;
                padding: 12px 14px;
            }

            .count-box .count-number {
                font-size: 20px;
            }

            .count-box .count-label {
                font-size: 10px;
            }
        }
    </style>
</head>

<body class='bg-gray-50 text-gray-900'>
    <script>
    lucide.createIcons();
</script>
    <div class='flex min-h-screen flex-col bg-gray-50'>
        <?php include 'header.php'; ?> 
        <div class='flex flex-1 items-start'>
            <?php include 'Sidebar.php'; ?>
            <main class='flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64'>
                <div class='flex flex-col gap-5'>
                    <div class='flex flex-col md:flex-row items-center justify-between gap-4'>
                        <div class="flex items-center gap-4">
                            <a href="dashboard.php" class="back-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                            </a>
                            <div>
                                <h1 class='text-2xl lg:text-3xl font-bold tracking-tight'>All Patients</h1>
                                <p class='text-gray-500 text-sm'>Manage your registered patients and their medical records.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Type Count Boxes -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=all" 
                           class="count-box total <?php echo $activeTab == 'all' ? 'active' : ''; ?>">
                            <svg class="count-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span class="count-number"><?php echo $stageCounts['all']; ?></span>
                            <span class="count-label">Total</span>
                        </a>
                        
                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=Call" 
                           class="count-box call <?php echo $activeTab == 'Call' ? 'active' : ''; ?>">
                            <svg class="count-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <span class="count-number"><?php echo $stageCounts['Call']; ?></span>
                            <span class="count-label">Call</span>
                        </a>
                        
                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=OPD" 
                           class="count-box opd <?php echo $activeTab == 'OPD' ? 'active' : ''; ?>">
                            <svg class="count-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="M9 12l2 2 4-4"/>
                            </svg>
                            <span class="count-number"><?php echo $stageCounts['OPD']; ?></span>
                            <span class="count-label">OPD</span>
                        </a>
                        
                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=IPD" 
                           class="count-box ipd <?php echo $activeTab == 'IPD' ? 'active' : ''; ?>">
                            <svg class="count-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                            <span class="count-number"><?php echo $stageCounts['IPD']; ?></span>
                            <span class="count-label">IPD</span>
                        </a>
                        
                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=Referral" 
                           class="count-box referral <?php echo $activeTab == 'Referral' ? 'active' : ''; ?>">
                            <svg class="count-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            <span class="count-number"><?php echo $stageCounts['Referral']; ?></span>
                            <span class="count-label">Referral</span>
                        </a>
                    </div>

                   

                    <div class="bg-white rounded-xl border shadow-sm p-4 mt-2 mb-5 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>&patient_stage=<?php echo $patient_stage; ?>"
                               class="p-2 border rounded-lg hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                                </svg>
                            </a>

                            <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>&patient_stage=<?php echo $patient_stage; ?>"
                               class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                                <?php echo $title; ?>
                            </a>

                            <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>&patient_stage=<?php echo $patient_stage; ?>"
                               class="p-2 border rounded-lg hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </a>
                        </div>

                        <div class="flex rounded-lg border overflow-hidden">
                            <a href="patients.php?view=day&date=<?php echo $currentDate; ?>&patient_stage=<?php echo $patient_stage; ?>"
                               class="px-4 py-2 <?php echo ($view=='day')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?>">
                                Day
                            </a>
                            <a href="patients.php?view=week&date=<?php echo $currentDate; ?>&patient_stage=<?php echo $patient_stage; ?>"
                               class="px-4 py-2 <?php echo ($view=='week')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?>">
                                Week
                            </a>
                            <a href="patients.php?view=month&date=<?php echo $currentDate; ?>&patient_stage=<?php echo $patient_stage; ?>"
                               class="px-4 py-2 <?php echo ($view=='month')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?>">
                                Month
                            </a>
                        </div>
                    </div>

                    <div class='rounded-xl border bg-white shadow-sm overflow-hidden'>
                        <div class='flex flex-col md:flex-row md:items-center md:justify-between p-4 border-b bg-gray-50/50'>
                            <div>
                                <h2 class='text-lg font-semibold text-gray-900'>
                                    <?php 
                                        if ($patient_stage == 'all') {
                                            echo 'All Registered Patients';
                                        } else {
                                            echo ucfirst(strtolower($patient_stage)) . ' Patients';
                                        }
                                    ?>
                                </h2>
                                <div class='text-xs text-gray-500 mt-0.5'>
                                    Showing <?php echo $result->num_rows; ?> patient<?php echo $result->num_rows > 1 ? 's' : ''; ?> 
                                    <?php if ($patient_stage != 'all'): ?>
                                        in <span class="font-medium"><?php echo ucfirst(strtolower($patient_stage)); ?></span> category
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form action="patients.php" method="GET" class="md:mb-0">
                                <input type="hidden" name="view" value="<?php echo $view; ?>">
                                <input type="hidden" name="date" value="<?php echo $currentDate; ?>">
                                <input type="hidden" name="patient_stage" value="<?php echo $patient_stage; ?>">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-1">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                                        </svg>
                                        <input type="text" id="searchInput" name="search" placeholder="Search patient by name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="w-full rounded-lg border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition" onkeyup="searchPatients()">
                                    </div>
                                    
                                    <?php if(!empty($search_term)): ?>
                                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=<?php echo $patient_stage; ?>"
                                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition">
                                            Reset
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <div class='p-0'>
                            <div class='relative w-full overflow-auto'>
                                <table class='w-full caption-bottom text-sm'>
                                    <thead>
                                        <tr class='border-b border-gray-200 bg-gray-50/30'>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Name</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>DOB</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Age</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Blood Group</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Gender</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Stage</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Contact</th>
                                            <th class='h-12 px-4 text-right font-semibold text-gray-600 text-xs uppercase tracking-wider'>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <?php if ($result && $result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $patient_id = $row['patient_id'];
                                                $name = $row['patient_name'];
                                                $dob = $row['date_of_birth'];
                                                $age = $row['age'];
                                                $blood_group = $row['blood_group'];
                                                $gender = $row['gender'];
                                                $email = $row['email'];
                                                $mobile = $row['mobile'];
                                                $status = isset($row['status']) ? $row['status'] : 'Active';
                                                $stage = isset($row['patient_admission_type']) ? $row['patient_admission_type'] : 'OPD';
                                                $status_class = $status == 'Active' ? 'status-active' : 'status-inactive';
                                                $register_name = $row['register_name'] ?? '';
                                                $register_email = $row['register_email'] ?? '';
                                                
                                                // Stage badge color
                                                $stageColors = [
                                                    'Call' => 'bg-purple-100 text-purple-700',
                                                    'OPD' => 'bg-blue-100 text-blue-700',
                                                    'IPD' => 'bg-green-100 text-green-700',
                                                    'Referral' => 'bg-orange-100 text-orange-700'
                                                ];
                                                $stageColor = $stageColors[$stage] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                        <tr class="patient-row border-b border-gray-50 hover:bg-gray-50/50 transition" data-name="<?php echo strtolower($name); ?>" onclick="window.location.href='view_patient.php?id=<?php echo $patient_id; ?>'">
                                            <td class='p-4 align-middle'>
                                                <div class='flex items-center gap-3'>
                                                    <?php 
                                                        $img_path = $row['patient_image'];
                                                        if (!empty($img_path) && file_exists($img_path)): 
                                                    ?>
                                                        <img src="<?php echo $img_path; ?>" class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                                                    <?php else: ?>
                                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs border border-blue-200">
                                                            <?php echo strtoupper(substr($name, 0, 2)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <p class='font-medium text-gray-900'><?php echo htmlspecialchars($name); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($dob); ?></td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($age); ?></td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($blood_group); ?></td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($gender); ?></td>
                                            <td class='p-4 align-middle'>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $stageColor; ?>">
                                                    <?php echo htmlspecialchars($stage); ?>
                                                </span>
                                            </td>
                                            <td class='p-4 align-middle'>
                                                <div class='text-gray-700 font-medium'><?php echo htmlspecialchars($mobile); ?></div>
                                                <div class='text-xs text-gray-400'><?php echo htmlspecialchars($email); ?></div>
                                            </td>
                                            <td class='p-4 align-middle text-right'>
                                                <div class='action-icons'>
                                                    <a href='view_patient.php?id=<?php echo $patient_id; ?>' class='action-icon view-icon' title='View Patient'>
                                                         
                                                    </a>
                                                    <a href='update_patient.php?id=<?php echo $patient_id; ?>' class='action-icon edit-icon' title='Edit Patient'>
                                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <path d='M12 20h9'></path>
                                                            <path d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'></path>
                                                        </svg>
                                                    </a>
                                                    <a href='delete_patient.php?id=<?php echo $patient_id; ?>' class='action-icon delete-icon' title='Delete Patient' onclick='return confirm("Are you sure you want to delete this patient?")'>
                                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <polyline points='3 6 5 6 21 6'></polyline>
                                                            <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
                                                            <line x1='10' y1='11' x2='10' y2='17'></line>
                                                            <line x1='14' y1='11' x2='14' y2='17'></line>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php }
                                        } else { ?>
                                        <tr id="noPatientsRow">
                                            <td colspan='8' class='p-16 text-center text-gray-400'>
                                                <div class='flex flex-col items-center gap-3'>
                                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-300">
                                                        <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'>
                                                            <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>
                                                            <circle cx='12' cy='7' r='4'></circle>
                                                        </svg>
                                                    </div>
                                                    <span class='font-semibold text-gray-900'>No <?php echo $patient_stage != 'all' ? ucfirst(strtolower($patient_stage)) : ''; ?> Patients Found</span>
                                                    <span class='text-sm max-w-xs mx-auto'>
                                                        <?php echo !empty($search_term) ? "No results matching \"" . htmlspecialchars($search_term) . "\". Try a different search term." : "Click \"Add Patient\" to register a new patient."; ?>
                                                    </span>
                                                    <?php if (!empty($search_term)): ?>
                                                        <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=<?php echo $patient_stage; ?>" class="mt-2 text-blue-600 hover:underline text-sm font-medium">Clear search</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/30 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>
                                Showing <span class="font-medium text-gray-700" id="rowCount"><?php echo $result->num_rows; ?></span> patient<?php echo $result->num_rows > 1 ? 's' : ''; ?>
                                <?php if (!empty($search_term)): ?>
                                    matching "<span class="font-medium text-gray-700"><?php echo htmlspecialchars($search_term); ?></span>"
                                <?php endif; ?>
                                <?php if ($patient_stage != 'all'): ?>
                                    in <span class="font-medium text-gray-700"><?php echo ucfirst(strtolower($patient_stage)); ?></span> category
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function searchPatients() {
            const input = document.getElementById("searchInput").value.toLowerCase().trim();
            const rows = document.querySelectorAll(".patient-row");
            const tableBody = document.getElementById("tableBody");
            const rowCountSpan = document.getElementById("rowCount");

            let visible = 0;

            rows.forEach(row => {
                const name = row.getAttribute("data-name");
                if (name.includes(input)) {
                    row.style.display = "";
                    visible++;
                } else {
                    row.style.display = "none";
                }
            });

            const oldRow = document.getElementById("noPatientsRow");
            if (oldRow) oldRow.remove();

            if (visible === 0) {
                tableBody.insertAdjacentHTML("beforeend", `
                    <tr id="noPatientsRow">
                        <td colspan="8" class="p-16 text-center text-gray-400">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-900">No Patients Found</span>
                                <span class="text-sm max-w-xs mx-auto">No results matching "${input}".</span>
                                <a href="patients.php?view=<?php echo $view; ?>&date=<?php echo $currentDate; ?>&patient_stage=<?php echo $patient_stage; ?>" class="mt-2 text-blue-600 hover:underline text-sm font-medium">Clear search</a>
                            </div>
                        </td>
                    </tr>
                `);
            }

            if (rowCountSpan) {
                rowCountSpan.textContent = visible;
            }
        }
    </script>
</body>
</html>