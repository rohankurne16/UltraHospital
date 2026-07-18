<?php 
    session_start();
    include '../config/hospital.php'; 
    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location:my_prescriptions.php");
        exit();
    }

    $prescription_id = mysqli_real_escape_string($conn, $_GET['id']);
    $register_id = $_SESSION["id"];

    $prescription_query = "select pr.*, p.patient_name, p.email, p.mobile, p.address, p.age, p.gender, d.doctor_name, d.department, d.specialization 
                          from prescriptions pr 
                          join patients p on pr.patient_id = p.patient_id 
                          join doctor d on pr.doctor_id = d.doctor_id 
                          where pr.id = '$prescription_id' and p.register_id = '$register_id'";
    
    $result = $conn->query($prescription_query);
    
    if ($result->num_rows == 0) {
        echo "Prescription not found or access denied.";
        exit();
    }

    $presc = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription #<?php echo $presc['id']; ?> - <?php echo $hospital['hospital_name'] ?></title>
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
                        <a href="show_my_prescriptions.php" class="text-gray-500 hover:text-gray-700 dark:text-neutral-400 dark:hover:text-neutral-200 flex items-center gap-2 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            Back to Prescriptions
                        </a>
                        <div class="flex gap-3">
                            <button onclick="window.print()" class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 text-gray-700 dark:text-neutral-200 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                Print Prescription
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-xl overflow-hidden print-shadow-none">
                        <div class="p-8 lg:p-12 border-b border-gray-100 dark:border-neutral-800 flex flex-col md:flex-row justify-between gap-8">
                            <div>
                                <div class="flex items-center gap-2 mb-6">
                                    <img src="../<?php echo $hospital['hospital_logo']; ?>" height="120" width="120" >
                                </div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Prescribed By</h2>
                                <p class="font-bold text-lg"><?php echo $presc['doctor_name']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $presc['department']; ?> - <?php echo $presc['specialization']; ?></p>
                                <p class="text-gray-500 text-sm mt-2"><?php echo $hospital['hospital_name']; ?></p>
                                <p class="text-gray-500 text-sm"><?php echo $hospital['address']; ?></p>
                            </div>
                            <div class="md:text-right">
                                <h1 class="text-4xl font-black text-gray-200 dark:text-neutral-800 mb-4 uppercase">Prescription</h1>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Record Number</p>
                                <p class="font-black text-xl text-blue-600 mb-4">#PRE-<?php echo $presc['id']; ?></p>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Date Prescribed</p>
                                <p class="font-bold"><?php echo date('F d, Y', strtotime($presc['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="px-8 lg:px-12 py-8 bg-gray-50/50 dark:bg-neutral-800/30 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Patient Details</h2>
                                <p class="font-black text-lg"><?php echo $presc['patient_name']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm">Age/Gender: <?php echo $presc['age']; ?> / <?php echo $presc['gender']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm">Contact: <?php echo $presc['mobile']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $presc['address']; ?></p>
                            </div>
                            <div class="md:text-right">
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Follow-up</h2>
                                <p class="font-bold text-blue-600 text-lg"><?php echo $presc['followup_date'] ? date('F d, Y', strtotime($presc['followup_date'])) : 'No follow-up required'; ?></p>
                                <p class="text-xs text-gray-500 mt-1 italic">Please bring this copy during your next visit.</p>
                            </div>
                        </div>

                        <div class="p-8 lg:p-12">
                            

                            <table class="w-full text-left mb-8">
                                <thead>
                                    <tr class="border-b-2 border-gray-100 dark:border-neutral-800 text-xs font-bold uppercase text-gray-400">
                                        <th class="py-4">Medicine & Strength</th>
                                        <th class="py-4 text-center">Dosage</th>
                                        <th class="py-4 text-center">Frequency</th>
                                        <th class="py-4 text-right">Duration</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-neutral-800">
                                    <tr>
                                        <td class="py-6">
                                            <p class="font-bold text-lg text-blue-600 dark:text-blue-400"><?php echo $presc['medicine_name']; ?></p>
                                            <p class="text-sm text-gray-500 mt-1 italic"><?php echo $presc['timing']; ?></p>
                                        </td>
                                        <td class="py-6 text-center font-bold"><?php echo $presc['dosage']; ?></td>
                                        <td class="py-6 text-center font-bold"><?php echo $presc['frequency']; ?></td>
                                        <td class="py-6 text-right font-bold"><?php echo $presc['dosage']; ?> Days</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-8 border-t-2 border-gray-100 dark:border-neutral-800">
                                <div>
                                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Advice & Remarks</h3>
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-900/20">
                                        <p class="text-sm text-gray-700 dark:text-neutral-300 leading-relaxed italic">
                                            "<?php echo $presc['advice'] ?: 'No additional advice provided.'; ?>"
                                        </p>
                                    </div>
                                </div>
                               
                            </div>
                        </div>

                        <div class="p-8 bg-gray-900 text-white text-center">
                            <p class="font-bold mb-1">Stay Healthy with <?php echo $hospital['hospital_name']; ?></p>
                            <p class="text-gray-400 text-xs">This is a digitally generated prescription for informational and clinical use.</p>
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
