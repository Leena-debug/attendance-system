<?php
require_once '../auth/check_auth.php';
checkAuth('admin');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$stats = [];
$attendance_trends = [];
$course_stats = [];

if ($conn) {
    // Get comprehensive statistics
    $stats_queries = [
        'total_users' => "SELECT COUNT(*) as count FROM users",
        'active_sessions_today' => "SELECT COUNT(*) as count FROM attendance_sessions WHERE session_date = CURDATE()",
        'attendance_rate' => "SELECT ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as rate FROM attendance_records",
        'justification_approval_rate' => "SELECT ROUND((SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as rate FROM justifications WHERE status != 'pending'",
        'avg_participation' => "SELECT ROUND(AVG(CASE participation WHEN 'excellent' THEN 4 WHEN 'good' THEN 3 WHEN 'average' THEN 2 WHEN 'poor' THEN 1 END), 2) as avg FROM attendance_records"
    ];

    foreach ($stats_queries as $key => $query) {
        $stmt = $conn->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['rate'] ?? $result['count'] ?? $result['avg'] ?? 0;
    }

    // Get attendance trends (last 7 days)
    $stmt = $conn->prepare("
        SELECT 
            session_date,
            COUNT(*) as total_sessions,
            SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
            ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as attendance_rate
        FROM attendance_sessions s
        JOIN attendance_records ar ON s.id = ar.session_id
        WHERE s.session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY s.session_date
        ORDER BY s.session_date DESC
        LIMIT 7
    ");
    $stmt->execute();
    $attendance_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get course-wise statistics
    $stmt = $conn->prepare("
        SELECT 
            c.course_name,
            c.course_code,
            COUNT(DISTINCT s.id) as session_count,
            COUNT(ar.id) as total_records,
            SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
            ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as attendance_rate,
            COUNT(DISTINCT e.student_id) as student_count
        FROM courses c
        LEFT JOIN attendance_sessions s ON c.id = s.course_id
        LEFT JOIN attendance_records ar ON s.id = ar.session_id
        LEFT JOIN enrollments e ON c.id = e.course_id
        GROUP BY c.id, c.course_name, c.course_code
        ORDER BY attendance_rate DESC
    ");
    $stmt->execute();
    $course_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get professor performance
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            COUNT(DISTINCT s.id) as sessions_conducted,
            COUNT(DISTINCT c.id) as courses_taught,
            COUNT(ar.id) as records_managed
        FROM users u
        LEFT JOIN courses c ON u.id = c.professor_id
        LEFT JOIN attendance_sessions s ON u.id = s.opened_by
        LEFT JOIN attendance_records ar ON s.id = ar.session_id
        WHERE u.role = 'professor'
        GROUP BY u.id, u.full_name
        ORDER BY sessions_conducted DESC
    ");
    $stmt->execute();
    $professor_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">📊 System Statistics</h1>
        <p style="color: #666;">Comprehensive analytics and performance metrics</p>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_users']; ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <h3><?php echo $stats['attendance_rate']; ?>%</h3>
                <p>Overall Attendance</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <h3><?php echo $stats['justification_approval_rate']; ?>%</h3>
                <p>Justification Approval</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <h3><?php echo $stats['avg_participation']; ?></h3>
                <p>Avg Participation</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <h3><?php echo $stats['active_sessions_today']; ?></h3>
                <p>Today's Sessions</p>
            </div>
        </div>
    </div>

    <!-- Attendance Trends -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📈 Attendance Trends (Last 7 Days)</h3>
        
        <?php if (empty($attendance_trends)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📊</div>
                <h4>No Data Available</h4>
                <p>No attendance records found for the last 7 days.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Sessions</th>
                            <th>Present Records</th>
                            <th>Attendance Rate</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_trends as $trend): ?>
                        <tr>
                            <td><strong><?php echo $trend['session_date']; ?></strong></td>
                            <td><?php echo $trend['total_sessions']; ?></td>
                            <td style="color: #4caf50; font-weight: bold;"><?php echo $trend['present_count']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #f0f0f0; border-radius: 10px; height: 8px;">
                                        <div style="background: 
                                            <?php echo $trend['attendance_rate'] >= 80 ? '#4caf50' : 
                                                   ($trend['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>; 
                                            width: <?php echo min($trend['attendance_rate'], 100); ?>%; 
                                            height: 100%; border-radius: 10px;">
                                        </div>
                                    </div>
                                    <span style="font-weight: bold; min-width: 50px; text-align: right;">
                                        <?php echo $trend['attendance_rate']; ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $rate = $trend['attendance_rate'];
                                if ($rate >= 80) echo '🟢 Excellent';
                                elseif ($rate >= 60) echo '🟡 Good';
                                else echo '🔴 Needs Attention';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Course Performance -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📚 Course Performance</h3>
        
        <?php if (empty($course_stats)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📚</div>
                <h4>No Course Data</h4>
                <p>No courses or attendance data available.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Students</th>
                            <th>Sessions</th>
                            <th>Attendance Rate</th>
                            <th>Performance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($course_stats as $course): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($course['course_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo $course['course_code']; ?></small>
                            </td>
                            <td><?php echo $course['student_count']; ?></td>
                            <td><?php echo $course['session_count']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #f0f0f0; border-radius: 10px; height: 8px;">
                                        <div style="background: 
                                            <?php echo $course['attendance_rate'] >= 80 ? '#4caf50' : 
                                                   ($course['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>; 
                                            width: <?php echo min($course['attendance_rate'], 100); ?>%; 
                                            height: 100%; border-radius: 10px;">
                                        </div>
                                    </div>
                                    <span style="font-weight: bold; color: 
                                        <?php echo $course['attendance_rate'] >= 80 ? '#4caf50' : 
                                               ($course['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>">
                                        <?php echo $course['attendance_rate']; ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $rate = $course['attendance_rate'];
                                if ($rate >= 85) echo '⭐ Outstanding';
                                elseif ($rate >= 75) echo '👍 Good';
                                elseif ($rate >= 60) echo '⚠️ Average';
                                else echo '🔴 Poor';
                                ?>
                            </td>
                            <td>
                                <span style="background: 
                                    <?php echo $course['attendance_rate'] >= 75 ? '#4caf50' : 
                                           ($course['attendance_rate'] >= 60 ? '#ff9800' : '#f44336'); ?>; 
                                    color: white; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                    <?php echo $course['attendance_rate'] >= 75 ? 'HEALTHY' : 
                                           ($course['attendance_rate'] >= 60 ? 'WARNING' : 'CRITICAL'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Professor Performance -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">👨‍🏫 Professor Performance</h3>
        
        <?php if (empty($professor_stats)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">👨‍🏫</div>
                <h4>No Professor Data</h4>
                <p>No professors or session data available.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Professor</th>
                            <th>Courses</th>
                            <th>Sessions</th>
                            <th>Records Managed</th>
                            <th>Activity Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($professor_stats as $prof): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($prof['full_name']); ?></strong></td>
                            <td><?php echo $prof['courses_taught']; ?></td>
                            <td><?php echo $prof['sessions_conducted']; ?></td>
                            <td><?php echo $prof['records_managed']; ?></td>
                            <td>
                                <?php
                                $sessions = $prof['sessions_conducted'];
                                if ($sessions >= 20) echo '🔥 Very Active';
                                elseif ($sessions >= 10) echo '👍 Active';
                                elseif ($sessions >= 5) echo '⚡ Moderate';
                                else echo 'Good';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Export Options -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📁 Export Reports</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <button onclick="exportReport('attendance')" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 32px; margin-bottom: 10px;">📊</div>
                <strong>Attendance Report</strong>
            </button>
            <button onclick="exportReport('courses')" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 32px; margin-bottom: 10px;">📚</div>
                <strong>Course Performance</strong>
            </button>
            <button onclick="exportReport('professors')" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 32px; margin-bottom: 10px;">👨‍🏫</div>
                <strong>Professor Stats</strong>
            </button>
            <a href="import_export.php" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                <div style="font-size: 32px; margin-bottom: 10px;">📁</div>
                <strong>Advanced Export</strong>
            </a>
        </div>
    </div>
</div>

<script>
function exportReport(type) {
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Generating...';
    btn.disabled = true;

    // Simulate report generation
    setTimeout(() => {
        alert(`📄 ${type.charAt(0).toUpperCase() + type.slice(1)} report generated successfully!\n\nThis would download a CSV file with all ${type} data.`);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 1500);
}

// Add chart functionality with Chart.js
$(document).ready(function() {
    // This would integrate with Chart.js for visual charts
    console.log('Chart functionality ready - integrate with Chart.js for visualizations');
});
</script>

<?php include '../includes/footer.php'; ?>