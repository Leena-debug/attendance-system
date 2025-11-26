<?php
/**
 * Database Setup Script for Attendance Management System
 * Run this file once to set up the database structure and demo data
 */

// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'attendance_system';

// Create connection
try {
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='max-width: 800px; margin: 20px auto; font-family: Arial, sans-serif;'>";
    echo "<div style='background: linear-gradient(135deg, #e91e63, #ad1457); color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;'>";
    echo "<h1>🎓 Attendance System - Database Setup</h1>";
    echo "<p>Setting up your database structure and demo data...</p>";
    echo "</div>";
    echo "<div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);'>";
    
    // Step 1: Create database
    echo "<h3 style='color: #e91e63; margin-bottom: 15px;'>Step 1: Creating Database</h3>";
    try {
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $conn->exec("USE `$db_name`");
        echo "<p style='color: #4caf50; padding: 10px; background: #e8f5e8; border-radius: 5px;'>✅ Database '$db_name' created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 10px; background: #ffe8e8; border-radius: 5px;'>❌ Error creating database: " . $e->getMessage() . "</p>";
        exit;
    }
    
    // Step 2: Create tables (WITHOUT foreign keys first)
    echo "<h3 style='color: #e91e63; margin: 25px 0 15px 0;'>Step 2: Creating Tables</h3>";
    
    // Disable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables_sql = [
        "users" => "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `email` VARCHAR(100),
            `role` ENUM('student', 'professor', 'admin') NOT NULL,
            `full_name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`),
            INDEX `idx_role` (`role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "courses" => "CREATE TABLE IF NOT EXISTS `courses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_code` VARCHAR(20) UNIQUE NOT NULL,
            `course_name` VARCHAR(100) NOT NULL,
            `professor_id` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_course_code` (`course_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "student_groups" => "CREATE TABLE IF NOT EXISTS `student_groups` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `group_name` VARCHAR(20) NOT NULL,
            `course_id` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_course_group` (`course_id`, `group_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "enrollments" => "CREATE TABLE IF NOT EXISTS `enrollments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT,
            `course_id` INT,
            `group_id` INT,
            `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_enrollment` (`student_id`, `course_id`),
            INDEX `idx_student` (`student_id`),
            INDEX `idx_course` (`course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "attendance_sessions" => "CREATE TABLE IF NOT EXISTS `attendance_sessions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `group_id` INT NOT NULL,
            `session_date` DATE NOT NULL,
            `opened_by` INT NOT NULL,
            `status` ENUM('open', 'closed') DEFAULT 'open',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_date` (`session_date`),
            INDEX `idx_course_group` (`course_id`, `group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "attendance_records" => "CREATE TABLE IF NOT EXISTS `attendance_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `student_id` INT NOT NULL,
            `status` ENUM('present', 'absent', 'late') DEFAULT 'absent',
            `participation` ENUM('excellent', 'good', 'average', 'poor') DEFAULT 'average',
            `notes` TEXT,
            `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_attendance` (`session_id`, `student_id`),
            INDEX `idx_session` (`session_id`),
            INDEX `idx_student` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "justifications" => "CREATE TABLE IF NOT EXISTS `justifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `session_id` INT NOT NULL,
            `reason` TEXT NOT NULL,
            `file_path` VARCHAR(255),
            `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `reviewed_by` INT,
            `reviewed_at` TIMESTAMP NULL,
            INDEX `idx_student` (`student_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    $tables_created = 0;
    foreach ($tables_sql as $table_name => $sql) {
        try {
            $conn->exec($sql);
            echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Table '$table_name' created successfully!</p>";
            $tables_created++;
        } catch (PDOException $e) {
            echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error creating table '$table_name': " . $e->getMessage() . "</p>";
        }
    }
    
    // Step 3: Insert demo data in CORRECT order
    echo "<h3 style='color: #e91e63; margin: 25px 0 15px 0;'>Step 3: Inserting Demo Data</h3>";
    
    // 1. Insert users first (no dependencies)
    try {
        $conn->exec("DELETE FROM `users`");
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $conn->exec("INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`) VALUES
            (1, 'prof1', '$password_hash', 'prof1@Hemili-univ.dz', 'professor', 'Dr. Mohamed Hemili'),
            (2, 'prof2', '$password_hash', 'prof2@Jaberi-univ.dz', 'professor', 'Dr. Baraa Jaberi'),
            (3, 'prof3', '$password_hash', 'prof3@Hemili-univ.dz', 'professor', 'Dr. Amel Saidani'),
            (4, 'prof4', '$password_hash', 'prof4@Jaberi-univ.dz', 'professor', 'Dr. Norhen Lamiric'),
            (5, 'student1', '$password_hash', 'lyna.achi@univ-alg.dz', 'student', 'Lyna Achi'),
            (6, 'student2', '$password_hash', 'feriel.smith@univ-alg.dz', 'student', 'Feriel Smith'),
            (7, 'student3', '$password_hash', 'hakim.webbi@univ-alg.dz', 'student', 'Hakim Webbi'),
            (8, 'student4', '$password_hash', 'sarbi@univ-alg.dz', 'student', 'Sara Abi'),
            (9, 'student5', '$password_hash', 'abdulbarr.klem@univ-alg.dz', 'student', 'AbdulBarr Klem'),
            (10, 'student6', '$password_hash', 'ghaith05@univ-alg.dz', 'student', 'Ghaith Benoun'),
            (11, 'admin', '$admin_hash', 'admin@univ-alg.dz', 'admin', 'Admin User')");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Demo users inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting users: " . $e->getMessage() . "</p>";
    }
    
    // 2. Insert courses (depends on users)
    try {
        $conn->exec("DELETE FROM `courses`");
        $conn->exec("INSERT INTO `courses` (`id`, `course_code`, `course_name`, `professor_id`) VALUES
            (1, 'PAW', 'Introduction to Advanced Web Programming', 1),
            (2, 'MATH3', 'Advanced Mathematics', 2),
            (3, 'SID', 'System d\'Information Distribuée', 1)");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Demo courses inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting courses: " . $e->getMessage() . "</p>";
    }
    
    // 3. Insert student groups (depends on courses)
    try {
        $conn->exec("DELETE FROM `student_groups`");
        $conn->exec("INSERT INTO `student_groups` (`id`, `group_name`, `course_id`) VALUES
            (1, 'GROUP_1', 1),
            (2, 'GROUP_2', 1),
            (3, 'GROUP_3', 2), 
            (4, 'GROUP_4', 2),
            (5, 'GROUP_5', 3)");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Student groups inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting student groups: " . $e->getMessage() . "</p>";
    }
    
    // 4. Insert enrollments (depends on users, courses, and groups)
    try {
        $conn->exec("DELETE FROM `enrollments`");
        $conn->exec("INSERT INTO `enrollments` (`student_id`, `course_id`, `group_id`) VALUES
            (3, 1, 1), 
            (4, 1, 1), 
            (5, 1, 2),
            (3, 2, 3), 
            (4, 2, 3),
            (5, 3, 5)");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Student enrollments inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting enrollments: " . $e->getMessage() . "</p>";
    }
    
    // 5. Insert attendance sessions (depends on courses, groups, and users)
    try {
        $conn->exec("DELETE FROM `attendance_sessions`");
        $conn->exec("INSERT INTO `attendance_sessions` (`id`, `course_id`, `group_id`, `session_date`, `opened_by`, `status`) VALUES
            (1, 1, 1, CURDATE(), 1, 'open'),
            (2, 1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1, 'closed'),
            (3, 2, 3, CURDATE(), 2, 'open'),
            (4, 3, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 'closed')");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Attendance sessions inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting attendance sessions: " . $e->getMessage() . "</p>";
    }
    
    // 6. Insert attendance records (depends on sessions and users)
    try {
        $conn->exec("DELETE FROM `attendance_records`");
        $conn->exec("INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`, `participation`, `notes`) VALUES
            (1, 3, 'present', 'excellent', 'Active participation'),
            (1, 4, 'present', 'good', 'Good work'),
            (1, 5, 'late', 'average', 'Arrived 10 minutes late'),
            (2, 3, 'present', 'excellent', 'Outstanding performance'),
            (2, 4, 'absent', 'poor', 'No show'),
            (3, 3, 'present', 'good', NULL),
            (3, 4, 'present', 'average', NULL),
            (4, 5, 'present', 'excellent', 'Perfect attendance')");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Attendance records inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting attendance records: " . $e->getMessage() . "</p>";
    }
    
    // 7. Insert justifications (depends on sessions and users)
    try {
        $conn->exec("DELETE FROM `justifications`");
        $conn->exec("INSERT INTO `justifications` (`student_id`, `session_id`, `reason`, `status`, `submitted_at`) VALUES
            (4, 2, 'Medical appointment with doctor', 'pending', NOW())");
        echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Justifications inserted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error inserting justifications: " . $e->getMessage() . "</p>";
    }
    
    // Step 4: Add foreign key constraints AFTER data is inserted
    echo "<h3 style='color: #e91e63; margin: 25px 0 15px 0;'>Step 4: Adding Foreign Key Constraints</h3>";
    
    $foreign_keys_sql = [
        "courses" => "ALTER TABLE `courses` ADD FOREIGN KEY (`professor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL",
        "student_groups" => "ALTER TABLE `student_groups` ADD FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE",
        "enrollments" => "ALTER TABLE `enrollments` 
            ADD FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`group_id`) REFERENCES `student_groups`(`id`) ON DELETE CASCADE",
        "attendance_sessions" => "ALTER TABLE `attendance_sessions`
            ADD FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`group_id`) REFERENCES `student_groups`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`opened_by`) REFERENCES `users`(`id`) ON DELETE CASCADE",
        "attendance_records" => "ALTER TABLE `attendance_records`
            ADD FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE",
        "justifications" => "ALTER TABLE `justifications`
            ADD FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions`(`id`) ON DELETE CASCADE,
            ADD FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL"
    ];
    
    $keys_added = 0;
    foreach ($foreign_keys_sql as $table_name => $sql) {
        try {
            $conn->exec($sql);
            echo "<p style='color: #4caf50; padding: 8px; background: #e8f5e8; border-radius: 5px; margin: 5px 0;'>✅ Foreign keys added to '$table_name' successfully!</p>";
            $keys_added++;
        } catch (PDOException $e) {
            echo "<p style='color: #f44336; padding: 8px; background: #ffe8e8; border-radius: 5px; margin: 5px 0;'>❌ Error adding foreign keys to '$table_name': " . $e->getMessage() . "</p>";
        }
    }
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Step 5: Final summary
    echo "<h3 style='color: #e91e63; margin: 25px 0 15px 0;'>Step 5: Setup Complete!</h3>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; border: 2px solid #4caf50; margin: 15px 0;'>";
    echo "<h4 style='color: #4caf50; margin-bottom: 15px;'>✅ Database Setup Successful!</h4>";
    echo "<p>All tables created, demo data inserted, and foreign keys established successfully.</p>";
    echo "<p><strong>Tables Created:</strong> $tables_created</p>";
    echo "<p><strong>Foreign Keys Added:</strong> $keys_added</p>";
    echo "</div>";
    
    // Demo credentials
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; border: 2px solid #2196f3; margin: 20px 0;'>";
    echo "<h4 style='color: #2196f3; margin-bottom: 15px;'>🔐 Demo Login Credentials</h4>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";
    echo "<div style='background: white; padding: 15px; border-radius: 6px;'>";
    echo "<strong>👨‍🏫 Professor Account:</strong><br>";
    echo "Username: <code>prof1</code><br>";
    echo "Password: <code>password123</code>";
    echo "</div>";
    echo "<div style='background: white; padding: 15px; border-radius: 6px;'>";
    echo "<strong>🎓 Student Account:</strong><br>";
    echo "Username: <code>student1</code><br>";
    echo "Password: <code>password123</code>";
    echo "</div>";
    echo "<div style='background: white; padding: 15px; border-radius: 6px;'>";
    echo "<strong>👨‍💼 Admin Account:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>admin123</code>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Next steps
    echo "<div style='background: #fce4ec; padding: 20px; border-radius: 8px; border: 2px solid #e91e63; margin: 20px 0;'>";
    echo "<h4 style='color: #e91e63; margin-bottom: 15px;'>🚀 Next Steps</h4>";
    echo "<ol style='margin-left: 20px;'>";
    echo "<li><strong>Test the login</strong> using the demo credentials above</li>";
    echo "<li><strong>Explore different roles</strong> to see the system features</li>";
    echo "<li><strong>Delete this setup file</strong> for security: <code>setup-database.php</code></li>";
    echo "<li><strong>Start using</strong> the attendance management system!</li>";
    echo "</ol>";
    echo "</div>";
    
    // Action buttons
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px;'>";
    echo "<a href='auth/login.php' style='display: block; padding: 15px; background: #e91e63; color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: bold;'>🎓 Go to Login Page</a>";
    echo "<a href='professor/' style='display: block; padding: 15px; background: #2196f3; color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: bold;'>👨‍🏫 Professor Dashboard</a>";
    echo "<a href='admin/' style='display: block; padding: 15px; background: #4caf50; color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: bold;'>👨‍💼 Admin Dashboard</a>";
    echo "</div>";
    
    echo "</div>"; // Close content div
    echo "</div>"; // Close main div
    
} catch (PDOException $e) {
    echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background: #ffe8e8; border: 2px solid #f44336; border-radius: 10px; text-align: center;'>";
    echo "<h2 style='color: #f44336;'>❌ Database Connection Failed</h2>";
    echo "<p style='color: #666; margin: 15px 0;'>Unable to connect to the database. Please check your database configuration.</p>";
    echo "<div style='background: white; padding: 15px; border-radius: 6px; margin: 15px 0; text-align: left;'>";
    echo "<strong>Common Solutions:</strong>";
    echo "<ul style='margin: 10px 0 0 20px;'>";
    echo "<li>Make sure MySQL is running (check XAMPP/Laragon/WAMP)</li>";
    echo "<li>Verify database credentials in this file</li>";
    echo "<li>Check if MySQL port (usually 3306) is available</li>";
    echo "<li>Ensure MySQL user has proper permissions</li>";
    echo "</ul>";
    echo "</div>";
    echo "<p style='color: #666;'><strong>Error Details:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>