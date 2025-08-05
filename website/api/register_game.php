<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Path to game's SQLite database (now in Server directory)
$dbPath = '/home/crucifix/Server/db.sqlite';

// Check if database exists
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'message' => 'Game database not found. Start the game server first.']);
    exit;
}

// Create database if it doesn't exist (with game's schema)
$newDb = !file_exists($dbPath);

try {
    // Connect to SQLite
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // If new database, create the game's schema
    if ($newDb) {
        // Create account table matching game's schema
        $db->exec("
            CREATE TABLE IF NOT EXISTS account (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                registration_ip TEXT,
                registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                logged_in INTEGER DEFAULT 0,
                login_time DATETIME,
                logged_out INTEGER DEFAULT 0,
                logout_time DATETIME,
                muted_until DATETIME,
                banned_until DATETIME,
                staffmodlevel INTEGER DEFAULT 0,
                members INTEGER DEFAULT 0,
                email TEXT,
                password_updated DATETIME,
                oauth_provider TEXT
            )
        ");
        
        // Create other tables the game expects
        $db->exec("
            CREATE TABLE IF NOT EXISTS login (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL,
                account_id INTEGER NOT NULL,
                world INTEGER NOT NULL,
                ip TEXT NOT NULL,
                timestamp DATETIME NOT NULL
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS session (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uid TEXT NOT NULL,
                account_id INTEGER NOT NULL,
                timestamp DATETIME NOT NULL
            )
        ");
    }
    
    // Check if username already exists
    $stmt = $db->prepare("SELECT id FROM account WHERE LOWER(username) = LOWER(?)");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }
    
    // Hash password using bcrypt - MUST use $2b$ format for Node.js compatibility
    // PHP's password_hash creates $2y$ which Node.js bcrypt can't verify
    $lowercasePassword = strtolower($password);
    
    // Use Node.js bcrypt to generate compatible hash
    $command = sprintf(
        'cd /home/crucifix/Server && node hash_password.mjs %s 2>&1',
        escapeshellarg($lowercasePassword)
    );
    $passwordHash = trim(shell_exec($command));
    
    // Validate hash format
    if (empty($passwordHash) || strpos($passwordHash, '$2b$') !== 0) {
        error_log("Hash generation failed: " . $passwordHash);
        echo json_encode(['success' => false, 'message' => 'Password hashing failed']);
        exit;
    }
    
    // Insert account
    $stmt = $db->prepare("
        INSERT INTO account (username, password, registration_ip, email)
        VALUES (?, ?, ?, ?)
    ");
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $stmt->execute([$username, $passwordHash, $ipAddress, $email]);
    $accountId = $db->lastInsertId();
    
    // Don't create save file - let the game create it on first login
    // Empty save files cause the login server to crash
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'username' => $username,
        'account_id' => $accountId
    ]);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>