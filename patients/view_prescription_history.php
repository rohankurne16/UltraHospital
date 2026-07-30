<?php 
    session_start();
    include '../config/hospital.php';

    if (!$conn) {
        die("Connection Failed : " . mysqli_connect_error());
    }

    $view = isset($_GET['view']) ? $_GET['view'] : 'month';
    $currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    $patient_id = $_GET['id'];

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
        $deleteQuery = "
      UPDATE prescription_master
SET delete_flag = 1
WHERE prescription_id = '$delete_id'
        ";
        
        if ($conn->query($deleteQuery)) {
            echo "<script>
                alert('Prescription deleted successfully!');
                window.location.href='view_prescription_history.php';
            </script>";
            exit();
        }
    }

    $prescriptionQuery = "SELECT
    pm.*,
    pat.patient_name,
    GROUP_CONCAT(pd.medicine_name SEPARATOR ', ') AS medicines
FROM prescription_master pm
LEFT JOIN patients pat
    ON pm.patient_id = pat.patient_id
LEFT JOIN prescription_details pd
    ON pm.prescription_id = pd.prescription_id
WHERE pm.patient_id = '$patient_id'
AND (pm.delete_flag = 0 OR pm.delete_flag IS NULL)
AND $dateCondition
GROUP BY pm.prescription_id
ORDER BY pm.created_at DESC
  ";

    $prescriptionResult = $conn->query($prescriptionQuery);
    $prescriptionCount = $prescriptionResult->num_rows;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charSet="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $hospital['hospital_name'] ?> - Prescription History</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <link rel="preload" href="_next/static/media/83afe278b6a6bb3c-s.p.3a6ba036.woff2" as="font" crossorigin="" type="font/woff2" />
    <link rel="stylesheet" href="_next/static/chunks/4fbfc6079ef7eaf2.css" data-precedence="next" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #f1f5f9; 
            color: #0f172a;
        }
        
        .sidebar-active { 
            background-color: #f3f4f6; 
            color: #111827; 
        }
        
        .main-content { 
            margin-left: 260px; 
            padding: 24px 32px; 
            min-height: 100vh; 
            background: #f8fafc;
        }
        
        /* Modern Card Design */
        .card-modern {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .card-modern:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }
        
        .card-header-custom {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #fafbfc;
        }
        
        .card-header-custom h2 {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-body-custom {
            padding: 20px 24px;
            background: #ffffff;
        }
        
        /* Back Button */
        .back-btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            color: #475569;
            transition: all 0.2s ease;
            flex-shrink: 0;
            text-decoration: none;
        }
        
        .back-btn-modern:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateX(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .back-btn-modern i {
            font-size: 18px;
            line-height: 1;
        }
        
        /* Navigation Controls */
        .nav-controls {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        
        .nav-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #475569;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }
        
        .nav-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        
        .nav-btn-icon {
            padding: 8px 10px;
        }
        
        .nav-btn-icon svg {
            width: 18px;
            height: 18px;
        }
        
        .nav-btn-primary {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .nav-btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: white;
        }
        
        .view-toggle {
            display: flex;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: white;
        }
        
        .view-toggle a {
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: #64748b;
            transition: all 0.2s ease;
            background: white;
        }
        
        .view-toggle a:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        
        .view-toggle a.active {
            background: #2563eb;
            color: white;
            font-weight: 600;
        }
        
        /* Search Input */
        .search-input {
            padding: 9px 16px;
            font-size: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            background: white;
            color: #0f172a;
            transition: all 0.2s ease;
            min-width: 240px;
        }
        
        .search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-input::placeholder {
            color: #94a3b8;
        }
        
        /* Table Styles */
        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }
        
        .table-modern thead {
            background: #f8fafc;
        }
        
        .table-modern th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            position: sticky;
            top: 0;
            background: #f8fafc;
        }
        
        .table-modern td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        
        .table-modern tbody tr {
            transition: all 0.15s ease;
            cursor: pointer;
        }
        
        .table-modern tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.002);
        }
        
        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.01em;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Action Buttons */
        .action-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #64748b;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .action-btn i {
            width: 16px;
            height: 16px;
        }
        
        .action-btn-edit {
            color: #8b5cf6;
        }
        
        .action-btn-edit:hover {
            background: #ede9fe;
            color: #7c3aed;
        }
        
        .action-btn-delete {
            color: #ef4444;
        }
        
        .action-btn-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .action-btn-view {
            color: #3b82f6;
        }
        
        .action-btn-view:hover {
            background: #dbeafe;
            color: #2563eb;
        }
        
        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #94a3b8;
        }
        
        .empty-state i {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            color: #cbd5e1;
        }
        
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        
        .empty-state p {
            font-size: 14px;
            color: #94a3b8;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .card-header-custom {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input {
                width: 100%;
                min-width: unset;
            }
            
            .nav-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .nav-group {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .view-toggle {
                width: 100%;
            }
            
            .view-toggle a {
                flex: 1;
                text-align: center;
            }
            
            .table-modern {
                font-size: 13px;
            }
            
            .table-modern th,
            .table-modern td {
                padding: 10px 12px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
            }
            
            .card-header-custom {
                padding: 16px;
            }
            
            .card-body-custom {
                padding: 12px;
                overflow-x: auto;
            }
            
            .table-modern {
                font-size: 12px;
            }
            
            .table-modern th,
            .table-modern td {
                padding: 8px 10px;
            }
        }

        /* Optional: Gradient button style */
.btn-gradient {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
}

.btn-gradient:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #6d28d9 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35);
}

/* Optional: Outline button style */
.btn-outline {
    background: transparent;
    border: 2px solid #2563eb;
    color: #2563eb;
}

.btn-outline:hover {
    background: #2563eb;
    color: white;
}
    </style>
</head>

<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content w-full">

               
                <div class="mb-6">
    <div class="flex items-center justify-between">

        <!-- Left: Back Button + Title -->
        <div class="flex items-center gap-4">
            <a href="prescriptions.php"
               class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-100 transition shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>

            <h1 class="text-2xl font-bold text-gray-900">
                Prescription History
            </h1>
        </div>

      

    </div>

    <!-- Subtitle -->
    <p class="text-sm text-gray-500 mt-2 ml-14">
        View and manage all prescriptions for this patient
    </p>
</div>
           

                    <!-- Navigation Controls -->
                    <div class="nav-controls" style="margin-top:30px">
                        <div class="nav-group">
                            <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>" 
                               class="nav-btn nav-btn-icon" title="Previous">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M15 18l-6-6 6-6"/>
                                </svg>
                            </a>
                            
                            <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>" 
                               class="nav-btn nav-btn-primary">
                                <?php echo $title; ?>
                            </a>
                            
                            <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>" 
                               class="nav-btn nav-btn-icon" title="Next">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </a>
                        </div>

                        <div class="view-toggle">
                            <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=day&date=<?php echo $currentDate; ?>" 
                               class="<?php echo ($view=='day') ? 'active' : ''; ?>">Day</a>
                            <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=week&date=<?php echo $currentDate; ?>" 
                               class="<?php echo ($view=='week') ? 'active' : ''; ?>">Week</a>
                            <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=month&date=<?php echo $currentDate; ?>" 
                               class="<?php echo ($view=='month') ? 'active' : ''; ?>">Month</a>
                        </div>
                    </div>

                    <!-- Main Card -->
                    <div class="card-modern">
                        <div class="card-header-custom">
                            <h2>
                                <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                                All Prescriptions
                                <span class="ml-2 text-sm font-normal text-gray-400">
                                    (<?php echo $prescriptionCount; ?> found)
                                </span>
                            </h2>
                            <input type="text" id="searchInput" placeholder="Search prescriptions..." 
                                   class="search-input" onkeyup="searchPrescriptions()">
                        </div>
                        
                        <div class="card-body-custom">
                            <?php if ($prescriptionCount > 0): ?>
                                <div style="overflow-x: auto;">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Patient</th>
                                                <th>Medicine</th>
                                             
                                                <th>Follow-up</th>
                                            
                                            </tr>
                                        </thead>
                                        <tbody id="prescriptionTableBody">
                                            <?php $i = 1; while ($row = $prescriptionResult->fetch_assoc()): ?>
                                          <tr onclick="window.location='view_prescription.php?id=<?php echo $row['prescription_id']; ?>'">
                                                <td><span class="badge badge-info"><?php echo $i++; ?></span></td>
                                                <td><strong><?php echo date('d M Y', strtotime($row['created_at'])); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                       <td><?php echo htmlspecialchars($row['medicines']); ?></td>         
                                            
                                                <td>
                                                    <?php if ($row['followup_date']): ?>
                                                        <span class="badge badge-success">
                                                            <?php echo date('d M Y', strtotime($row['followup_date'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 13px;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i data-lucide="file-text" class="w-14 h-14 mx-auto text-gray-300 mb-4"></i>
                                    <h3>No prescriptions found</h3>
                                    <p>There are no prescriptions for this period. Create your first prescription now.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        function confirmDelete(id) {
            if (confirm("⚠️ Are you sure you want to delete this prescription? This action cannot be undone.")) {
                window.location.href = "view_prescription_history.php?delete_id=" + id;
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
        }
    </script>
</body>
</html>