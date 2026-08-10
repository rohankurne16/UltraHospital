<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .prescription-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .prescription-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59,130,246,0.08); }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.dispensed { background: #dcfce7; color: #166534; }
        .status-badge.partial { background: #dbeafe; color: #1d4ed8; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-gray-900">Prescriptions</h1><p class="text-gray-500 text-sm">Manage all prescriptions</p></div>
                <div class="flex gap-3">
                    <a href="pending.php" class="bg-yellow-600 text-white px-5 py-2.5 rounded-xl hover:bg-yellow-700 transition flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-clock"></i> Pending (5)
                    </a>
                    <a href="dispense.php" class="bg-green-600 text-white px-5 py-2.5 rounded-xl hover:bg-green-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-green-500/20">
                        <i class="fas fa-prescription-bottle"></i> Dispense
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" placeholder="Search by RX # or Patient..." class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <select class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">All Status</option>
                            <option>Pending</option>
                            <option>Dispensed</option>
                            <option>Partial</option>
                            <option>Cancelled</option>
                        </select>
                    </div>
                    <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition text-sm font-semibold"><i class="fas fa-filter mr-2"></i>Apply</button>
                </div>
            </div>

            <!-- Prescription Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="prescription-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-blue-600">#RX-2025-0015</p>
                            <p class="font-semibold text-gray-800">Rahul Sharma</p>
                            <p class="text-xs text-gray-500">Dr. Sanket Pawar • 15 Jan 2025</p>
                        </div>
                        <span class="status-badge pending">Pending</span>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm text-gray-600">Medicines: Paracetamol 500mg, Amoxicillin 250mg</p>
                        <p class="text-xs text-gray-400">3 items • 7 days supply</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">View Details</button>
                        <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition">Dispense</button>
                    </div>
                </div>

                <div class="prescription-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-blue-600">#RX-2025-0014</p>
                            <p class="font-semibold text-gray-800">Priya Patel</p>
                            <p class="text-xs text-gray-500">Dr. Ayush Nipane • 14 Jan 2025</p>
                        </div>
                        <span class="status-badge dispensed">Dispensed</span>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm text-gray-600">Medicines: Metformin 500mg, Vitamin C 1000mg</p>
                        <p class="text-xs text-gray-400">2 items • 15 days supply</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">View Details</button>
                        <button class="px-4 py-2 bg-gray-200 text-gray-600 rounded-lg text-sm font-semibold cursor-not-allowed" disabled>Dispensed</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>