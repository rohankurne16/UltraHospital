<?php

session_start();

include "../config/hospital.php";
include "../config/permission.php";

$hid = $_SESSION['hospital_id'] ?? 0;

if (!$hid) {
    header("Location: ../index.php");
    exit;
}

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

/*
|--------------------------------------------------------------------------
| FETCH ALL VITALS
|--------------------------------------------------------------------------
| nurse_id in patient_vitals stores register.id
|--------------------------------------------------------------------------
*/

$sql = "SELECT 
            pv.*,
            p.patient_name,
            p.patient_image,
            r.name AS nurse_name,
            hm.hospital_name
        FROM patient_vitals pv

        LEFT JOIN patients p 
            ON pv.patient_id = p.patient_id

        LEFT JOIN register r 
            ON pv.nurse_id = r.id

        LEFT JOIN hospital_master hm 
            ON pv.hospital_id = hm.hospital_id

        WHERE pv.hospital_id = '$hid'
        AND (pv.delete_flag IS NULL OR pv.delete_flag = 0)

        ORDER BY pv.recorded_at DESC";

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

    <title>
        Vital Records -
        <?php echo htmlspecialchars($hospital['hospital_name'] ?? 'Hospital'); ?>
    </title>

    <link rel="icon"
          type="image/png"
          href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? ''); ?>">

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>

        body {
            font-family: 'Inter', sans-serif;
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

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .edit-btn {
            color: #7c3aed;
            background: #f5f3ff;
        }

        .edit-btn:hover {
            background: #ede9fe;
        }

        .delete-btn {
            color: #dc2626;
            background: #fef2f2;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }

        .vital-value {
            font-weight: 600;
            color: #374151;
        }

    </style>

</head>


<body class="bg-gray-50 text-gray-900">

<?php include '../header.php'; ?>

<div class="flex min-h-screen">

    <?php include '../Sidebar.php'; ?>


    <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64">

        <div class="flex flex-col gap-5">


            <!-- ===================================================== -->
            <!-- HEADER -->
            <!-- ===================================================== -->

            <div class="flex flex-col md:flex-row md:justify-between
                        justify-start items-start md:items-center gap-4">

                <div class="flex items-center gap-4">

                    <!-- BACK BUTTON -->

                    <a href="dashboard.php"
                       class="back-btn"
                       title="Back to Vitals">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="20"
                             height="20"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <path d="M19 12H5"></path>
                            <path d="M12 19l-7-7 7-7"></path>

                        </svg>

                    </a>


                    <div>

                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight">
                            Vital Records
                        </h1>

                        <p class="text-gray-500 text-sm">
                            View and manage all recorded patient vitals.
                        </p>

                    </div>

                </div>


                <!-- ADD VITAL BUTTON -->

                <a href="add_vitals.php"
                   class="inline-flex items-center gap-2
                          bg-blue-600 hover:bg-blue-700
                          text-white px-5 py-2.5
                          rounded-lg text-sm font-medium
                          transition">

                    <i data-lucide="plus" class="w-4 h-4"></i>

                    Add Vitals

                </a>

            </div>


            <!-- ===================================================== -->
            <!-- TABLE CARD -->
            <!-- ===================================================== -->

            <div class="rounded-xl border bg-white shadow-sm overflow-hidden">


                <!-- TABLE HEADER -->

                <div class="flex flex-col md:flex-row
                            md:items-center md:justify-between
                            gap-3 p-4 border-b bg-gray-50/50">

                    <div>

                        <h2 class="text-lg font-semibold text-gray-900">
                            All Vital Records
                        </h2>

                        <p class="text-xs text-gray-500 mt-1">

                            Showing
                            <span class="font-medium text-gray-700">
                                <?php echo $result->num_rows; ?>
                            </span>

                            vital record<?php echo $result->num_rows != 1 ? 's' : ''; ?>

                        </p>

                    </div>


                    <!-- SEARCH -->

                    <div class="relative w-full md:w-72">

                        <i data-lucide="search"
                           class="absolute left-3 top-1/2
                                  -translate-y-1/2
                                  w-4 h-4 text-gray-400">
                        </i>

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search patient or nurse..."
                            onkeyup="searchVitals()"
                            class="w-full rounded-lg
                                   border border-gray-300
                                   bg-white
                                   py-2.5 pl-10 pr-4
                                   text-sm
                                   outline-none
                                   focus:border-blue-500
                                   focus:ring-2
                                   focus:ring-blue-200">

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- TABLE -->
                <!-- ================================================= -->

                <div class="relative w-full overflow-x-auto">

                    <table class="w-full text-sm">

                        <thead>

                            <tr class="border-b border-gray-200
                                       bg-gray-50/50">

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Patient
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Nurse
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    BP
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Pulse
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Temperature
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Respiration
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    SpO₂
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Height
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Weight
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Sugar
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Pain
                                </th>

                                <th class="h-12 px-4 text-left
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Recorded
                                </th>

                                <th class="h-12 px-4 text-right
                                           font-semibold text-gray-600
                                           text-xs uppercase
                                           tracking-wider">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody id="vitalsTableBody">

                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr class="vital-row border-b
                                           border-gray-50
                                           hover:bg-gray-50/50
                                           transition">


                                    <!-- PATIENT -->

                                    <td class="p-4">

                                        <div class="flex items-center gap-3">

                                            <?php

                                            $patientImage = $row['patient_image'] ?? '';

                                            $fullImagePath =
                                                !empty($patientImage)
                                                ? '../' . $patientImage
                                                : '';

                                            if (
                                                !empty($fullImagePath) &&
                                                file_exists($fullImagePath)
                                            ):

                                            ?>

                                                <img
                                                    src="<?php echo htmlspecialchars($fullImagePath); ?>"
                                                    class="w-10 h-10 rounded-full
                                                           object-cover
                                                           border border-gray-200">

                                            <?php else: ?>

                                                <div class="w-10 h-10 rounded-full
                                                            bg-blue-100
                                                            flex items-center
                                                            justify-center
                                                            text-blue-600
                                                            font-bold text-xs">

                                                    <?php
                                                    echo strtoupper(
                                                        substr(
                                                            $row['patient_name'] ?? 'NA',
                                                            0,
                                                            2
                                                        )
                                                    );
                                                    ?>

                                                </div>

                                            <?php endif; ?>


                                            <div>

                                                <p class="font-medium text-gray-900">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $row['patient_name'] ?? 'Unknown'
                                                    );
                                                    ?>

                                                </p>

                                                <p class="text-xs text-gray-400">

                                                    ID:
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $row['patient_id']
                                                    );
                                                    ?>

                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- NURSE -->

                                    <td class="p-4">

                                        <span class="font-medium text-gray-700">

                                            <?php
                                            echo htmlspecialchars(
                                                $row['nurse_name'] ?? 'Unknown'
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- BP -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['bp'] ?? '-'
                                        );
                                        ?>

                                    </td>


                                    <!-- PULSE -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['pulse'] ?? '-'
                                        );
                                        ?>

                                        <span class="text-xs text-gray-400">
                                            bpm
                                        </span>

                                    </td>


                                    <!-- TEMPERATURE -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['temperature'] ?? '-'
                                        );
                                        ?>

                                        <span class="text-xs text-gray-400">
                                            °F
                                        </span>

                                    </td>


                                    <!-- RESPIRATION -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['respiration_rate'] ?? '-'
                                        );
                                        ?>

                                    </td>


                                    <!-- SPO2 -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['spo2'] ?? '-'
                                        );
                                        ?>

                                        <span class="text-xs text-gray-400">
                                            %
                                        </span>

                                    </td>


                                    <!-- HEIGHT -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['height'] ?? '-'
                                        );
                                        ?>

                                        <span class="text-xs text-gray-400">
                                            cm
                                        </span>

                                    </td>


                                    <!-- WEIGHT -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['weight'] ?? '-'
                                        );
                                        ?>

                                        <span class="text-xs text-gray-400">
                                            kg
                                        </span>

                                    </td>


                                    <!-- BLOOD SUGAR -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['blood_sugar'] ?? '-'
                                        );
                                        ?>

                                    </td>


                                    <!-- PAIN SCORE -->

                                    <td class="p-4 vital-value">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['pain_score'] ?? '-'
                                        );
                                        ?>

                                        /10

                                    </td>


                                    <!-- RECORDED -->

                                    <td class="p-4">

                                        <div class="text-gray-700 font-medium">

                                            <?php

                                            echo !empty($row['recorded_at'])
                                                ? date(
                                                    'd M Y',
                                                    strtotime($row['recorded_at'])
                                                )
                                                : '-';

                                            ?>

                                        </div>

                                        <div class="text-xs text-gray-400">

                                            <?php

                                            echo !empty($row['recorded_at'])
                                                ? date(
                                                    'h:i A',
                                                    strtotime($row['recorded_at'])
                                                )
                                                : '';

                                            ?>

                                        </div>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td class="p-4">

                                        <div class="flex items-center
                                                    justify-end gap-2">


                                            <!-- EDIT -->

                                            <a
                                                href="update_vitals.php?id=<?php echo $row['vital_id']; ?>"
                                                class="action-btn edit-btn"
                                                title="Edit Vitals">

                                                <i
                                                    data-lucide="pencil"
                                                    class="w-4 h-4">
                                                </i>

                                            </a>


                                           

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="13"
                                    class="p-16 text-center text-gray-400">

                                    <div class="flex flex-col
                                                items-center gap-3">

                                        <div class="w-16 h-16
                                                    bg-gray-100
                                                    rounded-full
                                                    flex items-center
                                                    justify-center">

                                            <i data-lucide="activity"
                                               class="w-8 h-8 text-gray-300">
                                            </i>

                                        </div>

                                        <span class="font-semibold
                                                     text-gray-900">

                                            No Vital Records Found

                                        </span>

                                        <span class="text-sm">

                                            No vitals have been recorded yet.

                                        </span>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- ================================================= -->
                <!-- FOOTER -->
                <!-- ================================================= -->

                <div class="px-4 py-3 border-t
                            border-gray-200
                            bg-gray-50/30
                            flex items-center
                            justify-between
                            text-sm text-gray-500">

                    <div>

                        Total Records:

                        <span class="font-medium text-gray-700">

                            <?php echo $result->num_rows; ?>

                        </span>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<script>

    lucide.createIcons();


    function searchVitals() {

        const input =
            document
                .getElementById("searchInput")
                .value
                .toLowerCase()
                .trim();

        const rows =
            document.querySelectorAll(".vital-row");


        rows.forEach(function(row) {

            const text =
                row.innerText.toLowerCase();

            if (text.includes(input)) {

                row.style.display = "";

            } else {

                row.style.display = "none";

            }

        });

    }

</script>

</body>

</html>