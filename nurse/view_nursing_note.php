<?php

session_start();

include "../config/hospital.php";
include "../config/permission.php";

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$hid = (int)($_SESSION['hospital_id'] ?? 0);

$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($note_id <= 0) {
    die("Invalid nursing note.");
}

$sql = "
    SELECT
        n.*,
        p.patient_name,
        p.age,
        p.gender,
        p.blood_group,
        p.mobile,
        r.name AS nurse_name
    FROM nursing_notes n

    INNER JOIN patients p
        ON n.patient_id = p.patient_id

    LEFT JOIN register r
        ON n.nurse_id = r.id

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

$note = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>View Nursing Note</title>

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

            <div class="flex items-center justify-between mb-6">

                <div class="flex items-center gap-4">

                    <a href="nursing_notes.php"
                       class="inline-flex items-center justify-center w-10 h-10 border border-gray-200 rounded-lg bg-white hover:bg-gray-100">

                        ←

                    </a>

                    <div>

                        <h1 class="text-2xl lg:text-3xl font-bold">
                            Nursing Note
                        </h1>

                        <p class="text-gray-500 text-sm">
                            View nursing note details.
                        </p>

                    </div>

                </div>


                <a href="edit_nursing_note.php?id=<?php echo $note_id; ?>"
                   class="px-5 py-3 rounded-lg bg-purple-600 text-white font-semibold hover:bg-purple-700">

                    Edit Note

                </a>

            </div>


            <!-- PATIENT -->

            <div class="bg-white rounded-xl border shadow-sm mb-5">

                <div class="px-6 py-4 border-b">

                    <h2 class="font-semibold text-lg">
                        Patient Information
                    </h2>

                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>

                        <p class="text-xs text-gray-500 mb-1">
                            Patient Name
                        </p>

                        <p class="font-semibold">
                            <?php echo htmlspecialchars($note['patient_name']); ?>
                        </p>

                    </div>

                    <div>

                        <p class="text-xs text-gray-500 mb-1">
                            Age / Gender
                        </p>

                        <p class="font-semibold">

                            <?php echo htmlspecialchars($note['age']); ?>
                            years
                            •
                            <?php echo htmlspecialchars($note['gender']); ?>

                        </p>

                    </div>

                    <div>

                        <p class="text-xs text-gray-500 mb-1">
                            Blood Group
                        </p>

                        <p class="font-semibold">
                            <?php echo htmlspecialchars($note['blood_group']); ?>
                        </p>

                    </div>

                    <div>

                        <p class="text-xs text-gray-500 mb-1">
                            Mobile
                        </p>

                        <p class="font-semibold">
                            <?php echo htmlspecialchars($note['mobile']); ?>
                        </p>

                    </div>

                </div>

            </div>


            <!-- NOTE -->

            <div class="bg-white rounded-xl border shadow-sm">

                <div class="px-6 py-4 border-b flex justify-between items-center">

                    <h2 class="font-semibold text-lg">
                        Nursing Note
                    </h2>

                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">

                        <?php echo htmlspecialchars($note['note_type']); ?>

                    </span>

                </div>

                <div class="p-6">

                    <div class="bg-gray-50 rounded-lg p-5">

                        <p class="text-gray-800 whitespace-pre-line leading-7">

                            <?php echo htmlspecialchars($note['note']); ?>

                        </p>

                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">

                        <div>

                            <p class="text-xs text-gray-500 mb-1">
                                Recorded By
                            </p>

                            <p class="font-semibold">

                                <?php echo htmlspecialchars(
                                    $note['nurse_name'] ?? 'Unknown'
                                ); ?>

                            </p>

                        </div>


                        <div>

                            <p class="text-xs text-gray-500 mb-1">
                                Recorded At
                            </p>

                            <p class="font-semibold">

                                <?php echo date(
                                    'd M Y, h:i A',
                                    strtotime($note['recorded_at'])
                                ); ?>

                            </p>

                        </div>


                        <?php if (!empty($note['modified_at'])): ?>

                        <div>

                            <p class="text-xs text-gray-500 mb-1">
                                Last Modified
                            </p>

                            <p class="font-semibold">

                                <?php echo date(
                                    'd M Y, h:i A',
                                    strtotime($note['modified_at'])
                                ); ?>

                            </p>

                        </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>