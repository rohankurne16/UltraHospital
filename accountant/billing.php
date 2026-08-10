<?php
session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

// Ensure database connection exists before querying
if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check your config.");
}

 $hospital_id = $_SESSION['hospital_id'] ?? 0;

// Filters
 $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
 $status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
 $from   = isset($_GET['from']) ? $conn->real_escape_string($_GET['from']) : '';
 $to     = isset($_GET['to']) ? $conn->real_escape_string($_GET['to']) : '';

// Base Query (Using the new `bills` table)
 $where = "b.hospital_id = $hospital_id AND b.delete_flag = 0";

if ($search) {
    $where .= " AND (b.bill_id LIKE '%$search%' OR p.patient_name LIKE '%$search%')";
}
if ($status != '') {
    $statusMap = [
        'paid' => 'Paid',
        'pending' => 'Unpaid',
        'partial' => 'Partial'
    ];
    if (isset($statusMap[$status])) {
        $dbStatus = $statusMap[$status];
        $where .= " AND b.status = '$dbStatus'";
    }
}
if ($from && $to) {
    $where .= " AND DATE(b.bill_date) BETWEEN '$from' AND '$to'";
}

 $query = "SELECT b.*, p.patient_name 
          FROM bills b
          LEFT JOIN patients p ON b.patient_id = p.patient_id
          WHERE $where 
          ORDER BY b.bill_date DESC, b.bill_id DESC";

 $result = $conn->query($query);
 $totalBills = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bills & Invoices - Accountant</title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? 'favicon.ico'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #334155; }
        a { text-decoration: none; }
        
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 16px; }
        .mb-4 { margin-bottom: 24px; }
        .fw-bold { font-weight: 700; }
        .text-muted { color: #94a3b8; }
        
        /* Layout */
        .main-content { margin-left: 260px; padding: 32px; min-height: 100vh; width: calc(100% - 260px); }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; width: 100%; } }
        
        /* Page Header */
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 12px; }
        .page-header h1 i { color: var(--primary); }
        
        /* Buttons */
        .btn { display: inline-flex; align-items: center; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: 0 4px 15px -4px rgba(99, 102, 241, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(99, 102, 241, 0.5); }
        
        /* Filter Card */
        .filter-card { background: white; padding: 20px 24px; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control { padding: 10px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; background: var(--light); transition: all 0.2s; min-width: 180px; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); background: white; }
        .btn-filter { background: var(--primary); color: white; padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; height: 42px; }
        .btn-filter:hover { background: var(--primary-dark); }
        .btn-reset { background: #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; height: 42px; }
        .btn-reset:hover { background: #cbd5e1; }
        
        /* Table Card */
        .table-card { background: white; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header-custom { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .card-header-custom h5 { font-weight: 700; color: var(--dark); margin: 0; font-size: 18px; display: flex; align-items: center; gap: 12px; }
        .card-header-custom h5 i { color: var(--primary); }
        .badge-count { background: #eff6ff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; color: var(--primary); border: 1px solid #dbeafe; }
        
        .table-responsive { overflow-x: auto; width: 100%; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 900px; }
        thead th { background: var(--light); color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; padding: 16px; text-align: left; border-bottom: 2px solid var(--border); }
        tbody td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        tbody tr { transition: background 0.15s ease; cursor: pointer; }
        tbody tr:hover { background: var(--light); }
        tbody tr:last-child td { border-bottom: none; }
        
        /* Status Badges */
        .badge-status { padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; text-transform: uppercase; letter-spacing: 0.03em; }
        .badge-status.Paid { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-status.Partial { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-status.Unpaid { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        
        /* Action Icons */
        .action-btn { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; margin-right: 4px; border: 1px solid transparent; }
        .action-btn.view { background: #eff6ff; color: #3b82f6; }
        .action-btn.view:hover { background: #dbeafe; }
        .action-btn.collect { background: #f0fdf4; color: #10b981; }
        .action-btn.collect:hover { background: #dcfce7; }
        .action-btn.print { background: #f8fafc; color: #64748b; }
        .action-btn.print:hover { background: #f1f5f9; }
        
        /* Empty State */
        .empty-state { padding: 60px 20px; text-align: center; color: #94a3b8; }
        .empty-state i { font-size: 64px; margin-bottom: 16px; color: #cbd5e1; }
        .empty-state p { font-size: 18px; font-weight: 500; color: #64748b; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-file-invoice-dollar"></i> Bills & Invoices</h1>
                <a href="create_bill.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Bill
                </a>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" placeholder="Bill ID / Patient Name" id="searchInput" class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="paid" <?php echo $status=='paid'?'selected':''; ?>>Paid</option>
                        <option value="pending" <?php echo $status=='pending'?'selected':''; ?>>Unpaid</option>
                        <option value="partial" <?php echo $status=='partial'?'selected':''; ?>>Partial</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" id="fromDate" class="form-control" value="<?php echo htmlspecialchars($from); ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" id="toDate" class="form-control" value="<?php echo htmlspecialchars($to); ?>">
                </div>
                <button class="btn-filter" onclick="applyFilters()"><i class="fas fa-search"></i> Apply</button>
                <button class="btn-reset" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset</button>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-list-ul"></i> All Bills</h5>
                    <span class="badge-count"><?php echo $totalBills; ?> Records Found</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Patient Name</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $statusClass = htmlspecialchars($row['status']);
                                ?>
                                <tr>
                                    <td><strong>#INV-<?php echo str_pad($row['bill_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['patient_name'] ?? 'Walk-in Patient'); ?></td>
                                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['paid_amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['balance_amount'], 2); ?></td>
                                    <td><span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusClass; ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($row['bill_date'])); ?></td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="view_bill.php?id=<?php echo $row['bill_id']; ?>" class="action-btn view" title="View Bill"><i class="fas fa-eye"></i></a>
                                        <?php if($row['balance_amount'] > 0): ?>
                                        <a href="collect_payment.php?bill_id=<?php echo $row['bill_id']; ?>" class="action-btn collect" title="Collect Payment"><i class="fas fa-hand-holding-usd"></i></a>
                                        <?php endif; ?>
                                        <a href="print_invoice.php?id=<?php echo $row['bill_id']; ?>" class="action-btn print" title="Print Invoice" target="_blank"><i class="fas fa-print"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-folder-open"></i>
                                            <p>No bills found matching your criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
    </div>

    <script>
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            window.location.href = `billing.php?search=${encodeURIComponent(search)}&status=${status}&from=${from}&to=${to}`;
        }
        
        function resetFilters() {
            window.location.href = 'billing.php';
        }
        
        // Allow pressing Enter to search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
</body>
</html>