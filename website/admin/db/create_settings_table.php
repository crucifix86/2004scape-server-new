<?php
// Create game_settings table for dynamic configuration
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

// Create game_settings table
$sql = "CREATE TABLE IF NOT EXISTS game_settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    category TEXT NOT NULL,
    type TEXT NOT NULL,
    description TEXT,
    requires_restart INTEGER DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

$db->exec($sql);

// Insert default settings with current .env values
$defaults = [
    // Server Configuration
    ['key' => 'NODE_ID', 'value' => '10', 'category' => 'Server', 'type' => 'number', 'description' => 'World/Server ID', 'requires_restart' => 1],
    ['key' => 'NODE_PORT', 'value' => '43594', 'category' => 'Server', 'type' => 'number', 'description' => 'Game server port', 'requires_restart' => 1],
    ['key' => 'WEB_PORT', 'value' => '8080', 'category' => 'Server', 'type' => 'number', 'description' => 'Web server port', 'requires_restart' => 1],
    ['key' => 'WEB_SOCKET', 'value' => 'true', 'category' => 'Server', 'type' => 'boolean', 'description' => 'Enable WebSocket support', 'requires_restart' => 1],
    
    // World Settings
    ['key' => 'NODE_MEMBERS', 'value' => 'true', 'category' => 'World', 'type' => 'boolean', 'description' => 'Members world enabled', 'requires_restart' => 1],
    ['key' => 'NODE_XPRATE', 'value' => '1', 'category' => 'World', 'type' => 'number', 'description' => 'XP rate multiplier', 'requires_restart' => 1],
    ['key' => 'NODE_MAX_PLAYERS', 'value' => '2047', 'category' => 'World', 'type' => 'number', 'description' => 'Maximum players allowed', 'requires_restart' => 1],
    ['key' => 'NODE_MAX_NPCS', 'value' => '8191', 'category' => 'World', 'type' => 'number', 'description' => 'Maximum NPCs', 'requires_restart' => 1],
    
    // Game Mechanics
    ['key' => 'PLAYER_SAVERATE', 'value' => '1500', 'category' => 'Mechanics', 'type' => 'number', 'description' => 'Auto-save interval (ticks)', 'requires_restart' => 0],
    ['key' => 'INV_STOCKRATE', 'value' => '100', 'category' => 'Mechanics', 'type' => 'number', 'description' => 'Shop restock rate (ticks)', 'requires_restart' => 0],
    ['key' => 'AFK_EVENTRATE', 'value' => '500', 'category' => 'Mechanics', 'type' => 'number', 'description' => 'AFK check rate (ticks)', 'requires_restart' => 0],
    
    // Authentication
    ['key' => 'WEBSITE_REGISTRATION', 'value' => 'true', 'category' => 'Authentication', 'type' => 'boolean', 'description' => 'Require website registration', 'requires_restart' => 1],
    ['key' => 'LOGIN_SERVER', 'value' => 'true', 'category' => 'Authentication', 'type' => 'boolean', 'description' => 'Enable login server', 'requires_restart' => 1],
    
    // Services
    ['key' => 'FRIEND_SERVER', 'value' => 'true', 'category' => 'Services', 'type' => 'boolean', 'description' => 'Enable friend/chat server', 'requires_restart' => 1],
    ['key' => 'EASY_STARTUP', 'value' => 'true', 'category' => 'Services', 'type' => 'boolean', 'description' => 'Easy startup mode', 'requires_restart' => 1],
    
    // Debug
    ['key' => 'NODE_DEBUG', 'value' => 'false', 'category' => 'Debug', 'type' => 'boolean', 'description' => 'Debug mode', 'requires_restart' => 1],
    ['key' => 'NODE_PRODUCTION', 'value' => 'false', 'category' => 'Debug', 'type' => 'boolean', 'description' => 'Production mode', 'requires_restart' => 1],
];

// Insert defaults if not exists
$stmt = $db->prepare("INSERT OR IGNORE INTO game_settings (key, value, category, type, description, requires_restart) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($defaults as $setting) {
    $stmt->bindValue(1, $setting['key']);
    $stmt->bindValue(2, $setting['value']);
    $stmt->bindValue(3, $setting['category']);
    $stmt->bindValue(4, $setting['type']);
    $stmt->bindValue(5, $setting['description']);
    $stmt->bindValue(6, $setting['requires_restart']);
    $stmt->execute();
}

echo "Game settings table created successfully!";
?>