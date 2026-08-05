
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital_name); ?> - Recruitment Reports</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hospital_logo); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .main-content { width: 100%; margin-left: 0; padding: 20px 28px; min-height: 100vh; }
        @media (max-width: 1024px) { .main-content { padding: 16px; } }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .card-header { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .welcome-section { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .welcome-section h1 { font-size: 24px; font-weight: 700; }
        .welcome-section p { opacity: 0.9; margin-top: 4px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; border-radius: 12px; padding: 16px 20px; border: 1px solid #e5e7eb; text-align: center; }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; }
        .stat-card .stat-label { color: #6b7280; font-size: 13px; margin-top: 2px; }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 1024px) { .grid-2col { grid-template-columns: 1fr; } }
        .chart-bar { background: #f1f5f9; border-radius: 4px; height: 20px; margin: 4px 0; position: relative; }
        .chart-bar .fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #3b82f6, #7c3aed); transition: width 0.6s; }
        .chart-bar .label { display: flex; justify-content: space-between; font-size: 13px; padding: 2px 0; }
        .badge-count { background: #e5e7eb; color: #4b5563; padding: 1px 8px; border-radius: 12px; font-size: 11px; }
    </style>
</head>
<body>

<?php include '../Sidebar.php'; ?>

<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <?php include '../header.php'; ?>
    <div class="flex flex-1 items-start">
        <main class="main-content">
            <div class="welcome-section">
                <div class="flex flex-wrap items-center justify-between">
                    <div>
                        <h1><i class="fas fa-chart-pie mr-3 text-white"></i> Recruitment Reports</h1>
                        <p>Analytics and insights on recruitment activities</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stat-grid">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-number text-blue-600"><?php echo $total_openings; ?></div>
                    <div class="stat-label">Job Openings</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-number text-green-600"><?php echo $total_applicants; ?></div>
                    <div class="stat-label">Total Applicants</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div class="stat-number text-yellow-600"><?php echo $total_selected; ?></div>
                    <div class="stat-label">Selected</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                    <div class="stat-number text-purple-600"><?php echo $total_joined; ?></div>
                    <div class="stat-label">Joined</div>
                </div>
            </div>

            <div class="grid-2col">
                <!-- Status Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tags mr-2 text-blue-500"></i> Candidate Status</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($status_breakdown)): ?>
                            <?php $max = max($status_breakdown); ?>
                            <?php foreach ($status_breakdown as $status => $count): ?>
                                <div class="chart-bar">
                                    <div class="label"><span><?php echo $status; ?></span><span><?php echo $count; ?></span></div>
                                    <div class="fill" style="width: <?php echo ($max > 0) ? ($count / $max * 100) : 0; ?>%;"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No data.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Source Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullseye mr-2 text-blue-500"></i> Source of Candidates</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($source_breakdown)): ?>
                            <?php $max = max($source_breakdown); ?>
                            <?php foreach ($source_breakdown as $source => $count): ?>
                                <div class="chart-bar">
                                    <div class="label"><span><?php echo $source; ?></span><span><?php echo $count; ?></span></div>
                                    <div class="fill" style="width: <?php echo ($max > 0) ? ($count / $max * 100) : 0; ?>%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No data.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Monthly Hires -->
                <div class="card col-span-2">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Monthly Hires (Last 6 Months)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($monthly_hires)): ?>
                            <?php $max = max($monthly_hires); ?>
                            <?php foreach ($monthly_hires as $month => $count): ?>
                                <div class="chart-bar">
                                    <div class="label"><span><?php echo date('M Y', strtotime($month . '-01')); ?></span><span><?php echo $count; ?></span></div>
                                    <div class="fill" style="width: <?php echo ($max > 0) ? ($count / $max * 100) : 0; ?>%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No hires in the last 6 months.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>