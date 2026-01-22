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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <h1>🕌 Halaqaat Management System</h1>
        <div class="user-info">
            <span style="color: var(--color-cream-100);">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
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
                    <h3>Total Exams</h3>
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
                <p>Use the sidebar to manage students, halaqaat classes, exams, and view comprehensive reports.</p>
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