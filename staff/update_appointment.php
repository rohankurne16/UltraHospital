<?php 
session_start(); 
include '../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../../appointments_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch appointment details
$sql = "SELECT * FROM appointments WHERE appointment_id = '$id' AND (delete_flag=0 OR delete_flag IS NULL)";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    header("Location: ../../appointments_list.php");
    exit();
}
$appointment = $result->fetch_assoc();

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $doctor_name = mysqli_real_escape_string($conn, $_POST['doctor_name']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $appointment_type = mysqli_real_escape_string($conn, $_POST['appointment_type']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $updateQuery = "UPDATE appointments SET 
                    patient_name='$patient_name', doctor_name='$doctor_name', department='$department',
                    appointment_type='$appointment_type', appointment_date='$appointment_date',
                    appointment_time='$appointment_time', duration='$duration', reason='$reason',
                    notes='$note', status='$status'
                    WHERE appointment_id='$id'";

    if ($conn->query($updateQuery) === TRUE) {
        echo "<script>
            alert('Appointment updated successfully!');
            window.location.href='appointments_list.php';
        </script>";
        exit();
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Update Appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
        }
        .main-content { 
            margin-left: 260px; 
            padding: 30px; 
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            width: 100%;
        }
        .form-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }
        .form-card { 
            background: white; 
            border-radius: 16px; 
            border: 1px solid #e5e7eb; 
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            width: 100%;
        }
        .form-card .header { 
            padding: 24px 32px; 
            border-bottom: 1px solid #e5e7eb; 
            background: #f8fafc;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .form-card .header .header-icon {
            width: 44px;
            height: 44px;
            background: #eff6ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
        }
        .form-card .header .header-title {
            flex: 1;
        }
        .form-card .header h3 { 
            font-size: 20px; 
            font-weight: 700; 
            color: #0f172a; 
            margin: 0;
        }
        .form-card .header .subtitle {
            font-size: 14px;
            color: #64748b;
            font-weight: 400;
        }
        .form-card .body { 
            padding: 32px 36px; 
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            font-size: 14px; 
            font-weight: 600; 
            color: #0f172a; 
            margin-bottom: 6px; 
        }
        .form-group label .required { 
            color: #ef4444; 
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 15px; 
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
        .form-group input[readonly] { 
            background: #f1f5f9; 
            cursor: not-allowed;
            color: #475569;
        }
        .form-group textarea { 
            resize: vertical; 
            min-height: 80px; 
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .alert { 
            padding: 14px 20px; 
            border-radius: 10px; 
            margin-bottom: 24px; 
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
            padding: 12px 32px; 
            background: #3b82f6; 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            font-size: 15px;
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
            padding: 12px 28px; 
            background: #f1f5f9; 
            color: #475569; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 10px; 
            font-weight: 600; 
            font-size: 15px;
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
            gap: 14px;
            flex-wrap: wrap;
            padding-top: 24px;
            border-top: 1.5px solid #e5e7eb;
            margin-top: 8px;
        }
        .status-badge {
            padding: 6px 18px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .status-confirmed { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        .confirm-btn {
            background: #22c55e;
        }
        .confirm-btn:hover {
            background: #16a34a;
        }

        @media (max-width: 1024px) { 
            .main-content { 
                margin-left: 0; 
                padding: 20px; 
            }
            .form-card .body { 
                padding: 24px 28px; 
            }
        }
        @media (max-width: 768px) { 
            .form-card .header { 
                padding: 20px; 
            }
            .form-card .body { 
                padding: 20px; 
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .btn-actions {
                flex-direction: column;
            }
            .btn-actions a, .btn-actions button {
                width: 100%;
                justify-content: center;
            }
            .form-group input, 
            .form-group select, 
            .form-group textarea { 
                padding: 11px 14px; 
                font-size: 14px; 
            }
        }
        @media (max-width: 480px) {
            .form-card .body { 
                padding: 16px; 
            }
            .form-card .header h3 { 
                font-size: 18px; 
            }
        }
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include '../staff_header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include '../staff_sidebar.php'; ?>
            <main class="main-content">
                <div class="form-container">
                    <!-- Back Link -->
                    <a href="../../appointments_list.php" class="back-link">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Back to Appointments
                    </a>

                    <!-- Form Card -->
                    <div class="form-card fade-in">
                        <div class="header">
                            <div class="header-icon">
                                <i data-lucide="calendar" class="w-5 h-5"></i>
                            </div>
                            <div class="header-title">
                                <h3>Update Appointment</h3>
                                <div class="subtitle">Modify appointment details for #<?php echo htmlspecialchars($appointment['appointment_no']); ?></div>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo strtolower($appointment['status'] ?? 'scheduled'); ?>">
                                    <?php echo htmlspecialchars($appointment['status'] ?? 'Scheduled'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="body">
                            <?php if (!empty($message)): ?>
                                <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                                    <span><?php echo $message; ?></span>
                                </div>
                            <?php endif; ?>

                            <form action="update_appointment.php?id=<?php echo $id; ?>" method="POST">
                                <!-- Appointment No and Type -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Appointment No</label>
                                        <input type="text" value="<?php echo htmlspecialchars($appointment['appointment_no']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="appointment_type">Visit Type <span class="required">*</span></label>
                                        <select id="appointment_type" name="appointment_type" required>
                                            <option value="Consultation" <?php echo ($appointment['appointment_type'] == 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                                            <option value="Follow-up" <?php echo ($appointment['appointment_type'] == 'Follow-up') ? 'selected' : ''; ?>>Follow-up</option>
                                            <option value="Procedure" <?php echo ($appointment['appointment_type'] == 'Procedure') ? 'selected' : ''; ?>>Procedure</option>
                                            <option value="Check-up" <?php echo ($appointment['appointment_type'] == 'Check-up') ? 'selected' : ''; ?>>Check-up</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Date and Time -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="appointment_date">Date <span class="required">*</span></label>
                                        <input type="date" id="appointment_date" name="appointment_date" 
                                               value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="appointment_time">Time Slot <span class="required">*</span></label>
                                        <select id="appointment_time" name="appointment_time" required>
                                            <option value="09:00 AM" <?php echo ($appointment['appointment_time'] == '09:00 AM') ? 'selected' : ''; ?>>09:00 AM</option>
                                            <option value="09:30 AM" <?php echo ($appointment['appointment_time'] == '09:30 AM') ? 'selected' : ''; ?>>09:30 AM</option>
                                            <option value="10:00 AM" <?php echo ($appointment['appointment_time'] == '10:00 AM') ? 'selected' : ''; ?>>10:00 AM</option>
                                            <option value="10:30 AM" <?php echo ($appointment['appointment_time'] == '10:30 AM') ? 'selected' : ''; ?>>10:30 AM</option>
                                            <option value="11:00 AM" <?php echo ($appointment['appointment_time'] == '11:00 AM') ? 'selected' : ''; ?>>11:00 AM</option>
                                            <option value="11:30 AM" <?php echo ($appointment['appointment_time'] == '11:30 AM') ? 'selected' : ''; ?>>11:30 AM</option>
                                            <option value="02:00 PM" <?php echo ($appointment['appointment_time'] == '02:00 PM') ? 'selected' : ''; ?>>02:00 PM</option>
                                            <option value="02:30 PM" <?php echo ($appointment['appointment_time'] == '02:30 PM') ? 'selected' : ''; ?>>02:30 PM</option>
                                            <option value="03:00 PM" <?php echo ($appointment['appointment_time'] == '03:00 PM') ? 'selected' : ''; ?>>03:00 PM</option>
                                            <option value="03:30 PM" <?php echo ($appointment['appointment_time'] == '03:30 PM') ? 'selected' : ''; ?>>03:30 PM</option>
                                            <option value="04:00 PM" <?php echo ($appointment['appointment_time'] == '04:00 PM') ? 'selected' : ''; ?>>04:00 PM</option>
                                            <option value="04:30 PM" <?php echo ($appointment['appointment_time'] == '04:30 PM') ? 'selected' : ''; ?>>04:30 PM</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Duration and Department -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="duration">Duration <span class="required">*</span></label>
                                        <select id="duration" name="duration" required>
                                            <option value="15" <?php echo ($appointment['duration'] == '15') ? 'selected' : ''; ?>>15 minutes</option>
                                            <option value="30" <?php echo ($appointment['duration'] == '30') ? 'selected' : ''; ?>>30 minutes</option>
                                            <option value="45" <?php echo ($appointment['duration'] == '45') ? 'selected' : ''; ?>>45 minutes</option>
                                            <option value="60" <?php echo ($appointment['duration'] == '60') ? 'selected' : ''; ?>>60 minutes</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="department">Department <span class="required">*</span></label>
                                        <input type="text" id="department" name="department" 
                                               value="<?php echo htmlspecialchars($appointment['department']); ?>" required>
                                    </div>
                                </div>

                                <!-- Patient and Doctor -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="patient_name">Patient Name <span class="required">*</span></label>
                                        <input type="text" id="patient_name" name="patient_name" 
                                               value="<?php echo htmlspecialchars($appointment['patient_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="doctor_name">Doctor Name <span class="required">*</span></label>
                                        <input type="text" id="doctor_name" name="doctor_name" 
                                               value="<?php echo htmlspecialchars($appointment['doctor_name']); ?>" required>
                                    </div>
                                </div>

                                <!-- Reason and Notes -->
                                <div class="form-group">
                                    <label for="reason">Reason for Visit</label>
                                    <textarea id="reason" name="reason" rows="2"><?php echo htmlspecialchars($appointment['reason']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="note">Additional Notes</label>
                                    <textarea id="note" name="note" rows="2"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                                </div>

                                <!-- Status -->
                                <div class="form-group">
                                    <label for="status">Status <span class="required">*</span></label>
                                    <select id="status" name="status" required>
                                        <option value="Scheduled" <?php echo ($appointment['status'] == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="Confirmed" <?php echo ($appointment['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="Completed" <?php echo ($appointment['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo ($appointment['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="In Progress" <?php echo ($appointment['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div class="btn-actions">
                                    <button type="submit" class="btn-primary">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Update Appointment
                                    </button>
                                    <a href="../../appointments_list.php" class="btn-secondary">
                                        <i data-lucide="list" class="w-4 h-4"></i>
                                        Cancel
                                    </a>
                                    <?php if (strtolower($appointment['status']) != 'completed' && strtolower($appointment['status']) != 'cancelled'): ?>
                              <!--      <a href="confirm_appointment.php?id=<?php echo $id; ?>" 
                                       class="btn-primary confirm-btn"
                                       onclick="return confirm('Are you sure you want to confirm this appointment?')">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        Confirm
                                    </a>
                                    -->
                                    <?php endif; ?>
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
    </script>
</body>
</html>
<?php $conn->close(); ?>