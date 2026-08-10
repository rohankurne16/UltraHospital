<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; }
        tr:hover { background: #f8fafc; }
        .type-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .type-badge.in { background: #dcfce7; color: #166534; }
        .type-badge.out { background: #fee2e2; color: #991b1b; }
        .type-badge.adjustment { background: #fef3c7; color: #92400e; }
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
                <div><h1 class="text-2xl font-bold text-gray-900">Stock History</h1><p class="text-gray-500 text-sm">Complete audit trail of stock movements</p></div>
                <div class="flex gap-3">
                    <button class="bg-blue-600 text-white px-4 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold"><i class="fas fa-file-pdf"></i> Export</button>
                    <button class="bg-gray-600 text-white px-4 py-2.5 rounded-xl hover:bg-gray-700 transition flex items-center gap-2 text-sm font-semibold"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px] search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search by medicine or batch..." class="w-full px-4 py-2.5 pl-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <select class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">All Types</option>
                            <option>In</option>
                            <option>Out</option>
                            <option>Adjustment</option>
                        </select>
                    </div>
                    <div>
                        <input type="date" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <input type="date" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm">
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
                                <th>Date & Time</th>
                                <th>Medicine</th>
                                <th>Batch</th>
                                <th>Type</th>
                                <th>Qty Change</th>
                                <th>Previous</th>
                                <th>New</th>
                                <th>Reference</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="text-sm">15 Jan 2025 10:30</span></td>
                                <td><span class="font-semibold">Paracetamol 500mg</span></td>
                                <td><span class="text-sm">BATCH-001</span></td>
                                <td><span class="type-badge in">IN</span></td>
                                <td><span class="text-green-600 font-semibold">+200</span></td>
                                <td>45</td>
                                <td>245</td>
                                <td><span class="text-sm text-blue-600">PO-2025-0042</span></td>
                                <td><span class="text-sm">Admin</span></td>
                            </tr>
                            <tr>
                                <td><span class="text-sm">15 Jan 2025 09:15</span></td>
                                <td><span class="font-semibold">Amoxicillin 250mg</span></td>
                                <td><span class="text-sm">BATCH-002</span></td>
                                <td><span class="type-badge out">OUT</span></td>
                                <td><span class="text-red-600 font-semibold">-5</span></td>
                                <td>20</td>
                                <td>15</td>
                                <td><span class="text-sm text-orange-600">RET-2025-003</span></td>
                                <td><span class="text-sm">Staff</span></td>
                            </tr>
                            <tr>
                                <td><span class="text-sm">14 Jan 2025 16:45</span></td>
                                <td><span class="font-semibold">Vitamin C 1000mg</span></td>
                                <td><span class="text-sm">BATCH-003</span></td>
                                <td><span class="type-badge adjustment">ADJ</span></td>
                                <td><span class="text-yellow-600 font-semibold">±0</span></td>
                                <td>100</td>
                                <td>95</td>
                                <td><span class="text-sm text-gray-500">Physical Count</span></td>
                                <td><span class="text-sm">Admin</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing 1-10 of 1,284 stock entries</p>
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