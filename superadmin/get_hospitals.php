<?php
// get_hospitals.php
header('Content-Type: application/json');

include('config/hospital.php');

// Fetch hospitals where delete_flag = 0 and status = 'Active'
$sql = "SELECT 
            hospital_id, 
            hospital_name, 
            hospital_code,
            hospital_type,
            address,
            city,
            state,
            country,
            phone,
            email,
            status
        FROM hospital_master 
        WHERE delete_flag = 0 AND status = 'Active' 
        ORDER BY hospital_name ASC";

$result = $conn->query($sql);

$hospitals = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hospitals[] = $row;
    }
    echo json_encode(['success' => true, 'hospitals' => $hospitals]);
} else {
    echo json_encode(['success' => true, 'hospitals' => []]);
}

$conn->close();
?>