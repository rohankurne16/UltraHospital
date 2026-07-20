<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-['Inter']">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-lock text-4xl text-red-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">Access Denied</h1>
            <p class="text-gray-600 mb-6">You don't have permission to access this page. Please contact your administrator.</p>
            <div class="space-y-3">
                <a href="<?php 
                    $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
                   if ($role == 'superadmin') {
                        echo 'superadmin/dashboard.php';

                    } elseif ($role == 'admin') {
                        echo 'dashboard.php';

                    } elseif ($role == 'doctor') {
                        echo 'doctors/dashboard.php';

                    } elseif (in_array($role, ['nurse', 'receptionist', 'lab_technician', 'pharmacist', 'staff'])) {
                        echo 'staff/staff_dashboard.php';

                    } else {
                        echo 'index.php';
                }
                ?>" class="block w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Go Back to Dashboard
                </a>
                <a href="logout.php" class="block w-full border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html>