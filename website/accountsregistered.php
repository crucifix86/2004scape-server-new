<?php
// 2006Scape Server Integration - Accounts Registered
// This file receives total registered accounts updates from the game server

// CHANGE THIS to match your server's website password from data/secrets.json
$SERVER_PASSWORD = "your_password_here";

// Get parameters from server
$password = isset($_GET['pass']) ? $_GET['pass'] : '';
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;
$world = isset($_GET['world']) ? intval($_GET['world']) : 1;

// Verify password
if ($password !== $SERVER_PASSWORD) {
    http_response_code(403);
    die("Invalid password");
}

// Store the data - using a simple JSON file
// In production, you'd probably use a database
$dataFile = 'data/server_stats.json';
$dataDir = dirname($dataFile);

// Create data directory if it doesn't exist
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Load existing data or create new
if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
} else {
    $data = array();
}

// Update registered accounts count
$data['registered_accounts'] = $amount;
$data['accounts_last_update'] = time();
$data['accounts_last_update_readable'] = date('Y-m-d H:i:s');

// Save data
file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

// Return success
echo "OK - Total registered accounts: $amount";
?>