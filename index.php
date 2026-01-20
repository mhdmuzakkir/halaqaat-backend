<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once 'config.php';  // MySQL live

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// GET /api/halqaat → JSON list
if ($path == '/api/halqaat' && $method == 'GET') {
    $stmt = $pdo->query("SELECT * FROM halqaat ORDER BY id");
    echo json_encode($stmt->fetchAll());
    exit;
}

// Fallback: Frontend HTML/JS
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>حلقات أكاديمية</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Tajawal', sans-serif; background:#f5f5f5; padding:20px; }
        h1 { text-align:center; color:#2c3e50; margin-bottom:30px; }
        .halqa { background:white; margin:15px 0; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        .name { font-size:1.2em; font-weight:700; color:#34495e; }
        .details { color:#7f8c8d; margin-top:10px; }
        .gender { padding:5px 10px; border-radius:20px; font-size:0.9em; }
        .مختلط { background:#3498db; color:white; }
        .بنات { background:#e74c3c; color:white; }
        .أولاد { background:#27ae60; color:white; }
        @media (max-width:600px) { body { padding:10px; } }
    </style>
</head>
<body>
    <h1>حلقات الأكاديمية</h1>
    <div id="halqaat-list"></div>
    <a href="admin.php" style="display:block; text-align:center; margin-top:30px; color:#3498db; text-decoration:none;">لوحة الإدارة</a>

    <script>
        fetch('/api/halqaat')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('halqaat-list');
                data.forEach(h => {
                    const div = document.createElement('div');
                    div.className = 'halqa';
                    div.innerHTML = `
                        <div class="name">${h.name}</div>
                        <div class="details">
                            أستاذ: ${h.ustad} | طلاب: ${h.students} | 
                            <span class="gender ${h.gender}">${h.gender}</span>
                        </div>
                    `;
                    list.appendChild(div);
                });
            })
            .catch(e => console.error('API error:', e));
    </script>
</body>
</html>
