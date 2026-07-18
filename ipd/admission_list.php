<?php 
session_start(); 
include "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

// Handle Delete Request
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE ipd_admissions SET delete_flag = 1 WHERE id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Admission record deleted successfully!');
            window.location.href='admission_list.php';
        </script>";
        exit();
    }
}

// Handle Discharge Request
if (isset($_GET['discharge_id']) && !empty($_GET['discharge_id'])) {
    $discharge_id = mysqli_real_escape_string($conn, $_GET['discharge_id']);
    $dischargeQuery = "UPDATE ipd_admissions SET status = 'Discharged' WHERE id = '$discharge_id'";
    if ($conn->query($dischargeQuery)) {
        echo "<script>
            alert('Patient discharged successfully!');
            window.location.href='admission_list.php';
        </script>";
        exit();
    }
}

// Fetch all admissions with patient, doctor, and ward details
$admissionQuery = "SELECT a.*, 
                    p.patient_name, p.mobile,
                    d.doctor_name,
                    w.ward_name
                    FROM ipd_admissions a
                    LEFT JOIN patients p ON a.patient_id = p.patient_id
                    LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
                    LEFT JOIN wards w ON a.ward_id = w.ward_id
                    WHERE (a.delete_flag=0 OR a.delete_flag IS NULL)
                    ORDER BY a.created_at DESC";
$admissionResult = $conn->query($admissionQuery);

// Check if query was successful
if ($admissionResult) {
    $admissionCount = $admissionResult->num_rows;
} else {
    $admissionCount = 0;
    // For debugging, you could uncomment the next line:
    // die("Query failed: " . $conn->error);
}

// Get counts for stats
$totalQuery = "SELECT COUNT(*) AS total FROM ipd_admissions WHERE (delete_flag=0 OR delete_flag IS NULL)";
$totalResult = $conn->query($totalQuery);
$totalCount = ($totalResult) ? $totalResult->fetch_assoc() : ['total' => 0];

$admittedQuery = "SELECT COUNT(*) AS total FROM ipd_admissions WHERE status = 'Admitted' AND (delete_flag=0 OR delete_flag IS NULL)";
$admittedResult = $conn->query($admittedQuery);
$admittedCount = ($admittedResult) ? $admittedResult->fetch_assoc() : ['total' => 0];

$dischargedQuery = "SELECT COUNT(*) AS total FROM ipd_admissions WHERE status = 'Discharged' AND (delete_flag=0 OR delete_flag IS NULL)";
$dischargedResult = $conn->query($dischargedQuery);
$dischargedCount = ($dischargedResult) ? $dischargedResult->fetch_assoc() : ['total' => 0];

// Today's Admissions
$todayQuery = "SELECT COUNT(*) AS total FROM ipd_admissions WHERE DATE(admission_date) = CURDATE() AND (delete_flag=0 OR delete_flag IS NULL)";
$todayResult = $conn->query($todayQuery);
$todayCount = ($todayResult) ? $todayResult->fetch_assoc() : ['total' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Admission List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }
        
        .stat-card { background: white; border-radius: 12px; padding: 20px 24px; border: 1px solid #e5e7eb; transition: all 0.2s ease; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: 14px; color: #64748b; font-weight: 500; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-view { color: #3b82f6; }
        .action-btn-view:hover { background: #dbeafe; }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; }
        .action-btn-discharge { color: #22c55e; }
        .action-btn-discharge:hover { background: #d1fae5; }
        
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-admitted { background: #dbeafe; color: #1e40af; }
        .status-discharged { background: #d1fae5; color: #065f46; }
        
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; border-radius: 16px; max-width: 420px; width: 90%; padding: 32px; text-align: center; animation: scaleIn 0.3s ease; }
        @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-box .modal-icon { width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .modal-box .modal-icon i { color: #dc2626; width: 32px; height: 32px; }
        .modal-box h3 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .modal-box p { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .modal-box .btn-group { display: flex; gap: 12px; justify-content: center; }
        .modal-box .btn-group button { padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; border: none; }
        .modal-box .btn-cancel { background: #f1f5f9; color: #475569; }
        .modal-box .btn-cancel:hover { background: #e2e8f0; }
        .modal-box .btn-delete { background: #dc2626; color: white; }
        .modal-box .btn-delete:hover { background: #b91c1c; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
        @media (max-width: 640px) {
            .main-content { padding: 12px; }
            .stat-card .stat-number { font-size: 22px; }
            .card-body { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff/staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="../staff/staff_dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">IPD Admissions</h1>
                                    <p class="text-gray-500">Manage all in-patient department admissions.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="admit_patient.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    Admit Patient
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Admissions</div>
                                    <div class="stat-number"><?php echo $totalCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-blue-50 text-blue-600">
                                    <i data-lucide="hospital" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Admitted</div>
                                    <div class="stat-number"><?php echo $admittedCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-green-50 text-green-600">
                                    <i data-lucide="user-check" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Discharged</div>
                                    <div class="stat-number"><?php echo $dischargedCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-gray-50 text-gray-600">
                                    <i data-lucide="user-x" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Today's Admissions</div>
                                    <div class="stat-number"><?php echo $todayCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-amber-50 text-amber-600">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admissions Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3>All Admissions</h3>
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    <input type="text" id="searchInput" placeholder="Search admissions..." 
                                           class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchAdmissions()">
                                </div>
                            </div>
                        </div>
                        <div class="card-body overflow-x-auto">
                            <?php if ($admissionCount > 0): ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ward</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room/Bed</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admissionTableBody">
                                    <?php $i = 1; while ($row = $admissionResult->fetch_assoc()): ?>
                                    <tr class="admission-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in"
                                        data-search="<?php echo strtolower($row['admission_no'] . ' ' . $row['patient_name'] . ' ' . $row['doctor_name']); ?>">
                                        <td class="px-4 py-3 text-gray-500"><?php echo $i++; ?></td>
                                        <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($row['admission_no']); ?></td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <div>
                                                <div><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($row['mobile']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['ward_name'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <?php if ($row['room_no'] || $row['bed_no']): ?>
                                                <?php echo htmlspecialchars($row['room_no'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($row['bed_no'] ?? 'N/A'); ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo date('M d, Y', strtotime($row['admission_date'])); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'admitted'); ?>">
                                                <?php echo htmlspecialchars($row['status'] ?? 'Admitted'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="view_admission.php?id=<?php echo $row['id']; ?>" 
                                                   class="action-btn action-btn-view" title="View Details">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </a>
                                                <a href="edit_admission.php?id=<?php echo $row['id']; ?>" 
                                                   class="action-btn action-btn-edit" title="Edit Admission">
                                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                </a>
                                                <?php if ($row['status'] == 'Admitted'): ?>
                                                    <a href="admission_list.php?discharge_id=<?php echo $row['id']; ?>" 
                                                       class="action-btn action-btn-discharge" title="Discharge Patient"
                                                       onclick="return confirm('Are you sure you want to discharge this patient?')">
                                                        <i data-lucide="log-out" class="w-4 h-4"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['admission_no']); ?>', '<?php echo htmlspecialchars($row['patient_name']); ?>')" 
                                                        class="action-btn action-btn-delete" title="Delete Admission">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="text-center py-12">
                                <i data-lucide="hospital" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-600">No Admissions Found</h3>
                                <p class="text-gray-400 mt-1">Start by admitting a new patient.</p>
                                <a href="admit_patient.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-all">
                                    Admit Patient
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Showing <span id="visibleCount"><?php echo $admissionCount; ?></span> admissions</div>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                                <span class="px-3 py-1 bg-blue-600 text-white rounded-md">1</span>
                                <button class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon">
                <i data-lucide="alert-triangle" class="w-8 h-8"></i>
            </div>
            <h3>Delete Admission</h3>
            <p id="deleteMessage">Are you sure you want to delete this admission record? This action cannot be undone.</p>
            <div class="btn-group">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let deleteId = null;

        function confirmDelete(id, admissionNo, patientName) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete admission #${admissionNo} for patient "${patientName}"? This action cannot be undone.`;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = `admission_list.php?delete_id=${deleteId}`;
            }
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        function searchAdmissions() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.admission-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const searchData = row.dataset.search || '';
                if (searchData.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
