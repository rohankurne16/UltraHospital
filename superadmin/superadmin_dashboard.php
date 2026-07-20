<?php
// Include superadmin config
include '../config/permission.php';

// Check if user is Super Admin
checkSuperAdminLogin();

// Get current theme from session or set default
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}

$theme = $_SESSION['theme'];

// Get all statistics
$total_hospitals = getCount('hospital_master');
$active_hospitals = getCount('hospital_master', null, "status = 'Active'");
$inactive_hospitals = getCount('hospital_master', null, "status = 'Inactive'");
$total_admins = getCount('hospital_admin');
$total_doctors = getCount('doctor');
$total_patients = getCount('staff');
$total_appointments = getCount('appointments');
$total_ipd = getCount('ipd_admissions');
$total_opd = getCount('opd');

// Get revenue (from billing)
$revenue_query = "SELECT SUM(total) as total_revenue FROM billing WHERE delete_flag = 0";
$revenue_result = mysqli_query($conn, $revenue_query);
$revenue_row = mysqli_fetch_assoc($revenue_result);
$total_revenue = $revenue_row['total_revenue'] ?? 0;

// Get hospital wise patients
$hospital_patients_query = "SELECT h.hospital_id, h.hospital_name, h.status, COUNT(p.patient_id) as patient_count 
                            FROM hospital_master h 
                            LEFT JOIN patients p ON h.hospital_id = p.hospital_id AND p.delete_flag = 0 
                            WHERE h.delete_flag = 0 
                            GROUP BY h.hospital_id";
$hospital_patients_result = mysqli_query($conn, $hospital_patients_query);

// Get monthly revenue (last 6 months)
$revenue_monthly_query = "SELECT DATE_FORMAT(bill_date, '%b') as month, SUM(total) as revenue 
                          FROM billing 
                          WHERE delete_flag = 0 AND bill_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                          GROUP BY MONTH(bill_date)";
$revenue_monthly_result = mysqli_query($conn, $revenue_monthly_query);

// Get recent audit logs
$audit_query = "SELECT a.*, r.name FROM audit_logs a 
               LEFT JOIN register r ON a.register_id = r.id 
               ORDER BY a.created_at DESC LIMIT 10";
$audit_result = mysqli_query($conn, $audit_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - MedixPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        /* Dark Theme */
        body.dark { background: #0a0a0a; }
        body.dark .sidebar { background: linear-gradient(180deg, #1a1a1a, #121212); border-right: 1px solid #2a2a2a; }
        body.dark .sidebar-item { color: #d1d5db; }
        body.dark .sidebar-item:hover, body.dark .sidebar-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        body.dark .stat-card { background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a; }
        body.dark .stat-card h3 { color: #f1f5f9; }
        body.dark .stat-card p { color: #d1d5db; }
        body.dark .content-card { background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a; }
        body.dark .table-text { color: #f1f5f9; }
        body.dark .table-header { color: #9ca3af; }
        body.dark .border-divider { border-color: #2a2a2a; }
        body.dark .text-primary { color: #f1f5f9; }
        body.dark .text-secondary { color: #9ca3af; }
        body.dark .bg-card { background: #1a1a1a; }
        body.dark .hover-bg:hover { background: rgba(255,255,255,0.05); }

        /* Light Theme */
        body.light { background: #f1f5f9; }
        body.light .sidebar { background: linear-gradient(180deg, #ffffff, #f8fafc); border-right: 1px solid #e2e8f0; }
        body.light .sidebar-item { color: #475569; }
        body.light .sidebar-item:hover, body.light .sidebar-item.active { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        body.light .stat-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        body.light .stat-card h3 { color: #1e293b; }
        body.light .stat-card p { color: #475569; }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; }
        body.light .table-text { color: #1e293b; }
        body.light .table-header { color: #64748b; }
        body.light .border-divider { border-color: #e2e8f0; }
        body.light .text-primary { color: #1e293b; }
        body.light .text-secondary { color: #64748b; }
        body.light .bg-card { background: #ffffff; }
        body.light .hover-bg:hover { background: rgba(0,0,0,0.03); }

        /* ============================================
           SIDEBAR STYLES - FIXED WIDTH (NO HOVER)
           ============================================ */
        .sidebar { 
            position: fixed; 
            top: 0; 
            left: 0; 
            height: 100vh; 
            width: 250px; 
            padding: 1rem 0.5rem; 
            overflow-y: auto; 
            overflow-x: hidden;
            z-index: 1000; 
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        .sidebar.closed {
            width: 70px;
        }
        
        /* ============================================
           SIDEBAR ITEMS
           ============================================ */
        .sidebar-item { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.7rem 0.8rem; 
            border-radius: 0.75rem; 
            transition: background 0.2s ease, color 0.2s ease; 
            white-space: nowrap; 
            overflow: hidden; 
            text-decoration: none; 
            font-size: 0.85rem; 
            margin: 2px 0;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        
        .sidebar-item i { 
            width: 1.25rem; 
            min-width: 1.25rem;
            text-align: center; 
            flex-shrink: 0; 
        }
        
        .sidebar-item span { 
            opacity: 1; 
            transition: opacity 0.2s ease; 
        }
        
        .sidebar.closed .sidebar-item span {
            opacity: 0;
            width: 0;
        }
        
        .sidebar-item:hover { 
            background: <?php echo $theme == 'dark' ? 'rgba(59, 130, 246, 0.15)' : 'rgba(59, 130, 246, 0.08)'; ?>;
            color: #3b82f6; 
        }
        
        .sidebar-item.active { 
            background: <?php echo $theme == 'dark' ? 'rgba(59, 130, 246, 0.15)' : 'rgba(59, 130, 246, 0.08)'; ?>;
            color: #3b82f6; 
        }
        
        .sidebar-label { 
            font-size: 0.6rem; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            padding: 0.5rem 0.8rem 0.3rem; 
            color: #64748b; 
            font-weight: 600; 
            opacity: 1; 
            transition: opacity 0.2s ease; 
        }
        
        .sidebar.closed .sidebar-label {
            opacity: 0;
            width: 0;
            padding: 0;
        }
        
        /* ============================================
           MAIN CONTENT - FIXED WIDTH
           ============================================ */
        .main-content { 
            margin-left: 250px; 
            padding: 1.5rem; 
            min-height: 100vh; 
            width: calc(100% - 250px);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                        width 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        .main-content.expanded {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
        
        /* ============================================
           STAT CARDS
           ============================================ */
        .stat-card { 
            border-radius: 16px; 
            padding: 1.5rem; 
            transition: all 0.3s ease; 
        }
        .stat-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.2); 
        }
        .content-card { 
            border-radius: 16px; 
            padding: 1.5rem; 
            transition: all 0.3s ease; 
        }
        
        .theme-toggle { 
            cursor: pointer; 
            transition: all 0.3s ease; 
            padding: 8px 12px; 
            border-radius: 10px; 
            border: 1px solid transparent; 
        }
        .theme-toggle:hover { 
            transform: scale(1.05); 
        }
        
        .status-badge { 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 4px; 
        }
        .status-active { 
            background: rgba(34, 197, 94, 0.15); 
            color: #22c55e; 
        }
        .status-inactive { 
            background: rgba(239, 68, 68, 0.15); 
            color: #ef4444; 
        }
        
        .toggle-switch { 
            position: relative; 
            width: 44px; 
            height: 24px; 
            display: inline-block; 
            flex-shrink: 0; 
        }
        .toggle-switch input { 
            opacity: 0; 
            width: 0; 
            height: 0; 
        }
        .toggle-slider { 
            position: absolute; 
            cursor: pointer; 
            top: 0; 
            left: 0; 
            right: 0; 
            bottom: 0; 
            background: #4b5563; 
            transition: 0.3s; 
            border-radius: 24px; 
        }
        .toggle-slider:before { 
            content: ""; 
            position: absolute; 
            height: 18px; 
            width: 18px; 
            left: 3px; 
            bottom: 3px; 
            background: white; 
            transition: 0.3s; 
            border-radius: 50%; 
        }
        .toggle-switch input:checked + .toggle-slider { 
            background: #3b82f6; 
        }
        .toggle-switch input:checked + .toggle-slider:before { 
            transform: translateX(20px); 
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .sidebar { 
                width: 200px; 
            }
            .sidebar.closed { 
                width: 60px; 
            }
            .main-content { 
                margin-left: 200px; 
                width: calc(100% - 200px);
                padding: 1rem; 
            }
            .main-content.expanded {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <?php include 'header.php'; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6" style="
    width: 134%;">
        <div class="stat-card" style="<?php echo $theme == 'dark' ? 'background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a;' : 'background: #ffffff; border: 1px solid #e2e8f0;'; ?>">
            <div class="flex items-center justify-between mb-2">
                <p class="text-secondary text-sm font-medium">Total Hospitals</p>
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                    <i class="fas fa-hospital text-xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-primary"><?php echo $total_hospitals; ?></p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs" style="color: #22c55e;"><i class="fas fa-circle text-[6px] mr-1"></i><?php echo $active_hospitals; ?> Active</span>
                <span class="text-xs" style="color: #ef4444;"><i class="fas fa-circle text-[6px] mr-1"></i><?php echo $inactive_hospitals; ?> Inactive</span>
            </div>
        </div>
        
    
        
        <div class="stat-card" style="<?php echo $theme == 'dark' ? 'background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a;' : 'background: #ffffff; border: 1px solid #e2e8f0;'; ?>">
            <div class="flex items-center justify-between mb-2">
                <p class="text-secondary text-sm font-medium">Total Doctors</p>
                <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500">
                    <i class="fas fa-user-md text-xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-primary"><?php echo $total_doctors; ?></p>
        </div>
        
        <div class="stat-card" style="<?php echo $theme == 'dark' ? 'background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a;' : 'background: #ffffff; border: 1px solid #e2e8f0;'; ?>">
            <div class="flex items-center justify-between mb-2">
                <p class="text-secondary text-sm font-medium">Total Staff</p>
                <div class="w-12 h-12 rounded-xl bg-yellow-500/10 flex items-center justify-center text-yellow-500">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-primary"><?php echo $total_patients; ?></p>
        </div>
    </div>

    <!-- Charts & Hospital Status Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Hospital Wise Patients -->
        <div class="stat-card" style="<?php echo $theme == 'dark' ? 'background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a;' : 'background: #ffffff; border: 1px solid #e2e8f0;'; ?>">
            <h3 class="text-primary font-semibold mb-4">Hospital Status Control</h3>
            <div class="space-y-3">
                <?php 
                $max_patients = getCount('patients');
                while($row = mysqli_fetch_assoc($hospital_patients_result)): 
                    $percent = $max_patients > 0 ? ($row['patient_count'] / $max_patients) * 100 : 0;
                ?>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-primary"><?php echo $row['hospital_name']; ?></span>
                              
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 rounded-full h-2 transition-all" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>
                        <!-- Hospital Status Toggle -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="status-badge <?php echo $row['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       class="hospital-status-toggle" 
                                       data-hospital="<?php echo $row['hospital_id']; ?>"
                                       data-hospital-name="<?php echo htmlspecialchars($row['hospital_name']); ?>"
                                       <?php echo $row['status'] == 'Active' ? 'checked' : ''; ?>
                                       onchange="toggleHospitalStatus(this, <?php echo $row['hospital_id']; ?>, '<?php echo htmlspecialchars($row['hospital_name']); ?>')">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Monthly Revenue -->
        
    

    <!-- Recent Activity -->
    <div class="stat-card" style="<?php echo $theme == 'dark' ? 'background: linear-gradient(145deg, #1a1a1a, #121212); border: 1px solid #2a2a2a;' : 'background: #ffffff; border: 1px solid #e2e8f0;'; ?>">
        <h3 class="text-primary font-semibold mb-4">Recent Audit Logs</h3>
        <?php if (mysqli_num_rows($audit_result) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-secondary text-xs uppercase border-divider" style="border-bottom-width: 1px;">
                        <th class="pb-2">User</th>
                        <th class="pb-2">Module</th>
                        <th class="pb-2">Action</th>
                        <th class="pb-2">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($audit_result)): ?>
                        <tr class="border-divider hover-bg" style="border-bottom-width: 1px;">
                            <td class="py-2 text-primary text-sm"><?php echo $row['name'] ?? 'System'; ?></td>
                            <td class="py-2 text-primary text-sm"><?php echo $row['module']; ?></td>
                            <td class="py-2 text-secondary text-sm"><?php echo substr($row['action'], 0, 50); ?>...</td>
                            <td class="py-2 text-secondary text-sm"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-secondary text-center py-4">No audit logs found</p>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
    // Theme Toggle Function
    function toggleTheme() {

        const body = document.body;
        const currentTheme = body.classList.contains('light') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        body.classList.remove(currentTheme);
        body.classList.add(newTheme);

        const themeBtn = document.querySelector('.theme-toggle i');
        const themeText = document.querySelector('.theme-toggle span');

        if (newTheme === 'dark') {
            themeBtn.className = 'fas fa-sun';
            themeText.textContent = 'Light';
        } else {
            themeBtn.className = 'fas fa-moon';
            themeText.textContent = 'Dark';
        }

        // Page Reload (AJAX नाही)
        window.location.href = "toggle_theme.php?theme=" + newTheme;
    }

    // Hospital Status Toggle
    function toggleHospitalStatus(element, hospitalId) {

        var status = element.checked ? "Active" : "Inactive";

        if (confirm("Are you sure you want to " + status + " this hospital?")) {

            // AJAX नाही
            window.location.href =
                "toggle_hospital_status.php?hospital_id=" +
                hospitalId +
                "&status=" +
                status;

        } else {

            // Toggle पूर्वीच्या स्थितीत आणा
            element.checked = !element.checked;
        }
    }

    document.addEventListener("DOMContentLoaded", function () {

    });
</script>
</body>
</html>