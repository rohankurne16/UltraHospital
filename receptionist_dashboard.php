<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["staff_id"]) || $_SESSION["role"] !== "receptionist") {
    header("Location: auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Receptionist Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'staff_sidebar.php'; ?>

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Receptionist Dashboard</h1>
                    <p class="text-gray-500">Welcome, Receptionist! Here's an overview of today's activities.</p>
                    <!-- Receptionist specific content goes here -->
                    <div class="mt-8 p-6 bg-white rounded-lg shadow">
                        <h2 class="text-xl font-semibold text-gray-800">Daily Overview</h2>
                        <p class="text-gray-600 mt-2">This section will display key information for front desk operations.</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                            <div class="bg-blue-50 p-4 rounded-lg text-blue-800">
                                <i data-lucide="calendar-days" class="w-6 h-6 inline-block mr-2"></i>Appointments Today: 15
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg text-green-800">
                                <i data-lucide="user-plus" class="w-6 h-6 inline-block mr-2"></i>New Registrations: 5
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg text-yellow-800">
                                <i data-lucide="clock" class="w-6 h-6 inline-block mr-2"></i>Pending Check-ins: 3
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
<?php $conn->close(); ?>
