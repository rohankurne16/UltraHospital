<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location:../auth/logout.php");
    exit();
}

$hid = (int)$_SESSION["hospital_id"];

// Get room_id and ward_id from URL
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$ward_id = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;

if ($room_id == 0) {
    header("Location: room_master.php");
    exit();
}

// Fetch Room Details
$roomQuery = "
    SELECT r.*, w.ward_name
    FROM room_master r
    LEFT JOIN ward_master w
        ON r.ward_id = w.ward_id
    WHERE r.room_id = '$room_id'
    AND r.hospital_id = '$hid'
    AND (r.delete_flag = 0 OR r.delete_flag IS NULL)
";

$roomResult = mysqli_query($conn, $roomQuery);

if (!$roomResult) {
    die("Room Query Error : " . mysqli_error($conn));
}

if (mysqli_num_rows($roomResult) == 0) {
    header("Location: room_master.php");
    exit();
}

$room = mysqli_fetch_assoc($roomResult);

// If ward_id not passed
if ($ward_id == 0) {
    $ward_id = $room['ward_id'];
}

// Get Ward Name
$ward_name = "";

if ($ward_id > 0) {

    $wardQuery = "
        SELECT ward_name
        FROM ward_master
        WHERE ward_id = '$ward_id'
        AND hospital_id = '$hid'
        AND (delete_flag = 0 OR delete_flag IS NULL)
    ";

    $wardResult = mysqli_query($conn, $wardQuery);

    if ($wardResult && mysqli_num_rows($wardResult) > 0) {
        $ward = mysqli_fetch_assoc($wardResult);
        $ward_name = $ward['ward_name'];
    }
}

$error_message = "";
$success_message = "";

if (isset($_POST['save'])) {

    $room_id  = mysqli_real_escape_string($conn, $_POST['room_id']);
    $bed_no   = trim(mysqli_real_escape_string($conn, $_POST['bed_no']));
    $bed_type = trim(mysqli_real_escape_string($conn, $_POST['bed_type']));
    $status   = mysqli_real_escape_string($conn, $_POST['status']);

    // Validation
    if (empty($bed_no)) {

        $error_message = "Bed Number is required.";

    } elseif (!preg_match("/^[A-Za-z0-9\s\-\'&.]+$/", $bed_no)) {

        $error_message = "Invalid Bed Number.";

    } elseif (!preg_match("/^[A-Za-z0-9\s\-\'&.]+$/", $bed_type)) {

        $error_message = "Invalid Bed Type.";

    } elseif (!in_array($status, ['Available','Occupied','Maintenance'])) {

        $error_message = "Invalid Status.";

    } else {

        // Check Duplicate Bed
        $checkQuery = "
            SELECT bed_id
            FROM bed_master
            WHERE bed_no = '$bed_no'
            AND room_id = '$room_id'
            AND hospital_id = '$hid'
            AND (delete_flag = 0 OR delete_flag IS NULL)
        ";

        $checkResult = mysqli_query($conn, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {

            $error_message = "Bed Number already exists in this room.";

        } else {

            $insertQuery = "
                INSERT INTO bed_master
                (
                    room_id,
                    bed_no,
                    bed_type,
                    status,
                    hospital_id
                )
                VALUES
                (
                    '$room_id',
                    '$bed_no',
                    '$bed_type',
                    '$status',
                    '$hid'
                )
            ";

            if (mysqli_query($conn, $insertQuery)) {

                $success_message = "Bed Added Successfully!";

                echo "
                <script>
                    setTimeout(function(){
                        window.location='view_bed.php?id=$room_id&ward_id=$ward_id';
                    },1500);
                </script>";

            } else {

                $error_message = mysqli_error($conn);

            }
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
            transition: all 0.3s ease;
        }

        .form-hint.error {
            color: #ef4444;
        }

        .form-hint.success {
            color: #22c55e;
        }

        .form-input.error,
        .form-select.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .form-input.error:focus,
        .form-select.error:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-input.success {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .form-input.success:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .validation-message {
            font-size: 12px;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        .validation-message.show { display: flex; }
        .validation-message.error { color: #ef4444; }
        .validation-message.success { color: #22c55e; }

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

        /* Alert messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert i {
            font-size: 18px;
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
                        
                        <form method="post" class="form-body" id="bedForm" novalidate>
                            <!-- Display Success/Error Messages -->
                          <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>

                           <?php if (!empty($error_message)): ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Room Info Box -->
                            <div class="room-info-box">
                                <i class="fas fa-bed" style="color: #3b82f6; font-size: 18px;"></i>
                                <div>
                                    <span class="info-label">Adding bed to room:</span>
                                    <span class="room-name">
                                        <?php 
                                        // Use the correct column name - adjust based on your table structure
                                        if (isset($room['room_name'])) {
                                            echo htmlspecialchars($room['room_name']);
                                        } elseif (isset($room['room_number'])) {
                                            echo htmlspecialchars($room['room_number']);
                                        } elseif (isset($room['room_no'])) {
                                            echo htmlspecialchars($room['room_no']);
                                        } else {
                                            echo "Room #" . $room_id;
                                        }
                                        ?>
                                    </span>
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
                                    <div class="input-wrapper">
                                        <input 
                                            type="text"
                                            name="bed_no"
                                            id="bed_no"
                                            required
                                            placeholder="e.g., 1, A-1, ICU-01"
                                            class="form-input"
                                            pattern="^[A-Za-z0-9\s\-\'&.]+$"
                                            data-validation="bed_no"
                                            title="Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.">
                                    </div>
                                    <p class="form-hint" id="bed_no_hint">
                                        <i class="fas fa-info-circle"></i> Enter a unique bed number within this room
                                    </p>
                                    <div class="validation-message error" id="bed_no_error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.</span>
                                    </div>
                                    <div class="validation-message success" id="bed_no_success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Valid bed number</span>
                                    </div>
                                </div>

                                <!-- Bed Type -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Bed Type <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input 
                                            type="text"
                                            name="bed_type"
                                            id="bed_type"
                                            required
                                            placeholder="e.g., ICU, General, Private"
                                            class="form-input"
                                            pattern="^[A-Za-z0-9\s\-\'&.]+$"
                                            data-validation="bed_type"
                                            title="Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.">
                                    </div>
                                    <p class="form-hint" id="bed_type_hint">
                                        <i class="fas fa-info-circle"></i> Specify the type or category of the bed
                                    </p>
                                    <div class="validation-message error" id="bed_type_error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.</span>
                                    </div>
                                    <div class="validation-message success" id="bed_type_success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Valid bed type</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="form-label">
                                    Status <span class="required">*</span>
                                </label>
                                <div class="input-icon">
                                    <select name="status" id="status" class="form-select">
                                        <option value="Available">Available</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                </div>
                                <p class="form-hint">
                                    <i class="fas fa-info-circle"></i> Choose the current status of the bed
                                </p>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="save" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Bed
                                </button>
                                <a href="view_bed.php?id=<?php echo $room_id; ?>&ward_id=<?php echo $ward_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ============================================================
            // VALIDATION LOGIC
            // ============================================================
            
            // Define validation patterns
            const patterns = {
                bed_no: /^[A-Za-z0-9\s\-\'&.]+$/,
                bed_type: /^[A-Za-z0-9\s\-\'&.]+$/
            };

            // Get fields that need validation
            const fields = {
                bed_no: { pattern: patterns.bed_no, required: true },
                bed_type: { pattern: patterns.bed_type, required: true }
            };

            // Function to validate a single field
            function validateField(fieldId) {
                const input = document.getElementById(fieldId);
                if (!input) return true;

                const value = input.value.trim();
                const fieldConfig = fields[fieldId];
                const isRequired = fieldConfig ? fieldConfig.required : false;
                const pattern = fieldConfig ? fieldConfig.pattern : null;

                const errorMsg = document.getElementById(fieldId + '_error');
                const successMsg = document.getElementById(fieldId + '_success');
                const hint = document.getElementById(fieldId + '_hint');

                // Reset states
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (hint) {
                    hint.classList.remove('error', 'success');
                    hint.style.display = 'block';
                }

                // Check if empty and required
                if (isRequired && value === '') {
                    input.classList.add('error');
                    if (errorMsg) {
                        errorMsg.querySelector('span').textContent = 'This field is required';
                        errorMsg.classList.add('show');
                    }
                    if (hint) hint.style.display = 'none';
                    return false;
                }

                // If optional and empty, it's valid
                if (!isRequired && value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (hint) hint.style.display = 'none';
                    return true;
                }

                // Test against pattern
                if (pattern && !pattern.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) {
                        errorMsg.querySelector('span').textContent = 'Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.';
                        errorMsg.classList.add('show');
                    }
                    if (hint) hint.style.display = 'none';
                    return false;
                }

                // All validations passed
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (hint) hint.style.display = 'none';
                return true;
            }

            // Attach event listeners for real-time validation
            Object.keys(fields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                // Validate on blur
                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });

                // Validate on input for better UX
                input.addEventListener('input', function() {
                    validateField(fieldId);
                });
            });

            // Form submission validation
            document.getElementById('bedForm').addEventListener('submit', function(e) {
                let isValid = true;

                // Validate all fields
                Object.keys(fields).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                // Validate status dropdown
                const statusSelect = document.getElementById('status');
                if (statusSelect && statusSelect.value === '') {
                    statusSelect.classList.add('error');
                    isValid = false;
                } else if (statusSelect) {
                    statusSelect.classList.remove('error');
                }

                if (!isValid) {
                    e.preventDefault();
                    const firstError = document.querySelector('.form-input.error, .form-select.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    </script>

</body>
</html>