<?php
$pageTitle = 'Resolution Time Report';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Average resolution time
$avg = $pdo->prepare("SELECT AVG(resolution_time_minutes) as avg_min FROM resolutions r JOIN tickets t ON r.ticket_id = t.ticket_id WHERE t.created_datetime BETWEEN ? AND ?");
$avg->execute([$dateFrom, $dateTo . ' 23:59:59']); $avgMin = round($avg->fetch()['avg_min'] ?? 0);

// By staff
$byStaff = $pdo->prepare("SELECT s.full_name, COUNT(*) as resolved, AVG(r.resolution_time_minutes) as avg_min FROM resolutions r JOIN support_staff s ON r.staff_id = s.staff_id JOIN tickets t ON r.ticket_id = t.ticket_id WHERE t.created_datetime BETWEEN ? AND ? GROUP BY s.staff_id ORDER BY avg_min");
$byStaff->execute([$dateFrom, $dateTo . ' 23:59:59']); $byStaff = $byStaff->fetchAll();

// By category
$byCat = $pdo->prepare("SELECT c.category_name, AVG(r.resolution_time_minutes) as avg_min, COUNT(*) as cnt FROM resolutions r JOIN tickets t ON r.ticket_id = t.ticket_id LEFT JOIN categories c ON t.category_id = c.category_id WHERE t.created_datetime BETWEEN ? AND ? GROUP BY c.category_id ORDER BY avg_min DESC");
$byCat->execute([$dateFrom, $dateTo . ' 23:59:59']); $byCat = $byCat->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-clock me-2"></i>Resolution Time Report</h1></div>
<div class="card mb-3"><div class="card-body py-3"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fs-xs">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>"></div>
<div class="col-md-3"><label class="form-label fs-xs">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>"></div>
<div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
</form></div></div>

<div class="row g-3 mb-4">
<div class="col-md-4"><div class="stat-card stat-primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-value"><?= round($avgMin/60, 1) ?>h</div><div class="stat-label">Avg Resolution Time</div></div></div>
</div>

<div class="row g-3 mb-4">
<div class="col-lg-6"><div class="card"><div class="card-header">By Staff</div><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Staff</th><th>Resolved</th><th>Avg Time</th></tr></thead><tbody>
<?php foreach($byStaff as $s): ?>
<tr><td class="fw-600"><?= e($s['full_name']) ?></td><td><?= $s['resolved'] ?></td><td><?= round($s['avg_min']/60, 1) ?>h</td></tr>
<?php endforeach; ?></tbody></table></div></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">By Category</div><div class="card-body"><div class="chart-container"><canvas id="catChart"></canvas></div></div></div></div>
</div>

<div class="card mb-4"><div class="card-header"><span><i class="fas fa-robot me-2"></i>AI Insights <span class="badge badge-ai">AI</span></span>
<button class="btn btn-sm btn-ai" onclick="getInsights(this)"><i class="fas fa-sync-alt me-1"></i>Generate</button></div>
<div class="card-body" id="insightsArea"><div class="text-muted text-center py-3">Click "Generate" for AI-powered insights.</div></div></div>

<script>
new Chart(document.getElementById('catChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($byCat, 'category_name')) ?>, datasets: [{ label: 'Avg Hours', data: <?= json_encode(array_map(fn($c) => round($c['avg_min']/60, 1), $byCat)) ?>, backgroundColor: '#2563eb', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } } });

function getInsights(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/report_insights.php', { report_type: 'resolution_time', data: { avg_minutes: <?= $avgMin ?>, by_staff: <?= json_encode($byStaff) ?>, by_category: <?= json_encode($byCat) ?> } }, function(data) {
        const area = document.getElementById('insightsArea');
        if (data.success && data.data) {
            let html = '<div class="ai-suggestion-box">';
            ['key_observations','operational_risks','recommendations'].forEach(k => { if (data.data[k]) { html += '<h6 class="fw-600 mb-2">' + k.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase()) + '</h6><ul class="mb-2">' + data.data[k].map(o => '<li>' + o + '</li>').join('') + '</ul>'; } });
            html += '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div></div>';
            area.innerHTML = html;
        } else { area.innerHTML = '<div class="alert alert-warning">' + (data.error || 'Failed') + '</div>'; }
    });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
