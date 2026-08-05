<?php
session_start();
include "config/hospital.php";

// Check Login
if (!isset($_SESSION["id"])) {
    header("Location: index.php");
    exit();
}

$hid = $_SESSION["hospital_id"];

// Report ID
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id <= 0) {
    die("Invalid Report ID");
}

/* =====================================================
   HOSPITAL DETAILS
===================================================== */

$sqlHospital = "
SELECT *
FROM hospital_master
WHERE hospital_id='$hid'
AND delete_flag=0
LIMIT 1";

$resHospital = mysqli_query($conn,$sqlHospital);

$hospital = mysqli_fetch_assoc($resHospital);

$hospital_name  = $hospital['hospital_name'] ?? '';
$hospital_logo = $hospital['hospital_logo'] ?? '';

if (!empty($hospital_logo)) {

    // Remove ../ if already present
    $hospital_logo = ltrim($hospital_logo, './');

    // If database stores only filename
    if (strpos($hospital_logo, 'documents/') === false) {
        $hospital_logo = 'documents/hospital/' . $hospital_logo;
    }
}
$hospital_phone = $hospital['phone'] ?? '';
$hospital_email = $hospital['email'] ?? '';
$hospital_city  = $hospital['city'] ?? '';
$hospital_state = $hospital['state'] ?? '';
$hospital_pin   = $hospital['pincode'] ?? '';
$hospital_add   = $hospital['address'] ?? '';

$hospital_address = trim(
    $hospital_add.", ".
    $hospital_city.", ".
    $hospital_state." - ".
    $hospital_pin
);

/* =====================================================
   REPORT DETAILS
===================================================== */

$sql = "
SELECT

r.*,

o.order_no,
o.order_date,

p.patient_name,
p.mobile,
p.gender,
p.date_of_birth,
p.age,
p.address,

d.doctor_name,
d.qualification,
d.department,

s.name AS technician_name

FROM lab_reports r

LEFT JOIN lab_orders o
ON r.order_id=o.order_id

LEFT JOIN patients p
ON r.patient_id=p.patient_id

LEFT JOIN doctor d
ON r.doctor_id=d.doctor_id

LEFT JOIN staff s
ON r.technician_id=s.staff_id

WHERE r.report_id='$report_id'
AND r.hospital_id='$hid'

LIMIT 1";

$result=mysqli_query($conn,$sql);

if(mysqli_num_rows($result)==0){
    die("Report Not Found");
}

$report=mysqli_fetch_assoc($result);


/* =====================================================
   TEST DETAILS
===================================================== */

$sqlTests="

SELECT

od.detail_id,

t.test_name,
t.test_code,
t.normal_range,
t.unit,

ltr.result_value,
ltr.remarks,
ltr.report_status

FROM lab_order_details od

LEFT JOIN lab_tests t
ON od.test_id=t.test_id

LEFT JOIN lab_test_results ltr
ON od.detail_id=ltr.order_detail_id

WHERE od.order_id='".$report['order_id']."'

ORDER BY od.detail_id ASC";

$resTests=mysqli_query($conn,$sqlTests);

$tests=[];

while($row=mysqli_fetch_assoc($resTests))
{
    $tests[]=$row;
}


/* =====================================================
   DATE FORMAT
===================================================== */

$report_date=date(
"d M Y",
strtotime($report['report_date'])
);

$order_date=date(
"d M Y",
strtotime($report['order_date'])
);

$dob="N/A";

if(
!empty($report['date_of_birth']) &&
$report['date_of_birth']!="0000-00-00"
)
{
$dob=date(
"d M Y",
strtotime($report['date_of_birth'])
);
}

/* =====================================================
   DOCTOR NAME
===================================================== */

$doctor_name="Not Assigned";

if(!empty($report['doctor_name']))
{

$name=preg_replace(
'/^Dr\.?\s*/i',
'',
$report['doctor_name']
);

$doctor_name="Dr. ".$name;

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo htmlspecialchars($hospital_name); ?> - Laboratory Report</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,Helvetica,sans-serif;
}

body{
    background:#fff;
    color:#222;
    padding:20px;
}

.report-container{
    width:900px;
    margin:auto;
    background:#fff;
}

/*=========================
HEADER
=========================*/

.report-header{

    display:flex;
    justify-content:space-between;
    align-items:center;

    border-bottom:3px solid #2563eb;

    padding-bottom:15px;

    margin-bottom:20px;
}

.logo{

    width:120px;
}

.logo img{

    width:100%;
    max-height:90px;
    object-fit:contain;

}

.hospital{

    flex:1;
    text-align:center;

}

.hospital h1{

    font-size:28px;
    color:#1e3a8a;
    margin-bottom:5px;
}

.hospital p{

    font-size:13px;
    color:#555;
    margin:2px;
}

.report-title{

    text-align:center;

    font-size:22px;

    font-weight:bold;

    margin:20px 0;

    color:#1e40af;

    text-transform:uppercase;

    letter-spacing:1px;
}

/*=========================
PATIENT INFO
=========================*/

.info-box{

    border:1px solid #ddd;

    border-radius:6px;

    margin-bottom:20px;

    overflow:hidden;
}

.info-header{

    background:#2563eb;

    color:#fff;

    padding:10px 15px;

    font-weight:bold;

    font-size:15px;
}

.info-body{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:10px;

    padding:15px;
}

.info-row{

    display:flex;

    font-size:14px;
}

.label{

    width:140px;

    font-weight:bold;

    color:#444;
}

.value{

    color:#111;
}

/*=========================
TABLE
=========================*/

.report-table{

    width:100%;

    border-collapse:collapse;

    margin-top:10px;
}

.report-table th{

    background:#2563eb;

    color:white;

    padding:12px;

    border:1px solid #ddd;

    font-size:13px;
}

.report-table td{

    border:1px solid #ddd;

    padding:10px;

    font-size:13px;
}

.report-table tr:nth-child(even){

    background:#f9fafb;
}

/*=========================
STATUS
=========================*/

.badge{

    padding:4px 12px;

    border-radius:20px;

    font-size:12px;

    font-weight:bold;
}

.completed{

    background:#dcfce7;

    color:#15803d;
}

.pending{

    background:#fef3c7;

    color:#b45309;
}

/*=========================
FOOTER
=========================*/

.footer{

    margin-top:40px;

    display:flex;

    justify-content:space-between;

    align-items:flex-end;
}

.signature{

    text-align:center;
}

.signature-line{

    width:200px;

    border-top:1px solid #000;

    margin-bottom:5px;
}

/*=========================
PRINT
=========================*/

@media print{

body{

padding:0;

}

.report-container{

width:100%;

}

}

</style>
</head>

<body>

<div class="report-container">

<div class="report-header">

<div class="logo">

<?php if(!empty($hospital_logo)){ ?>

<img src="<?php echo htmlspecialchars($hospital_logo); ?>">

<?php } ?>

</div>

<div class="hospital">

<h1><?php echo htmlspecialchars($hospital_name); ?></h1>

<p><?php echo htmlspecialchars($hospital_address); ?></p>

<p>

Phone :
<?php echo htmlspecialchars($hospital_phone); ?>

|

Email :
<?php echo htmlspecialchars($hospital_email); ?>

</p>

</div>

<div style="width:120px;"></div>

</div>

<div class="report-title">

Laboratory Test Report

</div>

<div class="info-box">

<div class="info-header">

Patient Information

</div>

<div class="info-body">

<div class="info-row">
<div class="label">Patient Name</div>
<div class="value"><?php echo htmlspecialchars($report['patient_name']); ?></div>
</div>

<div class="info-row">
<div class="label">Gender</div>
<div class="value"><?php echo htmlspecialchars($report['gender']); ?></div>
</div>

<div class="info-row">
<div class="label">Date of Birth</div>
<div class="value"><?php echo $dob; ?></div>
</div>

<div class="info-row">
<div class="label">Mobile</div>
<div class="value"><?php echo htmlspecialchars($report['mobile']); ?></div>
</div>

<div class="info-row">
<div class="label">Doctor</div>
<div class="value"><?php echo htmlspecialchars($doctor_name); ?></div>
</div>

<div class="info-row">
<div class="label">Department</div>
<div class="value"><?php echo htmlspecialchars($report['department']); ?></div>
</div>

<div class="info-row">
<div class="label">Report No</div>
<div class="value"><?php echo htmlspecialchars($report['report_no']); ?></div>
</div>

<div class="info-row">
<div class="label">Order No</div>
<div class="value"><?php echo htmlspecialchars($report['order_no']); ?></div>
</div>

<div class="info-row">
<div class="label">Order Date</div>
<div class="value"><?php echo $order_date; ?></div>
</div>

<div class="info-row">
<div class="label">Report Date</div>
<div class="value"><?php echo $report_date; ?></div>
</div>

</div>

</div><!-- =========================
TEST RESULTS
========================= -->

<table class="report-table">

<thead>

<tr>

<th style="width:50px;">#</th>

<th>Test Name</th>

<th style="width:120px;">Result</th>

<th style="width:140px;">Normal Range</th>

<th style="width:90px;">Unit</th>

<th>Remarks</th>

</tr>

</thead>

<tbody>

<?php if(!empty($tests)){ ?>

<?php foreach($tests as $key=>$test){ ?>

<tr>

<td align="center">

<?php echo $key+1; ?>

</td>

<td>

<strong>

<?php echo htmlspecialchars($test['test_name']); ?>

</strong>

<br>

<small style="color:#777;">

<?php echo htmlspecialchars($test['test_code']); ?>

</small>

</td>

<td>

<?php

$result=$test['result_value'] ?? '';

echo !empty($result)
? htmlspecialchars($result)
: "<span style='color:#999;'>Pending</span>";

?>

</td>

<td>

<?php

echo htmlspecialchars(

$test['normal_range']

??

'N/A'

);

?>

</td>

<td>

<?php

echo htmlspecialchars(

$test['unit']

??

''

);

?>

</td>

<td>

<?php

echo htmlspecialchars(

$test['remarks']

??

'-'

);

?>

</td>

</tr>

<?php } ?>

<?php }else{ ?>

<tr>

<td colspan="6" align="center">

No Test Results Found

</td>

</tr>

<?php } ?>

</tbody>

</table>

<!-- =========================
OVERALL REMARKS
========================= -->

<?php if(!empty($report['remarks'])){ ?>

<div style="

margin-top:25px;

padding:15px;

border-left:5px solid #2563eb;

background:#f8fafc;

">

<b>Remarks :</b>

<br><br>

<?php echo nl2br(htmlspecialchars($report['remarks'])); ?>

</div>

<?php } ?>

<!-- =========================
FOOTER
========================= -->

<div class="footer">

<div>

<p>

<strong>Lab Technician :</strong>

<?php

echo htmlspecialchars(

$report['technician_name']

??

'N/A'

);

?>

</p>

<br>

<p>

Generated On :

<?php

echo date(

'd M Y h:i A'

);

?>

</p>

<br>

<?php if(!empty($hospital_registration)){ ?>

<p>

Registration No :

<?php echo htmlspecialchars($hospital_registration); ?>

</p>

<?php } ?>

<?php if(!empty($hospital_gst)){ ?>

<p>

GST No :

<?php echo htmlspecialchars($hospital_gst); ?>

</p>

<?php } ?>

</div>

<div class="signature">

<div class="signature-line"></div>

<b>

Authorized Signature

</b>



<br>



</div>

</div><!-- =========================
END REPORT CONTAINER
========================= -->

</div>

<script>

// Print automatically after page loads
window.addEventListener("load", function () {

    setTimeout(function () {
        window.print();
    }, 500);

});

// Close window after printing
window.addEventListener("afterprint", function () {

    window.close();

});

// Fallback for browsers that don't support afterprint
if (window.matchMedia) {

    const mediaQueryList = window.matchMedia("print");

    mediaQueryList.addEventListener("change", function (mql) {

        if (!mql.matches) {

            window.close();

        }

    });

}

</script>

</body>

</html>