<?php

include "../config/db.php";

if (isset($_GET['patient_name'])) {
    $search = mysqli_real_escape_string($conn, $_GET['patient_name']);

    $sql = "SELECT patient_name FROM patients 
            WHERE patient_name LIKE '%$search%' 
            AND delete_flag = 0 
            LIMIT 10";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $name = htmlspecialchars($row['patient_name']);
            echo "<div onclick=\"selectPatient('$name')\" class=\"p-3 cursor-pointer border-b border-gray-100 dark:border-darkBorder hover:bg-gray-50 dark:hover:bg-neutral-800 transition-colors\">";
            echo $name;
            echo "</div>";
        }
    } else {
        echo "<div class=\"p-3 text-gray-500 text-sm\">No patients found.</div>";
    }
}

mysqli_close($conn);

?>