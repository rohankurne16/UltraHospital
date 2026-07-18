<?php 
    session_start();
    include '../config/hospital.php'; 

    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }

    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';

    $where_clauses = ["delete_flag = 0"];
    if (!empty($search)) {
        $where_clauses[] = "department_name like '%$search%'";
    }
    if ($status_filter !== 'all') {
        $where_clauses[] = "status = '$status_filter'";
    }

    $where_sql = implode(" and ", $where_clauses);
    $dept_query = "select * from department where $where_sql order by id desc";
    $result = $conn->query($dept_query);

    $total_depts_query = "select count(*) as total from department where delete_flag = 0";
    $total_depts_res = $conn->query($total_depts_query);
    $total_depts = $total_depts_res->fetch_assoc()['total'];

    $active_depts_query = "select count(*) as total from department where status = 'Active' and delete_flag = 0";
    $active_depts_res = $conn->query($active_depts_query);
    $active_depts = $active_depts_res->fetch_assoc()['total'];

    $inactive_depts_query = "select count(*) as total from department where status = 'Inactive' and delete_flag = 0";
    $inactive_depts_res = $conn->query($inactive_depts_query);
    $inactive_depts = $inactive_depts_res->fetch_assoc()['total'];

    $total_docs_query = "select count(*) as total from doctor where delete_flag = 0";
    $total_docs_res = $conn->query($total_docs_query);
    $total_docs = $total_docs_res->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - <?php echo $hospital['hospital_logo'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">


    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-btn.active {
            background: white !important;
            color: #2563eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .dark .tab-btn.active {
            background: #262626 !important;
            color: #60a5fa !important;
        }
        .department-row {
            transition: all 0.3s ease;
        }
        .department-row.hidden-row {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="flex min-h-screen flex-col">
         <?php include('staff_header.php') ?>
        
        <div class="flex flex-1 items-start">
            <?php include('staff_sidebar.php') ?>
            <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-6xl mx-auto">

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Total Depts</h3>
                                <div class="size-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black" id="totalDepts"><?php echo $total_depts; ?></p>
                        </div>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Active Depts</h3>
                                <div class="size-8 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center text-green-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black text-green-600" id="activeDepts"><?php echo $active_depts; ?></p>
                        </div>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Inactive Depts</h3>
                                <div class="size-8 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black text-red-600" id="inactiveDepts"><?php echo $inactive_depts; ?></p>
                        </div>
                        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Total Doctors</h3>
                                <div class="size-8 rounded-lg bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center text-purple-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black text-purple-600"><?php echo $total_docs; ?></p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-sm">
                        
                        <div class="p-6 border-b dark:border-neutral-800 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                            <!-- Tab buttons with onclick -->
                            <div class="flex items-center gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-lg">
                                <button onclick="filterDepartments('all')" class="tab-btn active px-4 py-1.5 rounded-md text-sm font-bold transition-all" data-tab="all">
                                    All
                                </button>
                                <button onclick="filterDepartments('Active')" class="tab-btn px-4 py-1.5 rounded-md text-sm font-bold transition-all text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-tab="active">
                                    Active
                                </button>
                                <button onclick="filterDepartments('Inactive')" class="tab-btn px-4 py-1.5 rounded-md text-sm font-bold transition-all text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-tab="inactive">
                                    Inactive
                                </button>
                            </div>

                            <form action="departments.php" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                <div class="relative flex-1 md:w-64">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    <input type="text" name="search" id="searchInput" value="<?php echo $search; ?>" placeholder="Search departments..." class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full" onkeyup="searchDepartments()">
                                </div>
                                <button type="submit" class="bg-gray-900 dark:bg-neutral-700 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-800 transition-all">Search</button>
                                
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 dark:bg-neutral-800/50">
                                    <tr class="text-xs font-bold uppercase text-gray-500 dark:text-neutral-400">
                                        <th class="p-4">Department Name</th>
                                        <th class="p-4">Doctors</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="departmentTableBody">
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <?php 
                                            $d_name = $row['department_name'];
                                            $doc_count_query = "select count(*) as total from doctor where department = '$d_name' and delete_flag = 0";
                                            $doc_count_res = $conn->query($doc_count_query);
                                            $doc_count = $doc_count_res->fetch_assoc()['total'];
                                        ?>
                                        <tr class="department-row text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/30 transition-colors" data-status="<?php echo $row['status']; ?>" data-name="<?php echo strtolower($row['department_name']); ?>">
                                            <td class="p-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="size-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold"><?php echo $row['department_name']; ?></p>
                                                        <p class="text-xs text-gray-500 truncate max-w-[200px]"><?php echo $row['description'] ?: 'No description'; ?></p>
                                                    </div>
                                                </div>
                                            
                                            </td>
                                            <td class="p-4">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-neutral-800 text-xs font-bold text-gray-600 dark:text-neutral-400">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                                    <?php echo $doc_count; ?>
                                                </span>
                                            </td>
                                            <td class="p-4">
                                                <span class="status-badge px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $row['status'] === 'Active' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'; ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="view_department.php?id=<?php echo $row['id']; ?>" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" title="View">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    </a>
                                                  <!--  <a href="departments/edit_department.php?id=<?php echo $row['id']; ?>" class="p-2 text-gray-400 hover:text-green-600 transition-colors" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                                    </a>
                                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                    </button> -->
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="p-12 text-center text-gray-500 italic">
                                                No departments found matching your filters. <a href="show_departments.php" class="text-blue-600 font-bold hover:underline">Clear Filters</a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        // Function to filter departments by status
        function filterDepartments(status) {
            // Update tab button styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('text-gray-500', 'dark:text-gray-400');
                btn.classList.remove('text-blue-600', 'dark:text-blue-400');
            });
            
            // Get the clicked button and add active class
            const clickedBtn = document.querySelector(`[data-tab="${status.toLowerCase()}"]`) || document.querySelector('[data-tab="all"]');
            clickedBtn.classList.add('active');
            clickedBtn.classList.remove('text-gray-500', 'dark:text-gray-400');
            
            // Show/hide rows based on status
            const rows = document.querySelectorAll('.department-row');
            let visibleCount = 0;
            let activeCount = 0;
            let inactiveCount = 0;
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                
                if (status === 'all') {
                    row.classList.remove('hidden-row');
                    visibleCount++;
                } else if (rowStatus === status) {
                    row.classList.remove('hidden-row');
                    visibleCount++;
                } else {
                    row.classList.add('hidden-row');
                }
                
                // Count for statistics
                if (rowStatus === 'Active') activeCount++;
                if (rowStatus === 'Inactive') inactiveCount++;
            });
            
            // Update statistics
            document.getElementById('totalDepts').textContent = visibleCount;
            document.getElementById('activeDepts').textContent = activeCount;
            document.getElementById('inactiveDepts').textContent = inactiveCount;
        }

        // Function to search departments
        function searchDepartments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.department-row');
            const currentTab = document.querySelector('.tab-btn.active');
            const currentStatus = currentTab ? currentTab.getAttribute('data-tab') : 'all';
            
            let visibleCount = 0;
            let activeCount = 0;
            let inactiveCount = 0;
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowName = row.getAttribute('data-name');
                const matchesSearch = rowName.includes(searchTerm);
                const matchesStatus = currentStatus === 'all' || rowStatus === currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
                
                if (matchesSearch && matchesStatus) {
                    row.classList.remove('hidden-row');
                    visibleCount++;
                } else {
                    row.classList.add('hidden-row');
                }
                
                // Count for statistics
                if (rowStatus === 'Active') activeCount++;
                if (rowStatus === 'Inactive') inactiveCount++;
            });
            
            // Update statistics
            document.getElementById('totalDepts').textContent = visibleCount;
            document.getElementById('activeDepts').textContent = activeCount;
            document.getElementById('inactiveDepts').textContent = inactiveCount;
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
                window.location.href = 'departments/delete_department.php?id=' + id;
            }
        }

        // Initialize the page with all departments visible
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure all rows are visible initially
            const rows = document.querySelectorAll('.department-row');
            rows.forEach(row => {
                row.classList.remove('hidden-row');
            });
        });
    </script>
</body>
</html>