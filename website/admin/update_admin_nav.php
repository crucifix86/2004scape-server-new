<?php
// Script to show which admin pages need updating
$adminDir = __DIR__;
$adminPages = [
    'index.php',
    'players.php', 
    'settings.php',
    'news.php',
    'bans.php',
    'reports.php',
    'chat.php',
    'mod_logs.php'
];

echo "<h2>Admin Pages Navigation Status</h2>";
echo "<pre>";

foreach ($adminPages as $page) {
    $filePath = $adminDir . '/' . $page;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        // Check if using shared nav
        if (strpos($content, "includes/nav.php") !== false) {
            echo "✅ $page - Using shared navigation\n";
        } else {
            echo "❌ $page - Needs update\n";
            
            // Check what kind of nav it has
            if (strpos($content, '<nav class="nav">') !== false) {
                echo "   - Has old horizontal nav style\n";
            } elseif (strpos($content, '<div class="sidebar">') !== false) {
                echo "   - Has sidebar nav (needs include)\n";
            } else {
                echo "   - Unknown nav style\n";
            }
        }
    } else {
        echo "⚠️  $page - File not found\n";
    }
}

echo "</pre>";

// Show the correct structure
echo "<h3>Correct Structure for Admin Pages:</h3>";
echo "<pre>";
echo htmlspecialchars('<!DOCTYPE html>
<html>
<head>
    <title>Page Title - Admin Panel</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <!-- Any page-specific styles here -->
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php require_once \'includes/nav.php\'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page content here -->
        </div>
    </div>
</body>
</html>');
echo "</pre>";
?>