<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_helper.php';

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = intval($input['ticket_id'] ?? 0);
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT t.*, c.category_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id WHERE t.ticket_id = ?");
$stmt->execute([$ticketId]); $ticket = $stmt->fetch();

if (!$ticket) { echo json_encode(['success' => false, 'error' => 'Ticket not found.']); exit; }

$logs = $pdo->prepare("SELECT * FROM ticket_logs WHERE ticket_id = ? ORDER BY created_datetime");
$logs->execute([$ticketId]);
$ticket['logs'] = $logs->fetchAll();

$result = summarizeTicket($ticket);
logAIInteraction(getCurrentUserId(), $ticketId, 'Ticket Summary', 'Summarize ticket #' . $ticket['ticket_number'], json_encode($result['data'] ?? ''), $result['success'] ? 'Success' : 'Failed', $result['error'] ?? null);

echo json_encode($result);
