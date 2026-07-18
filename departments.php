<?php 
    session_start();
    include 'config/hospital.php'; 

    if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
        header("Location:../auth/logout.php");
        exit();
    }
    $hid=$_SESSION["hospital_id"];
    

    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';

    $where_clauses = ["delete_flag = 0 and hospital_id = $hid"];
    if (!empty($search)) {
        $where_clauses[] = "department_name like '%$search%'";
    }
    if ($status_filter !== 'all') {
        $where_clauses[] = "status = '$status_filter'";
    }

    $where_sql = implode(" and ", $where_clauses);
    $dept_query = "select * from department where $where_sql order by id desc";
    
$dept_result = $conn->query($dept_query);
    
    

   $total_depts_query = "select count(*) as total from department where delete_flag = 0 and hospital_id = $hid";
    $total_depts_res = $conn->query($total_depts_query);
    $total_depts = $total_depts_res->fetch_assoc()['total'];

    $active_depts_query = "select count(*) as total from department where status='Active' and delete_flag=0 and hospital_id=$hid";
    $active_depts_res = $conn->query($active_depts_query);
    $active_depts = $active_depts_res->fetch_assoc()['total'];

    $inactive_depts_query = "select count(*) as total from department where status = 'Inactive' and delete_flag = 0 and hospital_id=$hid";
    $inactive_depts_res = $conn->query($inactive_depts_query);
    $inactive_depts = $inactive_depts_res->fetch_assoc()['total'];

    $total_docs_query = "select count(*) as total from doctor where delete_flag = 0 and hospital_id=$hid";
    $total_docs_res = $conn->query($total_docs_query);
    $total_docs = $total_docs_res->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .stat-card {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col">
         <?php include('header.php') ?>
        
        <div class="flex flex-1 items-start">
            <div id="sidebar-container">
                <?php include('Sidebar.php') ?>
            </div>
            
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-6 xl:ml-64 w-full">
              
                 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                        <div class="flex items-center gap-4">
                            <button id="mobile-toggle" class="xl:hidden">
                                <i class="fas fa-bars"></i>
                            </button>
                            <a href="dashboard.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                            </a>

                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">
                                    Departments
                                </h1>
                                <p class="text-slate-500 text-sm md:text-base mt-1">
                                    Manage your clinic's departments
                                </p>
                            </div>
                        </div>

                        <div class="w-full md:w-auto">
                            <a href="add_department.php" class="inline-flex items-center justify-center gap-2 w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-sm">
                                <i class="fas fa-plus"></i>
                                Add Department
                            </a>
                        </div>
                    </div>
                                
                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="stat-card bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm" onclick="filterDepartments('all')">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Total Depts</h3>
                                <div class="size-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black" id="totalDepts"><?php echo $total_depts; ?></p>
                        </div>
                        <div class="stat-card bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm" onclick="filterDepartments('Active')">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Active Depts</h3>
                                <div class="size-8 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center text-green-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black text-green-600" id="activeDepts"><?php echo $active_depts; ?></p>
                        </div>
                        <div class="stat-card bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm" onclick="filterDepartments('Inactive')">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Inactive Depts</h3>
                                <div class="size-8 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
                                </div>
                            </div>
                            <p class="text-3xl font-black text-red-600" id="inactiveDepts"><?php echo $inactive_depts; ?></p>
                        </div>
                        
                    </div>

                    <!-- Main Table -->
                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-sm">
                        
                        <div class="p-4 md:p-6 border-b dark:border-neutral-800 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <!-- Tab buttons -->
                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-neutral-800 p-1 rounded-lg w-full sm:w-auto">
                                <button onclick="filterDepartments('all')" class="tab-btn active flex-1 sm:flex-none px-4 py-1.5 rounded-md text-sm font-bold transition-all" data-tab="all">
                                    All
                                </button>
                                <button onclick="filterDepartments('Active')" class="tab-btn flex-1 sm:flex-none px-4 py-1.5 rounded-md text-sm font-bold transition-all text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-tab="active">
                                    Active
                                </button>
                                <button onclick="filterDepartments('Inactive')" class="tab-btn flex-1 sm:flex-none px-4 py-1.5 rounded-md text-sm font-bold transition-all text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-tab="inactive">
                                    Inactive
                                </button>
                            </div>

                            <form action="departments.php" method="GET" class="flex flex-col sm:flex-row items-center gap-2 w-full lg:w-auto" id="searchForm">
                                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                <div class="relative w-full sm:flex-1 lg:w-64">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    <input type="text" name="search" id="searchInput" value="<?php echo $search; ?>" placeholder="Search departments..." class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full" onkeyup="handleSearch(event)">
                                </div>
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <button type="submit" class="flex-1 sm:flex-none bg-gray-900 dark:bg-neutral-700 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-gray-800 transition-all">Search</button>
                                    <?php if(!empty($search) || $status_filter !== 'all'): ?>
                                        <a href="departments.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[600px]">
                                <thead class="bg-gray-50 dark:bg-neutral-800/50">
                                    <tr class="text-xs font-bold uppercase text-gray-500 dark:text-neutral-400">
                                        <th class="p-4">Department Name</th>
                                        <th class="p-4">Doctors</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="departmentTableBody">
                                   <?php if ($dept_result->num_rows > 0): ?>

                                        <?php while($row = $dept_result->fetch_assoc()): ?>
                                        <?php 
                                            $d_name = $row['department_name'];
                                            $doc_count_query = "select count(*) as total from doctor where department = '$d_name' and delete_flag = 0 and hospital_id=$hid";
                                            $doc_count_res = $conn->query($doc_count_query);
                                            $doc_count = $doc_count_res->fetch_assoc()['total'];
                                        ?>
                                        <tr
                                            class="department-row text-sm hover:bg-gray-50 dark:hover:bg-neutral-800/30 transition-colors cursor-pointer"
                                            onclick="window.location.href='view_department.php?id=<?php echo $row['id']; ?>'"
                                            data-status="<?php echo $row['status']; ?>"
                                            data-name="<?php echo strtolower($row['department_name']); ?>">
                                            <td class="p-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="size-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 flex-shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-bold truncate"><?php echo $row['department_name']; ?></p>
                                                        <p class="text-xs text-gray-500 truncate max-w-[150px] sm:max-w-[250px]"><?php echo $row['description'] ?: 'No description'; ?></p>
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
                                                   
                                                  <a href="edit_department.php?id=<?php echo $row['id']; ?>" class="p-2 text-gray-400 hover:text-green-600 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                                    </a>
                                                  
                                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="p-12 text-center text-gray-500 italic">
                                                No departments found matching your filters. 
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

            // Close button inside sidebar
            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });

            // Initialize Department Manager
            DepartmentManager.init();
        });

        // Main JavaScript Object for Department Management
        const DepartmentManager = {
            elements: {
                rows: () => document.querySelectorAll('.department-row'),
                totalDepts: () => document.getElementById('totalDepts'),
                activeDepts: () => document.getElementById('activeDepts'),
                inactiveDepts: () => document.getElementById('inactiveDepts'),
                searchInput: () => document.getElementById('searchInput'),
                tableBody: () => document.getElementById('departmentTableBody'),
                tabButtons: () => document.querySelectorAll('.tab-btn')
            },

            state: {
                currentFilter: 'all',
                searchTerm: ''
            },

            init() {
                this.updateStatistics();
                this.setupEventListeners();
                this.applyInitialFilters();
            },

            applyInitialFilters() {
                const urlParams = new URLSearchParams(window.location.search);
                const statusParam = urlParams.get('status');
                if (statusParam && statusParam !== 'all') {
                    const status = statusParam.charAt(0).toUpperCase() + statusParam.slice(1);
                    this.filterByStatus(status);
                }
            },

            updateStatistics() {
                const rows = document.querySelectorAll('.department-row:not(.hidden-row)');
                let total = 0, active = 0, inactive = 0;

                rows.forEach(row => {
                    if (!row.classList.contains('hidden-row')) {
                        total++;
                        const status = row.getAttribute('data-status');
                        if (status === 'Active') active++;
                        if (status === 'Inactive') inactive++;
                    }
                });

                if(this.elements.totalDepts()) this.elements.totalDepts().textContent = total;
                if(this.elements.activeDepts()) this.elements.activeDepts().textContent = active;
                if(this.elements.inactiveDepts()) this.elements.inactiveDepts().textContent = inactive;
            },

          filterByStatus(status) {
            const rows = this.elements.rows();
            const searchTerm = this.state.searchTerm.toLowerCase();

            this.elements.tabButtons().forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('text-gray-500', 'dark:text-gray-400');

                const tabValue = btn.getAttribute('data-tab');
                const isActive =
                    (status === 'all' && tabValue === 'all') ||
                    (status === 'Active' && tabValue === 'active') ||
                    (status === 'Inactive' && tabValue === 'inactive');

                if (isActive) {
                    btn.classList.add('active');
                    btn.classList.remove('text-gray-500', 'dark:text-gray-400');
                }
            });

            let visibleRows = 0;
            rows.forEach(row => {
                const rowStatus = row.dataset.status;
                const rowName = row.dataset.name;
                const matchStatus = status === 'all' || rowStatus === status;
                const matchSearch = rowName.includes(searchTerm);

                if (matchStatus && matchSearch) {
                    row.classList.remove('hidden-row');
                    visibleRows++;
                } else {
                    row.classList.add('hidden-row');
                }
            });

            const oldRow = document.getElementById("noResultRow");
            if (oldRow) oldRow.remove();

            if (visibleRows === 0 && rows.length > 0) {
                const html = `
                <tr id="noResultRow">
                    <td colspan="4" class="p-12 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-3">
                            <i class="fas fa-search text-4xl text-gray-300"></i>
                            <h3 class="font-semibold text-lg">No Departments Found</h3>
                            <p>No department matches your search/filter</p>
                        </div>
                    </td>
                </tr>`;
                this.elements.tableBody().insertAdjacentHTML("beforeend", html);
            }

            this.state.currentFilter = status;
            this.updateStatistics();
        },

            searchDepartments(searchTerm) {
                this.state.searchTerm = searchTerm.toLowerCase();
                this.filterByStatus(this.state.currentFilter);
            },

            setupEventListeners() {
                const searchInput = this.elements.searchInput();
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        this.searchDepartments(e.target.value);
                    });
                }

                document.getElementById('searchForm')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const searchValue = this.elements.searchInput().value;
                    this.searchDepartments(searchValue);
                });
            }
        };

        function filterDepartments(status) {
            DepartmentManager.filterByStatus(status);
        }

        function handleSearch(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                DepartmentManager.searchDepartments(event.target.value);
            }
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
                window.location.href = 'delete_department.php?id=' + id;
            }
        }
    </script>

</body>
</html>
