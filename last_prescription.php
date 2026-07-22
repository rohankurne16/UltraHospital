<?php
session_start();
include 'config/hospital.php';

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/logout.php");
    exit();
}

if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    die("Patient ID not found.");
}

$patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);

$prescription_query = "
SELECT
    pr.*,
    p.patient_name,
    p.email,
    p.mobile,
    p.address,
    p.age,
    p.gender,
    d.doctor_name,
    d.department,
    d.specialization
FROM prescriptions pr
JOIN patients p
    ON pr.patient_id = p.patient_id
JOIN doctor d
    ON pr.doctor_id = d.doctor_id
WHERE pr.patient_id = '$patient_id'
ORDER BY pr.created_at DESC
LIMIT 1
";

$result = mysqli_query($conn, $prescription_query);

if (!$result || mysqli_num_rows($result) == 0) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Prescription - <?php echo $hospital['hospital_name']; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .main-content{
            margin-left:260px;
        }

        @media(max-width:1024px){
            .main-content{
                margin-left:0;
            }
        }
    </style>
</head>

<body class="bg-gray-50">

<div class="flex min-h-screen flex-col">

    <?php include 'header.php'; ?>

    <div class="flex flex-1">

        <?php include 'Sidebar.php'; ?>

        <main class="main-content flex-1 flex items-center justify-center p-8">

            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-10 max-w-lg w-full text-center">

                <div class="mx-auto w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mb-6">
                    <i data-lucide="file-x" class="w-10 h-10 text-red-600"></i>
                </div>

                <h2 class="text-2xl font-bold text-gray-900">
                    No Prescription Found
                </h2>

                <p class="text-gray-500 mt-3">
                    This patient doesn't have any prescription records yet.
                </p>

                <div class="mt-8 flex justify-center gap-3">

                    <a href="view_patient.php?id=<?php echo $patient_id; ?>"
                       class="px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">
                        Back to Patient
                    </a>

                 

                </div>

            </div>

        </main>

    </div>

</div>

<script>
lucide.createIcons();
</script>

</body>
</html>

<?php
exit();
}

$presc = mysqli_fetch_assoc($result);
$patient_id = $presc['patient_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription #<?php echo $presc['id']; ?> - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-shadow-none { box-shadow: none !important; border: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            body { background: white; color: black; }
        }
        .main-content { margin-left: 260px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body class="bg-gray-50 text-neutral-900">

    <div class="flex min-h-screen flex-col">
         <div class="no-print"><?php include('header.php') ?></div>
        
        <div class="flex flex-1 items-start">
            <div class="no-print"><?php include('Sidebar.php') ?></div>
            <main class="main-content flex-1 p-4 xl:p-8 w-full">
                <div class="max-w-4xl mx-auto">
                    
                    <div class="flex items-center justify-between mb-8 no-print">
                        <a href="prescriptions.php" class="text-gray-500 hover:text-gray-700 flex items-center gap-2 transition-colors">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            Back to Prescriptions
                        </a>
                        <div class="flex gap-3">
                             <button onclick="window.location.href='view_prescription_history.php?id=<?php echo $patient_id ?>'" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                                View History
                            </button>

                            <button onclick="window.print()" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                                Print Prescription
                            </button>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden print-shadow-none">
                        <!-- Header -->
                        <div class="p-8 lg:p-12 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-8">
                            <div>
                                <div class="flex items-center gap-2 mb-6">
                                    <img src="<?php echo $hospital['hospital_logo']; ?>" height="80" width="80" class="object-contain">
                                    <div class="ml-2">
                                        <h2 class="text-xl font-bold text-blue-600"><?php echo $hospital['hospital_name']; ?></h2>
                                        <p class="text-gray-500 text-xs"><?php echo $hospital['address']; ?></p>
                                    </div>
                                </div>
                                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Prescribed By</h2>
                                <p class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($presc['doctor_name']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($presc['department']); ?> - <?php echo htmlspecialchars($presc['specialization']); ?></p>
                            </div>
                            <div class="md:text-right">
                                <h1 class="text-4xl font-black text-gray-100 mb-4 uppercase select-none">Prescription</h1>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Record Number</p>
                                <p class="font-black text-xl text-blue-600 mb-4">#PRE-<?php echo str_pad($presc['id'], 5, '0', STR_PAD_LEFT); ?></p>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Date Prescribed</p>
                                <p class="font-bold text-gray-900"><?php echo date('F d, Y', strtotime($presc['created_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Patient Info -->
                        <div class="px-8 lg:px-12 py-8 bg-gray-50/50 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Patient Details</h2>
                                <p class="font-black text-lg text-gray-900"><?php echo htmlspecialchars($presc['patient_name']); ?></p>
                                <p class="text-gray-600 text-sm">Age/Gender: <?php echo $presc['age']; ?> / <?php echo $presc['gender']; ?></p>
                                <p class="text-gray-600 text-sm">Contact: <?php echo htmlspecialchars($presc['mobile']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($presc['address']); ?></p>
                            </div>
                            <div class="md:text-right">
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Follow-up</h2>
                                <p class="font-bold text-blue-600 text-lg">
                                    <?php echo (!empty($presc['followup_date']) && $presc['followup_date'] != '0000-00-00') ? date('F d, Y', strtotime($presc['followup_date'])) : 'No follow-up required'; ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1 italic">Please bring this copy during your next visit.</p>
                            </div>
                        </div>

                        <!-- Medication Table -->
                        <div class="p-8 lg:p-12">
                            <table class="w-full text-left mb-8">
                                <thead>
                                    <tr class="border-b-2 border-gray-100 text-xs font-bold uppercase text-gray-400">
                                        <th class="py-4">Medicine & Timing</th>
                                        <th class="py-4 text-center">Dosage</th>
                                        <th class="py-4 text-center">Frequency</th>
                                        <th class="py-4 text-right">Duration</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr>
                                        <td class="py-6">
                                            <p class="font-bold text-lg text-blue-600"><?php echo htmlspecialchars($presc['medicine_name']); ?></p>
                                            <p class="text-sm text-gray-500 mt-1 italic"><?php echo htmlspecialchars($presc['timing']); ?></p>
                                        </td>
                                        <td class="py-6 text-center font-bold text-gray-700"><?php echo htmlspecialchars($presc['dosage']); ?></td>
                                        <td class="py-6 text-center font-bold text-gray-700"><?php echo htmlspecialchars($presc['frequency']); ?></td>
                                        <td class="py-6 text-right font-bold text-gray-700"><?php echo htmlspecialchars($presc['days']); ?> Days</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Advice -->
                            <div class="pt-8 border-t-2 border-gray-100">
                                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Advice & Remarks</h3>
                                <div class="p-5 bg-blue-50 rounded-xl border border-blue-100">
                                    <p class="text-sm text-gray-700 leading-relaxed italic">
                                        "<?php echo $presc['advice'] ? htmlspecialchars($presc['advice']) : 'No additional advice provided.'; ?>"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-8 bg-gray-900 text-white text-center">
                            <p class="font-bold mb-1">Stay Healthy with <?php echo $hospital['hospital_name']; ?></p>
                            <p class="text-gray-400 text-xs">This is a digitally generated prescription for informational and clinical use.</p>
                        </div>
                    </div>

                    <p class="text-center mt-8 text-gray-400 text-sm no-print">
                        &copy; <?php echo date('Y'); ?> <?php echo $hospital['hospital_name']; ?> Management System.
                    </p>

                </div>
            </main>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
