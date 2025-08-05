<?php
/**
 * Compare two save files to find what changed
 */

if ($argc < 3) {
    echo "Usage: php compare_saves.php <before_file> <after_file>\n";
    exit(1);
}

$beforeFile = $argv[1];
$afterFile = $argv[2];

if (!file_exists($beforeFile) || !file_exists($afterFile)) {
    echo "Error: One or both files don't exist\n";
    exit(1);
}

$before = file_get_contents($beforeFile);
$after = file_get_contents($afterFile);

echo "=== SAVE FILE COMPARISON ===\n\n";
echo "Before file: $beforeFile (" . strlen($before) . " bytes)\n";
echo "After file: $afterFile (" . strlen($after) . " bytes)\n\n";

if (strlen($before) != strlen($after)) {
    echo "WARNING: File sizes are different!\n\n";
}

$differences = [];
$maxLen = max(strlen($before), strlen($after));

for ($i = 0; $i < $maxLen; $i++) {
    $beforeByte = $i < strlen($before) ? ord($before[$i]) : 'EOF';
    $afterByte = $i < strlen($after) ? ord($after[$i]) : 'EOF';
    
    if ($beforeByte !== $afterByte) {
        $differences[] = [
            'offset' => $i,
            'before' => $beforeByte,
            'after' => $afterByte
        ];
    }
}

echo "Found " . count($differences) . " byte differences:\n\n";

foreach ($differences as $diff) {
    $offset = $diff['offset'];
    $before = $diff['before'];
    $after = $diff['after'];
    
    printf("Offset 0x%04X (%4d): %3s -> %3s", $offset, $offset, $before, $after);
    
    // Show context (8 bytes before and after)
    echo " | Context: ";
    $start = max(0, $offset - 4);
    $end = min(strlen($after), $offset + 5);
    
    for ($j = $start; $j < $end; $j++) {
        if ($j == $offset) {
            printf("[%02X]", ord($after[$j]));
        } else {
            printf("%02X", ord($after[$j]));
        }
        echo " ";
    }
    echo "\n";
}

// If differences are clustered, show them as groups
if (count($differences) > 1) {
    echo "\n=== DIFFERENCE ANALYSIS ===\n";
    
    // Group consecutive differences
    $groups = [];
    $currentGroup = [$differences[0]];
    
    for ($i = 1; $i < count($differences); $i++) {
        if ($differences[$i]['offset'] == $differences[$i-1]['offset'] + 1) {
            $currentGroup[] = $differences[$i];
        } else {
            $groups[] = $currentGroup;
            $currentGroup = [$differences[$i]];
        }
    }
    $groups[] = $currentGroup;
    
    foreach ($groups as $idx => $group) {
        $start = $group[0]['offset'];
        $end = $group[count($group)-1]['offset'];
        $size = count($group);
        
        echo "Group #" . ($idx + 1) . ": ";
        printf("0x%04X-0x%04X (%d bytes) - ", $start, $end, $size);
        
        if ($size == 4) {
            echo "Likely 4-byte value (int/XP)";
        } else if ($size == 2) {
            echo "Likely 2-byte value (short)";
        } else if ($size == 1) {
            echo "Single byte change";
        } else {
            echo "$size consecutive bytes";
        }
        echo "\n";
        
        // Show the actual value change for multi-byte groups
        if ($size >= 2 && $size <= 8) {
            $beforeBytes = '';
            $afterBytes = '';
            foreach ($group as $diff) {
                $beforeBytes .= chr($diff['before']);
                $afterBytes .= chr($diff['after']);
            }
            
            if ($size == 4) {
                $beforeVal = unpack('N', $beforeBytes)[1]; // Big-endian
                $afterVal = unpack('N', $afterBytes)[1];
                echo "  Big-endian 4-byte: $beforeVal -> $afterVal (change: " . ($afterVal - $beforeVal) . ")\n";
                
                $beforeVal = unpack('V', $beforeBytes)[1]; // Little-endian
                $afterVal = unpack('V', $afterBytes)[1];
                echo "  Little-endian 4-byte: $beforeVal -> $afterVal (change: " . ($afterVal - $beforeVal) . ")\n";
            } else if ($size == 2) {
                $beforeVal = unpack('n', $beforeBytes)[1]; // Big-endian
                $afterVal = unpack('n', $afterBytes)[1];
                echo "  Big-endian 2-byte: $beforeVal -> $afterVal (change: " . ($afterVal - $beforeVal) . ")\n";
            }
        }
        echo "\n";
    }
}
?>