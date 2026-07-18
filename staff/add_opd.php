<?php 
session_start(); 
include "../config/db.php";

$message = "";
$messageType = "";
$opdNo = "";

// Fetch patients for dropdown
$patientQuery = "SELECT patient_id, patient_name FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY patient_name ASC";
$patientResult = $conn->query($patientQuery);
$patients = array();
if ($patientResult && $patientResult->num_rows > 0) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch doctors for dropdown
$doctorQuery = "SELECT doctor_id, doctor_name, department FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY doctor_name ASC";
$doctorResult = $conn->query($doctorQuery);
$doctors = array();
if ($doctorResult && $doctorResult->num_rows > 0) {
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
$opdNo = $row['opd_no'] ?? "";
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
    $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
    $doctor_note = mysqli_real_escape_string($conn, $_POST['doctor_note']);

    // Validation
    if (empty($opd_no) || empty($patient_id) || empty($doctor_id) || empty($visit_date)) {
        $message = "Please fill in all required fields!";
        $messageType = "error";
    } else {
        $insertQuery = "INSERT INTO opd (
            opd_no, patient_id, doctor_id, visit_date, symptoms, diagnosis, 
            bp, pulse, weight, temperature, doctor_note, delete_flag
        ) VALUES (
            '$opd_no', '$patient_id', '$doctor_id', '$visit_date', '$symptoms', '$diagnosis',
            '$bp', '$pulse', '$weight', '$temperature', '$doctor_note', 0
        )";

        if ($conn->query($insertQuery) === TRUE) {
            $message = "OPD record added successfully!";
            $messageType = "success";
            $_POST = array();
            echo "<script>
                alert('OPD record added successfully!');
                window.location='../staff/add_opd.php';
            </script>";
            exit();
        } else {
            $message = "Error adding OPD record: " . $conn->error;
            $messageType = "error";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Add OPD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .sidebar-active {
            background-color: #f3f4f6;
            color: #111827;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px 28px;
            min-height: 100vh;
        }
        .form-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .form-card .header {
            padding: 20px 28px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .form-card .header .header-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
        }
        .form-card .header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }
        .form-card .header .subtitle {
            font-size: 13px;
            color: #64748b;
            font-weight: 400;
        }
        .form-card .body {
            padding: 28px 32px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 5px;
        }
        .form-group label .required {
            color: #ef4444;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            outline: none;
            background: white;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 70px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-row-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 18px;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-primary {
            padding: 10px 28px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .btn-secondary {
            padding: 10px 24px;
            background: #f1f5f9;
            color: #475569;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        .btn-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1.5px solid #e5e7eb;
            margin-top: 8px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
            margin-bottom: 16px;
        }
        .back-link:hover {
            color: #0f172a;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-right: 40px;
        }
        .search-box .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .search-results {
            position: absolute;
            z-index: 50;
            width: 100%;
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            max-height: 220px;
            overflow-y: auto;
            display: none;
            margin-top: 4px;
        }
        .search-results .result-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .search-results .result-item:hover {
            background: #f1f5f9;
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
            border: 1.5px solid #bbf7d0;
            border-radius: 10px;
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
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
            .form-row-4 {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 768px) {
            .form-card .body { padding: 16px; }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .form-row-4 {
                grid-template-columns: 1fr 1fr;
            }
            .btn-actions {
                flex-direction: column;
            }
            .btn-actions a, .btn-actions button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff/staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../staff/staff_sidebar.php'; ?>
            <main class="main-content">
                <div class="max-w-4xl mx-auto w-full">
                    <!-- Back Link -->
                    <a href="opd_list.php" class="back-link">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Back to OPD List
                    </a>

                    <!-- Form Card -->
                    <div class="form-card fade-in">
                        <div class="header">
                            <div class="header-icon">
                                <i data-lucide="file-plus" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3>Add New OPD</h3>
                                <div class="subtitle">Register a new Outpatient Department visit</div>
                            </div>
                        </div>

                        <div class="body">
                            <?php if (!empty($message)): ?>
                                <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                                    <span><?php echo $message; ?></span>
                                </div>
                            <?php endif; ?>

                            <form action="add_opd.php" method="POST">
                                <input type="hidden" id="patient_id" name="patient_id" value="">
                                <input type="hidden" id="doctor_id" name="doctor_id" value="">

                                <!-- OPD Number and Visit Date -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="opd_no">OPD Number</label>
                                        <input type="text" id="opd_no" name="opd_no" placeholder = "Enter OPD Number" value="<?php echo $opdNo; ?>" >
                                    </div>
                                    <div class="form-group">
                                        <label for="visit_date">Visit Date <span class="required">*</span></label>
                                        <input type="date" id="visit_date" name="visit_date"  value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>

                                <!-- Patient Search -->
                                <div class="form-group">
                                    <label for="patientSearch">Search Patient <span class="required">*</span></label>
                                    <div class="search-box">
                                        <input type="text" id="patientSearch" placeholder="Type patient name..." 
                                               onkeyup="searchPatients(this.value)" autocomplete="off">
                                        <span class="search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                                        <div id="patientResults" class="search-results"></div>
                                    </div>
                                    <div id="selectedPatient" class="selected-item" style="display: none;">
                                        <div class="label">Selected Patient</div>
                                        <div class="value" id="selectedPatientName"></div>
                                        <div class="sub" id="selectedPatientId"></div>
                                    </div>
                                </div>

                                <!-- Doctor Search -->
                                <div class="form-group">
                                    <label for="doctorSearch">Search Doctor <span class="required">*</span></label>
                                    <div class="search-box">
                                        <input type="text" id="doctorSearch" placeholder="Type doctor name..." 
                                               onkeyup="searchDoctors(this.value)" autocomplete="off">
                                        <span class="search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                                        <div id="doctorResults" class="search-results"></div>
                                    </div>
                                    <div id="selectedDoctor" class="selected-item" style="display: none;">
                                        <div class="label">Selected Doctor</div>
                                        <div class="value" id="selectedDoctorName"></div>
                                        <div class="sub" id="selectedDoctorDept"></div>
                                    </div>
                                </div>

                                <!-- Symptoms and Diagnosis -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="symptoms">Symptoms</label>
                                        <textarea id="symptoms" name="symptoms" rows="3" placeholder="Enter patient symptoms..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="diagnosis">Diagnosis</label>
                                        <textarea id="diagnosis" name="diagnosis" rows="3" placeholder="Enter diagnosis..."></textarea>
                                    </div>
                                </div>

                                <!-- Vital Signs -->
                                <h3 class="text-md font-semibold text-gray-700 mb-3 border-b pb-2">Vital Signs</h3>
                                
                                <div class="form-row-4">
                                    <div class="form-group">
                                        <label for="bp">Blood Pressure (BP)</label>
                                        <input type="text" id="bp" name="bp" placeholder="e.g., 120/80">
                                    </div>
                                    <div class="form-group">
                                        <label for="pulse">Pulse (bpm)</label>
                                        <input type="number" id="pulse" name="pulse" placeholder="e.g., 72">
                                    </div>
                                    <div class="form-group">
                                        <label for="weight">Weight (kg)</label>
                                        <input type="number" step="0.1" id="weight" name="weight" placeholder="e.g., 75.5">
                                    </div>
                                    <div class="form-group">
                                        <label for="temperature">Temperature (°F)</label>
                                        <input type="number" step="0.1" id="temperature" name="temperature" placeholder="e.g., 98.6">
                                    </div>
                                </div>

                                <!-- Doctor's Note -->
                                <div class="form-group">
                                    <label for="doctor_note">Doctor's Note</label>
                                    <textarea id="doctor_note" name="doctor_note" rows="3" placeholder="Enter doctor's notes..."></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="btn-actions">
                                    <button type="submit" name="submit" class="btn-primary">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Save OPD Visit
                                    </button>
                                    <button type="reset" class="btn-secondary">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        Reset
                                    </button>
                                    <a href="opd_list.php" class="btn-secondary">
                                        <i data-lucide="list" class="w-4 h-4"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Patient data
        const allPatients = <?php 
            $patientsData = array();
            $patientsResult = $conn->query("SELECT patient_id, patient_name, mobile FROM patients WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY patient_name ASC");
            while($row = $patientsResult->fetch_assoc()) {
                $patientsData[] = $row;
            }
            echo json_encode($patientsData);
        ?>;

        // Doctor data
        const allDoctors = <?php 
            $doctorsData = array();
            $doctorsResult = $conn->query("SELECT doctor_id, doctor_name, department FROM doctor WHERE (delete_flag=0 OR delete_flag IS NULL) ORDER BY doctor_name ASC");
            while($row = $doctorsResult->fetch_assoc()) {
                $doctorsData[] = $row;
            }
            echo json_encode($doctorsData);
        ?>;

        // Search Patients
        function searchPatients(query) {
            const resultsDiv = document.getElementById('patientResults');
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                return;
            }

            const filtered = allPatients.filter(patient => 
                patient.patient_name.toLowerCase().includes(query.toLowerCase())
            );

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="result-item"><div class="name text-gray-500">No patients found</div></div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(patient => {
                html += `
                    <div class="result-item" onclick="selectPatient(${patient.patient_id}, '${patient.patient_name}')">
                        <div class="name">${patient.patient_name}</div>
                        <div class="info">${patient.mobile || 'N/A'}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        // Select Patient
        function selectPatient(id, name) {
            document.getElementById('patient_id').value = id;
            document.getElementById('patientSearch').value = name;
            document.getElementById('selectedPatientName').textContent = name;
            document.getElementById('selectedPatientId').textContent = 'ID: ' + id;
            document.getElementById('selectedPatient').style.display = 'block';
            document.getElementById('patientResults').style.display = 'none';
        }

        // Search Doctors
        function searchDoctors(query) {
            const resultsDiv = document.getElementById('doctorResults');
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                return;
            }

            const filtered = allDoctors.filter(doctor => 
                doctor.doctor_name.toLowerCase().includes(query.toLowerCase())
            );

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="result-item"><div class="name text-gray-500">No doctors found</div></div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(doctor => {
                html += `
                    <div class="result-item" onclick="selectDoctor(${doctor.doctor_id}, '${doctor.doctor_name}', '${doctor.department}')">
                        <div class="name">${doctor.doctor_name}</div>
                        <div class="info">${doctor.department}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        // Select Doctor
        function selectDoctor(id, name, department) {
            document.getElementById('doctor_id').value = id;
            document.getElementById('doctorSearch').value = name;
            document.getElementById('selectedDoctorName').textContent = name;
            document.getElementById('selectedDoctorDept').textContent = 'Department: ' + department;
            document.getElementById('selectedDoctor').style.display = 'block';
            document.getElementById('doctorResults').style.display = 'none';
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            const patientSearch = document.getElementById('patientSearch');
            const patientResults = document.getElementById('patientResults');
            const doctorSearch = document.getElementById('doctorSearch');
            const doctorResults = document.getElementById('doctorResults');
            
            if (!patientSearch.contains(e.target) && !patientResults.contains(e.target)) {
                patientResults.style.display = 'none';
            }
            if (!doctorSearch.contains(e.target) && !doctorResults.contains(e.target)) {
                doctorResults.style.display = 'none';
            }
        });
        localStorage.clear();
    </script>
</body>
</html>
<?php $conn->close(); ?>