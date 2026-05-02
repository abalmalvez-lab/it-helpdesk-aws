<?php
// Resolution - handled in tickets/view.php resolve form
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
header('Location: ' . $baseUrl . '/modules/tickets/index.php?status=Resolved');
exit;
