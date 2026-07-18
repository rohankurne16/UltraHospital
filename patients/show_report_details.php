<?php 
    session_start();
    include '../config/hospital.php'; 
    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location:my_reports.php");
        exit();
    }

    $report_id = mysqli_real_escape_string($conn, $_GET['id']);
    $register_id = $_SESSION["id"];

    $report_query = "select r.*, p.patient_name, p.email, p.mobile, p.address, p.age, p.gender, p.blood_group 
                    from lab_report r 
                    join patients p on r.patient_id = p.patient_id 
                    where r.id = '$report_id' and p.register_id = '$register_id'";
    
    $result = $conn->query($report_query);
    
    if ($result->num_rows == 0) {
        echo "Report not found or access denied.";
        exit();
    }

    $report = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report #<?php echo $report['id']; ?> - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none; }
            .print-shadow-none { box-shadow: none !important; border: none !important; }
            body { background: white; color: black; }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
         <div class="no-print"><?php include('header.php') ?></div>
        
        <div class="flex flex-1 items-start">
            <div class="no-print"><?php include('Sidebar.php') ?></div>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-4xl mx-auto">
                    
                    <div class="flex items-center justify-between mb-8 no-print">
                        <a href="show_lab_reports.php" class="text-gray-500 hover:text-gray-700 dark:text-neutral-400 dark:hover:text-neutral-200 flex items-center gap-2 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            Back to Reports
                        </a>
                        <div class="flex gap-3">
                            <button onclick="window.print()" class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 text-gray-700 dark:text-neutral-200 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                Print Report
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-xl overflow-hidden print-shadow-none">
                        <div class="p-8 lg:p-12 border-b border-gray-100 dark:border-neutral-800 flex flex-col md:flex-row justify-between gap-8">
                            <div>
                                <div class="flex items-center gap-2 mb-6">
                                    <img src="../<?php echo $hospital['hospital_logo']; ?>" height="120" width="120" >
                                </div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Laboratory Unit</h2>
                                <p class="font-bold text-lg"><?php echo $hospital['hospital_name']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $hospital['address']; ?></p>
                                <p class="text-gray-500 text-sm mt-2"><?php echo "+91 ".$hospital['phone']; ?></p>
                            </div>
                            <div class="md:text-right">
                                <h1 class="text-4xl font-black text-gray-200 dark:text-neutral-800 mb-4 uppercase">Lab Report</h1>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Report ID</p>
                                <p class="font-black text-xl text-blue-600 mb-4">#LR-<?php echo $report['id']; ?></p>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Test Date</p>
                                <p class="font-bold"><?php echo date('F d, Y', strtotime($report['report_date'])); ?></p>
                            </div>
                        </div>

                        <div class="px-8 lg:px-12 py-8 bg-gray-50/50 dark:bg-neutral-800/30 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Patient Information</h2>
                                <p class="font-black text-lg"><?php echo $report['patient_name']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm">Age/Gender: <?php echo $report['age']; ?> / <?php echo $report['gender']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm">Blood Group: <span class="font-bold text-red-600"><?php echo $report['blood_group']; ?></span></p>
                            </div>
                            <div class="md:text-right">
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Contact Details</h2>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $report['mobile']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $report['email']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm italic"><?php echo $report['address']; ?></p>
                            </div>
                        </div>

                        <div class="p-8 lg:p-12">
                            <div class="flex items-center gap-2 mb-6 border-b pb-4 dark:border-neutral-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M4.5 16.5c-1.5 1.26-2 3.33-1 4.5s3.24.5 4.5-1l10-10c1.5-1.26 2-3.33 1-4.5s-3.24-.5-4.5 1z"/><path d="m7.5 10.5 6 6"/></svg>
                                <h2 class="text-xl font-black uppercase tracking-tight">Test Results</h2>
                            </div>

                            <table class="w-full text-left mb-8">
                                <thead>
                                    <tr class="border-b-2 border-gray-100 dark:border-neutral-800 text-xs font-bold uppercase text-gray-400">
                                        <th class="py-4">Investigation</th>
                                      
                                        <th class="py-4 text-right">Result Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-neutral-800">
                                    <tr>
                                        <td class="py-6">
                                            <p class="font-bold text-lg text-blue-600 dark:text-blue-400"><?php echo $report['test_name']; ?></p>
                                           
                                        </td>
                                        
                                        <td class="py-6 text-right font-black text-lg"><?php echo $report['result_value']; ?></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="grid grid-cols-1 gap-8 pt-8 border-t-2 border-gray-100 dark:border-neutral-800">
                                <div>
                                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Pathologist's Remarks</h3>
                                    <div class="p-6 bg-gray-50 dark:bg-neutral-800/50 rounded-xl border border-gray-100 dark:border-neutral-800">
                                        <p class="text-sm text-gray-700 dark:text-neutral-300 leading-relaxed font-medium">
                                            <?php echo $report['remark'] ?: 'All parameters are within normal clinical limits. No abnormalities detected.'; ?>
                                        </p>
                                    </div>
                                </div>
                              
                            </div>
                        </div>

                        <div class="p-8 bg-gray-900 text-white text-center">
                            <p class="font-bold mb-1"><?php echo $hospital['hospital_name']; ?> Diagnostic Services</p>
                            <p class="text-gray-400 text-xs">This is a verified laboratory report. Please consult your physician for clinical correlation.</p>
                        </div>
                    </div>

                    <p class="text-center mt-8 text-gray-400 text-sm no-print">
                        &copy; <?php echo date('Y'); ?> MedixPro Healthcare Management System.
                    </p>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
