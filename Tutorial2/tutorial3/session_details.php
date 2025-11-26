<?php
require_once 'db_connect.php';

$session = null;
$conn = getDBConnection();

if (isset($_GET['id'])) {
    $session_id = $_GET['id'];
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "<div style='color: red; padding: 15px; background: #ffe8e8; border: 2px solid #f44336; border-radius: 6px; margin: 20px;'>";
            echo "<h3>❌ Database Error</h3>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
}

if (!$session) {
    echo "<div style='color: red; padding: 15px; background: #ffe8e8; border: 2px solid #f44336; border-radius: 6px; margin: 20px;'>";
    echo "<h3>❌ Session Not Found</h3>";
    echo "<p>The requested session does not exist.</p>";
    echo "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Session Details</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
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
        }
        .session-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-item {
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #2196F3;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .status-open {
            color: #4CAF50;
            background: #e8f5e8;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-closed {
            color: #f44336;
            background: #ffe8e8;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        .nav-links {
            text-align: center;
            margin-top: 30px;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .nav-links a:hover {
            background: #0b7dda;
        }
        .close-btn {
            background: #ff9800 !important;
        }
        .close-btn:hover {
            background: #e68900 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Session Details</h2>
        
        <div class="session-info">
            <div class="info-item">
                <span class="info-label">Session ID:</span>
                <?= htmlspecialchars($session['id']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Course ID:</span>
                <?= htmlspecialchars($session['course_id']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Group ID:</span>
                <?= htmlspecialchars($session['group_id']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Date:</span>
                <?= htmlspecialchars($session['date']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Opened By:</span>
                Professor #<?= htmlspecialchars($session['opened_by']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <?php if ($session['status'] === 'open'): ?>
                    <span class="status-open">🟢 OPEN</span>
                <?php else: ?>
                    <span class="status-closed">🔴 CLOSED</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="nav-links">
            <?php if ($session['status'] === 'open'): ?>
                <a href="close_session.php?id=<?= $session['id'] ?>" class="close-btn" onclick="return confirm('Are you sure you want to close this session?')">🔒 Close Session</a>
            <?php endif; ?>
            <a href="list_sessions.php">📊 All Sessions</a>
            <a href="create_session.php">➕ New Session</a>
            <a href="../exercise4/list_students.php">👨‍🎓 Students</a>
        </div>
    </div>
</body>
</html>