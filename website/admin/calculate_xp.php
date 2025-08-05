<?php
// Calculate XP required for each level
function xpForLevel($level) {
    if ($level <= 1) return 0;
    
    $points = 0;
    for ($lvl = 1; $lvl < $level; $lvl++) {
        $points += floor($lvl + 300 * pow(2, $lvl / 7));
    }
    return floor($points / 4);
}

// Show XP for levels 1-20
echo "Level -> XP Required\n";
echo "--------------------\n";
for ($i = 1; $i <= 20; $i++) {
    $xp = xpForLevel($i);
    echo "Level $i: " . number_format($xp) . " XP\n";
}

echo "\n";
echo "Level 10 Hitpoints requires: " . xpForLevel(10) . " XP\n";
echo "In hex that would be: 0x" . dechex(xpForLevel(10)) . "\n";

// Check what 00 2d 14 0a means
$bytes = [0x00, 0x2d, 0x14, 0x0a];
$value = ($bytes[0] << 24) | ($bytes[1] << 16) | ($bytes[2] << 8) | $bytes[3];
echo "\nThe bytes 00 2d 14 0a = $value in decimal\n";

// Maybe it's stored differently?
// Check if bytes are in different order
$value2 = ($bytes[3] << 24) | ($bytes[2] << 16) | ($bytes[1] << 8) | $bytes[0];
echo "If reversed: 0a 14 2d 00 = $value2 in decimal\n";

// Or maybe as two 16-bit values?
$val1 = ($bytes[0] << 8) | $bytes[1];
$val2 = ($bytes[2] << 8) | $bytes[3];
echo "As two 16-bit values: $val1 and $val2\n";
?>