<?php
include "../config/db.php";

$query = isset($_GET["query"]) ? mysqli_real_escape_string($conn, $_GET["query"]) : "";
$type = isset($_GET["type"]) ? $_GET["type"] : "";

$output = "";

if ($query !== "") {
    if ($type === "patient") {
        $sql = "SELECT patient_name name FROM patients WHERE name LIKE '%$query%' LIMIT 5";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $output .= "<div class=\"p-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-neutral-800 border-b dark:border-darkBorder last:border-0 patient-item\" data-name=\"". htmlspecialchars($row["name"]) ."\">" . htmlspecialchars($row["name"]) . "</div>";
            }
        } else {
            $output .= "<div class=\"p-3 text-gray-500\">No patients found</div>";
        }
    } else if ($type === "doctor") {
        
        $sql = "SELECT doctor_name name  FROM doctors WHERE name LIKE '%$query%' LIMIT 5";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $output .= "<div class=\"p-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-neutral-800 border-b dark:border-darkBorder last:border-0 doctor-item\" data-name=\"". htmlspecialchars($row["name"]) ."\">" . htmlspecialchars($row["name"]) . " (" . htmlspecialchars($row["department"]) . ")</div>";
            }
        } else {
            $output .= "<div class=\"p-3 text-gray-500\">No doctors found</div>";
        }
    }
}

echo $output;
mysqli_close($conn);
?>