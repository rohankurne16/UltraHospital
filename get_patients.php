<?php
include "config/hospital.php";

$doctor_id = $_GET['doctor_id'];

$sql = "SELECT patient_id, patient_name
        FROM patients
        WHERE doctor_id='$doctor_id'
        AND (delete_flag=0 OR delete_flag IS NULL)
        ORDER BY patient_name";

$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>