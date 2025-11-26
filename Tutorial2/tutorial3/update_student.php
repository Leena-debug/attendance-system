<?php
require_once 'db_connect.php';

$conn = getDBConnection();
$student = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $fullname = $_POST['fullname'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    $group_id = $_POST['group_id'] ?? '';
    
    $errors = [];
    
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($matricule)) $errors[] = "Matricule is required";
    if (empty($group_id)) $errors[] = "Group ID is required";
    
    if (empty($errors) && $conn) {
        try {
            $stmt = $conn->prepare("UPDATE students SET fullname = ?, matricule = ?, group_id = ? WHERE id = ?");
            $stmt->execute([$fullname, $matricule, $group_id, $id]);
            
            echo "<div style='color: green; padding: 10px; background: #e8f5e8; border: 1px solid green;'>Student updated successfully!</div>";
            
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            echo "<div style='color: red; padding: 10px; background: #ffe8e8; border: 1px solid red;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

if (!$student) {
    echo "<div style='color: red; padding: 10px; background: #ffe8e8; border: 1px solid red;'>Student not found</div>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Student</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0b7dda; }
    </style>
</head>
<body>
    <h2>Update Student</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($student['id']) ?>">
        <div class="form-group">
            <label for="fullname">Full Name:</label>
            <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($student['fullname']) ?>" required>
        </div>
        <div class="form-group">
            <label for="matricule">Matricule:</label>
            <input type="text" id="matricule" name="matricule" value="<?= htmlspecialchars($student['matricule']) ?>" required>
        </div>
        <div class="form-group">
            <label for="group_id">Group ID:</label>
            <input type="text" id="group_id" name="group_id" value="<?= htmlspecialchars($student['group_id']) ?>" required>
        </div>
        <button type="submit">Update Student</button>
    </form>
    <p><a href="list_students.php">Back to Student List</a></p>
</body>
</html>