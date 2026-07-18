<?php
session_start();
include "config/hospital.php";

$staff_data = null;

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $check_sql = "SELECT * FROM staff WHERE staff_id = '$id' AND (delete_flag IS NULL OR delete_flag = 0)";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows > 0) {
        $staff_data = $check_result->fetch_assoc();
    } else {
        echo "<script>alert('Staff member not found'); window.location='staff.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('No staff ID provided'); window.location='staff.php';</script>";
    exit();
}

if(isset($_POST['update_role'])) {
    $staff_id = $_POST['staff_id'];
    $role = $_POST['role'];
    
    $update_sql = "UPDATE staff SET role = '$role', updated_at = CURRENT_TIMESTAMP() WHERE staff_id = '$staff_id'";
    
    if($conn->query($update_sql)) {
        echo "<script>alert('Role updated successfully'); window.location='staff.php';</script>";
    } else {
        echo "<script>alert('Error updating role: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Update Role</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
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
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef3c7; color: #b45309; }
        .status-suspended { background: #fecaca; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?> 

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?> 

            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-2xl mx-auto w-full">
                    <div class="flex items-center gap-4 mb-8">
                        <a href="staff.php" class="back-btn">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Update Staff Role</h1>
                            <p class="text-gray-500 text-sm">Change the role of a staff member.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border shadow-sm p-6 md:p-8">
                        <?php if($staff_data): ?>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mb-6">
                            <?php 
                                $img_path = $staff_data['profile_image'];
                                if (!empty($img_path) && file_exists($img_path)): 
                            ?>
                                <img src="<?php echo $img_path; ?>" class="w-14 h-14 rounded-full object-cover border border-gray-200 shadow-sm">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xl border border-blue-200">
                                    <?php echo strtoupper(substr($staff_data['name'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($staff_data['name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($staff_data['email']); ?></p>
                                <span class="status-badge <?php 
                                    if($staff_data['status'] == 'Active') { echo 'status-active'; } 
                                    elseif($staff_data['status'] == 'Suspended') { echo 'status-suspended'; } 
                                    else { echo 'status-inactive'; } 
                                ?>">
                                    <?php echo $staff_data['status']; ?>
                                </span>
                            </div>
                        </div>

                        <form action="role.php" method="POST">
                            <input type="hidden" name="staff_id" value="<?php echo $staff_data['staff_id']; ?>">
                            
                            <div class="space-y-2">
                                <label class="text-sm font-medium" for="role">Current Role</label>
                                <div class="text-sm text-gray-600 mb-2">Current: <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($staff_data['role']); ?></span></div>
                                
                                <label class="text-sm font-medium" for="role">New Role <span class="text-red-500">*</span></label>
                                <select id="role" name="role"
                                    class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                                    <option value="">Select New Role</option>
                                    <option value="Admin" <?php echo ($staff_data['role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="Doctor" <?php echo ($staff_data['role'] == 'Doctor') ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="Nurse" <?php echo ($staff_data['role'] == 'Nurse') ? 'selected' : ''; ?>>Nurse</option>
                                    <option value="Receptionist" <?php echo ($staff_data['role'] == 'Receptionist') ? 'selected' : ''; ?>>Receptionist</option>
                                    <option value="Ward Boy" <?php echo ($staff_data['role'] == 'Ward Boy') ? 'selected' : ''; ?>>Ward Boy</option>
                                    <option value="Pharmacist" <?php echo ($staff_data['role'] == 'Pharmacist') ? 'selected' : ''; ?>>Pharmacist</option>
                                    <option value="Lab Technician" <?php echo ($staff_data['role'] == 'Lab Technician') ? 'selected' : ''; ?>>Lab Technician</option>
                                    <option value="Accountant" <?php echo ($staff_data['role'] == 'Accountant') ? 'selected' : ''; ?>>Accountant</option>
                                    <option value="HR" <?php echo ($staff_data['role'] == 'HR') ? 'selected' : ''; ?>>HR</option>
                                    <option value="Other" <?php echo ($staff_data['role'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="mt-8 flex justify-end gap-4 border-t pt-6">
                                <a href="staff.php" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition">Cancel</a>
                                <button type="submit" name="update_role" class="bg-blue-600 text-white px-6 py-2 rounded-md font-medium hover:bg-blue-700 shadow-md transition">Update Role</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center py-10">
                            <p class="text-gray-500">Staff member not found.</p>
                            <a href="staff.php" class="mt-4 inline-block text-blue-600 hover:underline">Go back to staff list</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>