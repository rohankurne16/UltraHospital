<?php
// ============================================================
// DASHBOARD - WITH DYNAMIC SIDEBAR
// ============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/permission.php';


// Check login
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$user_id = $_SESSION['id'];
$role_id = $_SESSION['role_id'];
$role_name = $_SESSION['role'] ?? '';
$page_title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Hospital Management System</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f0f2f5;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 70px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            cursor: pointer;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .dashboard-card .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .dashboard-card .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .dashboard-card .card-label {
            font-size: 0.85rem;
            color: #94a3b8;
        }
        
        .color-blue { background: #dbeafe; color: #3b82f6; }
        .color-green { background: #dcfce7; color: #22c55e; }
        .color-purple { background: #f3e8ff; color: #8b5cf6; }
        .color-orange { background: #fef3c7; color: #f59e0b; }
        .color-red { background: #fee2e2; color: #dc2626; }
        .color-pink { background: #fce7f3; color: #ec4899; }
        .color-indigo { background: #e0e7ff; color: #6366f1; }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<!-- Include Dynamic Sidebar -->
<?php include 'Sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content <?php echo isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : ''; ?>" id="mainContent">
    
    <!-- Page Header -->
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-500 text-sm">
                <i class="far fa-calendar-alt mr-1"></i> 
                <?php echo date('l, F j, Y'); ?>
                <span class="mx-2">|</span>
                <i class="far fa-clock mr-1"></i> 
                <?php echo date('h:i A'); ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm flex items-center">
                <i class="fas fa-user-tag mr-1"></i> 
                <?php echo htmlspecialchars($role_name); ?>
            </span>
            <?php if (!empty($user_profile['hospital_name'])): ?>
            <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-sm flex items-center">
                <i class="fas fa-hospital mr-1"></i> 
                <?php echo htmlspecialchars($user_profile['hospital_name']); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dashboard Widgets -->
    <?php if (!empty($widgets)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php foreach ($widgets as $index => $widget): ?>
        <div class="dashboard-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="card-value">0</div>
                    <div class="card-label"><?php echo htmlspecialchars($widget['widget_title']); ?></div>
                </div>
                <div class="card-icon color-<?php echo $widget['color'] ?? 'blue'; ?>">
                    <i class="fas <?php echo $widget['widget_icon'] ?? 'fa-chart-bar'; ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="dashboard-card p-6">
        <h3 class="font-semibold text-gray-700 mb-4"><i class="fas fa-bolt text-blue-600 mr-2"></i> Quick Actions</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <?php if (hasPermission('patient-registration')): ?>
            <a href="patients.php?action=add" class="p-3 bg-blue-50 rounded-lg text-center hover:bg-blue-100 transition">
                <i class="fas fa-user-plus text-blue-600 text-xl block mb-1"></i>
                <span class="text-xs text-gray-600">Add Patient</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('appointment-create')): ?>
            <a href="appointments.php?action=add" class="p-3 bg-green-50 rounded-lg text-center hover:bg-green-100 transition">
                <i class="fas fa-calendar-plus text-green-600 text-xl block mb-1"></i>
                <span class="text-xs text-gray-600">New Appointment</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('opd-create')): ?>
            <a href="opd.php?action=add" class="p-3 bg-purple-50 rounded-lg text-center hover:bg-purple-100 transition">
                <i class="fas fa-stethoscope text-purple-600 text-xl block mb-1"></i>
                <span class="text-xs text-gray-600">New OPD</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('billing-create')): ?>
            <a href="billing.php?action=add" class="p-3 bg-orange-50 rounded-lg text-center hover:bg-orange-100 transition">
                <i class="fas fa-file-invoice-dollar text-orange-600 text-xl block mb-1"></i>
                <span class="text-xs text-gray-600">New Bill</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('prescription-create')): ?>
            <a href="prescriptions.php?action=add" class="p-3 bg-pink-50 rounded-lg text-center hover:bg-pink-100 transition">
                <i class="fas fa-prescription text-pink-600 text-xl block mb-1"></i>
                <span class="text-xs text-gray-600">New Prescription</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('lab-orders-create')): ?>
            <a href="lab_order.php?action=add" class="p-3 bg-indigo-50 rounded-lg text-center hover:bg-indigo-100 transition">
                <i class="fas fa-flask text-indigo-600 text-xl block mb-1"></i>
                <span class="text-xs text-gray-600">New Lab Order</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="dashboard-card p-8 text-center mt-6">
        <div class="text-4xl text-blue-600 mb-4">
            <i class="fas fa-hand-wave"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome to <?php echo htmlspecialchars($hospital_name); ?></h2>
        <p class="text-gray-500">You are logged in as <strong><?php echo htmlspecialchars($role_name); ?></strong></p>
        <p class="text-gray-400 text-sm mt-2">Select an option from the sidebar to get started.</p>
    </div>
</div>

<script>
// Update main content margin when sidebar toggles
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        const isCollapsed = sidebar.classList.contains('collapsed');
        if (isCollapsed) {
            mainContent.style.marginLeft = '70px';
        }
    }
});

// Set sidebar toggle listener
document.addEventListener('sidebarToggled', function(e) {
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.style.marginLeft = e.detail.collapsed ? '70px' : '260px';
        mainContent.classList.toggle('sidebar-collapsed', e.detail.collapsed);
    }
});
</script>

</body>
</html>