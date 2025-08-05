<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config/database.php';

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

// Get database connection
$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE LOWER(username) = LOWER(?)");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }
    
    // Hash the password
    $passwordHash = hashPassword($password);
    
    // Get client IP
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    // Insert account (using 'password' column to match game server)
    $stmt = $pdo->prepare("
        INSERT INTO accounts (username, display_name, password, email, ip_address, rights)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$username, $username, $passwordHash, $email, $ipAddress]);
    
    $accountId = $pdo->lastInsertId();
    
    // Insert player data with default spawn location
    $stmt = $pdo->prepare("
        INSERT INTO player_data (account_id, x, y, height, orientation, run_energy)
        VALUES (?, 3222, 3218, 0, 0, 10000)
    ");
    $stmt->execute([$accountId]);
    
    // Insert default skills
    $skills = [
        0 => ['name' => 'Attack', 'xp' => 0, 'level' => 1],
        1 => ['name' => 'Defence', 'xp' => 0, 'level' => 1],
        2 => ['name' => 'Strength', 'xp' => 0, 'level' => 1],
        3 => ['name' => 'Hitpoints', 'xp' => 1154, 'level' => 10],
        4 => ['name' => 'Ranged', 'xp' => 0, 'level' => 1],
        5 => ['name' => 'Prayer', 'xp' => 0, 'level' => 1],
        6 => ['name' => 'Magic', 'xp' => 0, 'level' => 1],
        7 => ['name' => 'Cooking', 'xp' => 0, 'level' => 1],
        8 => ['name' => 'Woodcutting', 'xp' => 0, 'level' => 1],
        9 => ['name' => 'Fletching', 'xp' => 0, 'level' => 1],
        10 => ['name' => 'Fishing', 'xp' => 0, 'level' => 1],
        11 => ['name' => 'Firemaking', 'xp' => 0, 'level' => 1],
        12 => ['name' => 'Crafting', 'xp' => 0, 'level' => 1],
        13 => ['name' => 'Smithing', 'xp' => 0, 'level' => 1],
        14 => ['name' => 'Mining', 'xp' => 0, 'level' => 1],
        15 => ['name' => 'Herblore', 'xp' => 0, 'level' => 1],
        16 => ['name' => 'Agility', 'xp' => 0, 'level' => 1],
        17 => ['name' => 'Thieving', 'xp' => 0, 'level' => 1],
        18 => ['name' => 'Slayer', 'xp' => 0, 'level' => 1],
        19 => ['name' => 'Farming', 'xp' => 0, 'level' => 1],
        20 => ['name' => 'Runecraft', 'xp' => 0, 'level' => 1],
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO player_skills (account_id, skill_id, level, experience)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($skills as $skillId => $skill) {
        $stmt->execute([$accountId, $skillId, $skill['level'], $skill['xp']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // IMPORTANT: Also create a minimal save file so the game recognizes the account
    // The game auto-creates accounts but we want it to exist first
    $username_lower = strtolower($username);
    $savePath = "/home/crucifix/Server/data/players/main/{$username_lower}.sav";
    
    if (!file_exists($savePath)) {
        // Create an empty save file - the game will initialize it properly on first login
        // Just needs to exist for the game to recognize the account
        @touch($savePath);
        @chmod($savePath, 0666);
    }
    
    // Log the registration
    $logFile = '/var/www/html/2004scape/data/registrations.log';
    $logEntry = date('Y-m-d H:i:s') . " - New account: $username (ID: $accountId, IP: $ipAddress) [SQL]\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'username' => $username,
        'account_id' => $accountId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
?>