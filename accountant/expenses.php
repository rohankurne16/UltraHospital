<?php
require_once __DIR__ . '/../config/permission.php';
// (Optional) Add role/permission check here if needed
include '../header.php';
include '../Sidebar.php';

$page_title = 'Expenses';
?>
<div class="main-content" style="margin-top: 70px;">
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
                <div class="stat-card border-red">
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
                <div class="stat-card border-yellow">
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
                <div class="stat-card border-blue">
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
                <div class="stat-card border-green">
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
<script>
    $(document).ready(function() {
        $('#expensesTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10
        });
    });
</script>
</body>
</html>