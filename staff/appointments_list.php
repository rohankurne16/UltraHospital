<?php 
session_start(); 
include("../../config/db.php");

if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}

$sql = "SELECT * FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY appointment_date DESC, appointment_time ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}

$totalCount = $result->num_rows;

$confirmedSql = "SELECT COUNT(*) as count FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status='Confirmed'";
$confirmedResult = $conn->query($confirmedSql);
$confirmedCount = $confirmedResult ? $confirmedResult->fetch_assoc()['count'] : 0;

$todaySql = "SELECT COUNT(*) as count FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND appointment_date = CURDATE()";
$todayResult = $conn->query($todaySql);
$todayCount = $todayResult ? $todayResult->fetch_assoc()['count'] : 0;

$completedSql = "SELECT COUNT(*) as count FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Completed'";
$completedResult = $conn->query($completedSql);
$completedCount = $completedResult ? $completedResult->fetch_assoc()['count'] : 0;

$cancelledSql = "SELECT COUNT(*) as count FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) AND status = 'Cancelled'";
$cancelledResult = $conn->query($cancelledSql);
$cancelledCount = $cancelledResult ? $cancelledResult->fetch_assoc()['count'] : 0;

$sql = "SELECT * FROM appointments WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY appointment_date DESC, appointment_time ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .tab-active { background-color: white; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tab-inactive { color: #6b7280; }
        .tab-inactive:hover { background-color: #f3f4f6; color: #111827; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; }
        .action-btn-confirm { color: #22c55e; }
        .action-btn-confirm:hover { background: #d1fae5; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../staff_sidebar.php'; ?>
            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
                                <p class="text-gray-500 mt-1">Manage your clinic's appointments and schedules.</p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="add_appointment.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    New Appointment
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex flex-wrap gap-1 -mb-px" aria-label="Tabs">
                            <button onclick="filterAppointments('all')" id="tab-all" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-active">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="list" class="w-4 h-4"></i>
                                    All
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $totalCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('confirmed')" id="tab-confirmed" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    Confirmed
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $confirmedCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('today')" id="tab-today" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    Today
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $todayCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('completed')" id="tab-completed" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Completed
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $completedCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('cancelled')" id="tab-cancelled" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    Cancelled
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $cancelledCount; ?></span>
                                </span>
                            </button>
                        </nav>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900" id="table-title">All Appointments</h2>
                                <p class="text-sm text-gray-500" id="table-subtitle">View and manage all scheduled appointments.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                <div class="relative flex-1 sm:flex-none">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    <input type="text" id="searchInput" placeholder="Search appointments..." 
                                           class="w-full sm:w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchAppointments()">
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appt No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <?php
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $statusClass = 'status-scheduled';
                                            $statusText = isset($row['status']) ? $row['status'] : 'Scheduled';
                                            
                                            if (strtolower($statusText) == 'completed') {
                                                $statusClass = 'status-completed';
                                            } elseif (strtolower($statusText) == 'cancelled') {
                                                $statusClass = 'status-cancelled';
                                            } elseif (strtolower($statusText) == 'confirmed') {
                                                $statusClass = 'status-confirmed';
                                            } elseif (strtolower($statusText) == 'in progress') {
                                                $statusClass = 'status-in-progress';
                                            }
                                            
                                            $dataStatus = strtolower($statusText);
                                            $dataDate = isset($row['appointment_date']) ? $row['appointment_date'] : '';
                                            $patientName = isset($row['patient_name']) ? htmlspecialchars($row['patient_name']) : '';
                                            $doctorName = isset($row['doctor_name']) ? htmlspecialchars($row['doctor_name']) : '';
                                            $appointmentNo = isset($row['appointment_no']) ? htmlspecialchars($row['appointment_no']) : '';
                                            $department = isset($row['department']) ? htmlspecialchars($row['department']) : '';
                                            $appointmentTime = isset($row['appointment_time']) ? htmlspecialchars($row['appointment_time']) : '';
                                            $duration = isset($row['duration']) ? htmlspecialchars($row['duration']) : '0';
                                            $appointmentId = isset($row['appointment_id']) ? $row['appointment_id'] : '';
                                            
                                            $appointmentDateFormatted = '';
                                            if ($dataDate) {
                                                $appointmentDateFormatted = date('M d, Y', strtotime($dataDate));
                                            }
                                            
                                            echo "<tr class=\"appointment-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in\" 
                                                        data-status=\"{$dataStatus}\" 
                                                        data-date=\"{$dataDate}\"
                                                        data-patient=\"" . strtolower($patientName) . "\"
                                                        data-doctor=\"" . strtolower($doctorName) . "\"
                                                        data-appointment=\"" . strtolower($appointmentNo) . "\">";
                                            echo "<td class=\"px-4 py-3 font-medium text-gray-900\">" . $appointmentNo . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $patientName . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $doctorName . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $department . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $appointmentDateFormatted . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $appointmentTime . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $duration . " min</td>";
                                            echo "<td class=\"px-4 py-3\"><span class=\"status-badge {$statusClass}\">" . ucfirst($statusText) . "</span></td>";
                                            echo "<td class=\"px-4 py-3 text-center\">";
                                            echo "<div class=\"flex items-center justify-center gap-1\">";
                                            
                                            echo "<a href=\".view_appointment.php?id=" . $appointmentId . "\" 
                                                    class=\"action-btn p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-all\" 
                                                    title=\"View Appointment\">
                                                    <i data-lucide=\"eye\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            echo "<a href=\"update_appointment.php?id=" . $appointmentId . "\" 
                                                    class=\"action-btn action-btn-edit\" 
                                                    title=\"Edit Appointment\">
                                                    <i data-lucide=\"edit-2\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            
                                            if ($dataStatus != 'completed' && $dataStatus != 'cancelled') {
                                                echo "<a href=\"confirm_appointment.php?id=" . $appointmentId . "\" 
                                                        class=\"action-btn action-btn-confirm\" 
                                                        title=\"Confirm Appointment\"
                                                        onclick=\"return confirm('Are you sure you want to confirm this appointment?')\">
                                                        <i data-lucide=\"check-circle\" class=\"w-4 h-4\"></i>
                                                      </a>";
                                            }
                                            
                                            echo "<a href=\"delete_appointment.php?id=" . $appointmentId . "\" 
                                                    class=\"action-btn action-btn-delete\" 
                                                    title=\"Delete Appointment\"
                                                    onclick=\"return confirm('Are you sure you want to delete this appointment? This action cannot be undone.')\">
                                                    <i data-lucide=\"trash-2\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            
                                            echo "</div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan=\"9\" class=\"px-4 py-8 text-center text-gray-500\">";
                                        echo "<div class=\"flex flex-col items-center justify-center\">";
                                        echo "<i data-lucide=\"calendar\" class=\"w-12 h-12 mx-auto text-gray-300 mb-3\"></i>";
                                        echo "<p class=\"text-lg font-medium text-gray-600\">No appointments found</p>";
                                        echo "<p class=\"text-sm text-gray-400 mt-1\">Start by creating a new appointment</p>";
                                        echo "<a href=\"add_appointment.php\" class=\"inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-all\">";
                                        echo "Create Appointment";
                                        echo "</a>";
                                        echo "</div>";
                                        echo "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Showing <span id="visibleCount"><?php echo $result ? $result->num_rows : 0; ?></span> appointments</div>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                                <span class="px-3 py-1 bg-blue-600 text-white rounded-md">1</span>
                                <button class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let currentFilter = 'all';

        function filterAppointments(filter) {
            currentFilter = filter;
            const rows = document.querySelectorAll('.appointment-row');
            const today = new Date().toISOString().split('T')[0];
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
                'all': ['All Appointments', 'View and manage all scheduled appointments.'],
                'confirmed': ['Confirmed Appointments', 'View all confirmed appointments.'],
                'today': ['Today\'s Appointments', 'View appointments scheduled for today.'],
                'completed': ['Completed Appointments', 'View all completed appointments.'],
                'cancelled': ['Cancelled Appointments', 'View all cancelled appointments.']
            };
            
            document.getElementById('table-title').textContent = titles[filter]?.[0] || 'All Appointments';
            document.getElementById('table-subtitle').textContent = titles[filter]?.[1] || '';

            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            rows.forEach(row => {
                let show = true;
                const status = row.dataset.status;
                const date = row.dataset.date;
                const patient = row.dataset.patient || '';
                const doctor = row.dataset.doctor || '';
                const appointment = row.dataset.appointment || '';
                const searchText = patient + ' ' + doctor + ' ' + appointment;

                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'confirmed':
                        show = status === 'confirmed';
                        break;
                    case 'today':
                        show = date === today;
                        break;
                    case 'completed':
                        show = status === 'completed';
                        break;
                    case 'cancelled':
                        show = status === 'cancelled';
                        break;
                    default:
                        show = true;
                }

                if (show && searchText.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function searchAppointments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.appointment-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const patient = row.dataset.patient || '';
                const doctor = row.dataset.doctor || '';
                const appointment = row.dataset.appointment || '';
                const text = patient + ' ' + doctor + ' ' + appointment;

                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>