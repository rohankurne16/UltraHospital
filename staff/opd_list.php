<?php 
session_start(); 
include "../config/db.php";

// Handle Delete Request
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Soft delete - update delete_flag to 1
    $deleteQuery = "UPDATE opd SET delete_flag = 1 WHERE id = '$delete_id'";
    
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('OPD record deleted successfully!');
            window.location.href='opd_list.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Error deleting OPD record: " . $conn->error . "');
            window.location.href='opd_list.php';
        </script>";
        exit();
    }
}

// Fetch all OPD records with patient and doctor details
$opdQuery = "SELECT o.*, p.patient_name, d.doctor_name, d.department 
             FROM opd o
             LEFT JOIN patients p ON o.patient_id = p.patient_id
             LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
             WHERE (o.delete_flag=0 OR o.delete_flag IS NULL)
             ORDER BY o.visit_date DESC, o.created_at DESC";
$opdResult = $conn->query($opdQuery);
$opdCount = $opdResult->num_rows;

// Get counts for stats
$totalOpdQuery = "SELECT COUNT(*) AS total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL)";
$totalOpdResult = $conn->query($totalOpdQuery);
$totalOpdCount = $totalOpdResult->fetch_assoc();

$todayOpdQuery = "SELECT COUNT(*) AS total FROM opd WHERE visit_date = CURDATE() AND (delete_flag=0 OR delete_flag IS NULL)";
$todayOpdResult = $conn->query($todayOpdQuery);
$todayOpdCount = $todayOpdResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - OPD List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            background-color: #f3f4f6;
            color: #111827;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .card-body {
            padding: 20px 24px;
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .delete-btn:hover {
            color: #dc2626;
            background: #fee2e2;
            transform: scale(1.1);
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideInRight 0.5s ease;
        }
        .toast-success {
            background: #22c55e;
        }
        .toast-error {
            background: #ef4444;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: white;
            border-radius: 16px;
            max-width: 420px;
            width: 90%;
            padding: 32px;
            text-align: center;
            animation: scaleIn 0.3s ease;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        .modal-box .modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .modal-box .modal-icon i {
            color: #dc2626;
            width: 32px;
            height: 32px;
        }
        .modal-box h3 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .modal-box p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .modal-box .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .modal-box .btn-group button {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        .modal-box .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }
        .modal-box .btn-cancel:hover {
            background: #e2e8f0;
        }
        .modal-box .btn-delete {
            background: #dc2626;
            color: white;
        }
        .modal-box .btn-delete:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff/staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="../staff/staff_dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m12 19-7-7 7-7"></path>
                                        <path d="M19 12H5"></path>
                                    </svg>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">OPD List</h1>
                                    <p class="text-gray-500">Manage all Outpatient Department visits.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="add_opd.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    Add New OPD
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total OPD Visits</div>
                                    <div class="stat-number"><?php echo $totalOpdCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-blue-50 text-blue-600">
                                    <i data-lucide="file-text" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Today's OPD</div>
                                    <div class="stat-number"><?php echo $todayOpdCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-green-50 text-green-600">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Patients</div>
                                    <div class="stat-number"><?php 
                                        $patientCountQuery = "SELECT COUNT(DISTINCT patient_id) AS total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL)";
                                        $patientCountResult = $conn->query($patientCountQuery);
                                        $patientCount = $patientCountResult->fetch_assoc();
                                        echo $patientCount['total'];
                                    ?></div>
                                </div>
                                <div class="stat-icon bg-purple-50 text-purple-600">
                                    <i data-lucide="users" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Doctors</div>
                                    <div class="stat-number"><?php 
                                        $doctorCountQuery = "SELECT COUNT(DISTINCT doctor_id) AS total FROM opd WHERE (delete_flag=0 OR delete_flag IS NULL)";
                                        $doctorCountResult = $conn->query($doctorCountQuery);
                                        $doctorCount = $doctorCountResult->fetch_assoc();
                                        echo $doctorCount['total'];
                                    ?></div>
                                </div>
                                <div class="stat-icon bg-amber-50 text-amber-600">
                                    <i data-lucide="stethoscope" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>All OPD Records</h3>
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    <input type="text" id="searchInput" placeholder="Search OPD records..." 
                                           class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchOPD()">
                                </div>
                            </div>
                        </div>
                        <div class="card-body overflow-x-auto">
                            <?php if ($opdCount > 0): ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OPD No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symptoms</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vitals</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="opdTableBody">
                                    <?php $i = 1; while ($row = $opdResult->fetch_assoc()): ?>
                                    <tr class="opd-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in"
                                        data-search="<?php echo strtolower($row['opd_no'] . ' ' . $row['patient_name'] . ' ' . $row['doctor_name'] . ' ' . $row['diagnosis']); ?>"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['patient_name']); ?>"
                                        data-opd="<?php echo htmlspecialchars($row['opd_no']); ?>">
                                        <td class="px-4 py-3 text-gray-500"><?php echo $i++; ?></td>
                                        <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($row['opd_no']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['department']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo date('M d, Y', strtotime($row['visit_date'])); ?></td>
                                        <td class="px-4 py-3 text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($row['symptoms']); ?></td>
                                        <td class="px-4 py-3 text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                <?php if ($row['bp']): ?>
                                                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">BP: <?php echo $row['bp']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($row['pulse']): ?>
                                                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">P: <?php echo $row['pulse']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($row['weight']): ?>
                                                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">W: <?php echo $row['weight']; ?>kg</span>
                                                <?php endif; ?>
                                                <?php if ($row['temperature']): ?>
                                                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">T: <?php echo $row['temperature']; ?>°F</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="view_opd.php?id=<?php echo $row['id']; ?>" 
                                                   class="action-btn p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-all" title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </a>
                                                <a href="edit_opd.php?id=<?php echo $row['id']; ?>" 
                                                   class="action-btn p-1.5 rounded-md text-indigo-600 hover:bg-indigo-50 transition-all" title="Edit">
                                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                </a>
                                                <button onclick="confirmDelete('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['patient_name']); ?>', '<?php echo htmlspecialchars($row['opd_no']); ?>')" 
                                                   class="action-btn p-1.5 rounded-md text-red-600 hover:bg-red-50 transition-all delete-btn" title="Delete">
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
                                <i data-lucide="file-text" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-600">No OPD Records Found</h3>
                                <p class="text-gray-400 mt-1">Start by adding a new OPD visit.</p>
                                <a href="add_opd.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-all">
                                    Add New OPD
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>
                                Showing <span id="visibleCount"><?php echo $opdCount; ?></span> records
                            </div>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon">
                <i data-lucide="alert-triangle" class="w-8 h-8"></i>
            </div>
            <h3>Delete OPD Record</h3>
            <p id="deleteMessage">Are you sure you want to delete this OPD record? This action cannot be undone.</p>
            <div class="btn-group">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let deleteId = null;

        function confirmDelete(id, patientName, opdNo) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete OPD record #${opdNo} for patient "${patientName}"? This action cannot be undone.`;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = `opd_list.php?delete_id=${deleteId}`;
            }
        });

        // Close modal on overlay click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        function searchOPD() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.opd-row');
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