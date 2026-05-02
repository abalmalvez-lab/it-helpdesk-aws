<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_helper.php';

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$reportData = $input['data'] ?? $input;

$result = generateReportInsights($reportData);
logAIInteraction(getCurrentUserId(), null, 'Report Insights', 'Generate insights for ' . ($input['report_type'] ?? 'report'), json_encode($result['data'] ?? ''), $result['success'] ? 'Success' : 'Failed', $result['error'] ?? null);

echo json_encode($result);
