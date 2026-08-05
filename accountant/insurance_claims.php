<?php
session_start();
require_once '../config/hospital.php';
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$query = "SELECT * FROM insurance_claims WHERE hospital_id = $hospital_id AND delete_flag = 0 ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Insurance Claims</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
        .page-header h1 { font-size:24px; font-weight:700; display:flex; align-items:center; gap:10px; }
        .page-header h1 i { color:#ed8936; }
        .table-card { background:white; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; }
        .table-responsive { overflow-x:auto; padding:0 4px; }
        table { width:100%; border-collapse:collapse; font-size:13px; min-width:700px; }
        thead th { background:#f8fafc; color:#64748b; font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:0.03em; padding:12px 16px; text-align:left; border-bottom:2px solid #e2e8f0; }
        tbody td { padding:12px 16px; border-bottom:1px solid #f1f5f9; }
        .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-approved { background:#d1fae5; color:#065f46; }
        .badge-pending { background:#fef3c7; color:#92400e; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }
        .badge-partial { background:#fef3c7; color:#92400e; }
        .btn-sm { padding:4px 12px; border-radius:6px; border:none; font-size:12px; font-weight:600; cursor:pointer; background:#ed8936; color:white; text-decoration:none; display:inline-block; }
        .btn-sm:hover { background:#d97706; }
        .action-btn { padding:4px 8px; border-radius:6px; border:none; background:transparent; cursor:pointer; }
        .action-btn:hover { background:#eef2ff; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-file-signature"></i> Insurance Claims</h1>
                <a href="add_insurance_claim.php" class="btn-sm" style="padding:10px 20px;"><i class="fas fa-plus"></i> New Claim</a>
            </div>
            <div class="table-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Patient</th>
                                <th>Insurance</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['claim_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['insurance_company']); ?></td>
                                    <td>₹ <?php echo number_format($row['approved_amount'], 2); ?></td>
                                    <td>₹ <?php echo number_format($row['pending_amount'], 2); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="view_insurance_claim.php?id=<?php echo $row['claim_id']; ?>" class="action-btn"><i class="fas fa-eye"></i></a>
                                        <a href="edit_insurance_claim.php?id=<?php echo $row['claim_id']; ?>" class="action-btn"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center; padding:40px; color:#94a3b8;">No claims found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>