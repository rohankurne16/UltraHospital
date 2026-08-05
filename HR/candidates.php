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

// ========== GET JOB OPENINGS FOR DROPDOWN ==========
$job_openings = [];
$jo_sql = "SELECT id, title FROM job_openings WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Open' ORDER BY title";
$jo_res = $conn->query($jo_sql);
if ($jo_res) {
    while ($row = $jo_res->fetch_assoc()) {
        $job_openings[] = $row;
    }
}

// ========== HANDLE DELETE ==========
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $id = intval($_GET['delete']);
    $check = $conn->query("SELECT id FROM candidates WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE candidates SET delete_flag = 1 WHERE id = $id");
        header('Location: candidates.php?deleted=1');
        exit();
    }
}

// ========== HANDLE ADD/EDIT ==========
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM candidates WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_opening_id = intval($_POST['job_opening_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_company = trim($_POST['current_company'] ?? '');
    $current_designation = trim($_POST['current_designation'] ?? '');
    $total_experience = floatval($_POST['total_experience'] ?? 0);
    $source = trim($_POST['source'] ?? 'Direct');
    $status = $_POST['status'] ?? 'Applied';

    if (empty($first_name) || empty($last_name) || empty($email) || $job_opening_id <= 0) {
        $message = 'First Name, Last Name, Email, and Job Opening are required.';
        $message_type = 'error';
    } else {
        // Resume upload
        $resume_file = $edit_data['resume_file'] ?? '';
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx', 'txt'];
            $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $message = 'Invalid resume format. Allowed: PDF, DOC, DOCX, TXT.';
                $message_type = 'error';
            } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
                $message = 'Resume size too large (max 5MB).';
                $message_type = 'error';
            } else {
                $upload_dir = '../uploads/resumes/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $new_filename = 'resume_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $new_filename)) {
                    $resume_file = $new_filename;
                } else {
                    $message = 'Failed to upload resume.';
                    $message_type = 'error';
                }
            }
        }
        if (empty($message)) {
            if ($edit_id > 0) {
                $sql = "UPDATE candidates SET 
                        job_opening_id = $job_opening_id, first_name = '$first_name', last_name = '$last_name',
                        email = '$email', phone = '$phone', address = '$address', 
                        current_company = '$current_company', current_designation = '$current_designation',
                        total_experience = $total_experience, source = '$source', status = '$status'";
                if (!empty($resume_file)) {
                    $sql .= ", resume_file = '$resume_file'";
                }
                $sql .= " WHERE id = $edit_id AND hospital_id = $hospital_id";
                if ($conn->query($sql)) {
                    $message = 'Candidate updated successfully.';
                    $message_type = 'success';
                    $edit_data = null;
                    $res = $conn->query("SELECT * FROM candidates WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
                    if ($res && $res->num_rows > 0) $edit_data = $res->fetch_assoc();
                } else {
                    $message = 'Error: ' . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $sql = "INSERT INTO candidates (job_opening_id, first_name, last_name, email, phone, address, resume_file, 
                        current_company, current_designation, total_experience, source, status, hospital_id) 
                        VALUES ($job_opening_id, '$first_name', '$last_name', '$email', '$phone', '$address', '$resume_file', 
                        '$current_company', '$current_designation', $total_experience, '$source', '$status', $hospital_id)";
                if ($conn->query($sql)) {
                    $message = 'Candidate registered successfully.';
                    $message_type = 'success';
                    $_POST = [];
                } else {
                    $message = 'Error: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }
}

// ========== FETCH CANDIDATES ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$job_filter = isset($_GET['job']) ? intval($_GET['job']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["c.hospital_id = $hospital_id", "c.delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(c.first_name LIKE '%$search%' OR c.last_name LIKE '%$search%' OR c.email LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where[] = "c.status = '$status_filter'";
}
if ($job_filter > 0) {
    $where[] = "c.job_opening_id = $job_filter";
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
$candidates = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Candidates</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* (same styles as previous pages, omitted for brevity) */
        /* Copy from job_openings.php or include common style */
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
        .btn-danger { background: #ef4444; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; color: #6b7280; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group .required { color: #ef4444; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .search-box { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .search-box input, .search-box select { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; min-width: 160px; }
        .search-box input:focus, .search-box select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.Applied { background: #dbeafe; color: #1e40af; }
        .status-badge.Shortlisted { background: #fef3c7; color: #92400e; }
        .status-badge.Interviewed { background: #ede9fe; color: #6d28d9; }
        .status-badge.Selected { background: #dcfce7; color: #166534; }
        .status-badge.Rejected { background: #fecaca; color: #991b1b; }
        .status-badge.Joined { background: #d1fae5; color: #065f46; }
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
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 750px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; }
        .modal-close:hover { color: #1f2937; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        .file-upload-wrapper { position: relative; }
        .file-upload-wrapper input[type="file"] { padding: 6px; border: 1px dashed #d1d5db; border-radius: 8px; width: 100%; cursor: pointer; }
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
                        <h1><i class="fas fa-users mr-3 text-white"></i> Candidates</h1>
                        <p>Manage candidate profiles, resumes, and status</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-user-plus"></i> Register Candidate
                    </button>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Candidate List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> candidates</span>
                </div>
                <div class="card-body">
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Applied" <?php echo ($status_filter == 'Applied') ? 'selected' : ''; ?>>Applied</option>
                            <option value="Shortlisted" <?php echo ($status_filter == 'Shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                            <option value="Interviewed" <?php echo ($status_filter == 'Interviewed') ? 'selected' : ''; ?>>Interviewed</option>
                            <option value="Selected" <?php echo ($status_filter == 'Selected') ? 'selected' : ''; ?>>Selected</option>
                            <option value="Rejected" <?php echo ($status_filter == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Joined" <?php echo ($status_filter == 'Joined') ? 'selected' : ''; ?>>Joined</option>
                        </select>
                        <select name="job">
                            <option value="0">All Job Openings</option>
                            <?php foreach ($job_openings as $j): ?>
                                <option value="<?php echo $j['id']; ?>" <?php echo ($job_filter == $j['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($j['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="candidates.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <?php if (!empty($candidates)): ?>
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
                                        <th>Resume</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($candidates as $c): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                                            <td><?php echo htmlspecialchars($c['job_title'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($c['total_experience'], 1) . ' yrs'; ?></td>
                                            <td><span class="status-badge <?php echo $c['status']; ?>"><?php echo $c['status']; ?></span></td>
                                            <td>
                                                <?php if (!empty($c['resume_file'])): ?>
                                                    <a href="../uploads/resumes/<?php echo $c['resume_file']; ?>" target="_blank" class="btn-outline btn-xs"><i class="fas fa-file-pdf"></i> View</a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="editCandidate(<?php echo $c['id']; ?>)" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteCandidate(<?php echo $c['id']; ?>)" class="btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <!-- Quick actions: schedule interview, select, etc. -->
                                                    <a href="interview_schedule.php?candidate=<?php echo $c['id']; ?>" class="btn-success btn-xs" title="Schedule Interview">
                                                        <i class="fas fa-calendar-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&job=<?php echo $job_filter; ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&job=<?php echo $job_filter; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&job=<?php echo $job_filter; ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p class="text-lg font-medium text-gray-700">No candidates registered</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Register Candidate" to add a new candidate.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== ADD/EDIT MODAL ========== -->
<div class="modal" id="candidateModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-user-plus mr-2 text-blue-500"></i> Register Candidate</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="candidates.php" enctype="multipart/form-data" id="candidateForm">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            <div class="form-group">
                <label>Job Opening <span class="required">*</span></label>
                <select name="job_opening_id" id="job_opening_id" class="form-control" required>
                    <option value="">Select Job Opening</option>
                    <?php foreach ($job_openings as $j): ?>
                        <option value="<?php echo $j['id']; ?>"><?php echo htmlspecialchars($j['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" id="address" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Current Company</label>
                    <input type="text" name="current_company" id="current_company" class="form-control">
                </div>
                <div class="form-group">
                    <label>Current Designation</label>
                    <input type="text" name="current_designation" id="current_designation" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Total Experience (years)</label>
                    <input type="number" name="total_experience" id="total_experience" class="form-control" step="0.5" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Source</label>
                    <select name="source" id="source" class="form-control">
                        <option value="Direct">Direct</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="Naukri">Naukri</option>
                        <option value="Referral">Referral</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="Applied">Applied</option>
                    <option value="Shortlisted">Shortlisted</option>
                    <option value="Interviewed">Interviewed</option>
                    <option value="Selected">Selected</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Joined">Joined</option>
                </select>
            </div>
            <div class="form-group">
                <label>Resume (PDF, DOC, DOCX, TXT - Max 5MB)</label>
                <input type="file" name="resume" id="resume" class="form-control" accept=".pdf,.doc,.docx,.txt">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep existing resume on edit.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus mr-2 text-blue-500"></i> Register Candidate';
    document.getElementById('edit_id').value = 0;
    document.getElementById('candidateForm').reset();
    document.getElementById('status').value = 'Applied';
    document.getElementById('total_experience').value = 0;
    document.getElementById('candidateModal').classList.add('show');
}

function editCandidate(id) {
    fetch('candidates_ajax.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-500"></i> Edit Candidate';
                document.getElementById('edit_id').value = data.id;
                document.getElementById('job_opening_id').value = data.job_opening_id;
                document.getElementById('first_name').value = data.first_name;
                document.getElementById('last_name').value = data.last_name;
                document.getElementById('email').value = data.email;
                document.getElementById('phone').value = data.phone;
                document.getElementById('address').value = data.address;
                document.getElementById('current_company').value = data.current_company;
                document.getElementById('current_designation').value = data.current_designation;
                document.getElementById('total_experience').value = data.total_experience;
                document.getElementById('source').value = data.source;
                document.getElementById('status').value = data.status;
                document.getElementById('candidateModal').classList.add('show');
            } else {
                alert('Error loading candidate data.');
            }
        })
        .catch(() => alert('Network error.'));
}

function deleteCandidate(id) {
    if (confirm('Are you sure you want to delete this candidate?')) {
        window.location.href = 'candidates.php?delete=' + id;
    }
}

function closeModal() {
    document.getElementById('candidateModal').classList.remove('show');
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>