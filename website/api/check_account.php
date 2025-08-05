<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$username = $_GET['username'] ?? '';

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        a.*,
        pd.x, pd.y, pd.height,
        COUNT(DISTINCT ps.skill_id) as skills_count,
        SUM(ps.level) as total_level
    FROM accounts a
    LEFT JOIN player_data pd ON a.id = pd.account_id
    LEFT JOIN player_skills ps ON a.id = ps.account_id
    WHERE a.username = ?
    GROUP BY a.id
");
$stmt->execute([$username]);
$account = $stmt->fetch();

if ($account) {
    // Get skills
    $stmt = $pdo->prepare("SELECT * FROM player_skills WHERE account_id = ? ORDER BY skill_id");
    $stmt->execute([$account['id']]);
    $skills = $stmt->fetchAll();
    
    $account['skills'] = $skills;
    echo json_encode(['success' => true, 'account' => $account]);
} else {
    echo json_encode(['success' => false, 'message' => 'Account not found']);
}
?>