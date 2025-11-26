<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise 3 - Test Database Connection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #fff0f5;
        }
        
        h1 {
            color: #e75480;
            text-align: center;
        }
        
        .connection-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(231, 84, 128, 0.3);
            max-width: 600px;
            margin: 20px auto;
            text-align: center;
        }
        
        .status-message {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .success {
            background-color: #f8d7da;
            color: #28a745;
            border: 2px solid #d4edda;
        }
        
        .error {
            background-color: #f5c6cb;
            color: #dc3545;
            border: 2px solid #f8d7da;
        }
        
        .info-box {
            background-color: #fffafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #e75480;
            text-align: left;
        }
        
        .info-title {
            color: #e75480;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .config-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 14px;
        }
        
        .test-buttons {
            margin: 20px 0;
        }
        
        button {
            background-color: #e75480;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        
        button:hover {
            background-color: #d44672;
        }
        
        .file-structure {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(231, 84, 128, 0.3);
            max-width: 600px;
            margin: 20px auto;
        }
        
        .file-list {
            list-style: none;
            padding: 0;
        }
        
        .file-item {
            padding: 10px;
            margin: 5px 0;
            background-color: #fffafb;
            border-radius: 5px;
            border-left: 4px solid #ffb6c1;
        }
        
        .file-name {
            font-weight: bold;
            color: #e75480;
        }
        
        .log-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Exercise 3 - Database Connection Test</h1>

    <div class="connection-container">
        <?php
        // Include database connection files
        require_once 'config.php';
        require_once 'db_connect.php';

        // Test the connection
        $db = getDBConnection();
        $isConnected = $db->isConnected();
        $error = $db->getError();

        if ($isConnected) {
            echo '<div class="status-message success">';
            echo '✅ Connection Successful!';
            echo '</div>';
            
            // Get database info
            try {
                $pdo = $db->getConnection();
                $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
                
                echo '<div class="info-box">';
                echo '<div class="info-title">Database Information:</div>';
                echo '<div class="config-details">';
                echo "MySQL Version: " . htmlspecialchars($version) . "<br>";
                echo "Database: " . htmlspecialchars($dbName) . "<br>";
                echo "Host: " . DB_HOST . "<br>";
                echo "Charset: " . DB_CHARSET;
                echo '</div>';
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="info-box">';
                echo '<div class="info-title">Database Info Error:</div>';
                echo '<div class="config-details">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div>';
            }
            
        } else {
            echo '<div class="status-message error">';
            echo '❌ Connection Failed!';
            echo '</div>';
            
            if ($error) {
                echo '<div class="info-box">';
                echo '<div class="info-title">Error Details:</div>';
                echo '<div class="config-details">' . htmlspecialchars($error) . '</div>';
                echo '</div>';
            }
        }
        ?>

        <div class="test-buttons">
            <button onclick="location.reload()">Test Again</button>
            <button onclick="window.location.href='../'">Back to Tutorial</button>
        </div>

        <div class="info-box">
            <div class="info-title">Current Configuration:</div>
            <div class="config-details">
                Host: <?php echo DB_HOST; ?><br>
                Username: <?php echo DB_USER; ?><br>
                Database: <?php echo DB_NAME; ?><br>
                Log File: <?php echo defined('LOG_FILE') ? LOG_FILE : 'Not set'; ?>
            </div>
        </div>

        <?php
        // Show log file content if exists
        if (defined('LOG_FILE') && file_exists(LOG_FILE)) {
            echo '<div class="info-box">';
            echo '<div class="info-title">Recent Log Entries:</div>';
            echo '<div class="log-content">';
            $logContent = file_get_contents(LOG_FILE);
            echo nl2br(htmlspecialchars($logContent));
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>

    <div class="file-structure">
        <h3 style="color: #e75480; text-align: center;">File Structure Created</h3>
        <ul class="file-list">
            <li class="file-item">
                <span class="file-name">config.php</span> - Database configuration
            </li>
            <li class="file-item">
                <span class="file-name">db_connect.php</span> - Connection class with try/catch
            </li>
            <li class="file-item">
                <span class="file-name">test_connection.php</span> - Connection test script
            </li>
            <li class="file-item">
                <span class="file-name">database_errors.log</span> - Error log file (auto-created)
            </li>
        </ul>
    </div>

    <div class="info-box" style="max-width: 600px; margin: 20px auto;">
        <div class="info-title">Next Steps:</div>
        <ol style="text-align: left; color: #666;">
            <li>Create database 'school_attendance' in phpMyAdmin</li>
            <li>Test the connection using this page</li>
            <li>Check the log file for any errors</li>
            <li>Proceed to Exercise 4</li>
        </ol>
    </div>
</body>
</html>