<?php
// Restore save file script

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$backupFile = '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754026820';

// Check current file
echo "Current save file size: " . filesize($saveFile) . " bytes\n";
echo "Backup file size: " . filesize($backupFile) . " bytes\n";

// Read first few bytes of current file
$current = file_get_contents($saveFile);
$backup = file_get_contents($backupFile);

echo "\nFirst 100 bytes of current file (hex):\n";
echo bin2hex(substr($current, 0, 100)) . "\n";

echo "\nFirst 100 bytes of backup file (hex):\n";
echo bin2hex(substr($backup, 0, 100)) . "\n";

// Check the HP XP value position (offset 0x1C + 3*4 = 0x28 = 40 decimal)
echo "\nHP XP in current file: ";
$hp_xp_current = unpack('N', substr($current, 40, 4))[1];
echo $hp_xp_current . "\n";

echo "HP XP in backup file: ";
$hp_xp_backup = unpack('N', substr($backup, 40, 4))[1];
echo $hp_xp_backup . "\n";

// Restore from backup
if (copy($backupFile, $saveFile)) {
    echo "\nSuccessfully restored from backup!\n";
    chmod($saveFile, 0666); // Make it writable
} else {
    echo "\nFailed to restore from backup.\n";
}
?>