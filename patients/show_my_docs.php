<?php 
session_start();
include("../config/hospital.php");

// Check if user is logged in
if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location: ../auth/logout.php");
    exit();
}

// Handle Delete Request (Soft Delete using delete_flag)
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $register_id = $_SESSION["id"];
    
    // Verify document belongs to this patient
    $find_patient = "SELECT patient_id FROM patients WHERE register_id='$register_id'";
    $pat_result = $conn->query($find_patient);
    $pat_data = $pat_result->fetch_assoc();
    $patient_id = $pat_data["patient_id"];
    
    // Check if document belongs to this patient and is not already deleted
    $check_doc = "SELECT * FROM patient_documents WHERE document_id='$delete_id' AND patient_id='$patient_id' AND (delete_flag=0 OR delete_flag IS NULL)";
    $check_result = $conn->query($check_doc);
    
    if ($check_result->num_rows > 0) {
        // Soft delete - update delete_flag to 1
        $deleteQuery = "UPDATE patient_documents SET delete_flag=1 WHERE document_id='$delete_id' AND patient_id='$patient_id'";
        if ($conn->query($deleteQuery)) {
            // Show success message and redirect
            echo "<script>
                alert('Document deleted successfully!');
                window.location.href='delete_doc.php';
            </script>";
            exit();
        } else {
            echo "<script>
                alert('Error deleting document: " . $conn->error . "');
                window.location.href='delete_doc.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>
            alert('You are not authorized to delete this document.');
            window.location.href='my_documents.php';
        </script>";
        exit();
    }
}

$register_id = $_SESSION["id"];
$find_patient_id = "SELECT patient_id FROM patients WHERE register_id='$register_id'";
$pat_id = $conn->query($find_patient_id);
$patient_id = $pat_id->fetch_assoc();
$patientid = $patient_id["patient_id"];

// Fetch only documents that are not deleted (delete_flag=0 or NULL)
$showmydocs = "SELECT * FROM patient_documents WHERE patient_id='$patientid' AND (delete_flag=0 OR delete_flag IS NULL) ORDER BY document_date DESC";
$mydocscount = $conn->query($showmydocs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
        
        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .action-btn-view {
            color: #3b82f6;
        }
        .action-btn-view:hover {
            background: #dbeafe;
        }
        .action-btn-delete {
            color: #ef4444;
        }
        .action-btn-delete:hover {
            background: #fee2e2;
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <?php include('header.php') ?>
        
        <div class="flex flex-1 items-start">
            <?php include('Sidebar.php') ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <div class="mb-8">
                        <div class="flex items-center justify-between flex-wrap gap-4">

                            <!-- Left Side -->
                            <div class="flex items-center gap-4">
                                <div>
                                    <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800"
                                        href="dashboard.php">
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
                                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">My Documents</h1>
                                    <p class="text-gray-500 text-sm">Manage and download your medical records and files.</p>
                                </div>
                            </div>

                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-bold flex items-center gap-2 transition-all shadow-md shadow-blue-500/20" 
                                    onclick="window.location.href='add_document.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/><path d="M12 5v14"/>
                                </svg>
                                Add New Document
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($mydocscount->num_rows > 0): ?>
                    <!-- Documents Grid/List -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 dark:bg-neutral-800/50">
                                    <tr class="text-xs font-bold uppercase text-gray-500 dark:text-neutral-400">
                                        <th class="p-4">Document Name</th>
                                        <th class="p-4">Type</th>
                                        <th class="p-4">Note</th>
                                        <th class="p-4">Date Uploaded</th>
                                        <th class="p-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-neutral-800">
                                    <?php while($row = $mydocscount->fetch_assoc()): ?>
                                    <tr class="text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/30 transition-colors fade-in">
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="size-10 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-bold"><?php echo htmlspecialchars($row['document_name']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 rounded bg-gray-100 dark:bg-neutral-800 text-[10px] font-bold uppercase tracking-wider">
                                                <?php echo htmlspecialchars($row['document_type']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-gray-600 dark:text-neutral-400">
                                            <?php echo htmlspecialchars($row['note'] ?? '-') ?>
                                        </td>
                                        <td class="p-4 text-gray-600 dark:text-neutral-400">
                                            <?php echo date('d-m-Y', strtotime($row['document_date'])) ?>
                                        </td>
                                        <td class="p-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="view_document.php?id=<?php echo $row['document_id']; ?>" 
                                                   class="action-btn action-btn-view p-2 rounded-md inline-flex" 
                                                   title="View Document">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </a>
                                                <?php $doc_id = $row['document_id'] ?>
                                                <button onclick="confirmDelete(<?php echo $doc_id ; ?>, '<?php echo htmlspecialchars($row['document_name']); ?>')" 
                                                        class="action-btn action-btn-delete p-2 rounded-md" 
                                                        title="Delete Document">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Empty State -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg p-12 text-center shadow-sm">
                        <div class="size-16 rounded-full bg-gray-100 dark:bg-neutral-800 flex items-center justify-center mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-1">No Documents Found</h3>
                        <p class="text-gray-500 text-sm mb-6">You haven't uploaded any documents yet.</p>
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold transition-all" 
                                onclick="window.location.href='add_document.php'">
                            Upload First Document
                        </button>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Delete function with simple confirm alert
        function confirmDelete(doc_id, documentName) {
            if (confirm(`Are you sure you want to delete document "${documentName}"? This action cannot be undone.`)) {
                window.location.href = `delete_doc.php?id=${doc_id}`;
            }
        }
    </script>
</body>
</html>