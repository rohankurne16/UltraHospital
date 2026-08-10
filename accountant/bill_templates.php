<?php
// ============================================================
// BILL TEMPLATES – List, create, edit templates
// ============================================================

session_start();
require_once '../config/hospital.php';
require_once '../config/permission.php';

if (!hasPermission('billing-view')) {
    header("Location: billing_list.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission for create/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = $_POST['template_name'];
    $template_content = $_POST['template_content'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($action == 'edit' && $id) {
        $update = "UPDATE bill_templates SET template_name = '$template_name', template_content = '$template_content', is_default = $is_default, modified_at = NOW() WHERE id = $id AND hospital_id = $hospital_id";
        if ($conn->query($update)) {
            echo "<script>alert('Template updated'); window.location='bill_templates.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    } else {
        $insert = "INSERT INTO bill_templates (hospital_id, template_name, template_content, is_default) VALUES ($hospital_id, '$template_name', '$template_content', $is_default)";
        if ($conn->query($insert)) {
            echo "<script>alert('Template created'); window.location='bill_templates.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    }
}

// Fetch templates
$query = "SELECT * FROM bill_templates WHERE hospital_id = $hospital_id AND delete_flag = 0 ORDER BY is_default DESC, template_name";
$result = $conn->query($query);

// Fetch single template for edit
$edit_template = null;
if ($action == 'edit' && $id) {
    $editQ = "SELECT * FROM bill_templates WHERE id = $id AND hospital_id = $hospital_id AND delete_flag = 0";
    $editR = $conn->query($editQ);
    if ($editR && $editR->num_rows > 0) {
        $edit_template = $editR->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Templates - Accountant</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?? 'favicon.ico'; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; background:#f0f4f8; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        @media (max-width:768px){ .main-content{ padding:16px; } }

        .greeting-gradient {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border-radius:16px;
            padding:20px 28px;
            margin-bottom:24px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
        }
        .greeting-gradient h1 { color:white; font-weight:700; font-size:22px; }
        .greeting-gradient p { color:rgba(255,255,255,0.7); font-size:14px; }
        .greeting-gradient .btn { padding:8px 20px; border-radius:8px; border:none; font-weight:600; cursor:pointer; background:#ed8936; color:white; transition:0.2s; }
        .greeting-gradient .btn:hover { background:#d97706; }

        .section-card { background:white; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; }
        .section-card .card-header { padding:16px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
        .section-card .card-header h5 { font-weight:700; font-size:16px; display:flex; align-items:center; gap:8px; }
        .section-card .card-header h5 i { color:#ed8936; }
        .section-card .card-body { padding:20px 24px; }

        .table-responsive { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:13px; min-width:700px; }
        thead th { background:#f8fafc; color:#64748b; font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:0.03em; padding:12px 16px; text-align:left; border-bottom:2px solid #e2e8f0; }
        tbody td { padding:12px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        tbody tr:hover { background:#f8fafc; }

        .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-default { background:#d1fae5; color:#065f46; }
        .badge-secondary { background:#f1f5f9; color:#64748b; }

        .action-btn { padding:4px 10px; border-radius:6px; border:none; background:transparent; transition:0.2s; cursor:pointer; }
        .action-btn:hover { background:#eef2ff; }

        .card-form { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; max-width:700px; margin:0 auto; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; font-size:13px; color:#475569; margin-bottom:4px; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#f8fafc; }
        .form-control:focus { border-color:#ed8936; outline:none; box-shadow:0 0 0 3px rgba(237,137,54,0.1); }
        textarea.form-control { min-height:150px; resize:vertical; }
        .flex { display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-end; margin-top:16px; }
        .btn { padding:10px 24px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#ed8936; color:white; }
        .btn-primary:hover { background:#d97706; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .btn-secondary:hover { background:#cbd5e1; }
        .checkbox { display:flex; align-items:center; gap:8px; }
        .checkbox input { width:18px; height:18px; cursor:pointer; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../Sidebar.php'; ?>
        <main class="main-content">
            <?php if ($action == 'create' || ($action == 'edit' && $edit_template)): ?>
            <!-- Create / Edit Form -->
            <div class="greeting-gradient">
                <div>
                    <h1><i class="fas fa-<?php echo $action=='edit'?'edit':'plus-circle'; ?>"></i> <?php echo $action=='edit'?'Edit':'Create'; ?> Bill Template</h1>
                    <p><?php echo $action=='edit'?'Update template details':'Add a new template'; ?></p>
                </div>
                <a href="bill_templates.php" class="btn" style="background:rgba(255,255,255,0.15); color:white;">Back to Templates</a>
            </div>

            <div class="card-form">
                <form method="POST">
                    <div class="form-group">
                        <label>Template Name</label>
                        <input type="text" class="form-control" name="template_name" value="<?php echo $edit_template ? htmlspecialchars($edit_template['template_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Template Content (HTML)</label>
                        <textarea class="form-control" name="template_content" rows="6"><?php echo $edit_template ? htmlspecialchars($edit_template['template_content']) : ''; ?></textarea>
                        <small style="color:#94a3b8; font-size:12px;">Use placeholders: {patient_name}, {bill_no}, {total}, {paid}, {pending}, {date}</small>
                    </div>
                    <div class="form-group checkbox">
                        <input type="checkbox" name="is_default" <?php echo ($edit_template && $edit_template['is_default']) ? 'checked' : ''; ?>>
                        <label style="margin-bottom:0;">Set as default template</label>
                    </div>
                    <div class="flex">
                        <a href="bill_templates.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $action=='edit'?'Update':'Create'; ?></button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <!-- List Templates -->
            <div class="greeting-gradient">
                <div>
                    <h1><i class="fas fa-file-invoice"></i> Bill Templates</h1>
                    <p>Manage bill templates for printing</p>
                </div>
                <a href="bill_templates.php?action=create" class="btn"><i class="fas fa-plus"></i> New Template</a>
            </div>

            <div class="section-card">
                <div class="card-header">
                    <h5><i class="fas fa-list-ul"></i> All Templates</h5>
                    <span class="badge-count" style="background:#f1f5f9; padding:2px 12px; border-radius:20px; font-size:12px; color:#4a5568;"><?php echo $result->num_rows; ?> records</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Default</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['template_name']); ?></strong></td>
                                        <td><?php echo $row['is_default'] ? '<span class="badge badge-default">Default</span>' : '<span class="badge badge-secondary">—</span>'; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="bill_templates.php?action=edit&id=<?php echo $row['id']; ?>" class="action-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="bill_templates.php?action=delete&id=<?php echo $row['id']; ?>" class="action-btn" title="Delete" onclick="return confirm('Delete this template?')"><i class="fas fa-trash" style="color:#e53e3e;"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="empty" style="padding:40px; text-align:center; color:#94a3b8;">No templates found. Create your first template.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>