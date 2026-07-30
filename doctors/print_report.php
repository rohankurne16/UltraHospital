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

if ($order_id == 0) {
    die("Invalid order ID");
}

// Get hospital data
$hospital_data = null;
$sql_hospital = "SELECT * FROM hospital_master WHERE hospital_id = $hid AND delete_flag = 0 LIMIT 1";
$result_hospital = $conn->query($sql_hospital);
if ($result_hospital && $result_hospital->num_rows > 0) {
    $hospital_data = $result_hospital->fetch_assoc();
}
$hospital_name = $hospital_data["hospital_name"] ?? "MedixPro";
$hospital_logo = $hospital_data["hospital_logo"] ?? "../documents/hospital/logo.png";
$hospital_address = $hospital_data["address"] ?? "";
$hospital_city = $hospital_data["city"] ?? "";
$hospital_state = $hospital_data["state"] ?? "";
$hospital_pincode = $hospital_data["pincode"] ?? "";
$hospital_phone = $hospital_data["phone"] ?? "";
$hospital_email = $hospital_data["email"] ?? "";
$hospital_registration = $hospital_data["registration_number"] ?? "";

// Full address
$full_address = $hospital_address;
if (!empty($hospital_city)) $full_address .= ", " . $hospital_city;
if (!empty($hospital_state)) $full_address .= ", " . $hospital_state;
if (!empty($hospital_pincode)) $full_address .= " - " . $hospital_pincode;

// Get order and report details
$sql = "SELECT r.*, o.order_no, o.order_date, o.doctor_id,
        p.patient_name, p.mobile, p.gender, p.date_of_birth, p.address,
        d.doctor_name, d.qualification,
        s.name as technician_name
        FROM lab_reports r
        LEFT JOIN lab_orders o ON r.order_id = o.order_id
        LEFT JOIN patients p ON r.patient_id = p.patient_id
        LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
        LEFT JOIN staff s ON r.technician_id = s.staff_id
        WHERE r.order_id = $order_id 
        AND o.hospital_id = $hid
        AND (o.delete_flag = 0 OR o.delete_flag IS NULL)
        LIMIT 1";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Report not found");
}

$report = $result->fetch_assoc();

// Format doctor name
$doctor_name = 'Not Assigned';
if (!empty($report['doctor_name'])) {
    $name = $report['doctor_name'];
    $name = preg_replace('/^Dr\.?\s*/i', '', $name);
    $doctor_name = 'Dr. ' . $name;
}

// Get test results for this order
$sql_tests = "SELECT od.*, t.test_name, t.test_code, t.normal_range as test_normal_range, t.unit,
              r2.result_value, r2.normal_range, r2.remarks as result_remarks
              FROM lab_order_details od
              LEFT JOIN lab_tests t ON od.test_id = t.test_id
              LEFT JOIN lab_test_results r2 ON od.detail_id = r2.order_detail_id
              WHERE od.order_id = $order_id
              ORDER BY od.detail_id";
$result_tests = $conn->query($sql_tests);
$test_results = [];
if ($result_tests) {
    while ($row = $result_tests->fetch_assoc()) {
        $test_results[] = $row;
    }
}

// Format dates
$report_date = date('d M Y', strtotime($report['report_date'] ?? 'now'));
$order_date = date('d M Y', strtotime($report['order_date'] ?? 'now'));
$dob = 'N/A';
if (!empty($report['date_of_birth']) && $report['date_of_birth'] != '0000-00-00') {
    $dob = date('d M Y', strtotime($report['date_of_birth']));
}

// Check if logo exists
$logo_path = $hospital_logo;
if (!file_exists($logo_path) && !file_exists('../' . $logo_path)) {
    $logo_path = '';
}

$display_logo = '';
if (!empty($logo_path)) {
    if (file_exists($logo_path)) {
        $display_logo = $logo_path;
    } elseif (file_exists('../' . $logo_path)) {
        $display_logo = '../' . $logo_path;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php echo $hospital['hospital_name'] ?>- Lab Report</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, 'Segoe UI', sans-serif;
        }
        
        body {
            background: #ffffff;
            padding: 20px;
            color: #1f2937;
        }
        
        .report-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px 35px;
        }
        
        /* Report Header - Exact same as PDF */
        .report-header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .report-header .logo-img {
            max-height: 60px;
            max-width: 120px;
            object-fit: contain;
            margin-bottom: 5px;
        }
        
        .report-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        
        .report-header p {
            color: #6b7280;
            font-size: 13px;
            margin: 2px 0;
        }
        
        .report-header .phone {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Report Title - Exact same as PDF */
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
            margin: 15px 0 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Report Info Grid - Exact same as PDF */
        .report-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
            background: #f8fafc;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .report-info-item {
            display: flex;
            padding: 2px 0;
            font-size: 13px;
        }
        
        .report-info-item .label {
            font-weight: 600;
            color: #4b5563;
            width: 120px;
            flex-shrink: 0;
        }
        
        .report-info-item .value {
            color: #1f2937;
        }
        
        .report-info-item .value strong {
            font-weight: 700;
        }
        
        /* Test Results Table - Exact same as PDF */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
        }
        
        .report-table th {
            background: #3b82f6;
            color: white;
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
            vertical-align: middle;
        }
        
        .report-table tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .report-table .result-highlight {
            font-weight: 700;
            color: #059669;
        }
        
        .report-table .test-code {
            font-size: 11px;
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }
        
        /* Status Badge - Exact same as PDF */
        .status-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.cancelled {
            background: #fecaca;
            color: #991b1b;
        }
        
        /* Report Footer - Exact same as PDF */
        .report-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 13px;
            color: #6b7280;
        }
        
        .report-footer .left strong {
            color: #1f2937;
        }
        
        .report-footer .left .generated {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 2px;
        }
        
        .report-footer .signature {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .report-footer .signature .line {
            width: 180px;
            border-top: 1.5px solid #1f2937;
            margin: 5px 0 3px 0;
        }
        
        .report-footer .signature span {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Print Actions */
        .print-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .print-actions button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .print-actions button:hover {
            background: #2563eb;
        }
        
        .print-actions .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            margin-left: 10px;
        }
        
        .print-actions .btn-secondary:hover {
            background: #d1d5db;
        }
        
        /* Print Styles - Exact same as PDF */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .report-container {
                padding: 30px 35px !important;
                max-width: 100% !important;
            }
            
            .print-actions {
                display: none !important;
            }
            
            .report-table tr:nth-child(even) {
                background: #f8fafc !important;
            }
            
            .report-table th {
                background: #3b82f6 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .status-badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .status-badge.completed {
                background: #dcfce7 !important;
                color: #166534 !important;
            }
            
            .status-badge.pending {
                background: #fef3c7 !important;
                color: #92400e !important;
            }
            
            .report-info-grid {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .report-header {
                border-bottom-color: #3b82f6 !important;
            }
        }
        
        @media (max-width: 640px) {
            body { padding: 10px; }
            .report-container { padding: 15px; }
            .report-info-grid { grid-template-columns: 1fr; }
            .report-info-item .label { width: 100px; }
            .report-table th, .report-table td { padding: 5px 8px; font-size: 11px; }
            .report-footer { flex-direction: column; align-items: center; text-align: center; }
            .report-header h1 { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="report-container" id="reportContainer">
        <!-- Report Header -->
        <div class="report-header">
            <?php if (!empty($display_logo) && file_exists($display_logo)): ?>
                <img src="<?php echo htmlspecialchars($display_logo); ?>" alt="Hospital Logo" class="logo-img">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($hospital_name); ?></h1>
            <p><?php echo htmlspecialchars($full_address); ?></p>
            <?php if (!empty($hospital_phone)): ?>
                <div class="phone">Phone: <?php echo htmlspecialchars($hospital_phone); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Report Title -->
        <div class="report-title">Laboratory Test Report</div>
        
        <!-- Patient & Report Info -->
        <div class="report-info-grid">
            <div class="report-info-item">
                <span class="label">Report No:</span>
                <span class="value"><strong><?php echo htmlspecialchars($report['report_no'] ?? 'N/A'); ?></strong></span>
            </div>
            <div class="report-info-item">
                <span class="label">Order No:</span>
                <span class="value"><?php echo htmlspecialchars($report['order_no'] ?? 'N/A'); ?></span>
            </div>
            <div class="report-info-item">
                <span class="label">Patient Name:</span>
                <span class="value"><strong><?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?></strong></span>
            </div>
            <div class="report-info-item">
                <span class="label">Gender:</span>
                <span class="value"><?php echo htmlspecialchars($report['gender'] ?? 'N/A'); ?></span>
            </div>
            <div class="report-info-item">
                <span class="label">Date of Birth:</span>
                <span class="value"><?php echo $dob; ?></span>
            </div>
            <div class="report-info-item">
                <span class="label">Mobile:</span>
                <span class="value"><?php echo htmlspecialchars($report['mobile'] ?? 'N/A'); ?></span>
            </div>
            <div class="report-info-item">
                <span class="label">Referring Doctor:</span>
                <span class="value" style="font-weight:500; color:#1e40af;"><?php echo $doctor_name; ?></span>
            </div>
            <div class="report-info-item">
                <span class="label">Report Date:</span>
                <span class="value"><strong><?php echo $report_date; ?></strong></span>
            </div>
            <div class="report-info-item">
                <span class="label">Order Date:</span>
                <span class="value"><?php echo $order_date; ?></span>
            </div>
            <div class="report-info-item">
                <span class="label">Status:</span>
                <span class="value">
                    <?php 
                    $status = $report['report_status'] ?? 'Pending';
                    $status_class = strtolower($status);
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                </span>
            </div>
        </div>
        
        <!-- Test Results Table -->
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Test Name</th>
                    <th style="width:100px;">Result</th>
                    <th style="width:120px;">Normal Range</th>
                    <th style="width:70px;">Unit</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($test_results)): ?>
                    <?php foreach ($test_results as $index => $test): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($test['test_name'] ?? 'N/A'); ?></strong>
                                <?php if (!empty($test['test_code'])): ?>
                                    <span class="test-code">Code: <?php echo htmlspecialchars($test['test_code']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="result-highlight"><?php echo htmlspecialchars($test['result_value'] ?? 'Not entered'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($test['normal_range'] ?? $test['test_normal_range'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($test['unit'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($test['result_remarks'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#6b7280; padding:20px;">No test results available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Report Footer -->
        <div class="report-footer">
            <div class="left">
                <div><strong>Technician:</strong> <?php echo htmlspecialchars($report['technician_name'] ?? 'N/A'); ?></div>
                <div class="generated">Generated on: <?php echo date('d M Y, h:i A'); ?></div>
                <?php if (!empty($hospital_registration)): ?>
                    <div class="generated" style="margin-top:2px;">Reg No: <?php echo htmlspecialchars($hospital_registration); ?></div>
                <?php endif; ?>
            </div>
            <div class="signature">
                <div class="line"></div>
                <span>Authorized Signature</span>
            </div>
        </div>
    </div>
    
    <!-- Print Actions -->
    <div class="print-actions">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button class="btn-secondary" onclick="window.close()">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        // Auto print after page loads with a small delay
        window.onload = function() {
            // Auto print after 1 second
            setTimeout(function() {
                // Uncomment below to auto-print
                // window.print();
            }, 1000);
        };
    </script>
</body>
</html>