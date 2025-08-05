<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

echo "=== CHAT DATABASE CHECK ===\n\n";

// Check public_chat table structure
$stmt = $db->query("PRAGMA table_info(public_chat)");
echo "public_chat table structure:\n";
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\n";

// Check private_chat table structure  
$stmt = $db->query("PRAGMA table_info(private_chat)");
echo "private_chat table structure:\n";
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\n";

// Count messages
$publicCount = $db->query("SELECT COUNT(*) FROM public_chat")->fetchColumn();
$privateCount = $db->query("SELECT COUNT(*) FROM private_chat")->fetchColumn();

echo "Message counts:\n";
echo "- Public chat messages: $publicCount\n";
echo "- Private chat messages: $privateCount\n\n";

// Show recent public messages
echo "Recent public messages:\n";
$stmt = $db->query("SELECT * FROM public_chat ORDER BY id DESC LIMIT 5");
while ($msg = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($msg);
}

echo "\nRecent private messages:\n";
$stmt = $db->query("SELECT * FROM private_chat ORDER BY id DESC LIMIT 5");
while ($msg = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($msg);
}
?>