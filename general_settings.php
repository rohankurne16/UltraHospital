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

    // Validation
    if (empty($hospital_name)) {
        $message = "Hospital name is required.";
        $message_type = "error";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $message = "Please enter a valid 10-digit primary phone number.";
        $message_type = "error";
    } elseif (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        $message = "Please enter a valid 6-digit PIN code.";
        $message_type = "error";
    } elseif (!empty($gst_number) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{3}$/', strtoupper($gst_number))) {
        $message = "Invalid GST Number.";
        $message_type = "error";
    }

    if (empty($message)) {
        // Handle logo upload
        $hospital_logo = $hospital_data['hospital_logo'] ?? 'documents/hospital/logo.png';
        if (!empty($_FILES['hospital_logo_file']['name'])) {
            $folder = "documents/hospital/";
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            $logo_name = $_FILES['hospital_logo_file']['name'];
            if (move_uploaded_file($_FILES['hospital_logo_file']['tmp_name'], $folder . $logo_name)) {
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
            } else {
                $message = "Error saving settings: " . $conn->error;
                $message_type = "error";
            }
        }
        
        // Refresh data
        $result = $conn->query("SELECT * FROM hospital_master WHERE hospital_id = $hid");
        $hospital_data = $result->fetch_assoc();
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

            <div class="settings-card">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="logo-upload-container">
                        <div class="logo-preview-wrapper">
                            <div class="logo-preview-container">
                                <?php if (!empty($hospital_data['hospital_logo'])): ?>
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
                    </div>

                    <div class="form-grid">
                        <!-- Hospital Name -->
                        <div class="field-group">
                            <label><i class="fas fa-hospital"></i> Hospital Name <span class="required">*</span></label>
                            <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($hospital_data['hospital_name'] ?? ''); ?>" required />
                        </div>

                        <!-- Hospital Code -->
                        <div class="field-group">
                            <label><i class="fas fa-code"></i> Hospital Code</label>
                            <input type="text" name="hospital_code" value="<?php echo htmlspecialchars($hospital_data['hospital_code'] ?? ''); ?>" />
                        </div>

                        <!-- Hospital Type -->
                        <div class="field-group">
                            <label><i class="fas fa-building"></i> Hospital Type</label>
                            <input type="text" name="hospital_type" value="<?php echo htmlspecialchars($hospital_data['hospital_type'] ?? ''); ?>" />
                        </div>

                        <!-- Registration Number -->
                        <div class="field-group">
                            <label><i class="fas fa-id-card"></i> Registration Number</label>
                            <input type="text" name="registration_number" value="<?php echo htmlspecialchars($hospital_data['registration_number'] ?? ''); ?>" />
                        </div>

                        <!-- Website -->
                        <div class="field-group">
                            <label><i class="fas fa-globe"></i> Website</label>
                            <input type="url" name="website" value="<?php echo htmlspecialchars($hospital_data['website'] ?? ''); ?>" />
                        </div>

                        <!-- GST Number -->
                        <div class="field-group">
                            <label><i class="fas fa-file-invoice"></i> GST Number</label>
                            <input
                                type="text"
                                name="gst_number"
                                value="<?php echo htmlspecialchars($hospital_data['gst_number'] ?? ''); ?>"
                                pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{3}"
                                maxlength="15"
                                style="text-transform:uppercase"
                                title="Enter a valid GST Number"
                            />
                        </div>

                        <!-- Primary Phone -->
                        <div class="field-group">
                            <label><i class="fas fa-phone-alt" style="display:inline-block; transform:rotateY(180deg);"></i> Primary Phone <span class="required">*</span></label>
                            <input
                                type="tel"
                                name="phone"
                                value="<?php echo htmlspecialchars($hospital_data['phone'] ?? ''); ?>"
                                pattern="[6-9][0-9]{9}"
                                maxlength="10"
                                minlength="10"
                                required
                                title="Enter a valid 10-digit mobile number"
                            />
                        </div>

                        <!-- Country -->
                        <div class="field-group">
                            <label><i class="fas fa-map-marked-alt"></i> Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($hospital_data['country'] ?? 'India'); ?>" />
                        </div>

                        <!-- State -->
                        <div class="field-group">
                            <label><i class="fas fa-flag"></i> State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($hospital_data['state'] ?? ''); ?>" />
                        </div>

                        <!-- City -->
                        <div class="field-group">
                            <label><i class="fas fa-city"></i> City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($hospital_data['city'] ?? ''); ?>" />
                        </div>

                        <!-- Pincode -->
                        <div class="field-group">
                            <label><i class="fas fa-mail-bulk"></i> Pincode</label>
                            <input type="text" name="pincode" value="<?php echo htmlspecialchars($hospital_data['pincode'] ?? ''); ?>" maxlength="6" pattern="[1-9][0-9]{5}" title="Enter a valid 6-digit PIN code" />
                        </div>

                        <!-- Address -->
                        <div class="field-group full-width">
                            <label><i class="fas fa-map-pin"></i> Full Address</label>
                            <textarea rows="2" name="address" required><?php echo htmlspecialchars($hospital_data['address'] ?? ''); ?></textarea>
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
