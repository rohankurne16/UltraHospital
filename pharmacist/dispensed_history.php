<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispensed History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 260px; padding: 24px 28px; min-height: 100vh; width: calc(100% - 260px); background: #f1f5f9; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 16px; width: 100%; } }
        
        .stat-card { background: white; border-radius: 14px; padding: 16px 18px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.07); border-color: #6366f1; }
        .stat-card .stat-number { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1.2; margin: 6px 0 2px; }
        .stat-card .stat-label { font-size: 12px; font-weight: 500; color: #94a3b8; }
        .stat-card .stat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: white; }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card.purple .stat-icon { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        
        .section-card { background: white; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #fafbfc; }
        .card-header h5 { font-weight: 700; color: #1e293b; margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .card-header h5 i { color: #6366f1; font-size: 16px; }
        .card-body { padding: 16px 20px; }
        
        .badge-status { padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: 600; display: inline-block; text-transform: uppercase; }
        .badge-status.Dispensed { background: #dcfce7; color: #16a34a; border: 1px solid #a7f3d0; }
        .badge-status.Partial { background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-status.Cancelled { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        .btn { padding: 8px 20px; font-size: 12px; font-weight: 600; border-radius: 10px; border: none; cursor: pointer; transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-outline { background: transparent; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-sm { padding: 5px 14px; font-size: 11px; }
        
        .d-flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .mb-3 { margin-bottom: 16px; }
        .mb-4 { margin-bottom: 24px; }
        .text-xs { font-size: 11px; }
        .text-sm { font-size: 13px; }
        .text-gray-500 { color: #94a3b8; }
        .font-semibold { font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        tbody tr { transition: background 0.15s ease; cursor: pointer; }
        tbody tr:hover { background: #f8fafc; }
        .table-responsive { overflow-x: auto; width: 100%; }
        
        .row { display: flex; flex-wrap: wrap; margin-left: -10px; margin-right: -10px; }
        .row.g-3 > [class*="col-"] { padding-left: 10px; padding-right: 10px; }
        .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
        .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
        @media (max-width: 991px) { .col-xl-3 { flex: 0 0 50%; max-width: 50%; } }
        @media (max-width: 767px) { .col-xl-3 { flex: 0 0 100%; max-width: 100%; } }
        
        .search-box { position: relative; }
        .search-box input { padding-left: 36px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="main-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Dispensed History</h1>
                    <p class="text-sm text-gray-500">Complete history of dispensed prescriptions</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="list.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
                    <button class="btn btn-primary"><i class="fas fa-file-export"></i> Export</button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                            <span class="text-xs font-semibold text-green-600">+12%</span>
                        </div>
                        <div class="stat-number">1,245</div>
                        <div class="stat-label">Total Dispensed</div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon blue"><i class="fas fa-hourglass-half"></i></div>
                            <span class="text-xs font-semibold text-blue-600">8</span>
                        </div>
                        <div class="stat-number">45</div>
                        <div class="stat-label">Partial Dispensed</div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon orange"><i class="fas fa-calendar-day"></i></div>
                            <span class="text-xs font-semibold text-orange-600">+5%</span>
                        </div>
                        <div class="stat-number">32</div>
                        <div class="stat-label">Dispensed Today</div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon purple"><i class="fas fa-user-md"></i></div>
                            <span class="text-xs font-semibold text-purple-600">12</span>
                        </div>
                        <div class="stat-number">156</div>
                        <div class="stat-label">Unique Patients</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="section-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="search-box flex-1" style="flex:1; min-width:200px;">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search by RX #, Patient or Medicine..." class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm bg-white">
                        </div>
                        <select class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">All Status</option>
                            <option>Dispensed</option>
                            <option>Partial</option>
                            <option>Cancelled</option>
                        </select>
                        <input type="date" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                        <input type="date" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                        <button class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="section-card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Dispensed Records</h5>
                    <span class="text-xs text-gray-500">Showing 1-10 of 1,245</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>RX #</th>
                                    <th>Patient</th>
                                    <th>Medicine</th>
                                    <th>Dispensed</th>
                                    <th>Qty</th>
                                    <th>Dispensed By</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr onclick="window.location.href='details.php?id=1'">
                                    <td><span class="font-semibold text-blue-600">RX-2025-0014</span></td>
                                    <td>Priya Patel</td>
                                    <td>Vitamin C 1000mg</td>
                                    <td>5</td>
                                    <td>5</td>
                                    <td>Staff A</td>
                                    <td>14 Jan 2025</td>
                                    <td><span class="badge-status Dispensed">Dispensed</span></td>
                                </tr>
                                <tr onclick="window.location.href='details.php?id=2'">
                                    <td><span class="font-semibold text-blue-600">RX-2025-0012</span></td>
                                    <td>Mohan Joshi</td>
                                    <td>Paracetamol 500mg</td>
                                    <td>10</td>
                                    <td>10</td>
                                    <td>Staff B</td>
                                    <td>13 Jan 2025</td>
                                    <td><span class="badge-status Dispensed">Dispensed</span></td>
                                </tr>
                                <tr onclick="window.location.href='details.php?id=3'">
                                    <td><span class="font-semibold text-blue-600">RX-2025-0010</span></td>
                                    <td>Sneha Reddy</td>
                                    <td>Amoxicillin 250mg</td>
                                    <td>10</td>
                                    <td>15</td>
                                    <td>Staff A</td>
                                    <td>12 Jan 2025</td>
                                    <td><span class="badge-status Partial">Partial</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-t border-gray-100">
                        <span class="text-xs text-gray-500">Showing 1-10 of 1,245</span>
                        <div class="d-flex gap-1">
                            <button class="px-3 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs"><i class="fas fa-chevron-left"></i></button>
                            <button class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs font-semibold">1</button>
                            <button class="px-3 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs">2</button>
                            <button class="px-3 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs">3</button>
                            <button class="px-3 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs">4</button>
                            <button class="px-3 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs">5</button>
                            <button class="px-3 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>