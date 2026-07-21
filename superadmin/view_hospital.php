<?php

include_once '../config/hospital.php';
include_once '../config/permission.php';

// Check Super Admin login
checkSuperAdminLogin();

// Get hospital ID from URL - This receives the ID from hospitals.php
$hospital_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// If no ID or invalid ID, redirect back to hospitals list
if ($hospital_id == 0) {
    header('Location: hospitals.php');
    exit;
}

// Fetch hospital details with all related data
$query = "SELECT h.*, 
          ha.admin_id,
          ha.full_name as admin_name, 
          ha.email as admin_email,
          ha.mobile as admin_phone,
           (SELECT COUNT(*) FROM doctor WHERE hospital_id=h.hospital_id AND delete_flag=0) AS total_doctors,
          (SELECT COUNT(*) FROM staff WHERE hospital_id=h.hospital_id AND delete_flag=0) AS total_staff,
          (SELECT COUNT(*) FROM patients WHERE hospital_id=h.hospital_id AND delete_flag=0) AS total_patients,
          (SELECT COUNT(*) FROM department WHERE hospital_id=h.hospital_id AND delete_flag=0) AS total_departments,
          
          (SELECT COUNT(*) FROM ipd_admissions WHERE hospital_id=h.hospital_id AND delete_flag=0) AS total_ipd,
          (SELECT COUNT(*) FROM opd WHERE hospital_id=h.hospital_id AND delete_flag=0) AS total_opd


          FROM hospital_master h 
          LEFT JOIN hospital_admin ha ON h.hospital_id = ha.hospital_id AND ha.delete_flag = 0
          WHERE h.hospital_id = $hospital_id AND h.delete_flag = 0";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// If hospital not found, redirect back
if (mysqli_num_rows($result) == 0) {
    header('Location: hospitals.php');
    exit;
}

$hospital = mysqli_fetch_assoc($result);
$theme = $_SESSION['theme'] ?? 'light';

$page_title = 'Hospital Details';
$page_subtitle = 'View complete details of ' . htmlspecialchars($hospital['hospital_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital['hospital_name']); ?> - Hospital Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.light { background: #f1f5f9; }
      
        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .brand-text h2 { 
            font-size: 0.9rem; 
            font-weight: 700; 
            margin: 0; 
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; 
        }
        .brand-text p { 
            font-size: 0.65rem; 
            color: #94a3b8; 
            margin: 0; 
        }
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed { margin-left: 70px; }
        
        .content-card { 
            border-radius: 16px; 
            padding: 1.5rem; 
            transition: all 0.3s ease; 
            margin-bottom: 1.5rem;
        }
        body.dark .content-card { 
            background: #1a1a1a; 
            border: 1px solid #2a2a2a; 
        }
        body.light .content-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.06); 
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            color: white; 
            border: none; 
            padding: 0.6rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); 
        }
        
        .btn-secondary { 
            padding: 0.6rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-secondary:hover { 
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; 
        }
        
        .btn-back { 
            padding: 0.6rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-back:hover { 
            background: <?php echo $theme == 'dark' ? '#3a3a3a' : '#e2e8f0'; ?>; 
            transform: translateY(-2px);
        }
        
        .status-badge { 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 600; 
            display: inline-block; 
        }
        .status-active { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .status-inactive { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .info-item {
            padding: 1rem;
            border-radius: 12px;
            background: <?php echo $theme == 'dark' ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)'; ?>;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
        }
        .info-item .label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .info-item .value {
            font-size: 1rem;
            font-weight: 500;
            margin-top: 0.25rem;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
        }
        .info-item .value i {
            margin-right: 0.5rem;
            color: #3b82f6;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-card {
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            background: <?php echo $theme == 'dark' ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)'; ?>;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.2);
        }
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #3b82f6;
        }
        .stat-card .stat-label {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        .stat-card .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #3b82f6;
        }
        
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i {
            color: #3b82f6;
        }
        
        @media (max-width: 768px) {
          
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .main-content.collapsed { margin-left: 0; }
            .action-row {
                flex-direction: column;
                align-items: stretch;
            }
            .action-row a {
                justify-content: center;
            }
            .stat-cards {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Action Row -->
    <div class="action-row">
        <a href="hospitals.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Hospitals
        </a>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a href="edit_hospital.php?id=<?php echo $hospital['hospital_id']; ?>" class="btn-primary" style="background:linear-gradient(135deg, #22c55e, #16a34a);">
                <i class="fas fa-edit"></i> Edit
            </a>
            
        </div>
    </div>

    

    <!-- Hospital Header -->
    <div class="content-card">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <div style="width:80px;height:80px;border-radius:20px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;color:#3b82f6;font-size:2.5rem;">
               <?php if(!empty($hospital['hospital_logo'])) { ?>
        
                    <img src="../<?php echo $hospital['hospital_logo']; ?>"
                        alt="Hospital Logo"
                        style="width:100%;height:100%;object-fit:cover;">

                <?php } else { ?>

                    <i class="fas fa-hospital"
                    style="font-size:24px;color:#3b82f6;"></i>

                <?php } ?>


            </div>
            <div style="flex:1;">
                <h2 style="font-size:1.8rem;font-weight:700;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin:0;">
                    <?php echo htmlspecialchars($hospital['hospital_name']); ?>
                </h2>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:0.25rem;">
                    <span style="font-size:0.9rem;color:#94a3b8;">
                        <i class="fas fa-code"></i> Code: <?php echo htmlspecialchars($hospital['hospital_code']); ?>
                    </span>
                    <span class="status-badge <?php echo $hospital['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                        <i class="fas fa-circle" style="font-size:0.5rem;margin-right:0.3rem;"></i>
                        <?php echo $hospital['status']; ?>
                    </span>
                    <span style="font-size:0.85rem;color:#94a3b8;">
                        <i class="fas fa-calendar-alt"></i> Established: <?php echo htmlspecialchars($hospital['established_year'] ?? 'N/A'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="content-card">
        <h3 class="section-title">
            <i class="fas fa-chart-bar"></i> Hospital Statistics
        </h3>
        <div class="stat-cards">
           
            <div class="stat-card">
                 <a href="hospital_doctors.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="stat-icon"><i class="fas fa-user-md"></i></div>
                <div class="number"><?php echo $hospital['total_doctors'] ?? 0; ?></div>
                <div class="stat-label">Total Doctors</div>
</a>
            </div>
            <div class="stat-card">
                <a href="hospital_staff.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="number"><?php echo $hospital['total_staff'] ?? 0; ?></div>
                <div class="stat-label">Total Staff</div>
                </a>
            </div>
            <div class="stat-card">
                <a href="hospital_patients.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
                <div class="number"><?php echo $hospital['total_patients'] ?? 0; ?></div>
                <div class="stat-label">Total Patients</div>
                </a>
            </div>
            <div class="stat-card">
                <a href="hospital_departments.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="number"><?php echo $hospital['total_departments'] ?? 0; ?></div>
                <div class="stat-label">Departments</div>
                </a>
            </div>
            <div class="stat-card">
                <a href="hospital_ipdadm.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="stat-icon"><i class="fas fa-procedures"></i></div>
                <div class="number"><?php echo $hospital['total_ipd'] ?? 0; ?></div>
                <div class="stat-label">IPD Admissions</div>
                                </a>

            </div>
            <div class="stat-card">
                <a href="hospital_opd.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="stat-icon"><i class="fas fa-clinic-medical"></i></div>
                <div class="number"><?php echo $hospital['total_opd'] ?? 0; ?></div>
                <div class="stat-label">OPD Visits</div>
                                </a>

            </div>
        </div>
    </div>

    <!-- Hospital Information -->
    <div class="content-card">
        <h3 class="section-title">
            <i class="fas fa-info-circle"></i> Hospital Information
        </h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="label"><i class="fas fa-envelope"></i> Email</div>
                <div class="value"><?php echo htmlspecialchars($hospital['email'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-phone"></i> Phone</div>
                <div class="value"><?php echo htmlspecialchars($hospital['phone'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-map-marker-alt"></i> Address</div>
                <div class="value"><?php echo htmlspecialchars($hospital['address'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-city"></i> City</div>
                <div class="value"><?php echo htmlspecialchars($hospital['city'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-map"></i> State</div>
                <div class="value"><?php echo htmlspecialchars($hospital['state'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-globe"></i> Country</div>
                <div class="value"><?php echo htmlspecialchars($hospital['country'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-mail-bulk"></i> Pincode</div>
                <div class="value"><?php echo htmlspecialchars($hospital['pincode'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="label"><i class="fas fa-calendar-plus"></i> Registration Date</div>
                <div class="value"><?php echo date('d M Y', strtotime($hospital['created_at'] ?? 'now')); ?></div>
            </div>
        </div>
    </div>

    <!-- Admin Details -->
    <div class="content-card">
        <h3 class="section-title">
            <i class="fas fa-user-shield"></i> Hospital Admin
        </h3>
        <?php if (!empty($hospital['admin_email'])): ?>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label"><i class="fas fa-user"></i> Name</div>
                    <div class="value"><?php echo htmlspecialchars($hospital['admin_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="value"><?php echo htmlspecialchars($hospital['admin_email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><i class="fas fa-phone"></i> Phone</div>
                    <div class="value"><?php echo htmlspecialchars($hospital['admin_phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><i class="fas fa-user-tag"></i> Status</div>
                    <div class="value">
                        <span class="status-badge <?php echo ($hospital['admin_status'] ?? '') == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $hospital['admin_status'] ?? 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:1.5rem;color:#94a3b8;">
                <i class="fas fa-exclamation-circle" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                <p>No admin assigned to this hospital</p>
                <a href="assign_admin.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" class="btn-primary" style="margin-top:1rem;">
                    <i class="fas fa-user-plus"></i> Assign Admin
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
   
</div>

<script>


// ============================================================
// THEME TOGGLE
// ============================================================
function toggleTheme() {
    const body = document.body;
    const currentTheme = body.classList.contains('light') ? 'dark' : 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.classList.remove(currentTheme);
    body.classList.add(newTheme);
    
    fetch('toggle_theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'theme=' + newTheme
    });
}

// ============================================================
// INITIALIZE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Any initialization code
});
</script>
</body>
</html>