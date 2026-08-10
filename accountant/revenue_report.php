<?php
session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

// Check login
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

 $hospital_id = $_SESSION['hospital_id'] ?? 0;
 $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
 $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
 $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
 $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Build query conditions based on the new `bills` table
if ($period == 'daily') {
    $where = "DATE(bill_date) = '$date'";
    $label = date('d M Y', strtotime($date));
} elseif ($period == 'monthly') {
    $where = "MONTH(bill_date) = $month AND YEAR(bill_date) = $year";
    $label = date('F Y', mktime(0, 0, 0, $month, 1, $year));
} else { // yearly
    $where = "YEAR(bill_date) = $year";
    $label = "Year " . $year;
}

 $query = "SELECT 
            COALESCE(SUM(total_amount), 0) as total, 
            COALESCE(SUM(paid_amount), 0) as paid, 
            COALESCE(SUM(balance_amount), 0) as pending 
          FROM bills 
          WHERE hospital_id = $hospital_id AND delete_flag = 0 AND $where";
          
 $result = $conn->query($query);
 $row = $result ? $result->fetch_assoc() : ['total' => 0, 'paid' => 0, 'pending' => 0];

// Calculate collection rate percentage
 $collectionRate = ($row['total'] > 0) ? ($row['paid'] / $row['total']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Report · Accountant</title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? 'favicon.ico'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.01);
            --hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: var(--dark); line-height: 1.5; }
        a { text-decoration: none; }
        
        .main-wrapper { display: flex; min-height: 100vh; background: #f1f5f9; }
        .main-content { margin-left: 260px; padding: 32px; width: calc(100% - 260px); margin-top: 67px; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; width: 100%; } }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-weight: 800; font-size: 24px; color: var(--dark); display: flex; align-items: center; gap: 12px; }
        .page-header h1 i { color: var(--primary); }
        
        /* Filter Card */
        .filter-card { background: #fff; padding: 20px 24px; border-radius: 16px; border: 1px solid var(--slate-200); box-shadow: var(--card-shadow); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 11px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { padding: 10px 14px; border: 1px solid var(--slate-200); border-radius: 10px; font-size: 14px; background: var(--slate-50); transition: all 0.2s; min-width: 150px; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); background: #fff; }
        
        .btn { display: inline-flex; align-items: center; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 10px; border: none; cursor: pointer; transition: all 0.3s ease; gap: 8px; text-decoration: none; height: 42px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; box-shadow: 0 4px 12px -2px rgba(79, 70, 229, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -2px rgba(79, 70, 229, 0.4); }
        
        /* Summary Cards */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid var(--slate-200); box-shadow: var(--card-shadow); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hover-shadow); }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px; }
        .stat-card .val { font-size: 28px; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; margin-bottom: 4px; }
        .stat-card .lbl { font-size: 14px; font-weight: 600; color: var(--slate-500); }
        
        .bg-primary-soft { background: #eef2ff; color: var(--primary); }
        .bg-success-soft { background: #ecfdf5; color: var(--success); }
        .bg-danger-soft { background: #fef2f2; color: var(--danger); }
        
        /* Report Card */
        .report-card { background: #fff; border-radius: 16px; border: 1px solid var(--slate-200); box-shadow: var(--card-shadow); overflow: hidden; }
        .report-header { padding: 20px 24px; border-bottom: 1px solid var(--slate-100); display: flex; justify-content: space-between; align-items: center; }
        .report-header h3 { font-size: 16px; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .report-header h3 i { color: var(--primary); }
        .report-body { padding: 24px; }
        
        .progress-wrap { margin-top: 24px; }
        .progress-label { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--slate-600); margin-bottom: 8px; }
        .progress-bar { background: var(--slate-100); border-radius: 40px; height: 12px; width: 100%; overflow: hidden; }
        .progress-fill { height: 12px; border-radius: 40px; background: linear-gradient(90deg, var(--success), #34d399); transition: width 0.6s ease; }
        
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .detail-table tr { border-bottom: 1px solid var(--slate-100); }
        .detail-table tr:last-child { border-bottom: none; }
        .detail-table td { padding: 14px 0; font-size: 14px; color: var(--slate-600); }
        .detail-table td:last-child { text-align: right; font-weight: 700; color: var(--dark); font-size: 16px; }
    </style>
</head>
<body>

<!-- ===== HEADER & SIDEBAR ===== -->
<?php include '../header.php'; ?>
<?php include '../Sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Revenue Report</h1>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="form-group">
                <label>Period</label>
                <select id="periodSelect" class="form-control" onchange="updatePeriodUI()">
                    <option value="daily" <?php echo $period=='daily'?'selected':''; ?>>Daily</option>
                    <option value="monthly" <?php echo $period=='monthly'?'selected':''; ?>>Monthly</option>
                    <option value="yearly" <?php echo $period=='yearly'?'selected':''; ?>>Yearly</option>
                </select>
            </div>
            
            <div class="form-group" id="dateGroup" style="<?php echo $period!='daily'?'display:none':''; ?>">
                <label>Date</label>
                <input type="date" id="dailyDate" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
            </div>
            
            <div class="form-group" id="monthGroup" style="<?php echo $period!='monthly'?'display:none':''; ?>">
                <label>Month</label>
                <select id="monthSelect" class="form-control">
                    <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo $m==$month?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Year</label>
                <select id="yearSelect" class="form-control">
                    <?php $currYear = (int)date('Y'); ?>
                    <?php for ($y=$currYear; $y>=2023; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y==$year?'selected':''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button class="btn btn-primary" onclick="applyReport()">
                <i class="fas fa-filter"></i> Generate Report
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="stat-card">
                <div class="icon bg-primary-soft"><i class="fas fa-coins"></i></div>
                <div class="val">₹<?php echo number_format($row['total'] ?? 0, 2); ?></div>
                <div class="lbl">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="icon bg-success-soft"><i class="fas fa-check-circle"></i></div>
                <div class="val">₹<?php echo number_format($row['paid'] ?? 0, 2); ?></div>
                <div class="lbl">Total Collected</div>
            </div>
            <div class="stat-card">
                <div class="icon bg-danger-soft"><i class="fas fa-hourglass-half"></i></div>
                <div class="val">₹<?php echo number_format($row['pending'] ?? 0, 2); ?></div>
                <div class="lbl">Pending Balance</div>
            </div>
        </div>

        <!-- Detailed Report Card -->
        <div class="report-card">
            <div class="report-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Financial Summary for <?php echo $label; ?></h3>
            </div>
            <div class="report-body">
                <table class="detail-table">
                    <tr>
                        <td>Gross Revenue</td>
                        <td>₹<?php echo number_format($row['total'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Amount Collected</td>
                        <td style="color: var(--success);">₹<?php echo number_format($row['paid'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Outstanding Balance</td>
                        <td style="color: var(--danger);">₹<?php echo number_format($row['pending'] ?? 0, 2); ?></td>
                    </tr>
                </table>

                <?php if (($row['total'] ?? 0) > 0): ?>
                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Collection Rate</span>
                        <span><?php echo round($collectionRate, 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $collectionRate; ?>%;"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    </main>
</div>

<script>
    function updatePeriodUI() {
        const period = document.getElementById('periodSelect').value;
        document.getElementById('dateGroup').style.display = period === 'daily' ? 'flex' : 'none';
        document.getElementById('monthGroup').style.display = period === 'monthly' ? 'flex' : 'none';
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
    
    // Run once on load to set correct UI state
    updatePeriodUI();
</script>

</body>
</html>