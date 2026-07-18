<?php
include "config/db.php";

$id = (int)$_GET['id'];

mysqli_query($conn,"update ward_master set delete_flag=1 where ward_id=$id");

header("location:ward_master.php");
exit;