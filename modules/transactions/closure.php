<?php
// Closure - handled in tickets/view.php close form
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
header('Location: ' . $baseUrl . '/modules/tickets/index.php?status=Closed');
exit;
