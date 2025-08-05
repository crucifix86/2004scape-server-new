<?php
session_start();

// Get database connection - use the game server's SQLite DB
$dbPath = '/home/crucifix/2004scape-server/db.sqlite';
$db = null;
if (file_exists($dbPath)) {
    $db = new SQLite3($dbPath);
}

// Get server stats
$totalPlayers = 0;
$onlinePlayers = 0;
$news = [];
$settings = [];
$xpRate = '1';
$dropRate = '1';
$serverName = '2004Scape';

if ($db) {
    try {
        $totalPlayers = $db->querySingle("SELECT COUNT(*) FROM account");
        $onlinePlayers = $db->querySingle("SELECT COUNT(DISTINCT account_id) FROM login WHERE timestamp > datetime('now', '-5 minutes')");
        
        // Get active news
        $newsResult = $db->query("SELECT * FROM news WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
        if ($newsResult) {
            while ($row = $newsResult->fetchArray(SQLITE3_ASSOC)) {
                $news[] = $row;
            }
        }
        
        // Check if settings table exists
        $tableExists = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
        if ($tableExists) {
            $result = $db->query("SELECT key, value FROM settings WHERE key IN ('xp_rate', 'drop_rate', 'server_name')");
            if ($result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $settings[$row['key']] = $row['value'];
                }
                $xpRate = $settings['xp_rate'] ?? '1';
                $dropRate = $settings['drop_rate'] ?? '1';
                $serverName = $settings['server_name'] ?? '2004Scape';
            }
        }
    } catch (Exception $e) {
        // Database operations failed, continue with defaults
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($serverName); ?> - Relive RuneScape 2004</title>
    <link rel="shortcut icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* --- FIXED HEADER STYLES --- */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        body {
            /* Adjust this value to match your header's height */
            padding-top: 80px; 
        }
        /* --- END FIXED HEADER STYLES --- */

        /* Homepage specific styles */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('img/hero-bg.jpg') center/cover;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h2 {
            font-size: 48px;
            color: #ffd700;
            margin-bottom: 20px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.8);
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            color: #e0e0e0;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #2e7d32, #43a047);
            color: white;
        }

        /* Grid Layout */
        .content-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Server Info */
        .server-info {
            text-align: center;
            padding: 15px !important;
        }
        
        .server-info h3 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        .server-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stat-item {
            background: rgba(0,0,0,0.3);
            padding: 8px;
            border-radius: 5px;
        }
        
        .server-info .stat-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            margin: 0;
        }
        
        .server-info .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #ffd700;
            margin: 0;
        }

        .server-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            background: rgba(0,0,0,0.3);
            border-radius: 5px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4CAF50;
            box-shadow: 0 0 10px #4CAF50;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 10px #4CAF50; }
            50% { box-shadow: 0 0 20px #4CAF50; }
            100% { box-shadow: 0 0 10px #4CAF50; }
        }

        /* Login Form */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .login-form input {
            padding: 12px;
            background: rgba(0,0,0,0.3);
            border: 1px solid #444;
            color: white;
            border-radius: 5px;
            font-size: 14px;
        }

        .login-form input:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(0,0,0,0.5);
        }

        .login-form button {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255,215,0,0.4);
        }

        /* User Info */
        .user-info {
            text-align: center;
        }

        .username {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .user-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .user-actions a {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            text-align: center;
        }

        .user-actions a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }

        /* News Section */
        .news-section {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            border: 1px solid #333;
        }

        .news-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .news-header h2 {
            color: #ffd700;
            font-size: 28px;
        }

        .news-item {
            background: rgba(0,0,0,0.3);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .news-item:hover {
            background: rgba(0,0,0,0.5);
            transform: translateX(5px);
        }

        /* Features Grid */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .feature-card {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #333;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            color: #ffd700;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .hero h2 {
                font-size: 36px;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="img/favicon.ico" alt="Logo">
                <h1><?php echo htmlspecialchars($serverName); ?></h1>
            </div>
            <nav class="nav">
                <a href="#news">News</a>
                <a href="hiscores.php">Hiscores</a>
                <a href="ex/rules.php">Rules</a>
                <a href="client/index.php=option1.php" class="play-button">Play Now</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h2>Experience RuneScape as it was in 2004</h2>
            <p>Join thousands of players in the most authentic Old School RuneScape experience</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-secondary">Create Account</a>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="content-grid">
            <aside class="sidebar">
                <div class="card server-info">
                    <h3>⚔️ Server Information</h3>
                    <div class="server-stats">
                        <div class="stat-item">
                            <div class="stat-label">XP Rate</div>
                            <div class="stat-value"><?php echo $xpRate; ?>x</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Drop Rate</div>
                            <div class="stat-value"><?php echo $dropRate; ?>x</div>
                        </div>
                    </div>
                    <div class="server-status">
                        <span class="status-indicator"></span>
                        <span>Server Online</span>
                    </div>
                    <div style="margin-top: 10px; color: #888; font-size: 13px;">
                        <div><?php echo number_format($totalPlayers); ?> Total Players</div>
                        <div><?php echo number_format($onlinePlayers); ?> Players Online</div>
                    </div>
                </div>

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <div class="card user-info">
                        <h3>👤 Account</h3>
                        <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="user-actions">
                            <a href="client/index.php=option1.php">🎮 Play Game</a>
                            <?php if ($_SESSION['staff_level'] >= 2): ?>
                                <a href="admin/">🛡️ Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout_handler.php">🚪 Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <h3>🔑 Account Login</h3>
                        <form class="login-form" id="login-form">
                            <input type="text" id="username" placeholder="Username" required>
                            <input type="password" id="password" placeholder="Password" required>
                            <button type="submit">Login</button>
                        </form>
                        <div style="text-align: center; margin-top: 10px;">
                            <a href="register.php" style="color: #ffd700; text-decoration: none;">Create New Account</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h3>🔗 Quick Links</h3>
                    <div class="user-actions">
                        <a href="hiscores.php">📊 Hiscores</a>
                        <a href="ex/rules.php">📜 Game Rules</a>
                        <a href="ex/geting-started.php">🎯 Getting Started</a>
                    </div>
                </div>
            </aside>

            <main>
                <section class="news-section" id="news">
                    <div class="news-header">
                        <h2>📰 Latest News & Updates</h2>
                    </div>
                    <?php if (empty($news)): ?>
                        <p style="color: #888;">No news articles available.</p>
                    <?php else: ?>
                        <?php foreach ($news as $article): ?>
                            <article class="news-item">
                                <h3 class="news-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                                <div class="news-meta">
                                    Posted by <?php echo htmlspecialchars($article['author']); ?> on 
                                    <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                                </div>
                                <div class="news-content">
                                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </main>
        </div>

        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">🎮</div>
                <h3>Authentic Gameplay</h3>
                <p>Experience RuneScape exactly as it was in 2004, with all the nostalgia and none of the modern changes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Active Community</h3>
                <p>Join a thriving community of players who share your passion for classic RuneScape.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚖️</div>
                <h3>Fair Play</h3>
                <p>No pay-to-win mechanics. Everyone starts equal and progresses through their own efforts.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure & Stable</h3>
                <p>Regular backups, active moderation, and dedicated hosting ensure your progress is always safe.</p>
            </div>
        </section>
    </div>

    <footer class="footer">
        <div class="footer-links">
            <a href="ex/rules.php">Rules</a>
            <a href="ex/your-safety.php">Safety</a>
            <a href="hiscores.php">Hiscores</a>
            <a href="ex/geting-started.php">Getting Started</a>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($serverName); ?>. All rights reserved.
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Login form handler
        $('#login-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $form.find('button');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Logging in...');
            
            $.ajax({
                url: 'login_handler.php',
                method: 'POST',
                data: {
                    username: $('#username').val(),
                    password: $('#password').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Create success message
                        const alert = $('<div class="alert alert-success">Login successful! Redirecting...</div>');
                        $form.before(alert);
                        
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Create error message
                        const alert = $('<div class="alert alert-error">' + (response.message || 'Login failed') + '</div>');
                        $form.before(alert);
                        
                        // Remove alert after 5 seconds
                        setTimeout(function() {
                            alert.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                        
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    const alert = $('<div class="alert alert-error">Connection error. Please try again.</div>');
                    $form.before(alert);
                    
                    setTimeout(function() {
                        alert.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                    
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    });
    </script>
</body>
</html>
