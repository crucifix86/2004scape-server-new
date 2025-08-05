<?php
$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

echo "=== BAN/MUTE SYSTEM TEST ===\n\n";

// Test banning fixedauth account for 1 hour
$testUsername = 'fixedauth';
$banUntil = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "Testing ban on account: $testUsername\n";
echo "Ban until: $banUntil\n\n";

// Apply ban
$stmt = $db->prepare("UPDATE account SET banned_until = ? WHERE username = ?");
$stmt->execute([$banUntil, $testUsername]);

echo "Ban applied. Checking account status:\n";

// Check account
$stmt = $db->prepare("SELECT username, banned_until, muted_until FROM account WHERE username = ?");
$stmt->execute([$testUsername]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($account) {
    echo "- Username: " . $account['username'] . "\n";
    echo "- Banned until: " . ($account['banned_until'] ?? 'Not banned') . "\n";
    echo "- Muted until: " . ($account['muted_until'] ?? 'Not muted') . "\n";
    
    if ($account['banned_until'] && strtotime($account['banned_until']) > time()) {
        echo "\n✅ Account is BANNED - login should be blocked\n";
    }
} else {
    echo "Account not found\n";
}

echo "\nNow try logging into the game with this account - it should reject the login with 'Account disabled' message.\n";

echo "\nTo unban, run: UPDATE account SET banned_until = NULL WHERE username = '$testUsername';\n";
?>