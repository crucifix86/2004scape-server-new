<?php
// Fix save file to proper starter values

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$data = file_get_contents($saveFile);

echo "=== FIXING SAVE FILE TO STARTER VALUES ===\n\n";

// Create a copy to modify
$fixedData = $data;

// Set all skills to 0 XP (level 1) except HP
$zeroXp = pack('N', 0);
$hpXp = pack('N', 1154); // Level 10 HP

// Fix all skill XP values
for ($i = 0; $i < 21; $i++) {
    $offset = 0x1C + ($i * 4);
    
    if ($i == 3) { // Hitpoints
        for ($j = 0; $j < 4; $j++) {
            $fixedData[$offset + $j] = $hpXp[$j];
        }
    } else {
        for ($j = 0; $j < 4; $j++) {
            $fixedData[$offset + $j] = $zeroXp[$j];
        }
    }
}

// Set all current levels to 1 except HP
for ($i = 0; $i < 21; $i++) {
    $offset = 0x70 + $i;
    if ($i == 3) { // Hitpoints
        $fixedData[$offset] = chr(10); // Level 10
    } else {
        $fixedData[$offset] = chr(1); // Level 1
    }
}

// Fix combat level (should be 3 for starter)
$combatLevel = 3;
$combatBytes = pack('n', $combatLevel);
$fixedData[0x0C] = $combatBytes[0];
$fixedData[0x0D] = $combatBytes[1];

// Save the fixed file
$tempFile = '/tmp/crucifix_starter_fixed.sav';
file_put_contents($tempFile, $fixedData);

echo "Fixed save file created at: $tempFile\n";
echo "\nStarter stats set:\n";
echo "- All skills: Level 1 (0 XP)\n";
echo "- Hitpoints: Level 10 (1,154 XP)\n";
echo "- Combat Level: 3\n";
echo "- Total Level: 28\n";

echo "\nTo apply the fix, run:\n";
echo "sudo cp $tempFile $saveFile && sudo chmod 666 $saveFile\n";

// Verify the fix
echo "\n=== VERIFICATION ===\n";
$hpOffset = 0x28;
$hpBytes = substr($fixedData, $hpOffset, 4);
$verifyHp = unpack('N', $hpBytes)[1];
echo "HP XP in fixed file: $verifyHp (should be 1154)\n";

// Check a few other skills
$attackBytes = substr($fixedData, 0x1C, 4);
$attackXp = unpack('N', $attackBytes)[1];
echo "Attack XP in fixed file: $attackXp (should be 0)\n";

// Make it executable from web
chmod($tempFile, 0666);
?>