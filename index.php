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
            --primary-color: #b18f6e;
            --primary-hover: #a07c5e;
            --secondary-color: #444444;
            --accent-color: #3e846a;
            --light-bg: #f8f8f8;
            --white: #ffffff;
            --text-dark: #2c2c2c;
            --text-light: #666666;
            --shadow: 0 0 3px 0 rgba(0, 0, 0, 0.22);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif; 
            background: var(--light-bg); 
            min-height: 100vh; 
            color: var(--text-dark);
        }
        
        /* Header Styling */
        header {
            background: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-title {
            font-size: 2em;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .admin-nav a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .admin-nav a:hover {
            color: var(--primary-color);
            background: rgba(177, 143, 110, 0.1);
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 40px 20px; 
        }
        
        h1 { 
            text-align: center; 
            background: var(--secondary-color);
            color: var(--white);
            padding: 30px 40px;
            border-radius: 12px;
            font-size: 2.5em; 
            margin-bottom: 40px; 
            box-shadow: var(--shadow-lg);
            font-weight: 700;
            position: relative;
            overflow: hidden;
        }
        
        h1::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
            text-align: center;
        }
        
        .stat {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            color: var(--text-dark);
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s, box-shadow 0.3s;
            border-right: 4px solid var(--primary-color);
        }
        
        .stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(177, 143, 110, 0.2);
        }
        
        .stat-number { 
            font-size: 3em; 
            font-weight: 700; 
            color: var(--primary-color);
            display: block;
            line-height: 1;
        }
        
        .stat-label { 
            font-size: 1.1em; 
            color: var(--text-light);
            margin-top: 10px;
        }
        
        /* Halqaat Grid */
        .halqaat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }
        
        .halqa-card { 
            background: var(--white); 
            border-radius: 12px; 
            padding: 30px; 
            box-shadow: var(--shadow-lg);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            border-right: 4px solid var(--primary-color);
            overflow: hidden;
        }
        
        .halqa-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .halqa-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 45px rgba(177, 143, 110, 0.25);
            border-right-color: var(--accent-color);
        }
        
        .halqa-name { 
            font-size: 1.8em; 
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            word-break: break-word;
        }
        
        .halqa-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }
        
        .detail-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            white-space: nowrap;
            flex-wrap: wrap;
        }
        
        .detail-badge:hover { 
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .ustad-badge { 
            background: linear-gradient(135deg, rgba(177, 143, 110, 0.1), rgba(177, 143, 110, 0.05)); 
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .students-badge { 
            background: linear-gradient(135deg, rgba(62, 132, 106, 0.1), rgba(62, 132, 106, 0.05)); 
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        
        .gender-badge { 
            padding: 14px 22px; 
            font-weight: 700; 
            font-size: 0.9em;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            color: var(--white);
            border: none;
        }
        
        .gender-مختلط { 
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .gender-بنات { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .gender-أولاد { 
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        /* Admin Link */
        .admin-link {
            display: inline-flex;
            margin: 50px auto 0;
            background: var(--secondary-color);
            color: var(--white);
            padding: 18px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1em;
            box-shadow: var(--shadow-lg);
            transition: all 0.4s;
            border: 2px solid var(--primary-color);
            text-align: center;
            display: block;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
            margin-top: 40px;
        }
        
        .admin-link:hover {
            background: var(--primary-color);
            border-color: var(--secondary-color);
            transform: scale(1.05);
            box-shadow: 0 15px 35px rgba(177, 143, 110, 0.4);
            color: var(--white);
        }
        
        /* Loading State */
        .loading { 
            text-align: center; 
            color: var(--text-dark); 
            font-size: 1.3em; 
            padding: 60px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Error State */
        .error-state {
            text-align: center;
            padding: 60px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            color: #c0392b;
        }
        
        .error-state h2 {
            margin-bottom: 15px;
            color: #c0392b;
        }
        
        .error-state a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { 
                padding: 10px; 
            }
            
            h1 { 
                font-size: 1.8em; 
                padding: 20px 15px; 
            }
            
            .header-title {
                font-size: 1.4em;
            }
            
            .halqaat-grid { 
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .halqa-details { 
                flex-direction: column;
                gap: 10px;
            }
            
            .detail-badge {
                width: 100%;
            }
            
            .stats-bar { 
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat {
                border-right: none;
                border-top: 4px solid var(--primary-color);
            }
            
            .admin-link {
                width: 100%;
                max-width: 100%;
            }
        }
        
        /* Footer */
        footer {
            background: var(--secondary-color);
            color: var(--white);
            text-align: center;
            padding: 20px;
            margin-top: 50px;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <h2 class="header-title">🏛️ حلقات الأكاديمية الإسلامية</h2>
            <div class="admin-nav">
                <a href="admin.php">⚙️ الإدارة</a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Stats Bar -->
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
        
        <!-- Main Title -->
        <h1>📚 عرض الحلقات المتاحة</h1>
        
        <!-- Halqaat Grid -->
        <div class="halqaat-grid" id="halqaat-list">
            <div class="loading">🔄 جاري تحميل حلقاتكم...</div>
        </div>
        
        <!-- Admin Link -->
        <a href="admin.php" class="admin-link">⚙️ لوحة الإدارة المتقدمة</a>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 - جمعية مكنون لتحفيظ القرآن الكريم بالرياض | جميع الحقوق محفوظة</p>
    </footer>

    <script>
        fetch('/api/halqaat')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('halqaat-list');
                const stats = document.getElementById('stats');
                list.innerHTML = '';
                
                let totalStudents = 0;
                
                // Check if data is empty
                if (!data || data.length === 0) {
                    list.innerHTML = '<div class="error-state"><h2>⚠️ لا توجد حلقات متاحة</h2><p>يرجى التحقق لاحقاً</p></div>';
                    stats.style.display = 'none';
                    return;
                }
                
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
                            ${h.gender ? `<div class="detail-badge gender-badge gender-${h.gender}">
                                ${h.gender}
                            </div>` : ''}
                        </div>
                    `;
                    list.appendChild(card);
                });
                
                document.getElementById('total-halqaat').textContent = data.length;
                document.getElementById('total-students').textContent = totalStudents;
                stats.style.display = 'grid';
            })
            .catch(e => {
                console.error(e);
                document.getElementById('halqaat-list').innerHTML = 
                    '<div class="error-state"><h2>⚠️ خطأ في الاتصال</h2><p>تحقق من الاتصال بالخادم وحاول مرة أخرى</p><p><a href="admin.php">الذهاب إلى لوحة الإدارة</a></p></div>';
                document.getElementById('stats').style.display = 'none';
            });
    </script>
</body>
</html>