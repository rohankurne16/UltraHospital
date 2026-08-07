<?php

session_start();

include "../config/hospital.php";
include "../config/permission.php";

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$hid = (int)($_SESSION['hospital_id'] ?? 0);

if ($hid <= 0) {
    die("Hospital ID not found.");
}

if (!$conn) {
    die("Database connection failed.");
}


/* =========================================================
   FETCH ALL NURSING NOTES
   ========================================================= */

$sql = "
    SELECT
        n.note_id,
        n.patient_id,
        n.hospital_id,
        n.nurse_id,
        n.note_type,
        n.note,
        n.recorded_at,
        n.modified_at,
        p.patient_name,
        p.age,
        p.gender,
        r.name AS nurse_name
    FROM nursing_notes n

    INNER JOIN patients p
        ON n.patient_id = p.patient_id

    LEFT JOIN register r
        ON n.nurse_id = r.id

    WHERE n.hospital_id = '$hid'
    AND (n.delete_flag = 0 OR n.delete_flag IS NULL)

    ORDER BY n.recorded_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Nursing Notes - <?php echo htmlspecialchars($hospital['hospital_name']); ?></title>

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

        <div class="flex flex-col gap-5">

            <!-- HEADER -->

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <div class="flex items-center gap-4">

                    <a href="dashboard.php"
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
                            Nursing Notes
                        </h1>

                        <p class="text-gray-500 text-sm">
                            View and manage patient nursing notes.
                        </p>

                    </div>

                </div>


                <a href="add_nursing_note.php"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">

                    <span class="text-xl">+</span>
                    Add Nursing Note

                </a>

            </div>


            <!-- NOTES CARD -->

            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">

                <div class="px-5 py-4 border-b bg-gray-50/50">

                    <h2 class="font-semibold text-lg">
                        All Nursing Notes
                    </h2>

                    <p class="text-sm text-gray-500">
                        Showing <?php echo $result->num_rows; ?> notes
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full text-sm">

                        <thead>

                            <tr class="border-b bg-gray-50">

                                <th class="px-5 py-4 text-left font-semibold text-gray-600">
                                    Patient
                                </th>

                                <th class="px-5 py-4 text-left font-semibold text-gray-600">
                                    Note Type
                                </th>

                                <th class="px-5 py-4 text-left font-semibold text-gray-600">
                                    Note
                                </th>

                                <th class="px-5 py-4 text-left font-semibold text-gray-600">
                                    Nurse
                                </th>

                                <th class="px-5 py-4 text-left font-semibold text-gray-600">
                                    Recorded At
                                </th>

                                <th class="px-5 py-4 text-right font-semibold text-gray-600">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr class="border-b hover:bg-gray-50">

                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-gray-900">

                                            <?php echo htmlspecialchars($row['patient_name']); ?>

                                        </div>

                                        <div class="text-xs text-gray-500">

                                            <?php echo htmlspecialchars($row['age']); ?> yrs
                                            •
                                            <?php echo htmlspecialchars($row['gender']); ?>

                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <span class="inline-flex px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">

                                            <?php echo htmlspecialchars($row['note_type']); ?>

                                        </span>

                                    </td>


                                    <td class="px-5 py-4 max-w-md">

                                        <div class="text-gray-700 truncate">

                                            <?php echo htmlspecialchars($row['note']); ?>

                                        </div>

                                    </td>


                                    <td class="px-5 py-4 text-gray-700">

                                        <?php echo htmlspecialchars($row['nurse_name'] ?? 'Unknown'); ?>

                                    </td>


                                    <td class="px-5 py-4 text-gray-600">

                                        <?php echo date(
                                            'd M Y, h:i A',
                                            strtotime($row['recorded_at'])
                                        ); ?>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="flex justify-end gap-2">

                                         

                                            <a href="edit_nursing_note.php?id=<?php echo $row['note_id']; ?>"
                                               class="px-3 py-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 font-medium">

                                                Edit

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6" class="p-16 text-center">

                                    <div class="text-gray-400">

                                        <div class="text-4xl mb-3">
                                            📝
                                        </div>

                                        <p class="font-semibold text-gray-700">
                                            No Nursing Notes Found
                                        </p>

                                        <p class="text-sm mt-1">
                                            Add a nursing note to get started.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>