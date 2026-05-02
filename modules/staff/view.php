<?php
$pageTitle = 'View Staff';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT s.*, u.email, u.department FROM support_staff s LEFT JOIN users u ON s.user_id = u.user_id WHERE s.staff_id = ?");
$stmt->execute([$id]); $staff = $stmt->fetch();
if (!$staff) { setFlashMessage('error', 'Staff not found.'); header('Location: index.php'); exit; }

$tickets = $pdo->prepare("SELECT t.*, c.category_name, u.full_name as user_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id WHERE t.assigned_staff_id = ? ORDER BY t.created_datetime DESC LIMIT 20");
$tickets->execute([$id]); $tickets = $tickets->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-user-shield me-2"></i><?= e($staff['full_name']) ?></h1></div>
<div class="row">
<div class="col-lg-4 mb-3"><div class="card"><div class="card-body">
<h5 class="fw-600"><?= e($staff['full_name']) ?></h5>
<span class="badge <?= $staff['status']==='Active'?'bg-success':'bg-secondary' ?>"><?= e($staff['status']) ?></span>
<hr>
<p class="fs-sm"><strong>Staff #:</strong> <?= e($staff['staff_number']) ?></p>
<p class="fs-sm"><strong>Specialization:</strong> <?= e($staff['specialization'] ?? '—') ?></p>
<p class="fs-sm"><strong>Shift:</strong> <?= e($staff['shift_schedule'] ?? '—') ?></p>
<p class="fs-sm mb-0"><strong>Email:</strong> <?= e($staff['email'] ?? '—') ?></p>
</div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header"><i class="fas fa-ticket-alt me-2"></i>Assigned Tickets (<?= count($tickets) ?>)</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table"><thead><tr><th>Ticket</th><th>Title</th><th>By</th><th>Priority</th><th>Status</th></tr></thead><tbody>
<?php foreach($tickets as $t): ?>
<tr>
<td><a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $t['ticket_id'] ?>" class="fw-600 text-decoration-none"><?= e($t['ticket_number']) ?></a></td>
<td><?= e(truncateText($t['issue_title'],40)) ?></td>
<td class="fs-sm"><?= e($t['user_name']) ?></td>
<td><span class="badge <?= getPriorityBadgeClass($t['priority_level']) ?>"><?= e($t['priority_level']) ?></span></td>
<td><span class="badge <?= getStatusBadgeClass($t['status']) ?>"><?= e($t['status']) ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
