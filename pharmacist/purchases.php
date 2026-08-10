<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase List</title>
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
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.received { background: #dcfce7; color: #166534; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
        .status-badge.draft { background: #e2e8f0; color: #475569; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-gray-900">Purchase List</h1><p class="text-gray-500 text-sm">Manage all purchase orders</p></div>
                <a href="new.php" class="bg-green-600 text-white px-5 py-2.5 rounded-xl hover:bg-green-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-green-500/20">
                    <i class="fas fa-plus"></i> New Purchase
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="font-semibold text-blue-600">PO-2025-0042</span></td>
                                <td>MediPharm Supplies</td>
                                <td>15 Jan 2025</td>
                                <td>12</td>
                                <td><span class="font-semibold">₹45,680</span></td>
                                <td><span class="status-badge received">Received</span></td>
                                <td>
                                    <button class="text-blue-600 hover:text-blue-700 mr-2"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-700 mr-2"><i class="fas fa-print"></i></button>
                                    <button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="font-semibold text-blue-600">PO-2025-0041</span></td>
                                <td>HealthCare Distributors</td>
                                <td>14 Jan 2025</td>
                                <td>8</td>
                                <td><span class="font-semibold">₹28,450</span></td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>
                                    <button class="text-blue-600 hover:text-blue-700 mr-2"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-700 mr-2"><i class="fas fa-print"></i></button>
                                    <button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing 1-10 of 42 purchases</p>
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