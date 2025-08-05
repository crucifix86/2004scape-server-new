<?php
session_start();

$error = '';
$dbPath = '/home/crucifix/Server/db.sqlite';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = new PDO("sqlite:$dbPath");
        $stmt = $db->prepare("SELECT id, username, password, staffmodlevel FROM account WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account && $account['staffmodlevel'] >= 2) {
            // Verify password using Node.js bcrypt
            $command = sprintf(
                'cd /home/crucifix/Server && node verify_password.mjs %s %s 2>&1',
                escapeshellarg(strtolower($password)),
                escapeshellarg($account['password'])
            );
            $result = trim(shell_exec($command));
            
            if ($result === '1') {
                $_SESSION['admin_id'] = $account['id'];
                $_SESSION['admin_username'] = $account['username'];
                $_SESSION['admin_level'] = $account['staffmodlevel'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Access denied - insufficient privileges';
        }
    } catch (Exception $e) {
        $error = 'Database error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - 2004Scape</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            background: rgba(0, 0, 0, 0.8);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            width: 400px;
        }
        
        h1 {
            color: #ffd700;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #ffd700;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #333;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            font-size: 16px;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #ffd700;
            background: #fff;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            background: linear-gradient(135deg, #ffed4e, #ffd700);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
            color: #ff9999;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info {
            color: #999;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🛡️ Admin Panel Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="info">
            Staff level 2+ required for access
        </div>
    </div>
</body>
</html>