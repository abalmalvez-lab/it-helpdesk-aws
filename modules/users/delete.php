<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $id = intval($_POST['user_id'] ?? 0);
    if ($id > 0 && $id !== getCurrentUserId()) {
        $pdo = getDBConnection();
        $pdo->prepare("UPDATE users SET status = 'Inactive' WHERE user_id = ?")->execute([$id]);
        setFlashMessage('success', 'User deactivated.');
    }
}
header('Location: index.php'); exit;
