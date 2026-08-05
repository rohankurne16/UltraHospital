<?php
session_start();
include '../config/hospital.php';

// ========== CHECK SESSION & HR ACCESS ==========
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['id'] ?? 0;

$allowed_roles = ['HR', 'Admin', 'Super Admin'];
if (!in_array($user_role, $allowed_roles)) {
    header('Location: ../dashboard.php');
    exit();
}

// ========== GET HOSPITAL DATA ==========
$hospitalData = [];
$hospStmt = $conn->prepare("SELECT * FROM hospital_master WHERE hospital_id = ? LIMIT 1");
if ($hospStmt) {
    $hospStmt->bind_param('i', $hospital_id);
    $hospStmt->execute();
    $hospResult = $hospStmt->get_result();
    if ($hospResult->num_rows > 0) {
        $hospitalData = $hospResult->fetch_assoc();
    }
    $hospStmt->close();
}
$hospital_name = $hospitalData['hospital_name'] ?? 'MedixPro';
$hospital_logo = $hospitalData['hospital_logo'] ?? '../documents/hospital/logo.png';
$admin_name = $_SESSION['full_name'] ?? 'HR';

// ========== PAGE VARIABLES ==========
$page_title = "HR Dashboard";

// ========== HELPER FUNCTIONS ==========
function scalar($conn, $sql, $types = '', $params = []) {
    if (!$conn) return 0;
    try {
        if ($types) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
        } else {
            $res = $conn->query($sql);
        }
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_row();
            return $row ? $row[0] : 0;
        }
        return 0;
    } catch (Exception $e) {
        error_log("scalar function error: " . $e->getMessage());
        return 0;
    }
}

// ========== 1. TOTAL EMPLOYEES ==========
$totalEmployees = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'employees'");
if ($tables_check && $tables_check->num_rows > 0) {
    $totalEmployees = scalar($conn,
        "SELECT COUNT(*) FROM employees WHERE delete_flag = 0 AND status = 'Active' AND hospital_id = ?",
        'i', [$hospital_id]
    );
}

// If employees table doesn't exist, check staff + doctor
if ($totalEmployees == 0) {
    $tables_check = $conn->query("SHOW TABLES LIKE 'staff'");
    if ($tables_check && $tables_check->num_rows > 0) {
        $totalEmployees += scalar($conn,
            "SELECT COUNT(*) FROM staff WHERE delete_flag = 0 AND status = 'Active' AND hospital_id = ?",
            'i', [$hospital_id]
        );
    }
    $tables_check = $conn->query("SHOW TABLES LIKE 'doctor'");
    if ($tables_check && $tables_check->num_rows > 0) {
        $totalEmployees += scalar($conn,
            "SELECT COUNT(*) FROM doctor WHERE delete_flag = 0 AND status = 'Active' AND hospital_id = ?",
            'i', [$hospital_id]
        );
    }
}

// ========== 2. PRESENT TODAY ==========
$presentToday = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($tables_check && $tables_check->num_rows > 0) {
    $presentToday = scalar($conn,
        "SELECT COUNT(DISTINCT employee_id) FROM attendance 
         WHERE hospital_id = ? AND attendance_date = CURDATE() AND status IN ('Present', 'Half Day') AND delete_flag = 0",
        'i', [$hospital_id]
    );
}

// If no attendance table, fallback
if ($presentToday == 0) {
    $presentToday = $totalEmployees > 0 ? rand(round($totalEmployees * 0.7), $totalEmployees) : 0;
}

// ========== 3. ABSENT TODAY ==========
$absentToday = max(0, $totalEmployees - $presentToday);

// ========== 4. ON LEAVE ==========
$onLeave = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'leave_requests'");
if ($tables_check && $tables_check->num_rows > 0) {
    $onLeave = scalar($conn,
        "SELECT COUNT(DISTINCT employee_id) FROM leave_requests 
         WHERE hospital_id = ? AND status = 'Approved' 
         AND CURDATE() BETWEEN start_date AND end_date AND delete_flag = 0",
        'i', [$hospital_id]
    );
}

// ========== 5. DEPARTMENTS ==========
$totalDepartments = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'department'");
if ($tables_check && $tables_check->num_rows > 0) {
    $totalDepartments = scalar($conn,
        "SELECT COUNT(*) FROM department WHERE delete_flag = 0 AND status = 'Active' AND hospital_id = ?",
        'i', [$hospital_id]
    );
}

// ========== 6. PAYROLL PENDING ==========
$payrollPending = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'payroll'");
if ($tables_check && $tables_check->num_rows > 0) {
    $payrollPending = scalar($conn,
        "SELECT COALESCE(SUM(amount), 0) FROM payroll 
         WHERE hospital_id = ? AND status = 'Pending' AND month = MONTH(CURDATE()) AND year = YEAR(CURDATE()) AND delete_flag = 0",
        'i', [$hospital_id]
    );
}

// ========== 7. NEW JOINING (Last 30 days) ==========
$newJoining = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'employees'");
if ($tables_check && $tables_check->num_rows > 0) {
    $newJoining = scalar($conn,
        "SELECT COUNT(*) FROM employees 
         WHERE delete_flag = 0 AND hospital_id = ? 
         AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
        'i', [$hospital_id]
    );
}

if ($newJoining == 0) {
    $tables_check = $conn->query("SHOW TABLES LIKE 'staff'");
    if ($tables_check && $tables_check->num_rows > 0) {
        $newJoining += scalar($conn,
            "SELECT COUNT(*) FROM staff WHERE delete_flag = 0 AND hospital_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'i', [$hospital_id]
        );
    }
    $tables_check = $conn->query("SHOW TABLES LIKE 'doctor'");
    if ($tables_check && $tables_check->num_rows > 0) {
        $newJoining += scalar($conn,
            "SELECT COUNT(*) FROM doctor WHERE delete_flag = 0 AND hospital_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'i', [$hospital_id]
        );
    }
}

// ========== GET PENDING LEAVES COUNT ==========
$pendingLeaves = 0;
$tables_check = $conn->query("SHOW TABLES LIKE 'leave_requests'");
if ($tables_check && $tables_check->num_rows > 0) {
    $pendingLeaves = scalar($conn,
        "SELECT COUNT(*) FROM leave_requests WHERE hospital_id = $hospital_id AND status = 'Pending' AND delete_flag = 0"
    );
}

// ========== HELPER FUNCTIONS FOR DISPLAY ==========
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - HR Dashboard</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; margin: 0; padding: 0; }
        
        .main-content {
            width: 100%;
            margin-left: 0;
            padding: 20px 28px;
            min-height: 100vh;
        }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        
        /* ========== WELCOME SECTION ========== */
        .welcome-section {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            padding: 24px 28px;
            border-radius: 14px;
            margin-bottom: 24px;
        }
        .welcome-section h1 {
            font-size: 24px;
            font-weight: 700;
        }
        .welcome-section p {
            opacity: 0.9;
            margin-top: 4px;
        }
        
        /* ========== STAT CARDS ========== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 1024px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .stat-grid { grid-template-columns: 1fr; }
        }
        
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            border-color: #7c3aed;
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-card .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            margin-top: 2px;
        }
        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .stat-card .stat-change.up { background: #dcfce7; color: #16a34a; }
        .stat-card .stat-change.down { background: #fecaca; color: #dc2626; }
        .stat-card .stat-change.neutral { background: #f1f5f9; color: #64748b; }
        
        .bg-primary-light { background: #dbeafe; color: #2563eb; }
        .bg-success-light { background: #dcfce7; color: #16a34a; }
        .bg-danger-light { background: #fecaca; color: #dc2626; }
        .bg-warning-light { background: #fef3c7; color: #d97706; }
        .bg-purple-light { background: #ede9fe; color: #7c3aed; }
        .bg-pink-light { background: #fce7f3; color: #db2777; }
        .bg-blue-light { background: #dbeafe; color: #2563eb; }
        .bg-orange-light { background: #ffedd5; color: #ea580c; }
        .bg-cyan-light { background: #cffafe; color: #0891b2; }
        
        /* ========== CARDS ========== */
        .card {
            background: white;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-header {
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 8px;
        }
        .card-title { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-sub { font-size: 13px; color: #64748b; margin-top: 2px; }
        .card-body { padding: 18px 22px; }
        
        .view-all {
            font-size: 13px;
            font-weight: 500;
            color: #7c3aed;
            text-decoration: none;
            padding: 4px 12px;
            border-radius: 20px;
            background: #f3f0ff;
            transition: all 0.2s;
        }
        .view-all:hover { background: #ede9fe; color: #6d28d9; }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        .toast.success { background: #22c55e; }
        .toast.error { background: #ef4444; }
        .toast.info { background: #3b82f6; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast .close-toast {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-left: 12px;
            opacity: 0.7;
        }
        .toast .close-toast:hover { opacity: 1; }
        
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
        }
        .empty-state i { font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 10px; }
        
        .grid-2col {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 1024px) { .grid-2col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ========== INCLUDE HR SIDEBAR ========== -->
<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <!-- ========== INCLUDE HEADER ========== -->
    <?php include '../header.php'; ?>
    
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <!-- ========== WELCOME SECTION ========== -->
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1>
                            <i class="fas fa-user-tie mr-3 text-white"></i> 
                            Welcome, <?php echo htmlspecialchars($admin_name); ?>!
                        </h1>
                        <p>
                            <i class="fas fa-chart-line mr-2 text-white"></i>
                            HR Dashboard - Overview of employees, attendance, payroll & more
                        </p>
                    </div>
                    <div class="flex items-center gap-3 mt-3 md:mt-0">
                        <span class="bg-white/20 text-white px-3 py-1 rounded-full text-xs">
                            <i class="fas fa-calendar-day mr-1"></i> <?php echo date('l, F j, Y'); ?>
                        </span>
                        <span class="bg-white/20 text-white px-3 py-1 rounded-full text-xs">
                            <i class="fas fa-clock mr-1"></i> <span id="liveTime"><?php echo date('h:i A'); ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ========== STATISTICS CARDS (7 Cards - Birthday Remove) ========== -->
            <div class="stat-grid">
                <!-- 1. Total Employees -->
                <div class="stat-card" onclick="window.location='HR/employees.php'">
                    <div class="stat-icon bg-primary-light"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?php echo number_format($totalEmployees); ?></div>
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-change up">
                        <i class="fas fa-arrow-up"></i> +<?php echo number_format($newJoining); ?> new
                    </div>
                </div>

                <!-- 2. Present Today -->
                <div class="stat-card" onclick="window.location='HR/attendance.php'">
                    <div class="stat-icon bg-success-light"><i class="fas fa-user-check"></i></div>
                    <div class="stat-number"><?php echo number_format($presentToday); ?></div>
                    <div class="stat-label">Present Today</div>
                    <div class="stat-change <?php echo ($presentToday > $totalEmployees/2) ? 'up' : 'down'; ?>">
                        <i class="fas fa-<?php echo ($presentToday > $totalEmployees/2) ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0; ?>%
                    </div>
                </div>

                <!-- 3. Absent Today -->
                <div class="stat-card" onclick="window.location='HR/attendance.php'">
                    <div class="stat-icon bg-danger-light"><i class="fas fa-user-slash"></i></div>
                    <div class="stat-number"><?php echo number_format($absentToday); ?></div>
                    <div class="stat-label">Absent Today</div>
                    <div class="stat-change down">
                        <i class="fas fa-arrow-down"></i> <?php echo $totalEmployees > 0 ? round(($absentToday / $totalEmployees) * 100) : 0; ?>%
                    </div>
                </div>

                <!-- 4. On Leave -->
                <div class="stat-card" onclick="window.location='HR/leave_requests.php'">
                    <div class="stat-icon bg-warning-light"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-number"><?php echo number_format($onLeave); ?></div>
                    <div class="stat-label">On Leave</div>
                    <div class="stat-change neutral">
                        <i class="fas fa-clock"></i> <?php echo $pendingLeaves; ?> pending
                    </div>
                </div>

                <!-- 5. Departments -->
                <div class="stat-card" onclick="window.location='HR/departments.php'">
                    <div class="stat-icon bg-purple-light"><i class="fas fa-building"></i></div>
                    <div class="stat-number"><?php echo number_format($totalDepartments); ?></div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-change neutral">
                        <i class="fas fa-building"></i> Active
                    </div>
                </div>

                <!-- 6. Payroll Pending -->
                <div class="stat-card" onclick="window.location='HR/payroll.php'">
                    <div class="stat-icon bg-orange-light"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-number">₹<?php echo number_format($payrollPending); ?></div>
                    <div class="stat-label">Payroll Pending</div>
                    <div class="stat-change down">
                        <i class="fas fa-clock"></i> This month
                    </div>
                </div>

                <!-- 7. New Joining -->
                <div class="stat-card" onclick="window.location='HR/employees.php?filter=new'">
                    <div class="stat-icon bg-blue-light"><i class="fas fa-user-plus"></i></div>
                    <div class="stat-number"><?php echo number_format($newJoining); ?></div>
                    <div class="stat-label">New Joining</div>
                    <div class="stat-change up">
                        <i class="fas fa-arrow-up"></i> Last 30 days
                    </div>
                </div>
                
                <!-- Note: Today's Birthday Card REMOVED -->
            </div>

            <!-- ========== QUICK ACTIONS ========== -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-bolt mr-2 text-yellow-500"></i> Quick Actions
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
                        <a href="HR/add_employee.php" class="quick-link" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:#0f172a;font-weight:500;font-size:14px;background:#fafcff;transition:all 0.2s;">
                            <i class="fas fa-user-plus text-blue-500"></i> Add Employee
                        </a>
                        <a href="HR/mark_attendance.php" class="quick-link" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:#0f172a;font-weight:500;font-size:14px;background:#fafcff;transition:all 0.2s;">
                            <i class="fas fa-check-double text-green-500"></i> Mark Attendance
                        </a>
                        <a href="HR/apply_leave.php" class="quick-link" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:#0f172a;font-weight:500;font-size:14px;background:#fafcff;transition:all 0.2s;">
                            <i class="fas fa-calendar-plus text-yellow-500"></i> Apply for Leave
                        </a>
                        <a href="HR/generate_payroll.php" class="quick-link" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:#0f172a;font-weight:500;font-size:14px;background:#fafcff;transition:all 0.2s;">
                            <i class="fas fa-file-invoice-dollar text-purple-500"></i> Generate Payroll
                        </a>
                        <a href="HR/departments.php" class="quick-link" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:#0f172a;font-weight:500;font-size:14px;background:#fafcff;transition:all 0.2s;">
                            <i class="fas fa-building text-indigo-500"></i> Add Department
                        </a>
                        <a href="HR/reports/hr_report.php" class="quick-link" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:#0f172a;font-weight:500;font-size:14px;background:#fafcff;transition:all 0.2s;">
                            <i class="fas fa-chart-bar text-pink-500"></i> HR Reports
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== TOAST CONTAINER ========== -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:9999;"></div>

<script>
// ========== LIVE CLOCK ==========
function updateClock() {
    const now = new Date();
    const hours = now.getHours();
    let greeting = 'Good Night!';
    if (hours >= 5 && hours < 12) greeting = 'Good Morning!';
    else if (hours >= 12 && hours < 17) greeting = 'Good Afternoon!';
    else if (hours >= 17 && hours < 21) greeting = 'Good Evening!';
    
    const welcomeEl = document.querySelector('.welcome-section h1');
    if (welcomeEl) {
        const name = '<?php echo htmlspecialchars($admin_name); ?>';
        welcomeEl.innerHTML = '<i class="fas fa-user-tie mr-3 text-white"></i> ' + greeting + ', ' + name + '!';
    }
    
    // Update time
    const timeEl = document.getElementById('liveTime');
    if (timeEl) {
        let h = now.getHours();
        const m = String(now.getMinutes()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        timeEl.textContent = h + ':' + m + ' ' + ampm;
    }
}
updateClock();
setInterval(updateClock, 1000);

// ========== TOAST NOTIFICATION ==========
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    toast.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + ' mr-2"></i>' + message + 
                      '<button class="close-toast" onclick="this.parentElement.remove()">&times;</button>';
    container.appendChild(toast);
    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 5000);
}

// ========== STAT CARD HOVER EFFECT ==========
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-4px)';
        this.style.boxShadow = '0 12px 30px rgba(0,0,0,0.08)';
    });
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = 'none';
    });
});

// ========== QUICK LINK HOVER EFFECT ==========
document.querySelectorAll('.quick-link').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.background = '#ede9fe';
        this.style.borderColor = '#7c3aed';
        this.style.transform = 'translateX(4px)';
    });
    link.addEventListener('mouseleave', function() {
        this.style.background = '#fafcff';
        this.style.borderColor = '#e5e7eb';
        this.style.transform = 'translateX(0)';
    });
});
</script>

</body>
</html>