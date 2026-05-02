<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_helper.php';

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

// Basic rate limiting (1 request per 3 seconds)
$lastChat = $_SESSION['last_chat_time'] ?? 0;
if (time() - $lastChat < 3) {
    echo json_encode(['success' => false, 'error' => 'Please wait a moment before sending another message.']);
    exit;
}
$_SESSION['last_chat_time'] = time();

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a message.']);
    exit;
}

$result = helpdeskChat($message);
logAIInteraction(getCurrentUserId(), null, 'Helpdesk Chat', substr($message, 0, 200), substr($result['content'] ?? ($result['error'] ?? ''), 0, 500), $result['success'] ? 'Success' : 'Failed', $result['error'] ?? null);

if ($result['success']) {
    echo json_encode(['success' => true, 'reply' => $result['content']]);
} else {
    echo json_encode(['success' => false, 'error' => $result['error']]);
}
