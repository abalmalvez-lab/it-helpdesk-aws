<?php
$pageTitle = 'Edit Category';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);
$pdo = getDBConnection();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?"); $stmt->execute([$id]); $cat = $stmt->fetch();
if (!$cat) { setFlashMessage('error', 'Not found.'); header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sla = max(1, intval($_POST['sla_hours'] ?? 24));
    if (!empty($name)) {
        $pdo->prepare("UPDATE categories SET category_name=?, description=?, sla_hours=? WHERE category_id=?")->execute([$name, $desc, $sla, $id]);
        setFlashMessage('success', 'Category updated.'); header("Location: index.php"); exit;
    }
}
?>
<div class="page-header"><h1><i class="fas fa-edit me-2"></i>Edit Category</h1></div>
<div class="card"><div class="card-body"><form method="POST"><?= csrfTokenField() ?>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Category Name *</label><input type="text" name="category_name" class="form-control" value="<?= e($cat['category_name']) ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">SLA Hours</label><input type="number" name="sla_hours" class="form-control" value="<?= $cat['sla_hours'] ?>" min="1"></div>
<div class="col-12 mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($cat['description']) ?></textarea></div>
</div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button><a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
</form></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
