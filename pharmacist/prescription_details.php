<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 260px; padding: 24px 28px; min-height: 100vh; width: calc(100% - 260px); background: #f1f5f9; }
        @media (max-width: 991px) { .main-content { margin-left: 0; padding: 16px; width: 100%; } }
        
        .detail-card { background: white; border-radius: 16px; padding: 24px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; }
        .section-card { background: white; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #fafbfc; }
        .card-header h5 { font-weight: 700; color: #1e293b; margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .card-header h5 i { color: #6366f1; font-size: 16px; }
        .card-body { padding: 16px 20px; }
        
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { width: 140px; font-size: 12px; color: #94a3b8; font-weight: 500; flex-shrink: 0; }
        .info-row .value { font-size: 13px; color: #1e293b; font-weight: 500; }
        
        .badge-status { padding: 3px 12px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block; text-transform: uppercase; }
        .badge-status.Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-status.Dispensed { background: #dcfce7; color: #16a34a; border: 1px solid #a7f3d0; }
        .badge-status.Partial { background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-status.Cancelled { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        .btn { padding: 8px 20px; font-size: 12px; font-weight: 600; border-radius: 10px; border: none; cursor: pointer; transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-outline { background: transparent; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
        .btn-sm { padding: 5px 14px; font-size: 11px; }
        
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
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        tbody tr:hover { background: #f8fafc; }
        .table-responsive { overflow-x: auto; width: 100%; }
        
        .status-badge-lg { padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-badge-lg.Pending { background: #fef3c7; color: #d97706; }
        .status-badge-lg.Dispensed { background: #dcfce7; color: #16a34a; }
        .status-badge-lg.Partial { background: #dbeafe; color: #2563eb; }
        .status-badge-lg.Cancelled { background: #fee2e2; color: #dc2626; }
        
        .med-item { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
        .med-item:last-child { border-bottom: none; }
        .med-item .name { font-weight: 600; color: #1e293b; font-size: 13px; }
        .med-item .detail { font-size: 11px; color: #94a3b8; }
        
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .row { display: flex; flex-wrap: wrap; margin-left: -10px; margin-right: -10px; }
        .row.g-3 > [class*="col-"] { padding-left: 10px; padding-right: 10px; }
        .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
        .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
        @media (max-width: 767px) { .col-xl-6 { flex: 0 0 100%; max-width: 100%; } }
        
        .print-btn { background: #64748b; color: white; }
        .print-btn:hover { background: #475569; transform: translateY(-2px); }
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
                    <h1 class="text-xl font-bold text-gray-900">Prescription Details</h1>
                    <p class="text-sm text-gray-500">View complete prescription information</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="list.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
                    <a href="#" class="btn print-btn"><i class="fas fa-print"></i> Print</a>
                </div>
            </div>

            <!-- Prescription Header -->
            <div class="detail-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h2 class="text-lg font-bold text-gray-900">#RX-2025-0015</h2>
                            <span class="status-badge-lg Pending">Pending</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Prescribed on 15 Jan 2025 • 10:30 AM</p>
                    </div>
                    <div class="action-buttons">
                        <a href="dispense.php" class="btn btn-success"><i class="fas fa-prescription-bottle"></i> Dispense</a>
                        <button class="btn btn-warning"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-danger"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Patient & Doctor Info -->
            <div class="row g-3 mb-4">
                <div class="col-xl-6">
                    <div class="detail-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-user text-blue-600 mr-2"></i> Patient Information</h5>
                        <div class="info-row"><span class="label">Patient Name</span><span class="value">Rahul Sharma</span></div>
                        <div class="info-row"><span class="label">Age / Gender</span><span class="value">32 Years / Male</span></div>
                        <div class="info-row"><span class="label">Contact</span><span class="value">+91 98765 43210</span></div>
                        <div class="info-row"><span class="label">Email</span><span class="value">rahul.sharma@email.com</span></div>
                        <div class="info-row"><span class="label">Address</span><span class="value">123, Park Street, Mumbai - 400001</span></div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="detail-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-user-md text-green-600 mr-2"></i> Doctor Information</h5>
                        <div class="info-row"><span class="label">Doctor Name</span><span class="value">Dr. Sanket Pawar</span></div>
                        <div class="info-row"><span class="label">Specialization</span><span class="value">Cardiologist</span></div>
                        <div class="info-row"><span class="label">Department</span><span class="value">Cardiology</span></div>
                        <div class="info-row"><span class="label">Contact</span><span class="value">+91 87654 32109</span></div>
                        <div class="info-row"><span class="label">Email</span><span class="value">dr.sanket@hospital.com</span></div>
                    </div>
                </div>
            </div>

            <!-- Prescription Details -->
            <div class="section-card">
                <div class="card-header">
                    <h5><i class="fas fa-prescription"></i> Prescribed Medicines</h5>
                    <span class="text-xs text-gray-500">3 items</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine Name</th>
                                    <th>Strength</th>
                                    <th>Dosage</th>
                                    <th>Qty</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><span class="font-semibold">Paracetamol 500mg</span></td>
                                    <td>500mg</td>
                                    <td>1 Tablet</td>
                                    <td>10</td>
                                    <td>3 times daily</td>
                                    <td>5 days</td>
                                    <td><span class="badge-status Pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><span class="font-semibold">Amoxicillin 250mg</span></td>
                                    <td>250mg</td>
                                    <td>2 Capsules</td>
                                    <td>15</td>
                                    <td>2 times daily</td>
                                    <td>7 days</td>
                                    <td><span class="badge-status Pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><span class="font-semibold">Vitamin C 1000mg</span></td>
                                    <td>1000mg</td>
                                    <td>1 Tablet</td>
                                    <td>5</td>
                                    <td>1 time daily</td>
                                    <td>5 days</td>
                                    <td><span class="badge-status Dispensed">Dispensed</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Instructions & Notes -->
            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="detail-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-stethoscope text-purple-600 mr-2"></i> Diagnosis & Instructions</h5>
                        <p class="text-sm text-gray-600 mb-2"><strong>Diagnosis:</strong> Acute pharyngitis</p>
                        <p class="text-sm text-gray-600"><strong>Instructions:</strong></p>
                        <ul class="text-sm text-gray-600 list-disc pl-4 space-y-1">
                            <li>Take medicine after meals</li>
                            <li>Complete the full course of antibiotics</li>
                            <li>Stay hydrated and take rest</li>
                            <li>Follow up after 7 days</li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="detail-card">
                        <h5 class="font-semibold text-gray-800 mb-3"><i class="fas fa-notes-medical text-orange-600 mr-2"></i> Additional Notes</h5>
                        <p class="text-sm text-gray-600 mb-2"><strong>Allergies:</strong> None reported</p>
                        <p class="text-sm text-gray-600"><strong>Notes:</strong></p>
                        <p class="text-sm text-gray-600">Patient has mild fever. Prescribed paracetamol for fever and amoxicillin for infection. Vitamin C for immunity boost.</p>
                        <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                            <p class="text-xs text-yellow-700"><i class="fas fa-info-circle mr-1"></i> <strong>Note:</strong> This prescription expires on 30 Jan 2025</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="section-card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Prescription Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Prescription Created</p>
                                <p class="text-xs text-gray-500">15 Jan 2025, 10:30 AM • By Dr. Sanket Pawar</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-prescription-bottle text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Partially Dispensed</p>
                                <p class="text-xs text-gray-500">15 Jan 2025, 11:45 AM • Vitamin C 1000mg dispensed</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3 opacity-50">
                            <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-400">Awaiting Remaining Items</p>
                                <p class="text-xs text-gray-400">2 items pending for dispensing</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="detail-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="dispense.php" class="btn btn-success"><i class="fas fa-prescription-bottle"></i> Dispense</a>
                        <button class="btn btn-primary"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-outline"><i class="fas fa-print"></i> Print</button>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-warning"><i class="fas fa-envelope"></i> Send to Patient</button>
                        <button class="btn btn-danger"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>