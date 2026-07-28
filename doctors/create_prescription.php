<?php 
session_start(); 
include "../config/hospital.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}
$doctor_register_id = $_SESSION["id"];

$hid=$_SESSION["hospital_id"];

$sql = "select * from doctor where register_id='$doctor_register_id' and hospital_id='$hid'";
$all_doctor_info = $conn->query($sql);

if ($all_doctor_info && $all_doctor_info->num_rows > 0) {
    $doctor = $all_doctor_info->fetch_assoc();
    $doctor_name = $doctor["doctor_name"];
    $doctor_id = $doctor["doctor_id"];

    $selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    $selected_patient_name = '';

    if ($selected_patient_id > 0) {
        $patientQuery = "SELECT patient_name FROM patients WHERE patient_id = '$selected_patient_id' and doctor_id='$doctor_id' and hospital_id='$hid'";
        $patientResult = $conn->query($patientQuery);
        if ($patientResult && $patientResult->num_rows > 0) {
            $patientData = $patientResult->fetch_assoc();
            $selected_patient_name = $patientData['patient_name'];
        }
    }

    $patients = [];

if ($selected_patient_id == 0) {

    $patientSql = "SELECT patient_id, patient_name
                   FROM patients
                   WHERE (delete_flag = 0 OR delete_flag IS NULL)
                   and doctor_id='$doctor_id' and hospital_id='$hid'
                   ORDER BY patient_name";

    $patientResult = $conn->query($patientSql);

    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $followup_date = mysqli_real_escape_string($conn, $_POST['followup_date']);
        
        // Get arrays of medicines
        $medicine_names = isset($_POST['medicine_name']) ? $_POST['medicine_name'] : [];
        $dosages = isset($_POST['dosage']) ? $_POST['dosage'] : [];
        $frequencies = isset($_POST['frequency']) ? $_POST['frequency'] : [];
        $days_array = isset($_POST['days']) ? $_POST['days'] : [];
        $complaint = mysqli_real_escape_string($conn, $_POST['complaint']);

        $timings = isset($_POST['timing']) ? $_POST['timing'] : [];
        $advices = isset($_POST['advice']) ? $_POST['advice'] : [];

        $all_inserted = true;
        $error_msg = "";

        $insertMaster = "insert into prescription_master
(
    patient_id,
    doctor_id,
    hospital_id,
    followup_date,
    created_at,
    delete_flag,
    complaint
)
values
(
    '$patient_id',
    '$doctor_id',
    '$hid',
    '$followup_date',
    now(),
    0,
    '$complaint'
)";

if(!$conn->query($insertMaster)){
    die($conn->error);
}

$prescription_id = $conn->insert_id;
        // Insert each medicine as a separate prescription record
        for ($i = 0; $i < count($medicine_names); $i++) {
            if (!empty($medicine_names[$i])) {
                $medicine_name = mysqli_real_escape_string($conn, $medicine_names[$i]);
                $dosage = mysqli_real_escape_string($conn, $dosages[$i] ?? '');
                $frequency = mysqli_real_escape_string($conn, $frequencies[$i] ?? '');
                $days = mysqli_real_escape_string($conn, $days_array[$i] ?? '');
                $timing = mysqli_real_escape_string($conn, $timings[$i] ?? '');
                $advice = mysqli_real_escape_string($conn, $advices[$i] ?? '');

              $insertQuery = "INSERT INTO prescription_details
(
    prescription_id,
    medicine_name,
    dosage,
    frequency,
    days,
    timing,
    advice
)
VALUES
(
    '$prescription_id',
    '$medicine_name',
    '$dosage',
    '$frequency',
    '$days',
    '$timing',
    '$advice'
)";
                if (!$conn->query($insertQuery)) {
                    $all_inserted = false;
                    $error_msg = "Error inserting medicine: " . $conn->error;
                    break;
                }
            }
        }

        if ($all_inserted && count($medicine_names) > 0) {
            if (!empty($followup_date)) {

    // Generate Appointment Number
    $appointment_no = "APP-" . date("YmdHis") . "-" . substr(md5(uniqid()), 0, 6);

    // Get department from doctor table
    $deptQuery = $conn->query("SELECT department FROM doctor WHERE doctor_id='$doctor_id'");
    $dept = "";
    if ($deptQuery && $deptQuery->num_rows > 0) {
        $dept = $deptQuery->fetch_assoc()['department'];
    }

    $reason = "Follow-up Visit";

    $insertAppointment = "INSERT INTO appointments
    (
        appointment_no,
        patient_id,
        doctor_id,
        department,
        appointment_type,
        opd_ipd_type,
        appointment_date,
        appointment_time,
        duration,
        reason,
        status,
        notes,
        created_at,
        delete_flag,
        hospital_id
    )
    VALUES
    (
        '$appointment_no',
        '$patient_id',
        '$doctor_id',
        '$dept',
        'Follow-up',
        'OPD',
        '$followup_date',
        '10:00:00',
        '15',
        '$reason',
        'Scheduled',
        'Auto Follow-up Appointment',
        NOW(),
        0,
        '$hid'
    )";

 if (!$conn->query($insertAppointment)) {

    die("Appointment Insert Error: " . $conn->error);
} else {
    echo "Appointment Inserted Successfully";
}   
}
            echo "<script>
                alert('Prescriptions created successfully!');
                window.location.href='prescriptions.php';
            </script>";
            exit();
        } else {
            $error = $error_msg ?: "Please add at least one medicine.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Create Prescription</title>
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .card-header h3 { font-size: 18px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #475569; margin-bottom: 6px; }
        .form-label .required { color: #ef4444; }
        .form-input { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-input:disabled { background: #f1f5f9; cursor: not-allowed; }
        .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s; min-height: 80px; resize: vertical; }
        .form-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-primary { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
        .btn-secondary { padding: 10px 24px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-danger { padding: 6px 10px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-add-row { padding: 6px 10px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; }
        .btn-add-row:hover { background: #059669; }
        .medicine-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .medicine-table thead { background: #f3f4f6; }
        .medicine-table th { padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; font-size: 13px; }
        .medicine-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .medicine-table tbody tr:hover { background: #f9fafb; }
        .medicine-table input, .medicine-table select { width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
        .medicine-table input:focus, .medicine-table select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
        .action-buttons { display: flex; gap: 6px; align-items: center; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 640px) { .btn-actions { flex-direction: column; } .btn-actions a, .btn-actions button { width: 100%; justify-content: center; } }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .medicine-row { animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content w-full">
                <div class="max-w-5xl mx-auto fade-in">
                    <div class="mb-8">
                        <div class="flex items-center gap-4">
                            <a href="prescriptions.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Create New Prescription</h1>
                                <p class="text-gray-500">Enter medication details for the patient. Add multiple medicines below.</p>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3>Prescription Details</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" id="prescriptionForm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">Chief Complaint <span class="required">*</span></label>
                                        <textarea name="complaint"
                                                  class="form-textarea"
                                                  placeholder="Enter patient's complaint..."
                                                  required></textarea>
                                    </div>
                                    <?php if ($selected_patient_id > 0) { ?>

<div class="form-group">
    <label class="form-label">
        Patient <span class="required">*</span>
    </label>

    <input type="text"
           class="form-input"
           value="<?php echo htmlspecialchars($selected_patient_name); ?>"
           readonly>

    <input type="hidden"
           name="patient_id"
           value="<?php echo $selected_patient_id; ?>">
</div>

<?php } else { ?>

<div class="form-group">
    <label class="form-label">
        Search Patient <span class="required">*</span>
    </label>

    <input
        type="text"
        id="patientSearch"
        class="form-input"
        placeholder="Search patient...">

    <input
        type="hidden"
        name="patient_id"
        id="patient_id"
        required>

    <div
        id="patientList"
        class="hidden mt-2 border rounded-lg bg-white shadow max-h-56 overflow-y-auto">
    </div>

</div>

<?php } ?>

                                    <div class="form-group">
                                        <label class="form-label">Doctor <span class="required">*</span></label>
                                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($doctor_name); ?>" disabled>
                                        <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Follow-up Date</label>
                                        <input type="date" name="followup_date" class="form-input">
                                    </div>
                                </div>
                                

                                <!-- Medicines Table -->
                                <div class="mb-8">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Medicines</h3>

                                    <div class="overflow-x-auto border rounded-lg">
                                        <table class="medicine-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 18%;">Medicine Name <span class="text-red-500">*</span></th>
                                                    <th style="width: 11%;">Dosage</th>
                                                    <th style="width: 13%;">Frequency</th>
                                                    <th style="width: 9%;">Days</th>
                                                    <th style="width: 11%;">Timing</th>
                                                    <th style="width: 23%;">Instructions</th>
                                                    <th style="width: 15%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="medicinesTableBody">
                                                <!-- Rows will be added here dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mt-8 flex items-center justify-end gap-4">
                                    <a href="prescription_list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-all">Cancel</a>
                                    <button type="submit" name="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm">
                                        <i data-lucide="check" class="w-4 h-4 inline mr-2"></i>
                                        Save Prescription
                                    </button>
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

<?php if($selected_patient_id == 0){ ?>

const patients = <?php echo json_encode($patients); ?>;

const search = document.getElementById("patientSearch");
const list = document.getElementById("patientList");
const hidden = document.getElementById("patient_id");

search.addEventListener("keyup", function () {

    let value = this.value.toLowerCase();

    list.innerHTML = "";

    if(value==""){
        list.classList.add("hidden");
        return;
    }

    let result = patients.filter(function(patient){

        return patient.patient_name
        .toLowerCase()
        .includes(value);

    });

    result.forEach(function(patient){

        let div = document.createElement("div");

        div.className =
        "px-3 py-2 hover:bg-blue-100 cursor-pointer";

        div.innerHTML = patient.patient_name;

        div.onclick = function(){

            search.value = patient.patient_name;

            hidden.value = patient.patient_id;

            list.classList.add("hidden");

        };

        list.appendChild(div);

    });

    if(result.length>0)
        list.classList.remove("hidden");
    else
        list.classList.add("hidden");

});

document.addEventListener("click",function(e){

    if(!search.contains(e.target) && !list.contains(e.target))
        list.classList.add("hidden");

});

<?php } ?>

// Medicine table functionality
let rowCount = 0;

function createMedicineRow() {
    rowCount++;
    const row = document.createElement('tr');
    row.className = 'medicine-row';
    row.innerHTML = `
        <td>
            <input type="text" name="medicine_name[]" placeholder="e.g. Paracetamol" required>
        </td>
        <td>
            <input type="text" name="dosage[]" placeholder="e.g. 500mg">
        </td>
        <td>
            <input type="text" name="frequency[]" placeholder="e.g. Twice a day">
        </td>
        <td>
            <input type="number" name="days[]" placeholder="e.g. 7" min="1">
        </td>
        <td>
            <select name="timing[]">
                <option value="">Select</option>
                <option value="Morning">Morning</option>
                <option value="Afternoon">Afternoon</option>
                <option value="Evening">Evening</option>
                <option value="Night">Night</option>
                <option value="M-A-N">M-A-N</option>
                <option value="M-N">M-N</option>
            </select>
        </td>
        <td>
            <input type="text" name="advice[]" placeholder="e.g. Take after meal">
        </td>
        <td>
            <div class="action-buttons">
                <button type="button" class="btn-add-row add-row-btn" title="Add new row">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                </button>
                <button type="button" class="btn-danger remove-row-btn" title="Delete row">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </td>
    `;
    
    // Add row button functionality
    row.querySelector('.add-row-btn').addEventListener('click', function() {
        createMedicineRow();
        lucide.createIcons();
    });
    
    // Remove row button functionality
    row.querySelector('.remove-row-btn').addEventListener('click', function() {
        row.remove();
        lucide.createIcons();
    });
    
    document.getElementById('medicinesTableBody').appendChild(row);
    lucide.createIcons();
}

// Add initial empty row
createMedicineRow();

// Form validation
document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#medicinesTableBody tr');
    if (rows.length === 0) {
        e.preventDefault();
        alert('Please add at least one medicine.');
        return false;
    }
    
    let hasValidMedicine = false;
    rows.forEach(row => {
        const medicineName = row.querySelector('input[name="medicine_name[]"]').value.trim();
        if (medicineName) {
            hasValidMedicine = true;
        }
    });
    
    if (!hasValidMedicine) {
        e.preventDefault();
        alert('Please enter at least one medicine name.');
        return false;
    }
});

</script>
</body>
</html>
<?php } ?>
