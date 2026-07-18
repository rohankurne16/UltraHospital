<?php
session_start();

// Set toast messages for testing
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'success') {
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Data Saved Successfully!'
        ];
    } elseif ($_GET['action'] == 'error') {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Something went wrong!'
        ];
    } elseif ($_GET['action'] == 'warning') {
        $_SESSION['toast'] = [
            'type' => 'warning',
            'message' => 'Please fill all required fields!'
        ];
    } elseif ($_GET['action'] == 'info') {
        $_SESSION['toast'] = [
            'type' => 'info',
            'message' => 'Operation completed successfully!'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toastify Test - Hospital Management</title>
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 5px;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        h1 {
            color: #1f2937;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 20px;
        }
        .toast-demo {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #3b82f6;
        }
        .toast-demo code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Toastify Notifications</h1>
        <p class="subtitle">Test different toast notifications</p>

        <?php if(isset($_SESSION['toast'])): ?>
            <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; border-left: 4px solid #22c55e; margin-bottom: 20px;">
                <strong>Session Toast Set:</strong> 
                <?= $_SESSION['toast']['message']; ?>
                (<?= $_SESSION['toast']['type']; ?>)
            </div>
        <?php endif; ?>

        <div class="btn-group">
            <a href="?action=success" class="btn btn-success">Success Toast</a>
            <a href="?action=error" class="btn btn-danger">Error Toast</a>
            <a href="?action=warning" class="btn btn-warning">Warning Toast</a>
            <a href="?action=info" class="btn btn-info">Info Toast</a>
            <a href="?" class="btn btn-primary">Clear Session</a>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <h3 style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 10px;">🔥 Direct Toast (without session)</h3>
            <div class="btn-group">
                <button onclick="showToast('success', 'Data Saved Successfully!')" class="btn btn-success">Direct Success</button>
                <button onclick="showToast('error', 'Something went wrong!')" class="btn btn-danger">Direct Error</button>
                <button onclick="showToast('warning', 'Please fill all required fields!')" class="btn btn-warning">Direct Warning</button>
                <button onclick="showToast('info', 'Operation completed!')" class="btn btn-info">Direct Info</button>
            </div>
        </div>

        <div class="toast-demo">
            <strong>📌 Session Toast Code:</strong>
            <pre style="background: #1f2937; color: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 8px; overflow-x: auto; font-size: 12px;">
&lt;?php 
session_start(); 
$_SESSION['toast'] = [ 
    'type' => 'success', 
    'message' => 'Record saved successfully!' 
]; 
header("Location: index.php"); 
exit; 
?&gt;

&lt;?php if(isset($_SESSION['toast'])): ?&gt;
&lt;script&gt;
Toastify({ 
    text: "&lt;?= $_SESSION['toast']['message']; ?&gt;", 
    duration: 3000, 
    gravity: "top", 
    position: "right", 
    close: true, 
    style: { 
        background: "&lt;?= $_SESSION['toast']['type']=='success' ? '#28a745' : '#dc3545' ?&gt;" 
    } 
}).showToast(); 
&lt;/script&gt;
&lt;?php unset($_SESSION['toast']); endif; ?&gt;
            </pre>
        </div>
    </div>

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <script>
        // Function to show toast directly
        function showToast(type, message) {
            let colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            
            let textColor = type === 'warning' ? '#000' : '#fff';
            
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                close: true,
                stopOnFocus: true,
                style: {
                    background: colors[type] || '#28a745',
                    color: textColor
                }
            }).showToast();
        }

        // Auto show toast from session
        <?php if(isset($_SESSION['toast'])): ?>
            (function() {
                let type = "<?= $_SESSION['toast']['type']; ?>";
                let message = "<?= $_SESSION['toast']['message']; ?>";
                let colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    warning: '#ffc107',
                    info: '#17a2b8'
                };
                let textColor = type === 'warning' ? '#000' : '#fff';
                
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    close: true,
                    stopOnFocus: true,
                    style: {
                        background: colors[type] || '#28a745',
                        color: textColor
                    }
                }).showToast();
            })();
        <?php unset($_SESSION['toast']); endif; ?>
    </script>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>