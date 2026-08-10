<?php
// ============================================================
// PRINT BILL – Professional Printable Layout
// ============================================================

session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

 $bill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
 $hospital_id = $_SESSION['hospital_id'] ?? 0;

// Fetch Hospital Details
 $hospital = [];
 $hospQuery = $conn->query("SELECT * FROM hospital_master WHERE hospital_id = $hospital_id");
if ($hospQuery && $hospQuery->num_rows > 0) {
    $hospital = $hospQuery->fetch_assoc();
}

// Fetch Bill Details (Using new 'bills' table)
 $query = "
    SELECT b.*, p.patient_name, p.mobile, p.address, p.email 
    FROM bills b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    WHERE b.bill_id = $bill_id AND b.hospital_id = $hospital_id AND b.delete_flag = 0
";
 $result = $conn->query($query);
if (!$result || $result->num_rows == 0) { 
    die('<h2>Bill not found.</h2>'); 
}
 $bill = $result->fetch_assoc();

// Fetch Bill Items
 $items = [];
 $itemQuery = $conn->query("SELECT * FROM bill_items WHERE bill_id = $bill_id");
if ($itemQuery && $itemQuery->num_rows > 0) {
    while ($row = $itemQuery->fetch_assoc()) {
        $items[] = $row;
    }
}

 $statusClass = '';
if ($bill['status'] == 'Paid') $statusClass = 'paid';
elseif ($bill['status'] == 'Partial') $statusClass = 'partial';
else $statusClass = 'unpaid';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #INV-<?php echo str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #e2e8f0; 
            color: var(--dark); 
            padding: 40px 20px;
            line-height: 1.6;
        }
        
        /* Invoice Container */
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        /* Action Buttons (Screen only) */
        .action-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn-print { 
            background: var(--primary); color: white; border: none; padding: 10px 20px; 
            border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px; 
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-print:hover { background: #4f46e5; }
        .btn-close { 
            background: #64748b; color: white; border: none; padding: 10px 20px; 
            border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px; 
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-close:hover { background: #475569; }
        
        /* Header */
        .header { 
            padding: 32px 40px; 
            background: linear-gradient(135deg, var(--primary), #8b5cf6); 
            color: white; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .logo-img { 
            height: 60px; width: auto; background: white; padding: 5px; border-radius: 10px; 
            object-fit: contain; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .hospital-name { font-size: 24px; font-weight: 800; margin: 0; }
        .hospital-meta { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        
        .header-right { text-align: right; }
        .header-right h1 { font-size: 28px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }
        .header-right p { font-size: 14px; margin-top: 4px; }
        
        /* Body */
        .body-content { padding: 40px; }
        
        .info-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 40px; 
            margin-bottom: 32px; 
        }
        .info-card h4 { 
            text-transform: uppercase; font-size: 12px; letter-spacing: 1px; 
            color: #94a3b8; margin-bottom: 8px; 
        }
        .info-card p { font-size: 15px; font-weight: 600; color: var(--dark); }
        .info-card span { font-size: 14px; color: #64748b; display: block; margin-top: 2px;}
        
        /* Status Badge */
        .status-badge { 
            display: inline-block; padding: 6px 16px; border-radius: 20px; 
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; 
            margin-top: 8px;
        }
        .status-badge.paid { background: #ecfdf5; color: var(--success); border: 1px solid #a7f3d0; }
        .status-badge.partial { background: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }
        .status-badge.unpaid { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
        
        /* Table */
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        .item-table thead th { 
            background: var(--light); color: #64748b; font-weight: 700; 
            font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; 
            padding: 12px 16px; text-align: left; border-bottom: 2px solid var(--border); 
        }
        .item-table thead th:last-child { text-align: right; }
        .item-table tbody td { 
            padding: 16px; border-bottom: 1px solid var(--light); font-size: 14px; 
        }
        .item-table tbody td:last-child { text-align: right; font-weight: 600; }
        
        /* Totals */
        .totals-grid { 
            display: flex; justify-content: flex-end; margin-bottom: 32px; 
        }
        .totals-table { width: 300px; }
        .totals-table .row { 
            display: flex; justify-content: space-between; padding: 8px 0; 
            font-size: 15px; color: #64748b; 
        }
        .totals-table .row.grand { 
            border-top: 2px solid var(--border); 
            margin-top: 8px; padding-top: 16px; 
            font-size: 18px; font-weight: 800; color: var(--dark); 
        }
        .totals-table .row.danger { color: var(--danger); font-weight: 700; }
        .totals-table .row.success { color: var(--success); font-weight: 700; }
        
        /* Footer */
        .footer { 
            padding: 24px 40px; background: var(--light); border-top: 1px solid var(--border); 
            text-align: center; font-size: 13px; color: #64748b; 
        }
        .footer strong { color: var(--dark); }
        
        /* Print Styles */
        @media print {
            body { background: white; padding: 0; }
            .action-bar { display: none !important; }
            .invoice-container { 
                box-shadow: none; border-radius: 0; max-width: 100%; 
                border: none; margin: 0; 
            }
            .header { 
                -webkit-print-color-adjust: exact; print-color-adjust: exact; 
                padding: 20px; 
            }
            .status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .item-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <!-- Action Buttons (Hidden on Print) -->
    <div class="action-bar">
        <button onclick="window.close()" class="btn-close">
            <i class="fas fa-times"></i> Close
        </button>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print Invoice
        </button>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <?php if (!empty($hospital['hospital_logo'])): ?>
                    <img src="../<?php echo htmlspecialchars($hospital['hospital_logo']); ?>" alt="Logo" class="logo-img">
                <?php endif; ?>
                <div>
                    <h2 class="hospital-name"><?php echo htmlspecialchars($hospital['hospital_name'] ?? 'City Hospital'); ?></h2>
                    <p class="hospital-meta">
                        <?php echo htmlspecialchars($hospital['address'] ?? ''); ?><br>
                        Phone: <?php echo htmlspecialchars($hospital['phone'] ?? 'N/A'); ?> | 
                        Email: <?php echo htmlspecialchars($hospital['email'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
            <div class="header-right">
                <h1>INVOICE</h1>
                <p>#INV-<?php echo str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <!-- Body -->
        <div class="body-content">
            <div class="info-grid">
                <div class="info-card">
                    <h4>Billed To</h4>
                    <p><?php echo htmlspecialchars($bill['patient_name'] ?? 'Walk-in Patient'); ?></p>
                    <span><?php echo htmlspecialchars($bill['address'] ?? ''); ?></span>
                    <span>Phone: <?php echo htmlspecialchars($bill['mobile'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-card" style="text-align: right;">
                    <h4>Invoice Details</h4>
                    <p>Date: <?php echo date('d M Y', strtotime($bill['bill_date'])); ?></p>
                    <span>Payment Mode: <?php echo htmlspecialchars($bill['payment_mode'] ?? 'N/A'); ?></span>
                    <div style="margin-top: 8px;">
                        <span style="display: block; margin-bottom: 4px;">Status</span>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($bill['status']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="item-table">
                <thead>
                    <tr>
                        <th>Service / Item</th>
                        <th style="width: 80px; text-align: center;">Qty</th>
                        <th style="width: 120px; text-align: right;">Rate</th>
                        <th style="width: 140px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                <td style="text-align: center;"><?php echo (int)$item['qty']; ?></td>
                                <td style="text-align: right;">₹<?php echo number_format($item['rate'], 2); ?></td>
                                <td>₹<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 24px; color: #94a3b8;">
                                No itemized services recorded.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals-grid">
                <div class="totals-table">
                    <div class="row">
                        <span>Total Amount</span>
                        <span>₹<?php echo number_format($bill['total_amount'], 2); ?></span>
                    </div>
                    <div class="row success">
                        <span>Amount Paid</span>
                        <span>₹<?php echo number_format($bill['paid_amount'], 2); ?></span>
                    </div>
                    <?php if ($bill['balance_amount'] > 0): ?>
                    <div class="row danger">
                        <span>Balance Due</span>
                        <span>₹<?php echo number_format($bill['balance_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="row grand">
                        <span>Status</span>
                        <span><?php echo htmlspecialchars($bill['status']); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($bill['remark'])): ?>
            <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border-left: 4px solid var(--primary); border-radius: 8px;">
                <strong style="font-size: 13px; color: #64748b;">REMARKS:</strong>
                <p style="font-size: 14px; margin-top: 4px;"><?php echo htmlspecialchars($bill['remark']); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for choosing <?php echo htmlspecialchars($hospital['hospital_name'] ?? 'our hospital'); ?>!</strong></p>
            <p>This is a computer-generated invoice and does not require a physical signature.</p>
        </div>
    </div>

</body>
</html>