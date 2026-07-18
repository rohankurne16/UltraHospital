<?php
    include("../config/hospital.php");

    $id=$_SESSION['id'];

    $patient_info="select * from patients where register_id='$id'";
    $patient_data=$conn->query($patient_info);
    $res=$patient_data->fetch_assoc();

    $patient_image=$res["patient_image"];
    $patient_name=$res["patient_name"];
 


?>
<aside class="!fixed h-full left-0 bottom-0 z-50 flex w-64 flex-col border-r bg-white transition-transform duration-300 ease-in-out translate-x-0 hidden xl:flex">
                <div class="flex py-4 items-center justify-between px-6">
                    <a class="flex items-center space-x-2" href="dashboard.php">
                        <div>
                           <img src="../<?php echo $hospital['hospital_logo']; ?>" height="70" width="70" >
                        </div>
                        <span class="font-bold inline-block text-xl tracking-tight"><?php echo $hospital['hospital_name']; ?></span>
                    </a>
                </div>
                <div class="flex-1 py-4 border-t h-full overflow-y-auto custom-scrollbar">
                    <nav class="space-y-1 px-3">
                        <a href="dashboard.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors bg-blue-50 text-blue-600">
                            <i data-lucide="layout-dashboard" class="mr-3 h-4 w-4"></i>Dashboard
                        </a>
                        
                        <div class="pt-4 pb-2 px-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Medical Services</p>
                        </div>
                        <a href="show_patient_appointments.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i data-lucide="calendar" class="mr-3 h-4 w-4"></i>Appointments
                        </a>
                        <a href="show_my_prescriptions.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i data-lucide="pill" class="mr-3 h-4 w-4"></i>Prescriptions
                        </a>
                        <a href="show_lab_reports.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i data-lucide="microscope" class="mr-3 h-4 w-4"></i>Lab Reports
                        </a>

                        <div class="pt-4 pb-2 px-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Financials</p>
                        </div>
                        <a href="view_bills.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i data-lucide="receipt-text" class="mr-3 h-4 w-4"></i>Billing & Payments
                        </a>

                        <div class="pt-4 pb-2 px-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Account</p>
                        </div>
                        <a href="show_my_docs.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i data-lucide="folder" class="mr-3 h-4 w-4"></i>Documents
                        </a>
                        <a href="profile.php" class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i data-lucide="user" class="mr-3 h-4 w-4"></i>Profile 
                        </a>
                    </nav>
                </div>
                <div class="border-t p-4 shrink-0">
        <div class="flex items-center gap-3">
            <?php if (isset($patient_image)): ?>
                <img src="<?php echo $patient_image; ?>" class="w-10 h-10 rounded-full object-cover">
            <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                    <?php echo isset($patient_name) ? strtoupper(substr($patient_name, 0, 1)) : 'D'; ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?php echo isset($patient_name) ? $patient_name : 'Patient'; ?></p>
                <p class="text-xs text-gray-500 truncate">Patient</p>
            </div>
        </div>
    </div>
            </aside>
            