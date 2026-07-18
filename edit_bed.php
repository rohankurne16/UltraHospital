<?php
session_start();
include "config/hospital.php";

if (!isset($_GET['bed_id'])) {
    header("Location: room_master.php");
    exit;
}

$id = (int)$_GET['bed_id'];

// Fetch bed details along with room and ward
$sql = "
SELECT b.*, r.room_no, w.ward_name
FROM bed_master b
JOIN room_master r ON b.room_id = r.room_id
JOIN ward_master w ON r.ward_id = w.ward_id
WHERE b.bed_id='$id'
AND (b.delete_flag=0 OR b.delete_flag IS NULL)
";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    die("Bed not found.");
}

$bed = mysqli_fetch_assoc($result);

// Update
if (isset($_POST['update'])) {

    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $bed_no = mysqli_real_escape_string($conn, $_POST['bed_no']);
    $bed_type = mysqli_real_escape_string($conn, $_POST['bed_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check duplicate bed number in same room
    $check = mysqli_query($conn,"
    SELECT *
    FROM bed_master
    WHERE room_id='$room_id'
    AND bed_no='$bed_no'
    AND bed_id!='$id'
    AND (delete_flag=0 OR delete_flag IS NULL)
    ");

    if(mysqli_num_rows($check)>0){
        echo "<script>alert('Bed Number already exists in this room.');</script>";
    }else{
        $update="
        UPDATE bed_master
        SET
            bed_no='$bed_no',
            bed_type='$bed_type',
            status='$status'
        WHERE bed_id='$id'
        ";

        if(mysqli_query($conn,$update)){
            echo "<script>
                alert('Bed Updated Successfully');
                window.location.href='view_bed.php?id=$room_id';
            </script>";
            exit;
        }else{
            echo mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MedixPro - Edit Bed</title>
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

        .form-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }

        .location-info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .location-name {
            font-weight: 600;
            color: #1e40af;
        }

        .location-label {
            color: #64748b;
            font-size: 13px;
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
                        <a href="view_bed.php?id=<?= $bed['room_id']; ?>" class="back-btn" title="Back">
                              <i class="fa-solid fa-arrow-left"></i> 
                        </a>
                        <div>
                            <h1 class="page-title">Edit Bed</h1>
                            <p class="page-subtitle">Update bed details and availability status</p>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="form-card">
                        <div class="form-header">
                            <h2>Bed Information</h2>
                        </div>
                        
                        <form method="post" class="form-body">
                            <!-- Hidden Room ID -->
                            <input type="hidden" name="room_id" value="<?= $bed['room_id']; ?>">

                            <!-- Location Info Box -->
                            <div class="location-info-box">
                                <div>
                                    <span class="location-label">Bed location:</span>
                                    <span class="location-name"><?= htmlspecialchars($bed['ward_name'].' - '.$bed['room_no']); ?></span>
                                </div>
                            </div>

                            <!-- Room Location (Read-only) -->
                            <div class="form-group">
                                <label class="form-label">Room Location</label>
                                <input 
                                    type="text"
                                    value="<?= htmlspecialchars($bed['ward_name'].' - '.$bed['room_no']); ?>"
                                    disabled
                                    class="form-input form-input-readonly">
                                <p class="form-hint" style="color: #3b82f6;">
                                    Room assignment is locked and cannot be changed.
                                </p>
                            </div>

                            <!-- Bed Number -->
                            <div class="form-group">
                                <label class="form-label">Bed Number <span class="required">*</span></label>
                                <input 
                                    type="text"
                                    name="bed_no"
                                    value="<?= htmlspecialchars($bed['bed_no']); ?>"
                                    required
                                    placeholder="Enter bed number"
                                    class="form-input">
                                <p class="form-hint">Enter a unique bed number within this room</p>
                            </div>

                            <!-- Bed Type -->
                            <div class="form-group">
                                <label class="form-label">Bed Type <span class="required">*</span></label>
                                <select name="bed_type" required class="form-select">
                                    <?php
                                    $types = [
                                        "General", "Private", "Semi Private", "ICU", 
                                        "Emergency", "Pediatric", "Maternity", 
                                        "Orthopedic", "Cardiology", "Isolation"
                                    ];
                                    foreach($types as $type){
                                    ?>
                                    <option value="<?= $type ?>" <?= ($bed['bed_type']==$type)?'selected':''; ?>>
                                        <?= $type ?>
                                    </option>
                                    <?php } ?>
                                </select>
                                <p class="form-hint">Select the category of this bed</p>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="form-label">Status <span class="required">*</span></label>
                                <select name="status" required class="form-select">
                                    <option value="Available" <?= ($bed['status']=="Available")?'selected':''; ?>>Available</option>
                                    <option value="Occupied" <?= ($bed['status']=="Occupied")?'selected':''; ?>>Occupied</option>
                                    <option value="Maintenance" <?= ($bed['status']=="Maintenance")?'selected':''; ?>>Maintenance</option>
                                </select>
                                <p class="form-hint">Choose current availability of the bed</p>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="update" class="btn btn-primary">
                                    Update Bed
                                </button>
                                <a href="view_bed.php?id=<?= $bed['room_id']; ?>" class="btn btn-secondary">
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
