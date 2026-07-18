<?php 
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("location: ../index.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
include("../config/hospital.php");

$sql = "select a.*, p.patient_name, d.doctor_name from appointments a left join patients p on a.patient_id = p.patient_id left join doctor d on a.doctor_id = d.doctor_id where a.patient_id='$patient_id' and (a.delete_flag = 0 or a.delete_flag is null) order by a.appointment_date desc, a.appointment_time asc";
$result = $conn->query($sql);

if (!$result) {
    die("query error: " . $conn->error);
}

$total_count = $result->num_rows;

$upcoming_sql = "select count(*) as count from appointments where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) and status='confirmed'";
$upcoming_result = $conn->query($upcoming_sql);
$upcoming_count = $upcoming_result ? $upcoming_result->fetch_assoc()['count'] : 0;

$today_sql = "select count(*) as count from appointments where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) and appointment_date = curdate()";
$today_result = $conn->query($today_sql);
$today_count = $today_result ? $today_result->fetch_assoc()['count'] : 0;

$completed_sql = "select count(*) as count from appointments where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) and status = 'completed'";
$completed_result = $conn->query($completed_sql);
$completed_count = $completed_result ? $completed_result->fetch_assoc()['count'] : 0;

$cancelled_sql = "select count(*) as count from appointments where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null) and status = 'cancelled'";
$cancelled_result = $conn->query($cancelled_sql);
$cancelled_count = $cancelled_result ? $cancelled_result->fetch_assoc()['count'] : 0;
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
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .tab-active { background-color: white; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tab-inactive { color: #6b7280; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-4">
                                <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors" href="dashboard.php">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
                                    <p class="text-gray-500 mt-1">Manage your clinic's appointments and schedules.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex flex-wrap gap-1 -mb-px">
                            <button onclick="filterAppointments('all')" id="tab-all" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg tab-active">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="list" class="w-4 h-4"></i>
                                    All Appointments
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $total_count; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('confirmed')" id="tab-confirmed" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    Confirmed
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $upcoming_count; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('today')" id="tab-today" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                                    Today
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $today_count; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('completed')" id="tab-completed" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    Completed
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $completed_count; ?></span>
                                </span>
                            </button>
                            <button onclick="filterAppointments('cancelled')" id="tab-cancelled" class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg tab-inactive">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    Cancelled
                                    <span class="ml-1 bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo $cancelled_count; ?></span>
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
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                <input type="text" id="searchInput" placeholder="Search appointments..." class="w-full sm:w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-md outline-none" onkeyup="searchAppointments()">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appt No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): 
                                            $appt_id = $row['id'] ?? $row['appointment_id'] ?? 0;
                                            $status = strtolower($row['status'] ?? 'scheduled');
                                            $status_class = 'status-scheduled';
                                            if ($status == 'completed') $status_class = 'status-completed';
                                            elseif ($status == 'cancelled') $status_class = 'status-cancelled';
                                            elseif ($status == 'confirmed') $status_class = 'status-confirmed';
                                            elseif ($status == 'in progress') $status_class = 'status-in-progress';
                                        ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 appointment-row" data-status="<?php echo $status; ?>" data-date="<?php echo $row['appointment_date']; ?>">
                                            <td class="px-4 py-4 font-medium"><?php echo htmlspecialchars($row['appointment_no'] ?? '#-'); ?></td>
                                            <td class="px-4 py-4"><?php echo htmlspecialchars($row['doctor_name'] ?? 'Not Assigned'); ?></td>
                                            <td class="px-4 py-4"><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                            <td class="px-4 py-4"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                            <td class="px-4 py-4"><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-2">
                                                    <?php if ($status != 'completed' && $status != 'cancelled' && $appt_id != 0): ?>
                                                        <a href="reschedule_appointment.php?id=<?php echo $appt_id; ?>" class="text-blue-600 hover:bg-blue-50 p-2 rounded-md transition-colors" title="Reschedule">
                                                            <i data-lucide="calendar" class="w-4 h-4"></i>
                                                        </a>
                                                        <button onclick="confirmCancel(<?php echo $appt_id; ?>)" class="text-red-600 hover:bg-red-50 p-2 rounded-md transition-colors" title="Cancel">
                                                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-xs italic">No actions</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">No appointments found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function confirmCancel(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                window.location.href = 'cancel_my_appointment.php?id=' + id;
            }
        }

        function filterAppointments(filter) {
            const rows = document.querySelectorAll('.appointment-row');
            const today = new Date().toISOString().split('T')[0];
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const date = row.getAttribute('data-date');
                
                if (filter === 'all') row.style.display = '';
                else if (filter === 'today') row.style.display = (date === today) ? '' : 'none';
                else row.style.display = (status === filter) ? '' : 'none';
            });

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });
            document.getElementById('tab-' + filter).classList.remove('tab-inactive');
            document.getElementById('tab-' + filter).classList.add('tab-active');
        }

        function searchAppointments() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.appointment-row');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
