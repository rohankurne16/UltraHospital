<?php
require_once __DIR__ . '/../config/permission.php';
include '../header.php';
include '../Sidebar.php';

$user_name = $_SESSION['username'] ?? 'Accountant';
$hospital_name = $_SESSION['hospital_name'] ?? 'City Hospital';
$current_date = date('d M Y, l');
$current_time = date('h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard – <?php echo $hospital_name; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ----- RESET & BASE ----- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            color: #1e293b;
            line-height: 1.6;
        }

        /* ----- MAIN CONTENT (fixed offset for sidebar & header) ----- */
        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            padding: 2rem 1.5rem;
            min-height: 100vh;
            transition: margin 0.3s;
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 1rem; }
        }

        /* ----- CONTAINER (center & limit width) ----- */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ----- GLASSMORPHISM CARDS ----- */
        .glass-card {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.04), 0 2px 8px rgba(0,0,0,0.02);
            transition: all 0.25s ease;
            padding: 1.25rem;
            height: 100%;
        }
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.02);
        }

        /* ----- GRID SYSTEM (fully responsive) ----- */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -0.75rem;
            margin-left: -0.75rem;
        }
        .row > [class*="col-"] {
            padding-right: 0.75rem;
            padding-left: 0.75rem;
            flex: 0 0 100%;
            max-width: 100%;
        }
        /* Breakpoints */
        @media (min-width: 576px) {
            .col-sm-6 { flex: 0 0 50%; max-width: 50%; }
            .col-sm-12 { flex: 0 0 100%; max-width: 100%; }
        }
        @media (min-width: 768px) {
            .col-md-3 { flex: 0 0 25%; max-width: 25%; }
            .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-md-6 { flex: 0 0 50%; max-width: 50%; }
            .col-md-8 { flex: 0 0 66.667%; max-width: 66.667%; }
        }
        @media (min-width: 992px) {
            .col-lg-2 { flex: 0 0 16.667%; max-width: 16.667%; }
            .col-lg-3 { flex: 0 0 25%; max-width: 25%; }
            .col-lg-4 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-lg-6 { flex: 0 0 50%; max-width: 50%; }
            .col-lg-8 { flex: 0 0 66.667%; max-width: 66.667%; }
            .col-lg-12 { flex: 0 0 100%; max-width: 100%; }
        }
        @media (min-width: 1200px) {
            .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
            .col-xl-4 { flex: 0 0 33.333%; max-width: 33.333%; }
            .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
            .col-xl-8 { flex: 0 0 66.667%; max-width: 66.667%; }
            .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
        }
        .g-3 { gap: 0.75rem; } /* fallback, but we use padding */

        /* ----- KPI CARDS ----- */
        .kpi-card {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.5rem 0;
        }
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            flex-shrink: 0;
        }
        .kpi-info {
            flex: 1;
        }
        .kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            color: #0f172a;
        }
        .kpi-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            font-weight: 500;
        }
        .kpi-trend {
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.15rem;
        }
        .kpi-progress {
            height: 4px;
            background: #e9edf2;
            border-radius: 4px;
            margin-top: 0.3rem;
            overflow: hidden;
        }
        .kpi-progress-bar {
            height: 100%;
            border-radius: 4px;
        }

        /* ----- HEADER TOP ----- */
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            margin-bottom: 2rem;
        }
        .header-top .page-title h2 {
            font-weight: 700;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #0f172a, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        .header-top .page-title p {
            color: #64748b;
            margin: 0;
            font-size: 0.9rem;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .header-actions .search-box {
            position: relative;
        }
        .header-actions .search-box input {
            padding: 0.4rem 1rem 0.4rem 2.5rem;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            background: #fff;
            font-size: 0.85rem;
            width: 200px;
            transition: all 0.2s;
        }
        .header-actions .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
            width: 260px;
        }
        .header-actions .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .header-actions .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            transition: all 0.2s;
            position: relative;
            cursor: pointer;
        }
        .header-actions .icon-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .header-actions .icon-btn .badge-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #ef4444;
            color: #fff;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .header-actions .profile-dropdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff;
            padding: 0.2rem 0.8rem 0.2rem 0.4rem;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .header-actions .profile-dropdown:hover {
            background: #f8fafc;
        }
        .header-actions .profile-dropdown img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .header-actions .profile-dropdown span {
            font-size: 0.85rem;
            font-weight: 500;
            color: #1e293b;
        }
        .header-actions .profile-dropdown i {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        .header-date {
            font-size: 0.85rem;
            color: #475569;
            background: #fff;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ----- BUTTONS & BADGES ----- */
        .badge-status {
            padding: 0.25rem 0.7rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.7rem;
        }
        .btn-action {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            border: none;
            transition: all 0.2s;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            display: inline-block;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-action:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .btn-primary-glass {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
            padding: 0.5rem 1.2rem;
            border-radius: 12px;
            font-weight: 600;
            display: inline-block;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59,130,246,0.4);
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 8px;
        }
        .btn-outline-primary {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-outline-primary:hover {
            background: #3b82f6;
            color: #fff;
        }
        .btn-outline-warning {
            background: transparent;
            color: #f59e0b;
            border: 2px solid #f59e0b;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-outline-warning:hover {
            background: #f59e0b;
            color: #fff;
        }

        /* ----- TABLES ----- */
        .table-responsive {
            border-radius: 16px;
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .table thead th {
            background: #f8fafc;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .table tbody td {
            padding: 0.75rem 1rem;
            border-top: 1px solid #e9edf2;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background: #f1f5f9;
        }

        /* ----- CHARTS ----- */
        .chart-wrapper {
            position: relative;
            height: 260px;
        }

        /* ----- ALERT CARDS ----- */
        .alert-card {
            border-radius: 16px;
            padding: 1rem 1.2rem;
            border-left: 5px solid;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: all 0.2s;
            margin-bottom: 0.8rem;
        }
        .alert-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        }

        /* ----- UTILITY CLASSES ----- */
        .text-gradient { background: linear-gradient(135deg, #0f172a, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .bg-soft-primary { background: #eff6ff; }
        .bg-soft-success { background: #dcfce7; }
        .bg-soft-danger { background: #fee2e2; }
        .bg-soft-warning { background: #fef3c7; }
        .bg-soft-purple { background: #f3e8ff; }
        .bg-soft-teal { background: #ccfbf1; }
        .border-left-primary { border-left: 4px solid #3b82f6; }
        .border-left-success { border-left: 4px solid #22c55e; }
        .border-left-danger { border-left: 4px solid #ef4444; }
        .border-left-warning { border-left: 4px solid #f59e0b; }
        .border-left-purple { border-left: 4px solid #8b5cf6; }
        .border-left-teal { border-left: 4px solid #14b8a6; }
        .icon-gradient-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .icon-gradient-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .icon-gradient-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .icon-gradient-yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .icon-gradient-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .icon-gradient-teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
        .icon-gradient-pink { background: linear-gradient(135deg, #ec4899, #db2777); }
        .icon-gradient-indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .icon-gradient-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
        .icon-gradient-cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .text-xs { font-size: 0.7rem; }
        .fw-bold { font-weight: 700; }
        .text-white { color: #fff; }
        .text-dark { color: #1e293b; }
        .text-muted { color: #64748b; }
        .text-success { color: #22c55e; }
        .text-danger { color: #ef4444; }
        .text-warning { color: #f59e0b; }
        .text-primary { color: #3b82f6; }
        .text-purple { color: #8b5cf6; }
        .text-info { color: #06b6d4; }
        .bg-success { background: #22c55e; }
        .bg-danger { background: #ef4444; }
        .bg-warning { background: #f59e0b; }
        .bg-primary { background: #3b82f6; }
        .bg-purple { background: #8b5cf6; }
        .bg-teal { background: #14b8a6; }
        .bg-pink { background: #ec4899; }
        .bg-indigo { background: #6366f1; }
        .bg-orange { background: #f97316; }
        .bg-cyan { background: #06b6d4; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        .d-flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .mb-0 { margin-bottom: 0; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .p-2 { padding: 0.5rem; }
        .p-3 { padding: 1rem; }
        .rounded-3 { border-radius: 0.5rem; }
        .position-relative { position: relative; }
        .small { font-size: 0.8rem; }
        .list-unstyled { list-style: none; padding-left: 0; }
        .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
        .me-1 { margin-right: 0.25rem; }
        .me-2 { margin-right: 0.5rem; }
        .ms-2 { margin-left: 0.5rem; }
        .text-center { text-align: center; }
        .progress {
            height: 5px;
            background: #e9edf2;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 4px;
            background: #3b82f6;
        }
        hr { border: 0; border-top: 1px solid #e9edf2; margin: 1rem 0; }

        /* Responsive tweaks */
        @media (max-width: 768px) {
            .header-top .page-title h2 { font-size: 1.4rem; }
            .header-actions .search-box input { width: 140px; }
            .header-actions .search-box input:focus { width: 180px; }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="dashboard-container">
            <!-- HEADER TOP -->
            <div class="header-top">
                <div class="page-title">
                    <h2>Accountant Dashboard</h2>
                    <p><i class="fas fa-building me-1"></i> <?php echo $hospital_name; ?> &bull; Welcome, <?php echo htmlspecialchars(ucwords($user_name)); ?></p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search anything..." aria-label="Search">
                    </div>
                    <div class="header-date">
                        <i class="far fa-calendar-alt"></i> <?php echo $current_date; ?> &bull; <i class="far fa-clock"></i> <?php echo $current_time; ?>
                    </div>
                    <div class="icon-btn position-relative">
                        <i class="far fa-bell"></i>
                        <span class="badge-dot">6</span>
                    </div>
                    <div class="icon-btn position-relative">
                        <i class="far fa-envelope"></i>
                        <span class="badge-dot">3</span>
                    </div>
                    <div class="profile-dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=3b82f6&color=fff&size=32" alt="Profile">
                        <span><?php echo htmlspecialchars(ucwords($user_name)); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            <!-- ======== KPI CARDS (4 per row) ======== -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-green"><i class="fas fa-rupee-sign"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹4,28,600</div>
                                <div class="kpi-label">Today's Revenue</div>
                                <div class="kpi-trend text-success"><i class="fas fa-arrow-up"></i> +12.5%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-success" style="width:78%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-blue"><i class="fas fa-chart-line"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹32,14,800</div>
                                <div class="kpi-label">Monthly Revenue</div>
                                <div class="kpi-trend text-success"><i class="fas fa-arrow-up"></i> +8.3%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-primary" style="width:65%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-yellow"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹1,87,200</div>
                                <div class="kpi-label">Cash Collection</div>
                                <div class="kpi-trend text-success"><i class="fas fa-arrow-up"></i> +5.2%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-warning" style="width:55%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-purple"><i class="fas fa-mobile-alt"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹1,02,400</div>
                                <div class="kpi-label">UPI Collection</div>
                                <div class="kpi-trend text-success"><i class="fas fa-arrow-up"></i> +18.7%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-purple" style="width:42%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-pink"><i class="fas fa-credit-card"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹89,500</div>
                                <div class="kpi-label">Card Payments</div>
                                <div class="kpi-trend text-danger"><i class="fas fa-arrow-down"></i> -2.1%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-pink" style="width:35%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-indigo"><i class="fas fa-university"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹1,24,300</div>
                                <div class="kpi-label">Bank Transfer</div>
                                <div class="kpi-trend text-success"><i class="fas fa-arrow-up"></i> +6.8%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-indigo" style="width:48%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-red"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹5,67,200</div>
                                <div class="kpi-label">Outstanding Bills</div>
                                <div class="kpi-trend text-danger"><i class="fas fa-arrow-up"></i> +3.4%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-danger" style="width:70%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-orange"><i class="fas fa-hourglass-half"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹3,42,800</div>
                                <div class="kpi-label">Pending Payments</div>
                                <div class="kpi-trend text-warning"><i class="fas fa-minus"></i> 0.0%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-orange" style="width:45%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-teal"><i class="fas fa-receipt"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹8,76,400</div>
                                <div class="kpi-label">Total Expenses</div>
                                <div class="kpi-trend text-danger"><i class="fas fa-arrow-up"></i> +4.1%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-teal" style="width:52%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="glass-card">
                        <div class="kpi-card">
                            <div class="kpi-icon icon-gradient-cyan"><i class="fas fa-coins"></i></div>
                            <div class="kpi-info">
                                <div class="kpi-value">₹23,38,400</div>
                                <div class="kpi-label">Net Profit</div>
                                <div class="kpi-trend text-success"><i class="fas fa-arrow-up"></i> +10.2%</div>
                                <div class="kpi-progress"><div class="kpi-progress-bar bg-cyan" style="width:88%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== FINANCIAL ANALYTICS ======== -->
            <div class="row g-3 mb-4">
                <div class="col-xl-8 col-lg-8 col-md-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-area me-2 text-primary"></i>Revenue vs Expenses (Area)</h6>
                        <div class="chart-wrapper"><canvas id="revenueExpenseChart"></canvas></div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie me-2 text-purple"></i>Payment Mode Distribution</h6>
                        <div class="chart-wrapper"><canvas id="paymentModeChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-success"></i>Monthly Revenue (Bar)</h6>
                        <div class="chart-wrapper"><canvas id="monthlyRevenueChart"></canvas></div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-warning"></i>Department Revenue (Horizontal)</h6>
                        <div class="chart-wrapper"><canvas id="departmentRevenueChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-line me-2 text-info"></i>Daily Collections (Line)</h6>
                        <div class="chart-wrapper"><canvas id="dailyCollectionsChart"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- ======== BILLING SUMMARY ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-file-invoice me-2 text-primary"></i>Billing Summary</h6>
                        <div class="row g-3">
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">₹1,24,500</span><br><span class="text-xs text-muted">OPD</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-success rounded-3"><span class="fw-bold">₹2,45,800</span><br><span class="text-xs text-muted">IPD</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-warning rounded-3"><span class="fw-bold">₹1,87,300</span><br><span class="text-xs text-muted">Pharmacy</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-purple rounded-3"><span class="fw-bold">₹56,200</span><br><span class="text-xs text-muted">Laboratory</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-teal rounded-3"><span class="fw-bold">₹34,800</span><br><span class="text-xs text-muted">Radiology</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-danger rounded-3"><span class="fw-bold">₹67,400</span><br><span class="text-xs text-muted">OT</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">₹45,200</span><br><span class="text-xs text-muted">Emergency</span></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== PAYMENT SUMMARY ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-credit-card me-2 text-success"></i>Payment Summary</h6>
                        <div class="row g-3">
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-success rounded-3"><span class="fw-bold">₹14,56,200</span><br><span class="text-xs text-muted">Paid Bills</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-danger rounded-3"><span class="fw-bold">₹5,67,200</span><br><span class="text-xs text-muted">Pending Bills</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-warning rounded-3"><span class="fw-bold">₹2,34,500</span><br><span class="text-xs text-muted">Advance Payments</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-purple rounded-3"><span class="fw-bold">₹1,87,600</span><br><span class="text-xs text-muted">Partial Payments</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-teal rounded-3"><span class="fw-bold">₹34,200</span><br><span class="text-xs text-muted">Refunds</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">₹1,12,400</span><br><span class="text-xs text-muted">Discounts Given</span></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== RECENT TRANSACTIONS ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Recent Transactions</h6>
                            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr>
                                    <th>Bill No.</th><th>Patient</th><th>Department</th><th>Amount</th><th>Payment Mode</th><th>Status</th><th>Date</th>
                                </tr></thead>
                                <tbody>
                                    <tr><td>#INV-001</td><td>Ravi Singh</td><td>OPD</td><td>₹4,500</td><td>Cash</td><td><span class="badge-status bg-success text-white">Paid</span></td><td>06 Aug 2026</td></tr>
                                    <tr><td>#INV-002</td><td>Priya Mehta</td><td>IPD</td><td>₹18,200</td><td>UPI</td><td><span class="badge-status bg-warning text-dark">Pending</span></td><td>06 Aug 2026</td></tr>
                                    <tr><td>#INV-003</td><td>Vijay Kumar</td><td>Pharmacy</td><td>₹2,800</td><td>Card</td><td><span class="badge-status bg-success text-white">Paid</span></td><td>05 Aug 2026</td></tr>
                                    <tr><td>#INV-004</td><td>Sneha Patel</td><td>Laboratory</td><td>₹7,300</td><td>Bank Transfer</td><td><span class="badge-status bg-danger text-white">Overdue</span></td><td>05 Aug 2026</td></tr>
                                    <tr><td>#INV-005</td><td>Amit Sharma</td><td>OT</td><td>₹25,000</td><td>Cash</td><td><span class="badge-status bg-success text-white">Paid</span></td><td>04 Aug 2026</td></tr>
                                    <tr><td>#INV-006</td><td>Neha Reddy</td><td>Radiology</td><td>₹5,600</td><td>UPI</td><td><span class="badge-status bg-success text-white">Paid</span></td><td>04 Aug 2026</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== PENDING PAYMENTS ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-warning"></i>Pending Payments</h6>
                            <a href="#" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr>
                                    <th>Patient</th><th>Bill No.</th><th>Amount</th><th>Due Date</th><th>Status</th>
                                </tr></thead>
                                <tbody>
                                    <tr><td>Priya Mehta</td><td>#INV-002</td><td>₹18,200</td><td>10 Aug 2026</td><td><span class="badge-status bg-warning text-dark">Pending</span></td></tr>
                                    <tr><td>Sneha Patel</td><td>#INV-004</td><td>₹7,300</td><td>08 Aug 2026</td><td><span class="badge-status bg-danger text-white">Overdue</span></td></tr>
                                    <tr><td>Rohit Gupta</td><td>#INV-007</td><td>₹12,500</td><td>12 Aug 2026</td><td><span class="badge-status bg-warning text-dark">Pending</span></td></tr>
                                    <tr><td>Kiran Raj</td><td>#INV-009</td><td>₹5,400</td><td>09 Aug 2026</td><td><span class="badge-status bg-danger text-white">Overdue</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== INSURANCE / TPA ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2 text-primary"></i>Insurance / TPA</h6>
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6"><div class="p-3 bg-soft-primary rounded-3"><span class="fw-bold">18</span><br><span class="text-xs text-muted">Claims Submitted</span></div></div>
                            <div class="col-lg-3 col-md-6"><div class="p-3 bg-soft-success rounded-3"><span class="fw-bold">12</span><br><span class="text-xs text-muted">Claims Approved</span></div></div>
                            <div class="col-lg-3 col-md-6"><div class="p-3 bg-soft-warning rounded-3"><span class="fw-bold">6</span><br><span class="text-xs text-muted">Claims Pending</span></div></div>
                            <div class="col-lg-3 col-md-6"><div class="p-3 bg-soft-purple rounded-3"><span class="fw-bold">₹4,28,500</span><br><span class="text-xs text-muted">Claim Amount</span></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== EXPENSE SUMMARY ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-receipt me-2 text-danger"></i>Expense Summary</h6>
                        <div class="row g-3">
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-danger rounded-3"><span class="fw-bold">₹3,45,000</span><br><span class="text-xs text-muted">Staff Salary</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-warning rounded-3"><span class="fw-bold">₹1,12,400</span><br><span class="text-xs text-muted">Utility Bills</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">₹2,34,200</span><br><span class="text-xs text-muted">Medical Purchases</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-teal rounded-3"><span class="fw-bold">₹56,800</span><br><span class="text-xs text-muted">Maintenance</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-purple rounded-3"><span class="fw-bold">₹1,28,000</span><br><span class="text-xs text-muted">Other Expenses</span></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== DAILY CASH SUMMARY ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-money-bill-alt me-2 text-success"></i>Daily Cash Summary</h6>
                        <div class="row g-3">
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-success rounded-3"><span class="fw-bold">₹2,50,000</span><br><span class="text-xs text-muted">Opening Cash</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">₹1,87,200</span><br><span class="text-xs text-muted">Today's Collection</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-danger rounded-3"><span class="fw-bold">₹45,600</span><br><span class="text-xs text-muted">Today's Expense</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-warning rounded-3"><span class="fw-bold">₹3,91,600</span><br><span class="text-xs text-muted">Closing Cash</span></div></div>
                            <div class="col-lg-2 col-md-4 col-6"><div class="p-2 bg-soft-teal rounded-3"><span class="fw-bold">₹1,41,600</span><br><span class="text-xs text-muted">Cash in Hand</span></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== TOP REVENUE DEPARTMENTS ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-trophy me-2 text-warning"></i>Top Revenue Departments</h6>
                        <div class="row g-3">
                            <div class="col-md-3 col-6"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">1. IPD</span><br><span class="text-xs text-muted">₹2,45,800</span><div class="progress mt-1"><div class="progress-bar bg-primary" style="width:100%"></div></div></div></div>
                            <div class="col-md-3 col-6"><div class="p-2 bg-soft-success rounded-3"><span class="fw-bold">2. OT</span><br><span class="text-xs text-muted">₹67,400</span><div class="progress mt-1"><div class="progress-bar bg-success" style="width:80%"></div></div></div></div>
                            <div class="col-md-3 col-6"><div class="p-2 bg-soft-warning rounded-3"><span class="fw-bold">3. Pharmacy</span><br><span class="text-xs text-muted">₹1,87,300</span><div class="progress mt-1"><div class="progress-bar bg-warning" style="width:95%"></div></div></div></div>
                            <div class="col-md-3 col-6"><div class="p-2 bg-soft-danger rounded-3"><span class="fw-bold">4. OPD</span><br><span class="text-xs text-muted">₹1,24,500</span><div class="progress mt-1"><div class="progress-bar bg-danger" style="width:70%"></div></div></div></div>
                            <div class="col-md-3 col-6"><div class="p-2 bg-soft-purple rounded-3"><span class="fw-bold">5. Laboratory</span><br><span class="text-xs text-muted">₹56,200</span><div class="progress mt-1"><div class="progress-bar bg-purple" style="width:60%"></div></div></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== QUICK ACTIONS ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary-glass"><i class="fas fa-file-invoice"></i> Create Bill</button>
                            <button class="btn btn-action"><i class="fas fa-hand-holding-usd"></i> Receive Payment</button>
                            <button class="btn btn-action"><i class="fas fa-undo-alt"></i> Refund</button>
                            <button class="btn btn-action"><i class="fas fa-plus-circle"></i> Add Expense</button>
                            <button class="btn btn-action"><i class="fas fa-print"></i> Print Invoice</button>
                            <button class="btn btn-action"><i class="fas fa-chart-pie"></i> Generate Report</button>
                            <button class="btn btn-action"><i class="fas fa-book"></i> View Ledger</button>
                            <button class="btn btn-action"><i class="fas fa-file-pdf"></i> Export PDF</button>
                            <button class="btn btn-action"><i class="fas fa-file-excel"></i> Export Excel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== ALERTS PANEL ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-exclamation-circle me-2 text-danger"></i>Alerts</h6>
                        <div class="row g-3">
                            <div class="col-md-4"><div class="alert-card border-left-warning"><i class="fas fa-clock text-warning me-2"></i> <strong>Pending Bills:</strong> 12 bills overdue.</div></div>
                            <div class="col-md-4"><div class="alert-card border-left-danger"><i class="fas fa-file-medical-alt text-danger me-2"></i> <strong>Insurance Claims:</strong> 6 claims pending approval.</div></div>
                            <div class="col-md-4"><div class="alert-card border-left-warning"><i class="fas fa-truck text-warning me-2"></i> <strong>Supplier Payments:</strong> 3 payments due this week.</div></div>
                            <div class="col-md-4"><div class="alert-card border-left-danger"><i class="fas fa-coins text-danger me-2"></i> <strong>Low Cash Balance:</strong> ₹1,41,600 remaining.</div></div>
                            <div class="col-md-4"><div class="alert-card border-left-warning"><i class="fas fa-file-invoice text-warning me-2"></i> <strong>GST Reminder:</strong> GST return filing due on 20th.</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== REPORT SHORTCUTS ======== -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-folder-open me-2 text-primary"></i>Report Shortcuts</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="#" class="btn btn-action"><i class="fas fa-chart-line text-primary"></i> Revenue</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-chart-pie text-danger"></i> Expense</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-hand-holding-usd text-success"></i> Collection</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-book text-warning"></i> Cash Book</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-university text-info"></i> Bank Book</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-book-open text-purple"></i> Ledger</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-chart-bar text-danger"></i> Profit &amp; Loss</a>
                            <a href="#" class="btn btn-action"><i class="fas fa-file-invoice text-warning"></i> GST Report</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======== RIGHT SIDEBAR (summary at bottom) ======== -->
            <div class="row g-3">
                <div class="col-12">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Today's Financial Summary</h6>
                        <div class="row g-3">
                            <div class="col-md-3"><div class="p-2 bg-soft-success rounded-3"><span class="fw-bold">₹4,28,600</span><br><span class="text-xs text-muted">Today's Collection</span></div></div>
                            <div class="col-md-3"><div class="p-2 bg-soft-danger rounded-3"><span class="fw-bold">₹45,600</span><br><span class="text-xs text-muted">Today's Expense</span></div></div>
                            <div class="col-md-3"><div class="p-2 bg-soft-warning rounded-3"><span class="fw-bold">₹3,83,000</span><br><span class="text-xs text-muted">Net Today</span></div></div>
                            <div class="col-md-3"><div class="p-2 bg-soft-primary rounded-3"><span class="fw-bold">18</span><br><span class="text-xs text-muted">Total Transactions</span></div></div>
                        </div>
                        <hr>
                        <h6 class="fw-bold mb-2"><i class="fas fa-history me-2 text-muted"></i>Recent Activities</h6>
                        <ul class="list-unstyled small">
                            <li class="py-1"><i class="fas fa-check-circle text-success me-2"></i> Payment received from Ravi Singh – ₹4,500</li>
                            <li class="py-1"><i class="fas fa-file-invoice text-warning me-2"></i> New bill generated for Priya Mehta – ₹18,200</li>
                            <li class="py-1"><i class="fas fa-credit-card text-primary me-2"></i> Card payment of ₹2,800 from Vijay Kumar</li>
                            <li class="py-1"><i class="fas fa-exclamation-triangle text-danger me-2"></i> Overdue bill for Sneha Patel – ₹7,300</li>
                        </ul>
                        <hr>
                        <h6 class="fw-bold mb-2"><i class="fas fa-hourglass-half me-2 text-warning"></i>Upcoming Due Payments</h6>
                        <ul class="list-unstyled small">
                            <li class="py-1"><span class="badge-status bg-danger text-white me-2">Due Today</span> Priya Mehta – ₹18,200</li>
                            <li class="py-1"><span class="badge-status bg-warning text-dark me-2">Due Tomorrow</span> Rohit Gupta – ₹12,500</li>
                            <li class="py-1"><span class="badge-status bg-warning text-dark me-2">Due 2 days</span> Sneha Patel – ₹7,300</li>
                        </ul>
                        <hr>
                        <h6 class="fw-bold mb-2"><i class="fas fa-coins me-2 text-success"></i>Latest Collections</h6>
                        <ul class="list-unstyled small">
                            <li class="py-1"><i class="fas fa-rupee-sign text-success me-2"></i> ₹4,500 – Ravi Singh (Cash)</li>
                            <li class="py-1"><i class="fas fa-rupee-sign text-success me-2"></i> ₹2,800 – Vijay Kumar (Card)</li>
                            <li class="py-1"><i class="fas fa-rupee-sign text-success me-2"></i> ₹5,600 – Neha Reddy (UPI)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div> <!-- /dashboard-container -->
    </div> <!-- /main-content -->

    <!-- ======== CHARTS ======== -->
    <script>
        // 1. Revenue vs Expenses (Area Chart)
        const ctx1 = document.getElementById('revenueExpenseChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                datasets: [
                    {
                        label: 'Revenue',
                        data: [120000, 150000, 180000, 220000, 200000, 240000, 280000, 320000],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Expenses',
                        data: [80000, 90000, 100000, 110000, 120000, 130000, 140000, 150000],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
        });

        // 2. Payment Mode Distribution (Donut)
        const ctx2 = document.getElementById('paymentModeChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Insurance'],
                datasets: [{
                    data: [187200, 102400, 89500, 124300, 68000],
                    backgroundColor: ['#f59e0b', '#8b5cf6', '#ec4899', '#6366f1', '#06b6d4'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
        });

        // 3. Monthly Revenue (Bar)
        const ctx3 = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: [120000, 150000, 180000, 220000, 200000, 240000, 280000, 321480],
                    backgroundColor: '#22c55e',
                    borderRadius: 6
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // 4. Department Revenue (Horizontal Bar)
        const ctx4 = document.getElementById('departmentRevenueChart').getContext('2d');
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: ['IPD', 'OPD', 'Pharmacy', 'Laboratory', 'Radiology', 'OT', 'Emergency'],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: [245800, 124500, 187300, 56200, 34800, 67400, 45200],
                    backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'],
                    borderRadius: 6
                }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // 5. Daily Collections (Line)
        const ctx5 = document.getElementById('dailyCollectionsChart').getContext('2d');
        new Chart(ctx5, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Collection (₹)',
                    data: [45000, 52000, 48000, 61000, 58000, 49000, 56000],
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6,182,212,0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>