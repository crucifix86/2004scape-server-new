<?php
// Add more advanced game settings
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

$advancedSettings = [
    // XP and Rates
    ['key' => 'DROP_RATE_MULTIPLIER', 'value' => '1', 'category' => 'Rates', 'type' => 'number', 'description' => 'Drop rate multiplier (1 = normal, 2 = double)', 'requires_restart' => 1],
    ['key' => 'RESPAWN_RATE_MULTIPLIER', 'value' => '1', 'category' => 'Rates', 'type' => 'number', 'description' => 'NPC respawn rate multiplier', 'requires_restart' => 1],
    
    // Combat Settings
    ['key' => 'PVP_ENABLED', 'value' => 'true', 'category' => 'Combat', 'type' => 'boolean', 'description' => 'Enable PvP in wilderness', 'requires_restart' => 0],
    ['key' => 'DEATH_ITEM_LOSS', 'value' => 'true', 'category' => 'Combat', 'type' => 'boolean', 'description' => 'Lose items on death', 'requires_restart' => 0],
    ['key' => 'PROTECT_ITEM_ENABLED', 'value' => 'true', 'category' => 'Combat', 'type' => 'boolean', 'description' => 'Allow protect item prayer', 'requires_restart' => 0],
    
    // Economy
    ['key' => 'SHOP_RESTOCK_SPEED', 'value' => '100', 'category' => 'Economy', 'type' => 'number', 'description' => 'Shop restock speed in ticks', 'requires_restart' => 0],
    ['key' => 'HIGH_ALCH_MULTIPLIER', 'value' => '1', 'category' => 'Economy', 'type' => 'number', 'description' => 'High alchemy value multiplier', 'requires_restart' => 0],
    ['key' => 'STARTING_GOLD', 'value' => '0', 'category' => 'Economy', 'type' => 'number', 'description' => 'Starting gold for new players', 'requires_restart' => 0],
    
    // World Events
    ['key' => 'DOUBLE_XP_ENABLED', 'value' => 'false', 'category' => 'Events', 'type' => 'boolean', 'description' => 'Enable double XP weekend', 'requires_restart' => 0],
    ['key' => 'HOLIDAY_EVENT', 'value' => 'none', 'category' => 'Events', 'type' => 'text', 'description' => 'Active holiday event (halloween, christmas, easter, none)', 'requires_restart' => 1],
    
    // Performance
    ['key' => 'MAX_PACKETS_PER_TICK', 'value' => '10', 'category' => 'Performance', 'type' => 'number', 'description' => 'Maximum packets processed per tick', 'requires_restart' => 0],
    ['key' => 'VIEW_DISTANCE', 'value' => '15', 'category' => 'Performance', 'type' => 'number', 'description' => 'Player view distance in tiles', 'requires_restart' => 0],
    
    // Security
    ['key' => 'AUTO_BAN_THRESHOLD', 'value' => '5', 'category' => 'Security', 'type' => 'number', 'description' => 'Auto-ban after X reports', 'requires_restart' => 0],
    ['key' => 'PACKET_FLOOD_PROTECTION', 'value' => 'true', 'category' => 'Security', 'type' => 'boolean', 'description' => 'Enable packet flood protection', 'requires_restart' => 1],
    ['key' => 'BOT_DETECTION', 'value' => 'true', 'category' => 'Security', 'type' => 'boolean', 'description' => 'Enable bot detection system', 'requires_restart' => 0],
    
    // Minigames
    ['key' => 'CASTLE_WARS_ENABLED', 'value' => 'true', 'category' => 'Minigames', 'type' => 'boolean', 'description' => 'Enable Castle Wars', 'requires_restart' => 0],
    ['key' => 'DUEL_ARENA_ENABLED', 'value' => 'true', 'category' => 'Minigames', 'type' => 'boolean', 'description' => 'Enable Duel Arena', 'requires_restart' => 0],
    ['key' => 'MINIGAME_REWARDS_MULTIPLIER', 'value' => '1', 'category' => 'Minigames', 'type' => 'number', 'description' => 'Minigame rewards multiplier', 'requires_restart' => 0],
];

// Insert advanced settings
$stmt = $db->prepare("INSERT OR IGNORE INTO game_settings (key, value, category, type, description, requires_restart) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($advancedSettings as $setting) {
    $stmt->bindValue(1, $setting['key']);
    $stmt->bindValue(2, $setting['value']);
    $stmt->bindValue(3, $setting['category']);
    $stmt->bindValue(4, $setting['type']);
    $stmt->bindValue(5, $setting['description']);
    $stmt->bindValue(6, $setting['requires_restart']);
    $stmt->execute();
}

echo "Advanced settings added successfully!";
?>