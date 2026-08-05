<?php 
session_start(); 

include '../config/hospital.php';
include '../config/permission.php';
checkPermission('appointment-view'); 

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$register_id  = (int) $_SESSION['id'];
$hospital_id  = isset($_SESSION['hospital_id']) ? (int) $_SESSION['hospital_id'] : 0;
$user_role = $_SESSION['role'] ?? '';

// Server's current date – used for "Today" counts and client-side filtering
$todayDate = date('Y-m-d');

// Hospital details
$hospital = [
    'hospital_name' => 'Hospital',
    'hospital_logo' => ''
];

$hospitalQuery = "SELECT hospital_name, hospital_logo 
                  FROM hospital_master 
                  WHERE hospital_id = $hospital_id AND (delete_flag = 0 OR delete_flag IS NULL)";
$hospitalResult = mysqli_query($conn, $hospitalQuery);
if ($hospitalResult && mysqli_num_rows($hospitalResult) > 0) {
    $hospital = mysqli_fetch_assoc($hospitalResult);
}

// ============================================================
// ROLE DETECTION
// ============================================================
$isAdmin = ($user_role == 'Admin' || $user_role == 'admin');
$isDoctor = false;
$doctor_id = 0;
$doctor_name = '';

// Check if user is a doctor
$docQuery = "SELECT doctor_id, doctor_name FROM doctor 
             WHERE register_id = $register_id AND hospital_id = $hospital_id 
             AND (delete_flag = 0 OR delete_flag IS NULL)";
$docResult = mysqli_query($conn, $docQuery);
if ($docResult && mysqli_num_rows($docResult) > 0) {
    $docdata = mysqli_fetch_assoc($docResult);
    $doctor_id = (int)$docdata['doctor_id'];
    $doctor_name = $docdata['doctor_name'];
    $isDoctor = true;
    $_SESSION['doctor_id'] = $doctor_id;
}

// If not admin and not doctor -> receptionist (or other staff) – they can view all appointments
$isReceptionist = (!$isAdmin && !$isDoctor);

// ============================================================
// BUILD WHERE CLAUSE
// ============================================================
if ($isAdmin || $isReceptionist) {
    // Show all appointments for the hospital
    $whereClause = "a.hospital_id = $hospital_id AND (a.delete_flag = 0 OR a.delete_flag IS NULL)";
} else {
    // Doctor: only their own appointments
    $whereClause = "a.doctor_id = $doctor_id AND a.hospital_id = $hospital_id AND (a.delete_flag = 0 OR a.delete_flag IS NULL)";
}

// Main query – all columns from appointments + patient_name + doctor_name
$sql = "SELECT a.*, p.patient_name, d.doctor_name, d.department 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
        WHERE $whereClause 
        ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$re = mysqli_query($conn, $sql);
$totalCount = mysqli_num_rows($re);

// ============================================================
// COUNT HELPER
// ============================================================
function getCount($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    return $res ? (int) mysqli_fetch_assoc($res)['count'] : 0;
}

// Count queries – use the same filtering logic (without doctor filter if receptionist)
if ($isAdmin || $isReceptionist) {
    $countWhere = "hospital_id = $hospital_id AND (delete_flag = 0 OR delete_flag IS NULL)";
} else {
    $countWhere = "doctor_id = $doctor_id AND hospital_id = $hospital_id AND (delete_flag = 0 OR delete_flag IS NULL)";
}

$upcomingCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE $countWhere AND status = 'Confirmed'"
);

$todayQuery = "SELECT COUNT(*) as count FROM appointments 
               WHERE $countWhere AND appointment_date = '$todayDate'";
$todayResult = mysqli_query($conn, $todayQuery);
$todayCount = $todayResult ? (int) mysqli_fetch_assoc($todayResult)['count'] : 0;

$completedCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE $countWhere AND status = 'Completed'"
);

$cancelledCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE $countWhere AND status = 'Cancelled'"
);

$opdCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE $countWhere AND opd_ipd_type = 'OPD'"
);

$ipdCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE $countWhere AND opd_ipd_type = 'IPD'"
);

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$page_title = ($hospital['hospital_name'] ?? 'Hospital') . " - Appointments";

// Determine role label for display
if ($isAdmin) {
    $roleLabel = 'Admin';
} elseif ($isDoctor) {
    $roleLabel = 'Doctor';
} else {
    $roleLabel = 'Receptionist';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php if (!empty($hospital['hospital_logo'])): ?>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital['hospital_logo']); ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        
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

        .status-badge { padding: 4px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .transition-all { transition: all 0.3s ease; }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .action-btn { transition: all 0.2s ease; cursor: pointer; padding: 6px; border-radius: 8px; }
        .action-btn:hover { transform: scale(1.1); }
        .fade-in { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert { animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .table-row-hover:hover { background: #f8fafc; }
        .btn-primary-custom { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 10px 24px; border-radius: 10px; font-weight: 600; border: none; transition: all 0.3s ease; }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59, 130, 246, 0.35); }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; width: 100%; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) { .main-content { padding: 12px; } }
        .doctor-dept { font-size: 0.7rem; color: #94a3b8; font-weight: 500; margin-top: 2px; }
        table tbody tr td:last-child .action-btn { padding: 6px 10px; font-size: 12px; }

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

        .role-badge {
            background: #8b5cf6;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">

                    <?php if ($successMessage): ?>
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0"><i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i></div>
                                <div class="ml-3"><p class="text-sm text-green-700"><?php echo htmlspecialchars($successMessage); ?></p></div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-green-500 hover:text-green-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0"><i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i></div>
                                <div class="ml-3"><p class="text-sm text-red-700"><?php echo htmlspecialchars($errorMessage); ?></p></div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-red-500 hover:text-red-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">
                                        <?php if ($isAdmin): ?>
                                            All Appointments <span class="role-badge">Admin</span>
                                        <?php elseif ($isDoctor): ?>
                                            My Appointments <span class="role-badge">Doctor</span>
                                        <?php else: ?>
                                            All Appointments <span class="role-badge">Receptionist</span>
                                        <?php endif; ?>
                                    </h1>
                                    <p class="text-gray-500 text-sm mt-1">
                                        <?php if ($isAdmin): ?>
                                            Manage all appointments across the hospital
                                        <?php elseif ($isDoctor): ?>
                                            Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?> – manage your appointments and schedules.
                                        <?php else: ?>
                                            View and manage all appointments for the hospital
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="add_appointment.php"
                                   class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    Add Appointment
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ===== RESPONSIVE TABS ===== -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="tabs-wrapper">
                            <div class="flex flex-wrap gap-2 mb-6" style="flex-wrap: nowrap;">
                                <div class="tab-btn tab-active" id="tab-all" onclick="filterAppointments('all')">
                                    All <span class="badge-count"><?php echo $totalCount; ?></span>
                                </div>
                                <div class="tab-btn tab-inactive" id="tab-today" onclick="filterAppointments('today')">
                                    Today <span class="badge-count"><?php echo $todayCount; ?></span>
                                </div>
                                <div class="tab-btn tab-inactive" id="tab-confirmed" onclick="filterAppointments('confirmed')">
                                    Confirmed <span class="badge-count"><?php echo $upcomingCount; ?></span>
                                </div>
                                <div class="tab-btn tab-inactive" id="tab-cancelled" onclick="filterAppointments('cancelled')">
                                    Cancelled <span class="badge-count"><?php echo $cancelledCount; ?></span>
                                </div>
                                <div class="tab-btn tab-inactive" id="tab-opd" onclick="filterAppointments('opd')">
                                    OPD <span class="badge-count"><?php echo $opdCount; ?></span>
                                </div>
                                <div class="tab-btn tab-inactive" id="tab-ipd" onclick="filterAppointments('ipd')">
                                    IPD <span class="badge-count"><?php echo $ipdCount; ?></span>
                                </div>
                            </div>
                        </div>
                        <!-- ===== END TABS ===== -->

                        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900" id="table-title">
                                    <?php if ($isAdmin): ?>
                                        All Appointments
                                    <?php elseif ($isDoctor): ?>
                                        My Appointments
                                    <?php else: ?>
                                        All Appointments
                                    <?php endif; ?>
                                </h2>
                                <p class="text-sm text-gray-500" id="table-subtitle">
                                    <?php if ($isAdmin): ?>
                                        View and manage all appointments across the hospital
                                    <?php elseif ($isDoctor): ?>
                                        View and manage all your scheduled appointments
                                    <?php else: ?>
                                        View and manage all appointments for the hospital
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="relative flex-1 sm:flex-none">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                <input type="text" id="searchInput"
                                       placeholder="Search appointments..."
                                       class="w-full sm:w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                       onkeyup="searchAppointments()">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date &amp; Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-36" style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <?php if ($re && mysqli_num_rows($re) > 0) { ?>
                                        <?php while ($row = mysqli_fetch_assoc($re)) { 
                                            $statusText = $row['status'] ?? 'Scheduled';
                                            $statusClass = 'status-scheduled';
                                            if (strtolower($statusText) == 'completed') $statusClass = 'status-completed';
                                            elseif (strtolower($statusText) == 'cancelled') $statusClass = 'status-cancelled';
                                            elseif (strtolower($statusText) == 'confirmed') $statusClass = 'status-confirmed';
                                            elseif (strtolower($statusText) == 'in progress') $statusClass = 'status-in-progress';

                                            $dataStatus = strtolower($statusText);
                                            $dataDate = $row['appointment_date'] ?? '';
                                            $patientName = !empty($row['patient_name']) ? htmlspecialchars($row['patient_name']) : 'Unknown Patient';
                                            $appointmentNo = !empty($row['appointment_no']) ? htmlspecialchars($row['appointment_no']) : 'NA-' . ($row['appointment_id'] ?? '000');
                                            $appointmentTime = $row['appointment_time'] ?? '00:00:00';
                                            $appointmentId = (int) ($row['appointment_id'] ?? 0);
                                            $department = htmlspecialchars($row['department'] ?? 'N/A');
                                            $appointmentType = !empty($row['opd_ipd_type']) ? $row['opd_ipd_type'] : 'OPD';
                                            $dataType = strtolower($appointmentType);
                                            $appointmentDateFormatted = '';
                                            if ($dataDate && $dataDate != '0000-00-00') {
                                                $appointmentDateFormatted = date('d M Y', strtotime($dataDate));
                                            }
                                            $docName = !empty($row['doctor_name']) ? htmlspecialchars($row['doctor_name']) : 'N/A';
                                            $deptName = $department;

                                            // For receptionist, we allow edit/delete if they have permission? 
                                            // We'll keep the same permission check: only doctor/admin can edit/delete.
                                            $canEdit = ($isAdmin || ($isDoctor && $row['doctor_id'] == $doctor_id));
                                        ?>
                                        <tr class="appointment-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in"
                                            data-status="<?php echo $dataStatus; ?>"
                                            data-date="<?php echo htmlspecialchars($dataDate); ?>"
                                            data-type="<?php echo $dataType; ?>"
                                            data-patient="<?php echo strtolower($patientName); ?>"
                                            data-appointment="<?php echo strtolower($appointmentNo); ?>"
                                            onclick="window.location='view_appointment.php?id=<?php echo $appointmentId; ?>'"
                                            style="cursor:pointer;">
                                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                                <div><?php echo $appointmentDateFormatted; ?></div>
                                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($appointmentTime)); ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700">
                                                <?php echo $patientName; ?>
                                                <div class="text-xs text-gray-500"><?php echo $appointmentNo; ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900"><?php echo $docName; ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-xs">
                                                <?php echo $deptName; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 rounded text-xs font-medium <?php echo $appointmentType == 'IPD' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                                    <?php echo htmlspecialchars($appointmentType); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> <?php echo ucfirst($statusText); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right" onclick="event.stopPropagation();" style="text-align: center;">
                                                <?php if ($canEdit): ?>
                                                    <a href="edit_appointment.php?id=<?php echo $appointmentId; ?>" class="action-btn edit" title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </a>
                                                    <a href="delete_appointment.php?id=<?php echo $appointmentId; ?>" class="action-btn delete" title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this appointment?');">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center;padding:20px;">No Appointments Found</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Showing <span id="visibleCount"><?php echo $totalCount; ?></span> appointments</div>
                            <div class="text-xs text-gray-400"><i class="fas fa-sync-alt mr-1"></i> Live updates</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
        let currentFilter = 'all';
        const serverToday = '<?php echo $todayDate; ?>';
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

        // ========== FILTER APPOINTMENTS ==========
        function filterAppointments(filter) {
            currentFilter = filter;
            const rows = document.querySelectorAll('.appointment-row');
            const today = serverToday;
            let visibleCount = 0;

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });
            const activeTab = document.getElementById('tab-' + filter);
            if (activeTab) {
                activeTab.classList.remove('tab-inactive');
                activeTab.classList.add('tab-active');
            }

            const titles = {
                'all': ['All Appointments', isAdmin ? 'View all appointments across the hospital' : 'View all your scheduled appointments'],
                'confirmed': ['Confirmed Appointments', 'View confirmed appointments'],
                'today': ['Today Appointments', 'View today appointments'],
                'completed': ['Completed Appointments', 'View completed appointments'],
                'cancelled': ['Cancelled Appointments', 'View cancelled appointments'],
                'opd': ['OPD Appointments', 'View only outpatient appointments'],
                'ipd': ['IPD Appointments', 'View only inpatient appointments']
            };
            document.getElementById('table-title').textContent = titles[filter]?.[0] || 'All Appointments';
            document.getElementById('table-subtitle').textContent = titles[filter]?.[1] || '';

            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            rows.forEach(row => {
                let show;
                const status = row.dataset.status;
                const date = row.dataset.date;
                const type = row.dataset.type;
                const searchText = (row.dataset.patient || '') + ' ' + (row.dataset.appointment || '');

                switch (filter) {
                    case 'confirmed': show = status === 'confirmed'; break;
                    case 'today': show = date === today; break;
                    case 'completed': show = status === 'completed'; break;
                    case 'cancelled': show = status === 'cancelled'; break;
                    case 'opd': show = type === 'opd'; break;
                    case 'ipd': show = type === 'ipd'; break;
                    default: show = true;
                }

                if (show && (searchTerm === '' || searchText.includes(searchTerm))) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function searchAppointments() {
            filterAppointments(currentFilter);
        }

        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });
            const allTab = document.getElementById('tab-all');
            if (allTab) {
                allTab.classList.remove('tab-inactive');
                allTab.classList.add('tab-active');
            }
            filterAppointments('all');
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>