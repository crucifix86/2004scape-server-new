<?php
$username = $_GET['u'] ?? 'test';
$password = $_GET['p'] ?? 'test';

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

$stmt = $db->prepare("SELECT id, username, password, staffmodlevel FROM account WHERE LOWER(username) = LOWER(?)");
$stmt->execute([$username]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Testing login for: $username\n\n";

if (!$account) {
    echo "Account not found!\n";
    exit;
}

echo "Account found:\n";
echo "- ID: " . $account['id'] . "\n";
echo "- Username: " . $account['username'] . "\n";
echo "- Staff Level: " . $account['staffmodlevel'] . "\n";
echo "- Password hash prefix: " . substr($account['password'], 0, 7) . "...\n\n";

// Test different verification methods
echo "Testing password verification:\n\n";

// Method 1: Using Node.js bcrypt via command line
echo "Method 1 - Node.js bcrypt command:\n";
$command = sprintf(
    'cd /home/crucifix/Server && node -e "import(\'bcrypt\').then(b => b.default.compare(%s, %s).then(r => console.log(r ? \'true\' : \'false\')));"',
    escapeshellarg(strtolower($password)),
    escapeshellarg($account['password'])
);
echo "Command: " . substr($command, 0, 100) . "...\n";
$result = trim(shell_exec($command . " 2>&1"));
echo "Result: '$result'\n\n";

// Method 2: Using the helper script
echo "Method 2 - Using helper script:\n";
$command2 = sprintf(
    'cd /home/crucifix/Server && node -e "import(\'bcrypt\').then(b => b.default.compare(\'%s\', \'%s\').then(r => console.log(r)));"',
    strtolower($password),
    $account['password']
);
$result2 = trim(shell_exec($command2 . " 2>&1"));
echo "Result: '$result2'\n\n";

// Method 3: Test with a fresh hash
echo "Method 3 - Create new hash and verify:\n";
$hashCommand = sprintf(
    'cd /home/crucifix/Server && node hash_password.mjs %s 2>&1',
    escapeshellarg(strtolower($password))
);
$newHash = trim(shell_exec($hashCommand));
echo "New hash created: " . substr($newHash, 0, 7) . "...\n";

$verifyCommand = sprintf(
    'cd /home/crucifix/Server && node -e "import(\'bcrypt\').then(b => b.default.compare(%s, %s).then(r => console.log(r)));"',
    escapeshellarg(strtolower($password)),
    escapeshellarg($newHash)
);
$verifyResult = trim(shell_exec($verifyCommand . " 2>&1"));
echo "Verification of new hash: $verifyResult\n";
?>