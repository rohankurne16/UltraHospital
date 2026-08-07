<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedixPro - Recruitment Reports</title>
    <link rel="icon" type="image/png" href="../documents/hospital/logo.png">
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
        .col-span-2 { grid-column: span 2; }
        @media (max-width: 1024px) { .col-span-2 { grid-column: span 1; } }
    </style>
</head>
<body>

<!-- ========== SIDEBAR ========== -->
<?php include '../Sidebar.php'; ?>

<!-- ========== MAIN CONTENT ========== -->
<div class="flex min-h-screen flex-col bg-gray-50" style="margin-left: 260px;">
    <!-- ========== HEADER ========== -->
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
                    <div class="stat-number text-blue-600">24</div>
                    <div class="stat-label">Job Openings</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #22c55e;">
                    <div class="stat-number text-green-600">187</div>
                    <div class="stat-label">Total Applicants</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div class="stat-number text-yellow-600">42</div>
                    <div class="stat-label">Selected</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                    <div class="stat-number text-purple-600">31</div>
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
                        <div class="chart-bar">
                            <div class="label"><span>Applied</span><span>48</span></div>
                            <div class="fill" style="width: 100%;"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Under Review</span><span>32</span></div>
                            <div class="fill" style="width: 67%;"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Interview</span><span>25</span></div>
                            <div class="fill" style="width: 52%;"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Selected</span><span>42</span></div>
                            <div class="fill" style="width: 88%;"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Rejected</span><span>40</span></div>
                            <div class="fill" style="width: 83%; background: linear-gradient(90deg, #ef4444, #dc2626);"></div>
                        </div>
                    </div>
                </div>

                <!-- Source Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullseye mr-2 text-blue-500"></i> Source of Candidates</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <div class="label"><span>LinkedIn</span><span>62</span></div>
                            <div class="fill" style="width: 100%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Naukri.com</span><span>45</span></div>
                            <div class="fill" style="width: 73%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Company Website</span><span>28</span></div>
                            <div class="fill" style="width: 45%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Referral</span><span>35</span></div>
                            <div class="fill" style="width: 56%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Indeed</span><span>12</span></div>
                            <div class="fill" style="width: 19%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Other</span><span>5</span></div>
                            <div class="fill" style="width: 8%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Hires -->
                <div class="card col-span-2">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Monthly Hires (Last 6 Months)</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <div class="label"><span>Mar 2026</span><span>8</span></div>
                            <div class="fill" style="width: 53%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Apr 2026</span><span>6</span></div>
                            <div class="fill" style="width: 40%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>May 2026</span><span>12</span></div>
                            <div class="fill" style="width: 80%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Jun 2026</span><span>15</span></div>
                            <div class="fill" style="width: 100%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Jul 2026</span><span>10</span></div>
                            <div class="fill" style="width: 67%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                        </div>
                        <div class="chart-bar">
                            <div class="label"><span>Aug 2026</span><span>7</span></div>
                            <div class="fill" style="width: 47%; background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer style="margin-left: 260px; background: white; padding: 16px 28px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 13px; color: #6b7280;">
    &copy; 2026 MedixPro - HR Management System. All rights reserved.
</footer>

<script>
// ========== ANIMATE CHART BARS ON LOAD ==========
document.addEventListener('DOMContentLoaded', function() {
    const chartBars = document.querySelectorAll('.chart-bar .fill');
    chartBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.transition = 'width 0.8s ease';
            bar.style.width = width;
        }, 200 + (index * 100));
    });
});
</script>

</body>
</html>