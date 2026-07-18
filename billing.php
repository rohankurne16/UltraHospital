<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["staff_id"]) && !isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit();
}

if (isset($_GET["delete_id"]) && !empty($_GET["delete_id"])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET["delete_id"]);
    $deleteQuery = "UPDATE billing SET delete_flag = 1 WHERE id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>alert('Bill deleted successfully!'); window.location.href='billing.php';</script>";
        exit();
    }
}

$billQuery = "SELECT b.*, p.patient_name, p.mobile 
              FROM billing b
              LEFT JOIN patients p ON b.patient_id = p.patient_id
              WHERE (b.delete_flag=0 OR b.delete_flag IS NULL)
              ORDER BY b.created_at DESC";
$billResult = $conn->query($billQuery);
$billCount = $billResult->num_rows;

$totalBillsQuery = "SELECT COUNT(*) AS total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL)";
$totalBillsResult = $conn->query($totalBillsQuery);
$totalBillsCount = $totalBillsResult->fetch_assoc();

$pendingBillsQuery = "SELECT COUNT(*) AS total FROM billing WHERE pending_amount > 0 AND (delete_flag=0 OR delete_flag IS NULL)";
$pendingBillsResult = $conn->query($pendingBillsQuery);
$pendingBillsCount = $pendingBillsResult->fetch_assoc();

$paidBillsQuery = "SELECT COUNT(*) AS total FROM billing WHERE pending_amount = 0 AND paid_amount > 0 AND (delete_flag=0 OR delete_flag IS NULL)";
$paidBillsResult = $conn->query($paidBillsQuery);
$paidBillsCount = $paidBillsResult->fetch_assoc();

$totalRevenueQuery = "SELECT SUM(paid_amount) AS total FROM billing WHERE (delete_flag=0 OR delete_flag IS NULL)";
$totalRevenueResult = $conn->query($totalRevenueQuery);
$totalRevenue = $totalRevenueResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Billing List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .stat-card { background: white; border-radius: 12px; padding: 20px 24px; border: 1px solid #e5e7eb; transition: all 0.2s ease; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: 14px; color: #64748b; font-weight: 500; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-partial { background: #dbeafe; color: #1e40af; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; border-radius: 16px; max-width: 420px; width: 90%; padding: 32px; text-align: center; }
        .modal-box .modal-icon { width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .modal-box .modal-icon i { color: #dc2626; width: 32px; height: 32px; }
        .modal-box h3 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .modal-box p { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .modal-box .btn-group { display: flex; gap: 12px; justify-content: center; }
        .modal-box .btn-group button { padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; border: none; }
        .modal-box .btn-cancel { background: #f1f5f9; color: #475569; }
        .modal-box .btn-delete { background: #dc2626; color: white; }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'staff_sidebar.php'; ?>
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="../staff/staff_dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">Billing</h1>
                                    <p class="text-gray-500">Manage all patient bills and invoices.</p>
                                </div>
                            </div>
                            <a href="create_bill.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Create Bill
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div><div class="stat-label">Total Bills</div><div class="stat-number"><?php echo $totalBillsCount['total']; ?></div></div>
                                <div class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="file-text" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div><div class="stat-label">Pending Bills</div><div class="stat-number"><?php echo $pendingBillsCount['total']; ?></div></div>
                                <div class="stat-icon bg-amber-50 text-amber-600"><i data-lucide="clock" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div><div class="stat-label">Paid Bills</div><div class="stat-number"><?php echo $paidBillsCount['total']; ?></div></div>
                                <div class="stat-icon bg-green-50 text-green-600"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div><div class="stat-label">Total Revenue</div><div class="stat-number">₹<?php echo number_format($totalRevenue['total'] ?? 0, 2); ?></div></div>
                                <div class="stat-icon bg-purple-50 text-purple-600"><i data-lucide="indian-rupee" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>All Bills</h3>
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                <input type="text" id="searchInput" placeholder="Search bills..." class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg outline-none" onkeyup="searchBills()">
                            </div>
                        </div>
                        <div class="card-body overflow-x-auto">
                            <?php if ($billCount > 0): ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left">#</th>
                                        <th class="px-4 py-3 text-left">Bill No</th>
                                        <th class="px-4 py-3 text-left">Patient</th>
                                        <th class="px-4 py-3 text-left">Date</th>
                                        <th class="px-4 py-3 text-left">Service</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-right">Discount (%)</th>
                                        <th class="px-4 py-3 text-right">Paid</th>
                                        <th class="px-4 py-3 text-right">Pending</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="billTableBody">
                                    <?php $i = 1; while ($row = $billResult->fetch_assoc()): 
                                        $status = ($row['pending_amount'] <= 0) ? 'Paid' : (($row['paid_amount'] > 0) ? 'Partial' : 'Pending');
                                        $statusClass = ($status == 'Paid') ? 'status-paid' : (($status == 'Partial') ? 'status-partial' : 'status-pending');
                                        $subtotal = $row['qty'] * $row['rate'];
                                    ?>
                                    <tr class="bill-row border-b border-gray-100 hover:bg-gray-50" data-search="<?php echo strtolower($row['bill_no'] . ' ' . $row['patient_name'] . ' ' . $row['service_name']); ?>">
                                        <td class="px-4 py-3 text-gray-500"><?php echo $i++; ?></td>
                                        <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($row['bill_no']); ?></td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <div><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($row['mobile']); ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo date('d/m/Y', strtotime($row['bill_date'])); ?></td>
                                        <td class="px-4 py-3 text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($row['service_name']); ?></td>
                                        <td class="px-4 py-3 text-right font-medium">₹<?php echo number_format($subtotal, 2); ?></td>
                                        <td class="px-4 py-3 text-right text-gray-700"><?php echo number_format($row['discount'], 2); ?>%</td>
                                        <td class="px-4 py-3 text-right text-green-600">₹<?php echo number_format($row['paid_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-right text-red-600">₹<?php echo number_format($row['pending_amount'], 2); ?></td>
                                        <td class="px-4 py-3"><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="view_bill.php?id=<?php echo $row['id']; ?>" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                                <a href="edit_bill.php?id=<?php echo $row['id']; ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                                <a href="print_bill.php?id=<?php echo $row['id']; ?>" class="p-1.5 text-gray-600 hover:bg-gray-50 rounded-md" target="_blank"><i data-lucide="printer" class="w-4 h-4"></i></a>
                                                <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['bill_no']; ?>', '<?php echo $row['patient_name']; ?>')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="text-center py-12">
                                <i data-lucide="receipt" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-600">No Bills Found</h3>
                                <a href="create_bill.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Create Bill</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-500">
                            <div>Showing <span id="visibleCount"><?php echo $billCount; ?></span> bills</div>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-1 border rounded-md disabled:opacity-50" disabled>Previous</button>
                                <span class="px-3 py-1 bg-blue-600 text-white rounded-md">1</span>
                                <button class="px-3 py-1 border rounded-md disabled:opacity-50" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i data-lucide="alert-triangle" class="w-8 h-8"></i></div>
            <h3>Delete Bill</h3>
            <p id="deleteMessage">Are you sure you want to delete this bill?</p>
            <div class="btn-group">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        let deleteId = null;
        function confirmDelete(id, billNo, patientName) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent = `Delete bill #${billNo} for patient "${patientName}"?`;
            document.getElementById('deleteModal').classList.add('active');
        }
        function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); deleteId = null; }
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() { if (deleteId) window.location.href = 'billing.php?delete_id=' + deleteId; });
        function searchBills() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.bill-row');
            let count = 0;
            rows.forEach(row => {
                const visible = row.dataset.search.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
                if (visible) count++;
            });
            document.getElementById('visibleCount').textContent = count;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
