<?php
session_start();
include "../config/hospital.php";

if (!isset($_SESSION["id"])) {
    header("Location:../index.php");
    exit();
}

if ($_SESSION["id"]) {
    $id = $_SESSION["id"];
    $hid = $_SESSION["hospital_id"];

    $view_patient = "SELECT * FROM patients WHERE register_id='$id' AND (delete_flag=0 OR delete_flag IS NULL)";
    $data = $conn->query($view_patient);

    if ($data->num_rows > 0) {
        while ($row = $data->fetch_assoc()) {
            $_SESSION["patient_id"] = $row["patient_id"];
            $_SESSION["patient_name"] = $row["patient_name"];
            $_SESSION["patient_image"] = $row["patient_image"];
            $_SESSION["dob"] = $row["date_of_birth"];
            $_SESSION["age"] = $row["age"];
            $_SESSION["blood_group"] = $row["blood_group"];
            $_SESSION["gender"] = $row["gender"];
            $_SESSION["address"] = $row["address"];
            $_SESSION["emergency_contact"] = $row["emergency_contact"];
            $_SESSION["medical_history"] = $row["medical_history"];
            $_SESSION["medications"] = explode(",", $row["medical_history"]);
            $_SESSION["allergy"] = $row["allergy"];
            $_SESSION["allergies"] = explode(",", $row["allergy"]);
            $_SESSION["email"] = $row["email"];
            $_SESSION["mobile"] = $row["mobile"];
            $_SESSION["status"] = isset($row["status"]) ? $row["status"] : 'Active';
            $_SESSION["status_class"] = ($_SESSION["status"] == 'Active') ? 'status-active' : 'status-inactive';

            $patient_id = $_SESSION["patient_id"];

            date_default_timezone_set('Asia/Kolkata');
            $hour = date('H');
            if ($hour < 12) {
                $greeting = "Good Morning";
                $greeting_icon = "🌅";
                $gradient_class = "from-blue-500 via-purple-500 to-pink-500";
            } elseif ($hour < 17) {
                $greeting = "Good Afternoon";
                $greeting_icon = "☀️";
                $gradient_class = "from-orange-400 via-pink-500 to-purple-500";
            } elseif ($hour < 20) {
                $greeting = "Good Evening";
                $greeting_icon = "🌆";
                $gradient_class = "from-purple-500 via-pink-500 to-orange-400";
            } else {
                $greeting = "Good Night";
                $greeting_icon = "🌙";
                $gradient_class = "from-indigo-600 via-purple-600 to-blue-600";
            }

            // Fetch statistics
            $total_appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
            $completed_appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE patient_id='$patient_id' AND status='Completed' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
            $pending_appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE patient_id='$patient_id' AND status='Pending' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
            $total_prescriptions = $conn->query("SELECT COUNT(*) AS total FROM prescription_master WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
            $total_reports = $conn->query("SELECT COUNT(*) AS total FROM lab_reports WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
            $total_bills = $conn->query("SELECT COUNT(*) AS total FROM billing WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital["hospital_name"]; ?> - Patient Dashboard</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital["hospital_logo"]; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f4f8; }
        
        /* Gradient Greeting Card */
        .greeting-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
            overflow: hidden;
        }
        .greeting-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(25deg);
        }
        .greeting-gradient::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 40%;
            height: 150%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
            transform: rotate(-15deg);
        }
        .greeting-content {
            position: relative;
            z-index: 1;
        }
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 0;
        }
        .floating-shapes span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }
        .floating-shapes span:nth-child(1) { top: 20%; left: 10%; width: 30px; height: 30px; animation-delay: 0s; }
        .floating-shapes span:nth-child(2) { top: 60%; right: 15%; width: 40px; height: 40px; animation-delay: 5s; }
        .floating-shapes span:nth-child(3) { bottom: 30%; left: 20%; width: 25px; height: 25px; animation-delay: 10s; }
        .floating-shapes span:nth-child(4) { top: 40%; right: 30%; width: 35px; height: 35px; animation-delay: 15s; }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg) scale(1); }
            50% { transform: translateY(-30px) rotate(180deg) scale(1.2); }
            100% { transform: translateY(0) rotate(360deg) scale(1); }
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .stat-card:hover::before {
            opacity: 1;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.15);
            border-color: transparent;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }
        .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.2;
            background: linear-gradient(135deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-scheduled { background: #e0e7ff; color: #3730a3; }
        .status-in-progress { background: #fef3c7; color: #92400e; }

        /* Cards */
        .dashboard-card {
            background: white;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        .dashboard-card:hover {
            box-shadow: 0 12px 30px -8px rgba(0,0,0,0.08);
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .card-body {
            padding: 1.25rem 1.5rem;
        }

        /* Bill Card */
        .bill-card-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            position: relative;
            overflow: hidden;
        }
        .bill-card-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        /* Report Items */
        .report-item {
            transition: all 0.2s ease;
            cursor: pointer;
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px solid #f1f5f9;
        }
        .report-item:hover {
            background: #f8fafc;
            border-color: #667eea;
            transform: translateX(4px);
        }

        /* Document Items */
        .doc-item {
            transition: all 0.2s ease;
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px solid #f1f5f9;
            cursor: pointer;
        }
        .doc-item:hover {
            background: #f8fafc;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .chart-container {
            height: 220px;
            position: relative;
        }

        /* Action Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.625rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -6px rgba(102, 126, 234, 0.5);
        }
        .btn-secondary {
            background: white;
            color: #1e293b;
            padding: 0.625rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Timeline dots */
        .timeline-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .timeline-dot.blue { background: #3b82f6; }
        .timeline-dot.green { background: #22c55e; }
        .timeline-dot.purple { background: #a855f7; }
        .timeline-dot.orange { background: #f59e0b; }
        .timeline-dot.pink { background: #ec4899; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-[#f0f4f8]">
        <?php include "../header.php"; ?>
        <div class="flex flex-1 items-start">
            <?php include "../Sidebar.php"; ?>
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    
                    <!-- Gradient Greeting Row -->
                    <div class="greeting-gradient rounded-2xl mb-8 shadow-xl shadow-purple-500/20">
                        <div class="floating-shapes">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="greeting-content p-6 md:p-8">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="text-4xl md:text-5xl"><?php echo $greeting_icon; ?></span>
                                        <div>
                                            <h1 class="text-2xl md:text-4xl font-extrabold text-white drop-shadow-lg">
                                                <?php echo $greeting . "! 👋"; ?>
                                            </h1>
                                            <p class="text-white/90 text-lg md:text-xl font-medium mt-1">
                                                <?php echo $_SESSION["name"]; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-white/80 text-sm mt-1 flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4"></i>
                                        <?php echo date('l, F j, Y'); ?>
                                        <span class="w-1 h-1 bg-white/40 rounded-full"></span>
                                        <i data-lucide="clock" class="w-4 h-4"></i>
                                        <?php echo date('h:i A'); ?>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button class="btn-primary shadow-lg shadow-purple-500/30" onclick="window.location.href='add_appointment.php?patient_id=<?php echo $patient_id ?>'">
                                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Book Appointment
                                    </button>
                                    <button class="btn-secondary bg-white/20 backdrop-blur-sm border-white/30 text-white hover:bg-white/30" onclick="window.location.href='profile.php'">
                                        <i data-lucide="user" class="w-4 h-4"></i> My Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8"  style="cursor:pointer;">
                        <div class="stat-card">
                            <div class="flex items-center justify-between" onclick="window.location.href='appointments.php';">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Appointments</span>
                                <div class="stat-icon bg-blue-50">
                                    <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
                                </div>
                            </div>
                            <p class="stat-number mt-2"><?php echo $total_appointments; ?></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="timeline-dot green"></span>
                                <span class="text-[10px] text-gray-500 font-medium"><?php echo $completed_appointments; ?> completed</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between" onclick="window.location.href='prescriptions.php';">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Prescriptions</span>
                                <div class="stat-icon bg-purple-50">
                                    <i data-lucide="pill" class="w-5 h-5 text-purple-600"></i>
                                </div>
                            </div>
                            <p class="stat-number mt-2"><?php echo $total_prescriptions; ?></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="timeline-dot purple"></span>
                                <span class="text-[10px] text-gray-500 font-medium">Active</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between" onclick="window.location.href='lab_report.php';">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lab Reports</span>
                                <div class="stat-icon bg-green-50">
                                    <i data-lucide="microscope" class="w-5 h-5 text-green-600"></i>
                                </div>
                            </div>
                            <p class="stat-number mt-2"><?php echo $total_reports; ?></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="timeline-dot green"></span>
                                <span class="text-[10px] text-gray-500 font-medium">Available</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between" onclick="window.location.href='view_bills.php';">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bills</span>
                                <div class="stat-icon bg-orange-50">
                                    <i data-lucide="credit-card" class="w-5 h-5 text-orange-600"></i>
                                </div>
                            </div>
                            <p class="stat-number mt-2"><?php echo $total_bills; ?></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="timeline-dot orange"></span>
                                <span class="text-[10px] text-gray-500 font-medium">Invoices</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between" onclick="window.location.href='show_my_docs.php';">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Documents</span>
                                <div class="stat-icon bg-indigo-50">
                                    <i data-lucide="folder" class="w-5 h-5 text-indigo-600"></i>
                                </div>
                            </div>
                            <p class="stat-number mt-2">
                                <?php 
                                $doc_count = $conn->query("SELECT COUNT(*) AS total FROM patient_documents WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
                                echo $doc_count; 
                                ?>
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="timeline-dot blue"></span>
                                <span class="text-[10px] text-gray-500 font-medium">Uploaded</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending Bills</span>
                                <div class="stat-icon bg-red-50">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                                </div>
                            </div>
                            <p class="stat-number mt-2">₹<?php 
                                $pending = $conn->query("SELECT COALESCE(SUM(pending_amount),0) AS total FROM billing WHERE patient_id='$patient_id'")->fetch_assoc()["total"];
                                echo number_format($pending, 0); 
                            ?></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="timeline-dot <?php echo $pending > 0 ? 'pink' : 'green'; ?>"></span>
                                <span class="text-[10px] <?php echo $pending > 0 ? 'text-red-600' : 'text-green-600'; ?> font-medium">
                                    <?php echo $pending > 0 ? 'Due' : 'All cleared'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 1: Appointment Chart + Bill Summary -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Chart -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i data-lucide="bar-chart-2" class="w-5 h-5 text-blue-600"></i>
                                        Appointment Statistics
                                    </h3>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full font-medium">Last 6 Months</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="appointmentChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Summary -->
                        <div class="dashboard-card overflow-hidden">
                            <?php 
                            $pending_amount = "SELECT COALESCE(SUM(pending_amount),0) AS pending_sum FROM billing WHERE patient_id='$patient_id'";
                            $pending_sum_data = $conn->query($pending_amount);
                            if ($pending_sum_data->num_rows > 0) {
                                while ($sum = $pending_sum_data->fetch_assoc()) {
                            ?>
                            <div class="bill-card-gradient p-6 text-white">
                                <div class="flex items-center justify-between relative z-10">
                                    <div>
                                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Outstanding</p>
                                        <p class="text-3xl font-bold mt-1">₹<?php echo number_format($sum["pending_sum"], 0); ?></p>
                                    </div>
                                    <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center border border-white/20">
                                        <i data-lucide="credit-card" class="w-7 h-7 text-white"></i>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-2 relative z-10">
                                    <span class="timeline-dot <?php echo $sum['pending_sum'] > 0 ? 'pink' : 'green'; ?>"></span>
                                    <p class="text-xs <?php echo $sum['pending_sum'] > 0 ? 'text-yellow-400' : 'text-green-400'; ?> font-medium">
                                        <?php echo $sum['pending_sum'] > 0 ? 'Payment pending' : 'All bills cleared'; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="space-y-2">
                                    <?php 
                                    $billing = "SELECT * FROM billing WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL) ORDER BY created_at DESC LIMIT 3";
                                    $bills = $conn->query($billing);
                                    if ($bills->num_rows > 0) {
                                        while ($row = $bills->fetch_assoc()) {
                                    ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition border border-transparent hover:border-gray-200">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($row["service_name"]); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo date('d M Y', strtotime($row["created_at"])); ?></p>
                                        </div>
                                        <div class="flex items-center gap-3 ml-4">
                                            <span class="font-bold text-gray-900 text-sm">₹<?php echo number_format($row["total"], 0); ?></span>
                                            <?php if($row["pending_amount"] > 0) { ?>
                                            <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[9px] font-bold rounded-full uppercase tracking-wider">Due</span>
                                            <?php } else { ?>
                                            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[9px] font-bold rounded-full uppercase tracking-wider">Paid</span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php } } else { ?>
                                    <div class="text-center py-8">
                                        <i data-lucide="file-minus" class="w-12 h-12 mx-auto text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-400">No billing records</p>
                                    </div>
                                    <?php } ?>
                                </div>
                                <button class="w-full mt-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 transition-all hover:scale-[1.02] flex items-center justify-center gap-2" onclick="window.location.href='view_bills.php'">
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i> View All Bills
                                </button>
                            </div>
                            <?php } } ?>
                        </div>
                    </div>

                    <!-- Row 2: Upcoming Appointments + Recent Prescriptions -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Upcoming Appointments -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                    <i data-lucide="calendar-check" class="w-5 h-5 text-green-600"></i>
                                    Upcoming Appointments
                                    <span class="ml-auto text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
                                        <?php 
                                        $upcoming_count = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE patient_id='$patient_id' AND appointment_date >= CURDATE() AND (delete_flag=0 OR delete_flag IS NULL)")->fetch_assoc()["total"];
                                        echo $upcoming_count; 
                                        ?>
                                    </span>
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <?php 
                                    $upcoming_appointments = "
                                        SELECT a.*, d.doctor_name
                                        FROM appointments a
                                        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
                                        WHERE a.patient_id='$patient_id'
                                        AND a.appointment_date >= CURDATE()
                                        AND (a.delete_flag=0 OR a.delete_flag IS NULL)
                                        ORDER BY a.appointment_date ASC
                                        LIMIT 4";
                                    $upcoming_data = $conn->query($upcoming_appointments);
                                    if ($upcoming_data->num_rows > 0) {
                                        while ($row = $upcoming_data->fetch_assoc()) {
                                    ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex flex-col items-center justify-center text-blue-600 font-bold flex-shrink-0">
                                                <span class="text-sm leading-none"><?php echo date('d', strtotime($row["appointment_date"])); ?></span>
                                                <span class="text-[8px] uppercase tracking-wider"><?php echo date('M', strtotime($row["appointment_date"])); ?></span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($row["reason"]); ?></p>
                                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                                    <?php echo date('h:i A', strtotime($row["appointment_time"])); ?>
                                                    <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                                    <i data-lucide="user" class="w-3 h-3"></i>
                                                    Dr. <?php echo htmlspecialchars($row["doctor_name"]); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($row["status"]); ?>">
                                            <?php echo htmlspecialchars($row["status"]); ?>
                                        </span>
                                    </div>
                                    <?php 
                                        }
                                    } else { ?>
                                    <div class="text-center py-8">
                                        <i data-lucide="calendar-off" class="w-12 h-12 mx-auto text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-500 font-medium">No upcoming appointments</p>
                                        <button class="mt-3 text-xs text-blue-600 font-semibold hover:underline" onclick="window.location.href='add_appointment.php?patient_id=<?php echo $patient_id ?>'">Book one now →</button>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php if ($upcoming_data->num_rows > 0) { ?>
                                <button class="w-full mt-4 py-2.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-xl transition border-2 border-blue-100 hover:border-blue-300 flex items-center justify-center gap-2" onclick="window.location.href='appointments.php'">
                                    View All Appointments <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </button>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Recent Prescriptions -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                    <i data-lucide="pill" class="w-5 h-5 text-purple-600"></i>
                                    Recent Prescriptions
                                    <span class="ml-auto text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-medium">
                                        <?php echo $total_prescriptions; ?>
                                    </span>
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <?php 
                                    $recent_prescriptions = "
                                        SELECT p.*, d.doctor_name
                                        FROM prescription_master p
                                        LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
                                        WHERE p.patient_id='$patient_id'
                                        AND (p.delete_flag=0 OR p.delete_flag IS NULL)
                                        ORDER BY p.created_at DESC
                                        LIMIT 4";
                                    $prescriptions_data = $conn->query($recent_prescriptions);
                                    if ($prescriptions_data->num_rows > 0) {
                                        while ($row = $prescriptions_data->fetch_assoc()) {
                                    ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50/30 transition-all group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 flex items-center justify-center text-purple-600 flex-shrink-0">
                                                <i data-lucide="file-text" class="w-5 h-5"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($row["complaint"]); ?></p>
                                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                                    <i data-lucide="user" class="w-3 h-3"></i>
                                                    Dr. <?php echo htmlspecialchars($row["doctor_name"] ?? "N/A"); ?>
                                                    <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                                    <?php echo date('d M Y', strtotime($row["created_at"])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <button class="p-2 rounded-lg hover:bg-purple-100 transition-all group-hover:scale-110" onclick="window.location.href='view_prescription.php?id=<?php echo $row["prescription_id"]; ?>'">
                                            <i data-lucide="eye" class="w-4 h-4 text-purple-600"></i>
                                        </button>
                                    </div>
                                    <?php 
                                        }
                                    } else { ?>
                                    <div class="text-center py-8">
                                        <i data-lucide="file-minus" class="w-12 h-12 mx-auto text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-500 font-medium">No prescriptions yet</p>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php if ($prescriptions_data->num_rows > 0) { ?>
                                <button class="w-full mt-4 py-2.5 text-xs font-semibold text-purple-600 hover:bg-purple-50 rounded-xl transition border-2 border-purple-100 hover:border-purple-300 flex items-center justify-center gap-2" onclick="window.location.href='prescriptions.php'">
                                    View All Prescriptions <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Lab Reports + Documents -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Lab Reports -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i data-lucide="microscope" class="w-5 h-5 text-blue-600"></i>
                                        Recent Lab Reports
                                    </h3>
                                    <span class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">
                                        <?php echo $total_reports; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <?php 
                                    $show_my_reports = "
                                        SELECT
                                            lr.*,
                                            lt.test_name
                                        FROM lab_reports lr
                                        LEFT JOIN lab_order_details lod ON lr.detail_id = lod.detail_id
                                        LEFT JOIN lab_tests lt ON lod.test_id = lt.test_id
                                        WHERE lr.patient_id='$patient_id'
                                        AND lr.hospital_id='$hid'
                                        AND (lr.delete_flag=0 OR lr.delete_flag IS NULL)
                                        ORDER BY lr.report_date DESC
                                        LIMIT 4";
                                    $reports_data = $conn->query($show_my_reports);
                                    if ($reports_data->num_rows > 0) {
                                        while ($row = $reports_data->fetch_assoc()) {
                                    ?>
                                    <div class="report-item flex items-center justify-between" onclick="openReport('<?php echo $row["report_file"]; ?>', '<?php echo $row["test_name"]; ?>')">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                                                <i data-lucide="file-text" class="w-5 h-5"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($row["test_name"]); ?></p>
                                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                                    <?php echo date('d M Y', strtotime($row["report_date"])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <button class="p-2 rounded-lg hover:bg-blue-100 transition-all download-btn" onclick="event.stopPropagation(); downloadReport('<?php echo $row["report_file"]; ?>', '<?php echo $row["test_name"]; ?>')">
                                            <i data-lucide="download" class="w-4 h-4 text-blue-600"></i>
                                        </button>
                                    </div>
                                    <?php 
                                        }
                                    } else { ?>
                                    <div class="text-center py-8">
                                        <i data-lucide="file-minus" class="w-12 h-12 mx-auto text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-500 font-medium">No lab reports available</p>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php if ($reports_data->num_rows > 0) { ?>
                                <button class="w-full mt-4 py-2.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-xl transition border-2 border-blue-100 hover:border-blue-300 flex items-center justify-center gap-2" onclick="window.location.href='lab_report.php'">
                                    View All Reports <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </button>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i data-lucide="folder" class="w-5 h-5 text-orange-600"></i>
                                        My Documents
                                    </h3>
                                    <button class="text-xs bg-gradient-to-r from-orange-500 to-pink-500 text-white px-3 py-1.5 rounded-lg font-semibold hover:shadow-lg hover:shadow-orange-500/30 transition-all flex items-center gap-1" onclick="window.location.href='add_document.php'">
                                        <i data-lucide="upload" class="w-3 h-3"></i> Upload
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php
                                $my_docs_name = "SELECT * FROM patient_documents WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL) LIMIT 4";
                                $my_documents_name = $conn->query($my_docs_name);
                                if ($my_documents_name->num_rows > 0) {
                                ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <?php
                                    while ($row = $my_documents_name->fetch_assoc()) {
                                        $docid = $row["document_id"];
                                        $file_ext = pathinfo($row["document_name"], PATHINFO_EXTENSION);
                                        $icon = 'file';
                                        $color = 'text-blue-600';
                                        $bg = 'bg-blue-50';
                                        if (in_array($file_ext, ['pdf'])) { 
                                            $icon = 'file-pdf'; 
                                            $color = 'text-red-600';
                                            $bg = 'bg-red-50';
                                        }
                                        elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { 
                                            $icon = 'file-image'; 
                                            $color = 'text-green-600';
                                            $bg = 'bg-green-50';
                                        }
                                        elseif (in_array($file_ext, ['doc', 'docx'])) { 
                                            $icon = 'file-text'; 
                                            $color = 'text-blue-600';
                                            $bg = 'bg-blue-50';
                                        }
                                        elseif (in_array($file_ext, ['xls', 'xlsx'])) { 
                                            $icon = 'file-spreadsheet'; 
                                            $color = 'text-green-700';
                                            $bg = 'bg-green-50';
                                        }
                                    ?> 
                                    <div class="doc-item flex items-center gap-3" onclick="window.location.href='view_document.php?id=<?php echo $docid; ?>'">
                                        <div class="w-10 h-10 rounded-xl <?php echo $bg; ?> flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 <?php echo $color; ?>"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($row["document_name"]); ?></p>
                                            <p class="text-[9px] text-gray-400 uppercase tracking-wider"><?php echo $file_ext; ?></p>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php if ($my_documents_name->num_rows > 0) { ?>
                                <button class="w-full mt-4 py-2.5 text-xs font-semibold text-orange-600 hover:bg-orange-50 rounded-xl transition border-2 border-orange-100 hover:border-orange-300 flex items-center justify-center gap-2" onclick="window.location.href='show_my_docs.php'">
                                    View All Documents <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </button>
                                <?php } ?>
                                <?php } else { ?>
                                <div class="text-center py-8">
                                    <i data-lucide="folder-open" class="w-12 h-12 mx-auto text-gray-300 mb-2"></i>
                                    <p class="text-sm text-gray-500 font-medium">No documents uploaded</p>
                                    <button class="mt-3 text-xs text-orange-600 font-semibold hover:underline" onclick="window.location.href='add_document.php'">Upload your first document →</button>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        document.addEventListener('DOMContentLoaded', function() {
            <?php
            $months = []; $counts = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('M', strtotime("-$i months"));
                $months[] = $month;
                $count = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE patient_id='$patient_id' AND MONTH(appointment_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL $i MONTH)) AND YEAR(appointment_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL $i MONTH))")->fetch_assoc()["total"];
                $counts[] = $count;
            }
            ?>
            const ctx = document.getElementById('appointmentChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Appointments',
                        data: <?php echo json_encode($counts); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#e2e8f0',
                            padding: 12,
                            borderRadius: 12,
                            cornerRadius: 12,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' appointments';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { 
                                stepSize: 1, 
                                font: { size: 10, weight: '600' },
                                color: '#94a3b8'
                            }, 
                            grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false } 
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { 
                                font: { size: 10, weight: '600' },
                                color: '#94a3b8'
                            } 
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        });

        function openReport(fileName, testName) {
            if (!fileName) { 
                Swal.fire({
                    icon: 'info',
                    title: 'No Report Available',
                    text: 'This report file is not available for viewing.',
                    confirmButtonColor: '#667eea'
                });
                return; 
            }
            var reportPath = '../uploads/lab_reports/' + fileName;
            window.open(reportPath, '_blank');
        }

        function downloadReport(fileName, testName) {
            if (!fileName) { 
                Swal.fire({
                    icon: 'info',
                    title: 'No Report Available',
                    text: 'This report file is not available for download.',
                    confirmButtonColor: '#667eea'
                });
                return; 
            }
            var reportPath = '../uploads/lab_reports/' + fileName;
            var link = document.createElement('a');
            link.href = reportPath;
            var today = new Date();
            var dateStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
            var fileExt = fileName.split('.').pop();
            var downloadName = testName.replace(/\s+/g, '_') + '_report_' + dateStr;
            link.download = downloadName + '.' + fileExt;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
<?php
        }
    }
}
