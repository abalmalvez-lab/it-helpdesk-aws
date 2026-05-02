<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_helper.php';

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($title) || empty($description)) {
    echo json_encode(['success' => false, 'error' => 'Title and description required.']);
    exit;
}

$categories = getCategories();
$result = classifyTicket($title, $description, $categories);

logAIInteraction(getCurrentUserId(), null, 'Ticket Classification', "Classify: $title", json_encode($result['data'] ?? $result['error'] ?? ''), $result['success'] ? 'Success' : 'Failed', $result['error'] ?? null);

echo json_encode($result);
