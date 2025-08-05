<?php
// This creates a game account .sav file when someone registers

function createGameAccount($username, $password) {
    // The game expects lowercase usernames for files
    $username = strtolower($username);
    $savePath = "/home/crucifix/Server/data/players/main/{$username}.sav";
    
    // Check if account already exists
    if (file_exists($savePath)) {
        return false; // Account already exists
    }
    
    // Create a minimal .sav file
    // This is the binary format the game expects
    $data = [];
    
    // Magic number (0x2004)
    $data[] = 0x20;
    $data[] = 0x04;
    
    // Version (6)
    $data[] = 0x00;
    $data[] = 0x06;
    
    // Username as base37 (simplified - just empty for now)
    for ($i = 0; i < 8; $i++) {
        $data[] = 0x00;
    }
    
    // Coordinates (Lumbridge spawn)
    $data[] = 0x0C;
    $data[] = 0x96; // x = 3222
    $data[] = 0x0C;
    $data[] = 0x92; // y = 3218
    $data[] = 0x00; // height = 0
    
    // Initialize with zeros for basic data
    for ($i = 0; $i < 1400; $i++) {
        $data[] = 0x00;
    }
    
    // Calculate CRC32 checksum
    $binary = '';
    foreach ($data as $byte) {
        $binary .= chr($byte);
    }
    $crc = crc32($binary);
    
    // Append CRC as 4 bytes (big-endian)
    $data[] = ($crc >> 24) & 0xFF;
    $data[] = ($crc >> 16) & 0xFF;
    $data[] = ($crc >> 8) & 0xFF;
    $data[] = $crc & 0xFF;
    
    // Write the file
    $binary = '';
    foreach ($data as $byte) {
        $binary .= chr($byte);
    }
    
    if (!file_put_contents($savePath, $binary)) {
        return false;
    }
    
    // Set permissions so game can read/write
    chmod($savePath, 0666);
    
    return true;
}