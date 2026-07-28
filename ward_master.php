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

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Fetch wards with filter
$wardQuery = "SELECT * FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id='$hid'";
if ($filter !== 'all') {
    $wardQuery .= " AND status = '$filter'";
}
$wardQuery .= " ORDER BY ward_name ASC";
$wardResult = $conn->query($wardQuery);
$wardCount = $wardResult->num_rows;

// Get counts for stats
$totalQuery = "SELECT COUNT(*) AS total FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL) AND hospital_id='$hid'";
$totalResult = $conn->query($totalQuery);
$totalCount = $totalResult->fetch_assoc()['total'] ?? 0;

$activeQuery = "SELECT COUNT(*) AS total FROM ward_master WHERE status = 'Available' AND (delete_flag=0 OR delete_flag IS NULL) AND hospital_id='$hid'";
$activeResult = $conn->query($activeQuery);
$activeCount = $activeResult->fetch_assoc()['total'] ?? 0;

$inactiveQuery = "SELECT COUNT(*) AS total FROM ward_master WHERE status = 'Occupied' AND (delete_flag=0 OR delete_flag IS NULL) AND hospital_id='$hid'";
$inactiveResult = $conn->query($inactiveQuery);
$inactiveCount = $inactiveResult->fetch_assoc()['total'] ?? 0;

$page_title = ($hospital['hospital_name'] ?? 'Hospital') . " - Ward Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?? ''; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
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
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        /* ===== TAB STYLES (Same as Appointments) ===== */
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #64748b;
            text-decoration: none;
            user-select: none;
            white-space: nowrap;
        }
        .tab-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .tab-btn .badge-count {
            background: #e2e8f0;
            color: #64748b;
            padding: 1px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .tab-active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .tab-active:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-color: #2563eb;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
        }
        .tab-active .badge-count {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        .tab-inactive {
            background: #fff;
            color: #64748b;
            border-color: #e5e7eb;
        }
        .tab-inactive .badge-count {
            background: #e2e8f0;
            color: #64748b;
        }
        .tabs-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding: 16px 9px;
            margin-bottom: -31px;
        }
        .tabs-wrapper::-webkit-scrollbar {
            height: 4px;
        }
        .tabs-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .tabs-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .tabs-wrapper .flex-wrap {
            flex-wrap: nowrap;
        }
        @media (min-width: 640px) {
            .tabs-wrapper .flex-wrap {
                flex-wrap: wrap;
            }
        }
        /* ===== END TAB STYLES ===== */

        /* Card Styles */
        .card { 
            background: white; 
            border-radius: 12px; 
            border: 1px solid #e5e7eb; 
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .card-header { 
            padding: 16px 24px; 
            border-bottom: 1px solid #e5e7eb; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .card-header h3 { 
            font-size: 16px; 
            font-weight: 600; 
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-body { 
            padding: 20px 24px; 
        }

        /* Table Styles */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #f9fafb; }
        th { padding: 10px 16px; text-align: left; font-weight: 600; color: #4b5563; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }

        /* Status Badges */
        .status-badge { 
            padding: 4px 14px; 
            border-radius: 9999px; 
            font-size: 12px; 
            font-weight: 600; 
            display: inline-block;
        }
        .status-available {
            background: #dcfce7;
            color: #166534;
        }
        .status-occupied {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Action Buttons */
        .action-btn { 
            transition: all 0.2s ease; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            padding: 6px 10px; 
            border-radius: 6px;
            border: none;
            font-size: 14px;
            text-decoration: none;
        }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-edit { background: #fff7ed; color: #ea580c; }
        .action-btn-edit:hover { background: #fed7aa; }
        .action-btn-delete { background: #fee2e2; color: #dc2626; }
        .action-btn-delete:hover { background: #fecaca; }

        .search-input {
            position: relative;
            display: inline-block;
        }
        .search-input input {
            padding: 8px 12px 8px 36px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            width: 240px;
            transition: all 0.2s ease;
            outline: none;
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
            color: #9ca3af;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; }

        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }

        .fade-in { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
            .search-input input {
                width: 100%;
            }
            .tabs-wrapper .flex-wrap {
                flex-wrap: nowrap;
            }
            .tab-btn {
                padding: 6px 14px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            
            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    <!-- Page Header -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="back-btn">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">Ward Management</h1>
                                    <p class="text-gray-500 text-xs md:text-sm">Manage hospital wards and their status.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="add_ward.php" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    Add Ward
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ===== TABS (Appointments style) ===== -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="tabs-wrapper">
                            <div class="flex flex-wrap gap-2 mb-6" style="flex-wrap: nowrap;">
                                <a href="ward_master.php?filter=all" 
                                   class="tab-btn <?php echo ($filter == 'all') ? 'tab-active' : 'tab-inactive'; ?>" 
                                   id="tab-all">
                                    All <span class="badge-count"><?php echo $totalCount; ?></span>
                                </a>
                                <a href="ward_master.php?filter=Available" 
                                   class="tab-btn <?php echo ($filter == 'Available') ? 'tab-active' : 'tab-inactive'; ?>" 
                                   id="tab-available">
                                    Available <span class="badge-count"><?php echo $activeCount; ?></span>
                                </a>
                                <a href="ward_master.php?filter=Occupied" 
                                   class="tab-btn <?php echo ($filter == 'Occupied') ? 'tab-active' : 'tab-inactive'; ?>" 
                                   id="tab-occupied">
                                    Occupied <span class="badge-count"><?php echo $inactiveCount; ?></span>
                                </a>
                            </div>
                        </div>
                    <!-- ===== END TABS ===== -->

                    <!-- Wards Table -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div>
                                <h3>
                                    <i class="fas fa-list text-blue-500 mr-2"></i>
                                    <?php 
                                        if ($filter == 'all') echo 'All Wards';
                                        elseif ($filter == 'Available') echo 'Available Wards';
                                        else echo 'Occupied Wards';
                                    ?>
                                </h3>
                                <span class="badge-count"><?php echo $wardCount; ?> wards</span>
                            </div>
                            <div class="search-input">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search wards..." onkeyup="searchWards()">
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($wardCount > 0): ?>
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ward Name</th>
                                            <th>Ward Type</th>
                                            <th>Floor</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="wardTableBody">
                                        <?php $i = 1; while ($row = $wardResult->fetch_assoc()): ?>
                                        <tr class="ward-row fade-in" 
                                            data-search="<?php echo strtolower(trim($row['ward_name'])); ?>"
                                            data-status="<?php echo trim($row['status'] ?? 'Available'); ?>"
                                            onclick="window.location.href='view_ward.php?id=<?php echo $row['ward_id']; ?>'">
                                            <td><?php echo $i++; ?></td>
                                            <td>
                                                <span class="font-medium"><?php echo htmlspecialchars($row['ward_name']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['ward_type'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['floor_no'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'Available'); ?>">
                                                    <?php echo htmlspecialchars($row['status'] ?? 'Available'); ?>
                                                </span>
                                            </td>
                                            <td class="text-center" onclick="event.stopPropagation();">
                                                <div class="flex items-center justify-center gap-1">
                                                    <a href="edit_ward.php?id=<?php echo $row['ward_id']; ?>" 
                                                       class="action-btn action-btn-edit" title="Edit Ward">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(<?php echo $row['ward_id']; ?>, '<?php echo htmlspecialchars($row['ward_name']); ?>')" 
                                                            class="action-btn action-btn-delete" title="Delete Ward">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-building"></i>
                                <p class="text-lg font-medium text-gray-700">No wards found</p>
                                <p class="text-sm text-gray-400 mt-1">Click "Add Ward" to create a new ward.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Showing <span id="visibleCount"><?php echo $wardCount; ?></span> wards</div>
                            <div class="text-xs text-gray-400"><i class="fas fa-sync-alt mr-1"></i> Live updates</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-box" style="background: white; border-radius: 12px; max-width: 420px; width: 90%; padding: 32px; text-align: center; animation: fadeIn 0.2s ease;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: #dc2626;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Delete Ward</h3>
            <p id="deleteMessage" style="color: #64748b; font-size: 14px; margin-bottom: 24px;">Are you sure you want to delete this ward? This action cannot be undone.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="closeDeleteModal()" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s ease;">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s ease;">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let deleteId = null;
        let currentFilter = '<?php echo $filter; ?>';

        function confirmDelete(id, wardName) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete ward "${wardName}"? This action cannot be undone.`;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
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
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.ward-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const searchData = row.dataset.search || '';
                if (searchData.includes(input)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        // Keyboard shortcut for search (Ctrl+F)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });
    </script>
</body>
</html>