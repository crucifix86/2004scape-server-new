<?php
require_once 'check_admin.php';
requireAdmin();

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

// Search filters
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query
$query = "
    SELECT 
        ma.*,
        a1.username as mod_username,
        a2.username as target_username
    FROM mod_action ma
    LEFT JOIN account a1 ON ma.account_id = a1.id
    LEFT JOIN account a2 ON ma.target_id = a2.id
    WHERE 1=1
";
$countQuery = "SELECT COUNT(*) FROM mod_action ma WHERE 1=1";
$params = [];

if ($search) {
    $searchCondition = " AND (a1.username LIKE ? OR a2.username LIKE ? OR ma.data LIKE ?)";
    $query .= $searchCondition;
    $countQuery .= str_replace(['a1.username', 'a2.username'], ['(SELECT username FROM account WHERE id = ma.account_id)', '(SELECT username FROM account WHERE id = ma.target_id)'], $searchCondition);
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
}

if ($filter !== 'all') {
    $actionFilter = " AND ma.action_id = ?";
    $query .= $actionFilter;
    $countQuery .= $actionFilter;
    $params[] = intval($filter);
}

// Get total count
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get results
$query .= " ORDER BY ma.timestamp DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Action type names
function getActionName($actionId) {
    $actions = [
        1 => 'Ban Player',
        2 => 'Unban Player',
        3 => 'Mute Player',
        4 => 'Unmute Player',
        5 => 'Kick Player',
        6 => 'Promote Staff',
        7 => 'Demote Staff',
        8 => 'IP Ban',
        9 => 'IP Unban',
        10 => 'View Logs'
    ];
    return $actions[$actionId] ?? "Action #$actionId";
}

function getActionIcon($actionId) {
    $icons = [
        1 => '🔨',
        2 => '✅',
        3 => '🔇',
        4 => '🔊',
        5 => '👢',
        6 => '⬆️',
        7 => '⬇️',
        8 => '🌐',
        9 => '🌍',
        10 => '👁️'
    ];
    return $icons[$actionId] ?? '📝';
}

function getActionColor($actionId) {
    $colors = [
        1 => '#f44336',
        2 => '#4CAF50',
        3 => '#ff9800',
        4 => '#2196F3',
        5 => '#9C27B0',
        6 => '#4CAF50',
        7 => '#f44336',
        8 => '#f44336',
        9 => '#4CAF50',
        10 => '#607D8B'
    ];
    return $colors[$actionId] ?? '#999';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mod Action Logs - Admin Panel</title>
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
        
        .logs-table {
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
        
        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
        }
        
        .moderator {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .target {
            color: #ff9800;
            font-weight: bold;
        }
        
        .ip-address {
            color: #999;
            font-family: monospace;
            font-size: 12px;
        }
        
        .data-field {
            color: #aaa;
            font-size: 12px;
        }
        
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        
        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            background: #2a2a2a;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
        }
        
        .pagination a:hover {
            background: #3a3a3a;
        }
        
        .pagination .active {
            background: #ffd700;
            color: #333;
        }
        
        .pagination .disabled {
            background: #1a1a1a;
            color: #666;
            cursor: not-allowed;
        }
        
        .logout {
            float: right;
            background: #ff4444;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .stats {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats span {
            color: #ffd700;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="logout.php" class="logout">Logout</a>
        <h1>📋 Moderator Action Logs</h1>
    </div>
    
    <?php require_once 'includes/nav.php'; ?>
    
    <div class="container">
        <div class="stats">
            Total Actions Logged: <span><?php echo number_format($totalCount); ?></span>
        </div>
        
        <form class="search-bar" method="GET">
            <input type="text" name="search" placeholder="Search moderator, target, or details..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="filter">
                <option value="all">All Actions</option>
                <option value="1" <?php echo $filter == '1' ? 'selected' : ''; ?>>Bans</option>
                <option value="2" <?php echo $filter == '2' ? 'selected' : ''; ?>>Unbans</option>
                <option value="3" <?php echo $filter == '3' ? 'selected' : ''; ?>>Mutes</option>
                <option value="4" <?php echo $filter == '4' ? 'selected' : ''; ?>>Unmutes</option>
                <option value="5" <?php echo $filter == '5' ? 'selected' : ''; ?>>Kicks</option>
                <option value="6" <?php echo $filter == '6' ? 'selected' : ''; ?>>Promotions</option>
                <option value="7" <?php echo $filter == '7' ? 'selected' : ''; ?>>Demotions</option>
            </select>
            <button type="submit">Filter</button>
        </form>
        
        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Moderator</th>
                        <th>Target</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['timestamp']; ?></td>
                        <td>
                            <span class="action-badge" style="background: <?php echo getActionColor($log['action_id']); ?>">
                                <?php echo getActionIcon($log['action_id']); ?> <?php echo getActionName($log['action_id']); ?>
                            </span>
                        </td>
                        <td class="moderator">
                            <?php echo htmlspecialchars($log['mod_username'] ?? 'System'); ?>
                        </td>
                        <td class="target">
                            <?php echo htmlspecialchars($log['target_username'] ?? 'N/A'); ?>
                        </td>
                        <td class="data-field">
                            <?php echo htmlspecialchars($log['data'] ?? '-'); ?>
                        </td>
                        <td class="ip-address">
                            <?php echo htmlspecialchars($log['ip'] ?? 'Unknown'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">First</a>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">Previous</a>
            <?php else: ?>
                <span class="disabled">First</span>
                <span class="disabled">Previous</span>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">Next</a>
                <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">Last</a>
            <?php else: ?>
                <span class="disabled">Next</span>
                <span class="disabled">Last</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>