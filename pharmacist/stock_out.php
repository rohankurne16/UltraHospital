<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out</title>
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
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex items-center gap-4 mb-6">
                <a href="current.php" class="p-2 border border-gray-200 rounded-xl hover:bg-white transition"><i class="fas fa-arrow-left text-gray-500"></i></a>
                <div><h1 class="text-2xl font-bold text-gray-900">Stock Out</h1><p class="text-gray-500 text-sm">Remove stock from inventory</p></div>
            </div>

            <div class="form-card">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Medicine <span class="required">*</span></label>
                        <select class="form-control">
                            <option value="">Select Medicine</option>
                            <option>Paracetamol 500mg (245 in stock)</option>
                            <option>Amoxicillin 250mg (15 in stock)</option>
                            <option>Vitamin C 1000mg (0 in stock - Out of Stock)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Batch Number <span class="required">*</span></label>
                        <select class="form-control">
                            <option value="">Select Batch</option>
                            <option>BATCH-001</option>
                            <option>BATCH-002</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity to Remove <span class="required">*</span></label>
                        <input type="number" class="form-control" placeholder="Enter quantity" min="1">
                    </div>
                    <div class="form-group">
                        <label>Reason <span class="required">*</span></label>
                        <select class="form-control">
                            <option value="">Select Reason</option>
                            <option>Sold</option>
                            <option>Damaged</option>
                            <option>Expired</option>
                            <option>Returned</option>
                            <option>Sample/Donation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" class="form-control" placeholder="e.g. Sale # or Return #">
                    </div>
                    <div class="form-group">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                    <a href="current.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button class="btn-danger"><i class="fas fa-arrow-up"></i> Remove Stock</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>