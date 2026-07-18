<?php
include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// Check Super Admin login
checkSuperAdminLogin();

$page_title = 'Hospitals';
$page_subtitle = 'Manage all hospitals in the system';

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build WHERE clause
$where = "h.delete_flag = 0";
if ($search) {
    $where .= " AND (h.hospital_name LIKE '%$search%' OR h.hospital_code LIKE '%$search%' OR h.city LIKE '%$search%' OR h.email LIKE '%$search%')";
}
if ($status_filter) {
    $where .= " AND h.status = '$status_filter'";
}

// Corrected Query
$query = "SELECT h.*, ha.email as admin_email 
          FROM hospital_master h 
          LEFT JOIN hospital_admin ha ON h.hospital_id = ha.hospital_id AND ha.delete_flag = 0
          WHERE $where 
          ORDER BY h.hospital_id DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitals - Super Admin</title>
     <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.light { background: #f1f5f9; }
        
        /* ============================================
           SIDEBAR STYLES
           ============================================ */
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
        
        .form-control { 
            padding: 0.6rem 1rem; 
            border-radius: 10px; 
            transition: all 0.3s ease; 
            width: 100%; 
            outline: none; 
            font-size: 0.9rem; 
        }
        body.dark .form-control { 
            background: #1e1e1e; 
            border: 1px solid #2a2a2a; 
            color: #f1f5f9; 
        }
        body.light .form-control { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            color: #1e293b; 
        }
        .form-control:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); 
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
            background: <?php echo $theme == 'dark' ? '#2a2a2a' :  '#3b82f6' ?>;
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
        
        .action-btn { 
            padding: 0.4rem; 
            border-radius: 8px; 
            border: none; 
            background: transparent; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            color: #94a3b8; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .action-btn:hover { 
            background: <?php echo $theme == 'dark' ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'; ?>; 
        }
        .action-btn.blue:hover { color: #3b82f6; }
        .action-btn.green:hover { color: #22c55e; }
        .action-btn.yellow:hover { color: #eab308; }
        .action-btn.red:hover { color: #ef4444; }
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        
        /* Action Row - Back + Add in one line */
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
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

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Action Row - Back + Add Hospital in one line -->
    <div class="action-row">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="add_hospital.php" class="btn-primary">
            <i class="fas fa-plus"></i> Add New Hospital
        </a>
    </div>

    <!-- Filters -->
    <div class="content-card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
            <div style="flex:1;min-width:200px;">
                <input type="text" name="search" placeholder="Search hospitals by name, code, city..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <div style="width:150px;">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="padding:0.6rem 1.2rem;">
                <i class="fas fa-search"></i>Search
            </button>
            <a href="hospitals.php" class="btn-secondary">
                <i class="fas fa-undo"></i>Reset
            </a>
        </form>
    </div>

    <!-- Hospital List -->
    <div class="content-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Code</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                         <tr class="table-row"
    onclick="window.location.href='view_hospital.php?id=<?php echo $row['hospital_id']; ?>';"
    style="cursor:pointer;">
                                <td>
                                    <div style="display:flex;align-items:center;gap:0.75rem;">
                                        <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;color:#3b82f6;">
                                            <i class="fas fa-hospital"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                                                <?php echo htmlspecialchars($row['hospital_name']); ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:#94a3b8;">
                                                <?php echo htmlspecialchars($row['admin_email'] ?? 'No Admin'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-size:0.85rem;color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;">
                                        <?php echo htmlspecialchars($row['hospital_code']); ?>
                                    </span>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;">
                                    <?php echo htmlspecialchars($row['city'] . ', ' . $row['state']); ?>
                                </td>
                                <td style="color:<?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;font-size:0.85rem;">
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $row['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                              <td style="text-align:right;" onclick="event.stopPropagation();">
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.25rem;">

        <a href="edit_hospital.php?id=<?php echo $row['hospital_id']; ?>" class="action-btn green">
            <i class="fas fa-edit"></i>
        </a>

        <a href="delete_hospital.php?id=<?php echo $row['hospital_id']; ?>"
           class="action-btn red"
           onclick="return confirm('Delete this hospital and all associated data? This action cannot be undone!')">
            <i class="fas fa-trash"></i>
        </a>

    </div>
</td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding:3rem;text-align:center;color:#94a3b8;">
                                <i class="fas fa-hospital" style="font-size:3rem;display:block;margin-bottom:1rem;color:#2a2a2a;"></i>
                                No hospitals found
                                <?php if ($search || $status_filter): ?>
                                    <br><span style="font-size:0.85rem;">Try adjusting your search filters</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ============================================================
// SIDEBAR TOGGLE
// ============================================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    sidebar.classList.toggle('closed');
    mainContent.classList.toggle('collapsed');
}

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