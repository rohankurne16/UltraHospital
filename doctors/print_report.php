<?php
session_start();
include "../config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["id"];
$hid = $_SESSION["hospital_id"];

// Get order_id from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID");
}

// Get order details
$sql_order = "SELECT o.*, p.patient_name, p.mobile, p.gender, p.date_of_birth, p.address,
               d.doctor_name, d.qualification, d.department,
               s.name as technician_name
               FROM lab_orders o
               LEFT JOIN patients p ON o.patient_id = p.patient_id
               LEFT JOIN doctor d ON o.doctor_id = d.doctor_id
               LEFT JOIN staff s ON o.technician_id = s.staff_id
               WHERE o.order_id = $order_id 
               AND o.doctor_id = $user_id
               AND o.hospital_id = $hid
               AND (o.delete_flag = 0 OR o.delete_flag IS NULL)";

$result_order = $conn->query($sql_order);

if (!$result_order || $result_order->num_rows == 0) {
    die("Order not found or you don't have permission to view this report.");
}

$order = $result_order->fetch_assoc();

// Get test results
$sql_tests = "SELECT od.*, t.test_name, t.test_code, t.normal_range as test_normal_range, t.unit,
               tr.result_value, tr.normal_range, tr.remarks as result_remarks
               FROM lab_order_details od
               LEFT JOIN lab_tests t ON od.test_id = t.test_id
               LEFT JOIN lab_test_results tr ON od.detail_id = tr.order_detail_id
               WHERE od.order_id = $order_id 
               AND (od.delete_flag = 0 OR od.delete_flag IS NULL)
               ORDER BY od.detail_id";

$result_tests = $conn->query($sql_tests);
$tests = [];
if ($result_tests) {
    while ($row = $result_tests->fetch_assoc()) {
        $tests[] = $row;
    }
}

// Get hospital data
$sql_hospital = "SELECT * FROM hospital_master LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
$hospital_data = $result_hospital ? $result_hospital->fetch_assoc() : null;
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_address = $hospital_data["address"] ?? "";
$hospital_phone = $hospital_data["phone"] ?? "";
$hospital_email = $hospital_data["email"] ?? "";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";

// ========== CHECK IF PDF DOWNLOAD REQUESTED ==========
if (isset($_GET['download_pdf'])) {
    // Generate PDF using html2pdf or dompdf
    // For now, we'll use the print dialog with PDF option
    header("Location: print_report.php?order_id=" . $order_id . "&print=1");
    exit();
}

// ========== CHECK IF PRINT REQUESTED ==========
$auto_print = isset($_GET['print']) && $_GET['print'] == 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Report</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Times New Roman', serif; 
            background: white; 
            padding: 20px;
            color: #1f2937;
        }
        
        .print-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }
        
        /* Print Button - Hidden when printing */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-print { background: #1e40af; color: white; }
        .btn-print:hover { background: #1e3a8a; }
        .btn-pdf { background: #dc2626; color: white; }
        .btn-pdf:hover { background: #b91c1c; }
        .btn-back { background: #6b7280; color: white; }
        .btn-back:hover { background: #4b5563; }
        .btn i { color: white !important; }
        
        /* Report Header */
        .report-header {
            text-align: center;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .hospital-name {
            font-size: 28px;
            font-weight: 700;
            color: #1e40af;
            letter-spacing: 2px;
        }
        
        .hospital-address {
            font-size: 14px;
            color: #4b5563;
            margin-top: 4px;
        }
        
        .hospital-contact {
            font-size: 13px;
            color: #6b7280;
        }
        
        .report-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e40af;
            margin-top: 10px;
            letter-spacing: 1px;
            border: 2px solid #1e40af;
            display: inline-block;
            padding: 4px 30px;
            border-radius: 4px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .info-section h3 {
            font-size: 14px;
            font-weight: 700;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        
        .info-section p {
            font-size: 14px;
            margin: 2px 0;
        }
        
        .info-section .label {
            color: #6b7280;
            font-weight: 600;
        }
        
        .tests-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .tests-table thead {
            background: #1e40af;
            color: white;
        }
        
        .tests-table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tests-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .tests-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .test-code-badge {
            font-family: monospace;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .result-normal { color: #16a34a; font-weight: 600; }
        .result-pending { color: #f59e0b; font-weight: 600; }
        
        .report-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6b7280;
        }
        
        .footer-signature {
            text-align: center;
            margin-top: 20px;
        }
        
        .signature-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #1f2937;
            margin-top: 40px;
        }
        
        .signature-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        /* Print Styles */
        @media print {
            body { 
                padding: 0; 
                background: white;
                margin: 0;
            }
            .print-container {
                padding: 20px;
                margin: 0;
                max-width: 100%;
            }
            .no-print { 
                display: none !important; 
            }
            .tests-table thead { 
                background: #1e40af !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
            .tests-table tbody tr:nth-child(even) { 
                background: #f8fafc !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
            .info-grid { 
                background: #f8fafc !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
            .report-header {
                border-bottom-color: #1e40af !important;
            }
            .hospital-name {
                color: #1e40af !important;
            }
            .report-title {
                border-color: #1e40af !important;
                color: #1e40af !important;
            }
        }
        
        @media (max-width: 640px) {
            .info-grid { grid-template-columns: 1fr; gap: 10px; }
            .print-container { padding: 15px; }
            .hospital-name { font-size: 22px; }
            .report-title { font-size: 18px; padding: 4px 20px; }
            .btn { padding: 8px 16px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Print Buttons - Hidden when printing -->
        <div class="no-print">
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button onclick="downloadPDF()" class="btn btn-pdf">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
                <a href="doctor_lab_reports.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <div class="hospital-name"><?php echo htmlspecialchars($hospital_name); ?></div>
            <?php if ($hospital_address): ?>
                <div class="hospital-address"><?php echo htmlspecialchars($hospital_address); ?></div>
            <?php endif; ?>
            <?php if ($hospital_phone || $hospital_email): ?>
                <div class="hospital-contact">
                    <?php if ($hospital_phone): ?>Phone: <?php echo htmlspecialchars($hospital_phone); ?><?php endif; ?>
                    <?php if ($hospital_phone && $hospital_email): ?> | <?php endif; ?>
                    <?php if ($hospital_email): ?>Email: <?php echo htmlspecialchars($hospital_email); ?><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="report-title">LABORATORY REPORT</div>
        </div>

        <!-- Patient and Order Info -->
        <div class="info-grid">
            <div class="info-section">
                <h3>Patient Information</h3>
                <p><span class="label">Name:</span> <?php echo htmlspecialchars($order['patient_name'] ?? 'N/A'); ?></p>
                <p><span class="label">Gender:</span> <?php echo htmlspecialchars($order['gender'] ?? 'N/A'); ?></p>
                <p><span class="label">Date of Birth:</span> <?php echo htmlspecialchars($order['date_of_birth'] ?? 'N/A'); ?></p>
                <p><span class="label">Mobile:</span> <?php echo htmlspecialchars($order['mobile'] ?? 'N/A'); ?></p>
                <?php if (!empty($order['address'])): ?>
                    <p><span class="label">Address:</span> <?php echo htmlspecialchars($order['address']); ?></p>
                <?php endif; ?>
            </div>
            <div class="info-section">
                <h3>Order Details</h3>
                <p><span class="label">Order No:</span> <?php echo htmlspecialchars($order['order_no']); ?></p>
                <p><span class="label">Order Date:</span> <?php echo date('d-m-Y', strtotime($order['order_date'])); ?></p>
                <p><span class="label">Report Date:</span> <?php echo date('d-m-Y'); ?></p>
                <p><span class="label">Doctor:</span> <?php 
                    $doc_name = $order['doctor_name'] ?? '';
                    if (!empty($doc_name)) {
                        $doc_name = preg_replace('/^Dr\.?\s*/i', '', $doc_name);
                        echo 'Dr. ' . $doc_name;
                    } else {
                        echo 'N/A';
                    }
                ?></p>
                <p><span class="label">Technician:</span> <?php echo htmlspecialchars($order['technician_name'] ?? 'N/A'); ?></p>
                <?php if (!empty($order['clinical_notes'])): ?>
                    <p><span class="label">Clinical Notes:</span> <?php echo htmlspecialchars($order['clinical_notes']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Test Results Table -->
        <h3 style="font-size: 16px; font-weight: 700; color: #1e40af; margin-bottom: 10px;">
            <i class="fas fa-flask"></i> Test Results
        </h3>
        
        <table class="tests-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 120px;">Test Code</th>
                    <th>Test Name</th>
                    <th style="width: 100px;">Result</th>
                    <th style="width: 100px;">Unit</th>
                    <th style="width: 120px;">Normal Range</th>
                    <th style="width: 100px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tests)): ?>
                    <?php $counter = 1; ?>
                    <?php foreach ($tests as $test): ?>
                        <?php 
                            $result_value = $test['result_value'] ?? '';
                            $normal_range = $test['normal_range'] ?? $test['test_normal_range'] ?? 'N/A';
                            $unit = $test['unit'] ?? 'N/A';
                            $status = !empty($result_value) ? 'Completed' : 'Pending';
                            $status_class = !empty($result_value) ? 'result-normal' : 'result-pending';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code'] ?? 'N/A'); ?></span></td>
                            <td><?php echo htmlspecialchars($test['test_name'] ?? 'N/A'); ?></td>
                            <td class="<?php echo $status_class; ?>">
                                <?php echo !empty($result_value) ? htmlspecialchars($result_value) : '—'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($unit); ?></td>
                            <td><?php echo htmlspecialchars($normal_range); ?></td>
                            <td>
                                <span class="<?php echo $status_class; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                        </tr>
                        <?php if (!empty($test['result_remarks'])): ?>
                            <tr>
                                <td colspan="7" style="padding: 4px 12px 10px 12px; font-size: 12px; color: #6b7280; background: #f9fafb;">
                                    <i class="fas fa-comment"></i> Remarks: <?php echo htmlspecialchars($test['result_remarks']); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #6b7280;">
                            <i class="fas fa-info-circle"></i> No test results available for this order.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($order['remarks'])): ?>
            <div style="margin: 10px 0; padding: 10px; background: #f9fafb; border-radius: 6px; border-left: 4px solid #1e40af;">
                <p style="font-size: 14px; color: #4b5563;">
                    <strong>Additional Remarks:</strong> <?php echo htmlspecialchars($order['remarks']); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="report-footer">
            <div>
                <p>Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
                <p>This is a computer-generated report</p>
            </div>
            <div class="footer-signature">
                <div class="signature-line"></div>
                <div class="signature-text">Authorized Signature</div>
            </div>
        </div>

        <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 11px; color: #9ca3af;">
            <?php echo htmlspecialchars($hospital_name); ?> - Laboratory Report | Page 1 of 1
        </div>
    </div>

    <script>
        // ========== DOWNLOAD PDF ==========
        function downloadPDF() {
            // Use window.print() which allows "Save as PDF" option
            // First show a message
            if (confirm('Click "Save as PDF" in the print dialog to download as PDF.')) {
                window.print();
            }
        }

        // ========== AUTO PRINT ==========
        <?php if ($auto_print): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
        <?php endif; ?>
    </script>
</body>
</html>