<?php
// ============================================================
// RECEPTIONIST DASHBOARD – Premium SaaS Style
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/permission.php';
require_once '../config/hospital.php';

// --- Authentication check ---
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id     = (int) $_SESSION['id'];
$hospital_id = (int) ($_SESSION['hospital_id'] ?? 0);

if (!$hospital_id) {
    header('Location: ../index.php');
    exit;
}

$user_name = $_SESSION['name'] ?? 'Receptionist';

// ---------- Helper: scalar query ----------
function scalar($conn, $sql, $types = '', $params = []) {
    if ($types) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) return 0;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) { $stmt->close(); return 0; }
        $row = $res->fetch_row();
        $stmt->close();
        return $row ? (int) $row[0] : 0;
    } else {
        $res = $conn->query($sql);
        if ($res === false) return 0;
        $row = $res->fetch_row();
        return $row ? (int) $row[0] : 0;
    }
}

$today = date('Y-m-d');

// ---------- STATISTICS (hospital‑filtered) ----------
$total_patients = scalar($conn,
    "SELECT COUNT(*) FROM patients WHERE delete_flag = 0 AND hospital_id = ?",
    'i', [$hospital_id]);

$today_appointments = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND appointment_date = ?",
    'is', [$hospital_id, $today]);

$today_opd = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND appointment_date = ? AND opd_ipd_type = 'OPD'",
    'is', [$hospital_id, $today]);

$today_ipd = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND appointment_date = ? AND opd_ipd_type = 'IPD'",
    'is', [$hospital_id, $today]);

$pending_appointments = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND status IN ('Scheduled','Pending')",
    'i', [$hospital_id]);

$confirmed_appointments = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND status = 'Confirmed'",
    'i', [$hospital_id]);

$cancelled_appointments = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND status = 'Cancelled'",
    'i', [$hospital_id]);

$today_bills_count = scalar($conn,
    "SELECT COUNT(*) FROM billing WHERE delete_flag = 0 AND hospital_id = ? AND DATE(created_at) = ?",
    'is', [$hospital_id, $today]);

$today_bills_amount = scalar($conn,
    "SELECT IFNULL(SUM(total_amount), 0) FROM billing WHERE delete_flag = 0 AND hospital_id = ? AND DATE(created_at) = ?",
    'is', [$hospital_id, $today]);

$today_followups = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND appointment_type = 'Follow-up' AND appointment_date = ?",
    'is', [$hospital_id, $today]);

$notifications = scalar($conn,
    "SELECT COUNT(*) FROM patient_alerts WHERE delete_flag = 0 AND hospital_id = ? AND status = 'Active'",
    'i', [$hospital_id]);

// ---------- Trend (last 7 days) ----------
$trendLabels = [];
$trendData   = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i day"));
    $trendLabels[] = date('D', strtotime($date));
    $count = scalar($conn,
        "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND appointment_date = ?",
        'is', [$hospital_id, $date]);
    $trendData[] = $count;
}

// ---------- OPD vs IPD totals (all time) ----------
$total_opd = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND opd_ipd_type = 'OPD'",
    'i', [$hospital_id]);
$total_ipd = scalar($conn,
    "SELECT COUNT(*) FROM appointments WHERE delete_flag = 0 AND hospital_id = ? AND opd_ipd_type = 'IPD'",
    'i', [$hospital_id]);

// ---------- Recent Patients (last 5) ----------
$recentPatients = [];
$stmt = $conn->prepare(
    "SELECT patient_id, patient_name, patient_image, mobile, status, created_at
     FROM patients
     WHERE delete_flag = 0 AND hospital_id = ?
     ORDER BY created_at DESC LIMIT 5"
);
$stmt->bind_param('i', $hospital_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recentPatients[] = $row;
}

// ---------- Upcoming Appointments (next 5) ----------
$upcomingAppointments = [];
$stmt = $conn->prepare(
    "SELECT a.appointment_id, a.appointment_time, a.status, a.opd_ipd_type,
            p.patient_name, d.doctor_name
     FROM appointments a
     LEFT JOIN patients p ON p.patient_id = a.patient_id
     LEFT JOIN doctor d ON d.doctor_id = a.doctor_id
     WHERE a.delete_flag = 0 AND a.hospital_id = ? AND a.appointment_date >= ?
     ORDER BY a.appointment_date, a.appointment_time LIMIT 5"
);
$stmt->bind_param('is', $hospital_id, $today);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $upcomingAppointments[] = $row;
}

// ---------- Helper: initials ----------
function initials($name) {
    $name = trim($name ?: '?');
    $parts = preg_split('/\s+/', $name);
    $ini = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $ini .= strtoupper(substr(end($parts), 0, 1));
    return $ini;
}

// ---------- JSON for charts ----------
$trendLabelsJson = json_encode($trendLabels);
$trendDataJson   = json_encode($trendData);

$page_title = "Receptionist Dashboard – " . htmlspecialchars($hospital['hospital_name'] ?? 'Hospital');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?? ''; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ============================================================
           SAME STYLING AS DOCTOR DASHBOARD (premium SaaS)
           ============================================================ */
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: #f8fafc; }
        
        .uh-dash{ --uhp:#4f6ef7; --uhp-d:#3a56d4; --uhp-l:#eef1fd;
            --uhs:#1aa053; --uhw:#f2a93b; --uhr:#e5484d; --uhi:#2fb5d2; --uhu:#7b3fe4;
            --ink:#1f2430; --muted:#6b7a8f; --border:#e8ebf0;
            font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding:24px 28px 44px; 
            max-width:1500px; 
            margin-left:280px;
            margin-right:0;
            font-size:14px; 
            line-height:1.5; 
            color:var(--ink);
            transition: margin-left 0.3s ease;
        }
        .uh-dash *{ box-sizing:border-box; }
        .uh-dash .uh-head{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
        .uh-dash .uh-head h4{ font-weight:600; font-size:1.5rem; color:var(--ink); margin:0; letter-spacing:-0.02em; }
        .uh-dash .uh-head h4 span{ font-weight:400; font-size:1rem; }
        .uh-dash .uh-head p{ color:var(--muted); margin:4px 0 0; font-size:0.875rem; }
        .uh-dash .uh-date{ background:#fff; border:1px solid var(--border); border-radius:40px; padding:8px 18px;
            font-size:0.8rem; font-weight:500; color:var(--muted); box-shadow:0 2px 8px rgba(31,36,48,.04); white-space:nowrap; }
        .uh-dash .uh-date i{ color:var(--uhp); margin-right:6px; }

        .uh-dash .uh-grid{ display:grid; gap:16px; margin-bottom:16px; }
        .uh-dash .uh-grid-4{ grid-template-columns:repeat(4,1fr); }
        .uh-dash .uh-grid-2-1{ grid-template-columns:2fr 1fr; }
        .uh-dash .uh-grid-7-5{ grid-template-columns:1.4fr 1fr; }

        @media (max-width:1100px){
            .uh-dash .uh-grid-4{ grid-template-columns:repeat(2,1fr); }
            .uh-dash .uh-grid-2-1, .uh-dash .uh-grid-7-5{ grid-template-columns:1fr; }
            .uh-dash{ margin-left:240px; padding:20px; }
        }
        @media (max-width:991px){
            .uh-dash{ margin-left:0 !important; padding:16px; }
        }
        @media (max-width:560px){ 
            .uh-dash .uh-grid-4{ grid-template-columns:1fr; }
            .uh-dash{ padding:12px; }
        }

        .uh-dash .stat{ background:#fff; border:1px solid var(--border); border-radius:14px; padding:18px 20px;
            box-shadow:0 2px 8px rgba(31,36,48,.04); transition:all 0.2s ease; cursor:pointer; }
        .uh-dash .stat:hover{ transform:translateY(-3px); box-shadow:0 8px 20px rgba(31,36,48,.08); border-color:var(--uhp); }
        .uh-dash .stat .ic{ width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; margin-bottom:14px; }
        .uh-dash .stat .val{ font-size:1.6rem; font-weight:600; color:var(--ink); line-height:1.1; letter-spacing:-0.02em; }
        .uh-dash .stat .lbl{ color:var(--muted); font-size:0.78rem; font-weight:500; margin-top:6px; }

        .uh-dash .soft-primary{ background:var(--uhp-l); color:var(--uhp); }
        .uh-dash .soft-success{ background:#e6f7ed; color:var(--uhs); }
        .uh-dash .soft-warning{ background:#fef4e4; color:var(--uhw); }
        .uh-dash .soft-info{ background:#e4f5fa; color:var(--uhi); }
        .uh-dash .soft-danger{ background:#fdeaea; color:var(--uhr); }
        .uh-dash .soft-purple{ background:#f0e9fd; color:var(--uhu); }

        .uh-dash .card{ background:#fff; border:1px solid var(--border); border-radius:14px; padding:22px 24px;
            box-shadow:0 2px 8px rgba(31,36,48,.04); }
        .uh-dash .card-title{ font-weight:600; color:var(--ink); font-size:1rem; margin:0 0 2px; }
        .uh-dash .card-sub{ color:var(--muted); font-size:0.78rem; margin:0 0 16px; }
        .uh-dash .card-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap; }
        .uh-dash .view-all{ font-size:0.8rem; font-weight:500; color:var(--uhp); text-decoration:none; white-space:nowrap; padding:4px 0; }
        .uh-dash .view-all:hover{ color:var(--uhp-d); text-decoration:underline; }

        .uh-dash .chart-box{ position:relative; height:260px; }
        .uh-dash .chart-box.sm{ height:210px; }

        .uh-dash table{ width:100%; border-collapse:separate; border-spacing:0; font-size:0.85rem; }
        .uh-dash thead th{ text-align:left; color:var(--muted); font-weight:600; text-transform:uppercase;
            font-size:0.62rem; letter-spacing:0.04em; padding:0 8px 10px; border-bottom:1px solid var(--border); }
        .uh-dash tbody td{ padding:12px 8px; border-bottom:1px solid #f0f2f6; color:var(--ink); vertical-align:middle; }
        .uh-dash tbody tr:last-child td{ border-bottom:none; }
        .uh-dash tbody tr{ cursor:pointer; transition:background 0.15s; }
        .uh-dash tbody tr:hover{ background:#f8fafc; }
        
        .uh-dash .name-cell{ display:flex; align-items:center; gap:8px; }
        .uh-dash .name-cell a{ color:var(--ink); text-decoration:none; display:flex; align-items:center; gap:8px; }
        .uh-dash .name-cell a:hover{ color:var(--uhp); text-decoration:underline; }
        .uh-dash .avatar{ width:32px; height:32px; border-radius:50%; background:var(--uhp-l); color:var(--uhp);
            display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.7rem; flex-shrink:0; }

        .uh-dash .uh-badge{ display:inline-block; font-weight:500; font-size:0.7rem; border-radius:20px; padding:3px 11px; letter-spacing:0.01em; }
        .uh-dash .uh-badge-primary{ background:var(--uhp-l); color:var(--uhp); }
        .uh-dash .uh-badge-success{ background:#e6f7ed; color:var(--uhs); }
        .uh-dash .uh-badge-info{ background:#e4f5fa; color:#1e8ea8; }
        .uh-dash .uh-badge-warning{ background:#fef4e4; color:#b87a1e; }
        .uh-dash .uh-badge-danger{ background:#fdeaea; color:var(--uhr); }
        .uh-dash .uh-badge-secondary{ background:#eef0f4; color:var(--muted); }
        .uh-dash .uh-badge-purple{ background:#f0e9fd; color:var(--uhu); }

        .uh-dash .empty{ text-align:center; color:var(--muted); padding:30px 10px; font-size:0.85rem; }

        .uh-dash .quick-links{ display:flex; flex-direction:column; gap:8px; }
        .uh-dash .quick-link{ display:flex; align-items:center; gap:12px; padding:13px 16px; border-radius:12px;
            border:1px solid var(--border); text-decoration:none; color:var(--ink); font-weight:500; font-size:0.85rem;
            background:#fafcff; transition:all 0.2s ease; }
        .uh-dash .quick-link:hover{ background:var(--uhp-l); color:var(--uhp); border-color:var(--uhp); transform:translateX(4px); }
        .uh-dash .quick-link i{ width:20px; text-align:center; font-size:1rem; color:var(--uhp); }

        .uh-dash .stat-link{ text-decoration:none; display:block; }
        .uh-dash .clickable-row{ cursor:pointer; }
        .uh-dash .clickable-row:hover{ background:#f1f5f9; }

        /* Attractive table styles */
        .table-wrapper { position: relative; overflow: hidden; }
        .table-wrapper .table-header-bg { background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); }
        
        .uh-dash .table-modern {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .uh-dash .table-modern thead th {
            padding: 14px 16px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            background: #f8fafc;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .uh-dash .table-modern thead th:first-child { border-radius: 12px 0 0 0; padding-left: 20px; }
        .uh-dash .table-modern thead th:last-child { border-radius: 0 12px 0 0; padding-right: 20px; }
        
        .uh-dash .table-modern tbody tr {
            transition: all 0.25s ease;
            border-bottom: 1px solid #f1f5f9;
            position: relative;
        }
        .uh-dash .table-modern tbody tr:last-child { border-bottom: none; }
        .uh-dash .table-modern tbody tr:last-child td:first-child { border-radius: 0 0 0 12px; }
        .uh-dash .table-modern tbody tr:last-child td:last-child { border-radius: 0 0 12px 0; }
        .uh-dash .table-modern tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .uh-dash .table-modern tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
            color: #1e293b;
            background: transparent;
        }
        .uh-dash .table-modern tbody td:first-child { padding-left: 20px; }
        .uh-dash .table-modern tbody td:last-child { padding-right: 20px; }

        .uh-dash .avatar-modern {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: #fff;
            flex-shrink: 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: relative;
        }
        .uh-dash .avatar-modern:hover { transform: scale(1.1); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
        .uh-dash .avatar-modern .initials {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: #fff;
            background: inherit;
            border-radius: 50%;
        }
        .uh-dash .avatar-modern img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .uh-dash .avatar-modern .initials.hide { display: none; }
        .uh-dash .avatar-modern img.hide { display: none; }

        .uh-dash .avatar-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .uh-dash .avatar-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .uh-dash .avatar-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .uh-dash .avatar-pink { background: linear-gradient(135deg, #ec4899, #db2777); }
        .uh-dash .avatar-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .uh-dash .avatar-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .uh-dash .avatar-teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
        .uh-dash .avatar-indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }

        .uh-dash .badge-modern {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .uh-dash .badge-modern:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .uh-dash .badge-modern.badge-confirmed { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
        .uh-dash .badge-modern.badge-scheduled { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .uh-dash .badge-modern.badge-completed { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .uh-dash .badge-modern.badge-cancelled { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .uh-dash .badge-modern.badge-active { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .uh-dash .badge-modern.badge-inactive { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
        .uh-dash .badge-modern.badge-pending { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .uh-dash .badge-modern.badge-secondary { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
        .uh-dash .badge-modern.badge-purple { background: #ede9fe; color: #6d28d9; border-color: #ddd6fe; }

        .uh-dash .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .uh-dash .type-badge.type-opd { background: #dbeafe; color: #1e40af; }
        .uh-dash .type-badge.type-ipd { background: #ede9fe; color: #6d28d9; }

        .uh-dash .row-number-modern {
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.75rem;
            width: 24px;
            text-align: center;
            display: inline-block;
        }
        .uh-dash .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .uh-dash .status-dot.active { background: #22c55e; }
        .uh-dash .status-dot.inactive { background: #94a3b8; }
        .uh-dash .status-dot.pending { background: #f59e0b; }
        .uh-dash .status-dot.completed { background: #3b82f6; }
        .uh-dash .status-dot.cancelled { background: #ef4444; }

        .uh-dash .card-footer-shimmer {
            background: linear-gradient(90deg, #f8fafc 25%, #eef2ff 50%, #f8fafc 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
            padding: 8px 16px;
            border-radius: 0 0 12px 12px;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .table-scroll {
            overflow-x: auto;
            margin: 0 -4px;
            padding: 0 4px;
        }
        .table-scroll::-webkit-scrollbar { height: 4px; }
        .table-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        @media (max-width: 768px) {
            .uh-dash .table-modern thead th,
            .uh-dash .table-modern tbody td { padding: 10px 12px; font-size: 0.78rem; }
            .uh-dash .table-modern tbody td:first-child,
            .uh-dash .table-modern thead th:first-child { padding-left: 12px; }
            .uh-dash .table-modern tbody td:last-child,
            .uh-dash .table-modern thead th:last-child { padding-right: 12px; }
            .uh-dash .avatar-modern { width: 32px; height: 32px; min-width: 32px; font-size: 0.65rem; }
            .uh-dash .badge-modern { padding: 3px 10px; font-size: 0.6rem; }
        }

        /* Receptionist-specific welcome */
        .receptionist-welcome {
            background: linear-gradient(135deg, #4f6ef7 0%, #3a56d4 100%);
            color: white;
            border-radius: 16px;
            padding: 24px 30px;
            margin-bottom: 24px;
            box-shadow: 0 8px 24px rgba(79,110,247,0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .receptionist-welcome h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.6rem;
            letter-spacing: -0.02em;
        }
        .receptionist-welcome h3 small {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.85;
            margin-left: 8px;
        }
        .receptionist-welcome .rec-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 18px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
        }
        .receptionist-welcome .rec-badge i {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<?php include '../header.php'; ?>
<?php include '../Sidebar.php'; ?>

<div class="uh-dash" id="uhDashboardRoot">

    <!-- Receptionist Welcome Banner -->
    <div class="receptionist-welcome">
        <div>
            <h3>👋 Welcome, <?php echo htmlspecialchars($user_name); ?></h3>
            <p style="margin:4px 0 0; opacity:0.9; font-size:0.95rem;">
                Here's what's happening in the hospital today.
            </p>
        </div>
        <div class="rec-badge">
            <i class="fas fa-concierge-bell"></i> 
            <?php echo htmlspecialchars($hospital['hospital_name'] ?? 'Hospital'); ?>
        </div>
    </div>

    <!-- KPI ROW 1 -->
    <div class="uh-grid uh-grid-4">
        <a href="#" class="stat-link">
            <div class="stat">
                <div class="ic soft-primary"><i class="fa-solid fa-users"></i></div>
                <div class="val"><?php echo number_format($total_patients); ?></div>
                <div class="lbl">Total Patients</div>
            </div>
        </a>
        <a href="#?date=<?= date('Y-m-d') ?>" class="stat-link">
            <div class="stat">
                <div class="ic soft-success"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="val"><?php echo number_format($today_appointments); ?></div>
                <div class="lbl">Today's Appointments</div>
            </div>
        </a>
        <a href="#?date=<?= date('Y-m-d') ?>&type=OPD" class="stat-link">
            <div class="stat">
                <div class="ic soft-info"><i class="fa-solid fa-stethoscope"></i></div>
                <div class="val"><?php echo number_format($today_opd); ?></div>
                <div class="lbl">Today's OPD</div>
            </div>
        </a>
        <a href="#?status=pending" class="stat-link">
            <div class="stat">
                <div class="ic soft-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="val"><?php echo number_format($pending_appointments); ?></div>
                <div class="lbl">Pending Appointments</div>
            </div>
        </a>
    </div>

    <!-- KPI ROW 2 -->
    <div class="uh-grid uh-grid-4">
        <a href="#?status=confirmed" class="stat-link">
            <div class="stat">
                <div class="ic soft-success"><i class="fa-solid fa-check-circle"></i></div>
                <div class="val"><?php echo number_format($confirmed_appointments); ?></div>
                <div class="lbl">Confirmed</div>
            </div>
        </a>
        <a href="#?status=cancelled" class="stat-link">
            <div class="stat">
                <div class="ic soft-danger"><i class="fa-solid fa-times-circle"></i></div>
                <div class="val"><?php echo number_format($cancelled_appointments); ?></div>
                <div class="lbl">Cancelled</div>
            </div>
        </a>
       <!-- <a href="billing.php?date=<?= date('Y-m-d') ?>" class="stat-link">
            <div class="stat">
                <div class="ic soft-purple"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="val"><?php echo number_format($today_bills_count); ?></div>
                <div class="lbl">Today's Billing Count</div>
            </div>
        </a>
        <a href="billing.php?date=<?= date('Y-m-d') ?>" class="stat-link">
            <div class="stat">
                <div class="ic soft-info"><i class="fa-solid fa-dollar-sign"></i></div>
                <div class="val">₦ <?php echo number_format($today_bills_amount, 0); ?></div>
                <div class="lbl">Today's Billing Amount</div>
            </div>
        </a>  -->
         <a href="#?type=followup&date=<?= date('Y-m-d') ?>" class="stat-link">
            <div class="stat">
                <div class="ic soft-warning"><i class="fa-solid fa-phone-alt"></i></div>
                <div class="val"><?php echo number_format($today_followups); ?></div>
                <div class="lbl">Today's Follow‑ups</div>
            </div>
        </a>
        <a href="#" class="stat-link">
            <div class="stat">
                <div class="ic soft-danger"><i class="fa-solid fa-bell"></i></div>
                <div class="val"><?php echo number_format($notifications); ?></div>
                <div class="lbl">Notifications</div>
            </div>
        </a>
    </div>

    <!-- KPI ROW 3 (Follow-ups & Notifications) -->
    <div class="uh-grid uh-grid-4" style="margin-bottom: 16px;">
       
        <!-- Two empty cards to keep grid alignment (optional) -->
        <div class="stat" style="visibility:hidden;"></div>
        <div class="stat" style="visibility:hidden;"></div>
    </div>

    <!-- CHARTS -->
    <div class="uh-grid uh-grid-2-1">
        <div class="card">
            <div class="card-title">Appointments Trend (Last 7 Days)</div>
            <div class="card-sub">Hospital‑wide volume</div>
            <div class="chart-box"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-title">OPD vs IPD</div>
            <div class="card-sub">Overall distribution</div>
            <div class="chart-box sm"><canvas id="opdIpdChart"></canvas></div>
        </div>
    </div>

    <!-- TABLES -->
    <div class="uh-grid uh-grid-7-5">
        <!-- Recent Patients -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="card-head" style="padding: 20px 24px 16px 24px; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <div class="card-title" style="font-size: 1.05rem;">
                        <i class="fas fa-user-plus" style="color: #22c55e; margin-right: 8px;"></i>
                        Recent Patient Registrations
                    </div>
                    <div class="card-sub" style="margin-bottom: 0; font-size: 0.8rem;">
                        <i class="fas fa-clock" style="margin-right: 4px;"></i>
                        Latest patients
                    </div>
                </div>
                <a href="patients.php" class="view-all" style="background: #f1f5f9; padding: 6px 16px; border-radius: 20px; font-size: 0.75rem;">
                    View all <i class="fas fa-arrow-right" style="margin-left: 4px;"></i>
                </a>
            </div>
            
            <div class="table-scroll" style="padding: 0 4px;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>Patient</th>
                            <th>Mobile</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentPatients)): ?>
                        <tr>
                            <td colspan="4" class="empty" style="padding: 40px 20px;">
                                <i class="fas fa-user-slash" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                                No patients yet
                            </td>
                        </tr>
                    <?php else: 
                        $avatarColors = ['avatar-blue', 'avatar-green', 'avatar-purple', 'avatar-pink', 'avatar-orange'];
                        $colorIndex = 0;
                        foreach ($recentPatients as $p): 
                            $avatarClass = $avatarColors[$colorIndex % count($avatarColors)];
                            $colorIndex++;
                            $initials = initials($p['patient_name'] ?? '?');
                    ?>
                        <tr class="clickable-row" onclick="window.location='view_patient.php?id=<?= $p['patient_id'] ?? 0 ?>'">
                            <td><span class="row-number-modern"><?= $colorIndex ?></span></td>
                            <td>
                                <div class="name-cell">
                                    <div class="avatar-modern <?= $avatarClass ?>">
                                        <?php if (!empty($p['patient_image'])): ?>
                                            <img src="../<?= htmlspecialchars($p['patient_image']) ?>" alt="<?= htmlspecialchars($p['patient_name']) ?>"
                                                 onerror="this.style.display='none'; this.parentElement.querySelector('.initials').classList.remove('hide');">
                                            <span class="initials hide"><?= $initials ?></span>
                                        <?php else: ?>
                                            <span class="initials"><?= $initials ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="view_patient.php?id=<?= $p['patient_id'] ?? 0 ?>" style="font-weight:600; color:#1e293b;">
                                            <?= htmlspecialchars($p['patient_name'] ?? 'N/A') ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($p['mobile']) && $p['mobile'] != '—'): ?>
                                    <a href="tel:<?= $p['mobile'] ?>" style="color:#475569; text-decoration:none;">
                                        <i class="fas fa-phone" style="color:#22c55e; margin-right:4px; font-size:0.7rem;"></i>
                                        <?= htmlspecialchars($p['mobile']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $statusMap = [
                                    'Active' => 'badge-active',
                                    'Inactive' => 'badge-inactive',
                                    'Pending' => 'badge-pending'
                                ];
                                $badgeClass = $statusMap[$p['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge-modern <?= $badgeClass ?>">
                                    <span class="status-dot <?= strtolower($p['status']) ?>"></span>
                                    <?= $p['status'] ?? 'N/A' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer-shimmer">
                <i class="fas fa-user-plus" style="margin-right: 6px;"></i>
                Latest <?= count($recentPatients) ?> patients
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="card-head" style="padding: 20px 24px 16px 24px; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <div class="card-title" style="font-size: 1.05rem;">
                        <i class="fas fa-calendar-week" style="color: #3b82f6; margin-right: 8px;"></i>
                        Upcoming Appointments
                    </div>
                    <div class="card-sub" style="margin-bottom: 0; font-size: 0.8rem;">
                        <i class="fas fa-clock" style="margin-right: 4px;"></i>
                        Next in line
                    </div>
                </div>
                <a href="appointments.php" class="view-all" style="background: #f1f5f9; padding: 6px 16px; border-radius: 20px; font-size: 0.75rem;">
                    View all <i class="fas fa-arrow-right" style="margin-left: 4px;"></i>
                </a>
            </div>
            
            <div class="table-scroll" style="padding: 0 4px;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>Patient</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($upcomingAppointments)): ?>
                        <tr>
                            <td colspan="5" class="empty" style="padding: 40px 20px;">
                                <i class="fas fa-calendar-times" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                                No upcoming appointments
                            </td>
                        </tr>
                    <?php else: 
                        $colorIndex = 0;
                        foreach ($upcomingAppointments as $a): 
                            $colorIndex++;
                            $initials = initials($a['patient_name'] ?? '?');
                            // random avatar for upcoming (or you could use patient image if available)
                            $avatarColors = ['avatar-blue', 'avatar-green', 'avatar-purple', 'avatar-pink', 'avatar-orange'];
                            $avatarClass = $avatarColors[$colorIndex % count($avatarColors)];
                    ?>
                        <tr class="clickable-row" onclick="window.location='view_appointment.php?id=<?= $a['appointment_id'] ?? 0 ?>'">
                            <td><span class="row-number-modern"><?= $colorIndex ?></span></td>
                            <td>
                                <div class="name-cell">
                                    <div class="avatar-modern <?= $avatarClass ?>" style="width:32px; height:32px; min-width:32px; font-size:0.7rem;">
                                        <span class="initials"><?= $initials ?></span>
                                    </div>
                                    <div>
                                        <a href="view_patient.php?id=<?= $a['patient_id'] ?? 0 ?>" style="font-weight:600; color:#1e293b;">
                                            <?= htmlspecialchars($a['patient_name'] ?? 'N/A') ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($a['doctor_name'] ?? '—') ?>
                            </td>
                            <td>
                                <?php 
                                $statusMap = [
                                    'Confirmed' => 'badge-confirmed',
                                    'Scheduled' => 'badge-scheduled',
                                    'Completed' => 'badge-completed',
                                    'Cancelled' => 'badge-cancelled',
                                ];
                                $badgeClass = $statusMap[$a['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge-modern <?= $badgeClass ?>">
                                    <span class="status-dot <?= strtolower($a['status']) ?>"></span>
                                    <?= $a['status'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer-shimmer">
                <i class="fas fa-calendar-check" style="margin-right: 6px;"></i>
                Next <?= count($upcomingAppointments) ?> appointments
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS (Receptionist) -->
    <div class="uh-grid uh-grid-2-1" style="margin-top: 16px;">
        <div class="card">
            <div class="card-title">Quick Actions</div>
            <div class="quick-links">
                <a href="add_patient.php" class="quick-link">
                    <i class="fa-solid fa-user-plus"></i> Register Patient
                </a>
                <a href="add_appointment.php" class="quick-link">
                    <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                </a>
                <a href="opd_checkin.php" class="quick-link">
                    <i class="fa-solid fa-clinic-medical"></i> OPD Check‑In
                </a>
              <!--  <a href="add_bill.php" class="quick-link">
                    <i class="fa-solid fa-file-invoice"></i> Create Bill
                </a>
                <a href="print_receipt.php" class="quick-link">
                    <i class="fa-solid fa-print"></i> Print Receipt
                </a>
                <a href="search_patient.php" class="quick-link">
                    <i class="fa-solid fa-search"></i> Search Patient
                </a>  -->
            </div>
        </div>
        <div class="card">
            <div class="card-title">Today's Summary</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
                <div style="background:#f1f5f9; border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--uhp);"><?= $today_opd ?></div>
                    <div style="font-size:0.75rem; color:var(--muted);">OPD</div>
                </div>
                <div style="background:#f1f5f9; border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--uhs);"><?= $today_appointments ?></div>
                    <div style="font-size:0.75rem; color:var(--muted);">Appointments</div>
                </div>
                <div style="background:#f1f5f9; border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--uhw);">₦ <?= number_format($today_bills_amount, 0) ?></div>
                    <div style="font-size:0.75rem; color:var(--muted);">Billing</div>
                </div>
                <div style="background:#f1f5f9; border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--uhu);"><?= $today_followups ?></div>
                    <div style="font-size:0.75rem; color:var(--muted);">Follow‑ups</div>
                </div>
            </div>
            <div style="margin-top:12px; background:var(--uhp-l); border-radius:12px; padding:12px; text-align:center; font-weight:500; color:var(--uhp);">
                <i class="fas fa-users"></i> Total Patients: <?= number_format($total_patients) ?>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trendLabels = <?= $trendLabelsJson ?>;
        const trendData = <?= $trendDataJson ?>;
        const opdCount = <?= $total_opd ?>;
        const ipdCount = <?= $total_ipd ?>;

        // Trend chart
        const trendEl = document.getElementById('trendChart');
        if (trendEl) {
            const ctx = trendEl.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 260);
            grad.addColorStop(0, 'rgba(79,110,247,.2)');
            grad.addColorStop(1, 'rgba(79,110,247,0)');

            new Chart(trendEl, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Appointments',
                        data: trendData,
                        borderColor: '#4f6ef7',
                        backgroundColor: grad,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4f6ef7',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f0f2f7' }, ticks: { precision: 0, font: { size: 11 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }

        // OPD/IPD doughnut
        const opdEl = document.getElementById('opdIpdChart');
        if (opdEl) {
            new Chart(opdEl, {
                type: 'doughnut',
                data: {
                    labels: ['OPD', 'IPD'],
                    datasets: [{
                        data: [opdCount, ipdCount],
                        backgroundColor: ['#4f6ef7', '#2fb5d2'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, font: { size: 11, weight: '500' }, usePointStyle: true, padding: 14 }
                        }
                    }
                }
            });
        }
    });
</script>

</body>
</html>