<?php
session_start();
include "config/hospital.php";

if (isset($_GET['id']) && isset($_GET['patient_id'])) {

    $document_id = mysqli_real_escape_string($conn, $_GET['id']);
    $patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);

    $query = "update patient_documents
              set delete_flag='1',
                  modified_at=now()
              where document_id='$document_id'";

    if (mysqli_query($conn, $query)) {
        header("Location: view_patient.php?id=$patient_id");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }

} else {
    header("Location: patients.php");
    exit();
}
?>