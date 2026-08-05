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

// ========== GET CANDIDATES & JOB OPENINGS FOR DROPDOWNS ==========
$candidates = [];
$cand_sql = "SELECT id, first_name, last_name FROM candidates WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status != 'Rejected' ORDER BY first_name";
$cand_res = $conn->query($cand_sql);
if ($cand_res) {
    while ($row = $cand_res->fetch_assoc()) {
        $candidates[] = $row;
    }
}
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
    $check = $conn->query("SELECT id FROM interview_schedule WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE interview_schedule SET delete_flag = 1 WHERE id = $id");
        header('Location: interview_schedule.php?deleted=1');
        exit();
    }
}

// ========== HANDLE ADD/EDIT ==========
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM interview_schedule WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $job_opening_id = intval($_POST['job_opening_id'] ?? 0);
    $interview_date = $_POST['interview_date'] ?? '';
    $interview_time = $_POST['interview_time'] ?? '';
    $mode = $_POST['mode'] ?? 'In-person';
    $interviewer_name = trim($_POST['interviewer_name'] ?? '');
    $status = $_POST['status'] ?? 'Scheduled';

    if ($candidate_id <= 0 || $job_opening_id <= 0 || empty($interview_date) || empty($interview_time)) {
        $message = 'Candidate, Job, Date, and Time are required.';
        $message_type = 'error';
    } else {
        if ($edit_id > 0) {
            $sql = "UPDATE interview_schedule SET 
                    candidate_id = $candidate_id, job_opening_id = $job_opening_id,
                    interview_date = '$interview_date', interview_time = '$interview_time',
                    mode = '$mode', interviewer_name = '$interviewer_name', status = '$status'
                    WHERE id = $edit_id AND hospital_id = $hospital_id";
            if ($conn->query($sql)) {
                $message = 'Interview schedule updated.';
                $message_type = 'success';
                $edit_data = null;
                $res = $conn->query("SELECT * FROM interview_schedule WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
                if ($res && $res->num_rows > 0) $edit_data = $res->fetch_assoc();
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        } else {
            $sql = "INSERT INTO interview_schedule (candidate_id, job_opening_id, interview_date, interview_time, mode, interviewer_name, status, hospital_id) 
                    VALUES ($candidate_id, $job_opening_id, '$interview_date', '$interview_time', '$mode', '$interviewer_name', '$status', $hospital_id)";
            if ($conn->query($sql)) {
                $message = 'Interview scheduled successfully.';
                $message_type = 'success';
                $_POST = [];
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// ========== FETCH SCHEDULES ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["s.hospital_id = $hospital_id", "s.delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(c.first_name LIKE '%$search%' OR c.last_name LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where[] = "s.status = '$status_filter'";
}
$where_clause = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) as total FROM interview_schedule s JOIN candidates c ON s.candidate_id = c.id WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT s.*, c.first_name, c.last_name, c.email, j.title as job_title 
        FROM interview_schedule s
        JOIN candidates c ON s.candidate_id = c.id
        LEFT JOIN job_openings j ON s.job_opening_id = j.id
        WHERE $where_clause
        ORDER BY s.interview_date DESC, s.interview_time DESC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$schedules = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Interview Schedule</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Include same base styles as previous pages */
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
        .status-badge.Scheduled { background: #dbeafe; color: #1e40af; }
        .status-badge.Completed { background: #dcfce7; color: #166534; }
        .status-badge.Cancelled { background: #fecaca; color: #991b1b; }
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
        .modal-content { background: white; border-radius: 12px; max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; }
        .modal-close:hover { color: #1f2937; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
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
                        <h1><i class="fas fa-calendar-plus mr-3 text-white"></i> Interview Schedule</h1>
                        <p>Schedule and manage interviews for candidates</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-plus"></i> Schedule Interview
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
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Interview Schedule List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> interviews</span>
                </div>
                <div class="card-body">
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search candidate..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Scheduled" <?php echo ($status_filter == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="Completed" <?php echo ($status_filter == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($status_filter == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="interview_schedule.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <?php if (!empty($schedules)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Candidate</th>
                                        <th>Job</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Mode</th>
                                        <th>Interviewer</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($schedules as $s): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($s['job_title'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($s['interview_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($s['interview_time'])); ?></td>
                                            <td><?php echo $s['mode']; ?></td>
                                            <td><?php echo htmlspecialchars($s['interviewer_name']); ?></td>
                                            <td><span class="status-badge <?php echo $s['status']; ?>"><?php echo $s['status']; ?></span></td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="editSchedule(<?php echo $s['id']; ?>)" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteSchedule(<?php echo $s['id']; ?>)" class="btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php if ($s['status'] == 'Scheduled'): ?>
                                                        <a href="interview_feedback.php?interview=<?php echo $s['id']; ?>&candidate=<?php echo $s['candidate_id']; ?>" class="btn-success btn-xs" title="Add Feedback">
                                                            <i class="fas fa-star"></i>
                                                        </a>
                                                    <?php endif; ?>
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
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p class="text-lg font-medium text-gray-700">No interviews scheduled</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Schedule Interview" to add a new one.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== ADD/EDIT MODAL ========== -->
<div class="modal" id="scheduleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-calendar-plus mr-2 text-blue-500"></i> Schedule Interview</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="interview_schedule.php" id="scheduleForm">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            <div class="form-group">
                <label>Candidate <span class="required">*</span></label>
                <select name="candidate_id" id="candidate_id" class="form-control" required>
                    <option value="">Select Candidate</option>
                    <?php foreach ($candidates as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Job Opening <span class="required">*</span></label>
                <select name="job_opening_id" id="job_opening_id" class="form-control" required>
                    <option value="">Select Job</option>
                    <?php foreach ($job_openings as $j): ?>
                        <option value="<?php echo $j['id']; ?>"><?php echo htmlspecialchars($j['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="interview_date" id="interview_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Time <span class="required">*</span></label>
                    <input type="time" name="interview_time" id="interview_time" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Mode</label>
                    <select name="mode" id="mode" class="form-control">
                        <option value="In-person">In-person</option>
                        <option value="Online">Online</option>
                        <option value="Phone">Phone</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Interviewer Name</label>
                    <input type="text" name="interviewer_name" id="interviewer_name" class="form-control" placeholder="e.g. John Doe">
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="Scheduled">Scheduled</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
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
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-calendar-plus mr-2 text-blue-500"></i> Schedule Interview';
    document.getElementById('edit_id').value = 0;
    document.getElementById('scheduleForm').reset();
    document.getElementById('status').value = 'Scheduled';
    document.getElementById('scheduleModal').classList.add('show');
}

function editSchedule(id) {
    fetch('interview_schedule_ajax.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-500"></i> Edit Schedule';
                document.getElementById('edit_id').value = data.id;
                document.getElementById('candidate_id').value = data.candidate_id;
                document.getElementById('job_opening_id').value = data.job_opening_id;
                document.getElementById('interview_date').value = data.interview_date;
                document.getElementById('interview_time').value = data.interview_time;
                document.getElementById('mode').value = data.mode;
                document.getElementById('interviewer_name').value = data.interviewer_name;
                document.getElementById('status').value = data.status;
                document.getElementById('scheduleModal').classList.add('show');
            } else {
                alert('Error loading schedule data.');
            }
        })
        .catch(() => alert('Network error.'));
}

function deleteSchedule(id) {
    if (confirm('Are you sure you want to delete this interview schedule?')) {
        window.location.href = 'interview_schedule.php?delete=' + id;
    }
}

function closeModal() {
    document.getElementById('scheduleModal').classList.remove('show');
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>