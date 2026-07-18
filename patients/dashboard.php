<?php 
session_start();
include "../config/hospital.php";

if(!isset($_SESSION["id"])) {
    header("Location:../index.php");
}

if($_SESSION['id']){

$id = $_SESSION['id'];


$view_patient = "select * from patients where register_id='$id' and(delete_flag=0 or delete_flag is null)";

$data= $conn->query($view_patient);
if($data->num_rows > 0){

    while($row = $data->fetch_assoc()) {

        $_SESSION['patient_id'] = $row['patient_id'];
        $_SESSION['patient_name'] = $row['patient_name'];
        $_SESSION['patient_image'] = $row['patient_image'];
        $_SESSION['dob'] = $row['date_of_birth'];
        $_SESSION['age'] = $row['age'];
        $_SESSION['blood_group'] = $row['blood_group'];
        $_SESSION['gender'] = $row['gender'];
        $_SESSION['address'] = $row['address'];
        $_SESSION['emergency_contact'] = $row['emergency_contact'];
        $_SESSION['medical_history'] = $row['medical_history'];
        $_SESSION['medications'] = explode(',', $row['medical_history']);
        $_SESSION['allergy'] = $row['allergy'];
        $_SESSION['allergies'] = explode(',', $row['allergy']);
        $_SESSION['email'] = $row['email'];
        $_SESSION['mobile'] = $row['mobile'];
        $_SESSION['status'] = isset($row['status']) ? $row['status'] : 'Active';
        $_SESSION['status_class'] = ($_SESSION['status'] == 'Active') ? 'status-active' : 'status-inactive';

       $patient_id= $_SESSION['patient_id'];
        
       
        date_default_timezone_set('Asia/Kolkata');
        $hour = date('H'); 
        if ($hour < 12) {
            $greeting = "Good Morning";
        } elseif ($hour < 17) {
            $greeting = "Good Afternoon";
        } elseif ($hour < 20) {
            $greeting = "Good Evening";
        } else {
            $greeting = "Good Night";
        }

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Patient Dashboard</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #3b82f6; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include "header.php"?>

        <div class="flex flex-1 items-start">
            <!-- Sidebar Navigation -->
            <?php include "Sidebar.php" ?>

            <!-- Main Content Area -->
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    <!-- Welcome Section -->
                    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"> <?php echo $greeting ."!  " . $_SESSION['name']; ?></h1>
                            <p class="text-gray-500">Manage your appointments, health records, and billing in one place.</p>
                        </div>
                        <div class="flex gap-3">
                            
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 shadow-md transition flex items-center gap-2" onclick="window.location.href='book_appointment.php'">
                                <i data-lucide="plus" class="w-4 h-4"></i> Book Appointment
                            </button>
                        </div>
                    </div>

                    <!-- Dashboard Overview Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <!-- Left & Middle: Main Features -->
                        <div class="lg:col-span-2 space-y-8">
                            
                            <!-- Upcoming Appointments -->
                            <div class="bg-white rounded-xl border shadow-sm">
                                <div class="p-5 border-b flex items-center justify-between">
                                    <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
                                        Upcoming Appointments
                                    </h2>
                                    <button class="text-xs text-blue-600 font-semibold hover:underline" onclick="window.location.href='show_patient_appointments.php'">Manage All</button>
                                </div>
                                <div class="p-5">
                                    <div class="space-y-4">
                                        <!-- Appointment Item -->
                                        <?php 
                                            $show_my_appointments="SELECT * FROM appointments WHERE patient_id='$patient_id' AND appointment_date>=CURDATE() AND status NOT IN('Completed','Cancelled') AND (delete_flag=0 OR delete_flag IS NULL) ORDER BY appointment_date ASC, appointment_time ASC;";

                                            $my_appointments=$conn->query($show_my_appointments);
                                            if($my_appointments->num_rows>0){
                                                while($row = $my_appointments->fetch_assoc()){

                                               $doctor_id=$row['doctor_id'];
                                               $getdoctorimg="select doctor_image,doctor_name from doctor where doctor_id='$doctor_id'";
                                               $drimg=$conn->query($getdoctorimg);
                                               $img=$drimg->fetch_assoc();                                        
                                        ?>

                                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-blue-200 transition">
                                            <div class="flex items-center gap-4">
                                              <div class="w-12 h-12 rounded-full border border-gray-300 overflow-hidden flex items-center justify-center bg-gray-100">
                                                    <?php if (!empty($img['doctor_image'])) { ?>
                                                        <img src="<?php echo $img['doctor_image']; ?>"
                                                            alt="Doctor Profile"
                                                            class="w-full h-full object-cover">
                                                    <?php } else { ?>
                                                        <i data-lucide="user" class="w-6 h-6 text-gray-500"></i>
                                                    <?php } ?>
                                                </div>
                                              
                                                <div>
                                                    <p class="text-sm font-bold text-gray-900"><?php echo $img['doctor_name']; ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $row['department']; ?>• <?php echo $row['appointment_type']; ?></p>
                                                    <div class="mt-1 flex items-center gap-3 text-[10px] text-gray-400">
                                                        <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i><?php echo $row['appointment_date']; ?> </span>
                                                        <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?php echo $row['appointment_time']; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <button class="p-2 text-gray-400 hover:text-red-600 transition" title="Cancel Appointment" onclick="if(confirm('Are you sure you want to cancel this appointment?')) { window.location.href='cancel_my_appointment.php?id=<?php echo $row['appointment_id']; ?>'; }">
                                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                                </button>
                                                <button class="px-3 py-1.5 bg-white border rounded-md text-xs font-medium hover:bg-gray-50 transition shadow-sm" onclick="window.location.href='reschedule_appointment.php?id=<?php echo $row['appointment_id'] ?>'">
                                                    Reschedule
                                                </button>
                                            </div>
                                        </div>
                                        <?php }}else{  ?>
                                                  <p>No Upcoming Appointments</p>         
                                       <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Latest Prescriptions & Lab Reports -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Prescriptions -->
                                <div class="bg-white rounded-xl border shadow-sm">
                                    <div class="p-5 border-b flex items-center justify-between">
                                        <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                            <i data-lucide="pill" class="w-5 h-5 text-purple-600"></i>
                                            Latest Prescriptions
                                        </h2>
                                    </div>
                                    <div class="p-5 space-y-4">
                                        <?php
                                            $show_my_prescriptions="select * from prescriptions where patient_id='$patient_id' and (delete_flag=0 and delete_flag is null)";

                                            $my_prescriptions=$conn->query($show_my_prescriptions);
                                            if($my_prescriptions->num_rows> 0){
                                                while($row = $my_prescriptions->fetch_assoc()){ ?>
                                                    <div class="p-3 bg-purple-50 rounded-lg border border-purple-100">
                                                        <p class="text-sm font-bold text-purple-900"><?php echo $row['medicine_name'] ?></p>
                                                        <p class="text-xs text-purple-700"><?php echo $row['dosage'] . $row['frequency'] ?></p>
                                                      
                                                    </div>
                                                   
                                                    <?php }
                                            }else{ ?>
                                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                                    <p class="text-sm font-bold text-gray-900">No Prescriptions Alloted</p>
                                                   
                                                </div>
                                            <?php }}?>
                            
                                        <button class="w-full py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-md transition" onclick="window.location.href='show_my_prescriptions.php'">View All Prescriptions</button>
                                    </div>
                                </div>

                                <!-- Lab Reports -->
                                <div class="bg-white rounded-xl border shadow-sm">
                                    <div class="p-5 border-b flex items-center justify-between">
                                        <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                            <i data-lucide="microscope" class="w-5 h-5 text-green-600"></i>
                                            Recent Lab Reports
                                        </h2>
                                    </div>
                                    <div class="divide-y">
                                        <?php 
                                            $show_my_reports="select * from lab_report where patient_id='$patient_id' and(delete_flag=0 and delete_flag is null)";
                                            $result4=$conn->query($show_my_reports);
                                            if($result4->num_rows> 0){
                                                while($row = $result4->fetch_assoc()){
                                            ?>
                                                <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                            <div class="flex items-center gap-3">
                                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                                                <div>
                                                    <p class="text-xs font-bold text-gray-900"><?php echo $row['test_name'] ?></p>
                                                    <p class="text-[10px] text-gray-500"><?php echo $row['report_date'] ?></p>
                                                </div>
                                            </div>
                                            <div class="flex gap-1">
                                              
                                                <button class="p-1.5 hover:bg-gray-200 rounded transition" title="Download"><i data-lucide="download" class="w-3.5 h-3.5 text-gray-600"></i></button>
                                            </div>
                                        </div>
                                

                                                <?php }
                                            }else{ ?>
                                                    <p>No Lab Reports Available</p>
                                            <?php } ?>
                                    </div>
                                    <div class="p-4 border-t">
                                        <button class="w-full py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-md transition" onclick="window.location.href='show_lab_reports.php'">Access All Reports</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents Section -->
                            <div class="bg-white rounded-xl border shadow-sm">
                                <div class="p-5 border-b flex items-center justify-between">
                                    <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i data-lucide="folder" class="w-5 h-5 text-orange-600"></i>
                                        My Documents
                                    </h2>
                                    <button class="text-xs text-blue-600 font-semibold flex items-center gap-1 hover:underline" onclick="window.location.href='add_document.php'">
                                        <i data-lucide="upload" class="w-3 h-3"></i> Upload New
                                    </button>
                                </div>
                                <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <?php
                                           $my_docs_name =  "select * from patient_documents where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null)";

                                           $my_documents_name=$conn->query($my_docs_name);

                                           if($my_documents_name->num_rows> 0){

                                                 while($row = $my_documents_name->fetch_assoc()){

                                                 $docid=$row["document_id"];
                                    
                                            ?> 
                                               <button onclick="window.location.href='view_document.php?id=<?php echo $docid ?>'">
                                                    <div class="p-3 border rounded-lg flex items-center gap-3 hover:bg-gray-50 transition cursor-pointer">
                                                        <i data-lucide="image" class="w-5 h-5 text-blue-500"></i>
                                                        <span class="text-xs font-medium"><?php echo $row['document_name'] ?></span>
                                                    </div>
                                               </button>
                                           <?php } 
                                     } ?>
                                </div>
                            </div>

                        </div>

             
                        <div class="space-y-8">
    
                           <?php 
                                $pending_amount="select coalesce(Sum(pending_amount),0) as pending_sum from billing where patient_id='$patient_id'";
                                $pending_sum=$conn->query($pending_amount);
                                if($pending_sum->num_rows> 0){
                                    while($sum = $pending_sum->fetch_assoc()){
                            ?>

                            <!-- Billing Overview -->
                            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">

                                <div class="p-5 bg-gradient-to-br from-gray-900 to-gray-800 text-white">
                                    <div class="flex items-center justify-between mb-4">
                                        <p class="text-xs font-medium text-gray-400">Total Outstanding</p>
                                        <i data-lucide="credit-card" class="w-4 h-4 text-gray-400"></i>
                                    </div>
                                    <p class="text-3xl font-bold mb-1">₹<?php echo $sum['pending_sum'] ?></p>
                                   
                                </div>
                                <div class="p-5 space-y-4">
                                     <?php 
                                            $billing="select * from billing where patient_id='$patient_id' and (delete_flag=0 or delete_flag is null)";
                                            $bills=$conn->query($billing);
                                            if($bills->num_rows> 0){
                                                while($row = $bills->fetch_assoc()){
                                    ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500"><?php echo $row['service_name'] ?></span>
                                        <span class="font-bold text-gray-900">₹<?php echo $row['total'] ?></span>
                                    </div>
                                    <?php }} ?>
                                    <button class="w-full py-3 bg-blue-600 text-white rounded-lg text-sm font-bold shadow-md hover:bg-blue-700 transition" onclick="window.location.href='view_bills.php'">View All Bills</button>
                                    
                                       
                                </div>
                                <?php }} ?>
                            </div>

                            <!-- Quick Profile Update -->
                            <div class="bg-white p-6 rounded-xl border shadow-sm">
                                <h2 class="font-bold text-gray-900 mb-4">Quick Settings</h2>
                                <div class="space-y-3">
                                    <button  class="w-full flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition group"  onclick="window.location.href='update_profile.php'">
                                        <div class="flex items-center gap-3">
                                            <i data-lucide="user-cog" class="w-4 h-4 text-gray-400 group-hover:text-blue-600"></i>
                                            <span class="text-xs font-medium text-gray-700">Update Profile</span>
                                        </div>
                                        <i data-lucide="chevron-right" class="w-3 h-3 text-gray-300"></i>
                                    </button>
                                    <button class="w-full flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition group" onclick="window.location.href='change_pass.php'">
                                        <div class="flex items-center gap-3">
                                            <i data-lucide="key" class="w-4 h-4 text-gray-400 group-hover:text-blue-600"></i>
                                            <span class="text-xs font-medium text-gray-700">Change Password</span>
                                        </div>
                                        <i data-lucide="chevron-right" class="w-3 h-3 text-gray-300"></i>
                                    </button>
                                </div>
                            </div>

                           
                        </div>
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
<?php }} ?>