<?php
$dbPath = '/tmp/game_db.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check accounts
    $stmt = $db->query("SELECT id, username, substr(password, 1, 10) as pass_preview FROM account");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Accounts in game database:\n";
    foreach ($accounts as $account) {
        echo "ID: {$account['id']}, User: {$account['username']}, Pass: {$account['pass_preview']}...\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>