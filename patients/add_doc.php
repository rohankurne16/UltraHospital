<?php
session_start();
include "../config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("Location:../index.php");
    exit();
}

$patient_id = $_SESSION["patient_id"];
$hid = $_SESSION["hospital_id"];
$user_id = $_SESSION["id"];

// Handle file upload
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_document'])) {
    // Validate inputs
    $document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
    $document_type = mysqli_real_escape_string($conn, $_POST['document_type']);
    $document_category = mysqli_real_escape_string($conn, $_POST['document_category']);
    $document_sub_category = mysqli_real_escape_string($conn, $_POST['document_sub_category']);
    $document_date = mysqli_real_escape_string($conn, $_POST['document_date']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $document_tags = mysqli_real_escape_string($conn, $_POST['document_tags']);
    
    // Handle file upload
    $upload_file = '';
    $file_size = 0;
    
    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == 0) {
        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt'];
        $file_extension = strtolower(pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_extensions)) {
            $max_file_size = 10 * 1024 * 1024; // 10MB
            if ($_FILES['upload_file']['size'] <= $max_file_size) {
                $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['upload_file']['name']);

                // Physical folder on server
                $upload_dir = "../upload/documents/";

                // Path stored in database
                $upload_file = "upload/documents/" . $file_name;

                // Physical path where file will be moved
                $upload_path = $upload_dir . $file_name;

                // Create folder if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path)) {
                    $file_size = $_FILES['upload_file']['size'];
                } else {
                    $message = "Failed to upload file. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "File size exceeds 10MB limit.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF, WEBP, TXT";
            $message_type = "error";
        }
    } else {
        $message = "Please select a file to upload.";
        $message_type = "error";
    }
    
    // Insert into database if file uploaded successfully
    if ($upload_file) {
        $insert_query = "INSERT INTO patient_documents (
            patient_id, 
            document_name, 
            document_type, 
            document_category, 
            document_sub_category, 
            upload_file, 
            file_size, 
            uploaded_by, 
            note, 
            document_tags, 
            document_date,
            created_at
        ) VALUES (
            '$patient_id',
            '$document_name',
            '$document_type',
            '$document_category',
            '$document_sub_category',
            '$upload_file',
            '$file_size',
            '$user_id',
            '$note',
            '$document_tags',
            '$document_date',
            NOW()
        )";
        
        if ($conn->query($insert_query)) {
            $message = "Document uploaded successfully!";
            $message_type = "success";
            
            // Clear form data after success
            $document_name = $document_type = $document_category = $document_sub_category = '';
            $document_date = date('Y-m-d');
            $note = $document_tags = '';
        } else {
            $message = "Database error: " . $conn->error;
            $message_type = "error";
            // Delete uploaded file if database insert fails
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
        }
    }
}

// Get document types and categories for dropdowns
$doc_types = [
    'Report' => 'Report',
    'Prescription' => 'Prescription',
    'Lab Result' => 'Lab Result',
    'Medical Certificate' => 'Medical Certificate',
    'Insurance' => 'Insurance',
    'ID Proof' => 'ID Proof',
    'Other' => 'Other'
];

$doc_categories = [
    'Pre-Operation' => 'Pre-Operation',
    'Post-Operation' => 'Post-Operation',
   
    'Operation-Theatre' => 'Operation-Theatre'
];

$doc_sub_categories = [
    'OT' => 'OT',
    'Lab' => 'Lab',
    'Radiology' => 'Radiology',
    'Pharmacy' => 'Pharmacy',
    'General' => 'General'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f0f4f8;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
        
        .upload-zone {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fafbfc;
            position: relative;
        }
        .upload-zone:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
        .upload-zone.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .upload-zone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 0.75rem;
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
            display: block;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: #fafbfc;
            color: #1f2937;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-input.error, .form-select.error, .form-textarea.error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            justify-content: center;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-primary:active {
            transform: scale(0.98);
        }
        .btn-secondary {
            background: white;
            color: #1f2937;
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            border: 1.5px solid #e5e7eb;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            justify-content: center;
        }
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .alert-success {
            background: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #f87171;
            color: #991b1b;
        }
        .alert-info {
            background: #dbeafe;
            border: 1px solid #60a5fa;
            color: #1e40af;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">
    <div class="flex min-h-screen flex-col">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-4xl mx-auto">
                    
                    <!-- Header Section -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <!-- Left Side -->
                            <div class="flex items-center gap-4">
                                <div>
                                    <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800"
                                        href="show_my_docs.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="lucide lucide-arrow-left">
                                            <path d="m12 19-7-7 7-7"></path>
                                            <path d="M19 12H5"></path>
                                        </svg>
                                        <span class="sr-only">Back</span>
                                    </a>
                                </div>
                                <div>
                                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Add Document</h1>
                                    <p class="text-gray-500 text-sm">Upload and manage your medical records and files.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> fade-in">
                            <i data-lucide="<?php echo $message_type == 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                            <div><?php echo $message; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Card -->
                    <div class="card">
                        <form method="POST" action="" enctype="multipart/form-data" id="documentForm">
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    
                                    <!-- Document Name -->
                                    <div class="md:col-span-2">
                                        <label class="form-label">
                                            <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                                            Document Name *
                                        </label>
                                        <input type="text" name="document_name" class="form-input" 
                                               placeholder="e.g., Blood Test Report, MRI Scan, etc."
                                               value="<?php echo isset($document_name) ? htmlspecialchars($document_name) : ''; ?>" 
                                               required>
                                    </div>

                                    <!-- Document Type -->
                                    <div>
                                        <label class="form-label">
                                            <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i>
                                            Document Type *
                                        </label>
                                        <select name="document_type" class="form-select" required>
                                            <option value="">Select Document Type</option>
                                            <?php foreach ($doc_types as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" 
                                                    <?php echo (isset($document_type) && $document_type == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Document Category -->
                                    <div>
                                        <label class="form-label">
                                            <i data-lucide="folder" class="w-4 h-4 inline mr-1"></i>
                                            Category *
                                        </label>
                                        <select name="document_category" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($doc_categories as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" 
                                                    <?php echo (isset($document_category) && $document_category == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Sub Category -->
                                    <div>
                                        <label class="form-label">
                                            <i data-lucide="layers" class="w-4 h-4 inline mr-1"></i>
                                            Sub Category
                                        </label>
                                        <select name="document_sub_category" class="form-select">
                                            <option value="">Select Sub Category</option>
                                            <?php foreach ($doc_sub_categories as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" 
                                                    <?php echo (isset($document_sub_category) && $document_sub_category == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Document Date -->
                                    <div>
                                        <label class="form-label">
                                            <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                            Document Date *
                                        </label>
                                        <input type="date" name="document_date" class="form-input" 
                                               value="<?php echo isset($document_date) ? $document_date : date('Y-m-d'); ?>" 
                                               required>
                                    </div>

                                    <!-- Tags -->
                                    <div>
                                        <label class="form-label">
                                            <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
                                            Tags
                                        </label>
                                        <input type="text" name="document_tags" class="form-input" 
                                               placeholder="e.g., blood-test, mri, report-2024"
                                               value="<?php echo isset($document_tags) ? htmlspecialchars($document_tags) : ''; ?>">
                                        <p class="text-xs text-gray-500 mt-1">Separate tags with commas</p>
                                    </div>

                                    <!-- Note -->
                                    <div class="md:col-span-2">
                                        <label class="form-label">
                                            <i data-lucide="edit-3" class="w-4 h-4 inline mr-1"></i>
                                            Note / Description
                                        </label>
                                        <textarea name="note" class="form-textarea" 
                                                  placeholder="Add any additional notes about this document..."><?php echo isset($note) ? htmlspecialchars($note) : ''; ?></textarea>
                                    </div>

                                    <!-- File Upload -->
                                    <div class="md:col-span-2">
                                        <label class="form-label">
                                            <i data-lucide="upload" class="w-4 h-4 inline mr-1"></i>
                                            Upload File *
                                        </label>
                                        <div class="upload-zone" id="uploadZone">
                                            <input type="file" name="upload_file" id="fileInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.txt" required>
                                            <div id="uploadContent">
                                                <i data-lucide="cloud-upload" class="w-10 h-10 mx-auto text-gray-400 mb-2"></i>
                                                <p class="text-gray-600 font-medium">Click or drag & drop to upload</p>
                                                <p class="text-sm text-gray-400 mt-1">PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, WEBP, TXT (Max 10MB)</p>
                                            </div>
                                            <div id="filePreview" style="display: none;" class="file-preview">
                                                <i data-lucide="file" class="w-5 h-5 text-blue-600"></i>
                                                <span id="fileName" class="text-sm font-medium text-gray-700"></span>
                                                <span id="fileSize" class="text-xs text-gray-400"></span>
                                                <button type="button" onclick="removeFile()" class="text-red-500 hover:text-red-700">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                                            <i data-lucide="info" class="w-3 h-3"></i>
                                            <span>Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, WEBP, TXT</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                                    <button type="submit" name="add_document" class="btn-primary">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        Upload Document
                                    </button>
                                    <button type="reset" class="btn-secondary" onclick="resetForm()">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tips Section -->
                    

                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // File upload handling
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const uploadContent = document.getElementById('uploadContent');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        // Drag and drop events
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(fileExtension)) {
                alert('Invalid file type. Please upload a PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, WEBP, or TXT file.');
                fileInput.value = '';
                return;
            }
            
            if (file.size > maxSize) {
                alert('File is too large. Maximum size is 10MB.');
                fileInput.value = '';
                return;
            }
            
            // Show preview
            uploadContent.style.display = 'none';
            filePreview.style.display = 'inline-flex';
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
            
            // Update icon based on file type
            const iconElement = filePreview.querySelector('i');
            if (fileExtension === 'pdf') {
                iconElement.setAttribute('data-lucide', 'file-pdf');
                iconElement.className = 'w-5 h-5 text-red-600';
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                iconElement.setAttribute('data-lucide', 'file-image');
                iconElement.className = 'w-5 h-5 text-green-600';
            } else if (['doc', 'docx'].includes(fileExtension)) {
                iconElement.setAttribute('data-lucide', 'file-text');
                iconElement.className = 'w-5 h-5 text-blue-600';
            } else if (['xls', 'xlsx'].includes(fileExtension)) {
                iconElement.setAttribute('data-lucide', 'file-spreadsheet');
                iconElement.className = 'w-5 h-5 text-green-700';
            } else {
                iconElement.setAttribute('data-lucide', 'file');
                iconElement.className = 'w-5 h-5 text-gray-600';
            }
            lucide.createIcons();
        }
        
        function removeFile() {
            fileInput.value = '';
            uploadContent.style.display = 'block';
            filePreview.style.display = 'none';
        }
        
        function resetForm() {
            document.getElementById('documentForm').reset();
            removeFile();
            document.querySelectorAll('.form-input.error, .form-select.error, .form-textarea.error').forEach(el => {
                el.classList.remove('error');
            });
        }
        
        // Form validation
        document.getElementById('documentForm').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!fileInput.files.length) {
                uploadZone.style.borderColor = '#ef4444';
                uploadZone.style.background = '#fef2f2';
                alert('Please select a file to upload.');
                isValid = false;
            } else {
                uploadZone.style.borderColor = '';
                uploadZone.style.background = '';
            }
            
            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Remove error state on focus
        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(el => {
            el.addEventListener('focus', function() {
                this.classList.remove('error');
            });
        });
        
        // Auto-resize textarea
        document.querySelectorAll('.form-textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    </script>
</body>
</html>