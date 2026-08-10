<?php
require_once __DIR__ . '/../config/permission.php';
// (Optional) Add role/permission check here if needed
include '../header.php';
include '../Sidebar.php';

 $page_title = 'Expenses';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* Main Content Layout */
       .main-content {
    margin-left: 260px;
    padding: 0px 15px 61px;
    width: calc(100% - 260px);
    min-height: calc(100vh - 67px);
    background: #f1f5f9;
    margin-top: 23px;
        }
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-header h3 {
            font-weight: 700;
            font-size: 1.8rem;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .text-muted { color: #64748b !important; font-size: 0.9rem; margin: 0; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .btn-sm:hover {
            background: #dbeafe;
        }

        /* Grid System */
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .col-md-6 { flex: 1 1 45%; }
        .col-xl-3 { flex: 1 1 22%; }

        /* Cards */
        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            padding: 1.5rem;
            transition: all 0.3s ease;
            height: 100%;
        }
        .card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            transform: translateY(-3px);
        }
        .p-3 { padding: 1.5rem !important; }

        /* Summary Stat Cards */
        .stat-card {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
        }
        .bg-light-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .bg-light-yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .bg-light-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .bg-light-green { background: linear-gradient(135deg, #22c55e, #16a34a); }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Table Styles */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-0 { margin-bottom: 0; }
        .fw-bold { font-weight: 700; color: #1e293b; font-size: 1rem; }
        .text-red { color: #ef4444; margin-right: 0.5rem; }
        .text-primary { color: #2563eb; }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .table tbody td {
            padding: 0.85rem 1rem;
            border-top: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .bg-success { background: #dcfce7; color: #16a34a; }
        .bg-warning { background: #fef3c7; color: #d97706; }
        .bg-danger { background: #fee2e2; color: #dc2626; }
        
    </style>
</head>
<body>

<div class="main-content">
    <div class="page-header">
        <div>
            <h3>💸 <?php echo $page_title; ?></h3>
            <p class="text-muted">Track and manage all hospital expenses</p>
        </div>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Expense</button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card">
                    <div class="stat-icon bg-light-red"><i class="fas fa-receipt"></i></div>
                    <div>
                        <div class="stat-value">₹1,24,500</div>
                        <div class="stat-label">Total Expenses</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card">
                    <div class="stat-icon bg-light-yellow"><i class="fas fa-calendar-week"></i></div>
                    <div>
                        <div class="stat-value">₹32,800</div>
                        <div class="stat-label">This Month</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card">
                    <div class="stat-icon bg-light-blue"><i class="fas fa-chart-pie"></i></div>
                    <div>
                        <div class="stat-value">12</div>
                        <div class="stat-label">Categories</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card">
                    <div class="stat-icon bg-light-green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-value">85%</div>
                        <div class="stat-label">Budget Utilized</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Table -->
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fas fa-list text-red"></i> Recent Expenses</h6>
            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="expensesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Mode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Utilities</td>
                        <td>Electricity Bill</td>
                        <td>05 Aug 2026</td>
                        <td>₹12,400</td>
                        <td>Bank Transfer</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Salary</td>
                        <td>Staff Salaries (July)</td>
                        <td>01 Aug 2026</td>
                        <td>₹85,000</td>
                        <td>Bank Transfer</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Medical Supplies</td>
                        <td>Syringes &amp; Gloves</td>
                        <td>28 Jul 2026</td>
                        <td>₹18,200</td>
                        <td>Cash</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Maintenance</td>
                        <td>AC Repair</td>
                        <td>25 Jul 2026</td>
                        <td>₹5,500</td>
                        <td>UPI</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Miscellaneous</td>
                        <td>Office Stationery</td>
                        <td>20 Jul 2026</td>
                        <td>₹3,400</td>
                        <td>Cash</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
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

</body>
</html>