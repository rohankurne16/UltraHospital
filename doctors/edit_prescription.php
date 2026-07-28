<?php 
session_start(); 
include "../config/hospital.php";

$doctor_register_id = $_SESSION['id'];

$sql = "SELECT * FROM doctor WHERE register_id='$doctor_register_id'";
$result = $conn->query($sql);

if($result && $result->num_rows > 0){

    $doctor = $result->fetch_assoc();

    $doctor_name = $doctor['doctor_name'];
    $doctor_id = $doctor['doctor_id'];

}

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: prescriptions.php");
    exit();
}

$id = mysqli_real_escape_string($conn,$_GET['id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {

    $patient_id = mysqli_real_escape_string($conn,$_POST['patient_id']);
    $complaint = mysqli_real_escape_string($conn,$_POST['complaint']);
    $followup_date = mysqli_real_escape_string($conn,$_POST['followup_date']);

    $medicine_names = $_POST['medicine_name'];
    $dosages = $_POST['dosage'];
    $frequencies = $_POST['frequency'];
    $days_array = $_POST['days'];
    $timings = $_POST['timing'];
    $advices = $_POST['advice'];

    mysqli_begin_transaction($conn);

    try{

        $updateMaster="
        UPDATE prescription_master
        SET
            patient_id='$patient_id',
            complaint='$complaint',
            followup_date='$followup_date',
            modified_at=NOW()
        WHERE prescription_id='$id'
        ";

        if(!$conn->query($updateMaster)){
            throw new Exception($conn->error);
        }

        $delete="
        DELETE FROM prescription_details
        WHERE prescription_id='$id'
        ";

        if(!$conn->query($delete)){
            throw new Exception($conn->error);
        }

        for($i=0;$i<count($medicine_names);$i++){

            if(trim($medicine_names[$i])==""){
                continue;
            }

            $medicine=mysqli_real_escape_string($conn,$medicine_names[$i]);
            $dosage=mysqli_real_escape_string($conn,$dosages[$i]);
            $frequency=mysqli_real_escape_string($conn,$frequencies[$i]);
            $days=mysqli_real_escape_string($conn,$days_array[$i]);
            $timing=mysqli_real_escape_string($conn,$timings[$i]);
            $advice=mysqli_real_escape_string($conn,$advices[$i]);

            $insert="
            INSERT INTO prescription_details
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
                '$id',
                '$medicine',
                '$dosage',
                '$frequency',
                '$days',
                '$timing',
                '$advice'
            )
            ";

            if(!$conn->query($insert)){
                throw new Exception($conn->error);
            }

        }

        mysqli_commit($conn);

        echo "<script>
        alert('Prescription Updated Successfully');
        window.location='prescriptions.php';
        </script>";

        exit();

    }

    catch(Exception $e){

        mysqli_rollback($conn);

        $error=$e->getMessage();

    }

}

$masterQuery="
SELECT *
FROM prescription_master
WHERE prescription_id='$id'
";

$masterResult=$conn->query($masterQuery);

if($masterResult->num_rows==0){

    header("Location: prescriptions.php");
    exit();

}

$data=$masterResult->fetch_assoc();

$medicineQuery="
SELECT *
FROM prescription_details
WHERE prescription_id='$id'
ORDER BY detail_id ASC
";

$medicineResult=$conn->query($medicineQuery);

$medicines=[];

while($row=$medicineResult->fetch_assoc()){

    $medicines[]=$row;

}

$patients_result=$conn->query("
SELECT patient_id,patient_name
FROM patients
WHERE delete_flag=0
OR delete_flag IS NULL
");




$selected_patient_name='';

$patientQuery="
SELECT patient_name
FROM patients
WHERE patient_id='".$data['patient_id']."'
";

$patientResult=$conn->query($patientQuery);

if($patientResult && $patientResult->num_rows){

    $patientData=$patientResult->fetch_assoc();

    $selected_patient_name=$patientData['patient_name'];

}
$selected_patient_name = '';
if (!empty($data['patient_id'])) {
    $patientQuery = "SELECT patient_name FROM patients WHERE patient_id = '" . $data['patient_id'] . "'";
    $patientResult = $conn->query($patientQuery);
    if ($patientResult && $patientResult->num_rows > 0) {
        $patientData = $patientResult->fetch_assoc();
        $selected_patient_name = $patientData['patient_name'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['hospital_name'] ?> - Edit Prescription</title>
    
    <link rel="icon" type="image/png" href="../<?php echo $hospital['hospital_logo'] ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 16px; } }
        @media (max-width: 640px) { .btn-actions { flex-direction: column; } .btn-actions a, .btn-actions button { width: 100%; justify-content: center; } }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
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
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .back-btn i {
            font-size: 18px;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../Sidebar.php'; ?>
            <main class="main-content w-full">
                <div class="max-w-4xl mx-auto fade-in">
                    <div class="mb-8">
                        <div class="flex items-center gap-4">
                            <a href="prescriptions.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Edit Prescription</h1>
                                <p class="text-gray-500">Modify prescription details for #<?php echo $id; ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle w-5 h-5"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3><i class="fas fa-edit mr-2 text-blue-500"></i> Update Details</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Patient -->

    <div class="form-group">

        <label class="form-label">

            Patient <span class="required">*</span>

        </label>

        <input
            type="text"
            class="form-input"
            value="<?php echo htmlspecialchars($selected_patient_name); ?>"
            readonly>

        <input
            type="hidden"
            name="patient_id"
            value="<?php echo $data['patient_id']; ?>">

    </div>

    <!-- Doctor -->

    <div class="form-group">

        <label class="form-label">

            Doctor

        </label>

        <input
            type="text"
            class="form-input"
            value="<?php echo htmlspecialchars($doctor_name); ?>"
            readonly>

    </div>

    <!-- Complaint -->

    <div class="form-group md:col-span-2">

        <label class="form-label">

            Chief Complaint
            <span class="required">*</span>

        </label>

        <textarea
            name="complaint"
            rows="4"
            class="form-textarea"
            required><?php echo htmlspecialchars($data['complaint']); ?></textarea>

    </div>

    <!-- Followup -->

    <div class="form-group">

        <label class="form-label">

            Follow-up Date

        </label>

        <input
            type="date"
            name="followup_date"
            class="form-input"
            value="<?php echo $data['followup_date']; ?>">

    </div>

</div>

<hr class="my-6">

<h3 class="text-lg font-semibold mb-4">

Medicines

</h3>
<div class="overflow-x-auto">
<table class="min-w-full border border-gray-300" id="medicineTable">

    <thead class="bg-gray-100">
        <tr>
            <th class="border p-2">Medicine</th>
            <th class="border p-2">Dosage</th>
            <th class="border p-2">Frequency</th>
            <th class="border p-2">Days</th>
            <th class="border p-2">Timing</th>
            <th class="border p-2">Advice</th>
            <th class="border p-2">Action</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach($medicines as $medicine){ ?>

    <tr>

        <td class="border p-2">
            <input type="text"
                   name="medicine_name[]"
                   class="form-input"
                   value="<?php echo htmlspecialchars($medicine['medicine_name']); ?>"
                   required>
        </td>

        <td class="border p-2">
            <input type="text"
                   name="dosage[]"
                   class="form-input"
                   value="<?php echo htmlspecialchars($medicine['dosage']); ?>"
                   required>
        </td>

        <td class="border p-2">
            <input type="text"
                   name="frequency[]"
                   class="form-input"
                   value="<?php echo htmlspecialchars($medicine['frequency']); ?>"
                   required>
        </td>

        <td class="border p-2">
            <input type="number"
                   name="days[]"
                   class="form-input"
                   value="<?php echo htmlspecialchars($medicine['days']); ?>"
                   required>
        </td>

        <td class="border p-2">

            <select name="timing[]" class="form-input">

                <option value="Morning" <?=($medicine['timing']=="Morning")?'selected':'';?>>
                    Morning
                </option>

                <option value="Afternoon" <?=($medicine['timing']=="Afternoon")?'selected':'';?>>
                    Afternoon
                </option>

                <option value="Evening" <?=($medicine['timing']=="Evening")?'selected':'';?>>
                    Evening
                </option>

                <option value="Night" <?=($medicine['timing']=="Night")?'selected':'';?>>
                    Night
                </option>

                <option value="M-A-N" <?=($medicine['timing']=="M-A-N")?'selected':'';?>>
                    M-A-N
                </option>

                <option value="M-N" <?=($medicine['timing']=="M-N")?'selected':'';?>>
                    M-N
                </option>

            </select>

        </td>

        <td class="border p-2">
            <input type="text"
                   name="advice[]"
                   class="form-input"
                   value="<?php echo htmlspecialchars($medicine['advice']); ?>">
        </td>

        <td class="border p-2 text-center">

            <button
                type="button"
                onclick="removeRow(this)"
                class="bg-red-500 text-white px-3 py-1 rounded">

                Delete

            </button>

        </td>

    </tr>

    <?php } ?>

    </tbody>

</table>
</div>

<div class="mt-4">

    <button
        type="button"
        onclick="addRow()"
        class="bg-green-600 text-white px-4 py-2 rounded">

        <i class="fas fa-plus"></i>

        Add Medicine

    </button>

</div>

<div class="mt-8 flex justify-end gap-3">

    <a href="prescriptions.php"
       class="px-5 py-2 border rounded">

        Cancel

    </a>

    <button
        type="submit"
        name="submit"
        class="bg-blue-600 text-white px-6 py-2 rounded">

        Update Prescription

    </button>

</div>

</form>

    <script>

function addRow() {

    let tbody = document.querySelector("#medicineTable tbody");

    let row = `
    <tr>

        <td class="border p-2">
            <input type="text"
                   name="medicine_name[]"
                   class="form-input"
                   required>
        </td>

        <td class="border p-2">
            <input type="text"
                   name="dosage[]"
                   class="form-input"
                   required>
        </td>

        <td class="border p-2">
            <input type="text"
                   name="frequency[]"
                   class="form-input"
                   required>
        </td>

        <td class="border p-2">
            <input type="number"
                   name="days[]"
                   class="form-input"
                   required>
        </td>

        <td class="border p-2">

            <select
                name="timing[]"
                class="form-input">

                <option value="Morning">Morning</option>
                <option value="Afternoon">Afternoon</option>
                <option value="Evening">Evening</option>
                <option value="Night">Night</option>
                <option value="M-A-N">M-A-N</option>
                <option value="M-N">M-N</option>

            </select>

        </td>

        <td class="border p-2">

            <input
                type="text"
                name="advice[]"
                class="form-input">

        </td>

        <td class="border p-2 text-center">

            <button
                type="button"
                onclick="removeRow(this)"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">

                Delete

            </button>

        </td>

    </tr>
    `;

    tbody.insertAdjacentHTML("beforeend", row);
}

function removeRow(button) {

    let tbody = document.querySelector("#medicineTable tbody");

    if (tbody.rows.length == 1) {

        alert("At least one medicine is required.");
        return;

    }

    button.closest("tr").remove();
}

</script>
</body>
</html>