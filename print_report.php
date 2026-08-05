<?php
session_start();
include "config/hospital.php";

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["id"];
$hid = $_SESSION["hospital_id"];
$role = $_SESSION["role"] ?? '';

// Get report_id from URL
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id == 0) {
    die("Invalid report ID");
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
$hospital_gst = $hospital_data["gst_number"] ?? "";

// Full address
$full_address = $hospital_address;
if (!empty($hospital_city)) $full_address .= ", " . $hospital_city;
if (!empty($hospital_state)) $full_address .= ", " . $hospital_state;
if (!empty($hospital_pincode)) $full_address .= " - " . $hospital_pincode;

// Get report details
$sql = "SELECT r.*, o.order_no, o.order_date, o.doctor_id,
        p.patient_name, p.mobile, p.gender, p.date_of_birth, p.address,
        d.doctor_name, d.qualification,
        s.name as technician_name
        FROM lab_reports r
        LEFT JOIN lab_orders o ON r.order_id = o.order_id
        LEFT JOIN patients p ON r.patient_id = p.patient_id
        LEFT JOIN doctor d ON r.doctor_id = d.doctor_id
        LEFT JOIN staff s ON r.technician_id = s.staff_id
        WHERE r.report_id = $report_id 
        AND r.hospital_id = $hid
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
              r2.result_value, r2.normal_range, r2.remarks as result_remarks,
              r2.report_status as test_report_status
              FROM lab_order_details od
              LEFT JOIN lab_tests t ON od.test_id = t.test_id
              LEFT JOIN lab_test_results r2 ON od.detail_id = r2.order_detail_id
              WHERE od.order_id = " . $report['order_id'] . "
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

// Get user role for display
$user_role_display = ucfirst(str_replace('_', ' ', $role));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Lab Report</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        
        .report-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 30px 35px;
        }
        
        /* Hide all content initially - will show in print */
        .report-container {
            visibility: hidden;
        }
        
        @media print {
            .report-container {
                visibility: visible !important;
            }
        }
        
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .report-header .logo-section {
            flex: 1;
            text-align: left;
        }
        
        .report-header .logo-img {
            max-height: 70px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .report-header .hospital-info {
            flex: 2;
            text-align: center;
        }
        
        .report-header .hospital-info h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        
        .report-header .hospital-info p {
            color: #6b7280;
            font-size: 13px;
            margin: 2px 0;
        }
        
        .report-header .hospital-info .phone-email {
            font-size: 12px;
            color: #6b7280;
        }
        
        .report-header .spacer {
            flex: 1;
        }
        
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
            margin: 15px 0 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .report-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 30px;
            background: #f8fafc;
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .report-info-item {
            display: flex;
            padding: 3px 0;
            font-size: 13px;
        }
        
        .report-info-item .label {
            font-weight: 600;
            color: #4b5563;
            width: 130px;
            flex-shrink: 0;
        }
        
        .report-info-item .value {
            color: #1f2937;
        }
        
        .report-info-item .value strong {
            font-weight: 700;
        }
        
        .report-info-item .value.doctor-name {
            font-weight: 500;
            color: #1e40af;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
        }
        
        .report-table th {
            background: #3b82f6;
            color: white;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-table td {
            padding: 10px 14px;
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
            font-size: 14px;
        }
        
        .report-table .result-abnormal {
            font-weight: 700;
            color: #dc2626;
            font-size: 14px;
        }
        
        .report-table .test-code {
            font-size: 11px;
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 14px;
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
        
        .status-badge.corrected {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.in_process {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .status-badge.abnormal {
            background: #fecaca;
            color: #991b1b;
        }
        
        .status-badge.normal {
            background: #dcfce7;
            color: #166534;
        }
        
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
            width: 200px;
            border-top: 1.5px solid #1f2937;
            margin: 5px 0 3px 0;
        }
        
        .report-footer .signature span {
            font-size: 12px;
            color: #6b7280;
        }
        
        .report-footer .signature .signature-name {
            font-size: 13px;
            font-weight: 500;
            color: #1f2937;
            margin-top: 2px;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
            color: #3b82f6;
            font-weight: 700;
            letter-spacing: 10px;
        }
        
        .corrected-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .report-container {
                padding: 30px 35px !important;
                max-width: 100% !important;
                visibility: visible !important;
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
            
            .status-badge.cancelled {
                background: #fecaca !important;
                color: #991b1b !important;
            }
            
            .status-badge.corrected {
                background: #dbeafe !important;
                color: #1e40af !important;
            }
            
            .status-badge.in_process {
                background: #e0f2fe !important;
                color: #0369a1 !important;
            }
            
            .report-info-grid {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .report-table tr:nth-child(even) {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .watermark {
                display: none !important;
            }
        }
        
        @media (max-width: 640px) {
            body { padding: 10px; }
            .report-container { padding: 15px; }
            .report-header { flex-direction: column; text-align: center; }
            .report-header .logo-section { text-align: center; }
            .report-info-grid { grid-template-columns: 1fr; }
            .report-info-item .label { width: 100px; }
            .report-table th, .report-table td { padding: 6px 10px; font-size: 11px; }
            .report-footer { flex-direction: column; align-items: center; text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Report Content - Hidden until print -->
    <div class="report-container" id="reportContainer">
        <!-- Watermark for corrected reports -->
        <?php if (isset($report['report_status']) && $report['report_status'] == 'Corrected'): ?>
            <div class="watermark">CORRECTED</div>
        <?php endif; ?>
        
        <!-- Report Header with Logo -->
        <div class="report-header">
            <div class="logo-section">
                <?php if (!empty($display_logo) && file_exists($display_logo)): ?>
                    <img src="<?php echo htmlspecialchars($display_logo); ?>" alt="Hospital Logo" class="logo-img">
                <?php endif; ?>
            </div>
            <div class="hospital-info">
                <h1><?php echo htmlspecialchars($hospital_name); ?></h1>
                <p><?php echo htmlspecialchars($full_address); ?></p>
                <?php if (!empty($hospital_phone) || !empty($hospital_email)): ?>
                    <div class="phone-email">
                        <?php if (!empty($hospital_phone)): ?>
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($hospital_phone); ?>
                        <?php endif; ?>
                        <?php if (!empty($hospital_phone) && !empty($hospital_email)): ?>
                            &nbsp;|&nbsp;
                        <?php endif; ?>
                        <?php if (!empty($hospital_email)): ?>
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($hospital_email); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="spacer"></div>
        </div>
        
        <!-- Report Title -->
        <div class="report-title">
            Laboratory Test Report
            <?php if (isset($report['report_status']) && $report['report_status'] == 'Corrected'): ?>
                <span class="corrected-badge">
                    <i class="fas fa-edit"></i> CORRECTED
                </span>
            <?php endif; ?>
        </div>
        
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
                <span class="value doctor-name"><?php echo $doctor_name; ?></span>
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
            <?php if (isset($report['corrected_date']) && !empty($report['corrected_date'])): ?>
            <div class="report-info-item" style="grid-column: span 2;">
                <span class="label">Corrected On:</span>
                <span class="value"><?php echo date('d M Y, h:i A', strtotime($report['corrected_date'])); ?></span>
            </div>
            <?php endif; ?>
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
                    <th style="width:100px;">Status</th>
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
                                <?php 
                                $result_value = $test['result_value'] ?? 'Not entered';
                                $test_status = $test['test_report_status'] ?? 'Pending';
                                ?>
                                <span class="<?php echo ($test_status == 'Abnormal') ? 'result-abnormal' : 'result-highlight'; ?>">
                                    <?php echo htmlspecialchars($result_value); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($test['normal_range'] ?? $test['test_normal_range'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($test['unit'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $status_class = strtolower(str_replace(' ', '_', $test_status));
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($test_status); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($test['result_remarks'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#6b7280; padding:20px;">No test results available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Remarks Section -->
        <?php if (!empty($report['remarks'])): ?>
        <div style="margin-top: 15px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; border-left: 4px solid #3b82f6;">
            <strong style="font-size: 13px; color: #0f172a;">Remarks:</strong>
            <span style="font-size: 14px; color: #334155;"><?php echo nl2br(htmlspecialchars($report['remarks'])); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Report Footer -->
        <div class="report-footer">
            <div class="left">
                <div>
                    <strong>Technician:</strong> 
                    <?php echo htmlspecialchars($report['technician_name'] ?? 'Not Assigned'); ?>
                </div>
                <div>
                    <strong>Generated By:</strong> 
                    <?php echo htmlspecialchars($user_role_display ?? 'Admin'); ?>
                </div>
                <div class="generated">Generated on: <?php echo date('d M Y, h:i A'); ?></div>
                <?php if (!empty($hospital_registration)): ?>
                    <div class="generated" style="margin-top:2px;">Reg No: <?php echo htmlspecialchars($hospital_registration); ?></div>
                <?php endif; ?>
                <?php if (!empty($hospital_gst)): ?>
                    <div class="generated">GST: <?php echo htmlspecialchars($hospital_gst); ?></div>
                <?php endif; ?>
            </div>
            <div class="signature">
                <div class="line"></div>
                <span>Authorized Signature</span>
                <div class="signature-name">
                    <?php echo htmlspecialchars($report['doctor_name'] ?? '___________________'); ?>
                </div>
                <span style="font-size: 11px; color: #9ca3af;">
                    <?php echo htmlspecialchars($report['qualification'] ?? ''); ?>
                </span>
            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        // Immediately trigger print as soon as the page loads
        (function() {
            // Store the original print function
            var originalPrint = window.print;
            
            // Override window.print to close window after printing
            window.print = function() {
                originalPrint.call(window);
                
                // Listen for afterprint event to close the window
                var afterPrint = function() {
                    window.close();
                };
                
                // Check if browser supports afterprint event
                if (window.matchMedia) {
                    var mediaQueryList = window.matchMedia('print');
                    mediaQueryList.addListener(function(mql) {
                        if (!mql.matches) {
                            afterPrint();
                        }
                    });
                }
                
                // Fallback for browsers that don't support afterprint
                setTimeout(function() {
                    afterPrint();
                }, 2000);
            };
            
            // Trigger print immediately
            window.print();
        })();
    </script>
</body>
</html>