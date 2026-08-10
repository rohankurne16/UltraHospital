<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Purchase</title>
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
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1.25rem; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 0.65rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-secondary:hover { background: #e2e8f0; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; }
        @media (max-width: 768px) { .grid-2, .grid-4 { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex items-center gap-4 mb-6">
                <a href="list.php" class="p-2 border border-gray-200 rounded-xl hover:bg-white transition"><i class="fas fa-arrow-left text-gray-500"></i></a>
                <div><h1 class="text-2xl font-bold text-gray-900">New Purchase Order</h1><p class="text-gray-500 text-sm">Create a new purchase order</p></div>
            </div>

            <div class="form-card">
                <!-- Purchase Details -->
                <div class="grid-2">
                    <div class="form-group">
                        <label>Purchase Order # <span class="required">*</span></label>
                        <input type="text" class="form-control" value="PO-2025-0043" readonly>
                    </div>
                    <div class="form-group">
                        <label>Supplier <span class="required">*</span></label>
                        <select class="form-control">
                            <option value="">Select Supplier</option>
                            <option>MediPharm Supplies</option>
                            <option>HealthCare Distributors</option>
                            <option>Pharma Plus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Purchase Date <span class="required">*</span></label>
                        <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Expected Delivery</label>
                        <input type="date" class="form-control">
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Purchase Items</h3>
                        <button class="btn-secondary"><i class="fas fa-plus"></i> Add Item</button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Batch</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-control text-sm">
                                            <option>Paracetamol 500mg</option>
                                            <option>Amoxicillin 250mg</option>
                                            <option>Vitamin C 1000mg</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control text-sm" placeholder="Batch #"></td>
                                    <td><input type="number" class="form-control text-sm" value="100"></td>
                                    <td><input type="number" class="form-control text-sm" value="25.00" step="0.01"></td>
                                    <td><span class="font-semibold">₹2,500.00</span></td>
                                    <td><button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                <tr>
                                    <td>
                                        <select class="form-control text-sm">
                                            <option>Paracetamol 500mg</option>
                                            <option selected>Amoxicillin 250mg</option>
                                            <option>Vitamin C 1000mg</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control text-sm" placeholder="Batch #"></td>
                                    <td><input type="number" class="form-control text-sm" value="200"></td>
                                    <td><input type="number" class="form-control text-sm" value="45.00" step="0.01"></td>
                                    <td><span class="font-semibold">₹9,000.00</span></td>
                                    <td><button class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="grid-2">
                        <div></div>
                        <div class="space-y-2">
                            <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span class="font-semibold">₹11,500.00</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Discount (5%)</span><span class="font-semibold text-red-600">-₹575.00</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Tax (12%)</span><span class="font-semibold">₹1,380.00</span></div>
                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2"><span>Grand Total</span><span class="text-blue-600">₹12,305.00</span></div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mt-4">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" rows="2" placeholder="Additional notes about this purchase..."></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                    <a href="list.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button class="btn-secondary"><i class="fas fa-save"></i> Save as Draft</button>
                    <button class="btn-success"><i class="fas fa-check"></i> Confirm Purchase</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>