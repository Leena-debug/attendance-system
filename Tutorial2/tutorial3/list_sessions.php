<?php
require_once 'db_connect.php';

$conn = getDBConnection();
$sessions = [];

if ($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM attendance_sessions ORDER BY date DESC, id DESC");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "<div style='color: red; padding: 15px; background: #ffe8e8; border: 2px solid #f44336; border-radius: 6px; margin: 20px;'>";
        echo "<h3>❌ Database Error</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Sessions List</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
            color: #333;
        }
        tr:hover { 
            background-color: #f5f5f5; 
        }
        .status-open {
            color: #4CAF50;
            background: #e8f5e8;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .status-closed {
            color: #f44336;
            background: #ffe8e8;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .actions a { 
            margin-right: 10px; 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            font-size: 14px;
        }
        .close-btn { 
            background: #ff9800; 
            color: white; 
        }
        .close-btn:hover { 
            background: #e68900; 
        }
        .view-btn { 
            background: #2196F3; 
            color: white; 
        }
        .view-btn:hover { 
            background: #0b7dda; 
        }
        .nav-links {
            text-align: center;
            margin: 30px 0 20px 0;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .nav-links a:hover {
            background: #45a049;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📊 Attendance Sessions</h2>
        
        <div class="nav-links">
            <a href="create_session.php">➕ Create New Session</a>
            <a href="close_session.php">🔒 Close Session</a>
            <a href="../exercise4/list_students.php">👨‍🎓 Manage Students</a>
        </div>

        <?php if (empty($sessions)): ?>
            <div class="empty-state">
                <div>📭</div>
                <h3>No Sessions Found</h3>
                <p>No attendance sessions have been created yet.</p>
                <a href="create_session.php" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 6px;">Create First Session</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course ID</th>
                        <th>Group ID</th>
                        <th>Date</th>
                        <th>Opened By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?= htmlspecialchars($session['id']) ?></td>
                        <td><?= htmlspecialchars($session['course_id']) ?></td>
                        <td><?= htmlspecialchars($session['group_id']) ?></td>
                        <td><?= htmlspecialchars($session['date']) ?></td>
                        <td><?= htmlspecialchars($session['opened_by']) ?></td>
                        <td>
                            <?php if ($session['status'] === 'open'): ?>
                                <span class="status-open">🟢 OPEN</span>
                            <?php else: ?>
                                <span class="status-closed">🔴 CLOSED</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php if ($session['status'] === 'open'): ?>
                                <a href="close_session.php?id=<?= $session['id'] ?>" class="close-btn" onclick="return confirm('Are you sure you want to close this session?')">🔒 Close</a>
                            <?php endif; ?>
                            <a href="session_details.php?id=<?= $session['id'] ?>" class="view-btn">👁️ View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; text-align: center; color: #666;">
                <p>Total Sessions: <strong><?= count($sessions) ?></strong></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>