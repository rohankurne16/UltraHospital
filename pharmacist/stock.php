<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .stock-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .stock-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .progress-bar { height: 6px; border-radius: 3px; background: #e5e7eb; overflow: hidden; }
        .progress-bar .fill { height: 100%; border-radius: 3px; transition: width 0.6s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-gray-900">Current Stock</h1><p class="text-gray-500 text-sm">View real-time stock levels</p></div>
                <div class="flex gap-3">
                    <a href="stock_in.php" class="bg-green-600 text-white px-4 py-2.5 rounded-xl hover:bg-green-700 transition flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-arrow-down"></i> Stock In
                    </a>
                    <a href="stock_out.php" class="bg-red-600 text-white px-4 py-2.5 rounded-xl hover:bg-red-700 transition flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-arrow-up"></i> Stock Out
                    </a>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500">Total Items</p>
                    <p class="text-2xl font-bold text-gray-900">1,284</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500">Total Stock Value</p>
                    <p class="text-2xl font-bold text-green-600">₹8,45,680</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500">Low Stock Items</p>
                    <p class="text-2xl font-bold text-yellow-600">12</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-sm text-gray-500">Out of Stock</p>
                    <p class="text-2xl font-bold text-red-600">8</p>
                </div>
            </div>

            <!-- Stock Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <div class="flex gap-4">
                        <input type="text" placeholder="Search stock..." class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm w-64">
                        <select class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option>All Categories</option>
                            <option>Antibiotics</option>
                            <option>Pain Relief</option>
                        </select>
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700 transition text-sm font-semibold"><i class="fas fa-file-export mr-2"></i>Export</button>
                </div>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Category</th>
                                <th>Batch</th>
                                <th>Stock</th>
                                <th>Min Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="font-semibold">Paracetamol 500mg</span></td>
                                <td>Pain Relief</td>
                                <td>BATCH-001</td>
                                <td><span class="font-semibold">245</span></td>
                                <td>50</td>
                                <td>
                                    <div class="w-32">
                                        <div class="progress-bar"><div class="fill bg-green-500" style="width: 80%"></div></div>
                                        <span class="text-xs text-green-600">In Stock</span>
                                    </div>
                                </td>
                                <td><button class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button></td>
                            </tr>
                            <tr>
                                <td><span class="font-semibold">Amoxicillin 250mg</span></td>
                                <td>Antibiotics</td>
                                <td>BATCH-002</td>
                                <td><span class="font-semibold text-yellow-600">15</span></td>
                                <td>50</td>
                                <td>
                                    <div class="w-32">
                                        <div class="progress-bar"><div class="fill bg-yellow-500" style="width: 30%"></div></div>
                                        <span class="text-xs text-yellow-600">Low Stock</span>
                                    </div>
                                </td>
                                <td><button class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>