<?php
// Transaction: Assignment - handled in tickets/view.php (Admin assign action)
// This file provides a standalone assignment interface
$pageTitle = 'Ticket Assignment';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();

// Get unassigned open tickets
$openTickets = $pdo->query("SELECT t.*, c.category_name, u.full_name as user_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id WHERE t.status = 'Open' AND t.assigned_staff_id IS NULL ORDER BY FIELD(t.priority_level,'Critical','High','Medium','Low'), t.created_datetime")->fetchAll();
$staff = getActiveStaff();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $staffId = intval($_POST['staff_id'] ?? 0);
    if ($ticketId > 0 && $staffId > 0) {
        $pdo->prepare("UPDATE tickets SET assigned_staff_id = ?, status = 'Assigned', updated_datetime = NOW() WHERE ticket_id = ?")->execute([$staffId, $ticketId]);
        logTicketAction($ticketId, getCurrentUserId(), 'Ticket Assigned', 'Open', 'Assigned', 'Assigned via assignment module');
        setFlashMessage('success', 'Ticket assigned successfully.');
        header('Location: assignment.php'); exit;
    }
}
?>
<div class="page-header"><h1><i class="fas fa-user-plus me-2"></i>Ticket Assignment</h1></div>
<?php if (empty($openTickets)): ?>
<div class="card"><div class="card-body"><div class="empty-state"><i class="fas fa-check-circle"></i><h5>All Clear!</h5><p>No unassigned tickets at this time.</p></div></div></div>
<?php else: ?>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Ticket</th><th>Title</th><th>Category</th><th>Priority</th><th>Submitted By</th><th>Assign To</th><th></th></tr></thead>
<tbody>
<?php foreach($openTickets as $t): ?>
<tr>
<form method="POST"><?= csrfTokenField() ?><input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
<td class="fw-600"><?= e($t['ticket_number']) ?></td>
<td><a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $t['ticket_id'] ?>"><?= e(truncateText($t['issue_title'],40)) ?></a></td>
<td><span class="badge bg-light text-dark"><?= e($t['category_name'] ?? '—') ?></span></td>
<td><span class="badge <?= getPriorityBadgeClass($t['priority_level']) ?>"><?= e($t['priority_level']) ?></span></td>
<td class="fs-sm"><?= e($t['user_name']) ?></td>
<td><select name="staff_id" class="form-select form-select-sm" required style="min-width:160px;">
<option value="">Select...</option>
<?php foreach($staff as $s): ?><option value="<?= $s['staff_id'] ?>"><?= e($s['full_name']) ?> (<?= e($s['specialization']) ?>)</option><?php endforeach; ?>
</select></td>
<td><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></button></td>
</form>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
