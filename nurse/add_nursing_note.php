<?php

session_start();

include "../config/hospital.php";
include "../config/permission.php";

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$hid = (int)($_SESSION['hospital_id'] ?? 0);
$nurse_id = (int)$_SESSION['id'];

if ($hid <= 0) {
    die("Hospital ID not found.");
}

if (!$conn) {
    die("Database connection failed.");
}

/* =========================================================
   PATIENT ID FROM URL
   Example:
   add_nursing_note.php?patient_id=6
   ========================================================= */

$selected_patient_id = isset($_GET['patient_id'])
    ? (int)$_GET['patient_id']
    : 0;

$message = "";
$error = "";


/* =========================================================
   FETCH PATIENTS
   ========================================================= */

$patients = [];

$patient_sql = "
    SELECT patient_id, patient_name, age, gender
    FROM patients
    WHERE hospital_id = '$hid'
    AND (delete_flag = 0 OR delete_flag IS NULL)
    ORDER BY patient_name ASC
";

$patient_result = $conn->query($patient_sql);

if ($patient_result) {
    while ($row = $patient_result->fetch_assoc()) {
        $patients[] = $row;
    }
}


/* =========================================================
   ADD NURSING NOTE
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = isset($_POST['patient_id'])
        ? (int)$_POST['patient_id']
        : 0;

    $note_type = trim($_POST['note_type'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($patient_id <= 0) {
        $error = "Please select a patient.";
    } elseif (empty($note_type)) {
        $error = "Please select note type.";
    } elseif (empty($note)) {
        $error = "Please enter nursing note.";
    } else {

        $note_type = mysqli_real_escape_string($conn, $note_type);
        $note = mysqli_real_escape_string($conn, $note);

        /* Verify patient belongs to same hospital */

        $check_patient = "
            SELECT patient_id
            FROM patients
            WHERE patient_id = '$patient_id'
            AND hospital_id = '$hid'
            AND (delete_flag = 0 OR delete_flag IS NULL)
            LIMIT 1
        ";

        $check_result = $conn->query($check_patient);

        if (!$check_result || $check_result->num_rows == 0) {

            $error = "Invalid patient.";

        } else {

            $insert_sql = "
                INSERT INTO nursing_notes
                (
                    patient_id,
                    hospital_id,
                    nurse_id,
                    note_type,
                    note,
                    recorded_at,
                    delete_flag
                )
                VALUES
                (
                    '$patient_id',
                    '$hid',
                    '$nurse_id',
                    '$note_type',
                    '$note',
                    NOW(),
                    0
                )
            ";

            if ($conn->query($insert_sql)) {

                $new_note_id = $conn->insert_id;

                header(
                    "Location:nursing_notes.php?id=" .
                    $new_note_id .
                    "&success=1"
                );
                exit;

            } else {

                $error = "Failed to add nursing note: " . $conn->error;
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

<title>Add Nursing Note - <?php echo htmlspecialchars($hospital['hospital_name']); ?></title>

<link rel="icon"
      type="image/png"
      href="../<?php echo htmlspecialchars($hospital['hospital_logo']); ?>">

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet">

</head>

<body class="bg-gray-50 text-gray-900">

<?php include "../header.php"; ?>

<div class="flex min-h-screen">

    <?php include "../Sidebar.php"; ?>

    <main class="flex-1 overflow-auto p-4 xl:p-6 xl:ml-64">

        <div class="max-w-4xl mx-auto">

            <!-- HEADER -->

            <div class="flex items-center gap-4 mb-6">

                <a href="nursing_notes.php"
                   class="inline-flex items-center justify-center w-10 h-10 border border-gray-200 rounded-lg bg-white hover:bg-gray-100">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M19 12H5"/>
                        <path d="M12 19l-7-7 7-7"/>

                    </svg>

                </a>

                <div>

                    <h1 class="text-2xl lg:text-3xl font-bold">
                        Add Nursing Note
                    </h1>

                    <p class="text-gray-500 text-sm">
                        Record a nursing observation or patient note.
                    </p>

                </div>

            </div>


            <!-- ERROR -->

            <?php if (!empty($error)): ?>

                <div class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <div class="bg-white rounded-xl border shadow-sm p-6">

                <form method="POST">

                    <!-- PATIENT -->

                    <div class="mb-5">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Patient <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="patient_id"
                            required
                            <?php echo $selected_patient_id > 0 ? 'disabled' : ''; ?>
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">

                            <option value="">Select Patient</option>

                            <?php foreach ($patients as $patient): ?>

                                <option
                                    value="<?php echo $patient['patient_id']; ?>"
                                    <?php echo $selected_patient_id == $patient['patient_id'] ? 'selected' : ''; ?>>

                                    <?php echo htmlspecialchars($patient['patient_name']); ?>

                                    <?php if (!empty($patient['age'])): ?>
                                        - <?php echo htmlspecialchars($patient['age']); ?> yrs
                                    <?php endif; ?>

                                    <?php if (!empty($patient['gender'])): ?>
                                        - <?php echo htmlspecialchars($patient['gender']); ?>
                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if ($selected_patient_id > 0): ?>

                            <input type="hidden"
                                   name="patient_id"
                                   value="<?php echo $selected_patient_id; ?>">

                            <p class="text-xs text-blue-600 mt-2">
                                Patient automatically selected from the patient profile.
                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- NOTE TYPE -->

                    <div class="mb-5">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Note Type <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="note_type"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">

                            <option value="">Select Note Type</option>

                            <option value="Observation">Observation</option>
                            <option value="Patient Condition">Patient Condition</option>
                            <option value="Pain">Pain</option>
                            <option value="Medication">Medication</option>
                            <option value="Food & Fluid">Food & Fluid</option>
                            <option value="Sleep">Sleep</option>
                            <option value="Hygiene">Hygiene</option>
                            <option value="Other">Other</option>

                        </select>

                    </div>


                    <!-- NOTE -->

                    <div class="mb-6">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nursing Note <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            name="note"
                            rows="7"
                            required
                            placeholder="Enter nursing observation or patient condition..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none resize-none"></textarea>

                    </div>


                    <!-- BUTTONS -->

                    <div class="flex justify-end gap-3">

                        <a href="nursing_notes.php"
                           class="px-5 py-3 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium hover:bg-gray-50">

                            Cancel

                        </a>

                        <button
                            type="submit"
                            class="px-6 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">

                            Add Nursing Note

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

</body>
</html>