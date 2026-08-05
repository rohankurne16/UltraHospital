<?php
session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = "b.hospital_id = $hospital_id AND b.delete_flag = 0";
if ($search) $where .= " AND (b.bill_no LIKE '%$search%' OR p.patient_name LIKE '%$search%')";
if ($status == 'paid') $where .= " AND b.pending_amount = 0";
elseif ($status == 'pending') $where .= " AND b.pending_amount = b.total";
elseif ($status == 'partial') $where .= " AND b.pending_amount > 0 AND b.pending_amount < b.total";
if ($from && $to) $where .= " AND DATE(b.created_at) BETWEEN '$from' AND '$to'";

$query = "SELECT b.*, p.patient_name FROM billing b
          LEFT JOIN patients p ON b.patient_id = p.patient_id
          WHERE $where ORDER BY b.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bills - Accountant</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? 'favicon.ico'; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; background:#f0f4f8; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        @media (max-width:768px){ .main-content{ padding:16px; } }
        .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
        .page-header h1 { font-size:24px; font-weight:700; color:#1a202c; display:flex; align-items:center; gap:10px; }
        .page-header h1 i { color:#ed8936; }
        .filter-bar { display:flex; flex-wrap:wrap; gap:12px; background:white; padding:16px 20px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px; }
        .filter-bar input, .filter-bar select { padding:8px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; background:#f8fafc; }
        .filter-bar .btn { padding:8px 18px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:#ed8936; color:white; transition:0.2s; }
        .filter-bar .btn:hover { background:#d97706; }
        .table-card { background:white; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; }
        .table-card .card-header { padding:16px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
        .table-card .card-header h5 { font-weight:700; font-size:16px; display:flex; align-items:center; gap:8px; }
        .table-card .card-header h5 i { color:#ed8936; }
        .table-responsive { overflow-x:auto; padding:0 4px; }
        table { width:100%; border-collapse:collapse; font-size:13px; min-width:700px; }
        thead th { background:#f8fafc; color:#64748b; font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:0.03em; padding:12px 16px; text-align:left; border-bottom:2px solid #e2e8f0; }
        tbody td { padding:12px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        tbody tr:hover { background:#f8fafc; }
        .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-paid { background:#d1fae5; color:#065f46; }
        .badge-pending { background:#fef3c7; color:#92400e; }
        .badge-partial { background:#fef3c7; color:#92400e; }
        .action-btn { padding:4px 10px; border-radius:6px; border:none; background:transparent; transition:0.2s; cursor:pointer; }
        .action-btn:hover { background:#eef2ff; }
        .btn-sm { padding:4px 12px; border-radius:6px; border:none; font-size:12px; font-weight:600; cursor:pointer; background:#ed8936; color:white; }
        .btn-sm:hover { background:#d97706; }
        .empty { padding:40px 20px; text-align:center; color:#94a3b8; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-file-invoice"></i> Billing</h1>
                <a href="add_bill.php" class="btn-sm" style="padding:10px 20px; background:#ed8936; color:white; border-radius:8px; text-decoration:none;"><i class="fas fa-plus"></i> New Bill</a>
            </div>

            <div class="filter-bar">
                <input type="text" placeholder="Search bill no / patient..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="paid" <?php echo $status=='paid'?'selected':''; ?>>Paid</option>
                    <option value="pending" <?php echo $status=='pending'?'selected':''; ?>>Pending</option>
                    <option value="partial" <?php echo $status=='partial'?'selected':''; ?>>Partial</option>
                </select>
                <input type="date" id="fromDate" value="<?php echo $from; ?>" placeholder="From">
                <input type="date" id="toDate" value="<?php echo $to; ?>" placeholder="To">
                <button class="btn" onclick="applyFilters()"><i class="fas fa-search"></i> Apply</button>
                <button class="btn" style="background:#64748b;" onclick="resetFilters()">Reset</button>
            </div>

            <div class="table-card">
                <div class="card-header">
                    <h5><i class="fas fa-list-ul"></i> All Bills</h5>
                    <span class="badge-count" style="background:#f1f5f9; padding:2px 12px; border-radius:20px; font-size:12px; color:#4a5568;"><?php echo $result->num_rows; ?> records</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th>Patient</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $statusClass = 'paid';
                                    $statusLabel = 'Paid';
                                    if ($row['pending_amount'] == $row['total']) { $statusClass = 'pending'; $statusLabel = 'Pending'; }
                                    elseif ($row['pending_amount'] > 0 && $row['pending_amount'] < $row['total']) { $statusClass = 'partial'; $statusLabel = 'Partial'; }
                                ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($row['bill_no']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['patient_name'] ?? 'Unknown'); ?></td>
                                    <td>₹ <?php echo number_format($row['total'], 2); ?></td>
                                    <td>₹ <?php echo number_format($row['paid_amount'], 2); ?></td>
                                    <td>₹ <?php echo number_format($row['pending_amount'], 2); ?></td>
                                    <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="view_bill.php?id=<?php echo $row['id']; ?>" class="action-btn" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="collect_payment.php?id=<?php echo $row['id']; ?>" class="action-btn" title="Collect"><i class="fas fa-hand-holding-usd"></i></a>
                                        <a href="print_invoice.php?id=<?php echo $row['id']; ?>" class="action-btn" title="Print"><i class="fas fa-print"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="empty">No bills found.</td></tr>
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
    </script>
</body>
</html>