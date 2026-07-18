<?php 
    $id=$_SESSION["id"];
    if(!isset($_SESSION["id"])) {
        header("Location:../index.php");
    }

    include("../config/hospital.php");

?>
<header class="sticky top-0 z-40 border-b bg-white duration-300 xl:ml-64">
    <div class="flex h-16 items-center justify-between px-4 md:px-6">
        <button class="xl:hidden p-2 rounded-md hover:bg-gray-100">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        <div class="flex items-center gap-2 px-4 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold hidden md:flex">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
           Hospital Managment System with AI
        </div>
        <div class="ml-auto flex items-center space-x-4">
            
            <div class="flex items-center gap-3 pl-4 border-l">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-900"><?php echo $_SESSION['name'] ?></p>
                  
                </div>
                
                <!-- Profile Dropdown Container -->
                <div class="relative" id="profile-dropdown-container">
                    <button 
                        onclick="toggleDropdown()"
                        class="relative h-9 w-9 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                    >
                        <?php
                            $get_image = "select patient_image from patients where register_id='$id'";
                            $img = $conn->query($get_image);
                            $row = $img->fetch_assoc();

                            if (!empty($row['patient_image'])) {
                            ?>
                                <img src="<?php echo $row['patient_image']; ?>"
                                    alt="Profile"
                                    class="w-9 h-9 rounded-full object-cover">
                            <?php
                            } else {
                                $name_parts = explode(' ', $_SESSION['name']);
                                $initials = '';

                                foreach ($name_parts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }

                                echo substr($initials, 0, 2);
                            }
                        ?>
                    </button>

                   
                    <div 
                        id="profile-dropdown"
                        class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-50"
                        role="menu" 
                        aria-orientation="vertical" 
                        aria-labelledby="user-menu-button"
                    >
                        <div class="px-4 py-3 md:hidden">
                            <p class="text-sm font-bold text-gray-900"><?php echo $_SESSION['name'] ?></p>
                            <p class="text-xs text-gray-500 truncate">ID: #<?php echo $_SESSION['patient_id'] ?></p>
                        </div>
                        <div class="py-1" role="none">
                            <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" role="menuitem">
                                <i data-lucide="user" class="w-4 h-4 mr-3 text-gray-400"></i>
                                View Profile
                            </a>
                          
                        </div>
                        <div class="py-1" role="none">
                            <a href="../auth/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors" role="menuitem">
                                <i data-lucide="log-out" class="w-4 h-4 mr-3 text-red-400"></i>
                                Logout
                            </a>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('profile-dropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    window.addEventListener('click', function(e) {
        const container = document.getElementById('profile-dropdown-container');
        const dropdown = document.getElementById('profile-dropdown');
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
</script>
