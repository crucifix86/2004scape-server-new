<?php
require_once 'lib/PlayerSaveParser.php';

$username = 'crucifix';
$saveFile = "/home/crucifix/Server/data/players/main/{$username}.sav";

echo "Testing inventory parser for: $username\n\n";

// Read raw data
$data = file_get_contents($saveFile);

// Jump directly to inventory position
$pos = 0x548; // Where the actual items start

echo "Reading inventory from position 0x548:\n";

$items = [];
for ($i = 0; $i < 28; $i++) {
    if ($pos + 2 >= strlen($data)) break;
    
    $itemId = (ord($data[$pos]) << 8) | ord($data[$pos + 1]);
    $pos += 2;
    
    if ($itemId == 0xFFFF || $itemId == 0) {
        // Empty slot
        continue;
    }
    
    if ($itemId > 20000) {
        // End of items
        break;
    }
    
    $count = ord($data[$pos]);
    $pos++;
    
    if ($count == 0xFF) {
        // Large stack
        $count = (ord($data[$pos]) << 24) | (ord($data[$pos+1]) << 16) | (ord($data[$pos+2]) << 8) | ord($data[$pos+3]);
        $pos += 4;
    }
    
    // Adjust item ID (game stores as ID + 1)
    $actualId = $itemId - 1;
    
    $items[] = [
        'slot' => $i,
        'id' => $actualId,
        'amount' => $count
    ];
    
    echo "  Slot $i: Item #$actualId x $count\n";
}

echo "\nTotal items found: " . count($items) . "\n";

// Now test the parser class
echo "\n\nTesting PlayerSaveParser class:\n";
$parser = new PlayerSaveParser($saveFile);
$playerData = $parser->parse();

echo "Inventory items from parser: " . count($playerData['inventories']['inventory']) . "\n";
if (count($playerData['inventories']['inventory']) > 0) {
    foreach ($playerData['inventories']['inventory'] as $item) {
        echo "  {$item['name']} x {$item['amount']}\n";
    }
}
?>