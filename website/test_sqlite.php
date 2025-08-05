<?php
$dbPath = '/home/crucifix/Server/db.sqlite';

echo "Testing SQLite...\n";
echo "Path: $dbPath\n";
echo "Directory writable: " . (is_writable(dirname($dbPath)) ? 'Yes' : 'No') . "\n";

if (!is_writable(dirname($dbPath))) {
    $dbPath = '/tmp/game_db.sqlite';
    echo "Using fallback: $dbPath\n";
}

try {
    $db = new PDO("sqlite:$dbPath");
    echo "Connected successfully!\n";
    
    // Create table
    $db->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER)");
    echo "Table created!\n";
    
    // Check file exists
    echo "File exists: " . (file_exists($dbPath) ? 'Yes' : 'No') . "\n";
    echo "File path: " . realpath($dbPath) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>