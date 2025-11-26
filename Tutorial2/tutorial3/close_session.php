<?php
require_once 'db_connect.php';

$message = '';
$message_type = '';

if (isset($_GET['id'])) {
    $session_id = $_GET['id'];
    $conn = getDBConnection();
    
    if ($conn) {
        try {
            // First, check if session exists and is open
            $stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ? AND status = 'open'");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Close the session
                $stmt = $conn->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ?");
                $stmt->execute([$session_id]);
                
                $message = "✅ Session #$session_id has been closed successfully!";
                $message_type = 'success';
            } else {
                $message = "❌ Session not found or already closed!";
                $message_type = 'error';
            }
            
        } catch(PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = "❌ Database connection failed!";
        $message_type = 'error';
    }
} else {
    $message = "❌ No session ID provided!";
    $message_type = 'error';
}

// If form is submitted for manual closing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id'])) {
    $session_id = $_POST['session_id'];
    $conn = getDBConnection();
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ?");
            $stmt->execute([$session_id]);
            
            $message = "✅ Session #$session_id has been closed successfully!";
            $message_type = 'success';
            
        } catch(PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Close Attendance Session</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            border-bottom: 2px solid #ff9800;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            padding: 20px;
            background: #e8f5e8;
            border: 2px solid #4CAF50;
            border-radius: 6px;
            margin: 20px 0;
        }
        .error {
            color: #f44336;
            padding: 20px;
            background: #ffe8e8;
            border: 2px solid #f44336;
            border-radius: 6px;
            margin: 20px 0;
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold;
            color: #555;
        }
        input[type="text"] { 
            padding: 12px; 
            width: 100%; 
            border: 1px solid #ddd; 
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button { 
            padding: 12px 30px; 
            background: #ff9800; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
        }
        button:hover { 
            background: #e68900; 
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
        .info-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔒 Close Attendance Session</h2>
        
        <?php if ($message): ?>
            <div class="<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <h3><?php echo $message_type === 'success' ? '✅ Success!' : '❌ Error!'; ?></h3>
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ Information:</strong>
            <p>Close an attendance session to prevent further modifications. This action cannot be undone.</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="session_id">Session ID to Close:</label>
                <input type="text" id="session_id" name="session_id" placeholder="Enter session ID (e.g., 1, 2, 3)" required>
            </div>
            
            <button type="submit" onclick="return confirm('Are you sure you want to close this session?')">🔒 Close Session</button>
        </form>
        
        <div class="nav-links">
            <a href="list_sessions.php">📊 View All Sessions</a>
            <a href="create_session.php">➕ Create New Session</a>
            <a href="../exercise4/list_students.php">👨‍🎓 Manage Students</a>
        </div>
    </div>
</body>
</html>