<?php
session_start(); 

include "config/hospital.php";
include "config/permission.php";

  checkPermission('staff-view');

$hid=(int)$_SESSION["hospital_id"];


if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

$view = isset($_GET['view']) ? $_GET['view'] : 'month';
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$timestamp = strtotime($currentDate);

if($view == "day"){

    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 day'));
    $title = date('d M Y', $timestamp);

}
elseif($view == "week"){

    $prevDate = date('Y-m-d', strtotime($currentDate.' -7 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +7 day'));

    $weekStart = date('d M', strtotime('monday this week', $timestamp));
    $weekEnd   = date('d M Y', strtotime('sunday this week', $timestamp));

    $title = $weekStart." - ".$weekEnd;

}
else{

    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 month'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 month'));

    $title = date('F Y', $timestamp);

}

switch($view){

    case "day":

        $dateCondition = "DATE(created_at)='".date('Y-m-d',$timestamp)."'";
        break;

    case "week":

        $dateCondition = "YEARWEEK(created_at,1)=YEARWEEK('$currentDate',1)";
        break;

    case "month":
    default:

        $dateCondition = "MONTH(created_at)='".date('m',$timestamp)."'
                          AND YEAR(created_at)='".date('Y',$timestamp)."'";
        break;
}




$base_sql = "SELECT *
FROM staff
WHERE hospital_id = $hid
AND (delete_flag IS NULL OR delete_flag = 0)
AND $dateCondition";

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_term)) {

    $search_term = mysqli_real_escape_string($conn,$search_term);

    $sql = $base_sql . " AND name LIKE '%$search_term%' ORDER BY staff_id DESC";

} else {

    $sql = $base_sql . " ORDER BY staff_id DESC";

}

$res = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8' />
    <link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <meta name='viewport' content='width=device-width, initial-scale=1' />
     <title><?php echo $hospital['hospital_name'] ?> - Staff Dashboard</title>
    
        <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Sidebar and Layout */
        #sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            transition: transform 0.3s ease;
            background: white;
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
            #main-content {
                margin-left: 0 !important;
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
        }

        .action-icons {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }
        
        .action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            text-decoration: none;
        }
        
        .action-icon svg {
            width: 18px;
            height: 18px;
        }
        
        .action-icon.view-icon:hover { background: #eff6ff; }
        .action-icon.view-icon svg { color: #3b82f6; }
        
        .action-icon.edit-icon:hover { background: #f5f3ff; }
        .action-icon.edit-icon svg { color: #8b5cf6; }
        
        .action-icon.delete-icon:hover { background: #fef2f2; }
        .action-icon.delete-icon svg { color: #ef4444; }

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

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef3c7; color: #b45309; }
        .status-suspended { background: #fecaca; color: #991b1b; }
    </style>
</head>

<body class='bg-gray-50 text-gray-900'>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class='flex min-h-screen flex-col bg-gray-50'>
        <?php include 'header.php'; ?> 
        <div class='flex flex-1 items-start' >
           
                <?php include 'Sidebar.php'; ?>
         
            
            <main id="main-content" class='flex-1 overflow-x-hidden duration-300 p-4 xl:p-6 xl:ml-64 w-full'>
                <div class='flex flex-col gap-5'>
                    <div class='flex flex-col md:flex-row md:items-center justify-between gap-4'>
                        <div class="flex items-center gap-4">
                            
                            <a href="dashboard.php" class="back-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                            </a>
                            <div>
                                <h1 class='text-2xl lg:text-3xl font-bold tracking-tight'>Staff</h1>
                                <p class='text-gray-500 text-sm md:text-base'>Manage your staff members.</p>
                            </div>
                        </div>
                        <div class="w-full md:w-auto">
                            <a class='inline-flex items-center justify-center gap-2 rounded-xl text-sm font-bold bg-blue-600 text-white hover:bg-blue-700 h-11 px-6 w-full md:w-auto shadow-sm transition-all'
                                href='add_staff.php'>
                                <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>
                                    <path d='M5 12h14'></path>
                                    <path d='M12 5v14'></path>
                                </svg>
                                Add Staff
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border shadow-sm p-4 mt-2 flex flex-col lg:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-2 w-full lg:w-auto justify-center">
                            <a href="staff.php?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>"
                               class="p-2 border rounded-lg hover:bg-gray-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M15 18l-6-6 6-6"/>
                                </svg>
                            </a>

                            <a href="staff.php?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>"
                               class="px-4 py-2 border rounded-lg hover:bg-gray-100 font-bold text-sm whitespace-nowrap">
                                <?php
                                    date_default_timezone_set("America/New_York");
                                    echo $title;
                                ?>
                            </a>

                            <a href="staff.php?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>"
                               class="p-2 border rounded-lg hover:bg-gray-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </a>
                        </div>

                        <div class="flex rounded-lg border overflow-hidden w-full lg:w-auto">
                            <a href="staff.php?view=day&date=<?php echo $currentDate; ?>"
                               class="flex-1 text-center px-4 py-2 text-sm font-bold <?php echo ($view=='day')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?> transition-colors">
                                Day
                            </a>
                            <a href="staff.php?view=week&date=<?php echo $currentDate; ?>"
                               class="flex-1 text-center px-4 py-2 text-sm font-bold <?php echo ($view=='week')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?> transition-colors border-l border-r">
                                Week
                            </a>
                            <a href="staff.php?view=month&date=<?php echo $currentDate; ?>"
                               class="flex-1 text-center px-4 py-2 text-sm font-bold <?php echo ($view=='month')?'bg-blue-600 text-white':'hover:bg-gray-100'; ?> transition-colors">
                                Month
                            </a>
                        </div>
                    </div>


                    <div class='rounded-xl border bg-white shadow-sm overflow-hidden'>
                        <div class='flex flex-col lg:flex-row lg:items-center lg:justify-between p-4 md:p-6 border-b bg-gray-50/50 gap-4'>
                            <div>
                                <h2 class='text-lg font-bold text-gray-900'>Staff List</h2>
                                <div class='text-xs text-gray-500 mt-0.5 uppercase tracking-wider'>Directory of all staff members.</div>
                            </div>

                            <form action="staff.php" method="GET" class="w-full lg:w-auto">
                                <input type="hidden" name="view" value="<?php echo $view; ?>">
                                <input type="hidden" name="date" value="<?php echo $currentDate; ?>">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1 lg:w-72">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                                        </svg>
                                        <input type="text" id="searchInput" name="search" placeholder="Search by name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-12 pr-4 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition" onkeyup="searchStaff()">
                                    </div>
                                    
                                    <?php if(!empty($search_term)): ?>
                                        <a href="staff.php"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-all">
                                            Reset
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        
                        <div class='overflow-x-auto'>
                            <table class='w-full text-left min-w-[700px]'>
                                <thead>
                                    <tr class='border-b border-gray-100 bg-gray-50/30'>
                                        <th class='p-4 text-xs font-bold text-gray-400 uppercase tracking-widest'>Name</th>
                                        <th class='p-4 text-xs font-bold text-gray-400 uppercase tracking-widest'>Role</th>
                                        <th class='p-4 text-xs font-bold text-gray-400 uppercase tracking-widest'>Status</th>
                                        <th class='p-4 text-xs font-bold text-gray-400 uppercase tracking-widest'>Contact</th>
                                        <th class='p-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest'>Actions</th>
                                    </tr>
                                </thead>
                               <tbody id="tableBody">
<?php
if ($res && mysqli_num_rows($res) > 0) {

    while ($row = mysqli_fetch_assoc($res)) {

        $staff_id = $row['staff_id'];
        $name     = $row['name'];
        $role     = $row['role'];
        $mobile   = $row['mobile'];
        $email    = $row['email'];
        $status   = $row['status'];
       

        if ($status == "Active") {
            $status_class = "status-active";
        } elseif ($status == "Suspended") {
            $status_class = "status-suspended";
        } else {
            $status_class = "status-inactive";
        }
?>
<tr class="staff-row border-b border-gray-100 hover:bg-gray-50 transition cursor-pointer"
    data-name="<?php echo strtolower($name); ?>"
    onclick="window.location.href='view_staff.php?id=<?php echo $staff_id; ?>'">

    <!-- Name -->
    <td class="p-4">
        <div class="flex items-center gap-3">
            

            <?php if (!empty($row['profile_image'])) { ?>

                <img src="<?php echo htmlspecialchars($row['profile_image']); ?>"
                    class="w-10 h-10 rounded-full object-cover border">

            <?php } else { ?>

                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                    <?php echo strtoupper(substr($name,0,2)); ?>
                </div>

            <?php } ?>

            <div>
                <div class="font-semibold">
                    <?php echo htmlspecialchars($name); ?>
                </div>
            </div>

        </div>
    </td>

    <!-- Role -->
    <td class="p-4">
        <?php echo htmlspecialchars($role); ?>
    </td>

    <!-- Status -->
    <td class="p-4">
        <span class="status-badge <?php echo $status_class; ?>">
            <?php echo htmlspecialchars($status); ?>
        </span>
    </td>

    <!-- Contact -->
    <td class="p-4">
        <div class="font-medium">
            <?php echo htmlspecialchars($mobile); ?>
        </div>

        <div class="text-xs text-gray-500">
            <?php echo htmlspecialchars($email); ?>
        </div>
    </td>

    <!-- Actions -->
    <td class="p-4 text-right">

        <div class="flex justify-end gap-3">

           

            <a href="update_staff.php?id=<?php echo $staff_id; ?>"
               onclick="event.stopPropagation();"
               class="text-green-600 hover:text-green-800">
                Edit
            </a>

            <a href="delete_staff.php?id=<?php echo $staff_id; ?>"
               onclick="event.stopPropagation(); return confirm('Delete this staff?');"
               class="text-red-600 hover:text-red-800">
                Delete
            </a>

        </div>

    </td>

</tr>

<?php
    }
} else {
?>

<tr id="noStaffRow">
    <td colspan="5" class="text-center p-10 text-gray-500">
        No Staff Found
    </td>
</tr>

<?php } ?>
</tbody>
                            </table>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500 font-bold uppercase tracking-wider">
                            <div>
                                Showing <span class="text-gray-900" id="rowCount"><?php echo $res->num_rows; ?></span> staff member<?php echo $res->num_rows > 1 ? 's' : ''; ?>
                                <?php if (!empty($search_term)): ?>
                                    matching "<?php echo htmlspecialchars($search_term); ?>"
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
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

            // Handle close button inside Sidebar.php
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
        });

        function searchStaff() {
            const input = document.getElementById("searchInput").value.toLowerCase().trim();
            const rows = document.querySelectorAll(".staff-row");
            const tableBody = document.getElementById("tableBody");
            const rowCountSpan = document.getElementById("rowCount");

            let visible = 0;

            rows.forEach(row => {
                const name = row.getAttribute("data-name");
                if (name.includes(input)) {
                    row.style.display = "";
                    visible++;
                } else {
                    row.style.display = "none";
                }
            });

            const oldRow = document.getElementById("noStaffRow");
            if (oldRow) oldRow.remove();

            if (visible === 0 && rows.length > 0) {
                tableBody.insertAdjacentHTML("beforeend", `
                    <tr id="noStaffRow">
                        <td colspan="5" class="p-16 text-center text-gray-400">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                                <span class="font-bold text-gray-900">No Staff Found</span>
                                <span class="text-sm max-w-xs mx-auto">No results matching "${input}".</span>
                                <a href="staff.php" class="mt-2 text-blue-600 hover:underline text-sm font-bold">Clear search</a>
                            </div>
                        </td>
                    </tr>
                `);
            }

            if (rowCountSpan) {
                rowCountSpan.textContent = visible;
            }
        }
    </script>
</body>
</html>
