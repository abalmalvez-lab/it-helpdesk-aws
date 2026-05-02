<?php
// Escalation - handled in tickets/view.php
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
header('Location: ' . $baseUrl . '/modules/tickets/index.php?status=Escalated');
exit;
