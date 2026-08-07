<?php
require_once __DIR__ . '/../config/permission.php';
include '../header.php';
include '../Sidebar.php';

// Get user name from session (fallback)
$user_name = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Accountant';
$page_title = 'Salary Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* ----- RESET & BASE ----- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            color: #1e293b;
            line-height: 1.6;
        }

        /* ----- FIX: MAIN CONTENT OFFSET FOR SIDEBAR & HEADER ----- */
        .main-content {
            margin-left: 260px;          /* Sidebar width */
            padding: 2rem 1.5rem;
            min-height: 100vh;
            transition: margin 0.3s;
            margin-top: 70px;            /* Header height */
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 1rem; }
        }

        /* ----- GREETING ----- */
        .greeting {
            margin-bottom: 2rem;
        }
        .greeting h2 {
            font-weight: 700;
            font-size: 1.8rem;
            color: #0f172a;
        }
        .greeting p {
            color: #64748b;
            font-size: 1rem;
            margin-top: 0.25rem;
        }

        /* ----- STAT CARDS (compact, like your image) ----- */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card-mini {
            background: #fff;
            border-radius: 14px;
            padding: 1rem 1.2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
            border-left: 4px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .stat-card-mini:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        .stat-card-mini .icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            flex-shrink: 0;
        }
        .stat-card-mini .info {
            display: flex;
            flex-direction: column;
        }
        .stat-card-mini .number {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.2;
            color: #0f172a;
        }
        .stat-card-mini .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            font-weight: 500;
        }
        /* Border & icon colours (same palette) */
        .border-blue { border-left-color: #3b82f6; }
        .border-green { border-left-color: #22c55e; }
        .border-red { border-left-color: #ef4444; }
        .border-yellow { border-left-color: #f59e0b; }
        .border-purple { border-left-color: #8b5cf6; }
        .border-teal { border-left-color: #14b8a6; }

        .icon-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .icon-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .icon-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .icon-yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .icon-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .icon-teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }

        /* ----- CARDS ----- */
        .card {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            transition: all 0.2s;
        }
        .card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.07);
        }

        /* ----- BUTTONS ----- */
        .btn {
            display: inline-block;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 4px 10px rgba(59,130,246,0.25);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(59,130,246,0.35);
        }
        .btn-outline-primary {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
        .btn-outline-primary:hover {
            background: #3b82f6;
            color: #fff;
        }
        .btn-sm { padding: 0.25rem 0.7rem; font-size: 0.75rem; border-radius: 8px; }

        /* ----- TABLE ----- */
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .table th, .table td {
            padding: 0.7rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9edf2;
        }
        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.03em;
        }
        .table tbody tr:hover { background: #f1f5f9; }

        /* ----- BADGES ----- */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge i { margin-right: 3px; }

        /* ----- CHARTS ----- */
        .chart-container {
            position: relative;
            height: 220px;
        }

        /* ----- UTILITIES ----- */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        .mb-0 { margin-bottom: 0; }
        .mb-3 { margin-bottom: 1rem; }
        .mt-2 { margin-top: 0.5rem; }
        .fw-bold { font-weight: 700; }
        .text-muted { color: #94a3b8; }
        .text-center { text-align: center; }
        .small { font-size: 0.8rem; }
        .flex-wrap { flex-wrap: wrap; }
        .bg-teal { background: #14b8a6; color: #fff; }
        .bg-pink { background: #f472b6; color: #fff; }
        .bg-purple { background: #8b5cf6; color: #fff; }
        .text-purple { color: #8b5cf6; }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Greeting -->
        <div class="greeting">
            <h2>Welcome, <?php echo htmlspecialchars(ucwords($user_name)); ?> 👋</h2>
            <p>Here's an overview of your salary and payroll data.</p>
        </div>

        <!-- Stat Cards (compact, 6 items) -->
        <div class="stat-grid">
            <div class="stat-card-mini border-blue">
                <div class="icon icon-blue"><i class="fas fa-coins"></i></div>
                <div class="info">
                    <span class="number">₹6.42L</span>
                    <span class="label">Total Salary</span>
                </div>
            </div>
            <div class="stat-card-mini border-green">
                <div class="icon icon-green"><i class="fas fa-check-circle"></i></div>
                <div class="info">
                    <span class="number">₹4.86L</span>
                    <span class="label">Paid</span>
                </div>
            </div>
            <div class="stat-card-mini border-red">
                <div class="icon icon-red"><i class="fas fa-hourglass-half"></i></div>
                <div class="info">
                    <span class="number">₹1.56L</span>
                    <span class="label">Pending</span>
                </div>
            </div>
            <div class="stat-card-mini border-yellow">
                <div class="icon icon-yellow"><i class="fas fa-users"></i></div>
                <div class="info">
                    <span class="number">28</span>
                    <span class="label">Employees</span>
                </div>
            </div>
            <div class="stat-card-mini border-purple">
                <div class="icon icon-purple"><i class="fas fa-user-check"></i></div>
                <div class="info">
                    <span class="number">25</span>
                    <span class="label">Active</span>
                </div>
            </div>
            <div class="stat-card-mini border-teal">
                <div class="icon icon-teal"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="info">
                    <span class="number">3</span>
                    <span class="label">Overdue</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons Row -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Salary</button>
            <button class="btn btn-outline-primary"><i class="fas fa-file-pdf"></i> Generate Payslip</button>
            <button class="btn btn-outline-primary"><i class="fas fa-file-excel"></i> Export</button>
            <button class="btn btn-outline-primary"><i class="fas fa-print"></i> Print</button>
        </div>

        <!-- Table + Charts Row -->
        <div class="row" style="display:flex; flex-wrap:wrap; gap:1.5rem;">
            <!-- Table (takes 60% width) -->
            <div style="flex: 2; min-width: 300px;">
                <div class="card">
                    <h6 class="fw-bold mb-3"><i class="fas fa-list text-blue"></i> Employee Salary Details</h6>
                    <div class="table-responsive">
                        <table class="table table-hover" id="salaryTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Basic</th>
                                    <th>Allowances</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data from your image -->
                                <tr>
                                    <td>1</td>
                                    <td><strong>Dr. Anjali Sharma</strong></td>
                                    <td>Cardiology</td>
                                    <td>₹85,000</td>
                                    <td>₹15,000</td>
                                    <td>₹5,000</td>
                                    <td><span class="fw-bold">₹95,000</span></td>
                                    <td><span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><strong>Dr. Rajesh Kumar</strong></td>
                                    <td>Orthopedics</td>
                                    <td>₹75,000</td>
                                    <td>₹12,000</td>
                                    <td>₹4,000</td>
                                    <td><span class="fw-bold">₹83,000</span></td>
                                    <td><span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span></td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><strong>Nurse Priya Singh</strong></td>
                                    <td>ICU</td>
                                    <td>₹45,000</td>
                                    <td>₹8,000</td>
                                    <td>₹2,000</td>
                                    <td><span class="fw-bold">₹51,000</span></td>
                                    <td><span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span></td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td><strong>Mr. Vikram Patel</strong></td>
                                    <td>Pharmacy</td>
                                    <td>₹40,000</td>
                                    <td>₹6,000</td>
                                    <td>₹2,500</td>
                                    <td><span class="fw-bold">₹43,500</span></td>
                                    <td><span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span></td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td><strong>Ms. Ritu Gupta</strong></td>
                                    <td>Reception</td>
                                    <td>₹35,000</td>
                                    <td>₹5,000</td>
                                    <td>₹1,500</td>
                                    <td><span class="fw-bold">₹38,500</span></td>
                                    <td><span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> Overdue</span></td>
                                </tr>
                                <tr>
                                    <td>6</td>
                                    <td><strong>Mr. Suresh Rao</strong></td>
                                    <td>Maintenance</td>
                                    <td>₹30,000</td>
                                    <td>₹4,000</td>
                                    <td>₹1,000</td>
                                    <td><span class="fw-bold">₹33,000</span></td>
                                    <td><span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span></td>
                                </tr>
                                <tr>
                                    <td>7</td>
                                    <td><strong>Dr. Meena Reddy</strong></td>
                                    <td>Pediatrics</td>
                                    <td>₹80,000</td>
                                    <td>₹14,000</td>
                                    <td>₹4,500</td>
                                    <td><span class="fw-bold">₹89,500</span></td>
                                    <td><span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span></td>
                                </tr>
                                <tr>
                                    <td>8</td>
                                    <td><strong>Mr. Arun Nair</strong></td>
                                    <td>Administration</td>
                                    <td>₹50,000</td>
                                    <td>₹9,000</td>
                                    <td>₹3,000</td>
                                    <td><span class="fw-bold">₹56,000</span></td>
                                    <td><span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts (40% width) -->
            <div style="flex: 1; min-width: 250px;">
                <!-- Bar Chart -->
                <div class="card">
                    <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar text-primary"></i> Net Pay by Employee</h6>
                    <div class="chart-container">
                        <canvas id="salaryBarChart"></canvas>
                    </div>
                    <div class="mt-2 small d-flex justify-content-center gap-3 flex-wrap">
                        <span><i class="fas fa-circle text-success"></i> Paid</span>
                        <span><i class="fas fa-circle text-warning"></i> Pending</span>
                        <span><i class="fas fa-circle text-danger"></i> Overdue</span>
                    </div>
                </div>

                <!-- Pie Chart -->
                <div class="card mt-3">
                    <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie text-purple"></i> by Department</h6>
                    <div class="chart-container" style="height:180px;">
                        <canvas id="departmentPieChart"></canvas>
                    </div>
                    <div class="mt-2 small d-flex flex-wrap justify-content-center gap-1">
                        <span class="badge bg-primary">Cardiology</span>
                        <span class="badge bg-success">Orthopedics</span>
                        <span class="badge bg-warning text-dark">ICU</span>
                        <span class="badge bg-info text-dark">Pharmacy</span>
                        <span class="badge bg-danger">Reception</span>
                        <span class="badge bg-teal">Maintenance</span>
                        <span class="badge bg-pink">Pediatrics</span>
                        <span class="badge bg-purple">Admin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            $('#salaryTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                language: { emptyTable: 'No salary records found' }
            });
        });

        // Bar Chart – Net Pay per Employee (colored by status)
        const ctxBar = document.getElementById('salaryBarChart').getContext('2d');
        const employees = ['Dr. Anjali', 'Dr. Rajesh', 'Nurse Priya', 'Mr. Vikram', 'Ms. Ritu', 'Mr. Suresh', 'Dr. Meena', 'Mr. Arun'];
        const netPays = [95000, 83000, 51000, 43500, 38500, 33000, 89500, 56000];
        const statuses = ['Paid', 'Paid', 'Pending', 'Paid', 'Overdue', 'Paid', 'Pending', 'Paid'];
        const colors = statuses.map(s => {
            if (s === 'Paid') return '#22c55e';
            if (s === 'Pending') return '#f59e0b';
            return '#ef4444';
        });

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: employees,
                datasets: [{
                    label: 'Net Pay (₹)',
                    data: netPays,
                    backgroundColor: colors,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => '₹' + v.toLocaleString() } }
                }
            }
        });

        // Pie Chart – Department Distribution
        const ctxPie = document.getElementById('departmentPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Cardiology', 'Orthopedics', 'ICU', 'Pharmacy', 'Reception', 'Maintenance', 'Pediatrics', 'Admin'],
                datasets: [{
                    data: [95000, 83000, 51000, 43500, 38500, 33000, 89500, 56000],
                    backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6', '#f472b6', '#6366f1'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 6, font: { size: 9 } } }
                },
                cutout: '60%'
            }
        });
    </script>
</body>
</html>