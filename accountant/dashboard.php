<?php
session_start();
require_once __DIR__ . '/../config/hospital.php';
require_once __DIR__ . '/../config/permission.php';

// Check login
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

 $hospital_id = $_SESSION['hospital_id'] ?? 0;
 $user_name   = $_SESSION['name'] ?? 'Accountant';
 $hospital_name = $hospital['hospital_name'] ?? 'Hospital';
 $current_date = date('d M Y');
 $current_time = date('h:i A');

// FIX: Added Greeting Logic to prevent Undefined Variable warning
 $hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
    $greeting_icon = "🌅";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon";
    $greeting_icon = "☀️";
} elseif ($hour < 20) {
    $greeting = "Good Evening";
    $greeting_icon = "🌆";
} else {
    $greeting = "Good Night";
    $greeting_icon = "🌙";
}

// ============================================================
// HELPER: Safe scalar query
// ============================================================
function getScalar($conn, $sql) {
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }
    return 0;
}
function getCount($conn, $sql) {
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }
    return 0;
}

// ============================================================
// 1. KPI QUERIES (Using the `bills` table)
// ============================================================
 $today = date('Y-m-d');
 $current_month = date('m');
 $current_year  = date('Y');

 $todayRevenue = getScalar($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND DATE(bill_date) = '$today' AND delete_flag = 0");
 $pendingBillsCount = getCount($conn, "SELECT COUNT(*) as count FROM bills WHERE hospital_id = $hospital_id AND balance_amount > 0 AND delete_flag = 0");
 $outstandingAmount = getScalar($conn, "SELECT COALESCE(SUM(balance_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND balance_amount > 0 AND delete_flag = 0");
 $overdueBillsCount = getCount($conn, "SELECT COUNT(*) as count FROM bills WHERE hospital_id = $hospital_id AND balance_amount > 0 AND bill_date < DATE_SUB(NOW(), INTERVAL 15 DAY) AND delete_flag = 0");
 $paymentsToday = getScalar($conn, "SELECT COALESCE(SUM(paid_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND DATE(bill_date) = '$today' AND paid_amount > 0 AND delete_flag = 0");
 $transactionsToday = getCount($conn, "SELECT COUNT(*) as count FROM bills WHERE hospital_id = $hospital_id AND DATE(bill_date) = '$today' AND delete_flag = 0");
 $monthlyIncome = getScalar($conn, "SELECT COALESCE(SUM(paid_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND MONTH(bill_date) = $current_month AND YEAR(bill_date) = $current_year AND delete_flag = 0");
 $cashCollection = getScalar($conn, "SELECT COALESCE(SUM(paid_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND DATE(bill_date) = '$today' AND payment_mode = 'Cash' AND delete_flag = 0");
 $upiCollection = getScalar($conn, "SELECT COALESCE(SUM(paid_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND DATE(bill_date) = '$today' AND payment_mode = 'UPI' AND delete_flag = 0");

// ============================================================
// 2. CHART DATA (Last 7 Days Revenue)
// ============================================================
 $chartLabels = [];
 $chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D', strtotime($date));
    $val = getScalar($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM bills WHERE hospital_id = $hospital_id AND DATE(bill_date) = '$date' AND delete_flag = 0");
    $chartValues[] = $val;
}
 $totalWeeklyRevenue = array_sum($chartValues);

// ============================================================
// 3. INCOME & EXPENSE SUMMARY (Monthly)
// ============================================================
 $monthlyExpense = 41180; // Static for now
 $netProfit = $monthlyIncome - $monthlyExpense;

// FIX: Added Safe Division Check to prevent Fatal Error when income is 0
 $totalFinance = $monthlyIncome + $monthlyExpense;
if ($totalFinance > 0) {
    $incomePercent = ($monthlyIncome / $totalFinance) * 100;
    $expensePercent = ($monthlyExpense / $totalFinance) * 100;
} else {
    $incomePercent = 0;
    $expensePercent = 0;
}

// ============================================================
// 4. RECENT PAYMENTS & INVOICES (Tables)
// ============================================================
 $recentPayments = [];
 $payRes = $conn->query("SELECT b.*, p.patient_name FROM bills b LEFT JOIN patients p ON b.patient_id = p.patient_id WHERE b.hospital_id = $hospital_id AND b.paid_amount > 0 AND b.delete_flag = 0 ORDER BY b.bill_date DESC LIMIT 5");
if ($payRes) { while ($row = $payRes->fetch_assoc()) { $recentPayments[] = $row; } }

 $recentInvoices = [];
 $invRes = $conn->query("SELECT b.*, p.patient_name FROM bills b LEFT JOIN patients p ON b.patient_id = p.patient_id WHERE b.hospital_id = $hospital_id AND b.delete_flag = 0 ORDER BY b.bill_date DESC LIMIT 5");
if ($invRes) { while ($row = $invRes->fetch_assoc()) { $recentInvoices[] = $row; } }

function formatMoney($val) {
    return '₹' . number_format($val, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard · <?php echo htmlspecialchars($hospital_name); ?></title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? 'favicon.ico'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.01);
            --hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f1f5f9; 
            color: var(--dark); 
            line-height: 1.5;
        }
        a { text-decoration: none; }
        
        /* Layout - Proper spacing with header and sidebar */
        .main-wrapper { display: flex; min-height: 100vh; background: #f1f5f9; }
        
        /* Main content - accounts for sidebar and header */
       .main-content {
    margin-left: 260px;
    padding: 0px 15px 61px;
    width: calc(100% - 260px);
    min-height: calc(100vh - 67px);
    background: #f1f5f9;
    margin-top: 23px;
        }
        
        @media (max-width: 991px) { 
            .main-content { 
                margin-left: 0; 
                padding: 20px; 
                width: 100%; 
                margin-top: 67px;
            } 
        }
        
        .dashboard { max-width: 1400px; margin: 0 auto; }
        
        /* FIX: Added CSS for Hero Card and Utilities to make it attractive */
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .gap-3 { gap: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        
        .hero-card {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 16px; 
            margin-bottom: 28px; 
            padding: 32px; 
            color: white;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        .hero-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .hero-card h1 { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.5px; color: #fff; }
        .hero-card p { color: rgba(255,255,255,0.9); font-size: 15px; margin: 6px 0 0 0; font-weight: 500; }
        .hero-meta { font-size: 13px; margin-top: 16px; color: rgba(255,255,255,0.8); display: flex; gap: 20px; flex-wrap: wrap; align-items: center; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 8px;
            text-decoration: none;
        }
        .btn-glass {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 2;
        }
        .btn-glass:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* Dashboard Header - Clean and aligned */
        .dashboard-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 28px; 
            flex-wrap: wrap; 
            gap: 16px; 
            background: transparent;
            padding: 0;
        }
        .dashboard-header .left h1 { 
            font-weight: 800; 
            font-size: 28px; 
            letter-spacing: -0.5px; 
            color: var(--dark); 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .dashboard-header .left h1 i { 
            color: var(--primary); 
            font-size: 28px; 
        }
        .dashboard-header .left .sub { 
            font-size: 14px; 
            color: var(--slate-500); 
            margin-top: 4px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            flex-wrap: wrap; 
        }
        .dashboard-header .left .sub .role-tag { 
            background: #eef2ff; 
            color: var(--primary); 
            font-size: 11px; 
            font-weight: 700; 
            padding: 4px 12px; 
            border-radius: 20px; 
            letter-spacing: 0.3px; 
        }
        .dashboard-header .right { 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            flex-wrap: wrap;
        }
        .dashboard-header .date-badge { 
            background: #fff; 
            padding: 8px 18px; 
            border-radius: 10px; 
            font-size: 13px; 
            font-weight: 600; 
            border: 1px solid var(--slate-200); 
            color: var(--slate-800); 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: var(--card-shadow); 
        }
        .dashboard-header .user-profile { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            background: #fff; 
            padding: 6px 16px 6px 10px; 
            border-radius: 10px; 
            border: 1px solid var(--slate-200); 
            box-shadow: var(--card-shadow); 
        }
        .dashboard-header .user-profile i { 
            font-size: 22px; 
            color: var(--primary); 
        }
        .dashboard-header .user-profile span { 
            font-weight: 700; 
            font-size: 14px; 
            color: var(--dark); 
        }
        .dashboard-header .user-profile .acc-tag { 
            background: #eef2ff; 
            color: var(--primary); 
            font-size: 10px; 
            font-weight: 700; 
            padding: 2px 10px; 
            border-radius: 20px; 
        }
        
        /* KPI Grid - 2 Rows of 4 */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
        @media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .kpi-grid { grid-template-columns: 1fr; } }
        
        .kpi-card { 
            background: #ffffff; 
            border-radius: 16px; 
            padding: 18px 20px; 
            border: 1px solid var(--slate-200); 
            box-shadow: var(--card-shadow); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--hover-shadow); border-color: #cbd5e1; }
        
        .kpi-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .kpi-label { font-size: 11px; font-weight: 600; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-icon-box { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; }
        .kpi-value { font-size: 24px; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; margin-bottom: 4px; }
        .kpi-sub { font-size: 12px; color: var(--slate-500); display: flex; align-items: center; gap: 6px; font-weight: 500; }
        .trend-up { color: var(--success); } .trend-down { color: var(--danger); }
        
        /* Icon Backgrounds */
        .bg-primary-soft { background: #eef2ff; color: var(--primary); }
        .bg-success-soft { background: #ecfdf5; color: var(--success); }
        .bg-warning-soft { background: #fffbeb; color: var(--warning); }
        .bg-danger-soft { background: #fef2f2; color: var(--danger); }
        .bg-purple-soft { background: #f5f3ff; color: var(--secondary); }
        .bg-cyan-soft { background: #ecfeff; color: #06b6d4; }
        .bg-indigo-soft { background: #e0e7ff; color: #4f46e5; }
        .bg-pink-soft { background: #fce7f3; color: #db2777; }
        
        /* Grid Layout */
        .row-2col { display: grid; grid-template-columns: 1.4fr 1fr; gap: 22px; margin-bottom: 28px; }
        @media (max-width: 1100px) { .row-2col { grid-template-columns: 1fr; } }
        
        /* Cards */
        .card { background: #ffffff; border-radius: 16px; padding: 22px 24px; border: 1px solid var(--slate-200); box-shadow: var(--card-shadow); margin-bottom: 22px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--slate-100); }
        .card-header h3 { font-weight: 700; font-size: 15px; color: var(--dark); display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-header h3 i { color: var(--primary); font-size: 17px; }
        .card-header a { font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 8px; transition: 0.2s; background: #eef2ff; }
        .card-header a:hover { background: var(--primary); color: #fff; }
        
        /* Tables */
        .table-wrap { overflow-x: auto; margin: -6px -24px -14px -24px; padding: 0 24px; }
        .tx-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .tx-table th { text-align: left; padding: 12px 14px; font-weight: 700; color: var(--slate-500); border-bottom: 2px solid var(--slate-100); font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        .tx-table td { padding: 12px 14px; border-bottom: 1px solid var(--slate-100); color: var(--slate-800); vertical-align: middle; }
        .tx-table tr:last-child td { border-bottom: none; }
        .tx-table tbody tr { transition: background 0.15s; }
        .tx-table tbody tr:hover { background: var(--slate-50); }
        
        .badge-status { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: 0.3px; }
        .badge-paid { background: #ecfdf5; color: #059669; }
        .badge-partial { background: #fffbeb; color: #d97706; }
        .badge-unpaid { background: #fef2f2; color: #dc2626; }
        
        .action-icon { color: var(--primary); cursor: pointer; margin-right: 12px; transition: 0.2s; font-size: 14px; }
        .action-icon:hover { transform: scale(1.1); }
        .action-icon.muted { color: var(--slate-400); }
        
        /* Chart */
        .chart-container { position: relative; height: 260px; width: 100%; }
        
        /* Income/Expense */
        .ie-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
        .ie-item { background: var(--slate-50); padding: 14px 16px; border-radius: 12px; border: 1px solid var(--slate-100); }
        .ie-item span { font-size: 11px; color: var(--slate-500); display: block; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; }
        .ie-item div { font-size: 20px; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; }
        .progress-bar { background: var(--slate-100); border-radius: 40px; height: 8px; margin-top: 8px; width: 100%; overflow: hidden; }
        .progress-fill { height: 8px; border-radius: 40px; transition: width 0.6s ease; }
        
        /* Quick Actions */
        .quick-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .quick-btn { background: #fff; border: 1px solid var(--slate-200); padding: 7px 14px; border-radius: 10px; font-weight: 600; font-size: 12px; color: var(--slate-800); display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.02); text-decoration: none; }
        .quick-btn i { color: var(--primary); }
        .quick-btn:hover { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); }
        .quick-btn:hover i { color: white; }
        
        /* Alerts */
        .alert-box { background: #fffbeb; border-radius: 12px; padding: 14px 16px; border: 1px solid #fde68a; }
        .alert-item { display: flex; gap: 12px; align-items: center; padding: 4px 0; font-size: 13px; color: #92400e; }
        .alert-item a { margin-left: auto; font-size: 11px; color: #fff; background: var(--warning); font-weight: 700; padding: 3px 12px; border-radius: 6px; transition: 0.2s; text-decoration: none; }
        .alert-item a:hover { background: #d97706; }
        
        /* Footer */
        .footer-note { margin-top: 32px; font-size: 12px; color: var(--slate-500); border-top: 1px solid var(--slate-200); padding-top: 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .footer-note i { margin-right: 6px; color: var(--primary); }
    </style>
</head>
<body>

<!-- ===== HEADER (Moved outside main-wrapper to prevent flex layout issues) ===== -->
<?php include '../header.php'; ?>

<!-- ===== SIDEBAR (Moved outside main-wrapper) ===== -->
<?php include '../Sidebar.php'; ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-wrapper">
    <main class="main-content">
        <div class="dashboard">

            <!-- Hero Header -->
            <div class="hero-card">
                <div style="position: relative; z-index: 2;">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span style="font-size: 3rem;"><?php echo $greeting_icon; ?></span>
                        <div>
                            <h1><?php echo $greeting . "! 👋"; ?></h1>
                            <p><?php echo ucwords($user_name); ?> | <?php echo htmlspecialchars($hospital_name); ?></p>
                        </div>
                    </div>
                    <div class="hero-meta">
                        <span><i class="fas fa-calendar-alt"></i> <?php echo $current_date; ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo $current_time; ?></span>
                        <span><i class="fas fa-user-tie"></i> Accountant</span>
                    </div>
                </div>
                <button class="btn btn-glass" onclick="window.location.href='create_bill.php'" style="position: relative; z-index: 2;">
                    <i class="fas fa-plus"></i> Create New Bill
                </button>
            </div>

            <!-- KPI Cards (2 Rows of 4) -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Today's Revenue</div>
                        <div class="kpi-icon-box bg-primary-soft"><i class="fas fa-coins"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo formatMoney($todayRevenue); ?></div>
                    <div class="kpi-sub"><i class="fas fa-check-circle trend-up"></i> <?php echo $transactionsToday; ?> transactions today</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Pending Bills</div>
                        <div class="kpi-icon-box bg-warning-soft"><i class="fas fa-file-invoice"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo $pendingBillsCount; ?></div>
                    <div class="kpi-sub"><i class="fas fa-exclamation-triangle trend-down"></i> <?php echo $overdueBillsCount; ?> overdue</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Outstanding</div>
                        <div class="kpi-icon-box bg-danger-soft"><i class="fas fa-hand-holding-usd"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo formatMoney($outstandingAmount); ?></div>
                    <div class="kpi-sub"><i class="fas fa-arrow-down trend-down"></i> Total balance due</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Monthly Income</div>
                        <div class="kpi-icon-box bg-purple-soft"><i class="fas fa-hospital-user"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo formatMoney($monthlyIncome); ?></div>
                    <div class="kpi-sub"><i class="fas fa-calendar-alt"></i> For <?php echo date('F Y'); ?></div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Payments Today</div>
                        <div class="kpi-icon-box bg-success-soft"><i class="fas fa-credit-card"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo formatMoney($paymentsToday); ?></div>
                    <div class="kpi-sub"><i class="fas fa-check-circle trend-up"></i> Received today</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Cash Collection</div>
                        <div class="kpi-icon-box bg-cyan-soft"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo formatMoney($cashCollection); ?></div>
                    <div class="kpi-sub"><i class="fas fa-money-check"></i> Today's cash</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">UPI Collection</div>
                        <div class="kpi-icon-box bg-indigo-soft"><i class="fas fa-mobile-alt"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo formatMoney($upiCollection); ?></div>
                    <div class="kpi-sub"><i class="fas fa-arrow-up trend-up"></i> Today's UPI</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-label">Transactions</div>
                        <div class="kpi-icon-box bg-pink-soft"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                    <div class="kpi-value"><?php echo $transactionsToday; ?></div>
                    <div class="kpi-sub"><i class="fas fa-list"></i> Total today</div>
                </div>
            </div>

            <!-- Row 1: Chart + Recent Payments -->
            <div class="row-2col">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Weekly Revenue</h3>
                        <a href="billing_list.php">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="chart-container">
                        <canvas id="weeklyRevenueChart"></canvas>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 16px; font-size: 13px; color: var(--slate-500); padding-top: 14px; border-top: 1px solid var(--slate-100);">
                        <span style="font-weight: 600;"><i class="fas fa-circle" style="color: var(--primary);"></i> Total <?php echo formatMoney($totalWeeklyRevenue); ?></span>
                        <span><i class="fas fa-calendar-week"></i> Last 7 days</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list-ul"></i> Recent Payments</h3>
                        <a href="billing_list.php">All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-wrap">
                        <table class="tx-table">
                            <thead>
                                <tr><th>Patient</th><th>Bill #</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recentPayments)): ?>
                                    <?php foreach($recentPayments as $pay): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($pay['patient_name'] ?? 'Walk-in'); ?></td>
                                            <td style="color: var(--slate-500);">#INV-<?php echo str_pad($pay['bill_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td style="font-weight: 700;"><?php echo formatMoney($pay['paid_amount']); ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = strtolower($pay['status']);
                                                echo "<span class='badge-status badge-{$statusClass}'>{$pay['status']}</span>";
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--slate-400);">No payments received yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Row 2: Income/Expense + Quick Actions -->
            <div class="row-2col">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-wallet"></i> Income & Expense</h3>
                        <a href="ledger.php">Ledger <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="ie-grid">
                        <div class="ie-item">
                            <span>Total Income</span>
                            <div style="color: var(--success);"><?php echo formatMoney($monthlyIncome); ?></div>
                        </div>
                        <div class="ie-item">
                            <span>Total Expense</span>
                            <div style="color: var(--danger);"><?php echo formatMoney($monthlyExpense); ?></div>
                        </div>
                        <div class="ie-item">
                            <span>Net Profit</span>
                            <div><?php echo formatMoney($netProfit); ?></div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="background: linear-gradient(90deg, var(--primary), var(--secondary)); width: <?php echo $incomePercent; ?>%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--slate-500); margin-top: 6px; font-weight: 600;">
                        <span>Expense <?php echo round($expensePercent); ?>%</span>
                        <span>Income <?php echo round($incomePercent); ?>%</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        <span style="font-size:12px; color: var(--slate-500); font-weight: 600;"><i class="far fa-bell"></i> <?php echo $overdueBillsCount; ?> alerts</span>
                    </div>
                    <div class="quick-actions">
                        <a href="create_bill.php" class="quick-btn"><i class="fas fa-file-invoice"></i> New Bill</a>
                        <a href="collect_payment.php" class="quick-btn"><i class="fas fa-receipt"></i> Payment</a>
                        <a href="billing_list.php" class="quick-btn"><i class="fas fa-print"></i> Print Invoice</a>
                        <a href="reports.php" class="quick-btn"><i class="fas fa-chart-pie"></i> Reports</a>
                        <a href="pending_bills.php" class="quick-btn"><i class="fas fa-hand-holding-usd"></i> Pending</a>
                        <a href="expenses.php" class="quick-btn"><i class="fas fa-credit-card"></i> Expense</a>
                    </div>
                    <div class="alert-box">
                        <div class="alert-item">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><strong><?php echo $overdueBillsCount; ?> pending bills</strong> overdue > 15 days</span>
                            <a href="pending_bills.php">Review</a>
                        </div>
                        <div class="alert-item">
                            <i class="fas fa-file-signature"></i>
                            <span>Generate monthly financial report</span>
                            <a href="reports.php">Generate</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Recent Invoices Table -->
            <div class="card" style="margin-top: 0;">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Recent Invoices & Bills</h3>
                    <a href="billing_list.php">View all invoices <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="table-wrap">
                    <table class="tx-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th><th>Patient</th><th>Date</th><th>Amount</th><th>Status</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recentInvoices)): ?>
                                <?php foreach($recentInvoices as $inv): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: var(--primary);">#INV-<?php echo str_pad($inv['bill_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($inv['patient_name'] ?? 'Walk-in'); ?></td>
                                        <td style="color: var(--slate-500);"><?php echo date('d M Y', strtotime($inv['bill_date'])); ?></td>
                                        <td style="font-weight: 700;"><?php echo formatMoney($inv['total_amount']); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = strtolower($inv['status']);
                                            echo "<span class='badge-status badge-{$statusClass}'>{$inv['status']}</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <a href="view_bill.php?id=<?php echo $inv['bill_id']; ?>"><i class="fas fa-eye action-icon"></i></a>
                                            <a href="print_invoice.php?id=<?php echo $inv['bill_id']; ?>" target="_blank"><i class="fas fa-print action-icon muted"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--slate-400);">No invoices generated yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            

        </div>
    </main>
</div>

<script>
    // Chart.js: Weekly Revenue
    const ctx = document.getElementById('weeklyRevenueChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0.01)');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Revenue (₹)',
                data: <?php echo json_encode($chartValues); ?>,
                backgroundColor: gradient,
                borderColor: '#4f46e5',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '₹ ' + context.raw.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { callback: function(value) { return '₹' + value.toLocaleString(); }, font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, weight: '600' } }
                }
            }
        }
    });
</script>

</body>
</html>