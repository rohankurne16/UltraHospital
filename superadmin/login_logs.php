<?php
include_once '../config/hospital.php';
include_once '../config/superadmin.php';

// Check Super Admin login
checkSuperAdminLogin();

$page_title = 'Login Logs';
$page_subtitle = 'View all user login activities';

$theme = $_SESSION['theme'] ?? 'light';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

$where = "1=1";
if ($search) {
    $where .= " AND (r.name LIKE '%$search%' OR r.email LIKE '%$search%' OR l.ip_address LIKE '%$search%')";
}
if ($date_filter) {
    $where .= " AND DATE(l.login_time) = '$date_filter'";
}

$query = "SELECT l.*, r.name as user_name, r.email, h.hospital_name 
          FROM login_logs l 
          LEFT JOIN register r ON l.register_id = r.id 
          LEFT JOIN hospital_master h ON l.hospital_id = h.hospital_id 
          WHERE $where 
          ORDER BY l.login_time DESC 
          LIMIT 100";
$result = mysqli_query($conn, $query);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM login_logs";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_logs = $count_row['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Logs - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.light { background: #f1f5f9; }
        body.dark { background: #0a0a0a; }
        
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
        body.light .content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        body.dark .content-card {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
        }
        
        .form-control {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }
        body.light .form-control {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        body.dark .form-control {
            background: #1e1e1e;
            border: 1px solid #2a2a2a;
            color: #f1f5f9;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1 );
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
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        .text-muted { color: #94a3b8; font-size: 0.8rem; }
        
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
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            color: #2a2a2a;
        }
        
        .device-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .status-active {
            color: #22c55e;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 200px;
                padding: 1rem;
            }
            .main-content.collapsed {
                margin-left: 60px;
            }
        }
        @media (max-width: 480px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            .main-content.collapsed {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- Include Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Include Header -->
    <?php include 'header.php'; ?>
    <a href="dashboard.php" class="btn btn-primary" style="margin-bottom:2%;">
    <i class="fas fa-arrow-left"></i> Back
</a>
    <!-- Filters -->
    <div class="content-card" style=" margin-top: 3%;margin-bottom: 1.5rem;">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <div style="flex: 1; min-width: 180px;">
                <input type="text" name="search" placeholder="Search by user or IP..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <div style="width: 150px;">
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="form-control">
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-1"></i>Search
            </button>
            <a href="login_logs.php" class="btn-secondary">
                <i class="fas fa-undo"></i>Reset
            </a>
        </form>
    </div>

    <!-- Logs List -->
    <div class="content-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
            <span class="text-secondary text-sm">Total Logs: <?php echo $total_logs; ?></span>
            <span class="text-muted">
                <i class="fas fa-info-circle mr-1"></i> Showing last 100 records
            </span>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Hospital</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $duration = '';
                            if ($row['logout_time']) {
                                $login = strtotime($row['login_time']);
                                $logout = strtotime($row['logout_time']);
                                $diff = $logout - $login;
                                $hours = floor($diff / 3600);
                                $minutes = floor(($diff % 3600) / 60);
                                $duration = $hours . 'h ' . $minutes . 'm';
                            } else {
                                $duration = 'Active';
                            }
                        ?>
                            <tr class="table-row">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 600; font-size: 0.7rem;">
                                            <?php echo strtoupper(substr($row['user_name'] ?? 'S', 0, 2)); ?>
                                        </div>
                                        <span style="color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; font-weight:500;">
                                            <?php echo htmlspecialchars($row['user_name'] ?? 'System'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?>
                                </td>
                                <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($row['hospital_name'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <span style="font-family: monospace; color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.8rem; background: <?php echo $theme == 'dark' ? '#1e1e1e' : '#f1f5f9'; ?>; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                        <?php echo htmlspecialchars($row['ip_address'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="device-badge">
                                        <i class="fas fa-<?php 
                                            $device = $row['device'] ?? 'Desktop';
                                            echo $device == 'Mobile' ? 'mobile-alt' : ($device == 'Tablet' ? 'tablet-alt' : 'desktop'); 
                                        ?>"></i>
                                        <?php echo htmlspecialchars($row['device'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.8rem;">
                                    <?php echo date('d M Y h:i A', strtotime($row['login_time'])); ?>
                                </td>
                                <td style="color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>; font-size: 0.8rem;">
                                    <?php echo $row['logout_time'] ? date('d M Y h:i A', strtotime($row['logout_time'])) : '<span style="color:#94a3b8;">-</span>'; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: <?php echo $duration == 'Active' ? '#22c55e' : ($theme == 'dark' ? '#d1d5db' : '#475569'); ?>;">
                                        <?php echo $duration; ?>
                                        <?php if ($duration == 'Active'): ?>
                                            <span style="font-size:0.6rem;color:#22c55e;display:block;">(Currently Online)</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-sign-in-alt"></i>
                                No login logs found
                                <?php if ($search || $date_filter): ?>
                                      
<small>Try adjusting your search filters</small>
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
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    if (sidebar && mainContent) {
        sidebar.classList.toggle('closed');
        mainContent.classList.toggle('collapsed');
    }
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
    </script>
</body>
</html>