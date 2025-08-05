<?php
// Test tool to understand the save file issue

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$backupFile = '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754026820';

echo "=== SAVE FILE ANALYSIS ===\n\n";

// Read both files
$currentData = file_get_contents($saveFile);
$backupData = file_get_contents($backupFile);

echo "Current file size: " . strlen($currentData) . " bytes\n";
echo "Backup file size: " . strlen($backupData) . " bytes\n\n";

// Check HP XP position
$hpOffset = 0x28; // 0x1C + (3 * 4)

echo "HP XP bytes in current file (offset 0x28):\n";
for ($i = 0; $i < 4; $i++) {
    printf("  Byte %d: 0x%02X (%d)\n", $i, ord($currentData[$hpOffset + $i]), ord($currentData[$hpOffset + $i]));
}
$currentHpXp = unpack('N', substr($currentData, $hpOffset, 4))[1];
echo "  Decoded HP XP: $currentHpXp\n\n";

echo "HP XP bytes in backup file (offset 0x28):\n";
for ($i = 0; $i < 4; $i++) {
    printf("  Byte %d: 0x%02X (%d)\n", $i, ord($backupData[$hpOffset + $i]), ord($backupData[$hpOffset + $i]));
}
$backupHpXp = unpack('N', substr($backupData, $hpOffset, 4))[1];
echo "  Decoded HP XP: $backupHpXp\n\n";

// Check if files are identical except for specific bytes
$differences = [];
for ($i = 0; $i < min(strlen($currentData), strlen($backupData)); $i++) {
    if ($currentData[$i] !== $backupData[$i]) {
        $differences[] = sprintf("0x%04X: current=0x%02X, backup=0x%02X", $i, ord($currentData[$i]), ord($backupData[$i]));
    }
}

echo "Differences found: " . count($differences) . "\n";
if (count($differences) <= 10) {
    foreach ($differences as $diff) {
        echo "  $diff\n";
    }
} else {
    echo "  (too many to display)\n";
}

// Let's check the actual HP XP value that should be there
echo "\n=== EXPECTED VALUES ===\n";
echo "HP Level 10 should have XP: 1154\n";
echo "HP Level 11 should have XP: 1358\n";

// Test the pack/unpack functions
echo "\n=== PACK/UNPACK TEST ===\n";
$testXp = 1154;
$packed = pack('N', $testXp);
echo "Packing 1154:\n";
for ($i = 0; $i < 4; $i++) {
    printf("  Byte %d: 0x%02X\n", $i, ord($packed[$i]));
}
$unpacked = unpack('N', $packed)[1];
echo "Unpacked: $unpacked\n";

// Check what's at the beginning of the file
echo "\n=== FILE HEADER CHECK ===\n";
echo "First 32 bytes of current file:\n";
echo bin2hex(substr($currentData, 0, 32)) . "\n";
echo "\nFirst 32 bytes of backup file:\n";
echo bin2hex(substr($backupData, 0, 32)) . "\n";

// Check save file signature
$sig = unpack('n', substr($currentData, 0, 2))[1];
echo "\nFile signature: 0x" . sprintf('%04X', $sig) . " (should be 0x2004)\n";
?>