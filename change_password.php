<?php
$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required.';
    }
    if (empty($newPassword)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $errors[] = 'New password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $errors[] = 'New password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $errors[] = 'New password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        $errors[] = 'New password must contain at least one special character.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation do not match.';
    }
    if ($currentPassword === $newPassword) {
        $errors[] = 'New password must be different from the current password.';
    }

    if (empty($errors)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$newHash, getCurrentUserId()]);

            $success = true;
            setFlashMessage('success', 'Password changed successfully.');
        }
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-key me-2"></i>Change Password</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Change Password</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-lock me-2"></i>Update Your Password</div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-1"></i>
                    Your password has been updated successfully.
                </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfTokenField() ?>

                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-text">Minimum 8 characters with uppercase, lowercase, number, and special character.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                            <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Password</button>
                        <a href="<?= $baseUrl ?>/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="fas fa-shield-alt me-2"></i>Password Requirements</div>
            <div class="card-body fs-sm text-muted">
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1"><i class="fas fa-check-circle text-success me-1"></i>At least 8 characters</p>
                        <p class="mb-1"><i class="fas fa-check-circle text-success me-1"></i>One uppercase letter (A-Z)</p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1"><i class="fas fa-check-circle text-success me-1"></i>One number (0-9)</p>
                        <p class="mb-0"><i class="fas fa-check-circle text-success me-1"></i>One special character (!@#$...)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
