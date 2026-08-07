<?php
require_once __DIR__ . '/../config/permission.php';
include '../header.php';
include '../Sidebar.php';

$page_title = 'Payments';
?>
<div class="main-content" style="margin-top: 70px;">
    <div class="page-header">
        <div>
            <h3>💳 <?php echo $page_title; ?></h3>
            <p class="text-muted">View all incoming and outgoing payments</p>
        </div>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> New Payment</button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-green">
                    <div class="stat-icon bg-light-green"><i class="fas fa-arrow-down"></i></div>
                    <div>
                        <div class="stat-value">₹4,56,200</div>
                        <div class="stat-label">Total Received</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-arrow-up"></i></div>
                    <div>
                        <div class="stat-value">₹1,24,500</div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-yellow">
                    <div class="stat-icon bg-light-yellow"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="stat-value">₹78,300</div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-purple">
                    <div class="stat-icon bg-light-purple"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div class="stat-value">342</div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fas fa-list text-blue"></i> Recent Payments</h6>
            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="paymentsTable">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Patient / Vendor</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PAY-001</td>
                        <td>Ramesh Kumar</td>
                        <td>Invoice</td>
                        <td>06 Aug 2026</td>
                        <td>₹15,000</td>
                        <td>UPI</td>
                        <td><span class="badge bg-success">Completed</span></td>
                    </tr>
                    <tr>
                        <td>PAY-002</td>
                        <td>Pharma Supplies</td>
                        <td>Expense</td>
                        <td>05 Aug 2026</td>
                        <td>₹22,800</td>
                        <td>Bank Transfer</td>
                        <td><span class="badge bg-success">Completed</span></td>
                    </tr>
                    <tr>
                        <td>PAY-003</td>
                        <td>Sneha Patel</td>
                        <td>Invoice</td>
                        <td>04 Aug 2026</td>
                        <td>₹8,500</td>
                        <td>Cash</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                    </tr>
                    <tr>
                        <td>PAY-004</td>
                        <td>Electricity Dept</td>
                        <td>Expense</td>
                        <td>03 Aug 2026</td>
                        <td>₹12,400</td>
                        <td>Cheque</td>
                        <td><span class="badge bg-success">Completed</span></td>
                    </tr>
                    <tr>
                        <td>PAY-005</td>
                        <td>Anita Sharma</td>
                        <td>Insurance</td>
                        <td>02 Aug 2026</td>
                        <td>₹45,000</td>
                        <td>Card</td>
                        <td><span class="badge bg-success">Completed</span></td>
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
        $('#paymentsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10
        });
    });
</script>
</body>
</html>