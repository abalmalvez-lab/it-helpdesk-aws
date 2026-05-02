<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['Admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $id = intval($_POST['category_id'] ?? 0);
    if ($id > 0) {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$id]);
        setFlashMessage('success', 'Category deleted.');
    }
}
header('Location: index.php'); exit;
