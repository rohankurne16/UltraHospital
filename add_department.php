<?php 

session_start();
include 'config/hospital.php'; 

if (!isset($_SESSION["id"]) && empty($_SESSION["id"])) {
    header("Location:auth/logout.php");
    exit();
}

$hid=$_SESSION["hospital_id"];
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept_name = mysqli_real_escape_string($conn, $_POST['department_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Server-side Validation with Regex
    if (empty($dept_name)) {
        $message = "Department name is required!";
        $messageType = "error";
    } elseif (!preg_match('/^[A-Za-z0-9\s\-\'&.]+$/', $dept_name)) {
        $message = "Invalid Department Name. Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.";
        $messageType = "error";
    } elseif (!empty($description) && !preg_match('/^[A-Za-z0-9\s\-\.,#\/:]+$/', $description)) {
        $message = "Invalid Description. Only letters, numbers, spaces, hyphens, commas, periods, hash, slashes, and colons are allowed.";
        $messageType = "error";
    } elseif (!in_array($status, ['Active', 'Inactive'])) {
        $message = "Invalid Status selected.";
        $messageType = "error";
    } else {
        $check_query = "SELECT id FROM department WHERE department_name = '$dept_name' AND hospital_id = $hid AND delete_flag = 0";
        $check_result = $conn->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $message = "Error: A department with this name already exists!";
            $messageType = "error";
        } else {
            $sql = "INSERT INTO department (department_name, description, status, hospital_id) VALUES ('$dept_name', '$description', '$status', '$hid')";   
            if ($conn->query($sql) === TRUE) {
                header("Location: departments.php?success=Department added successfully");
                exit();
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Department - <?php echo $hospital['hospital_name'] ?></title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        @media (min-width: 1280px) {
            #sidebar-container {
                transform: translateX(0);
                width: 256px;
            }
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

        /* Validation Styles */
        .field-group { position: relative; }
        .input-wrapper { position: relative; }
        
        .input-wrapper .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .input-wrapper .input-icon.valid { color: #22c55e; opacity: 1; }
        .input-wrapper .input-icon.invalid { color: #ef4444; opacity: 1; }
        
        .form-input.error { 
            border-color: #ef4444 !important; 
            background-color: #fef2f2 !important; 
        }
        .form-input.success { 
            border-color: #22c55e !important; 
            background-color: #f0fdf4 !important; 
        }
        
        .validation-message {
            font-size: 11px;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        .validation-message.show { display: flex; }
        .validation-message.error { color: #ef4444; }
        .validation-message.success { color: #22c55e; }
        
        .validation-hint {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#131212] text-neutral-900 dark:text-neutral-100">

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex min-h-screen flex-col">
         <?php include('header.php') ?>
        
        <div class="flex flex-1 items-start">
           
                <?php include('Sidebar.php') ?>
           
            <main id="main-content" class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-6 xl:ml-64 w-full">
                <div class="max-w-4xl mx-auto">
                    
                    <div class="mb-8 flex items-center gap-4">
                      
                         <a href="departments.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold tracking-tight mb-1">Add Department</h1>
                            <p class="text-gray-500 text-sm md:text-base">Create a new medical department.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border <?php echo $messageType === 'success' ? 'bg-green-50 border-green-100 text-green-700 dark:bg-green-900/10 dark:border-green-900/20 dark:text-green-400' : 'bg-red-50 border-red-100 text-red-700 dark:bg-red-900/10 dark:border-red-900/20 dark:text-red-400'; ?>">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="<?php echo $messageType === 'success' ? 'm9 12 2 2 4-4' : 'm15 9-6 6M9 9l6 6'; ?>"/></svg>
                            <p class="font-bold text-sm md:text-base"><?php echo $message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl shadow-sm overflow-hidden">
                        <form action="add_department.php" method="POST" id="departmentForm" novalidate class="p-6 md:p-8 lg:p-12">
                            <div class="grid grid-cols-1 gap-6 md:gap-8">
                                
                                <div class="space-y-2 field-group">
                                    <label for="department_name" class="text-xs font-bold uppercase tracking-widest text-gray-400">Department Name <span class="text-red-500">*</span></label>
                                    <div class="input-wrapper">
                                        <input type="text" id="department_name" name="department_name" required 
                                            placeholder="e.g. Cardiology" 
                                            class="form-input w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm md:text-base"
                                            pattern="^[A-Za-z0-9\s\-\'&.]+$"
                                            data-validation="dept_name"
                                            title="Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.">
                                        <i class="fas fa-check-circle input-icon" id="department_name_icon"></i>
                                    </div>
                                    <div class="validation-message error" id="department_name_error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Only letters, numbers, spaces, hyphens, apostrophes, ampersands, and periods are allowed.</span>
                                    </div>
                                    <div class="validation-message success" id="department_name_success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Valid department name</span>
                                    </div>
                                    <small class="validation-hint">Letters, numbers, spaces, hyphens, apostrophes, ampersands, periods</small>
                                </div>

                                <div class="space-y-2">
                                    <label for="status" class="text-xs font-bold uppercase tracking-widest text-gray-400">Status</label>
                                    <select id="status" name="status" class="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm md:text-base">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="space-y-2 field-group">
                                    <label for="description" class="text-xs font-bold uppercase tracking-widest text-gray-400">Description</label>
                                    <div class="input-wrapper">
                                        <textarea id="description" name="description" rows="5" 
                                            placeholder="Describe the department's scope..." 
                                            class="form-input w-full bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none text-sm md:text-base"
                                            pattern="^[A-Za-z0-9\s\-\.,#\/:]*$"
                                            data-validation="description"></textarea>
                                    </div>
                                    <div class="validation-message error" id="description_error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Only letters, numbers, spaces, hyphens, commas, periods, hash, slashes, and colons are allowed.</span>
                                    </div>
                                    <div class="validation-message success" id="description_success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Valid description format</span>
                                    </div>
                                    <small class="validation-hint">Letters, numbers, spaces, hyphens, commas, periods, hash, slashes, colons</small>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-4 border-t dark:border-neutral-800">
                                    <a href="departments.php" class="text-gray-500 hover:text-gray-700 font-bold text-sm order-2 sm:order-1">Cancel</a>
                                    <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20 order-1 sm:order-2">
                                        Save Department
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="mt-8 p-6 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/20 rounded-2xl">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-blue-900 dark:text-blue-400 mb-1">Duplicate Prevention</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300/70">The system automatically checks if a department name already exists before saving to ensure data consistency.</p>
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

            document.addEventListener('click', function(e) {
                const closeBtn = e.target.closest('.lucide-x') || e.target.closest('.fa-xmark') || e.target.closest('#sidebar-close');
                if (closeBtn && window.innerWidth < 1280) {
                    closeSidebar();
                }
            });
        });

        // ============================================================
        // VALIDATION LOGIC
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Define validation patterns
            const patterns = {
                dept_name: /^[A-Za-z0-9\s\-\'&.]+$/,
                description: /^[A-Za-z0-9\s\-\.,#\/:]*$/
            };

            // Get fields that need validation
            const fields = {
                department_name: { pattern: patterns.dept_name, required: true },
                description: { pattern: patterns.description, required: false }
            };

            // Function to validate a single field
            function validateField(fieldId) {
                const input = document.getElementById(fieldId);
                if (!input) return true;

                const value = input.value.trim();
                const fieldConfig = fields[fieldId];
                const isRequired = fieldConfig ? fieldConfig.required : false;
                const pattern = fieldConfig ? fieldConfig.pattern : null;

                const errorMsg = document.getElementById(fieldId + '_error');
                const successMsg = document.getElementById(fieldId + '_success');
                const icon = document.getElementById(fieldId + '_icon');

                // Reset states
                input.classList.remove('error', 'success');
                if (errorMsg) errorMsg.classList.remove('show');
                if (successMsg) successMsg.classList.remove('show');
                if (icon) {
                    icon.classList.remove('valid', 'invalid');
                }

                // Check if empty and required
                if (isRequired && value === '') {
                    input.classList.add('error');
                    if (errorMsg) errorMsg.classList.add('show');
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // If optional and empty, it's valid
                if (!isRequired && value === '') {
                    input.classList.add('success');
                    if (successMsg) successMsg.classList.add('show');
                    if (icon) icon.classList.add('valid');
                    return true;
                }

                // Test against pattern
                if (pattern && !pattern.test(value)) {
                    input.classList.add('error');
                    if (errorMsg) errorMsg.classList.add('show');
                    if (icon) icon.classList.add('invalid');
                    return false;
                }

                // All validations passed
                input.classList.add('success');
                if (successMsg) successMsg.classList.add('show');
                if (icon) icon.classList.add('valid');
                return true;
            }

            // Attach event listeners for real-time validation
            Object.keys(fields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (!input) return;

                // Validate on blur
                input.addEventListener('blur', function() {
                    validateField(fieldId);
                });

                // Validate on input for better UX
                input.addEventListener('input', function() {
                    validateField(fieldId);
                });
            });

            // Form submission validation
            document.getElementById('departmentForm').addEventListener('submit', function(e) {
                let isValid = true;

                Object.keys(fields).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    const firstError = document.querySelector('.form-input.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    </script>
</body>
</html>