<?php
$current_role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #fce4ec;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #e91e63, #ad1457);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(233, 30, 99, 0.3);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-welcome {
            font-size: 14px;
        }
        
        .btn-logout {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .nav-container {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 10px;
        }
        
        .nav a {
            padding: 15px 20px;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .nav a:hover,
        .nav a.active {
            color: #e91e63;
            border-bottom-color: #e91e63;
            background: #fce4ec;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            min-height: calc(100vh - 140px);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #e91e63;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #e91e63;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #ad1457;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.3);
        }
        
        .btn-success {
            background: #4caf50;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #666;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e8f5e8;
            color: #4caf50;
            border: 1px solid #4caf50;
        }
        
        .alert-error {
            background: #ffe8e8;
            color: #f44336;
            border: 1px solid #f44336;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 4px solid #e91e63;
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-info h3 {
            font-size: 32px;
            color: #e91e63;
            margin-bottom: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #fce4ec;
            color: #e91e63;
            font-weight: 600;
        }
        
        tr:hover {
            background: #fafafa;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #f0f0f0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #e91e63;
            outline: none;
            box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 15px 20px;
                gap: 10px;
            }
            
            .nav {
                flex-wrap: wrap;
            }
            
            .nav a {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🎓 Attendance Pro</h1>
            </div>
            <div class="user-info">
                <div class="user-welcome">
                    Welcome, <strong><?php echo $_SESSION['full_name']; ?></strong> 
                    (<?php echo ucfirst($_SESSION['role']); ?>)
                </div>
                <a href="../auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>
    
    <nav class="nav-container">
        <div class="nav">
            <?php if ($current_role === 'professor'): ?>
                <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">🏠 Dashboard</a>
                <a href="attendance.php" class="<?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>">📝 Take Attendance</a>
                <a href="session.php" class="<?php echo $current_page === 'session.php' ? 'active' : ''; ?>">📊 Sessions</a>
                <a href="summary.php" class="<?php echo $current_page === 'summary.php' ? 'active' : ''; ?>">📈 Summary</a>
            <?php elseif ($current_role === 'student'): ?>
                <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">🏠 Dashboard</a>
                <a href="courses.php" class="<?php echo $current_page === 'courses.php' ? 'active' : ''; ?>">📚 My Courses</a>
                <a href="justifications.php" class="<?php echo $current_page === 'justifications.php' ? 'active' : ''; ?>">📄 Justifications</a>
            <?php elseif ($current_role === 'admin'): ?>
                <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">🏠 Dashboard</a>
                <a href="students.php" class="<?php echo $current_page === 'students.php' ? 'active' : ''; ?>">👥 Students</a>
                <a href="statistics.php" class="<?php echo $current_page === 'statistics.php' ? 'active' : ''; ?>">📊 Statistics</a>
                <a href="import_export.php" class="<?php echo $current_page === 'import_export.php' ? 'active' : ''; ?>">📁 Import/Export</a>
            <?php endif; ?>
        </div>
    </nav>