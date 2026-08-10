<?php
// This is a UI-only version - No backend logic
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .stat-card { background: white; border-radius: 16px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .stat-icon i { font-size: 20px; }
        .quick-action-btn { padding: 10px 16px; border-radius: 10px; border: 1px solid #e5e7eb; background: white; transition: all 0.2s; display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; color: #374151; cursor: pointer; text-decoration: none; }
        .quick-action-btn:hover { background: #f8fafc; border-color: #3b82f6; color: #3b82f6; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.15); }
        .quick-action-btn i { font-size: 16px; }
        .activity-item { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
        .activity-item:hover { background: #f8fafc; }
        .progress-bar { height: 6px; border-radius: 3px; background: #e5e7eb; overflow: hidden; }
        .progress-bar .fill { height: 100%; border-radius: 3px; transition: width 0.6s ease; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
        .status-badge.low { background: #fef3c7; color: #92400e; }
        .status-badge.critical { background: #fee2e2; color: #991b1b; }
        .status-badge.in-stock { background: #dcfce7; color: #166534; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include Header and Sidebar -->
    <?php include '../header.php'; ?>
    
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Pharmacy Dashboard</h1>
                    <p class="text-gray-500 text-sm mt-1">Manage your pharmacy operations efficiently</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="medicine/add.php" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-blue-500/20">
                        <i class="fas fa-plus"></i> Add Medicine
                    </a>
                    <a href="purchase/new.php" class="bg-green-600 text-white px-5 py-2.5 rounded-xl hover:bg-green-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-green-500/20">
                        <i class="fas fa-shopping-cart"></i> New Purchase
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Medicines</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">1,284</p>
                        </div>
                        <div class="stat-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-pills"></i>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                        <span class="text-green-600">● 24 categories</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-blue-600">● 18 suppliers</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">This Month's Sales</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">₹2,45,680</p>
                        </div>
                        <div class="stat-icon bg-green-50 text-green-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                        <span class="text-green-600">● 156 invoices</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-blue-600">● Today: ₹12,450</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">This Month's Purchases</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">₹1,85,320</p>
                        </div>
                        <div class="stat-icon bg-purple-50 text-purple-600">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                        <span class="text-purple-600">● 42 orders</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Stock Alerts</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">23</p>
                        </div>
                        <div class="stat-icon bg-red-50 text-red-600">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-yellow-600">● 12 low</span>
                        <span class="text-red-600">● 8 out</span>
                        <span class="text-orange-600">● 3 expiring</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
                <a href="medicine/add.php" class="quick-action-btn"><i class="fas fa-pills text-blue-600"></i> Add Medicine</a>
                <a href="purchase/new.php" class="quick-action-btn"><i class="fas fa-shopping-cart text-green-600"></i> New Purchase</a>
                <a href="sale/new.php" class="quick-action-btn"><i class="fas fa-cash-register text-purple-600"></i> New Sale</a>
                <a href="prescription/dispense.php" class="quick-action-btn"><i class="fas fa-prescription text-orange-600"></i> Dispense Rx</a>
                <a href="stock/stock_in.php" class="quick-action-btn"><i class="fas fa-arrow-down text-blue-600"></i> Stock In</a>
                <a href="reports/sales.php" class="quick-action-btn"><i class="fas fa-file-alt text-gray-600"></i> Reports</a>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Activities -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Recent Activities</h3>
                        <a href="#" class="text-xs text-blue-600 font-semibold hover:text-blue-700">View All</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div class="activity-item flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-receipt"></i></div>
                                <div><p class="text-sm font-semibold text-gray-800 truncate">Paracetamol 500mg</p><p class="text-xs text-gray-500">Sold by Staff • 15 Jan 2025, 10:30 AM</p></div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="font-semibold text-sm text-green-600">₹450.00</span>
                                <span class="status-badge completed">Completed</span>
                            </div>
                        </div>
                        <div class="activity-item flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-box"></i></div>
                                <div><p class="text-sm font-semibold text-gray-800 truncate">Purchase Order #PO-2025-0042</p><p class="text-xs text-gray-500">By Admin • 15 Jan 2025, 09:15 AM</p></div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="font-semibold text-sm text-purple-600">₹12,500.00</span>
                            </div>
                        </div>
                        <div class="activity-item flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-prescription"></i></div>
                                <div><p class="text-sm font-semibold text-gray-800 truncate">Prescription #RX-2025-0015</p><p class="text-xs text-gray-500">Dr. Sanket Pawar • 15 Jan 2025, 08:45 AM</p></div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="status-badge pending">Pending</span>
                            </div>
                        </div>
                        <div class="activity-item flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-exclamation-triangle"></i></div>
                                <div><p class="text-sm font-semibold text-gray-800 truncate">Low Stock Alert</p><p class="text-xs text-gray-500">Amoxicillin 250mg - Only 15 left (Min: 50)</p></div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="status-badge low">Low Stock</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Selling Medicines -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100"><h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Top Selling Medicines</h3></div>
                    <div class="p-4 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center font-bold text-sm flex-shrink-0">#1</div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">Paracetamol 500mg</p><div class="flex items-center gap-3 text-xs text-gray-500"><span>1,245 sold</span><span class="text-gray-300">|</span><span>₹45,820</span></div></div>
                            <div class="w-20"><div class="progress-bar"><div class="fill bg-yellow-500" style="width: 100%"></div></div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-sm flex-shrink-0">#2</div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">Amoxicillin 250mg</p><div class="flex items-center gap-3 text-xs text-gray-500"><span>980 sold</span><span class="text-gray-300">|</span><span>₹38,500</span></div></div>
                            <div class="w-20"><div class="progress-bar"><div class="fill bg-gray-500" style="width: 78%"></div></div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm flex-shrink-0">#3</div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">Vitamin C 1000mg</p><div class="flex items-center gap-3 text-xs text-gray-500"><span>756 sold</span><span class="text-gray-300">|</span><span>₹32,100</span></div></div>
                            <div class="w-20"><div class="progress-bar"><div class="fill bg-orange-500" style="width: 60%"></div></div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">#4</div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">Ibuprofen 400mg</p><div class="flex items-center gap-3 text-xs text-gray-500"><span>534 sold</span><span class="text-gray-300">|</span><span>₹22,800</span></div></div>
                            <div class="w-20"><div class="progress-bar"><div class="fill bg-blue-500" style="width: 42%"></div></div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">#5</div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">Metformin 500mg</p><div class="flex items-center gap-3 text-xs text-gray-500"><span>412 sold</span><span class="text-gray-300">|</span><span>₹18,900</span></div></div>
                            <div class="w-20"><div class="progress-bar"><div class="fill bg-blue-500" style="width: 33%"></div></div></div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50"><a href="#" class="text-sm text-blue-600 font-semibold hover:text-blue-700">View Full Sales Report →</a></div>
                </div>
            </div>

            <!-- Stock Alerts Section -->
            <div class="mt-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider"><i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i>Stock Alerts</h3>
                    <a href="#" class="text-xs text-blue-600 font-semibold hover:text-blue-700">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    <div class="p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center"><i class="fas fa-exclamation-circle"></i></div>
                            <div><p class="text-sm font-semibold text-gray-800">Low Stock Items</p><p class="text-2xl font-bold text-yellow-600">12</p></div>
                        </div>
                        <p class="text-xs text-gray-500">Items below minimum stock level</p>
                        <a href="#" class="text-xs text-blue-600 font-semibold mt-2 inline-block">View Details →</a>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center"><i class="fas fa-times-circle"></i></div>
                            <div><p class="text-sm font-semibold text-gray-800">Out of Stock</p><p class="text-2xl font-bold text-red-600">8</p></div>
                        </div>
                        <p class="text-xs text-gray-500">Items completely out of stock</p>
                        <a href="#" class="text-xs text-blue-600 font-semibold mt-2 inline-block">View Details →</a>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-clock"></i></div>
                            <div><p class="text-sm font-semibold text-gray-800">Expiring Soon</p><p class="text-2xl font-bold text-orange-600">3</p></div>
                        </div>
                        <p class="text-xs text-gray-500">Items expiring in next 30 days</p>
                        <a href="#" class="text-xs text-blue-600 font-semibold mt-2 inline-block">View Details →</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>