<?php

include 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$hospital = [];

if (isset($_SESSION['hospital_id']) && !empty($_SESSION['hospital_id'])) {

    $hospital_id = (int)$_SESSION['hospital_id'];

    $query = "SELECT *
              FROM hospital_master
              WHERE hospital_id = $hospital_id
              AND (delete_flag = 0 or delete_flag is null)";
              

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {

        $hospital = mysqli_fetch_assoc($result);

        $_SESSION['hospital_name'] = $hospital['hospital_name'];
        $_SESSION['hospital_code'] = $hospital['hospital_code'];
        $_SESSION['hospital_logo'] = $hospital['hospital_logo'];
        $_SESSION['hospital_type'] = $hospital['hospital_type'];
        $_SESSION['registration_number'] = $hospital['registration_number'];
        $_SESSION['gst_number'] = $hospital['gst_number'];
        $_SESSION['address'] = $hospital['address'];
        $_SESSION['email'] = $hospital['email'];
        $_SESSION['city'] = $hospital['city'];
        $_SESSION['state'] = $hospital['state'];
        $_SESSION['country'] = $hospital['country'];
        $_SESSION['pincode'] = $hospital['pincode'];
        $_SESSION['established_year'] = $hospital['established_year'];
        $_SESSION['phone'] = $hospital['phone'];
        $_SESSION['website'] = $hospital['website'];
        $_SESSION['hospital_status'] = $hospital['status'];
    }
}
?>