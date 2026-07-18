<?php 
session_start(); 
include "../config/db.php";



// Fetch all discharged patients
$dischargeQuery = "SELECT a.*, p.patient_name, p.mobile, d.doctor_name 
                   FROM ipd_admission a
                   LEFT JOIN patients p ON a.patient_id = p.patient_id
                   LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
                   WHERE a.status = 'Discharged' AND (a.delete_flag=0 OR a.delete_flag IS NULL)
                   ORDER BY a.discharge_date DESC";
$dischargeResult = $conn->query($dischargeQuery);
$dischargeCount = $dischargeResult->num_rows;

// Get counts
$totalQuery = "SELECT COUNT(*) AS total FROM ipd_admission WHERE status = 'Discharged' AND (delete_flag=0 OR delete_flag IS NULL)";
$totalResult = $conn->query($totalQuery);
$totalCount = $totalResult->fetch_assoc();

$todayQuery = "SELECT COUNT(*) AS total FROM ipd_admission WHERE DATE(discharge_date) = CURDATE() AND status = 'Discharged' AND (delete_flag=0 OR delete_flag IS NULL)";
$todayResult = $conn->query($todayQuery);
$todayCount = $todayResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Discharge List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
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
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; background: #d1fae5; color: #065f46; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
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
                                    <h1 class="text-2xl font-bold text-gray-900">Discharge List</h1>
                                    <p class="text-gray-500">View all discharged patients.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="discharge_summary.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    Discharge Patient
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Discharged</div>
                                    <div class="stat-number"><?php echo $totalCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-green-50 text-green-600">
                                    <i data-lucide="users" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Today's Discharges</div>
                                    <div class="stat-number"><?php echo $todayCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-blue-50 text-blue-600">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Patients</div>
                                    <div class="stat-number"><?php echo $dischargeCount; ?></div>
                                </div>
                                <div class="stat-icon bg-purple-50 text-purple-600">
                                    <i data-lucide="file-text" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Discharged Patients</h3>
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    <input type="text" id="searchInput" placeholder="Search patients..." 
                                           class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchDischarges()">
                                </div>
                            </div>
                        </div>
                        <div class="card-body overflow-x-auto">
                            <?php if ($dischargeCount > 0): ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discharge Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="dischargeTableBody">
                                    <?php $i = 1; while ($row = $dischargeResult->fetch_assoc()): ?>
                                    <tr class="discharge-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in"
                                        data-search="<?php echo strtolower($row['patient_name'] . ' ' . $row['doctor_name']); ?>">
                                        <td class="px-4 py-3 text-gray-500"><?php echo $i++; ?></td>
                                        <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($row['admission_no']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo date('M d, Y', strtotime($row['discharge_date'])); ?></td>
                                        <td class="px-4 py-3 text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($row['discharge_reason'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="status-badge">Discharged</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="view_discharge.php?id=<?php echo $row['id']; ?>" 
                                               class="action-btn p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-all" title="View">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="text-center py-12">
                                <i data-lucide="log-out" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-600">No Discharged Patients</h3>
                                <p class="text-gray-400 mt-1">No patients have been discharged yet.</p>
                                <a href="discharge_summary.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-all">
                                    Discharge Patient
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Showing <span id="visibleCount"><?php echo $dischargeCount; ?></span> patients</div>
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

    <script>
        lucide.createIcons();
        function searchDischarges() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.discharge-row');
            let visibleCount = 0;
            rows.forEach(row => {
                const searchData = row.dataset.search || '';
                if (searchData.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else { row.style.display = 'none'; }
            });
            document.getElementById('visibleCount').textContent = visibleCount;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>