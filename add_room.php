<?php
session_start();
include "config/hospital.php";

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location: auth/logout.php");
    exit();
}

// Get ward_id from URL
$selected_ward_id = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;
$error_message = "";
$success_message = "";

if(isset($_POST['save'])){
    $ward_id = mysqli_real_escape_string($conn, $_POST['ward_id']);
    $room_no = mysqli_real_escape_string($conn, $_POST['room_no']);
    $capacity = mysqli_real_escape_string($conn, $_POST['capacity']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Server-side Validation with Regex
    if (empty($ward_id) || $ward_id == 0) {
        $error_message = "Please select a ward.";
    } elseif (empty($room_no)) {
        $error_message = "Room number is required.";
    } elseif (!preg_match('/^[A-Za-z0-9\s\-\'&.]+$/', $room_no)) {
        $error_message = "Invalid Room Number. Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.";
    } elseif (!preg_match('/^[0-9]+$/', $capacity) || $capacity < 1) {
        $error_message = "Invalid Capacity. Must be a positive number (at least 1).";
    } elseif (!in_array($status, ['Available', 'Occupied', 'Maintenance'])) {
        $error_message = "Invalid Status selected.";
    } else {
        // Check if room already exists in this ward
        $check = mysqli_query($conn, "SELECT * FROM room_master WHERE room_no='$room_no' AND ward_id='$ward_id' AND (delete_flag=0 OR delete_flag IS NULL)");

        if(mysqli_num_rows($check) > 0){
            $error_message = "Room Number already exists in this ward. Please use a different room number.";
        } else {
            $sql = "INSERT INTO room_master (ward_id, room_no, capacity, status) VALUES ('$ward_id','$room_no','$capacity','$status')";

            if(mysqli_query($conn, $sql)){
                $success_message = "Room Added Successfully!";
                echo "<script>
                    setTimeout(function() {
                        window.location='view_ward.php?id=$ward_id';
                    }, 1500);
                </script>";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
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

// Get all active wards for dropdown (only if no ward_id is passed)
$wards = mysqli_query($conn, "SELECT * FROM ward_master WHERE (delete_flag=0 OR delete_flag IS NULL) AND status='Active' ORDER BY ward_name");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MedixPro - Add Room</title>
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

        /* Ward info box - professional styling */
        .ward-info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .ward-info-box .ward-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ward-info-box .ward-name {
            font-weight: 600;
            color: #1e40af;
            font-size: 14px;
            margin-top: 2px;
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

        /* Two-column layout */
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
                        <a href="<?php echo $selected_ward_id > 0 ? 'view_ward.php?id=' . $selected_ward_id : 'room_master.php'; ?>" class="back-btn" title="Back">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title">Add New Room</h1>
                            <p class="page-subtitle">Create a new room in the hospital ward</p>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="form-card">
                        <div class="form-header">
                            <h2>Room Information</h2>
                        </div>
                        
                        <form method="post" class="form-body" id="roomForm" novalidate>
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

                            <!-- Ward Info Box (when ward_id is passed) -->
                            <?php if ($selected_ward_id > 0 && !empty($ward_name)): ?>
                            <div class="ward-info-box">
                                <div class="ward-label">Adding room to ward</div>
                                <div class="ward-name"><?php echo htmlspecialchars($ward_name); ?></div>
                            </div>
                            <?php endif; ?>

                            <!-- Two-column layout for Ward and Room Number -->
                            <div class="form-row">
                                <!-- Ward Selection -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Ward <span class="required">*</span>
                                    </label>
                                    <?php if ($selected_ward_id > 0): ?>
                                        <!-- Hidden input to submit ward_id -->
                                        <input type="hidden" name="ward_id" value="<?php echo $selected_ward_id; ?>">
                                        <!-- Read-only display of ward name -->
                                        <input 
                                            type="text"
                                            value="<?php echo htmlspecialchars($ward_name); ?>"
                                            disabled
                                            class="form-input form-input-readonly">
                                        <p class="form-hint">Ward is locked</p>
                                    <?php else: ?>
                                        <!-- Regular dropdown when no ward_id is passed -->
                                        <select name="ward_id" id="ward_id" required class="form-select">
                                            <option value="">Select Ward</option>
                                            <?php 
                                            mysqli_data_seek($wards, 0);
                                            while($ward = mysqli_fetch_assoc($wards)): 
                                            ?>
                                                <option value="<?php echo $ward['ward_id']; ?>">
                                                    <?php echo htmlspecialchars($ward['ward_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <p class="form-hint">Select the ward where this room belongs</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Room Number -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Room Number <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input 
                                            type="text"
                                            name="room_no"
                                            id="room_no"
                                            required
                                            placeholder="e.g., 101, A-101, ICU-01"
                                            class="form-input"
                                            pattern="^[A-Za-z0-9\s\-\'&.]+$"
                                            data-validation="room_no"
                                            title="Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.">
                                    </div>
                                    <p class="form-hint" id="room_no_hint">
                                        <i class="fas fa-info-circle"></i> Enter a unique room number within this ward
                                    </p>
                                    <div class="validation-message error" id="room_no_error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.</span>
                                    </div>
                                    <div class="validation-message success" id="room_no_success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Valid room number</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Two-column layout for Capacity and Status -->
                            <div class="form-row">
                                <!-- Capacity -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Capacity <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input 
                                            type="number"
                                            name="capacity"
                                            id="capacity"
                                            required
                                            min="1"
                                            placeholder="Number of beds in room"
                                            class="form-input"
                                            data-validation="capacity"
                                            title="Must be a positive number (at least 1)">
                                    </div>
                                    <p class="form-hint" id="capacity_hint">
                                        <i class="fas fa-info-circle"></i> Enter the maximum number of beds in this room
                                    </p>
                                    <div class="validation-message error" id="capacity_error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Must be a positive number (at least 1)</span>
                                    </div>
                                    <div class="validation-message success" id="capacity_success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Valid capacity</span>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Status <span class="required">*</span>
                                    </label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="Available">Available</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                    <p class="form-hint">
                                        <i class="fas fa-info-circle"></i> Choose whether the room is Available, Occupied, or under Maintenance
                                    </p>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="save" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Room
                                </button>
                                <a href="<?php echo $selected_ward_id > 0 ? 'view_ward.php?id=' . $selected_ward_id : 'room_master.php'; ?>" class="btn btn-secondary">
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
                room_no: /^[A-Za-z0-9\s\-\'&.]+$/,
                capacity: /^[0-9]+$/
            };

            // Get fields that need validation
            const fields = {
                room_no: { pattern: patterns.room_no, required: true },
                capacity: { pattern: patterns.capacity, required: true }
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
                        if (fieldId === 'room_no') {
                            errorMsg.querySelector('span').textContent = 'Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.';
                        } else if (fieldId === 'capacity') {
                            errorMsg.querySelector('span').textContent = 'Must be a positive number (at least 1)';
                        }
                        errorMsg.classList.add('show');
                    }
                    if (hint) hint.style.display = 'none';
                    return false;
                }

                // Special validation for capacity
                if (fieldId === 'capacity' && value) {
                    const numValue = parseInt(value);
                    if (isNaN(numValue) || numValue < 1) {
                        input.classList.add('error');
                        if (errorMsg) {
                            errorMsg.querySelector('span').textContent = 'Must be a positive number (at least 1)';
                            errorMsg.classList.add('show');
                        }
                        if (hint) hint.style.display = 'none';
                        return false;
                    }
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
                    // For capacity, only allow digits
                    if (fieldId === 'capacity') {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    }
                    validateField(fieldId);
                });
            });

            // Ward selection validation for dropdown
            const wardSelect = document.getElementById('ward_id');
            if (wardSelect && !wardSelect.disabled) {
                wardSelect.addEventListener('change', function() {
                    if (this.value === '') {
                        this.classList.add('error');
                    } else {
                        this.classList.remove('error');
                        this.classList.add('success');
                    }
                });
            }

            // Form submission validation
            document.getElementById('roomForm').addEventListener('submit', function(e) {
                let isValid = true;

                // Validate all fields
                Object.keys(fields).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                // Validate ward selection
                const wardSelect = document.getElementById('ward_id');
                if (wardSelect && !wardSelect.disabled && wardSelect.value === '') {
                    wardSelect.classList.add('error');
                    isValid = false;
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