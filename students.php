<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$user = getCurrentUser();

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    
    $sql = "INSERT INTO students (name, email, phone, enrollment_date) VALUES ('$name', '$email', '$phone', NOW())";
    $conn->query($sql);
    header("Location: students.php");
    exit();
}

// Get all students
$result = $conn->query("SELECT * FROM students ORDER BY id DESC");
$students = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Halaqaat Management</title>
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
        }
        
        .sidebar a.active {
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #764ba2;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background: #f0f0f0;
            font-weight: 600;
        }
        
        table tr:hover {
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🕌 Halaqaat Management System</h1>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
            <a href="?logout=1" style="color: white; text-decoration: none; margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <a href="index.php">📊 Dashboard</a>
            <a href="students.php" class="active">👥 Students</a>
            <a href="halaqaat.php">📚 Halaqaat</a>
            <a href="exams.php">✏️ Exams</a>
            <a href="reports.php">📈 Reports</a>
        </div>
        
        <div class="main-content">
            <h2>Students Management</h2>
            
            <div class="card">
                <h3>Add New Student</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" required>
                    </div>
                    <button type="submit" name="add_student" class="btn">Add Student</button>
                </form>
            </div>
            
            <div class="card">
                <h3>All Students</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo $student['enrollment_date']; ?></td>
                            <td><?php echo ucfirst($student['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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