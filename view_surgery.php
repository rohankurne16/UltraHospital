<?php
session_start();
include('config/hospital.php');

// Check if surgery_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$surgery_id = mysqli_real_escape_string($conn, $_GET['id']);
$hid = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];
$hospital_logo = $_SESSION['hospital_logo'];

// Fetch surgery details with patient and doctor information
$query = "SELECT 
            s.*,
            p.patient_name,
            p.mobile,
            p.email,
            p.address,
            d.doctor_name as surgeon_name,
            d.specialization,
            d.mobile as doctor_phone
          FROM surgeries s
          LEFT JOIN patients p ON s.patient_id = p.patient_id
          LEFT JOIN doctor d ON s.doctor_id = d.doctor_id
          WHERE s.surgery_id = '$surgery_id' 
          AND s.hospital_id = '$hid'
          AND s.delete_flag = '0'";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: surgeries.php");
    exit();
}

$surgery = mysqli_fetch_assoc($result);

// Get hospital info
$hospital_query = mysqli_query($conn, "SELECT * FROM hospital_master WHERE hospital_id = '$hid'");
$hospital = mysqli_fetch_assoc($hospital_query);

// Format date and time
$surgery_date = date('F d, Y', strtotime($surgery['surgery_date']));
$surgery_time = date('h:i A', strtotime($surgery['surgery_time']));
$created_at = date('F d, Y h:i A', strtotime($surgery['created_at']));
$modified_at = $surgery['modified_at'] ? date('F d, Y h:i A', strtotime($surgery['modified_at'])) : 'Not modified yet';
$follow_up_date = $surgery['follow_up_date'] ? date('F d, Y', strtotime($surgery['follow_up_date'])) : 'Not scheduled';

// Status badge color
function getStatusBadge($status) {
    $badges = [
        'Scheduled' => 'bg-blue-100 text-blue-800',
        'Completed' => 'bg-green-100 text-green-800',
        'Cancelled' => 'bg-red-100 text-red-800',
        'In Progress' => 'bg-yellow-100 text-yellow-800'
    ];
    return isset($badges[$status]) ? $badges[$status] : 'bg-gray-100 text-gray-800';
}

// Surgery type badge color
function getTypeBadge($type) {
    $badges = [
        'Major' => 'bg-purple-100 text-purple-800',
        'Minor' => 'bg-indigo-100 text-indigo-800',
        'Emergency' => 'bg-red-100 text-red-800',
        'Elective' => 'bg-blue-100 text-blue-800'
    ];
    return isset($badges[$type]) ? $badges[$type] : 'bg-gray-100 text-gray-800';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital['hospital_name']); ?> - View Surgery</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital['hospital_logo']); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .main-wrapper {
            margin-left: 250px;
            margin-top: 70px;
            padding: 20px;
            min-height: 100vh;
            background: #f9fafb;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 10px;
                margin-top: 60px;
            }
            
            .detail-card {
                padding: 15px !important;
            }
            
            .grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .grid-cols-1.md\:grid-cols-3 {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .flex.justify-end.gap-3 {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }
            
            .flex.justify-end.gap-3 a,
            .flex.justify-end.gap-3 button {
                width: 100% !important;
                text-align: center !important;
                justify-content: center !important;
            }
            
            input, select, textarea {
                font-size: 16px !important;
            }
            
            .flex.items-center.gap-3 {
                flex-wrap: wrap;
            }
            
            h1.text-2xl {
                font-size: 1.25rem !important;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .main-wrapper {
                margin-left: 200px;
                padding: 15px;
            }
        }
        
        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 24px;
        }
        
        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 0.95rem;
            color: #1f2937;
            word-wrap: break-word;
        }
        
        .detail-value-null {
            color: #9ca3af;
            font-style: italic;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'Sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="surgeries.php" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-100 hover:bg-blue-50 transition-all flex-shrink-0">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="flex-1 min-w-[200px]">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Surgery Details</h1>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($hospital['hospital_name']); ?> • Surgery #<?php echo htmlspecialchars($surgery['surgery_no']); ?></p>
            </div>
            <div class="flex gap-2">
                <a href="edit_surgery.php?id=<?php echo $surgery_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i data-lucide="edit" class="w-4 h-4"></i> Edit
                </a>
                <button onclick="confirmDelete(<?php echo $surgery_id; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i data-lucide="trash" class="w-4 h-4"></i> Delete
                </button>
            </div>
        </div>
        
        <div class="mt-6">
            <!-- Patient & Surgery Overview -->
            <div class="detail-card mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="detail-label">Patient Information</div>
                        <div class="mt-2">
                            <p class="font-semibold text-lg"><?php echo htmlspecialchars($surgery['patient_name']); ?></p>
                            <p class="text-sm text-gray-600">Patient ID: #<?php echo htmlspecialchars($surgery['patient_id']); ?></p>
                            <?php if (!empty($surgery['patient_phone'])): ?>
                                <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($surgery['patient_phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($surgery['patient_email'])): ?>
                                <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($surgery['patient_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-label">Surgery Information</div>
                        <div class="mt-2">
                            <p class="font-semibold"><?php echo htmlspecialchars($surgery['surgery_title']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($surgery['surgery_full_name']); ?></p>
                            <p class="text-sm text-gray-600">Surgery #: <?php echo htmlspecialchars($surgery['surgery_no']); ?></p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="status-badge <?php echo getStatusBadge($surgery['status']); ?>">
                                    <?php echo htmlspecialchars($surgery['status']); ?>
                                </span>
                                <span class="type-badge <?php echo getTypeBadge($surgery['surgery_type']); ?>">
                                    <?php echo htmlspecialchars($surgery['surgery_type']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-label">Surgeon</div>
                        <div class="mt-2">
                            <p class="font-semibold"><?php echo htmlspecialchars($surgery['surgeon_name'] ?? 'Not assigned'); ?></p>
                            <?php if (!empty($surgery['doctor_specialty'])): ?>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($surgery['doctor_specialty']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($surgery['doctor_phone'])): ?>
                                <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($surgery['doctor_phone']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Surgery Schedule -->
            <div class="detail-card mb-6">
                <h3 class="section-title">Schedule Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?php echo $surgery_date; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Time</div>
                        <div class="detail-value"><?php echo $surgery_time; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Duration</div>
                        <div class="detail-value"><?php echo htmlspecialchars($surgery['surgery_duration']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Medical Team -->
            <div class="detail-card mb-6">
                <h3 class="section-title">Medical Team</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="detail-label">Surgeon</div>
                        <div class="detail-value"><?php echo htmlspecialchars($surgery['surgeon_name'] ?? 'Not assigned'); ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Assistant Surgeon</div>
                        <div class="detail-value <?php echo empty($surgery['assistant_surgeon']) ? 'detail-value-null' : ''; ?>">
                            <?php echo !empty($surgery['assistant_surgeon']) ? htmlspecialchars($surgery['assistant_surgeon']) : 'Not assigned'; ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Anesthetist</div>
                        <div class="detail-value"><?php echo htmlspecialchars($surgery['anesthetist']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Surgery Details -->
            <div class="detail-card mb-6">
                <h3 class="section-title">Surgery Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="detail-label">Surgery Type</div>
                        <div class="detail-value">
                            <span class="type-badge <?php echo getTypeBadge($surgery['surgery_type']); ?>">
                                <?php echo htmlspecialchars($surgery['surgery_type']); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Category</div>
                        <div class="detail-value"><?php echo htmlspecialchars($surgery['surgery_category']); ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Blood Loss</div>
                        <div class="detail-value <?php echo empty($surgery['blood_loss']) ? 'detail-value-null' : ''; ?>">
                            <?php echo !empty($surgery['blood_loss']) ? htmlspecialchars($surgery['blood_loss']) : 'Not recorded'; ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Follow-up Date</div>
                        <div class="detail-value <?php echo empty($surgery['follow_up_date']) ? 'detail-value-null' : ''; ?>">
                            <?php echo !empty($surgery['follow_up_date']) ? $follow_up_date : 'Not scheduled'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Clinical Notes -->
            <div class="detail-card mb-6">
                <h3 class="section-title">Clinical Notes</h3>
                <div class="space-y-4">
                    <div>
                        <div class="detail-label">Diagnosis Before Surgery</div>
                        <div class="detail-value bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($surgery['diagnosis_before_surgery'])); ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Procedure Details</div>
                        <div class="detail-value bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($surgery['procedure_details'])); ?></div>
                    </div>
                    <?php if (!empty($surgery['findings'])): ?>
                    <div>
                        <div class="detail-label">Findings</div>
                        <div class="detail-value bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($surgery['findings'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($surgery['complications'])): ?>
                    <div>
                        <div class="detail-label">Complications</div>
                        <div class="detail-value bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($surgery['complications'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($surgery['recovery_notes'])): ?>
                    <div>
                        <div class="detail-label">Recovery Notes</div>
                        <div class="detail-value bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($surgery['recovery_notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($surgery['notes'])): ?>
                    <div>
                        <div class="detail-label">Additional Notes</div>
                        <div class="detail-value bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($surgery['notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Metadata -->
            <div class="detail-card">
                <h3 class="section-title">System Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="detail-label">Created At</div>
                        <div class="detail-value text-sm text-gray-600"><?php echo $created_at; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Last Modified</div>
                        <div class="detail-value text-sm text-gray-600"><?php echo $modified_at; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Hospital Location</div>
                        <div class="detail-value text-sm text-gray-600"><?php echo htmlspecialchars($surgery['hospital_location']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                <a href="surgeries.php" class="px-6 py-2 border rounded-lg hover:bg-gray-50 text-center order-2 sm:order-1">
                    Back to List
                </a>
                <a href="edit_surgery.php?id=<?php echo $surgery_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-center order-1 sm:order-2">
                    <i class="fas fa-edit mr-2"></i> Edit Surgery
                </a>
                <a href="view_patient.php?id=<?php echo $surgery['patient_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg text-center order-3">
                    <i class="fas fa-user mr-2"></i> View Patient
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Delete confirmation
        function confirmDelete(surgeryId) {
            if (confirm('Are you sure you want to delete this surgery record? This action cannot be undone.')) {
                window.location.href = 'delete_surgery.php?id=' + surgeryId;
            }
        }
    </script>
</body>
</html>