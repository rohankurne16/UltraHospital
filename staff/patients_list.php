<?php
 session_start(); 
include "../config/db.php";


$base_sql = "SELECT * FROM patients WHERE delete_flag IS NULL OR delete_flag=0";

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_term)) {
    $search_term = mysqli_real_escape_string($conn, $search_term);
    $sql = $base_sql . " AND patient_name LIKE '%$search_term%'";
} else {
    $sql = $base_sql;
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1' />
    <title>MedixPro - Clinic Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
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

        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal-box {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
        }
        
        .modal-header {
            display: flex;
            justify-conhtent: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f8fafc;
        }
        
        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        
        .modal-close {
            background: #f1f5f9;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
        }
        
        .modal-close:hover { background: #e2e8f0; }
        
        .btn {
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-label { font-weight: 600; color: #64748b; font-size: 14px; }
        .detail-value { color: #0f172a; font-size: 14px; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef3c7; color: #b45309; }
    </style>
</head>

<body class='bg-gray-50 text-gray-900'>
    <div class='flex min-h-screen flex-col bg-gray-50'>

        <?php include '../staff/staff_header.php'; ?>

        <div class='flex flex-1 items-start'>

            <?php include '../staff/staff_sidebar.php'; ?>
            
            <main class='flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64'>
                <div class='flex flex-col gap-5'>
                    <div class='flex flex-col md:flex-row items-center justify-between gap-4'>
                        <div>
                            <h1 class='text-2xl lg:text-3xl font-bold tracking-tight mb-2'>Patients</h1>
                            <p class='text-gray-500'>Manage your patients and their medical records.</p>
                        </div>
                        <a class='inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 h-10 px-5'
                            href='patients/add.php'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>
                                <path d='M5 12h14'></path>
                                <path d='M12 5v14'></path>
                            </svg>
                            Add Patient
                        </a>
                    </div>

                    <div class='rounded-xl border bg-white shadow-sm overflow-hidden' >
                        <div class='flex flex-col md:flex-row md:items-center md:justify-between p-4 border-b bg-gray-50/50'>
                            <div>
                                <h2 class='text-xl font-semibold text-gray-900'>Patients List</h2>
                                <div class='text-sm text-gray-500 mt-0.5'>A list of all patients in your clinic with their details.</div>
                            </div>

                            <form action="patients_list.php" method="GET" class="md:mb-0">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-1">
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
                                        <input
                                            type="text"
                                            name="search"
                                            placeholder="Search patient by name..."
                                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                            class="w-full rounded-lg border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                                        >
                                    </div>
                                    <button
                                        type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
                                        Search
                                    </button>
                                    <a href="patients.php"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition">
                                        Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                        <div class='p-4'>
                            <div class='relative w-full overflow-auto'>
                                <table class='w-full caption-bottom text-sm'>
                                    <thead>
                                        <tr class='border-b border-gray-200'>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Name</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>DOB</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Age</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Blood Group</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Gender</th>
                                            <th class='h-12 px-4 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider'>Contact</th>
                                            <th class='h-12 px-4 text-right font-semibold text-gray-600 text-xs uppercase tracking-wider'>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result && $result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $patient_id = $row['patient_id'];
                                                $name = $row['patient_name'];
                                                $dob = $row['date_of_birth'];
                                                $age = $row['age'];
                                                $blood_group = $row['blood_group'];
                                                $gender = $row['gender'];
                                                $email = $row['email'];
                                                $mobile = $row['mobile'];
                                                $status = isset($row['status']) ? $row['status'] : 'Active';
                                                $status_class = $status == 'Active' ? 'status-active' : 'status-inactive';
                                        ?>
                                        <tr class='border-b border-gray-100 hover:bg-gray-50/50'>
                                            <td class='p-4 align-middle'>
                                                <div class='flex items-center gap-3'>
                                                    <img src="<?php echo str_replace('../', '', $row['patient_image']); ?>" width="30" height="30">
                                                    <div>
                                                        <p class='font-medium text-gray-900'><?php echo htmlspecialchars($name); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($dob); ?></td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($age); ?></td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($blood_group); ?></td>
                                            <td class='p-4 align-middle text-gray-700'><?php echo htmlspecialchars($gender); ?></td>
                                            <td class='p-4 align-middle'>
                                                <div class='text-gray-700'><?php echo htmlspecialchars($mobile); ?></div>
                                                <div class='text-xs text-gray-400'><?php echo htmlspecialchars($email); ?></div>
                                            </td>
                                            <td class='p-4 align-middle text-right'>
                                                <div class='action-icons'>
                                                    <a href='../staff/view_patient.php?id=<?php echo $patient_id; ?>' class='action-icon view-icon' title='View Details'>
                                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'></path>
                                                            <circle cx='12' cy='12' r='3'></circle>
                                                        </svg>
                                                    </a>
                                                    <a href='../staff/patient_update.php?id=<?php echo $patient_id; ?>' class='action-icon edit-icon' title='Edit Patient'>
                                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <path d='M12 20h9'></path>
                                                            <path d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'></path>
                                                        </svg>
                                                    </a>
                                                    <a href='../staff/delete_patient.php?id=<?php echo $patient_id; ?>' class='action-icon delete-icon' title='Delete Patient' onclick='return confirm("Are you sure you want to delete this patient?")'>
                                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <polyline points='3 6 5 6 21 6'></polyline>
                                                            <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
                                                            <line x1='10' y1='11' x2='10' y2='17'></line>
                                                            <line x1='14' y1='11' x2='14' y2='17'></line>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan='7' class='p-12 text-center text-gray-400'>
                                                <div class='flex flex-col items-center gap-2'>
                                                    <svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' class='text-gray-300'>
                                                        <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>
                                                        <circle cx='12' cy='7' r='4'></circle>
                                                    </svg>
                                                    <span>No patients found</span>
                                                    <span class='text-sm'>Click "Add Patient" to register a new patient.</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

<?php
if ($result) {
    mysqli_free_result($result);
}
mysqli_close($conn);
?>