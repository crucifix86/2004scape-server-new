<?php
session_start();
require_once 'check_admin.php';

// Update settings if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $dbPath = '/home/crucifix/Server/db.sqlite';
    $db = new SQLite3($dbPath);
    
    $requires_restart = false;
    
    foreach ($_POST['settings'] as $key => $value) {
        // Check if this setting requires restart
        $stmt = $db->prepare("SELECT requires_restart FROM game_settings WHERE key = ?");
        $stmt->bindValue(1, $key);
        $result = $stmt->execute()->fetchArray();
        if ($result && $result['requires_restart']) {
            $requires_restart = true;
        }
        
        // Update setting
        $stmt = $db->prepare("UPDATE game_settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE key = ?");
        $stmt->bindValue(1, $value);
        $stmt->bindValue(2, $key);
        $stmt->execute();
    }
    
    // Update .env file
    updateEnvFile($db);
    
    $success = "Settings updated successfully!";
    if ($requires_restart) {
        $success .= " Server restart required for some changes to take effect.";
    }
}

// Restart server if requested
if (isset($_POST['restart_server'])) {
    $output = shell_exec('cd /home/crucifix/Server && npm run stop 2>&1 && npm run start > server.log 2>&1 &');
    $restart_message = "Server restart initiated!";
}

function updateEnvFile($db) {
    $envPath = '/home/crucifix/Server/.env';
    $envContent = file_get_contents($envPath);
    
    // Get all settings
    $result = $db->query("SELECT key, value FROM game_settings");
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $key = $row['key'];
        $value = $row['value'];
        
        // Update or add the setting in .env
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }
    }
    
    file_put_contents($envPath, $envContent);
}

// Get settings from database
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

$categories = [];
$result = $db->query("SELECT * FROM game_settings ORDER BY category, key");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[$row['category']][] = $row;
}

// Check server status
$serverRunning = false;
$output = shell_exec('ps aux | grep "tsx src/app.ts" | grep -v grep');
if ($output) {
    $serverRunning = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Settings - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #ffcc00;
            border-bottom: 2px solid #ffcc00;
            padding-bottom: 10px;
        }
        .nav {
            background: #2a2a2a;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .nav a {
            color: #ffcc00;
            text-decoration: none;
            margin-right: 20px;
            padding: 5px 10px;
        }
        .nav a:hover {
            background: #3a3a3a;
            border-radius: 3px;
        }
        .status-bar {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .server-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-online { background: #4CAF50; }
        .status-offline { background: #f44336; }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .category {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 5px;
        }
        .category h2 {
            color: #ffcc00;
            margin-top: 0;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .setting {
            margin-bottom: 15px;
            padding: 10px;
            background: #1a1a1a;
            border-radius: 3px;
        }
        .setting label {
            display: block;
            margin-bottom: 5px;
            color: #ffcc00;
            font-weight: bold;
        }
        .setting-description {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
        }
        .setting input[type="text"],
        .setting input[type="number"],
        .setting select {
            width: 100%;
            padding: 8px;
            background: #333;
            border: 1px solid #555;
            color: #fff;
            border-radius: 3px;
        }
        .setting select {
            cursor: pointer;
        }
        .restart-required {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 10px;
        }
        .actions {
            position: sticky;
            bottom: 0;
            background: #1a1a1a;
            padding: 20px 0;
            border-top: 2px solid #444;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-primary {
            background: #ffcc00;
            color: #000;
        }
        .btn-danger {
            background: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #4CAF50;
            color: white;
        }
        .alert-info {
            background: #2196F3;
            color: white;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Game Settings</h1>
        
        <?php require_once 'includes/nav.php'; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($restart_message)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($restart_message); ?></div>
        <?php endif; ?>

        <div class="status-bar">
            <div class="server-status">
                <span>Server Status:</span>
                <span class="status-indicator <?php echo $serverRunning ? 'status-online' : 'status-offline'; ?>"></span>
                <strong><?php echo $serverRunning ? 'Online' : 'Offline'; ?></strong>
            </div>
            <div class="quick-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="restart_server" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to restart the server?');">
                        Restart Server
                    </button>
                </form>
            </div>
        </div>

        <form method="POST">
            <div class="settings-grid">
                <?php foreach ($categories as $categoryName => $settings): ?>
                    <div class="category">
                        <h2><?php echo htmlspecialchars($categoryName); ?></h2>
                        <?php foreach ($settings as $setting): ?>
                            <div class="setting">
                                <label for="<?php echo $setting['key']; ?>">
                                    <?php echo htmlspecialchars($setting['key']); ?>
                                    <?php if ($setting['requires_restart']): ?>
                                        <span class="restart-required">Requires Restart</span>
                                    <?php endif; ?>
                                </label>
                                <?php if ($setting['description']): ?>
                                    <div class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($setting['type'] === 'boolean'): ?>
                                    <select name="settings[<?php echo $setting['key']; ?>]" id="<?php echo $setting['key']; ?>">
                                        <option value="true" <?php echo $setting['value'] === 'true' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="false" <?php echo $setting['value'] === 'false' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                <?php elseif ($setting['type'] === 'number'): ?>
                                    <input type="number" 
                                           name="settings[<?php echo $setting['key']; ?>]" 
                                           id="<?php echo $setting['key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['value']); ?>">
                                <?php else: ?>
                                    <input type="text" 
                                           name="settings[<?php echo $setting['key']; ?>]" 
                                           id="<?php echo $setting['key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['value']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                <a href="index.php" class="btn" style="background: #666; color: white; text-decoration: none; display: inline-block;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>