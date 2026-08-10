<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medicine</title>
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
        .form-control.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex items-center gap-4 mb-6">
                <a href="list.php" class="p-2 border border-gray-200 rounded-xl hover:bg-white transition"><i class="fas fa-arrow-left text-gray-500"></i></a>
                <div><h1 class="text-2xl font-bold text-gray-900">Add New Medicine</h1><p class="text-gray-500 text-sm">Add a new medicine to the pharmacy inventory</p></div>
            </div>

            <div class="form-card">
                <form action="add.php" method="POST" enctype="multipart/form-data">
                    <!-- Basic Information -->
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-4">Basic Information</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Medicine Name <span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter medicine name">
                        </div>
                        <div class="form-group">
                            <label>Generic Name <span class="required">*</span></label>
                            <select class="form-control">
                                <option value="">Select Generic</option>
                                <option>Acetaminophen</option>
                                <option>Amoxicillin</option>
                                <option>Ascorbic Acid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select class="form-control">
                                <option value="">Select Category</option>
                                <option>Antibiotics</option>
                                <option>Pain Relief</option>
                                <option>Vitamins</option>
                                <option>Cardiac</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Manufacturer <span class="required">*</span></label>
                            <select class="form-control">
                                <option value="">Select Manufacturer</option>
                                <option>Sun Pharma</option>
                                <option>Cipla</option>
                                <option>Dr. Reddy's</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Strength</label>
                            <input type="text" class="form-control" placeholder="e.g. 500mg">
                        </div>
                        <div class="form-group">
                            <label>Packing</label>
                            <input type="text" class="form-control" placeholder="e.g. 10x10">
                        </div>
                    </div>

                    <!-- Stock Information -->
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest mt-6 mb-4">Stock Information</h2>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Batch Number</label>
                            <input type="text" class="form-control" placeholder="Enter batch number">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date <span class="required">*</span></label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Quantity <span class="required">*</span></label>
                            <input type="number" class="form-control" placeholder="Enter quantity">
                        </div>
                        <div class="form-group">
                            <label>Minimum Stock <span class="required">*</span></label>
                            <input type="number" class="form-control" placeholder="Enter min stock">
                        </div>
                    </div>

                    <!-- Pricing -->
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest mt-6 mb-4">Pricing Information</h2>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Purchase Price <span class="required">*</span></label>
                            <input type="number" class="form-control" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Selling Price <span class="required">*</span></label>
                            <input type="number" class="form-control" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Tax Rate (%)</label>
                            <input type="number" class="form-control" placeholder="0.00" value="0">
                        </div>
                    </div>

                    <!-- Additional -->
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest mt-6 mb-4">Additional Information</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Medicine Image</label>
                            <input type="file" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" rows="3" placeholder="Enter medicine description..."></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                        <a href="list.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Medicine</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>