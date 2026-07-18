<?php
session_start();
include "../config/hospital.php";

$message = "";
$error = "";


if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$register_id = $_SESSION["id"];

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['change_password'])) {
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];


    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 2) {
        $error = "Password must be at least 8 characters long.";
    } else {
             
        $update_sql = "UPDATE register SET password='$new_password' WHERE id='$register_id'";
            if ($conn->query($update_sql)) {
               $message = "Password updated successfully.";
            } else {
                $error = "Error updating password: " . $conn->error;
            }
    }
}
    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo $hospital['hospital_name'] ?> </title>
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
        
        <?php include'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-2xl mx-auto">
                    
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
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1"><a href="change_doctor_password.php">Change Password </a></h1>
                                <p class="text-gray-500 text-sm">Update your account security credentials.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form Card -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-lg p-6 shadow-sm">
                        <form action="change_doctor_password.php" method="POST" class="space-y-5">
                            
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-neutral-300">New Password</label>
                                <input type="password" name="new_password" required 
                                    class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                                    placeholder="Minimum 6 characters">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-neutral-300">Confirm New Password</label>
                                <input type="password" name="confirm_password" required 
                                    class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                                    placeholder="Repeat new password">
                            </div>

                            <div class="pt-2">
                                <button type="submit" name="change_password" 
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors shadow-md shadow-blue-500/20">
                                    Update Password
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Security Tips -->
                    <div class="mt-8 p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-lg">
                        <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400 mb-2 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                            Security Tip
                        </h4>
                        <p class="text-xs text-amber-700 dark:text-amber-500 leading-relaxed">
                            Use a strong password that includes a mix of uppercase letters, numbers, and special characters. Avoid using easily guessable information like your name or date of birth.
                        </p>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $conn->close(); ?>
</body>
</html>
