<?php
require_once 'check_admin.php';
requireAdmin();

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

// Search filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? 'all';
$limit = 100;

// Get public chat logs
if ($type === 'all' || $type === 'public') {
    $query = "
        SELECT 
            pc.id,
            pc.account_id,
            pc.profile,
            pc.world,
            pc.timestamp,
            pc.coord,
            pc.message,
            a.username 
        FROM public_chat pc
        LEFT JOIN account a ON pc.account_id = a.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($search) {
        $query .= " AND (a.username LIKE ? OR pc.message LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam];
    }
    
    $query .= " ORDER BY pc.timestamp DESC LIMIT $limit";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $publicChats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $publicChats = [];
}

// Get private chat logs
if ($type === 'all' || $type === 'private') {
    $query = "
        SELECT 
            pc.*,
            a1.username as from_username,
            a2.username as to_username
        FROM private_chat pc
        LEFT JOIN account a1 ON pc.account_id = a1.id
        LEFT JOIN account a2 ON pc.to_account_id = a2.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($search) {
        $query .= " AND (a1.username LIKE ? OR a2.username LIKE ? OR pc.message LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    $query .= " ORDER BY pc.timestamp DESC LIMIT $limit";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $privateChats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $privateChats = [];
}

// Merge and sort all chats by timestamp
$allChats = [];
foreach ($publicChats as $chat) {
    $chat['type'] = 'public';
    $allChats[] = $chat;
}
foreach ($privateChats as $chat) {
    $chat['type'] = 'private';
    $allChats[] = $chat;
}

// Sort by timestamp descending
usort($allChats, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Limit to top N results
$allChats = array_slice($allChats, 0, $limit);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat Logs - Admin Panel</title>
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
        
        .chat-logs {
            background: #2a2a2a;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .chat-entry {
            padding: 15px;
            border-bottom: 1px solid #444;
            transition: background 0.2s;
        }
        
        .chat-entry:hover {
            background: #333;
        }
        
        .chat-entry:last-child {
            border-bottom: none;
        }
        
        .chat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .chat-user {
            color: #ffd700;
            font-weight: bold;
        }
        
        .chat-time {
            color: #999;
            font-size: 12px;
        }
        
        .chat-message {
            color: #fff;
            word-wrap: break-word;
        }
        
        .chat-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .type-public {
            background: #2196F3;
            color: #fff;
        }
        
        .type-private {
            background: #9C27B0;
            color: #fff;
        }
        
        .chat-meta {
            margin-top: 5px;
            font-size: 12px;
            color: #777;
        }
        
        .logout {
            float: right;
            background: #ff4444;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .no-results {
            text-align: center;
            color: #999;
            padding: 40px;
        }
        
        .arrow {
            color: #666;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="logout.php" class="logout">Logout</a>
        <h1>💬 Chat Logs</h1>
    </div>
    
    <?php require_once 'includes/nav.php'; ?>
    
    <div class="container">
        <form class="search-bar" method="GET">
            <input type="text" name="search" placeholder="Search username or message content..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="type">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Chats</option>
                <option value="public" <?php echo $type === 'public' ? 'selected' : ''; ?>>Public Only</option>
                <option value="private" <?php echo $type === 'private' ? 'selected' : ''; ?>>Private Only</option>
            </select>
            <button type="submit">Search</button>
        </form>
        
        <div class="chat-logs">
            <?php if (count($allChats) > 0): ?>
                <?php foreach ($allChats as $chat): ?>
                <div class="chat-entry">
                    <div class="chat-header">
                        <div>
                            <?php if ($chat['type'] === 'public'): ?>
                                <span class="chat-user"><?php echo htmlspecialchars($chat['username'] ?? 'Unknown'); ?></span>
                                <span class="chat-type type-public">PUBLIC</span>
                            <?php else: ?>
                                <span class="chat-user"><?php echo htmlspecialchars($chat['from_username'] ?? 'Unknown'); ?></span>
                                <span class="arrow">→</span>
                                <span class="chat-user"><?php echo htmlspecialchars($chat['to_username'] ?? 'Unknown'); ?></span>
                                <span class="chat-type type-private">PRIVATE</span>
                            <?php endif; ?>
                        </div>
                        <span class="chat-time"><?php echo $chat['timestamp']; ?></span>
                    </div>
                    <div class="chat-message">
                        <?php echo htmlspecialchars($chat['message']); ?>
                    </div>
                    <?php if (isset($chat['coord'])): ?>
                    <div class="chat-meta">
                        Coord: <?php echo $chat['coord']; ?>
                        <?php if (isset($chat['profile'])): ?>
                        | Profile: <?php echo htmlspecialchars($chat['profile']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    No chat messages found
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>