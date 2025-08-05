<?php
$db = new SQLite3('/home/crucifix/Server/db.sqlite');
$result = $db->query("SELECT username FROM account WHERE username='crucifix'");
$row = $result->fetchArray();

if ($row) {
    echo "Account still exists!\n";
} else {
    echo "Account successfully deleted!\n";
}

echo "\nAll accounts in database:\n";
$result = $db->query("SELECT id, username FROM account");
while ($row = $result->fetchArray()) {
    echo "ID: " . $row['id'] . ", Username: " . $row['username'] . "\n";
}
?>