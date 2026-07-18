<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Details - MedixPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; color: black !important; }
            .prescription-card { border: none !important; box-shadow: none !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .xl\:ml-64 { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <div class="no-print">
            <?php include '../header.php'; ?>
        </div>
        
        <div class="flex flex-1 items-start">
            <div class="no-print">
                <?php include '../Sidebar.php'; ?>
            </div>
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-4xl mx-auto">
                    
                    <!-- Page Actions -->
                    <div class="flex items-center justify-between mb-8 no-print">
                        <div class="flex items-center gap-4">
                            <a class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:bg-gray-100 dark:hover:bg-neutral-800 size-11 transition-all shadow-sm" href="prescriptions.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                            </a>
                            <h1 class="text-2xl font-bold tracking-tight">Prescription View</h1>
                        </div>
                        <button onclick="window.print()" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg shadow-blue-500/30 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                            Print Prescription
                        </button>
                    </div>

                    <?php if (isset($prescription)): ?>
                    <!-- Prescription Card -->
                    <div class="prescription-card bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-3xl overflow-hidden shadow-sm">
                        
                        <!-- Header with Branding -->
                        <div class="p-8 border-b border-gray-100 dark:border-neutral-800 flex justify-between items-start bg-blue-50/30 dark:bg-blue-900/5">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-600 p-2 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-blue-600 dark:text-blue-400">MedixPro</h2>
                                    <p class="text-xs text-gray-500 dark:text-neutral-500 font-medium tracking-widest uppercase">Medical Center</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold">#PR-<?php echo str_pad($prescription['prescription_id'], 5, '0', STR_PAD_LEFT); ?></p>
                                <p class="text-sm text-gray-500 dark:text-neutral-400">Date: <?php echo date('M d, Y', strtotime($prescription['created_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Patient & Doctor Info -->
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-gray-100 dark:border-neutral-800">
                            <div class="space-y-4">
                                <h3 class="text-sm font-semibold text-gray-400 dark:text-neutral-500 uppercase tracking-wider">Patient Information</h3>
                                <div>
                                    <p class="text-xl font-bold"><?php echo $prescription['patient_name']; ?></p>
                                    <div class="flex gap-4 mt-1 text-sm text-gray-600 dark:text-neutral-400">
                                        <span>Age: <span class="font-medium"><?php echo $prescription['age']; ?> yrs</span></span>
                                        <span>Gender: <span class="font-medium"><?php echo $prescription['gender']; ?></span></span>
                                        <span>Blood: <span class="font-medium"><?php echo $prescription['blood_group']; ?></span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 md:text-right">
                                <h3 class="text-sm font-semibold text-gray-400 dark:text-neutral-500 uppercase tracking-wider">Prescribing Doctor</h3>
                                <div>
                                    <p class="text-xl font-bold">Dr. <?php echo $prescription['doctor_name']; ?></p>
                                    <p class="text-blue-600 dark:text-blue-400 font-medium"><?php echo $prescription['specialization']; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Medications List -->
                        <div class="p-8">
                            <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                                Medications & Dosage
                            </h3>
                            
                            <div class="overflow-hidden border border-gray-100 dark:border-neutral-800 rounded-2xl">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-neutral-800/50 text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider">
                                            <th class="px-6 py-4">Medicine Name</th>
                                            <th class="px-6 py-4">Dosage</th>
                                            <th class="px-6 py-4">Duration</th>
                                            <th class="px-6 py-4">Instruction</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                        <?php 
                                        // Example data if no medications found in DB
                                        $display_meds = !empty($medications) ? $medications : [
                                            ['name' => 'Amoxicillin 500mg', 'dosage' => '1-0-1', 'duration' => '5 Days', 'instruction' => 'After Meal'],
                                            ['name' => 'Paracetamol 650mg', 'dosage' => '1-1-1', 'duration' => '3 Days', 'instruction' => 'If Fever']
                                        ];
                                        
                                        foreach ($display_meds as $med): ?>
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                                            <td class="px-6 py-4 font-semibold"><?php echo $med['name']; ?></td>
                                            <td class="px-6 py-4"><?php echo $med['dosage']; ?></td>
                                            <td class="px-6 py-4"><?php echo $med['duration']; ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-neutral-400"><?php echo $med['instruction']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="px-8 pb-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="md:col-span-2">
                                <h3 class="text-sm font-semibold text-gray-400 dark:text-neutral-500 uppercase tracking-wider mb-3">Advice / Additional Notes</h3>
                                <div class="bg-gray-50 dark:bg-neutral-800/50 p-5 rounded-2xl text-sm leading-relaxed">
                                    <?php echo !empty($prescription['notes']) ? $prescription['notes'] : "Drink plenty of water and take complete bed rest for at least 3 days. Avoid cold drinks and spicy food. Follow up after 1 week if symptoms persist."; ?>
                                </div>
                            </div>
                            <div class="flex flex-col justify-end items-center text-center">
                                <div class="w-32 h-16 border-b border-gray-300 dark:border-neutral-700 mb-2"></div>
                                <p class="text-xs font-bold uppercase tracking-widest">Doctor's Signature</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-6 bg-gray-50 dark:bg-neutral-800/30 text-center text-xs text-gray-400 dark:text-neutral-500 border-t border-gray-100 dark:border-neutral-800">
                            This is a computer-generated prescription. No physical signature required.
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 p-12 rounded-2xl text-center shadow-sm">
                            <div class="bg-red-50 dark:bg-red-900/20 size-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">Prescription Not Found</h3>
                            <p class="text-gray-500 dark:text-neutral-400 mb-8"><?php echo $error ? $error : "The prescription record could not be found."; ?></p>
                            <a href="prescriptions.php" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-8 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/25">Return to List</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <?php $conn->close(); ?>
</body>
</html>
