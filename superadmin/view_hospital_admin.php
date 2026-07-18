<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/superadmin.php';
checkSuperAdminLogin();

$page_title = 'View Hospital Admin';
$page_subtitle = 'View hospital administrator details';

$theme = $_SESSION['theme'] ?? 'dark';

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

if ($admin_id == 0) {
    header('Location: hospital_admins.php');
    exit;
}

// Fetch admin details
$admin_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

$query = "SELECT ha.*, h.hospital_name, h.hospital_code, h.hospital_logo,
          h.address as hospital_address,
          h.city, h.state, h.country, h.pincode,
          h.phone as hospital_phone, h.email as hospital_email,
          r.role, r.reg_date, r.created_by as register_created_at
          FROM hospital_admin ha
          LEFT JOIN hospital_master h ON ha.hospital_id = h.hospital_id
          LEFT JOIN register r ON ha.register_id = r.id
          WHERE ha.admin_id='$admin_id'
          AND ha.delete_flag=0";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL Error : " . mysqli_error($conn));
}

echo "Rows Found = " . mysqli_num_rows($result);

$admin = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Hospital Admin - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.dark .content-card { background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a; }
        body.dark .text-primary { color: #f1f5f9; }
        body.dark .text-secondary { color: #9ca3af; }
        body.dark .profile-field { border-bottom-color: #2a2a2a; }
        body.dark .profile-label { color: #94a3b8; }
        body.dark .profile-value { color: #f1f5f9; }
        body.dark .stat-card { background: #1a1a1a; border: 1px solid #2a2a2a; }
        body.dark .stat-card .stat-value { color: #f1f5f9; }
        body.dark .stat-card .stat-label { color: #94a3b8; }
        
        body.light { background: #f1f5f9; }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        body.light .text-primary { color: #1e293b; }
        body.light .text-secondary { color: #64748b; }
        body.light .profile-field { border-bottom-color: #e2e8f0; }
        body.light .profile-label { color: #64748b; }
        body.light .profile-value { color: #1e293b; }
        body.light .stat-card { background: #ffffff; border: 1px solid #e2e8f0; }
        body.light .stat-card .stat-value { color: #1e293b; }
        body.light .stat-card .stat-label { color: #64748b; }
        
        .content-card { border-radius: 16px; padding: 1.5rem; transition: all 0.3s ease; margin-bottom: 1.5rem; }
        
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        .btn-secondary { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; padding: 0.6rem 1.5rem; border-radius: 10px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary:hover { background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(239, 68, 68, 0.5); }
        
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
        
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            background: rgba(168, 85, 247, 0.15);
            color: #a855f7;
            border: 3px solid #a855f7;
        }
        
        .profile-field {
            padding: 0.75rem 0;
            border-bottom: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .profile-field:last-child { border-bottom: none; }
        .profile-label { font-size: 0.85rem; font-weight: 500; }
        .profile-value { font-size: 0.95rem; font-weight: 600; }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .stat-card {
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }
        .stat-card .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #3b82f6;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-active { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .status-inactive { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .info-grid { grid-template-columns: 1fr; }
            .profile-field { flex-direction: column; align-items: flex-start; gap: 0.25rem; }
            .action-row { flex-direction: column; align-items: stretch; }
            .action-row a { justify-content: center; }
        }
        @media (max-width: 480px) {
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<?php include 'sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <?php include 'header.php'; ?>

    
    <div class="action-row">
        <a href="hospital_admins.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Admins
        </a>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a href="edit_hospital_admin.php?id=<?php echo $admin['admin_id']; ?>" class="btn-primary">
                <i class="fas fa-edit"></i> Edit Admin
            </a>
            <button onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>, '<?php echo addslashes($admin['full_name']); ?>')" class="btn-danger">
                <i class="fas fa-trash"></i> Delete Admin
            </button>
        </div>
    </div>

    <div class="content-card">
        <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($admin['full_name'], 0, 2)); ?>
            </div>
            <div>
                <h2 style="font-size:1.5rem;font-weight:700;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin:0;">
                    <?php echo htmlspecialchars($admin['full_name']); ?>
                </h2>
                <p style="color:#94a3b8;font-size:0.9rem;margin:0;">
                    <i class="fas fa-user-tag mr-1"></i> <?php echo htmlspecialchars($admin['role'] ?? 'Hospital Admin'); ?>
                </p>
                <p style="color:#94a3b8;font-size:0.85rem;margin-top:0.25rem;">
                    <i class="fas fa-id-badge mr-1"></i> Admin ID: <?php echo $admin['admin_id']; ?>
                </p>
            </div>
        </div>
    </div>

  
    <div class="content-card">
        <h3 style="font-size:1rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin-bottom:1rem;">
            <i class="fas fa-chart-bar mr-2" style="color:#3b82f6;"></i>Quick Stats
        </h3>
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hospital"></i></div>
                <div class="stat-value"><?php echo htmlspecialchars($admin['hospital_name'] ?? 'N/A'); ?></div>
                <div class="stat-label">Hospital</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-code"></i></div>
                <div class="stat-value"><?php echo htmlspecialchars($admin['hospital_code'] ?? 'N/A'); ?></div>
                <div class="stat-label">Hospital Code</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-value"><?php echo date('d M Y', strtotime($admin['reg_date'] ?? date('Y-m-d'))); ?></div>
                <div class="stat-label">Registered On</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-tag"></i></div>
                <div class="stat-value"><?php echo htmlspecialchars($admin['role'] ?? 'Admin'); ?></div>
                <div class="stat-label">Role</div>
            </div>
        </div>
    </div>

  
    <div class="content-card">
        <h3 style="font-size:1rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin-bottom:1rem;">
            <i class="fas fa-user-circle mr-2" style="color:#3b82f6;"></i>Admin Information
        </h3>
        <div class="info-grid">
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-user mr-2"></i>Full Name</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['full_name']); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-envelope mr-2"></i>Email</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['email']); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-phone mr-2"></i>Mobile</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['mobile'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-user-tag mr-2"></i>Role</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['role'] ?? 'Hospital Admin'); ?></span>
            </div>
        </div>
    </div>

  
    <div class="content-card">
        <h3 style="font-size:1rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin-bottom:1rem;">
            <i class="fas fa-hospital mr-2" style="color:#3b82f6;"></i>Hospital Information
        </h3>
        <div class="info-grid">
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-hospital mr-2"></i>Hospital Name</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['hospital_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-code mr-2"></i>Hospital Code</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['hospital_code'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-envelope mr-2"></i>Hospital Email</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['hospital_email'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-phone mr-2"></i>Hospital Phone</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['hospital_phone'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field" style="grid-column: 1 / -1;">
                <span class="profile-label"><i class="fas fa-map-marker-alt mr-2"></i>Address</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['hospital_address'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-city mr-2"></i>City</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['city'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-flag mr-2"></i>State</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['state'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-globe mr-2"></i>Country</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['country'] ?? 'N/A'); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-mail-bulk mr-2"></i>Pincode</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['pincode'] ?? 'N/A'); ?></span>
            </div>
        </div>
    </div>


    <div class="content-card">
        <h3 style="font-size:1rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin-bottom:1rem;">
            <i class="fas fa-clock mr-2" style="color:#3b82f6;"></i>Registration Details
        </h3>
        <div class="info-grid">
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-calendar-plus mr-2"></i>Registration Date</span>
                <span class="profile-value"><?php echo date('d M Y, h:i A', strtotime($admin['reg_date'] ?? date('Y-m-d'))); ?></span>
            </div>
            <div class="profile-field">
                <span class="profile-label"><i class="fas fa-clock mr-2"></i>Last Modified</span>
                <span class="profile-value"><?php echo date('d M Y, h:i A', strtotime($admin['modified_at'] ?? date('Y-m-d'))); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
function deleteAdmin(adminId, adminName) {
    if (confirm('Are you sure you want to delete "' + adminName + '"?')) {
        window.location.href = 'hospital_admins.php?delete=1&id=' + adminId;
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    if (sidebar && mainContent) {
        sidebar.classList.toggle('closed');
        mainContent.classList.toggle('collapsed');
    }
}
</script>
</body>
</html>