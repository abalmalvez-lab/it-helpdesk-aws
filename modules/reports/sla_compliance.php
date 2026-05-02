<?php
$pageTitle = 'SLA Compliance Report';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Resolved/closed within SLA
$withinSLA = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status IN ('Resolved','Closed') AND resolved_datetime IS NOT NULL AND resolved_datetime <= sla_due_datetime AND created_datetime BETWEEN ? AND ?");
$withinSLA->execute([$dateFrom, $dateTo.' 23:59:59']); $withinSLA = $withinSLA->fetchColumn();

// Resolved/closed but breached SLA
$breachedResolved = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status IN ('Resolved','Closed') AND resolved_datetime IS NOT NULL AND resolved_datetime > sla_due_datetime AND created_datetime BETWEEN ? AND ?");
$breachedResolved->execute([$dateFrom, $dateTo.' 23:59:59']); $breachedResolved = $breachedResolved->fetchColumn();

// Pending SLA risk (open tickets near or past SLA)
$pendingRisk = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('Resolved','Closed') AND sla_due_datetime < NOW() AND created_datetime BETWEEN ? AND ?");
$pendingRisk->execute([$dateFrom, $dateTo.' 23:59:59']); $pendingRisk = $pendingRisk->fetchColumn();

$totalResolved = $withinSLA + $breachedResolved;
$compliancePct = $totalResolved > 0 ? round($withinSLA / $totalResolved * 100, 1) : 0;

// SLA by category
$slaByCat = $pdo->prepare("SELECT c.category_name,
    SUM(CASE WHEN t.status IN ('Resolved','Closed') AND t.resolved_datetime <= t.sla_due_datetime THEN 1 ELSE 0 END) as within_sla,
    SUM(CASE WHEN t.status IN ('Resolved','Closed') AND t.resolved_datetime > t.sla_due_datetime THEN 1 ELSE 0 END) as breached,
    COUNT(*) as total
    FROM tickets t JOIN categories c ON t.category_id = c.category_id WHERE t.created_datetime BETWEEN ? AND ? GROUP BY c.category_id");
$slaByCat->execute([$dateFrom, $dateTo.' 23:59:59']); $slaByCat = $slaByCat->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-clipboard-check me-2"></i>SLA Compliance Report</h1></div>
<div class="card mb-3"><div class="card-body py-3"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fs-xs">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>"></div>
<div class="col-md-3"><label class="form-label fs-xs">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>"></div>
<div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
</form></div></div>

<div class="row g-3 mb-4">
<div class="col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-value"><?= $withinSLA ?></div><div class="stat-label">Within SLA</div></div></div>
<div class="col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-value"><?= $breachedResolved ?></div><div class="stat-label">SLA Breached</div></div></div>
<div class="col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-value"><?= $pendingRisk ?></div><div class="stat-label">Pending Risk</div></div></div>
<div class="col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="fas fa-percentage"></i></div><div class="stat-value"><?= $compliancePct ?>%</div><div class="stat-label">Compliance Rate</div></div></div>
</div>

<div class="row g-3 mb-4">
<div class="col-lg-5"><div class="card"><div class="card-header">SLA Overview</div><div class="card-body"><div class="chart-container"><canvas id="slaChart"></canvas></div></div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-header">SLA by Category</div><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Category</th><th>Total</th><th>Within SLA</th><th>Breached</th><th>Compliance</th></tr></thead><tbody>
<?php foreach($slaByCat as $c): $cPct = ($c['within_sla'] + $c['breached']) > 0 ? round($c['within_sla'] / ($c['within_sla'] + $c['breached']) * 100, 1) : 0; ?>
<tr><td class="fw-600"><?= e($c['category_name']) ?></td><td><?= $c['total'] ?></td>
<td class="text-success"><?= $c['within_sla'] ?></td><td class="<?= $c['breached'] > 0 ? 'text-danger fw-600' : '' ?>"><?= $c['breached'] ?></td>
<td><span class="badge <?= $cPct >= 80 ? 'bg-success' : ($cPct >= 60 ? 'bg-warning text-dark' : 'bg-danger') ?>"><?= $cPct ?>%</span></td></tr>
<?php endforeach; ?></tbody></table></div></div></div></div>
</div>

<div class="card mb-4"><div class="card-header"><span><i class="fas fa-robot me-2"></i>AI Insights <span class="badge badge-ai">AI</span></span>
<button class="btn btn-sm btn-ai" onclick="getInsights(this)"><i class="fas fa-sync-alt me-1"></i>Generate</button></div>
<div class="card-body" id="insightsArea"><div class="text-muted text-center py-3">Click "Generate" for AI-powered insights.</div></div></div>

<script>
new Chart(document.getElementById('slaChart'), { type: 'doughnut', data: { labels: ['Within SLA', 'Breached', 'Pending Risk'], datasets: [{ data: [<?= $withinSLA ?>, <?= $breachedResolved ?>, <?= $pendingRisk ?>], backgroundColor: ['#059669','#dc2626','#d97706'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });

function getInsights(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/report_insights.php', { report_type: 'sla_compliance', data: { within_sla: <?= $withinSLA ?>, breached: <?= $breachedResolved ?>, pending_risk: <?= $pendingRisk ?>, compliance_pct: <?= $compliancePct ?>, by_category: <?= json_encode($slaByCat) ?> } }, function(data) {
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
