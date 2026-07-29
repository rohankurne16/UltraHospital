<?php
// ============================================================
// SUBSCRIPTION LIMITS HELPER FUNCTIONS
// (Conditionally defined to prevent redeclaration)
// ============================================================

if (!function_exists('getHospitalLimits')) {
    function getHospitalLimits($hospital_id) {
        global $conn;

        $hospital_id = (int)$hospital_id;
        if ($hospital_id <= 0) {
            return false;
        }

        // Look for an active subscription
        $query = "SELECT max_departments, max_doctors, max_staff 
                  FROM subscriptions 
                  WHERE hospital_id = $hospital_id 
                    AND delete_flag = 0 
                  ORDER BY created_at DESC 
                  LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        // No subscription found – create default
        $default = [
            'max_departments' => 2,
            'max_doctors'     => 10,
            'max_staff'       => 10
        ];

        $insert = "INSERT INTO subscriptions 
                   (hospital_id, max_departments, max_doctors, max_staff, status, created_at, delete_flag) 
                   VALUES ($hospital_id, {$default['max_departments']}, {$default['max_doctors']}, {$default['max_staff']}, 'Active', NOW(), 0)";

        if (mysqli_query($conn, $insert)) {
            return $default;
        }

        // If insertion fails, return defaults anyway (so the system still works)
        return $default;
    }
}

if (!function_exists('checkResourceLimit')) {
    function checkResourceLimit($hospital_id, $resource_type) {
        global $conn;

        $hospital_id = (int)$hospital_id;
        if ($hospital_id <= 0) {
            return false;
        }

        $limits = getHospitalLimits($hospital_id);
        if (!$limits) {
            return false;
        }

        switch ($resource_type) {
            case 'department':
                $count_query = "SELECT COUNT(*) as total FROM department WHERE hospital_id = $hospital_id AND delete_flag = 0";
                $max = (int)$limits['max_departments'];
                break;
            case 'doctor':
                $count_query = "SELECT COUNT(*) as total FROM doctor WHERE hospital_id = $hospital_id AND delete_flag = 0";
                $max = (int)$limits['max_doctors'];
                break;
            case 'staff':
                $count_query = "SELECT COUNT(*) as total FROM staff WHERE hospital_id = $hospital_id AND delete_flag = 0";
                $max = (int)$limits['max_staff'];
                break;
            default:
                return false;
        }

        $result = mysqli_query($conn, $count_query);
        if (!$result) {
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        $current = (int)($row['total'] ?? 0);

        return $current < $max;
    }
}

if (!function_exists('getLimitMessage')) {
    function getLimitMessage($resource_type) {
        $labels = [
            'department' => 'departments',
            'doctor'     => 'doctors',
            'staff'      => 'staff members'
        ];
        $name = $labels[$resource_type] ?? $resource_type;

        return "You have reached the maximum limit allowed by your current subscription plan. Please contact the System Administrator to upgrade your plan and increase your resource limits for <strong>{$name}</strong>.";
    }
}

if (!function_exists('updateHospitalLimits')) {
    function updateHospitalLimits($hospital_id, $max_departments, $max_doctors, $max_staff, $amount = 0) {
        global $conn;
        
        $max_departments = (int)$max_departments;
        $max_doctors = (int)$max_doctors;
        $max_staff = (int)$max_staff;
        $amount = (float)$amount;
        
        // Check if subscription exists
        $query = "SELECT subscription_id FROM subscriptions WHERE hospital_id = $hospital_id AND delete_flag = 0 LIMIT 1";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $update = "UPDATE subscriptions 
                       SET max_departments = $max_departments, 
                           max_doctors = $max_doctors, 
                           max_staff = $max_staff,
                           amount = $amount,
                           modified_at = NOW()
                       WHERE subscription_id = {$row['subscription_id']}";
        } else {
            // Insert new
            $update = "INSERT INTO subscriptions (hospital_id, max_departments, max_doctors, max_staff, amount, status, created_at, delete_flag) 
                       VALUES ($hospital_id, $max_departments, $max_doctors, $max_staff, $amount, 'Active', NOW(), 0)";
        }
        
        return mysqli_query($conn, $update);
    }
}
?>