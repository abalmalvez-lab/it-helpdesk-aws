<?php
$pageTitle = 'Ticket Volume Report';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();

$period = $_GET['period'] ?? 'month';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Total tickets in period
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE created_datetime BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$totalTickets = $stmt->fetchColumn();

// By status
$byStatus = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY status");
$byStatus->execute([$dateFrom, $dateTo . ' 23:59:59']);
$byStatus = $byStatus->fetchAll();

// By priority
$byPriority = $pdo->prepare("SELECT priority_level, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY priority_level");
$byPriority->execute([$dateFrom, $dateTo . ' 23:59:59']);
$byPriority = $byPriority->fetchAll();

// Monthly trend
$monthlyTrend = $pdo->prepare("SELECT DATE_FORMAT(created_datetime, '%Y-%m') as month, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY month ORDER BY month");
$monthlyTrend->execute([$dateFrom, $dateTo . ' 23:59:59']);
$monthlyTrend = $monthlyTrend->fetchAll();
?>

<div class="page-header"><h1><i class="fas fa-chart-bar me-2"></i>Ticket Volume Report</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Ticket Volume</li></ol></nav></div>

<!-- Filters -->
<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fs-xs">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>"></div>
<div class="col-md-3"><label class="form-label fs-xs">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>"></div>
<div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
</form></div></div>

<!-- Summary -->
<div class="row g-3 mb-4">
<div class="col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="fas fa-ticket-alt"></i></div><div class="stat-value"><?= $totalTickets ?></div><div class="stat-label">Total Tickets</div></div></div>
<?php foreach($byStatus as $s): ?>
<div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:var(--body-bg);"><i class="fas fa-circle"></i></div><div class="stat-value"><?= $s['cnt'] ?></div><div class="stat-label"><?= e($s['status']) ?></div></div></div>
<?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
<div class="col-lg-6"><div class="card"><div class="card-header">Tickets by Status</div><div class="card-body"><div class="chart-container"><canvas id="statusChart"></canvas></div></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Monthly Trend</div><div class="card-body"><div class="chart-container"><canvas id="trendChart"></canvas></div></div></div></div>
</div>

<!-- AI Insights -->
<div class="card mb-4"><div class="card-header"><span><i class="fas fa-robot me-2"></i>AI Insights <span class="badge badge-ai">AI</span></span>
<button class="btn btn-sm btn-ai" onclick="getInsights(this)"><i class="fas fa-sync-alt me-1"></i>Generate</button></div>
<div class="card-body" id="insightsArea"><div class="text-muted text-center py-3">Click "Generate" for AI-powered insights.</div></div></div>

<script>
new Chart(document.getElementById('statusChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_column($byStatus, 'status')) ?>, datasets: [{ data: <?= json_encode(array_map('intval', array_column($byStatus, 'cnt'))) ?>, backgroundColor: ['#2563eb','#0891b2','#d97706','#dc2626','#059669','#94a3b8'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('trendChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($monthlyTrend, 'month')) ?>, datasets: [{ label: 'Tickets', data: <?= json_encode(array_map('intval', array_column($monthlyTrend, 'cnt'))) ?>, backgroundColor: '#2563eb', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });

function getInsights(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/report_insights.php', { report_type: 'ticket_volume', data: { total: <?= $totalTickets ?>, by_status: <?= json_encode($byStatus) ?>, by_priority: <?= json_encode($byPriority) ?>, period: '<?= e($dateFrom) ?> to <?= e($dateTo) ?>' } }, function(data) {
        const area = document.getElementById('insightsArea');
        if (data.success && data.data) {
            let html = '<div class="ai-suggestion-box">';
            if (data.data.key_observations) { html += '<h6 class="fw-600 mb-2"><i class="fas fa-lightbulb me-1"></i>Key Observations</h6><ul class="mb-3">' + data.data.key_observations.map(o => '<li>' + o + '</li>').join('') + '</ul>'; }
            if (data.data.operational_risks) { html += '<h6 class="fw-600 mb-2"><i class="fas fa-exclamation-triangle me-1 text-warning"></i>Risks</h6><ul class="mb-3">' + data.data.operational_risks.map(o => '<li>' + o + '</li>').join('') + '</ul>'; }
            if (data.data.recommendations) { html += '<h6 class="fw-600 mb-2"><i class="fas fa-check-circle me-1 text-success"></i>Recommendations</h6><ul class="mb-0">' + data.data.recommendations.map(o => '<li>' + o + '</li>').join('') + '</ul>'; }
            html += '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div></div>';
            area.innerHTML = html;
        } else { area.innerHTML = '<div class="alert alert-warning">' + (data.error || 'Failed') + '</div>'; }
    });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
