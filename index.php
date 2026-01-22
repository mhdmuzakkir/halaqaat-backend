<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Halaqaat Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 24px; }
        .navbar a { color: white; text-decoration: none; margin-left: 20px; }
        
        .container {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar a {
            display: block;
            padding: 12px;
            margin: 10px 0;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: #667eea;
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-card h3 { color: #667eea; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🕌 Halaqaat Management System</h1>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
            <a href="?logout=1">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <a href="index.php" class="active">📊 Dashboard</a>
            <a href="students.php">👥 Students</a>
            <a href="halaqaat.php">📚 Halaqaat</a>
            <a href="exams.php">✏️ Exams</a>
            <a href="reports.php">📈 Reports</a>
        </div>
        
        <div class="main-content">
            <h2>Dashboard Overview</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="number">
                        <?php
                        $result = $conn->query("SELECT COUNT(*) as count FROM students");
                        echo $result->fetch_assoc()['count'];
                        ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Active Halaqaat</h3>
                    <div class="number">
                        <?php
                        $result = $conn->query("SELECT COUNT(*) as count FROM halaqaat");
                        echo $result->fetch_assoc()['count'];
                        ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Exams</h3>
                    <div class="number">
                        <?php
                        $result = $conn->query("SELECT COUNT(*) as count FROM exams");
                        echo $result->fetch_assoc()['count'];
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3>Quick Actions</h3>
                <p>Use the sidebar to manage students, halaqaat, exams, and view reports.</p>
            </div>
        </div>
    </div>

    <?php
    if (isset($_GET['logout'])) {
        logout();
    }
    ?>
</body>
</html>