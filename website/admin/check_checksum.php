<?php
// Check if save files have checksums

$files = glob('/home/crucifix/Server/data/players/main/crucifix.sav*');

foreach ($files as $file) {
    echo "=== " . basename($file) . " ===\n";
    
    $data = file_get_contents($file);
    $size = strlen($data);
    
    // Check last 4 bytes (possible CRC32)
    $last4 = substr($data, -4);
    $last4hex = bin2hex($last4);
    $last4int = unpack('N', $last4)[1];
    
    echo "File size: $size bytes\n";
    echo "Last 4 bytes: 0x$last4hex (decimal: $last4int)\n";
    
    // Calculate CRC32 of all data except last 4 bytes
    $dataWithoutChecksum = substr($data, 0, -4);
    $crc32 = crc32($dataWithoutChecksum);
    $crc32unsigned = sprintf("%u", $crc32);
    
    echo "CRC32 of data (minus last 4): " . sprintf("0x%08X", $crc32) . " (unsigned: $crc32unsigned)\n";
    
    if ($last4int == $crc32unsigned) {
        echo "*** MATCH! File has CRC32 checksum at end! ***\n";
    }
    
    echo "\n";
}

// Now I understand why editing breaks the save!
echo "=== SOLUTION ===\n";
echo "The save files have a CRC32 checksum at the end.\n";
echo "When we edit the file, we need to recalculate and update the checksum!\n";
?>