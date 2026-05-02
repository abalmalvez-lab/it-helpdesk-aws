<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection();
$role = getCurrentUserRole();
$userId = getCurrentUserId();

// Get ticket counts
$counts = getTicketCounts();

// Role-specific ticket query
if ($role === 'End User') {
    $stmt = $pdo->prepare("SELECT t.*, c.category_name, s.full_name as staff_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN support_staff s ON t.assigned_staff_id = s.staff_id WHERE t.user_id = ? ORDER BY t.created_datetime DESC LIMIT 10");
    $stmt->execute([$userId]);
    
    // Override counts for end user
    $stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE user_id = ?");
    $stmt2->execute([$userId]);
    $counts['total'] = $stmt2->fetch()['total'];
    
    $statuses = ['Open', 'Assigned', 'In Progress', 'Escalated', 'Resolved', 'Closed'];
    foreach ($statuses as $s) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) as c FROM tickets WHERE user_id = ? AND status = ?");
        $stmt2->execute([$userId, $s]);
        $counts[strtolower(str_replace(' ', '_', $s))] = $stmt2->fetch()['c'];
    }
} elseif ($role === 'Support Staff') {
    $staffId = getCurrentStaffId();
    $stmt = $pdo->prepare("SELECT t.*, c.category_name, u.full_name as user_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id WHERE t.assigned_staff_id = ? ORDER BY t.created_datetime DESC LIMIT 10");
    $stmt->execute([$staffId]);
} else {
    $stmt = $pdo->query("SELECT t.*, c.category_name, u.full_name as user_name, s.full_name as staff_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id LEFT JOIN support_staff s ON t.assigned_staff_id = s.staff_id ORDER BY t.created_datetime DESC LIMIT 10");
}
$recentTickets = $stmt->fetchAll();

// Chart data: tickets by status
$statusData = $pdo->query("SELECT status, COUNT(*) as cnt FROM tickets GROUP BY status")->fetchAll();
$statusLabels = array_column($statusData, 'status');
$statusCounts = array_column($statusData, 'cnt');

// Chart data: tickets by category
$catData = $pdo->query("SELECT c.category_name, COUNT(t.ticket_id) as cnt FROM tickets t JOIN categories c ON t.category_id = c.category_id GROUP BY c.category_name ORDER BY cnt DESC")->fetchAll();
$catLabels = array_column($catData, 'category_name');
$catCounts = array_column($catData, 'cnt');

// Chart data: monthly volume (last 6 months)
$monthData = $pdo->query("SELECT DATE_FORMAT(created_datetime, '%Y-%m') as month, COUNT(*) as cnt FROM tickets WHERE created_datetime >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month")->fetchAll();
$monthLabels = array_column($monthData, 'month');
$monthCounts = array_column($monthData, 'cnt');
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-value"><?= $counts['total'] ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-value"><?= $counts['open'] ?></div>
            <div class="stat-label">Open</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
            <div class="stat-value"><?= $counts['in_progress'] ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value"><?= $counts['escalated'] ?></div>
            <div class="stat-label">Escalated</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $counts['resolved'] ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card" style="border-left: 3px solid var(--text-muted);">
            <div class="stat-icon" style="background:#f1f5f9;color:var(--text-secondary);"><i class="fas fa-archive"></i></div>
            <div class="stat-value"><?= $counts['closed'] ?></div>
            <div class="stat-label">Closed</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card" style="border-left: 3px solid var(--info);">
            <div class="stat-icon" style="background:var(--info-light);color:var(--info);"><i class="fas fa-user-check"></i></div>
            <div class="stat-value"><?= $counts['assigned'] ?></div>
            <div class="stat-label">Assigned</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card stat-danger" style="border-left: 3px solid var(--danger);">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $counts['sla_breached'] ?></div>
            <div class="stat-label">SLA Breached</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Tickets by Status</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Tickets by Category</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-line me-2"></i>Monthly Volume</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="monthlyChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tickets -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-list me-2"></i>Recent Tickets</span>
        <a href="<?= $baseUrl ?>/modules/tickets/index.php" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentTickets)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h5>No tickets yet</h5>
            <p>Tickets will appear here once created.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTickets as $ticket): ?>
                    <tr>
                        <td><span class="fw-600"><?= e($ticket['ticket_number']) ?></span></td>
                        <td><?= e(truncateText($ticket['issue_title'], 50)) ?></td>
                        <td><span class="badge bg-light text-dark"><?= e($ticket['category_name'] ?? '—') ?></span></td>
                        <td><span class="badge <?= getPriorityBadgeClass($ticket['priority_level']) ?>"><?= e($ticket['priority_level']) ?></span></td>
                        <td><span class="badge <?= getStatusBadgeClass($ticket['status']) ?>"><?= e($ticket['status']) ?></span></td>
                        <td class="fs-sm text-muted"><?= timeAgo($ticket['created_datetime']) ?></td>
                        <td>
                            <a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($role === 'Admin'): ?>
<!-- AI Operational Summary -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-robot me-2"></i>AI Operational Summary <span class="badge badge-ai ms-1">AI</span></span>
        <button class="btn btn-sm btn-ai" onclick="loadAISummary(this)">
            <i class="fas fa-sync-alt me-1"></i> Generate
        </button>
    </div>
    <div class="card-body" id="aiSummaryContent">
        <div class="text-muted text-center py-3">
            <i class="fas fa-robot fa-2x mb-2 d-block" style="opacity:0.2;"></i>
            Click "Generate" to get AI-powered operational insights.
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Status Chart
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', $statusCounts)) ?>,
            backgroundColor: ['#2563eb', '#0891b2', '#d97706', '#dc2626', '#059669', '#94a3b8'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } }
    }
});

// Category Chart
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', $catCounts)) ?>,
            backgroundColor: '#2563eb',
            borderRadius: 4,
            barThickness: 20
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { display: false } }, y: { grid: { display: false } } }
    }
});

// Monthly Chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Tickets',
            data: <?= json_encode(array_map('intval', $monthCounts)) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.08)',
            fill: true, tension: 0.4, borderWidth: 2,
            pointBackgroundColor: '#2563eb', pointRadius: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
    }
});

<?php if ($role === 'Admin'): ?>
function loadAISummary(btn) {
    const container = document.getElementById('aiSummaryContent');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> Generating...';
    container.innerHTML = '<div class="text-center py-3"><div class="ai-loading"><span class="spinner-border"></span> Analyzing helpdesk data...</div></div>';

    fetch('<?= $baseUrl ?>/modules/ai/report_insights.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_type: 'dashboard_summary', data: { total: <?= $counts['total'] ?>, open: <?= $counts['open'] ?>, in_progress: <?= $counts['in_progress'] ?>, escalated: <?= $counts['escalated'] ?>, resolved: <?= $counts['resolved'] ?>, closed: <?= $counts['closed'] ?>, sla_breached: <?= $counts['sla_breached'] ?> } })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = original;
        if (data.success && data.data) {
            let html = '<div class="ai-suggestion-box mt-2">';
            if (data.data.key_observations) {
                html += '<h6 class="fw-600 mb-2"><i class="fas fa-lightbulb me-1"></i>Key Observations</h6><ul class="mb-3">';
                data.data.key_observations.forEach(o => html += '<li>' + o + '</li>');
                html += '</ul>';
            }
            if (data.data.operational_risks) {
                html += '<h6 class="fw-600 mb-2"><i class="fas fa-exclamation-triangle me-1 text-warning"></i>Operational Risks</h6><ul class="mb-3">';
                data.data.operational_risks.forEach(o => html += '<li>' + o + '</li>');
                html += '</ul>';
            }
            if (data.data.recommendations) {
                html += '<h6 class="fw-600 mb-2"><i class="fas fa-check-circle me-1 text-success"></i>Recommendations</h6><ul class="mb-0">';
                data.data.recommendations.forEach(o => html += '<li>' + o + '</li>');
                html += '</ul>';
            }
            html += '<div class="ai-disclaimer mt-2">AI-generated suggestion. Please review before applying.</div></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-circle me-1"></i>' + (data.error || 'Could not generate insights.') + '</div>';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = original;
        container.innerHTML = '<div class="alert alert-danger mb-0">Failed to connect to AI service.</div>';
    });
}
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
