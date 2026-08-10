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

// Fetch all pending bills (balance_amount > 0)
 $query = "
    SELECT b.*, p.patient_name, p.mobile 
    FROM bills b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    WHERE b.hospital_id = $hospital_id 
    AND b.balance_amount > 0 
    AND b.delete_flag = 0
    ORDER BY b.bill_date ASC, b.bill_id DESC
";
 $result = $conn->query($query);
 $totalPending = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Bills - Accountant</title>
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
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f1f5f9; 
            color: var(--dark); 
            line-height: 1.5;
        }
        a { text-decoration: none; }
        
        /* Layout */
        .main-wrapper { display: flex; min-height: 100vh; background: #f1f5f9; }
        .main-content { margin-left: 260px; padding: 32px; width: calc(100% - 260px); margin-top: 67px; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 20px; width: 100%; } }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-weight: 800; font-size: 28px; letter-spacing: -0.5px; color: var(--dark); display: flex; align-items: center; gap: 12px; }
        .page-header h1 i { color: var(--danger); }
        
        /* Buttons */
        .btn { display: inline-flex; align-items: center; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; box-shadow: 0 4px 15px -4px rgba(99, 102, 241, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(99, 102, 241, 0.5); }
        
        /* Table Card */
        .table-card { background: #ffffff; border-radius: 20px; border: 1px solid var(--slate-200); box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header-custom { padding: 20px 24px; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #fff; }
        .card-header-custom h5 { font-weight: 700; color: var(--dark); margin: 0; font-size: 18px; display: flex; align-items: center; gap: 12px; }
        .card-header-custom h5 i { color: var(--primary); font-size: 20px; }
        .badge-count { background: #fff1f2; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; color: var(--danger); border: 1px solid #fecaca; }
        
        /* Tables */
        .table-responsive { overflow-x: auto; width: 100%; }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 900px; }
        .table-custom thead th { background: var(--slate-50); color: var(--slate-500); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px; text-align: left; border-bottom: 2px solid var(--slate-200); }
        .table-custom tbody td { padding: 16px; border-bottom: 1px solid var(--slate-100); color: var(--slate-800); vertical-align: middle; }
        .table-custom tbody tr { transition: background 0.15s ease; }
        .table-custom tbody tr:hover { background: var(--slate-50); }
        .table-custom tbody tr:last-child td { border-bottom: none; }
        
        /* Badges */
        .badge-status { padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; text-transform: uppercase; letter-spacing: 0.03em; }
        .badge-status.Partial { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-status.Unpaid { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        
        /* Action Icons */
        .action-btn { 
            display: inline-flex; align-items: center; justify-content: center; 
            padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; 
            transition: all 0.2s ease; text-decoration: none; gap: 6px; 
            background: var(--success); color: white; 
        }
        .action-btn:hover { background: #0ca672; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        .action-btn.view { background: #eef2ff; color: var(--primary); border: 1px solid #c7d2fe; }
        .action-btn.view:hover { background: var(--primary); color: white; border-color: var(--primary); }
        
        /* Empty State */
        .empty-state { padding: 60px 20px; text-align: center; color: var(--slate-400); }
        .empty-state i { font-size: 64px; margin-bottom: 16px; color: var(--success); }
        .empty-state p { font-size: 18px; font-weight: 500; color: var(--slate-600); }
        .empty-state small { font-size: 14px; color: var(--slate-400); display: block; margin-top: 8px; }
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
            <h1><i class="fas fa-clock"></i> Pending Bills</h1>
            <a href="billing_list.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to All Bills
            </a>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-list-ul"></i> Outstanding Invoices</h5>
                <span class="badge-count"><?php echo $totalPending; ?> Pending Records</span>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Patient Name</th>
                            <th>Contact</th>
                            <th>Bill Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance Due</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color: var(--primary);">#INV-<?php echo str_pad($row['bill_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['patient_name'] ?? 'Walk-in'); ?></td>
                                    <td style="color: var(--slate-500);"><?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?></td>
                                    <td style="color: var(--slate-500);"><?php echo date('d M Y', strtotime($row['bill_date'])); ?></td>
                                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td style="color: var(--success); font-weight: 600;">₹<?php echo number_format($row['paid_amount'], 2); ?></td>
                                    <td style="color: var(--danger); font-weight: 700;">₹<?php echo number_format($row['balance_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = htmlspecialchars($row['status']);
                                        echo "<span class='badge-status {$statusClass}'>{$row['status']}</span>";
                                        ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="collect_payment.php?bill_id=<?php echo $row['bill_id']; ?>" class="action-btn">
                                            <i class="fas fa-hand-holding-usd"></i> Collect
                                        </a>
                                        <a href="view_bill.php?id=<?php echo $row['bill_id']; ?>" class="action-btn view" style="margin-left: 8px;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <p>No Pending Bills!</p>
                                        <small>All patient bills have been fully paid.</small>
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

</body>
</html>