<?php
require_once '../auth/check_auth.php';
checkAuth('student');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$justifications = [];
$session_details = null;
$success_message = '';

if ($conn) {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $session_id = $_POST['session_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (!empty($session_id) && !empty($reason)) {
            try {
                // Handle file upload
                $file_path = null;
                if (isset($_FILES['justification_file']) && $_FILES['justification_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../assets/uploads/justifications/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_name = time() . '_' . basename($_FILES['justification_file']['name']);
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['justification_file']['tmp_name'], $target_path)) {
                        $file_path = 'justifications/' . $file_name;
                    }
                }
                
                // Insert justification
                $stmt = $conn->prepare("
                    INSERT INTO justifications (student_id, session_id, reason, file_path, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$_SESSION['user_id'], $session_id, $reason, $file_path]);
                
                $success_message = "✅ Justification submitted successfully! It is now pending review.";
                
            } catch(PDOException $e) {
                $error = "❌ Error submitting justification: " . $e->getMessage();
            }
        } else {
            $error = "❌ Please fill in all required fields.";
        }
    }
    
    // Get session details if provided
    $session_id = $_GET['session_id'] ?? '';
    if ($session_id) {
        $stmt = $conn->prepare("
            SELECT s.*, c.course_name, c.course_code 
            FROM attendance_sessions s 
            JOIN courses c ON s.course_id = c.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$session_id]);
        $session_details = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get student's justifications
    $stmt = $conn->prepare("
        SELECT j.*, s.session_date, c.course_name, c.course_code 
        FROM justifications j 
        JOIN attendance_sessions s ON j.session_id = s.id 
        JOIN courses c ON s.course_id = c.id 
        WHERE j.student_id = ? 
        ORDER BY j.submitted_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $justifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">📄 Absence Justifications</h1>
        <p style="color: #666;">Submit and track your absence justifications</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Justification Submission Form -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📝 Submit New Justification</h3>
        
        <?php if ($session_details): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin-bottom: 10px; color: #333;">Session Details</h4>
            <p style="margin: 5px 0;"><strong>Course:</strong> <?php echo htmlspecialchars($session_details['course_name']); ?></p>
            <p style="margin: 5px 0;"><strong>Date:</strong> <?php echo $session_details['session_date']; ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?php if ($session_details): ?>
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            <?php else: ?>
                <div class="form-group">
                   
    <label for="session_id">Select Absence Session to Justify:</label>
                        <select name="session_id" id="session_id" required>
    <option value="">Select a session to justify</option>

     <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>"
                            <?php echo isset($_POST['student_group_id']) && $_POST['student_group_id'] == $group['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </option>
                        <?php endforeach; ?>

    <?php
        // Fetch absence sessions from the DB
        $stmt = $pdo->query("SELECT session_id, session_name FROM absence_sessions ORDER BY session_name");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
    ?>
        <option value="<?php echo $row['session_id']; ?>">
            <?php echo htmlspecialchars($row['session_name']); ?>
        </option>
    <?php endwhile; ?>
</select>

                        <?php
                        // Get student's absences that don't have pending justifications
                        $stmt = $conn->prepare("
                            SELECT s.id, s.session_date, c.course_name, c.course_code 
                            FROM attendance_sessions s 
                            JOIN courses c ON s.course_id = c.id 
                            JOIN attendance_records ar ON s.id = ar.session_id 
                            LEFT JOIN justifications j ON s.id = j.session_id AND j.student_id = ?
                            WHERE ar.student_id = ? 
                            AND ar.status IN ('absent', 'late')
                            AND (j.id IS NULL OR j.status != 'pending')
                            ORDER BY s.session_date DESC
                        ");
                        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                        $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($absences as $absence):
                        ?>
                        <option value="<?php echo $absence['id']; ?>">
                            <?php echo $absence['session_date']; ?> - <?php echo $absence['course_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="reason">Justification Reason:</label>
                <textarea name="reason" id="reason" rows="5" required 
                          placeholder="Please provide a detailed reason for your absence..."
                          style="width: 100%; padding: 12px; border: 2px solid #f0f0f0; border-radius: 6px; resize: vertical;"></textarea>
            </div>

            <div class="form-group">
                <label for="justification_file">Supporting Document (Optional):</label>
                <input type="file" name="justification_file" id="justification_file" 
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                       style="padding: 10px; border: 2px dashed #ddd; border-radius: 6px; width: 100%;">
                <small style="color: #666;">Accepted formats: PDF, Word documents, JPG, PNG (Max: 5MB)</small>
            </div>

            <button type="submit" class="btn btn-success">✅ Submit Justification</button>
        </form>
    </div>

    <!-- Justification History -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📋 Justification History</h3>

        <?php if (empty($justifications)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                <h4>No Justifications Submitted</h4>
                <p>You haven't submitted any absence justifications yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Session Date</th>
                            <th>Reason</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($justifications as $justification): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($justification['course_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo $justification['course_code']; ?></small>
                            </td>
                            <td><?php echo $justification['session_date']; ?></td>
                            <td>
                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($justification['reason']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($justification['file_path']): ?>
                                    <a href="../assets/uploads/<?php echo $justification['file_path']; ?>" 
                                       target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px;">
                                       📎 View File
                                    </a>
                                <?php else: ?>
                                    <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background: 
                                    <?php echo $justification['status'] === 'approved' ? '#4caf50' : 
                                           ($justification['status'] === 'rejected' ? '#f44336' : '#ff9800'); ?>; 
                                    color: white; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                    <?php echo strtoupper($justification['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($justification['submitted_at'])); ?></td>
                            <td>
                                <?php if ($justification['reviewed_at']): ?>
                                    <?php echo date('M j, Y', strtotime($justification['reviewed_at'])); ?>
                                <?php else: ?>
                                    <span style="color: #666;">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Justification Statistics -->
            <div style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="color: #e91e63; margin-bottom: 15px;">📊 Justification Stats</h4>
                <?php
                $pending_count = count(array_filter($justifications, function($j) { return $j['status'] === 'pending'; }));
                $approved_count = count(array_filter($justifications, function($j) { return $j['status'] === 'approved'; }));
                $rejected_count = count(array_filter($justifications, function($j) { return $j['status'] === 'rejected'; }));
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 8px;">
                        <div style="font-size: 24px; color: #ff9800;">⏳</div>
                        <strong><?php echo $pending_count; ?></strong><br>
                        <small>Pending</small>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px;">
                        <div style="font-size: 24px; color: #4caf50;">✅</div>
                        <strong><?php echo $approved_count; ?></strong><br>
                        <small>Approved</small>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #ffe8e8; border-radius: 8px;">
                        <div style="font-size: 24px; color: #f44336;">❌</div>
                        <strong><?php echo $rejected_count; ?></strong><br>
                        <small>Rejected</small>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                        <div style="font-size: 24px; color: #2196f3;">📊</div>
                        <strong><?php echo count($justifications); ?></strong><br>
                        <small>Total</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Character counter for reason textarea
    $('#reason').on('input', function() {
        var length = $(this).val().length;
        $('#charCount').text(length + ' characters');
    });

    // File size validation
    $('#justification_file').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileSize = file.size / 1024 / 1024; // MB
            if (fileSize > 5) {
                alert('File size must be less than 5MB');
                $(this).val('');
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>