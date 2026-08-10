<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Return</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .return-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .return-card:hover { border-color: #f59e0b; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.approved { background: #dcfce7; color: #166534; }
        .status-badge.rejected { background: #fee2e2; color: #991b1b; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; }
        tr:hover { background: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-gray-900">Purchase Returns</h1><p class="text-gray-500 text-sm">Manage purchase returns</p></div>
                <button class="bg-orange-600 text-white px-5 py-2.5 rounded-xl hover:bg-orange-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-orange-500/20">
                    <i class="fas fa-plus"></i> New Return
                </button>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Return #</th>
                                <th>Purchase #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="font-semibold text-orange-600">RET-2025-003</span></td>
                                <td><span class="text-blue-600">PO-2025-0042</span></td>
                                <td>MediPharm Supplies</td>
                                <td>15 Jan 2025</td>
                                <td>3</td>
                                <td><span class="font-semibold">₹1,250.00</span></td>
                                <td><span class="status-badge approved">Approved</span></td>
                                <td>
                                    <button class="text-blue-600 hover:text-blue-700 mr-2"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-700 mr-2"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="font-semibold text-orange-600">RET-2025-002</span></td>
                                <td><span class="text-blue-600">PO-2025-0041</span></td>
                                <td>HealthCare Distributors</td>
                                <td>14 Jan 2025</td>
                                <td>2</td>
                                <td><span class="font-semibold">₹890.00</span></td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>
                                    <button class="text-blue-600 hover:text-blue-700 mr-2"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-700 mr-2"><i class="fas fa-check"></i></button>
                                    <button class="text-red-600 hover:text-red-700"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>