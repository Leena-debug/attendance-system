<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    $group_id = $_POST['group_id'] ?? '';
    
    $errors = [];
    
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($matricule)) $errors[] = "Matricule is required";
    if (empty($group_id)) $errors[] = "Group ID is required";
    
    if (empty($errors)) {
        $conn = getDBConnection();
        
        if ($conn) {
            try {
                $stmt = $conn->prepare("INSERT INTO students (fullname, matricule, group_id) VALUES (?, ?, ?)");
                $stmt->execute([$fullname, $matricule, $group_id]);
                
                echo "<div style='color: green; padding: 10px; background: #e8f5e8; border: 1px solid green;'>Student added successfully!</div>";
            } catch(PDOException $e) {
                echo "<div style='color: red; padding: 10px; background: #ffe8e8; border: 1px solid red;'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h2>Add New Student</h2>
    <form method="POST">
        <div class="form-group">
            <label for="fullname">Full Name:</label>
            <input type="text" id="fullname" name="fullname" required>
        </div>
        <div class="form-group">
            <label for="matricule">Matricule:</label>
            <input type="text" id="matricule" name="matricule" required>
        </div>
        <div class="form-group">
            <label for="group_id">Group ID:</label>
            <input type="text" id="group_id" name="group_id" required>
        </div>
        <button type="submit">Add Student</button>
    </form>
    <p><a href="list_students.php">View All Students</a></p>
</body>
</html>