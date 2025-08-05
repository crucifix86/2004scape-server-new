<?php
// Verify the HP XP issue

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$data = file_get_contents($saveFile);

echo "=== HP XP VERIFICATION ===\n\n";

// Check HP XP at offset 0x28
$hpXpOffset = 0x28;
$hpXpBytes = substr($data, $hpXpOffset, 4);

echo "Raw HP XP bytes at offset 0x28:\n";
for ($i = 0; $i < 4; $i++) {
    printf("  [%d] = 0x%02X (%d)\n", $i, ord($hpXpBytes[$i]), ord($hpXpBytes[$i]));
}

// Try unpacking as big-endian
$hpXp = unpack('N', $hpXpBytes)[1];
echo "\nHP XP (big-endian): $hpXp\n";

// HP Level 10 should be 1154 (0x00000482)
echo "\nExpected for level 10: 1154 (0x00000482)\n";
echo "Expected bytes: 00 00 04 82\n";

// Let me check what 256 represents
echo "\n256 in hex: 0x" . sprintf('%08X', 256) . "\n";
echo "This is 0x00000100\n";

// Check all skill XP values
echo "\n=== ALL SKILL XP VALUES ===\n";
$skillNames = ['Attack', 'Defence', 'Strength', 'Hitpoints', 'Ranged', 'Prayer', 'Magic', 
               'Cooking', 'Woodcutting', 'Fletching', 'Fishing', 'Firemaking', 'Crafting', 
               'Smithing', 'Mining', 'Herblore', 'Agility', 'Thieving', 'Stat18', 'Stat19', 'Runecraft'];

for ($i = 0; $i < 21; $i++) {
    $offset = 0x1C + ($i * 4);
    $xpBytes = substr($data, $offset, 4);
    $xp = unpack('N', $xpBytes)[1];
    
    // Show hex for suspicious values
    if ($xp == 0 || $xp == 1 || $xp == 256) {
        $hex = bin2hex($xpBytes);
        printf("%-12s: XP=%8d (0x%s)\n", $skillNames[$i], $xp, $hex);
    } else {
        printf("%-12s: XP=%8d\n", $skillNames[$i], $xp);
    }
}

// Let's manually set HP XP to 1154 and see what happens
echo "\n=== MANUAL FIX TEST ===\n";
echo "To manually fix HP XP to level 10 (1154 XP), the bytes at offset 0x28 should be:\n";
echo "00 00 04 82\n";

// Create a fixed version
$fixedData = $data;
$fixedXp = pack('N', 1154); // 1154 = 0x00000482
for ($i = 0; $i < 4; $i++) {
    $fixedData[0x28 + $i] = $fixedXp[$i];
}

// Also update combat level
// With HP 10, combat level should be 3
$combatLevel = 3;
$combatBytes = pack('n', $combatLevel);
$fixedData[0x0C] = $combatBytes[0];
$fixedData[0x0D] = $combatBytes[1];

// Save the fixed version
$fixedFile = '/tmp/crucifix_fixed.sav';
file_put_contents($fixedFile, $fixedData);
echo "\nFixed save file written to: $fixedFile\n";
echo "To apply the fix, run:\n";
echo "sudo cp $fixedFile $saveFile && sudo chmod 666 $saveFile\n";
?>