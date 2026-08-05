<?php
require_once __DIR__ . '/../config/permission.php';
if (!hasPerm('accountant-dashboard-view')) { header('Location: ../dashboard.php'); exit(); }
 $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 2rem; min-height: 100vh; transition: margin 0.3s; }
        @media (max-width: 992px) { .main-content { margin-left: 0; padding: 1rem; } }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .stat-card { padding: 1.5rem; display: flex; align-items: center; gap: 1rem; transition: transform 0.2s; border-left: 4px solid transparent; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .stat-label { font-size: 0.8rem; color: #64748b; }
        
        .bg-light-blue { background: #eff6ff; color: #3b82f6; } .border-blue { border-left-color: #3b82f6; }
        .bg-light-green { background: #dcfce7; color: #22c55e; } .border-green { border-left-color: #22c55e; }
        .bg-light-red { background: #fee2e2; color: #ef4444; } .border-red { border-left-color: #ef4444; }
        .bg-light-yellow { background: #fef3c7; color: #f59e0b; } .border-yellow { border-left-color: #f59e0b; }
        .bg-light-purple { background: #f3e8ff; color: #8b5cf6; } .border-purple { border-left-color: #8b5cf6; }
        
        .chart-container { position: relative; height: 300px; }
        .page-header { margin-bottom: 2rem; }
        .table thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 0.8rem; text-transform: uppercase; color: #64748b; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content" style="margin-top: 70px;">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1 fw-bold">Accounts Dashboard</h3>
                <p class="text-muted mb-0">Financial overview and statistics</p>
            </div>
            <button class="btn btn-primary"><i class="fas fa-download"></i> Export Report</button>
        </div>

        <!-- Dashboard Cards (12 Cards) -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-green">
                    <div class="stat-icon bg-light-green"><i class="fas fa-rupee-sign"></i></div>
                    <div><div class="stat-value">₹45,500</div><div class="stat-label">Today's Revenue</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-blue">
                    <div class="stat-icon bg-light-blue"><i class="fas fa-chart-line"></i></div>
                    <div><div class="stat-value">₹5,84,500</div><div class="stat-label">Monthly Revenue</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-exclamation-circle"></i></div>
                    <div><div class="stat-value">₹1,23,400</div><div class="stat-label">Outstanding Payments</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-yellow">
                    <div class="stat-icon bg-light-yellow"><i class="fas fa-money-bill-wave"></i></div>
                    <div><div class="stat-value">₹25,000</div><div class="stat-label">Cash Collection</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-purple">
                    <div class="stat-icon bg-light-purple"><i class="fas fa-laptop"></i></div>
                    <div><div class="stat-value">₹20,500</div><div class="stat-label">Online Payments</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-shield-alt"></i></div>
                    <div><div class="stat-value">₹80,000</div><div class="stat-label">Insurance Pending</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-blue">
                    <div class="stat-icon bg-light-blue"><i class="fas fa-file-medical"></i></div>
                    <div><div class="stat-value">12</div><div class="stat-label">TPA Claims</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-yellow">
                    <div class="stat-icon bg-light-yellow"><i class="fas fa-clock"></i></div>
                    <div><div class="stat-value">34</div><div class="stat-label">Pending Bills</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-green">
                    <div class="stat-icon bg-light-green"><i class="fas fa-check-circle"></i></div>
                    <div><div class="stat-value">120</div><div class="stat-label">Paid Bills</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-undo"></i></div>
                    <div><div class="stat-value">₹5,000</div><div class="stat-label">Refund Amount</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-receipt"></i></div>
                    <div><div class="stat-value">₹1,50,000</div><div class="stat-label">Expenses</div></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card stat-card border-green">
                    <div class="stat-icon bg-light-green"><i class="fas fa-coins"></i></div>
                    <div><div class="stat-value">₹4,34,500</div><div class="stat-label">Profit</div></div>
                </div>
            </div>
        </div>

        <!-- Charts Section (6 Charts) -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6 col-xl-4">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3">Daily Revenue</h6>
                    <div class="chart-container"><canvas id="dailyRevenueChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3">Monthly Revenue</h6>
                    <div class="chart-container"><canvas id="monthlyRevenueChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3">Payment Mode</h6>
                    <div class="chart-container"><canvas id="paymentModeChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3">Income vs Expense</h6>
                    <div class="chart-container"><canvas id="incomeExpenseChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3">Outstanding Amount</h6>
                    <div class="chart-container"><canvas id="outstandingChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3">Insurance Collection</h6>
                    <div class="chart-container"><canvas id="insuranceChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent Transactions</h6>
                <a href="payment_history.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Txn ID</th><th>Patient/Supplier</th><th>Type</th><th>Date</th><th>Amount</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>TXN1001</td><td>Rahul Sharma</td><td>OPD Bill</td><td>2023-10-26</td><td>₹500</td><td><span class="badge bg-success">Paid</span></td></tr>
                        <tr><td>TXN1002</td><td>MediCorp</td><td>Vendor Payment</td><td>2023-10-26</td><td>₹4,500</td><td><span class="badge bg-danger">Expense</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // DataTable Init
        $(document).ready(function() { $('#transactionsTable').DataTable(); });

        // Chart.js Config
        const ctx1 = document.getElementById('dailyRevenueChart').getContext('2d');
        new Chart(ctx1, { type: 'line', data: { labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'], datasets: [{ label: 'Revenue', data: [12000, 19000, 15000, 25000, 22000, 30000], borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true }] }, options: { responsive: true, maintainAspectRatio: false } });

        const ctx2 = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(ctx2, { type: 'bar', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], datasets: [{ label: 'Revenue', data: [300000, 400000, 350000, 500000, 450000, 584500], backgroundColor: '#22c55e' }] }, options: { responsive: true, maintainAspectRatio: false } });

        const ctx3 = document.getElementById('paymentModeChart').getContext('2d');
        new Chart(ctx3, { type: 'doughnut', data: { labels: ['Cash', 'UPI', 'Card', 'Insurance'], datasets: [{ data: [25000, 15000, 5000, 80000], backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444'] }] }, options: { responsive: true, maintainAspectRatio: false } });

        const ctx4 = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(ctx4, { type: 'bar', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'], datasets: [{ label: 'Income', data: [300000, 400000, 350000, 500000, 450000], backgroundColor: '#22c55e' }, { label: 'Expense', data: [100000, 150000, 120000, 200000, 180000], backgroundColor: '#ef4444' }] }, options: { responsive: true, maintainAspectRatio: false } });

        const ctx5 = document.getElementById('outstandingChart').getContext('2d');
        new Chart(ctx5, { type: 'line', data: { labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], datasets: [{ label: 'Outstanding', data: [50000, 80000, 60000, 123400], borderColor: '#ef4444', fill: false }] }, options: { responsive: true, maintainAspectRatio: false } });

        const ctx6 = document.getElementById('insuranceChart').getContext('2d');
        new Chart(ctx6, { type: 'bar', data: { labels: ['Cashless', 'Reimbursement'], datasets: [{ label: 'Amount', data: [500000, 200000], backgroundColor: ['#3b82f6', '#8b5cf6'] }] }, options: { responsive: true, maintainAspectRatio: false } });
    </script>
</body>
</html>