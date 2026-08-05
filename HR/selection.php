<?php
session_start();
include '../config/hospital.php';

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

// ========== FETCH SELECTED CANDIDATES (status = 'Selected') ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["c.hospital_id = $hospital_id", "c.delete_flag = 0", "c.status = 'Selected'"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(c.first_name LIKE '%$search%' OR c.last_name LIKE '%$search%' OR c.email LIKE '%$search%')";
}
$where_clause = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) as total FROM candidates c WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT c.*, j.title as job_title 
        FROM candidates c
        LEFT JOIN job_openings j ON c.job_opening_id = j.id
        WHERE $where_clause
        ORDER BY c.created_at DESC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$selected = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $selected[] = $row;
    }
}

// ========== PROCESS: Send to Offer ==========
if (isset($_GET['send_offer']) && intval($_GET['send_offer']) > 0) {
    $cand_id = intval($_GET['send_offer']);
    // Check if candidate already has an offer
    $check = $conn->query("SELECT id FROM offer_letters WHERE candidate_id = $cand_id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($check && $check->num_rows > 0) {
        $message = 'Offer already generated for this candidate.';
        $message_type = 'warning';
    } else {
        // Create offer draft
        $cand_sql = "SELECT * FROM candidates WHERE id = $cand_id AND hospital_id = $hospital_id";
        $cand_res = $conn->query($cand_sql);
        if ($cand_res && $cand_res->num_rows > 0) {
            $cand = $cand_res->fetch_assoc();
            $insert = "INSERT INTO offer_letters (candidate_id, job_opening_id, offer_date, joining_date, salary_offered, hospital_id) 
                       VALUES ($cand_id, {$cand['job_opening_id']}, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 0, $hospital_id)";
            if ($conn->query($insert)) {
                header('Location: offer_letter.php?candidate=' . $cand_id . '&generated=1');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Candidate Selection</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same base styles as previous, include status-badge for Selected */
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 0; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .btn-primary { background: #3b82f6; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-success:hover { background: #16a34a; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .search-box { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .search-box input { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; min-width: 200px; }
        .search-box input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.Selected { background: #dcfce7; color: #166534; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #4b5563; font-size: 13px; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active { background: #3b82f6; color: white; border-color: #3b82f6; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }
        .welcome-section { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
    </style>
</head>
<body>

<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <?php include '../header.php'; ?>
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-check-circle mr-3 text-white"></i> Candidate Selection</h1>
                        <p>View selected candidates and move them to offer stage</p>
                    </div>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Selected Candidates</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> selected</span>
                </div>
                <div class="card-body">
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="selection.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <?php if (!empty($selected)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Job</th>
                                        <th>Experience</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($selected as $c): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                                            <td><?php echo htmlspecialchars($c['job_title'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($c['total_experience'], 1) . ' yrs'; ?></td>
                                            <td><span class="status-badge Selected">Selected</span></td>
                                            <td>
                                                <a href="selection.php?send_offer=<?php echo $c['id']; ?>" class="btn-success btn-sm" title="Generate Offer">
                                                    <i class="fas fa-file-signature"></i> Offer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p class="text-lg font-medium text-gray-700">No selected candidates yet</p>
                            <p class="text-sm text-gray-400 mt-1">Candidates become selected after interview feedback with decision 'Select'.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>