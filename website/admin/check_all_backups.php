<?php
require_once 'lib/PlayerSaveParser.php';

echo "=== CHECKING ALL BACKUP FILES ===\n\n";

$backups = [
    '/home/crucifix/Server/data/players/main/crucifix.sav',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754027631',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028095',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028110',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028111',
    '/home/crucifix/Server/data/players/main/crucifix.sav.backup_1754028115'
];

foreach ($backups as $file) {
    echo "File: " . basename($file) . "\n";
    echo "Time: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
    
    try {
        $parser = new PlayerSaveParser($file);
        $data = $parser->parse();
        
        echo "Combat Level: " . $data['combat_level'] . "\n";
        echo "HP Level: " . $data['skills']['Hitpoints']['level'] . "\n";
        echo "HP XP: " . $data['skills']['Hitpoints']['experience'] . "\n";
        echo "Inventory Items: " . count($data['inventories']['inventory']) . "\n";
        
        // Show first few inventory items
        if (!empty($data['inventories']['inventory'])) {
            echo "First items: ";
            $shown = 0;
            foreach ($data['inventories']['inventory'] as $item) {
                echo $item['name'] . " ";
                if (++$shown >= 3) break;
            }
            echo "\n";
        }
        
        // Check if it's the "good" save with your items
        $hasYourItems = false;
        foreach ($data['inventories']['inventory'] as $item) {
            if (strpos($item['name'], 'Bronze') !== false || 
                strpos($item['name'], 'Leather') !== false ||
                strpos($item['name'], 'rune') !== false) {
                $hasYourItems = true;
                break;
            }
        }
        
        if ($hasYourItems) {
            echo "*** THIS LOOKS LIKE YOUR ORIGINAL SAVE WITH ITEMS! ***\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "---\n\n";
}

// Also check the temp files I created
$tempFiles = [
    '/tmp/crucifix_fixed.sav',
    '/tmp/crucifix_starter_fixed.sav'
];

echo "=== TEMP FILES ===\n";
foreach ($tempFiles as $file) {
    if (file_exists($file)) {
        echo basename($file) . " exists (created " . date('Y-m-d H:i:s', filemtime($file)) . ")\n";
    }
}
?>