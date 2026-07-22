<?php 
session_start(); 
include('config/hospital.php');

$hospital_data = null;

$hid=$_SESSION['hospital_id'];
// Fetch from hospital_master instead of hospital_settings
$sql = "select * from hospital_master where hospital_id = $hid";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $hospital_data = $result->fetch_assoc();
}

$message = '';
$message_type = '';
$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $hospital_name = mysqli_real_escape_string($conn, trim($_POST['hospital_name']));
    $hospital_code = mysqli_real_escape_string($conn, trim($_POST['hospital_code']));
    $hospital_type = mysqli_real_escape_string($conn, trim($_POST['hospital_type']));
    $registration_number = mysqli_real_escape_string($conn, trim($_POST['registration_number']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode']));
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $website = mysqli_real_escape_string($conn, trim($_POST['website']));
    $gst_number = strtoupper(mysqli_real_escape_string($conn, trim($_POST['gst_number'])));

    // Store form data for repopulation
    $form_data = [
        'hospital_name' => $hospital_name,
        'hospital_code' => $hospital_code,
        'hospital_type' => $hospital_type,
        'registration_number' => $registration_number,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'pincode' => $pincode,
        'country' => $country,
        'phone' => $phone,
        'website' => $website,
        'gst_number' => $gst_number
    ];

    // Validation
    // Hospital Name validation 
    if(empty($hospital_name)) {
        $errors['hospital_name'] = "Hospital name is required.";
    } elseif(strlen($hospital_name) < 3) {
        $errors['hospital_name'] = "Hospital name must be at least 3 characters.";
    } elseif(!preg_match("/^[a-zA-Z\s\.\-']+$/", $hospital_name)) {
        $errors['hospital_name'] = "Hospital name can only contain letters, spaces, dots, and hyphens.";
    }

    // Hospital Code validation
    if(!empty($hospital_code) && !preg_match("/^[A-Z0-9\-_]+$/", $hospital_code)) {
        $errors['hospital_code'] = "Hospital code can only contain uppercase letters, numbers, hyphens, and underscores.";
    }

    // Hospital Type validation
    if(!empty($hospital_type) && !preg_match("/^[a-zA-Z\s\.\-']+$/", $hospital_type)) {
        $errors['hospital_type'] = "Hospital type can only contain letters, spaces, dots, and hyphens.";
    }

  // Registration Number validation
if(empty($registration_number)) {
    $errors['registration_number'] = "Registration number is required.";
} elseif(!preg_match("/^[A-Z0-9\/\-]+$/", $registration_number)) {
    $errors['registration_number'] = "Registration number can only contain letters (A-Z), numbers (0-9), forward slash (/), and hyphen (-).";
} elseif(strlen($registration_number) > 30) {
    $errors['registration_number'] = "Registration number cannot exceed 30 characters.";
}

    // Phone validation
    if(empty($phone)) {
        $errors['phone'] = "Phone number is required.";
    } elseif(!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $errors['phone'] = "Please enter a valid 10-digit mobile number starting with 6,7,8, or 9.";
    }

    // Pincode validation
    if(empty($pincode)) {
        $errors['pincode'] = "PIN code is required.";
    } elseif(!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        $errors['pincode'] = "Please enter a valid 6-digit PIN code.";
    }

    // GST Number validation
    if(!empty($gst_number) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{3}$/', $gst_number)) {
        $errors['gst_number'] = "Invalid GST Number format. Example: 22AAAAA1234A1Z5";
    }

    // Website validation
    if(!empty($website)) {
        if(!filter_var($website, FILTER_VALIDATE_URL)) {
            $errors['website'] = "Please enter a valid website URL (e.g., https://example.com).";
        }
    }

    // Country validation
    if(!empty($country) && !preg_match("/^[a-zA-Z\s\.\-']+$/", $country)) {
        $errors['country'] = "Country name can only contain letters, spaces, dots, and hyphens.";
    }

    // State validation
    if(!empty($state) && !preg_match("/^[a-zA-Z\s\.\-']+$/", $state)) {
        $errors['state'] = "State name can only contain letters, spaces, dots, and hyphens.";
    }

    // City validation
    if(!empty($city) && !preg_match("/^[a-zA-Z\s\.\-']+$/", $city)) {
        $errors['city'] = "City name can only contain letters, spaces, dots, and hyphens.";
    }

    // Address validation
    if(empty($address)) {
        $errors['address'] = "Address is required.";
    } elseif(strlen($address) < 3) {
        $errors['address'] = "Address must be at least 3 characters.";
    }

    // File validation for logo
    if(isset($_FILES['hospital_logo_file']) && $_FILES['hospital_logo_file']['error'] == 0) {
        $file = $_FILES['hospital_logo_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if(!in_array($file['type'], $allowed_types)) {
            $errors['hospital_logo'] = "Only JPG, PNG, GIF, and WEBP images are allowed for logo.";
        } elseif($file['size'] > $max_size) {
            $errors['hospital_logo'] = "Logo image size must be less than 2MB.";
        } elseif($file['error'] !== UPLOAD_ERR_OK) {
            $errors['hospital_logo'] = "Failed to upload logo. Error code: " . $file['error'];
        }
    }

    if (empty($errors)) {
        // Handle logo upload
        $hospital_logo = $hospital_data['hospital_logo'] ?? 'documents/hospital/logo.png';
        if (!empty($_FILES['hospital_logo_file']['name']) && $_FILES['hospital_logo_file']['error'] == 0) {
            $folder = "documents/hospital/";
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            $logo_name = time() . '_' . basename($_FILES['hospital_logo_file']['name']);
            if (move_uploaded_file($_FILES['hospital_logo_file']['tmp_name'], $folder . $logo_name)) {
                // Delete old logo if exists and not default
                if(!empty($hospital_data['hospital_logo']) && $hospital_data['hospital_logo'] != 'documents/hospital/logo.png' && file_exists($hospital_data['hospital_logo'])) {
                    unlink($hospital_data['hospital_logo']);
                }
                $hospital_logo = $folder . $logo_name;
            }
        }

        if ($hospital_data) {
            // Update existing record
            $update_sql = "UPDATE hospital_master SET 
                hospital_name = '$hospital_name',
                hospital_code = '$hospital_code',
                hospital_logo = '$hospital_logo',
                hospital_type = '$hospital_type',
                registration_number = '$registration_number',
                gst_number = '$gst_number',
                address = '$address',
                city = '$city',
                state = '$state',
                country = '$country',
                pincode = '$pincode',
                phone = '$phone',
                website = '$website',
                modified_at = CURRENT_TIMESTAMP()
                WHERE hospital_id = " . $hospital_data['hospital_id'];
            
            if ($conn->query($update_sql)) {
                $message = "Hospital settings updated successfully!";
                $message_type = "success";
                // Clear form data on success
                $form_data = [];
            } else {
                $message = "Error updating settings: " . $conn->error;
                $message_type = "error";
            }
        } else {
            // Insert new record (if no record exists)
            $insert_sql = "INSERT INTO hospital_master (
                hospital_name, hospital_code, hospital_logo, hospital_type, 
                registration_number, gst_number, address, city, state, 
                country, pincode, phone, website, status, delete_flag
            ) VALUES (
                '$hospital_name', '$hospital_code', '$hospital_logo', '$hospital_type',
                '$registration_number', '$gst_number', '$address', '$city', '$state',
                '$country', '$pincode', '$phone', '$website', '1', '0'
            )";
            
            if ($conn->query($insert_sql)) {
                $message = "Hospital settings saved successfully!";
                $message_type = "success";
                // Get the new record
                $hospital_data = $conn->query("SELECT * FROM hospital_master WHERE hospital_id = " . $conn->insert_id)->fetch_assoc();
                // Clear form data on success
                $form_data = [];
            } else {
                $message = "Error saving settings: " . $conn->error;
                $message_type = "error";
            }
        }
        
        // Refresh data if successful
        if(empty($message_type) || $message_type == 'success') {
            $result = $conn->query("SELECT * FROM hospital_master WHERE hospital_id = $hid");
            $hospital_data = $result->fetch_assoc();
        }
    } else {
        // Set message for validation errors
        $message = "Please fix the errors below.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Hospital Settings · <?php echo $hospital_data['hospital_name'] ?? 'Hospital'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital_data['hospital_logo'] ?? ''; ?>">
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        
        /* Sidebar and Main Content Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
        }

        .main-content { 
            padding: 20px; 
            min-height: 100vh; 
            transition: 0.3s; 
            width: 100%;
        }

        /* Mobile Sidebar behavior */
        @media (max-width: 1279px) {
            #sidebar-container {
                transform: translateX(-100%);
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            #sidebar-container.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
                padding: 16px;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Desktop Sidebar behavior */
        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 256px;
            }
            .main-content {
                margin-left: 256px;
                padding: 32px;
            }
        }

        .settings-card { background: #ffffff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 24px; border: 1px solid #e2e8f0; width: 100%; }
        @media (min-width: 768px) { .settings-card { padding: 40px; } }

        .form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr; gap: 24px; } }

        .full-width { grid-column: 1 / -1; }
        .field-group label { font-weight: 600; font-size: 14px; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .field-group label i { color: #3b82f6; width: 18px; }
        .field-group input, .field-group textarea { padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #fcfdfe; font-size: 15px; outline: none; transition: 0.2s; width: 100%; }
        .field-group input:focus, .field-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
        .field-group input.input-error, .field-group textarea.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
        .field-group .error-text { color: #dc2626; font-size: 0.75rem; font-weight: 500; margin-top: 0.25rem; display: block; }
        
        .logo-upload-container { display: flex; justify-content: center; margin-bottom: 32px; }
        .logo-preview-wrapper { position: relative; width: 120px; height: 120px; }
        @media (min-width: 768px) { .logo-preview-wrapper { width: 140px; height: 140px; } }
        
        .logo-preview-container { width: 100%; height: 100%; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.1); overflow: hidden; background: #f1f5f9; }
        .logo-preview { width: 100%; height: 100%; object-fit: cover; }
        
        .edit-logo-btn { position: absolute; bottom: 5px; right: 5px; width: 36px; height: 36px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 10; }
        .edit-logo-btn:hover { background: #2563eb; transform: scale(1.1); }
        
        .btn-primary { background: #3b82f6; color: #fff; padding: 12px 32px; border-radius: 40px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; }
        @media (min-width: 640px) { .btn-primary { width: auto; } }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .back-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; color: #374151; transition: all 0.2s ease; text-decoration: none; }
        .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        .required { color: #ef4444; margin-left: 2px; }

        #mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            cursor: pointer;
        }
        
        .logo-error-border {
            border-color: #dc2626 !important;
            border-style: solid !important;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>  
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <div class="flex min-h-screen">
       
            <?php include 'Sidebar.php'; ?>  
       
        
        <main class="main-content">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-4">
                   
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900">
                           Hospital Settings
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base mt-1">Manage your hospital profile</p>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span class="text-sm md:text-base"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if(!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-red-600 font-bold text-sm mb-2">Please fix the following errors:</p>
                    <ul class="list-disc list-inside text-red-600 text-sm">
                        <?php foreach($errors as $field => $error_msg): ?>
                            <li><?php echo $error_msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="settings-card">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="logo-upload-container <?php echo isset($errors['hospital_logo']) ? 'logo-error-border' : ''; ?>">
                        <div class="logo-preview-wrapper">
                            <div class="logo-preview-container">
                                <?php if (!empty($hospital_data['hospital_logo']) && file_exists($hospital_data['hospital_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($hospital_data['hospital_logo']); ?>" class="logo-preview" id="logoPreview">
                                <?php else: ?>
                                    <div class="flex items-center justify-center h-full text-slate-400 text-4xl">
                                        <i class="fas fa-hospital"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="edit-logo-btn" onclick="document.getElementById('logoInput').click()">
                                <i class="fas fa-pencil-alt"></i>
                            </div>
                        </div>
                        <input type="file" id="logoInput" name="hospital_logo_file" class="hidden" accept="image/*" onchange="previewLogo(event)">
                        <?php if(isset($errors['hospital_logo'])): ?>
                            <span class="error-text" style="position:absolute; bottom:-25px;"><?php echo $errors['hospital_logo']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-grid">
                        <!-- Hospital Name -->
                        <div class="field-group">
                            <label><i class="fas fa-hospital"></i> Hospital Name</label>
                            <input type="text" name="hospital_name" value="<?php echo htmlspecialchars(!empty($form_data['hospital_name']) ? $form_data['hospital_name'] : ($hospital_data['hospital_name'] ?? '')); ?>" class="<?php echo isset($errors['hospital_name']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['hospital_name'])): ?>
                                <span class="error-text"><?php echo $errors['hospital_name']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Hospital Code -->
                        <div class="field-group">
                            <label><i class="fas fa-code"></i> Hospital Code</label>
                            <input type="text" name="hospital_code" value="<?php echo htmlspecialchars(!empty($form_data['hospital_code']) ? $form_data['hospital_code'] : ($hospital_data['hospital_code'] ?? '')); ?>" class="<?php echo isset($errors['hospital_code']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['hospital_code'])): ?>
                                <span class="error-text"><?php echo $errors['hospital_code']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Hospital Type -->
                        <div class="field-group">
                            <label><i class="fas fa-building"></i> Hospital Type</label>
                            <input type="text" name="hospital_type" value="<?php echo htmlspecialchars(!empty($form_data['hospital_type']) ? $form_data['hospital_type'] : ($hospital_data['hospital_type'] ?? '')); ?>" class="<?php echo isset($errors['hospital_type']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['hospital_type'])): ?>
                                <span class="error-text"><?php echo $errors['hospital_type']; ?></span>
                            <?php endif; ?>
                        </div>

                    <!-- Registration Number -->
<div class="field-group">
    <label><i class="fas fa-id-card"></i> Registration Number </label>
    <input type="text" name="registration_number" value="<?php echo htmlspecialchars(!empty($form_data['registration_number']) ? $form_data['registration_number'] : ($hospital_data['registration_number'] ?? '')); ?>" maxlength="30" placeholder="e.g., HOSP/2026/001234" class="<?php echo isset($errors['registration_number']) ? 'input-error' : ''; ?>" />
    <?php if(isset($errors['registration_number'])): ?>
        <span class="error-text"><?php echo $errors['registration_number']; ?></span>
    <?php endif; ?>
</div>
                        <!-- Website -->
                        <div class="field-group">
                            <label><i class="fas fa-globe"></i> Website</label>
                            <input type="url" name="website" placeholder="e.g., https://example.com" value="<?php echo htmlspecialchars(!empty($form_data['website']) ? $form_data['website'] : ($hospital_data['website'] ?? '')); ?>" class="<?php echo isset($errors['website']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['website'])): ?>
                                <span class="error-text"><?php echo $errors['website']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- GST Number -->
                        <div class="field-group">
                            <label><i class="fas fa-file-invoice"></i> GST Number</label>
                            <input
                                type="text"
                                name="gst_number" placeholder="e.g., 00XXXXX1234X0X0"
                                value="<?php echo htmlspecialchars(!empty($form_data['gst_number']) ? $form_data['gst_number'] : ($hospital_data['gst_number'] ?? '')); ?>"
                                pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{3}"
                                maxlength="15"
                                style="text-transform:uppercase"
                                title="Enter a valid GST Number"
                                class="<?php echo isset($errors['gst_number']) ? 'input-error' : ''; ?>"
                            />
                            <?php if(isset($errors['gst_number'])): ?>
                                <span class="error-text"><?php echo $errors['gst_number']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Primary Phone -->
                        <div class="field-group">
                            <label><i class="fas fa-phone-alt" style="display:inline-block; transform:rotateY(180deg);"></i> Primary Phone </label>
                            <input
                                type="tel"
                                name="phone"
                                value="<?php echo htmlspecialchars(!empty($form_data['phone']) ? $form_data['phone'] : ($hospital_data['phone'] ?? '')); ?>"
                                pattern="[6-9][0-9]{9}"
                                maxlength="10"
                                minlength="10"
                                title="Enter a valid 10-digit mobile number"
                                class="<?php echo isset($errors['phone']) ? 'input-error' : ''; ?>"
                            />
                            <?php if(isset($errors['phone'])): ?>
                                <span class="error-text"><?php echo $errors['phone']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Country -->
                        <div class="field-group">
                            <label><i class="fas fa-map-marked-alt"></i> Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars(!empty($form_data['country']) ? $form_data['country'] : ($hospital_data['country'] ?? 'India')); ?>" class="<?php echo isset($errors['country']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['country'])): ?>
                                <span class="error-text"><?php echo $errors['country']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- State -->
                        <div class="field-group">
                            <label><i class="fas fa-flag"></i> State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars(!empty($form_data['state']) ? $form_data['state'] : ($hospital_data['state'] ?? '')); ?>" class="<?php echo isset($errors['state']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['state'])): ?>
                                <span class="error-text"><?php echo $errors['state']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- City -->
                        <div class="field-group">
                            <label><i class="fas fa-city"></i> City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars(!empty($form_data['city']) ? $form_data['city'] : ($hospital_data['city'] ?? '')); ?>" class="<?php echo isset($errors['city']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['city'])): ?>
                                <span class="error-text"><?php echo $errors['city']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Pincode -->
                        <div class="field-group">
                            <label><i class="fas fa-mail-bulk"></i> Pincode </label>
                            <input type="text" name="pincode" value="<?php echo htmlspecialchars(!empty($form_data['pincode']) ? $form_data['pincode'] : ($hospital_data['pincode'] ?? '')); ?>" maxlength="6" pattern="[1-9][0-9]{5}" title="Enter a valid 6-digit PIN code" class="<?php echo isset($errors['pincode']) ? 'input-error' : ''; ?>" />
                            <?php if(isset($errors['pincode'])): ?>
                                <span class="error-text"><?php echo $errors['pincode']; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Address -->
                        <div class="field-group full-width">
                            <label><i class="fas fa-map-pin"></i> Full Address </label>
                            <textarea rows="2" name="address" class="<?php echo isset($errors['address']) ? 'input-error' : ''; ?>"><?php echo htmlspecialchars(!empty($form_data['address']) ? $form_data['address'] : ($hospital_data['address'] ?? '')); ?></textarea>
                            <?php if(isset($errors['address'])): ?>
                                <span class="error-text"><?php echo $errors['address']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row justify-end gap-4 border-t pt-8">
                        <button type="button" class="px-6 py-2.5 rounded-full font-semibold text-slate-600 hover:bg-slate-100 transition w-full sm:w-auto" onclick="window.history.back()">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebarContainer = document.getElementById('sidebar-container');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            function openSidebar() {
                sidebarContainer.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebarContainer.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

            // Handle the "cross" button inside Sidebar.php if it exists
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
        });

        function previewLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('logoPreview');
                if (img) {
                    img.src = e.target.result;
                } else {
                    const container = document.querySelector('.logo-preview-container');
                    container.innerHTML = `<img src="${e.target.result}" class="logo-preview" id="logoPreview">`;
                }
            };
            reader.readAsDataURL(file);
        }

        // Phone number validation - only digits, max 10
        document.querySelectorAll('input[name="phone"]').forEach(function(input) {
            input.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
            });
        });

        // Pincode validation - only digits, max 6
        document.querySelector('input[name="pincode"]')?.addEventListener("input", function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
</body>
</html>