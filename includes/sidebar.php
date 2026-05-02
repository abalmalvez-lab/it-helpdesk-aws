<?php
// Sidebar navigation - role-based menu
$role = getCurrentUserRole();
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-headset"></i></div>
        <div class="brand-text">
            IT Helpdesk
            <small>Ticketing System</small>
        </div>
    </div>
    <nav class="sidebar-menu">
        <!-- Main -->
        <div class="sidebar-section">Main</div>
        <a href="<?= $baseUrl ?>/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <!-- Tickets -->
        <div class="sidebar-section">Tickets</div>
        <?php if ($role === 'End User'): ?>
        <a href="<?= $baseUrl ?>/modules/tickets/create.php" class="sidebar-link <?= ($currentDir === 'tickets' && $currentPage === 'create') ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i> Submit Ticket
        </a>
        <a href="<?= $baseUrl ?>/modules/tickets/index.php" class="sidebar-link <?= ($currentDir === 'tickets' && $currentPage === 'index') ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i> My Tickets
        </a>
        <?php else: ?>
        <a href="<?= $baseUrl ?>/modules/tickets/index.php" class="sidebar-link <?= ($currentDir === 'tickets' && $currentPage === 'index') ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i> All Tickets
        </a>
        <?php if ($role === 'Admin'): ?>
        <a href="<?= $baseUrl ?>/modules/tickets/create.php" class="sidebar-link <?= ($currentDir === 'tickets' && $currentPage === 'create') ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i> Create Ticket
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($role === 'Admin'): ?>
        <!-- Management -->
        <div class="sidebar-section">Management</div>
        <a href="<?= $baseUrl ?>/modules/users/index.php" class="sidebar-link <?= $currentDir === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Users
        </a>
        <a href="<?= $baseUrl ?>/modules/staff/index.php" class="sidebar-link <?= $currentDir === 'staff' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i> Support Staff
        </a>
        <a href="<?= $baseUrl ?>/modules/categories/index.php" class="sidebar-link <?= $currentDir === 'categories' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Categories
        </a>

        <!-- Reports -->
        <div class="sidebar-section">Reports</div>
        <a href="<?= $baseUrl ?>/modules/reports/ticket_volume.php" class="sidebar-link <?= ($currentDir === 'reports' && $currentPage === 'ticket_volume') ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Ticket Volume
        </a>
        <a href="<?= $baseUrl ?>/modules/reports/resolution_time.php" class="sidebar-link <?= ($currentDir === 'reports' && $currentPage === 'resolution_time') ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Resolution Time
        </a>
        <a href="<?= $baseUrl ?>/modules/reports/staff_performance.php" class="sidebar-link <?= ($currentDir === 'reports' && $currentPage === 'staff_performance') ? 'active' : '' ?>">
            <i class="fas fa-user-chart"></i> Staff Performance
        </a>
        <a href="<?= $baseUrl ?>/modules/reports/category_analysis.php" class="sidebar-link <?= ($currentDir === 'reports' && $currentPage === 'category_analysis') ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Category Analysis
        </a>
        <a href="<?= $baseUrl ?>/modules/reports/sla_compliance.php" class="sidebar-link <?= ($currentDir === 'reports' && $currentPage === 'sla_compliance') ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check"></i> SLA Compliance
        </a>

        <!-- AI -->
        <div class="sidebar-section">AI Tools</div>
        <a href="<?= $baseUrl ?>/modules/ai/logs.php" class="sidebar-link <?= ($currentDir === 'ai' && $currentPage === 'logs') ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> AI Logs
        </a>
        <?php endif; ?>

        <?php if ($role === 'Support Staff'): ?>
        <!-- Staff Tools -->
        <div class="sidebar-section">Tools</div>
        <a href="<?= $baseUrl ?>/modules/tickets/index.php?filter=assigned" class="sidebar-link">
            <i class="fas fa-tasks"></i> My Assigned
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= $baseUrl ?>/logout.php" class="sidebar-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
