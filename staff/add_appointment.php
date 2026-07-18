<?php 
session_start(); 
include("../../config/hospital.php");

$conn->set_charset("utf8");

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_no   = $_POST['appointment_no'] ?? "";
    $patient_id       = $_POST['patient_id'] ?? "";
    $doctor_name      = $_POST['doctor_name'] ?? "";
    $department       = $_POST['department'] ?? "";
    $appointment_type = $_POST['appointment_type'] ?? "";
    $appointment_date = $_POST['appointment_date'] ?? "";
    $appointment_time = $_POST['appointment_time'] ?? "";
    $duration         = $_POST['duration'] ?? "";
    $reason           = $_POST['reason'] ?? "";
    $note             = $_POST['note'] ?? "";

    // Get patient name from patient_id
    $patient_name = "";
    if (!empty($patient_id)) {
        $patientQuery = "SELECT patient_name FROM patients WHERE patient_id = '$patient_id'";
        $patientResult = $conn->query($patientQuery);
        if ($patientResult && $patientResult->num_rows > 0) {
            $patientRow = $patientResult->fetch_assoc();
            $patient_name = $patientRow['patient_name'];
        }
    }

    if (empty($appointment_no) || empty($patient_id) || empty($doctor_name) || empty($appointment_date) || empty($appointment_time)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $sql = "INSERT INTO appointments (appointment_no, patient_id, patient_name, doctor_name, department, appointment_type, appointment_date, appointment_time, duration, reason, notes) 
                VALUES ('$appointment_no', '$patient_id', '$patient_name', '$doctor_name', '$department', '$appointment_type', '$appointment_date', '$appointment_time', '$duration', '$reason', '$note')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Appointment scheduled successfully!'); window.location='appointments_list.php';</script>";
            exit();
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Fetch all patients
$patientQuery = "SELECT patient_id, patient_name FROM patients ORDER BY patient_name ASC";
$patientResult = $conn->query($patientQuery);
$patients = array();
if ($patientResult && $patientResult->num_rows > 0) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch all doctors
$doctorQuery = "SELECT doctor_name, department FROM doctor ORDER BY doctor_name ASC";
$doctorResult = $conn->query($doctorQuery);
$doctors = array();
if ($doctorResult && $doctorResult->num_rows > 0) {
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Get unique departments
$departments = array();
foreach ($doctors as $doctor) {
    if (!in_array($doctor['department'], $departments)) {
        $departments[] = $doctor['department'];
    }
}
sort($departments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $hospital['hospital_name'] ?> - Add Appointment</title>
    <link rel="icon" type="image/png" href="../staff/<?php echo $hospital['hospital_logo'] ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            background-color: #f3f4f6;
            color: #111827;
        }
        .step-active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
        }
        .step-inactive {
            color: #6b7280;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }
        .list-item {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .list-item:hover {
            background: #f3f4f6;
        }
        .list-item.selected {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
        }
        .alert {
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .selected-display {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 10px;
            font-size: 14px;
        }
        .doctor-item {
            display: none;
        }
        .doctor-item.visible {
            display: flex;
        }
        .selected-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 10px;
            font-size: 14px;
        }
        .search-results {
            position: absolute;
            z-index: 50;
            width: 100%;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            max-height: 250px;
            overflow-y: auto;
            display: none;
        }
        .search-results .result-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        }
        .search-results .result-item:hover {
            background: #f3f4f6;
        }
        .search-results .result-item:last-child {
            border-bottom: none;
        }
        .search-results .result-item .name {
            font-weight: 500;
            font-size: 14px;
            color: #0f172a;
        }
        .search-results .result-item .info {
            font-size: 12px;
            color: #64748b;
        }
        .selected-item {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-top: 8px;
        }
        .selected-item .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .selected-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }
        .selected-item .sub {
            font-size: 12px;
            color: #64748b;
        }
        @media (max-width: 768px) { 
            .form-card .body { padding: 16px; } 
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Header -->
        <?php include '../staff_header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include '../staff_sidebar.php'; ?>

            <!-- Main Content Area -->
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-5xl mx-auto w-full">
                    <div class="mb-8">
                        <div class="flex items-center gap-4">
                            <a href="appointments_list.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Add New Appointment</h1>
                                <p class="text-gray-500">Schedule a new appointment for a patient with a doctor.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="alert mb-6 p-4 rounded-lg <?php echo ($messageType === 'success') ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                            <div class="flex items-center gap-2">
                                <i data-lucide="<?php echo ($messageType === 'success') ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                                <span><?php echo $message; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Step Navigation -->
                    <div class="flex border-b mb-8 overflow-x-auto custom-scrollbar">
                        <button onclick="showSection('appointment')" type="button" id="btn-appointment"
                            class="px-6 py-3 text-sm font-medium whitespace-nowrap step-active">
                            Appointment Details
                        </button>
                        <button onclick="showSection('patient')" type="button" id="btn-patient"
                            class="px-6 py-3 text-sm font-medium whitespace-nowrap step-inactive">
                            Patient Selection
                        </button>
                        <button onclick="showSection('doctor')" type="button" id="btn-doctor"
                            class="px-6 py-3 text-sm font-medium whitespace-nowrap step-inactive">
                            Doctor Selection
                        </button>
                    </div>

                    <!-- Form Container -->
                    <form action="add_appointment.php" method="POST" class="bg-white rounded-xl border shadow-sm p-6 md:p-8">
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" id="patient_id" name="patient_id" value="">
                        <input type="hidden" id="doctor_name" name="doctor_name" value="">

                        <!-- Section 0: Appointment Details -->
                        <div id="section-appointment" class="form-section active">
                            <h2 class="text-lg font-semibold mb-6">Appointment Details</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="appointment_no">Appointment No <span class="text-red-500">*</span></label>
                                    <input id="appointment_no" name="appointment_no" placeholder="Enter appointment number"
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                        value="<?php echo isset($_POST['appointment_no']) ? htmlspecialchars($_POST['appointment_no']) : ''; ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="appointment_type">Visit Type <span class="text-red-500">*</span></label>
                                    <select id="appointment_type" name="appointment_type"
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                                        <option value="">Select type</option>
                                        <option value="Consultation" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                                        <option value="Follow-up" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'Follow-up') ? 'selected' : ''; ?>>Follow-up</option>
                                        <option value="Procedure" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'Procedure') ? 'selected' : ''; ?>>Procedure</option>
                                        <option value="Check-up" <?php echo (isset($_POST['appointment_type']) && $_POST['appointment_type'] === 'Check-up') ? 'selected' : ''; ?>>Check-up</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="appointment_date">Date <span class="text-red-500">*</span></label>
                                    <input id="appointment_date" type="date" name="appointment_date"
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                        value="<?php echo isset($_POST['appointment_date']) ? htmlspecialchars($_POST['appointment_date']) : date('Y-m-d'); ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="appointment_time">Time Slot <span class="text-red-500">*</span></label>
                                    <select id="appointment_time" name="appointment_time"
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                                        <option value="">Select time</option>
                                        <option value="09:00 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '09:00 AM') ? 'selected' : ''; ?>>09:00 AM</option>
                                        <option value="09:30 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '09:30 AM') ? 'selected' : ''; ?>>09:30 AM</option>
                                        <option value="10:00 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '10:00 AM') ? 'selected' : ''; ?>>10:00 AM</option>
                                        <option value="10:30 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '10:30 AM') ? 'selected' : ''; ?>>10:30 AM</option>
                                        <option value="11:00 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '11:00 AM') ? 'selected' : ''; ?>>11:00 AM</option>
                                        <option value="11:30 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '11:30 AM') ? 'selected' : ''; ?>>11:30 AM</option>
                                        <option value="02:00 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '02:00 PM') ? 'selected' : ''; ?>>02:00 PM</option>
                                        <option value="02:30 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '02:30 PM') ? 'selected' : ''; ?>>02:30 PM</option>
                                        <option value="03:00 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '03:00 PM') ? 'selected' : ''; ?>>03:00 PM</option>
                                        <option value="03:30 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '03:30 PM') ? 'selected' : ''; ?>>03:30 PM</option>
                                        <option value="04:00 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '04:00 PM') ? 'selected' : ''; ?>>04:00 PM</option>
                                        <option value="04:30 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] === '04:30 PM') ? 'selected' : ''; ?>>04:30 PM</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="duration">Duration <span class="text-red-500">*</span></label>
                                    <select id="duration" name="duration"
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                                        <option value="15" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '15') ? 'selected' : ''; ?>>15 minutes</option>
                                        <option value="30" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '30') ? 'selected' : 'selected'; ?>>30 minutes</option>
                                        <option value="45" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '45') ? 'selected' : ''; ?>>45 minutes</option>
                                        <option value="60" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '60') ? 'selected' : ''; ?>>60 minutes</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="department">Department <span class="text-red-500">*</span></label>
                                    <select id="department" name="department"
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                        onchange="filterDoctorsByDepartment()" required>
                                        <option value="">Select department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="reason">Reason for Visit</label>
                                    <textarea id="reason" name="reason" placeholder="Enter reason for visit"
                                        class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="note">Additional Notes</label>
                                    <textarea id="note" name="note" placeholder="Enter any additional notes"
                                        class="w-full min-h-[80px] p-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea>
                                </div>
                            </div>

                            <!-- Selected Patient and Doctor Summary -->
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div id="selectedPatientSummary" class="selected-info" style="display: none;">
                                    <div class="text-xs text-gray-500">Selected Patient:</div>
                                    <div class="text-green-700 font-medium" id="selectedPatientSummaryName"></div>
                                    <div class="text-xs text-gray-400" id="selectedPatientSummaryId"></div>
                                </div>
                                <div id="selectedDoctorSummary" class="selected-info" style="display: none;">
                                    <div class="text-xs text-gray-500">Selected Doctor:</div>
                                    <div class="text-green-700 font-medium" id="selectedDoctorSummaryName"></div>
                                    <div class="text-xs text-gray-400" id="selectedDoctorSummaryDepartment"></div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="button" onclick="showSection('patient')"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md font-medium hover:bg-blue-700 transition">Next:
                                    Patient Selection</button>
                            </div>
                        </div>

                        <!-- Section 1: Patient Selection -->
                        <div id="section-patient" class="form-section">
                            <h2 class="text-lg font-semibold mb-6">Select Patient</h2>
                            
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="patientSearch">Search Patient</label>
                                    <input id="patientSearch" type="text" placeholder="Search by name..."
                                        class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                        onkeyup="filterPatients()">
                                </div>

                                <div class="border border-gray-200 rounded-lg max-h-[300px] overflow-y-auto custom-scrollbar" id="patientListContainer" style="display:none;">
                                    <?php if (count($patients) > 0): ?>
                                        <?php foreach ($patients as $patient): ?>
                                            <div class="list-item px-4 py-3 border-b border-gray-100 flex items-center gap-3"
                                                onclick="selectPatient(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['patient_name']); ?>')">
                                                <input type="radio" id="patient_<?php echo $patient['patient_id']; ?>" name="patient_id_radio" value="<?php echo $patient['patient_id']; ?>">
                                                <label for="patient_<?php echo $patient['patient_id']; ?>" class="flex-1 cursor-pointer text-sm"><?php echo htmlspecialchars($patient['patient_name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-gray-500 py-8 text-sm">No patients found in database</div>
                                    <?php endif; ?>
                                </div>

                                <div id="selectedPatientDisplay" class="selected-display" style="display: none;">
                                    <div class="text-xs text-gray-500">Selected Patient:</div>
                                    <div class="text-blue-600 font-medium" id="selectedPatientName"></div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="button" onclick="showSection('appointment')"
                                    class="border border-gray-300 px-6 py-2 rounded-md font-medium hover:bg-gray-50 transition">Back</button>
                                <button type="button" onclick="showSection('doctor')"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md font-medium hover:bg-blue-700 transition">Next:
                                    Doctor Selection</button>
                            </div>
                        </div>

                        <!-- Section 2: Doctor Selection -->
                        <div id="section-doctor" class="form-section">
                            <h2 class="text-lg font-semibold mb-6">Select Doctor</h2>
                            
                            <div class="space-y-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">
                                    <strong>Department:</strong> <span id="departmentDisplay">Select a department first</span>
                                    <div class="text-xs text-blue-500 mt-1">Doctors filtered by selected department</div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="doctorSearch">Search Doctor</label>
                                    <div class="relative">
                                        <input id="doctorSearch" type="text" 
                                            placeholder="Type to search doctors..." 
                                            class="w-full h-10 px-3 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                            autocomplete="off"
                                            onkeyup="searchDoctors(this.value)">
                                        <div id="doctorSearchResults" class="search-results"></div>
                                    </div>
                                    <input type="hidden" id="doctor_name" name="doctor_name" value="">
                                    <div id="selectedDoctorDisplay" class="selected-display" style="display: none;">
                                        <div class="text-xs text-gray-500">Selected Doctor:</div>
                                        <div class="text-blue-600 font-medium" id="selectedDoctorName"></div>
                                        <div class="text-xs text-gray-400" id="selectedDoctorDept"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <button type="button" onclick="showSection('patient')"
                                    class="border border-gray-300 px-6 py-2 rounded-md font-medium hover:bg-gray-50 transition">Back</button>
                                <button type="submit"
                                    class="bg-blue-600 text-white px-8 py-2 rounded-md font-semibold hover:bg-blue-700 shadow-md transition">
                                    <i data-lucide="calendar-plus" class="inline w-4 h-4 mr-2"></i>
                                    Schedule Appointment
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Store all data
        const allPatients = <?php echo json_encode($patients); ?>;
        const allDoctors = <?php echo json_encode($doctors); ?>;

        let selectedPatientId = null;
        let selectedPatientName = null;
        let selectedDoctorName = null;
        let selectedDoctorDepartment = null;

        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });

            // Show target section
            document.getElementById('section-' + sectionId).classList.add('active');

            const tabs = ['appointment', 'patient', 'doctor'];
            tabs.forEach(tab => {
                const btn = document.getElementById('btn-' + tab);
                if (tab === sectionId) {
                    btn.classList.add('step-active');
                    btn.classList.remove('step-inactive');
                } else {
                    btn.classList.remove('step-active');
                    btn.classList.add('step-inactive');
                }
            });

            // Update summary when going to appointment section
            if (sectionId === 'appointment') {
                updateSummary();
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Filter patients by search
       function filterPatients() {
    const searchValue = document.getElementById('patientSearch').value.toLowerCase().trim();
    const patientList = document.getElementById('patientListContainer');
    const patientItems = document.querySelectorAll('#patientListContainer .list-item');

    
    if (searchValue.length < 0) {
        patientList.style.display = "none";
        return;
    }

    patientList.style.display = "block";

    let found = false;

    patientItems.forEach(item => {
        const label = item.querySelector('label').textContent.toLowerCase();

        if (label.includes(searchValue)) {
            item.style.display = "flex";
            found = true;
        } else {
            item.style.display = "none";
        }
    });

    if (!found) {
        patientList.style.display = "none";
    }
}
        // Select patient
        function selectPatient(patientId, patientName) {
            selectedPatientId = patientId;
            selectedPatientName = patientName;
            
            document.getElementById('patient_' + patientId).checked = true;
            document.getElementById('selectedPatientName').textContent = patientName;
            document.getElementById('selectedPatientDisplay').style.display = 'block';
            document.getElementById('patient_id').value = patientId;
            document.getElementById('patientSearch').value = patientName;
document.getElementById('patientListContainer').style.display = "none";

            // Update list item styling
            document.querySelectorAll('#patientListContainer .list-item').forEach(item => {
                item.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Update summary
            updateSummary();
        }

        // Search doctors with autocomplete
        function searchDoctors(query) {
            const resultsDiv = document.getElementById('doctorSearchResults');
            const department = document.getElementById('department').value;
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                return;
            }

            // Filter doctors based on query and department
            const filtered = allDoctors.filter(doctor => {
                const nameMatch = doctor.doctor_name.toLowerCase().includes(query.toLowerCase());
                const deptMatch = department === '' || doctor.department === department;
                return nameMatch && deptMatch;
            });

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="result-item text-gray-500">No doctors found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(doctor => {
                html += `
                    <div class="result-item" onclick="selectDoctor('${doctor.doctor_name}', '${doctor.department}')">
                        <div class="name">${doctor.doctor_name}</div>
                        <div class="info">${doctor.department}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        // Select doctor from search results
        function selectDoctor(doctorName, department) {
            selectedDoctorName = doctorName;
            selectedDoctorDepartment = department;
            
            document.getElementById('doctorSearch').value = doctorName;
            document.getElementById('doctor_name').value = doctorName;
            document.getElementById('selectedDoctorName').textContent = doctorName;
            document.getElementById('selectedDoctorDept').textContent = 'Department: ' + department;
            document.getElementById('selectedDoctorDisplay').style.display = 'block';
            document.getElementById('doctorSearchResults').style.display = 'none';

            // Update summary
            updateSummary();
        }

        // Filter doctors by department (for the doctor list view)
        function filterDoctorsByDepartment() {
            const department = document.getElementById('department').value;
            document.getElementById('departmentDisplay').textContent = department || 'Select a department first';
            
            // Reset doctor selection
            document.getElementById('doctorSearch').value = '';
            document.getElementById('doctor_name').value = '';
            document.getElementById('selectedDoctorDisplay').style.display = 'none';
            document.getElementById('doctorSearchResults').style.display = 'none';
            
            selectedDoctorName = null;
            selectedDoctorDepartment = null;
            
            // Show/hide doctors in the list
            const doctorItems = document.querySelectorAll('#doctorListContainer .doctor-item');
            if (department === '') {
                doctorItems.forEach(item => {
                    item.style.display = 'none';
                    item.classList.remove('visible');
                });
                return;
            }

            doctorItems.forEach(item => {
                if (item.dataset.department === department) {
                    item.style.display = 'flex';
                    item.classList.add('visible');
                } else {
                    item.style.display = 'none';
                    item.classList.remove('visible');
                }
            });
            
            updateSummary();
        }

        // Update summary on appointment section
        function updateSummary() {
            const patientSummary = document.getElementById('selectedPatientSummary');
            const doctorSummary = document.getElementById('selectedDoctorSummary');

            if (selectedPatientId) {
                patientSummary.style.display = 'block';
                document.getElementById('selectedPatientSummaryName').textContent = selectedPatientName;
                document.getElementById('selectedPatientSummaryId').textContent = 'ID: ' + selectedPatientId;
            } else {
                patientSummary.style.display = 'none';
            }

            if (selectedDoctorName) {
                doctorSummary.style.display = 'block';
                document.getElementById('selectedDoctorSummaryName').textContent = selectedDoctorName;
                document.getElementById('selectedDoctorSummaryDepartment').textContent = 'Department: ' + selectedDoctorDepartment;
            } else {
                doctorSummary.style.display = 'none';
            }
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            const searchInput = document.getElementById('doctorSearch');
            const resultsDiv = document.getElementById('doctorSearchResults');
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedDepartment = document.getElementById('department').value;
            if (savedDepartment) {
                filterDoctorsByDepartment();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>