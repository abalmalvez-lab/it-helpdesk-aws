<?php
$pageTitle = 'View User';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?"); $stmt->execute([$id]); $user = $stmt->fetch();
if (!$user) { setFlashMessage('error', 'User not found.'); header('Location: index.php'); exit; }

$tickets = $pdo->prepare("SELECT t.*, c.category_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id WHERE t.user_id = ? ORDER BY t.created_datetime DESC LIMIT 20");
$tickets->execute([$id]); $tickets = $tickets->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-user me-2"></i><?= e($user['full_name']) ?></h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="index.php">Users</a></li><li class="breadcrumb-item active"><?= e($user['full_name']) ?></li></ol></nav></div>

<div class="row">
<div class="col-lg-4 mb-3">
<div class="card"><div class="card-body text-center">
<div class="topbar-user-avatar mx-auto mb-3" style="width:64px;height:64px;font-size:24px;border-radius:16px;"><?= strtoupper(substr($user['full_name'],0,2)) ?></div>
<h5 class="fw-600"><?= e($user['full_name']) ?></h5>
<span class="badge bg-light text-dark"><?= e($user['role']) ?></span>
<span class="badge <?= $user['status']==='Active'?'bg-success':'bg-secondary' ?>"><?= e($user['status']) ?></span>
<hr>
<div class="text-start fs-sm">
<p><strong>Employee ID:</strong> <?= e($user['employee_id']) ?></p>
<p><strong>Email:</strong> <?= e($user['email']) ?></p>
<p><strong>Department:</strong> <?= e($user['department'] ?? '—') ?></p>
<p><strong>Contact:</strong> <?= e($user['contact_number'] ?? '—') ?></p>
<p class="mb-0"><strong>Joined:</strong> <?= formatDate($user['created_datetime']) ?></p>
</div>
</div></div>
</div>
<div class="col-lg-8">
<div class="card"><div class="card-header"><i class="fas fa-ticket-alt me-2"></i>Submitted Tickets (<?= count($tickets) ?>)</div>
<div class="card-body p-0">
<?php if (empty($tickets)): ?>
<div class="p-4 text-center text-muted">No tickets submitted.</div>
<?php else: ?>
<div class="table-responsive"><table class="table"><thead><tr><th>Ticket #</th><th>Title</th><th>Priority</th><th>Status</th><th>Created</th></tr></thead><tbody>
<?php foreach ($tickets as $t): ?>
<tr>
<td><a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $t['ticket_id'] ?>" class="fw-600 text-decoration-none"><?= e($t['ticket_number']) ?></a></td>
<td><?= e(truncateText($t['issue_title'],40)) ?></td>
<td><span class="badge <?= getPriorityBadgeClass($t['priority_level']) ?>"><?= e($t['priority_level']) ?></span></td>
<td><span class="badge <?= getStatusBadgeClass($t['status']) ?>"><?= e($t['status']) ?></span></td>
<td class="fs-sm text-muted"><?= formatDate($t['created_datetime']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
