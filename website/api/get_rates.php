<?php
header('Content-Type: application/json');

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

// Get XP rate and drop rate from settings
$xpRate = $db->querySingle("SELECT value FROM game_settings WHERE key = 'NODE_XPRATE'") ?: '1';
$dropRate = $db->querySingle("SELECT value FROM game_settings WHERE key = 'DROP_RATE_MULTIPLIER'") ?: '1';

// Get online player count
$onlineCount = $db->querySingle("SELECT COUNT(DISTINCT account_id) FROM login WHERE timestamp > datetime('now', '-5 minutes')") ?: 0;

// Get server status
$serverRunning = false;
$output = shell_exec('ps aux | grep "tsx src/app.ts" | grep -v grep');
if ($output) {
    $serverRunning = true;
}

echo json_encode([
    'xp_rate' => floatval($xpRate),
    'drop_rate' => floatval($dropRate),
    'online_players' => intval($onlineCount),
    'server_status' => $serverRunning ? 'online' : 'offline'
]);
?>