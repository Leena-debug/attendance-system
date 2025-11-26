<?php
require_once '../auth/check_auth.php';
checkAuth('student');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$course_attendance = [];
$selected_course = null;

if ($conn) {
    // Get student's enrolled courses
    $stmt = $conn->prepare("
        SELECT c.*, g.group_name 
        FROM courses c 
        JOIN enrollments e ON c.id = e.course_id 
        JOIN student_groups g ON e.student_group_id = g.id 
        WHERE e.student_id = ? 
        ORDER BY c.course_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attendance for selected course or all courses
    $course_id = $_GET['course_id'] ?? '';
    
    if ($course_id) {
        // Get specific course details
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $selected_course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get attendance for specific course
        $stmt = $conn->prepare("
            SELECT ar.*, s.session_date, c.course_name, u.full_name as professor_name
            FROM attendance_records ar 
            JOIN attendance_sessions s ON ar.session_id = s.id 
            JOIN courses c ON s.course_id = c.id 
            JOIN users u ON s.opened_by = u.id 
            WHERE ar.student_id = ? AND c.id = ? 
            ORDER BY s.session_date DESC
        ");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        $course_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Get all attendance records
        $stmt = $conn->prepare("
            SELECT ar.*, s.session_date, c.course_name, c.course_code, u.full_name as professor_name
            FROM attendance_records ar 
            JOIN attendance_sessions s ON ar.session_id = s.id 
            JOIN courses c ON s.course_id = c.id 
            JOIN users u ON s.opened_by = u.id 
            WHERE ar.student_id = ? 
            ORDER BY s.session_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $course_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">📚 My Courses & Attendance</h1>
        <p style="color: #666;">View your attendance records across all courses</p>
    </div>

    <!-- Course Selection -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">🔍 Filter by Course</h3>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="courses.php" class="btn <?php echo empty($course_id) ? '' : 'btn-secondary'; ?>" 
               style="text-decoration: none;">
                📊 All Courses
            </a>
            <?php foreach ($courses as $course): ?>
            <a href="courses.php?course_id=<?php echo $course['id']; ?>" 
               class="btn <?php echo $course_id == $course['id'] ? '' : 'btn-secondary'; ?>" 
               style="text-decoration: none;">
                <?php echo htmlspecialchars($course['course_code']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($selected_course): ?>
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="color: #e91e63; margin-bottom: 5px;">
                    📊 <?php echo htmlspecialchars($selected_course['course_name']); ?>
                </h3>
                <p style="color: #666; margin: 0;">
                    <?php echo htmlspecialchars($selected_course['course_code']); ?> • 
                    <?php echo count($course_attendance); ?> sessions recorded
                </p>
            </div>
            <a href="justifications.php?course_id=<?php echo $course_id; ?>" class="btn">
                📄 Submit Justification
            </a>
        </div>

        <!-- Course Statistics -->
        <?php if (!empty($course_attendance)): ?>
        <?php
        $present_count = array_filter($course_attendance, function($r) { return $r['status'] === 'present'; });
        $absent_count = array_filter($course_attendance, function($r) { return $r['status'] === 'absent'; });
        $late_count = array_filter($course_attendance, function($r) { return $r['status'] === 'late'; });
        $attendance_rate = count($present_count) / count($course_attendance) * 100;
        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px;">
                <div style="font-size: 24px; color: #4caf50;">✅</div>
                <strong><?php echo count($present_count); ?></strong><br>
                <small>Present</small>
            </div>
            <div style="text-align: center; padding: 15px; background: #ffe8e8; border-radius: 8px;">
                <div style="font-size: 24px; color: #f44336;">❌</div>
                <strong><?php echo count($absent_count); ?></strong><br>
                <small>Absent</small>
            </div>
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 8px;">
                <div style="font-size: 24px; color: #ff9800;">⚠️</div>
                <strong><?php echo count($late_count); ?></strong><br>
                <small>Late</small>
            </div>
            <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                <div style="font-size: 24px; color: #2196f3;">📈</div>
                <strong><?php echo round($attendance_rate, 1); ?>%</strong><br>
                <small>Attendance Rate</small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Attendance Records -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">
            📅 Attendance Records 
            <?php if ($selected_course): ?>
                for <?php echo htmlspecialchars($selected_course['course_name']); ?>
            <?php else: ?>
                for All Courses
            <?php endif; ?>
        </h3>

        <?php if (empty($course_attendance)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                <h4>No Attendance Records</h4>
                <p>Your attendance hasn't been recorded yet for 
                   <?php echo $selected_course ? 'this course' : 'any courses'; ?>.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <?php if (!$selected_course): ?>
                                <th>Course</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Participation</th>
                            <th>Professor</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($course_attendance as $record): ?>
                        <tr>
                            <?php if (!$selected_course): ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['course_name']); ?></strong><br>
                                    <small style="color: #666;"><?php echo $record['course_code']; ?></small>
                                </td>
                            <?php endif; ?>
                            <td><?php echo $record['session_date']; ?></td>
                            <td>
                                <span style="background: 
                                    <?php echo $record['status'] === 'present' ? '#4caf50' : 
                                           ($record['status'] === 'absent' ? '#f44336' : '#ff9800'); ?>; 
                                    color: white; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                    <?php echo strtoupper($record['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: 
                                    <?php echo $record['participation'] === 'excellent' ? '#4caf50' : 
                                           ($record['participation'] === 'good' ? '#2196f3' : 
                                           ($record['participation'] === 'average' ? '#ff9800' : '#f44336')); ?>; 
                                    font-weight: bold;">
                                    ⭐ <?php echo ucfirst($record['participation']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['professor_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['notes'] ?: '-'); ?></td>
                            <td>
                                <?php if ($record['status'] === 'absent' || $record['status'] === 'late'): ?>
                                    <a href="justifications.php?session_id=<?php echo $record['session_id']; ?>" 
                                       class="btn" style="padding: 6px 12px; font-size: 12px;">
                                       📄 Justify
                                    </a>
                                <?php else: ?>
                                    <span style="color: #666; font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Overall Statistics -->
            <?php if (!$selected_course): ?>
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="color: #e91e63; margin-bottom: 15px;">📈 Overall Statistics</h4>
                <?php
                $total_records = count($course_attendance);
                $total_present = count(array_filter($course_attendance, function($r) { return $r['status'] === 'present'; }));
                $overall_rate = $total_present / $total_records * 100;
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="text-align: center;">
                        <strong style="font-size: 18px; color: #333;"><?php echo $total_records; ?></strong><br>
                        <small>Total Sessions</small>
                    </div>
                    <div style="text-align: center;">
                        <strong style="font-size: 18px; color: #4caf50;"><?php echo $total_present; ?></strong><br>
                        <small>Present Sessions</small>
                    </div>
                    <div style="text-align: center;">
                        <strong style="font-size: 18px; color: #2196f3;"><?php echo round($overall_rate, 1); ?>%</strong><br>
                        <small>Overall Attendance Rate</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add search functionality
    $('table').before('<div style="margin-bottom: 15px;"><input type="text" id="searchInput" placeholder="🔍 Search records..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%;"></div>');
    
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>