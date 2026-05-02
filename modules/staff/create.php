<?php
$pageTitle = 'Add Staff';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$users = $pdo->query("SELECT user_id, full_name, email FROM users WHERE role = 'Support Staff' AND user_id NOT IN (SELECT user_id FROM support_staff WHERE user_id IS NOT NULL) ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $staffNum = trim($_POST['staff_number'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $userId = intval($_POST['user_id'] ?? 0) ?: null;
    $spec = trim($_POST['specialization'] ?? '');
    $shift = trim($_POST['shift_schedule'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    
    if (!empty($staffNum) && !empty($name)) {
        $pdo->prepare("INSERT INTO support_staff (user_id, staff_number, full_name, specialization, shift_schedule, status) VALUES (?,?,?,?,?,?)")
            ->execute([$userId, $staffNum, $name, $spec, $shift, $status]);
        setFlashMessage('success', 'Staff member added.');
        header('Location: index.php'); exit;
    }
}
?>
<div class="page-header"><h1><i class="fas fa-user-plus me-2"></i>Add Support Staff</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="index.php">Staff</a></li><li class="breadcrumb-item active">Add</li></ol></nav></div>
<div class="card"><div class="card-body"><form method="POST"><?= csrfTokenField() ?>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Staff Number *</label><input type="text" name="staff_number" class="form-control" required value="<?= e($_POST['staff_number'] ?? '') ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Link to User Account</label><select name="user_id" class="form-select"><option value="">None</option>
<?php foreach($users as $u): ?><option value="<?= $u['user_id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['email']) ?>)</option><?php endforeach; ?></select></div>
<div class="col-md-6 mb-3"><label class="form-label">Specialization</label><select name="specialization" class="form-select">
<?php foreach(['Hardware','Software','Network','Account Access','Email and Collaboration','Security','Printer','Application Support'] as $sp): ?><option value="<?= $sp ?>"><?= $sp ?></option><?php endforeach; ?></select></div>
<div class="col-md-6 mb-3"><label class="form-label">Shift Schedule</label><input type="text" name="shift_schedule" class="form-control" value="<?= e($_POST['shift_schedule'] ?? '') ?>" placeholder="e.g. Morning (6AM-2PM)"></div>
<div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
</div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create</button><a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
</form></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
