<?php
// Diagnose why you can't login

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$backupFile = '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754027631';

echo "=== COMPARING CURRENT VS BACKUP ===\n\n";

$current = file_get_contents($saveFile);
$backup = file_get_contents($backupFile);

echo "Current file size: " . strlen($current) . " bytes\n";
echo "Backup file size: " . strlen($backup) . " bytes\n\n";

// Check file header
echo "CURRENT FILE HEADER (first 64 bytes):\n";
echo bin2hex(substr($current, 0, 64)) . "\n\n";

echo "BACKUP FILE HEADER (first 64 bytes):\n";
echo bin2hex(substr($backup, 0, 64)) . "\n\n";

// Check for differences
$differences = 0;
for ($i = 0; $i < min(strlen($current), strlen($backup)); $i++) {
    if ($current[$i] !== $backup[$i]) {
        $differences++;
        if ($differences <= 20) {
            printf("Diff at 0x%04X: current=0x%02X, backup=0x%02X\n", 
                   $i, ord($current[$i]), ord($backup[$i]));
        }
    }
}

echo "\nTotal differences: $differences\n";

// Check specific important values
echo "\n=== KEY VALUES ===\n";

// Position
$curX = unpack('n', substr($current, 4, 2))[1];
$curZ = unpack('n', substr($current, 6, 2))[1];
$bakX = unpack('n', substr($backup, 4, 2))[1];
$bakZ = unpack('n', substr($backup, 6, 2))[1];
echo "Position - Current: X=$curX,Z=$curZ | Backup: X=$bakX,Z=$bakZ\n";

// Combat level
$curCombat = unpack('n', substr($current, 0x0C, 2))[1];
$bakCombat = unpack('n', substr($backup, 0x0C, 2))[1];
echo "Combat Level - Current: $curCombat | Backup: $bakCombat\n";

// HP XP
$curHP = unpack('N', substr($current, 0x28, 4))[1];
$bakHP = unpack('N', substr($backup, 0x28, 4))[1];
echo "HP XP - Current: $curHP | Backup: $bakHP\n";

// Try to restore from backup
echo "\n=== RESTORATION COMMAND ===\n";
echo "To restore from the good backup, run:\n";
echo "sudo cp $backupFile $saveFile && sudo chmod 666 $saveFile\n";

// Check if file might be locked
echo "\n=== FILE PERMISSIONS ===\n";
system("ls -la $saveFile");

// Check if server might have the file open
echo "\n=== CHECKING IF FILE IS IN USE ===\n";
system("lsof $saveFile 2>/dev/null || echo 'File not currently open by any process'");
?>