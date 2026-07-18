<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "db.php";

$hospital = null;

// User has logged in
if (isset($_SESSION['hospital_id'])) {

    $hid = (int)$_SESSION['hospital_id'];

    $result = mysqli_query($conn, "SELECT * FROM hospital_master WHERE hospital_id = $hid");

    if ($result && mysqli_num_rows($result) > 0) {
        $hospital = mysqli_fetch_assoc($result);
    }

}
// User has not logged in yet
else {

    // Optional: Show a default hospital
    $result = mysqli_query($conn, "SELECT * FROM hospital_master ORDER BY hospital_id LIMIT 1");

    if ($result && mysqli_num_rows($result) > 0) {
        $hospital = mysqli_fetch_assoc($result);
    }
}

