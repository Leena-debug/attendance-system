<?php
require_once '../auth/check_auth.php';
checkAuth('admin');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$stats = [];

if ($conn) {
    // Get system statistics
    $queries = [
        'total_students' => "SELECT COUNT(*) as count FROM users WHERE role = 'student'",
        'total_professors' => "SELECT COUNT(*) as count FROM users WHERE role = 'professor'",
        'total_courses' => "SELECT COUNT(*) as count FROM courses",
        'total_sessions' => "SELECT COUNT(*) as count FROM attendance_sessions",
        'pending_justifications' => "SELECT COUNT(*) as count FROM justifications WHERE status = 'pending'",
        'today_sessions' => "SELECT COUNT(*) as count FROM attendance_sessions WHERE session_date = CURDATE()"
    ];

    foreach ($queries as $key => $query) {
        $stmt = $conn->query($query);
        $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    // Get recent activity
    $stmt = $conn->prepare("
        (SELECT 'attendance' as type, s.session_date as date, c.course_name, u.full_name as user_name
         FROM attendance_sessions s 
         JOIN courses c ON s.course_id = c.id 
         JOIN users u ON s.opened_by = u.id 
         ORDER BY s.created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'justification' as type, j.submitted_at as date, c.course_name, u.full_name as user_name
         FROM justifications j 
         JOIN attendance_sessions s ON j.session_id = s.id 
         JOIN courses c ON s.course_id = c.id 
         JOIN users u ON j.student_id = u.id 
         ORDER BY j.submitted_at DESC LIMIT 5)
        ORDER BY date DESC LIMIT 10
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">👨‍💼 Admin Dashboard</h1>
        <p style="color: #666; font-size: 16px;">Welcome back, <strong><?php echo $_SESSION['full_name']; ?></strong>!</p>
    </div>
    
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_students']; ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_professors']; ?></h3>
                <p>Professors</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_courses']; ?></h3>
                <p>Courses</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_sessions']; ?></h3>
                <p>Total Sessions</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-info">
                <h3><?php echo $stats['pending_justifications']; ?></h3>
                <p>Pending Justifications</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🕐</div>
            <div class="stat-info">
                <h3><?php echo $stats['today_sessions']; ?></h3>
                <p>Today's Sessions</p>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
        <!-- Quick Actions -->
        <div class="card">
            <h3 style="color: #e91e63; margin-bottom: 20px;">⚡ Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="students.php" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                    <div style="font-size: 32px; margin-bottom: 10px;">👥</div>
                    <strong>Manage Students</strong>
                </a>
                <a href="statistics.php" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📊</div>
                    <strong>View Statistics</strong>
                </a>
                <a href="import_export.php" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📁</div>
                    <strong>Import/Export</strong>
                </a>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card">
            <h3 style="color: #e91e63; margin-bottom: 20px;">🟢 System Status</h3>
            <div style="display: grid; gap: 15px;">
                <div style="display: flex; justify-content: between; align-items: center; padding: 12px; background: #e8f5e8; border-radius: 6px;">
                    <span>Database</span>
                    <span style="background: #4caf50; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                        Connected
                    </span>
                </div>
                <div style="display: flex; justify-content: between; align-items: center; padding: 12px; background: #e8f5e8; border-radius: 6px;">
                    <span>Uploads Directory</span>
                    <span style="background: #4caf50; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                        Writable
                    </span>
                </div>
                <div style="display: flex; justify-content: between; align-items: center; padding: 12px; background: #e8f5e8; border-radius: 6px;">
                    <span>Sessions</span>
                    <span style="background: #4caf50; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                        Active
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📋 Recent Activity</h3>
        
        <?php if (empty($recent_activity)): ?>
            <div style="text-align: center; padding: 30px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                <h4>No Recent Activity</h4>
                <p>There hasn't been any system activity recently.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 12px;">
                <?php foreach ($recent_activity as $activity): ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="font-size: 20px;">
                            <?php echo $activity['type'] === 'attendance' ? '📝' : '📄'; ?>
                        </div>
                        <div>
                            <strong style="color: #333;">
                                <?php echo htmlspecialchars($activity['user_name']); ?>
                            </strong>
                            <br>
                            <small style="color: #666;">
                                <?php echo $activity['type'] === 'attendance' ? 'Created attendance session' : 'Submitted justification'; ?>
                                for <?php echo htmlspecialchars($activity['course_name']); ?>
                            </small>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <small style="color: #666;">
                            <?php echo date('M j, g:i A', strtotime($activity['date'])); ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>