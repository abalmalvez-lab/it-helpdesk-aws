<?php
$pageTitle = 'Users';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

$where = [];
$params = [];
if ($search) { $where[] = '(full_name LIKE ? OR email LIKE ? OR employee_id LIKE ?)'; $params = ["%$search%","%$search%","%$search%"]; }
if ($roleFilter) { $where[] = 'role = ?'; $params[] = $roleFilter; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL"); $total->execute($params); $total = $total->fetchColumn();
$pagination = paginate($total, 15, $page);

$stmt = $pdo->prepare("SELECT * FROM users $whereSQL ORDER BY created_datetime DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-users me-2"></i>Users</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Users</li></ol></nav>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search users..." value="<?= e($search) ?>"></div>
            <div class="col-md-3">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <?php foreach (['Admin','Support Staff','End User'] as $r): ?>
                    <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Search</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <a href="create.php" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Add User</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Employee ID</th><th>Name</th><th>Email</th><th>Department</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-600"><?= e($u['employee_id']) ?></td>
                        <td><?= e($u['full_name']) ?></td>
                        <td class="fs-sm"><?= e($u['email']) ?></td>
                        <td class="fs-sm"><?= e($u['department'] ?? '—') ?></td>
                        <td><span class="badge bg-light text-dark"><?= e($u['role']) ?></span></td>
                        <td><span class="badge <?= $u['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= e($u['status']) ?></span></td>
                        <td>
                            <a href="view.php?id=<?= $u['user_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
                            <a href="edit.php?id=<?= $u['user_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent py-2"><?= renderPagination($pagination, array_filter(['search'=>$search,'role'=>$roleFilter])) ?></div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
