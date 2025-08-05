<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new SQLite3($dbPath);

// For now, just show top players by total level from accounts
$query = "SELECT username, 
          COALESCE(staffmodlevel, 0) as staff_level,
          COALESCE(banned_until, '') as banned_until
          FROM account 
          WHERE username NOT LIKE '%_OLD%'
          ORDER BY username
          LIMIT 100";

$players = [];
$result = $db->query($query);
if ($result) {
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hiscores - 2004Scape</title>
    <link rel="shortcut icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Hiscores specific styles */
        .info-box {
            text-align: center;
        }
        
        .info-box p {
            color: #888;
            font-size: 18px;
            line-height: 1.8;
        }
        
        .players-table {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            border: 1px solid #333;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .players-table h2 {
            color: #ffd700;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .coming-soon {
            background: rgba(255,215,0,0.1);
            border-left: 4px solid #ffd700;
            padding: 15px 20px;
            margin-top: 20px;
            border-radius: 5px;
        }
        
        .coming-soon strong {
            color: #ffd700;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="img/favicon.ico" alt="Logo">
                <h1>2004Scape</h1>
            </div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="hiscores.php">Hiscores</a>
                <a href="ex/rules.php">Rules</a>
                <a href="client/index.php=option1.php" class="play-button">Play Now</a>
            </nav>
        </div>
    </header>

    <!-- Page Title -->
    <div class="page-title">
        <h1>🏆 Hiscores</h1>
        <p>Track your progress and compete with other players</p>
    </div>
    
    <div class="container narrow">
        <div class="card info-box">
            <h2>📊 Hiscores Coming Soon!</h2>
            <p>
                We're working on implementing a comprehensive hiscores system that will track:<br>
                • Individual skill levels and experience<br>
                • Overall rankings<br>
                • Combat levels<br>
                • Total wealth<br>
                • And much more!
            </p>
            <div class="coming-soon">
                <strong>💡 Note:</strong> The hiscores will automatically update as you play. 
                Train your skills now to secure your spot on the leaderboards when they launch!
            </div>
        </div>
        
        <?php if (!empty($players)): ?>
        <div class="players-table">
            <h2>👥 Registered Players</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $index => $player): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php echo htmlspecialchars($player['username']); ?>
                                <?php if ($player['staff_level'] > 0): ?>
                                    <span class="staff-badge">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($player['banned_until'] && strtotime($player['banned_until']) > time()): ?>
                                    <span style="color: #ff4444;">Banned</span>
                                <?php else: ?>
                                    <span style="color: #4CAF50;">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="ex/rules.php">Rules</a>
            <a href="ex/geting-started.php">Getting Started</a>
            <a href="ex/your-safety.php">Safety</a>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> 2004Scape. All rights reserved.
        </div>
    </footer>
</body>
</html>