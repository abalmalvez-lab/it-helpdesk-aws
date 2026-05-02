<?php
$pageTitle = 'Categories';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM tickets t WHERE t.category_id = c.category_id) as ticket_count FROM categories c ORDER BY c.category_name")->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-tags me-2"></i>Categories</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Categories</li></ol></nav></div>

<div class="mb-3"><a href="create.php" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Add Category</a></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Name</th><th>Description</th><th>SLA (Hours)</th><th>Tickets</th><th></th></tr></thead>
<tbody>
<?php foreach($cats as $c): ?>
<tr>
<td class="fw-600"><?= e($c['category_name']) ?></td>
<td class="fs-sm"><?= e(truncateText($c['description'] ?? '',60)) ?></td>
<td><span class="badge bg-light text-dark"><?= $c['sla_hours'] ?>h</span></td>
<td><?= $c['ticket_count'] ?></td>
<td>
<a href="view.php?id=<?= $c['category_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
<a href="edit.php?id=<?= $c['category_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
