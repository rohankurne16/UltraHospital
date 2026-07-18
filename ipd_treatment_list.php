<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

// Handle Delete (Soft Delete)
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $conn->query("UPDATE ipd_treatment_master SET delete_flag = 1, modified_at = NOW() WHERE treatment_master_id = ".$delete_id);
    $_SESSION["toast"] = ["type" => "success", "message" => "Treatment deleted successfully!"];
    header("Location: ipd_treatment_list.php");
    exit();
}

// Fetch treatments - ONE per IPD admission
$sql = "SELECT tm.*, 
        p.patient_name, p.patient_id as patient_code, p.age, p.gender,
        d.doctor_name,
        a.admission_no, a.ward_id, a.room_no, a.bed_no, a.admission_date,
        w.ward_name,
        (SELECT COUNT(*) FROM ipd_treatment_daily WHERE treatment_master_id = tm.treatment_master_id AND delete_flag = 0) as total_days,
        (SELECT treatment_daily_id FROM ipd_treatment_daily WHERE treatment_master_id = tm.treatment_master_id AND delete_flag = 0 ORDER BY treatment_date DESC, treatment_daily_id DESC LIMIT 1) as latest_daily_id
        FROM ipd_treatment_master tm
        LEFT JOIN patients p ON tm.patient_id = p.patient_id
        LEFT JOIN doctor d ON tm.doctor_id = d.doctor_id
        LEFT JOIN ipd_admissions a ON tm.ipd_id = a.id
        LEFT JOIN ward_master w ON a.ward_id = w.ward_id
        WHERE tm.delete_flag = 0
        ORDER BY tm.treatment_master_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> -IPD Treatmentlist</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { width: 260px; height: 100vh; background: #f8f3f3; position: fixed; left: 0; top: 0; color: #0b0707; z-index: 50; overflow-y: auto; }
        .header { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; position: fixed; left: 260px; right: 0; top: 0; z-index: 40; display: flex; align-items: center; padding: 0 1.5rem; }
        .main-content { margin-left: 260px; padding: 84px 28px 20px 28px; min-height: 100vh; }
        .table-container { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .table-container .header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; position: static; }
        .table-container .body { padding: 20px; overflow-x: auto; }
        .btn-primary { background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-success { background: #22c55e; color: white; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-warning:hover { background: #d97706; }
        .btn-secondary { background: #e2e8f0; color: #475569; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { background: #d1d5db; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-discharged { background: #fee2e2; color: #991b1b; }
        .badge-transferred { background: #fef3c7; color: #92400e; }
        .clickable-row { cursor: pointer; transition: background 0.2s; }
        .clickable-row:hover { background: #f1f5f9; }
        .search-box { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .search-box input, .search-box select { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; }
        .search-box input:focus, .search-box select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } .header { left: 0; } }
        @media (max-width: 768px) { .search-box { flex-direction: column; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'Sidebar.php'; ?>
   

    <!-- Header -->
    
        <div class="flex flex-1 items-start">
            <?php include 'header.php'; ?>
       
    

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header with Back Button -->
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="p-2 border rounded-md hover:bg-gray-100 transition inline-flex items-center justify-center">
                                    <i class="fas fa-arrow-left text-gray-600 w-4 h-4"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">IPD Treatment</h1>
                                    <p class="text-gray-500">Manage IPD patient treatments</p>
                                </div>
                            </div>
                            <a href="ipd_treatment_add.php" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Start New Treatment
                            </a>
                        </div>

                <div class="table-container">
                                    <div class="header">
                                        <div class="search-box">
                                            <input type="text" id="searchPatient" placeholder="Search by Patient Name..." class="flex-1">
                                            <input type="text" id="searchIPD" placeholder="Search by IPD Number..." class="flex-1">
                                            <input type="date" id="searchDate" class="flex-1">
                                            <button onclick="applyFilters()" class="btn-primary">Search</button>
                                            <button onclick="resetFilters()" class="btn-secondary">Reset</button>
                                        </div>
                                    </div>
                            <div class="body">
                                <table class="w-full" id="treatmentTable">
                                                    <thead>
                                                        <tr class="border-b border-gray-200">
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">#</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">IPD No</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Patient</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Doctor</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Ward/Room/Bed</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Days</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Actions</th>
                                                        </tr>
                                                    </thead>
                                                <tbody>
                                                    <?php if ($result && $result->num_rows > 0): ?>
                                                        <?php while ($row = $result->fetch_assoc()): ?>
                                                        <tr class="border-b border-gray-100 clickable-row" onclick="viewTreatment(<?php echo $row['treatment_master_id']; ?>)">
                                                            <td class="py-3 px-4"><?php echo $row['treatment_master_id']; ?></td>
                                                            <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($row['admission_no'] ?? 'N/A'); ?></td>
                                                            <td class="py-3 px-4">
                                                                <div class="font-medium"><?php echo htmlspecialchars($row['patient_name'] ?? 'N/A'); ?></div>
                                                                <div class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($row['patient_code'] ?? 'N/A'); ?></div>
                                                            </td>
                                                            <td class="py-3 px-4"><?php echo htmlspecialchars($row['doctor_name'] ?? 'N/A'); ?></td>
                                                            <td class="py-3 px-4">
                                                                <?php if ($row['ward_name']): ?>
                                                                    <span class="text-sm"><?php echo htmlspecialchars($row['ward_name']); ?></span>
                                                                    <span class="text-sm text-gray-500">| Rm <?php echo htmlspecialchars($row['room_no'] ?? 'N/A'); ?></span>
                                                                    <span class="text-sm text-gray-500">| Bed <?php echo htmlspecialchars($row['bed_no'] ?? 'N/A'); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-gray-400">N/A</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="py-3 px-4 text-center font-bold"><?php echo $row['total_days']; ?></td>
                                                            <td class="py-3 px-4">
                                                                <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                                                    <?php echo $row['status']; ?>
                                                                </span>
                                                            </td>
                                                            <td class="py-3 px-4" onclick="event.stopPropagation();">
                                                                <div class="flex gap-2 flex-wrap">
                                                                    <a href="ipd_treatment_daily_add.php?master_id=<?php echo $row['treatment_master_id']; ?>" class="btn-success text-sm py-1 px-3" title="Add Daily Treatment">
                                                                        <i class="fas fa-plus"></i> Day
                                                                    </a>
                                                                    <a href="ipd_treatment_edit.php?daily_id=<?php echo $row["latest_daily_id"]; ?>&master_id=<?php echo $row["treatment_master_id"]; ?>" class="btn-warning text-sm py-1 px-3" title="Edit Treatment">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <button onclick="deleteTreatment(<?php echo $row['treatment_master_id']; ?>)" class="btn-danger text-sm py-1 px-3" title="Delete Treatment">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center py-8 text-gray-500">No treatments found</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                </table>
                            </div>
                </div>
    </main>

    <script>
        lucide.createIcons();

        function viewTreatment(id) {
            window.location.href = `ipd_treatment_view.php?id=${id}`;
        }

        function deleteTreatment(id) {
            event.stopPropagation();
            if (confirm('Are you sure you want to delete this treatment?')) {
                window.location.href = `ipd_treatment_list.php?delete_id=${id}`;
            }
        }

        function applyFilters() {
            const searchPatient = document.getElementById('searchPatient').value.toLowerCase();
            const searchIPD = document.getElementById('searchIPD').value.toLowerCase();
            const searchDate = document.getElementById('searchDate').value;
            const rows = document.querySelectorAll('#treatmentTable tbody tr');

            rows.forEach(row => {
                const patientName = row.children[2].children[0].textContent.toLowerCase();
                const ipdNo = row.children[1].textContent.toLowerCase();
               

                const patientMatch = patientName.includes(searchPatient);
                const ipdMatch = ipdNo.includes(searchIPD);

                if (patientMatch && ipdMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function resetFilters() {
            document.getElementById('searchPatient').value = '';
            document.getElementById('searchIPD').value = '';
            document.getElementById('searchDate').value = '';
            applyFilters(); // Re-apply filters to show all rows
        }

        <?php if (isset($_SESSION['toast'])): ?>
            Toastify({
                text: "<?php echo $_SESSION['toast']['message']; ?>",
                duration: 3000,
                newWindow: true,
                close: true,
                gravity: "top", 
                position: "right", 
                stopOnFocus: true,
                style: {
                    background: "<?php echo $_SESSION['toast']['type'] === 'success' ? '#22c55e' : '#ef4444'; ?>",
                },
                onClick: function(){}
            }).showToast();
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
