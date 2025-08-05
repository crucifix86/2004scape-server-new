<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

echo "=== CHAT DEBUG ===\n\n";

// Check if tables exist
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND (name='public_chat' OR name='private_chat')")->fetchAll();
echo "Chat tables found:\n";
foreach ($tables as $table) {
    echo "- " . $table['name'] . "\n";
}

echo "\n";

// Count all messages
$public = $db->query("SELECT COUNT(*) as cnt FROM public_chat")->fetch()['cnt'];
$private = $db->query("SELECT COUNT(*) as cnt FROM private_chat")->fetch()['cnt'];

echo "Message counts:\n";
echo "- Public messages: $public\n";
echo "- Private messages: $private\n\n";

// Get ALL public messages (no limit)
echo "ALL public chat messages:\n";
$stmt = $db->query("SELECT pc.*, a.username FROM public_chat pc LEFT JOIN account a ON pc.account_id = a.id ORDER BY pc.id DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($messages) > 0) {
    foreach ($messages as $msg) {
        echo "---\n";
        echo "ID: " . $msg['id'] . "\n";
        echo "User: " . ($msg['username'] ?? 'Unknown') . " (ID: " . $msg['account_id'] . ")\n";
        echo "Message: " . $msg['message'] . "\n";
        echo "Time: " . $msg['timestamp'] . "\n";
        echo "World: " . $msg['world'] . "\n";
    }
} else {
    echo "No messages found\n";
}

echo "\n";

// Check if FriendServer is running
echo "Checking recent server activity:\n";
$stmt = $db->query("SELECT * FROM login ORDER BY timestamp DESC LIMIT 3");
while ($login = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- Login at " . $login['timestamp'] . " (account " . $login['account_id'] . ")\n";
}
?>