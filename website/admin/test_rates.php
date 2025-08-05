<?php
// Test updating rates
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

// Set XP rate to 2x
$db->exec("UPDATE game_settings SET value = '2' WHERE key = 'NODE_XPRATE'");

// Set drop rate to 1.5x
$db->exec("UPDATE game_settings SET value = '1.5' WHERE key = 'DROP_RATE_MULTIPLIER'");

echo "Rates updated!\n";
echo "XP Rate: 2x\n";
echo "Drop Rate: 1.5x\n";
?>