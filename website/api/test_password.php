<?php
// Test password hashing to match game's format

$testPassword = "testpass123";
echo "Original: $testPassword\n";
echo "Lowercase: " . strtolower($testPassword) . "\n";

// Game's way - bcrypt with lowercase
$gameHash = password_hash(strtolower($testPassword), PASSWORD_BCRYPT, ['cost' => 10]);
echo "Game hash: $gameHash\n";

// Verify
$verified = password_verify(strtolower($testPassword), $gameHash);
echo "Verification: " . ($verified ? "PASS" : "FAIL") . "\n";

// Check what's in database for testauth
$dbPath = '/home/crucifix/Server/db.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $stmt = $db->prepare("SELECT username, password FROM account WHERE username = 'testauth'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "\nDatabase entry:\n";
        echo "Username: " . $row['username'] . "\n";
        echo "Password hash: " . $row['password'] . "\n";
        echo "Hash length: " . strlen($row['password']) . "\n";
        
        // Test verification
        $dbVerify = password_verify(strtolower($testPassword), $row['password']);
        echo "DB Verification: " . ($dbVerify ? "PASS" : "FAIL") . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>