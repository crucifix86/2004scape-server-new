<?php
require_once 'lib/RealPlayerSaveParser.php';

echo "=== TESTING REAL SAVE PARSER ===\n\n";

try {
    $parser = new RealPlayerSaveParser('/home/crucifix/Server/data/players/main/crucifix.sav');
    $data = $parser->parse();
    
    echo "\n=== FINAL RESULTS ===\n";
    echo "Combat Level: " . $data['combat_level'] . "\n";
    echo "Total Level: " . $data['total_level'] . "\n";
    echo "HP: Level " . $data['skills']['Hitpoints']['current'] . "/" . $data['skills']['Hitpoints']['level'] . " (XP: " . number_format($data['skills']['Hitpoints']['experience']) . ")\n";
    
    echo "\nAll skills:\n";
    foreach ($data['skills'] as $skill => $info) {
        if ($skill === 'Stat18' || $skill === 'Stat19') continue;
        printf("%-12s: Level %2d (XP: %s)\n", $skill, $info['level'], number_format($info['experience']));
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>