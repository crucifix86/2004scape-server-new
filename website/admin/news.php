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
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .news-form {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .news-list {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
        }
        
        .news-item {
            background: #1a1a1a;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        
        .news-content {
            flex: 1;
        }
        
        .news-actions {
            display: flex;
            gap: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-inactive {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php require_once 'includes/nav.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>News Management</h1>
                <p>Create and manage news articles for the homepage</p>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="message success">
                    <?php
                    switch($_GET['success']) {
                        case 'added': echo 'News article added successfully!'; break;
                        case 'updated': echo 'News article updated successfully!'; break;
                        case 'deleted': echo 'News article deleted successfully!'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Add/Edit Form -->
            <div class="news-form">
                <h2><?php echo $editItem ? 'Edit News Article' : 'Add New Article'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editItem ? 'edit' : 'add'; ?>">
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required 
                               value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" class="form-control" rows="5" required><?php echo $editItem ? htmlspecialchars($editItem['content']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Update Article' : 'Add Article'; ?></button>
                    <?php if ($editItem): ?>
                        <a href="news.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- News List -->
            <div class="news-list">
                <h2>All News Articles</h2>
                <?php if (empty($news)): ?>
                    <p class="text-muted">No news articles yet. Create your first one above!</p>
                <?php else: ?>
                    <?php foreach ($news as $article): ?>
                        <div class="news-item">
                            <div class="news-content">
                                <h3>
                                    <?php echo htmlspecialchars($article['title']); ?>
                                    <span class="status-badge <?php echo $article['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $article['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </h3>
                                <p><?php echo nl2br(htmlspecialchars($article['content'])); ?></p>
                                <small class="text-muted">
                                    By <?php echo htmlspecialchars($article['author']); ?> on 
                                    <?php echo date('M j, Y g:i A', strtotime($article['created_at'])); ?>
                                    <?php if ($article['updated_at'] != $article['created_at']): ?>
                                        (Updated: <?php echo date('M j, Y g:i A', strtotime($article['updated_at'])); ?>)
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="news-actions">
                                <a href="?edit=<?php echo $article['id']; ?>" class="btn btn-secondary">Edit</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="btn btn-secondary">
                                        <?php echo $article['is_active'] ? 'Hide' : 'Show'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this article?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>