<?php
include "../config/hospital.php";

$order_id = intval($_GET['order_id']);

$sql = "SELECT
            lr.report_no,
            lr.report_date,
            lr.remarks,
            lo.order_no,
            p.patient_name,
            p.gender,
            p.age,
            d.doctor_name,
            lt.test_name,
            ltr.result_value,
            ltr.normal_range,
            ltr.unit
        FROM lab_reports lr
        LEFT JOIN lab_orders lo ON lr.order_id = lo.order_id
        LEFT JOIN patients p ON lr.patient_id = p.patient_id
        LEFT JOIN doctor d ON lr.doctor_id = d.doctor_id
        LEFT JOIN lab_order_details lod ON lr.detail_id = lod.detail_id
        LEFT JOIN lab_tests lt ON lod.test_id = lt.test_id
        LEFT JOIN lab_test_results ltr ON ltr.order_detail_id = lod.detail_id
        WHERE lr.order_id = $order_id
        LIMIT 1";

$result = mysqli_query($conn,$sql);
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
<title>Lab Report</title>
<style>
body{font-family:Arial;padding:30px;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
table,th,td{border:1px solid #000;}
th,td{padding:10px;}
h2{text-align:center;}
@media print{
button{display:none;}
}
</style>
</head>

<body>

<button onclick="window.print()">Print</button>

<h2>LAB REPORT</h2>

<p><b>Report No :</b> <?= $data['report_no']; ?></p>
<p><b>Order No :</b> <?= $data['order_no']; ?></p>
<p><b>Patient :</b> <?= $data['patient_name']; ?></p>
<p><b>Doctor :</b> <?= $data['doctor_name']; ?></p>
<p><b>Date :</b> <?= $data['report_date']; ?></p>

<table>
<tr>
<th>Test</th>
<th>Result</th>
<th>Normal Range</th>
<th>Unit</th>
</tr>

<tr>
<td><?= $data['test_name']; ?></td>
<td><?= $data['result_value']; ?></td>
<td><?= $data['normal_range']; ?></td>
<td><?= $data['unit']; ?></td>
</tr>

</table>

<p><b>Remarks :</b> <?= $data['remarks']; ?></p>

<script>
window.onload=function(){
    window.print();
}
</script>

</body>
</html>