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
            --primary-gradient: linear-gradient(135deg, #00b894 0%, #778899 50%, #1a1a1a 100%);
            --charcoal: #1a1a1a;
            --green-teal: #00b894;
            --teal-gray: #778899;
            --card-shadow: 0 15px 35px rgba(0,0,0,0.3);
            --text-dark: #2c2c2c;
            --text-light: #666666;
            --white: #ffffff;
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
            background: var(--charcoal);
            color: var(--white);
            padding: 20px 40px;
            border-radius: 25px;
            font-size: 2.5em; 
            margin-bottom:40px; 
            box-shadow: var(--card-shadow);
            font-weight:700;
            position: relative;
            overflow: hidden;
        }
        h1::before {
            content: '';
            position: absolute;
            top: -2px; left: 0; right: 0;
            height: 6px;
            background: var(--green-teal);
        }
        .stats-bar {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            text-align: center;
        }
        .stat {
            background: rgba(255,255,255,0.95);
            padding: 25px 35px;
            border-radius: 20px;
            color: var(--charcoal);
            backdrop-filter: blur(15px);
            box-shadow: var(--card-shadow);
            min-width: 150px;
        }
        .stat-number { 
            font-size: 3em; 
            font-weight: 700; 
            color: var(--green-teal);
            display: block; 
        }
        .stat-label { font-size: 1.1em; color: var(--text-light); }
        .halqaat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 30px;
        }
        .halqa-card { 
            background: var(--white); 
            border-radius:25px; 
            padding:35px; 
            box-shadow: var(--card-shadow);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
        }
        .halqa-card::before {
            content: '';
            position: absolute;
            top:0; left:0; right:0;
            height:6px;
            background: var(--primary-gradient);
        }
        .halqa-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            border-color: var(--green-teal);
        }
        .halqa-name { 
            font-size:2em; 
            font-weight:700; 
            background: linear-gradient(135deg, var(--green-teal), var(--teal-gray));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom:20px;
        }
        .halqa-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top:25px;
        }
        .detail-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1em;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .detail-badge:hover { transform: scale(1.05); }
        .ustad-badge { 
            background: linear-gradient(135deg, #e8f8f5, #d1ecea); 
            color: var(--green-teal); 
            border: 2px solid var(--green-teal);
        }
        .students-badge { 
            background: linear-gradient(135deg, #e8f5e8, #d4edda); 
            color: #27ae60; 
            border: 2px solid #27ae60;
        }
        .gender-badge { 
            padding: 18px 30px; 
            font-weight: 700; 
            font-size: 1.2em;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        .gender-مختلط { 
            background: linear-gradient(135deg, #3498db, #2980b9); 
            color: white; 
        }
        .gender-بنات { 
            background: linear-gradient(135deg, #e74c3c, #c0392b); 
            color: white; 
        }
        .gender-أولاد { 
            background: linear-gradient(135deg, #27ae60, #229954); 
            color: white; 
        }
        .admin-link {
            display: block;
            margin: 50px auto 0;
            background: var(--charcoal);
            color: var(--white);
            padding: 25px 50px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.4em;
            box-shadow: var(--card-shadow);
            transition: all 0.4s;
            border: 3px solid var(--green-teal);
            max-width: 350px;
            text-align: center;
        }
        .admin-link:hover {
            background: var(--green-teal);
            border-color: var(--charcoal);
            transform: scale(1.08);
            box-shadow: 0 20px 40px rgba(0,184,148,0.4);
        }
        .loading { 
            text-align: center; 
            color: var(--white); 
            font-size: 1.4em; 
            padding: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        @media (max-width: 768px) {
            body { padding: 15px; }
            h1 { font-size: 2em; padding: 15px 25px; }
            .halqaat-grid { grid-template-columns: 1fr; gap: 25px; }
            .halqa-details { flex-direction: column; gap: 18px; }
            .stats-bar { flex-direction: column; gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏛️ حلقات الأكاديمية الإسلامية</h1>
        
        <div class="stats-bar" id="stats" style="display:none;">
            <div class="stat">
                <span class="stat-number" id="total-halqaat">-</span>
                <span class="stat-label">إجمالي الحلقات</span>
            </div>
            <div class="stat">
                <span class="stat-number" id="total-students">-</span>
                <span class="stat-label">إجمالي الطلاب</span>
            </div>
        </div>
        
        <div class="halqaat-grid" id="halqaat-list">
            <div class="loading">🔄 جاري تحميل حلقاتكم...</div>
        </div>
        
        <a href="admin.php" class="admin-link">⚙️ لوحة الإدارة المتقدمة</a>
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
                    totalStudents += parseInt(h.students || 0);
                    
                    const card = document.createElement('div');
                    card.className = 'halqa-card';
                    card.innerHTML = `
                        <div class="halqa-name">${h.name || 'غير محدد'}</div>
                        <div class="halqa-details">
                            <div class="detail-badge ustad-badge">
                                👨‍🏫 ${h.ustad || 'غير محدد'}
                            </div>
                            <div class="detail-badge students-badge">
                                👥 ${h.students || 0} طالب
                            </div>
                            <div class="detail-badge gender-badge gender-${h.gender || 'مختلط'}">
                                ${h.gender || 'مختلط'}
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
                console.error(e);
                document.getElementById('halqaat-list').innerHTML = 
                    '<div class="halqa-card" style="text-align:center;padding:60px;color:var(--charcoal);"><h2>⚠️ خطأ في الاتصال</h2><p>تحقق من <a href="admin.php" style="color:var(--green-teal);">لوحة الإدارة</a></p></div>';
            });
    </script>
</body>
</html>
