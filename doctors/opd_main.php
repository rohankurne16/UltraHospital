<?php 
session_start(); 

include '../config/hospital.php';

if(!$conn){
    die("Connection Failed : " . mysqli_connect_error());
}

if(!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$doctor_reg_id = $_SESSION['id'];

$getDoctor = "SELECT doctor_id FROM doctor WHERE register_id='$doctor_reg_id'";
$all_doctor_info = $conn->query($getDoctor);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_id = $doctor["doctor_id"];
$sql = "SELECT opd.*, patients.patient_name
        FROM opd
        LEFT JOIN patients
            ON opd.patient_id = patients.patient_id
        WHERE opd.doctor_id='$doctor_id'
        AND (opd.delete_flag=0 OR opd.delete_flag IS NULL)
        ORDER BY opd.visit_date DESC, opd.created_at DESC";


    $result = $conn->query($sql);
    
    if (!$result) {
        die("Query Error: " . $conn->error);
    }
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - OPD Records</title>
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
        .tab-active {
            background-color: white;
            color: #111827;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .tab-inactive {
            color: #6b7280;
        }
        .tab-inactive:hover {
            background-color: #f3f4f6;
            color: #111827;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .transition-all {
            transition: all 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .action-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert {
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }
        .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .table-cell-max {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php' ?>  
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    
                    <?php if($successMessage): ?>
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700"><?php echo $successMessage; ?></p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-green-500 hover:text-green-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($errorMessage): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"><?php echo $errorMessage; ?></p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button onclick="this.closest('.alert').remove()" class="text-red-500 hover:text-red-700">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">OPD Records</h1>
                                    <p class="text-gray-500 mt-1">Manage outpatient department records and consultations.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="add_opd.php" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all">
                                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                    New OPD Visit
                                </a>
                            </div>
                        </div>
                    </div>

                    

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">All OPD Records</h2>
                                <p class="text-sm text-gray-500">View and manage all OPD records.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                <div class="relative flex-1 sm:flex-none">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    <input type="text" id="searchInput" 
                                           placeholder="Search OPD records..." 
                                           class="w-full sm:w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                           onkeyup="searchOPD()">
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OPD No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient ID</th>
                                         <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symptoms</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BP</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pulse</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Temp</th>
                                        
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="opdTableBody">
                                    <?php
                                    if (isset($result) && $result && $result->num_rows > 0) {
                                        $i = 1;
                                        while ($row = $result->fetch_assoc()) {
                                            $statusText = 'Pending';
                                            $statusClass = 'status-pending';
                                            
                                            if (!empty($row['diagnosis']) && $row['diagnosis'] != '') {
                                                $statusText = 'Completed';
                                                $statusClass = 'status-completed';
                                            }
                                            
                                            $opdId = isset($row['id']) ? $row['id'] : '';
                                            $opdNo = isset($row['opd_no']) ? htmlspecialchars($row['opd_no']) : '';
                                            $patientId = isset($row['patient_id']) ? htmlspecialchars($row['patient_id']) : '';
                                            $patientName = isset($row['patient_name']) ? htmlspecialchars($row['patient_name']) : '';
                                            $visitDate = isset($row['visit_date']) ? date('M d, Y', strtotime($row['visit_date'])) : '';
                                            $symptoms = isset($row['symptoms']) ? htmlspecialchars($row['symptoms']) : '';
                                            $diagnosis = isset($row['diagnosis']) ? htmlspecialchars($row['diagnosis']) : '';
                                            $bp = isset($row['bp']) ? htmlspecialchars($row['bp']) : '';
                                            $pulse = isset($row['pulse']) ? htmlspecialchars($row['pulse']) : '';
                                            $weight = isset($row['weight']) ? htmlspecialchars($row['weight']) : '';
                                            $temperature = isset($row['temperature']) ? htmlspecialchars($row['temperature']) : '';
                                            $doctorNote = isset($row['doctor_note']) ? htmlspecialchars($row['doctor_note']) : '';
                                            
                                            echo "<tr class=\"opd-row border-b border-gray-100 hover:bg-gray-50 transition-all fade-in\" 
                                                        data-search=\"" . strtolower($opdNo . ' ' . $patientId . ' ' . $symptoms . ' ' . $diagnosis) . "\">";
                                            echo "<td class=\"px-4 py-3 text-gray-500\">" . $i++ . "</td>";
                                            echo "<td class=\"px-4 py-3 font-medium text-gray-900\">" . $opdNo . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $patientId . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $patientName . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . $visitDate . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700 table-cell-max\" title=\"" . $symptoms . "\">" . ($symptoms ?: '—') . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700 table-cell-max\" title=\"" . $diagnosis . "\">" . ($diagnosis ?: '—') . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . ($bp ?: '—') . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . ($pulse ?: '—') . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . ($weight ?: '—') . "</td>";
                                            echo "<td class=\"px-4 py-3 text-gray-700\">" . ($temperature ?: '—') . "</td>";
                                           
                                            echo "<td class=\"px-4 py-3 text-right\">";
                                            echo "<div class=\"flex items-center justify-end gap-1\">";
                                            
                                            echo "<a href=\"view_opd.php?id=" . $opdId . "\" 
                                                    class=\"action-btn p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-all\" 
                                                    title=\"View Details\">
                                                    <i data-lucide=\"eye\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            
                                            echo "<a href=\"edit_opd.php?id=" . $opdId . "\" 
                                                    class=\"action-btn p-1.5 rounded-md text-purple-600 hover:bg-purple-50 transition-all\" 
                                                    title=\"Edit Record\">
                                                    <i data-lucide=\"edit-2\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            
                                            if (!empty($row['patient_id'])) {
                                                echo "<a href=\"create_prescription.php?patient_id=" . $row['patient_id'] . "&opd_id=" . $opdId . "\" 
                                                        class=\"action-btn p-1.5 rounded-md text-indigo-600 hover:bg-indigo-50 transition-all\" 
                                                        title=\"Write Prescription\">
                                                        <i data-lucide=\"pill\" class=\"w-4 h-4\"></i>
                                                      </a>";
                                            }
                                            
                                            echo "<a href=\"delete_opd.php?id=" . $opdId . "\" 
                                                    class=\"action-btn p-1.5 rounded-md text-red-600 hover:bg-red-50 transition-all\" 
                                                    title=\"Delete Record\"
                                                    onclick=\"return confirm('Are you sure you want to delete this OPD record?')\">
                                                    <i data-lucide=\"trash-2\" class=\"w-4 h-4\"></i>
                                                  </a>";
                                            
                                            echo "</div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan=\"12\" class=\"px-4 py-8 text-center text-gray-500\">";
                                        echo "<div class=\"flex flex-col items-center justify-center\">";
                                        echo "<i data-lucide=\"stethoscope\" class=\"w-12 h-12 mx-auto text-gray-300 mb-3\"></i>";
                                        echo "<p class=\"text-lg font-medium text-gray-600\">No OPD records found</p>";
                                        echo "<p class=\"text-sm text-gray-400 mt-1\">Start by creating a new OPD visit</p>";
                                        echo "<a href=\"opd_add.php\" class=\"inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-all\">";
                                        echo "Add OPD Visit";
                                        echo "</a>";
                                        echo "</div>";
                                        echo "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                            <div>
                                Showing <span id="visibleCount"><?php echo isset($result) ? $result->num_rows : 0; ?></span> OPD records
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

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

        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>

<?php $conn->close(); ?> 