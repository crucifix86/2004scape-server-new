<?php
session_start();
require_once 'check_admin.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    $backupFile = $_POST['backup_file'] ?? '';
    $targetFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
    
    if (file_exists($backupFile)) {
        // Read backup content
        $content = file_get_contents($backupFile);
        
        // Write using temp file approach
        $tempFile = $targetFile . '.restore_' . uniqid();
        if (file_put_contents($tempFile, $content) !== false) {
            if (rename($tempFile, $targetFile) || copy($tempFile, $targetFile)) {
                unlink($tempFile);
                $message = "Successfully restored from backup!";
                $messageType = 'success';
            } else {
                unlink($tempFile);
                $message = "Failed to restore - permission issue";
                $messageType = 'error';
            }
        }
    }
}

// List available backups
$backups = glob('/home/crucifix/Server/data/players/main/crucifix.sav.backup_*');
rsort($backups); // Newest first
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Save Restore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #ffcc00;
        }
        .backup-list {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .backup-item {
            background: #333;
            padding: 15px;
            margin: 10px 0;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .restore-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success {
            background: #4CAF50;
        }
        .error {
            background: #f44336;
        }
        .info {
            font-size: 12px;
            color: #888;
        }
        .manual-fix {
            background: #ff9800;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        code {
            background: #333;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Emergency Save File Restore</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="manual-fix">
            <strong>Manual Fix Option:</strong><br>
            Run this command in your terminal to fix permissions:<br>
            <code>sudo cp /home/crucifix/Server/data/players/main/crucifix.sav.backup_1754026820 /home/crucifix/Server/data/players/main/crucifix.sav && sudo chmod 666 /home/crucifix/Server/data/players/main/crucifix.sav</code>
        </div>
        
        <div class="backup-list">
            <h2>Available Backups</h2>
            <?php foreach ($backups as $backup): ?>
                <?php 
                $timestamp = str_replace('/home/crucifix/Server/data/players/main/crucifix.sav.backup_', '', $backup);
                $date = date('Y-m-d H:i:s', $timestamp);
                $size = filesize($backup);
                ?>
                <div class="backup-item">
                    <div>
                        <strong><?php echo $date; ?></strong><br>
                        <span class="info">Size: <?php echo $size; ?> bytes</span>
                    </div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup); ?>">
                        <button type="submit" name="restore" class="restore-btn">Restore This Backup</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p><a href="player_stats.php" style="color: #ffcc00;">Back to Player Stats</a></p>
    </div>
</body>
</html>