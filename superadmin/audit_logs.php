<?php
// ============================================================
// AUDIT LOGS - SUPER ADMIN
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config files
include_once '../config/hospital.php';
include_once '../config/permission.php';

// Check Super Admin login
checkSuperAdminLogin();

// Get filter parameters
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';
$filter_module = isset($_GET['module']) ? $_GET['module'] : '';
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build where conditions
$where_conditions = [];
if (!empty($filter_user)) {
    $filter_user = mysqli_real_escape_string($conn, $filter_user);
    $where_conditions[] = "r.name LIKE '%$filter_user%'";
}
if (!empty($filter_module)) {
    $filter_module = mysqli_real_escape_string($conn, $filter_module);
    $where_conditions[] = "a.module LIKE '%$filter_module%'";
}
if (!empty($filter_action)) {
    $filter_action = mysqli_real_escape_string($conn, $filter_action);
    $where_conditions[] = "a.action LIKE '%$filter_action%'";
}
if (!empty($filter_date)) {
    $filter_date = mysqli_real_escape_string($conn, $filter_date);
    $where_conditions[] = "DATE(a.created_at) = '$filter_date'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM audit_logs a
                LEFT JOIN register r ON a.register_id = r.id
                $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_records = 0;
if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $total_records = $row['total'] ?? 0;
}
$total_pages = ceil($total_records / $per_page);

// Get audit logs with proper hospital name
$query = "SELECT 
            a.log_id,
            a.hospital_id,
            a.register_id,
            a.module,
            a.action,
            a.created_at,
            CASE 
                WHEN a.register_id = 999 THEN 'Super Admin'
                WHEN r.name IS NULL THEN CONCAT('User #', a.register_id)
                ELSE r.name 
            END as user_name,
            COALESCE(h.hospital_name, 'N/A') as hospital_name,
            CASE 
                WHEN a.hospital_id IS NULL OR a.hospital_id = 0 THEN 'System'
                WHEN h.hospital_name IS NULL THEN CONCAT('Hospital #', a.hospital_id)
                ELSE h.hospital_name 
            END as hospital_display
          FROM audit_logs a
          LEFT JOIN register r ON a.register_id = r.id
          LEFT JOIN hospital_master h ON a.hospital_id = h.hospital_id
          $where_clause
          ORDER BY a.created_at DESC
          LIMIT $offset, $per_page";

$result = mysqli_query($conn, $query);
$audit_logs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $audit_logs[] = $row;
    }
}

// Get modules for filter
$modules_query = "SELECT DISTINCT module FROM audit_logs ORDER BY module";
$modules_result = mysqli_query($conn, $modules_query);

// Get user name
$user_name = $_SESSION['name'] ?? 'Super Admin';
$hospital_name = isset($hospital['hospital_name']) ? $hospital['hospital_name'] : 'UltraHospital';

// Log this page access
if (function_exists('logAudit')) {
    logAudit('Audit Logs', 'User viewed audit logs');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - <?php echo htmlspecialchars($hospital_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }

       

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            width: calc(100% - 250px);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 1279px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                width: 100%;
            }
        }

        /* Top Header */
        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            min-height: 64px;
        }

        .top-header .header-left { display: flex; align-items: center; gap: 1rem; }
        .top-header .header-right { display: flex; align-items: center; gap: 1rem; }
        .top-header .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .mobile-toggle {
            display: none;
            padding: 0.5rem 0.75rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.25rem;
        }
        .mobile-toggle:hover { background: #f8fafc; }
        @media (max-width: 1279px) {
            .mobile-toggle { display: inline-flex; align-items: center; justify-content: center; }
        }

        /* Card */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1rem; font-weight: 600; color: #1e293b;
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }
        .card-title i { color: #3b82f6; }

        /* Filter */
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .filter-field label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-field input,
        .filter-field select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
        }
        .filter-field input:focus,
        .filter-field select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }

        .filter-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #475569;
        }
        tbody tr:hover {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-blue { background: #dbeafe; color: #3b82f6; }
        .badge-green { background: #dcfce7; color: #059669; }
        .badge-yellow { background: #fef3c7; color: #b45309; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }
        .badge-purple { background: #f5f3ff; color: #7c3aed; }

        .user-badge {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
        }
        .user-badge.superadmin { background: #fef3c7; color: #b45309; }

        .hospital-badge {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
        }
        .hospital-badge.system { background: #fef3c7; color: #b45309; }
        .hospital-badge.unknown { background: #fee2e2; color: #dc2626; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #475569;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        .pagination a:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .pagination .active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
        }
        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            display: block;
            margin-bottom: 1rem;
        }
        .empty-state h3 {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .empty-state p {
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .filter-row { grid-template-columns: 1fr; }
            table { font-size: 0.8rem; }
            th, td { padding: 0.75rem; }
        }
    </style>
</head>
<body>


    <?php include 'sidebar.php'; ?>


<!-- ============================================================
MAIN WRAPPER
============================================================ -->
<div style="display:flex; min-height:100vh;">
    <main class="main-content" id="mainContent">
        
        <!-- ============================================================
        TOP HEADER
        ============================================================ -->
        <div class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1 style="font-size:1.25rem; font-weight:700; color:#1e293b;">Audit Logs</h1>
                    <p style="font-size:0.875rem; color:#64748b;">Track all user activities and system changes</p>
                </div>
            </div>
            <div class="header-right">
                <span style="font-size:0.875rem; color:#64748b;"><?php echo date('M d, Y'); ?></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                </div>
            </div>
        </div>

        <!-- ============================================================
        BACK BUTTON
        ============================================================ -->
        <a href="dashboard.php" class="btn btn-primary" style="margin-bottom:1.5rem;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- ============================================================
        FILTERS
        ============================================================ -->
        <div class="card">
            <form method="GET" action="audit_logs.php">
                <div class="filter-row">
                    <div class="filter-field">
                        <label>User Name</label>
                        <input type="text" name="user" placeholder="Search user..." value="<?php echo htmlspecialchars($filter_user); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Module</label>
                        <select name="module">
                            <option value="">All Modules</option>
                            <?php if ($modules_result && mysqli_num_rows($modules_result) > 0): ?>
                                <?php while($module_row = mysqli_fetch_assoc($modules_result)): ?>
                                    <option value="<?php echo htmlspecialchars($module_row['module']); ?>" <?php echo $filter_module == $module_row['module'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($module_row['module']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Action</label>
                        <input type="text" name="action" placeholder="Search action..." value="<?php echo htmlspecialchars($filter_action); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="audit_logs.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- ============================================================
        AUDIT LOGS TABLE
        ============================================================ -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-list"></i> Audit Logs
                <span style="font-size:0.8rem; font-weight:400; color:#94a3b8; margin-left:0.5rem;">
                    (Total: <?php echo $total_records; ?> records)
                </span>
            </div>
            
            <?php if (!empty($audit_logs)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Hospital</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($log['register_id'] == 999) {
                                            echo '<span class="user-badge superadmin">👑 ' . htmlspecialchars($log['user_name']) . '</span>';
                                        } elseif (!empty($log['user_name'])) {
                                            echo '<span class="user-badge">👤 ' . htmlspecialchars($log['user_name']) . '</span>';
                                        } else {
                                            echo '<span class="user-badge">❓ User #' . $log['register_id'] . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // ============================================================
                                        // FIX: Hospital Name Display - Dynamic from hospital_master table
                                        // ============================================================
                                        if (isset($log['hospital_id']) && !empty($log['hospital_id']) && $log['hospital_id'] > 0) {
                                            // Check if we have hospital_name from query
                                            if (!empty($log['hospital_name']) && $log['hospital_name'] != 'N/A') {
                                                echo '<span class="hospital-badge">🏥 ' . htmlspecialchars($log['hospital_name']) . '</span>';
                                            } else {
                                                // Try to fetch hospital name from database
                                                $hid = $log['hospital_id'];
                                                $hosp_q = "SELECT hospital_name FROM hospital_master WHERE hospital_id = '$hid' AND delete_flag = 0";
                                                $hosp_r = mysqli_query($conn, $hosp_q);
                                                if ($hosp_r && mysqli_num_rows($hosp_r) > 0) {
                                                    $hosp_row = mysqli_fetch_assoc($hosp_r);
                                                    echo '<span class="hospital-badge">🏥 ' . htmlspecialchars($hosp_row['hospital_name']) . '</span>';
                                                } else {
                                                    echo '<span class="hospital-badge unknown">🏥 Hospital #' . $log['hospital_id'] . '</span>';
                                                }
                                            }
                                        } else {
                                            echo '<span class="hospital-badge system">🏢 System</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-blue"><?php echo htmlspecialchars($log['module']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $action = $log['action'];
                                        $badge_class = 'badge-gray';
                                        if (strpos($action, 'Login') !== false || strpos($action, 'login') !== false) {
                                            $badge_class = 'badge-blue';
                                        } elseif (strpos($action, 'Create') !== false || strpos($action, 'create') !== false || strpos($action, 'Add') !== false) {
                                            $badge_class = 'badge-green';
                                        } elseif (strpos($action, 'Update') !== false || strpos($action, 'update') !== false || strpos($action, 'Edit') !== false) {
                                            $badge_class = 'badge-yellow';
                                        } elseif (strpos($action, 'Delete') !== false || strpos($action, 'delete') !== false || strpos($action, 'Remove') !== false) {
                                            $badge_class = 'badge-red';
                                        } elseif (strpos($action, 'Permission') !== false || strpos($action, 'permission') !== false) {
                                            $badge_class = 'badge-purple';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars(substr($action, 0, 60) . (strlen($action) > 60 ? '...' : '')); ?>
                                        </span>
                                    </td>
                                    <td style="white-space:nowrap; font-size:0.8rem; color:#94a3b8;">
                                        <?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&user=<?php echo urlencode($filter_user); ?>&module=<?php echo urlencode($filter_module); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">
                                <i class="fas fa-angle-double-left"></i> First
                            </a>
                            <a href="?page=<?php echo $page - 1; ?>&user=<?php echo urlencode($filter_user); ?>&module=<?php echo urlencode($filter_module); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">
                                <i class="fas fa-angle-left"></i> Prev
                            </a>
                        <?php endif; ?>

                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&user=<?php echo urlencode($filter_user); ?>&module=<?php echo urlencode($filter_module); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&user=<?php echo urlencode($filter_user); ?>&module=<?php echo urlencode($filter_module); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">
                                Next <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $total_pages; ?>&user=<?php echo urlencode($filter_user); ?>&module=<?php echo urlencode($filter_module); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">
                                Last <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Audit Logs Found</h3>
                    <p>No activities match your filter criteria.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>



</body>
</html>