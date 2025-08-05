<?php
session_start();
require_once '../check_admin.php';
require_once '../lib/PlayerSaveParser.php';

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['staff_level'] < 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$username = $_GET['username'] ?? '';

if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Username required']);
    exit;
}

// Sanitize username
$username = strtolower(preg_replace('/[^a-zA-Z0-9_\- ]/', '', $username));

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

switch ($action) {
    case 'summary':
        // Get basic player info without parsing save file
        $stmt = $db->prepare("SELECT id, username, members, staffmodlevel, banned_until, muted_until, login_time FROM account WHERE LOWER(username) = LOWER(?)");
        $stmt->bindValue(1, $username);
        $account = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$account) {
            http_response_code(404);
            echo json_encode(['error' => 'Player not found']);
            exit;
        }
        
        // Check if online
        $stmt = $db->prepare("SELECT COUNT(*) as online FROM login WHERE account_id = ? AND timestamp > datetime('now', '-5 minutes')");
        $stmt->bindValue(1, $account['id']);
        $result = $stmt->execute()->fetchArray();
        $account['online'] = $result['online'] > 0;
        
        // Get hiscores data if available
        $stmt = $db->prepare("SELECT * FROM hiscore WHERE account_id = ?");
        $stmt->bindValue(1, $account['id']);
        $hiscores = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        echo json_encode([
            'account' => $account,
            'hiscores' => $hiscores
        ]);
        break;
        
    case 'full':
        // Get full player data including save file
        $stmt = $db->prepare("SELECT * FROM account WHERE LOWER(username) = LOWER(?)");
        $stmt->bindValue(1, $username);
        $account = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$account) {
            http_response_code(404);
            echo json_encode(['error' => 'Player not found']);
            exit;
        }
        
        $saveFile = "/home/crucifix/Server/data/players/main/{$username}.sav";
        
        if (!file_exists($saveFile)) {
            echo json_encode([
                'account' => $account,
                'save_data' => null,
                'error' => 'Save file not found'
            ]);
            exit;
        }
        
        try {
            $parser = new PlayerSaveParser($saveFile);
            $playerData = $parser->parse();
            
            echo json_encode([
                'account' => $account,
                'save_data' => $playerData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'account' => $account,
                'save_data' => null,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'inventory':
        // Get just inventory data
        $saveFile = "/home/crucifix/Server/data/players/main/{$username}.sav";
        
        if (!file_exists($saveFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Save file not found']);
            exit;
        }
        
        try {
            $parser = new PlayerSaveParser($saveFile);
            $playerData = $parser->parse();
            
            echo json_encode([
                'inventories' => $playerData['inventories']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'skills':
        // Get just skills data
        $saveFile = "/home/crucifix/Server/data/players/main/{$username}.sav";
        
        if (!file_exists($saveFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Save file not found']);
            exit;
        }
        
        try {
            $parser = new PlayerSaveParser($saveFile);
            $playerData = $parser->parse();
            
            $totalLevel = 0;
            $totalXP = 0;
            foreach ($playerData['skills'] as $skill) {
                $totalLevel += $skill['level'];
                $totalXP += $skill['experience'];
            }
            
            echo json_encode([
                'skills' => $playerData['skills'],
                'combat_level' => $playerData['combat_level'],
                'total_level' => $totalLevel,
                'total_xp' => $totalXP
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>