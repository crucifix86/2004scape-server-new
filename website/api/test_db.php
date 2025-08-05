<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
echo "Path: $dbPath\n";
echo "Exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";
echo "Readable: " . (is_readable($dbPath) ? 'YES' : 'NO') . "\n";
echo "Writeable: " . (is_writable($dbPath) ? 'YES' : 'NO') . "\n";
echo "Real path: " . realpath($dbPath) . "\n";
echo "PHP user: " . exec('whoami') . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "\n";
?>