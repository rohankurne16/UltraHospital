<?php
// Ensure session is started and config is included
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once("../config/hospital.php");

// Initialize variables with default values
$staff_name = "Guest";
$staff_role = "";
$staff_photo = "assets/img/default_avatar.png";
$staff_email = "";
$staff_id = "";

// Check if user is logged in
if (isset($_SESSION["id"])) {
    $logged_in_user_id = $_SESSION["id"];

    // First try to get from staff table using register_id
    $sql = "SELECT s.*, r.role AS user_role, r.email AS user_email 
            FROM staff s 
            INNER JOIN register r ON s.register_id = r.id 
            WHERE r.id = ? AND (s.delete_flag = 0 OR s.delete_flag IS NULL) 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $logged_in_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $staff_data = $result->fetch_assoc();
            $staff_name = htmlspecialchars($staff_data["name"]);
            $staff_role = htmlspecialchars($staff_data["user_role"]);
            $staff_email = htmlspecialchars($staff_data["user_email"]);
            $staff_id = $staff_data["staff_id"];
            
            // Set profile photo
            if (!empty($staff_data["profile_image"])) {
                if (file_exists($staff_data["profile_image"])) {
                    $staff_photo = htmlspecialchars($staff_data["profile_image"]);
                } elseif (file_exists("" . $staff_data["profile_image"])) {
                    $staff_photo = "" . htmlspecialchars($staff_data["profile_image"]);
                }
            }
        }
        $stmt->close();
    }
    
    // If not found in staff table, try register table directly
    if ($staff_name == "Guest") {
        $sql_register = "SELECT id, name, email, role FROM register WHERE id = ? AND (delete_flag = 0 OR delete_flag IS NULL)";
        $stmt_register = $conn->prepare($sql_register);
        
        if ($stmt_register) {
            $stmt_register->bind_param("i", $logged_in_user_id);
            $stmt_register->execute();
            $result_register = $stmt_register->get_result();
            
            if ($result_register->num_rows > 0) {
                $user_data = $result_register->fetch_assoc();
                $staff_name = htmlspecialchars($user_data["name"]);
                $staff_role = htmlspecialchars($user_data["role"]);
                $staff_email = htmlspecialchars($user_data["email"]);
                $staff_id = $user_data["id"];
            }
            $stmt_register->close();
        }
    }
}

// Determine current page for active class
$current_page = basename($_SERVER["PHP_SELF"]);

// Fetch hospital settings
$hospital_name = "Hospital";
$hospital_logo = "assets/img/logo.png";

$hospital_data_sidebar = null;
$sql_hospital_sidebar = "SELECT * FROM hospital_settings LIMIT 1";
$result_hospital_sidebar = $conn->query($sql_hospital_sidebar);
if ($result_hospital_sidebar && $result_hospital_sidebar->num_rows > 0) {
    $hospital_data_sidebar = $result_hospital_sidebar->fetch_assoc();
    $hospital_name = $hospital_data_sidebar["hospital_name"] ?? "Hospital";
    $hospital_logo = $hospital_data_sidebar["hospital_logo"] ?? "assets/img/logo.png";
}

// Stock alert count
$stockAlertCount = $_SESSION["stock_alert_count"] ?? 0;

// Get pending orders count
$pending_count = 0;
$count_sql = "SELECT COUNT(*) as total FROM lab_orders WHERE status = 'Pending' AND (delete_flag = 0 OR delete_flag IS NULL)";
$count_result = $conn->query($count_sql);
if ($count_result && $count_result->num_rows > 0) {
    $count_row = $count_result->fetch_assoc();
    $pending_count = $count_row['total'];
}
?>

<aside class="!fixed h-full left-0 bottom-0 z-50 flex w-64 flex-col border-r bg-background transition-transform duration-300 ease-in-out translate-x-0">
    <!-- Sidebar Header -->
    <div class="flex py-3 xl:py-3.5 items-center justify-between px-4">
        <a class="flex items-center space-x-2" href="../staff/staff_dashboard.php">
            <img alt="Hospital Logo" loading="lazy" width="36" height="36" decoding="async" data-nimg="1" style="color:transparent" src="<?php echo $hospital_logo; ?>" /> 
            <span class="font-bold inline-block"><?php echo $hospital_name; ?></span>
        </a>
        <button class="inline-flex items-center justify-center shrink-0 gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground size-10 xl:hidden" onclick="toggleSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x size-6">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
            <span class="sr-only">Close sidebar</span>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <div class="flex-1 py-2 border-t h-full overflow-y-auto">
        <nav class="space-y-1 px-2">
            <!-- Dashboard -->
            <div class="space-y-1 custom-scrollbar">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors <?php echo ($current_page == 'staff_dashboard.php') ? 'bg-primary/10 text-primary' : 'text-neutral-800 hover:bg-muted hover:text-foreground'; ?>" href="../staff/staff_dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard mr-2 h-4 w-4">
                        <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                        <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                        <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                        <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                    </svg>
                    Dashboard
                </a>
            </div>

            <!-- Patients -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('patientsMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users mr-2 h-4 w-4">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Patients
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="patientsMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'patient_register.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../staff/patient_register.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus mr-2 h-4 w-4">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                            Register Patient
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'patients_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../staff/patients_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round mr-2 h-4 w-4">
                                <circle cx="12" cy="8" r="5"></circle>
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                            </svg>
                            Patient List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Appointments -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('appointmentMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check-2 mr-2 h-4 w-4">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                            <path d="M3 10h18"></path>
                            <path d="m9 16 2 2 4-4"></path>
                        </svg>
                        Appointments
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="appointmentMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'add_appointment.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../staff/appointments/add_appointment.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-plus mr-2 h-4 w-4">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                                <path d="M12 16v-4"></path>
                                <path d="M10 14h4"></path>
                            </svg>
                            Add Appointment
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'appointment_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../staff/appointments/appointment_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check mr-2 h-4 w-4">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                                <path d="m9 16 2 2 4-4"></path>
                            </svg>
                            Appointment List
                        </a>
                    </div>
                </div>
            </div>

            <!-- OPD -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('opdMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list mr-2 h-4 w-4">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect>
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <path d="M12 11h4"></path>
                            <path d="M12 15h4"></path>
                            <path d="M8 11h.01"></path>
                            <path d="M8 15h.01"></path>
                        </svg>
                        OPD
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="opdMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'opd_registration.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../opd/opd_registration.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-circle mr-2 h-4 w-4">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            OPD Registration
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'opd_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../opd/opd_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            OPD List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Prescription -->
            <div class="space-y-1 custom-scrollbar">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'add_prescription.php' || $current_page == 'prescription_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../prescription/add_prescription.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pill mr-2 h-4 w-4">
                        <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                        <path d="m8.5 8.5 7 7"></path>
                    </svg>
                    Prescriptions
                </a>
            </div>

            <!-- Billing -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('billingMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-receipt mr-2 h-4 w-4">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path>
                            <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                            <path d="M12 17.5v-11"></path>
                        </svg>
                        Billing
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="billingMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'create_bill.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../billing/create_bill.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-plus mr-2 h-4 w-4">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                            Create Bill
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'bill_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../billing/bill_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text mr-2 h-4 w-4">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            Bill List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Laboratory -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('labMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flask mr-2 h-4 w-4">
                            <path d="M8 3h8"></path>
                            <path d="M8 7h8"></path>
                            <path d="M6 11h12"></path>
                            <path d="M10 15h4"></path>
                            <path d="M3 18h18"></path>
                            <path d="M12 3v15"></path>
                        </svg>
                        Laboratory
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="labMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'add_test.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../laboratory/add_test.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-circle mr-2 h-4 w-4">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            Add Lab Test
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'test_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../laboratory/test_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Lab Test List
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'add_lab_order.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../laboratory/add_lab_order.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus mr-2 h-4 w-4">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Lab Order
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'lab_orders_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../laboratory/lab_orders_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Lab Orders List
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'pending_orders.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../laboratory/pending_orders.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock mr-2 h-4 w-4">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Pending Orders
                            <?php if ($pending_count > 0): ?>
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pharmacy -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('pharmacyMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pill mr-2 h-4 w-4">
                            <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                            <path d="m8.5 8.5 7 7"></path>
                        </svg>
                        Pharmacy
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="pharmacyMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'add_medicine.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../pharmacy/add_medicine.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus mr-2 h-4 w-4">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Medicine
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'medicine_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../pharmacy/medicine_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Medicine List
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'stock.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../pharmacy/stock.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package mr-2 h-4 w-4">
                                <path d="M16.5 9.4 7.5 4.2"></path>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                <line x1="12" y1="22" x2="12" y2="12"></line>
                            </svg>
                            Stock Management
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'alert.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../pharmacy/alert.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell mr-2 h-4 w-4">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Stock Alert
                            <?php if ($stockAlertCount > 0): ?>
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $stockAlertCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- IPD -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('ipdMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hospital mr-2 h-4 w-4">
                            <path d="M12 6v4"></path>
                            <path d="M14 14h-4"></path>
                            <path d="M14 18h-4"></path>
                            <path d="M14 8h-4"></path>
                            <path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"></path>
                            <path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"></path>
                        </svg>
                        IPD
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="ipdMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'admit_patient.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../ipd/admit_patient.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus mr-2 h-4 w-4">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                            Admit Patient
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'admission_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../ipd/admission_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Admission List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ward -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('wardMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bed mr-2 h-4 w-4">
                            <path d="M2 4v16"></path>
                            <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                            <path d="M2 17h20"></path>
                            <path d="M6 8v9"></path>
                        </svg>
                        Ward
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="wardMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'ward_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../rooms/ward_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Ward List
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'add_ward.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../rooms/add_ward.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus mr-2 h-4 w-4">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Ward
                        </a>
                    </div>
                </div>
            </div>

            <!-- Discharge -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('dischargeMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out mr-2 h-4 w-4">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Discharge
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="dischargeMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'discharge_summary.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../discharge/discharge_summary.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-plus mr-2 h-4 w-4">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                            Discharge Summary
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'discharge_list.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../discharge/discharge_list.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list mr-2 h-4 w-4">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Discharge List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="space-y-1 custom-scrollbar">
                <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground" onclick="toggleMenu('reportsMenu')">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-chart mr-2 h-4 w-4">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <path d="M12 18v-4"></path>
                            <path d="M8 18v-2"></path>
                            <path d="M16 18v-6"></path>
                        </svg>
                        Reports
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div id="reportsMenu" style="display:none;">
                    <div class="ml-4 space-y-1 pl-2 pt-1">
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'patient_reports.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../reports/patient_reports.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user mr-2 h-4 w-4">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Patient Reports
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'appointment_reports.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../reports/appointment_reports.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar mr-2 h-4 w-4">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            Appointment Reports
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'financial_reports.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../reports/financial_reports.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign mr-2 h-4 w-4">
                                <line x1="12" y1="2" x2="12" y2="22"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Financial Reports
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'lab_reports.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../reports/lab_reports.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flask mr-2 h-4 w-4">
                                <path d="M8 3h8"></path>
                                <path d="M8 7h8"></path>
                                <path d="M6 11h12"></path>
                                <path d="M10 15h4"></path>
                                <path d="M3 18h18"></path>
                                <path d="M12 3v15"></path>
                            </svg>
                            Lab Reports
                        </a>
                        <a class="flex items-center rounded-md px-3 py-2 text-sm transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'stock_reports.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../reports/stock_reports.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package mr-2 h-4 w-4">
                                <path d="M16.5 9.4 7.5 4.2"></path>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                <line x1="12" y1="22" x2="12" y2="12"></line>
                            </svg>
                            Stock Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="space-y-1 custom-scrollbar">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors text-neutral-800 hover:bg-muted hover:text-foreground <?php echo ($current_page == 'profile.php') ? 'bg-primary/10 text-primary' : ''; ?>" href="../profile.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-circle mr-2 h-4 w-4">
                        <circle cx="12" cy="12" r="10"></circle>
                        <circle cx="12" cy="10" r="3"></circle>
                        <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path>
                    </svg>
                    Profile
                </a>
            </div>

            <!-- Logout -->
            <div class="space-y-1 custom-scrollbar">
                <a class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors text-red-600 hover:bg-red-50 hover:text-red-700" href="../logout.php" onclick="return confirm('Are you sure you want to logout?')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out mr-2 h-4 w-4">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Sidebar Footer (User Profile) -->
    <div class="border-t p-4 shrink-0">
        <div class="flex items-center gap-3">
            <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8">
                <?php if(!empty($staff_photo) && file_exists($staff_photo)): ?>
                    <img src="<?php echo $staff_photo; ?>" class="h-8 w-8 rounded-full object-cover" alt="Profile">
                <?php else: ?>
                    <span class="flex h-full w-full items-center justify-center rounded-full bg-blue-100 text-blue-700 font-semibold">
                        <?php echo strtoupper(substr($staff_name, 0, 2)); ?>
                    </span>
                <?php endif; ?>
            </span>
            <div class="space-y-0.5">
                <p class="text-sm font-medium"><?php echo $staff_name; ?></p>
                <p class="text-xs text-muted-foreground"><?php echo strtoupper($staff_role); ?></p>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleMenu(menuId) {
        var menu = document.getElementById(menuId);
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    function toggleSidebar() {
        const sidebar = document.querySelector('aside');
        if (sidebar) {
            if (sidebar.classList.contains('translate-x-0')) {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
            }
        }
    }

    // Close sidebar on window resize for mobile
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            const sidebar = document.querySelector('aside');
            if (sidebar) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
            }
        }
    });
</script>