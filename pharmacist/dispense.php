<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispense Medicine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 260px; padding: 24px 28px; min-height: 100vh; width: calc(100% - 260px); background: #f1f5f9; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 16px; width: 100%; } }
        
        .form-card { background: white; border-radius: 16px; padding: 24px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .section-card { background: white; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #fafbfc; }
        .card-header h5 { font-weight: 700; color: #1e293b; margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .card-header h5 i { color: #6366f1; font-size: 16px; }
        .card-body { padding: 16px 20px; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
        .form-control { width: 100%; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; background: #f8fafc; color: #1e293b; transition: all 0.2s ease; outline: none; }
        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: white; }
        .form-control:disabled { background: #f1f5f9; cursor: not-allowed; }
        .form-control.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
        
        .badge-status { padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: 600; display: inline-block; text-transform: uppercase; }
        .badge-status.In-Stock { background: #dcfce7; color: #16a34a; border: 1px solid #a7f3d0; }
        .badge-status.Low { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-status.Out { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-status.Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        
        .btn { padding: 8px 20px; font-size: 12px; font-weight: 600; border-radius: 10px; border: none; cursor: pointer; transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-outline { background: transparent; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
        .btn-sm { padding: 4px 12px; font-size: 10px; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
        
        .d-flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .align-items-center { align-items: center; }
        .align-items-start { align-items: flex-start; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-end { justify-content: flex-end; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 16px; }
        .mb-4 { margin-bottom: 24px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        .text-xs { font-size: 11px; }
        .text-sm { font-size: 13px; }
        .text-gray-500 { color: #94a3b8; }
        .text-gray-600 { color: #64748b; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        tbody tr:hover { background: #f8fafc; }
        .table-responsive { overflow-x: auto; width: 100%; }
        
        .row { display: flex; flex-wrap: wrap; margin-left: -10px; margin-right: -10px; }
        .row.g-3 > [class*="col-"] { padding-left: 10px; padding-right: 10px; }
        .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; }
        .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
        .col-xl-8 { flex: 0 0 66.667%; max-width: 66.667%; }
        .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
        @media (max-width: 991px) { .col-xl-4, .col-xl-6, .col-xl-8 { flex: 0 0 50%; max-width: 50%; } }
        @media (max-width: 767px) { .col-xl-4, .col-xl-6, .col-xl-8 { flex: 0 0 100%; max-width: 100%; } }
        
        .prescription-info { background: #f8fafc; border-radius: 12px; padding: 16px; }
        .prescription-info .info-item { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .prescription-info .info-item .label { color: #94a3b8; }
        .prescription-info .info-item .value { font-weight: 500; color: #1e293b; }
        
        .med-item { padding: 12px 16px; border: 1px solid #eef2f7; border-radius: 10px; margin-bottom: 10px; transition: all 0.2s ease; }
        .med-item:hover { border-color: #6366f1; background: #fafbfc; }
        .med-item:last-child { margin-bottom: 0; }
        .med-item .med-name { font-weight: 600; color: #1e293b; font-size: 14px; }
        .med-item .med-detail { font-size: 12px; color: #64748b; }
        .med-item .qty-input { width: 60px; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; text-align: center; }
        .med-item .qty-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 2px rgba(99,102,241,0.1); }
        
        .summary-item { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .summary-item .label { color: #64748b; }
        .summary-item .value { font-weight: 600; color: #1e293b; }
        .summary-item.total { border-top: 2px solid #e2e8f0; padding-top: 12px; margin-top: 8px; }
        .summary-item.total .value { font-size: 18px; color: #6366f1; }
        
        .search-box { position: relative; }
        .search-box input { padding-left: 36px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        
        .status-badge-lg { padding: 4px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-badge-lg.Pending { background: #fef3c7; color: #d97706; }
        
        .success-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px; }
        .success-box i { color: #10b981; font-size: 24px; }
        
        .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px; }
        .error-box i { color: #ef4444; font-size: 24px; }
        
        .warning-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; }
        .warning-box i { color: #f59e0b; font-size: 16px; }
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
                    <h1 class="text-xl font-bold text-gray-900">Dispense Medicine</h1>
                    <p class="text-sm text-gray-500">Dispense prescription #RX-2025-0015</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="list.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
                    <a href="details.php" class="btn btn-outline"><i class="fas fa-eye"></i> View Details</a>
                </div>
            </div>

            <!-- Prescription Status -->
            <div class="form-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="status-badge-lg Pending">Pending</span>
                        <span class="text-sm text-gray-500">Prescribed on 15 Jan 2025</span>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="text-xs text-gray-500">Waiting time: <span class="font-semibold text-orange-600">2 hrs</span></span>
                        <span class="text-xs text-gray-500">|</span>
                        <span class="text-xs text-gray-500">Priority: <span class="font-semibold text-red-600">Urgent</span></span>
                    </div>
                </div>
            </div>

            <!-- Patient & Prescription Info -->
            <div class="row g-3 mb-4">
                <div class="col-xl-6">
                    <div class="form-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-user text-blue-600 mr-2"></i> Patient Information</h5>
                        <div class="prescription-info">
                            <div class="info-item"><span class="label">Patient Name</span><span class="value">Rahul Sharma</span></div>
                            <div class="info-item"><span class="label">Age / Gender</span><span class="value">32 Years / Male</span></div>
                            <div class="info-item"><span class="label">Contact</span><span class="value">+91 98765 43210</span></div>
                            <div class="info-item"><span class="label">Email</span><span class="value">rahul.sharma@email.com</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="form-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-prescription text-green-600 mr-2"></i> Prescription Details</h5>
                        <div class="prescription-info">
                            <div class="info-item"><span class="label">RX Number</span><span class="value font-semibold text-blue-600">RX-2025-0015</span></div>
                            <div class="info-item"><span class="label">Doctor</span><span class="value">Dr. Sanket Pawar</span></div>
                            <div class="info-item"><span class="label">Department</span><span class="value">Cardiology</span></div>
                            <div class="info-item"><span class="label">Prescribed Date</span><span class="value">15 Jan 2025, 10:30 AM</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medicines to Dispense -->
            <div class="section-card">
                <div class="card-header">
                    <h5><i class="fas fa-pills"></i> Medicines to Dispense</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="text-xs text-gray-500">3 items</span>
                        <button class="btn btn-sm btn-outline"><i class="fas fa-sync-alt"></i> Refresh Stock</button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Medicine Item 1 -->
                    <div class="med-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="med-name">Paracetamol 500mg <span class="badge-status In-Stock ml-2">In Stock</span></div>
                                <div class="med-detail">Batch: BATCH-001 | Expiry: 31 Dec 2025</div>
                                <div class="med-detail mt-1">Prescribed: <span class="font-semibold">10 tablets</span> | Available: <span class="font-semibold text-green-600">245</span></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-xs text-gray-600">Dispense Qty:</label>
                                <input type="number" class="qty-input" value="10" min="1" max="245">
                                <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Dispense</button>
                            </div>
                        </div>
                    </div>

                    <!-- Medicine Item 2 -->
                    <div class="med-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="med-name">Amoxicillin 250mg <span class="badge-status Low ml-2">Low Stock</span></div>
                                <div class="med-detail">Batch: BATCH-002 | Expiry: 28 Feb 2025</div>
                                <div class="med-detail mt-1">Prescribed: <span class="font-semibold">15 capsules</span> | Available: <span class="font-semibold text-yellow-600">18</span></div>
                                <div class="warning-box mt-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span class="text-xs text-yellow-700">Low stock! Only 18 units available. Please order soon.</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-xs text-gray-600">Dispense Qty:</label>
                                <input type="number" class="qty-input" value="15" min="1" max="18">
                                <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Dispense</button>
                            </div>
                        </div>
                    </div>

                    <!-- Medicine Item 3 -->
                    <div class="med-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="med-name">Vitamin C 1000mg <span class="badge-status Out ml-2">Out of Stock</span></div>
                                <div class="med-detail">Batch: BATCH-003 | Expiry: 15 Mar 2025</div>
                                <div class="med-detail mt-1">Prescribed: <span class="font-semibold">5 tablets</span> | Available: <span class="font-semibold text-red-600">0</span></div>
                                <div class="error-box mt-2">
                                    <i class="fas fa-times-circle"></i>
                                    <span class="text-xs text-red-700">Out of stock! Cannot dispense this item.</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-xs text-gray-600">Dispense Qty:</label>
                                <input type="number" class="qty-input" value="0" min="0" max="0" disabled>
                                <button class="btn btn-sm btn-danger" disabled><i class="fas fa-times"></i> Unavailable</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dispense Summary -->
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="form-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-clipboard-check text-purple-600 mr-2"></i> Dispense Notes</h5>
                        <div class="form-group">
                            <label>Instructions for Patient</label>
                            <textarea class="form-control" rows="3" placeholder="Enter dispensing instructions or notes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Dispensed By</label>
                            <input type="text" class="form-control" value="Dr. Sanket Pawar" disabled>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="form-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-file-invoice text-green-600 mr-2"></i> Dispense Summary</h5>
                        <div class="summary-item"><span class="label">Total Items</span><span class="value">3</span></div>
                        <div class="summary-item"><span class="label">Dispensed</span><span class="value text-green-600">2</span></div>
                        <div class="summary-item"><span class="label">Pending</span><span class="value text-red-600">1</span></div>
                        <div class="summary-item"><span class="label">Total Quantity</span><span class="value">25</span></div>
                        <div class="summary-item total"><span class="label">Status</span><span class="value text-yellow-600">Partially Dispensed</span></div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline"><i class="fas fa-print"></i> Print Label</button>
                        <button class="btn btn-outline"><i class="fas fa-envelope"></i> Send to Patient</button>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-danger"><i class="fas fa-times"></i> Cancel</button>
                        <button class="btn btn-outline"><i class="fas fa-save"></i> Save & Continue</button>
                        <button class="btn btn-success"><i class="fas fa-check"></i> Complete Dispense</button>
                    </div>
                </div>
            </div>

            <!-- Success Message (Hidden by default) -->
            <div class="success-box" style="display:none;">
                <i class="fas fa-check-circle"></i>
                <div>
                    <p class="font-semibold text-green-800">Dispense Completed Successfully!</p>
                    <p class="text-xs text-green-700">All available medicines have been dispensed. Prescription updated.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Quantity validation and update
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max')) || 999;
                let val = parseInt(this.value) || 0;
                if (val > max) {
                    this.value = max;
                    this.classList.add('input-error');
                } else {
                    this.classList.remove('input-error');
                }
                if (val < 0) this.value = 0;
            });
        });

        // Dispense button click
        document.querySelectorAll('.btn-success').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.disabled) return;
                const parent = this.closest('.med-item');
                const name = parent.querySelector('.med-name').textContent.trim();
                const qty = parent.querySelector('.qty-input').value;
                if (confirm(`Dispense ${qty} units of ${name}?`)) {
                    // Show success message
                    alert(`Successfully dispensed ${qty} units of ${name}`);
                    this.textContent = ' ✓ Dispensed';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline');
                    this.disabled = true;
                }
            });
        });

        // Complete Dispense button
        document.querySelector('.btn-success:last-child')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to complete this dispense?')) {
                document.querySelector('.success-box').style.display = 'flex';
                this.textContent = ' ✓ Completed';
                this.disabled = true;
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 3000);
            }
        });

        // Cancel button
        document.querySelector('.btn-danger')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this dispense?')) {
                if (confirm('This will mark the prescription as cancelled. Continue?')) {
                    alert('Prescription cancelled successfully.');
                    window.location.href = 'list.php';
                }
            }
        });
    </script>
</body>
</html>