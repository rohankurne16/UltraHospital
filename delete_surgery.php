<?php
session_start();
include('config/hospital.php');

// Check if user is logged in
if (!isset($_SESSION['hospital_id'])) {
    header("Location: login.php");
    exit();
}

// Check if surgery_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No surgery ID provided.";
    header("Location: surgeries.php");
    exit();
}

$surgery_id = mysqli_real_escape_string($conn, $_GET['id']);
$hospital_id = $_SESSION['hospital_id'];

// Get surgery details before deletion
$query = "SELECT s.*, p.patient_name 
          FROM surgeries s
          LEFT JOIN patients p ON s.patient_id = p.patient_id
          WHERE s.surgery_id = '$surgery_id' 
          AND s.hospital_id = '$hospital_id'
          AND s.delete_flag = '0'";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "Surgery not found or already deleted.";
    header("Location: surgeries.php");
    exit();
}

$surgery = mysqli_fetch_assoc($result);

// Perform soft delete
$delete_query = "UPDATE surgeries 
                 SET delete_flag = '1',
                     modified_at = NOW()
                 WHERE surgery_id = '$surgery_id' 
                 AND hospital_id = '$hospital_id'";

if (mysqli_query($conn, $delete_query)) {
    
    // --- INSERT INTO ACTIVITY LOG ---
    $user_name = $_SESSION['user_name'] ?? 'Unknown User';
    $user_role = $_SESSION['user_role'] ?? 'User';
    $register_id = $_SESSION['user_id'] ?? 0;
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Get User Agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Detect browser (simple detection)
    $browser = 'Unknown';
    if (strpos($user_agent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'Opera') !== false) {
        $browser = 'Opera';
    }
    
    // Prepare description
    $description = "Deleted surgery #{$surgery['surgery_no']} for patient {$surgery['patient_name']} (ID: {$surgery['patient_id']})";
    
   
    
    $_SESSION['success_message'] = "Surgery #" . htmlspecialchars($surgery['surgery_no']) . " deleted successfully.";
    header("Location: surgeries.php");
    exit();
    
} else {
    $_SESSION['error_message'] = "Error deleting surgery: " . mysqli_error($conn);
    header("Location: view_surgery.php?id=" . $surgery_id);
    exit();
}
?>