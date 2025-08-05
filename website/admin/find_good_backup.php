<?php
// Find the actual good backup

echo "=== SEARCHING FOR GOOD BACKUP ===\n\n";

$backups = [
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754027631',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028095',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028110',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028111',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028115'
];

// Check each backup's raw data
foreach ($backups as $file) {
    $data = file_get_contents($file);
    $time = date('Y-m-d H:i:s', filemtime($file));
    
    echo "File: " . basename($file) . " (modified: $time)\n";
    
    // Check HP XP at offset 0x28
    $hpXp = unpack('N', substr($data, 0x28, 4))[1];
    echo "HP XP: $hpXp";
    
    if ($hpXp == 1154) {
        echo " (Level 10 - STARTER!)";
    } elseif ($hpXp == 1358) {
        echo " (Level 11)";
    }
    echo "\n";
    
    // Check combat level at 0x0C
    $combat = unpack('n', substr($data, 0x0C, 2))[1];
    echo "Combat Level bytes: $combat\n";
    
    // Check if skills look normal (not all zeros)
    $skillsOk = true;
    $zeroCount = 0;
    for ($i = 0; $i < 21; $i++) {
        $xp = unpack('N', substr($data, 0x1C + ($i * 4), 4))[1];
        if ($xp == 0 && $i != 3) { // HP shouldn't be 0
            $zeroCount++;
        }
    }
    
    echo "Skills with 0 XP: $zeroCount";
    if ($zeroCount == 20) {
        echo " (Looks like fresh starter account!)";
    }
    echo "\n";
    
    // Check inventory at 0x548
    $hasItems = false;
    $itemCount = 0;
    for ($i = 0; $i < 28; $i++) {
        $offset = 0x548 + ($i * 3);
        if ($offset + 2 < strlen($data)) {
            $itemId = unpack('n', substr($data, $offset, 2))[1];
            if ($itemId != 0xFFFF && $itemId > 0) {
                $itemCount++;
                $hasItems = true;
            }
        }
    }
    echo "Inventory items: $itemCount\n";
    
    if ($hpXp == 1154 && $combat == 3 && $hasItems) {
        echo "*** THIS MIGHT BE YOUR ORIGINAL GOOD SAVE! ***\n";
    }
    
    echo "---\n\n";
}

// Check for any other backup locations
echo "=== CHECKING OTHER POSSIBLE LOCATIONS ===\n";
$otherLocations = [
    '/tmp/',
    '/var/tmp/',
    '/var/www/html/2004scape/admin/',
    dirname(__FILE__) . '/'
];

foreach ($otherLocations as $dir) {
    $files = glob($dir . '*crucifix*.sav');
    if ($files) {
        foreach ($files as $file) {
            echo "Found: $file (size: " . filesize($file) . " bytes, modified: " . date('Y-m-d H:i:s', filemtime($file)) . ")\n";
        }
    }
}
?>