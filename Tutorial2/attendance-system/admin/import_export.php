<?php
require_once '../auth/check_auth.php';
checkAuth('admin');

require_once '../config/db_connect.php';

$conn = getDBConnection();
$success_message = '';
$error = '';

if ($conn) {
    // Handle file upload and import
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
        $file = $_FILES['import_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_type = pathinfo($file['name'], PATHINFO_EXTENSION);
            
            if (in_array($file_type, ['csv', 'xlsx', 'xls'])) {
                try {
                    // For CSV files
                    if ($file_type === 'csv') {
                        $handle = fopen($file['tmp_name'], 'r');
                        $import_type = $_POST['import_type'] ?? 'students';
                        
                        if ($handle !== FALSE) {
                            $conn->beginTransaction();
                            $row = 0;
                            
                            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                                $row++;
                                if ($row === 1) continue; // Skip header row
                                
                                switch ($import_type) {
                                    case 'students':
                                        if (count($data) >= 3) {
                                            $username = $data[0];
                                            $password = password_hash($data[1], PASSWORD_DEFAULT);
                                            $full_name = $data[2];
                                            $email = $data[3] ?? '';
                                            
                                            $stmt = $conn->prepare("
                                                INSERT INTO users (username, password, email, role, full_name) 
                                                VALUES (?, ?, ?, 'student', ?)
                                            ");
                                            $stmt->execute([$username, $password, $email, $full_name]);
                                        }
                                        break;
                                        
                                    case 'courses':
                                        if (count($data) >= 2) {
                                            $course_code = $data[0];
                                            $course_name = $data[1];
                                            $professor_id = $data[2] ?? null;
                                            
                                            $stmt = $conn->prepare("
                                                INSERT INTO courses (course_code, course_name, professor_id) 
                                                VALUES (?, ?, ?)
                                            ");
                                            $stmt->execute([$course_code, $course_name, $professor_id]);
                                        }
                                        break;
                                }
                            }
                            
                            fclose($handle);
                            $conn->commit();
                            $success_message = "✅ Successfully imported $import_type from CSV file!";
                        }
                    } else {
                        $error = "❌ Excel file import requires additional libraries. Please use CSV format.";
                    }
                    
                } catch(PDOException $e) {
                    $conn->rollBack();
                    $error = "❌ Import failed: " . $e->getMessage();
                }
            } else {
                $error = "❌ Please upload a CSV or Excel file.";
            }
        } else {
            $error = "❌ File upload error: " . $file['error'];
        }
    }
    
    // Handle export request
    if (isset($_GET['export'])) {
        $export_type = $_GET['export'];
        exportData($conn, $export_type);
        exit;
    }
}

function exportData($conn, $type) {
    switch ($type) {
        case 'students':
            $filename = "students_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Username', 'Full Name', 'Email', 'Courses Enrolled', 'Attendance Rate', 'Join Date']);
            
            $stmt = $conn->prepare("
                SELECT u.username, u.full_name, u.email, u.created_at,
                       COUNT(DISTINCT e.course_id) as course_count,
                       ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as attendance_rate
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.student_id
                LEFT JOIN attendance_records ar ON u.id = ar.student_id
                WHERE u.role = 'student'
                GROUP BY u.id
            ");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['username'],
                    $row['full_name'],
                    $row['email'],
                    $row['course_count'],
                    $row['attendance_rate'] . '%',
                    $row['created_at']
                ]);
            }
            fclose($output);
            break;
            
        case 'attendance':
            $filename = "attendance_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Course', 'Student', 'Status', 'Participation', 'Professor', 'Notes']);
            
            $stmt = $conn->prepare("
                SELECT s.session_date, c.course_name, u.full_name as student_name,
                       ar.status, ar.participation, p.full_name as professor_name, ar.notes
                FROM attendance_records ar
                JOIN attendance_sessions s ON ar.session_id = s.id
                JOIN courses c ON s.course_id = c.id
                JOIN users u ON ar.student_id = u.id
                JOIN users p ON s.opened_by = p.id
                ORDER BY s.session_date DESC
            ");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['session_date'],
                    $row['course_name'],
                    $row['student_name'],
                    $row['status'],
                    $row['participation'],
                    $row['professor_name'],
                    $row['notes']
                ]);
            }
            fclose($output);
            break;
            
        case 'courses':
            $filename = "courses_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Course Code', 'Course Name', 'Professor', 'Students Enrolled', 'Sessions', 'Attendance Rate']);
            
            $stmt = $conn->prepare("
                SELECT c.course_code, c.course_name, p.full_name as professor_name,
                       COUNT(DISTINCT e.student_id) as student_count,
                       COUNT(DISTINCT s.id) as session_count,
                       ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as attendance_rate
                FROM courses c
                LEFT JOIN users p ON c.professor_id = p.id
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN attendance_sessions s ON c.id = s.course_id
                LEFT JOIN attendance_records ar ON s.id = ar.session_id
                GROUP BY c.id
            ");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['course_code'],
                    $row['course_name'],
                    $row['professor_name'] ?: 'Not Assigned',
                    $row['student_count'],
                    $row['session_count'],
                    $row['attendance_rate'] . '%'
                ]);
            }
            fclose($output);
            break;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <h1 style="color: #e91e63; margin-bottom: 10px;">📁 Import & Export</h1>
        <p style="color: #666;">Bulk import data and export reports in various formats</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <!-- Import Section -->
        <div class="card">
            <h3 style="color: #e91e63; margin-bottom: 20px;">📤 Import Data</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import_type">Import Type:</label>
                    <select name="import_type" id="import_type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="students">👥 Students</option>
                        <option value="courses">📚 Courses</option>
                        <option value="enrollments">🎓 Enrollments</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="import_file">Select File (CSV):</label>
                    <input type="file" name="import_file" id="import_file" accept=".csv,.xlsx,.xls" required 
                           style="width: 100%; padding: 10px; border: 2px dashed #ddd; border-radius: 5px;">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        📝 Supported formats: CSV, Excel (XLSX, XLS)
                    </small>
                </div>
                
                <!-- Format Guides -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <h4 style="color: #e91e63; margin-bottom: 10px;">📋 Expected Format:</h4>
                    <div id="formatGuide">
                        <strong>Students CSV:</strong> username,password,full_name,email<br>
                        <strong>Courses CSV:</strong> course_code,course_name,professor_id<br>
                        <strong>Enrollments CSV:</strong> student_id,course_id,group_id
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    📤 Upload & Import Data
                </button>
            </form>
            
            <!-- Download Templates -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                <h4 style="color: #e91e63; margin-bottom: 10px;">📥 Download Templates</h4>
                <div style="display: grid; gap: 10px;">
                    <a href="?export=template_students" class="btn" style="text-align: center; padding: 12px; text-decoration: none;">
                        👥 Students Template
                    </a>
                    <a href="?export=template_courses" class="btn" style="text-align: center; padding: 12px; text-decoration: none;">
                        📚 Courses Template
                    </a>
                    <a href="?export=template_enrollments" class="btn" style="text-align: center; padding: 12px; text-decoration: none;">
                        🎓 Enrollments Template
                    </a>
                </div>
            </div>
        </div>

        <!-- Export Section -->
        <div class="card">
            <h3 style="color: #e91e63; margin-bottom: 20px;">📥 Export Reports</h3>
            
            <div style="display: grid; gap: 15px;">
                <a href="?export=students" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                    <div style="font-size: 32px; margin-bottom: 10px;">👥</div>
                    <strong>Export Students</strong>
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Complete student list with statistics
                    </small>
                </a>
                
                <a href="?export=attendance" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📊</div>
                    <strong>Export Attendance</strong>
                    <small style="display: block; margin-top: 5px; color: #666;">
                        All attendance records with details
                    </small>
                </a>
                
                <a href="?export=courses" class="btn" style="text-align: center; padding: 20px; text-decoration: none;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📚</div>
                    <strong>Export Courses</strong>
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Course data with performance metrics
                    </small>
                </a>
            </div>
            
            <!-- Advanced Export Options -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                <h4 style="color: #e91e63; margin-bottom: 15px;">⚡ Advanced Export</h4>
                
                <form method="GET" id="advancedExport">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="export_type">Data Type:</label>
                            <select name="export" id="export_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="students">Students</option>
                                <option value="attendance">Attendance</option>
                                <option value="courses">Courses</option>
                                <option value="justifications">Justifications</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_from">From Date:</label>
                            <input type="date" name="date_from" id="date_from" 
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">To Date:</label>
                            <input type="date" name="date_to" id="date_to" 
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="format">Export Format:</label>
                        <select name="format" id="format" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">
                        🚀 Generate Custom Export
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Import/Export Statistics -->
    <div class="card">
        <h3 style="color: #e91e63; margin-bottom: 20px;">📈 Data Management Stats</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 32px; color: #e91e63;">👥</div>
                <strong style="font-size: 24px;">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </strong><br>
                <small>Total Students</small>
            </div>
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 32px; color: #e91e63;">📚</div>
                <strong style="font-size: 24px;">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM courses");
                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </strong><br>
                <small>Courses</small>
            </div>
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 32px; color: #e91e63;">📊</div>
                <strong style="font-size: 24px;">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM attendance_records");
                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </strong><br>
                <small>Attendance Records</small>
            </div>
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 32px; color: #e91e63;">📄</div>
                <strong style="font-size: 24px;">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM justifications");
                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </strong><br>
                <small>Justifications</small>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update format guide based on import type
    $('#import_type').change(function() {
        var type = $(this).val();
        var guide = $('#formatGuide');
        
        switch(type) {
            case 'students':
                guide.html('<strong>Students CSV:</strong> username,password,full_name,email<br>Example: john_doe,password123,John Doe,john@email.com');
                break;
            case 'courses':
                guide.html('<strong>Courses CSV:</strong> course_code,course_name,professor_id<br>Example: CS101,Computer Science,1');
                break;
            case 'enrollments':
                guide.html('<strong>Enrollments CSV:</strong> student_id,course_id,group_id<br>Example: 1,1,1');
                break;
        }
    });
    
    // Initialize with default guide
    $('#import_type').trigger('change');
    
    // File validation
    $('#import_file').change(function() {
        var file = this.files[0];
        if (file) {
            var fileSize = file.size / 1024 / 1024; // MB
            if (fileSize > 10) {
                alert('File size must be less than 10MB');
                $(this).val('');
            }
        }
    });
    
    // Advanced export form handling
    $('#advancedExport').on('submit', function(e) {
        var dateFrom = $('#date_from').val();
        var dateTo = $('#date_to').val();
        
        if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
            alert('"From Date" cannot be after "To Date"');
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>