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

// ========== GET EMPLOYEE ID ==========
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($employee_id <= 0) {
    header('Location: employees.php');
    exit();
}

// ========== GET EMPLOYEE DETAILS ==========
$emp_sql = "SELECT employee_id, employee_code, first_name, last_name, profile_image 
            FROM employees 
            WHERE employee_id = $employee_id AND hospital_id = $hospital_id AND delete_flag = 0";
$emp_result = $conn->query($emp_sql);
if (!$emp_result || $emp_result->num_rows == 0) {
    header('Location: employees.php');
    exit();
}
$employee = $emp_result->fetch_assoc();

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

// ========== GET EMPLOYEE DOCUMENTS ==========
$documents = [];
$doc_sql = "SELECT * FROM employee_documents 
            WHERE employee_id = $employee_id AND delete_flag = 0 
            ORDER BY uploaded_at DESC";
$doc_result = $conn->query($doc_sql);
if ($doc_result) {
    while ($row = $doc_result->fetch_assoc()) {
        $documents[] = $row;
    }
}

// ========== PROCESS UPLOAD DOCUMENT ==========
$upload_error = '';
$upload_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $document_type = $_POST['document_type'] ?? '';
    $document_name = trim($_POST['document_name'] ?? '');
    
    if (empty($document_type) || empty($document_name)) {
        $upload_error = 'Please select document type and enter document name.';
    } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx'];
        $filename = $_FILES['document_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['document_file']['size'];
        $file_type = $_FILES['document_file']['type'];
        
        if (!in_array($ext, $allowed)) {
            $upload_error = 'Invalid file type. Allowed: ' . implode(', ', $allowed);
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB
            $upload_error = 'File size too large. Maximum 5MB allowed.';
        } else {
            $new_filename = 'doc_' . time() . '_' . $employee_id . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = '../uploads/employee_documents/';
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_path . $new_filename)) {
                $insert_sql = "INSERT INTO employee_documents (
                    employee_id, document_type, document_name, document_file, file_size, file_type, uploaded_by
                ) VALUES (
                    $employee_id, '$document_type', '$document_name', '$new_filename', $file_size, '$file_type', $user_id
                )";
                if ($conn->query($insert_sql)) {
                    $upload_success = 'Document uploaded successfully!';
                    // Refresh documents list
                    $doc_result = $conn->query($doc_sql);
                    $documents = [];
                    if ($doc_result) {
                        while ($row = $doc_result->fetch_assoc()) {
                            $documents[] = $row;
                        }
                    }
                } else {
                    $upload_error = 'Error saving document: ' . $conn->error;
                }
            } else {
                $upload_error = 'Error uploading file.';
            }
        }
    } else {
        $upload_error = 'Please select a file to upload.';
    }
}

// ========== PROCESS DELETE DOCUMENT ==========
if (isset($_GET['delete']) && isset($_GET['doc_id'])) {
    $doc_id = intval($_GET['doc_id']);
    $delete_sql = "UPDATE employee_documents SET delete_flag = 1 WHERE document_id = $doc_id AND employee_id = $employee_id";
    if ($conn->query($delete_sql)) {
        header('Location: employee_documents.php?id=' . $employee_id . '&deleted=1');
        exit();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Employee Documents</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        .form-group .required { color: #ef4444; }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        
        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e5e7eb;
        }
        .profile-avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            font-weight: 700;
            border: 3px solid #e5e7eb;
        }
        
        .doc-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            transition: all 0.2s;
            background: white;
        }
        .doc-card:hover {
            border-color: #7c3aed;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .doc-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h2 { font-size: 20px; font-weight: 600; color: #0f172a; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
        }
        .modal-close:hover { color: #1f2937; }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        
        .grid-3col {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 1024px) { .grid-3col { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .grid-3col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ========== INCLUDE HR SIDEBAR ========== -->
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
                        <h1><i class="fas fa-file-alt mr-3 text-white"></i> Employee Documents</h1>
                        <p>Manage documents for <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?> (<?php echo htmlspecialchars($employee['employee_code']); ?>)</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openUploadModal()" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                        <a href="view_employee.php?id=<?php echo $employee_id; ?>" class="btn-primary" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Employee Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($employee['profile_image']) && file_exists($employee['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" alt="Profile" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder">
                                <?php echo strtoupper(substr($employee['first_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </h4>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-id-card mr-1"></i> <?php echo htmlspecialchars($employee['employee_code']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-file-alt mr-1"></i> <?php echo count($documents); ?> document(s)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $upload_error; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($upload_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $upload_success; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Document deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Documents List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list mr-2 text-blue-500"></i> All Documents</h3>
                    <span class="badge-count"><?php echo count($documents); ?> documents</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($documents)): ?>
                        <div class="grid-3col">
                            <?php foreach ($documents as $doc): ?>
                                <?php 
                                $icon = getDocumentIcon($doc['document_type']);
                                $color = getDocumentColor($doc['document_type']);
                                $file_path = '../uploads/employee_documents/' . $doc['document_file'];
                                ?>
                                <div class="doc-card">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="doc-icon <?php echo $color; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-800 text-sm">
                                                    <?php echo htmlspecialchars($doc['document_name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($doc['document_type']); ?>
                                                </div>
                                            </div>
                                        </div>
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
                                    </div>
                                    <div class="mt-2 text-xs text-gray-400">
                                        <i class="fas fa-file mr-1"></i> <?php echo formatFileSize($doc['file_size']); ?>
                                        <span class="mx-1">•</span>
                                        <i class="fas fa-calendar mr-1"></i> <?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p class="text-lg font-medium text-gray-700">No documents uploaded</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Upload Document" to add documents for this employee</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== UPLOAD DOCUMENT MODAL ========== -->
<div class="modal" id="uploadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-upload mr-2 text-blue-500"></i> Upload Document</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" action="employee_documents.php?id=<?php echo $employee_id; ?>">
            <input type="hidden" name="action" value="upload">
            
            <div class="form-group">
                <label>Document Type <span class="required">*</span></label>
                <select class="form-control" name="document_type" required>
                    <option value="">Select Document Type</option>
                    <option value="Aadhaar Card">Aadhaar Card</option>
                    <option value="PAN Card">PAN Card</option>
                    <option value="Resume">Resume</option>
                    <option value="Certificate">Certificate</option>
                    <option value="Experience Letter">Experience Letter</option>
                    <option value="Offer Letter">Offer Letter</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Document Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="document_name" placeholder="e.g. Aadhaar Card - John Doe" required>
            </div>
            
            <div class="form-group">
                <label>Select File <span class="required">*</span></label>
                <input type="file" class="form-control" name="document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx" required>
                <p class="text-xs text-gray-500 mt-1">Allowed: PDF, DOC, DOCX, JPG, PNG, GIF, XLS, XLSX (Max 5MB)</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ========== OPEN UPLOAD MODAL ==========
function openUploadModal() {
    document.getElementById('uploadModal').classList.add('show');
}

// ========== CLOSE MODAL ==========
function closeModal() {
    document.getElementById('uploadModal').classList.remove('show');
}

// ========== DELETE DOCUMENT ==========
function deleteDocument(docId) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }
    window.location.href = 'employee_documents.php?id=<?php echo $employee_id; ?>&delete=1&doc_id=' + docId;
}

// ========== CLOSE MODAL ON ESC ==========
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('uploadModal').classList.remove('show');
    }
});

// ========== CLOSE MODAL ON OUTSIDE CLICK ==========
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>