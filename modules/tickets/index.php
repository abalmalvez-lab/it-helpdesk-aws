<?php
$pageTitle = 'Tickets';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$pdo = getDBConnection();
$role = getCurrentUserRole();
$userId = getCurrentUserId();
$staffId = getCurrentStaffId();

// Filters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$filter = $_GET['filter'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = [];
$params = [];

if ($role === 'End User') {
    $where[] = 't.user_id = ?';
    $params[] = $userId;
} elseif ($role === 'Support Staff' && $filter === 'assigned') {
    $where[] = 't.assigned_staff_id = ?';
    $params[] = $staffId;
}

if ($search) {
    $where[] = '(t.ticket_number LIKE ? OR t.issue_title LIKE ? OR t.issue_description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter) { $where[] = 't.status = ?'; $params[] = $statusFilter; }
if ($priorityFilter) { $where[] = 't.priority_level = ?'; $params[] = $priorityFilter; }
if ($categoryFilter) { $where[] = 't.category_id = ?'; $params[] = $categoryFilter; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets t $whereSQL");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$pagination = paginate($total, $perPage, $page);

// Fetch
$stmt = $pdo->prepare("SELECT t.*, c.category_name, u.full_name as user_name, s.full_name as staff_name 
    FROM tickets t 
    LEFT JOIN categories c ON t.category_id = c.category_id 
    LEFT JOIN users u ON t.user_id = u.user_id 
    LEFT JOIN support_staff s ON t.assigned_staff_id = s.staff_id 
    $whereSQL 
    ORDER BY t.created_datetime DESC 
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$tickets = $stmt->fetchAll();
$categories = getCategories();
?>

<div class="page-header">
    <h1><i class="fas fa-ticket-alt me-2"></i>Tickets</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Tickets</li>
        </ol>
    </nav>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <?php if ($filter): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tickets..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (['Open','Assigned','In Progress','Escalated','Resolved','Closed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priority</option>
                    <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
                    <option value="<?= $p ?>" <?= $priorityFilter === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= $categoryFilter == $cat['category_id'] ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="<?= $baseUrl ?>/modules/tickets/index.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php if ($role !== 'Support Staff'): ?>
                <a href="<?= $baseUrl ?>/modules/tickets/create.php" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>New</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h5>No tickets found</h5>
            <p>Try adjusting your filters or create a new ticket.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Title</th>
                        <?php if ($role !== 'End User'): ?><th>Submitted By</th><?php endif; ?>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>SLA</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><span class="fw-600"><?= e($t['ticket_number']) ?></span></td>
                        <td>
                            <a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $t['ticket_id'] ?>" class="text-decoration-none fw-600">
                                <?= e(truncateText($t['issue_title'], 45)) ?>
                            </a>
                            <?php if ($t['ai_suggested_category']): ?>
                            <span class="badge badge-ai ms-1" title="AI classified"><i class="fas fa-robot"></i></span>
                            <?php endif; ?>
                        </td>
                        <?php if ($role !== 'End User'): ?>
                        <td class="fs-sm"><?= e($t['user_name']) ?></td>
                        <?php endif; ?>
                        <td><span class="badge bg-light text-dark"><?= e($t['category_name'] ?? '—') ?></span></td>
                        <td><span class="badge <?= getPriorityBadgeClass($t['priority_level']) ?>"><?= e($t['priority_level']) ?></span></td>
                        <td><span class="badge <?= getStatusBadgeClass($t['status']) ?>"><?= e($t['status']) ?></span></td>
                        <td>
                            <?php if (isSLABreached($t['sla_due_datetime'], $t['status'])): ?>
                                <span class="sla-breached"><i class="fas fa-exclamation-circle"></i> Breached</span>
                            <?php elseif ($t['sla_due_datetime'] && !in_array($t['status'], ['Resolved','Closed'])): ?>
                                <span class="sla-ok fs-xs"><?= formatDateTime($t['sla_due_datetime']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="fs-sm text-muted"><?= timeAgo($t['created_datetime']) ?></td>
                        <td>
                            <a href="<?= $baseUrl ?>/modules/tickets/view.php?id=<?= $t['ticket_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent py-2">
            <?= renderPagination($pagination, array_filter(['search' => $search, 'status' => $statusFilter, 'priority' => $priorityFilter, 'category' => $categoryFilter, 'filter' => $filter])) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
