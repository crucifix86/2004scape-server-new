<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

echo "=== ONLINE PLAYER DIAGNOSTICS ===\n\n";

// Check all accounts with logged_in = 1
$stmt = $db->query("SELECT username, logged_in, login_time, logout_time FROM account WHERE logged_in = 1");
$loggedIn = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Accounts with logged_in = 1:\n";
foreach ($loggedIn as $acc) {
    echo "- " . $acc['username'] . " (login: " . $acc['login_time'] . ", logout: " . $acc['logout_time'] . ")\n";
}

echo "\n";

// Check recent logins
$stmt = $db->query("SELECT username, login_time FROM account WHERE login_time IS NOT NULL ORDER BY login_time DESC LIMIT 5");
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent login times:\n";
foreach ($recent as $acc) {
    $timeDiff = time() - strtotime($acc['login_time']);
    echo "- " . $acc['username'] . ": " . $acc['login_time'] . " (" . round($timeDiff/60) . " minutes ago)\n";
}

echo "\n";

// Check session table
$stmt = $db->query("SELECT COUNT(*) as count FROM session");
$sessions = $stmt->fetch()['count'];
echo "Sessions in session table: " . $sessions . "\n";

// Check login table for recent activity
$stmt = $db->query("SELECT COUNT(*) as count FROM login WHERE timestamp > datetime('now', '-5 minutes')");
$recentLogins = $stmt->fetch()['count'];
echo "Recent logins (last 5 min) in login table: " . $recentLogins . "\n";

// Try different queries for online count
echo "\nDifferent online count methods:\n";

// Method 1: logged_in flag
$stmt = $db->query("SELECT COUNT(*) as count FROM account WHERE logged_in = 1");
echo "Method 1 (logged_in = 1): " . $stmt->fetch()['count'] . "\n";

// Method 2: recent login_time
$stmt = $db->query("SELECT COUNT(*) as count FROM account WHERE login_time > datetime('now', '-5 minutes')");
echo "Method 2 (login_time < 5 min): " . $stmt->fetch()['count'] . "\n";

// Method 3: login table
$stmt = $db->query("SELECT COUNT(DISTINCT account_id) as count FROM login WHERE timestamp > datetime('now', '-5 minutes')");
echo "Method 3 (login table): " . $stmt->fetch()['count'] . "\n";
?>