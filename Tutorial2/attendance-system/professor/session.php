<?php
require_once '../auth/check_auth.php';
checkAuth('professor');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$session = null;
$attendance_records = [];

if (isset($_GET['id'])) {
    $session_id = $_GET['id'];
    
    if ($conn) {
        try {
            // Get session details
            $stmt = $conn->prepare("
                SELECT s.*, c.course_name, c.course_code, g.group_name, u.full_name as professor_name
                FROM attendance_sessions s 
                JOIN courses c ON s.course_id = c.id 
                JOIN groups g ON s.group_id = g.id 
                JOIN users u ON s.opened_by = u.id 
                WHERE s.id = ? AND s.opened_by = ?
            ");
            $stmt->execute([$session_id, $_SESSION['user_id']]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Get attendance records for this session
                $stmt = $conn->prepare("
                    SELECT ar.*, u.full_name, u.username 
                    FROM attendance_records ar 
                    JOIN users u ON ar.student_id = u.id 
                    WHERE ar.session_id = ? 
                    ORDER BY u.full_name
                ");
                $stmt->execute([$session_id]);
                $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

if (!$session) {
    header("Location: index.php");
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 20px;">
            <div style="flex: 1;">
                <h1 style="color: #e91e63; margin-bottom: 5px;">📊 Session Details</h1>
                <p style="color: #666; font-size: 16px;">
                    <?php echo htmlspecialchars($session['course_name']); ?> - <?php echo htmlspecialchars($session['group_name']); ?>
                </p>
            </div>
            <div style="text-align: right;">
                <span style="background: <?php echo $session['status'] === 'open' ? '#4caf50' : '#f44336'; ?>; 
                      color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
                    <?php echo strtoupper($session['status']); ?>
                </span>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; margin-bottom: 10px;">📅</div>
                <strong>Date</strong><br>
                <?php echo $session['session_date']; ?>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; margin-bottom: 10px;">👨‍🏫</div>
                <strong>Professor</strong><br>
                <?php echo htmlspecialchars($session['professor_name']); ?>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; margin-bottom: 10px;">👥</div>
                <strong>Students</strong><br>
                <?php echo count($attendance_records); ?> attended
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">🎯 Attendance Records</h3>
        
        <?php if (empty($attendance_records)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                <h3>No Attendance Records</h3>
                <p>No students have been marked for this session yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Participation</th>
                            <th>Notes</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($record['full_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($record['username']); ?></small>
                            </td>
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
                            <td><?php echo htmlspecialchars($record['notes'] ?: '-'); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($record['recorded_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="color: #e91e63; margin-bottom: 15px;">📈 Quick Stats</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <?php
                    $present_count = array_filter($attendance_records, function($r) { return $r['status'] === 'present'; });
                    $absent_count = array_filter($attendance_records, function($r) { return $r['status'] === 'absent'; });
                    $late_count = array_filter($attendance_records, function($r) { return $r['status'] === 'late'; });
                    ?>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; color: #4caf50;">✅</div>
                        <strong><?php echo count($present_count); ?></strong><br>
                        <small>Present</small>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; color: #f44336;">❌</div>
                        <strong><?php echo count($absent_count); ?></strong><br>
                        <small>Absent</small>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; color: #ff9800;">⚠️</div>
                        <strong><?php echo count($late_count); ?></strong><br>
                        <small>Late</small>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; color: #2196f3;">📊</div>
                        <strong><?php echo round(count($present_count) / count($attendance_records) * 100, 1); ?>%</strong><br>
                        <small>Attendance Rate</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>