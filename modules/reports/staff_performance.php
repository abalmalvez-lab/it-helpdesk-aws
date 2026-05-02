<?php
$pageTitle = 'Staff Performance Report';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$staffPerf = $pdo->prepare("SELECT s.full_name, s.specialization,
    (SELECT COUNT(*) FROM tickets t2 WHERE t2.assigned_staff_id = s.staff_id AND t2.created_datetime BETWEEN ? AND ?) as assigned,
    (SELECT COUNT(*) FROM tickets t3 WHERE t3.assigned_staff_id = s.staff_id AND t3.status IN ('Resolved','Closed') AND t3.created_datetime BETWEEN ? AND ?) as resolved,
    (SELECT COUNT(*) FROM tickets t4 WHERE t4.assigned_staff_id = s.staff_id AND t4.status = 'Escalated' AND t4.created_datetime BETWEEN ? AND ?) as escalated,
    (SELECT AVG(r2.resolution_time_minutes) FROM resolutions r2 WHERE r2.staff_id = s.staff_id) as avg_resolution
    FROM support_staff s WHERE s.status = 'Active' ORDER BY resolved DESC");
$staffPerf->execute([$dateFrom, $dateTo.' 23:59:59', $dateFrom, $dateTo.' 23:59:59', $dateFrom, $dateTo.' 23:59:59']);
$staffPerf = $staffPerf->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-users-cog me-2"></i>Staff Performance Report</h1></div>
<div class="card mb-3"><div class="card-body py-3"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fs-xs">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>"></div>
<div class="col-md-3"><label class="form-label fs-xs">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>"></div>
<div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
</form></div></div>

<div class="card mb-4"><div class="card-header">Staff Performance</div><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Staff</th><th>Specialization</th><th>Assigned</th><th>Resolved</th><th>Escalated</th><th>Avg Resolution</th></tr></thead><tbody>
<?php foreach($staffPerf as $s): ?>
<tr>
<td class="fw-600"><?= e($s['full_name']) ?></td>
<td><span class="badge bg-light text-dark"><?= e($s['specialization'] ?? '—') ?></span></td>
<td><?= $s['assigned'] ?></td>
<td class="text-success fw-600"><?= $s['resolved'] ?></td>
<td class="<?= $s['escalated'] > 0 ? 'text-danger fw-600' : '' ?>"><?= $s['escalated'] ?></td>
<td><?= $s['avg_resolution'] ? round($s['avg_resolution']/60, 1) . 'h' : '—' ?></td>
</tr>
<?php endforeach; ?></tbody></table></div></div></div>

<div class="row g-3 mb-4">
<div class="col-lg-6"><div class="card"><div class="card-header">Tickets Resolved by Staff</div><div class="card-body"><div class="chart-container"><canvas id="staffChart"></canvas></div></div></div></div>
</div>

<div class="card mb-4"><div class="card-header"><span><i class="fas fa-robot me-2"></i>AI Insights <span class="badge badge-ai">AI</span></span>
<button class="btn btn-sm btn-ai" onclick="getInsights(this)"><i class="fas fa-sync-alt me-1"></i>Generate</button></div>
<div class="card-body" id="insightsArea"><div class="text-muted text-center py-3">Click "Generate" for AI-powered insights.</div></div></div>

<script>
new Chart(document.getElementById('staffChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($staffPerf, 'full_name')) ?>, datasets: [
{ label: 'Assigned', data: <?= json_encode(array_map('intval', array_column($staffPerf, 'assigned'))) ?>, backgroundColor: '#2563eb' },
{ label: 'Resolved', data: <?= json_encode(array_map('intval', array_column($staffPerf, 'resolved'))) ?>, backgroundColor: '#059669' },
{ label: 'Escalated', data: <?= json_encode(array_map('intval', array_column($staffPerf, 'escalated'))) ?>, backgroundColor: '#dc2626' }
] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });

function getInsights(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/report_insights.php', { report_type: 'staff_performance', data: <?= json_encode($staffPerf) ?> }, function(data) {
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
