<?php
session_start();
require_once '../config/hospital.php';
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$period = $_GET['period'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

if ($period == 'daily') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $where = "DATE(created_at) = '$date'";
    $label = date('d M Y', strtotime($date));
} elseif ($period == 'monthly') {
    $where = "MONTH(created_at) = $month AND YEAR(created_at) = $year";
    $label = date('F Y', strtotime("$year-$month-01"));
} else { // yearly
    $where = "YEAR(created_at) = $year";
    $label = $year;
}

$query = "SELECT SUM(total) as total, SUM(paid_amount) as paid, SUM(pending_amount) as pending FROM billing 
          WHERE hospital_id = $hospital_id AND delete_flag = 0 AND $where";
$result = $conn->query($query);
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Revenue Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        .card { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; max-width:600px; margin:0 auto; }
        .stat { display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid #f1f5f9; }
        .stat:last-child { border-bottom:none; }
        .stat .label { font-weight:500; color:#64748b; }
        .stat .value { font-weight:700; font-size:18px; }
        .filter-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; background:white; padding:16px; border-radius:12px; border:1px solid #e2e8f0; }
        .filter-bar select, .filter-bar input { padding:8px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; background:#f8fafc; }
        .btn { padding:8px 18px; border-radius:8px; border:none; font-weight:600; cursor:pointer; background:#ed8936; color:white; }
        .btn:hover { background:#d97706; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <h1 style="font-size:24px; font-weight:700; margin-bottom:20px;"><i class="fas fa-chart-line" style="color:#ed8936;"></i> Revenue Report</h1>

            <div class="filter-bar">
                <select id="periodSelect" onchange="updatePeriod()">
                    <option value="daily" <?php echo $period=='daily'?'selected':''; ?>>Daily</option>
                    <option value="monthly" <?php echo $period=='monthly'?'selected':''; ?>>Monthly</option>
                    <option value="yearly" <?php echo $period=='yearly'?'selected':''; ?>>Yearly</option>
                </select>
                <input type="date" id="dailyDate" value="<?php echo date('Y-m-d'); ?>" style="<?php echo $period!='daily'?'display:none':''; ?>">
                <select id="monthSelect" style="<?php echo $period!='monthly'?'display:none':''; ?>">
                    <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?php echo sprintf('%02d',$m); ?>" <?php echo $m==$month?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
                <select id="yearSelect">
                    <option value="2024" <?php echo $year=='2024'?'selected':''; ?>>2024</option>
                    <option value="2025" <?php echo $year=='2025'?'selected':''; ?>>2025</option>
                    <option value="2026" <?php echo $year=='2026'?'selected':''; ?>>2026</option>
                </select>
                <button class="btn" onclick="applyReport()">Apply</button>
            </div>

            <div class="card">
                <h3 style="margin-bottom:12px;">Summary for <?php echo $label; ?></h3>
                <div class="stat"><span class="label">Total Revenue</span><span class="value">₹ <?php echo number_format($row['total'] ?? 0, 2); ?></span></div>
                <div class="stat"><span class="label">Total Paid</span><span class="value">₹ <?php echo number_format($row['paid'] ?? 0, 2); ?></span></div>
                <div class="stat"><span class="label">Pending</span><span class="value">₹ <?php echo number_format($row['pending'] ?? 0, 2); ?></span></div>
            </div>
        </main>
    </div>
    <script>
        function updatePeriod() {
            const period = document.getElementById('periodSelect').value;
            document.getElementById('dailyDate').style.display = period === 'daily' ? 'inline' : 'none';
            document.getElementById('monthSelect').style.display = period === 'monthly' ? 'inline' : 'none';
        }
        function applyReport() {
            const period = document.getElementById('periodSelect').value;
            const year = document.getElementById('yearSelect').value;
            let url = `revenue_report.php?period=${period}&year=${year}`;
            if (period === 'daily') {
                url += `&date=${document.getElementById('dailyDate').value}`;
            } else if (period === 'monthly') {
                url += `&month=${document.getElementById('monthSelect').value}`;
            }
            window.location.href = url;
        }
    </script>
</body>
</html>