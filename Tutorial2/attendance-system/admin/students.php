<?php
require_once '../auth/check_auth.php';
checkAuth('admin');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$students = [];
$success_message = '';
$error = '';

if ($conn) {
    // Handle student actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_student':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $email = $_POST['email'] ?? '';
                
                if ($username && $password && $full_name) {
                    try {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("
                            INSERT INTO users (username, password, email, role, full_name) 
                            VALUES (?, ?, ?, 'student', ?)
                        ");
                        $stmt->execute([$username, $password_hash, $email, $full_name]);
                        $success_message = "✅ Student '$full_name' added successfully!";
                    } catch(PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error = "❌ Username '$username' already exists!";
                        } else {
                            $error = "❌ Error adding student: " . $e->getMessage();
                        }
                    }
                } else {
                    $error = "❌ Please fill in all required fields!";
                }
                break;
                
            case 'delete_student':
                $student_id = $_POST['student_id'] ?? '';
                if ($student_id) {
                    try {
                        // Delete related records first
                        $conn->beginTransaction();
                        
                        // Delete justifications
                        $stmt = $conn->prepare("DELETE FROM justifications WHERE student_id = ?");
                        $stmt->execute([$student_id]);
                        
                        // Delete attendance records
                        $stmt = $conn->prepare("DELETE FROM attendance_records WHERE student_id = ?");
                        $stmt->execute([$student_id]);
                        
                        // Delete enrollments
                        $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
                        $stmt->execute([$student_id]);
                        
                        // Delete student
                        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
                        $stmt->execute([$student_id]);
                        
                        $conn->commit();
                        $success_message = "✅ Student deleted successfully!";
                    } catch(PDOException $e) {
                        $conn->rollBack();
                        $error = "❌ Error deleting student: " . $e->getMessage();
                    }
                }
                break;
                
            case 'enroll_course':
                $student_id = $_POST['student_id'] ?? '';
                $course_id = $_POST['course_id'] ?? '';
                $group_id = $_POST['group_id'] ?? '';
                
                if ($student_id && $course_id && $group_id) {
                    try {
                        $stmt = $conn->prepare("
                            INSERT INTO enrollments (student_id, course_id, group_id) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$student_id, $course_id, $group_id]);
                        $success_message = "✅ Student enrolled in course successfully!";
                    } catch(PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error = "❌ Student is already enrolled in this course!";
                        } else {
                            $error = "❌ Error enrolling student: " . $e->getMessage();
                        }
                    }
                }
                break;
        }
    }
    
    // Get all students with their statistics
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT e.course_id) as course_count,
            COUNT(ar.id) as attendance_count,
            SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
            ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as attendance_rate
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        LEFT JOIN attendance_records ar ON u.id = ar.student_id
        WHERE u.role = 'student'
        GROUP BY u.id, u.username, u.full_name, u.email, u.created_at
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get courses and groups for enrollment
    $stmt = $conn->query("SELECT * FROM courses ORDER BY course_name");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT * FROM groups ORDER BY group_name");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">👥 Student Management</h1>
        <p style="color: #666;">Manage student accounts, enrollments, and view performance</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Add Student Form -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">➕ Add New Student</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_student">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">👤 Username:</label>
                    <input type="text" name="username" required placeholder="Enter unique username">
                </div>
                <div class="form-group">
                    <label for="password">🔒 Password:</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                <div class="form-group">
                    <label for="full_name">📛 Full Name:</label>
                    <input type="text" name="full_name" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label for="email">📧 Email (Optional):</label>
                    <input type="email" name="email" placeholder="Enter email address">
                </div>
            </div>
            <button type="submit" class="btn btn-success">✅ Add Student</button>
        </form>
    </div>

    <!-- Student List -->
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #e91e63; margin: 0;">🎓 Student List (<?php echo count($students); ?> students)</h3>
            <div>
                <input type="text" id="searchStudents" placeholder="🔍 Search students..." 
                       style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 300px;">
            </div>
        </div>

        <?php if (empty($students)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">👥</div>
                <h4>No Students Found</h4>
                <p>No students have been added to the system yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Courses</th>
                            <th>Attendance</th>
                            <th>Performance</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                <small style="color: #666;">@<?php echo htmlspecialchars($student['username']); ?></small><br>
                                <?php if ($student['email']): ?>
                                    <small style="color: #666;"><?php echo htmlspecialchars($student['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background: #e91e63; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                    <?php echo $student['course_count']; ?> courses
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; background: #f0f0f0; border-radius: 10px; height: 6px;">
                                        <div style="background: 
                                            <?php echo $student['attendance_rate'] >= 80 ? '#4caf50' : 
                                                   ($student['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>; 
                                            width: <?php echo min($student['attendance_rate'], 100); ?>%; 
                                            height: 100%; border-radius: 10px;">
                                        </div>
                                    </div>
                                    <span style="font-size: 12px; font-weight: bold; min-width: 45px;">
                                        <?php echo $student['attendance_rate'] ? $student['attendance_rate'] . '%' : 'N/A'; ?>
                                    </span>
                                </div>
                                <small style="color: #666;">
                                    <?php echo $student['present_count'] ?? 0; ?>/<?php echo $student['attendance_count'] ?? 0; ?> present
                                </small>
                            </td>
                            <td>
                                <?php if ($student['attendance_rate']): ?>
                                    <?php if ($student['attendance_rate'] >= 85): ?>
                                        <span style="color: #4caf50; font-weight: bold;">⭐ Excellent</span>
                                    <?php elseif ($student['attendance_rate'] >= 70): ?>
                                        <span style="color: #ff9800; font-weight: bold;">👍 Good</span>
                                    <?php else: ?>
                                        <span style="color: #f44336; font-weight: bold;">⚠️ Needs Attention</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #666;">No data</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small style="color: #666;">
                                    <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <button onclick="showEnrollModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')" 
                                            class="btn" style="padding: 6px 10px; font-size: 11px;">
                                        📚 Enroll
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student? This action cannot be undone!')">
                                        <input type="hidden" name="action" value="delete_student">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 6px 10px; font-size: 11px;">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enrollment Modal -->
<div id="enrollModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <h3 style="color: #e91e63; margin-bottom: 20px;" id="modalTitle">Enroll Student</h3>
        <form method="POST">
            <input type="hidden" name="action" value="enroll_course">
            <input type="hidden" name="student_id" id="modalStudentId">
            
            <div class="form-group">
                <label for="course_id">📚 Course:</label>
                <select name="course_id" id="course_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>">
                        <?php echo htmlspecialchars($course['course_name']); ?> (<?php echo $course['course_code']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="group_id">👥 Group:</label>
                <select name="group_id" id="group_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Select Group</option>
                    <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group['id']; ?>">
                        <?php echo htmlspecialchars($group['group_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-success" style="flex: 1;">✅ Enroll Student</button>
                <button type="button" onclick="hideEnrollModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Student search functionality
    $('#searchStudents').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#studentsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

function showEnrollModal(studentId, studentName) {
    $('#modalStudentId').val(studentId);
    $('#modalTitle').text('Enroll ' + studentName + ' in Course');
    $('#enrollModal').fadeIn();
}

function hideEnrollModal() {
    $('#enrollModal').fadeOut();
}

// Close modal when clicking outside
$(document).on('click', function(e) {
    if ($(e.target).attr('id') === 'enrollModal') {
        hideEnrollModal();
    }
});
</script>

<?php include '../includes/header.php'; ?>