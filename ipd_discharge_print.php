<?php
session_start();
include("config/hospital.php");

$conn->set_charset("utf8");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid discharge ID!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

// Fetch discharge summary
$sql = "SELECT ds.*, 
        p.patient_name, p.patient_id as patient_code, p.age, p.gender, p.mobile, p.email, p.address, p.blood_group,
        d.doctor_name, d.specialization, d.qualification,
        a.admission_no, a.ward_id, a.room_no, a.bed_no, a.admission_date,
        w.ward_name,
        fd.doctor_name as follow_up_doctor_name
        FROM ipd_discharge_summary ds
        LEFT JOIN patients p ON ds.patient_id = p.patient_id
        LEFT JOIN doctor d ON ds.doctor_id = d.doctor_id
        LEFT JOIN ipd_admissions a ON ds.ipd_id = a.id
        LEFT JOIN ward_master w ON a.ward_id = w.ward_id
        LEFT JOIN doctor fd ON ds.follow_up_doctor = fd.doctor_id
        WHERE ds.discharge_id = '$id' AND ds.delete_flag = 0";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Discharge summary not found!'];
    header("Location: ipd_treatment_list.php");
    exit();
}

$discharge = $result->fetch_assoc();

// Fetch discharge medicines
$dis_medicines = [];
$med_result = $conn->query("SELECT * FROM ipd_discharge_medicines WHERE discharge_id = '$id' AND delete_flag = 0");
if ($med_result && $med_result->num_rows > 0) {
    while ($row = $med_result->fetch_assoc()) {
        $dis_medicines[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discharge Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-container { padding: 20px; }
            .page-break { page-break-after: always; }
        }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .print-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #3b82f6; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; font-weight: 700; color: #1e293b; }
        .header p { color: #64748b; }
        .section-title { font-size: 16px; font-weight: 700; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin: 20px 0 12px 0; }
        .info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; padding: 4px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row .label { color: #64748b; font-weight: 500; }
        .info-row .value { color: #0f172a; }
        .med-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .med-table th { background: #f1f5f9; padding: 8px 12px; text-align: left; font-weight: 600; color: #475569; border: 1px solid #e2e8f0; }
        .med-table td { padding: 8px 12px; border: 1px solid #e2e8f0; }
        .signature { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .footer { text-align: center; color: #94a3b8; font-size: 12px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-success { padding: 10px 24px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-success:hover { background: #16a34a; }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 768px) { .print-container { padding: 16px; } .info-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="print-container" id="printArea">
        <!-- Header -->
        <div class="header">
            <h1>Discharge Summary</h1>
            <p><?php echo $hospital['hospital_name'] ?? 'City Hospital'; ?></p>
            <p><?php echo $hospital['hospital_address'] ?? 'Main Street, City'; ?> | Ph: <?php echo $hospital['hospital_phone'] ?? '+91 9876543210'; ?></p>
        </div>

        <!-- Patient Information -->
        <div class="section-title">Patient Information</div>
        <div class="info-row"><span class="label">Patient ID:</span><span class="value"><?php echo htmlspecialchars($discharge['patient_code']); ?></span></div>
        <div class="info-row"><span class="label">Patient Name:</span><span class="value"><?php echo htmlspecialchars($discharge['patient_name']); ?></span></div>
        <div class="info-row"><span class="label">Age / Gender:</span><span class="value"><?php echo htmlspecialchars($discharge['age']); ?> / <?php echo htmlspecialchars($discharge['gender']); ?></span></div>
        <div class="info-row"><span class="label">Blood Group:</span><span class="value"><?php echo htmlspecialchars($discharge['blood_group'] ?? 'N/A'); ?></span></div>
        <div class="info-row"><span class="label">Mobile:</span><span class="value"><?php echo htmlspecialchars($discharge['mobile']); ?></span></div>
        <div class="info-row"><span class="label">Address:</span><span class="value"><?php echo htmlspecialchars($discharge['address'] ?? 'N/A'); ?></span></div>

        <!-- Admission Details -->
        <div class="section-title">Admission Details</div>
        <div class="info-row"><span class="label">IPD Number:</span><span class="value"><?php echo htmlspecialchars($discharge['admission_no']); ?></span></div>
        <div class="info-row"><span class="label">Admission Date:</span><span class="value"><?php echo date('d-m-Y h:i A', strtotime($discharge['admission_date'])); ?></span></div>
        <div class="info-row"><span class="label">Discharge Date:</span><span class="value"><?php echo date('d-m-Y h:i A', strtotime($discharge['discharge_date'])); ?></span></div>
        <div class="info-row"><span class="label">Total Days:</span><span class="value"><?php echo htmlspecialchars($discharge['total_admission_days'] ?? 'N/A'); ?> days</span></div>
        <div class="info-row"><span class="label">Ward / Room / Bed:</span><span class="value"><?php echo htmlspecialchars($discharge['ward_name']); ?> / <?php echo htmlspecialchars($discharge['room_no']); ?> / <?php echo htmlspecialchars($discharge['bed_no']); ?></span></div>
        <div class="info-row"><span class="label">Treating Doctor:</span><span class="value"><?php echo htmlspecialchars($discharge['doctor_name']); ?></span></div>

        <!-- Diagnosis -->
        <div class="section-title">Diagnosis</div>
        <div class="info-row"><span class="label">Primary Diagnosis:</span><span class="value"><?php echo htmlspecialchars($discharge['primary_diagnosis']); ?></span></div>
        <div class="info-row"><span class="label">Final Diagnosis:</span><span class="value"><?php echo htmlspecialchars($discharge['final_diagnosis']); ?></span></div>

        <!-- Treatment Summary -->
        <div class="section-title">Treatment Summary</div>
        <div class="info-row"><span class="label">Treatment Given:</span><span class="value"><?php echo nl2br(htmlspecialchars($discharge['treatment_given'] ?? 'N/A')); ?></span></div>
        <div class="info-row"><span class="label">Procedures Performed:</span><span class="value"><?php echo nl2br(htmlspecialchars($discharge['procedures_performed'] ?? 'N/A')); ?></span></div>

        <!-- Patient Condition -->
        <div class="section-title">Patient Condition at Discharge</div>
        <div class="info-row"><span class="label">Condition:</span><span class="value font-bold text-green-600"><?php echo htmlspecialchars($discharge['patient_condition']); ?></span></div>

        <!-- Discharge Medicines -->
        <div class="section-title">Discharge Medicines</div>
        <?php if (count($dis_medicines) > 0): ?>
            <table class="med-table">
                <thead>
                    <tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Days</th><th>Instructions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dis_medicines as $med): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                            <td><?php echo htmlspecialchars($med['dosage'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($med['frequency'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($med['number_of_days'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($med['instructions'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No discharge medicines prescribed.</p>
        <?php endif; ?>

        <!-- Diet & Activity Advice -->
        <div class="section-title">Advice</div>
        <div class="info-row"><span class="label">Diet Advice:</span><span class="value"><?php echo htmlspecialchars($discharge['diet_advice'] ?? 'N/A'); ?></span></div>
        <div class="info-row"><span class="label">Activity Advice:</span><span class="value"><?php echo htmlspecialchars($discharge['activity_advice'] ?? 'N/A'); ?></span></div>

        <!-- Follow-up -->
        <div class="section-title">Follow-up</div>
        <div class="info-row"><span class="label">Follow-up Date:</span><span class="value"><?php echo date('d-m-Y', strtotime($discharge['follow_up_date'] ?? 'N/A')); ?></span></div>
        <div class="info-row"><span class="label">Follow-up Doctor:</span><span class="value"><?php echo htmlspecialchars($discharge['follow_up_doctor_name'] ?? 'N/A'); ?></span></div>
        <div class="info-row"><span class="label">Instructions:</span><span class="value"><?php echo nl2br(htmlspecialchars($discharge['follow_up_instructions'] ?? 'N/A')); ?></span></div>

        <!-- Final Remarks -->
        <div class="section-title">Final Remarks</div>
        <div class="info-row"><span class="label">Remarks:</span><span class="value"><?php echo nl2br(htmlspecialchars($discharge['final_remarks'] ?? 'N/A')); ?></span></div>

        <!-- Doctor Signature -->
        <div class="signature">
            <div class="flex justify-between">
                <div>
                    <p><strong>Doctor Name:</strong> <?php echo htmlspecialchars($discharge['doctor_name']); ?></p>
                    <p><strong>Registration No:</strong> <?php echo htmlspecialchars($discharge['registration_number'] ?? 'N/A'); ?></p>
                </div>
                <div class="text-right">
                    <p><strong>Signature:</strong></p>
                    <div style="height: 40px;"></div>
                    <p>_________________________</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated discharge summary. No signature is required.</p>
            <p>Generated on: <?php echo date('d-m-Y h:i A'); ?></p>
        </div>
    </div>

    <!-- Print Buttons -->
    <div class="text-center no-print" style="padding: 20px; max-width: 900px; margin: 0 auto;">
        <button onclick="window.print()" class="btn-primary">
            <i class="fas fa-print mr-2"></i> Print / PDF
        </button>
        <a href="ipd_treatment_view.php?id=<?php echo $discharge['treatment_master_id']; ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        <a href="ipd_discharge.php?id=<?php echo $discharge['treatment_master_id']; ?>" class="btn-secondary">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
    </div>

    <script>
        // Auto print on load if needed
        // window.onload = function() { setTimeout(function() { window.print(); }, 1000); };
    </script>
</body>
</html>