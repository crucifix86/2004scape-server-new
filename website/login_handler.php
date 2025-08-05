<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $dbPath = '/home/crucifix/Server/db.sqlite';
    
    try {
        $db = new PDO("sqlite:$dbPath");
        $stmt = $db->prepare("SELECT id, username, password, staffmodlevel, email FROM account WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account) {
            // Verify password using Node.js bcrypt
            $command = sprintf(
                'cd /home/crucifix/Server && node verify_password.mjs %s %s 2>&1',
                escapeshellarg(strtolower($password)),
                escapeshellarg($account['password'])
            );
            $result = trim(shell_exec($command));
            
            if ($result === '1') {
                // Auto-promote developers from config
                $configPath = '/home/crucifix/Server/admin_config.json';
                if (file_exists($configPath)) {
                    $config = json_decode(file_get_contents($configPath), true);
                    if (isset($config['developers']) && in_array(strtolower($account['username']), array_map('strtolower', $config['developers']))) {
                        if ($account['staffmodlevel'] < 4) {
                            $stmt = $db->prepare("UPDATE account SET staffmodlevel = 4 WHERE id = ?");
                            $stmt->execute([$account['id']]);
                            $account['staffmodlevel'] = 4;
                        }
                    }
                }
                
                // Set session variables
                $_SESSION['user_id'] = $account['id'];
                $_SESSION['username'] = $account['username'];
                $_SESSION['staff_level'] = $account['staffmodlevel'];
                $_SESSION['logged_in'] = true;
                
                // Return success
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'staff_level' => $account['staffmodlevel']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid password'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Username not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>