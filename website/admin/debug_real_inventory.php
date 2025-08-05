<?php
$saveFile = "/home/crucifix/Server/data/players/main/crucifix.sav";
$data = file_get_contents($saveFile);

echo "Save file size: " . strlen($data) . " bytes\n\n";

// The inventory data seems to start around 0x540
// Let's look for the inventory marker
$pos = 0x540;

echo "Data around inventory section (0x540):\n";
for ($i = 0; $i < 100; $i++) {
    if ($pos + $i >= strlen($data)) break;
    
    $byte = ord($data[$pos + $i]);
    if ($byte > 0) {
        printf("Position 0x%04x: 0x%02x (%d)\n", $pos + $i, $byte, $byte);
    }
}

// Try to parse as inventory
echo "\n\nTrying to parse as inventory items:\n";
$pos = 0x540;

// Skip to what looks like inventory count
$invCount = ord($data[$pos + 2]); // 0x5d = 93 = inventory type
echo "Inventory type ID: $invCount\n";

$pos = 0x543; // Start of items
$itemCount = 0;

// Read items (format appears to be 2 bytes item ID, 1 byte count)
for ($i = 0; $i < 28; $i++) { // 28 inventory slots
    if ($pos + 2 >= strlen($data)) break;
    
    $itemId = (ord($data[$pos]) << 8) | ord($data[$pos + 1]);
    $pos += 2;
    
    if ($itemId == 0xFFFF || $itemId == 0) {
        // Empty slot
        continue;
    }
    
    $count = ord($data[$pos]);
    $pos++;
    
    if ($count == 0xFF) {
        // Large stack, read 4 more bytes
        $count = (ord($data[$pos]) << 24) | (ord($data[$pos+1]) << 16) | (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
        $pos += 4;
    }
    
    if ($itemId > 0 && $itemId < 20000) {
        $itemCount++;
        echo "  Slot $i: Item ID $itemId x $count\n";
    }
}

echo "\nTotal items in inventory: $itemCount\n";
?>