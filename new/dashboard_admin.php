<?php
require_once 'includes/header.php';
requireRole('admin');

// Fetch statistics
$stats = [];

// Total Halaqaat
$result = $conn->query("SELECT COUNT(*) as total FROM halaqaat WHERE status = 'active'");
$stats['total_halaqaat'] = $result->fetch_assoc()['total'];

// Total Students
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$stats['total_students'] = $result->fetch_assoc()['total'];

// Total Mumayyizeen
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE is_mumayyiz = 1 AND status = 'active'");
$stats['total_mumayyizeen'] = $result->fetch_assoc()['total'];

// Students by Shuba
$shubaData = [];
$result = $conn->query("SELECT shuba, COUNT(*) as count FROM students WHERE status = 'active' GROUP BY shuba ORDER BY shuba");
while ($row = $result->fetch_assoc()) {
    $shubaData[$row['shuba']] = $row['count'];
}

// Students by Gender
$genderData = [];
$result = $conn->query("SELECT gender, COUNT(*) as count FROM students WHERE status = 'active' GROUP BY gender");
while ($row = $result->fetch_assoc()) {
    $genderData[$row['gender']] = $row['count'];
}

// Highest % Halaqa (Girls - Banaat)
$highestGirls = null;
$result = $conn->query("
    SELECT h.id, h.name, u.name as teacher_name,
           AVG(COALESCE(er.percentage, 0)) as avg_percentage,
           COUNT(DISTINCT s.id) as student_count
    FROM halaqaat h
    LEFT JOIN users u ON h.teacher_id = u.id
    LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
    LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
    WHERE h.gender = 'banaat' AND h.status = 'active'
    GROUP BY h.id
    HAVING avg_percentage > 0
    ORDER BY avg_percentage DESC
    LIMIT 1
");
$highestGirls = $result->fetch_assoc();

// Highest % Halaqa (Boys - Subah)
$highestBoysSubah = null;
$result = $conn->query("
    SELECT h.id, h.name, u.name as teacher_name,
           AVG(COALESCE(er.percentage, 0)) as avg_percentage,
           COUNT(DISTINCT s.id) as student_count
    FROM halaqaat h
    LEFT JOIN users u ON h.teacher_id = u.id
    LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
    LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
    WHERE h.gender = 'baneen' AND h.time_slot = 'subah' AND h.status = 'active'
    GROUP BY h.id
    HAVING avg_percentage > 0
    ORDER BY avg_percentage DESC
    LIMIT 1
");
$highestBoysSubah = $result->fetch_assoc();

// Highest % Halaqa (Boys - Asr)
$highestBoysAsr = null;
$result = $conn->query("
    SELECT h.id, h.name, u.name as teacher_name,
           AVG(COALESCE(er.percentage, 0)) as avg_percentage,
           COUNT(DISTINCT s.id) as student_count
    FROM halaqaat h
    LEFT JOIN users u ON h.teacher_id = u.id
    LEFT JOIN students s ON s.halaqa_id = h.id AND s.status = 'active'
    LEFT JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
    WHERE h.gender = 'baneen' AND h.time_slot = 'asr' AND h.status = 'active'
    GROUP BY h.id
    HAVING avg_percentage > 0
    ORDER BY avg_percentage DESC
    LIMIT 1
");
$highestBoysAsr = $result->fetch_assoc();

// Mumtaaz Talaba (Highest scorer across all halaqaat)
$mumtaazTalaba = null;
$result = $conn->query("
    SELECT s.id, s.name, s.roll_number, h.name as halaqa_name,
           AVG(er.percentage) as avg_percentage
    FROM students s
    JOIN halaqaat h ON s.halaqa_id = h.id
    JOIN exam_results er ON er.student_id = s.id AND er.status = 'finalized'
    WHERE s.status = 'active'
    GROUP BY s.id
    HAVING COUNT(er.id) >= 1
    ORDER BY avg_percentage DESC
    LIMIT 1
");
$mumtaazTalaba = $result->fetch_assoc();
?>

<div class="container py-4">
    <!-- Page Title -->
    <div class="mb-4">
        <h2 class="page-title"><?php echo t('dashboard'); ?></h2>
        <p class="greeting"><?php echo t('assalam_alaikum'); ?></p>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number" style="color: #28a745;"><?php echo $stats['total_halaqaat']; ?></div>
                <div class="stat-label"><?php echo t('total_halaqaat'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="stat-number" style="color: var(--secondary-color);"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label"><?php echo t('total_students'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_mumayyizeen']; ?></div>
                <div class="stat-label"><?php echo t('total_mumayyizeen'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Analytics Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h5 class="mb-3"><?php echo t('students_by_shuba'); ?></h5>
                <canvas id="shubaChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5 class="mb-3"><?php echo t('students_by_gender'); ?></h5>
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Highlights -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="highlight-card h-100">
                <div class="highlight-title"><?php echo t('highest_percentage'); ?> (<?php echo t('banaat'); ?>)</div>
                <?php if ($highestGirls): ?>
                <div class="highlight-value"><?php echo round($highestGirls['avg_percentage'], 1); ?>%</div>
                <small><?php echo htmlspecialchars($highestGirls['name']); ?></small><br>
                <small><i class="bi bi-person"></i> <?php echo htmlspecialchars($highestGirls['teacher_name']); ?></small>
                <?php else: ?>
                <div class="highlight-value">-</div>
                <small><?php echo t('no_data_found'); ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="highlight-card h-100">
                <div class="highlight-title"><?php echo t('highest_percentage'); ?> (<?php echo t('baneen'); ?> - <?php echo t('subah'); ?>)</div>
                <?php if ($highestBoysSubah): ?>
                <div class="highlight-value"><?php echo round($highestBoysSubah['avg_percentage'], 1); ?>%</div>
                <small><?php echo htmlspecialchars($highestBoysSubah['name']); ?></small><br>
                <small><i class="bi bi-person"></i> <?php echo htmlspecialchars($highestBoysSubah['teacher_name']); ?></small>
                <?php else: ?>
                <div class="highlight-value">-</div>
                <small><?php echo t('no_data_found'); ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="highlight-card h-100">
                <div class="highlight-title"><?php echo t('highest_percentage'); ?> (<?php echo t('baneen'); ?> - <?php echo t('asr'); ?>)</div>
                <?php if ($highestBoysAsr): ?>
                <div class="highlight-value"><?php echo round($highestBoysAsr['avg_percentage'], 1); ?>%</div>
                <small><?php echo htmlspecialchars($highestBoysAsr['name']); ?></small><br>
                <small><i class="bi bi-person"></i> <?php echo htmlspecialchars($highestBoysAsr['teacher_name']); ?></small>
                <?php else: ?>
                <div class="highlight-value">-</div>
                <small><?php echo t('no_data_found'); ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mumtaaz-card h-100">
                <div class="highlight-title"><i class="bi bi-trophy-fill me-2"></i><?php echo t('mumtaaz_talaba'); ?></div>
                <?php if ($mumtaazTalaba): ?>
                <div class="highlight-value"><?php echo round($mumtaazTalaba['avg_percentage'], 1); ?>%</div>
                <small><?php echo htmlspecialchars($mumtaazTalaba['name']); ?></small><br>
                <small><i class="bi bi-building"></i> <?php echo htmlspecialchars($mumtaazTalaba['halaqa_name']); ?></small>
                <?php else: ?>
                <div class="highlight-value">-</div>
                <small><?php echo t('no_data_found'); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill me-2"></i><?php echo $isRTL ? 'فوری عمل' : 'Quick Actions'; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="halaqaat_manage.php" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-plus-circle fs-4 d-block mb-2"></i>
                                <?php echo $isRTL ? 'نیا حلقہ' : 'New Halaqa'; ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="students_manage.php" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-person-plus fs-4 d-block mb-2"></i>
                                <?php echo $isRTL ? 'نیا طالب علم' : 'New Student'; ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="users_manage.php" class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-person-gear fs-4 d-block mb-2"></i>
                                <?php echo $isRTL ? 'نیا صارف' : 'New User'; ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="exams_manage.php" class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-file-earmark-plus fs-4 d-block mb-2"></i>
                                <?php echo $isRTL ? 'نیا امتحان' : 'New Exam'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Students by Shuba Chart
const shubaCtx = document.getElementById('shubaChart').getContext('2d');
new Chart(shubaCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($shubaData)); ?>,
        datasets: [{
            label: '<?php echo t('students'); ?>',
            data: <?php echo json_encode(array_values($shubaData)); ?>,
            backgroundColor: '#aa815e',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Students by Gender Chart
const genderCtx = document.getElementById('genderChart').getContext('2d');
new Chart(genderCtx, {
    type: 'doughnut',
    data: {
        labels: [
            '<?php echo t('baneen'); ?>',
            '<?php echo t('banaat'); ?>'
        ],
        datasets: [{
            data: [
                <?php echo $genderData['baneen'] ?? 0; ?>,
                <?php echo $genderData['banaat'] ?? 0; ?>
            ],
            backgroundColor: ['#0f2d3d', '#e91e63'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
