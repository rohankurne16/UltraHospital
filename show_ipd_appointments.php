<?php
session_start();
include 'config/hospital.php';
include 'config/permission.php';
checkPermission('ipd-view'); 

$conn->set_charset("utf8");

$opd_ipd_type_filter = 'IPD';
$appointments = [];

$sql = "SELECT
            a.appointment_id,
            a.appointment_no,
            p.patient_name,
            d.doctor_name,
            a.department,
            a.appointment_type,
            a.opd_ipd_type,
            a.appointment_date,
            a.appointment_time,
            a.status
        FROM
            appointments a
        JOIN
            patients p ON a.patient_id = p.patient_id
        JOIN
            doctor d ON a.doctor_id = d.doctor_id
        WHERE
            a.opd_ipd_type = ? AND (a.delete_flag = 0 OR a.delete_flag IS NULL)
        ORDER BY
            a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $opd_ipd_type_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - IPD Appointments</title>
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

        .table-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Responsive Table Wrapper */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        /* Mobile Card View */
        @media (max-width: 768px) {
            table {
                min-width: 100%;
            }
            
            .table-wrapper {
                overflow-x: visible;
            }
            
            /* Hide table headers on mobile */
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
            
            table tbody tr td:last-child .action-btn {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            /* Add label before each cell */
            table tbody tr td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                flex-shrink: 0;
                min-width: 85px;
            }
            
            /* Remove label for actions column */
            table tbody tr td:last-child::before {
                display: none;
            }
            
            /* Style status badge in mobile view */
            table tbody tr td .status-scheduled,
            table tbody tr td .status-confirmed,
            table tbody tr td .status-completed,
            table tbody tr td .status-cancelled {
                display: inline-block;
                padding: 2px 10px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 500;
            }
            
            /* Appointment number as header in card */
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
                color: #8b5cf6;
            }
        }

        th {
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        td {
            padding: 12px 16px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Status styles */
        .status-scheduled {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-completed {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-right: 4px;
            border: none;
            cursor: pointer;
        }

        .action-btn.view {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        .action-btn.view:hover {
            background-color: #bae6fd;
        }
        .action-btn.edit {
            background-color: #fff7ed;
            color: #ea580c;
        }
        .action-btn.edit:hover {
            background-color: #fed7aa;
        }
        .action-btn.delete {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .action-btn.delete:hover {
            background-color: #fecaca;
        }

        .action-btn i {
            width: 16px;
            height: 16px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: #f1f5f9;
            border-color: #d1d5db;
        }

        /* Count badge */
        .count-badge {
            background: #f3e8ff;
            color: #7c3aed;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .no-records {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .no-records i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 12px;
            display: block;
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

        /* IPD specific color accent */
        .ipd-badge {
            background: #f3e8ff;
            color: #7c3aed;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    

    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
           
                <?php include 'Sidebar.php'; ?>
         

            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    <!-- Page Header -->
                    <div class="mb-6 flex flex-wrap items-center gap-4">
                      
                        <a href="dashboard.php" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div class="flex-1">
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">IPD Appointments</h1>
                            <p class="text-gray-500 text-xs md:text-sm">View all Inpatient Department appointments.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="count-badge">
                                <i class="fas fa-bed mr-1"></i>
                                <?php echo count($appointments); ?> Total
                            </span>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Appointment No</th>
                                        <th>Patient Name</th>
                                        <th>Doctor Name</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>OPD/IPD</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointments)) { ?>
                                        <tr>
                                            <td colspan="10" class="no-records">
                                                <i class="fas fa-hospital-user"></i>
                                                No IPD appointments found.
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($appointments as $appointment) { ?>
                                            <tr onclick="window.location.href='view_appointment.php?id=<?php echo $appointment['appointment_id']; ?>'">
                                                <td data-label="Appointment No">
                                                    <span class="font-semibold text-purple-600"><?php echo htmlspecialchars($appointment['appointment_no']); ?></span>
                                                </td>
                                                <td data-label="Patient">
                                                    <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                                </td>
                                                <td data-label="Doctor">
                                                    <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                                </td>
                                                <td data-label="Department">
                                                    <?php echo htmlspecialchars($appointment['department']); ?>
                                                </td>
                                                <td data-label="Type">
                                                    <span class="text-xs px-2 py-0.5 bg-gray-100 rounded-full">
                                                        <?php echo htmlspecialchars($appointment['appointment_type']); ?>
                                                    </span>
                                                </td>
                                                <td data-label="OPD/IPD">
                                                    <span class="ipd-badge">
                                                        <?php echo htmlspecialchars($appointment['opd_ipd_type']); ?>
                                                    </span>
                                                </td>
                                                <td data-label="Date">
                                                    <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?>
                                                </td>
                                                <td data-label="Time">
                                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                </td>
                                                <td data-label="Status">
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($appointment['status']) {
                                                        case 'Scheduled': $statusClass = 'status-scheduled'; break;
                                                        case 'Confirmed': $statusClass = 'status-confirmed'; break;
                                                        case 'Completed': $statusClass = 'status-completed'; break;
                                                        case 'Cancelled': $statusClass = 'status-cancelled'; break;
                                                        default: $statusClass = 'status-scheduled';
                                                    }
                                                    ?>
                                                    <span class="<?php echo $statusClass; ?>">
                                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td data-label="Actions" onclick="event.stopPropagation();">
                                                    <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                                       class="action-btn edit" title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </a>
                                                    <a href="delete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                                       class="action-btn delete" title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this appointment?');">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Stats -->
                    <div class="mt-4 flex flex-wrap justify-between items-center gap-3 text-sm text-gray-500">
                        <span>Showing <?php echo count($appointments); ?> appointment(s)</span>
                        <span>Last updated: <?php echo date('d M Y, h:i A'); ?></span>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

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

            // Handle close button inside Sidebar.php
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            // Close sidebar on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            // Auto-close sidebar on window resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1280) {
                    closeSidebar();
                }
            });
        });
    </script>
</body>
</html>