<?php
$pageTitle = 'AI Interaction Logs';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();

$page = max(1, intval($_GET['page'] ?? 1));
$featureFilter = $_GET['feature'] ?? '';
$where = $featureFilter ? "WHERE ai.feature_name = ?" : '';
$params = $featureFilter ? [$featureFilter] : [];

$total = $pdo->prepare("SELECT COUNT(*) FROM ai_interactions ai $where"); $total->execute($params); $total = $total->fetchColumn();
$pagination = paginate($total, 20, $page);

$stmt = $pdo->prepare("SELECT ai.*, u.full_name, t.ticket_number FROM ai_interactions ai LEFT JOIN users u ON ai.user_id = u.user_id LEFT JOIN tickets t ON ai.ticket_id = t.ticket_id $where ORDER BY ai.created_datetime DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$features = $pdo->query("SELECT DISTINCT feature_name FROM ai_interactions ORDER BY feature_name")->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="page-header"><h1><i class="fas fa-robot me-2"></i>AI Interaction Logs</h1></div>

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
<div class="col-md-4"><select name="feature" class="form-select form-select-sm"><option value="">All Features</option>
<?php foreach($features as $f): ?><option value="<?= e($f) ?>" <?= $featureFilter===$f?'selected':'' ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button><a href="logs.php" class="btn btn-sm btn-outline-secondary ms-1">Clear</a></div>
</form></div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>ID</th><th>Feature</th><th>User</th><th>Ticket</th><th>Status</th><th>When</th><th>Prompt</th></tr></thead>
<tbody>
<?php foreach($logs as $l): ?>
<tr>
<td class="fw-600">#<?= $l['ai_interaction_id'] ?></td>
<td><span class="badge badge-ai"><?= e($l['feature_name']) ?></span></td>
<td class="fs-sm"><?= e($l['full_name'] ?? 'System') ?></td>
<td class="fs-sm"><?= $l['ticket_number'] ? '<a href="'.$baseUrl.'/modules/tickets/view.php?id='.$l['ticket_id'].'">'.$l['ticket_number'].'</a>' : '—' ?></td>
<td><span class="badge <?= $l['status']==='Success'?'bg-success':'bg-danger' ?>"><?= e($l['status']) ?></span></td>
<td class="fs-xs text-muted"><?= formatDateTime($l['created_datetime']) ?></td>
<td class="fs-sm"><?= e(truncateText($l['prompt_summary'] ?? '',50)) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="card-footer bg-transparent py-2"><?= renderPagination($pagination, array_filter(['feature'=>$featureFilter])) ?></div>
</div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
