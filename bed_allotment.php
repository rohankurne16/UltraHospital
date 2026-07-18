<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

// Get parameters from URL
$bed_id = isset($_GET['bed_id']) ? (int)$_GET['bed_id'] : 0;

// Fetch bed details with treatment_master_id
if ($bed_id > 0) {
    $sql="
    SELECT
        b.bed_id,
        b.bed_no,
        b.bed_type,
        b.status AS bed_status,

        r.room_id,
        r.room_no,

        w.ward_id,
        w.ward_name,

        ba.allocation_id,
        ba.admit_date,
        ba.discharge_date,
        ba.status AS allocation_status,

        p.patient_id,
        p.patient_name,
        p.patient_image,
        p.age,
        p.blood_group,
        p.gender,
        p.mobile,
        p.email,
        p.medical_history,
        p.allergy,

        ia.id AS admission_id,
        ia.admission_no,
        ia.doctor_id,
        ia.department,
        ia.admission_date,

        d.doctor_name,
        
        tm.treatment_master_id

    FROM bed_master b

    INNER JOIN room_master r
        ON b.room_id = r.room_id

    INNER JOIN ward_master w
        ON r.ward_id = w.ward_id

    LEFT JOIN bed_allocation ba
        ON b.bed_id = ba.bed_id
        AND ba.status = 'Occupied'

    LEFT JOIN patients p
        ON ba.patient_id = p.patient_id
        AND (p.delete_flag = 0 OR p.delete_flag IS NULL)

    LEFT JOIN ipd_admissions ia
        ON ia.id = (
            SELECT MAX(id)
            FROM ipd_admissions
            WHERE patient_id = ba.patient_id
            AND status = 'Admitted'
            AND delete_flag = 0
        )

    LEFT JOIN doctor d
        ON ia.doctor_id = d.doctor_id
        
    LEFT JOIN ipd_treatment_master tm
        ON tm.ipd_id = ia.id
        AND tm.delete_flag = 0
        AND tm.status = 'Active'

    WHERE b.bed_id = $bed_id
    AND (b.delete_flag = 0 OR b.delete_flag IS NULL)";
    
    $result = mysqli_query($conn, $sql);
    if(!$result){
        die("SQL Error : ".mysqli_error($conn));
    }
    
    $bedDetails = mysqli_fetch_assoc($result);
    
    
    if (!$bedDetails) {
        die("Bed not found");
    }
} else {
    die("No bed ID provided");
}

// Check if patient is assigned
$hasPatient = !empty($bedDetails['patient_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Bed #<?php echo htmlspecialchars($bedDetails['bed_no']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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

        .page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #64748b;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .back-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .page-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }

        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 24px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .info-item {
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .info-item:hover {
            background: white;
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-occupied {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-maintenance {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Patient Card Styling */
        .patient-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin-top: 10px;
            position: relative;
            transition: all 0.3s ease;
        }

        .clickable-patient-card {
            cursor: pointer;
        }

        .clickable-patient-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: #22c55e;
        }

        .patient-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .patient-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #16a34a;
            font-weight: 700;
            font-size: 32px;
            border: 3px solid #b9f6ca;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .patient-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .patient-info-header {
            flex: 1;
        }

        .patient-name {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .patient-id {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }

        /* Discharge Button Styling */
        .discharge-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #ef4444;
            color: white;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            border: none;
            cursor: pointer;
        }

        .discharge-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
            color: white;
        }

        .discharge-btn i {
            font-size: 16px;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            background: #f8fafc;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 56px;
            color: #cbd5e1;
            margin-bottom: 16px;
            display: block;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
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

            .page-title {
                font-size: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .patient-header {
                flex-direction: column;
                text-align: center;
            }

            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <!-- Sidebar -->
            <?php include 'Sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="main-content">
                <div class="max-w-7xl mx-auto w-full">
                    
                    <!-- Page Header -->
                    <div class="page-header">
                        <a href="view_bed.php?id=<?php echo $bedDetails['room_id']; ?>&ward_id=<?php echo $bedDetails['ward_id']; ?>" class="back-btn" title="Back to Beds">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title">Bed #<?php echo htmlspecialchars($bedDetails['bed_no']); ?></h1>
                            <p class="page-subtitle"><?php echo htmlspecialchars($bedDetails['ward_name']); ?> - Room <?php echo htmlspecialchars($bedDetails['room_no']); ?></p>
                        </div>
                    </div>

                    <!-- Bed Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Bed Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Bed Number</div>
                                    <div class="info-value">#<?php echo htmlspecialchars($bedDetails['bed_no']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Bed Type</div>
                                    <div class="info-value"><?php echo htmlspecialchars($bedDetails['bed_type']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Ward</div>
                                    <div class="info-value"><?php echo htmlspecialchars($bedDetails['ward_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Room</div>
                                    <div class="info-value"><?php echo htmlspecialchars($bedDetails['room_no']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <span class="status-badge status-<?php echo strtolower($bedDetails['bed_status']); ?>">
                                            <?php echo htmlspecialchars($bedDetails['bed_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information Card -->
                    <?php if ($hasPatient): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2>Patient Information</h2>
                                <!-- Discharge Button in Header -->
                              <a href="ipd_discharge.php?id=<?php echo $bedDetails['treatment_master_id']; ?>" class="discharge-btn">
                                <i class="fa-solid fa-door-open"></i>
                                Discharge Patient
                            </a>
                            
                            </div>
                            <div class="card-body">
                                <div class="patient-card clickable-patient-card" onclick="window.location.href='view_patient.php?id=<?php echo $bedDetails['patient_id']; ?>'">
                                    <div class="patient-header">
                                        <div class="patient-avatar">
                                            <?php if (!empty($bedDetails['patient_image']) && file_exists($bedDetails['patient_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($bedDetails['patient_image']); ?>" alt="Patient">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($bedDetails['patient_name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="patient-info-header">
                                            <div class="patient-name"><?php echo htmlspecialchars($bedDetails['patient_name']); ?></div>
                                            <div class="patient-id">Patient ID: <?php echo htmlspecialchars($bedDetails['patient_id']); ?></div>
                                        </div>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Age / Gender</div>
                                            <div class="info-value"><?php echo htmlspecialchars($bedDetails['age'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($bedDetails['gender'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Blood Group</div>
                                            <div class="info-value"><?php echo htmlspecialchars($bedDetails['blood_group'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Mobile</div>
                                            <div class="info-value"><?php echo htmlspecialchars($bedDetails['mobile'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Assigned Doctor</div>
                                            <div class="info-value"><?php echo htmlspecialchars($bedDetails['doctor_name'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Admission Date</div>
                                            <div class="info-value"><?php echo date('d M Y', strtotime($bedDetails['admit_date'])); ?></div>
                                        </div>
                                        <?php if (!empty($bedDetails['allergy'])): ?>
                                        <div class="info-item">
                                            <div class="info-label">Allergies</div>
                                            <div class="info-value"><?php echo htmlspecialchars($bedDetails['allergy']); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($bedDetails['medical_history'])): ?>
                                    <div style="margin-top: 16px; padding: 12px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        <div class="info-label" style="margin-bottom: 8px;">Medical History</div>
                                        <div style="color: #0f172a; font-size: 14px; line-height: 1.6;">
                                            <?php echo htmlspecialchars($bedDetails['medical_history']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h2>Patient Information</h2>
                            </div>
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fa-solid fa-user-slash"></i>
                                    <h3>No Patient Assigned</h3>
                                    <p>This bed is currently unoccupied. No patient has been assigned to this bed yet.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
