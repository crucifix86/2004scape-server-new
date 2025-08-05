<?php
// Analyze the real save file format to find correct offsets

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$data = file_get_contents($saveFile);

echo "=== ANALYZING REAL SAVE FILE FORMAT ===\n\n";
echo "File size: " . strlen($data) . " bytes\n\n";

// Show first 200 bytes in hex with ASCII
echo "First 200 bytes (hex + ASCII):\n";
for ($i = 0; $i < min(200, strlen($data)); $i += 16) {
    printf("%04X: ", $i);
    
    // Hex part
    for ($j = 0; $j < 16 && $i + $j < strlen($data); $j++) {
        printf("%02X ", ord($data[$i + $j]));
    }
    
    // Padding for incomplete lines
    for ($j = ($i + 16 > strlen($data) ? strlen($data) - $i : 16); $j < 16; $j++) {
        echo "   ";
    }
    
    echo " | ";
    
    // ASCII part
    for ($j = 0; $j < 16 && $i + $j < strlen($data); $j++) {
        $char = $data[$i + $j];
        echo (ord($char) >= 32 && ord($char) <= 126) ? $char : '.';
    }
    
    echo "\n";
}

echo "\n=== SEARCHING FOR HP XP (1154 = 0x482) ===\n";
// Look for the bytes that represent 1154 in different byte orders
$patterns = [
    pack('N', 1154),    // Big-endian 4-byte
    pack('V', 1154),    // Little-endian 4-byte  
    pack('n', 1154),    // Big-endian 2-byte
    pack('v', 1154),    // Little-endian 2-byte
];

$patternNames = ['Big-endian 4-byte', 'Little-endian 4-byte', 'Big-endian 2-byte', 'Little-endian 2-byte'];

for ($p = 0; $p < count($patterns); $p++) {
    $pattern = $patterns[$p];
    $name = $patternNames[$p];
    
    $offset = 0;
    $found = [];
    while (($pos = strpos($data, $pattern, $offset)) !== false) {
        $found[] = sprintf("0x%04X (%d)", $pos, $pos);
        $offset = $pos + 1;
    }
    
    if ($found) {
        echo "$name (1154): Found at " . implode(", ", $found) . "\n";
    }
}

echo "\n=== SEARCHING FOR COMBAT LEVEL 3 ===\n";
// Look for combat level 3
$patterns = [
    pack('N', 3),    // Big-endian 4-byte
    pack('V', 3),    // Little-endian 4-byte  
    pack('n', 3),    // Big-endian 2-byte
    pack('v', 3),    // Little-endian 2-byte
    chr(3),          // Single byte
];

$patternNames = ['Big-endian 4-byte', 'Little-endian 4-byte', 'Big-endian 2-byte', 'Little-endian 2-byte', 'Single byte'];

for ($p = 0; $p < count($patterns); $p++) {
    $pattern = $patterns[$p];
    $name = $patternNames[$p];
    
    $offset = 0;
    $found = [];
    while (($pos = strpos($data, $pattern, $offset)) !== false) {
        $found[] = sprintf("0x%04X (%d)", $pos, $pos);
        $offset = $pos + 1;
    }
    
    if ($found) {
        echo "$name (3): Found at " . implode(", ", $found) . "\n";
    }
}

echo "\n=== TESTING DIFFERENT SKILL XP INTERPRETATIONS ===\n";
// Try reading skills from different starting positions
$testOffsets = [0x1C, 0x20, 0x24, 0x28, 0x30, 0x40, 0x50];

foreach ($testOffsets as $startOffset) {
    echo "\nTrying skills starting at offset 0x" . sprintf("%02X", $startOffset) . ":\n";
    
    for ($skill = 0; $skill < 5; $skill++) { // Just check first 5 skills
        $offset = $startOffset + ($skill * 4);
        if ($offset + 4 <= strlen($data)) {
            $xp = unpack('N', substr($data, $offset, 4))[1]; // Big-endian
            echo sprintf("  Skill %d (0x%02X): XP = %d\n", $skill, $offset, $xp);
        }
    }
}
?>