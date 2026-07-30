<?php 
session_start();
include("../config/hospital.php");

// Check if user is logged in
if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location: ../auth/logout.php");
    exit();
}

$register_id = $_SESSION["id"];

// Get patient ID
$find_patient_id = "SELECT patient_id FROM patients WHERE register_id='$register_id'";
$pat_id = $conn->query($find_patient_id);
$patient_data = $pat_id->fetch_assoc();
$patient_id = $patient_data["patient_id"];

// Get active tab from URL
$active_tab = isset($_GET['doc_tab']) ? mysqli_real_escape_string($conn, $_GET['doc_tab']) : 'Pre-Operation';

// Count documents by category
$count_query = "SELECT 
    SUM(CASE WHEN document_category='Pre-Operation' THEN 1 ELSE 0 END) as pre_count,
    SUM(CASE WHEN document_category='OT' THEN 1 ELSE 0 END) as ot_count,
    SUM(CASE WHEN document_category='Post-Operation' THEN 1 ELSE 0 END) as post_count
FROM patient_documents 
WHERE patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)";

$count_result = $conn->query($count_query);
$counts = $count_result->fetch_assoc();

$pre_count = $counts['pre_count'] ?? 0;
$ot_count = $counts['ot_count'] ?? 0;
$post_count = $counts['post_count'] ?? 0;

// Fetch documents based on active tab
$doc_query = "
    SELECT pd.*, r.name as uploaded_by_name
    FROM patient_documents pd
    LEFT JOIN register r ON pd.uploaded_by = r.id
    WHERE pd.patient_id='$patient_id'
    AND pd.document_category='$active_tab'
    AND (pd.delete_flag=0 OR pd.delete_flag IS NULL)
    ORDER BY pd.document_date DESC
";

$doc_result = mysqli_query($conn, $doc_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-active {
            border-bottom: 2px solid #3b82f6;
            color: #1e40af;
            font-weight: 600;
        }
        .tab-inactive {
            border-bottom: 2px solid transparent;
            color: #6b7280;
        }
        .tab-inactive:hover {
            border-bottom: 2px solid #d1d5db;
            color: #374151;
        }
        
        .stat-card {
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .btn-action {
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 0.75rem;
            padding: 0.375rem 0.875rem;
            border-radius: 0.5rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }
        
        .document-row {
            transition: all 0.2s ease;
        }
        .document-row:hover {
            background: #f9fafb;
        }
        
        /* Empty state */
        .empty-state-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">
    <div class="flex min-h-screen flex-col">
        <?php include('../header.php') ?>
        <div class="flex flex-1 items-start">
            <?php include('../Sidebar.php') ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-7xl mx-auto">
                    
                    <!-- Header Section -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <!-- Left Side -->
                            <div class="flex items-center gap-4">
                                <div>
                                    <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800"
                                        href="dashboard.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="m12 19-7-7 7-7"></path>
                                            <path d="M19 12H5"></path>
                                        </svg>
                                        <span class="sr-only">Back</span>
                                    </a>
                                </div>
                                <div>
                                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">My Documents</h1>
                                    <p class="text-gray-500 text-sm">View and download your medical records and files.</p>
                                </div>
                            </div>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-bold flex items-center gap-2 transition-all shadow-md shadow-blue-500/20" 
                                    onclick="window.location.href='add_doc.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/><path d="M12 5v14"/>
                                </svg>
                                Add New Document
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    

                    <!-- Documents Table -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                        <!-- Tabs -->
                        <div class="border-b border-gray-200 dark:border-neutral-800">
                            <div class="flex items-center gap-2 px-6 overflow-x-auto">
                                <a href="?doc_tab=Pre-Operation" 
                                   class="px-4 py-3 text-sm transition-colors <?php echo ($active_tab == 'Pre-Operation') ? 'tab-active' : 'tab-inactive'; ?>">
                                    <span class="flex items-center gap-2">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                        Pre-Operation
                                        <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full text-xs"><?php echo $pre_count; ?></span>
                                    </span>
                                </a>
                                <a href="?doc_tab=OT" 
                                   class="px-4 py-3 text-sm transition-colors <?php echo ($active_tab == 'OT') ? 'tab-active' : 'tab-inactive'; ?>">
                                    <span class="flex items-center gap-2">
                                        <i data-lucide="folder" class="w-4 h-4"></i>
                                        OT
                                        <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-2 py-0.5 rounded-full text-xs"><?php echo $ot_count; ?></span>
                                    </span>
                                </a>
                                <a href="?doc_tab=Post-Operation" 
                                   class="px-4 py-3 text-sm transition-colors <?php echo ($active_tab == 'Post-Operation') ? 'tab-active' : 'tab-inactive'; ?>">
                                    <span class="flex items-center gap-2">
                                        <i data-lucide="image" class="w-4 h-4"></i>
                                        Post-Operation
                                        <span class="bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 px-2 py-0.5 rounded-full text-xs"><?php echo $post_count; ?></span>
                                    </span>
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto p-6">
                            <?php if (mysqli_num_rows($doc_result) > 0): ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-neutral-800 text-gray-500 dark:text-neutral-400">
                                        <th class="text-left py-3 font-semibold">Document Date</th>
                                        <th class="text-left py-3 font-semibold">Document Title</th>
                                        <th class="text-left py-3 font-semibold">Document Type</th>
                                        <th class="text-left py-3 font-semibold">Uploaded By</th>
                                        <th class="text-left py-3 font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($doc = mysqli_fetch_assoc($doc_result)): ?>
                                    <tr class="document-row border-b border-gray-100 dark:border-neutral-800">
                                        <td class="py-3 text-gray-600 dark:text-neutral-300">
                                            <?php echo date("d M Y", strtotime($doc['document_date'])); ?>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                                                    <?php 
                                                    $file_ext = pathinfo($doc['upload_file'], PATHINFO_EXTENSION);
                                                    $icon = 'file-text';
                                                    $icon_color = 'text-blue-600';
                                                    if (in_array($file_ext, ['pdf'])) { 
                                                        $icon = 'file-pdf'; 
                                                        $icon_color = 'text-red-600';
                                                    }
                                                    elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { 
                                                        $icon = 'file-image'; 
                                                        $icon_color = 'text-green-600';
                                                    }
                                                    elseif (in_array($file_ext, ['doc', 'docx'])) { 
                                                        $icon = 'file-text'; 
                                                        $icon_color = 'text-blue-600';
                                                    }
                                                    elseif (in_array($file_ext, ['xls', 'xlsx'])) { 
                                                        $icon = 'file-spreadsheet'; 
                                                        $icon_color = 'text-green-700';
                                                    }
                                                    ?>
                                                    <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 <?php echo $icon_color; ?>"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 dark:text-white">
                                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-neutral-400">
                                                        <?php echo basename($doc['upload_file']); ?>
                                                        <span class="ml-2 text-[10px] uppercase text-gray-400"><?php echo $file_ext; ?></span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-neutral-300 text-xs font-medium">
                                                <?php echo htmlspecialchars($doc['document_type']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-gray-600 dark:text-neutral-300">
                                            <?php echo htmlspecialchars($doc['uploaded_by_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <!-- View Button -->
                                                <button onclick="viewDocument('<?php echo '../' . $doc['upload_file']; ?>')" 
                                                        class="btn-action bg-blue-600 text-white hover:bg-blue-700">
                                                    <i data-lucide="eye" class="w-3 h-3"></i> View
                                                </button>
                                                
                                                <!-- Download Button -->
                                                <button onclick="downloadDocument('<?php echo '../' . $doc['upload_file']; ?>', '<?php echo htmlspecialchars($doc['document_name']); ?>')" 
                                                        class="btn-action bg-green-600 text-white hover:bg-green-700">
                                                    <i data-lucide="download" class="w-3 h-3"></i> Download
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <!-- Empty State -->
                            <div class="text-center py-12">
                                <div class="empty-state-icon">
                                    <i data-lucide="folder-open" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No Documents Found</h3>
                                <p class="text-sm text-gray-500 dark:text-neutral-400">
                                    No documents available under <strong><?php echo $active_tab; ?></strong>
                                </p>
                                <button class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-all shadow-md shadow-blue-500/20" 
                                        onclick="window.location.href='add_doc.php'">
                                    <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Upload Document
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // View Document
        function viewDocument(filePath) {
            if (filePath && filePath !== '') {
                window.open(filePath, '_blank');
            } else {
                alert('Document file not available');
            }
        }

        // Download Document
        function downloadDocument(filePath, documentName) {
            if (!filePath || filePath.trim() === '') {
                alert('Document file not available');
                return;
            }
            
            let link = document.createElement('a');
            link.href = filePath;
            link.download = documentName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>