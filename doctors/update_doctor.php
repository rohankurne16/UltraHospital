<?php
session_start();
include "../config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("location: ../index.php");
    exit();
}

$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";
$error = "";

if ($doctor_id <= 0) {
    header("location: update_doctor.php");
    exit();
}

$sql = "SELECT * FROM doctor WHERE doctor_id = '$doctor_id' AND (delete_flag=0 OR delete_flag IS NULL)";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    header("location: update_doctor.php");
    exit();
}
$doctor = $result->fetch_assoc();

$register_id = $doctor['register_id'];
$register_sql = "SELECT * FROM register WHERE id = '$register_id'";
$register_result = $conn->query($register_sql);
$register = $register_result->fetch_assoc();

$doctor_image = $doctor['doctor_image'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    
    // Handle image upload
    if (!empty($_FILES['doctor_image']['name']) && $_FILES['doctor_image']['error'] == 0) {
        $folder = "../documents/doctors/images/";
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        $image_name = $_FILES['doctor_image']['name'];
        $image_path = $folder . $image_name;
        if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $image_path)) {
            $doctor_image = "../documents/doctors/images/" . $image_name;
        } else {
            $error = "Failed to upload image.";
        }
    }

    // Get form data
    $doctor_name = mysqli_real_escape_string($conn, $_POST['newdoctor_name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['newmobile']);
    $email = mysqli_real_escape_string($conn, $_POST['newemail']);
    $department = mysqli_real_escape_string($conn, $_POST['newdepartment']);
    $qualification = mysqli_real_escape_string($conn, $_POST['newqualification']);
    $specialization = mysqli_real_escape_string($conn, $_POST['newspecialization']);
    $experience = mysqli_real_escape_string($conn, $_POST['newexperience']);
    $consultation_fee = mysqli_real_escape_string($conn, $_POST['newconsultation_fee']);
    $timing = mysqli_real_escape_string($conn, $_POST['newtiming']);
    $address = mysqli_real_escape_string($conn, $_POST['newaddress']);
    $status = mysqli_real_escape_string($conn, $_POST['newstatus']);
    
    // Update doctor
    $sql = "UPDATE doctor SET 
            doctor_name='$doctor_name', 
            doctor_image='$doctor_image', 
            mobile='$mobile', 
            email='$email', 
            department='$department', 
            qualification='$qualification', 
            specialization='$specialization', 
            experience='$experience', 
            consultation_fee='$consultation_fee', 
            timing='$timing', 
            address='$address', 
            status='$status' 
            WHERE doctor_id='$id'";
            
    if (mysqli_query($conn, $sql)) {
        // Update register table
        $sql2 = "UPDATE register SET name='$doctor_name', email='$email', modified_by='admin' WHERE id='$register_id'";
        if (mysqli_query($conn, $sql2)) {
            echo "<script>
                alert('Doctor updated successfully!');
                window.location.href='view_doctor.php?id=$id';
            </script>";
            exit();
        } else {
            $error = "Error updating register table: " . mysqli_error($conn);
        }
    } else {
        $error = "Error updating doctor: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Edit Doctor</title>

    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
        }
        .sidebar-active { 
            background-color: #f3f4f6; 
            color: #111827; 
        }
        .main-content { 
            margin-left: 260px; 
            padding: 30px 40px; 
            min-height: 100vh; 
        }
        .form-container { 
            width: 100%; 
            margin: 0 auto; 
            max-width: 1200px;
        }
        .form-card { 
            background: white; 
            border-radius: 20px; 
            border: 1px solid #e5e7eb; 
            overflow: hidden; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            width: 100%; 
        }
        .form-card .header { 
            padding: 24px 32px; 
            border-bottom: 1px solid #e5e7eb; 
            background: #f8fafc; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .form-card .header .header-icon { 
            width: 44px; 
            height: 44px; 
            background: #eff6ff; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #3b82f6; 
        }
        .form-card .header h3 { 
            font-size: 20px; 
            font-weight: 700; 
            color: #0f172a; 
            margin: 0; 
        }
        .form-card .header .subtitle { 
            font-size: 14px; 
            color: #64748b; 
            font-weight: 400; 
        }
        .form-card .body { 
            padding: 32px 40px; 
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            font-size: 14px; 
            font-weight: 600; 
            color: #0f172a; 
            margin-bottom: 6px; 
        }
        .form-group label .required { 
            color: #ef4444; 
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 12px; 
            font-size: 15px; 
            transition: all 0.2s ease; 
            outline: none; 
            background: white; 
            color: #0f172a; 
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1); 
        }
        .form-group textarea { 
            resize: vertical; 
            min-height: 80px; 
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        }
        .btn-primary { 
            padding: 12px 32px; 
            background: #3b82f6; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 15px; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
        }
        .btn-primary:hover { 
            background: #2563eb; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 16px rgba(59,130,246,0.3); 
        }
        .btn-secondary { 
            padding: 12px 28px; 
            background: #f1f5f9; 
            color: #475569; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 15px; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
        }
        .btn-secondary:hover { 
            background: #e2e8f0; 
        }
        .btn-actions { 
            display: flex; 
            gap: 14px; 
            flex-wrap: wrap; 
            padding-top: 24px; 
            border-top: 1.5px solid #e5e7eb; 
            margin-top: 8px; 
        }
        .alert { 
            padding: 14px 20px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .alert-success { 
            background: #d1fae5; 
            color: #065f46; 
            border: 1px solid #a7f3d0; 
        }
        .alert-error { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }
        .image-preview-container { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            padding: 10px 0 20px 0; 
        }
        .image-preview-wrapper { 
            position: relative; 
            width: 160px; 
            height: 160px; 
            border-radius: 50%; 
            overflow: hidden; 
            border: 4px solid #e2e8f0; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            transition: all 0.3s ease; 
            background: #f8fafc; 
        }
        .image-preview-wrapper:hover { 
            border-color: #3b82f6; 
            box-shadow: 0 4px 20px rgba(59,130,246,0.2); 
            transform: scale(1.03); 
        }
        .image-preview { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        .image-placeholder { 
            width: 100%; 
            height: 100%; 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 54px; 
            font-weight: 700; 
            color: white; 
            text-transform: uppercase; 
        }
        .file-upload-wrapper { 
            margin-top: 16px; 
            display: inline-block; 
            text-align: center; 
        }
        .file-upload-btn { 
            padding: 10px 24px; 
            background: white; 
            border: 2px dashed #d1d5db; 
            border-radius: 12px; 
            font-size: 14px; 
            font-weight: 500; 
            color: #64748b; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
        }
        .file-upload-btn:hover { 
            border-color: #3b82f6; 
            color: #3b82f6; 
            background: #eff6ff; 
        }
        .file-upload-wrapper input[type=file] { 
            display: none; 
        }
        .back-btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            width: 40px; 
            height: 40px; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            background: white; 
            color: #374151; 
            transition: all 0.2s ease; 
            text-decoration: none; 
        }
        .back-btn:hover { 
            background: #f3f4f6; 
            border-color: #d1d5db; 
        }
        .header-title h1 { 
            font-size: 24px; 
            font-weight: 700; 
            color: #111827; 
            margin: 0; 
        }
        .header-title p { 
            font-size: 14px; 
            color: #6b7280; 
            margin: 2px 0 0 0; 
        }
        @media (max-width: 1024px) { 
            .main-content { 
                margin-left: 0; 
                padding: 20px; 
            } 
            .form-card .body { 
                padding: 24px 28px; 
            } 
        }
        @media (max-width: 768px) { 
            .form-row { 
                grid-template-columns: 1fr; 
                gap: 0; 
            } 
            .btn-actions { 
                flex-direction: column; 
            } 
            .btn-actions a, 
            .btn-actions button { 
                width: 100%; 
                justify-content: center; 
            } 
        }
        .fade-in { 
            animation: fadeIn 0.3s ease; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content w-full">
                <div class="form-container w-full">
                    <div class="flex items-center gap-4 mb-6">
                        <a href="view_doctor.php?id=<?php echo $doctor_id; ?>" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div class="header-title">
                            <h1>Edit Doctor</h1>
                            <p>Update doctor information for <?php echo htmlspecialchars($doctor['doctor_name']); ?></p>
                        </div>
                    </div>

                    <div class="form-card fade-in w-full">
                        <div class="header">
                            <div class="header-icon"><i class="fas fa-user-md"></i></div>
                            <div>
                                <h3>Doctor Details</h3>
                                <div class="subtitle">Update professional information</div>
                            </div>
                        </div>

                        <div class="body">
                            <?php if (!empty($message)): ?>
                                <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo $message; ?></span></div>
                            <?php endif; ?>
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?php echo $error; ?></span></div>
                            <?php endif; ?>

                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                                
                                <div class="image-preview-container">
                                    <div class="image-preview-wrapper" id="imageWrapper">
                                        <?php if (!empty($doctor['doctor_image']) && file_exists("../" . $doctor['doctor_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($doctor['doctor_image']); ?>" class="image-preview" id="imagePreview">
                                        <?php elseif (!empty($doctor['doctor_image']) && file_exists($doctor['doctor_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($doctor['doctor_image']); ?>" class="image-preview" id="imagePreview">
                                        <?php else: 
                                            $name_parts = explode(' ', $doctor['doctor_name']);
                                            $initials = '';
                                            foreach ($name_parts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                        ?>
                                            <div class="image-placeholder"><?php echo substr($initials, 0, 2); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="file-upload-wrapper">
                                        <label class="file-upload-btn">
                                            <i class="fas fa-camera"></i> Change Photo
                                            <input type="file" name="doctor_image" accept="image/*" onchange="previewImage(event)">
                                        </label>
                                        <p class="text-xs text-gray-400 mt-2">JPG, PNG, GIF (Max 5MB)</p>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name <span class="required">*</span></label>
                                        <input type="text" name="newdoctor_name" value="<?php echo htmlspecialchars($doctor['doctor_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email <span class="required">*</span></label>
                                        <input type="email" name="newemail" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Mobile <span class="required">*</span></label>
                                        <input type="tel" name="newmobile" value="<?php echo htmlspecialchars($doctor['mobile']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Department <span class="required">*</span></label>
                                        <input type="text" name="newdepartment" value="<?php echo htmlspecialchars($doctor['department']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Qualification <span class="required">*</span></label>
                                        <input type="text" name="newqualification" value="<?php echo htmlspecialchars($doctor['qualification']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Specialization <span class="required">*</span></label>
                                        <input type="text" name="newspecialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Experience (Years) <span class="required">*</span></label>
                                        <input type="number" name="newexperience" value="<?php echo htmlspecialchars($doctor['experience']); ?>" min="0" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Consultation Fee ($) <span class="required">*</span></label>
                                        <input type="number" name="newconsultation_fee" value="<?php echo htmlspecialchars($doctor['consultation_fee']); ?>" step="0.01" min="0" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Consultation Timing <span class="required">*</span></label>
                                        <input type="text" name="newtiming" value="<?php echo htmlspecialchars($doctor['timing']); ?>" placeholder="e.g., 9:00 AM - 6:00 PM" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Status <span class="required">*</span></label>
                                        <select name="newstatus" required>
                                            <option value="Active" <?php echo ($doctor['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($doctor['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Address <span class="required">*</span></label>
                                    <textarea name="newaddress" rows="2" required><?php echo htmlspecialchars($doctor['address']); ?></textarea>
                                </div>

                                <div class="btn-actions">
                                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Doctor</button>
                                    <a href="doctor_profile.php?id=<?php echo $doctor_id; ?>" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imageWrapper').innerHTML = `<img src="${e.target.result}" class="image-preview" id="imagePreview">`;
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>