<?php
$pageTitle = 'Category Analysis Report';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$catData = $pdo->prepare("SELECT c.category_name, c.sla_hours, COUNT(t.ticket_id) as total,
    SUM(CASE WHEN t.status NOT IN ('Resolved','Closed') AND t.sla_due_datetime < NOW() THEN 1 ELSE 0 END) as sla_breached
    FROM categories c LEFT JOIN tickets t ON t.category_id = c.category_id AND t.created_datetime BETWEEN ? AND ?
    GROUP BY c.category_id ORDER BY total DESC");
$catData->execute([$dateFrom, $dateTo . ' 23:59:59']); $catData = $catData->fetchAll();
$totalAll = array_sum(array_column($catData, 'total'));
?>
<div class="page-header"><h1><i class="fas fa-th-large me-2"></i>Category Analysis Report</h1></div>
<div class="card mb-3"><div class="card-body py-3"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fs-xs">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>"></div>
<div class="col-md-3"><label class="form-label fs-xs">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>"></div>
<div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
</form></div></div>

<div class="row g-3 mb-4">
<div class="col-lg-6"><div class="card"><div class="card-header">Tickets by Category</div><div class="card-body"><div class="chart-container"><canvas id="catChart"></canvas></div></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Category Breakdown</div><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Category</th><th>Tickets</th><th>Share</th><th>SLA Breaches</th><th>SLA Target</th></tr></thead><tbody>
<?php foreach($catData as $c): $pct = $totalAll > 0 ? round($c['total']/$totalAll*100, 1) : 0; ?>
<tr><td class="fw-600"><?= e($c['category_name']) ?></td><td><?= $c['total'] ?></td>
<td><div class="progress" style="height:18px;"><div class="progress-bar" style="width:<?= $pct ?>%"><?= $pct ?>%</div></div></td>
<td class="<?= $c['sla_breached'] > 0 ? 'text-danger fw-600' : '' ?>"><?= $c['sla_breached'] ?></td>
<td><?= $c['sla_hours'] ?>h</td></tr>
<?php endforeach; ?></tbody></table></div></div></div></div>
</div>

<div class="card mb-4"><div class="card-header"><span><i class="fas fa-robot me-2"></i>AI Insights <span class="badge badge-ai">AI</span></span>
<button class="btn btn-sm btn-ai" onclick="getInsights(this)"><i class="fas fa-sync-alt me-1"></i>Generate</button></div>
<div class="card-body" id="insightsArea"><div class="text-muted text-center py-3">Click "Generate" for AI-powered insights.</div></div></div>

<script>
new Chart(document.getElementById('catChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_column($catData, 'category_name')) ?>, datasets: [{ data: <?= json_encode(array_map('intval', array_column($catData, 'total'))) ?>, backgroundColor: ['#2563eb','#059669','#d97706','#dc2626','#0891b2','#7c3aed','#ea580c','#64748b'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });

function getInsights(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/report_insights.php', { report_type: 'category_analysis', data: <?= json_encode($catData) ?> }, function(data) {
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
