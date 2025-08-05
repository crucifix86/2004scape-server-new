<?php
// Simple save file comparison focused on finding the XP change

$before = file_get_contents('/home/crucifix/Server/BEFORE_XP_CHANGE.sav');
$after = file_get_contents('/home/crucifix/Server/data/players/main/crucifix.sav');

echo "=== SIMPLE SAVE COMPARISON ===\n\n";
echo "Looking for changes that could be +25 XP...\n\n";

// Look for 4-byte sequences that changed by exactly 25
echo "=== SEARCHING FOR +25 CHANGES ===\n";

for ($i = 0; $i < min(strlen($before), strlen($after)) - 4; $i += 1) {
    // Check both byte orders
    $beforeBig = unpack('N', substr($before, $i, 4))[1];
    $afterBig = unpack('N', substr($after, $i, 4))[1];
    
    $beforeLittle = unpack('V', substr($before, $i, 4))[1];
    $afterLittle = unpack('V', substr($after, $i, 4))[1];
    
    // Check if either changed by exactly 25
    if ($afterBig - $beforeBig == 25) {
        printf("FOUND +25 at offset 0x%04X (big-endian): %d -> %d\n", $i, $beforeBig, $afterBig);
    }
    
    if ($afterLittle - $beforeLittle == 25) {
        printf("FOUND +25 at offset 0x%04X (little-endian): %d -> %d\n", $i, $beforeLittle, $afterLittle);
    }
}

echo "\n=== CHECKING SPECIFIC KEY OFFSETS ===\n";

// Check the areas that had changes
$keyOffsets = [0x052E, 0x0047, 0x001B];

foreach ($keyOffsets as $offset) {
    if ($offset + 4 <= min(strlen($before), strlen($after))) {
        echo "\nOffset 0x" . sprintf('%04X', $offset) . ":\n";
        
        // Show raw bytes
        echo "Before: ";
        for ($i = 0; $i < 8; $i++) {
            printf("%02X ", ord($before[$offset + $i]));
        }
        echo "\nAfter:  ";
        for ($i = 0; $i < 8; $i++) {
            printf("%02X ", ord($after[$offset + $i]));
        }
        
        // Try interpreting as different value types
        $beforeBig = unpack('N', substr($before, $offset, 4))[1];
        $afterBig = unpack('N', substr($after, $offset, 4))[1];
        
        echo "\nBig-endian 4-byte: $beforeBig -> $afterBig (change: " . ($afterBig - $beforeBig) . ")\n";
        
        if ($afterBig - $beforeBig == 25) {
            echo "*** THIS IS THE WOODCUTTING XP! ***\n";
        }
    }
}

echo "\n=== EXAMINING WOODCUTTING SKILL AREA ===\n";
// Woodcutting is skill index 8, so if skills start at 0x1C, it should be at 0x1C + (8*4) = 0x3C
$possibleWoodcuttingOffsets = [0x3C, 0x40, 0x44, 0x48, 0x052E];

foreach ($possibleWoodcuttingOffsets as $offset) {
    if ($offset + 4 <= min(strlen($before), strlen($after))) {
        $beforeVal = unpack('N', substr($before, $offset, 4))[1];
        $afterVal = unpack('N', substr($after, $offset, 4))[1];
        $change = $afterVal - $beforeVal;
        
        printf("Offset 0x%04X: %d -> %d (change: %+d)\n", $offset, $beforeVal, $afterVal, $change);
        
        if ($change == 25) {
            echo "  *** WOODCUTTING XP FOUND! ***\n";
        }
    }
}
?>