<?php
require_once '../auth/check_auth.php';
checkAuth('professor');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$summary_data = [];
$course_id = $_GET['course_id'] ?? '';

if ($conn) {
    if ($course_id) {
        // Get course details
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND professor_id = ?");
        $stmt->execute([$course_id, $_SESSION['user_id']]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            // Get attendance summary
            $stmt = $conn->prepare("
                SELECT 
                    u.id as student_id,
                    u.full_name,
                    u.username,
                    g.group_name,
                    COUNT(ar.id) as total_sessions,
                    SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                    ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as attendance_rate
                FROM users u
                JOIN enrollments e ON u.id = e.student_id
                JOIN student_groups g ON e.group_id = g.id
                LEFT JOIN attendance_sessions s ON s.course_id = ? AND s.group_id = g.id
                LEFT JOIN attendance_records ar ON ar.session_id = s.id AND ar.student_id = u.id
                WHERE e.course_id = ? AND u.role = 'student'
                GROUP BY u.id, u.full_name, u.username, g.group_name
                ORDER BY g.group_name, u.full_name
            ");
            $stmt->execute([$course_id, $course_id]);
            $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Get professor's courses for dropdown
    $stmt = $conn->prepare("SELECT * FROM courses WHERE professor_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">📈 Attendance Summary</h1>
        <p style="color: #666;">View detailed attendance reports for your courses</p>
    </div>
    
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">🔍 Select Course</h3>
        <form method="GET">
            <div class="form-group">
                <select name="course_id" onchange="this.form.submit()" style="padding: 12px; border: 2px solid #f0f0f0; border-radius: 6px; width: 100%;">
                    <option value="">Select a course to view summary</option>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $course_id == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['course_name']); ?> (<?php echo $c['course_code']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    
    <?php if ($course_id && !empty($summary_data)): ?>
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 25px;">
            <h3 style="color: #e91e63; margin: 0;">
                📊 Summary for <?php echo htmlspecialchars($course['course_name']); ?>
            </h3>
            <button onclick="window.print()" class="btn">🖨️ Print Report</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Group</th>
                        <th>Total Sessions</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary_data as $student): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($student['username']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($student['group_name']); ?></td>
                        <td><?php echo $student['total_sessions']; ?></td>
                        <td style="color: #4caf50; font-weight: bold;"><?php echo $student['present_count']; ?></td>
                        <td style="color: #f44336; font-weight: bold;"><?php echo $student['absent_count']; ?></td>
                        <td style="color: #ff9800; font-weight: bold;"><?php echo $student['late_count']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f0; border-radius: 10px; height: 8px;">
                                    <div style="background: 
                                        <?php echo $student['attendance_rate'] >= 80 ? '#4caf50' : 
                                               ($student['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>; 
                                        width: <?php echo min($student['attendance_rate'], 100); ?>%; 
                                        height: 100%; border-radius: 10px;">
                                    </div>
                                </div>
                                <span style="font-weight: bold; color: 
                                    <?php echo $student['attendance_rate'] >= 80 ? '#4caf50' : 
                                           ($student['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>">
                                    <?php echo $student['attendance_rate']; ?>%
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php if ($student['attendance_rate'] >= 80): ?>
                                <span style="color: #4caf50; font-weight: bold;">✅ Excellent</span>
                            <?php elseif ($student['attendance_rate'] >= 60): ?>
                                <span style="color: #ff9800; font-weight: bold;">⚠️ Needs Improvement</span>
                            <?php else: ?>
                                <span style="color: #f44336; font-weight: bold;">❌ Critical</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Statistics -->
        <div style="margin-top: 30px; padding: 25px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="color: #e91e63; margin-bottom: 20px;">📊 Course Statistics</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <?php
                $total_students = count($summary_data);
                $avg_attendance = array_sum(array_column($summary_data, 'attendance_rate')) / $total_students;
                $excellent_count = count(array_filter($summary_data, function($s) { return $s['attendance_rate'] >= 80; }));
                $critical_count = count(array_filter($summary_data, function($s) { return $s['attendance_rate'] < 60; }));
                ?>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 8px;">
                    <div style="font-size: 32px; color: #e91e63;">👥</div>
                    <strong style="font-size: 24px; color: #333;"><?php echo $total_students; ?></strong><br>
                    <small>Total Students</small>
                </div>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 8px;">
                    <div style="font-size: 32px; color: #4caf50;">📈</div>
                    <strong style="font-size: 24px; color: #333;"><?php echo round($avg_attendance, 1); ?>%</strong><br>
                    <small>Average Attendance</small>
                </div>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 8px;">
                    <div style="font-size: 32px; color: #4caf50;">⭐</div>
                    <strong style="font-size: 24px; color: #333;"><?php echo $excellent_count; ?></strong><br>
                    <small>Excellent (>80%)</small>
                </div>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 8px;">
                    <div style="font-size: 32px; color: #f44336;">⚠️</div>
                    <strong style="font-size: 24px; color: #333;"><?php echo $critical_count; ?></strong><br>
                    <small>Critical (<60%)</small>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($course_id): ?>
    <div class="card">
        <div style="text-align: center; padding: 40px; color: #666;">
            <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
            <h3>No Attendance Data</h3>
            <p>No attendance records found for this course yet.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Add search functionality
    $('table').before('<div style="margin-bottom: 15px;"><input type="text" id="searchInput" placeholder="🔍 Search students..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%;"></div>');
    
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>