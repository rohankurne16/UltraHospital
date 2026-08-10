<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generic Medicines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .generic-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .generic-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .generic-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; }
        tr:hover { background: #f8fafc; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #fee2e2; color: #991b1b; }
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .action-btn:hover { transform: scale(1.05); }
        .search-box { position: relative; }
        .search-box input { padding-left: 40px; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
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
                        <span class="text-gray-700">Generic Medicines</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Generic Medicines</h1>
                    <p class="text-gray-500 text-sm">Manage generic medicine names</p>
                </div>
                <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-blue-500/20">
                    <i class="fas fa-plus"></i> Add Generic
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px] search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search generic medicines..." class="w-full px-4 py-2.5 pl-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                    </div>
                    <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition text-sm font-semibold"><i class="fas fa-filter mr-2"></i>Apply</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="rounded border-gray-300"></th>
                                <th>Generic Name</th>
                                <th>Description</th>
                                <th>Medicines</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="checkbox" class="rounded border-gray-300"></td>
                                <td><span class="font-semibold text-gray-800">Acetaminophen</span></td>
                                <td><span class="text-sm text-gray-600">Pain reliever and fever reducer</span></td>
                                <td><span class="text-sm">24</span></td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>
                                    <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="rounded border-gray-300"></td>
                                <td><span class="font-semibold text-gray-800">Amoxicillin</span></td>
                                <td><span class="text-sm text-gray-600">Antibiotic medication</span></td>
                                <td><span class="text-sm">18</span></td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>
                                    <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="rounded border-gray-300"></td>
                                <td><span class="font-semibold text-gray-800">Ascorbic Acid</span></td>
                                <td><span class="text-sm text-gray-600">Vitamin C supplement</span></td>
                                <td><span class="text-sm">15</span></td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>
                                    <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="rounded border-gray-300"></td>
                                <td><span class="font-semibold text-gray-800">Metformin</span></td>
                                <td><span class="text-sm text-gray-600">Diabetes medication</span></td>
                                <td><span class="text-sm">12</span></td>
                                <td><span class="status-badge inactive">Inactive</span></td>
                                <td>
                                    <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing 1-10 of 45 generic medicines</p>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm"><i class="fas fa-chevron-left"></i></button>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">1</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">2</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">3</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">4</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>