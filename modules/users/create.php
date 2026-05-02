<?php
$pageTitle = 'Add User';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $empId = trim($_POST['employee_id'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $dept = trim($_POST['department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'End User';
    $status = $_POST['status'] ?? 'Active';
    
    $errors = [];
    if (empty($empId)) $errors[] = 'Employee ID required.';
    if (empty($name)) $errors[] = 'Full name required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    
    // Check uniqueness
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR employee_id = ?");
    $check->execute([$email, $empId]);
    if ($check->fetchColumn() > 0) $errors[] = 'Email or Employee ID already exists.';
    
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (employee_id, full_name, department, email, contact_number, password_hash, role, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$empId, $name, $dept, $email, $contact, $hash, $role, $status]);
        setFlashMessage('success', 'User created successfully.');
        header('Location: index.php');
        exit;
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-user-plus me-2"></i>Add User</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="index.php">Users</a></li><li class="breadcrumb-item active">Add</li></ol></nav>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-1"></i><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrfTokenField() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" class="form-control" value="<?= e($_POST['employee_id'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= e($_POST['contact_number'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?= e($_POST['department'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="End User">End User</option>
                        <option value="Support Staff">Support Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create User</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
