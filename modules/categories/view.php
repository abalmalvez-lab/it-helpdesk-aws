<?php
$pageTitle = 'View Category';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$cat = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?"); $cat->execute([$id]); $cat = $cat->fetch();
if (!$cat) { setFlashMessage('error', 'Not found.'); header('Location: index.php'); exit; }
$tickets = $pdo->prepare("SELECT t.*, u.full_name as user_name FROM tickets t LEFT JOIN users u ON t.user_id = u.user_id WHERE t.category_id = ? ORDER BY t.created_datetime DESC LIMIT 20");
$tickets->execute([$id]); $tickets = $tickets->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-tag me-2"></i><?= e($cat['category_name']) ?></h1></div>
<div class="row"><div class="col-lg-4 mb-3"><div class="card"><div class="card-body">
<h5 class="fw-600"><?= e($cat['category_name']) ?></h5>
<p class="fs-sm text-muted"><?= e($cat['description'] ?? 'No description') ?></p>
<p class="fs-sm"><strong>SLA:</strong> <?= $cat['sla_hours'] ?> hours</p>
<a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit me-1"></i>Edit</a>
</div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header">Related Tickets (<?= count($tickets) ?>)</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table"><thead><tr><th>Ticket</th><th>Title</th><th>By</th><th>Status</th></tr></thead><tbody>
<?php foreach($tickets as $t): ?>
<tr><td><a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $t['ticket_id'] ?>"><?= e($t['ticket_number']) ?></a></td>
<td><?= e(truncateText($t['issue_title'],40)) ?></td><td class="fs-sm"><?= e($t['user_name']) ?></td>
<td><span class="badge <?= getStatusBadgeClass($t['status']) ?>"><?= e($t['status']) ?></span></td></tr>
<?php endforeach; ?></tbody></table></div></div></div></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
