<?php
session_start();
include "../config/hospital.php";

header('Content-Type: application/json');

if (isset($_GET['hospital_id']) && !empty($_GET['hospital_id'])) {
    $hospital_id = (int)$_GET['hospital_id'];
    
    $query = "SELECT department_name 
              FROM department 
              WHERE status = 'Active' 
              AND hospital_id = '$hospital_id' 
              AND (delete_flag = 0 OR delete_flag IS NULL) 
              ORDER BY department_name ASC";
    
    $result = mysqli_query($conn, $query);
    
    $departments = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = $row;
        }
        echo json_encode(['success' => true, 'departments' => $departments]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No departments found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Hospital ID required']);
}

mysqli_close($conn);
?>