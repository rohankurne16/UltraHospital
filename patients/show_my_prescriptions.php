<?php 
session_start();
include("../config/hospital.php");


if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$id = $_SESSION["id"];


$findpatientdataa = "select * from patients where register_id='$id'";
$allpatientdata = $conn->query($findpatientdataa);

if ($allpatientdata && $allpatientdata->num_rows > 0) {
    $patient = $allpatientdata->fetch_assoc();
    $patient_id = $patient["patient_id"];

  
    $show_my_prescriptions = "select * from prescriptions where patient_id='$patient_id' and(delete_flag=0 or delete_flag is null)";
    $my_prescriptions = $conn->query($show_my_prescriptions);
} else {
    die("Patient data not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - <?php echo $hospital['hospital_name'] ?></title>
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
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <!-- Header -->
                    <div class="flex flex-col gap-5 mb-8">
                        <div class="flex items-center flex-wrap gap-4">
                            <a class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800" href="dashboard.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                                <span class="sr-only">Back</span>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">My Prescriptions</h1>
                                <p class="text-gray-500 text-sm">View and manage your medical prescriptions.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription Content -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg p-6 shadow-sm">
                        
                        <!-- Patient Info Section -->
                        <div class="mb-8">
                            <h3 class="font-bold mb-4 text-gray-700 dark:text-neutral-300">Patient Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 dark:bg-neutral-800/50 p-4 rounded-lg">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-medium">Name</p>
                                    <p class="font-semibold"><?php echo $patient['patient_name']; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-medium">Age / Gender</p>
                                    <p class="font-semibold"><?php echo $patient['age'] . " / " . $patient['gender']; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-medium">Blood Group</p>
                                    <p class="font-semibold"><?php echo $patient['blood_group']; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Medication Table -->
                        <div class="mb-8">
                            <h3 class="font-bold mb-4 text-gray-700 dark:text-neutral-300">Prescription Records</h3>
                            <div class="border rounded-lg overflow-hidden dark:border-neutral-800">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50 dark:bg-neutral-800">
                                        <tr class="text-xs font-bold uppercase text-gray-500">
                                            <th class="p-3">Prescription ID</th>
                                            <th class="p-3">Doctor</th>
                                            <th class="p-3">Medicine Name</th>
                                            <th class="p-3">Dosage</th>
                                            <th class="p-3">Frequency</th>
                                            <th class="p-3">Follow-up</th>
                                            <th class="p-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y dark:divide-neutral-800">
                                        <?php 
                                        if ($my_prescriptions && $my_prescriptions->num_rows > 0) {
                                            while ($row = $my_prescriptions->fetch_assoc()) {
                                                $doctor_id = $row['doctor_id'];
                                                $doctordata = "SELECT * FROM doctor WHERE doctor_id='$doctor_id'";
                                                $doctorinfo = $conn->query($doctordata);
                                                $docrow = ($doctorinfo && $doctorinfo->num_rows > 0) ? $doctorinfo->fetch_assoc() : ['doctor_name' => 'Unknown', 'department' => 'N/A'];
                                        ?>
                                        <tr class="text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/50 transition-colors">
                                            <td class="p-3 font-medium">#<?php echo $row['id']; ?></td>
                                            <td class="p-3">
                                                <div class="font-medium">Dr. <?php echo $docrow['doctor_name']; ?></div>
                                                <div class="text-xs text-gray-500"><?php echo $docrow['department']; ?></div>
                                            </td>
                                            <td class="p-3 font-medium text-blue-600 dark:text-blue-400"><?php echo $row['medicine_name']; ?></td>
                                            <td class="p-3"><?php echo $row['timing'] . " (" . $row['dosage'] . " Days)"; ?></td>
                                            <td class="p-3"><?php echo $row['frequency']; ?></td>
                                            <td class="p-3"><?php echo $row['followup_date']; ?></td>
                                            <td class="p-3 text-right">
                                               <button
                                                class="text-blue-600 hover:text-blue-800 font-medium text-xs"
                                                onclick="window.location.href='prescription_details.php?id=<?php echo $row['id']; ?>'">
                                                View Prescription
                                            </button>
                                            </td>
                                        </tr>
                                        <?php 
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='p-8 text-center text-gray-500'>No prescriptions found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div class="mb-8">
                            <h3 class="font-bold mb-2 text-gray-700 dark:text-neutral-300">General Advice</h3>
                            <div class="p-4 border rounded-lg text-sm text-gray-600 dark:text-neutral-400 dark:border-neutral-800">
                                <p>Please ensure you follow the dosage instructions carefully. If you experience any unusual side effects, contact your doctor immediately. Always keep a copy of your prescription for future reference.</p>
                            </div>
                        </div>


                    </div>
                  
                </div>
            </main>
        </div>
    </div>
</body>
</html>
