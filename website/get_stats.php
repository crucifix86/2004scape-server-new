<?php
// Get server statistics for display on website
header('Content-Type: application/json');

$dataFile = 'data/server_stats.json';

if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
    
    // Clean up old world data (older than 5 minutes)
    if (isset($data['worlds'])) {
        foreach ($data['worlds'] as $worldId => $worldData) {
            if (time() - $worldData['last_update'] > 300) {
                unset($data['worlds'][$worldId]);
            }
        }
    }
    
    // Recalculate total
    $totalPlayers = 0;
    if (isset($data['worlds'])) {
        foreach ($data['worlds'] as $worldData) {
            $totalPlayers += $worldData['players_online'];
        }
    }
    $data['total_players_online'] = $totalPlayers;
    
    echo json_encode($data);
} else {
    // Return default data if file doesn't exist
    echo json_encode(array(
        'total_players_online' => 0,
        'registered_accounts' => 0,
        'worlds' => array()
    ));
}
?>