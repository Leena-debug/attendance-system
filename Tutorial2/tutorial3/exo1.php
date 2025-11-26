<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise 1 - Add Student</title>
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
        
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(231, 84, 128, 0.3);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #e75480;
            font-weight: bold;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ffb6c1;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #e75480;
        }
        
        button {
            background-color: #e75480;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        button:hover {
            background-color: #d44672;
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
        
        .error {
            background-color: #f5c6cb;
            color: #dc3545;
            border: 1px solid #f8d7da;
        }
        
        .student-list {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(231, 84, 128, 0.3);
            max-width: 800px;
            margin: 20px auto;
        }
        
        .student-item {
            background-color: #fffafb;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid #e75480;
        }
    </style>
</head>
<body>
    <h1>Exercise 1 - Add Student</h1>

    <div class="form-container">
        <?php
        // Define the JSON file path - Laragon compatible
        $jsonFile = __DIR__ . '/students.json';
        
        // Initialize variables
        $message = '';
        $messageType = '';
        
        // Check if form is submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $student_id = trim($_POST['student_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $group = trim($_POST['group'] ?? '');
            
            // Validate form data
            $errors = [];
            
            if (empty($student_id)) {
                $errors[] = "Student ID is required";
            } elseif (!preg_match('/^\d+$/', $student_id)) {
                $errors[] = "Student ID must contain only numbers";
            }
            
            if (empty($name)) {
                $errors[] = "Name is required";
            } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
                $errors[] = "Name must contain only letters and spaces";
            }
            
            if (empty($group)) {
                $errors[] = "Group is required";
            }
            
            // If no errors, process the data
            if (empty($errors)) {
                // Load existing students from JSON file
                $students = [];
                if (file_exists($jsonFile)) {
                    $jsonData = file_get_contents($jsonFile);
                    if ($jsonData !== false) {
                        $students = json_decode($jsonData, true) ?? [];
                    }
                }
                
                // Check if student ID already exists
                $studentExists = false;
                foreach ($students as $student) {
                    if (isset($student['student_id']) && $student['student_id'] === $student_id) {
                        $studentExists = true;
                        break;
                    }
                }
                
                if ($studentExists) {
                    $message = "Error: Student ID $student_id already exists!";
                    $messageType = 'error';
                } else {
                    // Add new student
                    $newStudent = [
                        'student_id' => $student_id,
                        'name' => $name,
                        'group' => $group
                    ];
                    
                    $students[] = $newStudent;
                    
                    // Save back to JSON file
                    $result = file_put_contents($jsonFile, json_encode($students, JSON_PRETTY_PRINT));
                    if ($result !== false) {
                        $message = "Student $name (ID: $student_id) added successfully!";
                        $messageType = 'success';
                    } else {
                        $message = "Error: Could not save student data! Check file permissions.";
                        $messageType = 'error';
                    }
                }
            } else {
                $message = "Please fix the following errors:<br>" . implode('<br>', $errors);
                $messageType = 'error';
            }
        }
        
        // Display message if any
        if (!empty($message)) {
            echo "<div class='message $messageType'>$message</div>";
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="student_id">Student ID:</label>
                <input type="text" id="student_id" name="student_id" 
                       value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="group">Group:</label>
                <input type="text" id="group" name="group" 
                       value="<?php echo htmlspecialchars($_POST['group'] ?? ''); ?>" 
                       required>
            </div>
            
            <button type="submit">Add Student</button>
        </form>
    </div>

    <?php
    // Display existing students
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        if ($jsonData !== false) {
            $students = json_decode($jsonData, true) ?? [];
            
            if (!empty($students)) {
                echo '<div class="student-list">';
                echo '<h2 style="color: #e75480; text-align: center;">Existing Students (' . count($students) . ')</h2>';
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">';
                
                foreach ($students as $student) {
                    if (isset($student['student_id']) && isset($student['name']) && isset($student['group'])) {
                        echo '<div class="student-item">';
                        echo '<strong>ID:</strong> ' . htmlspecialchars($student['student_id']) . '<br>';
                        echo '<strong>Name:</strong> ' . htmlspecialchars($student['name']) . '<br>';
                        echo '<strong>Group:</strong> ' . htmlspecialchars($student['group']);
                        echo '</div>';
                    }
                }
                
                echo '</div>';
                echo '</div>';
            }
        }
    } else {
        echo '<div class="student-list">';
        echo '<h2 style="color: #e75480; text-align: center;">No Students Yet</h2>';
        echo '<p style="text-align: center; color: #666;">Add your first student using the form above.</p>';
        echo '</div>';
    }
    ?>
</body>
</html>