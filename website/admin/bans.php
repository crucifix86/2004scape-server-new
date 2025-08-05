<?php
require_once 'check_admin.php';
requireAdmin();

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_id = $_POST['target_id'] ?? 0;
    
    if ($action === 'unban') {
        $stmt = $db->prepare("UPDATE account SET banned_until = NULL WHERE id = ?");
        $stmt->execute([$target_id]);
        logModAction($target_id, MOD_ACTION_UNBAN);
    } elseif ($action === 'unmute') {
        $stmt = $db->prepare("UPDATE account SET muted_until = NULL WHERE id = ?");
        $stmt->execute([$target_id]);
        logModAction($target_id, MOD_ACTION_UNMUTE);
    }
    
    header('Location: bans.php?success=1');
    exit;
}

// Get all banned accounts
$stmt = $db->query("
    SELECT id, username, banned_until, registration_ip, email
    FROM account 
    WHERE banned_until IS NOT NULL AND banned_until > datetime('now')
    ORDER BY banned_until DESC
");
$banned = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all muted accounts  
$stmt = $db->query("
    SELECT id, username, muted_until, registration_ip, email
    FROM account 
    WHERE muted_until IS NOT NULL AND muted_until > datetime('now')
    ORDER BY muted_until DESC
");
$muted = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get IP bans (check if table exists first)
$ipbans = [];
try {
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ipban'");
    if ($tableCheck->fetch()) {
        // Table exists, get IP bans (ipban table might not have id column)
        $stmt = $db->query("SELECT * FROM ipban");
        $ipbans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Table doesn't exist or has different structure
    $ipbans = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bans & Mutes - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .header h1 {
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .nav {
            background: #2a2a2a;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .nav ul {
            list-style: none;
            display: flex;
        }
        
        .nav li {
            flex: 1;
        }
        
        .nav a {
            display: block;
            padding: 15px;
            color: #fff;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: #3a3a3a;
        }
        
        .nav a.active {
            background: #444;
            border-bottom: 3px solid #ffd700;
        }
        
        .container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .panel {
            background: #2a2a2a;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .panel h2 {
            color: #ffd700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #444;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #1a1a1a;
            padding: 10px;
            text-align: left;
            color: #ffd700;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #444;
        }
        
        tr:hover {
            background: #333;
        }
        
        .btn {
            padding: 5px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            color: #fff;
            text-decoration: none;
        }
        
        .btn-unban { background: #4CAF50; }
        .btn-unmute { background: #2196F3; }
        .btn-danger { background: #f44336; }
        
        .btn:hover { opacity: 0.8; }
        
        .time-remaining {
            color: #ff9800;
            font-size: 12px;
        }
        
        .permanent {
            color: #f44336;
            font-weight: bold;
        }
        
        .logout {
            float: right;
            background: #ff4444;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .success {
            background: #4CAF50;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .add-ban {
            background: #f44336;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .add-ban:hover {
            background: #ff6666;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="logout.php" class="logout">Logout</a>
        <h1>🚫 Bans & Mutes Management</h1>
    </div>
    
    <?php require_once 'includes/nav.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success">✓ Action completed successfully</div>
        <?php endif; ?>
        
        <div class="panel">
            <h2>🔨 Banned Players (<?php echo count($banned); ?>)</h2>
            <?php if (count($banned) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>IP Address</th>
                        <th>Banned Until</th>
                        <th>Time Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($banned as $player): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($player['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($player['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($player['registration_ip'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            if (strtotime($player['banned_until']) > strtotime('2090-01-01')) {
                                echo '<span class="permanent">PERMANENT</span>';
                            } else {
                                echo $player['banned_until'];
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $remaining = strtotime($player['banned_until']) - time();
                            if ($remaining > 31536000) {
                                echo '<span class="permanent">Permanent</span>';
                            } else {
                                $days = floor($remaining / 86400);
                                $hours = floor(($remaining % 86400) / 3600);
                                echo "<span class='time-remaining'>$days days, $hours hours</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unban">
                                <input type="hidden" name="target_id" value="<?php echo $player['id']; ?>">
                                <button type="submit" class="btn btn-unban">Unban</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color: #999; padding: 20px;">No banned players</p>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <h2>🔇 Muted Players (<?php echo count($muted); ?>)</h2>
            <?php if (count($muted) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>IP Address</th>
                        <th>Muted Until</th>
                        <th>Time Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($muted as $player): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($player['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($player['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($player['registration_ip'] ?? 'N/A'); ?></td>
                        <td><?php echo $player['muted_until']; ?></td>
                        <td>
                            <?php
                            $remaining = strtotime($player['muted_until']) - time();
                            if ($remaining > 0) {
                                $hours = floor($remaining / 3600);
                                $minutes = floor(($remaining % 3600) / 60);
                                echo "<span class='time-remaining'>$hours hours, $minutes minutes</span>";
                            } else {
                                echo '<span style="color: #4CAF50;">Expired</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unmute">
                                <input type="hidden" name="target_id" value="<?php echo $player['id']; ?>">
                                <button type="submit" class="btn btn-unmute">Unmute</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color: #999; padding: 20px;">No muted players</p>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <h2>🌐 IP Bans (<?php echo count($ipbans); ?>)</h2>
            <?php if (count($ipbans) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>IP Address</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ipbans as $idx => $ipban): ?>
                    <tr>
                        <td><?php echo isset($ipban['id']) ? $ipban['id'] : ($idx + 1); ?></td>
                        <td><strong><?php echo htmlspecialchars($ipban['ip'] ?? $ipban['address'] ?? 'Unknown'); ?></strong></td>
                        <td><?php echo $ipban['timestamp'] ?? $ipban['date'] ?? 'Unknown'; ?></td>
                        <td>
                            <button class="btn btn-danger" disabled>Remove (TODO)</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color: #999; padding: 20px;">No IP bans</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>