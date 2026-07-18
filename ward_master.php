<?php 
session_start(); 
include "config/hospital.php";

$hid=$_SESSION["hospital_id"];

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

// Handle Delete Request
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE ward_master SET delete_flag = 1 WHERE ward_id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Ward deleted successfully!');
            window.location.href='ward_master.php';
        </script>";
        exit();
    }
}

// Fetch all wards
$wardQuery = "SELECT * FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid' ORDER BY ward_name ASC";
$wardResult = $conn->query($wardQuery);
$wardCount = $wardResult->num_rows;

// Get counts for stats
$totalQuery = "SELECT COUNT(*) AS total FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid'";
$totalResult = $conn->query($totalQuery);
$totalCount = $totalResult->fetch_assoc();

$activeQuery = "SELECT COUNT(*) AS total FROM ward_master WHERE status = 'Available' AND (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid'";
$activeResult = $conn->query($activeQuery);
$activeCount = $activeResult->fetch_assoc();

$inactiveQuery = "SELECT COUNT(*) AS total FROM ward_master WHERE status = 'Occupied' AND (delete_flag=0 OR delete_flag IS NULL)and hospital_id='$hid'";
$inactiveResult = $conn->query($inactiveQuery);
$inactiveCount = $inactiveResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> -IPD Appointments</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
            width: 100%;
        }
        
        /* Stat Cards */
        .stat-card { 
            background: white; 
            border-radius: 12px; 
            padding: 20px 24px; 
            border: 2px solid #e5e7eb; 
            transition: all 0.3s ease; 
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #10b981);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover { 
            box-shadow: 0 8px 16px rgba(0,0,0,0.08); 
            transform: translateY(-4px); 
            border-color: #3b82f6;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card.active {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15);
        }
        
        .stat-card.active::before {
            opacity: 1;
        }
        
        .stat-card .stat-number { 
            font-size: 32px; 
            font-weight: 700; 
            color: #0f172a; 
            margin: 8px 0;
        }
        
        .stat-card .stat-label { 
            font-size: 13px; 
            color: #64748b; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .stat-icon { 
            width: 48px; 
            height: 48px; 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-size: 24px;
        }
        
        /* Cards */
        .card { 
            background: white; 
            border-radius: 12px; 
            border: 1px solid #e5e7eb; 
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .card-header { 
            padding: 16px 24px; 
            border-bottom: 1px solid #e5e7eb; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .card-header h3 { 
            font-size: 16px; 
            font-weight: 700; 
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #dbeafe;
            color: #0c4a6e;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .filter-badge .close-btn {
            cursor: pointer;
            font-weight: 700;
            margin-left: 4px;
        }
        
        .card-body { 
            padding: 20px 24px; 
        }
        
        .fade-in { 
            animation: fadeIn 0.3s ease; 
        }
        
        @keyframes fadeIn { 
            from { 
                opacity: 0; 
                transform: translateY(5px); 
            } 
            to { 
                opacity: 1; 
                transform: translateY(0); 
            } 
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead tr {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-bottom: 2px solid #e5e7eb;
        }
        
        thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        tbody tr:hover {
            background: #f8fafc;
            box-shadow: inset 0 0 0 1px #e5e7eb;
        }
        
        tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: #0f172a;
        }
        
        /* Action Buttons */
        .action-btn { 
            transition: all 0.2s ease; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            padding: 8px; 
            border-radius: 6px;
            border: 1px solid transparent;
            font-size: 16px;
        }
        
        .action-btn:hover { 
            transform: scale(1.1);
        }
        
        .action-btn-edit { 
            color: #8b5cf6;
        }
        
        .action-btn-edit:hover { 
            background: #ede9fe;
            border-color: #8b5cf6;
        }
        
        .action-btn-delete { 
            color: #ef4444;
        }
        
        .action-btn-delete:hover { 
            background: #fee2e2;
            border-color: #ef4444;
        }
        
        /* Status Badges */
        .status-badge { 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
            display: inline-block;
            text-transform: capitalize;
            transition: all 0.2s ease;
        }
        
        .status-available {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-occupied {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .status-badge:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Modal */
        .modal-overlay { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: rgba(0,0,0,0.6); 
            z-index: 9998; 
            align-items: center; 
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        
        .modal-overlay.active { 
            display: flex;
            animation: fadeIn 0.2s ease;
        }
        
        .modal-box { 
            background: white; 
            border-radius: 16px; 
            max-width: 420px; 
            width: 90%; 
            padding: 32px; 
            text-align: center; 
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
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
            font-size: 32px;
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
            line-height: 1.6;
        }
        
        .modal-box .btn-group { 
            display: flex; 
            gap: 12px; 
            justify-content: center; 
        }
        
        .modal-box .btn-cancel { 
            background: #f1f5f9; 
            color: #475569;
            padding: 10px 20px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .modal-box .btn-cancel:hover { 
            background: #e2e8f0;
            border-color: #94a3b8;
        }
        
        .modal-box .btn-delete { 
            background: #dc2626; 
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .modal-box .btn-delete:hover { 
            background: #b91c1c;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        /* Search Input */
        .search-input {
            position: relative;
        }
        
        .search-input input {
            padding-left: 36px;
            transition: all 0.2s ease;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px 8px 36px;
        }
        
        .search-input input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-input i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 16px;
            display: block;
        }
        
        .empty-state h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 13px;
            color: #64748b;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) { 
            .main-content { 
                margin-left: 0; 
                padding: 16px; 
            } 
        }
        
        @media (max-width: 768px) { 
            .main-content { 
                padding: 12px; 
            } 
            
            .stat-card .stat-number { 
                font-size: 24px; 
            }
            
            .card-body { 
                padding: 16px; 
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            table {
                font-size: 12px;
            }
            
            thead th, tbody td {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <!-- Sidebar -->
            <?php include 'Sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">Ward Management</h1>
                                    <p class="text-gray-500">Manage hospital wards and rooms.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="add_ward.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                    <i class="fa-solid fa-plus mr-2"></i>
                                    Add Ward
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Grid - Clickable -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                        <!-- Total Wards Card -->
                        <div class="stat-card" onclick="filterByStatus('all')" id="stat-all">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Wards</div>
                                    <div class="stat-number"><?php echo $totalCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-blue-50 text-blue-600">
                                    <i class="fa-solid fa-building"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Wards Card -->
                        <div class="stat-card" onclick="filterByStatus('Available')" id="stat-active">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Available
                                        
                                    </div>
                                    <div class="stat-number"><?php echo $activeCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-green-50 text-green-600">
                                    <i class="fa-solid fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Inactive Wards Card -->
                        <div class="stat-card" onclick="filterByStatus('Occupied')" id="stat-inactive">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Occupied</div>
                                    <div class="stat-number"><?php echo $inactiveCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-red-50 text-red-600">
                                    <i class="fa-solid fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wards Table Card -->
                    <div class="card">
                        <div class="card-header">
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%;">
                                <h3>
                                    <i class="fa-solid fa-list"></i>
                                    All Wards
                                </h3>
                                <div id="filterBadge" style="display: none;">
                                    <span class="filter-badge">
                                        Filtered by: <span id="filterText"></span>
                                        <span class="close-btn" onclick="clearFilter()">✕</span>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="search-input">
                                    <i class="fa-solid fa-search"></i>
                                    <input type="text" id="searchInput" placeholder="Search wards..." 
                                           class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchWards()">
                                </div>
                            </div>
                        </div>
                        <div class="card-body overflow-x-auto">
                            <?php if ($wardCount > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ward Name</th>
                                        <th>Ward Type</th>
                                        <th>Floor</th>
                                        <th>Status</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="wardTableBody">
                                    <?php $i = 1; while ($row = $wardResult->fetch_assoc()): ?>
                                        
                                    <tr class="ward-row fade-in"
                               data-search="<?php echo strtolower(trim($row['ward_name'])); ?>"
                                       data-status="<?php echo trim($row['status']); ?>"
                                        onclick="window.location.href='view_ward.php?id=<?php echo $row['ward_id']; ?>'">
                                        <td class="text-gray-500"><?php echo $i++; ?></td>
                                        <td style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($row['ward_name']); ?></td>
                                        <td class="text-gray-700"><?php echo htmlspecialchars($row['ward_type'] ?? 'N/A'); ?></td>
                                        <td class="text-gray-700"><?php echo htmlspecialchars($row['floor_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'Available'); ?>">
                                                <?php echo htmlspecialchars($row['status'] ?? 'Available'); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="edit_ward.php?id=<?php echo $row['ward_id']; ?>" 
                                                   class="action-btn action-btn-edit" title="Edit Ward"
                                                   onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <button onclick="event.stopPropagation(); confirmDelete(<?php echo $row['ward_id']; ?>, '<?php echo htmlspecialchars($row['ward_name']); ?>')" 
                                                        class="action-btn action-btn-delete" title="Delete Ward">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-building"></i>
                                <h3>No wards found</h3>
                                <p class="text-gray-400 mt-1">Start by adding a new ward.</p>
                                <a href="add_ward.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-all">
                                    Add Ward
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="pagination">
                            <div>Showing <span id="visibleCount"><?php echo $wardCount; ?></span> wards</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3>Delete Ward</h3>
            <p id="deleteMessage">Are you sure you want to delete this ward? This action cannot be undone.</p>
            <div class="btn-group">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let deleteId = null;
        let currentFilter = 'all';
        
        // Filter by status
        function filterByStatus(status) {
            currentFilter = status;
            const rows = document.querySelectorAll('.ward-row');
            let visibleCount = 0;
            
           // Remove active class from all cards
            document.getElementById('stat-all').classList.remove('active');
            document.getElementById('stat-active').classList.remove('active');
            document.getElementById('stat-inactive').classList.remove('active');

            // Add active class to selected card
            if (status === 'all') {
                document.getElementById('stat-all').classList.add('active');
                document.getElementById('filterBadge').style.display = 'none';
            }
            else if (status === 'Available') {
                document.getElementById('stat-active').classList.add('active');
                document.getElementById('filterText').textContent = 'Available Wards';
                document.getElementById('filterBadge').style.display = 'block';
            }
            else if (status === 'Occupied') {
                document.getElementById('stat-inactive').classList.add('active');
                document.getElementById('filterText').textContent = 'Occupied Wards';
                document.getElementById('filterBadge').style.display = 'block';
            }
            
            // Filter rows
            rows.forEach(row => {
                const rowStatus = row.dataset.status;
                const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                const searchData = row.dataset.search || '';
                
                if (status === 'all') {
                    if (searchData.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    if (rowStatus === status && searchData.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            document.getElementById('visibleCount').textContent = visibleCount;
        }
        
        // Clear filter
        function clearFilter() {
            document.getElementById('searchInput').value = '';
            filterByStatus('all');
        }
        
        function confirmDelete(id, wardName) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent =
                `Are you sure you want to delete ward "${wardName}"? This action cannot be undone.`;

            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) { 
                window.location.href = `ward_master.php?delete_id=${deleteId}`; 
            }
        });
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) { 
                closeDeleteModal(); 
            }
        });
        
        // Search function
        function searchWards() {
            filterByStatus(currentFilter);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
