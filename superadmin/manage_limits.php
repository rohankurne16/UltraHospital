<?php
// manage_limits.php – Super Admin only
session_start();
require_once '../config/permission.php';
checkSuperAdminLogin();
require_once '../config/subscription_limits.php';

$theme = $_SESSION['theme'] ?? 'light';
$success_msg = $error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_limits'])) {
    $hospital_id = (int)$_POST['hospital_id'];
    $max_departments = (int)$_POST['max_departments'];
    $max_doctors = (int)$_POST['max_doctors'];
    $max_staff = (int)$_POST['max_staff'];
    
    if ($hospital_id > 0) {
        if (updateHospitalLimits($hospital_id, $max_departments, $max_doctors, $max_staff)) {
            $success_msg = "Limits updated successfully for Hospital ID $hospital_id.";
            logAudit('Subscription', "Super Admin updated limits for Hospital ID $hospital_id: Dep=$max_departments, Doc=$max_doctors, Staff=$max_staff");
        } else {
            $error_msg = "Failed to update limits. Please try again.";
        }
    }
}

// Fetch all hospitals with their current limits
$query = "SELECT h.hospital_id, h.hospital_name, 
                 COALESCE(s.max_departments, 2) AS max_departments,
                 COALESCE(s.max_doctors, 10) AS max_doctors,
                 COALESCE(s.max_staff, 10) AS max_staff
          FROM hospital_master h
          LEFT JOIN subscriptions s ON h.hospital_id = s.hospital_id AND s.delete_flag = 0
          WHERE h.delete_flag = 0
          ORDER BY h.hospital_name";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscription Limits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ===== BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        
        /* ===== MAIN LAYOUT ===== */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            margin-top: 70px;
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
        }

        /* ===== BACK BUTTON (matches your theme) ===== */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            background: #f1f5f9;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            margin-bottom: 1.2rem;
        }
        .back-btn:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        /* ===== CONTENT CARD ===== */
        .content-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 1.2rem;
        }

        /* ===== CARD TITLE ===== */
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-title i {
            color: #3b82f6;
            font-size: 1.2rem;
        }

        /* ===== ALERTS ===== */
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ===== TABLE ===== */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
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
            padding: 0.7rem 0.8rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        td {
            padding: 0.7rem 0.8rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
            color: #1e293b;
            vertical-align: middle;
        }
        tbody tr:hover {
            background: #f8fafc;
        }

        /* ===== FORM CONTROLS ===== */
        .form-control {
            padding: 0.4rem 0.6rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            width: 70px;
            font-size: 0.85rem;
            transition: all 0.2s;
            background: #fcfdfe;
        }
        .form-control:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.1);
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: #3b82f6;
            color: #fff;
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* ===== INFO BOX ===== */
        .info-box {
            margin-top: 1rem;
            padding: 0.8rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #64748b;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .info-box i {
            color: #3b82f6;
            margin-top: 0.1rem;
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 0.8rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.2rem;
            align-items: center;
        }
        .stats-bar .stat-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
        }
        .stats-bar .stat-item .num {
            font-weight: 700;
            color: #1e293b;
        }
        .stats-bar .stat-item i {
            color: #3b82f6;
            width: 1.2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <?php include 'header.php'; ?>

        <!-- ===== BACK BUTTON ===== -->
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- ===== ALERTS ===== -->
        <?php if ($success_msg): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- ===== MAIN CARD ===== -->
        <div class="content-card">
            <div class="card-title">
                <i class="fas fa-edit"></i> Manage Subscription Limits
            </div>
            <p style="color:#64748b; font-size:0.9rem; margin-bottom:1.2rem;">
                Update the maximum number of <strong>Departments, Doctors, and Staff</strong> each hospital can create.
                These limits are enforced when Hospital Admins try to add new resources.
            </p>

            <!-- ===== STATS ===== -->
            <div class="stats-bar">
                <div class="stat-item">
                    <i class="fas fa-hospital"></i>
                    <span>Total Hospitals: <span class="num"><?php echo mysqli_num_rows($result); ?></span></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-arrow-up"></i>
                    <span>Default Limits: <span class="num">2 / 10 / 10</span></span>
                </div>
            </div>

            <!-- ===== TABLE ===== -->
            <div class="table-wrapper">
                <form method="POST" id="limitsForm">
                    <table>
                        <thead>
                            <tr>
                                <th>Hospital Name</th>
                                <th style="width:100px;">Departments</th>
                                <th style="width:100px;">Doctors</th>
                                <th style="width:100px;">Staff</th>
                                <th style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="font-semibold"><?php echo htmlspecialchars($row['hospital_name']); ?></td>
                                        <td>
                                            <input type="number" name="max_departments_<?php echo $row['hospital_id']; ?>" 
                                                   value="<?php echo $row['max_departments']; ?>" min="0" class="form-control">
                                        </td>
                                        <td>
                                            <input type="number" name="max_doctors_<?php echo $row['hospital_id']; ?>" 
                                                   value="<?php echo $row['max_doctors']; ?>" min="0" class="form-control">
                                        </td>
                                        <td>
                                            <input type="number" name="max_staff_<?php echo $row['hospital_id']; ?>" 
                                                   value="<?php echo $row['max_staff']; ?>" min="0" class="form-control">
                                        </td>
                                        <td>
                                            <button type="submit" name="update_limits" value="<?php echo $row['hospital_id']; ?>" 
                                                    class="btn btn-primary" 
                                                    onclick="this.form.hospital_id.value=this.value; 
                                                             this.form.max_departments.value=document.getElementsByName('max_departments_<?php echo $row['hospital_id']; ?>')[0].value; 
                                                             this.form.max_doctors.value=document.getElementsByName('max_doctors_<?php echo $row['hospital_id']; ?>')[0].value; 
                                                             this.form.max_staff.value=document.getElementsByName('max_staff_<?php echo $row['hospital_id']; ?>')[0].value;">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding:2rem; color:#94a3b8;">No hospitals found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="hospital_id" id="hospital_id">
                    <input type="hidden" name="max_departments" id="max_departments">
                    <input type="hidden" name="max_doctors" id="max_doctors">
                    <input type="hidden" name="max_staff" id="max_staff">
                </form>
            </div>

            <!-- ===== INFO BOX ===== -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Note:</strong> Changing these limits will immediately allow Hospital Admins to add more resources.
                    Default limits: <strong>Departments = 2, Doctors = 10, Staff = 10</strong>.
                </div>
            </div>
        </div>
    </div>

    <script>
        // No extra JS needed – the form submits via hidden fields.
        // Hidden fields are populated on button click via the onclick attribute.
    </script>
</body>
</html>