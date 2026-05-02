<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    if ($ticketId > 0) {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?")->execute([$ticketId]);
        setFlashMessage('success', 'Ticket deleted successfully.');
    }
}
header('Location: index.php');
exit;
