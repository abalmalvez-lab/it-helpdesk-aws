<?php
$pageTitle = 'Edit User';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?"); $stmt->execute([$id]); $user = $stmt->fetch();
if (!$user) { setFlashMessage('error', 'User not found.'); header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $name = trim($_POST['full_name'] ?? '');
    $dept = trim($_POST['department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? $user['status'];
    $password = $_POST['password'] ?? '';
    
    if (!empty($name) && !empty($email)) {
        $sql = "UPDATE users SET full_name=?, department=?, email=?, contact_number=?, role=?, status=?";
        $params = [$name, $dept, $email, $contact, $role, $status];
        if (!empty($password) && strlen($password) >= 6) {
            $sql .= ", password_hash=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE user_id=?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);
        setFlashMessage('success', 'User updated.');
        header("Location: view.php?id=$id"); exit;
    }
}
?>
<div class="page-header"><h1><i class="fas fa-edit me-2"></i>Edit User</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="index.php">Users</a></li><li class="breadcrumb-item active">Edit</li></ol></nav></div>
<div class="card"><div class="card-body">
<form method="POST"><?= csrfTokenField() ?>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="<?= e($user['department']) ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Contact</label><input type="text" name="contact_number" class="form-control" value="<?= e($user['contact_number']) ?>"></div>
<div class="col-md-4 mb-3"><label class="form-label">Role</label><select name="role" class="form-select">
<?php foreach(['Admin','Support Staff','End User'] as $r): ?><option value="<?= $r ?>" <?= $user['role']===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="status" class="form-select">
<option value="Active" <?= $user['status']==='Active'?'selected':'' ?>>Active</option><option value="Inactive" <?= $user['status']==='Inactive'?'selected':'' ?>>Inactive</option></select></div>
<div class="col-md-4 mb-3"><label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label><input type="password" name="password" class="form-control" minlength="6"></div>
</div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button><a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
</form></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
