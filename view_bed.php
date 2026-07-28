<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ward_id = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;

// Fetch room details
$roomQuery = "SELECT r.*, w.ward_name, w.ward_id 
              FROM room_master r
              LEFT JOIN ward_master w ON r.ward_id = w.ward_id
              WHERE r.room_id = $room_id AND (r.delete_flag = 0 OR r.delete_flag IS NULL)";
$roomResult = $conn->query($roomQuery);
if ($roomResult->num_rows == 0) {
    header("Location: room_master.php");
    exit();
}
$room = $roomResult->fetch_assoc();

if ($ward_id == 0) {
    $ward_id = $room['ward_id'];
}

// Fetch bed statistics
$bedStatsQuery = "SELECT 
                    COUNT(*) as total_beds,
                    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_beds,
                    SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied_beds,
                    SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_beds
                  FROM bed_master 
                  WHERE room_id = $room_id AND (delete_flag = 0 OR delete_flag IS NULL)";
$bedStatsResult = $conn->query($bedStatsQuery);
$bedStats = $bedStatsResult->fetch_assoc();

if (!$bedStats) {
    $bedStats = [
        'total_beds' => 0,
        'available_beds' => 0,
        'occupied_beds' => 0,
        'maintenance_beds' => 0
    ];
}

// Fetch all beds
$bedsQuery = "SELECT * FROM bed_master 
              WHERE room_id = $room_id AND (delete_flag = 0 OR delete_flag IS NULL)
              ORDER BY bed_no ASC";
$bedsResult = $conn->query($bedsQuery);
$bedCount = $bedsResult->num_rows;

$allBeds = [];
while ($bed = $bedsResult->fetch_assoc()) {
    $allBeds[] = $bed;
}

// Handle delete request
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE bed_master SET delete_flag = 1 WHERE bed_id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Bed deleted successfully!');
            window.location.href='view_bed.php?id=$room_id&ward_id=$ward_id';
        </script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Beds - Room <?php echo htmlspecialchars($room['room_no']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* ===== EXISTING STYLES (unchanged) ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; width: 100%; }
        .stat-card { background: white; border-radius: 12px; padding: 20px 24px; border: 1px solid #e5e7eb; transition: all 0.3s ease; position: relative; overflow: hidden; cursor: pointer; text-decoration: none; display: block; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #3b82f6, #8b5cf6, #10b981); opacity: 0; transition: opacity 0.3s ease; }
        .stat-card:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.08); transform: translateY(-4px); }
        .stat-card:hover::before { opacity: 1; }
        .stat-card.active { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
        .stat-card.active::before { opacity: 1; }
        .stat-card .stat-number { font-size: 32px; font-weight: 700; color: #0f172a; margin: 8px 0; }
        .stat-card .stat-label { font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%); flex-wrap: wrap; gap: 12px; }
        .card-header h3 { font-size: 16px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 20px 24px; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .status-active { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-inactive { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-available { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .status-occupied { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-maintenance { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 8px; border-radius: 6px; border: 1px solid transparent; background: transparent; }
        .action-btn:hover { transform: scale(1.1); }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; border-color: #8b5cf6; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; border-color: #ef4444; }
        .action-btn-back { color: #3b82f6; }
        .action-btn-back:hover { background: #dbeafe; border-color: #3b82f6; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row .label { font-weight: 600; color: #475569; width: 140px; flex-shrink: 0; }
        .info-row .value { color: #0f172a; }
        .clickable-bed { cursor: pointer; }
        .clickable-bed:hover { background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); border-bottom: 2px solid #e5e7eb; }
        thead th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr { border-bottom: 1px solid #e5e7eb; transition: all 0.2s ease; }
        tbody tr:hover { background: #f8fafc; box-shadow: inset 0 0 0 1px #e5e7eb; }
        tbody td { padding: 14px 16px; font-size: 14px; color: #0f172a; }
        .empty-state { text-align: center; padding: 48px 20px; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }
        .empty-state h3 { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px; }
        .empty-state p { color: #94a3b8; font-size: 14px; margin-bottom: 16px; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9998; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-overlay.active { display: flex; animation: fadeIn 0.2s ease; }
        .modal-box { background: white; border-radius: 16px; max-width: 420px; width: 90%; padding: 32px; text-align: center; animation: slideUp 0.3s ease; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-box .modal-icon { width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 32px; color: #dc2626; }
        .modal-box h3 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .modal-box p { color: #64748b; font-size: 14px; margin-bottom: 24px; line-height: 1.6; }
        .modal-box .btn-group { display: flex; gap: 12px; justify-content: center; }
        .modal-box .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 24px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s ease; }
        .modal-box .btn-cancel:hover { background: #e2e8f0; border-color: #94a3b8; }
        .modal-box .btn-delete { background: #dc2626; color: white; padding: 10px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s ease; }
        .modal-box .btn-delete:hover { background: #b91c1c; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
        .bed-row-hidden { display: none; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 768px) { .main-content { padding: 12px; } .stat-card .stat-number { font-size: 24px; } .card-body { padding: 16px; } .card-header { flex-direction: column; align-items: flex-start; gap: 12px; } .info-row { flex-direction: column; padding: 12px 0; } .info-row .label { width: 100%; margin-bottom: 4px; } }
        .page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
        .back-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; border: 1px solid #e5e7eb; background: white; color: #64748b; transition: all 0.2s ease; text-decoration: none; cursor: pointer; }
        .back-btn:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }
        .debug-message { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #92400e; font-size: 14px; }

        /* ===== NEW TAB STYLES (added) ===== */
        .tab-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
            width: 100%;
        }
        .tab-btn {
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            background: white;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab-btn:hover {
            background: #f1f5f9;
        }
        .tab-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .tab-btn.active:hover {
            background: #2563eb;
        }
        @media (max-width: 640px) {
            .tab-btn {
                padding: 5px 12px;
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
                    <div class="page-header flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-7">
                        <div class="flex items-center gap-4">
                            <a href="view_ward.php?id=<?php echo $ward_id; ?>" class="back-btn" title="Back to Ward">
                                <i class="fa-solid fa-arrow-left"></i> 
                            </a>
                            <div>
                                <h1 class="page-title text-2xl font-bold text-gray-900">
                                    Room <?php echo htmlspecialchars($room['room_no']); ?>
                                </h1>
                                <p class="page-subtitle text-gray-500">
                                    <?php echo htmlspecialchars($room['ward_name']); ?> - Bed Management
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-3">
                            <a href="add_bed.php?room_id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                <i class="fa-solid fa-plus mr-2"></i> Add Bed
                            </a>
                        </div>
                    </div>

                    <!-- Debug Info -->
                    <?php if ($bedCount == 0): ?>
                    <div class="debug-message">
                        <strong><i class="fa-solid fa-info-circle"></i> Debug Info:</strong> 
                        No beds found for Room ID: <?php echo $room_id; ?>. 
                        Please check if beds exist in the bed_master table for this room.
                    </div>
                    <?php endif; ?>

                    <!-- Room Information -->
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3>
                                <i class="fa-solid fa-info-circle text-blue-600"></i>
                                Room Information
                            </h3>
                            <span class="status-badge status-<?php echo strtolower($room['status'] ?? 'available'); ?>">
                                <?php echo $room['status'] ?? 'Available'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="grid md:grid-cols-3 gap-4">
                                <div class="info-row">
                                    <span class="label">Room Number</span>
                                    <span class="value"><?php echo htmlspecialchars($room['room_no']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label">Ward</span>
                                    <span class="value"><?php echo htmlspecialchars($room['ward_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="label">Capacity</span>
                                    <span class="value"><?php echo $room['capacity'] ?? 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                   

                    <!-- Beds List -->
                    <div class="card">
                        <div class="card-header">
                            <div class="flex flex-col w-full gap-2">
                                <div class="flex items-center justify-between w-full flex-wrap gap-2">
                                    <h3>
                                        <i class="fa-solid fa-list text-blue-600"></i>
                                        Beds in Room <?php echo htmlspecialchars($room['room_no']); ?>
                                    </h3>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-gray-500" id="bedCount">
                                            Total: <?php echo count($allBeds); ?> beds
                                        </span>
                                        <span class="text-sm text-gray-500" id="filterLabel"></span>
                                    </div>
                                </div>
                                <!-- ===== NEW TAB BAR (added) ===== -->
                                <div class="tab-bar">
                                    <button class="tab-btn active" data-filter="all" onclick="filterBeds('all', this)">All</button>
                                    <button class="tab-btn" data-filter="Available" onclick="filterBeds('Available', this)">Available</button>
                                    <button class="tab-btn" data-filter="Occupied" onclick="filterBeds('Occupied', this)">Occupied</button>
                                    <button class="tab-btn" data-filter="Maintenance" onclick="filterBeds('Maintenance', this)">Maintenance</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body overflow-x-auto">
                            <?php if (count($allBeds) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Bed No</th>
                                        <th>Bed Type</th>
                                        <th>Status</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="bedTableBody">
                                    <?php $i = 1; foreach ($allBeds as $bed): ?>
                                    <tr class="clickable-bed bed-row" data-status="<?php echo $bed['status'] ?? 'Available'; ?>" onclick="window.location.href='bed_allotment.php?bed_id=<?php echo $bed['bed_id']; ?>&room_id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>'">
                                        <td class="text-gray-500"><?php echo $i++; ?></td>
                                        <td style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($bed['bed_no']); ?></td>
                                        <td><?php echo htmlspecialchars($bed['bed_type'] ?? 'Standard'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($bed['status'] ?? 'available'); ?>">
                                                <?php echo $bed['status'] ?? 'Available'; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="edit_bed.php?bed_id=<?php echo $bed['bed_id']; ?>" 
                                                   onclick="event.stopPropagation();"
                                                   class="action-btn action-btn-edit" title="Edit Bed">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <button onclick="event.stopPropagation(); confirmDelete(<?php echo $bed['bed_id']; ?>, '<?php echo htmlspecialchars($bed['bed_no']); ?>')" 
                                                        class="action-btn action-btn-delete" title="Delete Bed">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-bed"></i>
                                <h3>No beds found</h3>
                                <p>This room doesn't have any beds yet.</p>
                                <a href="add_bed.php?room_id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all">
                                    <i class="fa-solid fa-plus mr-2"></i>
                                    Add First Bed
                                </a>
                            </div>
                            <?php endif; ?>
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
            <h3>Delete Bed</h3>
            <p id="deleteMessage">Are you sure you want to delete this bed? This action cannot be undone.</p>
            <div class="btn-group">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let deleteId = null;
        let currentFilter = 'all';
        
        function confirmDelete(id, bedNo) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete bed "${bedNo}"? This action cannot be undone.`;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) { 
                window.location.href = `view_bed.php?id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>&delete_id=${deleteId}`; 
            }
        });
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) { 
                closeDeleteModal(); 
            }
        });

        // ===== UPDATED filterBeds FUNCTION (supports both stat cards & tabs) =====
        function filterBeds(filter, element) {
            currentFilter = filter;
            
            // Remove active from all stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });
            // Remove active from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            // Add active to the clicked element (if it's a stat-card or tab-btn)
            if (element) {
                element.classList.add('active');
            }
            
            // Get all bed rows
            const rows = document.querySelectorAll('.bed-row');
            let visibleCount = 0;
            
            // Show/hide rows based on filter
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update bed count
            const countDisplay = document.getElementById('bedCount');
            const filterLabel = document.getElementById('filterLabel');
            
            if (filter === 'all') {
                countDisplay.textContent = `Total: ${rows.length} beds`;
                filterLabel.textContent = '';
            } else {
                countDisplay.textContent = `Showing: ${visibleCount} beds`;
                filterLabel.textContent = `(Filtered by: ${filter})`;
                filterLabel.style.color = '#3b82f6';
                filterLabel.style.fontWeight = '500';
            }
        }

        // Set initial active tab (All) on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure the 'All' tab is active
            const allTab = document.querySelector('.tab-btn[data-filter="all"]');
            if (allTab) {
                allTab.classList.add('active');
            }
            // Ensure the 'All' stat card is active
            const allStat = document.querySelector('.stat-card[data-filter="all"]');
            if (allStat) {
                allStat.classList.add('active');
            }
        });

        // Debug logs
        console.log('Room ID:', <?php echo $room_id; ?>);
        console.log('Total Beds:', <?php echo count($allBeds); ?>);
        console.log('Beds Data:', <?php echo json_encode($allBeds); ?>);
    </script>
</body>
</html>