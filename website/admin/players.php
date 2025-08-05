<?php
require_once 'check_admin.php';
requireAdmin();

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_id = $_POST['target_id'] ?? 0;
    
    switch($action) {
        case 'ban':
            $duration = $_POST['duration'] ?? 'permanent';
            $banned_until = $duration === 'permanent' ? '2099-12-31 23:59:59' : date('Y-m-d H:i:s', strtotime($duration));
            $stmt = $db->prepare("UPDATE account SET banned_until = ? WHERE id = ?");
            $stmt->execute([$banned_until, $target_id]);
            logModAction($target_id, MOD_ACTION_BAN, $duration);
            break;
            
        case 'unban':
            $stmt = $db->prepare("UPDATE account SET banned_until = NULL WHERE id = ?");
            $stmt->execute([$target_id]);
            logModAction($target_id, MOD_ACTION_UNBAN);
            break;
            
        case 'mute':
            $duration = $_POST['duration'] ?? '1 hour';
            $muted_until = date('Y-m-d H:i:s', strtotime($duration));
            $stmt = $db->prepare("UPDATE account SET muted_until = ? WHERE id = ?");
            $stmt->execute([$muted_until, $target_id]);
            logModAction($target_id, MOD_ACTION_MUTE, $duration);
            break;
            
        case 'unmute':
            $stmt = $db->prepare("UPDATE account SET muted_until = NULL WHERE id = ?");
            $stmt->execute([$target_id]);
            logModAction($target_id, MOD_ACTION_UNMUTE);
            break;
            
        case 'set_level':
            $level = intval($_POST['level'] ?? 0);
            $stmt = $db->prepare("UPDATE account SET staffmodlevel = ? WHERE id = ?");
            $stmt->execute([$level, $target_id]);
            logModAction($target_id, $level > 0 ? MOD_ACTION_PROMOTE : MOD_ACTION_DEMOTE, "Level: $level");
            break;
    }
    
    header('Location: players.php?success=1');
    exit;
}

// Search/filter
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$query = "SELECT * FROM account WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR registration_ip LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
}

if ($filter === 'banned') {
    $query .= " AND banned_until > datetime('now')";
} elseif ($filter === 'muted') {
    $query .= " AND muted_until > datetime('now')";
} elseif ($filter === 'staff') {
    $query .= " AND staffmodlevel > 0";
} elseif ($filter === 'online') {
    $query .= " AND id IN (SELECT DISTINCT account_id FROM login WHERE timestamp > datetime('now', '-5 minutes'))";
}

$query .= " ORDER BY id DESC LIMIT 100";

$stmt = $db->prepare($query);
$stmt->execute($params);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Player Management - Admin Panel</title>
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
        
        .search-bar {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-bar input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #444;
            background: #1a1a1a;
            color: #fff;
            border-radius: 5px;
        }
        
        .search-bar select,
        .search-bar button {
            padding: 10px 20px;
            border: 1px solid #444;
            background: #1a1a1a;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .search-bar button {
            background: #ffd700;
            color: #333;
            font-weight: bold;
        }
        
        .search-bar button:hover {
            background: #ffed4e;
        }
        
        .player-table {
            background: #2a2a2a;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #1a1a1a;
            padding: 15px;
            text-align: left;
            color: #ffd700;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #444;
        }
        
        tr:hover {
            background: #333;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-staff {
            background: #4CAF50;
            color: #fff;
        }
        
        .badge-banned {
            background: #f44336;
            color: #fff;
        }
        
        .badge-muted {
            background: #ff9800;
            color: #fff;
        }
        
        .badge-online {
            background: #2196F3;
            color: #fff;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            color: #fff;
        }
        
        .btn-ban { background: #f44336; }
        .btn-unban { background: #4CAF50; }
        .btn-mute { background: #ff9800; }
        .btn-unmute { background: #2196F3; }
        .btn-edit { background: #9C27B0; }
        
        .btn:hover { opacity: 0.8; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            background: #2a2a2a;
            margin: 10% auto;
            padding: 20px;
            width: 500px;
            border-radius: 10px;
        }
        
        .modal h2 {
            color: #ffd700;
            margin-bottom: 20px;
        }
        
        .modal .form-group {
            margin-bottom: 15px;
        }
        
        .modal label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .modal input,
        .modal select {
            width: 100%;
            padding: 8px;
            border: 1px solid #444;
            background: #1a1a1a;
            color: #fff;
            border-radius: 5px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .logout {
            float: right;
            background: #ff4444;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="logout.php" class="logout">Logout</a>
        <h1>👥 Player Management</h1>
    </div>
    
    <?php require_once 'includes/nav.php'; ?>
    
    <div class="container">
        <form class="search-bar" method="GET">
            <input type="text" name="search" placeholder="Search username, email, or IP..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="filter">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Players</option>
                <option value="online" <?php echo $filter === 'online' ? 'selected' : ''; ?>>Online Only</option>
                <option value="banned" <?php echo $filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                <option value="muted" <?php echo $filter === 'muted' ? 'selected' : ''; ?>>Muted</option>
                <option value="staff" <?php echo $filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
            </select>
            <button type="submit">Search</button>
        </form>
        
        <div class="player-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Staff Level</th>
                        <th>Registration</th>
                        <th>IP</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $player): ?>
                    <tr>
                        <td><?php echo $player['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($player['username']); ?></strong>
                            <?php 
                            // Check if player is online via login table
                            $onlineCheck = $db->prepare("SELECT COUNT(*) FROM login WHERE account_id = ? AND timestamp > datetime('now', '-5 minutes')");
                            $onlineCheck->execute([$player['id']]);
                            if ($onlineCheck->fetchColumn() > 0): ?>
                                <span class="badge badge-online">ONLINE</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($player['email'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($player['banned_until'] && strtotime($player['banned_until']) > time()): ?>
                                <span class="badge badge-banned">BANNED</span>
                            <?php endif; ?>
                            <?php if ($player['muted_until'] && strtotime($player['muted_until']) > time()): ?>
                                <span class="badge badge-muted">MUTED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($player['staffmodlevel'] > 0): ?>
                                <span class="badge badge-staff"><?php echo getStaffLevelName($player['staffmodlevel']); ?></span>
                            <?php else: ?>
                                Player
                            <?php endif; ?>
                        </td>
                        <td><?php echo $player['registration_date']; ?></td>
                        <td><?php echo htmlspecialchars($player['registration_ip'] ?? 'N/A'); ?></td>
                        <td>
                            <div class="actions">
                                <?php if ($player['banned_until'] && strtotime($player['banned_until']) > time()): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="unban">
                                        <input type="hidden" name="target_id" value="<?php echo $player['id']; ?>">
                                        <button type="submit" class="btn btn-unban">Unban</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-ban" onclick="showBanModal(<?php echo $player['id']; ?>, '<?php echo htmlspecialchars($player['username']); ?>')">Ban</button>
                                <?php endif; ?>
                                
                                <?php if ($player['muted_until'] && strtotime($player['muted_until']) > time()): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="unmute">
                                        <input type="hidden" name="target_id" value="<?php echo $player['id']; ?>">
                                        <button type="submit" class="btn btn-unmute">Unmute</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-mute" onclick="showMuteModal(<?php echo $player['id']; ?>, '<?php echo htmlspecialchars($player['username']); ?>')">Mute</button>
                                <?php endif; ?>
                                
                                <button class="btn btn-edit" onclick="showEditModal(<?php echo $player['id']; ?>, '<?php echo htmlspecialchars($player['username']); ?>', <?php echo $player['staffmodlevel']; ?>)">Edit</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Ban Modal -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <h2>Ban Player: <span id="banUsername"></span></h2>
            <form method="POST">
                <input type="hidden" name="action" value="ban">
                <input type="hidden" name="target_id" id="banTargetId">
                
                <div class="form-group">
                    <label>Duration:</label>
                    <select name="duration">
                        <option value="1 hour">1 Hour</option>
                        <option value="1 day">1 Day</option>
                        <option value="1 week">1 Week</option>
                        <option value="1 month">1 Month</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('banModal')">Cancel</button>
                    <button type="submit" class="btn btn-ban">Ban Player</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Mute Modal -->
    <div id="muteModal" class="modal">
        <div class="modal-content">
            <h2>Mute Player: <span id="muteUsername"></span></h2>
            <form method="POST">
                <input type="hidden" name="action" value="mute">
                <input type="hidden" name="target_id" id="muteTargetId">
                
                <div class="form-group">
                    <label>Duration:</label>
                    <select name="duration">
                        <option value="30 minutes">30 Minutes</option>
                        <option value="1 hour">1 Hour</option>
                        <option value="6 hours">6 Hours</option>
                        <option value="1 day">1 Day</option>
                        <option value="1 week">1 Week</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('muteModal')">Cancel</button>
                    <button type="submit" class="btn btn-mute">Mute Player</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Player: <span id="editUsername"></span></h2>
            <form method="POST">
                <input type="hidden" name="action" value="set_level">
                <input type="hidden" name="target_id" id="editTargetId">
                
                <div class="form-group">
                    <label>Staff Level:</label>
                    <select name="level" id="editLevel">
                        <option value="0">Player</option>
                        <option value="1">Helper</option>
                        <option value="2">Moderator</option>
                        <option value="3">Admin</option>
                        <option value="4">Developer</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-edit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showBanModal(id, username) {
            document.getElementById('banTargetId').value = id;
            document.getElementById('banUsername').textContent = username;
            document.getElementById('banModal').style.display = 'block';
        }
        
        function showMuteModal(id, username) {
            document.getElementById('muteTargetId').value = id;
            document.getElementById('muteUsername').textContent = username;
            document.getElementById('muteModal').style.display = 'block';
        }
        
        function showEditModal(id, username, level) {
            document.getElementById('editTargetId').value = id;
            document.getElementById('editUsername').textContent = username;
            document.getElementById('editLevel').value = level;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>