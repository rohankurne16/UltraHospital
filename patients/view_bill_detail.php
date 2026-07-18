<?php 
    session_start();
    include '../config/hospital.php'; 
    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location:view_bills.php");
        exit();
    }

    $bill_id = mysqli_real_escape_string($conn, $_GET['id']);
    $register_id = $_SESSION["id"];

    $bill_query = "select b.*, p.patient_name, p.email, p.mobile, p.address  from billing b  join patients p ON b.patient_id = p.patient_id  where b.id = '$bill_id' and p.register_id = '$register_id' and b.delete_flag = 0";
    
    $result = $conn->query($bill_query);
    
    if ($result->num_rows == 0) {
        echo "Bill not found or access denied.";
        exit();
    }

    $bill = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #<?php echo $bill['bill_no']; ?> - <?php echo $hospital['hospital_name'] ?></title>
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
                    
                    <!-- Navigation & Actions -->
                    <div class="flex items-center justify-between mb-8 no-print">
                        <a href="view_bills.php" class="text-gray-500 hover:text-gray-700 dark:text-neutral-400 dark:hover:text-neutral-200 flex items-center gap-2 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            Back to Bills
                        </a>
                        <div class="flex gap-3">
                            <button onclick="window.print()" class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 text-gray-700 dark:text-neutral-200 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 hover:bg-gray-50 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                Print Invoice
                            </button>
                        </div>
                    </div>

                    <!-- Invoice Card -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-xl overflow-hidden print-shadow-none">
                        <!-- Invoice Header -->
                        <div class="p-8 lg:p-12 border-b border-gray-100 dark:border-neutral-800 flex flex-col md:flex-row justify-between gap-8">
                            <div>
                                <div class="flex items-center gap-2 mb-6">
                                    <img src="../<?php echo $hospital['hospital_logo']; ?>" height="180" width="180" >
                                </div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Invoice From</h2>
                                <p class="font-bold"><?php echo $hospital['hospital_name']; ?></p>
                                <p class="text-gray-500 text-sm"><?php echo $hospital['address']; ?></p>
                                <p class="text-gray-500 text-sm">Contact: +91 <?php echo $hospital['phone']; ?></p>
                            </div>
                            <div class="md:text-right">
                                <h1 class="text-4xl font-black text-gray-200 dark:text-neutral-800 mb-4">INVOICE</h1>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Bill Number</p>
                                <p class="font-black text-xl text-blue-600 mb-4">#<?php echo $bill['bill_no']; ?></p>
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-1">Date Issued</p>
                                <p class="font-bold"><?php echo date('F d, Y', strtotime($bill['bill_date'])); ?></p>
                            </div>
                        </div>

                        <!-- Client Info -->
                        <div class="px-8 lg:px-12 py-8 bg-gray-50/50 dark:bg-neutral-800/30 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Bill To</h2>
                                <p class="font-black text-lg"><?php echo $bill['patient_name']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $bill['address'] ?: 'N/A'; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $bill['mobile']; ?></p>
                                <p class="text-gray-600 dark:text-neutral-400 text-sm"><?php echo $bill['email']; ?></p>
                            </div>
                            <div class="md:text-right">
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Payment Details</h2>
                                <p class="text-sm text-gray-600 dark:text-neutral-400"><span class="font-bold text-gray-900 dark:text-white">Method:</span> <?php echo $bill['payment_mode']; ?></p>
                                <p class="text-sm text-gray-600 dark:text-neutral-400"><span class="font-bold text-gray-900 dark:text-white">Status:</span> 
                                    <?php if($bill['pending_amount'] <= 0): ?>
                                        <span class="text-green-600 font-bold">FULLY PAID</span>
                                    <?php else: ?>
                                        <span class="text-yellow-600 font-bold">PARTIALLY PAID</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="p-8 lg:p-12">
                            <table class="w-full text-left mb-8">
                                <thead>
                                    <tr class="border-b-2 border-gray-100 dark:border-neutral-800 text-xs font-bold uppercase text-gray-400">
                                        <th class="py-4">Service Description</th>
                                        <th class="py-4 text-center">Qty</th>
                                        <th class="py-4 text-right">Rate</th>
                                        <th class="py-4 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-neutral-800">
                                    <tr>
                                        <td class="py-6">
                                            <p class="font-bold text-lg"><?php echo $bill['service_name']; ?></p>
                                            <p class="text-sm text-gray-500 mt-1"><?php echo $bill['remark'] ?: 'No additional remarks.'; ?></p>
                                        </td>
                                        <td class="py-6 text-center font-bold"><?php echo $bill['qty']; ?></td>
                                        <td class="py-6 text-right">$<?php echo number_format($bill['rate'], 2); ?></td>
                                        <td class="py-6 text-right font-bold">$<?php echo number_format($bill['rate'] * $bill['qty'], 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Calculation -->
                            <div class="flex flex-col md:flex-row justify-between gap-8 pt-8 border-t-2 border-gray-100 dark:border-neutral-800">
                                <div class="max-w-xs">
                                    <h3 class="font-bold mb-2">Important Note:</h3>
                                    <p class="text-sm text-gray-500 italic">Please keep this invoice for your medical insurance claims and tax records. For any queries regarding this bill, contact our billing department.</p>
                                </div>
                                <div class="w-full md:w-64 space-y-3">
                                    <div class="flex justify-between text-gray-500">
                                        <span>Subtotal</span>
                                        <span>$<?php echo number_format($bill['total'] + $bill['discount'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-red-500">
                                        <span>Discount</span>
                                        <span>-$<?php echo number_format($bill['discount'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between font-black text-xl pt-3 border-t dark:border-neutral-800">
                                        <span>Total</span>
                                        <span class="text-blue-600">$<?php echo number_format($bill['total'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-green-600 font-bold">
                                        <span>Amount Paid</span>
                                        <span>$<?php echo number_format($bill['paid_amount'], 2); ?></span>
                                    </div>
                                    <?php if($bill['pending_amount'] > 0): ?>
                                    <div class="flex justify-between text-red-600 font-black p-3 bg-red-50 dark:bg-red-900/10 rounded-lg">
                                        <span>Balance Due</span>
                                        <span>$<?php echo number_format($bill['pending_amount'], 2); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-8 bg-gray-900 text-white text-center">
                            <p class="font-bold mb-1">Thank you for choosing <?php echo $hospital['hospital_name']; ?></p>
                            <p class="text-gray-400 text-xs">This is a computer-generated invoice and does not require a physical signature.</p>
                        </div>
                    </div>

                  

                </div>
            </main>
        </div>
    </div>
</body>
</html>
