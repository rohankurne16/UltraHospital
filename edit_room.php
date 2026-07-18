<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

if(!isset($_GET['id'])){
    header("Location: ward_master.php");
    exit();
}

$id = (int)$_GET['id'];
$selected_ward_id = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;

$result = mysqli_query($conn,"SELECT * FROM room_master WHERE room_id='$id' AND (delete_flag=0 OR delete_flag IS NULL)");

if(mysqli_num_rows($result)==0){
    header("Location: ward_master.php");
    exit();
}

$line = mysqli_fetch_assoc($result);


// If ward_id is passed in URL, use it, otherwise use the room's current ward
if ($selected_ward_id == 0) {
    $selected_ward_id = $line['ward_id'];
}

if(isset($_POST['update'])){
    $ward_id = mysqli_real_escape_string($conn,$_POST['ward_id']);
    $room_no = mysqli_real_escape_string($conn,$_POST['room_no']);
    $capacity = mysqli_real_escape_string($conn,$_POST['capacity']);
    $status = mysqli_real_escape_string($conn,$_POST['status']);

    $check = mysqli_query($conn,"SELECT * FROM room_master
    WHERE room_no='$room_no'
    AND room_id!='$id'
    AND ward_id='$ward_id'
    AND (delete_flag=0 OR delete_flag IS NULL)");

    if(mysqli_num_rows($check)>0){
        echo "<script>alert('Room Number already exists in this ward.');</script>";
    } else {
        $sql = "UPDATE room_master SET
        ward_id='$ward_id',
        room_no='$room_no',
        capacity='$capacity',
        status='$status'
        WHERE room_id='$id'";

        if(mysqli_query($conn,$sql)){
            echo "<script>
                alert('Room Updated Successfully');
                window.location='view_ward.php?id=$ward_id';
            </script>";
        } else {
            echo "<script>alert('".mysqli_error($conn)."');</script>";
        }
    }
}

// Get ward name for display
$ward_name = '';
if ($selected_ward_id > 0) {
    $ward_query = mysqli_query($conn, "SELECT ward_name FROM ward_master WHERE ward_id = $selected_ward_id AND (delete_flag=0 OR delete_flag IS NULL)");
    if ($ward_data = mysqli_fetch_assoc($ward_query)) {
        $ward_name = $ward_data['ward_name'];
    }
}

// Get all active wards for dropdown
$wards = mysqli_query($conn,"SELECT * FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL) AND status='Active' ORDER BY ward_name");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MedixPro - Edit Room</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
 
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
            max-width: 100%;
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

        /* Selected ward info box */
        .ward-info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        

        .ward-info-box .ward-name {
            font-weight: 600;
            color: #1e40af;
        }

        .ward-info-box .ward-label {
            color: #64748b;
            font-size: 13px;
        }

        /* Disabled select styling */
        .form-select:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Read-only input styling */
        .form-input-readonly {
            background: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.7;
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

            .form-card {
                max-width: 100%;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
                        <a href="<?php echo $selected_ward_id > 0 ? 'view_ward.php?id=' . $selected_ward_id : 'ward_master.php'; ?>" class="back-btn" title="Back">
                              <i class="fa-solid fa-arrow-left"></i> 
                        </a>
                        <div>
                            <h1 class="page-title">
                                 
                                Edit Room
                            </h1>
                            <p class="page-subtitle">Update room details</p>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="form-card">
                        <div class="form-header">
                            <h2>
                                
                                Room Information
                            </h2>
                        </div>
                        
                        <form method="post" class="form-body">
                            <!-- Ward Info Box -->
                            <?php if ($selected_ward_id > 0 && !empty($ward_name)): ?>
                            <div class="ward-info-box">
                                
                                <div>
                                    <span class="ward-label">Room belongs to ward:</span>
                                    <span class="ward-name"><?php echo htmlspecialchars($ward_name); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Ward Selection -->
                            <div class="form-group">
                                <label class="form-label">
                                    Ward <span class="required">*</span>
                                </label>
                                <div class="input-icon">
                                    
                                    <?php if (isset($_GET['ward_id']) && $_GET['ward_id'] > 0): ?>
                                        <!-- Hidden input to submit ward_id -->
                                        <input type="hidden" name="ward_id" value="<?php echo $selected_ward_id; ?>">
                                        <!-- Read-only display of ward name -->
                                        <input 
                                            type="text"
                                            value="<?php echo htmlspecialchars($ward_name); ?>"
                                            disabled
                                            class="form-input form-input-readonly"
                                            style="padding-left: 14px;">
                                        <p class="form-hint" style="color: #3b82f6;">
                                             Ward is locked. To change, go back and select a different ward.
                                        </p>
                                    <?php else: ?>
                                        <!-- Regular dropdown when no ward_id is passed -->
                                        <select name="ward_id" required class="form-select">
                                            <option value="">Select Ward</option>
                                            <?php 
                                            mysqli_data_seek($wards, 0);
                                            while($ward = mysqli_fetch_assoc($wards)): 
                                            ?>
                                                <option value="<?php echo $ward['ward_id']; ?>"
                                                    <?php echo ($ward['ward_id'] == $line['ward_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ward['ward_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <p class="form-hint">Select the ward where this room belongs</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Room Number -->
                            <div class="form-group">
                                <label class="form-label">
                                    Room Number <span class="required">*</span>
                                </label>
                                <div class="input-icon">
                                    
                                    <input 
                                        type="text"
                                        name="room_no"
                                        value="<?php echo htmlspecialchars($line['room_no']); ?>"
                                        required
                                        placeholder="e.g., 101, A-101, ICU-01"
                                        class="form-input">
                                </div>
                                <p class="form-hint">Enter a unique room number within this ward</p>
                            </div>

                            <!-- Capacity -->
                            <div class="form-group">
                                <label class="form-label">
                                    Capacity <span class="required">*</span>
                                </label>
                                <div class="input-icon">
                                    
                                    <input 
                                        type="number"
                                        name="capacity"
                                        value="<?php echo htmlspecialchars($line['capacity']); ?>"
                                        required
                                        min="1"
                                        placeholder="Number of beds in room"
                                        class="form-input">
                                </div>
                                <p class="form-hint">Enter the maximum number of beds in this room</p>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="form-label">
                                    Status <span class="required">*</span>
                                </label>
                                <div class="input-icon">
                                    
                                   <select name="status" class="form-select">
                                        <option value="Available" <?php echo ($line['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="Occupied" <?php echo ($line['status'] == 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="Maintenance" <?php echo ($line['status'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </div>
                                <p class="form-hint">Choose whether the room is active or inactive</p>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="update" class="btn btn-primary">
                                    
                                    Update Room
                                </button>
                                <a href="<?php echo $selected_ward_id > 0 ? 'view_ward.php?id=' . $selected_ward_id : 'ward_master.php'; ?>" class="btn btn-secondary">
                                    
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