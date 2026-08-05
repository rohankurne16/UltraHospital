<?php
session_start();
require_once '../config/hospital.php';
$hospital_id = $_SESSION['hospital_id'] ?? 0;
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $patient_name = $_POST['patient_name'];
    $insurance_company = $_POST['insurance_company'];
    $policy_number = $_POST['policy_number'];
    $claim_number = $_POST['claim_number'];
    $amount = $_POST['amount'];
    $submitted_date = $_POST['submitted_date'];
    $remark = $_POST['remark'];

    $insert = "INSERT INTO insurance_claims (hospital_id, patient_id, patient_name, insurance_company, policy_number, claim_number, approved_amount, pending_amount, submitted_date, remark, status)
               VALUES ($hospital_id, $patient_id, '$patient_name', '$insurance_company', '$policy_number', '$claim_number', $amount, $amount, '$submitted_date', '$remark', 'Pending')";
    if ($conn->query($insert)) {
        echo "<script>alert('Claim created successfully'); window.location='insurance_claims.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Insurance Claim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; color:#1a202c; }
        .main-content { margin-left:260px; padding:24px 32px; min-height:100vh; width:calc(100% - 260px); }
        @media (max-width:1024px){ .main-content{ margin-left:0; padding:20px; width:100%; } }
        .card { background:white; border-radius:16px; border:1px solid #e2e8f0; padding:24px; max-width:600px; margin:0 auto; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; font-size:13px; color:#475569; margin-bottom:4px; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#f8fafc; }
        .btn { padding:10px 24px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#ed8936; color:white; }
        .btn-primary:hover { background:#d97706; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .flex { display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-end; margin-top:16px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="d-flex">
        <?php include '../sidebar.php'; ?>
        <main class="main-content">
            <div class="card">
                <h2 style="margin-bottom:16px;"><i class="fas fa-file-signature"></i> New Insurance Claim</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Patient Name *</label>
                        <input type="text" class="form-control" name="patient_name" required>
                    </div>
                    <div class="form-group">
                        <label>Patient ID</label>
                        <input type="number" class="form-control" name="patient_id">
                    </div>
                    <div class="form-group">
                        <label>Insurance Company *</label>
                        <input type="text" class="form-control" name="insurance_company" required>
                    </div>
                    <div class="form-group">
                        <label>Policy Number</label>
                        <input type="text" class="form-control" name="policy_number">
                    </div>
                    <div class="form-group">
                        <label>Claim Number</label>
                        <input type="text" class="form-control" name="claim_number">
                    </div>
                    <div class="form-group">
                        <label>Claim Amount *</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Submitted Date</label>
                        <input type="date" class="form-control" name="submitted_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Remark</label>
                        <textarea class="form-control" name="remark" rows="3"></textarea>
                    </div>
                    <div class="flex">
                        <a href="insurance_claims.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Claim</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>