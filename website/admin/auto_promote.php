<?php
// This runs on every admin page load to auto-promote configured developers
function autoPromoteDevelopers() {
    $configPath = '/home/crucifix/Server/admin_config.json';
    
    if (!file_exists($configPath)) {
        return;
    }
    
    $config = json_decode(file_get_contents($configPath), true);
    if (!isset($config['developers']) || !is_array($config['developers'])) {
        return;
    }
    
    $dbPath = '/home/crucifix/Server/db.sqlite';
    
    try {
        $db = new PDO("sqlite:$dbPath");
        
        foreach ($config['developers'] as $username) {
            // Check if user exists and promote to developer
            $stmt = $db->prepare("UPDATE account SET staffmodlevel = 4 WHERE LOWER(username) = LOWER(?) AND staffmodlevel < 4");
            $stmt->execute([$username]);
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// Run on include
autoPromoteDevelopers();
?>