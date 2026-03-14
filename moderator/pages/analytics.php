<?php
if (!defined('in_nia_app')) exit;

$admin_title = 'Analytics Overview';
global $db;
$pre = $db->prefix();

// --- 1. General Totals ---
$counts = [
    'videos' => 0,
    'music' => 0,
    'images' => 0,
    'users' => 0,
    'channels' => 0,
    'playlists' => 0
];

try {
    $counts['videos'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos WHERE type = 'video'"));
    $counts['music'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos WHERE type = 'music'"));
    $counts['users'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}users"));
    $counts['images'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}images"));
    $counts['channels'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}channels"));
    $counts['playlists'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}playlists"));
} catch (Exception $e) {}

// --- 2. Activity Trends (Last 7 Days) ---
$days = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date));
    $days[$date] = ['users' => 0, 'content' => 0];
}

try {
    // User registrations
    $user_reg = $db->fetchAll("SELECT DATE(created_at) as d, COUNT(*) as c FROM {$pre}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY d");
    foreach ($user_reg as $r) {
        if (isset($days[$r->d])) $days[$r->d]['users'] = (int)$r->c;
    }
    
    // Content uploads (videos + music)
    $cont_reg = $db->fetchAll("SELECT DATE(created_at) as d, COUNT(*) as c FROM {$pre}videos WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY d");
    foreach ($cont_reg as $r) {
        if (isset($days[$r->d])) $days[$r->d]['content'] = (int)$r->c;
    }
} catch (Exception $e) {}

$chart_users = [];
$chart_content = [];
foreach ($days as $d) {
    $chart_users[] = $d['users'];
    $chart_content[] = $d['content'];
}

// --- 3. Top Content ---
$top_videos = [];
try {
    $top_videos = $db->fetchAll("SELECT id, title, views, thumb FROM {$pre}videos ORDER BY views DESC LIMIT 5");
} catch (Exception $e) {}

// --- 4. Activity Distribution ---
$activity_dist = [];
try {
    $dist = $db->fetchAll("SELECT action, COUNT(*) as c FROM {$pre}activity GROUP BY action ORDER BY c DESC LIMIT 10");
    foreach ($dist as $d) {
        $activity_dist[] = ['label' => ucfirst($d->action), 'value' => (int)$d->c];
    }
} catch (Exception $e) {}

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row g-4 mb-4">
    <!-- Highlight Cards -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small opacity-75 fw-bold text-uppercase mb-1">Total Users</div>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($counts['users']); ?></h2>
                    </div>
                    <span class="material-icons opacity-50 fs-1">people</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small opacity-75 fw-bold text-uppercase mb-1">Total Media</div>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($counts['videos'] + $counts['music'] + $counts['images']); ?></h2>
                    </div>
                    <span class="material-icons opacity-50 fs-1">perm_media</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small opacity-75 fw-bold text-uppercase mb-1">Total Views</div>
                        <?php 
                        $total_views = 0;
                        try {
                            $total_views = admin_fetch_count($db->fetch("SELECT SUM(views) as c FROM {$pre}videos"));
                        } catch(Exception $e) {}
                        ?>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($total_views); ?></h2>
                    </div>
                    <span class="material-icons opacity-50 fs-1">visibility</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small opacity-75 fw-bold text-uppercase mb-1">Engagement</div>
                        <?php 
                        $total_act = 0;
                        try {
                            $total_act = admin_fetch_count($db->fetch("SELECT COUNT(*) as c FROM {$pre}activity"));
                        } catch(Exception $e) {}
                        ?>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($total_act); ?></h2>
                    </div>
                    <span class="material-icons opacity-50 fs-1">bolt</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Growth Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <span class="material-icons text-primary me-2">trending_up</span>
                <h6 class="mb-0 fw-bold">Platform Growth (7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="growthChart" height="280"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
             <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <span class="material-icons text-primary me-2">star</span>
                <h6 class="mb-0 fw-bold">Top Performing Media</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4">Media</th>
                                <th class="border-0 text-end pe-4">Views</th>
                                <th class="border-0 text-end pe-4">Popularity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_videos as $v): 
                                $pct = $total_views > 0 ? ($v->views / $total_views) * 200 : 0;
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php if ($v->thumb): ?>
                                            <img src="<?php echo _e($v->thumb); ?>" class="rounded me-3" style="width: 45px; height: 30px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 30px;">
                                                <span class="material-icons text-muted" style="font-size: 14px;">image</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="fw-bold small"><?php echo _e($v->title); ?></div>
                                    </div>
                                </td>
                                <td class="text-end pe-4"><span class="badge bg-light text-dark border"><?php echo number_format($v->views); ?></span></td>
                                <td class="text-end pe-4" style="width: 150px;">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo min(100, $pct); ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Side Analysis -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">pie_chart</span> Content Breakdown</h6>
            </div>
            <div class="card-body">
                <canvas id="contentPieChart" height="280"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">donut_large</span> Engagement Mix</h6>
            </div>
            <div class="card-body">
                 <ul class="list-group list-group-flush">
                    <?php foreach ($activity_dist as $act): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 py-2">
                            <span class="small fw-medium text-muted"><?php echo _e($act['label']); ?></span>
                            <span class="fw-bold"><?php echo number_format($act['value']); ?></span>
                        </li>
                    <?php endforeach; ?>
                 </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Growth Chart
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'New Content',
                    data: <?php echo json_encode($chart_content); ?>,
                    borderColor: '#3699ff',
                    backgroundColor: 'rgba(54, 153, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'New Users',
                    data: <?php echo json_encode($chart_users); ?>,
                    borderColor: '#1bc5bd',
                    backgroundColor: 'rgba(27, 197, 189, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
        }
    });

    // Content Pie Chart
    const pieCtx = document.getElementById('contentPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Videos', 'Music', 'Images'],
            datasets: [{
                data: [<?php echo $counts['videos']; ?>, <?php echo $counts['music']; ?>, <?php echo $counts['images']; ?>],
                backgroundColor: ['#3699ff', '#1bc5bd', '#f64e60'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            cutout: '70%'
        }
    });
});
</script>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
