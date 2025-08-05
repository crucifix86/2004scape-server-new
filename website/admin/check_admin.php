<?php
session_start();

// Auto-promote configured developers
require_once 'auto_promote.php';

// Database connection
$dbPath = '/home/crucifix/Server/db.sqlite';

function isAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    global $dbPath;
    try {
        $db = new PDO("sqlite:$dbPath");
        $stmt = $db->prepare("SELECT staffmodlevel FROM account WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Staff level 2+ required for admin panel
        return $account && $account['staffmodlevel'] >= 2;
    } catch (Exception $e) {
        return false;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        // For main admin, also check the general session
        if (isset($_SESSION['logged_in']) && $_SESSION['staff_level'] >= 2) {
            $_SESSION['admin_id'] = $_SESSION['user_id'];
            $_SESSION['admin_username'] = $_SESSION['username'];
            $_SESSION['admin_level'] = $_SESSION['staff_level'];
        } else {
            header('Location: login.php');
            exit;
        }
    }
}

function getStaffLevelName($level) {
    switch($level) {
        case 0: return 'Player';
        case 1: return 'Helper';
        case 2: return 'Moderator';
        case 3: return 'Admin';
        case 4: return 'Developer';
        default: return 'Unknown';
    }
}

function logModAction($targetId, $actionId, $data = null) {
    global $dbPath;
    try {
        $db = new PDO("sqlite:$dbPath");
        $stmt = $db->prepare("
            INSERT INTO mod_action (account_id, target_id, action_id, data, ip, timestamp)
            VALUES (?, ?, ?, ?, ?, datetime('now'))
        ");
        $stmt->execute([
            $_SESSION['admin_id'],
            $targetId,
            $actionId,
            $data,
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        error_log("Mod action log failed: " . $e->getMessage());
    }
}

// Mod action types
define('MOD_ACTION_BAN', 1);
define('MOD_ACTION_UNBAN', 2);
define('MOD_ACTION_MUTE', 3);
define('MOD_ACTION_UNMUTE', 4);
define('MOD_ACTION_KICK', 5);
define('MOD_ACTION_PROMOTE', 6);
define('MOD_ACTION_DEMOTE', 7);
define('MOD_ACTION_IP_BAN', 8);
define('MOD_ACTION_IP_UNBAN', 9);
define('MOD_ACTION_VIEW_LOGS', 10);
?>