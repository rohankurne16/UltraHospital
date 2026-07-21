<?php
// ============================================================
// PERMISSION MANAGEMENT PAGE (permissions.php)
// ============================================================
require_once __DIR__ . '/../config/permission.php';

// Security: Only Super Admin can access this page
if (!$is_super_admin) {
    header("Location: dashboard.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// ============================================================
// HANDLE ADD NEW PERMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_permission'])) {
    $permission_group = mysqli_real_escape_string($conn, $_POST['permission_group']);
    $permission_name = mysqli_real_escape_string($conn, $_POST['permission_name']);
    $permission_slug = mysqli_real_escape_string($conn, $_POST['permission_slug']);
    $permission_icon = mysqli_real_escape_string($conn, $_POST['permission_icon'] ?? 'fa-circle');
    $menu_order = intval($_POST['menu_order'] ?? 0);
    $is_sidebar = isset($_POST['is_sidebar']) ? 1 : 0;
    $is_dashboard = isset($_POST['is_dashboard']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

    // Check if permission slug already exists
    $check_query = "SELECT permission_id FROM permissions WHERE permission_slug = '$permission_slug' AND delete_flag = 0";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error_msg = "Permission with slug '$permission_slug' already exists!";
    } else {
        $insert_query = "INSERT INTO permissions (
            permission_group,
            parent_id,
            permission_name,
            permission_slug,
            permission_icon,
            menu_order,
            is_sidebar,
            is_dashboard,
            description,
            is_system,
            sort_order,
            created_at,
            modified_at,
            delete_flag
        ) VALUES (
            '$permission_group',
            NULL,
            '$permission_name',
            '$permission_slug',
            '$permission_icon',
            '$menu_order',
            '$is_sidebar',
            '$is_dashboard',
            '$description',
            0,
            '$sort_order',
            NOW(),
            NOW(),
            0
        )";

        if (mysqli_query($conn, $insert_query)) {
            logAudit('Permission', "Added new permission: $permission_name (Slug: $permission_slug)");
            $success_msg = "Permission added successfully!";
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch all hospitals for filter
$hospitals_query = "SELECT hospital_id, hospital_name FROM hospital_master WHERE (delete_flag = 0 OR delete_flag IS NULL) AND status = 'Active' ORDER BY hospital_name";
$hospitals_result = mysqli_query($conn, $hospitals_query);
$hospitals = [];
if ($hospitals_result && mysqli_num_rows($hospitals_result) > 0) {
    while ($row = mysqli_fetch_assoc($hospitals_result)) {
        $hospitals[] = $row;
    }
}

// Get selected hospital filter
$selected_hospital = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

// Fetch all roles (with optional hospital filter)
$roles_query = "SELECT role_id, role_name, description FROM roles WHERE (delete_flag = 0 OR delete_flag IS NULL)";
if ($selected_hospital > 0) {
    $roles_query .= " AND (hospital_id IS NULL OR hospital_id = $selected_hospital)";
}
$roles_query .= " ORDER BY role_name";
$roles_res = mysqli_query($conn, $roles_query);
$roles = [];
if ($roles_res && mysqli_num_rows($roles_res) > 0) {
    while ($row = mysqli_fetch_assoc($roles_res)) {
        $roles[] = $row;
    }
}

// Handle Form Submission: Save Permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $selected_hospital = intval($_POST['hospital_id']);
    $selected_role_id = intval($_POST['role_id']);
    $assigned_permissions = $_POST['permissions'] ?? [];

    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Remove existing permissions for this role
        $delete_query = "DELETE FROM role_permissions WHERE hospital_id = ? AND role_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $selected_hospital, $selected_role_id);
        $stmt->execute();

        // Insert new permissions
        if (!empty($assigned_permissions)) {
            $insert_query = "INSERT INTO role_permissions (hospital_id, role_id, permission_id) VALUES (?, ?, ?)";

            $stmt = $conn->prepare($insert_query);

                foreach ($assigned_permissions as $p_id) {
                    $p_id = intval($p_id);
                    $stmt->bind_param("iii", $selected_hospital, $selected_role_id, $p_id);
                    $stmt->execute();
                }
        }
        mysqli_commit($conn);
        $success_msg = "Permissions updated successfully!";
        
        // Refresh role permissions
        $role_permissions = [];
        $rp_query = "SELECT permission_id FROM role_permissions WHERE hospital_id = ? AND role_id = ?";
        $stmt = $conn->prepare($rp_query);
        $stmt->bind_param("ii", $selected_hospital, $selected_role_id);
        $stmt->execute();
        $rp_res = $stmt->get_result();
        while ($row = $rp_res->fetch_assoc()) {
            $role_permissions[] = $row['permission_id'];
        }
        $stmt->close();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Error updating permissions: " . $e->getMessage();
    }
}

// Fetch all permissions grouped by category
$perm_res = mysqli_query($conn, "SELECT * FROM permissions WHERE (delete_flag = 0 OR delete_flag IS NULL) ORDER BY permission_group, permission_name");
$all_permissions = [];
while ($row = mysqli_fetch_assoc($perm_res)) {
    $all_permissions[$row['permission_group']][] = $row;
}

// Get permissions for selected role
$selected_role = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
$role_permissions = [];
if ($selected_role > 0) {
    $rp_query = "SELECT permission_id FROM role_permissions WHERE hospital_id = ? AND role_id = ?";
    $stmt = $conn->prepare($rp_query);
    $stmt->bind_param("ii", $selected_hospital, $selected_role_id);
    $stmt->execute();
    $rp_res = $stmt->get_result();
    while ($row = $rp_res->fetch_assoc()) {
        $role_permissions[] = $row['permission_id'];
    }
    $stmt->close();
}

// Get role name for display
$role_name = '';
if ($selected_role > 0) {
   $role_query = "SELECT role_name FROM roles WHERE role_id = $selected_role"; 
    $role_result = mysqli_query($conn, $role_query);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_data = mysqli_fetch_assoc($role_result);
        $role_name = $role_data['role_name'];
    }
}

// Common Font Awesome Icons for dropdown
$fa_icons = [
    'fa-circle' => 'Circle',
    'fa-user' => 'User',
    'fa-users' => 'Users',
    'fa-user-md' => 'User MD',
    'fa-user-plus' => 'User Plus',
    'fa-user-edit' => 'User Edit',
    'fa-user-times' => 'User Times',
    'fa-user-injured' => 'User Injured',
    'fa-hospital' => 'Hospital',
    'fa-hospital-user' => 'Hospital User',
    'fa-plus-circle' => 'Plus Circle',
    'fa-edit' => 'Edit',
    'fa-trash' => 'Trash',
    'fa-trash-alt' => 'Trash Alt',
    'fa-cog' => 'Settings',
    'fa-building' => 'Building',
    'fa-plus' => 'Plus',
    'fa-pen' => 'Pen',
    'fa-bed' => 'Bed',
    'fa-door-open' => 'Door Open',
    'fa-flask' => 'Flask',
    'fa-pills' => 'Pills',
    'fa-calendar-check' => 'Calendar Check',
    'fa-stethoscope' => 'Stethoscope',
    'fa-prescription' => 'Prescription',
    'fa-file-medical' => 'File Medical',
    'fa-cash-register' => 'Cash Register',
    'fa-boxes' => 'Boxes',
    'fa-file-invoice-dollar' => 'Invoice Dollar',
    'fa-chart-bar' => 'Chart Bar',
    'fa-chart-pie' => 'Chart Pie',
    'fa-lock' => 'Lock',
    'fa-unlock' => 'Unlock',
    'fa-key' => 'Key',
    'fa-shield-alt' => 'Shield',
    'fa-bell' => 'Bell',
    'fa-envelope' => 'Envelope',
    'fa-phone' => 'Phone',
    'fa-address-card' => 'Address Card',
    'fa-map-marker-alt' => 'Map Marker',
    'fa-clock' => 'Clock',
    'fa-calendar' => 'Calendar',
    'fa-file-alt' => 'File Alt',
    'fa-file-pdf' => 'File PDF',
    'fa-file-word' => 'File Word',
    'fa-file-excel' => 'File Excel',
    'fa-print' => 'Print',
    'fa-download' => 'Download',
    'fa-upload' => 'Upload',
    'fa-search' => 'Search',
    'fa-filter' => 'Filter',
    'fa-save' => 'Save',
    'fa-undo' => 'Undo',
    'fa-redo' => 'Redo',
    'fa-sync' => 'Sync',
    'fa-times' => 'Times',
    'fa-check' => 'Check',
    'fa-check-circle' => 'Check Circle',
    'fa-exclamation-circle' => 'Exclamation Circle',
    'fa-info-circle' => 'Info Circle',
    'fa-question-circle' => 'Question Circle',
    'fa-arrow-left' => 'Arrow Left',
    'fa-arrow-right' => 'Arrow Right',
    'fa-arrow-up' => 'Arrow Up',
    'fa-arrow-down' => 'Arrow Down',
    'fa-chevron-left' => 'Chevron Left',
    'fa-chevron-right' => 'Chevron Right',
    'fa-chevron-up' => 'Chevron Up',
    'fa-chevron-down' => 'Chevron Down',
    'fa-home' => 'Home',
    'fa-dashboard' => 'Dashboard',
    'fa-pencil-alt' => 'Pencil Alt',
    'fa-copy' => 'Copy',
    'fa-paste' => 'Paste',
    'fa-clipboard' => 'Clipboard',
    'fa-list' => 'List',
    'fa-table' => 'Table',
    'fa-th-large' => 'Th Large',
    'fa-th-list' => 'Th List',
    'fa-eye' => 'Eye',
    'fa-eye-slash' => 'Eye Slash',
    'fa-hide' => 'Hide',
    'fa-show' => 'Show',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Permissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; display: flex; }
        
        body.dark .card {
    background: #1a1a1a;
    border-color: #2a2a2a;
}

body.dark .card h3 {
    color: #f1f5f9 !important;
}

body.dark .form-label {
    color: #d1d5db;
}

body.dark .form-control {
    background: #1e1e1e;
    border-color: #2a2a2a;
    color: #f1f5f9;
}

body.dark .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    background: #2a2a2a;
}

body.dark .form-control::placeholder {
    color: #6b7280;
}

body.dark .icon-preview {
    background: #2a2a2a;
    color: #d1d5db;
}

body.dark .checkbox-group label {
    color: #d1d5db;
}

body.dark .checkbox-group input[type="checkbox"] {
    accent-color: #3b82f6;
}

body.dark .btn-secondary {
    background: #2a2a2a;
    color: #d1d5db;
    border-color: #3a3a3a;
}

body.dark .btn-secondary:hover {
    background: #3a3a3a;
    color: #f1f5f9;
}

body.dark .btn-secondary i {
    color: #9ca3af;
}

body.dark small {
    color: #6b7280 !important;
}

body.dark .required {
    color: #ef4444;
}

body.dark #addPermissionForm {
    border-top-color: #2a2a2a !important;
}

/* Toggle Button Styles */
.btn-toggle {
    padding: 0.5rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
}

body.dark .btn-toggle {
    background: #2a2a2a;
    border-color: #3a3a3a;
    color: #d1d5db;
}

.btn-toggle:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

body.dark .btn-toggle:hover {
    background: #3a3a3a;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.btn-toggle i {
    transition: transform 0.3s ease;
}

.btn-toggle.active i {
    transform: rotate(180deg);
}

/* Card Header with Gradient Accent */
.card-header-accent {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e2e8f0;
}

body.dark .card-header-accent {
    border-bottom-color: #2a2a2a;
}

.card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.dark .card-title {
    color: #f1f5f9;
}

.card-title .title-icon {
    color: #3b82f6;
    font-size: 1.2rem;
}

/* Form Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    animation: fadeSlideIn 0.4s ease;
}

@keyframes fadeSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.full-width {
    grid-column: 1 / -1;
}

/* Form Label */
.form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.4rem;
    letter-spacing: 0.3px;
}

.form-label .required {
    color: #ef4444;
    margin-left: 2px;
}

/* Form Control */
.form-control {
    width: 100%;
    padding: 0.6rem 0.9rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.3s ease;
    background: #f8fafc;
    color: #1e293b;
    font-family: 'Inter', sans-serif;
}

.form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    background: #ffffff;
}

.form-control::placeholder {
    color: #94a3b8;
    font-weight: 400;
}

.form-control:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Form Help Text */
.form-help {
    color: #94a3b8;
    font-size: 0.7rem;
    margin-top: 0.3rem;
    display: block;
}

/* Icon Preview */
.icon-preview {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.35rem 0.9rem;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #475569;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.icon-preview i {
    font-size: 1.1rem;
    color: #3b82f6;
    transition: all 0.3s ease;
}

.icon-preview .icon-label {
    font-weight: 500;
}

/* Checkbox Group */
.checkbox-group {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    padding-top: 0.3rem;
    flex-wrap: wrap;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #475569;
    cursor: pointer;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.checkbox-group label:hover {
    background: #f1f5f9;
}

body.dark .checkbox-group label:hover {
    background: #2a2a2a;
}

.checkbox-group input[type="checkbox"] {
    width: 17px;
    height: 17px;
    accent-color: #3b82f6;
    cursor: pointer;
    flex-shrink: 0;
}

/* Custom Checkbox Toggle Style */
.checkbox-toggle {
    position: relative;
    width: 44px;
    height: 24px;
    background: #cbd5e1;
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    flex-shrink: 0;
}

body.dark .checkbox-toggle {
    background: #3a3a3a;
}

.checkbox-toggle.active {
    background: #3b82f6;
}

.checkbox-toggle .toggle-dot {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.checkbox-toggle.active .toggle-dot {
    left: 22px;
}

/* Button Styles */
.btn {
    padding: 0.6rem 1.4rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    font-family: 'Inter', sans-serif;
}

.btn-success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.25);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.35);
}

.btn-success:active {
    transform: translateY(0px);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.btn-secondary:active {
    transform: translateY(0px);
}

.btn-success i,
.btn-secondary i {
    font-size: 0.9rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 0.5rem;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .full-width {
        grid-column: 1;
    }
}

@media (max-width: 768px) {
    .card-header-accent {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .btn-toggle {
        width: 100%;
        justify-content: center;
    }
    
    .checkbox-group {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .form-grid {
        gap: 0.75rem;
    }
    
    .form-control {
        font-size: 0.85rem;
        padding: 0.5rem 0.7rem;
    }
    
    .icon-preview {
        font-size: 0.75rem;
        padding: 0.25rem 0.6rem;
    }
    
    .btn {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
}

/* Scroll Animation */
.form-scroll-enter {
    animation: fadeSlideIn 0.4s ease;
}

/* Loading State for Button */
.btn-loading {
    opacity: 0.7;
    pointer-events: none;
}

.btn-loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Tooltip/Helper Icon */
.helper-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #64748b;
    font-size: 0.6rem;
    font-weight: 700;
    cursor: help;
    margin-left: 0.3rem;
    transition: all 0.2s ease;
}

.helper-icon:hover {
    background: #3b82f6;
    color: #ffffff;
}

body.dark .helper-icon {
    background: #2a2a2a;
    color: #9ca3af;
}

body.dark .helper-icon:hover {
    background: #3b82f6;
    color: #ffffff;
}

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            width: calc(100% - 260px);
            min-height: 100vh;
        }
        
        .card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert i { font-size: 1.1rem; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        .page-header p {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .page-header .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-header .header-left i {
            color: #3b82f6;
            font-size: 1.5rem;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-row .field {
            flex: 1;
            min-width: 180px;
        }
        .filter-row label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-row .form-control {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .filter-row .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59,130,246,0.3);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34,197,94,0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239,68,68,0.3);
        }
        
        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.6rem;
        }
        .perm-group {
            margin-bottom: 2rem;
        }
        .perm-group-title {
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            color: #3b82f6;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .perm-group-title .count {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 400;
            background: #f1f5f9;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
        }
        .perm-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            padding: 0.5rem 0.7rem;
            border-radius: 6px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .perm-item:hover {
            background: #eff6ff;
            border-color: #dbeafe;
        }
        .perm-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
            flex-shrink: 0;
        }
        .perm-item span {
            font-size: 0.85rem;
            color: #475569;
        }
        .perm-item .perm-icon {
            color: #3b82f6;
            font-size: 0.8rem;
            margin-right: 0.2rem;
        }
        
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            background: #dbeafe;
            color: #3b82f6;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .sticky-footer {
            position: sticky;
            bottom: 2rem;
            text-align: right;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 3rem;
            color: #e2e8f0;
            display: block;
            margin-bottom: 1rem;
        }
        .empty-state h3 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .hospital-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Add Permission Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-grid .full-width {
            grid-column: 1 / -1;
        }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.3rem;
        }
        .form-label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        .icon-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 0.8rem;
            background: #f1f5f9;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #475569;
        }
        .icon-preview i {
            font-size: 1rem;
            color: #3b82f6;
        }
        .checkbox-group {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            padding-top: 0.3rem;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 200px; padding: 1rem; width: calc(100% - 200px); }
            .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 70px; padding: 1rem; width: calc(100% - 70px); }
            .perm-grid { grid-template-columns: 1fr; }
            .filter-row .field { min-width: 140px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .form-grid { grid-template-columns: 1fr; }
            .checkbox-group { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'header.php' ?>

<?php include 'sidebar.php'; ?>

<div class="main-content" style="margin-top: 80px;">
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
            <i class="fas fa-lock"></i>
            <div>
                <h1>Permission Management</h1>
                <p>Manage role-based access control for all users</p>
            </div>
        </div>
        
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- ADD PERMISSION FORM -->
    <!-- ============================================================ -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <h3 style="font-size:1.1rem; color:#1e293b; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-plus-circle" style="color:#3b82f6;"></i>
                Add New Permission
            </h3>
            <button type="button" class="btn btn-secondary" onclick="toggleAddForm()">
                <i class="fas fa-chevron-down" id="toggleIcon"></i> <span id="toggleText">Show Form</span>
            </button>
        </div>
        
        <div id="addPermissionForm" style="display:none; padding-top:1rem; border-top:1px solid #e2e8f0;">
            <form method="POST" onsubmit="return validatePermissionForm()">
                <div class="form-grid">
                    <div>
                        <label class="form-label">Permission Group <span class="required">*</span></label>
                        <input type="text" name="permission_group" class="form-control" placeholder="e.g., Dashboard, Patients, Reports" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Permission Name <span class="required">*</span></label>
                        <input type="text" name="permission_name" class="form-control" placeholder="e.g., Dashboard View" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Permission Slug <span class="required">*</span></label>
                        <input type="text" name="permission_slug" class="form-control" placeholder="e.g., dashboard-view" required>
                        <small style="color:#94a3b8; font-size:0.7rem;">Unique identifier (lowercase, hyphens only)</small>
                    </div>
                    
                    <div>
                        <label class="form-label">Font Awesome Icon</label>
                        <select name="permission_icon" class="form-control" id="iconSelect" onchange="updateIconPreview()">
                            <option value="fa-circle">fa-circle</option>
                            <?php foreach ($fa_icons as $icon => $label): ?>
                                <option value="<?php echo $icon; ?>"><?php echo $icon; ?> - <?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="margin-top:0.4rem;">
                            <span class="icon-preview">
                                <i id="iconPreview" class="fas fa-circle"></i> 
                                <span id="iconPreviewText">fa-circle</span>
                            </span>
                        </div>
                    </div>
                    
                   
                    
                    <div class="full-width">
                        <label class="form-label">Visibility Settings</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="is_sidebar" value="1" checked>
                                Show in Sidebar
                            </label>
                            <label>
                                <input type="checkbox" name="is_dashboard" value="1" checked>
                                Show in Dashboard
                            </label>
                        </div>
                    </div>
                    
                    <div class="full-width" style="display:flex; gap:0.75rem; padding-top:0.5rem;">
                        <button type="submit" name="add_permission" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Permission
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card">
        <form method="GET" id="filterForm" class="filter-row">
            <div class="field">
                <label><i class="fas fa-hospital" style="color:#3b82f6;"></i> Hospital</label>
                <select name="hospital_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Hospitals</option>
                    <?php foreach ($hospitals as $h): ?>
                        <option value="<?php echo $h['hospital_id']; ?>" <?php echo ($selected_hospital == $h['hospital_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['hospital_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label><i class="fas fa-user-tag" style="color:#3b82f6;"></i> Role</label>
                <select name="role_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['role_id']; ?>" <?php echo ($selected_role == $r['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="flex:0.5; min-width:150px;">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('filterForm').reset(); this.form.submit();">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </form>
        
        <?php if ($selected_hospital > 0): ?>
        <div style="margin-top:0.75rem; display:flex; align-items:center; gap:0.5rem;">
            <span class="hospital-badge">
                <i class="fas fa-hospital"></i> 
                <?php 
                    foreach ($hospitals as $h) {
                        if ($h['hospital_id'] == $selected_hospital) {
                            echo htmlspecialchars($h['hospital_name']);
                            break;
                        }
                    }
                ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($selected_role > 0): ?>
        <!-- Role Info -->
        <div class="card" style="padding:0.8rem 1.25rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <span class="role-badge">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($role_name); ?>
                </span>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()">
                        <i class="fas fa-check-double"></i> Select All
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()">
                        <i class="fas fa-times"></i> Deselect All
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Permissions Form -->
        <form method="POST" id="permissionForm">
            <input type="hidden" name="hospital_id" value="<?php echo $selected_hospital; ?>">
            <input type="hidden" name="role_id" value="<?php echo $selected_role; ?>">
            <input type="hidden" name="save_permissions" value="1">
            
            <?php foreach ($all_permissions as $group => $perms): ?>
                <div class="card perm-group">
                    <div class="perm-group-title">
                        <i class="fas fa-folder-open"></i>
                        <?php echo htmlspecialchars($group); ?>
                        <span class="count"><?php echo count($perms); ?> permissions</span>
                    </div>
                    <div class="perm-grid">
                        <?php foreach ($perms as $p): ?>
                            <div class="perm-item">
                                <input type="checkbox" name="permissions[]" value="<?php echo $p['permission_id']; ?>" 
                                    id="perm_<?php echo $p['permission_id']; ?>"
                                    <?php echo in_array($p['permission_id'], $role_permissions) ? 'checked' : ''; ?>>
                                <label for="perm_<?php echo $p['permission_id']; ?>" style="display:flex; align-items:center; gap:0.4rem; cursor:pointer; width:100%;">
                                    <?php if (!empty($p['permission_icon'])): ?>
                                        <i class="fas <?php echo $p['permission_icon']; ?> perm-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle perm-icon"></i>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($p['permission_name']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="sticky-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="card empty-state">
            <i class="fas fa-hand-pointer"></i>
            <h3>Select a Role</h3>
            <p>Choose a role from the dropdown above to view and manage its permissions.</p>
            <p style="font-size:0.85rem; margin-top:0.5rem; color:#94a3b8;">
                <?php if ($selected_hospital > 0): ?>
                    Showing roles for selected hospital only.
                <?php else: ?>
                    Showing all roles across all hospitals.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle Add Permission Form
function toggleAddForm() {
    const form = document.getElementById('addPermissionForm');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');
    
    if (form.style.display === 'none') {
        form.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
        text.textContent = 'Hide Form';
    } else {
        form.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        text.textContent = 'Show Form';
    }
}

// Update Icon Preview
function updateIconPreview() {
    const select = document.getElementById('iconSelect');
    const icon = document.getElementById('iconPreview');
    const text = document.getElementById('iconPreviewText');
    const selectedValue = select.value;
    
    icon.className = 'fas ' + selectedValue;
    text.textContent = selectedValue;
}

// Validate Permission Form
function validatePermissionForm() {
    const group = document.querySelector('input[name="permission_group"]').value.trim();
    const name = document.querySelector('input[name="permission_name"]').value.trim();
    const slug = document.querySelector('input[name="permission_slug"]').value.trim();
    
    if (!group || !name || !slug) {
        alert('Please fill in all required fields (Group, Name, and Slug).');
        return false;
    }
    
    // Check slug format (only lowercase letters, numbers, and hyphens)
    if (!/^[a-z0-9-]+$/.test(slug)) {
        alert('Permission slug can only contain lowercase letters, numbers, and hyphens.');
        return false;
    }
    
    return true;
}

// Select/Deselect All
function selectAll() {
    document.querySelectorAll('.perm-item input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.perm-item input[type="checkbox"]').forEach(cb => cb.checked = false);
}

// Auto-submit on hospital/role change
document.addEventListener('DOMContentLoaded', function() {
    const hospitalSelect = document.querySelector('select[name="hospital_id"]');
    const roleSelect = document.querySelector('select[name="role_id"]');
    
    if (hospitalSelect) {
        hospitalSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});

// Confirm before saving
document.getElementById('permissionForm')?.addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to save these permissions?')) {
        e.preventDefault();
    }
});

// Auto-generate slug from permission name
document.querySelector('input[name="permission_name"]')?.addEventListener('input', function() {
    const slugInput = document.querySelector('input[name="permission_slug"]');
    if (slugInput && !slugInput.dataset.userModified) {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        slugInput.value = slug;
    }
});

// Mark slug as user-modified when user types in it
document.querySelector('input[name="permission_slug"]')?.addEventListener('input', function() {
    this.dataset.userModified = 'true';
});

// Initialize icon preview
document.addEventListener('DOMContentLoaded', function() {
    updateIconPreview();
});
</script>

</body>
</html>