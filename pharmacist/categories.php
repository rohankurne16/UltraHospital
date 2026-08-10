<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Categories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .category-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .category-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .category-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <div class="flex">
        <?php include '../Sidebar.php'; ?>
        <main class="flex-1 overflow-x-hidden duration-300 p-4 xl:p-8 xl:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-gray-900">Medicine Categories</h1><p class="text-gray-500 text-sm">Manage medicine categories</p></div>
                <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold shadow-lg shadow-blue-500/20">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="category-card">
                    <div class="flex items-center gap-4">
                        <div class="category-icon bg-blue-50 text-blue-600"><i class="fas fa-capsules text-xl"></i></div>
                        <div><h3 class="font-semibold text-gray-800">Antibiotics</h3><p class="text-xs text-gray-500">24 medicines</p></div>
                    </div>
                    <div class="mt-3 flex gap-2"><span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Active</span></div>
                    <div class="mt-3 flex gap-2"><button class="text-blue-600 text-sm hover:text-blue-700"><i class="fas fa-edit"></i></button><button class="text-red-600 text-sm hover:text-red-700"><i class="fas fa-trash"></i></button></div>
                </div>
                <div class="category-card">
                    <div class="flex items-center gap-4">
                        <div class="category-icon bg-red-50 text-red-600"><i class="fas fa-tablets text-xl"></i></div>
                        <div><h3 class="font-semibold text-gray-800">Pain Relief</h3><p class="text-xs text-gray-500">18 medicines</p></div>
                    </div>
                    <div class="mt-3 flex gap-2"><span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Active</span></div>
                    <div class="mt-3 flex gap-2"><button class="text-blue-600 text-sm hover:text-blue-700"><i class="fas fa-edit"></i></button><button class="text-red-600 text-sm hover:text-red-700"><i class="fas fa-trash"></i></button></div>
                </div>
                <div class="category-card">
                    <div class="flex items-center gap-4">
                        <div class="category-icon bg-green-50 text-green-600"><i class="fas fa-leaf text-xl"></i></div>
                        <div><h3 class="font-semibold text-gray-800">Vitamins</h3><p class="text-xs text-gray-500">15 medicines</p></div>
                    </div>
                    <div class="mt-3 flex gap-2"><span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Active</span></div>
                    <div class="mt-3 flex gap-2"><button class="text-blue-600 text-sm hover:text-blue-700"><i class="fas fa-edit"></i></button><button class="text-red-600 text-sm hover:text-red-700"><i class="fas fa-trash"></i></button></div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>