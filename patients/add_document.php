<?php 
    session_start();
    include("../config/hospital.php");
    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    $register_id = $_SESSION["id"];
    $find_patient_id = "select patient_id from patients where register_id='$register_id'";
    $pat_id = $conn->query($find_patient_id);
    $patient_id_row = $pat_id->fetch_assoc();
    $patient_id = $patient_id_row["patient_id"];

    $message = "";
    $messageType = "";

    if (isset($_POST['submit'])) {
        $document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
        $document_type = mysqli_real_escape_string($conn, $_POST['document_type']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);
        $document_date = mysqli_real_escape_string($conn, $_POST['document_date']);
        
        
        $target_dir = "../documents/patients/document/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name =$_FILES["upload_file"]["name"];
        $target_file = $target_dir . $file_name;
        $uploadOk = 1;
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_file)) {

            $insert_query = "INSERT INTO patient_documents
            (patient_id, document_name, document_type, upload_file, note, document_date, delete_flag)
            VALUES
            ('$patient_id', '$document_name', '$document_type', '$target_file', '$note', '$document_date', 0)";

            if ($conn->query($insert_query)) {
                $message = "Document uploaded successfully!";
                $messageType = "success";
                header("location:show_my_docs.php");
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
            $messageType = "error";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document - <?php echo $hospital['hospital_name'] ?> </title> 
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
         <?php include('header.php') ?>
        
        <div class="flex flex-1 items-start">
            <?php include('Sidebar.php') ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-3xl mx-auto">
                    
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
                                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Add New Document</h1>
                                    <p class="text-gray-500 text-sm">Upload and categorize your medical records.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    

                    <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                        <?php echo $message; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Form Card -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl shadow-sm overflow-hidden">
                        <form action="" method="POST" enctype="multipart/form-data" class="p-6 lg:p-8 space-y-6">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Document Name -->
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300" for="document_name">Document Name</label>
                                    <input type="text" name="document_name" id="document_name" required
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                        placeholder="e.g. Blood Test Report">
                                </div>

                                <!-- Document Type -->
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300" for="document_type">Document Type</label>
                                    <select name="document_type" id="document_type" required
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                        <option value="">Select Type</option>
                                         <option>Medical Report</option>
                                                <option>ID Proof</option>
                                                <option>Prescription</option>
                                                <option>Insurance Card</option>
                                                <option>Lab Report</option>
                                                <option>Consent Form</option>
                                                <option>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Document Date -->
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300" for="document_date">Document Date</label>
                                    <input type="date" name="document_date" id="document_date" required
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                </div>

                                <!-- File Upload -->
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300" for="upload_file">Upload File</label>
                                    <input type="file" name="upload_file" id="upload_file" required
                                        class="w-full px-4 py-2 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                            </div>

                            <!-- Note -->
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300" for="note">Note (Optional)</label>
                                <textarea name="note" id="note" rows="4"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="Add any additional details about this document..."></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button type="submit" name="submit"
                                    class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition-all shadow-md shadow-blue-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                                    Upload Document
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
