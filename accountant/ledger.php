<?php
require_once __DIR__ . '/../config/permission.php';
include '../header.php';
include '../Sidebar.php';

$page_title = 'Ledger';
?>
<div class="main-content" style="margin-top: 70px;">
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
<script>
    $(document).ready(function() {
        $('#ledgerTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10
        });
    });
</script>
</body>
</html>