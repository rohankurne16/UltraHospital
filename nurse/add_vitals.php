<?php

session_start();

include "../config/hospital.php";
include "../config/permission.php";

// Uncomment if you want permission checking
// checkPermission('vitals-add');


// ============================================================
// SESSION DATA
// ============================================================

$hospital_id = (int) ($_SESSION['hospital_id'] ?? 0);
$nurse_id    = (int) ($_SESSION['id'] ?? 0);


// ============================================================
// DATABASE CHECK
// ============================================================

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

if ($hospital_id <= 0) {
    die("Hospital session not found.");
}

if ($nurse_id <= 0) {
    die("Nurse session not found.");
}


// ============================================================
// PATIENT ID FROM URL
// ============================================================

$url_patient_id = isset($_GET['patient_id'])
    ? (int) $_GET['patient_id']
    : 0;


// ============================================================
// VARIABLES
// ============================================================

$success = "";
$error   = "";
$errors  = []; // Array to store field-specific errors

$selected_patient_id = $url_patient_id;

$bp               = "";
$pulse            = "";
$temperature      = "";
$respiration_rate = "";
$spo2             = "";
$height           = "";
$weight           = "";
$blood_sugar      = "";
$pain_score       = "";
$remarks          = "";


// ============================================================
// LOAD PATIENTS
// ============================================================

$patients = [];

$patient_sql = "
    SELECT 
        patient_id,
        patient_name,
        age,
        gender
    FROM patients
    WHERE hospital_id = $hospital_id
    AND (delete_flag IS NULL OR delete_flag = 0)
    ORDER BY patient_name ASC
";

$patient_result = mysqli_query($conn, $patient_sql);

if (!$patient_result) {
    die("Patient Query Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($patient_result)) {
    $patients[] = $row;
}


// ============================================================
// IF PATIENT ID IS PROVIDED IN URL,
// VERIFY THAT PATIENT BELONGS TO CURRENT HOSPITAL
// ============================================================

if ($url_patient_id > 0) {

    $verify_sql = "
        SELECT 
            patient_id,
            patient_name
        FROM patients
        WHERE patient_id = $url_patient_id
        AND hospital_id = $hospital_id
        AND (delete_flag IS NULL OR delete_flag = 0)
        LIMIT 1
    ";

    $verify_result = mysqli_query($conn, $verify_sql);

    if (!$verify_result) {
        die("Patient Verification Error: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($verify_result) == 0) {

        $error = "Invalid patient selected.";

        $selected_patient_id = 0;
    }
}


// ============================================================
// VALIDATION FUNCTIONS
// ============================================================

function validateBloodPressure($bp) {
    $bp = trim($bp);
    if (empty($bp)) {
        return ['valid' => false, 'message' => 'Blood pressure is required.'];
    }
    
    // Pattern: systolic/diastolic (e.g., 120/80)
    if (!preg_match('/^[0-9]{2,3}\/[0-9]{2,3}$/', $bp)) {
        return ['valid' => false, 'message' => 'Invalid format. Use systolic/diastolic (e.g., 120/80)'];
    }
    
    list($systolic, $diastolic) = explode('/', $bp);
    
    if ($systolic < 60 || $systolic > 250) {
        return ['valid' => false, 'message' => 'Systolic must be between 60-250 mmHg'];
    }
    
    if ($diastolic < 30 || $diastolic > 200) {
        return ['valid' => false, 'message' => 'Diastolic must be between 30-200 mmHg'];
    }
    
    if ($diastolic >= $systolic) {
        return ['valid' => false, 'message' => 'Diastolic must be less than systolic'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validatePulse($pulse) {
    $pulse = trim($pulse);
    if (empty($pulse)) {
        return ['valid' => false, 'message' => 'Pulse is required.'];
    }
    
    if (!is_numeric($pulse)) {
        return ['valid' => false, 'message' => 'Pulse must be a number'];
    }
    
    $pulse = (int)$pulse;
    if ($pulse < 30 || $pulse > 220) {
        return ['valid' => false, 'message' => 'Pulse must be between 30-220 BPM'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateTemperature($temp) {
    $temp = trim($temp);
    if (empty($temp)) {
        return ['valid' => false, 'message' => 'Temperature is required.'];
    }
    
    if (!is_numeric($temp)) {
        return ['valid' => false, 'message' => 'Temperature must be a number'];
    }
    
    $temp = (float)$temp;
    if ($temp < 32 || $temp > 43) {
        return ['valid' => false, 'message' => 'Temperature must be between 32-43 °C'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateRespirationRate($rr) {
    $rr = trim($rr);
    if (empty($rr)) {
        return ['valid' => false, 'message' => 'Respiration rate is required.'];
    }
    
    if (!is_numeric($rr)) {
        return ['valid' => false, 'message' => 'Respiration rate must be a number'];
    }
    
    $rr = (int)$rr;
    if ($rr < 5 || $rr > 60) {
        return ['valid' => false, 'message' => 'Respiration rate must be between 5-60 breaths/min'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateSpO2($spo2) {
    $spo2 = trim($spo2);
    if (empty($spo2)) {
        return ['valid' => false, 'message' => 'SpO2 is required.'];
    }
    
    if (!is_numeric($spo2)) {
        return ['valid' => false, 'message' => 'SpO2 must be a number'];
    }
    
    $spo2 = (int)$spo2;
    if ($spo2 < 70 || $spo2 > 100) {
        return ['valid' => false, 'message' => 'SpO2 must be between 70-100%'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateHeight($height) {
    $height = trim($height);
    if (empty($height)) {
        return ['valid' => false, 'message' => 'Height is required.'];
    }
    
    if (!is_numeric($height)) {
        return ['valid' => false, 'message' => 'Height must be a number'];
    }
    
    $height = (float)$height;
    if ($height < 50 || $height > 280) {
        return ['valid' => false, 'message' => 'Height must be between 50-280 cm'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateWeight($weight) {
    $weight = trim($weight);
    if (empty($weight)) {
        return ['valid' => false, 'message' => 'Weight is required.'];
    }
    
    if (!is_numeric($weight)) {
        return ['valid' => false, 'message' => 'Weight must be a number'];
    }
    
    $weight = (float)$weight;
    if ($weight < 1 || $weight > 400) {
        return ['valid' => false, 'message' => 'Weight must be between 1-400 kg'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateBloodSugar($sugar) {
    $sugar = trim($sugar);
    if (empty($sugar)) {
        return ['valid' => false, 'message' => 'Blood sugar is required.'];
    }
    
    if (!is_numeric($sugar)) {
        return ['valid' => false, 'message' => 'Blood sugar must be a number'];
    }
    
    $sugar = (float)$sugar;
    if ($sugar < 10 || $sugar > 600) {
        return ['valid' => false, 'message' => 'Blood sugar must be between 10-600 mg/dL'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validatePainScore($pain) {
    $pain = trim($pain);
    if (empty($pain)) {
        return ['valid' => false, 'message' => 'Pain score is required.'];
    }
    
    if (!is_numeric($pain)) {
        return ['valid' => false, 'message' => 'Pain score must be a number'];
    }
    
    $pain = (int)$pain;
    if ($pain < 0 || $pain > 10) {
        return ['valid' => false, 'message' => 'Pain score must be between 0-10'];
    }
    
    return ['valid' => true, 'message' => ''];
}


// ============================================================
// FORM SUBMISSION
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------------
    // Patient
    // --------------------------------------------------------

    $patient_id = (int) ($_POST['patient_id'] ?? 0);


    // --------------------------------------------------------
    // Vital values
    // --------------------------------------------------------

    $bp               = trim($_POST['bp'] ?? '');
    $pulse            = trim($_POST['pulse'] ?? '');
    $temperature      = trim($_POST['temperature'] ?? '');
    $respiration_rate = trim($_POST['respiration_rate'] ?? '');
    $spo2             = trim($_POST['spo2'] ?? '');
    $height           = trim($_POST['height'] ?? '');
    $weight           = trim($_POST['weight'] ?? '');
    $blood_sugar      = trim($_POST['blood_sugar'] ?? '');
    $pain_score       = trim($_POST['pain_score'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');


    // Keep selected patient after submit
    $selected_patient_id = $patient_id;


    // ========================================================
    // VALIDATE PATIENT
    // ========================================================

    if ($patient_id <= 0) {
        $errors['patient_id'] = "Please select a patient.";
    } else {
        $check_patient_sql = "
            SELECT patient_id
            FROM patients
            WHERE patient_id = $patient_id
            AND hospital_id = $hospital_id
            AND (delete_flag IS NULL OR delete_flag = 0)
            LIMIT 1
        ";

        $check_patient_result = mysqli_query($conn, $check_patient_sql);

        if (!$check_patient_result) {
            $errors['patient_id'] = "Patient validation failed: " . mysqli_error($conn);
        } elseif (mysqli_num_rows($check_patient_result) == 0) {
            $errors['patient_id'] = "Invalid patient selected.";
        }
    }


    // ========================================================
    // VALIDATE VITAL SIGNS
    // ========================================================

    // Validate BP
    $bpValidation = validateBloodPressure($bp);
    if (!$bpValidation['valid']) {
        $errors['bp'] = $bpValidation['message'];
    }

    // Validate Pulse
    $pulseValidation = validatePulse($pulse);
    if (!$pulseValidation['valid']) {
        $errors['pulse'] = $pulseValidation['message'];
    }

    // Validate Temperature
    $tempValidation = validateTemperature($temperature);
    if (!$tempValidation['valid']) {
        $errors['temperature'] = $tempValidation['message'];
    }

    // Validate Respiration Rate
    $rrValidation = validateRespirationRate($respiration_rate);
    if (!$rrValidation['valid']) {
        $errors['respiration_rate'] = $rrValidation['message'];
    }

    // Validate SpO2
    $spo2Validation = validateSpO2($spo2);
    if (!$spo2Validation['valid']) {
        $errors['spo2'] = $spo2Validation['message'];
    }

    // Validate Height
    $heightValidation = validateHeight($height);
    if (!$heightValidation['valid']) {
        $errors['height'] = $heightValidation['message'];
    }

    // Validate Weight
    $weightValidation = validateWeight($weight);
    if (!$weightValidation['valid']) {
        $errors['weight'] = $weightValidation['message'];
    }

    // Validate Blood Sugar
    $sugarValidation = validateBloodSugar($blood_sugar);
    if (!$sugarValidation['valid']) {
        $errors['blood_sugar'] = $sugarValidation['message'];
    }

    // Validate Pain Score
    $painValidation = validatePainScore($pain_score);
    if (!$painValidation['valid']) {
        $errors['pain_score'] = $painValidation['message'];
    }


    // ========================================================
    // IF NO ERRORS, INSERT INTO DATABASE
    // ========================================================

    if (empty($errors)) {

        // ESCAPE VALUES
        $bp = mysqli_real_escape_string($conn, $bp);
        $pulse = mysqli_real_escape_string($conn, $pulse);
        $temperature = mysqli_real_escape_string($conn, $temperature);
        $respiration_rate = mysqli_real_escape_string($conn, $respiration_rate);
        $spo2 = mysqli_real_escape_string($conn, $spo2);
        $height = mysqli_real_escape_string($conn, $height);
        $weight = mysqli_real_escape_string($conn, $weight);
        $blood_sugar = mysqli_real_escape_string($conn, $blood_sugar);
        $pain_score = mysqli_real_escape_string($conn, $pain_score);
        $remarks = mysqli_real_escape_string($conn, $remarks);

        // INSERT VITALS
        $insert_sql = "
            INSERT INTO patient_vitals
            (
                patient_id,
                hospital_id,
                nurse_id,
                bp,
                pulse,
                temperature,
                respiration_rate,
                spo2,
                height,
                weight,
                blood_sugar,
                pain_score,
                remarks,
                recorded_at,
                delete_flag
            )
            VALUES
            (
                $patient_id,
                $hospital_id,
                $nurse_id,
                '$bp',
                '$pulse',
                '$temperature',
                '$respiration_rate',
                '$spo2',
                '$height',
                '$weight',
                '$blood_sugar',
                '$pain_score',
                '$remarks',
                NOW(),
                0
            )
        ";

        $insert_result = mysqli_query($conn, $insert_sql);

        if ($insert_result) {
            $success = "Vitals added successfully.";

            // Clear form values
            $bp               = "";
            $pulse            = "";
            $temperature      = "";
            $respiration_rate = "";
            $spo2             = "";
            $height           = "";
            $weight           = "";
            $blood_sugar      = "";
            $pain_score       = "";
            $remarks          = "";

            // Keep selected patient
            $selected_patient_id = $patient_id;
        } else {
            $error = "Failed to add vitals: " . mysqli_error($conn);
        }
    }
}


// ============================================================
// GET SELECTED PATIENT DETAILS
// ============================================================

$selected_patient = null;

if ($selected_patient_id > 0) {

    $selected_sql = "
        SELECT
            patient_id,
            patient_name,
            age,
            gender
        FROM patients
        WHERE patient_id = $selected_patient_id
        AND hospital_id = $hospital_id
        AND (delete_flag IS NULL OR delete_flag = 0)
        LIMIT 1
    ";

    $selected_result = mysqli_query($conn, $selected_sql);

    if ($selected_result && mysqli_num_rows($selected_result) > 0) {
        $selected_patient = mysqli_fetch_assoc($selected_result);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Add Vitals -
        <?php echo htmlspecialchars($hospital['hospital_name'] ?? 'Hospital'); ?>
    </title>


    <link
        rel="icon"
        type="image/png"
        href="../<?php echo htmlspecialchars($hospital['hospital_logo'] ?? ''); ?>"
    >


    <script src="https://cdn.tailwindcss.com"></script>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


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

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            background: white;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .form-input:disabled {
            background: #f9fafb;
            color: #6b7280;
        }

        .form-input.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .form-input.error:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }

        .required {
            color: #ef4444;
        }

        .field-required {
            border-left: 3px solid #ef4444;
            padding-left: 4px;
        }

    </style>

</head>


<body class="bg-gray-50 text-gray-900">


<script>
    lucide.createIcons();
</script>


<div class="flex min-h-screen flex-col bg-gray-50">


    <!-- HEADER -->

    <?php include "../header.php"; ?>


    <div class="flex flex-1 items-start">


        <!-- SIDEBAR -->

        <?php include "../Sidebar.php"; ?>


        <!-- MAIN -->

        <main class="flex-1 overflow-auto p-4 xl:p-6 xl:ml-64">


            <div class="max-w-6xl mx-auto">


                <!-- =====================================================
                     PAGE HEADER
                ====================================================== -->

                <div class="flex items-center gap-4 mb-6">


                    <a
                        href="patients.php"
                        class="back-btn"
                        title="Back"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M19 12H5"></path>
                            <path d="M12 19l-7-7 7-7"></path>
                        </svg>

                    </a>


                    <div>

                        <h1 class="text-2xl lg:text-3xl font-bold">
                            Add Patient Vitals
                        </h1>

                        <p class="text-gray-500 text-sm mt-1">
                            Record patient's vital signs and measurements.
                        </p>

                    </div>

                </div>


                <!-- =====================================================
                     SUCCESS MESSAGE
                ====================================================== -->

                <?php if (!empty($success)): ?>

                    <div
                        class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                    >

                        <div class="flex items-center gap-2">

                            <i
                                data-lucide="check-circle"
                                class="w-5 h-5"
                            ></i>

                            <span>
                                <?php echo htmlspecialchars($success); ?>
                            </span>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                     ERROR MESSAGE
                ====================================================== -->

                <?php if (!empty($error)): ?>

                    <div
                        class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >

                        <div class="flex items-center gap-2">

                            <i
                                data-lucide="alert-circle"
                                class="w-5 h-5"
                            ></i>

                            <span>
                                <?php echo htmlspecialchars($error); ?>
                            </span>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                     FORM
                ====================================================== -->

                <form
                    method="POST"
                    action=""
                    id="vitalsForm"
                >


                    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">


                        <!-- =================================================
                             PATIENT SECTION
                        ================================================== -->

                        <div class="px-6 py-5 border-b bg-gray-50">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">

                                    <i
                                        data-lucide="user"
                                        class="w-5 h-5"
                                    ></i>

                                </div>

                                <div>

                                    <h2 class="font-semibold text-gray-900">
                                        Patient Information
                                    </h2>

                                    <p class="text-xs text-gray-500">
                                        Select the patient for these vitals.
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="p-6">


                            <!-- PATIENT DROPDOWN -->

                            <div class="max-w-xl">

                                <label
                                    for="patient_id"
                                    class="form-label"
                                >

                                    Patient
                                    <span class="required">*</span>

                                </label>


                                <select
                                    name="patient_id"
                                    id="patient_id"
                                    class="form-input <?php echo isset($errors['patient_id']) ? 'error' : ''; ?>"
                                    required
                                    <?php echo ($url_patient_id > 0 && $selected_patient) ? 'disabled' : ''; ?>
                                >

                                    <option value="">
                                        -- Select Patient --
                                    </option>


                                    <?php foreach ($patients as $patient): ?>

                                        <option
                                            value="<?php echo (int) $patient['patient_id']; ?>"
                                            <?php
                                            echo (
                                                $selected_patient_id ==
                                                $patient['patient_id']
                                            )
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $patient['patient_name']
                                            );
                                            ?>

                                            -

                                            <?php
                                            echo htmlspecialchars(
                                                $patient['gender']
                                            );
                                            ?>

                                            -

                                            Age:
                                            <?php
                                            echo htmlspecialchars(
                                                $patient['age']
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <?php if (isset($errors['patient_id'])): ?>
                                    <span class="error-message">
                                        <?php echo htmlspecialchars($errors['patient_id']); ?>
                                    </span>
                                <?php endif; ?>


                                <?php
                                /*
                                 * When disabled, a disabled select
                                 * does NOT submit its value.
                                 *
                                 * Therefore we add hidden patient_id.
                                 */
                                ?>

                                <?php if ($url_patient_id > 0 && $selected_patient): ?>

                                    <input
                                        type="hidden"
                                        name="patient_id"
                                        value="<?php echo (int) $selected_patient_id; ?>"
                                    >

                                    <p class="mt-2 text-xs text-blue-600">

                                        Patient automatically selected from
                                        patient profile.

                                    </p>

                                <?php endif; ?>


                            </div>


                            <!-- SELECTED PATIENT CARD -->

                            <?php if ($selected_patient): ?>

                                <div class="mt-5 max-w-xl rounded-lg border border-blue-200 bg-blue-50 p-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">

                                            <?php
                                            echo strtoupper(
                                                substr(
                                                    $selected_patient['patient_name'],
                                                    0,
                                                    2
                                                )
                                            );
                                            ?>

                                        </div>


                                        <div>

                                            <p class="font-semibold text-gray-900">

                                                <?php
                                                echo htmlspecialchars(
                                                    $selected_patient['patient_name']
                                                );
                                                ?>

                                            </p>


                                            <p class="text-xs text-gray-500">

                                                Age:
                                                <?php
                                                echo htmlspecialchars(
                                                    $selected_patient['age']
                                                );
                                                ?>

                                                &nbsp; | &nbsp;

                                                Gender:
                                                <?php
                                                echo htmlspecialchars(
                                                    $selected_patient['gender']
                                                );
                                                ?>

                                            </p>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             VITALS SECTION
                        ================================================== -->

                        <div class="px-6 py-5 border-y bg-gray-50">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">

                                    <i
                                        data-lucide="activity"
                                        class="w-5 h-5"
                                    ></i>

                                </div>

                                <div>

                                    <h2 class="font-semibold text-gray-900">
                                        Vital Signs
                                    </h2>

                                    <p class="text-xs text-gray-500">
                                        Enter the patient's current measurements.
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="p-6">


                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">


                                <!-- BP -->

                                <div>

                                    <label
                                        for="bp"
                                        class="form-label"
                                    >
                                        Blood Pressure
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="bp"
                                        name="bp"
                                        class="form-input <?php echo isset($errors['bp']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 120/80"
                                        value="<?php echo htmlspecialchars($bp); ?>"
                                        required
                                        pattern="^[0-9]{2,3}/[0-9]{2,3}$"
                                        title="Format: systolic/diastolic (e.g., 120/80)"
                                    >

                                    <?php if (isset($errors['bp'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['bp']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- PULSE -->

                                <div>

                                    <label
                                        for="pulse"
                                        class="form-label"
                                    >
                                        Pulse
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="pulse"
                                        name="pulse"
                                        class="form-input <?php echo isset($errors['pulse']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 78"
                                        value="<?php echo htmlspecialchars($pulse); ?>"
                                        required
                                        min="30"
                                        max="220"
                                        step="1"
                                        oninput="if(this.value.length > 3) this.value = this.value.slice(0, 3)"
                                    >

                                    <?php if (isset($errors['pulse'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['pulse']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- TEMPERATURE -->

                                <div>

                                    <label
                                        for="temperature"
                                        class="form-label"
                                    >
                                        Temperature (°C)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="temperature"
                                        name="temperature"
                                        class="form-input <?php echo isset($errors['temperature']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 98.6"
                                        value="<?php echo htmlspecialchars($temperature); ?>"
                                        required
                                        min="32"
                                        max="43"
                                        step="0.1"
                                    >

                                    <?php if (isset($errors['temperature'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['temperature']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- RESPIRATION RATE -->

                                <div>

                                    <label
                                        for="respiration_rate"
                                        class="form-label"
                                    >
                                        Respiration Rate
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="respiration_rate"
                                        name="respiration_rate"
                                        class="form-input <?php echo isset($errors['respiration_rate']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 16"
                                        value="<?php echo htmlspecialchars($respiration_rate); ?>"
                                        required
                                        min="5"
                                        max="60"
                                        step="1"
                                    >

                                    <?php if (isset($errors['respiration_rate'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['respiration_rate']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- SPO2 -->

                                <div>

                                    <label
                                        for="spo2"
                                        class="form-label"
                                    >
                                        SpO2 (%)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="spo2"
                                        name="spo2"
                                        class="form-input <?php echo isset($errors['spo2']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 99"
                                        value="<?php echo htmlspecialchars($spo2); ?>"
                                        required
                                        min="70"
                                        max="100"
                                        step="1"
                                    >

                                    <?php if (isset($errors['spo2'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['spo2']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- HEIGHT -->

                                <div>

                                    <label
                                        for="height"
                                        class="form-label"
                                    >
                                        Height (cm)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="height"
                                        name="height"
                                        class="form-input <?php echo isset($errors['height']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 170.50"
                                        value="<?php echo htmlspecialchars($height); ?>"
                                        required
                                        min="50"
                                        max="280"
                                        step="0.01"
                                    >

                                    <?php if (isset($errors['height'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['height']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- WEIGHT -->

                                <div>

                                    <label
                                        for="weight"
                                        class="form-label"
                                    >
                                        Weight (kg)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="weight"
                                        name="weight"
                                        class="form-input <?php echo isset($errors['weight']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 68.20"
                                        value="<?php echo htmlspecialchars($weight); ?>"
                                        required
                                        min="1"
                                        max="400"
                                        step="0.01"
                                    >

                                    <?php if (isset($errors['weight'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['weight']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- BLOOD SUGAR -->

                                <div>

                                    <label
                                        for="blood_sugar"
                                        class="form-label"
                                    >
                                        Blood Sugar (mg/dL)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="blood_sugar"
                                        name="blood_sugar"
                                        class="form-input <?php echo isset($errors['blood_sugar']) ? 'error' : ''; ?>"
                                        placeholder="e.g. 99"
                                        value="<?php echo htmlspecialchars($blood_sugar); ?>"
                                        required
                                        min="10"
                                        max="600"
                                        step="1"
                                    >

                                    <?php if (isset($errors['blood_sugar'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['blood_sugar']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- PAIN SCORE -->

                                <div>

                                    <label
                                        for="pain_score"
                                        class="form-label"
                                    >
                                        Pain Score (0-10)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="pain_score"
                                        name="pain_score"
                                        class="form-input <?php echo isset($errors['pain_score']) ? 'error' : ''; ?>"
                                        placeholder="0 - 10"
                                        value="<?php echo htmlspecialchars($pain_score); ?>"
                                        required
                                        min="0"
                                        max="10"
                                        step="1"
                                    >

                                    <?php if (isset($errors['pain_score'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['pain_score']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                            </div>


                            <!-- REMARKS -->

                            <div class="mt-5">

                                <label
                                    for="remarks"
                                    class="form-label"
                                >
                                    Remarks
                                </label>

                                <textarea
                                    id="remarks"
                                    name="remarks"
                                    rows="4"
                                    class="form-input"
                                    placeholder="Enter any additional observations or remarks..."
                                ><?php echo htmlspecialchars($remarks); ?></textarea>

                            </div>


                        </div>


                        <!-- =================================================
                             FOOTER BUTTONS
                        ================================================== -->

                        <div class="px-6 py-4 border-t bg-gray-50 flex flex-col sm:flex-row justify-end gap-3">


                            <a
                                href="../patients.php"
                                class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm text-center hover:bg-gray-100 transition"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 transition flex items-center justify-center gap-2"
                            >

                                <i
                                    data-lucide="save"
                                    class="w-4 h-4"
                                ></i>

                                Save Vitals

                            </button>


                        </div>


                    </div>


                </form>


            </div>


        </main>


    </div>


</div>


<script>

    lucide.createIcons();

    // Client-side validation
    document.getElementById('vitalsForm').addEventListener('submit', function(e) {
        let isValid = true;
        const errors = {};

        // Validate patient selection
        const patientSelect = document.getElementById('patient_id');
        if (patientSelect && patientSelect.value === '') {
            errors.patient_id = 'Please select a patient.';
            isValid = false;
        }

        // Validate BP
        const bp = document.getElementById('bp');
        const bpPattern = /^[0-9]{2,3}\/[0-9]{2,3}$/;
        if (bp && !bpPattern.test(bp.value)) {
            errors.bp = 'Invalid format. Use systolic/diastolic (e.g., 120/80)';
            isValid = false;
        }

        // Validate Pulse
        const pulse = document.getElementById('pulse');
        if (pulse && (pulse.value < 30 || pulse.value > 220)) {
            errors.pulse = 'Pulse must be between 30-220 BPM';
            isValid = false;
        }

        // Validate Temperature
        const temp = document.getElementById('temperature');
        if (temp && (temp.value < 32 || temp.value > 43)) {
            errors.temperature = 'Temperature must be between 32-43 °C';
            isValid = false;
        }

        // Validate Respiration Rate
        const rr = document.getElementById('respiration_rate');
        if (rr && (rr.value < 5 || rr.value > 60)) {
            errors.respiration_rate = 'Respiration rate must be between 5-60 breaths/min';
            isValid = false;
        }

        // Validate SpO2
        const spo2 = document.getElementById('spo2');
        if (spo2 && (spo2.value < 70 || spo2.value > 100)) {
            errors.spo2 = 'SpO2 must be between 70-100%';
            isValid = false;
        }

        // Validate Height
        const height = document.getElementById('height');
        if (height && (height.value < 50 || height.value > 280)) {
            errors.height = 'Height must be between 50-280 cm';
            isValid = false;
        }

        // Validate Weight
        const weight = document.getElementById('weight');
        if (weight && (weight.value < 1 || weight.value > 400)) {
            errors.weight = 'Weight must be between 1-400 kg';
            isValid = false;
        }

        // Validate Blood Sugar
        const sugar = document.getElementById('blood_sugar');
        if (sugar && (sugar.value < 10 || sugar.value > 600)) {
            errors.blood_sugar = 'Blood sugar must be between 10-600 mg/dL';
            isValid = false;
        }

        // Validate Pain Score
        const pain = document.getElementById('pain_score');
        if (pain && (pain.value < 0 || pain.value > 10)) {
            errors.pain_score = 'Pain score must be between 0-10';
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Show errors in a summary or alert
            let errorMsg = 'Please fix the following errors:\n\n';
            for (const [field, msg] of Object.entries(errors)) {
                errorMsg += `• ${msg}\n`;
            }
            alert(errorMsg);
        }
    });

</script>


</body>

</html>