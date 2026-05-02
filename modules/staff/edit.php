<?php
$pageTitle = 'Edit Staff';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM support_staff WHERE staff_id = ?"); $stmt->execute([$id]); $staff = $stmt->fetch();
if (!$staff) { setFlashMessage('error', 'Staff not found.'); header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $name = trim($_POST['full_name'] ?? '');
    $spec = trim($_POST['specialization'] ?? '');
    $shift = trim($_POST['shift_schedule'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    if (!empty($name)) {
        $pdo->prepare("UPDATE support_staff SET full_name=?, specialization=?, shift_schedule=?, status=? WHERE staff_id=?")->execute([$name, $spec, $shift, $status, $id]);
        setFlashMessage('success', 'Staff updated.'); header("Location: view.php?id=$id"); exit;
    }
}
?>
<div class="page-header"><h1><i class="fas fa-edit me-2"></i>Edit Staff</h1></div>
<div class="card"><div class="card-body"><form method="POST"><?= csrfTokenField() ?>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= e($staff['full_name']) ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Specialization</label><select name="specialization" class="form-select">
<?php foreach(['Hardware','Software','Network','Account Access','Email and Collaboration','Security','Printer','Application Support'] as $sp): ?><option value="<?= $sp ?>" <?= $staff['specialization']===$sp?'selected':'' ?>><?= $sp ?></option><?php endforeach; ?></select></div>
<div class="col-md-6 mb-3"><label class="form-label">Shift Schedule</label><input type="text" name="shift_schedule" class="form-control" value="<?= e($staff['shift_schedule']) ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Active" <?= $staff['status']==='Active'?'selected':'' ?>>Active</option><option value="Inactive" <?= $staff['status']==='Inactive'?'selected':'' ?>>Inactive</option></select></div>
</div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button><a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
</form></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
