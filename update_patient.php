<?php
session_start(); 
include "config/hospital.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = $_POST['newpatient_id'];

    $get = "select * from patients where patient_id='$id'";
    $res = mysqli_query($conn, $get);
    $patient = mysqli_fetch_assoc($res);

    $register_id = $patient["register_id"];
    $patient_image = $patient['patient_image'];

    if (isset($_FILES['newpatient_image']) && $_FILES['newpatient_image']['error'] == 0) {
        $folder = "documents/patients/images/";
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        $filename = basename($_FILES['newpatient_image']['name']);
        if (move_uploaded_file($_FILES['newpatient_image']['tmp_name'], $folder . $filename)) {
            $patient_image = "documents/patients/images/" . $filename;
        } else {
            die("Image upload failed!");
        }
    }

    $patient_name       = mysqli_real_escape_string($conn, $_POST['newpatient_name']);
    $dob                = $_POST['newdob'];
    $age                = $_POST['newage'];
    $blood_group        = $_POST['newblood_group'];
    $gender             = $_POST['newgender'];
    $address            = mysqli_real_escape_string($conn, $_POST['newaddress']);
    $mobile             = $_POST['newmobile'];
    $email              = $_POST['newemail'];
    $emergency_contact  = $_POST['newemergency_contact'];
    $medical_history    = mysqli_real_escape_string($conn, $_POST['newmedical_history']);
    $allergy            = mysqli_real_escape_string($conn, $_POST['newallergy']);
    $status             = $_POST['newstatus'];

    $sql = "update patients set patient_name='$patient_name', patient_image='$patient_image', date_of_birth='$dob', age='$age', blood_group='$blood_group', gender='$gender', address='$address', mobile='$mobile', email='$email', emergency_contact='$emergency_contact', medical_history='$medical_history', allergy='$allergy', status='$status' where patient_id='$id'";

    if (mysqli_query($conn, $sql)) {
        $sql2 = "update register set name='$patient_name', email='$email', modified_by='Admin' where id='$register_id'";
        if(mysqli_query($conn, $sql2)){
            $res = mysqli_query($conn, "select * from patients where patient_id='$id'");
            $patient = mysqli_fetch_assoc($res);
            header("Location:patients.php?msg=success");
            exit();
        }   
    } else {
        $error = mysqli_error($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $res = mysqli_query($conn, "select * from patients where patient_id='$id'");
        if(mysqli_num_rows($res)>0){
            $patient = mysqli_fetch_assoc($res);
        } else {
            $error = "Patient not found.";
        }
    } else {
        $error = "Invalid Patient ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $hospital['hospital_name'] ?>- Edit Patient</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .main-content { margin-left: 260px; padding: 32px; min-height: 100vh; transition: 0.3s; }
        .form-container { width: 100%; margin: 0 auto; max-width: 1200px; }
        .form-card { background: white; border-radius: 20px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; }
        .form-card .header { padding: 24px 32px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; display: flex; align-items: center; gap: 12px; }
        .form-card .header .header-icon { width: 44px; height: 44px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; }
        .form-card .header h3 { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
        .form-card .header .subtitle { font-size: 14px; color: #64748b; font-weight: 400; }
        .form-card .body { padding: 32px 40px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .full-width { grid-column: 1 / -1; }
        .field-group label { font-weight: 600; font-size: 14px; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .field-group label i { color: #3b82f6; width: 18px; }
        .field-group input, .field-group select, .field-group textarea { padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #fcfdfe; font-size: 15px; outline: none; transition: 0.2s; width: 100%; }
        .field-group input:focus, .field-group select:focus, .field-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
        .image-upload-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 40px; }
        .image-preview-wrapper { position: relative; width: 140px; height: 140px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.1); overflow: hidden; background: #f1f5f9; cursor: pointer; transition: 0.3s; }
        .image-preview-wrapper:hover { transform: scale(1.03); border-color: #3b82f6; }
        .image-preview { width: 100%; height: 100%; object-fit: cover; }
        .camera-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: #fff; transition: 0.3s; opacity: 0; }
        .image-preview-wrapper:hover .camera-overlay { opacity: 1; }
        .btn-primary { background: #3b82f6; color: #fff; padding: 12px 32px; border-radius: 40px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .back-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; color: #374151; transition: all 0.2s ease; text-decoration: none; }
        .back-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>  
    <div class="flex min-h-screen">
        <?php include 'Sidebar.php'; ?>  
        <main class="main-content w-full">
            <div class="form-container">
                <div class="flex items-center gap-4 mb-8">
                    <a href="patients.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Edit Patient</h1>
                        <p class="text-slate-500 mt-1">Update information for <?php echo htmlspecialchars($patient['patient_name'] ?? 'Patient'); ?></p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($patient): ?>
                <div class="form-card">
                    <div class="header">
                        <div class="header-icon"><i class="fas fa-user"></i></div>
                        <div>
                            <h3>Patient Details</h3>
                            <div class="subtitle">Update personal and medical information</div>
                        </div>
                    </div>

                    <div class="body">
                        <form action="update_patient.php?id=<?php echo $patient['patient_id']; ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="newpatient_id" value="<?php echo $patient['patient_id']; ?>">
                            
                            <div class="image-upload-container">
                                <div class="image-preview-wrapper" onclick="document.getElementById('imageInput').click()">
                                    <?php if (!empty($patient['patient_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['patient_image']); ?>" class="image-preview" id="imagePreview">
                                    <?php else: ?>
                                        <div class="flex items-center justify-center h-full bg-blue-600 text-white text-4xl font-bold">
                                            <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="camera-overlay">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                <input type="file" id="imageInput" name="newpatient_image" class="hidden" accept="image/*" onchange="previewImage(event)">
                                <p class="text-xs text-slate-400 mt-3">Click to update photo</p>
                            </div>

                            <div class="form-grid">
                                <div class="field-group">
                                    <label><i class="fas fa-user"></i> Patient Name</label>
                                    <input type="text" name="newpatient_name" value="<?php echo htmlspecialchars($patient['patient_name']); ?>" required />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                                    <input type="date" name="newdob" value="<?php echo $patient['date_of_birth']; ?>" />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-birthday-cake"></i> Age</label>
                                    <input type="number" name="newage" value="<?php echo $patient['age']; ?>" />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-tint"></i> Blood Group</label>
                                    <input type="text" name="newblood_group" value="<?php echo htmlspecialchars($patient['blood_group']); ?>" />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-venus-mars"></i> Gender</label>
                                    <select name="newgender">
                                        <option value="Male" <?php if($patient['gender']=="Male") echo "selected"; ?>>Male</option>
                                        <option value="Female" <?php if($patient['gender']=="Female") echo "selected"; ?>>Female</option>
                                        <option value="Other" <?php if($patient['gender']=="Other") echo "selected"; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-phone"></i> Mobile</label>
                                    <input type="text" name="newmobile" value="<?php echo htmlspecialchars($patient['mobile']); ?>" />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" name="newemail" value="<?php echo htmlspecialchars($patient['email']); ?>" />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-phone-square"></i> Emergency Contact</label>
                                    <input type="text" name="newemergency_contact" value="<?php echo htmlspecialchars($patient['emergency_contact']); ?>" />
                                </div>

                                <div class="field-group">
                                    <label><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="newstatus">
                                        <option value="Active" <?php if($patient['status']=="Active") echo "selected"; ?>>Active</option>
                                        <option value="Inactive" <?php if($patient['status']=="Inactive") echo "selected"; ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="field-group full-width">
                                    <label><i class="fas fa-map-marker-alt"></i> Address</label>
                                    <textarea name="newaddress" rows="2"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                                </div>

                                <div class="field-group full-width">
                                    <label><i class="fas fa-history"></i> Medical History</label>
                                    <textarea name="newmedical_history" rows="2"><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                                </div>

                                <div class="field-group full-width">
                                    <label><i class="fas fa-allergies"></i> Allergy</label>
                                    <textarea name="newallergy" rows="2"><?php echo htmlspecialchars($patient['allergy']); ?></textarea>
                                </div>
                            </div>

                            <div class="mt-10 flex justify-end gap-4 border-t pt-8">
                                <button type="button" class="px-6 py-2.5 rounded-full font-semibold text-slate-600 hover:bg-slate-100 transition" onclick="window.history.back()">Cancel</button>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Update Patient
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.querySelector('.image-preview-wrapper');
                wrapper.innerHTML = `
                    <img src="${e.target.result}" class="image-preview" id="imagePreview">
                    <div class="camera-overlay"><i class="fas fa-camera"></i></div>
                `;
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>
