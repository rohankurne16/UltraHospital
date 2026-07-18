<?php
include '../config/superadmin.php';
include '../config/permission.php';
checkSuperAdminLogin();
checkPermission('role-management');

$role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($role_id <= 0) {
    header("Location: role_list.php");
    exit();
}

$query = "SELECT * FROM roles WHERE role_id = '$role_id' AND delete_flag = 0";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    header("Location: role_list.php");
    exit();
}

$role = mysqli_fetch_assoc($result);

if ($role['is_system'] == 1) {
    header("Location: role_list.php?error=system_role");
    exit();
}

$user_check = "SELECT COUNT(*) as total FROM register WHERE role_id = '$role_id' AND delete_flag = 0";
$user_result = mysqli_query($conn, $user_check);
$user_count = mysqli_fetch_assoc($user_result)['total'];

if ($user_count > 0) {
    header("Location: role_list.php?error=users_assigned");
    exit();
}

$delete_query = "UPDATE roles SET delete_flag = 1 WHERE role_id = '$role_id'";
if (mysqli_query($conn, $delete_query)) {
    logAudit('Role', 'Deleted role: ' . $role['role_name'] . ' (ID: ' . $role_id . ')');
    header("Location: role_list.php?deleted=1");
    exit();
} else {
    header("Location: role_list.php?error=delete_failed");
    exit();
}
?>