<?php
session_start();
require_once '../check_admin.php';

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['staff_level'] < 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_all':
        // Get all settings grouped by category
        $result = $db->query("SELECT * FROM game_settings ORDER BY category, key");
        $settings = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['category']][] = $row;
        }
        echo json_encode($settings);
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $key = $data['key'] ?? '';
        $value = $data['value'] ?? '';
        
        if (!$key) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing key']);
            exit;
        }
        
        // Update setting
        $stmt = $db->prepare("UPDATE game_settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE key = ?");
        $stmt->bindValue(1, $value);
        $stmt->bindValue(2, $key);
        $result = $stmt->execute();
        
        // Check if restart required
        $stmt = $db->prepare("SELECT requires_restart FROM game_settings WHERE key = ?");
        $stmt->bindValue(1, $key);
        $row = $stmt->execute()->fetchArray();
        
        // Update .env file
        updateEnvFile($db);
        
        // Log the action
        $stmt = $db->prepare("INSERT INTO mod_action (account_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt->bindValue(1, $_SESSION['user_id']);
        $stmt->bindValue(2, 'setting_update');
        $stmt->bindValue(3, "Updated $key to $value");
        $stmt->bindValue(4, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'requires_restart' => $row['requires_restart'] ?? false
        ]);
        break;
        
    case 'add_custom':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['key']) || !isset($data['value']) || !isset($data['category'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        // Insert new setting
        $stmt = $db->prepare("INSERT INTO game_settings (key, value, category, type, description, requires_restart) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $data['key']);
        $stmt->bindValue(2, $data['value']);
        $stmt->bindValue(3, $data['category']);
        $stmt->bindValue(4, $data['type'] ?? 'text');
        $stmt->bindValue(5, $data['description'] ?? '');
        $stmt->bindValue(6, $data['requires_restart'] ?? 0);
        
        if ($stmt->execute()) {
            updateEnvFile($db);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add setting']);
        }
        break;
        
    case 'server_status':
        // Check if server is running
        $output = shell_exec('ps aux | grep "tsx src/app.ts" | grep -v grep');
        $running = !empty($output);
        
        // Get server uptime if running
        $uptime = null;
        if ($running) {
            $pidOutput = shell_exec("ps aux | grep 'tsx src/app.ts' | grep -v grep | awk '{print $2}'");
            if ($pidOutput) {
                $pid = trim($pidOutput);
                $uptimeOutput = shell_exec("ps -o etime= -p $pid 2>/dev/null");
                $uptime = trim($uptimeOutput);
            }
        }
        
        echo json_encode([
            'running' => $running,
            'uptime' => $uptime
        ]);
        break;
        
    case 'restart_server':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        // Stop existing server
        shell_exec('cd /home/crucifix/Server && npm run stop 2>&1');
        sleep(2);
        
        // Start server
        shell_exec('cd /home/crucifix/Server && npm run start > server.log 2>&1 &');
        
        // Log the action
        $stmt = $db->prepare("INSERT INTO mod_action (account_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt->bindValue(1, $_SESSION['user_id']);
        $stmt->bindValue(2, 'server_restart');
        $stmt->bindValue(3, "Restarted game server");
        $stmt->bindValue(4, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function updateEnvFile($db) {
    $envPath = '/home/crucifix/Server/.env';
    $envContent = file_get_contents($envPath);
    
    // Get all settings
    $result = $db->query("SELECT key, value FROM game_settings");
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $key = $row['key'];
        $value = $row['value'];
        
        // Update or add the setting in .env
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }
    }
    
    file_put_contents($envPath, $envContent);
}
?>