<?php
$saveFile = "/home/crucifix/Server/data/players/main/crucifix.sav";
$data = file_get_contents($saveFile);

echo "First 50 bytes (hex): ";
for ($i = 0; $i < 50; $i++) {
    printf("%02x ", ord($data[$i]));
    if (($i + 1) % 16 == 0) echo "\n                      ";
}
echo "\n\n";

// Parse correctly
$pos = 0;

// Version (2 bytes)
$version = (ord($data[$pos]) << 8) | ord($data[$pos+1]);
$pos += 2;
echo "Version: $version\n";

// Skip 2 bytes (likely padding or crc start)
$pos += 2;

// Position
$x = (ord($data[$pos]) << 8) | ord($data[$pos+1]);
$pos += 2;
$z = (ord($data[$pos]) << 8) | ord($data[$pos+1]);
$pos += 2;
$level = ord($data[$pos]);
$pos += 1;

echo "Position: X=$x, Z=$z, Level=$level\n";

// Appearance (7 bytes)
echo "Appearance bytes: ";
for ($i = 0; $i < 7; $i++) {
    echo ord($data[$pos]) . " ";
    $pos++;
}
echo "\n";

// Colors (5 bytes)
echo "Color bytes: ";
for ($i = 0; $i < 5; $i++) {
    echo ord($data[$pos]) . " ";
    $pos++;
}
echo "\n";

// Gender
$gender = ord($data[$pos]);
$pos++;
echo "Gender: $gender\n";

// Run energy (2 bytes)
$runEnergy = (ord($data[$pos]) << 8) | ord($data[$pos+1]);
$pos += 2;
echo "Run Energy: " . ($runEnergy / 100) . "%\n";

// Playtime (4 bytes)
$playtime = (ord($data[$pos]) << 24) | (ord($data[$pos+1]) << 16) | (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
$pos += 4;
echo "Playtime: $playtime ticks\n\n";

// Skills
echo "Skills (reading from position $pos):\n";
$skills = ['Attack', 'Defence', 'Strength', 'Hitpoints', 'Ranged', 'Prayer', 'Magic', 'Cooking', 'Woodcutting', 'Fletching', 'Fishing', 'Firemaking', 'Crafting', 'Smithing', 'Mining', 'Herblore', 'Agility', 'Thieving', 'Stat18', 'Stat19', 'Runecraft'];

for ($i = 0; $i < 21; $i++) {
    $xp = (ord($data[$pos]) << 24) | (ord($data[$pos+1]) << 16) | (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
    
    // Handle signed int
    if ($xp & 0x80000000) {
        $xp = $xp - 0x100000000;
    }
    
    echo "  Skill $i ({$skills[$i]}): XP = $xp (bytes: ";
    printf("%02x %02x %02x %02x", ord($data[$pos]), ord($data[$pos+1]), ord($data[$pos+2]), ord($data[$pos+3]));
    echo ")\n";
    
    $pos += 4;
}
?>