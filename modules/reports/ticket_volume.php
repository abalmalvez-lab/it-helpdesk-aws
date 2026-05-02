<?php
$pageTitle = 'Ticket Volume Report';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$viewMode = $_GET['view'] ?? 'summary';          // summary | status | trend
$filterStatus = $_GET['filter_status'] ?? '';     // specific status filter
$filterMonth = $_GET['filter_month'] ?? '';       // specific month filter (YYYY-MM)
$dateRange = [$dateFrom, $dateTo . ' 23:59:59'];

// ── Aggregate Data ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE created_datetime BETWEEN ? AND ?");
$stmt->execute($dateRange);
$totalTickets = $stmt->fetchColumn();

$byStatus = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY status ORDER BY FIELD(status,'Open','Assigned','In Progress','Escalated','Resolved','Closed')");
$byStatus->execute($dateRange);
$byStatus = $byStatus->fetchAll();

$byPriority = $pdo->prepare("SELECT priority_level, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY priority_level");
$byPriority->execute($dateRange);
$byPriority = $byPriority->fetchAll();

$monthlyTrend = $pdo->prepare("SELECT DATE_FORMAT(created_datetime, '%Y-%m') as month, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY month ORDER BY month");
$monthlyTrend->execute($dateRange);
$monthlyTrend = $monthlyTrend->fetchAll();

// ── Monthly × Status Crosstab ──
$crosstab = $pdo->prepare("SELECT DATE_FORMAT(created_datetime, '%Y-%m') as month, status, COUNT(*) as cnt FROM tickets WHERE created_datetime BETWEEN ? AND ? GROUP BY month, status ORDER BY month, FIELD(status,'Open','Assigned','In Progress','Escalated','Resolved','Closed')");
$crosstab->execute($dateRange);
$crosstabRaw = $crosstab->fetchAll();

$allStatuses = ['Open','Assigned','In Progress','Escalated','Resolved','Closed'];
$months = array_unique(array_column($crosstabRaw, 'month'));
$crosstabData = [];
foreach ($crosstabRaw as $row) {
    $crosstabData[$row['month']][$row['status']] = $row['cnt'];
}

// ── Detail Tickets (when clicking a number) ──
$detailTickets = [];
$detailTitle = '';
$ticketBase = $baseUrl . '/modules/tickets';

if ($viewMode === 'status' && $filterStatus) {
    $detailTitle = "Tickets with status: $filterStatus";
    $stmt = $pdo->prepare("SELECT t.*, c.category_name, u.full_name as user_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id WHERE t.status = ? AND t.created_datetime BETWEEN ? AND ? ORDER BY t.created_datetime DESC");
    $stmt->execute([$filterStatus, $dateFrom, $dateTo . ' 23:59:59']);
    $detailTickets = $stmt->fetchAll();
} elseif ($viewMode === 'trend' && $filterMonth) {
    $detailTitle = "Tickets created in: $filterMonth";
    $monthStart = $filterMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $stmt = $pdo->prepare("SELECT t.*, c.category_name, u.full_name as user_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id WHERE t.created_datetime BETWEEN ? AND ? ORDER BY t.created_datetime DESC");
    $stmt->execute([$monthStart, $monthEnd . ' 23:59:59']);
    $detailTickets = $stmt->fetchAll();
}

// Helper to build clickable URL
function volUrl($params) {
    global $dateFrom, $dateTo;
    $base = array_merge(['date_from' => $dateFrom, 'date_to' => $dateTo], $params);
    return '?' . http_build_query($base);
}
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar me-2"></i>Ticket Volume Report</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Ticket Volume</li>
        </ol>
    </nav>
</div>

<!-- Date Range Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-xs">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-xs">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Apply</button>
                <a href="ticket_volume.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stat Cards (clickable) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3">
        <a href="<?= $ticketBase ?>/index.php" class="stat-card-link">
            <div class="stat-card stat-primary">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-value"><?= $totalTickets ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </a>
    </div>
    <?php
    $statusIcons = ['Open'=>'folder-open','Assigned'=>'user-check','In Progress'=>'spinner','Escalated'=>'exclamation-triangle','Resolved'=>'check-circle','Closed'=>'archive'];
    $statusStyles = ['Open'=>'stat-info','Assigned'=>'stat-info','In Progress'=>'stat-warning','Escalated'=>'stat-danger','Resolved'=>'stat-success','Closed'=>''];
    foreach ($byStatus as $s):
        $icon = $statusIcons[$s['status']] ?? 'circle';
        $style = $statusStyles[$s['status']] ?? '';
    ?>
    <div class="col-6 col-md-4 col-xl-3">
        <a href="<?= volUrl(['view'=>'status','filter_status'=>$s['status']]) ?>" class="stat-card-link">
            <div class="stat-card <?= $style ?>">
                <div class="stat-icon"><i class="fas fa-<?= $icon ?>"></i></div>
                <div class="stat-value"><?= $s['cnt'] ?></div>
                <div class="stat-label"><?= e($s['status']) ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Tickets by Status</div>
            <div class="card-body"><div class="chart-container"><canvas id="statusChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-line me-2"></i>Monthly Trend</div>
            <div class="card-body"><div class="chart-container"><canvas id="trendChart"></canvas></div></div>
        </div>
    </div>
</div>

<!-- Monthly × Status Crosstab Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-th me-2"></i>Volume Breakdown — Month × Status</span>
        <div class="d-flex gap-1">
            <a href="<?= volUrl(['view'=>'summary']) ?>" class="btn btn-sm <?= $viewMode === 'summary' || !$viewMode ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
            <?php foreach ($allStatuses as $st): ?>
            <a href="<?= volUrl(['view'=>'status','filter_status'=>$st]) ?>" class="btn btn-sm <?= ($viewMode === 'status' && $filterStatus === $st) ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $st ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <?php foreach ($allStatuses as $st): ?>
                        <th class="text-center">
                            <a href="<?= volUrl(['view'=>'status','filter_status'=>$st]) ?>" class="text-decoration-none" title="Filter by <?= $st ?>">
                                <?= $st ?>
                            </a>
                        </th>
                        <?php endforeach; ?>
                        <th class="text-center fw-600">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grandTotals = array_fill_keys($allStatuses, 0);
                    $grandTotal = 0;
                    foreach ($months as $month):
                        $rowTotal = 0;
                        foreach ($allStatuses as $st) {
                            $val = $crosstabData[$month][$st] ?? 0;
                            $rowTotal += $val;
                            $grandTotals[$st] += $val;
                        }
                        $grandTotal += $rowTotal;
                    ?>
                    <tr>
                        <td>
                            <a href="<?= volUrl(['view'=>'trend','filter_month'=>$month]) ?>" class="fw-600 text-decoration-none" title="View tickets for <?= $month ?>">
                                <?= date('M Y', strtotime($month . '-01')) ?>
                            </a>
                        </td>
                        <?php foreach ($allStatuses as $st):
                            $val = $crosstabData[$month][$st] ?? 0;
                        ?>
                        <td class="text-center">
                            <?php if ($val > 0): ?>
                            <a href="<?= $ticketBase ?>/index.php?status=<?= urlencode($st) ?>" class="text-decoration-none fw-600">
                                <?= $val ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-center">
                            <a href="<?= volUrl(['view'=>'trend','filter_month'=>$month]) ?>" class="fw-600 text-decoration-none">
                                <?= $rowTotal ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--slate-50);">
                        <td class="fw-600">Grand Total</td>
                        <?php foreach ($allStatuses as $st): ?>
                        <td class="text-center">
                            <a href="<?= volUrl(['view'=>'status','filter_status'=>$st]) ?>" class="fw-600 text-decoration-none">
                                <?= $grandTotals[$st] ?>
                            </a>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-center fw-600" style="color:var(--brand-600);"><?= $grandTotal ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($detailTickets)): ?>
<!-- Detail View (when a number is clicked) -->
<div class="card mb-4" id="detailTable">
    <div class="card-header">
        <span>
            <i class="fas fa-list me-2"></i><?= e($detailTitle) ?>
            <span class="badge bg-primary ms-1"><?= count($detailTickets) ?></span>
        </span>
        <a href="<?= volUrl(['view'=>'summary']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Close</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Title</th>
                        <th>Submitted By</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailTickets as $t): ?>
                    <tr>
                        <td><span class="fw-600"><?= e($t['ticket_number']) ?></span></td>
                        <td>
                            <a href="<?= $ticketBase ?>/view.php?id=<?= $t['ticket_id'] ?>" class="text-decoration-none fw-600">
                                <?= e(truncateText($t['issue_title'], 45)) ?>
                            </a>
                        </td>
                        <td class="fs-sm"><?= e($t['user_name']) ?></td>
                        <td><span class="badge bg-light text-dark"><?= e($t['category_name'] ?? '—') ?></span></td>
                        <td><span class="badge <?= getPriorityBadgeClass($t['priority_level']) ?>"><?= e($t['priority_level']) ?></span></td>
                        <td><span class="badge <?= getStatusBadgeClass($t['status']) ?>"><?= e($t['status']) ?></span></td>
                        <td class="fs-sm text-muted"><?= formatDateTime($t['created_datetime']) ?></td>
                        <td>
                            <a href="<?= $ticketBase ?>/view.php?id=<?= $t['ticket_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>document.getElementById('detailTable').scrollIntoView({ behavior: 'smooth', block: 'start' });</script>
<?php endif; ?>

<!-- AI Insights -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-robot me-2"></i>AI Insights <span class="badge badge-ai">AI</span></span>
        <button class="btn btn-sm btn-ai" onclick="getInsights(this)"><i class="fas fa-sync-alt me-1"></i>Generate</button>
    </div>
    <div class="card-body" id="insightsArea">
        <div class="text-muted text-center py-3">Click "Generate" for AI-powered insights.</div>
    </div>
</div>

<script>
// Status Chart — clickable segments
const statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($byStatus, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($byStatus, 'cnt'))) ?>,
            backgroundColor: ['#3b82f6','#06b6d4','#f59e0b','#ef4444','#22c55e','#94a3b8'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } },
        onClick: function(e, elements) {
            if (elements.length > 0) {
                const status = this.data.labels[elements[0].index];
                window.location.href = '<?= volUrl([]) ?>&view=status&filter_status=' + encodeURIComponent(status);
            }
        }
    }
});

// Monthly Trend Chart — clickable bars
const trendChart = new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyTrend, 'month')) ?>,
        datasets: [{
            label: 'Tickets',
            data: <?= json_encode(array_map('intval', array_column($monthlyTrend, 'cnt'))) ?>,
            backgroundColor: '#3b82f6',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } },
        onClick: function(e, elements) {
            if (elements.length > 0) {
                const month = this.data.labels[elements[0].index];
                window.location.href = '<?= volUrl([]) ?>&view=trend&filter_month=' + encodeURIComponent(month);
            }
        }
    }
});

// Pointer cursor on chart hover
document.getElementById('statusChart').style.cursor = 'pointer';
document.getElementById('trendChart').style.cursor = 'pointer';

function getInsights(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/report_insights.php', {
        report_type: 'ticket_volume',
        data: {
            total: <?= $totalTickets ?>,
            by_status: <?= json_encode($byStatus) ?>,
            by_priority: <?= json_encode($byPriority) ?>,
            period: '<?= e($dateFrom) ?> to <?= e($dateTo) ?>'
        }
    }, function(data) {
        const area = document.getElementById('insightsArea');
        if (data.success && data.data) {
            let html = '<div class="ai-suggestion-box">';
            ['key_observations','operational_risks','recommendations'].forEach(k => {
                if (data.data[k]) {
                    const title = k.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase());
                    html += '<h6 class="fw-600 mb-2">' + title + '</h6><ul class="mb-2">' + data.data[k].map(o => '<li>' + o + '</li>').join('') + '</ul>';
                }
            });
            html += '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div></div>';
            area.innerHTML = html;
        } else {
            area.innerHTML = '<div class="alert alert-warning">' + (data.error || 'Failed') + '</div>';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
