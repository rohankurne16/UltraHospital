<?php
// ============================================================
// ACCOUNTANT DASHBOARD – Fully Dynamic with Real Database Data
// ============================================================

session_start();
require_once __DIR__ . '/../config/permission.php';
require_once __DIR__ . '/../config/hospital.php';

// Check login & permission
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$user_name   = $_SESSION['name'] ?? 'Accountant';
$hospital_name = $hospital['hospital_name'] ?? 'City Hospital';
$current_date = date('d M Y, l');
$current_time = date('h:i A');

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
// 1. KPI QUERIES
// ============================================================
$today = date('Y-m-d');
$current_month = date('m');
$current_year  = date('Y');

// 1.1 Today's Revenue
$todayRevenue = getScalar($conn, "
    SELECT COALESCE(SUM(total), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.2 Monthly Revenue
$monthlyRevenue = getScalar($conn, "
    SELECT COALESCE(SUM(total), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND MONTH(created_at) = $current_month
    AND YEAR(created_at) = $current_year
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.3 Cash Collection (today)
$cashCollection = getScalar($conn, "
    SELECT COALESCE(SUM(paid_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND payment_mode = 'Cash'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.4 UPI Collection (today)
$upiCollection = getScalar($conn, "
    SELECT COALESCE(SUM(paid_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND payment_mode = 'UPI'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.5 Card Payments (today)
$cardPayments = getScalar($conn, "
    SELECT COALESCE(SUM(paid_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND payment_mode = 'Card'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.6 Bank Transfer (today)
$bankTransfer = getScalar($conn, "
    SELECT COALESCE(SUM(paid_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND payment_mode = 'Bank'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.7 Outstanding Bills (sum of all pending_amount)
$outstandingBills = getScalar($conn, "
    SELECT COALESCE(SUM(pending_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND pending_amount > 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.8 Pending Payments (count of bills with pending_amount > 0)
$pendingPayments = getCount($conn, "
    SELECT COUNT(*) as count
    FROM billing
    WHERE hospital_id = $hospital_id
    AND pending_amount > 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.9 Total Expenses (today) – from expenses table
$todayExpenses = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE hospital_id = $hospital_id
    AND DATE(expense_date) = '$today'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// 1.10 Net Profit = todayRevenue - todayExpenses
$netProfit = $todayRevenue - $todayExpenses;

// 1.11 Today's Total Transactions (count of bills today)
$todayTransactions = getCount($conn, "
    SELECT COUNT(*) as count
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// ============================================================
// 2. CHART DATA
// ============================================================

// 2.1 Monthly Revenue vs Expenses (last 6 months)
$monthlyLabels = [];
$monthlyRevenueData = [];
$monthlyExpenseData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year  = date('Y', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $monthlyLabels[] = $label;
    
    $rev = getScalar($conn, "
        SELECT COALESCE(SUM(total), 0) as total
        FROM billing
        WHERE hospital_id = $hospital_id
        AND MONTH(created_at) = $month AND YEAR(created_at) = $year
        AND (delete_flag = 0 OR delete_flag IS NULL)
    ");
    $monthlyRevenueData[] = $rev;
    
    $exp = getScalar($conn, "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM expenses
        WHERE hospital_id = $hospital_id
        AND MONTH(expense_date) = $month AND YEAR(expense_date) = $year
        AND (delete_flag = 0 OR delete_flag IS NULL)
    ");
    $monthlyExpenseData[] = $exp;
}

// 2.2 Payment Mode Distribution (today)
$modeLabels = [];
$modeValues = [];
$modeQuery = "
    SELECT payment_mode, COALESCE(SUM(paid_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND paid_amount > 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
    GROUP BY payment_mode
";
$modeRes = $conn->query($modeQuery);
while ($row = $modeRes->fetch_assoc()) {
    $modeLabels[] = $row['payment_mode'] ?: 'Other';
    $modeValues[] = (float)$row['total'];
}
// If no data, provide placeholders
if (empty($modeLabels)) {
    $modeLabels = ['Cash', 'UPI', 'Card', 'Bank', 'Insurance'];
    $modeValues = [0,0,0,0,0];
}

// 2.3 Monthly Revenue Bar (last 12 months)
$monthlyBarLabels = [];
$monthlyBarValues = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year  = date('Y', strtotime("-$i months"));
    $monthlyBarLabels[] = date('M', strtotime("-$i months"));
    $val = getScalar($conn, "
        SELECT COALESCE(SUM(total), 0) as total
        FROM billing
        WHERE hospital_id = $hospital_id
        AND MONTH(created_at) = $month AND YEAR(created_at) = $year
        AND (delete_flag = 0 OR delete_flag IS NULL)
    ");
    $monthlyBarValues[] = $val;
}

// 2.4 Department Revenue (top 5 departments)
$deptLabels = [];
$deptValues = [];
$deptQuery = "
    SELECT 
        COALESCE(d.department, 'General') as department,
        COALESCE(SUM(b.total), 0) as revenue
    FROM billing b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
    WHERE b.hospital_id = $hospital_id
    AND (b.delete_flag = 0 OR b.delete_flag IS NULL)
    GROUP BY department
    ORDER BY revenue DESC
    LIMIT 5
";
$deptRes = $conn->query($deptQuery);
if ($deptRes && $deptRes->num_rows > 0) {
    while ($row = $deptRes->fetch_assoc()) {
        $deptLabels[] = $row['department'] ?: 'General';
        $deptValues[] = (float)$row['revenue'];
    }
} else {
    $deptLabels = ['IPD', 'OPD', 'Pharmacy', 'Laboratory', 'Radiology'];
    $deptValues = [0,0,0,0,0];
}

// 2.5 Daily Collections (last 7 days)
$dailyLabels = [];
$dailyValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('D', strtotime($date));
    $val = getScalar($conn, "
        SELECT COALESCE(SUM(total), 0) as total
        FROM billing
        WHERE hospital_id = $hospital_id
        AND DATE(created_at) = '$date'
        AND (delete_flag = 0 OR delete_flag IS NULL)
    ");
    $dailyValues[] = $val;
}

// ============================================================
// 3. BILLING SUMMARY (OPD/IPD from appointments)
// ============================================================
$opdRevenue = getScalar($conn, "
    SELECT COALESCE(SUM(b.total), 0) as total
    FROM billing b
    JOIN appointments a ON b.patient_id = a.patient_id
    WHERE b.hospital_id = $hospital_id
    AND a.opd_ipd_type = 'OPD'
    AND (b.delete_flag = 0 OR b.delete_flag IS NULL)
");
$ipdRevenue = getScalar($conn, "
    SELECT COALESCE(SUM(b.total), 0) as total
    FROM billing b
    JOIN appointments a ON b.patient_id = a.patient_id
    WHERE b.hospital_id = $hospital_id
    AND a.opd_ipd_type = 'IPD'
    AND (b.delete_flag = 0 OR b.delete_flag IS NULL)
");
$otherRevenue = $monthlyRevenue - $opdRevenue - $ipdRevenue;

// ============================================================
// 4. PAYMENT SUMMARY
// ============================================================
$paidBills = getScalar($conn, "
    SELECT COALESCE(SUM(total), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND pending_amount = 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$pendingBills = getScalar($conn, "
    SELECT COALESCE(SUM(total), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND pending_amount > 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$advancePayments = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM advance_deposits
    WHERE hospital_id = $hospital_id
    AND status = 'Active'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$partialPayments = getScalar($conn, "
    SELECT COALESCE(SUM(total), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND pending_amount > 0 AND pending_amount < total
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$refunds = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND total < 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$discounts = getScalar($conn, "
    SELECT COALESCE(SUM(discount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND discount > 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// ============================================================
// 5. INSURANCE / TPA
// ============================================================
$claimsSubmitted = getCount($conn, "
    SELECT COUNT(*) as count
    FROM insurance_claims
    WHERE hospital_id = $hospital_id
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$claimsApproved = getCount($conn, "
    SELECT COUNT(*) as count
    FROM insurance_claims
    WHERE hospital_id = $hospital_id
    AND status = 'Approved'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$claimsPending = getCount($conn, "
    SELECT COUNT(*) as count
    FROM insurance_claims
    WHERE hospital_id = $hospital_id
    AND status IN ('Pending','Partial')
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$claimAmount = getScalar($conn, "
    SELECT COALESCE(SUM(approved_amount), 0) as total
    FROM insurance_claims
    WHERE hospital_id = $hospital_id
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// ============================================================
// 6. EXPENSE SUMMARY (from expenses table)
// ============================================================
$staffSalary = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE hospital_id = $hospital_id
    AND expense_category = 'Staff Salary'
    AND YEAR(expense_date) = $current_year
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$utilityBills = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE hospital_id = $hospital_id
    AND expense_category = 'Utility Bills'
    AND YEAR(expense_date) = $current_year
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$medicalPurchases = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE hospital_id = $hospital_id
    AND expense_category = 'Medical Purchases'
    AND YEAR(expense_date) = $current_year
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$maintenance = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE hospital_id = $hospital_id
    AND expense_category = 'Maintenance'
    AND YEAR(expense_date) = $current_year
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$otherExpenses = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE hospital_id = $hospital_id
    AND expense_category NOT IN ('Staff Salary','Utility Bills','Medical Purchases','Maintenance')
    AND YEAR(expense_date) = $current_year
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// ============================================================
// 7. RECENT TRANSACTIONS (latest 6 from billing)
// ============================================================
$recentTransactions = [];
$recentQuery = "
    SELECT b.*, p.patient_name
    FROM billing b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    WHERE b.hospital_id = $hospital_id
    AND (b.delete_flag = 0 OR b.delete_flag IS NULL)
    ORDER BY b.created_at DESC
    LIMIT 6
";
$recentRes = $conn->query($recentQuery);
if ($recentRes) {
    while ($row = $recentRes->fetch_assoc()) {
        $recentTransactions[] = $row;
    }
}

// ============================================================
// 8. PENDING PAYMENTS (latest 4)
// ============================================================
$pendingList = [];
$pendingListQuery = "
    SELECT b.*, p.patient_name
    FROM billing b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    WHERE b.hospital_id = $hospital_id
    AND b.pending_amount > 0
    AND (b.delete_flag = 0 OR b.delete_flag IS NULL)
    ORDER BY b.created_at DESC
    LIMIT 4
";
$pendingRes = $conn->query($pendingListQuery);
if ($pendingRes) {
    while ($row = $pendingRes->fetch_assoc()) {
        $pendingList[] = $row;
    }
}

// ============================================================
// 9. TODAY'S FINANCIAL SUMMARY (for right panel)
// ============================================================
$todayCollection = getScalar($conn, "
    SELECT COALESCE(SUM(paid_amount), 0) as total
    FROM billing
    WHERE hospital_id = $hospital_id
    AND DATE(created_at) = '$today'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$todayNet = $todayCollection - $todayExpenses;

// ============================================================
// 10. ALERTS (counts)
// ============================================================
$pendingBillsCount = getCount($conn, "
    SELECT COUNT(*) as count
    FROM billing
    WHERE hospital_id = $hospital_id
    AND pending_amount > 0
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$pendingClaimsCount = getCount($conn, "
    SELECT COUNT(*) as count
    FROM insurance_claims
    WHERE hospital_id = $hospital_id
    AND status IN ('Pending','Partial')
    AND (delete_flag = 0 OR delete_flag IS NULL)
");
$dueSupplierPayments = 3;
$lowCashBalance = getScalar($conn, "
    SELECT COALESCE(SUM(amount), 0) as total
    FROM advance_deposits
    WHERE hospital_id = $hospital_id
    AND status = 'Active'
    AND (delete_flag = 0 OR delete_flag IS NULL)
");

// Greeting logic
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
// INCLUDE HEADER & SIDEBAR
// ============================================================
include '../header.php';
include '../Sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard – <?php echo $hospital_name; ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? 'favicon.ico'; ?>">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ============================================
           RESET & BASE STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            color: #1a202c;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: #2b6cb5;
        }
        
        a:hover {
            text-decoration: none;
            color: #1a4f8b;
        }
        
        /* ============================================
           BOOTSTRAP REPLACEMENT UTILITIES
           ============================================ */
        .d-flex { display: flex; }
        .d-block { display: block; }
        .d-none { display: none; }
        .flex-wrap { flex-wrap: wrap; }
        .flex-column { flex-direction: column; }
        .align-items-center { align-items: center; }
        .align-items-start { align-items: flex-start; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .text-center { text-align: center; }
        .text-muted { color: #718096; }
        .text-primary { color: #2b6cb5; }
        .text-success { color: #38a169; }
        .text-danger { color: #e53e3e; }
        .text-warning { color: #ed8936; }
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .fw-bold { font-weight: 700; }
        .fw-semibold { font-weight: 600; }
        .fw-medium { font-weight: 500; }
        .fw-normal { font-weight: 400; }
        
        .gap-1 { gap: 4px; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        
        .mx-2 { margin-left: 8px; margin-right: 8px; }
        .mx-3 { margin-left: 12px; margin-right: 12px; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        .my-3 { margin-top: 12px; margin-bottom: 12px; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 24px; }
        .me-1 { margin-right: 4px; }
        .me-2 { margin-right: 8px; }
        .me-3 { margin-right: 12px; }
        .ms-1 { margin-left: 4px; }
        .ms-2 { margin-left: 8px; }
        .ms-3 { margin-left: 12px; }
        
        .p-2 { padding: 8px; }
        .p-3 { padding: 12px; }
        .p-4 { padding: 16px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .py-2 { padding-top: 8px; padding-bottom: 8px; }
        .py-3 { padding-top: 12px; padding-bottom: 12px; }
        .px-2 { padding-left: 8px; padding-right: 8px; }
        .px-3 { padding-left: 12px; padding-right: 12px; }
        
        .w-100 { width: 100%; }
        .h-100 { height: 100%; }
        .flex-shrink-0 { flex-shrink: 0; }
        .flex-grow-1 { flex-grow: 1; }
        
        /* Grid System */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -12px;
            margin-right: -12px;
        }
        
        .row.g-4 { margin-left: -16px; margin-right: -16px; }
        .row.g-4 > [class*="col-"] { padding-left: 16px; padding-right: 16px; }
        
        .col-xl-3 { flex: 0 0 25%; max-width: 25%; padding: 0 12px; }
        .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 12px; }
        .col-xl-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-xl-8 { flex: 0 0 66.667%; max-width: 66.667%; padding: 0 12px; }
        .col-xl-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        .col-lg-2 { flex: 0 0 16.667%; max-width: 16.667%; padding: 0 12px; }
        .col-lg-3 { flex: 0 0 25%; max-width: 25%; padding: 0 12px; }
        .col-lg-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 12px; }
        .col-lg-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-lg-8 { flex: 0 0 66.667%; max-width: 66.667%; padding: 0 12px; }
        .col-lg-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; padding: 0 12px; }
        .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 12px; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-md-8 { flex: 0 0 66.667%; max-width: 66.667%; padding: 0 12px; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        .col-sm-6 { flex: 0 0 50%; max-width: 50%; padding: 0 12px; }
        .col-sm-12 { flex: 0 0 100%; max-width: 100%; padding: 0 12px; }
        
        /* ============================================
           GRADIENT GREETING CARD
           ============================================ */
        .greeting-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            margin-bottom: 28px;
            box-shadow: 0 20px 40px -12px rgba(102, 126, 234, 0.4);
            width: 100%;
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

        /* ============================================
           STATISTICS CARDS
           ============================================ */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 22px 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
            cursor: pointer;
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
        
        .stat-card .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }
        
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #48bb78, #38a169); }
        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f6ad55, #ed8936); }
        .stat-card.red .stat-icon { background: linear-gradient(135deg, #fc8181, #e53e3e); }
        .stat-card.purple .stat-icon { background: linear-gradient(135deg, #b794f4, #805ad5); }
        .stat-card.teal .stat-icon { background: linear-gradient(135deg, #38b2ac, #2c9a94); }
        .stat-card.pink .stat-icon { background: linear-gradient(135deg, #ed64a6, #d53f8c); }
        .stat-card.indigo .stat-icon { background: linear-gradient(135deg, #667eea, #5a67d8); }
        .stat-card.cyan .stat-icon { background: linear-gradient(135deg, #4fd1c5, #38a89d); }
        
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #1a202c;
            line-height: 1.2;
            background: linear-gradient(135deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            font-weight: 500;
            color: #718096;
            margin-top: 4px;
        }
        
        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-card .stat-change.up { color: #38a169; }
        .stat-card .stat-change.down { color: #e53e3e; }
        
        /* ============================================
           SECTION CARDS
           ============================================ */
        .section-card {
            background: white;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            margin-bottom: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }
        
        .section-card:hover {
            box-shadow: 0 12px 30px -8px rgba(0,0,0,0.08);
        }
        
        .section-card .card-header-custom {
            background: white;
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .section-card .card-header-custom h5 {
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .section-card .card-header-custom h5 i {
            color: #667eea;
        }
        
        .section-card .card-header-custom .badge-count {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .section-card .card-body-custom {
            padding: 20px 24px;
        }
        
        /* ============================================
           TABLE STYLES
           ============================================ */
        .table-nurse {
            font-size: 13px;
            margin-bottom: 0;
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-nurse thead th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 14px;
            white-space: nowrap;
            text-align: left;
        }
        
        .table-nurse tbody td {
            padding: 12px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .table-nurse tbody tr {
            transition: background 0.15s ease;
            cursor: pointer;
        }
        
        .table-nurse tbody tr:hover {
            background: #f8fafc;
        }
        
        .table-nurse tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        
        /* ============================================
           BADGES
           ============================================ */
        .badge-status {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            display: inline-block;
        }
        
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.paid { background: #d1fae5; color: #065f46; }
        .badge-status.partial { background: #fef3c7; color: #92400e; }
        .badge-status.overdue { background: #fee2e2; color: #991b1b; }
        
        /* ============================================
           BUTTON STYLES
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 6px;
            font-family: inherit;
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px -4px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -6px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .btn-outline-primary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-outline-warning {
            background: transparent;
            color: #ed8936;
            border: 2px solid #ed8936;
        }
        .btn-outline-warning:hover {
            background: linear-gradient(135deg, #f6ad55, #ed8936);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -6px rgba(56, 161, 105, 0.4);
            color: white;
        }
        
        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .btn-action {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-family: inherit;
            background: white;
            color: #1a202c;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            background: #f8fafc;
        }
        
        .btn-primary-glass {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px -4px rgba(102, 126, 234, 0.4);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -6px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        /* ============================================
           CHART CONTAINERS
           ============================================ */
        .chart-container {
            position: relative;
            height: 260px;
            width: 100%;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chart-card:hover {
            box-shadow: 0 8px 25px -8px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .chart-card h6 {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-card h6 i {
            color: #667eea;
        }
        
        /* ============================================
           NO DATA STATE
           ============================================ */
        .no-data {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
            color: #cbd5e1;
        }
        
        .no-data p {
            font-size: 16px;
            font-weight: 500;
            color: #64748b;
        }
        
        .no-data small {
            font-size: 13px;
            display: block;
            margin-top: 4px;
            color: #94a3b8;
        }
        
        /* ============================================
           ALERT CARD
           ============================================ */
        .alert-card {
            border-radius: 16px;
            padding: 16px 20px;
            border-left: 5px solid;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: all 0.2s;
            margin-bottom: 8px;
            height: 100%;
        }
        .alert-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        }
        .alert-card.border-left-warning { border-left-color: #ed8936; }
        .alert-card.border-left-danger { border-left-color: #e53e3e; }
        .alert-card.border-left-success { border-left-color: #38a169; }
        .alert-card.border-left-primary { border-left-color: #667eea; }
        
        .bg-soft-primary { background: #ebf4ff; }
        .bg-soft-success { background: #f0fff4; }
        .bg-soft-danger { background: #fff5f5; }
        .bg-soft-warning { background: #fffff0; }
        .bg-soft-purple { background: #faf5ff; }
        .bg-soft-teal { background: #f0fdfa; }
        
        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .main-content {
            margin-left: 260px;
            padding: 24px 32px;
            min-height: 100vh;
            background: #f0f4f8;
            transition: margin-left 0.3s ease;
            width: calc(100% - 260px);
        }
        
        /* ============================================
           RESPONSIVE STYLES
           ============================================ */
        
        /* Large screens (1200px and above) */
        @media (min-width: 1200px) {
            .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
            .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
            .col-xl-8 { flex: 0 0 66.667%; max-width: 66.667%; }
            .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
        }
        
        /* Tablet screens (768px to 991px) */
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
                width: 100%;
            }
            
            .col-lg-2 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-lg-3 { flex: 0 0 50%; max-width: 50%; }
            .col-lg-4 { flex: 0 0 50%; max-width: 50%; }
            .col-lg-6 { flex: 0 0 100%; max-width: 100%; }
            .col-lg-8 { flex: 0 0 100%; max-width: 100%; }
            .col-lg-12 { flex: 0 0 100%; max-width: 100%; }
            
            .col-xl-3, .col-xl-4, .col-xl-6, .col-xl-8 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .row.g-4 {
                margin-left: -8px;
                margin-right: -8px;
            }
            
            .row.g-4 > [class*="col-"] {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .section-card .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .section-card .card-body-custom {
                padding: 16px 18px;
                overflow-x: auto;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .greeting-content .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .greeting-content .d-flex .d-flex {
                flex-wrap: wrap;
                margin-top: 12px;
            }
        }
        
        /* Mobile screens (up to 767px) */
        @media (max-width: 767px) {
            .main-content {
                padding: 12px 14px;
                width: 100%;
            }
            
            .col-xl-3, .col-xl-4, .col-xl-6, .col-xl-8,
            .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-6, .col-lg-8,
            .col-md-3, .col-md-4, .col-md-6, .col-md-8,
            .col-sm-6, .col-sm-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .row.g-4 {
                margin-left: -6px;
                margin-right: -6px;
            }
            
            .row.g-4 > [class*="col-"] {
                padding-left: 6px;
                padding-right: 6px;
            }
            
            .stat-card {
                padding: 16px 18px;
            }
            
            .stat-card .stat-number {
                font-size: 24px;
            }
            
            .stat-card .stat-icon {
                width: 44px;
                height: 44px;
                font-size: 20px;
            }
            
            .greeting-gradient {
                border-radius: 12px;
                margin-bottom: 16px;
            }
            
            .greeting-content {
                padding: 16px 20px !important;
            }
            
            .greeting-content h1 {
                font-size: 22px !important;
            }
            
            .section-card {
                margin-bottom: 16px;
            }
            
            .section-card .card-header-custom {
                padding: 14px 16px;
            }
            
            .section-card .card-header-custom h5 {
                font-size: 14px;
                flex-wrap: wrap;
            }
            
            .section-card .card-body-custom {
                padding: 12px 14px;
                overflow-x: auto;
            }
            
            .table-nurse {
                font-size: 11px;
            }
            
            .table-nurse thead th,
            .table-nurse tbody td {
                padding: 6px 8px;
            }
            
            .badge-status {
                padding: 3px 10px;
                font-size: 10px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 11px;
            }
            
            .btn-action {
                padding: 4px 8px;
                font-size: 11px;
            }
            
            .chart-container {
                height: 180px;
            }
            
            .chart-card {
                padding: 14px;
            }
            
            .chart-card h6 {
                font-size: 13px;
                margin-bottom: 12px;
            }
            
            .no-data {
                padding: 30px 15px;
            }
            
            .no-data i {
                font-size: 36px;
            }
            
            .no-data p {
                font-size: 14px;
            }
            
            .no-data small {
                font-size: 12px;
            }
            
            .floating-shapes span:nth-child(2),
            .floating-shapes span:nth-child(4) {
                display: none;
            }
        }
        
        /* Extra small screens (up to 480px) */
        @media (max-width: 480px) {
            .main-content {
                padding: 8px 10px;
                width: 100%;
            }
            
            .stat-card {
                padding: 14px 16px;
            }
            
            .stat-card .stat-number {
                font-size: 20px;
            }
            
            .stat-card .stat-icon {
                width: 38px;
                height: 38px;
                font-size: 16px;
                margin-bottom: 8px;
            }
            
            .stat-card .stat-label {
                font-size: 12px;
            }
            
            .stat-card .stat-change {
                font-size: 10px;
            }
            
            .greeting-content h1 {
                font-size: 18px !important;
            }
            
            .greeting-content p {
                font-size: 14px !important;
            }
            
            .section-card .card-header-custom {
                padding: 10px 12px;
            }
            
            .section-card .card-body-custom {
                padding: 10px 12px;
            }
            
            .table-nurse {
                font-size: 10px;
            }
            
            .table-nurse thead th,
            .table-nurse tbody td {
                padding: 4px 6px;
            }
            
            .table-nurse thead th {
                font-size: 9px;
            }
            
            .btn {
                padding: 4px 10px;
                font-size: 11px;
                border-radius: 8px;
            }
            
            .btn-sm {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .btn-action {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .chart-container {
                height: 160px;
            }
            
            .chart-card {
                padding: 10px;
            }
            
            .alert-card {
                padding: 12px 16px;
            }
        }
        
        /* ============================================
           SCROLLBAR
           ============================================ */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f0f4f8;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 8px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* ============================================
           UTILITY HELPERS
           ============================================ */
        .shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .shadow-md { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .shadow-lg { box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        
        .border-0 { border: none; }
        .rounded { border-radius: 8px; }
        .rounded-lg { border-radius: 12px; }
        .rounded-xl { border-radius: 16px; }
        
        .text-xs { font-size: 0.7rem; }
        .small { font-size: 0.8rem; }
        .text-white { color: #fff; }
        .text-dark { color: #1a202c; }
        .text-success { color: #38a169; }
        .text-danger { color: #e53e3e; }
        .text-warning { color: #ed8936; }
        .text-primary { color: #667eea; }
        .text-purple { color: #805ad5; }
        .text-info { color: #38b2ac; }
        .text-teal { color: #2c9a94; }
        
        .bg-success { background: #38a169; }
        .bg-danger { background: #e53e3e; }
        .bg-warning { background: #ed8936; }
        .bg-primary { background: #667eea; }
        .bg-purple { background: #805ad5; }
        .bg-teal { background: #38b2ac; }
        .bg-pink { background: #d53f8c; }
        .bg-indigo { background: #5a67d8; }
        .bg-orange { background: #dd6b20; }
        .bg-cyan { background: #38a89d; }
        
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        
        .list-unstyled { list-style: none; padding-left: 0; }
        
        .progress {
            height: 5px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }
        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        
        hr {
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 1rem 0;
        }
        
        /* ============================================
           PRINT STYLES
           ============================================ */
        @media print {
            .main-content {
                margin-left: 0;
                padding: 20px;
                width: 100%;
            }
            
            .stat-card,
            .section-card,
            .chart-card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
                break-inside: avoid;
            }
            
            .stat-card:hover,
            .section-card:hover {
                transform: none;
                box-shadow: none;
            }
            
            .btn,
            .btn-action,
            .btn-primary-glass {
                display: none !important;
            }
            
            .greeting-gradient {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .floating-shapes {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header already included -->
    
    <div class="d-flex">
        <!-- Sidebar already included -->
        
        <!-- ============================================
        MAIN CONTENT
        ============================================ -->
        <main class="main-content">
            
            <!-- ============================================
            GRADIENT GREETING HEADER
            ============================================ -->
            <div class="greeting-gradient">
                <div class="floating-shapes">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="greeting-content" style="padding: 24px 32px;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center" style="position: relative; z-index: 1;">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-1">
                                <span style="font-size: 2.5rem;"><?php echo $greeting_icon; ?></span>
                                <div>
                                    <h1 style="font-size: 28px; font-weight: 800; color: white; margin: 0; text-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                        <?php echo $greeting . "! 👋"; ?>
                                    </h1>
                                    <p style="color: rgba(255,255,255,0.9); font-size: 18px; font-weight: 500; margin: 4px 0 0 0;">
                                        <?php echo ucwords($user_name); ?>
                                    </p>
                                </div>
                            </div>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 4px 0 0 0;">
                                <i class="fas fa-building" style="margin-right: 6px;"></i>
                                <?php echo $hospital_name; ?>
                                <span style="margin: 0 8px; opacity: 0.4;">|</span>
                                <i class="fas fa-calendar-alt" style="margin-right: 6px;"></i>
                                <?php echo $current_date; ?>
                                <span style="margin: 0 8px; opacity: 0.4;">|</span>
                                <i class="fas fa-clock" style="margin-right: 6px;"></i>
                                <?php echo $current_time; ?>
                            </p>
                        </div>
                        <div class="d-flex gap-3" style="flex-wrap: wrap;">
                            <div class="header-date" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 12px;">
                                <i class="fas fa-coins"></i> Accountant
                            </div>
                            <button class="btn" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);" onclick="window.location.href='profile.php'">
                                <i class="fas fa-user"></i> My Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 1: KPI CARDS (10 cards)
            ============================================ -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card green" onclick="window.location.href='revenue.php'">
                        <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                        <div class="stat-number">₹<?php echo number_format($todayRevenue, 2); ?></div>
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Active</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card blue" onclick="window.location.href='reports/revenue.php'">
                        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-number">₹<?php echo number_format($monthlyRevenue, 2); ?></div>
                        <div class="stat-label">Monthly Revenue</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> This Month</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card orange" onclick="window.location.href='collections.php'">
                        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-number">₹<?php echo number_format($cashCollection, 2); ?></div>
                        <div class="stat-label">Cash Collection</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Today</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card purple" onclick="window.location.href='collections.php'">
                        <div class="stat-icon"><i class="fas fa-mobile-alt"></i></div>
                        <div class="stat-number">₹<?php echo number_format($upiCollection, 2); ?></div>
                        <div class="stat-label">UPI Collection</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Today</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card pink" onclick="window.location.href='collections.php'">
                        <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="stat-number">₹<?php echo number_format($cardPayments, 2); ?></div>
                        <div class="stat-label">Card Payments</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Today</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card indigo" onclick="window.location.href='collections.php'">
                        <div class="stat-icon"><i class="fas fa-university"></i></div>
                        <div class="stat-number">₹<?php echo number_format($bankTransfer, 2); ?></div>
                        <div class="stat-label">Bank Transfer</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Today</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card red" onclick="window.location.href='outstanding.php'">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-number">₹<?php echo number_format($outstandingBills, 2); ?></div>
                        <div class="stat-label">Outstanding Bills</div>
                        <div class="stat-change down"><i class="fas fa-arrow-down"></i> Pending</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card orange" onclick="window.location.href='pending_bills.php'">
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-number">₹<?php echo number_format($pendingBills, 2); ?></div>
                        <div class="stat-label">Pending Payments</div>
                        <div class="stat-change down"><i class="fas fa-arrow-down"></i> Due</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card teal" onclick="window.location.href='expenses.php'">
                        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                        <div class="stat-number">₹<?php echo number_format($todayExpenses, 2); ?></div>
                        <div class="stat-label">Today's Expenses</div>
                        <div class="stat-change down"><i class="fas fa-arrow-down"></i> Spent</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="stat-card cyan" onclick="window.location.href='profit_loss.php'">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="stat-number">₹<?php echo number_format($netProfit, 2); ?></div>
                        <div class="stat-label">Net Profit</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Today</div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 2: FINANCIAL ANALYTICS (Charts)
            ============================================ -->
            <div class="row g-4 mb-4">
                <div class="col-xl-8 col-lg-8 col-md-12">
                    <div class="chart-card">
                        <h6><i class="fas fa-chart-area me-2 text-primary"></i>Revenue vs Expenses (Area)</h6>
                        <div class="chart-container"><canvas id="revenueExpenseChart"></canvas></div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-12">
                    <div class="chart-card">
                        <h6><i class="fas fa-chart-pie me-2 text-purple"></i>Payment Mode Distribution</h6>
                        <div class="chart-container"><canvas id="paymentModeChart"></canvas></div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="chart-card">
                        <h6><i class="fas fa-chart-bar me-2 text-success"></i>Monthly Revenue (Bar)</h6>
                        <div class="chart-container"><canvas id="monthlyRevenueChart"></canvas></div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="chart-card">
                        <h6><i class="fas fa-chart-bar me-2 text-warning"></i>Department Revenue (Horizontal)</h6>
                        <div class="chart-container"><canvas id="departmentRevenueChart"></canvas></div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="chart-card">
                        <h6><i class="fas fa-chart-line me-2 text-info"></i>Daily Collections (Line)</h6>
                        <div class="chart-container"><canvas id="dailyCollectionsChart"></canvas></div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 3: BILLING SUMMARY
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-file-invoice"></i> Billing Summary</h5>
                    <span class="badge-count">Monthly</span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-primary rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($opdRevenue, 2); ?></span>
                                <div class="text-muted text-xs">OPD</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-success rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($ipdRevenue, 2); ?></span>
                                <div class="text-muted text-xs">IPD</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-warning rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($otherRevenue, 2); ?></span>
                                <div class="text-muted text-xs">Others</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 4: PAYMENT SUMMARY
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-credit-card"></i> Payment Summary</h5>
                    <span class="badge-count">Overview</span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-success rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($paidBills, 2); ?></span>
                                <div class="text-muted text-xs">Paid Bills</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-danger rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($pendingBills, 2); ?></span>
                                <div class="text-muted text-xs">Pending Bills</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-warning rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($advancePayments, 2); ?></span>
                                <div class="text-muted text-xs">Advance Payments</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-purple rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($partialPayments, 2); ?></span>
                                <div class="text-muted text-xs">Partial Payments</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-teal rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($refunds, 2); ?></span>
                                <div class="text-muted text-xs">Refunds</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-primary rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($discounts, 2); ?></span>
                                <div class="text-muted text-xs">Discounts Given</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 5: RECENT TRANSACTIONS
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-list"></i> Recent Transactions</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="window.location.href='billing_list.php'">
                            <i class="fas fa-arrow-right"></i> View All
                        </button>
                    </div>
                </div>
                <div class="card-body-custom">
                    <?php if (!empty($recentTransactions)): ?>
                        <div class="table-responsive">
                            <table class="table-nurse">
                                <thead>
                                    <tr>
                                        <th>Bill No.</th>
                                        <th>Patient</th>
                                        <th>Department</th>
                                        <th>Amount</th>
                                        <th>Payment Mode</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $txn): 
                                        $status = ($txn['pending_amount'] == 0) ? 'Paid' : (($txn['pending_amount'] > 0 && $txn['pending_amount'] < $txn['total']) ? 'Partial' : 'Pending');
                                        $badgeClass = ($status == 'Paid') ? 'paid' : (($status == 'Partial') ? 'partial' : 'pending');
                                        $dept = 'N/A';
                                        if ($txn['patient_id']) {
                                            $deptQuery = "SELECT d.department FROM patients p LEFT JOIN doctor d ON p.doctor_id = d.doctor_id WHERE p.patient_id = " . (int)$txn['patient_id'];
                                            $deptRes = $conn->query($deptQuery);
                                            if ($deptRes && $deptRes->num_rows > 0) {
                                                $deptRow = $deptRes->fetch_assoc();
                                                $dept = $deptRow['department'] ?: 'General';
                                            }
                                        }
                                    ?>
                                        <tr onclick="window.location.href='view_bill.php?id=<?php echo $txn['bill_id']; ?>'">
                                            <td><span class="fw-bold">#<?php echo $txn['bill_no']; ?></span></td>
                                            <td><?php echo htmlspecialchars($txn['patient_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo $dept; ?></td>
                                            <td>₹<?php echo number_format($txn['total'], 2); ?></td>
                                            <td><?php echo $txn['payment_mode'] ?? 'N/A'; ?></td>
                                            <td><span class="badge-status <?php echo $badgeClass; ?>"><?php echo $status; ?></span></td>
                                            <td><?php echo date('d M Y', strtotime($txn['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-list"></i>
                            <p>No recent transactions</p>
                            <small>Transactions will appear here</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 6: PENDING PAYMENTS
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-clock"></i> Pending Payments</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-warning" onclick="window.location.href='pending_bills.php'">
                            <i class="fas fa-arrow-right"></i> View All
                        </button>
                    </div>
                </div>
                <div class="card-body-custom">
                    <?php if (!empty($pendingList)): ?>
                        <div class="table-responsive">
                            <table class="table-nurse">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Bill No.</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingList as $pend): 
                                        $dueDate = date('d M Y', strtotime($pend['created_at'] . ' + 30 days'));
                                        $status = ($pend['pending_amount'] == $pend['total']) ? 'Pending' : 'Partial';
                                        $badgeClass = ($status == 'Pending') ? 'pending' : 'partial';
                                    ?>
                                        <tr onclick="window.location.href='view_bill.php?id=<?php echo $pend['bill_id']; ?>'">
                                            <td><?php echo htmlspecialchars($pend['patient_name'] ?? 'Unknown'); ?></td>
                                            <td>#<?php echo $pend['bill_no']; ?></td>
                                            <td>₹<?php echo number_format($pend['pending_amount'], 2); ?></td>
                                            <td><?php echo $dueDate; ?></td>
                                            <td><span class="badge-status <?php echo $badgeClass; ?>"><?php echo $status; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-clock"></i>
                            <p>No pending payments</p>
                            <small>All payments are up to date</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 7: INSURANCE / TPA
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-shield-alt"></i> Insurance / TPA</h5>
                    <span class="badge-count">Claims</span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-primary rounded-lg">
                                <span class="fw-bold"><?php echo $claimsSubmitted; ?></span>
                                <div class="text-muted text-xs">Claims Submitted</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-success rounded-lg">
                                <span class="fw-bold"><?php echo $claimsApproved; ?></span>
                                <div class="text-muted text-xs">Claims Approved</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-warning rounded-lg">
                                <span class="fw-bold"><?php echo $claimsPending; ?></span>
                                <div class="text-muted text-xs">Claims Pending</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-purple rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($claimAmount, 2); ?></span>
                                <div class="text-muted text-xs">Claim Amount</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 8: EXPENSE SUMMARY
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-receipt"></i> Expense Summary (Year-to-Date)</h5>
                    <span class="badge-count">YTD</span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-danger rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($staffSalary, 2); ?></span>
                                <div class="text-muted text-xs">Staff Salary</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-warning rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($utilityBills, 2); ?></span>
                                <div class="text-muted text-xs">Utility Bills</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-primary rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($medicalPurchases, 2); ?></span>
                                <div class="text-muted text-xs">Medical Purchases</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-teal rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($maintenance, 2); ?></span>
                                <div class="text-muted text-xs">Maintenance</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="p-3 bg-soft-purple rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($otherExpenses, 2); ?></span>
                                <div class="text-muted text-xs">Other Expenses</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 9: DAILY CASH SUMMARY
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-money-bill-alt"></i> Daily Cash Summary</h5>
                    <span class="badge-count"><?php echo date('d M Y'); ?></span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-success rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($todayCollection, 2); ?></span>
                                <div class="text-muted text-xs">Today's Collection</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-danger rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($todayExpenses, 2); ?></span>
                                <div class="text-muted text-xs">Today's Expense</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-warning rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($todayNet, 2); ?></span>
                                <div class="text-muted text-xs">Net Today</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="p-3 bg-soft-primary rounded-lg">
                                <span class="fw-bold"><?php echo $todayTransactions; ?></span>
                                <div class="text-muted text-xs">Total Transactions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 10: TOP REVENUE DEPARTMENTS
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-trophy"></i> Top Revenue Departments</h5>
                    <span class="badge-count">Top 5</span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <?php 
                        $topDepts = [];
                        $topQuery = "
                            SELECT 
                                COALESCE(d.department, 'General') as dept,
                                COALESCE(SUM(b.total), 0) as rev
                            FROM billing b
                            LEFT JOIN patients p ON b.patient_id = p.patient_id
                            LEFT JOIN doctor d ON p.doctor_id = d.doctor_id
                            WHERE b.hospital_id = $hospital_id
                            AND (b.delete_flag = 0 OR b.delete_flag IS NULL)
                            GROUP BY dept
                            ORDER BY rev DESC
                            LIMIT 5
                        ";
                        $topRes = $conn->query($topQuery);
                        if ($topRes && $topRes->num_rows > 0) {
                            $topDepts = $topRes->fetch_all(MYSQLI_ASSOC);
                        } else {
                            $topDepts = [['dept' => 'No Data', 'rev' => 0]];
                        }
                        $maxRev = max(array_column($topDepts, 'rev')) ?: 1;
                        $colors = ['primary', 'success', 'warning', 'danger', 'purple'];
                        $bgColors = ['bg-soft-primary', 'bg-soft-success', 'bg-soft-warning', 'bg-soft-danger', 'bg-soft-purple'];
                        ?>
                        <?php foreach ($topDepts as $idx => $d): ?>
                        <div class="col-md-3 col-6">
                            <div class="p-3 <?php echo $bgColors[$idx % 5]; ?> rounded-lg">
                                <span class="fw-bold"><?php echo ($idx+1).'. '.htmlspecialchars($d['dept']); ?></span>
                                <div class="text-muted text-xs">₹<?php echo number_format($d['rev'], 2); ?></div>
                                <div class="progress mt-1">
                                    <div class="progress-bar bg-<?php echo $colors[$idx % 5]; ?>" style="width:<?php echo ($d['rev']/$maxRev)*100; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 11: QUICK ACTIONS
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                    <span class="badge-count">Shortcuts</span>
                </div>
                <div class="card-body-custom">
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-primary-glass" onclick="window.location.href='create_bill.php'">
                            <i class="fas fa-file-invoice"></i> Create Bill
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='collect_payment.php'">
                            <i class="fas fa-hand-holding-usd"></i> Receive Payment
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='process_refund.php'">
                            <i class="fas fa-undo-alt"></i> Refund
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='add_expense.php'">
                            <i class="fas fa-plus-circle"></i> Add Expense
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='print_invoice.php'">
                            <i class="fas fa-print"></i> Print Invoice
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='export_report.php'">
                            <i class="fas fa-chart-pie"></i> Generate Report
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='ledger.php'">
                            <i class="fas fa-book"></i> View Ledger
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='export_pdf.php'">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='export_excel.php'">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 12: ALERTS PANEL
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-exclamation-circle"></i> Alerts</h5>
                    <span class="badge-count"><?php echo $pendingBillsCount + $pendingClaimsCount; ?> Alerts</span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="alert-card border-left-warning">
                                <i class="fas fa-clock text-warning me-2"></i>
                                <strong>Pending Bills:</strong> <?php echo $pendingBillsCount; ?> bills pending.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert-card border-left-danger">
                                <i class="fas fa-file-medical-alt text-danger me-2"></i>
                                <strong>Insurance Claims:</strong> <?php echo $claimsPending; ?> claims pending approval.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert-card border-left-warning">
                                <i class="fas fa-truck text-warning me-2"></i>
                                <strong>Supplier Payments:</strong> <?php echo $dueSupplierPayments; ?> payments due this week.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert-card border-left-danger">
                                <i class="fas fa-coins text-danger me-2"></i>
                                <strong>Low Cash Balance:</strong> ₹<?php echo number_format($lowCashBalance, 2); ?> remaining.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert-card border-left-warning">
                                <i class="fas fa-file-invoice text-warning me-2"></i>
                                <strong>GST Reminder:</strong> GST return filing due on 20th.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 13: REPORT SHORTCUTS
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-folder-open"></i> Report Shortcuts</h5>
                    <span class="badge-count">Reports</span>
                </div>
                <div class="card-body-custom">
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-action" onclick="window.location.href='reports/revenue.php'">
                            <i class="fas fa-chart-line text-primary"></i> Revenue
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/expense.php'">
                            <i class="fas fa-chart-pie text-danger"></i> Expense
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/collection.php'">
                            <i class="fas fa-hand-holding-usd text-success"></i> Collection
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/cash_book.php'">
                            <i class="fas fa-book text-warning"></i> Cash Book
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/bank_book.php'">
                            <i class="fas fa-university text-info"></i> Bank Book
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/ledger.php'">
                            <i class="fas fa-book-open text-purple"></i> Ledger
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/profit_loss.php'">
                            <i class="fas fa-chart-bar text-danger"></i> Profit &amp; Loss
                        </button>
                        <button class="btn btn-action" onclick="window.location.href='reports/gst.php'">
                            <i class="fas fa-file-invoice text-warning"></i> GST Report
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
            SECTION 14: TODAY'S FINANCIAL SUMMARY
            ============================================ -->
            <div class="section-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-info-circle"></i> Today's Financial Summary</h5>
                    <span class="badge-count"><?php echo date('d M Y'); ?></span>
                </div>
                <div class="card-body-custom">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="p-3 bg-soft-success rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($todayCollection, 2); ?></span>
                                <div class="text-muted text-xs">Today's Collection</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-soft-danger rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($todayExpenses, 2); ?></span>
                                <div class="text-muted text-xs">Today's Expense</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-soft-warning rounded-lg">
                                <span class="fw-bold">₹<?php echo number_format($todayNet, 2); ?></span>
                                <div class="text-muted text-xs">Net Today</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-soft-primary rounded-lg">
                                <span class="fw-bold"><?php echo $todayTransactions; ?></span>
                                <div class="text-muted text-xs">Total Transactions</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2"><i class="fas fa-history me-2 text-muted"></i>Recent Activities</h6>
                            <ul class="list-unstyled small">
                                <?php 
                                $activityQuery = "
                                    SELECT CONCAT('Payment received from ', p.patient_name, ' – ₹', b.total) as msg
                                    FROM billing b
                                    LEFT JOIN patients p ON b.patient_id = p.patient_id
                                    WHERE b.hospital_id = $hospital_id AND b.paid_amount > 0
                                    ORDER BY b.created_at DESC LIMIT 3
                                ";
                                $actRes = $conn->query($activityQuery);
                                if ($actRes && $actRes->num_rows > 0) {
                                    while ($act = $actRes->fetch_assoc()) {
                                        echo '<li class="py-1"><i class="fas fa-check-circle text-success me-2"></i> '.htmlspecialchars($act['msg']).'</li>';
                                    }
                                } else {
                                    echo '<li class="py-1 text-muted">No recent activities.</li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2"><i class="fas fa-hourglass-half me-2 text-warning"></i>Upcoming Due Payments</h6>
                            <ul class="list-unstyled small">
                                <?php 
                                $dueQuery = "
                                    SELECT p.patient_name, b.pending_amount, b.bill_no
                                    FROM billing b
                                    LEFT JOIN patients p ON b.patient_id = p.patient_id
                                    WHERE b.hospital_id = $hospital_id AND b.pending_amount > 0
                                    ORDER BY b.created_at ASC LIMIT 3
                                ";
                                $dueRes = $conn->query($dueQuery);
                                if ($dueRes && $dueRes->num_rows > 0) {
                                    while ($due = $dueRes->fetch_assoc()) {
                                        echo '<li class="py-1"><span class="badge-status pending me-2">Pending</span> '.htmlspecialchars($due['patient_name']).' – ₹'.number_format($due['pending_amount'], 2).'</li>';
                                    }
                                } else {
                                    echo '<li class="py-1 text-muted">No due payments.</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-bold mb-2"><i class="fas fa-coins me-2 text-success"></i>Latest Collections</h6>
                            <ul class="list-unstyled small">
                                <?php 
                                $collQuery = "
                                    SELECT p.patient_name, b.total, b.payment_mode
                                    FROM billing b
                                    LEFT JOIN patients p ON b.patient_id = p.patient_id
                                    WHERE b.hospital_id = $hospital_id AND b.paid_amount > 0
                                    ORDER BY b.created_at DESC LIMIT 3
                                ";
                                $collRes = $conn->query($collQuery);
                                if ($collRes && $collRes->num_rows > 0) {
                                    while ($coll = $collRes->fetch_assoc()) {
                                        echo '<li class="py-1"><i class="fas fa-rupee-sign text-success me-2"></i> ₹'.number_format($coll['total'], 2).' – '.htmlspecialchars($coll['patient_name']).' ('.$coll['payment_mode'].')</li>';
                                    }
                                } else {
                                    echo '<li class="py-1 text-muted">No recent collections.</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
    
    <!-- ============================================
    JAVASCRIPT - Charts
    ============================================ -->
    <script>
        // 1. Revenue vs Expenses (Area Chart)
        const ctx1 = document.getElementById('revenueExpenseChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthlyLabels); ?>,
                datasets: [
                    {
                        label: 'Revenue',
                        data: <?php echo json_encode($monthlyRevenueData); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102,126,234,0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode($monthlyExpenseData); ?>,
                        borderColor: '#e53e3e',
                        backgroundColor: 'rgba(229,62,62,0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });

        // 2. Payment Mode Distribution (Donut)
        const ctx2 = document.getElementById('paymentModeChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($modeLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($modeValues); ?>,
                    backgroundColor: ['#ed8936', '#805ad5', '#d53f8c', '#5a67d8', '#38b2ac'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    } 
                }, 
                cutout: '60%' 
            }
        });

        // 3. Monthly Revenue (Bar)
        const ctx3 = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthlyBarLabels); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($monthlyBarValues); ?>,
                    backgroundColor: '#38a169',
                    borderRadius: 6
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });

        // 4. Department Revenue (Horizontal Bar)
        const ctx4 = document.getElementById('departmentRevenueChart').getContext('2d');
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($deptLabels); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($deptValues); ?>,
                    backgroundColor: ['#667eea', '#38a169', '#ed8936', '#805ad5', '#d53f8c'],
                    borderRadius: 6
                }]
            },
            options: { 
                indexAxis: 'y', 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false } 
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });

        // 5. Daily Collections (Line)
        const ctx5 = document.getElementById('dailyCollectionsChart').getContext('2d');
        new Chart(ctx5, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'Collection (₹)',
                    data: <?php echo json_encode($dailyValues); ?>,
                    borderColor: '#38b2ac',
                    backgroundColor: 'rgba(56,178,172,0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>