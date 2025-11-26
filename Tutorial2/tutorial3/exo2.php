<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise 2 - Take Attendance</title>
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
        
        .attendance-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(231, 84, 128, 0.3);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
        }
        
        .success {
            background-color: #f8d7da;
            color: #28a745;
            border: 1px solid #d4edda;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .error {
            background-color: #f5c6cb;
            color: #dc3545;
            border: 1px solid #f8d7da;
        }
        
        .student-list {
            margin: 20px 0;
        }
        
        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background-color: #fffafb;
            border-radius: 8px;
            border-left: 4px solid #e75480;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-weight: bold;
            color: #e75480;
            font-size: 16px;
        }
        
        .student-details {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .attendance-options {
            display: flex;
            gap: 15px;
        }
        
        .attendance-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        input[type="radio"] {
            transform: scale(1.2);
            accent-color: #e75480;
        }
        
        label {
            color: #666;
            cursor: pointer;
        }
        
        button {
            background-color: #e75480;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        
        button:hover {
            background-color: #d44672;
        }
        
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .date-info {
            text-align: center;
            color: #e75480;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .no-students {
            text-align: center;
            color: #666;
            padding: 40px;
            background-color: #fffafb;
            border-radius: 8px;
            border: 2px dashed #ffb6c1;
        }
        
        .attendance-count {
            background-color: #fffafb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #ffb6c1;
            text-align: center;
        }
        
        .count-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .count-item {
            padding: 10px;
            border-radius: 5px;
        }
        
        .count-total { background-color: #f8d7da; }
        .count-present { background-color: #d4edda; }
        .count-absent { background-color: #f5c6cb; }
        
        .count-number {
            font-size: 24px;
            font-weight: bold;
            color: #e75480;
        }
        
        .count-label {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Exercise 2 - Take Attendance</h1>

    <div class="attendance-container">
        <?php
        // Define file paths
        $studentsFile = __DIR__ . '/students.json';
        $today = date('Y-m-d');
        $attendanceFile = __DIR__ . "/attendance_{$today}.json";
        
        // Initialize variables
        $message = '';
        $messageType = '';
        $students = [];
        $attendanceTaken = false;
        
        // Check if attendance already taken for today
        if (file_exists($attendanceFile)) {
            $attendanceTaken = true;
            $message = "Attendance for today has already been taken.";
            $messageType = 'warning';
        }
        
        // Load students from JSON file
        if (file_exists($studentsFile)) {
            $jsonData = file_get_contents($studentsFile);
            if ($jsonData !== false) {
                $students = json_decode($jsonData, true) ?? [];
            }
        }
        
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$attendanceTaken) {
            $attendanceData = [];
            $presentCount = 0;
            $absentCount = 0;
            
            foreach ($students as $student) {
                $studentId = $student['student_id'];
                $status = $_POST["attendance_{$studentId}"] ?? 'absent';
                
                $attendanceData[] = [
                    'student_id' => $studentId,
                    'status' => $status
                ];
                
                if ($status === 'present') {
                    $presentCount++;
                } else {
                    $absentCount++;
                }
            }
            
            // Save attendance to JSON file
            $result = file_put_contents($attendanceFile, json_encode($attendanceData, JSON_PRETTY_PRINT));
            
            if ($result !== false) {
                $message = "Attendance taken successfully! Present: {$presentCount}, Absent: {$absentCount}";
                $messageType = 'success';
                $attendanceTaken = true;
            } else {
                $message = "Error: Could not save attendance data!";
                $messageType = 'error';
            }
        }
        
        // Display message if any
        if (!empty($message)) {
            echo "<div class='message $messageType'>$message</div>";
        }
        
        // Display current date
        echo "<div class='date-info'>Today's Date: " . date('F j, Y') . "</div>";
        
        // Display attendance counts if available
        if (file_exists($attendanceFile) && !$attendanceTaken) {
            $attendanceData = json_decode(file_get_contents($attendanceFile), true) ?? [];
            $presentCount = 0;
            $absentCount = 0;
            
            foreach ($attendanceData as $record) {
                if ($record['status'] === 'present') {
                    $presentCount++;
                } else {
                    $absentCount++;
                }
            }
            
            echo "<div class='attendance-count'>";
            echo "<div class='count-grid'>";
            echo "<div class='count-item count-total'><div class='count-number'>" . count($students) . "</div><div class='count-label'>Total Students</div></div>";
            echo "<div class='count-item count-present'><div class='count-number'>$presentCount</div><div class='count-label'>Present</div></div>";
            echo "<div class='count-item count-absent'><div class='count-number'>$absentCount</div><div class='count-label'>Absent</div></div>";
            echo "</div>";
            echo "</div>";
        }
        ?>
        
        <?php if (empty($students)): ?>
            <div class="no-students">
                <h3>No Students Found</h3>
                <p>Please add students first using the Add Student form.</p>
                <p><a href="add_student.php" style="color: #e75480; font-weight: bold;">Go to Add Student</a></p>
            </div>
        <?php elseif ($attendanceTaken): ?>
            <div class="no-students">
                <h3>Attendance Completed</h3>
                <p>Today's attendance has been recorded.</p>
                <p>File created: <strong>attendance_<?php echo $today; ?>.json</strong></p>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="student-list">
                    <?php foreach ($students as $student): ?>
                        <div class="student-item">
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                <div class="student-details">
                                    ID: <?php echo htmlspecialchars($student['student_id']); ?> | 
                                    Group: <?php echo htmlspecialchars($student['group']); ?>
                                </div>
                            </div>
                            <div class="attendance-options">
                                <div class="attendance-option">
                                    <input type="radio" 
                                           id="present_<?php echo $student['student_id']; ?>" 
                                           name="attendance_<?php echo $student['student_id']; ?>" 
                                           value="present" checked>
                                    <label for="present_<?php echo $student['student_id']; ?>">Present</label>
                                </div>
                                <div class="attendance-option">
                                    <input type="radio" 
                                           id="absent_<?php echo $student['student_id']; ?>" 
                                           name="attendance_<?php echo $student['student_id']; ?>" 
                                           value="absent">
                                    <label for="absent_<?php echo $student['student_id']; ?>">Absent</label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit">Submit Attendance</button>
            </form>
        <?php endif; ?>
    </div>

    <?php
    // Display previous attendance files
    $attendanceFiles = glob(__DIR__ . '/attendance_*.json');
    if (!empty($attendanceFiles)) {
        echo '<div class="attendance-container">';
        echo '<h2 style="color: #e75480; text-align: center;">Previous Attendance Records</h2>';
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">';
        
        foreach ($attendanceFiles as $file) {
            $filename = basename($file);
            $date = str_replace(['attendance_', '.json'], '', $filename);
            $attendanceData = json_decode(file_get_contents($file), true) ?? [];
            
            $presentCount = 0;
            foreach ($attendanceData as $record) {
                if ($record['status'] === 'present') {
                    $presentCount++;
                }
            }
            
            echo '<div class="student-item" style="text-align: center;">';
            echo '<div class="student-name">' . $date . '</div>';
            echo '<div class="student-details">' . $presentCount . ' / ' . count($attendanceData) . ' Present</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    ?>
</body>
</html>