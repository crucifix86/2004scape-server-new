<?php
session_start();
require_once 'check_admin.php';

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("INSERT INTO news (title, content, author) VALUES (?, ?, ?)");
                $stmt->bindValue(1, $_POST['title']);
                $stmt->bindValue(2, $_POST['content']);
                $stmt->bindValue(3, $_SESSION['admin_username']);
                $stmt->execute();
                header('Location: news.php?success=added');
                exit;
                
            case 'edit':
                $stmt = $db->prepare("UPDATE news SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bindValue(1, $_POST['title']);
                $stmt->bindValue(2, $_POST['content']);
                $stmt->bindValue(3, $_POST['id']);
                $stmt->execute();
                header('Location: news.php?success=updated');
                exit;
                
            case 'toggle':
                $stmt = $db->prepare("UPDATE news SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
                $stmt->bindValue(1, $_POST['id']);
                $stmt->execute();
                header('Location: news.php');
                exit;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
                $stmt->bindValue(1, $_POST['id']);
                $stmt->execute();
                header('Location: news.php?success=deleted');
                exit;
        }
    }
}

// Get all news
$news = [];
$result = $db->query("SELECT * FROM news ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $news[] = $row;
    }
}

// Get single news item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->bindValue(1, $_GET['edit']);
    $result = $stmt->execute();
    $editItem = $result->fetchArray(SQLITE3_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>News Management - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #ffcc00;
            border-bottom: 2px solid #ffcc00;
            padding-bottom: 10px;
        }
        .nav {
            background: #2a2a2a;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .nav a {
            color: #ffcc00;
            text-decoration: none;
            margin-right: 20px;
            padding: 5px 10px;
        }
        .nav a:hover {
            background: #3a3a3a;
            border-radius: 3px;
        }
        .success {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .news-form {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .news-form h2 {
            color: #ffcc00;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ffcc00;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 3px;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .btn {
            background: #ffcc00;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn:hover {
            background: #ffd633;
        }
        .btn-danger {
            background: #f44336;
            color: #fff;
        }
        .btn-danger:hover {
            background: #ff5252;
        }
        .btn-secondary {
            background: #666;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #777;
        }
        .news-list {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 5px;
        }
        .news-list h2 {
            color: #ffcc00;
            margin-top: 0;
        }
        .news-item {
            background: #1a1a1a;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 3px;
            border: 1px solid #444;
        }
        .news-item h3 {
            color: #ffcc00;
            margin: 0 0 10px 0;
        }
        .news-meta {
            color: #888;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .news-content {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .news-actions {
            display: flex;
            gap: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background: #4CAF50;
            color: white;
        }
        .status-inactive {
            background: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>News Management</h1>
        
        <div class="nav">
            <a href="index.php">Dashboard</a>
            <a href="players.php">Players</a>
            <a href="bans.php">Bans</a>
            <a href="chat.php">Chat Logs</a>
            <a href="reports.php">Reports</a>
            <a href="mod_logs.php">Mod Actions</a>
            <a href="settings.php">Settings</a>
            <a href="news.php">News</a>
            <a href="../index.php">Back to Site</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                <?php
                switch ($_GET['success']) {
                    case 'added': echo 'News article added successfully!'; break;
                    case 'updated': echo 'News article updated successfully!'; break;
                    case 'deleted': echo 'News article deleted successfully!'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="news-form">
            <h2><?php echo $editItem ? 'Edit News Article' : 'Add New Article'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editItem ? 'edit' : 'add'; ?>">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required><?php echo htmlspecialchars($editItem['content'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn">
                    <?php echo $editItem ? 'Update Article' : 'Add Article'; ?>
                </button>
                <?php if ($editItem): ?>
                    <a href="news.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="news-list">
            <h2>All News Articles</h2>
            <?php foreach ($news as $item): ?>
                <div class="news-item">
                    <h3>
                        <?php echo htmlspecialchars($item['title']); ?>
                        <span class="status-badge <?php echo $item['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </h3>
                    <div class="news-meta">
                        By <?php echo htmlspecialchars($item['author']); ?> | 
                        Created: <?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?>
                        <?php if ($item['created_at'] !== $item['updated_at']): ?>
                            | Updated: <?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?>
                        <?php endif; ?>
                    </div>
                    <div class="news-content">
                        <?php echo nl2br(htmlspecialchars($item['content'])); ?>
                    </div>
                    <div class="news-actions">
                        <a href="?edit=<?php echo $item['id']; ?>" class="btn">Edit</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-secondary">
                                <?php echo $item['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this article?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>