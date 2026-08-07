<?php

session_start();

include "../config/hospital.php";
include "../config/permission.php";

// Nurse must have update permission
// checkPermission('vitals-update');

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

$hospital_id = $_SESSION['hospital_id'] ?? 0;
$nurse_id    = $_SESSION['id'] ?? 0;

if (!$hospital_id || !$nurse_id) {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Vital ID
|--------------------------------------------------------------------------
*/

$vital_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vital_id <= 0) {
    die("Invalid Vital ID.");
}

/*
|--------------------------------------------------------------------------
| Fetch Vital Record
|--------------------------------------------------------------------------
*/

$sql = "SELECT 
            pv.*,
            p.patient_name,
            p.patient_image
        FROM patient_vitals pv
        INNER JOIN patients p 
            ON pv.patient_id = p.patient_id
        WHERE pv.vital_id = '$vital_id'
        AND pv.hospital_id = '$hospital_id'
        AND (pv.delete_flag IS NULL OR pv.delete_flag = 0)
        LIMIT 1";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

if ($result->num_rows == 0) {
    die("Vital record not found.");
}

$vital = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Validation Functions
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Update Vital Record
|--------------------------------------------------------------------------
*/

$success = "";
$error = "";
$errors = []; // Array to store field-specific errors
$form_data = []; // To store form data

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bp              = trim($_POST['bp'] ?? '');
    $pulse           = trim($_POST['pulse'] ?? '');
    $temperature     = trim($_POST['temperature'] ?? '');
    $respiration_rate = trim($_POST['respiration_rate'] ?? '');
    $spo2             = trim($_POST['spo2'] ?? '');
    $height          = trim($_POST['height'] ?? '');
    $weight          = trim($_POST['weight'] ?? '');
    $blood_sugar     = trim($_POST['blood_sugar'] ?? '');
    $pain_score      = trim($_POST['pain_score'] ?? '');
    $remarks         = trim($_POST['remarks'] ?? '');

    // Store form data for re-display
    $form_data = [
        'bp' => $bp,
        'pulse' => $pulse,
        'temperature' => $temperature,
        'respiration_rate' => $respiration_rate,
        'spo2' => $spo2,
        'height' => $height,
        'weight' => $weight,
        'blood_sugar' => $blood_sugar,
        'pain_score' => $pain_score,
        'remarks' => $remarks
    ];

    /*
    |--------------------------------------------------------------------------
    | Validate all fields
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | If no errors, update the record
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        // Escape values
        $bp               = mysqli_real_escape_string($conn, $bp);
        $pulse            = mysqli_real_escape_string($conn, $pulse);
        $temperature      = mysqli_real_escape_string($conn, $temperature);
        $respiration_rate = mysqli_real_escape_string($conn, $respiration_rate);
        $spo2             = mysqli_real_escape_string($conn, $spo2);
        $height           = mysqli_real_escape_string($conn, $height);
        $weight           = mysqli_real_escape_string($conn, $weight);
        $blood_sugar      = mysqli_real_escape_string($conn, $blood_sugar);
        $pain_score       = mysqli_real_escape_string($conn, $pain_score);
        $remarks          = mysqli_real_escape_string($conn, $remarks);

        $updateSql = "UPDATE patient_vitals SET
                        bp = '$bp',
                        pulse = '$pulse',
                        temperature = '$temperature',
                        respiration_rate = '$respiration_rate',
                        spo2 = '$spo2',
                        height = '$height',
                        weight = '$weight',
                        blood_sugar = '$blood_sugar',
                        pain_score = '$pain_score',
                        remarks = '$remarks',
                        modified_at = NOW()
                      WHERE vital_id = '$vital_id'
                      AND hospital_id = '$hospital_id'
                      AND (delete_flag IS NULL OR delete_flag = 0)";

        if ($conn->query($updateSql)) {

            header("Location: vitals.php?success=updated");
            exit;

        } else {

            $error = "Failed to update vitals: " . $conn->error;
        }

    } else {
        // Update the vital variable with form data to show entered values
        $vital['bp'] = $bp;
        $vital['pulse'] = $pulse;
        $vital['temperature'] = $temperature;
        $vital['respiration_rate'] = $respiration_rate;
        $vital['spo2'] = $spo2;
        $vital['height'] = $height;
        $vital['weight'] = $weight;
        $vital['blood_sugar'] = $blood_sugar;
        $vital['pain_score'] = $pain_score;
        $vital['remarks'] = $remarks;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Update Vitals - <?php echo htmlspecialchars($hospital['hospital_name']); ?></title>

    <link rel="icon"
          type="image/png"
          href="../<?php echo htmlspecialchars($hospital['hospital_logo']); ?>">

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

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .error-summary {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .error-summary-title {
            color: #dc2626;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .error-summary-list {
            color: #991b1b;
            list-style-type: disc;
            padding-left: 20px;
            margin: 0;
        }

        .error-summary-list li {
            margin-bottom: 4px;
        }
    </style>

</head>

<body class="bg-gray-50 text-gray-900">

<script>
    lucide.createIcons();
</script>

<div class="flex min-h-screen flex-col bg-gray-50">

    <!-- Header -->
    <?php include "../header.php"; ?>

    <div class="flex flex-1 items-start">

        <!-- Sidebar -->
        <?php include "../Sidebar.php"; ?>

        <!-- Main -->
        <main class="flex-1 overflow-auto duration-300 p-4 xl:p-6 xl:ml-64">

            <div class="max-w-5xl mx-auto">

                <!-- ================================================= -->
                <!-- HEADER -->
                <!-- ================================================= -->

                <div class="flex items-center gap-4 mb-6">

                    <a href="vitals.php"
                       class="inline-flex items-center justify-center w-10 h-10
                              border border-gray-200 rounded-lg bg-white
                              text-gray-600 hover:bg-gray-100 transition"
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
                            Update Vitals
                        </h1>

                        <p class="text-gray-500 text-sm mt-1">
                            Update patient's vital signs and medical observations.
                            <span class="text-red-500">*</span> Required fields
                        </p>

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- ERROR SUMMARY -->
                <!-- ================================================= -->

                <?php if (!empty($errors)): ?>

                    <div class="error-summary">

                        <div class="error-summary-title">
                            <i data-lucide="alert-circle" class="w-5 h-5 inline"></i>
                            Please fix the following errors:
                        </div>

                        <ul class="error-summary-list">
                            <?php foreach ($errors as $field => $message): ?>
                                <li><?php echo htmlspecialchars($message); ?></li>
                            <?php endforeach; ?>
                        </ul>

                    </div>

                <?php endif; ?>


                <!-- ================================================= -->
                <!-- DATABASE ERROR -->
                <!-- ================================================= -->

                <?php if (!empty($error)): ?>

                    <div class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">

                        <i data-lucide="x-circle" class="w-5 h-5 inline"></i>
                        <?php echo htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <!-- ================================================= -->
                <!-- PATIENT INFORMATION -->
                <!-- ================================================= -->

                <div class="bg-white rounded-xl border shadow-sm overflow-hidden mb-5">

                    <div class="px-6 py-4 border-b bg-gray-50">

                        <h2 class="text-lg font-semibold">
                            <i data-lucide="user" class="w-5 h-5 inline mr-2"></i>
                            Patient Information
                        </h2>

                    </div>

                    <div class="p-6">

                        <div class="flex items-center gap-4">

                            <?php

                            $patient_image = $vital['patient_image'] ?? '';

                            if (!empty($patient_image) &&
                                file_exists("../" . $patient_image)):

                            ?>

                                <img
                                    src="../<?php echo htmlspecialchars($patient_image); ?>"
                                    class="w-14 h-14 rounded-full object-cover border"
                                >

                            <?php else: ?>

                                <div class="w-14 h-14 rounded-full bg-blue-100
                                            flex items-center justify-center
                                            text-blue-600 font-bold text-lg">

                                    <?php
                                    echo strtoupper(
                                        substr($vital['patient_name'], 0, 2)
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>


                            <div>

                                <p class="font-semibold text-gray-900 text-lg">

                                    <?php
                                    echo htmlspecialchars(
                                        $vital['patient_name']
                                    );
                                    ?>

                                </p>

                                <p class="text-sm text-gray-500">

                                    Vital ID:
                                    #<?php echo $vital['vital_id']; ?>
                                    &nbsp;|&nbsp;
                                    Recorded:
                                    <?php echo date('M d, Y h:i A', strtotime($vital['recorded_at'] ?? 'now')); ?>

                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- VITAL FORM -->
                <!-- ================================================= -->

                <form method="POST" id="vitalsForm">

                    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">

                        <div class="px-6 py-4 border-b bg-gray-50">

                            <h2 class="text-lg font-semibold">
                                <i data-lucide="activity" class="w-5 h-5 inline mr-2"></i>
                                Vital Information
                            </h2>

                        </div>


                        <div class="p-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">


                                <!-- Blood Pressure -->

                                <div>

                                    <label class="form-label">
                                        Blood Pressure
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="bp"
                                        id="bp"
                                        value="<?php echo htmlspecialchars($vital['bp'] ?? ''); ?>"
                                        placeholder="e.g. 120/80"
                                        class="form-input <?php echo isset($errors['bp']) ? 'error' : ''; ?>"
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


                                <!-- Pulse -->

                                <div>

                                    <label class="form-label">
                                        Pulse (BPM)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="pulse"
                                        id="pulse"
                                        value="<?php echo htmlspecialchars($vital['pulse'] ?? ''); ?>"
                                        placeholder="e.g. 78"
                                        class="form-input <?php echo isset($errors['pulse']) ? 'error' : ''; ?>"
                                        required
                                        min="30"
                                        max="220"
                                        step="1"
                                    >

                                    <?php if (isset($errors['pulse'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['pulse']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- Temperature -->

                                <div>

                                    <label class="form-label">
                                        Temperature (°C)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="temperature"
                                        id="temperature"
                                        value="<?php echo htmlspecialchars($vital['temperature'] ?? ''); ?>"
                                        placeholder="e.g. 36.5"
                                        class="form-input <?php echo isset($errors['temperature']) ? 'error' : ''; ?>"
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


                                <!-- Respiration Rate -->

                                <div>

                                    <label class="form-label">
                                        Respiration Rate
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="respiration_rate"
                                        id="respiration_rate"
                                        value="<?php echo htmlspecialchars($vital['respiration_rate'] ?? ''); ?>"
                                        placeholder="e.g. 16"
                                        class="form-input <?php echo isset($errors['respiration_rate']) ? 'error' : ''; ?>"
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


                                <!-- SpO2 -->

                                <div>

                                    <label class="form-label">
                                        SpO2 (%)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="spo2"
                                        id="spo2"
                                        value="<?php echo htmlspecialchars($vital['spo2'] ?? ''); ?>"
                                        placeholder="e.g. 99"
                                        class="form-input <?php echo isset($errors['spo2']) ? 'error' : ''; ?>"
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


                                <!-- Height -->

                                <div>

                                    <label class="form-label">
                                        Height (cm)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="height"
                                        id="height"
                                        value="<?php echo htmlspecialchars($vital['height'] ?? ''); ?>"
                                        placeholder="e.g. 170.50"
                                        class="form-input <?php echo isset($errors['height']) ? 'error' : ''; ?>"
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


                                <!-- Weight -->

                                <div>

                                    <label class="form-label">
                                        Weight (kg)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="weight"
                                        id="weight"
                                        value="<?php echo htmlspecialchars($vital['weight'] ?? ''); ?>"
                                        placeholder="e.g. 68.20"
                                        class="form-input <?php echo isset($errors['weight']) ? 'error' : ''; ?>"
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


                                <!-- Blood Sugar -->

                                <div>

                                    <label class="form-label">
                                        Blood Sugar (mg/dL)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="blood_sugar"
                                        id="blood_sugar"
                                        value="<?php echo htmlspecialchars($vital['blood_sugar'] ?? ''); ?>"
                                        placeholder="e.g. 99"
                                        class="form-input <?php echo isset($errors['blood_sugar']) ? 'error' : ''; ?>"
                                        required
                                        min="10"
                                        max="600"
                                        step="0.01"
                                    >

                                    <?php if (isset($errors['blood_sugar'])): ?>
                                        <span class="error-message">
                                            <?php echo htmlspecialchars($errors['blood_sugar']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>


                                <!-- Pain Score -->

                                <div>

                                    <label class="form-label">
                                        Pain Score (0-10)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        min="0"
                                        max="10"
                                        name="pain_score"
                                        id="pain_score"
                                        value="<?php echo htmlspecialchars($vital['pain_score'] ?? ''); ?>"
                                        placeholder="0 - 10"
                                        class="form-input <?php echo isset($errors['pain_score']) ? 'error' : ''; ?>"
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


                                <!-- Remarks -->

                                <div class="md:col-span-2 lg:col-span-3">

                                    <label class="form-label">
                                        Remarks
                                    </label>

                                    <textarea
                                        name="remarks"
                                        id="remarks"
                                        rows="4"
                                        placeholder="Enter any additional observations or remarks..."
                                        class="form-input"
                                    ><?php echo htmlspecialchars($vital['remarks'] ?? ''); ?></textarea>

                                </div>

                            </div>


                            <!-- ================================================= -->
                            <!-- BUTTONS -->
                            <!-- ================================================= -->

                            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-5 border-t">

                                <a
                                    href="vitals.php"
                                    class="px-6 py-3 rounded-lg border border-gray-300
                                           bg-white text-gray-700 font-medium
                                           text-center hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </a>

                                <button
                                    type="submit"
                                    class="px-6 py-3 rounded-lg bg-blue-600
                                           text-white font-medium
                                           hover:bg-blue-700 transition
                                           flex items-center justify-center gap-2"
                                >
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    Update Vitals
                                </button>

                            </div>

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
        const errors = [];

        // Validate BP
        const bp = document.getElementById('bp');
        const bpPattern = /^[0-9]{2,3}\/[0-9]{2,3}$/;
        if (bp && !bpPattern.test(bp.value)) {
            errors.push('Blood Pressure: Invalid format. Use systolic/diastolic (e.g., 120/80)');
            isValid = false;
        }

        // Validate Pulse
        const pulse = document.getElementById('pulse');
        if (pulse && (pulse.value < 30 || pulse.value > 220)) {
            errors.push('Pulse: Must be between 30-220 BPM');
            isValid = false;
        }

        // Validate Temperature
        const temp = document.getElementById('temperature');
        if (temp && (temp.value < 32 || temp.value > 43)) {
            errors.push('Temperature: Must be between 32-43 °C');
            isValid = false;
        }

        // Validate Respiration Rate
        const rr = document.getElementById('respiration_rate');
        if (rr && (rr.value < 5 || rr.value > 60)) {
            errors.push('Respiration Rate: Must be between 5-60 breaths/min');
            isValid = false;
        }

        // Validate SpO2
        const spo2 = document.getElementById('spo2');
        if (spo2 && (spo2.value < 70 || spo2.value > 100)) {
            errors.push('SpO2: Must be between 70-100%');
            isValid = false;
        }

        // Validate Height
        const height = document.getElementById('height');
        if (height && (height.value < 50 || height.value > 280)) {
            errors.push('Height: Must be between 50-280 cm');
            isValid = false;
        }

        // Validate Weight
        const weight = document.getElementById('weight');
        if (weight && (weight.value < 1 || weight.value > 400)) {
            errors.push('Weight: Must be between 1-400 kg');
            isValid = false;
        }

        // Validate Blood Sugar
        const sugar = document.getElementById('blood_sugar');
        if (sugar && (sugar.value < 10 || sugar.value > 600)) {
            errors.push('Blood Sugar: Must be between 10-600 mg/dL');
            isValid = false;
        }

        // Validate Pain Score
        const pain = document.getElementById('pain_score');
        if (pain && (pain.value < 0 || pain.value > 10)) {
            errors.push('Pain Score: Must be between 0-10');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            let errorMsg = 'Please fix the following errors:\n\n';
            errors.forEach(err => {
                errorMsg += `• ${err}\n`;
            });
            alert(errorMsg);
        }
    });
</script>

</body>

</html>