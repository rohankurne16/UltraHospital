<?php
session_start();

include "config/hospital.php";

if(isset($_POST['save'])){
    $ward_name = mysqli_real_escape_string($conn, $_POST['ward_name']);
    $ward_type = mysqli_real_escape_string($conn, $_POST['ward_type']);
    $floor_no = mysqli_real_escape_string($conn, $_POST['floor_no']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check if ward already exists
    $check_sql = "SELECT * FROM ward_master WHERE ward_name = '$ward_name' and hospital_id='$hid' AND (delete_flag = 0 or delete_flag is null)";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0){
        $error_message = "Ward \"$ward_name\" already exists! Please use a different name.";
    } else {
        $sql = "INSERT INTO ward_master (ward_name, ward_type, floor_no, status) VALUES ('$ward_name', '$ward_type', '$floor_no', '$status')";
        
        if(mysqli_query($conn, $sql)){
            $success_message = "Ward added successfully!";
            echo "<script>
                setTimeout(function() {
                    window.location.href='ward_master.php';
                }, 1500);
            </script>";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// Handle AJAX request for checking duplicate ward name
if(isset($_POST['check_ward_name'])){
    $ward_name = mysqli_real_escape_string($conn, $_POST['check_ward_name']);
    
    $check_sql = "SELECT * FROM ward_master WHERE ward_name = '$ward_name' AND delete_flag = 0";
    $check_result = mysqli_query($conn, $check_sql);
    
    $response = array('exists' => false);
    
    if(mysqli_num_rows($check_result) > 0){
        $response['exists'] = true;
    }
    
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $hospital['hospital_name'] ?> -Add Ward</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
      
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
            text-transform: capitalize;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
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
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #f0f9ff;
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #94a3b8;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
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

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

        /* Two-column layout for larger screens */
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
        <!-- Header -->
        <?php include 'header.php'; ?>
        
        <div class="flex flex-1 items-start">
            <!-- Sidebar -->
            <?php include 'Sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="main-content">
                <div class="max-w-full mx-auto w-full">
                    
                    <!-- Page Header -->
                    <div class="page-header">
                        <a href="ward_master.php" class="back-btn" title="Back to Ward List">
                             <i class="fa-solid fa-arrow-left"></i> 
                        </a>
                        <div>
                            <h1 class="page-title">
                                
                                Add New Ward
                            </h1>
                            <p class="page-subtitle">Create a new hospital ward with details</p>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="form-card">
                        <div class="form-header">
                            <h2>
                                
                                Ward Information
                            </h2>
                        </div>
                        
                        <form method="POST" class="form-body" action="" id="wardForm">
                            <!-- Display Success/Error Messages -->
                            <?php if(isset($success_message)): ?>
                                <div class="alert alert-success">
                                    
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if(isset($error_message)): ?>
                                <div class="alert alert-error">
                                    
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Two-column layout for ward name and type -->
                            <div class="form-row">
                                <!-- Ward Name -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Ward Name <span class="required">*</span>
                                    </label>
                                    <div class="input-icon">
                                        
                                        <input 
                                            type="text"
                                            name="ward_name"
                                            id="ward_name"
                                            placeholder="e.g., ICU Ward, General Ward"
                                            required
                                            class="form-input"
                                            value="<?php echo isset($_POST['ward_name']) ? htmlspecialchars($_POST['ward_name']) : ''; ?>">
                                    </div>
                                    <p class="form-hint" id="ward_name_hint">
                                         Enter the name of the ward
                                    </p>
                                </div>

                                <!-- Ward Type -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Ward Type <span class="required">*</span>
                                    </label>
                                    <div class="input-icon">
                                        
                                        <input 
                                            type="text"
                                            name="ward_type"
                                            placeholder="e.g., ICU, General, Private"
                                            required
                                            class="form-input"
                                            value="<?php echo isset($_POST['ward_type']) ? htmlspecialchars($_POST['ward_type']) : ''; ?>">
                                    </div>
                                    <p class="form-hint"> Specify the type or category of the ward</p>
                                </div>
                            </div>

                            <!-- Two-column layout for floor and status -->
                            <div class="form-row">
                                <!-- Floor Number -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Floor Number <span class="required">*</span>
                                    </label>
                                    <div class="input-icon">
                                        
                                        <input 
                                            type="number"
                                            name="floor_no"
                                            placeholder="e.g., 1, 2, 3"
                                            required
                                            min="0"
                                            class="form-input"
                                            value="<?php echo isset($_POST['floor_no']) ? htmlspecialchars($_POST['floor_no']) : ''; ?>">
                                    </div>
                                    <p class="form-hint"> Enter the floor number where the ward is located</p>
                                </div>

                                <!-- Status -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Status <span class="required">*</span>
                                    </label>
                                    <div class="input-icon">
                                        
                                        <select name="status" required class="form-select">
                                            <option value="">-- Select Status --</option>
                                            <option value="Available" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                            <option value="Occupied" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                                        </select>
                                    </div>
                                    <p class="form-hint"> Choose whether the ward is Available or Occupied</p>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="save" class="btn btn-primary" id="submitBtn">
                                    
                                    Save Ward
                                </button>
                                <a href="ward_master.php" class="btn btn-secondary">
                                    
                                    Cancel
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
            const wardNameInput = document.getElementById('ward_name');
            const wardNameHint = document.getElementById('ward_name_hint');
            const submitBtn = document.getElementById('submitBtn');
            let isDuplicate = false;

            // Function to check ward name
            function checkWardName() {
                const wardName = wardNameInput.value.trim();
                
                if (wardName.length === 0) {
                    wardNameHint.className = 'form-hint';
                    wardNameHint.innerHTML = ' Enter the name of the ward';
                    wardNameInput.className = 'form-input';
                    isDuplicate = false;
                    submitBtn.disabled = false;
                    return;
                }

                // Show checking status
                wardNameHint.className = 'form-hint';
                wardNameHint.innerHTML = ' Checking availability...';
                wardNameInput.className = 'form-input';

                // AJAX request to check duplicate
                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.exists) {
                                wardNameHint.className = 'form-hint error';
                                wardNameHint.innerHTML = ' Ward "' + wardName + '" already exists! Please use a different name.';
                                wardNameInput.className = 'form-input error';
                                isDuplicate = true;
                                submitBtn.disabled = true;
                            } else {
                                wardNameHint.className = 'form-hint success';
                                wardNameHint.innerHTML = ' Ward name is available ✓';
                                wardNameInput.className = 'form-input success';
                                isDuplicate = false;
                                submitBtn.disabled = false;
                            }
                        } catch(e) {
                            console.error('Error parsing response:', e);
                        }
                    }
                };
                xhr.send('check_ward_name=' + encodeURIComponent(wardName));
            }

            // Check on blur (when user leaves the field)
            wardNameInput.addEventListener('blur', checkWardName);

            // Reset on input (when user types)
            wardNameInput.addEventListener('input', function() {
                if (this.value.trim().length === 0) {
                    wardNameHint.className = 'form-hint';
                    wardNameHint.innerHTML = ' Enter the name of the ward';
                    this.className = 'form-input';
                    isDuplicate = false;
                    submitBtn.disabled = false;
                } else {
                    // Reset to checking state
                    wardNameHint.className = 'form-hint';
                    wardNameHint.innerHTML = ' Checking availability...';
                    this.className = 'form-input';
                }
            });

            // Prevent form submission if duplicate exists
            document.getElementById('wardForm').addEventListener('submit', function(e) {
                if (isDuplicate) {
                    e.preventDefault();
                    wardNameHint.className = 'form-hint error';
                    wardNameHint.innerHTML = ' Please change the ward name before submitting.';
                    wardNameInput.className = 'form-input error';
                    wardNameInput.focus();
                }
            });
        });
    </script>

</body>
</html>