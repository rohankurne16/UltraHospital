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

    
    $show_bills = "select * from billing where patient_id='$patient_id' AND delete_flag=0 ORDER BY bill_date DESC";
    $bills_result = $conn->query($show_bills);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills - <?php echo $hospital['hospital_name'] ?></title>
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
                <div class="max-w-7xl mx-auto">
                    
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
                                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Billing History</h1>
                                    <p class="text-gray-500 text-sm">View and manage your medical invoices and payments.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                   

                    <!-- Bills Table -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 dark:bg-neutral-800/50">
                                    <tr class="text-xs font-bold uppercase text-gray-500 dark:text-neutral-400">
                                        <th class="p-4 whitespace-nowrap">Bill No</th>
                                        <th class="p-4 whitespace-nowrap">Date</th>
                                        <th class="p-4 whitespace-nowrap">Service</th>
                                        <th class="p-4 text-center whitespace-nowrap">Qty</th>
                                        <th class="p-4 text-right whitespace-nowrap">Total</th>
                                        <th class="p-4 text-right whitespace-nowrap">Paid</th>
                                        <th class="p-4 text-right whitespace-nowrap">Pending</th>
                                        <th class="p-4 text-center whitespace-nowrap">Status</th>
                                        <th class="p-4 text-right whitespace-nowrap">View Bill</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-neutral-800">
                                    <?php if($bills_result && $bills_result->num_rows > 0): ?>
                                        <?php while($row = $bills_result->fetch_assoc()): ?>
                                        <tr class="text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/30 transition-colors">
                                            <td class="p-4 font-bold text-blue-600 dark:text-blue-400">
                                                #<?php echo $row['bill_no']; ?>
                                            </td>
                                            <td class="p-4 text-gray-600 dark:text-neutral-400">
                                                <?php echo date('M d, Y', strtotime($row['bill_date'])); ?>
                                            </td>
                                            <td class="p-4">
                                                <div class="font-semibold"><?php echo $row['service_name']; ?></div>
                                                <div class="text-[10px] text-gray-400 uppercase"><?php echo $row['payment_mode']; ?></div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <?php echo $row['qty']; ?>
                                            </td>
                                            <td class="p-4 text-right font-semibold">
                                                $<?php echo number_format($row['total'], 2); ?>
                                            </td>
                                            <td class="p-4 text-right text-green-600 font-medium">
                                                $<?php echo number_format($row['paid_amount'], 2); ?>
                                            </td>
                                            <td class="p-4 text-right <?php echo $row['pending_amount'] > 0 ? 'text-red-500' : 'text-gray-400'; ?> font-medium">
                                                $<?php echo number_format($row['pending_amount'], 2); ?>
                                            </td>
                                            <td class="p-4 text-center">
                                                <?php if($row['pending_amount'] <= 0): ?>
                                                    <span class="px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-600 text-[10px] font-bold uppercase tracking-wider">Paid</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-600 text-[10px] font-bold uppercase tracking-wider">Partial</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button title="View Details" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" onclick="window.location.href='view_bill_detail.php?id=<?php echo $row['id'] ?>'">
                                                     <span>   <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg><a>View Bill</a></button></span>
                                                    </button>
                                                  
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="p-12 text-center">
                                                <div class="size-16 rounded-full bg-gray-100 dark:bg-neutral-800 flex items-center justify-center mx-auto mb-4">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                                </div>
                                                <h3 class="text-lg font-bold mb-1">No Bills Found</h3>
                                                <p class="text-gray-500 text-sm">You don't have any billing records at the moment.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 p-6 rounded-xl shadow-sm">
                            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Total Billed</p>
                            <?php 
                                $total_q = "select sum(total) as grand_total FROM billing where patient_id='$patient_id' AND delete_flag=0";
                                $total_res = $conn->query($total_q);
                                $total_row = $total_res->fetch_assoc();
                            ?>
                            <h2 class="text-2xl font-bold">$<?php echo number_format($total_row['grand_total'] ?? 0, 2); ?></h2>
                        </div>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 p-6 rounded-xl shadow-sm border-l-4 border-l-green-500">
                            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Total Paid</p>
                            <?php 
                                $paid_q = "select sum(paid_amount) as grand_paid FROM billing where patient_id='$patient_id' AND delete_flag=0";
                                $paid_res = $conn->query($paid_q);
                                $paid_row = $paid_res->fetch_assoc();
                            ?>
                            <h2 class="text-2xl font-bold text-green-600">$<?php echo number_format($paid_row['grand_paid'] ?? 0, 2); ?></h2>
                        </div>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 p-6 rounded-xl shadow-sm border-l-4 border-l-red-500">
                            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Total Pending</p>
                            <?php 
                                $pend_q = "select sum(pending_amount) as grand_pend FROM billing where patient_id='$patient_id' AND delete_flag=0";
                                $pend_res = $conn->query($pend_q);
                                $pend_row = $pend_res->fetch_assoc();
                            ?>
                            <h2 class="text-2xl font-bold text-red-500">$<?php echo number_format($pend_row['grand_pend'] ?? 0, 2); ?></h2>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
