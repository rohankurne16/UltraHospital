<?php
include("config/hospital.php");

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$beds = [];

if ($room_id > 0) {
    // Fetch all beds including status
    $sql = "SELECT bed_id, bed_no, status FROM bed_master WHERE room_id = '$room_id' AND (delete_flag = 0 OR delete_flag IS NULL)";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Check status and set proper value
            $status = trim($row['status'] ?? '');
            
            if (empty($status)) {
                $row['status'] = 'Available';
            } elseif (strtolower($status) == 'available' || strtolower($status) == 'a') {
                $row['status'] = 'Available';
            } elseif (strtolower($status) == 'occupied' || strtolower($status) == 'o') {
                $row['status'] = 'Occupied';
            } elseif (strtolower($status) == 'maintenance' || strtolower($status) == 'm') {
                $row['status'] = 'Maintenance';
            } else {
                // Default to Available if unknown status
                $row['status'] = 'Available';
            }
            
            $beds[] = $row;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($beds);
?>