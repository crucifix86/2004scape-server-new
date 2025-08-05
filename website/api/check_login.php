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

// Path to game's SQLite database
$dbPath = '/home/crucifix/Server/db.sqlite';

if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'message' => 'Database not found']);
    exit;
}

try {
    // Connect to SQLite
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get account
    $stmt = $db->prepare("SELECT id, username, password FROM account WHERE LOWER(username) = LOWER(?)");
    $stmt->execute([$username]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }
    
    // Verify password (game uses lowercase passwords)
    if (!password_verify(strtolower($password), $account['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'username' => $account['username']
    ]);
    
} catch (Exception $e) {
    error_log("Login check error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Login failed']);
}
?>