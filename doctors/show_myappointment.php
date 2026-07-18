<?php 
session_start(); 

include '../config/hospital.php';

if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}

 
if(!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$doctor_reg_id = $_SESSION['id']; //register id 


$getDoctor="SELECT doctor_name,doctor_id FROM doctor WHERE register_id='$doctor_reg_id'";
$doctor = $conn->query($getDoctor);
$docdata=$doctor->fetch_assoc();
$doctor_id = $docdata["doctor_id"];
$doctor_name = $docdata["doctor_name"];

$sql = "SELECT a.*, p.patient_name FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id WHERE a.doctor_id='$doctor_id' AND (a.delete_flag=0 OR a.delete_flag IS NULL) ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}

$totalCount = $result->num_rows;

$upcomingSql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id' and(delete_flag=0 OR delete_flag IS NULL) and status='Confirmed'";
$upcomingResult = $conn->query($upcomingSql);
$upcomingCount = $upcomingResult ? $upcomingResult->fetch_assoc()['count'] : 0;

$todaySql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id' and (delete_flag=0 OR delete_flag IS NULL) AND appointment_date = CURDATE()";
$todayResult = $conn->query($todaySql);
$todayCount = $todayResult ? $todayResult->fetch_assoc()['count'] : 0;

$completedSql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id' and (delete_flag=0 OR delete_flag IS NULL) AND status = 'Completed'";
$completedResult = $conn->query($completedSql);
$completedCount = $completedResult ? $completedResult->fetch_assoc()['count'] : 0;

$cancelledSql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id' and (delete_flag=0 OR delete_flag IS NULL) AND status = 'Cancelled'";
$cancelledResult = $conn->query($cancelledSql);
$cancelledCount = $cancelledResult ? $cancelledResult->fetch_assoc()['count'] : 0;

$result = $conn->query($sql);

 
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $hospital['hospital_name'] ?> - Appointments</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            background-color: #f3f4f6;
            color: #111827;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }
        .tab-active {
            background-color: white;
            color: #111827;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .tab-inactive {
            color: #6b7280;
        }
        .tab-inactive:hover {
            background-color: #f3f4f6;
            color: #111827;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-scheduled {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-confirmed {
            background: #fef3c7;
            color: #92400e;
        }
        .status-in-progress {
            background: #e0e7ff;
            color: #3730a3;
        }
        .transition-all {
            transition: all 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert {
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <!-- Sidebar - Direct Aside -->
            <?php include 'Sidebar.php' ?>  
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    
                    <!-- Success/Error Messages -->
                    <?php if($successMessage): ?>
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700"><?php echo $successMessage; ?></p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-green-500 hover:text-green-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($errorMessage): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"><?php echo $errorMessage; ?></p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-red-500 hover:text-red-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <!-- Updated: Back Button with Icon only (like prescription page) -->
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
                                    <p class="text-gray-500 mt-1">Manage your clinic's appointments and schedules.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="appointments/calendar.html" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-all">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                                    Calendar View
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex flex-wrap gap-1 -mb-px" aria-label="Tabs">
                            <button onclick="filterAppointments('all')" 
                                    id="tab-all"
                                    class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-active">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="list" class="w-4 h-4"></i>
                                    All Appointments
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $totalCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('confirmed')"
                                    id="tab-confirmed"
                                    class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    Confirmed
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $upcomingCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('today')" 
                                    id="tab-today"
                                    class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                                    Today
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $todayCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('completed')" 
                                    id="tab-completed"
                                    class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    Completed
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $completedCount; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('cancelled')" 
                                    id="tab-cancelled"
                                    class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all tab-inactive">
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
                                    <input type="text" id="searchInput" 
                                           placeholder="Search appointments..." 
                                           class="w-full sm:w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
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
                                 
                                       
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
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
                                           
                                            $appointmentNo = isset($row['appointment_no']) ? htmlspecialchars($row['appointment_no']) : '';
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
                                                                                                             data-appointment=\"" . strtolower($appointmentNo) . "\">";
                                            echo "<td class=\"px-4 py-3 font-medium text-gray-900\">" . $appointmentNo . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $patientName . "</td>";
                                           
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $appointmentDateFormatted . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $appointmentTime . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $duration . " min</td>";
                                            echo "<td class=\"px-4 py-3\"><span class=\"status-badge {$statusClass}\">" . ucfirst($statusText) . "</span></td>";
                                            echo "<td class=\"px-4 py-3 text-right\">";
                                            echo "<div class=\"flex items-center justify-end gap-1\">";
                                            
                                            // CONFIRM BUTTON - Show for ALL appointments
                                            echo "<a href=\"confirm_appointment.php?appointment_id=" . $appointmentId . "\" 
                                                    class=\"action-btn p-1.5 rounded-md text-green-600 hover:bg-green-50 transition-all\" 
                                                    title=\"Confirm Appointment\"
                                                    onclick=\"return confirm('Are you sure you want to confirm appointment #" . $appointmentNo . "?')\">
                                                    <i data-lucide=\"check-circle\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            
                                            // CANCEL BUTTON - Show for ALL appointments
                                            echo "<a href=\"cancel_appointment.php?appointment_id=" . $appointmentId . "\" 
                                                    class=\"action-btn p-1.5 rounded-md text-red-600 hover:bg-red-50 transition-all\" 
                                                    title=\"Cancel Appointment\"
                                                    onclick=\"return confirm('Are you sure you want to cancel appointment #" . $appointmentNo . "? This action cannot be undone.')\">
                                                    <i data-lucide=\"x-circle\" class=\"w-4 h-4\"></i>
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
                                       
                                        echo "</a>";
                                        echo "</div>";
                                        echo "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>
                                Showing <span id="visibleCount"><?php echo $result ? $result->num_rows : 0; ?></span> appointments
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
                'all': ['All Appointments', 'View all appointments'],
                'confirmed': ['Confirmed Appointments', 'View confirmed appointments'],
                'today': ['Today Appointments', 'View today appointments'],
                'completed': ['Completed Appointments', 'View completed appointments'],
                'cancelled': ['Cancelled Appointments', 'View cancelled appointments']
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

        // Initialize lucide icons after page load
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>