<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validation
if (strlen($username) < 3 || strlen($username) > 12) {
    echo json_encode(['success' => false, 'message' => 'Username must be between 3 and 12 characters']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
    exit;
}

if (strlen($password) < 5 || strlen($password) > 20) {
    echo json_encode(['success' => false, 'message' => 'Password must be between 5 and 20 characters']);
    exit;
}

// Path to 2004Scape server saves directory
// Using web-accessible directory for now (will sync with server later)
$savesDir = '/var/www/html/2004scape/data/saves';

// Try to create directory if it doesn't exist
if (!file_exists($savesDir)) {
    @mkdir($savesDir, 0777, true);
}

// Check if directory is accessible
if (!is_dir($savesDir)) {
    echo json_encode(['success' => false, 'message' => 'Server configuration error: Saves directory does not exist']);
    exit;
}

// Check if directory is writable
if (!is_writable($savesDir)) {
    echo json_encode(['success' => false, 'message' => 'Server configuration error: Saves directory not writable (permissions issue)']);
    exit;
}

// Convert username to lowercase for file storage (2004Scape convention)
$playerFile = $savesDir . '/' . strtolower($username) . '.json';

// Check if account already exists
if (file_exists($playerFile)) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit;
}

// Create player data structure matching 2004Scape format
$playerData = [
    'username' => $username,
    'displayName' => $username,
    'password' => $password, // In production, this should be hashed
    'email' => $email,
    'rights' => 0, // 0 = regular player, 1 = moderator, 2 = admin
    'x' => 3222, // Lumbridge spawn coordinates
    'y' => 3218,
    'height' => 0,
    'orientation' => 0,
    'lastLogin' => time() * 1000, // JavaScript timestamp format
    'createdOn' => time() * 1000,
    'muteTimeout' => 0,
    'banTimeout' => 0,
    'playTime' => 0,
    'skills' => [
        ['xp' => 0, 'level' => 1],      // Attack
        ['xp' => 0, 'level' => 1],      // Defence
        ['xp' => 0, 'level' => 1],      // Strength
        ['xp' => 1154, 'level' => 10],  // Hitpoints
        ['xp' => 0, 'level' => 1],      // Ranged
        ['xp' => 0, 'level' => 1],      // Prayer
        ['xp' => 0, 'level' => 1],      // Magic
        ['xp' => 0, 'level' => 1],      // Cooking
        ['xp' => 0, 'level' => 1],      // Woodcutting
        ['xp' => 0, 'level' => 1],      // Fletching
        ['xp' => 0, 'level' => 1],      // Fishing
        ['xp' => 0, 'level' => 1],      // Firemaking
        ['xp' => 0, 'level' => 1],      // Crafting
        ['xp' => 0, 'level' => 1],      // Smithing
        ['xp' => 0, 'level' => 1],      // Mining
        ['xp' => 0, 'level' => 1],      // Herblore
        ['xp' => 0, 'level' => 1],      // Agility
        ['xp' => 0, 'level' => 1],      // Thieving
        ['xp' => 0, 'level' => 1],      // Slayer
        ['xp' => 0, 'level' => 1],      // Farming
        ['xp' => 0, 'level' => 1],      // Runecraft
    ],
    'inventory' => [],
    'bank' => [],
    'equipment' => [],
    'quests' => [],
    'vars' => [],
    'varps' => [],
    'friends' => [],
    'ignores' => []
];

// Save player file
$jsonData = json_encode($playerData, JSON_PRETTY_PRINT);
if (file_put_contents($playerFile, $jsonData) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to create account']);
    exit;
}

// Update the website stats
$statsFile = '/home/crucifix/Server/website-stats.json';
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
    $stats['accountsRegistered'] = (isset($stats['accountsRegistered']) ? $stats['accountsRegistered'] : 0) + 1;
    $stats['lastUpdate'] = date('c');
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
}

// Log the registration
$logFile = '/var/www/html/2004scape/data/registrations.log';
$logEntry = date('Y-m-d H:i:s') . " - New account: $username (IP: " . $_SERVER['REMOTE_ADDR'] . ")\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

// Sync to game server
$gameSavesDir = '/home/crucifix/Server/data/saves';
if (is_dir($gameSavesDir) && is_writable($gameSavesDir)) {
    // Copy the account file to the game server
    @copy($playerFile, $gameSavesDir . '/' . basename($playerFile));
} else {
    // If direct copy fails, use the sync script
    @exec('/var/www/html/2004scape/sync-accounts.sh');
}

// Return success
echo json_encode([
    'success' => true,
    'message' => 'Account created successfully',
    'username' => $username
]);
?>