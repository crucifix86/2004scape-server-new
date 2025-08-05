<?php
$saveFile = "/home/crucifix/Server/data/players/main/crucifix.sav";
$data = file_get_contents($saveFile);

// Start after version and skip to position 4
$pos = 4;

// Read position
$x = (ord($data[$pos]) << 8) | ord($data[$pos+1]);
$z = (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
$level = ord($data[$pos+4]);

echo "Position: X=$x, Z=$z, Level=$level\n";

// Skip appearance (7 bytes) + colors (5 bytes) + gender (1 byte) = 13 bytes
$pos += 5 + 13;

// Read run energy (2 bytes)
$runEnergy = (ord($data[$pos]) << 8) | ord($data[$pos+1]);
$pos += 2;

echo "Run Energy: " . ($runEnergy / 100) . "%\n";

// Read playtime (4 bytes for version >= 2)
$playtime = (ord($data[$pos]) << 24) | (ord($data[$pos+1]) << 16) | (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
$pos += 4;

echo "Playtime: $playtime ticks = " . round($playtime * 0.6 / 60, 1) . " hours\n\n";

// Read skills - 21 skills x 4 bytes each = 84 bytes
echo "Skills (XP values):\n";
$skills = ['Attack', 'Defence', 'Strength', 'Hitpoints', 'Ranged', 'Prayer', 'Magic', 'Cooking', 'Woodcutting', 'Fletching', 'Fishing', 'Firemaking', 'Crafting', 'Smithing', 'Mining', 'Herblore', 'Agility', 'Thieving', 'Stat18', 'Stat19', 'Runecraft'];

$totalLevel = 0;
for ($i = 0; $i < 21; $i++) {
    $xp = (ord($data[$pos]) << 24) | (ord($data[$pos+1]) << 16) | (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
    $pos += 4;
    
    // Calculate level from XP
    $level = 1;
    if ($xp > 0) {
        $points = 0;
        for ($lvl = 1; $lvl < 99; $lvl++) {
            $points += floor($lvl + 300 * pow(2, $lvl / 7));
            if (floor($points / 4) > $xp) {
                break;
            }
            $level = $lvl + 1;
        }
    }
    
    if ($i < 18 || $i == 20) { // Skip stat18 and stat19
        $totalLevel += $level;
        echo "  {$skills[$i]}: XP=$xp, Level=$level\n";
    }
}

echo "\nTotal Level: $totalLevel\n";

// Read current levels (21 bytes)
echo "\nCurrent levels (with boosts/drains):\n";
for ($i = 0; $i < 21; $i++) {
    $currentLevel = ord($data[$pos]);
    $pos++;
    if ($i < 18 || $i == 20) {
        echo "  {$skills[$i]}: $currentLevel\n";
    }
}

// Calculate combat level correctly
function calculateCombat($skills_data) {
    // For a starter account with all 1s except HP at 10
    $attack = 1;
    $strength = 1; 
    $defence = 1;
    $hitpoints = 10;
    $prayer = 1;
    $ranged = 1;
    $magic = 1;
    
    $base = 0.25 * ($defence + $hitpoints + floor($prayer / 2));
    $melee = 0.325 * ($attack + $strength);
    $ranger = 0.325 * (floor($ranged * 1.5));
    $mage = 0.325 * (floor($magic * 1.5));
    
    return floor($base + max($melee, $ranger, $mage));
}

echo "\nCombat Level (starter): " . calculateCombat([]) . "\n";
?>