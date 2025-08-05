<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

$result = $db->query("SELECT category, COUNT(*) as count FROM game_settings GROUP BY category ORDER BY category");
echo "Settings categories:\n";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "- {$row['category']}: {$row['count']} settings\n";
}

echo "\nTotal settings: ";
$total = $db->querySingle("SELECT COUNT(*) FROM game_settings");
echo "$total\n";
?>