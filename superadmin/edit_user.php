<?php
include '../config/permission.php';
$error = "";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = (int)$_GET['id'];
$theme = $_SESSION['theme'] ?? 'light';

// Get user details
$query = "SELECT * FROM register WHERE id = $user_id AND delete_flag = 0";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: users.php");
    exit;
}

$user = mysqli_fetch_assoc($result);

// Get hospitals
$hospital_query = "SELECT hospital_id, hospital_name
                   FROM hospital_master
                   WHERE delete_flag = 0
                   ORDER BY hospital_name";
$hospital_result = mysqli_query($conn, $hospital_query);

// Get roles
$role_query = "SELECT role_id, role_name
               FROM roles
               WHERE delete_flag = 0
               ORDER BY role_name";
$role_result = mysqli_query($conn, $role_query);

// Update
if (isset($_POST['update'])) {

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
  
    $hospital_id = (int)$_POST['hospital_id'];
    $role_id = (int)$_POST['role_id'];

    // Get role name
    $role_name = "";
    $r = mysqli_query($conn, "SELECT role_name FROM roles WHERE role_id=$role_id");
    if ($r && mysqli_num_rows($r) > 0) {
        $role_name = mysqli_fetch_assoc($r)['role_name'];
    }

    $update = "UPDATE register SET
                name='$name',
                email='$email',        
                hospital_id='$hospital_id',
                role_id='$role_id',
                role='$role_name',
                modified_by='Super Admin'
                WHERE id=$user_id";

    if (mysqli_query($conn, $update)) {

        logAudit(
            "User",
            "Updated user : ".$name
        );

        header("Location: users.php?updated=1");
        exit;

    } else {
        $error = mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; min-height: 100vh; }
        body.light { background: #f1f5f9; color: #1e293b; }
        body.dark { background: #0a0a0a; color: #f1f5f9; }
        
        .content-card {
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            max-width: 800px;
            margin: 0 auto;
        }
        body.light .content-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        body.dark .content-card { background: #1a1a1a; border: 1px solid #2a2a2a; }
        
        .form-group { margin-bottom: 1.5rem; }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        body.light label { color: #475569; }
        body.dark label { color: #9ca3af; }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }
        body.light .form-control { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.dark .form-control { background: #1e1e1e; border: 1px solid #2a2a2a; color: #f1f5f9; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5); }
        
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            border: 1px solid <?php echo $theme == 'dark' ? '#2a2a2a' : '#e2e8f0'; ?>;
            background: <?php echo $theme == 'dark' ? '#2a2a2a' : '#f1f5f9'; ?>;
            color: <?php echo $theme == 'dark' ? '#d1d5db' : '#475569'; ?>;
        }
        .btn-secondary:hover { background: <?php echo $theme == 'dark' ? '#333' : '#e2e8f0'; ?>; }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .main-content {
            padding: 2rem;
            transition: all 0.3s ease;
        }

        @media(max-width: 768px) {
            .main-content { margin-left: 0 !important; padding: 1rem; }
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent" style="margin-left: 18%; margin-top: 2%;">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <div style="max-width: 800px; margin: 0 auto 1.5rem auto;">
            <a href="users.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>

        <div class="content-card">
            <h2 class="text-2xl font-bold mb-6" style="color: <?php echo $theme == 'dark' ? '#f1f5f9' : '#1e293b'; ?>;">
                <i class="fas fa-user-edit mr-2" style="color: #3b82f6;"></i> Edit User Details
            </h2>

            <?php if(!empty($error)): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" required placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Enter email address">
                </div>

                <div class="form-group">
                    <label>Hospital Assignment</label>
                    <select name="hospital_id" class="form-control">
                        <?php while($h = mysqli_fetch_assoc($hospital_result)): ?>
                            <option value="<?php echo $h['hospital_id']; ?>" 
                                    <?php if($user['hospital_id'] == $h['hospital_id']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($h['hospital_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>User Role</label>
                    <select name="role_id" class="form-control">
                        <?php while($r = mysqli_fetch_assoc($role_result)): ?>
                            <option value="<?php echo $r['role_id']; ?>" 
                                    <?php if($user['role_id'] == $r['role_id']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($r['role_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" name="update" class="btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="users.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Optional: Add any JS from the theme if needed
    </script>
</body>
</html>
