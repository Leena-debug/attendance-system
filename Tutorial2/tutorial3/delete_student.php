<?php
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn = getDBConnection();
    
    if ($conn) {
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header("Location: list_students.php");
exit;
?>