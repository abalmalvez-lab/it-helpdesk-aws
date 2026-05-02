<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['Admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $id = intval($_POST['staff_id'] ?? 0);
    if ($id > 0) {
        $pdo = getDBConnection();
        $pdo->prepare("UPDATE support_staff SET status = 'Inactive' WHERE staff_id = ?")->execute([$id]);
        setFlashMessage('success', 'Staff deactivated.');
    }
}
header('Location: index.php'); exit;
