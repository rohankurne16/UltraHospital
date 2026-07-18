<?php
include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// Check Super Admin login
checkSuperAdminLogin();

$hospital_id = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

if ($hospital_id == 0) {
    header('Location: hospitals.php');
    exit;
}

$hospital_query = "SELECT hospital_name, hospital_code FROM hospital_master WHERE hospital_id = $hospital_id AND delete_flag = 0";
$hospital_result = mysqli_query($conn, $hospital_query);

if (!$hospital_result || mysqli_num_rows($hospital_result) == 0) {
    header('Location: hospitals.php');
    exit;
}

$hospital = mysqli_fetch_assoc($hospital_result);
$theme = $_SESSION['theme'] ?? 'light';

$query = "
SELECT
    id,
    appointment_no,
    patient_id,
    doctor_id,
    department,
    appointment_type,
    appointment_date,
    appointment_time
FROM opd
WHERE hospital_id='$hospital_id'
AND delete_flag=0
ORDER BY appointment_date DESC, appointment_time DESC";

$result = mysqli_query($conn,$query);

if(!$result){
    die("Query Error : ".mysqli_error($conn));
}

$page_title = 'Hospital OPD Visits';
$page_subtitle = 'Manage OPD visits at ' . htmlspecialchars($hospital['hospital_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Visits - <?php echo htmlspecialchars($hospital['hospital_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.light { background: #f1f5f9; }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            padding: 1rem 0.5rem;
            overflow-y: auto;
            z-index: 1000;
            transition: width 0.3s ease;
        }
        body.dark .sidebar { background: #1a1a1a; border-right: 1px solid #2a2a2a; }
        body.light .sidebar { background: #ffffff; border-right: 1px solid #e2e8f0; }
        .sidebar.closed { width: 70px; }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.8rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 2px 0;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        .sidebar-item i { width: 1.25rem; text-align: center; }
        .sidebar-item:hover { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .sidebar-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .sidebar.closed .sidebar-item span { display: none; }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            padding: 0 0.5rem 1rem 0.5rem;
        }
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
        
        .appointment-badge {
            padding: 0.2rem 0.7rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .appointment-new { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .appointment-followup { background: rgba(234, 179, 8, 0.15); color: #eab308; }
        .appointment-emergency { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .appointment-consultation { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 0.75rem 1rem; 
            font-size: 0.7rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            color: #94a3b8; 
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; 
        }
        td { 
            padding: 0.75rem 1rem; 
            vertical-align: middle; 
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; 
        }
        .table-row:hover { 
            background: <?php echo $theme == 'dark' ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)'; ?>; 
        }
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .sidebar.closed { width: 60px; }
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
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
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<?php include 'sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <?php include 'header.php'; ?>

    <div class="action-row">
        <a href="view_hospital.php?id=<?php echo $hospital_id; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Hospital Details
        </a>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <span class="stat-badge">
                <i class="fas fa-hospital"></i> 
                <?php echo htmlspecialchars($hospital['hospital_name']); ?>
            </span>
            
        </div>
    </div>

    <div class="content-card">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
            <div style="width:45px;height:45px;border-radius:12px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;color:#3b82f6;font-size:1.2rem;">
                <i class="fas fa-clinic-medical"></i>
            </div>
            <div>
                <h3 style="font-size:1.2rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin:0;">
                    OPD Visits
                </h3>
                <p style="color:#94a3b8;font-size:0.85rem;margin:0;">
                    <?php echo mysqli_num_rows($result); ?> OPD visit(s) found
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Appointment No</th>
                        <th>Patient ID</th>
                        <th>Doctor ID</th>
                        <th>Department</th>
                        <th>Appointment Type</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="table-row">
                                <td style="color:#94a3b8;font-weight:500;">
                                    <?php echo $row['id']; ?>
                                </td>
                                <td style="font-weight:500;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                    <?php echo htmlspecialchars($row['appointment_no']); ?>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                                    <?php echo htmlspecialchars($row['patient_id']); ?>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                                    <?php echo htmlspecialchars($row['doctor_id']); ?>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                                    <?php echo htmlspecialchars($row['department']); ?>
                                </td>
                                <td>
                                    <span class="appointment-badge <?php 
                                        $type = strtolower($row['appointment_type']);
                                        if ($type == 'new') echo 'appointment-new';
                                        elseif ($type == 'followup' || $type == 'follow-up') echo 'appointment-followup';
                                        elseif ($type == 'emergency') echo 'appointment-emergency';
                                        else echo 'appointment-consultation';
                                    ?>">
                                        <?php echo htmlspecialchars($row['appointment_type']); ?>
                                    </span>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;">
                                    <?php echo date('d-m-Y', strtotime($row['appointment_date'])); ?>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;font-weight:500;">
                                    <i class="fas fa-clock" style="color:#94a3b8;margin-right:0.3rem;"></i>
                                    <?php echo date('h:i A', strtotime($row['appointment_time'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;">
                                <i class="fas fa-clinic-medical" style="font-size:3rem;display:block;margin-bottom:1rem;color:#2a2a2a;"></i>
                                No OPD visits found
                                <br>
                                <span style="font-size:0.85rem;">Click "Add OPD Visit" to add the first visit</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    sidebar.classList.toggle('closed');
    mainContent.classList.toggle('collapsed');
}

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

document.addEventListener('DOMContentLoaded', function() {
    
});
</script>
</body>
</html>