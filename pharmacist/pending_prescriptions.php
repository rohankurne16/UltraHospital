<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Prescriptions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 260px; padding: 24px 28px; min-height: 100vh; width: calc(100% - 260px); background: #f1f5f9; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 16px; width: 100%; } }
        .pending-card { background: white; border-radius: 14px; padding: 16px 18px; border: 1px solid #eef2f7; transition: all 0.25s ease; border-left: 4px solid #f59e0b; }
        .pending-card:hover { transform: translateX(4px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .pending-card.urgent { border-left-color: #ef4444; }
        .section-card { background: white; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #fafbfc; }
        .card-header h5 { font-weight: 700; color: #1e293b; margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 16px 20px; }
        .badge-status { padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: 600; display: inline-block; text-transform: uppercase; }
        .badge-status.Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-status.Urgent { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-sm { padding: 5px 12px; font-size: 11px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-outline { background: transparent; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f1f5f9; }
        .d-flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .mb-3 { margin-bottom: 16px; }
        .mb-4 { margin-bottom: 24px; }
        .mt-2 { margin-top: 8px; }
        .text-xs { font-size: 11px; }
        .text-sm { font-size: 13px; }
        .text-gray-500 { color: #94a3b8; }
        .text-gray-600 { color: #64748b; }
        .font-semibold { font-weight: 600; }
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
                    <h1 class="text-xl font-bold text-gray-900">Pending Prescriptions</h1>
                    <p class="text-sm text-gray-500">Prescriptions awaiting dispensing</p>
                </div>
                <a href="list.php" class="btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <!-- Pending Cards -->
            <div class="space-y-3">
                <div class="pending-card urgent">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="font-semibold text-blue-600">#RX-2025-0015</span>
                                <span class="badge-status Urgent">Urgent</span>
                            </div>
                            <p class="font-semibold text-gray-800 mt-1">Rahul Sharma</p>
                            <p class="text-sm text-gray-500">Dr. Sanket Pawar • 15 Jan 2025</p>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span>3 Items</span>
                            <span class="mx-2">|</span>
                            <span>Waiting: 2 hrs</span>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <a href="details.php" class="btn-sm btn-primary"><i class="fas fa-eye"></i> Details</a>
                        <a href="dispense.php" class="btn-sm btn-success"><i class="fas fa-prescription-bottle"></i> Dispense</a>
                    </div>
                </div>

                <div class="pending-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="font-semibold text-blue-600">#RX-2025-0013</span>
                                <span class="badge-status Pending">Pending</span>
                            </div>
                            <p class="font-semibold text-gray-800 mt-1">Sneha Reddy</p>
                            <p class="text-sm text-gray-500">Dr. Mohan Joshi • 15 Jan 2025</p>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span>2 Items</span>
                            <span class="mx-2">|</span>
                            <span>Waiting: 1 hr</span>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <a href="details.php" class="btn-sm btn-primary"><i class="fas fa-eye"></i> Details</a>
                        <a href="dispense.php" class="btn-sm btn-success"><i class="fas fa-prescription-bottle"></i> Dispense</a>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="section-card mt-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-4 justify-content-between align-items-center">
                        <div class="d-flex gap-4 flex-wrap">
                            <div><span class="text-xs text-gray-500">Total Pending</span><br><span class="text-lg font-bold">23</span></div>
                            <div><span class="text-xs text-gray-500">Urgent</span><br><span class="text-lg font-bold text-red-600">5</span></div>
                            <div><span class="text-xs text-gray-500">Today's</span><br><span class="text-lg font-bold">8</span></div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn-sm btn-primary"><i class="fas fa-sync-alt"></i> Refresh</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>