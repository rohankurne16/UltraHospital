<?php
include "../config/hospital.php";

if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("File not found.");
}

$file = basename($_GET['file']); // Security
$filePath = "../documents/reports/" . $file;

if (!file_exists($filePath)) {
    die("File does not exist.");
}

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($filePath) . "\"");
header("Content-Length: " . filesize($filePath));
header("Pragma: public");

readfile($filePath);
exit;
?>