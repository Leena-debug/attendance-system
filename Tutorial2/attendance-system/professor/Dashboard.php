<?php
require_once '../auth/check_auth.php';
checkAuth('professor');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$sessions = [];
$courses = [];

if ($conn) {
    // Get professor's courses
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(DISTINCT e.student_id) as student_count
        FROM courses c 
        LEFT JOIN enrollments e ON c.id = e.course_id 
        WHERE c.professor_id = ? 
        GROUP BY c.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent sessions - FIXED: changed groups to student_groups
    $stmt = $conn->prepare("
        SELECT s.*, c.course_name, g.group_name 
        FROM attendance_sessions s 
        JOIN courses c ON s.course_id = c.id 
        JOIN student_groups g ON s.group_id = g.id 
        WHERE s.opened_by = ? 
        ORDER BY s.session_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">👨‍🏫 Professor Dashboard</h1>
        <p style="color: #666; font-size: 16px;">Welcome back, <strong><?php echo $_SESSION['full_name']; ?></strong>!</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-info">
                <h3><?php echo count($courses); ?></h3>
                <p>Courses</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo array_sum(array_column($courses, 'student_count')); ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <h3><?php echo count($sessions); ?></h3>
                <p>Recent Sessions</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📚 My Courses</h3>
        <?php if (empty($courses)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                <h3>No Courses Assigned</h3>
                <p>You haven't been assigned to any courses yet.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 15px;">
                <?php foreach ($courses as $course): ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #e91e63;">
                    <div style="flex: 1;">
                        <h4 style="color: #333; margin-bottom: 5px;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p style="color: #666; margin-bottom: 5px;"><?php echo htmlspecialchars($course['course_code']); ?></p>
                        <span style="background: #e91e63; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                            <?php echo $course['student_count']; ?> students
                        </span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="attendance.php?course_id=<?php echo $course['id']; ?>" class="btn">Take Attendance</a>
                        <a href="summary.php?course_id=<?php echo $course['id']; ?>" class="btn btn-secondary">View Summary</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📅 Recent Sessions</h3>
        <?php if (empty($sessions)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📅</div>
                <h3>No Sessions Created</h3>
                <p>You haven't created any attendance sessions yet.</p>
                <a href="attendance.php" class="btn" style="margin-top: 15px;">Create First Session</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Group</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($session['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($session['group_name']); ?></td>
                        <td><?php echo $session['session_date']; ?></td>
                        <td>
                            <span style="background: <?php echo $session['status'] === 'open' ? '#4caf50' : '#f44336'; ?>; 
                                  color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                <?php echo ucfirst($session['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="session.php?id=<?php echo $session['id']; ?>" class="btn" style="padding: 8px 12px; font-size: 12px;">View Details</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>