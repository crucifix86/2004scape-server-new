<?php
// This file should be included after check_admin.php in each admin page
// Usage: require_once 'includes/nav.php';

$pageFile = basename($_SERVER['PHP_SELF']);
$currentPage = str_replace('.php', '', $pageFile);

// For pages with sidebar layout (news.php, content.php)
if (in_array($pageFile, ['news.php', 'content.php'])): ?>
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul class="nav-menu">
        <li><a href="index.php" <?php echo $pageFile == 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
        <li><a href="players.php" <?php echo $pageFile == 'players.php' ? 'class="active"' : ''; ?>>Players</a></li>
        <li><a href="settings.php" <?php echo $pageFile == 'settings.php' ? 'class="active"' : ''; ?>>Settings</a></li>
        <li><a href="news.php" <?php echo $pageFile == 'news.php' ? 'class="active"' : ''; ?>>News</a></li>
        <li><a href="content.php" <?php echo $pageFile == 'content.php' ? 'class="active"' : ''; ?>>Content</a></li>
        <li><a href="bans.php" <?php echo $pageFile == 'bans.php' ? 'class="active"' : ''; ?>>Bans & Mutes</a></li>
        <li><a href="reports.php" <?php echo $pageFile == 'reports.php' ? 'class="active"' : ''; ?>>Reports</a></li>
        <li><a href="chat.php" <?php echo $pageFile == 'chat.php' ? 'class="active"' : ''; ?>>Chat Logs</a></li>
        <li><a href="mod_logs.php" <?php echo $pageFile == 'mod_logs.php' ? 'class="active"' : ''; ?>>Mod Actions</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<?php elseif ($pageFile == 'index.php'): ?>
<!-- Dashboard uses div.nav without ul -->
<div class="nav">
    <a href="index.php">Dashboard</a>
    <a href="players.php">Players</a>
    <a href="bans.php">Bans & Mutes</a>
    <a href="reports.php">Reports</a>
    <a href="chat.php">Chat Logs</a>
    <a href="mod_logs.php">Mod Actions</a>
    <a href="settings.php">Settings</a>
    <a href="news.php">News</a>
    <a href="content.php">Content</a>
    <a href="../index.php">Back to Site</a>
</div>

<?php elseif ($pageFile == 'settings.php'): ?>
<!-- Settings uses div.nav without ul like dashboard -->
<div class="nav">
    <a href="index.php">Dashboard</a>
    <a href="players.php">Players</a>
    <a href="bans.php">Bans & Mutes</a>
    <a href="chat.php">Chat Logs</a>
    <a href="reports.php">Reports</a>
    <a href="mod_logs.php">Mod Actions</a>
    <a href="settings.php">Settings</a>
    <a href="news.php">News</a>
    <a href="content.php">Content</a>
    <a href="../index.php">Back to Site</a>
</div>

<?php else: ?>
<!-- Other pages use nav>ul structure -->
<nav class="nav">
    <ul>
        <li><a href="index.php" <?php echo $pageFile == 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
        <li><a href="players.php" <?php echo $pageFile == 'players.php' ? 'class="active"' : ''; ?>>Players</a></li>
        <li><a href="bans.php" <?php echo $pageFile == 'bans.php' ? 'class="active"' : ''; ?>>Bans & Mutes</a></li>
        <li><a href="reports.php" <?php echo $pageFile == 'reports.php' ? 'class="active"' : ''; ?>>Reports</a></li>
        <li><a href="chat.php" <?php echo $pageFile == 'chat.php' ? 'class="active"' : ''; ?>>Chat Logs</a></li>
        <li><a href="mod_logs.php" <?php echo $pageFile == 'mod_logs.php' ? 'class="active"' : ''; ?>>Mod Actions</a></li>
        <li><a href="settings.php" <?php echo $pageFile == 'settings.php' ? 'class="active"' : ''; ?>>Settings</a></li>
        <li><a href="news.php" <?php echo $pageFile == 'news.php' ? 'class="active"' : ''; ?>>News</a></li>
        <li><a href="content.php" <?php echo $pageFile == 'content.php' ? 'class="active"' : ''; ?>>Content</a></li>
    </ul>
</nav>
<?php endif; ?>