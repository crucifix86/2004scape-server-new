<?php
require_once 'check_admin.php';
requireAdmin();

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

// Get statistics
$stats = [];

// Total accounts
$stmt = $db->query("SELECT COUNT(*) as count FROM account");
$stats['total_accounts'] = $stmt->fetch()['count'];

// Online players (active in login table within last 5 minutes)
$stmt = $db->query("SELECT COUNT(DISTINCT account_id) as count FROM login WHERE timestamp > datetime('now', '-5 minutes')");
$stats['online_players'] = $stmt->fetch()['count'];

// Banned accounts
$stmt = $db->query("SELECT COUNT(*) as count FROM account WHERE banned_until > datetime('now')");
$stats['banned_accounts'] = $stmt->fetch()['count'];

// Muted accounts
$stmt = $db->query("SELECT COUNT(*) as count FROM account WHERE muted_until > datetime('now')");
$stats['muted_accounts'] = $stmt->fetch()['count'];

// Recent registrations (last 24h)
$stmt = $db->query("SELECT COUNT(*) as count FROM account WHERE registration_date > datetime('now', '-1 day')");
$stats['recent_registrations'] = $stmt->fetch()['count'];

// Recent logins
$stmt = $db->query("SELECT username, login_time FROM account WHERE login_time IS NOT NULL ORDER BY login_time DESC LIMIT 10");
$recent_logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent mod actions
$stmt = $db->query("
    SELECT ma.*, a.username as mod_username, t.username as target_username
    FROM mod_action ma
    LEFT JOIN account a ON ma.account_id = a.id
    LEFT JOIN account t ON ma.target_id = t.id
    ORDER BY ma.timestamp DESC
    LIMIT 10
");
$recent_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - 2004Scape</title>
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
        
        .header .user-info {
            color: #fff;
            margin-top: 10px;
        }
        
        .nav {
            background: #2a2a2a;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .nav a {
            display: inline-block;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card h3 {
            color: #999;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            color: #ffd700;
            font-size: 32px;
            font-weight: bold;
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
        
        .logout {
            float: right;
            background: #ff4444;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .logout:hover {
            background: #ff6666;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="logout.php" class="logout">Logout</a>
        <h1>🛡️ Admin Dashboard</h1>
        <div class="user-info">
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong> 
            (<?php echo getStaffLevelName($_SESSION['admin_level']); ?>)
        </div>
    </div>
    
    <?php require_once 'includes/nav.php'; ?>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Accounts</h3>
                <div class="value"><?php echo number_format($stats['total_accounts']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Online Players</h3>
                <div class="value"><?php echo number_format($stats['online_players']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Banned Accounts</h3>
                <div class="value"><?php echo number_format($stats['banned_accounts']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Muted Accounts</h3>
                <div class="value"><?php echo number_format($stats['muted_accounts']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>New Today</h3>
                <div class="value"><?php echo number_format($stats['recent_registrations']); ?></div>
            </div>
        </div>
        
        <div class="panel">
            <h2>Recent Logins</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Login Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logins as $login): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($login['username']); ?></td>
                        <td><?php echo $login['login_time']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="panel">
            <h2>Recent Mod Actions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Moderator</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_actions as $action): ?>
                    <tr>
                        <td><?php echo $action['timestamp']; ?></td>
                        <td><?php echo htmlspecialchars($action['mod_username'] ?? 'System'); ?></td>
                        <td>Action #<?php echo $action['action_id']; ?></td>
                        <td><?php echo htmlspecialchars($action['target_username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($action['data'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>