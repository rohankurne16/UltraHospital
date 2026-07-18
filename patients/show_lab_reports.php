<?php 
session_start();
include("../config/hospital.php");

if (!isset($_SESSION["id"])){
    header("location: ../auth/logout.php");
    exit();
}

$register_id = $_SESSION["id"];

$find_patient_query = "select * from patients where register_id='$register_id' and(delete_flag=0 or delete_flag is null)";
$patient_data = $conn->query($find_patient_query);

if ($patient_data && $patient_data->num_rows > 0) {
    $patient = $patient_data->fetch_assoc();
    $patient_id = $patient["patient_id"];

    $show_reports_query = "select * from lab_report where patient_id='$patient_id' and(delete_flag=0 or delete_flag is null)";
    $my_reports = $conn->query($show_reports_query);
} else {
    header("location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Reports - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <?php include("header.php");?>
        
        <div class="flex flex-1 items-start">
            <?php include("Sidebar.php");?>
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <div class="flex flex-col gap-5 mb-8">
                        <div class="flex items-center flex-wrap gap-4">
                            <button class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800" onclick="window.location.href='dashboard.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                            </button>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Lab Reports</h1>
                                <p class="text-gray-500 text-sm">View and download your diagnostic laboratory reports.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg p-6 shadow-sm">
                        
                        <div class="mb-8">
                            <h3 class="font-bold mb-4 text-gray-700 dark:text-neutral-300">Patient Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 dark:bg-neutral-800/50 p-4 rounded-lg">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-medium">Name</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($patient['patient_name']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-medium">Age / Gender</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($patient['age']) . " / " . htmlspecialchars($patient['gender']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-medium">Blood Group</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($patient['blood_group']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="font-bold mb-4 text-gray-700 dark:text-neutral-300">Recent Test Results</h3>
                            <div class="border rounded-lg overflow-hidden dark:border-neutral-800">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50 dark:bg-neutral-800">
                                        <tr class="text-xs font-bold uppercase text-gray-500">
                                            <th class="p-4">Report ID</th>
                                            <th class="p-4">Test Name</th>
                                            <th class="p-4">Date</th>
                                            <th class="p-4">Remark</th>
                                            <th class="p-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y dark:divide-neutral-800">
                                        <?php if ($my_reports && $my_reports->num_rows > 0): ?>
                                            <?php while($row = $my_reports->fetch_assoc()): ?>
                                            <tr class="text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/50 transition-colors">
                                                <td class="p-4 font-medium"><?php echo "#LR:". htmlspecialchars($row['id']) ?></td>
                                                <td class="p-4">
                                                    <div class="font-semibold"><?php echo htmlspecialchars($row['test_name']) ?></div>
                                                </td>
                                                <td class="p-4"><?php echo htmlspecialchars($row['report_date']) ?></td>
                                                <td class="p-4"><?php echo htmlspecialchars($row['remark']) ?></td>
                                                <td class="p-4 text-right">
                                                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs" onclick="window.location.href='show_report_details.php?id=<?php echo $row['id'] ?>'">View Reports</button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-neutral-400">
                                                    No Lab Reports Available
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="p-4 border border-blue-100 bg-blue-50/50 rounded-lg text-sm text-blue-700 dark:bg-blue-900/10 dark:border-blue-900/30 dark:text-blue-400">
                            <div class="flex gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg>
                                <p>Reports are usually available within 24-48 hours of sample collection. You will receive an SMS notification once your report is ready.</p>
                            </div>
                        </div>

                    </div>
                  
                </div>
            </main>
        </div>
    </div>
</body>
</html>
