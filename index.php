<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Fake data → MySQL later
$halqaat = [
    ['id'=>1, 'name'=>'حفظ الجزء الأول', 'ustad'=>'أحمد', 'students'=>14, 'gender'=>'مختلط'],
    ['id'=>2, 'name'=>'نظيرة بنات', 'ustad'=>'فاطمة', 'ustadah'=>true, 'students'=>12, 'gender'=>'بنات'],
    ['id'=>3, 'name'=>'قاعدة أولاد', 'ustad'=>'بلال', 'students'=>16, 'gender'=>'أولاد']
];

// Routes
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

switch($path) {
    case '/api/halqaat':
        if($method == 'GET') {
            echo json_encode($halqaat);
        }
        break;
    
    case '/api/grades':
        echo json_encode([
            ['hifz'=>60, 'tajweed'=>9, 'total'=>89, 'grade'=>'ممتاز']
        ]);
        break;
    
    default:
        // Frontend
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>أكاديمية الحلقات</title>
    <style>
        body{font-family:Tajawal,Arial; background:#f5f5f5; margin:20px;}
        .halqa{border:1px solid #ddd; margin:10px 0; padding:15px; background:white; border-radius:8px;}
        .ustad{color:#007cba; font-weight:bold;}
        h1{text-align:center; color:#2c3e50;}
    </style>
</head>
<body>
    <h1>📚 الحلقات الدراسية</h1>
    <div id="halqaat-list">جاري التحميل...</div>
    
    <h2 style="margin-top:40px;">الدرجات</h2>
    <div id="grades-list">جاري التحميل...</div>

    <script>
        // Load halqaat
        fetch('/api/halqaat').then(r=>r.json()).then(data=>{
            document.getElementById('halqaat-list').innerHTML = 
                data.map(h=>`
                    <div class="halqa">
                        <h3>${h.name}</h3>
                        <p><span class="ustad">${h.ustadah ? 'أستاذة ' + h.ustad : 'أستاذ ' + h.ustad}</span> | 
                        ${h.students} طالب/ة | ${h.gender}</p>
                    </div>
                `).join('');
        });

        // Load grades
        fetch('/api/grades').then(r=>r.json()).then(data=>{
            document.getElementById('grades-list').innerHTML = 
                data.map(g=>`
                    <div class="halqa">
                        <p>حفظ: ${g.hifz} | تجويد: ${g.tajweed} | 
                        الإجمالي: ${g.total} | ${g.grade}</p>
                    </div>
                `).join('');
        });
    </script>
</body>
</html>
<?php } ?>
