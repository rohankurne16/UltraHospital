<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config/hospital.php");

// Check if user is logged in and has appropriate role
if (!isset($_SESSION["id"]) || !isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit();
}

$conn->set_charset("utf8");

$logged_in_user_id = $_SESSION["id"];
$user_name = $_SESSION["name"] ?? "Staff Member";
$user_role = strtolower(trim($_SESSION["role"]));

// Define allowed roles for this dashboard
$allowed_roles = ['receptionist', 'nurse', 'ward boy', 'wardboy', 'lab technician'];

if (!in_array($user_role, $allowed_roles)) {
    header("Location: ../index.php");
    exit();
}

$today = date("Y-m-d");

// --- Fetch Dashboard Statistics ---

// Total Patients
$totalPatientsCount = 0;
$sql_total_patients = "SELECT COUNT(*) AS total FROM patients WHERE (delete_flag = 0 OR delete_flag IS NULL)";
$result_total_patients = $conn->query($sql_total_patients);
if ($result_total_patients) {
    $totalPatientsCount = $result_total_patients->fetch_assoc()["total"];
}

// Total Doctors
$totalDoctorsCount = 0;
$sql_total_doctors = "SELECT COUNT(*) AS total FROM doctor WHERE (delete_flag = 0 OR delete_flag IS NULL)";
$result_total_doctors = $conn->query($sql_total_doctors);
if ($result_total_doctors) {
    $totalDoctorsCount = $result_total_doctors->fetch_assoc()["total"];
}

// Total Appointments
$totalAppointmentsCount = 0;
$sql_total_appointments = "SELECT COUNT(*) AS total FROM appointments WHERE (delete_flag = 0 OR delete_flag IS NULL)";
$result_total_appointments = $conn->query($sql_total_appointments);
if ($result_total_appointments) {
    $totalAppointmentsCount = $result_total_appointments->fetch_assoc()["total"];
}

// Total Departments
$totalDepartmentsCount = 0;
$sql_total_departments = "SELECT COUNT(*) AS total FROM department WHERE (delete_flag = 0 OR delete_flag IS NULL)";
$result_total_departments = $conn->query($sql_total_departments);
if ($result_total_departments) {
    $totalDepartmentsCount = $result_total_departments->fetch_assoc()["total"];
}

// Today's Patients
$todayPatientsCount = 0;
$sql_today_patients = "SELECT COUNT(*) AS total FROM patients WHERE DATE(created_at) = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
$stmt_today_patients = $conn->prepare($sql_today_patients);
if ($stmt_today_patients) {
    $stmt_today_patients->bind_param("s", $today);
    $stmt_today_patients->execute();
    $result_today_patients = $stmt_today_patients->get_result();
    $todayPatientsCount = $result_today_patients->fetch_assoc()["total"];
    $stmt_today_patients->close();
}

// Today's Appointments
$todayAppointmentsCount = 0;
$sql_today_appointments = "SELECT COUNT(*) AS total FROM appointments WHERE appointment_date = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
$stmt_today_appointments = $conn->prepare($sql_today_appointments);
if ($stmt_today_appointments) {
    $stmt_today_appointments->bind_param("s", $today);
    $stmt_today_appointments->execute();
    $result_today_appointments = $stmt_today_appointments->get_result();
    $todayAppointmentsCount = $result_today_appointments->fetch_assoc()["total"];
    $stmt_today_appointments->close();
}

// Pending Bills
$pendingBillsCount = 0;
$tableCheck_billing = $conn->query("SHOW TABLES LIKE 'billing'");
if ($tableCheck_billing && $tableCheck_billing->num_rows > 0) {
    $sql_pending_bills = "SELECT COUNT(*) AS total FROM billing WHERE pending_amount > 0 AND (delete_flag = 0 OR delete_flag IS NULL)";
    $result_pending_bills = $conn->query($sql_pending_bills);
    if ($result_pending_bills) {
        $pendingBillsCount = $result_pending_bills->fetch_assoc()["total"];
    }
}

// Pending Lab Orders
$pendingLabOrdersCount = 0;
$tableCheck_lab_orders = $conn->query("SHOW TABLES LIKE 'lab_orders'");
if ($tableCheck_lab_orders && $tableCheck_lab_orders->num_rows > 0) {
    $sql_pending_lab_orders = "SELECT COUNT(*) AS total FROM lab_orders WHERE status = 'Pending' AND (delete_flag = 0 OR delete_flag IS NULL)";
    $result_pending_lab_orders = $conn->query($sql_pending_lab_orders);
    if ($result_pending_lab_orders) {
        $pendingLabOrdersCount = $result_pending_lab_orders->fetch_assoc()["total"];
    }
}

// Stock Alert
$stockAlertCount = 0;
$tableCheck_medicines = $conn->query("SHOW TABLES LIKE 'medicines'");
if ($tableCheck_medicines && $tableCheck_medicines->num_rows > 0) {
    $sql_stock_alert = "SELECT COUNT(*) AS total FROM medicines WHERE stock_quantity <= reorder_level AND (delete_flag = 0 OR delete_flag IS NULL)";
    $result_stock_alert = $conn->query($sql_stock_alert);
    if ($result_stock_alert) {
        $stockAlertCount = $result_stock_alert->fetch_assoc()["total"];
    }
}
$_SESSION["stock_alert_count"] = $stockAlertCount;

// --- Fetch Recent Activities ---

// Recent Appointments
$recentAppointments = [];
$sql_recent_appointments = "SELECT a.appointment_id, a.appointment_no, p.patient_name, a.appointment_date, a.appointment_time, a.status 
                            FROM appointments a
                            LEFT JOIN patients p ON a.patient_id = p.patient_id
                            WHERE (a.delete_flag = 0 OR a.delete_flag IS NULL)
                            ORDER BY a.created_at DESC LIMIT 10";
$result_recent_appointments = $conn->query($sql_recent_appointments);
if ($result_recent_appointments) {
    while ($row = $result_recent_appointments->fetch_assoc()) {
        $recentAppointments[] = $row;
    }
}

// Recent Patients
$recentPatients = [];
$sql_recent_patients = "SELECT patient_id, patient_name, created_at FROM patients WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY created_at DESC LIMIT 5";
$result_recent_patients = $conn->query($sql_recent_patients);
if ($result_recent_patients) {
    while ($row = $result_recent_patients->fetch_assoc()) {
        $recentPatients[] = $row;
    }
}

// Helper function for status badges
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Scheduled': return 'bg-blue-100 text-blue-800';
        case 'Confirmed': return 'bg-green-100 text-green-800';
        case 'Completed': return 'bg-indigo-100 text-indigo-800';
        case 'Cancelled': return 'bg-red-100 text-red-800';
        case 'Pending': return 'bg-yellow-100 text-yellow-800';
        case 'In-Process': return 'bg-purple-100 text-purple-800';
        case 'Paid': return 'bg-green-100 text-green-800';
        case 'Partial': return 'bg-orange-100 text-orange-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

// Determine current page for active class
$current_page = basename($_SERVER["PHP_SELF"]);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .card-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .card-body { padding: 20px 24px; }
        .appointment-item, .patient-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .appointment-item:last-child, .patient-item:last-child { border-bottom: none; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff/staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>

            <main class="main-content">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900">Staff Dashboard</h1>
                        <p class="text-gray-500 mt-1">
                            Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>! Here's what's happening today.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all text-sm font-medium text-gray-700">
                            <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                            <?php echo date("M d, Y"); ?>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
                    <div class="stat-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="stat-label">Total Patients</div>
                                <div class="stat-number"><?php echo $totalPatientsCount; ?></div>
                                <div class="stat-change text-xs text-gray-500 mt-1">Registered</div>
                            </div>
                            <div class="stat-icon bg-blue-50 text-blue-600">
                                <i data-lucide="user-round" class="w-6 h-6"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="stat-label">Total Doctors</div>
                                <div class="stat-number"><?php echo $totalDoctorsCount; ?></div>
                                <div class="stat-change text-xs text-gray-500 mt-1">On Staff</div>
                            </div>
                            <div class="stat-icon bg-green-50 text-green-600">
                                <i data-lucide="stethoscope" class="w-6 h-6"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="stat-label">Total Appointments</div>
                                <div class="stat-number"><?php echo $totalAppointmentsCount; ?></div>
                                <div class="stat-change text-xs text-gray-500 mt-1">All time</div>
                            </div>
                            <div class="stat-icon bg-purple-50 text-purple-600">
                                <i data-lucide="calendar" class="w-6 h-6"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="stat-label">Pending Bills</div>
                                <div class="stat-number"><?php echo $pendingBillsCount; ?></div>
                                <div class="stat-change text-xs text-amber-600 mt-1">Need attention</div>
                            </div>
                            <div class="stat-icon bg-amber-50 text-amber-600">
                                <i data-lucide="receipt" class="w-6 h-6"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="stat-label">Pending Lab Orders</div>
                                <div class="stat-number"><?php echo $pendingLabOrdersCount; ?></div>
                                <div class="stat-change text-xs text-red-600 mt-1">Awaiting results</div>
                            </div>
                            <div class="stat-icon bg-red-50 text-red-600">
                                <i data-lucide="flask" class="w-6 h-6"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-7 gap-6 mb-6">
                    <!-- Overview Chart -->
                    <div class="card lg:col-span-4">
                        <div class="card-header">
                            <h3>Overview</h3>
                            <span class="text-sm text-gray-500">Patient visits & revenue</span>
                        </div>
                        <div class="card-body">
                            <div class="h-[300px] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                                <div class="text-center text-gray-400">
                                    <i data-lucide="bar-chart-3" class="w-12 h-12 mx-auto mb-2 text-gray-300"></i>
                                    <p>Chart will be displayed here</p>
                                    <p class="text-sm">(Integration with chart library like Chart.js)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Appointments -->
                    <div class="card lg:col-span-3">
                        <div class="card-header">
                            <h3>Recent Appointments</h3>
                            <span class="text-sm text-gray-500">
                                <?php echo count($recentAppointments); ?> recent
                            </span>
                        </div>
                        <div class="card-body max-h-[300px] overflow-y-auto custom-scrollbar">
                            <?php if (empty($recentAppointments)): ?>
                                <p class="text-center text-gray-500 py-4">No recent appointments.</p>
                            <?php else: ?>
                                <?php foreach ($recentAppointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div>
                                            <p class="font-medium text-gray-800">
                                                <?php echo htmlspecialchars($appointment["appointment_no"] ?? 'N/A'); ?> - 
                                                <?php echo htmlspecialchars($appointment["patient_name"] ?? 'Unknown Patient'); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars(date("M d, Y", strtotime($appointment["appointment_date"]))); ?> 
                                                at <?php echo htmlspecialchars(date("h:i A", strtotime($appointment["appointment_time"]))); ?>
                                            </p>
                                        </div>
                                        <span class="status-badge <?php echo getStatusBadgeClass($appointment["status"] ?? 'Pending'); ?>">
                                            <?php echo htmlspecialchars($appointment["status"] ?? 'Pending'); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Patients -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Patients</h3>
                        <span class="text-sm text-gray-500">
                            <?php echo count($recentPatients); ?> new patients
                        </span>
                    </div>
                    <div class="card-body max-h-[250px] overflow-y-auto custom-scrollbar">
                        <?php if (empty($recentPatients)): ?>
                            <p class="text-center text-gray-500 py-4">No recent patients.</p>
                        <?php else: ?>
                            <?php foreach ($recentPatients as $patient): ?>
                                <div class="patient-item">
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($patient["patient_name"]); ?></p>
                                        <p class="text-sm text-gray-500">Registered on <?php echo htmlspecialchars(date("M d, Y", strtotime($patient["created_at"]))); ?></p>
                                    </div>
                                    <a href="patient_profile.php?id=<?php echo htmlspecialchars($patient["patient_id"]); ?>" class="text-blue-600 hover:underline text-sm">View Profile</a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>