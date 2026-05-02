<?php
$pageTitle = 'Support Staff';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE s.full_name LIKE ? OR s.specialization LIKE ? OR s.staff_number LIKE ?" : '';
$params = $search ? ["%$search%","%$search%","%$search%"] : [];

$stmt = $pdo->prepare("SELECT s.*, u.email FROM support_staff s LEFT JOIN users u ON s.user_id = u.user_id $where ORDER BY s.created_datetime DESC");
$stmt->execute($params);
$staff = $stmt->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-user-shield me-2"></i>Support Staff</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Support Staff</li></ol></nav></div>

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
<div class="col-md-5"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search staff..." value="<?= e($search) ?>"></div>
<div class="col-md-7"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Search</button>
<a href="index.php" class="btn btn-sm btn-outline-secondary">Clear</a>
<a href="create.php" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Add Staff</a></div>
</form></div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table">
<thead><tr><th>Staff #</th><th>Name</th><th>Specialization</th><th>Shift</th><th>Status</th><th>Email</th><th></th></tr></thead>
<tbody>
<?php foreach ($staff as $s): ?>
<tr>
<td class="fw-600"><?= e($s['staff_number']) ?></td>
<td><?= e($s['full_name']) ?></td>
<td><span class="badge bg-light text-dark"><?= e($s['specialization'] ?? '—') ?></span></td>
<td class="fs-sm"><?= e($s['shift_schedule'] ?? '—') ?></td>
<td><span class="badge <?= $s['status']==='Active'?'bg-success':'bg-secondary' ?>"><?= e($s['status']) ?></span></td>
<td class="fs-sm"><?= e($s['email'] ?? '—') ?></td>
<td>
<a href="view.php?id=<?= $s['staff_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
<a href="edit.php?id=<?= $s['staff_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
