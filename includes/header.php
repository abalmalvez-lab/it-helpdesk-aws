<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/functions.php';

$baseUrl = getBaseUrl();
$currentPage = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$currentDir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'IT Helpdesk') ?> — IT Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<?php if (isLoggedIn()): ?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<?php include __DIR__ . '/sidebar.php'; ?>
<!-- Topbar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-search d-none d-md-block">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search tickets, users..." id="globalSearch" autocomplete="off">
        </div>
    </div>
    <div class="topbar-right">
        <button class="topbar-icon" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="badge-dot"></span>
        </button>
        <div class="dropdown">
            <a class="topbar-user dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="topbar-user-avatar"><?= strtoupper(substr(getCurrentUserName(), 0, 2)) ?></div>
                <div class="topbar-user-info d-none d-sm-block">
                    <div class="name"><?= e(getCurrentUserName()) ?></div>
                    <div class="role"><?= e(getCurrentUserRole()) ?></div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= $baseUrl ?>/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</div>
<!-- Main Content -->
<div class="main-content">
    <div class="content-wrapper">
        <?= renderFlashMessages() ?>
<?php endif; ?>
