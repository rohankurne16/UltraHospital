<?php
require_once __DIR__ . '/../config/permission.php';
include '../header.php';
include '../Sidebar.php';

 $page_title = 'Ledger';
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
            flex-shrink: 0;
        }
        /* Gradients */
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
        .mb-4 { margin-bottom: 1.5rem; }
        .fw-bold { font-weight: 700; color: #1e293b; font-size: 1rem; }
        .text-blue { color: #3b82f6; margin-right: 0.5rem; }

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
            <h3>📒 <?php echo $page_title; ?></h3>
            <p class="text-muted">Complete financial ledger with debit/credit entries</p>
        </div>
        <button class="btn btn-primary"><i class="fas fa-print"></i> Print Ledger</button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-green">
                    <div class="stat-icon bg-light-green"><i class="fas fa-arrow-circle-right"></i></div>
                    <div>
                        <div class="stat-value">₹12,45,600</div>
                        <div class="stat-label">Total Debit</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-arrow-circle-left"></i></div>
                    <div>
                        <div class="stat-value">₹9,76,300</div>
                        <div class="stat-label">Total Credit</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-blue">
                    <div class="stat-icon bg-light-blue"><i class="fas fa-balance-scale"></i></div>
                    <div>
                        <div class="stat-value">₹2,69,300</div>
                        <div class="stat-label">Balance</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-yellow">
                    <div class="stat-icon bg-light-yellow"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <div class="stat-value">284</div>
                        <div class="stat-label">Total Entries</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fas fa-book text-blue"></i> Recent Entries</h6>
            <a href="#" class="btn btn-sm btn-outline-primary">View Full Ledger</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="ledgerTable">
                <thead>
                    <tr>
                        <th>Entry ID</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Debit (₹)</th>
                        <th>Credit (₹)</th>
                        <th>Balance (₹)</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>LED-001</td>
                        <td>06 Aug 2026</td>
                        <td>Patient Payment - Ravi Singh</td>
                        <td>25,000</td>
                        <td>0</td>
                        <td>2,69,300</td>
                        <td>Receivable</td>
                    </tr>
                    <tr>
                        <td>LED-002</td>
                        <td>05 Aug 2026</td>
                        <td>Salary Payment</td>
                        <td>0</td>
                        <td>85,000</td>
                        <td>2,44,300</td>
                        <td>Expense</td>
                    </tr>
                    <tr>
                        <td>LED-003</td>
                        <td>04 Aug 2026</td>
                        <td>Pharma Supplies</td>
                        <td>0</td>
                        <td>22,800</td>
                        <td>2,21,500</td>
                        <td>Expense</td>
                    </tr>
                    <tr>
                        <td>LED-004</td>
                        <td>03 Aug 2026</td>
                        <td>Insurance Claim - Anita</td>
                        <td>45,000</td>
                        <td>0</td>
                        <td>2,66,500</td>
                        <td>Receivable</td>
                    </tr>
                    <tr>
                        <td>LED-005</td>
                        <td>02 Aug 2026</td>
                        <td>Electricity Bill</td>
                        <td>0</td>
                        <td>12,400</td>
                        <td>2,54,100</td>
                        <td>Expense</td>
                    </tr>
                    <tr>
                        <td>LED-006</td>
                        <td>01 Aug 2026</td>
                        <td>Patient Payment - Priya</td>
                        <td>10,000</td>
                        <td>0</td>
                        <td>2,64,100</td>
                        <td>Receivable</td>
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