<?php 
session_start(); 
include "../config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/logout.php");
    exit();
}

$doctor_register_id = $_SESSION['id'];

$doctorSql = "SELECT doctor_id FROM doctor WHERE register_id = '$doctor_register_id'";
$doctorResult = $conn->query($doctorSql);
if ($doctorResult && $doctorResult->num_rows > 0) {
    $doctor = $doctorResult->fetch_assoc();
    $doctor_id = $doctor['doctor_id'];
} else {
    header("Location: ../auth/logout.php");
    exit();
}

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE prescriptions SET delete_flag = 1 WHERE id = '$delete_id' AND doctor_id = '$doctor_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Prescription deleted successfully!');
            window.location.href='prescription_list.php';
        </script>";
        exit();
    }
}

$prescriptionQuery = "SELECT p.*, pat.patient_name 
                      FROM prescriptions p 
                      LEFT JOIN patients pat ON p.patient_id = pat.patient_id 
                      WHERE p.doctor_id = '$doctor_id' 
                      AND (p.delete_flag = 0 OR p.delete_flag IS NULL) 
                      ORDER BY p.created_at DESC";
$prescriptionResult = $conn->query($prescriptionQuery);
$prescriptionCount = $prescriptionResult->num_rows;

$totalQuery = "SELECT COUNT(*) AS total FROM prescriptions WHERE doctor_id = '$doctor_id' AND (delete_flag=0 OR delete_flag IS NULL)";
$totalResult = $conn->query($totalQuery);
$totalCount = $totalResult->fetch_assoc();

$follow_up = "SELECT COUNT(*) AS total_follow FROM prescriptions WHERE doctor_id = '$doctor_id' AND followup_date = CURDATE() + INTERVAL 1 DAY AND (delete_flag = 0 OR delete_flag IS NULL)";
$follow = $conn->query($follow_up);
$followCount = $follow->fetch_assoc();

$todayQuery = "SELECT COUNT(*) AS total FROM prescriptions WHERE doctor_id = '$doctor_id' AND (delete_flag=0 OR delete_flag IS NULL) AND DATE(created_at) = CURDATE()";
$todayResult = $conn->query($todayQuery);
$todayCount = $todayResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - My Prescriptions</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .stat-card { background: white; border-radius: 12px; padding: 20px 24px; border: 1px solid #e5e7eb; transition: all 0.2s ease; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: 14px; color: #64748b; font-weight: 500; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-view { color: #3b82f6; }
        .action-btn-view:hover { background: #dbeafe; }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        
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
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .back-btn i {
            font-size: 18px;
            line-height: 1;
        }
        
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 640px) { .card-header { flex-direction: column; align-items: stretch; } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content w-full">
               <div class="w-full">
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="back-btn">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">Prescriptions</h1>
                                    <p class="text-gray-500">Manage patient prescriptions and medications.</p>
                                </div>
                            </div>
                           
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Total Prescriptions</div>
                                    <div class="stat-number"><?php echo $totalCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="file-text" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Today's Prescriptions</div>
                                    <div class="stat-number"><?php echo $todayCount['total']; ?></div>
                                </div>
                                <div class="stat-icon bg-green-50 text-green-600"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-label">Tomorrow's Follow-ups</div>
                                    <div class="stat-number"><?php echo $followCount['total_follow']; ?></div>
                                </div>
                                <div class="stat-icon bg-purple-50 text-purple-600"><i data-lucide="calendar-check" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="card w-full">
                        <div class="card-header">
                            <h3><i data-lucide="list" class="w-5 h-5 inline mr-2 text-blue-500"></i> All Prescriptions</h3>
                            <input type="text" id="searchInput" placeholder="Search prescriptions..." class="w-64 pl-4 pr-4 py-2 text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500" onkeyup="searchPrescriptions()">
                        </div>
                        <div class="card-body overflow-x-auto p-4">
                            <?php if ($prescriptionCount > 0): ?>
                           <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Patient</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Medicine</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Dosage</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Follow-up</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="prescriptionTableBody">
                                    <?php $i = 1; while ($row = $prescriptionResult->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all">
                                        <td class="px-4 py-3"><?php echo $i++; ?></td>
                                        <td class="px-4 py-3 font-medium"><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                        <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($row['dosage']); ?></td>
                                        <td class="px-4 py-3"><?php echo $row['followup_date'] ? date('d-m-Y', strtotime($row['followup_date'])) : '—'; ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="view_prescription.php?id=<?php echo $row['id']; ?>" class="action-btn action-btn-view" title="View"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                                <a href="edit_prescription.php?id=<?php echo $row['id']; ?>" class="action-btn action-btn-edit" title="Edit"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="action-btn action-btn-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                           </table>
                            <?php else: ?>
                            <div class="py-12 text-center text-gray-500">
                                <i data-lucide="file-text" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">No prescriptions found</p>
                                <p class="text-sm text-gray-400">Create your first prescription now.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this prescription?")) {
                window.location.href = "prescription_list.php?delete_id=" + id;
            }
        }

        function searchPrescriptions() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#prescriptionTableBody tr');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
</body>
</html>