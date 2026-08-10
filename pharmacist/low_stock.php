<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alerts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .alert-card { background: white; border-radius: 12px; padding: 1.25rem; border-left: 4px solid #f59e0b; transition: all 0.2s; }
        .alert-card:hover { transform: translateX(4px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .alert-card.critical { border-left-color: #ef4444; }
        .alert-card.warning { border-left-color: #f59e0b; }
        .alert-card.info { border-left-color: #3b82f6; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.critical { background: #fee2e2; color: #991b1b; }
        .status-badge.warning { background: #fef3c7; color: #92400e; }
        .status-badge.info { background: #dbeafe; color: #1d4ed8; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; }
        tr:hover { background: #f8fafc; }
        .search-box { position: relative; }
        .search-box input { padding-left: 40px; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .progress-bar { height: 8px; border-radius: 4px; background: #e5e7eb; overflow: hidden; }
        .progress-bar .fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
        .action-btn { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-primary { background: #3b82f6; color: white; }
        .action-btn-primary:hover { background: #2563eb; }
        .action-btn-success { background: #22c55e; color: white; }
        .action-btn-success:hover { background: #16a34a; }
        .action-btn-danger { background: #ef4444; color: white; }
        .action-btn-danger:hover { background: #dc2626; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <a href="../dashboard.php" class="hover:text-blue-600">Dashboard</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <a href="index.php" class="hover:text-blue-600">Alerts</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-gray-700">Low Stock</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Low Stock Alerts</h1>
                    <p class="text-gray-500 text-sm">Items that need immediate attention</p>
                </div>
                <div class="flex gap-3">
                    <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                    <button class="bg-gray-600 text-white px-5 py-2.5 rounded-xl hover:bg-gray-700 transition flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Critical Stock</p>
                            <p class="text-2xl font-bold text-red-600">8</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center"><i class="fas fa-exclamation-circle text-lg"></i></div>
                    </div>
                    <p class="text-xs text-red-600 mt-1">⚠️ Below 25% of min stock</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Warning Stock</p>
                            <p class="text-2xl font-bold text-yellow-600">12</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-lg"></i></div>
                    </div>
                    <p class="text-xs text-yellow-600 mt-1">⚠️ Below minimum stock level</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Out of Stock</p>
                            <p class="text-2xl font-bold text-gray-600">5</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center"><i class="fas fa-times-circle text-lg"></i></div>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">⚠️ Completely out of stock</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Value at Risk</p>
                            <p class="text-2xl font-bold text-orange-600">₹1,85,400</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-rupee-sign text-lg"></i></div>
                    </div>
                    <p class="text-xs text-orange-600 mt-1">⚠️ Potential revenue loss</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px] search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search medicines..." class="w-full px-4 py-2.5 pl-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <select class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">All Categories</option>
                            <option>Antibiotics</option>
                            <option>Pain Relief</option>
                            <option>Vitamins</option>
                            <option>Cardiac</option>
                        </select>
                    </div>
                    <div>
                        <select class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">All Status</option>
                            <option>Critical</option>
                            <option>Warning</option>
                            <option>Out of Stock</option>
                        </select>
                    </div>
                    <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition text-sm font-semibold"><i class="fas fa-filter mr-2"></i>Apply</button>
                </div>
            </div>

            <!-- Alert Cards -->
            <div class="space-y-3 mb-6">
                <!-- Critical Alert -->
                <div class="alert-card critical">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fas fa-exclamation-circle text-xl"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="font-semibold text-gray-800 text-lg">Amoxicillin 250mg</h3>
                                    <span class="status-badge critical">Critical</span>
                                </div>
                                <p class="text-sm text-gray-500">Category: Antibiotics | Batch: BATCH-002</p>
                                <div class="mt-2 flex flex-wrap items-center gap-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Current Stock: </span>
                                        <span class="font-bold text-red-600 text-lg">5</span>
                                        <span class="text-sm text-gray-500"> / Min: 50</span>
                                    </div>
                                    <div class="w-48">
                                        <div class="progress-bar">
                                            <div class="fill bg-red-500" style="width: 10%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-red-600 font-semibold">10% of min stock</span>
                                </div>
                                <div class="mt-2 text-sm text-gray-500">
                                    <i class="fas fa-clock mr-1"></i> Last ordered: 10 Jan 2025
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart mr-1"></i> Order Now</button>
                            <button class="action-btn action-btn-primary"><i class="fas fa-eye mr-1"></i> View</button>
                        </div>
                    </div>
                </div>

                <!-- Warning Alert -->
                <div class="alert-card warning">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fas fa-exclamation-triangle text-xl"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="font-semibold text-gray-800 text-lg">Metformin 500mg</h3>
                                    <span class="status-badge warning">Warning</span>
                                </div>
                                <p class="text-sm text-gray-500">Category: Diabetes | Batch: BATCH-005</p>
                                <div class="mt-2 flex flex-wrap items-center gap-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Current Stock: </span>
                                        <span class="font-bold text-yellow-600 text-lg">15</span>
                                        <span class="text-sm text-gray-500"> / Min: 50</span>
                                    </div>
                                    <div class="w-48">
                                        <div class="progress-bar">
                                            <div class="fill bg-yellow-500" style="width: 30%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-yellow-600 font-semibold">30% of min stock</span>
                                </div>
                                <div class="mt-2 text-sm text-gray-500">
                                    <i class="fas fa-clock mr-1"></i> Last ordered: 5 Jan 2025
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart mr-1"></i> Order Now</button>
                            <button class="action-btn action-btn-primary"><i class="fas fa-eye mr-1"></i> View</button>
                        </div>
                    </div>
                </div>

                <!-- Out of Stock Alert -->
                <div class="alert-card info">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fas fa-times-circle text-xl"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="font-semibold text-gray-800 text-lg">Vitamin C 1000mg</h3>
                                    <span class="status-badge info">Out of Stock</span>
                                </div>
                                <p class="text-sm text-gray-500">Category: Vitamins | Batch: BATCH-003</p>
                                <div class="mt-2 flex flex-wrap items-center gap-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Current Stock: </span>
                                        <span class="font-bold text-red-600 text-lg">0</span>
                                        <span class="text-sm text-gray-500"> / Min: 30</span>
                                    </div>
                                    <div class="w-48">
                                        <div class="progress-bar">
                                            <div class="fill bg-gray-300" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-red-600 font-semibold">Out of Stock!</span>
                                </div>
                                <div class="mt-2 text-sm text-gray-500">
                                    <i class="fas fa-clock mr-1"></i> Last ordered: 20 Dec 2024
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart mr-1"></i> Order Now</button>
                            <button class="action-btn action-btn-primary"><i class="fas fa-eye mr-1"></i> View</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table View -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">All Low Stock Items</h3>
                    <span class="text-sm text-gray-500">25 items found</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Category</th>
                                <th>Batch</th>
                                <th>Current</th>
                                <th>Min Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="font-semibold">Amoxicillin 250mg</span></td>
                                <td>Antibiotics</td>
                                <td>BATCH-002</td>
                                <td><span class="font-bold text-red-600">5</span></td>
                                <td>50</td>
                                <td><span class="status-badge critical">Critical</span></td>
                                <td>
                                    <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart"></i></button>
                                    <button class="action-btn action-btn-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="font-semibold">Metformin 500mg</span></td>
                                <td>Diabetes</td>
                                <td>BATCH-005</td>
                                <td><span class="font-bold text-yellow-600">15</span></td>
                                <td>50</td>
                                <td><span class="status-badge warning">Warning</span></td>
                                <td>
                                    <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart"></i></button>
                                    <button class="action-btn action-btn-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="font-semibold">Vitamin C 1000mg</span></td>
                                <td>Vitamins</td>
                                <td>BATCH-003</td>
                                <td><span class="font-bold text-gray-400">0</span></td>
                                <td>30</td>
                                <td><span class="status-badge info">Out of Stock</span></td>
                                <td>
                                    <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart"></i></button>
                                    <button class="action-btn action-btn-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="font-semibold">Ibuprofen 400mg</span></td>
                                <td>Pain Relief</td>
                                <td>BATCH-008</td>
                                <td><span class="font-bold text-yellow-600">18</span></td>
                                <td>40</td>
                                <td><span class="status-badge warning">Warning</span></td>
                                <td>
                                    <button class="action-btn action-btn-success"><i class="fas fa-shopping-cart"></i></button>
                                    <button class="action-btn action-btn-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing 1-10 of 25 low stock items</p>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm"><i class="fas fa-chevron-left"></i></button>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">1</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">2</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">3</button>
                        <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>