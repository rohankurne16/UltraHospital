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

$opd_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;
$doctor_reg_id = $_SESSION['id'];

$getDoctor = "SELECT doctor_id FROM doctor WHERE register_id='$doctor_reg_id'";
$all_doctor_info = $conn->query($getDoctor);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_id = $doctor["doctor_id"];
}

$sql = "SELECT o.*, p.patient_name,p.patient_image, p.mobile, p.gender, p.age, p.address, p.blood_group, p.email 
        FROM opd o 
        LEFT JOIN patients p ON o.patient_id = p.patient_id 
        WHERE o.id='$opd_id' 
        AND o.doctor_id='$doctor_id' 
        AND (o.delete_flag=0 OR o.delete_flag IS NULL)";


$result = mysqli_query($conn, $sql);
$opd = mysqli_fetch_assoc($result);

if(!$opd) {
    $_SESSION['error_message'] = "OPD record not found.";
    header("Location: dashboard.php");
    exit();
}

$previousVisitsSql = "SELECT o.*, p.patient_name,p.patient_image
                      FROM opd o 
                      LEFT JOIN patients p ON o.patient_id = p.patient_id 
                      WHERE o.patient_id = '{$opd['patient_id']}' 
                      AND o.id != '$opd_id' 
                      AND o.doctor_id = '$doctor_id'
                      AND (o.delete_flag=0 OR o.delete_flag IS NULL)
                      ORDER BY o.visit_date DESC, o.created_at DESC 
                      LIMIT 5";
$previousVisitsResult = mysqli_query($conn, $previousVisitsSql);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - OPD Details</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .detail-label { color: #64748b; font-weight: 500; font-size: 13px; }
        .detail-value { color: #0f172a; font-weight: 600; font-size: 15px; }
        .info-card { background: #f8fafc; border-radius: 8px; padding: 12px 16px; border: 1px solid #e5e7eb; }
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; }
        .action-btn-prescription { color: #6366f1; }
        .action-btn-prescription:hover { background: #e0e7ff; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; }
        .action-btn-back { color: #3b82f6; }
        .action-btn-back:hover { background: #dbeafe; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>  
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="opd_main.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">OPD Details</h1>
                                    <p class="text-gray-500 mt-1">View complete OPD record information.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="edit_opd.php?id=<?php echo $opd_id; ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-all">
                                    <i data-lucide="edit-2" class="w-4 h-4 mr-2"></i>
                                    Edit
                                </a>
                                <a href="create_prescription.php?patient_id=<?php echo $opd['patient_id']; ?>&opd_id=<?php echo $opd_id; ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all">
                                    <i data-lucide="pill" class="w-4 h-4 mr-2"></i>
                                    Prescription
                                </a>
                                <a href="delete_opd.php?id=<?php echo $opd_id; ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50 transition-all"
                                   onclick="return confirm('Are you sure you want to delete this OPD record?')">
                                    <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                                    Delete
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6 fade-in">
                        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
                            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                              
                                    <?php
                                        $image = $opd['patient_image'];
                                        if($image !=  null or !empty($image)) { ?>
                                           
                                            <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center">
                                                <?php                                            
                                                if (!empty($image) && file_exists("../" . $image)) {
                                                ?>
                                                    <img src="../<?php echo htmlspecialchars($image); ?>"
                                                        alt="<?php echo htmlspecialchars($opd['patient_name']); ?>"
                                                        class="w-24 h-24 rounded-full object-cover">
                                                <?php
                                                } else {
                                                ?>
                                                    <i data-lucide="user" class="w-12 h-12 text-blue-600"></i>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                        <?php }else{ ?>
                                                 <i data-lucide="user" class="w-10 h-10 text-blue-600"></i>
                                        <?php } ?>
                
                                   
                                
                                <div class="flex-1">

        
                                    <div class="flex flex-wrap items-center gap-3">
                                        
                                        <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($opd['patient_name'] ?? 'Unknown'); ?></h2>
                                        
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                                        <div>
                                            <p class="text-sm text-gray-500">OPD Number</p>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['opd_no']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Visit Date</p>
                                            <p class="font-semibold text-gray-900"><?php echo date('d M Y', strtotime($opd['visit_date'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Gender</p>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['gender'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Age</p>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['age'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Mobile</p>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['mobile'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Blood Group</p>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['blood_group'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['email'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Patient ID</p>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['patient_id'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <?php if(!empty($opd['address'])): ?>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($opd['address']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden fade-in">
                            <div class="p-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="font-semibold text-gray-900">Clinical Details</h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Symptoms</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($opd['symptoms'] ?? '—'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Diagnosis</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($opd['diagnosis'] ?? '—'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Doctor's Note</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($opd['doctor_note'] ?? '—'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden fade-in">
                            <div class="p-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="font-semibold text-gray-900">Vital Signs</h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="info-card">
                                        <p class="text-sm text-gray-500">Blood Pressure</p>
                                        <p class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($opd['bp'] ?? '—'); ?></p>
                                    </div>
                                    <div class="info-card">
                                        <p class="text-sm text-gray-500">Pulse (bpm)</p>
                                        <p class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($opd['pulse'] ?? '—'); ?></p>
                                    </div>
                                    <div class="info-card">
                                        <p class="text-sm text-gray-500">Weight (kg)</p>
                                        <p class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($opd['weight'] ?? '—'); ?></p>
                                    </div>
                                    <div class="info-card">
                                        <p class="text-sm text-gray-500">Temperature (°F)</p>
                                        <p class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($opd['temperature'] ?? '—'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if(mysqli_num_rows($previousVisitsResult) > 0): ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden fade-in">
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-900">Previous Visits</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OPD No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symptoms</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($visit = mysqli_fetch_assoc($previousVisitsResult)): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all">
                                        <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($visit['opd_no']); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo date('d M Y', strtotime($visit['visit_date'])); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($visit['symptoms'] ?? '—'); ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($visit['diagnosis'] ?? '—'); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="status-badge <?php echo (!empty($visit['diagnosis']) && $visit['diagnosis'] != '') ? 'status-completed' : 'status-pending'; ?>">
                                                <?php echo (!empty($visit['diagnosis']) && $visit['diagnosis'] != '') ? 'Completed' : 'Pending'; ?>
                                            </span>
                                        </td>
                                        
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>