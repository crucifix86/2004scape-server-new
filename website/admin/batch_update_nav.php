<?php
// Script to update all admin pages to use shared navigation

$filesToUpdate = [
    'settings.php',
    'players.php',
    'bans.php',
    'reports.php',
    'chat.php',
    'mod_logs.php'
];

foreach ($filesToUpdate as $file) {
    if (!file_exists($file)) {
        echo "❌ $file not found\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Backup original
    $backupFile = $file . '.backup_' . date('YmdHis');
    file_put_contents($backupFile, $content);
    
    // Check if already updated
    if (strpos($content, 'includes/nav.php') !== false) {
        echo "✅ $file already updated\n";
        continue;
    }
    
    // Find where the PHP logic ends and HTML begins
    $htmlStart = strpos($content, '<!DOCTYPE');
    if ($htmlStart === false) {
        echo "❌ $file - Could not find HTML start\n";
        continue;
    }
    
    $phpCode = substr($content, 0, $htmlStart);
    
    // Create new HTML structure
    $newHtml = '<!DOCTYPE html>
<html>
<head>
    <title>' . getPageTitle($file) . ' - Admin Panel</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php require_once \'includes/nav.php\'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>' . getPageHeading($file) . '</h1>
            </div>
            
            <!-- Page specific content will go here -->
            <div class="card">
                <p>This page needs to be updated with its specific content.</p>
            </div>
        </div>
    </div>
</body>
</html>';
    
    // Combine PHP code with new HTML
    $newContent = $phpCode . $newHtml;
    
    // Write updated file
    file_put_contents($file, $newContent);
    echo "✅ $file updated successfully (backup: $backupFile)\n";
}

function getPageTitle($file) {
    $titles = [
        'settings.php' => 'Game Settings',
        'players.php' => 'Player Management',
        'bans.php' => 'Bans & Mutes',
        'reports.php' => 'Reports',
        'chat.php' => 'Chat Logs',
        'mod_logs.php' => 'Mod Actions'
    ];
    return $titles[$file] ?? 'Admin';
}

function getPageHeading($file) {
    $headings = [
        'settings.php' => 'Game Settings',
        'players.php' => 'Player Management',
        'bans.php' => 'Bans & Mutes Management',
        'reports.php' => 'Player Reports',
        'chat.php' => 'Chat Logs',
        'mod_logs.php' => 'Moderator Actions'
    ];
    return $headings[$file] ?? 'Admin Panel';
}

echo "\n\nTo restore original files, use the backup files created.\n";
?>