<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once 'config.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// API: /api/halqaat → JSON
if ($path == '/api/halqaat' && $method == 'GET') {
    $stmt = $pdo->query("SELECT * FROM halqaat ORDER BY id");
    echo json_encode($stmt->fetchAll());
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>حلقات الأكاديمية الإسلامية</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.2);
            --text-dark: #444444;
            --text-light: #666666;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { 
            font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif; 
            background: var(--primary-gradient); 
            min-height:100vh; 
            padding:20px; 
            color: var(--text-dark);
        }
        .container { max-width:1200px; margin:0 auto; }
        h1 { 
            text-align:center; 
            color:white; 
            font-size:2.5em; 
            margin-bottom:40px; 
            text-shadow:0 4px 8px rgba(0,0,0,0.3);
            font-weight:700;
        }
        .halqaat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        .halqa-card { 
            background:white; 
            border-radius:20px; 
            padding:30px; 
            box-shadow: var(--card-shadow);
            transform: translateY(0);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .halqa-card::before {
            content: '';
            position: absolute;
            top:0; left:0; right:0;
            height:5px;
            background: var(--primary-gradient);
        }
        .halqa-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .halqa-name { 
            font-size:1.8em; 
            font-weight:700; 
            color: var(--text-dark);
            margin-bottom:15px;
        }
        .halqa-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top:20px;
        }
        .detail-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1em;
        }
        .ustad-badge { 
            background: #e8f4f8; 
            color: #2c3e50; 
            border: 2px solid #3498db;
        }
        .students-badge { 
            background: #d5f4e6; 
            color: #27ae60; 
            border: 2px solid #27ae60;
        }
        .gender-badge { 
            padding: 12px 24px; 
            font-weight: 700; 
            font-size: 1.1em;
        }
        .gender-مختلط { background: #3498db; color: white; }
        .gender-بنات { background: #e74c3c; color: white; }
        .gender-أولاد { background: #27ae60; color: white; }
        .admin-link {
            display: block;
            margin-top: 40px;
            text-align: center;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 20px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.3em;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .admin-link:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        .stats-bar {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            text-align: center;
        }
        .stat {
            background: rgba(255,255,255,0.2);
            padding: 20px 30px;
            border-radius: 15px;
            color: white;
            backdrop-filter: blur(10px);
        }
        .stat-number { font-size: 2.5em; font-weight: 700; display: block; }
        @media (max-width: 768px) {
            h1 { font-size: 2em; }
            .halqaat-grid { grid-template-columns: 1fr; gap: 20px; }
            .halqa-details { flex-direction: column; gap: 15px; }
        }
        .loading { text-align: center; color: white; font-size: 1.2em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏛️ حلقات الأكاديمية الإسلامية</h1>
        
        <div class="stats-bar" id="stats" style="display:none;">
            <div class="stat">
                <span class="stat-number" id="total-halqaat">-</span>
                إجمالي الحلقات
            </div>
            <div class="stat">
                <span class="stat-number" id="total-students">-</span>
                إجمالي الطلاب
            </div>
        </div>
        
        <div class="halqaat-grid" id="halqaat-list">
            <div class="loading">جاري تحميل الحلقات...</div>
        </div>
        
        <a href="admin.php" class="admin-link">⚙️ لوحة الإدارة</a>
    </div>

    <script>
        fetch('/api/halqaat')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('halqaat-list');
                const stats = document.getElementById('stats');
                list.innerHTML = '';
                
                let totalStudents = 0;
                data.forEach(h => {
                    totalStudents += parseInt(h.students);
                    
                    const card = document.createElement('div');
                    card.className = 'halqa-card';
                    card.innerHTML = `
                        <div class="halqa-name">${h.name}</div>
                        <div class="halqa-details">
                            <div class="detail-badge ustad-badge">
                                👨‍🏫 ${h.ustad || 'غير محدد'}
                            </div>
                            <div class="detail-badge students-badge">
                                👥 ${h.students} طالب
                            </div>
                            <div class="detail-badge gender-badge gender-${h.gender}">
                                ${h.gender}
                            </div>
                        </div>
                    `;
                    list.appendChild(card);
                });
                
                document.getElementById('total-halqaat').textContent = data.length;
                document.getElementById('total-students').textContent = totalStudents;
                stats.style.display = 'flex';
            })
            .catch(e => {
                document.getElementById('halqaat-list').innerHTML = 
                    '<div style="text-align:center;color:white;font-size:1.2em;">خطأ في تحميل البيانات - تحقق من /admin.php</div>';
            });
    </script>
</body>
</html>
