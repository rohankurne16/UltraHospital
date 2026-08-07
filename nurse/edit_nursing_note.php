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

$note_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : (int)($_POST['note_id'] ?? 0);

if ($note_id <= 0) {
    die("Invalid nursing note.");
}


/* =========================================================
   FETCH NOTE
   ========================================================= */

$sql = "
    SELECT
        n.*,
        p.patient_name,
        p.age,
        p.gender
    FROM nursing_notes n

    INNER JOIN patients p
        ON n.patient_id = p.patient_id

    WHERE n.note_id = '$note_id'
    AND n.hospital_id = '$hid'
    AND (n.delete_flag = 0 OR n.delete_flag IS NULL)

    LIMIT 1
";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

if ($result->num_rows == 0) {
    die("Nursing note not found.");
}

$current = $result->fetch_assoc();

$error = "";


/* =========================================================
   UPDATE NOTE
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $note_type = trim($_POST['note_type'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (empty($note_type)) {

        $error = "Please select note type.";

    } elseif (empty($note)) {

        $error = "Please enter nursing note.";

    } else {

        $note_type = mysqli_real_escape_string($conn, $note_type);
        $note = mysqli_real_escape_string($conn, $note);

        $update_sql = "
            UPDATE nursing_notes
            SET
                note_type = '$note_type',
                note = '$note',
                modified_at = NOW()
            WHERE note_id = '$note_id'
            AND hospital_id = '$hid'
            AND (delete_flag = 0 OR delete_flag IS NULL)
        ";

        if ($conn->query($update_sql)) {

            header(
                "Location: nursing_notes.php?id=" .
                $note_id .
                "&updated=1"
            );

            exit;

        } else {

            $error = "Failed to update nursing note: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Nursing Note</title>

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

                <a href="nursing_notes.php?id=<?php echo $note_id; ?>"
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
                        Edit Nursing Note
                    </h1>

                    <p class="text-gray-500 text-sm">
                        Update the nursing note.
                    </p>

                </div>

            </div>


            <?php if (!empty($error)): ?>

                <div class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <!-- PATIENT INFORMATION -->

            <div class="bg-white rounded-xl border shadow-sm p-6 mb-5">

                <p class="text-sm text-gray-500">
                    Patient
                </p>

                <p class="text-lg font-bold">

                    <?php echo htmlspecialchars($current['patient_name']); ?>

                </p>

                <p class="text-sm text-gray-500 mt-1">

                    <?php echo htmlspecialchars($current['age']); ?>
                    years
                    •
                    <?php echo htmlspecialchars($current['gender']); ?>

                </p>

            </div>


            <!-- FORM -->

            <div class="bg-white rounded-xl border shadow-sm p-6">

                <form method="POST">

                    <input type="hidden"
                           name="note_id"
                           value="<?php echo $note_id; ?>">


                    <!-- NOTE TYPE -->

                    <div class="mb-5">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">

                            Note Type

                            <span class="text-red-500">*</span>

                        </label>

                        <select
                            name="note_type"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">

                            <option value="">
                                Select Note Type
                            </option>

                            <?php

                            $types = [
                                'Observation',
                                'Patient Condition',
                                'Pain',
                                'Medication',
                                'Food & Fluid',
                                'Sleep',
                                'Hygiene',
                                'Other'
                            ];

                            foreach ($types as $type):

                            ?>

                                <option
                                    value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo $current['note_type'] === $type ? 'selected' : ''; ?>>

                                    <?php echo htmlspecialchars($type); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- NOTE -->

                    <div class="mb-6">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">

                            Nursing Note

                            <span class="text-red-500">*</span>

                        </label>

                        <textarea
                            name="note"
                            rows="8"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none resize-none"><?php echo htmlspecialchars($current['note']); ?></textarea>

                    </div>


                    <!-- BUTTONS -->

                    <div class="flex justify-end gap-3">

                        <a href="view_nursing_note.php?id=<?php echo $note_id; ?>"
                           class="px-5 py-3 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium hover:bg-gray-50">

                            Cancel

                        </a>

                        <button
                            type="submit"
                            class="px-6 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">

                            Update Nursing Note

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

</body>
</html>