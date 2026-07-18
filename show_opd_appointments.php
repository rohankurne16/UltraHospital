<?php
session_start();
include 'config/hospital.php';

include 'config/permission_check.php';
    checkPermission('appointment-view'); 

$conn->set_charset("utf8");

$opd_ipd_type_filter = 'OPD';
$appointments = [];

$sql = "SELECT
            a.appointment_id,
            a.appointment_no,
            p.patient_name,
            d.doctor_name,
            a.department,
            a.appointment_type,
            a.opd_ipd_type,
            a.appointment_date,
            a.appointment_time,
            a.status
        FROM
            appointments a
        JOIN
            patients p ON a.patient_id = p.patient_id
        JOIN
            doctor d ON a.doctor_id = d.doctor_id
        WHERE
            a.opd_ipd_type = ? AND (a.delete_flag = 0 OR a.delete_flag IS NULL)
        ORDER BY
            a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $opd_ipd_type_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    

      <title><?php echo $hospital['hospital_name'] ?>-OPD Appointments</title>
    <link rel="icon" type="image/png" href="<?php echo $hospital['hospital_logo'] ?>">
    
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
            padding: 20px 28px;
            min-height: 100vh;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow-x: auto;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f8fafc;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        td {
            font-size: 14px;
            color: #334155;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .status-scheduled {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-completed {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-right: 4px;
        }
        .action-btn.view {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        .action-btn.view:hover {
            background-color: #bae6fd;
        }
        .action-btn.edit {
            background-color: #fff7ed;
            color: #ea580c;
        }
        .action-btn.edit:hover {
            background-color: #fed7aa;
        }
        .action-btn.delete {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .action-btn.delete:hover {
            background-color: #fecaca;
        }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>

        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>

            <main class="main-content">
                <div class="max-w-6xl mx-auto w-full">
                    <div class="mb-6 flex items-center gap-4">
                        <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-100 size-10 transition-colors shadow-sm">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">OPD Appointments</h1>
                            <p class="text-gray-500">View all Outpatient Department appointments.</p>
                        </div>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                                    

                                <tr>
                                    <th>Appointment No</th>
                                    <th>Patient Name</th>
                                    <th>Doctor Name</th>
                                    <th>Department</th>
                                    <th>Type</th>
                                    <th>OPD/IPD</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)) { ?>
    <tr>
        <td colspan="10" class="text-center py-4 text-gray-500">
            No OPD appointments found.
        </td>
    </tr>
<?php } else { ?>

    <?php foreach ($appointments as $appointment) { ?>

        <tr style="cursor:pointer;"
            onclick="window.location.href='view_appointment.php?id=<?php echo $appointment['appointment_id']; ?>'">

            <td><?php echo htmlspecialchars($appointment['appointment_no']); ?></td>

            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>

            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>

            <td><?php echo htmlspecialchars($appointment['department']); ?></td>

            <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>

            <td><?php echo htmlspecialchars($appointment['opd_ipd_type']); ?></td>

            <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>

            <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>

            <td>
                <?php
                $statusClass = '';

                switch ($appointment['status']) {
                    case 'Scheduled':
                        $statusClass = 'status-scheduled';
                        break;
                    case 'Confirmed':
                        $statusClass = 'status-confirmed';
                        break;
                    case 'Completed':
                        $statusClass = 'status-completed';
                        break;
                    case 'Cancelled':
                        $statusClass = 'status-cancelled';
                        break;
                }
                ?>

                <span class="<?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($appointment['status']); ?>
                </span>
                            </td>

                            <td onclick="event.stopPropagation();">

                                <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id']; ?>"
                                class="action-btn edit"
                                title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>

                                <a href="delete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>"
                                class="action-btn delete"
                                title="Delete"
                                onclick="return confirm('Are you sure you want to delete this appointment?');">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>
                            </tbody>
                        </table>
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
