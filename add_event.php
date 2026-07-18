<?php
session_start();
include("config/db.php");

if(isset($_POST['save']))
{
    $event_name = mysqli_real_escape_string($conn,$_POST['event_name']);
    $event_date = $_POST['event_date'];

    $sql = "INSERT INTO add_events(event_name,event_date)
            VALUES('$event_name','$event_date')";

    if(mysqli_query($conn,$sql))
    {
        echo "<script>
        alert('Event Added Successfully');
        window.location='calendar.php';
        </script>";
    }
    else
    {
        echo "<script>alert('Failed to Add Event');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Event</title>

<link rel="stylesheet" href="_next/static/chunks/4fbfc6079ef7eaf2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

body{
    background:#f4f6f9;
}

.card{
    width:500px;
    margin:80px auto;
    background:#fff;
    border-radius:12px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.15);
}

.card h2{
    margin-bottom:25px;
}

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:8px;
    outline:none;
}

input:focus{
    border-color:#2563eb;
}

.btn{
    background:#2563eb;
    color:#fff;
    padding:12px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.btn:hover{
    background:#1d4ed8;
}

.back{
    text-decoration:none;
    margin-left:10px;
    color:#2563eb;
}

</style>

</head>

<body>

<div class="card">

<h2>
<i class="fa-solid fa-calendar-plus"></i>
Add Event
</h2>

<form method="post">

<div class="form-group">

<label>Event Name</label>

<input
type="text"
name="event_name"
placeholder="Enter Event Name"
required>

</div>

<div class="form-group">

<label>Event Date</label>

<input
type="date"
name="event_date"
required>

</div>

<button type="submit" class="btn" name="save">
    <i class="fa-solid fa-floppy-disk"></i> Save Event
</button>

<a href="calendar.php" class="back">
Cancel
</a>

</form>

</div>

</body>
</html>