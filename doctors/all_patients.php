<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../config/hospital.php");


$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;


$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';


$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';


$where_clause = "WHERE 1=1";
if (!empty($search)) {
    $where_clause .= " AND (p.patient_name LIKE '%$search%' OR p.email LIKE '%$search%' OR p.mobile LIKE '%$search%')";
}
if (!empty($date_filter)) {
    $where_clause .= " AND p.date_of_birth = '$date_filter' ";
}
$doctor_id = $_SESSION['doctor_id'];

$count_sql = "SELECT COUNT(*) as total FROM patients where doctor_id = '$doctor_id' and (delete_flag=0 or delete_flag is null)";
$count_result = mysqli_query($conn, $count_sql);

if (!$count_result) {
    die("Error in count query: " . mysqli_error($conn));
}

$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);



$sql = "SELECT *
        FROM patients
        WHERE doctor_id = '$doctor_id'
        AND (delete_flag = 0 OR delete_flag IS NULL)
        ORDER BY patient_id DESC";

$patientResult = mysqli_query($conn, $sql);


if (!$patientResult) {
    die("Error in main query: " . mysqli_error($conn));
}
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
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .blood-group-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .blood-a { background: #FEE2E2; color: #991B1B; }
        .blood-b { background: #DBEAFE; color: #1E40AF; }
        .blood-o { background: #D1FAE5; color: #065F46; }
        .blood-ab { background: #FEF3C7; color: #92400E; }
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php
echo "<h1 style='color:red'>PAGE LOADED</h1>";
?>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <header>
            <?php include 'header.php'; ?> 
        </header>

        <div class="flex flex-1 items-start">
            <!-- Sidebar Navigation -->
           <?php include 'sidebar.php' ?>

            <!-- Main Content -->
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    
                    <!-- Page Header -->
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">All Patients</h1>
                                    <p class="text-gray-500 mt-1">View and manage all registered patients.</p>
                                </div>
                            </div>
                          
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Total Patients</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_records; ?></p>
                                </div>
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Active</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        <?php
                                        
                                        $activeSql = "SELECT COUNT(*) as count FROM patients WHERE doctor_id='$doctor_id' and status='Active'and(delete_flag=0 or delete_flag is null)";
                                        $activeResult = mysqli_query($conn, $activeSql);
                                        $activeCount = $activeResult ? mysqli_fetch_assoc($activeResult)['count'] : 0;
                                        echo $activeCount;
                                        ?>
                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Inactive</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                       <?php
                                                $inactiveSql = "SELECT COUNT(*) AS count FROM patients WHERE status = 'Inactive'  AND doctor_id = '$doctor_id'
                                                                AND (delete_flag = 0 OR delete_flag IS NULL)";
                                                        
                                                $inactiveResult = mysqli_query($conn, $inactiveSql);

                                                if ($inactiveResult) {
                                                    $row = mysqli_fetch_assoc($inactiveResult);
                                                    $inactiveCount = $row['count'];
                                                } else {
                                                    $inactiveCount = 0;
                                                }

                                                echo $inactiveCount;
                                                ?>
                                                                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                                </div>
                            </div>
                        </div>
                       
                    </div>


                    <!-- Table Section -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Patient List</h2>
                                <p class="text-sm text-gray-500">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> patients</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DOB / Age</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($patientResult ) > 0): ?>
                                        <?php $counter = $offset + 1; ?>
                                        <?php while($row = mysqli_fetch_assoc($patientResult )): ?>
                                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all fade-in">
                                                <td class="px-4 py-3 font-medium text-gray-500"><?php echo $counter++; ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-3">
                                                       <?php
                                                        $image = $row['patient_image'];

                                                        if (!empty($image) && file_exists("" . $image)) {
                                                        ?>
                                                            <img src="<?php echo htmlspecialchars($image); ?>"
                                                                alt="<?php echo htmlspecialchars($row['patient_name']); ?>"
                                                                class="patient-avatar">
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                                                <?php echo strtoupper(substr($row['patient_name'], 0, 1)); ?>
                                                            </div>
                                                        <?php
                                                        }
                                                        ?>
                                                        <div>
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                                            <div class="text-xs text-gray-500">ID: #<?php echo $row['patient_id']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm">
                                                        <div class="flex items-center gap-1 text-gray-600">
                                                            <i data-lucide="mail" class="w-3 h-3"></i>
                                                            <?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?>
                                                        </div>
                                                        <div class="flex items-center gap-1 text-gray-600 mt-1">
                                                            <i data-lucide="phone" class="w-3 h-3"></i>
                                                            <?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php if(!empty($row['blood_group'])): 
                                                        $blood_class = 'blood-o';
                                                        if(strpos($row['blood_group'], 'A') !== false) $blood_class = 'blood-a';
                                                        elseif(strpos($row['blood_group'], 'B') !== false) $blood_class = 'blood-b';
                                                        elseif(strpos($row['blood_group'], 'AB') !== false) $blood_class = 'blood-ab';
                                                    ?>
                                                        <span class="blood-group-badge <?php echo $blood_class; ?>">
                                                            <?php echo htmlspecialchars($row['blood_group']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-sm">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm">
                                                        <div class="text-gray-700"><?php echo date("M d, Y", strtotime($row['date_of_birth'])); ?></div>
                                                        <div class="text-xs text-gray-500">Age: <?php echo $row['age']; ?> yrs</div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="capitalize text-gray-700"><?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php if(!empty($row['doctor_name'])): ?>
                                                        <span class="text-sm text-gray-700"><?php echo htmlspecialchars($row['doctor_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-sm">Not Assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                    $status = isset($row['status']) ? trim($row['status']) : 'Active';
                                                    if ($status == 'Active') {
                                                        $status_class = 'status-active';
                                                    } else {
                                                        $status_class = 'status-inactive';
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($status); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <a href="view_patient.php?id=<?php echo $row['patient_id']; ?>" 
                                                           class="action-btn p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-all" 
                                                           title="View Details">
                                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                                <div class="flex flex-col items-center justify-center">
                                                    <i data-lucide="users" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                                    <p class="text-lg font-medium text-gray-600">No patients found</p>
                                                    <p class="text-sm text-gray-400 mt-1">Try adjusting your search or filter criteria</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                            </div>
                            <div class="flex gap-2">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" 
                                       class="px-3 py-1 border rounded-lg hover:bg-gray-100 transition text-sm">
                                        <i data-lucide="chevron-left" class="w-4 h-4 inline"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if($i == $page): ?>
                                        <span class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" 
                                           class="px-3 py-1 border rounded-lg hover:bg-gray-100 transition text-sm">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" 
                                       class="px-3 py-1 border rounded-lg hover:bg-gray-100 transition text-sm">
                                        Next <i data-lucide="chevron-right" class="w-4 h-4 inline"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Confirm Delete</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this patient? This action cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg transition text-sm font-medium">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition text-sm font-medium">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

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