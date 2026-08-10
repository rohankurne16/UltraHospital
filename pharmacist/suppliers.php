<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .supplier-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .supplier-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); border-color: #3b82f6; }
        .supplier-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #fee2e2; color: #991b1b; }
        .search-box { position: relative; }
        .search-box input { padding-left: 40px; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .action-btn:hover { transform: scale(1.05); }
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
                        <a href="../purchase/list.php" class="hover:text-blue-600">Purchase</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-gray-700">Suppliers</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Supplier Management</h1>
                    <p class="text-gray-500 text-sm">Manage all suppliers and their information</p>
                </div>
                <div class="flex gap-3">
                    <a href="add.php" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-blue-500/20">
                        <i class="fas fa-plus"></i> Add Supplier
                    </a>
                    <button class="bg-gray-600 text-white px-5 py-2.5 rounded-xl hover:bg-gray-700 transition flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Suppliers</p>
                            <p class="text-2xl font-bold text-gray-900">18</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-truck text-lg"></i></div>
                    </div>
                    <p class="text-xs text-green-600 mt-1">↑ 2 new this month</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Active Suppliers</p>
                            <p class="text-2xl font-bold text-green-600">15</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center"><i class="fas fa-check-circle text-lg"></i></div>
                    </div>
                    <p class="text-xs text-green-600 mt-1">● All active suppliers</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Purchases</p>
                            <p class="text-2xl font-bold text-gray-900">156</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center"><i class="fas fa-shopping-cart text-lg"></i></div>
                    </div>
                    <p class="text-xs text-purple-600 mt-1">● This month: 12</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Outstanding Balance</p>
                            <p class="text-2xl font-bold text-red-600">₹2,45,680</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center"><i class="fas fa-money-bill-wave text-lg"></i></div>
                    </div>
                    <p class="text-xs text-red-600 mt-1">⚠️ Pending payments</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px] search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search suppliers..." class="w-full px-4 py-2.5 pl-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <select class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">All Status</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                    <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition text-sm font-semibold"><i class="fas fa-filter mr-2"></i>Apply</button>
                </div>
            </div>

            <!-- Supplier Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Supplier 1 -->
                <div class="supplier-card">
                    <div class="flex items-center gap-4">
                        <div class="supplier-icon bg-blue-100 text-blue-700">MS</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">MediPharm Supplies</h3>
                            <p class="text-xs text-gray-500">Contact: Rajesh Kumar</p>
                        </div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 98765 43210</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@medipharm.com</p>
                        <p class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-gray-400"></i>GST: 22ABCDE1234F1Z5</p>
                        <p class="text-gray-600"><i class="fas fa-shopping-cart mr-2 text-gray-400"></i>45 purchases</p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                            <button class="action-btn bg-green-50 text-green-600 hover:bg-green-100"><i class="fas fa-history"></i></button>
                            <button class="action-btn bg-purple-50 text-purple-600 hover:bg-purple-100"><i class="fas fa-money-bill"></i></button>
                            <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                        </div>
                        <span class="text-sm font-semibold text-red-600">₹45,000 outstanding</span>
                    </div>
                </div>

                <!-- Supplier 2 -->
                <div class="supplier-card">
                    <div class="flex items-center gap-4">
                        <div class="supplier-icon bg-green-100 text-green-700">HD</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">HealthCare Distributors</h3>
                            <p class="text-xs text-gray-500">Contact: Priya Singh</p>
                        </div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 87654 32109</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@healthcare.com</p>
                        <p class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-gray-400"></i>GST: 33BCDEF5678G2H6</p>
                        <p class="text-gray-600"><i class="fas fa-shopping-cart mr-2 text-gray-400"></i>38 purchases</p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                            <button class="action-btn bg-green-50 text-green-600 hover:bg-green-100"><i class="fas fa-history"></i></button>
                            <button class="action-btn bg-purple-50 text-purple-600 hover:bg-purple-100"><i class="fas fa-money-bill"></i></button>
                            <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                        </div>
                        <span class="text-sm font-semibold text-green-600">₹0 balance</span>
                    </div>
                </div>

                <!-- Supplier 3 -->
                <div class="supplier-card">
                    <div class="flex items-center gap-4">
                        <div class="supplier-icon bg-purple-100 text-purple-700">PP</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">Pharma Plus</h3>
                            <p class="text-xs text-gray-500">Contact: Anand Kumar</p>
                        </div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 76543 21098</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@pharmaplus.com</p>
                        <p class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-gray-400"></i>GST: 44CDEFG7890H3I7</p>
                        <p class="text-gray-600"><i class="fas fa-shopping-cart mr-2 text-gray-400"></i>32 purchases</p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                            <button class="action-btn bg-green-50 text-green-600 hover:bg-green-100"><i class="fas fa-history"></i></button>
                            <button class="action-btn bg-purple-50 text-purple-600 hover:bg-purple-100"><i class="fas fa-money-bill"></i></button>
                            <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                        </div>
                        <span class="text-sm font-semibold text-red-600">₹12,500 outstanding</span>
                    </div>
                </div>

                <!-- Supplier 4 -->
                <div class="supplier-card">
                    <div class="flex items-center gap-4">
                        <div class="supplier-icon bg-orange-100 text-orange-700">GS</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">Global Supplies</h3>
                            <p class="text-xs text-gray-500">Contact: Sarah Williams</p>
                        </div>
                        <span class="status-badge inactive">Inactive</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 65432 10987</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@globalsupplies.com</p>
                        <p class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-gray-400"></i>GST: 55DEFGH8901I4J8</p>
                        <p class="text-gray-600"><i class="fas fa-shopping-cart mr-2 text-gray-400"></i>28 purchases</p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                            <button class="action-btn bg-green-50 text-green-600 hover:bg-green-100"><i class="fas fa-history"></i></button>
                            <button class="action-btn bg-purple-50 text-purple-600 hover:bg-purple-100"><i class="fas fa-money-bill"></i></button>
                            <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                        </div>
                        <span class="text-sm font-semibold text-gray-400">No outstanding</span>
                    </div>
                </div>

                <!-- Supplier 5 -->
                <div class="supplier-card">
                    <div class="flex items-center gap-4">
                        <div class="supplier-icon bg-teal-100 text-teal-700">MS</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">MediCorp Solutions</h3>
                            <p class="text-xs text-gray-500">Contact: Vikram Patel</p>
                        </div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 54321 09876</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@medicorp.com</p>
                        <p class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-gray-400"></i>GST: 66EFGHI9012J5K9</p>
                        <p class="text-gray-600"><i class="fas fa-shopping-cart mr-2 text-gray-400"></i>22 purchases</p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                            <button class="action-btn bg-green-50 text-green-600 hover:bg-green-100"><i class="fas fa-history"></i></button>
                            <button class="action-btn bg-purple-50 text-purple-600 hover:bg-purple-100"><i class="fas fa-money-bill"></i></button>
                            <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                        </div>
                        <span class="text-sm font-semibold text-red-600">₹8,750 outstanding</span>
                    </div>
                </div>

                <!-- Supplier 6 -->
                <div class="supplier-card">
                    <div class="flex items-center gap-4">
                        <div class="supplier-icon bg-pink-100 text-pink-700">RM</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">Rapid Medics</h3>
                            <p class="text-xs text-gray-500">Contact: Meera Sharma</p>
                        </div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <p class="text-gray-600"><i class="fas fa-phone mr-2 text-gray-400"></i>+91 43210 98765</p>
                        <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>info@rapidmedics.com</p>
                        <p class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-gray-400"></i>GST: 77FGHIJ0123K6L0</p>
                        <p class="text-gray-600"><i class="fas fa-shopping-cart mr-2 text-gray-400"></i>18 purchases</p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fas fa-edit"></i></button>
                            <button class="action-btn bg-green-50 text-green-600 hover:bg-green-100"><i class="fas fa-history"></i></button>
                            <button class="action-btn bg-purple-50 text-purple-600 hover:bg-purple-100"><i class="fas fa-money-bill"></i></button>
                            <button class="action-btn bg-red-50 text-red-600 hover:bg-red-100"><i class="fas fa-trash"></i></button>
                        </div>
                        <span class="text-sm font-semibold text-green-600">₹0 balance</span>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex items-center justify-between">
                <p class="text-sm text-gray-500">Showing 1-6 of 18 suppliers</p>
                <div class="flex gap-2">
                    <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm"><i class="fas fa-chevron-left"></i></button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">1</button>
                    <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">2</button>
                    <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">3</button>
                    <button class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>