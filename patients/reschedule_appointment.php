<?php
session_start();
include "../config/hospital.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reschedule_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    $reason = mysqli_real_escape_string($conn, $_POST['reschedule_reason']);

  
    $sql = "UPDATE appointments SET appointment_date='$new_date', appointment_time='$new_time', status='Pending' WHERE appointment_id='$appointment_id'";

    if (mysqli_query($conn, $sql)) {

        $message = "Appointment rescheduled successfully.";

        header("Location: show_patient_appointments.php?msg=rescheduled");

        exit();
    } else {
        $error = "Error updating record: " . mysqli_error($conn);
    }
}


if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $res = mysqli_query($conn, "SELECT a.*, p.patient_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_id='$id'");
    
    if (mysqli_num_rows($res) > 0) {
        $appointment = mysqli_fetch_assoc($res);
    } else {
        $error = "Appointment not found.";
    }
} else {
    $error = "Invalid Appointment ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-3xl mx-auto">
                    
                    <!-- Header -->
                    <div class="flex flex-col gap-5 mb-8">
                        <div class="flex items-center gap-4">
                            <a class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:bg-gray-100 dark:hover:bg-neutral-800 size-11 transition-all shadow-sm" href="show_patient_appointments.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Reschedule Appointment</h1>
                                <p class="text-gray-500 dark:text-neutral-400 text-sm">Change the date and time for an existing appointment.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl relative mb-6" role="alert">
                            <span class="block sm:inline"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl relative mb-6" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($appointment)): ?>
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl p-8 shadow-sm">
                        <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-900/30">
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-400 uppercase tracking-wider mb-2">Current Appointment</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-neutral-500">Patient</p>
                                    <p class="font-medium"><?php echo $appointment['patient_name']; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-neutral-500">Scheduled For</p>
                                    <p class="font-medium"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <form action="reschedule_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" method="POST" class="space-y-6">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">New Date</label>
                                    <input type="date" name="new_date" required class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1.5">New Time</label>
                                    <input type="time" name="new_time" required class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                </div>
                            </div>

                           

                            <div class="flex items-center justify-end gap-4 pt-4">
                                <a href="show_patient_appointments.php" class="px-6 py-2.5 rounded-xl border border-gray-200 dark:border-neutral-800 hover:bg-gray-100 dark:hover:bg-neutral-800 font-medium transition-all">Discard</a>
                                <button type="submit" name="reschedule_appointment" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl font-semibold shadow-lg shadow-blue-500/30 transition-all transform active:scale-95">
                                    Confirm Reschedule
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 p-12 rounded-2xl text-center shadow-sm">
                            <div class="bg-red-50 dark:bg-red-900/20 size-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">Appointment Not Found</h3>
                            <p class="text-gray-500 dark:text-neutral-400 mb-8">The appointment record you are looking for could not be found or the ID is invalid.</p>
                            <a href="../appointments.php" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-8 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/25">Return to Appointments</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <?php $conn->close(); ?>
</body>
</html>
