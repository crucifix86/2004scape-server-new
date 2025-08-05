<?php
// Diagnostic tool to understand save file structure

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$data = file_get_contents($saveFile);

echo "Save file size: " . strlen($data) . " bytes\n\n";

// Check key offsets
echo "Key data points:\n";
echo "================\n";

// Signature
echo "Signature (0x00): " . sprintf("0x%04X", unpack('n', substr($data, 0, 2))[1]) . "\n";

// Version
echo "Version (0x02): " . unpack('n', substr($data, 2, 2))[1] . "\n";

// Position
$x = unpack('n', substr($data, 4, 2))[1];
$z = unpack('n', substr($data, 6, 2))[1];
$level = unpack('n', substr($data, 8, 2))[1];
echo "Position (0x04): X=$x, Z=$z, Level=$level\n";

// Combat level at 0x0C?
echo "Value at 0x0C: " . unpack('n', substr($data, 0x0C, 2))[1] . " (combat level?)\n";
echo "Value at 0x0E: " . unpack('n', substr($data, 0x0E, 2))[1] . "\n";

// Gender
echo "Gender (0x15): " . ord($data[0x15]) . " (0=Male, 1=Female)\n";

// Run energy
echo "Run Energy (0x16): " . unpack('n', substr($data, 0x16, 2))[1] . "\n";

// Playtime
echo "Playtime (0x18): " . unpack('N', substr($data, 0x18, 4))[1] . "\n";

echo "\nSkills XP (starting at 0x1C):\n";
echo "==============================\n";
$skillNames = ['Attack', 'Defence', 'Strength', 'Hitpoints', 'Ranged', 'Prayer', 'Magic', 'Cooking', 
               'Woodcutting', 'Fletching', 'Fishing', 'Firemaking', 'Crafting', 'Smithing', 'Mining',
               'Herblore', 'Agility', 'Thieving', 'Stat18', 'Stat19', 'Runecrafting'];

$totalLevel = 0;
for ($i = 0; $i < 21; $i++) {
    $offset = 0x1C + ($i * 4);
    $xp = unpack('N', substr($data, $offset, 4))[1];
    $level = getLevelFromXP($xp);
    if ($i < 18 || $i == 20) { // Skip stat18/19 for total
        $totalLevel += $level;
    }
    echo sprintf("%-12s: XP=%8d, Level=%2d\n", $skillNames[$i], $xp, $level);
}

echo "\nCalculated Total Level: $totalLevel\n";

// Check for total level storage
echo "\nSearching for total level value ($totalLevel) in save file:\n";
// Check as 2-byte big-endian
$searchValue = pack('n', $totalLevel);
$positions = [];
$offset = 0;
while (($pos = strpos($data, $searchValue, $offset)) !== false) {
    $positions[] = sprintf("0x%04X", $pos);
    $offset = $pos + 1;
}
if ($positions) {
    echo "Found at positions: " . implode(", ", $positions) . "\n";
} else {
    echo "Not found as 2-byte value\n";
}

// Combat level calculation
$attack = getLevelFromXP(unpack('N', substr($data, 0x1C, 4))[1]);
$defence = getLevelFromXP(unpack('N', substr($data, 0x1C + 4, 4))[1]);
$strength = getLevelFromXP(unpack('N', substr($data, 0x1C + 8, 4))[1]);
$hitpoints = getLevelFromXP(unpack('N', substr($data, 0x1C + 12, 4))[1]);
$prayer = getLevelFromXP(unpack('N', substr($data, 0x1C + 20, 4))[1]);
$ranged = getLevelFromXP(unpack('N', substr($data, 0x1C + 16, 4))[1]);
$magic = getLevelFromXP(unpack('N', substr($data, 0x1C + 24, 4))[1]);

$base = 0.25 * ($defence + $hitpoints + floor($prayer / 2));
$melee = 0.325 * ($attack + $strength);
$range = 0.325 * (floor($ranged * 1.5));
$mage = 0.325 * (floor($magic * 1.5));
$combatLevel = floor($base + max($melee, $range, $mage));

echo "\nCombat Level Calculation:\n";
echo "Attack=$attack, Defence=$defence, Strength=$strength, HP=$hitpoints\n";
echo "Prayer=$prayer, Ranged=$ranged, Magic=$magic\n";
echo "Calculated Combat Level: $combatLevel\n";

function getLevelFromXP($xp) {
    $xpTable = [
        1 => 0, 2 => 83, 3 => 174, 4 => 276, 5 => 388, 6 => 512, 7 => 650, 8 => 801,
        9 => 969, 10 => 1154, 11 => 1358, 12 => 1584, 13 => 1833, 14 => 2107,
        15 => 2411, 16 => 2746, 17 => 3115, 18 => 3523, 19 => 3973, 20 => 4470,
        21 => 5018, 22 => 5624, 23 => 6291, 24 => 7028, 25 => 7842, 26 => 8740,
        27 => 9730, 28 => 10824, 29 => 12031, 30 => 13363, 31 => 14833, 32 => 16456,
        33 => 18247, 34 => 20224, 35 => 22406, 36 => 24815, 37 => 27473, 38 => 30408,
        39 => 33648, 40 => 37224, 41 => 41171, 42 => 45529, 43 => 50339, 44 => 55649,
        45 => 61512, 46 => 67983, 47 => 75127, 48 => 83014, 49 => 91721, 50 => 101333,
        51 => 111945, 52 => 123660, 53 => 136594, 54 => 150872, 55 => 166636,
        56 => 184040, 57 => 203254, 58 => 224466, 59 => 247886, 60 => 273742,
        61 => 302288, 62 => 333804, 63 => 368599, 64 => 407015, 65 => 449428,
        66 => 496254, 67 => 547953, 68 => 605032, 69 => 668051, 70 => 737627,
        71 => 814445, 72 => 899257, 73 => 992895, 74 => 1096278, 75 => 1210421,
        76 => 1336443, 77 => 1475581, 78 => 1629200, 79 => 1798808, 80 => 1986068,
        81 => 2192818, 82 => 2421087, 83 => 2673114, 84 => 2951373, 85 => 3258594,
        86 => 3597792, 87 => 3972294, 88 => 4385776, 89 => 4842295, 90 => 5346332,
        91 => 5902831, 92 => 6517253, 93 => 7195629, 94 => 7944614, 95 => 8771558,
        96 => 9684577, 97 => 10692629, 98 => 11805606, 99 => 13034431
    ];
    
    for ($level = 99; $level >= 1; $level--) {
        if ($xp >= $xpTable[$level]) {
            return $level;
        }
    }
    return 1;
}
?>