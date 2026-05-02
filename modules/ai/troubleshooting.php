<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_helper.php';

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$result = generateTroubleshootingSteps($input);
$ticketId = $input['ticket_id'] ?? null;

logAIInteraction(getCurrentUserId(), $ticketId, 'Troubleshooting', 'Troubleshoot: ' . ($input['issue_title'] ?? ''), json_encode($result['data'] ?? $result['error'] ?? ''), $result['success'] ? 'Success' : 'Failed', $result['error'] ?? null);

echo json_encode($result);
