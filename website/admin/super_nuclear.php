<?php
// Super nuclear option - clean everything crucifix related

$dbPath = '/home/crucifix/Server/db.sqlite';
$username = 'crucifix';

echo "=== SUPER NUCLEAR OPTION ===\n\n";

// Connect to database
$db = new SQLite3($dbPath);

// Delete from ALL possible tables that might have crucifix data
$tables = ['account', 'account_session', 'friend_list', 'ignore_list', 'ban', 'mute', 'mod_action'];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    
    // Check if table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    if ($result->fetchArray()) {
        echo "  Table exists, attempting cleanup...\n";
        
        try {
            if ($table == 'account') {
                $stmt = $db->prepare("DELETE FROM $table WHERE username = ?");
                $stmt->bindValue(1, $username);
            } else {
                // For other tables, try different possible column names
                $possibleColumns = ['account_id', 'user_id', 'player_id', 'username'];
                
                foreach ($possibleColumns as $col) {
                    try {
                        if ($col == 'username') {
                            $stmt = $db->prepare("DELETE FROM $table WHERE $col = ?");
                            $stmt->bindValue(1, $username);
                        } else {
                            // Try to find account ID first
                            $accountResult = $db->query("SELECT id FROM account WHERE username = '$username'");
                            $accountData = $accountResult->fetchArray();
                            if ($accountData) {
                                $stmt = $db->prepare("DELETE FROM $table WHERE $col = ?");
                                $stmt->bindValue(1, $accountData['id']);
                            } else {
                                continue; // No account found, skip
                            }
                        }
                        $stmt->execute();
                        echo "    Cleaned column: $col\n";
                        break;
                    } catch (Exception $e) {
                        // Column doesn't exist, try next one
                        continue;
                    }
                }
            }
        } catch (Exception $e) {
            echo "    Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  Table doesn't exist, skipping\n";
    }
}

// Delete ALL save files and backups
echo "\n=== CLEANING FILES ===\n";

$directories = [
    '/home/crucifix/Server/data/players/main/',
    '/home/crucifix/Server/data/players/backups/crucifix/',
    '/tmp/'
];

foreach ($directories as $dir) {
    $files = glob($dir . "*crucifix*");
    foreach ($files as $file) {
        if (is_file($file)) {
            echo "Deleting: $file\n";
            unlink($file);
        }
    }
}

// Also clean the backup directory
$backupDir = '/home/crucifix/Server/data/players/backups/crucifix/';
if (is_dir($backupDir)) {
    echo "Removing backup directory: $backupDir\n";
    array_map('unlink', glob("$backupDir/*"));
    rmdir($backupDir);
}

echo "\n=== SUPER NUCLEAR COMPLETE ===\n";
echo "Everything crucifix-related has been obliterated.\n";
echo "You can now create a completely fresh account.\n";

$db->close();
?>