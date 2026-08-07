<?php
require_once __DIR__ . '/../config/permission.php';
include '../header.php';
include '../Sidebar.php';

$page_title = 'Invoices';
?>
<div class="main-content" style="margin-top: 70px;">
    <div class="page-header">
        <div>
            <h3>📄 <?php echo $page_title; ?></h3>
            <p class="text-muted">Manage all patient and billing invoices</p>
        </div>
        <button class="btn btn-primary"><i class="fas fa-file-invoice"></i> Generate Invoice</button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-blue">
                    <div class="stat-icon bg-light-blue"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div class="stat-value">₹8,92,400</div>
                        <div class="stat-label">Total Billed</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-green">
                    <div class="stat-icon bg-light-green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-value">₹6,34,200</div>
                        <div class="stat-label">Paid</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-red">
                    <div class="stat-icon bg-light-red"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="stat-value">₹2,58,200</div>
                        <div class="stat-label">Outstanding</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="stat-card border-yellow">
                    <div class="stat-icon bg-light-yellow"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div class="stat-value">56</div>
                        <div class="stat-label">Total Invoices</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fas fa-list text-blue"></i> Recent Invoices</h6>
            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="invoicesTable">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>INV-2026-001</td>
                        <td>Ravi Singh</td>
                        <td>06 Aug 2026</td>
                        <td>₹25,000</td>
                        <td>₹25,000</td>
                        <td>₹0</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
                    <tr>
                        <td>INV-2026-002</td>
                        <td>Priya Mehta</td>
                        <td>05 Aug 2026</td>
                        <td>₹18,500</td>
                        <td>₹10,000</td>
                        <td>₹8,500</td>
                        <td><span class="badge bg-warning">Partial</span></td>
                    </tr>
                    <tr>
                        <td>INV-2026-003</td>
                        <td>Vijay Kumar</td>
                        <td>04 Aug 2026</td>
                        <td>₹32,000</td>
                        <td>₹0</td>
                        <td>₹32,000</td>
                        <td><span class="badge bg-danger">Unpaid</span></td>
                    </tr>
                    <tr>
                        <td>INV-2026-004</td>
                        <td>Sneha Patel</td>
                        <td>03 Aug 2026</td>
                        <td>₹12,200</td>
                        <td>₹12,200</td>
                        <td>₹0</td>
                        <td><span class="badge bg-success">Paid</span></td>
                    </tr>
                    <tr>
                        <td>INV-2026-005</td>
                        <td>Amit Sharma</td>
                        <td>02 Aug 2026</td>
                        <td>₹45,000</td>
                        <td>₹45,000</td>
                        <td>₹0</td>
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
        $('#invoicesTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10
        });
    });
</script>
</body>
</html>