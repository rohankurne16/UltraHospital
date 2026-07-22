<?php
session_start();
include("config/hospital.php");

$hid = $_SESSION['hospital_id'];

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

$beds = [];

if ($room_id > 0) {

    $sql = "SELECT bed_id, bed_no, bed_type, status
            FROM bed_master
            WHERE room_id='$room_id'
            AND hospital_id='$hid'
            AND status='Available'
            AND (delete_flag=0 OR delete_flag IS NULL)
            ORDER BY bed_no";

    $result = mysqli_query($conn, $sql);

    while($row = mysqli_fetch_assoc($result)){
        $beds[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($beds);
?>