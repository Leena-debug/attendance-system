<?php
require_once '../auth/check_auth.php';
checkAuth('student');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$courses = [];
$recent_attendance = [];
$stats = [];

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

    // Get recent attendance
    $stmt = $conn->prepare("
        SELECT ar.*, s.session_date, c.course_name, c.course_code 
        FROM attendance_records ar 
        JOIN attendance_sessions s ON ar.session_id = s.id 
        JOIN courses c ON s.course_id = c.id 
        WHERE ar.student_id = ? 
        ORDER BY s.session_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attendance stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(ar.id) as total_sessions,
            SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
            ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as overall_rate
        FROM attendance_records ar 
        JOIN attendance_sessions s ON ar.session_id = s.id 
        WHERE ar.student_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">🎓 Student Dashboard</h1>
        <p style="color: #666; font-size: 16px;">Welcome back, <strong><?php echo $_SESSION['full_name']; ?></strong>!</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-info">
                <h3><?php echo count($courses); ?></h3>
                <p>Enrolled Courses</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <h3><?php echo $stats['present_count'] ?? 0; ?></h3>
                <p>Present Days</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <h3><?php echo $stats['overall_rate'] ?? 0; ?>%</h3>
                <p>Overall Attendance</p>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <div class="card">
            <h3 style="color: #e91e63; margin-bottom: 20px;">📚 My Courses</h3>
            <?php if (empty($courses)): ?>
                <div style="text-align: center; padding: 30px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                    <h4>No Courses Enrolled</h4>
                    <p>You are not enrolled in any courses yet.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 15px;">
                    <?php foreach ($courses as $course): ?>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #e91e63;">
                        <h4 style="margin-bottom: 5px; color: #333;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p style="color: #666; margin-bottom: 8px;"><?php echo htmlspecialchars($course['course_code']); ?></p>
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <span style="background: #e91e63; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                Group: <?php echo htmlspecialchars($course['group_name']); ?>
                            </span>
                            <a href="courses.php?course_id=<?php echo $course['id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px;">
                                View Attendance
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #e91e63; margin-bottom: 20px;">📅 Recent Attendance</h3>
            <?php if (empty($recent_attendance)): ?>
                <div style="text-align: center; padding: 30px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📅</div>
                    <h4>No Attendance Records</h4>
                    <p>Your attendance hasn't been recorded yet.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 12px;">
                    <?php foreach ($recent_attendance as $record): ?>
                    <div style="display: flex; justify-content: between; align-items: center; padding: 12px; background: #f8f9fa; border-radius: 6px;">
                        <div>
                            <strong style="color: #333;"><?php echo htmlspecialchars($record['course_name']); ?></strong><br>
                            <small style="color: #666;"><?php echo $record['session_date']; ?></small>
                        </div>
                        <span style="background: 
                            <?php echo $record['status'] === 'present' ? '#4caf50' : 
                                   ($record['status'] === 'absent' ? '#f44336' : '#ff9800'); ?>; 
                            color: white; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            <?php echo strtoupper($record['status']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="courses.php" class="btn" style="padding: 8px 16px; font-size: 14px;">View All Attendance</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($stats['total_sessions'])): ?>
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📊 Attendance Overview</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; text-align: center;">
            <div style="padding: 20px; background: #e8f5e8; border-radius: 8px;">
                <div style="font-size: 32px; color: #4caf50;">✅</div>
                <strong style="font-size: 24px;"><?php echo $stats['present_count']; ?></strong><br>
                <small>Present</small>
            </div>
            <div style="padding: 20px; background: #ffe8e8; border-radius: 8px;">
                <div style="font-size: 32px; color: #f44336;">❌</div>
                <strong style="font-size: 24px;"><?php echo $stats['absent_count']; ?></strong><br>
                <small>Absent</small>
            </div>
            <div style="padding: 20px; background: #fff3cd; border-radius: 8px;">
                <div style="font-size: 32px; color: #ff9800;">⚠️</div>
                <strong style="font-size: 24px;"><?php echo $stats['late_count']; ?></strong><br>
                <small>Late</small>
            </div>
            <div style="padding: 20px; background: #e3f2fd; border-radius: 8px;">
                <div style="font-size: 32px; color: #2196f3;">📈</div>
                <strong style="font-size: 24px;"><?php echo $stats['overall_rate']; ?>%</strong><br>
                <small>Overall Rate</small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>