<?php
/**
 * Reverse engineer the REAL save file format
 * Let's figure out where the actual stat data is stored
 */

$saveFile = '/home/crucifix/Server/data/players/main/crucifix.sav';
$data = file_get_contents($saveFile);

echo "=== REVERSE ENGINEERING SAVE FORMAT ===\n\n";
echo "File size: " . strlen($data) . " bytes\n\n";

// Since the game shows correct starter stats, let's look for patterns that match:
// - HP Level 10 (or HP XP 1154)
// - Combat Level 3
// - Total Level 28

echo "=== SEARCHING FOR EXPECTED VALUES ===\n";

// Search for different representations of key values
$searchValues = [
    'HP XP 1154' => [
        'big4' => pack('N', 1154),      // 0x00000482
        'little4' => pack('V', 1154),   // 0x82040000
        'big2' => pack('n', 1154),      // 0x0482
        'little2' => pack('v', 1154),   // 0x8204
        'byte' => chr(1154 % 256)       // Just the low byte
    ],
    'Combat Level 3' => [
        'big4' => pack('N', 3),
        'little4' => pack('V', 3),
        'big2' => pack('n', 3),
        'little2' => pack('v', 3),
        'byte' => chr(3)
    ],
    'HP Level 10' => [
        'big4' => pack('N', 10),
        'little4' => pack('V', 10),
        'big2' => pack('n', 10),
        'little2' => pack('v', 10),  
        'byte' => chr(10)
    ],
    'Total Level 28' => [
        'big4' => pack('N', 28),
        'little4' => pack('V', 28),
        'big2' => pack('n', 28),
        'little2' => pack('v', 28),
        'byte' => chr(28)
    ]
];

foreach ($searchValues as $valueName => $patterns) {
    echo "\nSearching for $valueName:\n";
    
    foreach ($patterns as $format => $pattern) {
        $offset = 0;
        $found = [];
        
        while (($pos = strpos($data, $pattern, $offset)) !== false) {
            $found[] = sprintf("0x%04X (%d)", $pos, $pos);
            $offset = $pos + 1;
        }
        
        if ($found) {
            echo "  $format: " . implode(", ", $found) . "\n";
        }
    }
}

echo "\n=== ANALYZING COMMON PATTERNS ===\n";

// Look for sequences that might represent stats
// Check for patterns of similar values that could be skill arrays

echo "Looking for arrays of similar small values (possible levels 1-99):\n";

$possibleStatArrays = [];
for ($start = 0; $start < strlen($data) - 84; $start += 4) { // 21 skills * 4 bytes
    $couldBeStats = true;
    $values = [];
    
    // Check if this could be a stat array (21 consecutive reasonable values)
    for ($i = 0; $i < 21; $i++) {
        $offset = $start + ($i * 4);
        if ($offset + 4 > strlen($data)) {
            $couldBeStats = false;
            break;
        }
        
        // Try both byte orders
        $bigEndian = unpack('N', substr($data, $offset, 4))[1];
        $littleEndian = unpack('V', substr($data, $offset, 4))[1];
        
        // Check if either value looks like reasonable XP (0 to ~200M)
        if ($bigEndian <= 200000000 && $bigEndian >= 0) {
            $values[] = ['big', $bigEndian];
        } else if ($littleEndian <= 200000000 && $littleEndian >= 0) {
            $values[] = ['little', $littleEndian];
        } else {
            $couldBeStats = false;
            break;
        }
    }
    
    if ($couldBeStats && count($values) == 21) {
        $possibleStatArrays[] = [
            'offset' => $start,
            'values' => $values
        ];
    }
}

echo "Found " . count($possibleStatArrays) . " possible stat arrays:\n";

foreach ($possibleStatArrays as $idx => $array) {
    echo "\nArray #$idx starting at offset 0x" . sprintf('%04X', $array['offset']) . ":\n";
    
    $totalLevel = 0;
    $hasReasonableHP = false;
    
    for ($i = 0; $i < min(21, count($array['values'])); $i++) {
        list($byteOrder, $xp) = $array['values'][$i];
        $level = getLevelFromXP($xp);
        
        if ($i == 3 && $level == 10) { // HP at level 10?
            $hasReasonableHP = true;
        }
        
        if ($i != 18 && $i != 19) { // Skip disabled stats
            $totalLevel += $level;
        }
        
        if ($level > 0 && $level <= 99) {
            echo sprintf("  Skill %2d: XP=%8d, Level=%2d (%s-endian)\n", $i, $xp, $level, $byteOrder);
        }
    }
    
    echo "  Total Level: $totalLevel\n";
    
    if ($hasReasonableHP && $totalLevel >= 20 && $totalLevel <= 40) {
        echo "  *** THIS LOOKS PROMISING FOR STARTER STATS! ***\n";
    }
}

function getLevelFromXP($xp) {
    $xpTable = [
        1 => 0, 2 => 83, 3 => 174, 4 => 276, 5 => 388, 6 => 512, 7 => 650, 8 => 801,
        9 => 969, 10 => 1154, 11 => 1358, 12 => 1584, 13 => 1833, 14 => 2107,
        15 => 2411, 16 => 2746, 17 => 3115, 18 => 3523, 19 => 3973, 20 => 4470
        // truncated for brevity
    ];
    
    for ($level = 20; $level >= 1; $level--) {
        if (isset($xpTable[$level]) && $xp >= $xpTable[$level]) {
            return $level;
        }
    }
    return 1;
}

echo "\n=== EXAMINING BYTES AROUND KEY LOCATIONS ===\n";

// Let's look at what's around common offsets
$keyOffsets = [0x1C, 0x28, 0x0C, 0x70, 0x100, 0x200, 0x300];

foreach ($keyOffsets as $offset) {
    if ($offset + 32 < strlen($data)) {
        echo "\nBytes at offset 0x" . sprintf('%04X', $offset) . ":\n";
        echo "Hex: ";
        for ($i = 0; $i < 32; $i++) {
            printf("%02X ", ord($data[$offset + $i]));
        }
        echo "\nDec: ";
        for ($i = 0; $i < 32; $i++) {
            printf("%3d ", ord($data[$offset + $i]));
        }
        echo "\n";
    }
}
?>