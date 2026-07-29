<?php
include "config/hospital.php";

$id = intval($_GET['id']);

$sql = "SELECT report_file
        FROM lab_reports
        WHERE report_id = $id
        LIMIT 1";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$file = "documents/reports/" . $row['report_file'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Report</title>
    <style>
        body{
            margin:0;
            text-align:center;
            background:#fff;
        }
        img{
            max-width:100%;
            height:auto;
        }
    </style>
</head>

<body onload="window.print()">

<img src="<?php echo $file; ?>">

</body>
</html>