<?php
session_start();
include("../config/hospital.php");


$doctor_id = $_SESSION['id'] ?? '';


$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;


$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';


$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';


$where_clause = "WHERE doctor_id='$doctor_id'";
if (!empty($search)) {
    $where_clause .= " AND (patient_name LIKE '%$search%' OR patient_email LIKE '%$search%' OR patient_phone LIKE '%$search%')";
}
if (!empty($date_filter)) {
    $where_clause .= " AND appointment_date = '$date_filter'";
}


$count_sql = "SELECT COUNT(*) as total FROM appointments $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);


$sql = "SELECT * FROM appointments 
        $where_clause 
        ORDER BY appointment_date DESC, appointment_time ASC 
        LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Patients - <?php echo $hospital['hospital_name'] ?></title>

    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-scheduled { background: #DBEAFE; color: #1E40AF; }
        .status-completed { background: #D1FAE5; color: #065F46; }
        .status-cancelled { background: #FEE2E2; color: #991B1B; }
        .status-no-show { background: #FEF3C7; color: #92400E; }
        .status-in-progress { background: #E0E7FF; color: #3730A3; }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        @media print {
            .no-print { display: none !important; }
            .sidebar-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <header>
            <?php include '../header.php'; ?> 
        </header>

        <div class="flex flex-1 items-start">
            <!-- Sidebar Navigation -->
            <?php include 'Sidebar.php' ?>

            <!-- Main Content -->
            <main class="flex-1 xl:ml-64 p-6">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-users text-blue-500 mr-2"></i>All Patients
                        </h1>
                        <p class="text-gray-600 mt-1">Total Patients: <?php echo $total_records; ?></p>
                    </div>
                    <div class="flex gap-3 mt-4 md:mt-0 no-print">
                        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                        <a href="../doctor_dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </a>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search Patient</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name, email or phone..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        <div class="w-full md:w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Filter</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition w-full md:w-auto">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                            <?php if(!empty($search) || !empty($date_filter)): ?>
                            <a href="all_patients.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Table Section -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-500 font-medium">
                                <tr>
                                    <th class="px-6 py-3">#</th>
                                    <th class="px-6 py-3">Patient</th>
                                    <th class="px-6 py-3">Contact</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Time</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-center no-print">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if(mysqli_num_rows($result) > 0): ?>
                                    <?php $counter = $offset + 1; ?>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 font-medium text-gray-500"><?php echo $counter++; ?></td>
                                            <td class="px-6 py-4">
                                                <div>
                                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $row['id'] ?? 'N/A'; ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm">
                                                    <div><i class="fas fa-envelope text-gray-400 mr-1"></i> <?php echo htmlspecialchars($row['patient_email'] ?? 'N/A'); ?></div>
                                                    <div><i class="fas fa-phone text-gray-400 mr-1"></i> <?php echo htmlspecialchars($row['patient_phone'] ?? 'N/A'); ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($row['appointment_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo date("M d, Y", strtotime($row['appointment_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo date("h:i A", strtotime($row['appointment_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php 
                                                $status = strtolower($row['status']);
                                                $status_class = '';
                                                switch($status) {
                                                    case 'scheduled':
                                                        $status_class = 'status-scheduled';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'status-completed';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'status-cancelled';
                                                        break;
                                                    case 'no-show':
                                                        $status_class = 'status-no-show';
                                                        break;
                                                    case 'in-progress':
                                                        $status_class = 'status-in-progress';
                                                        break;
                                                    default:
                                                        $status_class = 'status-scheduled';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center no-print">
                                                <div class="flex justify-center gap-2">
                                                    <a href="view_patient.php?id=<?php echo $row['id']; ?>" 
                                                       class="text-blue-500 hover:text-blue-700" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                   
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-12">
                                            <i class="fas fa-user-slash text-4xl text-gray-300 mb-3 block"></i>
                                            <p class="text-gray-500 text-lg">No patients found</p>
                                            <p class="text-gray-400 text-sm mt-1">Try adjusting your search or filter criteria</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row justify-between items-center gap-4 no-print">
                        <div class="text-sm text-gray-600">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                        </div>
                        <div class="flex gap-2">
                            <?php if($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" 
                                   class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if($i == $page): ?>
                                    <span class="px-4 py-2 bg-blue-500 text-white rounded-lg"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" 
                                       class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" 
                                   class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 no-print">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Confirm Delete</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this patient appointment? This action cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg transition">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        let deleteId = null;

        function deletePatient(id) {
            deleteId = id;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if(deleteId) {
                window.location.href = 'delete_patient.php?id=' + deleteId;
            }
        });

        // Close modal on outside click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>