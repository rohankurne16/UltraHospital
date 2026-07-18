<?php 
    session_start();
    include '../config/hospital.php'; 

    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../departments_list.php");
        exit();
    }

    $dept_id = $_GET['id'];

    $find_dept_id = "select * from department where id = '$dept_id' and delete_flag = 0";
    $dept_res = $conn->query($find_dept_id);
    $dept = $dept_res->fetch_assoc();

    if (!$dept) {
        echo "Department not found.";
        exit();
    }

    $dept_name = $dept['department_name'];
    $find_doctors = "select * from doctor where department = '$dept_name' and delete_flag = 0 order by doctor_name";
    $doctors_res = $conn->query($find_doctors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dept['department_name']; ?> Details - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
         <?php include('staff_header.php') ?>
        
        <div class="flex flex-1 items-start">
            <?php include('staff_sidebar.php') ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">
                    
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <a href="../staff/departments_list.php" class="inline-flex items-center justify-center rounded-md border border-input bg-white hover:bg-gray-100 size-10 transition-colors dark:bg-neutral-900 dark:border-neutral-800 dark:hover:bg-neutral-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1"><?php echo $dept['department_name']; ?></h1>
                                <p class="text-gray-500 text-sm">Department Overview and Staff Directory</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                          <!--  <a href="edit_department.php?id=<?php echo $dept['id']; ?>" class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 text-gray-700 dark:text-neutral-200 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                Edit Details
                            </a> -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-6 shadow-sm">
                                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-6">Department Info</h3>
                                <div class="space-y-4">
                                   
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-medium">Status</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $dept['status'] === 'Active' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'; ?>">
                                            <?php echo $dept['status']; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-medium">Description</p>
                                        <p class="text-sm text-gray-600 dark:text-neutral-400 leading-relaxed italic">
                                            "<?php echo $dept['description'] ?: 'No description provided for this department.'; ?>"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-sm overflow-hidden">
                                <div class="p-6 border-b dark:border-neutral-800 flex items-center justify-between">
                                    <h2 class="text-xl font-bold">Medical Staff Directory</h2>
                                    <span class="text-xs font-bold bg-gray-100 dark:bg-neutral-800 px-3 py-1 rounded-full text-gray-500">
                                        <?php echo $doctors_res->num_rows; ?> Doctors Assigned
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="bg-gray-50 dark:bg-neutral-800/50">
                                            <tr class="text-xs font-bold uppercase text-gray-500 dark:text-neutral-400">
                                                <th class="p-4">Doctor Name</th>
                                                <th class="p-4 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y dark:divide-neutral-800">
                                            <?php if ($doctors_res->num_rows > 0): ?>
                                                <?php while($doc = $doctors_res->fetch_assoc()): ?>
                                                <tr class="text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/30 transition-colors">
                                                    <td class="p-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                                            </div>
                                                            <div>
                                                                <p class="font-bold">Dr. <?php echo $doc['doctor_name']; ?></p>
                                                                <p class="text-xs text-gray-500"><?php echo $doc['specialization'] ?: 'General Practitioner'; ?></p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="p-4 text-right">
                                                        <a href="../doctors/view_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="text-blue-600 hover:text-blue-800 font-bold text-xs">View Profile</a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="2" class="p-12 text-center text-gray-500 italic">
                                                        No doctors are currently assigned to this department.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
