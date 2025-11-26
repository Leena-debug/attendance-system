<?php
// db_connect.php - Database Connection Class
require_once 'config.php';

class DatabaseConnection {
    private $pdo;
    private $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($e->getMessage());
            $this->pdo = null;
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function getError() {
        return $this->error;
    }

    public function isConnected() {
        return $this->pdo !== null;
    }

    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] Database Error: $message" . PHP_EOL;
        
        if (defined('LOG_FILE') && LOG_FILE) {
            file_put_contents(LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        // Also log to PHP error log
        error_log($message);
    }

    public function __destruct() {
        $this->pdo = null;
    }
}

// Helper function to get database connection
function getDBConnection() {
    static $db = null;
    
    if ($db === null) {
        $db = new DatabaseConnection();
    }
    
    return $db;
}
?>