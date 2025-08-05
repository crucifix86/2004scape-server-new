<?php
// Get server name from database if available
$serverName = '2004Scape';
if (isset($db)) {
    $tableExists = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
    if ($tableExists) {
        $name = $db->querySingle("SELECT value FROM settings WHERE key = 'server_name'");
        if ($name) {
            $serverName = $name;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo htmlspecialchars($serverName); ?></title>
    <link rel="shortcut icon" href="<?php echo isset($basePath) ? $basePath : ''; ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo isset($basePath) ? $basePath : ''; ?>css/style.css">
    <?php if (isset($additionalStyles)): ?>
    <style>
        <?php echo $additionalStyles; ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="<?php echo isset($basePath) ? $basePath : ''; ?>img/favicon.ico" alt="Logo">
                <h1><?php echo htmlspecialchars($serverName); ?></h1>
            </div>
            <nav class="nav">
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">Home</a>
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>hiscores.php">Hiscores</a>
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>ex/rules.php">Rules</a>
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>client/index.php=option1.php" class="play-button">Play Now</a>
            </nav>
        </div>
    </header>

    <?php if (isset($pageTitle) && isset($pageSubtitle)): ?>
    <!-- Page Title -->
    <div class="page-title">
        <h1><?php echo $pageTitle; ?></h1>
        <p><?php echo $pageSubtitle; ?></p>
    </div>
    <?php endif; ?>