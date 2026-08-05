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

// ========== GET HOSPITAL DATA ==========
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

// ========== DEPARTMENTS LIST ==========
$departments = [];
$dept_sql = "SELECT id, department_name FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY department_name";
$dept_res = $conn->query($dept_sql);
if ($dept_res) {
    while ($row = $dept_res->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ========== HANDLE DELETE ==========
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $id = intval($_GET['delete']);
    $check = $conn->query("SELECT id FROM job_openings WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE job_openings SET delete_flag = 1 WHERE id = $id");
        header('Location: job_openings.php?deleted=1');
        exit();
    }
}

// ========== HANDLE ADD/EDIT ==========
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM job_openings WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $vacancies = intval($_POST['vacancies'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $status = $_POST['status'] ?? 'Open';
    $closing_date = $_POST['closing_date'] ?? '';

    if (empty($title) || $department_id <= 0 || empty($position)) {
        $message = 'Title, Department, and Position are required.';
        $message_type = 'error';
    } else {
        if ($edit_id > 0) {
            $sql = "UPDATE job_openings SET 
                    title = '$title', department_id = $department_id, position = '$position',
                    vacancies = $vacancies, description = '$description', requirements = '$requirements',
                    status = '$status', closing_date = " . ($closing_date ? "'$closing_date'" : "NULL") . "
                    WHERE id = $edit_id AND hospital_id = $hospital_id";
            if ($conn->query($sql)) {
                $message = 'Job opening updated successfully.';
                $message_type = 'success';
                $edit_data = null;
                $res = $conn->query("SELECT * FROM job_openings WHERE id = $edit_id AND hospital_id = $hospital_id AND delete_flag = 0");
                if ($res && $res->num_rows > 0) $edit_data = $res->fetch_assoc();
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        } else {
            $sql = "INSERT INTO job_openings (title, department_id, position, vacancies, description, requirements, status, closing_date, hospital_id) 
                    VALUES ('$title', $department_id, '$position', $vacancies, '$description', '$requirements', '$status', " . ($closing_date ? "'$closing_date'" : "NULL") . ", $hospital_id)";
            if ($conn->query($sql)) {
                $message = 'Job opening added successfully.';
                $message_type = 'success';
                $_POST = [];
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// ========== FETCH OPENINGS ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$dept_filter = isset($_GET['department']) ? intval($_GET['department']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["j.hospital_id = $hospital_id", "j.delete_flag = 0"];
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(j.title LIKE '%$search%' OR j.position LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where[] = "j.status = '$status_filter'";
}
if ($dept_filter > 0) {
    $where[] = "j.department_id = $dept_filter";
}
$where_clause = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) as total FROM job_openings j WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT j.*, d.department_name 
        FROM job_openings j
        LEFT JOIN department d ON j.department_id = d.id
        WHERE $where_clause
        ORDER BY j.posted_date DESC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$openings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $openings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Job Openings</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .status-badge.Open { background: #dcfce7; color: #166534; }
        .status-badge.Closed { background: #f1f5f9; color: #64748b; }
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
        .modal-content { background: white; border-radius: 12px; max-width: 700px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 24px; animation: slideDown 0.3s ease; }
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
                        <h1><i class="fas fa-bullhorn mr-3 text-white"></i> Job Openings</h1>
                        <p>Create and manage job openings for recruitment</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-plus"></i> Add Opening
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
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Openings List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> openings</span>
                </div>
                <div class="card-body">
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search by title or position..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Open" <?php echo ($status_filter == 'Open') ? 'selected' : ''; ?>>Open</option>
                            <option value="Closed" <?php echo ($status_filter == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <select name="department">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($dept_filter == $d['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                        <a href="job_openings.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                    </form>

                    <?php if (!empty($openings)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th>Position</th>
                                        <th>Vacancies</th>
                                        <th>Posted</th>
                                        <th>Closing</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($openings as $o): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($o['title']); ?></td>
                                            <td><?php echo htmlspecialchars($o['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($o['position']); ?></td>
                                            <td><?php echo $o['vacancies']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($o['posted_date'])); ?></td>
                                            <td><?php echo $o['closing_date'] ? date('d M Y', strtotime($o['closing_date'])) : '—'; ?></td>
                                            <td><span class="status-badge <?php echo $o['status']; ?>"><?php echo $o['status']; ?></span></td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="editOpening(<?php echo $o['id']; ?>)" class="btn-primary btn-xs" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteOpening(<?php echo $o['id']; ?>)" class="btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo $dept_filter; ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo $dept_filter; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo $dept_filter; ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bullhorn"></i>
                            <p class="text-lg font-medium text-gray-700">No job openings found</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Opening" to create your first job posting.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== ADD/EDIT MODAL ========== -->
<div class="modal" id="jobModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle mr-2 text-blue-500"></i> Add Job Opening</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="job_openings.php" id="jobForm">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            <div class="form-group">
                <label>Job Title <span class="required">*</span></label>
                <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Senior Software Engineer" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Department <span class="required">*</span></label>
                    <select name="department_id" id="department_id" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Position <span class="required">*</span></label>
                    <input type="text" name="position" id="position" class="form-control" placeholder="e.g. Senior Developer" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Vacancies <span class="required">*</span></label>
                    <input type="number" name="vacancies" id="vacancies" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Closing Date</label>
                <input type="date" name="closing_date" id="closing_date" class="form-control">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Job description"></textarea>
            </div>
            <div class="form-group">
                <label>Requirements</label>
                <textarea name="requirements" id="requirements" class="form-control" rows="3" placeholder="Skills, qualifications, experience..."></textarea>
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
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle mr-2 text-blue-500"></i> Add Job Opening';
    document.getElementById('edit_id').value = 0;
    document.getElementById('jobForm').reset();
    document.getElementById('status').value = 'Open';
    document.getElementById('vacancies').value = 1;
    document.getElementById('jobModal').classList.add('show');
}

function editOpening(id) {
    fetch('job_openings_ajax.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-500"></i> Edit Job Opening';
                document.getElementById('edit_id').value = data.id;
                document.getElementById('title').value = data.title;
                document.getElementById('department_id').value = data.department_id;
                document.getElementById('position').value = data.position;
                document.getElementById('vacancies').value = data.vacancies;
                document.getElementById('status').value = data.status;
                document.getElementById('closing_date').value = data.closing_date;
                document.getElementById('description').value = data.description;
                document.getElementById('requirements').value = data.requirements;
                document.getElementById('jobModal').classList.add('show');
            } else {
                alert('Error loading job opening data.');
            }
        })
        .catch(() => alert('Network error.'));
}

function deleteOpening(id) {
    if (confirm('Are you sure you want to delete this job opening?')) {
        window.location.href = 'job_openings.php?delete=' + id;
    }
}

function closeModal() {
    document.getElementById('jobModal').classList.remove('show');
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>