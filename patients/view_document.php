<?php 
    session_start();
    include("../config/hospital.php");
    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location:my_documents.php");
        exit();
    }

    $doc_id = mysqli_real_escape_string($conn, $_GET['id']);
    $register_id = $_SESSION["id"];

    $doc_query = "select d.*, p.patient_name 
                 from patient_documents d 
                 join patients p on d.patient_id = p.patient_id 
                 where d.document_id = '$doc_id' and p.register_id = '$register_id' and d.delete_flag = 0";
    
    $result = $conn->query($doc_query);
    
    if ($result->num_rows == 0) {
        echo "Document not found or access denied.";
        exit();
    }

    $doc = $result->fetch_assoc();
    $file_path = "../uploads/documents/" . $doc['upload_file'];
    $file_ext = strtolower(pathinfo($doc['upload_file'], PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $doc['document_name']; ?> - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
         <?php include('header.php') ?>
        
        <div class="flex flex-1 items-start">
            <?php include('Sidebar.php') ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-5xl mx-auto">
                    
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <a href="show_my_docs.php" class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight mb-1"><?php echo $doc['document_name']; ?></h1>
                                <p class="text-gray-500 text-sm"><?php echo $doc['document_type']; ?> • Uploaded on <?php echo date('F d, Y', strtotime($doc['document_date'])); ?></p>
                            </div>
                        </div>
                        <a href="<?php echo $doc['upload_file'];; ?>" download class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold flex items-center gap-2 transition-all shadow-md shadow-blue-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                            Download File
                        </a>
                    </div>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-sm overflow-hidden min-h-[600px] flex flex-col">
                        
                        <div class="bg-gray-50 dark:bg-neutral-800/50 px-6 py-4 border-b dark:border-neutral-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-8 rounded bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <span class="text-sm font-semibold uppercase tracking-wider text-gray-500"><?php echo $file_ext; ?> Document</span>
                            </div>
                            <?php if ($doc['note']): ?>
                            <div class="text-sm text-gray-500 max-w-md truncate italic">
                                "<?php echo $doc['note']; ?>"
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1 p-6 flex items-center justify-center bg-gray-100 dark:bg-neutral-950">
                            <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                <img src="<?php echo $doc['upload_file']; ?>" alt="Document Preview" class="max-w-full max-h-[800px] shadow-2xl rounded-lg">
                            <?php elseif ($file_ext === 'pdf'): ?>
                                <iframe src="<?php echo $doc['upload_file']; ?>" class="w-full h-[800px] border-none rounded-lg shadow-inner"></iframe>
                            <?php else: ?>
                                <div class="text-center p-12">
                                    <div class="size-24 rounded-full bg-white dark:bg-neutral-900 flex items-center justify-center mx-auto mb-6 shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2">Preview Not Available</h3>
                                    <p class="text-gray-500 mb-8">This file type (.<?php echo $file_ext; ?>) cannot be previewed in the browser.</p>
                                    <a href="<?php echo $doc['upload_file']; ?>" download class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all">
                                        Download to View
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
