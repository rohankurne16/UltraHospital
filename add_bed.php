<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

// Get room_id and ward_id from URL
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$ward_id = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;

if ($room_id == 0) {
    header("Location: room_master.php");
    exit();
}

// Fetch room details
$roomQuery = "SELECT r.*, w.ward_name 
              FROM room_master r
              LEFT JOIN ward_master w ON r.ward_id = w.ward_id
              WHERE r.room_id = $room_id AND (r.delete_flag = 0 OR r.delete_flag IS NULL)";
$roomResult = $conn->query($roomQuery);
if ($roomResult->num_rows == 0) {
    header("Location: room_master.php");
    exit();
}
$room = $roomResult->fetch_assoc();

// If ward_id is not passed in URL, get it from room data
if ($ward_id == 0) {
    $ward_id = $room['ward_id'];
}

// Get ward name for display
$ward_name = '';
if ($ward_id > 0) {
    $ward_query = mysqli_query($conn, "SELECT ward_name FROM ward_master WHERE ward_id = $ward_id AND (delete_flag=0 OR delete_flag IS NULL)");
    if ($ward_data = mysqli_fetch_assoc($ward_query)) {
        $ward_name = $ward_data['ward_name'];
    }
}

if(isset($_POST['save'])){
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $bed_no = mysqli_real_escape_string($conn, $_POST['bed_no']);
    $bed_type = mysqli_real_escape_string($conn, $_POST['bed_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check if bed number already exists in this room
    $check = mysqli_query($conn, "SELECT * FROM bed_master WHERE bed_no='$bed_no' AND room_id='$room_id' AND (delete_flag=0 OR delete_flag IS NULL)");

    if(mysqli_num_rows($check) > 0){
        echo "<script>alert('Bed Number already exists in this room.');</script>";
    } else {
        $sql = "INSERT INTO bed_master (room_id, bed_no, bed_type, status) VALUES ('$room_id','$bed_no','$bed_type','$status')";

        if(mysqli_query($conn, $sql)){
            echo "<script>
                alert('Bed Added Successfully');
                window.location='view_bed.php?id=$room_id&ward_id=$ward_id';
            </script>";
        } else {
            echo "<script>alert('".mysqli_error($conn)."');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MedixPro - Add Bed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
            width: 100%;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #64748b;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .back-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .page-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            width: 100%;
        }

        .form-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        }

        .form-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-body {
            padding: 28px 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .form-label .required {
            color: #ef4444;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: white;
            color: #0f172a;
            transition: all 0.2s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #f0f9ff;
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }

        .input-icon {
            position: relative;
        }

        

        .input-icon input,
        .input-icon select {
            padding-left: 14px;
        }

        .form-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }

        .room-info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        

        .room-info-box .room-name {
            font-weight: 600;
            color: #1e40af;
        }

        .room-info-box .info-label {
            color: #64748b;
            font-size: 13px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 12px;
            }

            .page-title {
                font-size: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include "header.php"; ?>
        
        <div class="flex flex-1 items-start">
            <?php include "Sidebar.php"; ?>
            
            <main class="main-content">
                <div class="max-w-full mx-auto w-full">
                    
                    <!-- Page Header -->
                    <div class="page-header">
                        <a href="view_bed.php?id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>" class="back-btn" title="Back to Beds">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title">
                                
                                Add New Bed
                            </h1>
                            <p class="page-subtitle">Create a new bed in the room</p>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="form-card">
                        <div class="form-header">
                            <h2>
                                
                                Bed Information
                            </h2>
                        </div>
                        
                        <form method="post" class="form-body">
                            <!-- Room Info Box -->
                            <div class="room-info-box">
                                
                                <div>
                                    <span class="info-label">Adding bed to room:</span>
                                    <span class="room-name"><?php echo htmlspecialchars($room['room_no']); ?></span>
                                    <span class="info-label" style="margin-left: 8px;">| Ward:</span>
                                    <span class="room-name"><?php echo htmlspecialchars($ward_name); ?></span>
                                </div>
                            </div>

                            <!-- Hidden inputs -->
                            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">

                            <!-- Two-column layout -->
                            <div class="form-row">
                                <!-- Bed Number -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Bed Number <span class="required">*</span>
                                    </label>
                                    <div class="input-icon">
                                        
                                        <input 
                                            type="text"
                                            name="bed_no"
                                            required
                                            placeholder="e.g., 1, A-1, ICU-01"
                                            class="form-input">
                                    </div>
                                    <p class="form-hint">Enter a unique bed number within this room</p>
                                </div>

                                <!-- Bed Type -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Bed Type <span class="required">*</span>
                                    </label>
                                    <div class="input-icon">
                                        
                                        <input 
                                            type="text"
                                            name="bed_type"
                                            required
                                            placeholder="e.g., ICU, General, Private"
                                            class="form-input">
                                    </div>
                                    <p class="form-hint">Specify the type or category of the bed</p>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="form-label">
                                    Status <span class="required">*</span>
                                </label>
                                <div class="input-icon">
                                    
                                    <select name="status" class="form-select">
                                        <option value="Available">Available</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                </div>
                                <p class="form-hint">Choose the current status of the bed</p>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="save" class="btn btn-primary">
                                    
                                    Save Bed
                                </button>
                                <a href="view_bed.php?id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>" class="btn btn-secondary">
                                    
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>