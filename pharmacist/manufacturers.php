<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufacturers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .manufacturer-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .manufacturer-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .manufacturer-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <a href="../dashboard.php" class="hover:text-blue-600">Dashboard</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <a href="list.php" class="hover:text-blue-600">Medicine Master</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-gray-700">Manufacturers</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Manufacturers</h1>
                    <p class="text-gray-500 text-sm">Manage medicine manufacturers</p>
                </div>
                <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-blue-500/20">
                    <i class="fas fa-plus"></i> Add Manufacturer
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="manufacturer-card">
                    <div class="flex items-center gap-4">
                        <div class="manufacturer-icon bg-blue-100 text-blue-700">SP</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Sun Pharma</h3>
                            <p class="text-xs text-gray-500">Contact: Rajesh Mehta</p>
                        </div>
                        <span class="status-badge active ml-auto">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 98765 43210</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@sunpharma.com</p>
                        <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Mumbai, India</p>
                        <p class="text-gray-600"><i class="fas fa-pills mr-2 text-gray-400"></i>45 medicines</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                        <button class="text-green-600 hover:text-green-700"><i class="fas fa-eye"></i></button>
                        <button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <div class="manufacturer-card">
                    <div class="flex items-center gap-4">
                        <div class="manufacturer-icon bg-green-100 text-green-700">CI</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Cipla</h3>
                            <p class="text-xs text-gray-500">Contact: Priya Singh</p>
                        </div>
                        <span class="status-badge active ml-auto">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 87654 32109</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@cipla.com</p>
                        <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Mumbai, India</p>
                        <p class="text-gray-600"><i class="fas fa-pills mr-2 text-gray-400"></i>38 medicines</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                        <button class="text-green-600 hover:text-green-700"><i class="fas fa-eye"></i></button>
                        <button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <div class="manufacturer-card">
                    <div class="flex items-center gap-4">
                        <div class="manufacturer-icon bg-purple-100 text-purple-700">DR</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Dr. Reddy's</h3>
                            <p class="text-xs text-gray-500">Contact: Anand Kumar</p>
                        </div>
                        <span class="status-badge active ml-auto">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 76543 21098</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@drreddys.com</p>
                        <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Hyderabad, India</p>
                        <p class="text-gray-600"><i class="fas fa-pills mr-2 text-gray-400"></i>32 medicines</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                        <button class="text-green-600 hover:text-green-700"><i class="fas fa-eye"></i></button>
                        <button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <div class="manufacturer-card">
                    <div class="flex items-center gap-4">
                        <div class="manufacturer-icon bg-orange-100 text-orange-700">GL</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">GlaxoSmithKline</h3>
                            <p class="text-xs text-gray-500">Contact: Sarah Williams</p>
                        </div>
                        <span class="status-badge inactive ml-auto">Inactive</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 65432 10987</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@gsk.com</p>
                        <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Delhi, India</p>
                        <p class="text-gray-600"><i class="fas fa-pills mr-2 text-gray-400"></i>28 medicines</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                        <button class="text-green-600 hover:text-green-700"><i class="fas fa-eye"></i></button>
                        <button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>