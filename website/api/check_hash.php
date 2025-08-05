<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");
$stmt = $db->prepare("SELECT username, password FROM account WHERE username = 'fixedauth'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "Username: " . $row['username'] . "\n";
    echo "Hash prefix: " . substr($row['password'], 0, 4) . "\n";
    echo "Full hash: " . $row['password'] . "\n";
}
?>