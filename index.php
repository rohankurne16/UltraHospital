<?php
// ============================================================
// LOGIN PAGE - UPDATED WITH DYNAMIC SUPER ADMIN CHECK
// ============================================================

if (isset($_GET['hid']) && !empty($_GET['hid'])) {
    $hospital_id = decryptId(urldecode($_GET['hid']));
}
session_start();
include("config/db.php");


// ============================================================
// GET HOSPITAL FROM URL
// ============================================================
$hospital_id = 0;

if (isset($_GET['hid']) && !empty($_GET['hid'])) {

    $hospital_id = decryptId($_GET['hid']);

}
$hospital = null;

if (!empty($hospital_id) && is_numeric($hospital_id) && $hospital_id > 0) {
    $getHospital = "SELECT * FROM hospital_master WHERE hospital_id='$hospital_id' AND delete_flag = 0 AND status = 'Active'";
    $result = mysqli_query($conn, $getHospital);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $hospital = mysqli_fetch_assoc($result);
    }
}

// If no hospital found or no hospital ID, get default hospital
// If no hospital found, use UltraHospital
if (!$hospital) {
    $hospital = [
        'hospital_id'   => 0,
        'hospital_name' => 'UltraHospital',
        'hospital_logo' => null,
        'address'       => '',
        'phone'         => '',
        'city'          => '',
        'state'         => '',
        'country'       => '',
        'email'         => '',
        'status'        => 'Active'
    ];
}

$status = "";
$status_type = "";
$entered_email = "";

// Session status messages
if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];
    $status_type = $_SESSION['status_type'] ?? 'error';

    if (isset($_SESSION['entered_email'])) {
        $entered_email = $_SESSION['entered_email'];
        unset($_SESSION['entered_email']);
    }
    unset($_SESSION['status']);
    unset($_SESSION['status_type']);
}

if (isset($_GET['hid']) && !empty($_GET['hid'])) {
    $_SESSION['hid'] = $_GET['hid'];   // Save encrypted hospital id
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================
if (!function_exists('getRolePermissionNames')) {
    function getRolePermissionNames($role_id) {
        global $conn;
        $permissions = [];
        $query = "SELECT p.permission_slug 
                  FROM role_permissions rp 
                  JOIN permissions p ON rp.permission_id = p.permission_id 
                  WHERE rp.role_id = '$role_id' 
                  AND p.delete_flag = 0";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = $row['permission_slug'];
            }
        }
        return $permissions;
    }
}

if (!function_exists('getSuperAdminPermissionsList')) {
    function getSuperAdminPermissionsList() {
        global $conn;
        $permissions = [];
        $query = "SELECT permission_slug FROM permissions WHERE delete_flag = 0";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = $row['permission_slug'];
            }
        }
        return $permissions;
    }
}

function decryptId($encrypted)
{
    $key = 'UltraHospital@2026#SecureKey';

    $data = base64_decode($encrypted);

    $ivLength = openssl_cipher_iv_length('aes-256-cbc');

    $iv = substr($data, 0, $ivLength);

    $encryptedText = substr($data, $ivLength);

    return openssl_decrypt(
        $encryptedText,
        'aes-256-cbc',
        $key,
        0,
        $iv
    );
}

// ============================================================
// LOGIN HANDLING
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);

    // Validation
    if (empty($email) || empty($pass)) {
        $_SESSION['status'] = "Please fill in all fields.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php" . (!empty($hospital_id) ? "?hid=$hospital_id" : ""));
        exit();
    }

    // REGULAR USER LOGIN (Now includes Super Admin check from DB)
    $stmt = $conn->prepare("
        SELECT r.*, ro.role_name
        FROM register r
        LEFT JOIN roles ro ON r.role_id = ro.role_id
        WHERE r.email = ?
        AND (r.delete_flag = 0 OR r.delete_flag IS NULL)
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $_SESSION['status'] = "Account not found.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php" . (!empty($hospital_id) ? "?hid=$hospital_id" : ""));
        exit();
    }

    $row = $result->fetch_assoc();

    // VERIFY PASSWORD
    if (password_verify($pass, $row['password']) || $pass == $row['password']) {
        
        // Get Role Info
        $role_id = $row['role_id'] ?? 0;
        $role_name_from_db = $row['role_name'] ?? $row['role'];

        // Set session
        $_SESSION['id'] = $row['id'];

        if (strtolower(trim($role_name_from_db)) == 'lab technician') {

   $staffQuery = mysqli_query($conn,
    "SELECT staff_id, register_id
     FROM staff
     WHERE email = '{$row['email']}'
     AND delete_flag = 0
     LIMIT 1");

    if ($staffQuery && mysqli_num_rows($staffQuery) > 0) {
        $staff = mysqli_fetch_assoc($staffQuery);

        $_SESSION['id'] = $staff['staff_id'];          // 11
        $_SESSION['register_id'] = $staff['register_id']; // 1040

    }
}
        $_SESSION['name'] = $row['name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $role_name_from_db;
        $_SESSION['role_id'] = $role_id;
        $_SESSION['hospital_id'] = $row['hospital_id'];
        $_SESSION['login_time'] = time();

        // SET PERMISSIONS
        if (strtolower(trim($role_name_from_db)) == 'super admin' || strtolower(trim($role_name_from_db)) == 'superadmin') {
            $_SESSION['permissions'] = getSuperAdminPermissionsList();
        } else {
            $_SESSION['permissions'] = getRolePermissionNames($role_id);
            // Admin fallback
            if ($role_id == 5 || $role_id == 2 || strtolower($role_name_from_db) == 'admin') {
                $_SESSION['permissions'] = getSuperAdminPermissionsList();
            }
        }

        // Insert login log
        $register_id = $row['id'];
        $hospital_id_val = $row['hospital_id'] ?? 'NULL';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $browser = $_SERVER['HTTP_USER_AGENT'];
        $device = (strpos($browser, 'Mobile') !== false) ? 'Mobile' : 'Desktop';
        
        $login_sql = "INSERT INTO login_logs (register_id, hospital_id, ip_address, browser, device) 
                      VALUES ('$register_id', ".($hospital_id_val == 'NULL' ? 'NULL' : "'$hospital_id_val'").", '$ip_address', '$browser', '$device')";
        mysqli_query($conn, $login_sql);

        // ROLE-BASED REDIRECTION
        $role_check = strtolower(trim($role_name_from_db));

        if ($role_check == 'super admin' || $role_check == 'super admin') {
            header("Location: superadmin/dashboard.php");
            exit();
        }

        // Other roles redirection
        switch (strtolower($role_check)) {

    case 'super admin':
        header("Location: superadmin/dashboard.php");
        exit();

    case 'admin':
        header("Location: dashboard.php");
        exit();

    case 'doctor':
        header("Location: doctors/dashboard.php");
        exit();

    case 'nurse':
        header("Location: staff/nurse_dashboard.php");
        exit();

    case 'ward boy':
        header("Location: staff/wardboy_dashboard.php");
        exit();

    case 'lab technician':
        
        header("Location: labtechnician/dashboard.php");
        exit();

    case 'patient':
        header("Location: patients/dashboard.php");
        exit();

    case 'billing staff':
        header("Location: staff/billing_dashboard.php");
        exit();

    case 'accountant':
        header("Location: staff/accountant_dashboard.php");
        exit();

    case 'pharmacist':
        header("Location: staff/pharmacist_dashboard.php");
        exit();

    case 'staff':
        header("Location: staff/dashboard.php");
        exit();

    case 'receptionist':
        header("Location: staff/reception_dashboard.php");
        exit();

    default:
        header("Location: dashboard.php");
        exit();
}
        
    } else {
        $_SESSION['status'] = "Invalid password.";
        $_SESSION['status_type'] = "error";
        $_SESSION['entered_email'] = $email;
        header("location: index.php" . (!empty($hospital_id) ? "?hid=$hospital_id" : ""));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($hospital['hospital_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            width: 100%;
            padding: 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }
        .error-banner {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="login-card">
        <div class="text-center mb-8">
            <?php if ($hospital['hospital_logo']): ?>
                <img src="<?php echo htmlspecialchars($hospital['hospital_logo']); ?>" alt="Logo" class="h-16 mx-auto mb-4">
            <?php else: ?>
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                    <?php echo strtoupper(substr($hospital['hospital_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <h1 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($hospital['hospital_name']); ?></h1>
            <p class="text-slate-500 mt-1">Welcome back! Please login to your account.</p>
        </div>

        <?php if ($status): ?>
            <div class="error-banner">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($status); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@company.com" 
                       value="<?php echo htmlspecialchars($entered_email); ?>" required>
            </div>

            <div class="mb-6">
                <div class="flex justify-between mb-2">
                    <label class="text-sm font-semibold text-slate-700">Password</label>
                    <a href="send_reset_link.php" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary">
                Sign In
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-slate-500">
            Don't have an account? <a href="register.php" class="text-blue-600 font-semibold hover:underline">Contact Administrator</a>
        </div>
    </div>

</body>
</html>
