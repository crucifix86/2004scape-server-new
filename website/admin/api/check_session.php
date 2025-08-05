<?php
session_start();
require_once '../check_admin.php';

header('Content-Type: application/json');

echo json_encode([
    'session' => $_SESSION,
    'is_admin' => isAdmin(),
    'admin_id' => $_SESSION['admin_id'] ?? null,
    'admin_username' => $_SESSION['admin_username'] ?? null,
    'logged_in' => $_SESSION['logged_in'] ?? false,
    'staff_level' => $_SESSION['staff_level'] ?? 0
]);
?>