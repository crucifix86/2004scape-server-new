<?php
// Nuclear option - delete account completely

$dbPath = '/home/crucifix/Server/db.sqlite';
$username = 'crucifix';

echo "=== NUCLEAR OPTION: DELETE ACCOUNT ===\n\n";

// Connect to database
$db = new SQLite3($dbPath);

// First, let's backup the account info just in case
$stmt = $db->prepare("SELECT * FROM account WHERE username = ?");
$stmt->bindValue(1, $username);
$result = $stmt->execute();
$account = $result->fetchArray(SQLITE3_ASSOC);

if ($account) {
    echo "Account found:\n";
    echo "ID: " . $account['id'] . "\n";
    echo "Username: " . $account['username'] . "\n";
    echo "Created: " . date('Y-m-d H:i:s', $account['created'] ?? 0) . "\n";
    echo "\n";
    
    // Delete from all related tables
    $tables = ['account', 'account_session', 'friend_list', 'ignore_list'];
    
    foreach ($tables as $table) {
        echo "Deleting from $table...";
        try {
            if ($table == 'account') {
                $stmt = $db->prepare("DELETE FROM $table WHERE username = ?");
                $stmt->bindValue(1, $username);
            } else {
                $stmt = $db->prepare("DELETE FROM $table WHERE account_id = ?");
                $stmt->bindValue(1, $account['id']);
            }
            $stmt->execute();
            echo " Done\n";
        } catch (Exception $e) {
            echo " Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Also delete any save files
    echo "\nDeleting save files...\n";
    $saveFiles = glob("/home/crucifix/Server/data/players/main/{$username}*");
    foreach ($saveFiles as $file) {
        echo "Deleting: $file\n";
        unlink($file);
    }
    
    echo "\n=== ACCOUNT DELETED ===\n";
    echo "You can now create a fresh account with username: $username\n";
} else {
    echo "Account not found in database.\n";
}

$db->close();
?>