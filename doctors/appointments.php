<?php 
session_start(); 

include '../config/hospital.php';



if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$register_id  = (int) $_SESSION['id'];
$hospital_id  = isset($_SESSION['hospital_id']) ? (int) $_SESSION['hospital_id'] : 0;

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

// Doctor details
$doctor_id   = isset($_SESSION['doctor_id']) ? (int) $_SESSION['doctor_id'] : 0;
$doctor_name = 'Doctor';

if ($doctor_id) {
    $docQuery = "SELECT doctor_name FROM doctor 
                 WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
                 AND (delete_flag = 0 OR delete_flag IS NULL)";
    $docResult = mysqli_query($conn, $docQuery);
    if ($docResult && mysqli_num_rows($docResult) > 0) {
        $doctor_name = mysqli_fetch_assoc($docResult)['doctor_name'];
    } else {
        $doctor_id = 0;
    }
}

if (!$doctor_id) {
    $docQuery = "SELECT doctor_id, doctor_name FROM doctor 
                 WHERE register_id = $register_id AND hospital_id = $hospital_id 
                 AND (delete_flag = 0 OR delete_flag IS NULL)";
    $docResult = mysqli_query($conn, $docQuery);
    if ($docResult && mysqli_num_rows($docResult) > 0) {
        $docdata = mysqli_fetch_assoc($docResult);
        $doctor_id = $docdata['doctor_id'];
        $doctor_name = $docdata['doctor_name'];
        $_SESSION['doctor_id'] = $doctor_id;
    } else {
        echo "<script>alert('Doctor not found!'); window.location='dashboard.php';</script>";
        exit();
    }
}

// Main query – all columns from appointments + patient_name
// We LEFT JOIN patients without filtering on delete_flag, so we always get the name if it exists.
$sql = "SELECT a.*, p.patient_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = $doctor_id 
          AND a.hospital_id = $hospital_id
          AND (a.delete_flag = 0 OR a.delete_flag IS NULL) 
        ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$result = mysqli_query($conn, $sql);
$totalCount = mysqli_num_rows($result);

// Helper function for counts using simple queries
function getCount($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    return $res ? (int) mysqli_fetch_assoc($res)['count'] : 0;
}

$upcomingCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
       AND (delete_flag = 0 OR delete_flag IS NULL) AND status = 'Confirmed'"
);

// Today count – uses the server's $todayDate
$todayQuery = "SELECT COUNT(*) as count FROM appointments 
               WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
                 AND (delete_flag = 0 OR delete_flag IS NULL) AND appointment_date = '$todayDate'";
$todayResult = mysqli_query($conn, $todayQuery);
$todayCount = $todayResult ? (int) mysqli_fetch_assoc($todayResult)['count'] : 0;

$completedCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
       AND (delete_flag = 0 OR delete_flag IS NULL) AND status = 'Completed'"
);

$cancelledCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
       AND (delete_flag = 0 OR delete_flag IS NULL) AND status = 'Cancelled'"
);

$opdCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
       AND (delete_flag = 0 OR delete_flag IS NULL) AND opd_ipd_type = 'OPD'"
);

$ipdCount = getCount($conn,
    "SELECT COUNT(*) as count FROM appointments 
     WHERE doctor_id = $doctor_id AND hospital_id = $hospital_id 
       AND (delete_flag = 0 OR delete_flag IS NULL) AND opd_ipd_type = 'IPD'"
);

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$page_title = ($hospital['hospital_name'] ?? 'Hospital') . " - Doctor Appointments";

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
        .tab-active { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); border-radius: 10px 10px 0 0; }
        .tab-active .badge-count { background: rgba(255,255,255,0.3); color: white; }
        .tab-inactive { color: #64748b; border-radius: 10px 10px 0 0; }
        .tab-inactive:hover { background: #f1f5f9; color: #0f172a; }
        .tab-inactive .badge-count { background: #e2e8f0; color: #64748b; }
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
        .stat-card { background: white; border-radius: 12px; padding: 16px 20px; border: 1px solid #e5e7eb; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); border-color: #3b82f6; }
        .stat-card .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .stat-card .stat-value { font-size: 1.5rem; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: 0.78rem; color: #64748b; font-weight: 500; }
        .table-row-hover:hover { background: #f8fafc; }
        .btn-primary-custom { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 10px 24px; border-radius: 10px; font-weight: 600; border: none; transition: all 0.3s ease; }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59, 130, 246, 0.35); }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; width: 100%; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) { .main-content { padding: 12px; } }

        
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../doctors/header.php'; ?>

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
                                    <h1 class="text-2xl font-bold text-gray-900">My Appointments</h1>
                                    <p class="text-gray-500 text-sm mt-1">Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?> - Manage your appointments and schedules.</p>
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

                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <div class="stat-icon" style="background: #dbeafe; color: #3b82f6;"><i class="fas fa-calendar-check"></i></div>
                                <div><div class="stat-value"><?php echo $totalCount; ?></div><div class="stat-label">Total</div></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <div class="stat-icon" style="background: #fef3c7; color: #f59e0b;"><i class="fas fa-clock"></i></div>
                                <div><div class="stat-value"><?php echo $upcomingCount; ?></div><div class="stat-label">Confirmed</div></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <div class="stat-icon" style="background: #d1fae5; color: #10b981;"><i class="fas fa-calendar-day"></i></div>
                                <div><div class="stat-value"><?php echo $todayCount; ?></div><div class="stat-label">Today</div></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <div class="stat-icon" style="background: #fee2e2; color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                                <div><div class="stat-value"><?php echo $cancelledCount; ?></div><div class="stat-label">Cancelled</div></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;"><i class="fas fa-user-md"></i></div>
                                <div><div class="stat-value"><?php echo $opdCount; ?></div><div class="stat-label">OPD</div></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <div class="stat-icon" style="background: #fae8ff; color: #9333ea;"><i class="fas fa-hospital-user"></i></div>
                                <div><div class="stat-value"><?php echo $ipdCount; ?></div><div class="stat-label">IPD</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1 mb-4 border-b border-gray-200">
                        <button id="tab-all" class="tab-btn tab-active px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('all')">
                            <i data-lucide="list" class="w-4 h-4 inline mr-1.5"></i> All
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $totalCount; ?></span>
                        </button>
                        <button id="tab-confirmed" class="tab-btn tab-inactive px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('confirmed')">
                            <i data-lucide="check-circle" class="w-4 h-4 inline mr-1.5"></i> Confirmed
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $upcomingCount; ?></span>
                        </button>
                        <button id="tab-today" class="tab-btn tab-inactive px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('today')">
                            <i data-lucide="calendar" class="w-4 h-4 inline mr-1.5"></i> Today
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $todayCount; ?></span>
                        </button>
                        <button id="tab-completed" class="tab-btn tab-inactive px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('completed')">
                            <i data-lucide="check" class="w-4 h-4 inline mr-1.5"></i> Completed
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $completedCount; ?></span>
                        </button>
                        <button id="tab-cancelled" class="tab-btn tab-inactive px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('cancelled')">
                            <i data-lucide="x-circle" class="w-4 h-4 inline mr-1.5"></i> Cancelled
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $cancelledCount; ?></span>
                        </button>
                        <button id="tab-opd" class="tab-btn tab-inactive px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('opd')">
                            <i data-lucide="stethoscope" class="w-4 h-4 inline mr-1.5"></i> OPD
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $opdCount; ?></span>
                        </button>
                        <button id="tab-ipd" class="tab-btn tab-inactive px-4 py-2.5 text-sm font-medium transition-all" onclick="filterAppointments('ipd')">
                            <i data-lucide="hospital" class="w-4 h-4 inline mr-1.5"></i> IPD
                            <span class="badge-count ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"><?php echo $ipdCount; ?></span>
                        </button>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900" id="table-title">All Appointments</h2>
                                <p class="text-sm text-gray-500" id="table-subtitle">View and manage all your scheduled appointments.</p>
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appt No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Actions</th>
                                    </tr>
                                </thead>
                               <tbody id="appointmentsTableBody">
<?php if ($result && mysqli_num_rows($result) > 0) { ?>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>

    <tr>
        <td><?php echo htmlspecialchars($row['appointment_no']); ?></td>
        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
        <td><?php echo htmlspecialchars($row['opd_ipd_type']); ?></td>
        <td><?php echo date('d-m-Y', strtotime($row['appointment_date'])); ?></td>
        <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
        <td><?php echo htmlspecialchars($row['duration']); ?> Min</td>
        <td><?php echo htmlspecialchars($row['department']); ?></td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
        <td>
            <a href="view_appointment.php?id=<?php echo $row['appointment_id']; ?>">View</a> |
            <a href="edit_appointment.php?id=<?php echo $row['appointment_id']; ?>">Edit</a>
        </td>
    </tr>

    <?php } ?>

<?php } else { ?>

<tr>
    <td colspan="9" style="text-align:center;padding:20px;">
        No Appointments Found
    </td>
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

        // Use the server's current date for "Today" filtering – fixes time‑zone mismatch
        const serverToday = '<?php echo $todayDate; ?>';

        function filterAppointments(filter) {
            currentFilter = filter;
            const rows = document.querySelectorAll('.appointment-row');
            const today = serverToday;   // now consistent with server-side counts
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
                'all': ['All Appointments', 'View all your scheduled appointments'],
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
            //filterAppointments('all');
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>