<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .form-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.4rem; }
        .form-group label .required { color: #ef4444; }
        .form-control { width: 100%; padding: 0.7rem 1rem; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.9rem; background: #f8fafc; color: #1e293b; transition: all 0.2s ease; outline: none; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); background: #ffffff; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.completed { background: #dcfce7; color: #166534; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
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
            <div class="flex items-center gap-4 mb-6">
                <a href="current.php" class="p-2 border border-gray-200 rounded-xl hover:bg-white transition"><i class="fas fa-arrow-left text-gray-500"></i></a>
                <div><h1 class="text-2xl font-bold text-gray-900">Stock In</h1><p class="text-gray-500 text-sm">Add stock to inventory</p></div>
            </div>

            <div class="form-card">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Medicine <span class="required">*</span></label>
                        <select class="form-control">
                            <option value="">Select Medicine</option>
                            <option>Paracetamol 500mg</option>
                            <option>Amoxicillin 250mg</option>
                            <option>Vitamin C 1000mg</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Batch Number <span class="required">*</span></label>
                        <input type="text" class="form-control" placeholder="Enter batch number">
                    </div>
                    <div class="form-group">
                        <label>Quantity <span class="required">*</span></label>
                        <input type="number" class="form-control" placeholder="Enter quantity">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date <span class="required">*</span></label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Purchase Price <span class="required">*</span></label>
                        <input type="number" class="form-control" placeholder="0.00" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Selling Price <span class="required">*</span></label>
                        <input type="number" class="form-control" placeholder="0.00" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select class="form-control">
                            <option value="">Select Supplier</option>
                            <option>MediPharm Supplies</option>
                            <option>HealthCare Distributors</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Received Date <span class="required">*</span></label>
                        <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                    <a href="current.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button class="btn-success"><i class="fas fa-arrow-down"></i> Add Stock</button>
                </div>
            </div>

            <!-- Recent Stock In History -->
            <div class="mt-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Recent Stock In History</h3>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Medicine</th>
                                    <th>Batch</th>
                                    <th>Qty</th>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>15 Jan 2025</td>
                                    <td>Paracetamol 500mg</td>
                                    <td>BATCH-001</td>
                                    <td>200</td>
                                    <td>MediPharm Supplies</td>
                                    <td><span class="status-badge completed">Completed</span></td>
                                </tr>
                                <tr>
                                    <td>14 Jan 2025</td>
                                    <td>Amoxicillin 250mg</td>
                                    <td>BATCH-002</td>
                                    <td>150</td>
                                    <td>HealthCare Distributors</td>
                                    <td><span class="status-badge completed">Completed</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>