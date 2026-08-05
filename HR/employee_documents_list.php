<?php
session_start();
include '../config/hospital.php';

// ========== CHECK SESSION & HR ACCESS ==========
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
$admin_name = $_SESSION['full_name'] ?? 'HR';

// ========== PAGE VARIABLES ==========
$page_title = "All Employee Documents";

// ========== FILTERS ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? intval($_GET['department']) : 0;
$doc_type_filter = isset($_GET['doc_type']) ? trim($_GET['doc_type']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// ========== BUILD WHERE CLAUSE ==========
$where_conditions = ["ed.delete_flag = 0", "e.hospital_id = $hospital_id"];

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.employee_code LIKE '%$search%' OR ed.document_name LIKE '%$search%')";
}
if ($department_filter > 0) {
    $where_conditions[] = "e.department_id = $department_filter";
}
if (!empty($doc_type_filter)) {
    $where_conditions[] = "ed.document_type = '$doc_type_filter'";
}
if (!empty($date_from)) {
    $where_conditions[] = "DATE(ed.uploaded_at) >= '$date_from'";
}
if (!empty($date_to)) {
    $where_conditions[] = "DATE(ed.uploaded_at) <= '$date_to'";
}

$where_clause = implode(" AND ", $where_conditions);

// ========== COUNT TOTAL DOCUMENTS ==========
$count_sql = "SELECT COUNT(*) as total 
              FROM employee_documents ed
              JOIN employees e ON ed.employee_id = e.employee_id
              WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

// ========== FETCH DOCUMENTS WITH PAGINATION ==========
$sql = "SELECT ed.*, 
               e.employee_id, e.employee_code, e.first_name, e.last_name, e.profile_image,
               d.department_name
        FROM employee_documents ed
        JOIN employees e ON ed.employee_id = e.employee_id
        LEFT JOIN department d ON e.department_id = d.id
        WHERE $where_clause
        ORDER BY ed.uploaded_at DESC
        LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
$documents = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
}

// ========== GET DEPARTMENTS FOR FILTER ==========
$departments = [];
$dept_sql = "SELECT id, department_name FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0 AND status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_sql);
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ========== GET UNIQUE DOCUMENT TYPES ==========
$doc_types = [];
$type_sql = "SELECT DISTINCT document_type FROM employee_documents WHERE delete_flag = 0 ORDER BY document_type";
$type_result = $conn->query($type_sql);
if ($type_result) {
    while ($row = $type_result->fetch_assoc()) {
        $doc_types[] = $row['document_type'];
    }
}

// ========== HELPER FUNCTIONS ==========
function getDocumentIcon($type) {
    $icons = [
        'Aadhaar Card' => 'fa-id-card',
        'PAN Card' => 'fa-credit-card',
        'Resume' => 'fa-file-pdf',
        'Certificate' => 'fa-certificate',
        'Experience Letter' => 'fa-file-alt',
        'Offer Letter' => 'fa-file-signature',
        'Other' => 'fa-file'
    ];
    return $icons[$type] ?? 'fa-file';
}

function getDocumentColor($type) {
    $colors = [
        'Aadhaar Card' => 'blue',
        'PAN Card' => 'orange',
        'Resume' => 'green',
        'Certificate' => 'purple',
        'Experience Letter' => 'indigo',
        'Offer Letter' => 'pink',
        'Other' => 'gray'
    ];
    return $colors[$type] ?? 'gray';
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getAvatar($employee) {
    if (!empty($employee['profile_image']) && file_exists($employee['profile_image'])) {
        return '<img src="' . htmlspecialchars($employee['profile_image']) . '" alt="Profile" class="avatar-img">';
    }
    $name = ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '');
    $initial = strtoupper(substr(trim($name), 0, 1) ?: '?');
    return '<div class="avatar-initial">' . $initial . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($hospital_name); ?> - All Employee Documents</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content {
            width: 100%;
            margin-left: 0;
            padding: 20px 28px;
            min-height: 100vh;
        }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: #2563eb; }
        .btn-success {
            background: #22c55e;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-success:hover { background: #16a34a; }
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline {
            background: transparent;
            color: #6b7280;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-xs { padding: 2px 8px; font-size: 10px; }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            align-items: end;
        }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .welcome-section {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th {
            padding: 10px 16px;
            text-align: left;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        td {
            padding: 10px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            vertical-align: middle;
        }
        tr:hover { background: #f8fafc; }
        .avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-initial {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .name-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .name-cell .name-text { font-weight: 600; color: #0f172a; }
        .name-cell .emp-code { font-size: 11px; color: #94a3b8; }
        .doc-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .doc-icon.blue { background: #dbeafe; color: #2563eb; }
        .doc-icon.green { background: #dcfce7; color: #16a34a; }
        .doc-icon.purple { background: #ede9fe; color: #7c3aed; }
        .doc-icon.orange { background: #fef3c7; color: #d97706; }
        .doc-icon.pink { background: #fce7f3; color: #db2777; }
        .doc-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .doc-icon.gray { background: #f1f5f9; color: #64748b; }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 12px;
        }
        .badge-count {
            background: #e5e7eb;
            color: #4b5563;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #4b5563;
            font-size: 13px;
        }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 13px; margin-top: 2px; }
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ========== INCLUDE SIDEBAR ========== -->
<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <!-- ========== INCLUDE HEADER ========== -->
    <?php include '../header.php'; ?>
    
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-file-alt mr-3 text-white"></i> All Employee Documents</h1>
                        <p>Central repository – view, search, and manage every uploaded document</p>
                    </div>
                    <a href="employees.php" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left"></i> Back to Employees
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <?php
            $stats_sql = "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT ed.employee_id) as emp_count,
                COUNT(DISTINCT ed.document_type) as type_count
                FROM employee_documents ed
                JOIN employees e ON ed.employee_id = e.employee_id
                WHERE ed.delete_flag = 0 AND e.hospital_id = $hospital_id";
            $stats_result = $conn->query($stats_sql);
            $stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'emp_count' => 0, 'type_count' => 0];
            ?>
            <div class="stat-grid">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-number text-blue-600"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Documents</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-number text-green-600"><?php echo number_format($stats['emp_count']); ?></div>
                    <div class="stat-label">Employees with Docs</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                    <div class="stat-number text-purple-600"><?php echo number_format($stats['type_count']); ?></div>
                    <div class="stat-label">Document Types</div>
                </div>
            </div>

            <!-- Documents List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> Document List</h3>
                    <span class="badge-count"><?php echo $total_rows; ?> documents</span>
                </div>
                <div class="card-body">
                    <!-- Search & Filter -->
                    <form method="GET" class="mb-4">
                        <div class="form-row">
                            <div>
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Employee name, code, doc name..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div>
                                <label>Department</label>
                                <select name="department" class="form-control">
                                    <option value="0">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo ($department_filter == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Document Type</label>
                                <select name="doc_type" class="form-control">
                                    <option value="">All Types</option>
                                    <?php foreach ($doc_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($doc_type_filter == $type) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div>
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="flex gap-2 items-end" style="padding-bottom: 1px;">
                                <button type="submit" class="btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
                                <a href="employee_documents_list.php" class="btn-outline btn-sm"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </div>
                    </form>

                    <!-- Table -->
                    <?php if (!empty($documents)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Department</th>
                                        <th>Uploaded</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = $offset + 1; ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <?php 
                                        $icon = getDocumentIcon($doc['document_type']);
                                        $color = getDocumentColor($doc['document_type']);
                                        $file_path = '../uploads/employee_documents/' . $doc['document_file'];
                                        ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td>
                                                <div class="name-cell">
                                                    <?php echo getAvatar($doc); ?>
                                                    <div>
                                                        <div class="name-text">
                                                            <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                                        </div>
                                                        <div class="emp-code"><?php echo htmlspecialchars($doc['employee_code']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div class="doc-icon <?php echo $color; ?>">
                                                        <i class="fas <?php echo $icon; ?>"></i>
                                                    </div>
                                                    <span class="text-sm font-medium"><?php echo htmlspecialchars($doc['document_name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <?php if (file_exists($file_path)): ?>
                                                        <a href="<?php echo $file_path; ?>" target="_blank" class="btn-outline btn-xs" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo $file_path; ?>" download class="btn-success btn-xs" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button onclick="deleteDocument(<?php echo $doc['document_id']; ?>)" class="btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&doc_type=<?php echo urlencode($doc_type_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&doc_type=<?php echo urlencode($doc_type_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&doc_type=<?php echo urlencode($doc_type_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p class="text-lg font-medium text-gray-700">No documents found</p>
                            <p class="text-sm text-gray-400 mt-1">Try adjusting your filters or upload documents via the employee profile.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== DELETE DOCUMENT SCRIPT ========== -->
<script>
function deleteDocument(docId) {
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        return;
    }
    // Redirect to a delete handler – adjust if you have a separate delete script
    // For simplicity, we use a GET parameter on the same page.
    window.location.href = 'employee_documents_list.php?delete=1&doc_id=' + docId;
}
</script>

<?php
// ========== HANDLE DELETE REQUEST ==========
if (isset($_GET['delete']) && isset($_GET['doc_id'])) {
    $doc_id = intval($_GET['doc_id']);
    // Verify that the document belongs to this hospital (safety)
    $check_sql = "SELECT ed.document_id, ed.employee_id 
                  FROM employee_documents ed
                  JOIN employees e ON ed.employee_id = e.employee_id
                  WHERE ed.document_id = $doc_id AND e.hospital_id = $hospital_id AND ed.delete_flag = 0";
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        $update_sql = "UPDATE employee_documents SET delete_flag = 1 WHERE document_id = $doc_id";
        if ($conn->query($update_sql)) {
            // Redirect to refresh without delete params
            header('Location: employee_documents_list.php?deleted=1');
            exit();
        }
    }
}
?>
</body>
</html>