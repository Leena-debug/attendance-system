<?php
require_once '../auth/check_auth.php';
checkAuth('professor');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$students = [];
$courses = [];
$groups = [];
$error = '';

if ($conn) {
    // Get professor's courses
    $stmt = $conn->prepare("SELECT * FROM courses WHERE professor_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = $_POST['course_id'] ?? '';
        $group_id = $_POST['student_group_id'] ?? '';
        $session_date = $_POST['session_date'] ?? date('Y-m-d');
        
        if (!empty($course_id) && !empty($group_id)) {
            // Get students in this course group
            $stmt = $conn->prepare("
                SELECT u.id, u.full_name, u.username 
                FROM users u 
                JOIN enrollments e ON u.id = e.student_id 
                WHERE e.course_id = ? AND e.student_group_id = ? AND u.role = 'student'
            ");
            $stmt->execute([$course_id, $group_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Handle attendance submission
        if (isset($_POST['mark_attendance'])) {
            try {
                // Create attendance session
                $stmt = $conn->prepare("
                    INSERT INTO attendance_sessions (course_id, student_group_id, session_date, opened_by, status) 
                    VALUES (?, ?, ?, ?, 'open')
                ");
                $stmt->execute([$course_id, $group_id, $session_date, $_SESSION['user_id']]);
                $session_id = $conn->lastInsertId();
                
                // Insert attendance records
                foreach ($_POST['attendance'] as $student_id => $status) {
                    $participation = $_POST['participation'][$student_id] ?? 'average';
                    $notes = $_POST['notes'][$student_id] ?? '';
                    
                    $stmt = $conn->prepare("
                        INSERT INTO attendance_records (session_id, student_id, status, participation, notes) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$session_id, $student_id, $status, $participation, $notes]);
                }
                
                $_SESSION['success'] = "✅ Attendance marked successfully for " . count($students) . " students!";
                header("Location: index.php");
                exit;
                
            } catch(PDOException $e) {
                $error = "❌ Error saving attendance: " . $e->getMessage();
            }
        }
    }
    
    // Get groups for selected course
    if (isset($_GET['course_id'])) {
        $stmt = $conn->prepare("SELECT * FROM student_groups WHERE course_id = ?");
        $stmt->execute([$_GET['course_id']]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">📝 Take Attendance</h1>
        <p style="color: #666;">Mark attendance for your students</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">🔍 Select Course & Group</h3>
        <form method="POST" id="attendanceForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="course_id">📚 Course:</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" 
                            <?php echo isset($_POST['course_id']) && $_POST['course_id'] == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?> (<?php echo $course['course_code']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="student_group_id">👥 Group:</label>
                    <select name="student_group_id" id="student_group_id" required>
                        <option value="">Select Group</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>"
                            <?php echo isset($_POST['student_group_id']) && $_POST['student_group_id'] == $group['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="session_date">📅 Date:</label>
                    <input type="date" name="session_date" id="session_date" 
                           value="<?php echo $_POST['session_date'] ?? date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn">👥 Load Students</button>
        </form>
    </div>
    
    <?php if (!empty($students)): ?>
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">🎯 Student Attendance</h3>
        <form method="POST">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
            <input type="hidden" name="session_date" value="<?php echo $session_date; ?>">
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Participation</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($student['username']); ?></small>
                            </td>
                            <td>
                                <select name="attendance[<?php echo $student['id']; ?>]" class="status-select" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="present">✅ Present</option>
                                    <option value="absent">❌ Absent</option>
                                    <option value="late">⚠️ Late</option>
                                </select>
                            </td>
                            <td>
                                <select name="participation[<?php echo $student['id']; ?>]" class="participation-select" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="excellent">⭐ Excellent</option>
                                    <option value="good">👍 Good</option>
                                    <option value="average" selected>😐 Average</option>
                                    <option value="poor">👎 Poor</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="notes[<?php echo $student['id']; ?>]" 
                                       placeholder="Optional notes..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                <button type="submit" name="mark_attendance" class="btn btn-success" style="flex: 1;">
                    ✅ Save Attendance for <?php echo count($students); ?> Students
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Load groups when course changes
    $('#course_id').change(function() {
        var courseId = $(this).val();
        if (courseId) {
            window.location.href = 'attendance.php?course_id=' + courseId;
        }
    });
    
    // Auto-submit form when group is selected
    $('#group_id').change(function() {
        if ($(this).val()) {
            $('#attendanceForm').submit();
        }
    });
    
    // Add quick selection buttons
    $('.status-select').change(function() {
        $(this).css('background-color', 
            $(this).val() === 'present' ? '#e8f5e8' : 
            $(this).val() === 'absent' ? '#ffe8e8' : '#fff3cd'
        );
    });
});
</script>

<?php include '../includes/footer.php'; ?>