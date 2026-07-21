<?php
include_once '../config/hospital.php';
include_once '../config/permission.php';

// Check Super Admin login
checkSuperAdminLogin();

// Get hospital ID from URL
$hospital_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// If no ID or invalid ID, redirect back to hospitals list
if ($hospital_id == 0) {
    header('Location: hospitals.php');
    exit;
}

// Fetch hospital details
$query = "SELECT h.*, 
          ha.admin_id,
          ha.full_name as admin_name, 
          ha.email as admin_email,
          ha.mobile as admin_phone
          FROM hospital_master h 
          LEFT JOIN hospital_admin ha ON h.hospital_id = ha.hospital_id AND ha.delete_flag = 0
          WHERE h.hospital_id = $hospital_id AND h.delete_flag = 0";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: hospitals.php');
    exit;
}

$hospital = mysqli_fetch_assoc($result);
$theme = $_SESSION['theme'] ?? 'light';

// Update hospital
if (isset($_POST['update_hospital'])) {
    $hospital_name = mysqli_real_escape_string($conn, $_POST['hospital_name']);
    $hospital_code = mysqli_real_escape_string($conn, $_POST['hospital_code']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
    $established_year = mysqli_real_escape_string($conn, $_POST['established_year']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
$admin_name  = mysqli_real_escape_string($conn, $_POST['admin_name']);
$admin_email = mysqli_real_escape_string($conn, $_POST['admin_email']);
$admin_phone = mysqli_real_escape_string($conn, $_POST['admin_phone']);


$reg_name =mysqli_real_escape_string($conn, $_POST['admin_name']);
$reg_email=mysqli_real_escape_string($conn, $_POST['admin_email']);


$reg_update = "UPDATE register SET
    name = '$reg_name',
    email = '$reg_email'
   
WHERE hospital_id = '$hospital_id'
AND delete_flag = 0";


    $admin_update = "UPDATE hospital_admin SET
    full_name = '$admin_name',
    email = '$admin_email',
    mobile = '$admin_phone'
WHERE hospital_id = '$hospital_id'
AND delete_flag = 0";


    $update_query = "UPDATE hospital_master SET 
                     hospital_name = '$hospital_name',
                     hospital_code = '$hospital_code',
                     email = '$email',
                     phone = '$phone',
                     address = '$address',
                     city = '$city',
                     state = '$state',
                     country = '$country',
                     pincode = '$pincode',
                     established_year = '$established_year',
                     status = '$status',
                     modified_at = NOW()
                     WHERE hospital_id = $hospital_id";
    
   if (mysqli_query($conn, $update_query) && mysqli_query($conn, $admin_update) && mysqli_query($conn, $reg_update)  ) {

    logAudit('Hospital', 'Updated hospital: ' . $hospital_name);

    $success = "Hospital and Admin updated successfully!";

    $result = mysqli_query($conn, $query);
    $hospital = mysqli_fetch_assoc($result);

} else {

    $error = mysqli_error($conn);

}
}



$page_title = 'Edit Hospital';
$page_subtitle = 'Update ' . htmlspecialchars($hospital['hospital_name'] ?? 'Hospital') . ' details';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hospital - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        
        body.dark { background: #0a0a0a; }
        body.light { background: #f1f5f9; }
        
      
        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .brand-text h2 { 
            font-size: 0.9rem; 
            font-weight: 700; 
            margin: 0; 
            color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; 
        }
        .brand-text p { 
            font-size: 0.65rem; 
            color: #94a3b8; 
            margin: 0; 
        }
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed { margin-left: 70px; }
        
        .content-card { 
            border-radius: 16px; 
            padding: 1.5rem; 
            transition: all 0.3s ease; 
            margin-bottom: 1.5rem;
        }
        body.dark .content-card { 
            background: #1a1a1a; 
            border: 1px solid #2a2a2a; 
        }
        body.light .content-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.06); 
        }
        
        .form-control { 
            padding: 0.6rem 1rem; 
            border-radius: 10px; 
            transition: all 0.3s ease; 
            width: 100%; 
            outline: none; 
            font-size: 0.9rem; 
        }
        body.dark .form-control { 
            background: #1e1e1e; 
            border: 1px solid #2a2a2a; 
            color: #f1f5f9; 
        }
        body.light .form-control { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            color: #1e293b; 
        }
        .form-control:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); 
        }
        
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            color: white; 
            border: none; 
            padding: 0.6rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); 
        }
        
        .btn-secondary { 
            padding: 0.6rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-secondary:hover { 
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>; 
        }
        
        .btn-back { 
            padding: 0.6rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-back:hover { 
            background: <?php echo $theme == 'dark' ? '#3a3a3a' : '#e2e8f0'; ?>; 
            transform: translateY(-2px);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: white; 
            border: none; 
            padding: 0.6rem 2rem; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-success:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 30px -10px rgba(34, 197, 94, 0.5); 
        }
        
        .status-badge { 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 600; 
            display: inline-block; 
        }
        .status-active { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .status-inactive { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        
        
        .text-primary { color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>; }
        .text-secondary { color: <?php echo $theme == 'dark' ? '#9ca3af' : '#64748b'; ?>; }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
          
            .main-content { margin-left: 200px; padding: 1rem; }
            .main-content.collapsed { margin-left: 60px; }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .main-content.collapsed { margin-left: 0; }
            .action-row {
                flex-direction: column;
                align-items: stretch;
            }
            .action-row a {
                justify-content: center;
            }
        }
        .button-row{
    display:flex;
    flex-direction:row;
    align-items:center;
    justify-content:flex-start;
    gap:15px;
    margin-top:20px;
    grid-column:1 / -1;
}

.button-row .btn-success,
.button-row .btn-secondary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:auto !important;
    min-width:170px;
    white-space:nowrap;
    flex:none;
}
    </style>
</head>
<body class="<?php echo $theme; ?>">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Action Row -->
    <div class="action-row">
        <a href="view_hospital.php?id=<?php echo $hospital['hospital_id']; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>
        <a href="hospitals.php" class="btn-secondary">
            <i class="fas fa-list"></i> All Hospitals
        </a>
    </div>

 

    <!-- Edit Hospital Form -->
    <div class="content-card">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
            <div style="width:50px;height:50px;border-radius:12px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;color:#3b82f6;font-size:1.5rem;">
                <i class="fas fa-hospital"></i>
            </div>
            <div>
                <h3 style="font-size:1.3rem;font-weight:600;color:<?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;margin:0;">
                    Edit Hospital Details
                </h3>
                <p style="color:#94a3b8;font-size:0.85rem;margin:0;">
                    Update information for <?php echo htmlspecialchars($hospital['hospital_name'] ?? 'Hospital'); ?>
                </p>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-grid">
                <!-- Hospital Name -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-hospital"></i> Hospital Name </label>
                    <input type="text" name="hospital_name" class="form-control" value="<?php echo htmlspecialchars($hospital['hospital_name'] ?? ''); ?>" >
                </div>

                <!-- Hospital Code -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-code"></i> Hospital Code </label>
                    <input type="text" name="hospital_code" class="form-control" value="<?php echo htmlspecialchars($hospital['hospital_code'] ?? ''); ?>" >
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email </label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($hospital['email'] ?? ''); ?>" >
                </div>

                <!-- Phone -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-phone"></i> Phone </label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($hospital['phone'] ?? ''); ?>" >
                </div>

                <!-- Address -->
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($hospital['address'] ?? ''); ?></textarea>
                </div>

                <!-- City -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-city"></i> City</label>
                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($hospital['city'] ?? ''); ?>">
                </div>

                <!-- State -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-map"></i> State</label>
                    <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($hospital['state'] ?? ''); ?>">
                </div>

                <!-- Country -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-globe"></i> Country</label>
                    <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($hospital['country'] ?? ''); ?>">
                </div>

                <!-- Pincode -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-mail-bulk"></i> Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="<?php echo htmlspecialchars($hospital['pincode'] ?? ''); ?>">
                </div>

                <!-- Established Year -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-alt"></i> Established Year</label>
                    <input type="text" name="established_year" class="form-control" value="<?php echo htmlspecialchars($hospital['established_year'] ?? ''); ?>" placeholder="e.g. 2010">
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-toggle-on"></i> Status </label>
                    <select name="status" class="form-control" >
                        <option value="Active" <?php echo ($hospital['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($hospital['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
<!-- Admin Information -->
<div style="grid-column: 1 / -1; margin-top:20px;">
    <h4 style="margin-bottom:15px;font-size:16px;font-weight:600;">
        Admin Information
    </h4>

    <div class="form-grid">

        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-user"></i> Full Name
            </label>
            <input type="text"
       name="admin_name"
       class="form-control"
       value="<?php echo htmlspecialchars($hospital['admin_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-envelope"></i> Email
            </label>
           <input type="email"
       name="admin_email"
       class="form-control"
       value="<?php echo htmlspecialchars($hospital['admin_email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-phone"></i> Mobile
            </label>
           <input type="text"
       name="admin_phone"
       class="form-control"
       value="<?php echo htmlspecialchars($hospital['admin_phone'] ?? ''); ?>">
        </div>

    </div>
</div>

            <!-- Admin Information (Read-only) -->
      
<div class="button-row">
    <button type="submit" name="update_hospital" class="btn-success">
        <i class="fas fa-save"></i> Update Hospital
    </button>

    <a href="view_hospital.php?id=<?php echo $hospital['hospital_id']; ?>" class="btn-secondary">
        <i class="fas fa-times"></i> Cancel
    </a>
</div>
</div>

<script>


// ============================================================
// THEME TOGGLE
// ============================================================
function toggleTheme() {
    const body = document.body;
    const currentTheme = body.classList.contains('light') ? 'dark' : 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.classList.remove(currentTheme);
    body.classList.add(newTheme);
    
    fetch('toggle_theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'theme=' + newTheme
    });
}

// ============================================================
// FORM VALIDATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const name = document.querySelector('[name="hospital_name"]').value.trim();
        const code = document.querySelector('[name="hospital_code"]').value.trim();
        const email = document.querySelector('[name="email"]').value.trim();
        const phone = document.querySelector('[name="phone"]').value.trim();
        

        
   
const adminEmail = document.querySelector('[name="email"]').value.trim();

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

if (!emailRegex.test(hospitalEmail)) {
    e.preventDefault();
    alert('Please enter a valid Hospital Email');
    return false;
}

if (!emailRegex.test(adminEmail)) {
    e.preventDefault();
    alert('Please enter a valid Admin Email');
    return false;
}
        
        return true;
    });
});
</script>
</body>
</html>