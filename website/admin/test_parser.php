<?php
require_once 'lib/PlayerSaveParser.php';

$username = 'crucifix';
$saveFile = "/home/crucifix/Server/data/players/main/{$username}.sav";

if (file_exists($saveFile)) {
    try {
        $parser = new PlayerSaveParser($saveFile);
        $playerData = $parser->parse();
        
        echo "Player: $username\n";
        echo "Combat Level: {$playerData['combat_level']}\n";
        echo "Position: X={$playerData['position']['x']}, Z={$playerData['position']['z']}\n";
        echo "\nSkills:\n";
        
        $totalLevel = 0;
        foreach ($playerData['skills'] as $skillName => $skill) {
            if ($skillName !== 'Stat18' && $skillName !== 'Stat19') {
                echo "  $skillName: Level {$skill['level']}, XP: {$skill['experience']}\n";
                $totalLevel += $skill['level'];
            }
        }
        
        echo "\nTotal Level: $totalLevel\n";
        
        echo "\nInventory: " . count($playerData['inventories']['inventory'] ?? []) . " items\n";
        echo "Bank: " . count($playerData['inventories']['bank'] ?? []) . " items\n";
        echo "Equipment: " . count($playerData['inventories']['equipment'] ?? []) . " items\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Save file not found: $saveFile\n";
}
?>