<?php
require_once 'check_admin.php';
requireAdmin();

$dbPath = '/home/crucifix/Server/db.sqlite';
$db = new PDO("sqlite:$dbPath");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $report_id = $_POST['report_id'] ?? 0;
    
    if ($action === 'review') {
        $stmt = $db->prepare("UPDATE report SET reviewed = 1 WHERE id = ?");
        $stmt->execute([$report_id]);
    }
    
    header('Location: reports.php?success=1');
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'unreviewed';

// Build query
$query = "
    SELECT 
        r.*,
        a.username as reporter_username
    FROM report r
    LEFT JOIN account a ON r.account_id = a.id
    WHERE 1=1
";

if ($filter === 'unreviewed') {
    $query .= " AND r.reviewed = 0";
} elseif ($filter === 'reviewed') {
    $query .= " AND r.reviewed = 1";
}

$query .= " ORDER BY r.timestamp DESC LIMIT 100";

$stmt = $db->query($query);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Report reasons
function getReportReason($reasonId) {
    $reasons = [
        0 => 'Offensive Language',
        1 => 'Item Scamming',
        2 => 'Password Scamming',
        3 => 'Bug Abuse',
        4 => 'Staff Impersonation',
        5 => 'Account Sharing',
        6 => 'Macroing',
        7 => 'Multiple Logging',
        8 => 'Encouraging Rule Breaking',
        9 => 'Advertising',
        10 => 'Real World Trading',
        11 => 'Asking Personal Details',
        12 => 'Other'
    ];
    return $reasons[$reasonId] ?? "Unknown (#$reasonId)";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Player Reports - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .header h1 {
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .nav {
            background: #2a2a2a;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .nav ul {
            list-style: none;
            display: flex;
        }
        
        .nav li {
            flex: 1;
        }
        
        .nav a {
            display: block;
            padding: 15px;
            color: #fff;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: #3a3a3a;
        }
        
        .nav a.active {
            background: #444;
            border-bottom: 3px solid #ffd700;
        }
        
        .container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .filter-bar {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filter-bar a {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 10px;
            background: #1a1a1a;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .filter-bar a.active {
            background: #ffd700;
            color: #333;
        }
        
        .filter-bar a:hover {
            background: #3a3a3a;
        }
        
        .reports-table {
            background: #2a2a2a;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #1a1a1a;
            padding: 15px;
            text-align: left;
            color: #ffd700;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #444;
        }
        
        tr:hover {
            background: #333;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-unreviewed {
            background: #f44336;
            color: #fff;
        }
        
        .badge-reviewed {
            background: #4CAF50;
            color: #fff;
        }
        
        .reason-badge {
            background: #ff9800;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .btn {
            padding: 5px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            color: #fff;
            text-decoration: none;
        }
        
        .btn-review { background: #4CAF50; }
        .btn-view { background: #2196F3; }
        
        .btn:hover { opacity: 0.8; }
        
        .logout {
            float: right;
            background: #ff4444;
            color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .success {
            background: #4CAF50;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .reporter {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .offender {
            color: #f44336;
            font-weight: bold;
        }
        
        .no-reports {
            text-align: center;
            color: #999;
            padding: 40px;
        }
        
        .world-badge {
            background: #9C27B0;
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="logout.php" class="logout">Logout</a>
        <h1>🚨 Player Reports</h1>
    </div>
    
    <?php require_once 'includes/nav.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success">✓ Report marked as reviewed</div>
        <?php endif; ?>
        
        <div class="filter-bar">
            <a href="?filter=unreviewed" class="<?php echo $filter === 'unreviewed' ? 'active' : ''; ?>">
                Unreviewed (<?php 
                    $count = $db->query("SELECT COUNT(*) FROM report WHERE reviewed = 0")->fetchColumn();
                    echo $count;
                ?>)
            </a>
            <a href="?filter=reviewed" class="<?php echo $filter === 'reviewed' ? 'active' : ''; ?>">
                Reviewed
            </a>
            <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">
                All Reports
            </a>
        </div>
        
        <div class="reports-table">
            <?php if (count($reports) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>World</th>
                        <th>Reporter</th>
                        <th>Offender</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>#<?php echo $report['id']; ?></td>
                        <td><?php echo $report['timestamp']; ?></td>
                        <td>
                            <span class="world-badge">W<?php echo $report['world']; ?></span>
                        </td>
                        <td class="reporter">
                            <?php echo htmlspecialchars($report['reporter_username'] ?? 'Unknown'); ?>
                        </td>
                        <td class="offender">
                            <?php echo htmlspecialchars($report['offender']); ?>
                        </td>
                        <td>
                            <span class="reason-badge">
                                <?php echo getReportReason($report['reason']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($report['reviewed']): ?>
                                <span class="badge badge-reviewed">REVIEWED</span>
                            <?php else: ?>
                                <span class="badge badge-unreviewed">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$report['reviewed']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="review">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <button type="submit" class="btn btn-review">Mark Reviewed</button>
                                </form>
                            <?php endif; ?>
                            <a href="players.php?search=<?php echo urlencode($report['offender']); ?>" class="btn btn-view">View Player</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-reports">
                <?php if ($filter === 'unreviewed'): ?>
                    No unreviewed reports - good job! 
                <?php else: ?>
                    No reports found
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>