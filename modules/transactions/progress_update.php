<?php
// Progress Update - handled primarily in tickets/view.php
// This redirects to tickets list with in-progress filter
$pageTitle = 'Progress Update';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
header('Location: ' . $baseUrl . '/modules/tickets/index.php?status=In+Progress');
exit;
