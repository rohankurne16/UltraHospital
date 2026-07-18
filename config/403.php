<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { text-align: center; background: #ffffff; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .icon { font-size: 5rem; color: #ef4444; margin-bottom: 1.5rem; }
        h1 { font-size: 2.5rem; color: #1e293b; margin-bottom: 1rem; }
        p { font-size: 1.1rem; color: #64748b; margin-bottom: 2rem; }
        .btn { display: inline-block; padding: 0.75rem 2rem; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.3); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><i class="fas fa-ban"></i></div>
        <h1>403 - Access Denied</h1>
        <p>You do not have permission to access this page. Please contact your administrator if you believe this is an error.</p>
        <a href="dashboard.php" class="btn"><i class="fas fa-home"></i> Go to Dashboard</a>
    </div>
</body>
</html>
