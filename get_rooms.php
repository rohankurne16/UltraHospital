<?php
include("config/hospital.php");

$ward_id = isset($_GET['ward_id']) ? intval($_GET['ward_id']) : 0;
$rooms = [];

if ($ward_id > 0) {
    $query = "SELECT room_id, room_no, capacity FROM room_master WHERE ward_id = ? AND status != 'Occupied' AND delete_flag = 0 ORDER BY room_no ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $ward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($rooms);
?>