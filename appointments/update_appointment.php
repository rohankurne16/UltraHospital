<?php
session_start();
include "../config/db.php";

$appointment = null;
$message = '';
$error = '';

if (isset($_GET["appointment_id"])) {
    $id = mysqli_real_escape_string($conn, $_GET["appointment_id"]);
   $sql = "SELECT
            a.*,
            p.patient_name,
            d.doctor_name,
            d.department
        FROM appointments a
        LEFT JOIN patients p
            ON a.patient_id = p.patient_id
        LEFT JOIN doctor d
            ON a.doctor_id = d.doctor_id
        WHERE a.appointment_id='$id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
    } else {
        $error = "Appointment not found.";
    }
}

// Fetch all patients for the dropdown
$patientQuery = "SELECT patient_id, patient_name FROM patients ORDER BY patient_name ASC";
$patientResult = $conn->query($patientQuery);
$patients = array();
if ($patientResult && $patientResult->num_rows > 0) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch all doctors for the dropdown
$doctorQuery = "SELECT doctor_id, doctor_name, department FROM doctor ORDER BY doctor_name ASC";
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_id"])) {
    $id = mysqli_real_escape_string($conn, $_POST["appointment_id"]);
    $patient_id =  $_POST["patient_id"] ?? '';
    $doctor_id = $_POST["doctor_id"] ?? '';
    $appointment_type = mysqli_real_escape_string($conn, $_POST["appointment_type"]);
    $appointment_date = mysqli_real_escape_string($conn, $_POST["appointment_date"]);
    $appointment_time = mysqli_real_escape_string($conn, $_POST["appointment_time"]);
    $duration = mysqli_real_escape_string($conn, $_POST["duration"]);
    $reason = mysqli_real_escape_string($conn, $_POST["reason"]);
    $notes = mysqli_real_escape_string($conn, $_POST["notes"]);
    $status = mysqli_real_escape_string($conn, $_POST["status"]);

    $patient_name = "";
    if (!empty($patient_id)) {
        $patientQuery = "SELECT patient_name FROM patients WHERE patient_id = '$patient_id'";
        $patientResult = $conn->query($patientQuery);
        if ($patientResult && $patientResult->num_rows > 0) {
            $patientRow = $patientResult->fetch_assoc();
            $patient_name = $patientRow['patient_name'];
        }
    }

    $doctor_name = "";
    $department  = "";

    if (!empty($doctor_id)) {

        $doctorQuery = "SELECT doctor_name, department
                        FROM doctor
                        WHERE doctor_id='$doctor_id'";

        $doctorResult = $conn->query($doctorQuery);

        if ($doctorResult && $doctorResult->num_rows > 0) {

            $doctor = $doctorResult->fetch_assoc();

            $doctor_name = $doctor['doctor_name'];
            $department  = $doctor['department'];
        }
    }

    $sql = "UPDATE appointments SET
patient_id='$patient_id',
doctor_id='$doctor_id',
appointment_type='$appointment_type',
appointment_date='$appointment_date',
appointment_time='$appointment_time',
duration='$duration',
reason='$reason',
notes='$notes',
status='$status'
WHERE appointment_id='$id'";


    if ($conn->query($sql) === TRUE) {
        $message = "Appointment updated successfully!";
       $sql = "SELECT
            a.*,
            p.patient_name,
            d.doctor_name,
            d.department
        FROM appointments a
        LEFT JOIN patients p
            ON a.patient_id = p.patient_id
        LEFT JOIN doctor d
            ON a.doctor_id = d.doctor_id
        WHERE a.appointment_id='$id'";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
}
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Appointment - MedixPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-scheduled {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-tentative {
            background: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        .doctor-search-results {
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
        .doctor-search-results .result-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        }
        .doctor-search-results .result-item:hover {
            background: #f3f4f6;
        }
        .doctor-search-results .result-item:last-child {
            border-bottom: none;
        }
        .doctor-search-results .result-item .doctor-name {
            font-weight: 500;
            font-size: 14px;
        }
        .doctor-search-results .result-item .doctor-dept {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php if(file_exists("../header.php")) { include "../header.php"; } ?>
        <div class="flex flex-1 items-start">
            <?php if(file_exists("../Sidebar.php")) { include "../Sidebar.php"; } ?>
            <main class="flex-1 xl:ml-64 p-4 md:p-8">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                        <div class="flex items-center gap-4">
                            <a href="../appointments.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Update Appointment</h1>
                                <p class="text-gray-500 text-sm">Edit the details for the appointment.</p>
                            </div>
                        </div>
                        <?php if ($appointment): ?>
                        <span class="status-badge <?php 
                            $status = strtolower($appointment['status'] ?? 'scheduled');
                            echo 'status-' . $status;
                        ?>">
                            <?php echo htmlspecialchars($appointment['status'] ?? 'Scheduled'); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-3 alert">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span class="font-medium"><?php echo $message; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 flex items-center gap-3 alert">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-medium"><?php echo $error; ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($appointment): ?>
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
                        
                        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h2 class="text-lg font-semibold">Appointment Details</h2>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Appointment No</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['appointment_no']); ?>" 
                                               class="w-full h-11 px-4 rounded-lg border border-gray-300 bg-gray-50 text-sm" disabled>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Appointment Type <span class="text-red-500">*</span></label>
                                        <select name="appointment_type" class="w-full h-11 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="Consultation" <?php echo ($appointment['appointment_type'] == 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                                            <option value="Follow-up" <?php echo ($appointment['appointment_type'] == 'Follow-up') ? 'selected' : ''; ?>>Follow-up</option>
                                            <option value="Procedure" <?php echo ($appointment['appointment_type'] == 'Procedure') ? 'selected' : ''; ?>>Procedure</option>
                                            <option value="Check-up" <?php echo ($appointment['appointment_type'] == 'Check-up') ? 'selected' : ''; ?>>Check-up</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Patient <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="text" id="patientSearch" 
                                                   placeholder="Search patient..." 
                                                   class="w-full h-11 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                   autocomplete="off"
                                                   onkeyup="searchPatients(this.value)"
                                                   value="<?php echo htmlspecialchars($appointment['patient_name']); ?>">
                                            <input type="hidden" id="patient_id" name="patient_id" value="<?php echo $appointment['patient_id'] ?? ''; ?>">
                                            <div id="patientSearchResults" class="doctor-search-results"></div>
                                        </div>
                                        <div id="selectedPatientDisplay" class="selected-display" style="display: <?php echo !empty($appointment['patient_id']) ? 'block' : 'none'; ?>;">
                                            <div class="text-xs text-gray-500">Selected Patient:</div>
                                            <div class="text-blue-600 font-medium" id="selectedPatientName"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                            <div class="text-xs text-gray-400" id="selectedPatientId">ID: <?php echo htmlspecialchars($appointment['patient_id'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Doctor <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="text" id="doctorSearch" 
                                                   placeholder="Search doctor..." 
                                                   class="w-full h-11 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                                   autocomplete="off"
                                                   onkeyup="searchDoctors(this.value)"
                                                   value="<?php echo htmlspecialchars($appointment['doctor_name']); ?>">
                                           <input type="hidden" id="doctor_id" name="doctor_id" value="<?php echo htmlspecialchars($appointment['doctor_id']); ?>">
                                            <div id="doctorSearchResults" class="doctor-search-results"></div>
                                        </div>
                                        <div id="selectedDoctorDisplay" class="selected-display" style="display: <?php echo !empty($appointment['doctor_name']) ? 'block' : 'none'; ?>;">
                                            <div class="text-xs text-gray-500">Selected Doctor:</div>
                                            <div class="text-blue-600 font-medium" id="selectedDoctorName"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                            <div class="text-xs text-gray-400" id="selectedDoctorDept">Department: <?php echo htmlspecialchars($appointment['department'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Date <span class="text-red-500">*</span></label>
                                        <input type="date" name="appointment_date" value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" 
                                               class="w-full h-11 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Time Slot <span class="text-red-500">*</span></label>
                                        <select name="appointment_time" class="w-full h-11 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                                            <option value="">Select time</option>
                                            <option value="09:00 AM" <?php echo ($appointment['appointment_time'] == '09:00 AM') ? 'selected' : ''; ?>>09:00 AM</option>
                                            <option value="09:30 AM" <?php echo ($appointment['appointment_time'] == '09:30 AM') ? 'selected' : ''; ?>>09:30 AM</option>
                                            <option value="10:00 AM" <?php echo ($appointment['appointment_time'] == '10:00 AM') ? 'selected' : ''; ?>>10:00 AM</option>
                                            <option value="10:30 AM" <?php echo ($appointment['appointment_time'] == '10:30 AM') ? 'selected' : ''; ?>>10:30 AM</option>
                                            <option value="11:00 AM" <?php echo ($appointment['appointment_time'] == '11:00 AM') ? 'selected' : ''; ?>>11:00 AM</option>
                                            <option value="11:30 AM" <?php echo ($appointment['appointment_time'] == '11:30 AM') ? 'selected' : ''; ?>>11:30 AM</option>
                                            <option value="02:00 PM" <?php echo ($appointment['appointment_time'] == '02:00 PM') ? 'selected' : ''; ?>>02:00 PM</option>
                                            <option value="02:30 PM" <?php echo ($appointment['appointment_time'] == '02:30 PM') ? 'selected' : ''; ?>>02:30 PM</option>
                                            <option value="03:00 PM" <?php echo ($appointment['appointment_time'] == '03:00 PM') ? 'selected' : ''; ?>>03:00 PM</option>
                                            <option value="03:30 PM" <?php echo ($appointment['appointment_time'] == '03:30 PM') ? 'selected' : ''; ?>>03:30 PM</option>
                                            <option value="04:00 PM" <?php echo ($appointment['appointment_time'] == '04:00 PM') ? 'selected' : ''; ?>>04:00 PM</option>
                                            <option value="04:30 PM" <?php echo ($appointment['appointment_time'] == '04:30 PM') ? 'selected' : ''; ?>>04:30 PM</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Duration (minutes) <span class="text-red-500">*</span></label>
                                        <select name="duration" class="w-full h-11 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                            <option value="15" <?php echo ($appointment['duration'] == '15') ? 'selected' : ''; ?>>15 minutes</option>
                                            <option value="30" <?php echo ($appointment['duration'] == '30') ? 'selected' : ''; ?>>30 minutes</option>
                                            <option value="45" <?php echo ($appointment['duration'] == '45') ? 'selected' : ''; ?>>45 minutes</option>
                                            <option value="60" <?php echo ($appointment['duration'] == '60') ? 'selected' : ''; ?>>60 minutes</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Department</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['department'] ?? 'N/A'); ?>" 
                                               class="w-full h-11 px-4 rounded-lg border border-gray-300 bg-gray-50 text-sm" disabled>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Reason for Visit</label>
                                    <textarea name="reason" rows="3" class="w-full p-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"><?php echo htmlspecialchars($appointment['reason']); ?></textarea>
                                </div>

                                <hr class="my-6 border-gray-200">

                                <div class="space-y-4">
                                    <h3 class="text-lg font-medium">Appointment Status</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all <?php echo ($appointment['status'] == 'Scheduled') ? 'border-blue-500 bg-blue-50' : ''; ?>">
                                            <input type="radio" name="status" value="Scheduled" <?php echo ($appointment['status'] == 'Scheduled') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600">
                                            <span class="text-sm font-medium">Scheduled</span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all <?php echo ($appointment['status'] == 'Tentative') ? 'border-blue-500 bg-blue-50' : ''; ?>">
                                            <input type="radio" name="status" value="Tentative" <?php echo ($appointment['status'] == 'Tentative') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600">
                                            <span class="text-sm font-medium">Tentative</span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all <?php echo ($appointment['status'] == 'Completed') ? 'border-blue-500 bg-blue-50' : ''; ?>">
                                            <input type="radio" name="status" value="Completed" <?php echo ($appointment['status'] == 'Completed') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600">
                                            <span class="text-sm font-medium">Completed</span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all <?php echo ($appointment['status'] == 'Cancelled') ? 'border-blue-500 bg-blue-50' : ''; ?>">
                                            <input type="radio" name="status" value="Cancelled" <?php echo ($appointment['status'] == 'Cancelled') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600">
                                            <span class="text-sm font-medium">Cancelled</span>
                                        </label>
                                    </div>
                                </div>

                                <hr class="my-6 border-gray-200">

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Additional Notes</label>
                                    <textarea name="notes" rows="2" class="w-full p-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                                </div>

                                <div class="flex justify-end gap-4 pt-4">
                                    <a href="../appointments.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all shadow-sm">Cancel</a>
                                    <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all shadow-md flex items-center gap-2">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Update Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-8 rounded-lg text-center">
                        <i data-lucide="alert-triangle" class="w-12 h-12 mx-auto text-red-400 mb-4"></i>
                        <h3 class="text-lg font-bold mb-2">Error Loading Appointment</h3>
                        <p><?php echo $error ? $error : "Please provide a valid appointment ID."; ?></p>
                        <a href="../appointments.php" class="inline-flex items-center justify-center mt-6 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-all">
                            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                            Return to List
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <?php $conn->close(); ?>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Store all data
        const allPatients = <?php echo json_encode($patients); ?>;
        const allDoctors = <?php echo json_encode($doctors); ?>;

        let selectedPatientId = <?php echo json_encode($appointment['patient_id'] ?? null); ?>;
        let selectedPatientName = <?php echo json_encode($appointment['patient_name'] ?? null); ?>;
        let selectedDoctorName = <?php echo json_encode($appointment['doctor_name'] ?? null); ?>;
        let selectedDoctorDept = <?php echo json_encode($appointment['department'] ?? null); ?>;

        // Search patients with autocomplete
        function searchPatients(query) {
            const resultsDiv = document.getElementById('patientSearchResults');
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                return;
            }

            const filtered = allPatients.filter(patient => 
                patient.patient_name.toLowerCase().includes(query.toLowerCase())
            );

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="result-item text-gray-500">No patients found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(patient => {
                html += `
                    <div class="result-item" onclick="selectPatient(${patient.patient_id}, '${patient.patient_name}')">
                        <div class="doctor-name">${patient.patient_name}</div>
                        <div class="doctor-dept">ID: ${patient.patient_id}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        // Select patient from search results
        function selectPatient(patientId, patientName) {
            selectedPatientId = patientId;
            selectedPatientName = patientName;
            
            document.getElementById('patientSearch').value = patientName;
            document.getElementById('patient_id').value = patientId;
            document.getElementById('selectedPatientName').textContent = patientName;
            document.getElementById('selectedPatientId').textContent = 'ID: ' + patientId;
            document.getElementById('selectedPatientDisplay').style.display = 'block';
            document.getElementById('patientSearchResults').style.display = 'none';
        }

        // Search doctors with autocomplete
        function searchDoctors(query) {
            const resultsDiv = document.getElementById('doctorSearchResults');
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                return;
            }

            const filtered = allDoctors.filter(doctor => 
                doctor.doctor_name.toLowerCase().includes(query.toLowerCase())
            );

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="result-item text-gray-500">No doctors found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(doctor => {
                html += `
                    <div class="result-item" onclick="selectDoctor(${doctor.doctor_id}, '${doctor.doctor_name}', '${doctor.department}')">
                        <div class="doctor-name">${doctor.doctor_name}</div>
                        <div class="doctor-dept">${doctor.department}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        function selectDoctor(doctorId, doctorName, department) {

    document.getElementById('doctorSearch').value = doctorName;

    document.getElementById('doctor_id').value = doctorId;

    document.getElementById('selectedDoctorName').textContent = doctorName;

    document.getElementById('selectedDoctorDept').textContent =
        'Department: ' + department;

    document.getElementById('selectedDoctorDisplay').style.display = 'block';

    document.getElementById('doctorSearchResults').style.display = 'none';
}

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            const patientSearch = document.getElementById('patientSearch');
            const patientResults = document.getElementById('patientSearchResults');
            const doctorSearch = document.getElementById('doctorSearch');
            const doctorResults = document.getElementById('doctorSearchResults');
            
            if (!patientSearch.contains(e.target) && !patientResults.contains(e.target)) {
                patientResults.style.display = 'none';
            }
            if (!doctorSearch.contains(e.target) && !doctorResults.contains(e.target)) {
                doctorResults.style.display = 'none';
            }
        });
    </script>
</body>
</html>